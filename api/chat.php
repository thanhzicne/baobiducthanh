<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/customer.php';

header('Cache-Control: no-store, private');
start_app_session();
$isAdmin = ($_GET['side'] ?? '') === 'admin';
if ($isAdmin) {
    require_once __DIR__ . '/../admin/auth.php';
    if (!admin_is_logged_in()) json_response(['success' => false, 'message' => 'Vui lòng đăng nhập quản trị.'], 401);
    $lastActive = $_SESSION['admin_last_active'] ?? $_SESSION['admin_login_time'];
    if (time() - $lastActive > ADMIN_SESSION_TIMEOUT) {
        admin_logout();
        json_response(['success' => false, 'message' => 'Phiên quản trị đã hết hạn.'], 401);
    }
    if (!empty($_SESSION['admin_must_change_password'])) json_response(['success' => false, 'message' => 'Vui lòng đổi mật khẩu quản trị trước.'], 403);
    admin_touch_session();
} elseif (!customer_is_logged_in()) {
    json_response(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng chat.'], 401);
}

customer_ensure_tables($pdo);
$action = $_GET['action'] ?? '';

function chat_customer_conversation(PDO $pdo, int $customerId): int
{
    $stmt = $pdo->prepare("INSERT INTO chat_conversations (customer_id, status, updated_at) VALUES (?, 'open', NOW()) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)");
    $stmt->execute([$customerId]);
    $id = (int)$pdo->lastInsertId();
    if ($id > 0) return $id;
    $stmt = $pdo->prepare('SELECT id FROM chat_conversations WHERE customer_id = ? LIMIT 1');
    $stmt->execute([$customerId]);
    return (int)$stmt->fetchColumn();
}

function chat_mark_admin_read(PDO $pdo, int $conversationId): void
{
    $latestCustomerMessage = $pdo->prepare("SELECT COALESCE(MAX(id), 0) FROM chat_messages WHERE conversation_id = ? AND sender_type = 'customer'");
    $latestCustomerMessage->execute([$conversationId]);
    $lastReadId = (int)$latestCustomerMessage->fetchColumn();
    $update = $pdo->prepare('UPDATE chat_conversations SET last_admin_read_message_id = GREATEST(last_admin_read_message_id, ?) WHERE id = ?');
    $update->execute([$lastReadId, $conversationId]);
}

function chat_unread_total(PDO $pdo): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM chat_messages m JOIN chat_conversations c ON c.id = m.conversation_id WHERE m.sender_type = 'customer' AND m.id > c.last_admin_read_message_id")->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $isAdmin && $action === 'unread_count') {
    json_response(['success' => true, 'unread_total' => chat_unread_total($pdo)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $isAdmin && $action === 'conversations') {
    $rows = $pdo->query("SELECT c.id, c.status, c.updated_at, u.full_name, u.email,
        (SELECT message FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message,
        (SELECT sender_type FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_sender,
        (SELECT COUNT(*) FROM chat_messages unread WHERE unread.conversation_id = c.id AND unread.sender_type = 'customer' AND unread.id > c.last_admin_read_message_id) AS unread_count
        FROM chat_conversations c JOIN customers u ON u.id = c.customer_id
        ORDER BY c.updated_at DESC LIMIT 200")->fetchAll();
    json_response(['success' => true, 'conversations' => $rows, 'unread_total' => chat_unread_total($pdo)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'poll') {
    if ($isAdmin) {
        $conversationId = (int)($_GET['conversation_id'] ?? 0);
    } else {
        $conversationId = chat_customer_conversation($pdo, (int)$_SESSION['customer_id']);
    }
    if ($conversationId < 1) json_response(['success' => false, 'message' => 'Không tìm thấy hội thoại.'], 404);
    if ($isAdmin) {
        $exists = $pdo->prepare('SELECT id FROM chat_conversations WHERE id = ? LIMIT 1');
        $exists->execute([$conversationId]);
        if (!$exists->fetchColumn()) json_response(['success' => false, 'message' => 'Không tìm thấy hội thoại.'], 404);
        chat_mark_admin_read($pdo, $conversationId);
    }
    $afterId = max(0, (int)($_GET['after_id'] ?? 0));
    $stmt = $pdo->prepare('SELECT id, sender_type, message, created_at FROM chat_messages WHERE conversation_id = ? AND id > ? ORDER BY id ASC LIMIT 100');
    $stmt->execute([$conversationId, $afterId]);
    $response = ['success' => true, 'conversation_id' => $conversationId, 'messages' => $stmt->fetchAll()];
    if ($isAdmin) $response['unread_total'] = chat_unread_total($pdo);
    json_response($response);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) json_response(['success' => false, 'message' => 'Mã xác thực không hợp lệ.'], 403);
    if (!rate_limit_check('chat_send_' . ($isAdmin ? 'admin' : 'customer'), 30, 60)) {
        json_response(['success' => false, 'message' => 'Bạn gửi tin nhắn quá nhanh. Vui lòng thử lại sau.'], 429);
    }
    $message = trim((string)($_POST['message'] ?? ''));
    if ($message === '' || mb_strlen($message, 'UTF-8') > 2000) json_response(['success' => false, 'message' => 'Tin nhắn không được để trống và tối đa 2.000 ký tự.'], 422);
    if ($isAdmin) {
        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        $exists = $pdo->prepare('SELECT id FROM chat_conversations WHERE id = ? LIMIT 1');
        $exists->execute([$conversationId]);
        if (!$exists->fetchColumn()) json_response(['success' => false, 'message' => 'Không tìm thấy hội thoại.'], 404);
        $senderType = 'admin';
        $senderId = (int)$_SESSION['admin_id'];
    } else {
        $conversationId = chat_customer_conversation($pdo, (int)$_SESSION['customer_id']);
        $senderType = 'customer';
        $senderId = (int)$_SESSION['customer_id'];
    }
    $stmt = $pdo->prepare('INSERT INTO chat_messages (conversation_id, sender_type, sender_id, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$conversationId, $senderType, $senderId, $message]);
    if ($isAdmin) chat_mark_admin_read($pdo, $conversationId);
    $pdo->prepare("UPDATE chat_conversations SET status = 'open', updated_at = NOW() WHERE id = ?")->execute([$conversationId]);
    json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    if (!$isAdmin) json_response(['success' => false, 'message' => 'Chỉ quản trị viên mới có quyền xoá đoạn chat.'], 403);
    if (!verify_csrf($_POST['csrf_token'] ?? '')) json_response(['success' => false, 'message' => 'Mã xác thực không hợp lệ.'], 403);
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $exists = $pdo->prepare('SELECT id FROM chat_conversations WHERE id = ? LIMIT 1');
    $exists->execute([$conversationId]);
    if (!$exists->fetchColumn()) json_response(['success' => false, 'message' => 'Không tìm thấy hội thoại.'], 404);
    // Xoá toàn bộ tin nhắn rồi xoá hội thoại (tin nhắn có khoá ngoại CASCADE nhưng xoá tường minh cho chắc chắn)
    $pdo->prepare('DELETE FROM chat_messages WHERE conversation_id = ?')->execute([$conversationId]);
    $pdo->prepare('DELETE FROM chat_conversations WHERE id = ?')->execute([$conversationId]);
    json_response(['success' => true, 'unread_total' => chat_unread_total($pdo)]);
}

json_response(['success' => false, 'message' => 'Yêu cầu không hợp lệ.'], 400);
