<?php
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
}

if (!rate_limit_check('subscribe_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 5, 3600)) {
    json_response(['success' => false, 'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau 1 tiếng!'], 429);
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf($token)) {
    json_response(['success' => false, 'message' => 'Mã xác thực không hợp lệ. Vui lòng tải lại trang!'], 400);
}

$honeypot = trim($_POST['website'] ?? '');
if ($honeypot !== '') {
    json_response(['success' => true, 'message' => 'Đăng ký thành công! Cảm ơn bạn.']);
}

$email = trim($_POST['email'] ?? '');
$name = trim($_POST['name'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 200) {
    json_response(['success' => false, 'message' => 'Vui lòng nhập địa chỉ email hợp lệ!'], 400);
}

if (mb_strlen($name, 'UTF-8') > 200) {
    json_response(['success' => false, 'message' => 'Họ tên quá dài!'], 400);
}

try {
    require_once __DIR__ . '/../includes/db.php';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $check = $pdo->prepare('SELECT id FROM subscribers WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
        json_response(['success' => true, 'message' => 'Email này đã được đăng ký trước đó! Chúng tôi sẽ sớm gửi thông tin ưu đãi cho bạn.']);
    }

    $stmt = $pdo->prepare('INSERT INTO subscribers (email, name, ip_address, is_active, created_at) VALUES (?, ?, ?, 1, NOW())');
    $stmt->execute([$email, $name, $ip]);

    json_response([
        'success' => true,
        'message' => 'Đăng ký thành công! Cảm ơn bạn. Chúng tôi sẽ gửi thông tin ưu đãi sớm nhất qua email.'
    ]);
} catch (Exception $e) {
    error_log('Subscribe error: ' . $e->getMessage());
    if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062')) {
        json_response(['success' => true, 'message' => 'Email này đã được đăng ký! Cảm ơn bạn.']);
    }
    json_response(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau!'], 500);
}
