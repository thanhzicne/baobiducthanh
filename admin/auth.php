<?php

/**
 * Lớp xác thực & phân quyền cho trang quản trị.
 * Mọi file trong /admin đều phải require file này TRƯỚC khi xuất output.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

define('ADMIN_SESSION_TIMEOUT', 3600); // 60 phút không thao tác -> đăng nhập lại
define('ADMIN_LOGIN_MAX_ATTEMPTS', 5);
define('ADMIN_LOGIN_LOCK_SECONDS', 900); // 15 phút

start_app_session();

/** Địa chỉ IP thật (có hỗ trợ Cloudflare / proxy). */
function admin_client_ip()
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (empty($_SERVER[$key])) continue;
        $ip = trim(explode(',', (string)$_SERVER[$key])[0]);
        if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
    }
    return '0.0.0.0';
}

function admin_is_logged_in()
{
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_login_time']);
}

/** Gia hạn phiên khi còn hoạt động. */
function admin_touch_session()
{
    if (admin_is_logged_in()) {
        $_SESSION['admin_last_active'] = time();
    }
}

function admin_require_login()
{
    if (!admin_is_logged_in()) {
        flash_set('admin_msg', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.', 'warning');
        redirect(admin_url());
    }
    $last = $_SESSION['admin_last_active'] ?? $_SESSION['admin_login_time'];
    if (time() - $last > ADMIN_SESSION_TIMEOUT) {
        admin_logout();
        flash_set('admin_msg', 'Phiên làm việc đã hết hạn sau 60 phút không hoạt động. Vui lòng đăng nhập lại.', 'warning');
        redirect(admin_url());
    }
    if (!empty($_SESSION['admin_must_change_password']) && !in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['account.php', 'logout.php'], true)) {
        redirect(admin_url('account.php'));
    }
    admin_touch_session();
}

function admin_logout()
{
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_role'],
        $_SESSION['admin_must_change_password'],
        $_SESSION['admin_login_time'],
        $_SESSION['admin_last_active']
    );
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/** URL trang quản trị, tự nhận diện thư mục để chạy được ở localhost lẫn cPanel. */
function admin_url($path = '')
{
    static $base = null;
    if ($base === null) {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin')), '/');
        // Nếu đang ở trong /admin thì giữ nguyên, nếu ở trang chủ thì gắn /admin
        $base = (substr($scriptDir, -6) === '/admin') ? $scriptDir : $scriptDir . '/admin';
    }
    return rtrim($base, '/') . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function admin_role()
{
    return $_SESSION['admin_role'] ?? 'guest';
}

/** Chỉ superadmin được phép. */
function admin_require_super()
{
    admin_require_login();
    if (admin_role() !== 'superadmin') {
        flash_set('admin_msg', 'Bạn không có quyền thực hiện thao tác này.', 'error');
        redirect(admin_url('dashboard.php'));
    }
}

/**
 * Bắt buộc CSRF cho mọi request thay đổi dữ liệu (POST/PUT/DELETE).
 * Gọi ở đầu mỗi file admin sau khi require auth.php.
 */
function admin_verify_csrf_or_die()
{
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) return;
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!verify_csrf($token)) {
        http_response_code(403);
        exit('Mã xác thực không hợp lệ. Vui lòng tải lại trang.');
    }
}

/**
 * Chống lạm dụng form admin khi bị lộ link (không phải user thật).
 * Giới hạn theo phiên + IP.
 */
function admin_check_rate_limit(string $key, int $max = 30, int $window = 300): bool
{
    if (!rate_limit_check('admin_' . $key, $max, $window)) {
        flash_set('admin_msg', 'Bạn thao tác quá nhanh. Vui lòng đợi ít phút rồi thử lại.', 'error');
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('dashboard.php'));
    }
    return true;
}
