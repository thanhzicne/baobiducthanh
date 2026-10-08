<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/customer.php';

start_app_session();

// Chỉ coi là AJAX khi client thực sự yêu cầu JSON (fetch gửi header Accept).
// Nếu không, request đến từ form thường và phải redirect/flash như bình thường.
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$isAjax = stripos($accept, 'application/json') !== false
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) json_response(['success' => false, 'message' => 'Phương thức không hợp lệ'], 405);
    redirect(site_url('gio-bao-gia'));
}

$action = $_GET['action'] ?? '';
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf($csrf)) {
    if ($isAjax) json_response(['success' => false, 'message' => 'Mã xác thực không hợp lệ! Vui lòng tải lại trang.'], 400);
    flash_set('cart_status', 'Mã xác thực không hợp lệ. Tải lại trang và thử lại!', 'error');
    redirect(site_url('gio-bao-gia'));
}

if (!rate_limit_check('quote_form_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 60, 600)) {
    if ($isAjax) json_response(['success' => false, 'message' => 'Bạn thao tác quá nhanh. Vui lòng thử lại sau.'], 429);
    flash_set('cart_status', 'Bạn thao tác quá nhanh. Vui lòng thử lại sau.', 'error');
    redirect(site_url('gio-bao-gia'));
}

switch ($action) {
    case 'add':
        handleAdd();
        break;
    case 'update':
        handleUpdate();
        break;
    case 'remove':
        handleRemove();
        break;
    case 'clear':
        handleClear();
        break;
    case 'buy-now':
        handleBuyNow();
        break;
    case 'submit':
        handleSubmit();
        break;
    default:
        if ($isAjax) json_response(['success' => false, 'message' => 'Hành động không xác định'], 400);
        redirect(site_url('gio-bao-gia'));
}

function handleAdd()
{
    global $isAjax, $pdo;
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));

    if ($pid <= 0 || $qty <= 0) {
        if ($isAjax) json_response(['success' => false, 'message' => 'Sản phẩm không hợp lệ!'], 400);
        flash_set('cart_status', 'Sản phẩm không hợp lệ', 'error');
        redirect($_SERVER['HTTP_REFERER'] ?? site_url('gio-bao-gia'));
    }

    try {
        require_once __DIR__ . '/../includes/db.php';
        $st = $pdo->prepare('SELECT id, name, unit, min_order, is_active FROM products WHERE id = ? LIMIT 1');
        $st->execute([$pid]);
        $p = $st->fetch();
        if (!$p || empty($p['is_active'])) {
            if ($isAjax) json_response(['success' => false, 'message' => 'Sản phẩm không tồn tại!'], 404);
            flash_set('cart_status', 'Sản phẩm không tồn tại', 'error');
            redirect($_SERVER['HTTP_REFERER'] ?? site_url());
        }
        $cart = cart_get_items();
        if (isset($cart[$pid])) {
            $cart[$pid]['quantity'] = (int)($cart[$pid]['quantity'] ?? 0) + $qty;
        } else {
            $cart[$pid] = ['quantity' => $qty, 'note' => '', 'added_at' => time()];
        }
        cart_set_items($cart);

        $count = cart_count();
        if ($isAjax) {
            json_response([
                'success' => true,
                'message' => 'Đã thêm ' . $qty . ' ' . e($p['name']) . ' vào giỏ báo giá!',
                'count' => $count
            ]);
        }
        flash_set('cart_status', 'Đã thêm ' . e($p['name']) . ' vào giỏ báo giá!', 'success');
        redirect($_SERVER['HTTP_REFERER'] ?? site_url('gio-bao-gia'));
    } catch (Exception $e) {
        error_log('Quote add error: ' . $e->getMessage());
        if ($isAjax) json_response(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại!'], 500);
        flash_set('cart_status', 'Có lỗi xảy ra. Thử lại!', 'error');
        redirect($_SERVER['HTTP_REFERER'] ?? site_url('gio-bao-gia'));
    }
}

function handleBuyNow()
{
    global $isAjax, $pdo;
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    if ($pid <= 0) {
        flash_set('cart_status', 'Sản phẩm không hợp lệ', 'error');
        redirect(site_url('san-pham'));
    }
    try {
        require_once __DIR__ . '/../includes/db.php';
        require_once __DIR__ . '/../includes/customer.php';
        $st = $pdo->prepare('SELECT id, min_order, is_active FROM products WHERE id = ? LIMIT 1');
        $st->execute([$pid]);
        $p = $st->fetch();
        if (!$p || empty($p['is_active'])) {
            flash_set('cart_status', 'Sản phẩm không tồn tại', 'error');
            redirect(site_url('san-pham'));
        }
        $minOrder = max(1, (int)($p['min_order'] ?? 1));
        if ($qty < $minOrder) $qty = $minOrder;
        // Đặt "mua ngay": giỏ tạm thời chỉ chứa sản phẩm này, các sản phẩm cũ được lưu lại
        $old = cart_get_items();
        $_SESSION['cart_backup'] = $old;
        cart_set_items([$pid => ['quantity' => $qty, 'note' => '', 'added_at' => time()]]);
        redirect(site_url('thanh-toan'));
    } catch (Exception $e) {
        error_log('Buy now error: ' . $e->getMessage());
        flash_set('cart_status', 'Có lỗi xảy ra. Thử lại!', 'error');
        redirect(site_url('san-pham'));
    }
}

