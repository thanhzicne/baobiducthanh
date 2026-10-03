<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    flash_set('contact_form', 'Phương thức không hợp lệ', 'error');
    redirect(site_url('lien-he'));
}

if (!verify_csrf($_POST['csrf_token'] ?? '')) {
    flash_set('contact_form', 'Mã xác thực không hợp lệ. Vui lòng tải lại trang!', 'error');
    redirect(site_url('lien-he'));
}

if (!rate_limit_check('contact_form_' . ($_SERVER['REMOTE_ADDR'] ?? 'u'), 5, 3600)) {
    flash_set('contact_form', 'Quá nhiều yêu cầu. Vui lòng thử lại sau 1 tiếng!', 'error');
    redirect(site_url('lien-he'));
}

$hp = trim($_POST['company_hp'] ?? $_POST['website_url_hp'] ?? '');
if ($hp !== '') {
    flash_set('contact_form', 'Gửi liên hệ thành công! Chúng tôi sẽ liên hệ sớm nhất.', 'success');
    if (isset($_POST['company_hp'])) {
        flash_set('home_contact', 'Gửi thành công! Chúng tôi sẽ liên hệ sớm.', 'success');
        redirect(site_url());
    }
    redirect(site_url('lien-he'));
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$company = trim($_POST['company'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$errors = [];
if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 200) $errors[] = 'Họ tên từ 2-200 ký tự';
if (!preg_match('/^[0-9+\s.-]{8,20}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ (8-20 ký tự)';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 200) $errors[] = 'Email không hợp lệ';
if (mb_strlen($message, 'UTF-8') < 10 || mb_strlen($message, 'UTF-8') > 2000) $errors[] = 'Nội dung từ 10-2000 ký tự';
if (mb_strlen($company, 'UTF-8') > 200) $errors[] = 'Tên công ty tối đa 200 ký tự';
if (mb_strlen($subject, 'UTF-8') > 255) $errors[] = 'Tiêu đề tối đa 255 ký tự';

if (!empty($errors)) {
    flash_set('contact_form', implode('. ', $errors), 'error');
    redirect(site_url('lien-he'));
}

try {
    require_once __DIR__ . '/../includes/db.php';
    $stmt = $pdo->prepare('INSERT INTO contacts (name, email, phone, company, subject, message, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$name, $email, $phone, $company, $subject, $message, $ip]);

    if (MAIL_ENABLED && !empty($settings['contact_email_to'])) {
        // mail logic could go here
    }

    $successMsg = 'Gửi yêu cầu thành công! Chúng tôi sẽ liên hệ lại qua SĐT ' . e($phone) . ' trong vòng 60 phút làm việc.';
    $redirectUrl = (isset($_POST['company_hp'])) ? site_url() : site_url('lien-he');
    $flashKey = (isset($_POST['company_hp'])) ? 'home_contact' : 'contact_form';
    flash_set($flashKey, $successMsg, 'success');
    redirect($redirectUrl);
} catch (Exception $e) {
    error_log('Contact form error: ' . $e->getMessage());
    flash_set('contact_form', 'Có lỗi xảy ra. Vui lòng thử lại hoặc gọi 0901 234 567!', 'error');
    redirect(site_url('lien-he'));
}
