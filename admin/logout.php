<?php
require_once __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}
admin_verify_csrf_or_die();
admin_logout();
flash_set('admin_msg', 'Bạn đã đăng xuất.', 'success');
redirect(admin_url());