function handleUpdate()
{
    global $isAjax;
    $pid = (int)($_POST['id'] ?? 0);
    $qty = max(1, min(1000000, (int)($_POST['quantity'] ?? 1)));
    $cart = cart_get_items();
    if ($pid <= 0 || !isset($cart[$pid])) {
        if ($isAjax) json_response(['success' => false, 'message' => 'Không tìm thấy sản phẩm trong giỏ'], 400);
        redirect(site_url('gio-bao-gia'));
    }
    $cart[$pid]['quantity'] = $qty;
    if (trim((string)($_POST['note'] ?? '')) !== '') {
        $cart[$pid]['note'] = mb_substr(trim((string)$_POST['note']), 0, 500, 'UTF-8');
    }
    cart_set_items($cart);
    if ($isAjax) {
        json_response(['success' => true, 'message' => 'Cập nhật số lượng!', 'count' => cart_count(), 'redirect' => null]);
    }
    flash_set('cart_status', 'Cập nhật số lượng thành công', 'success');
    redirect(site_url('gio-bao-gia'));
}

function handleRemove()
{
    global $isAjax;
    $pid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $cart = cart_get_items();
    if ($pid <= 0 || !isset($cart[$pid])) {
        if ($isAjax) json_response(['success' => false, 'message' => 'Sản phẩm không có trong giỏ'], 400);
        redirect(site_url('gio-bao-gia'));
    }
    unset($cart[$pid]);
    cart_set_items($cart);
    if (empty($cart)) {
        if ($isAjax) json_response(['success' => true, 'message' => 'Đã xóa!', 'count' => 0, 'redirect' => site_url('gio-bao-gia')]);
        redirect(site_url('gio-bao-gia'));
    }
    if ($isAjax) json_response(['success' => true, 'message' => 'Đã xóa sản phẩm!', 'count' => cart_count()]);
    flash_set('cart_status', 'Đã xóa sản phẩm khỏi giỏ', 'success');
    redirect(site_url('gio-bao-gia'));
}

function handleClear()
{
    global $isAjax;
    cart_set_items([]);
    if ($isAjax) json_response(['success' => true, 'message' => 'Đã xóa giỏ hàng!', 'count' => 0, 'redirect' => site_url('gio-bao-gia')]);
    flash_set('cart_status', 'Đã xóa toàn bộ giỏ báo giá', 'info');
    redirect(site_url('gio-bao-gia'));
}

