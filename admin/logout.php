<?php
require_once __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}
admin_verify_csrf_or_die();
admin_logout();
unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_username'], $_SESSION['customer_email'], $_SESSION['customer_last_active']);
flash_set('customer_auth', 'Bạn đã đăng xuất.', 'success');
redirect(site_url());