function handleSubmit()
{
    global $isAjax, $pdo;
    if (!customer_is_logged_in()) {
        if ($isAjax) json_response(['success' => false, 'message' => 'Vui lòng đăng nhập để gửi yêu cầu báo giá.', 'redirect' => site_url('dang-nhap?next=gio-bao-gia/checkout')], 401);
        redirect(site_url('dang-nhap?next=gio-bao-gia/checkout'));
    }
    $cart = cart_get_items();
    if (empty($cart)) {
        flash_set('order_checkout', 'Giỏ hàng trống! Vui lòng thêm sản phẩm.', 'error');
        redirect(site_url('gio-bao-gia'));
    }

    // Honeypot: chống spam, coi như gửi thành công nhưng không ghi DB
    $hp = trim($_POST['customer_verify_hp'] ?? '');
    if ($hp !== '') {
        cart_clear_all();
        flash_set('cart_status', 'Đơn hàng đã được ghi nhận. Vui lòng gọi 0901 234 567 nếu cần hỗ trợ gấp.', 'info');
        redirect(site_url('gio-bao-gia'));
    }

    $shipping_name = trim($_POST['shipping_name'] ?? '');
    $shipping_phone = trim($_POST['shipping_phone'] ?? '');
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'cod');
    $note = trim($_POST['note'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if (!in_array($payment_method, ['cod', 'bank_transfer', 'e_wallet'], true)) $payment_method = 'cod';

    $errors = [];
    if (mb_strlen($shipping_name, 'UTF-8') < 2 || mb_strlen($shipping_name, 'UTF-8') > 200) $errors[] = 'Tên người nhận từ 2-200 ký tự';
    if (!preg_match('/^[0-9+\s.\-]{8,20}$/', $shipping_phone)) $errors[] = 'Số điện thoại người nhận không hợp lệ';
    if (mb_strlen($shipping_address, 'UTF-8') < 5 || mb_strlen($shipping_address, 'UTF-8') > 500) $errors[] = 'Địa chỉ nhận hàng từ 5-500 ký tự';
    if (mb_strlen($note, 'UTF-8') > 2000) $errors[] = 'Ghi chú tối đa 2000 ký tự';

    if (!empty($errors)) {
        $_SESSION['order_form'] = compact('shipping_name', 'shipping_phone', 'shipping_address', 'payment_method', 'note');
        flash_set('order_checkout', implode('. ', $errors), 'error');
        redirect(site_url('thanh-toan'));
    }

    try {
        require_once __DIR__ . '/../includes/db.php';
        require_once __DIR__ . '/../includes/customer.php';
        order_ensure_tables($pdo);

        $ids = array_keys($cart);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare('SELECT id, name, image, unit, price_start FROM products WHERE id IN (' . $ph . ') AND is_active = 1');
        $st->execute($ids);
        $products = [];
        while ($row = $st->fetch()) $products[$row['id']] = $row;

        $pdo->beginTransaction();
        $exists = true;
        $tries = 0;
        while ($exists && $tries < 5) {
            $rand = str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $code = 'DH-' . date('Ymd') . '-' . $rand;
            $check = $pdo->prepare('SELECT id FROM orders WHERE order_code = ? LIMIT 1');
            $check->execute([$code]);
            $exists = (bool)$check->fetchColumn();
            $tries++;
        }

        $totalAmount = 0;
        $totalItems = 0;
        foreach ($cart as $pid => $qi) {
            if (!isset($products[$pid])) continue;
            $qty = max(1, (int)($qi['quantity'] ?? 1));
            $totalItems += $qty;
            $totalAmount += (float)($products[$pid]['price_start'] ?? 0) * $qty;
        }

        $customerId = !empty($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
        $customerName = $_SESSION['customer_name'] ?? $shipping_name;
        $customerPhone = $_SESSION['customer_phone'] ?? '';
        try {
            if ($customerId) {
                $cst = $pdo->prepare('SELECT phone FROM customers WHERE id = ?');
                $cst->execute([$customerId]);
                $customerPhone = (string)$cst->fetchColumn();
            }
        } catch (Exception $e) {
        }

        $stmt = $pdo->prepare('INSERT INTO orders (order_code, customer_id, customer_name, customer_phone, shipping_name, shipping_phone, shipping_address, payment_method, note, total_amount, status, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$code, $customerId, $customerName, $customerPhone, $shipping_name, $shipping_phone, $shipping_address, $payment_method, $note !== '' ? $note : null, $totalAmount, 'pending', $ip]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, product_image, unit, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $savedItems = [];
        $insertedCount = 0;
        foreach ($cart as $pid => $qi) {
            if (!isset($products[$pid])) continue;
            $p = $products[$pid];
            $qty = max(1, (int)($qi['quantity'] ?? 1));
            $unitName = match ($p['unit'] ?? 'cai') {
                'cai' => 'cái',
                'thung' => 'thùng',
                'cuon' => 'cuộn',
                'kg' => 'kg',
                'dozen' => 'dozen',
                'met' => 'mét',
                default => $p['unit']
            };
            $price = (float)($p['price_start'] ?? 0);
            $itemStmt->execute([$orderId, $pid, $p['name'], $p['image'] ?? null, $unitName, $qty, $price, $price * $qty]);
            $insertedCount++;
            $savedItems[] = ['name' => $p['name'], 'quantity' => $qty, 'unit' => $unitName, 'price' => $price];
        }

        if ($insertedCount === 0) {
            throw new RuntimeException('Không còn sản phẩm nào hợp lệ trong giỏ.');
        }

        $pdo->commit();
        // Xoá giỏ (DB + session), khôi phục các sản phẩm đã có trước đó nếu đây là "Mua ngay"
        cart_clear_all();
        if (!empty($_SESSION['cart_backup']) && is_array($_SESSION['cart_backup'])) {
            cart_set_items($_SESSION['cart_backup']);
        }
        unset($_SESSION['cart_backup'], $_SESSION['order_form']);

        $paymentLabels = ['cod' => 'Nhận hàng trả tiền (COD)', 'bank_transfer' => 'Thanh toán qua ngân hàng', 'e_wallet' => 'Thanh toán qua ví điện tử'];
        $_SESSION['order_success'] = [
            'code' => $code,
            'shipping_name' => $shipping_name,
            'shipping_phone' => $shipping_phone,
            'shipping_address' => $shipping_address,
            'payment_method' => $payment_method,
            'payment_label' => $paymentLabels[$payment_method] ?? $payment_method,
            'total_amount' => $totalAmount,
            'total_items' => $insertedCount,
            'total_qty' => $totalItems,
            'items' => $savedItems
        ];

        flash_set('order_success', 'Đặt hàng thành công! Mã đơn: <strong>' . e($code) . '</strong>', 'success');
        redirect(site_url('dat-hang-thanh-cong'));
    } catch (Exception $e) {
        error_log('Order submit error: ' . $e->getMessage());
        try {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        } catch (Exception $ee) {
        }
        $_SESSION['order_form'] = compact('shipping_name', 'shipping_phone', 'shipping_address', 'payment_method', 'note');
        flash_set('order_checkout', 'Có lỗi xảy ra. Vui lòng thử lại hoặc gọi 0901 234 567!', 'error');
        redirect(site_url('thanh-toan'));
    }
}
