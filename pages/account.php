<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/customer.php';
start_app_session();

$mode = $_GET['mode'] ?? 'login';
$error = '';
$requestedNext = trim((string)($_POST['next'] ?? $_GET['next'] ?? ''));
$next = '';
if ($requestedNext === 'gio-bao-gia/checkout' || $requestedNext === '__home__') {
    $next = $requestedNext;
} elseif (preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_-]*$#', $requestedNext)) {
    $next = $requestedNext;
}
$customerRedirect = $next === '__home__' ? site_url() : site_url($next ?: 'gio-bao-gia/checkout');

$profile = null;
if ($mode === 'profile') {
    customer_ensure_tables($pdo);
    if (!customer_is_logged_in()) redirect(site_url('dang-nhap?next=tai-khoan'));
    $profileStmt = $pdo->prepare('SELECT id, full_name, username, email, phone, company, address FROM customers WHERE id = ? AND is_active = 1 LIMIT 1');
    $profileStmt->execute([(int)$_SESSION['customer_id']]);
    $profile = $profileStmt->fetch();
    if (!$profile) {
        customer_logout();
        redirect(site_url('dang-nhap'));
    }
}

if ($mode === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
        customer_logout();
        flash_set('customer_auth', 'Bạn đã đăng xuất.', 'success');
    }
    redirect(site_url());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'profile') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Mã xác thực không hợp lệ. Vui lòng tải lại trang.';
    } elseif (!rate_limit_check('customer_profile', 20, 300)) {
        $error = 'Bạn thao tác quá nhanh. Vui lòng thử lại sau.';
    } else {
        try {
            $action = $_POST['account_action'] ?? '';
            if ($action === 'update_profile') {
                $fullName = trim((string)($_POST['full_name'] ?? ''));
                $username = mb_strtolower(trim((string)($_POST['username'] ?? '')), 'UTF-8');
                $email = mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8');
                $phone = trim((string)($_POST['phone'] ?? ''));
                $company = trim((string)($_POST['company'] ?? ''));
                $address = trim((string)($_POST['address'] ?? ''));
                if (mb_strlen($fullName, 'UTF-8') < 2 || mb_strlen($fullName, 'UTF-8') > 200) throw new RuntimeException('Họ tên phải từ 2 đến 200 ký tự.');
                if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) throw new RuntimeException('Tên tài khoản cần từ 3-50 ký tự, chỉ gồm chữ, số, dấu chấm, gạch ngang hoặc gạch dưới.');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 200) throw new RuntimeException('Email không hợp lệ.');
                if (!preg_match('/^[0-9+\s.\-]{8,30}$/', $phone)) throw new RuntimeException('Số điện thoại không hợp lệ.');
                if (mb_strlen($company, 'UTF-8') > 200 || mb_strlen($address, 'UTF-8') > 500) throw new RuntimeException('Thông tin công ty hoặc địa chỉ vượt quá độ dài cho phép.');
                $stmt = $pdo->prepare('UPDATE customers SET full_name = ?, username = ?, email = ?, phone = ?, company = ?, address = ? WHERE id = ?');
                $stmt->execute([$fullName, $username, $email, $phone, $company !== '' ? $company : null, $address !== '' ? $address : null, (int)$_SESSION['customer_id']]);
                customer_set_session(['id' => $_SESSION['customer_id'], 'full_name' => $fullName, 'username' => $username, 'email' => $email]);
                flash_set('customer_auth', 'Thông tin cá nhân đã được cập nhật.', 'success');
                redirect(site_url('tai-khoan'));
            }
            if ($action === 'change_password') {
                $currentPassword = (string)($_POST['current_password'] ?? '');
                $newPassword = (string)($_POST['new_password'] ?? '');
                $confirmPassword = (string)($_POST['confirm_password'] ?? '');
                $passwordStmt = $pdo->prepare('SELECT password_hash FROM customers WHERE id = ? LIMIT 1');
                $passwordStmt->execute([(int)$_SESSION['customer_id']]);
                $passwordHash = $passwordStmt->fetchColumn();
                if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) throw new RuntimeException('Mật khẩu hiện tại chưa chính xác.');
                if (strlen($newPassword) < 8 || strlen($newPassword) > 200) throw new RuntimeException('Mật khẩu mới cần có ít nhất 8 ký tự.');
                if ($newPassword !== $confirmPassword) throw new RuntimeException('Mật khẩu xác nhận không khớp.');
                $pdo->prepare('UPDATE customers SET password_hash = ? WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_DEFAULT), (int)$_SESSION['customer_id']]);
                $profile['username'] = $_SESSION['customer_username'] ?? $profile['username'];
                customer_set_session($profile);
                flash_set('customer_auth', 'Mật khẩu đã được đổi thành công.', 'success');
                redirect(site_url('tai-khoan'));
            }
            throw new RuntimeException('Thao tác tài khoản không hợp lệ.');
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000' ? 'Tên tài khoản hoặc email đã được sử dụng.' : 'Không thể cập nhật tài khoản lúc này.';
            error_log('Customer profile error: ' . $exception->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'forgot') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Mã xác thực không hợp lệ. Vui lòng tải lại trang.';
    } elseif (!rate_limit_check('customer_password_reset', 5, 900)) {
        $error = 'Bạn thử đặt lại mật khẩu quá nhiều lần. Vui lòng thử lại sau.';
    } else {
        try {
            customer_ensure_tables($pdo);
            $username = mb_strtolower(trim((string)($_POST['username'] ?? '')), 'UTF-8');
            $newPassword = (string)($_POST['new_password'] ?? '');
            $confirmPassword = (string)($_POST['confirm_password'] ?? '');
            if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) throw new RuntimeException('Tên tài khoản không hợp lệ.');
            if (strlen($newPassword) < 8 || strlen($newPassword) > 200) throw new RuntimeException('Mật khẩu mới cần có ít nhất 8 ký tự.');
            if ($newPassword !== $confirmPassword) throw new RuntimeException('Mật khẩu xác nhận không khớp.');
            $stmt = $pdo->prepare('UPDATE customers SET password_hash = ? WHERE username = ? AND is_active = 1');
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $username]);
            flash_set('customer_auth', 'Nếu tên tài khoản tồn tại, mật khẩu mới đã được cập nhật. Bạn có thể đăng nhập.', 'success');
            redirect(site_url('dang-nhap'));
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            $error = 'Không thể đặt lại mật khẩu lúc này. Vui lòng thử lại.';
            error_log('Customer password reset error: ' . $exception->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($mode, ['profile', 'forgot'], true)) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Mã xác thực không hợp lệ. Vui lòng tải lại trang.';
    } elseif (!rate_limit_check('customer_auth', 10, 600)) {
        $error = 'Bạn thao tác quá nhanh. Vui lòng thử lại sau ít phút.';
    } else {
        $username = mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8');
        $email = $username;
        $password = (string)($_POST['password'] ?? '');
        try {
            if ($mode !== 'register' && $username === 'admin') {
                require_once __DIR__ . '/../admin/auth.php';
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $attemptStmt = $pdo->prepare('SELECT attempt_count, locked_until FROM login_attempts WHERE ip_address = ? LIMIT 1');
                $attemptStmt->execute([$ip]);
                $attempt = $attemptStmt->fetch();
                if ($attempt && $attempt['attempt_count'] >= 5 && strtotime($attempt['locked_until']) > time()) {
                    $error = 'Tài khoản bị khóa tạm thời do đăng nhập sai quá nhiều lần. Thử lại sau 15 phút!';
                } else {
                    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? AND is_active = 1 LIMIT 1');
                    $stmt->execute([$username]);
                    $admin = $stmt->fetch();
                    if ($admin && password_verify($password, $admin['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = (int)$admin['id'];
                        $_SESSION['admin_name'] = $admin['full_name'];
                        $_SESSION['admin_role'] = $admin['role'];
                        $_SESSION['admin_login_time'] = time();
                        $_SESSION['admin_last_active'] = time();
                        $_SESSION['admin_must_change_password'] = (bool)$admin['must_change_password'];
                        $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
                        $pdo->prepare('UPDATE admins SET last_login = NOW(), last_login_ip = ? WHERE id = ?')->execute([$ip, (int)$admin['id']]);
                        flash_set('admin_msg', 'Đăng nhập thành công. Chào mừng ' . e($admin['full_name']) . '!', 'success');
                        redirect(site_url('admin/dashboard.php'));
                    }
                    $count = (int)($attempt['attempt_count'] ?? 0) + 1;
                    $lockedUntil = $count >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
                    $pdo->prepare('INSERT INTO login_attempts (ip_address, attempt_count, locked_until, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE attempt_count = ?, locked_until = ?')
                        ->execute([$ip, $count, $lockedUntil, $count, $lockedUntil]);
                    $error = 'Sai tên đăng nhập hoặc mật khẩu! Còn ' . max(0, 5 - $count) . ' lần thử trước khi khóa.';
                }
            } else {
                customer_ensure_tables($pdo);
            }
            if ($error === '' && $mode === 'register') {
                $name = trim((string)($_POST['full_name'] ?? ''));
                $registeredUsername = mb_strtolower(trim((string)($_POST['username'] ?? '')), 'UTF-8');
                $phone = trim((string)($_POST['phone'] ?? ''));
                $company = trim((string)($_POST['company'] ?? ''));
                if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 200) throw new RuntimeException('Họ tên phải từ 2 đến 200 ký tự.');
                if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $registeredUsername)) throw new RuntimeException('Tên tài khoản cần từ 3-50 ký tự, chỉ gồm chữ, số, dấu chấm, gạch ngang hoặc gạch dưới.');
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email, 'UTF-8') > 200) throw new RuntimeException('Email không hợp lệ.');
                if (strlen($password) < 8 || strlen($password) > 200) throw new RuntimeException('Mật khẩu cần có ít nhất 8 ký tự.');
                if (!preg_match('/^[0-9+\s.\-]{8,30}$/', $phone)) throw new RuntimeException('Số điện thoại không hợp lệ.');
                $stmt = $pdo->prepare('INSERT INTO customers (full_name, username, email, password_hash, phone, company) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $registeredUsername, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $company !== '' ? $company : null]);
                customer_set_session(['id' => $pdo->lastInsertId(), 'full_name' => $name, 'username' => $registeredUsername, 'email' => $email]);
                redirect($customerRedirect);
            }
            if ($error === '' && $mode !== 'register') {
                $stmt = $pdo->prepare('SELECT * FROM customers WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
                $stmt->execute([$username, $email]);
                $customer = $stmt->fetch();
                if (!$customer || !password_verify($password, $customer['password_hash'])) throw new RuntimeException('Email hoặc mật khẩu chưa chính xác.');
                if (password_needs_rehash($customer['password_hash'], PASSWORD_DEFAULT)) {
                    $pdo->prepare('UPDATE customers SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $customer['id']]);
                }
                $pdo->prepare('UPDATE customers SET last_login = NOW() WHERE id = ?')->execute([$customer['id']]);
                customer_set_session($customer);
                redirect($customerRedirect);
            }
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            $error = $exception->getCode() === '23000' ? 'Tên tài khoản hoặc email đã được sử dụng.' : 'Không thể xử lý tài khoản lúc này. Vui lòng thử lại.';
            error_log('Customer account error: ' . $exception->getMessage());
        }
    }
}

$currentPage = 'account';
$pageTitle = [
    'register' => 'Đăng ký tài khoản',
    'profile' => 'Trang cá nhân',
    'forgot' => 'Quên mật khẩu',
    'login' => 'Đăng nhập'
][$mode] ?? 'Tài khoản';
$pageMetaDesc = 'Đăng nhập hoặc tạo tài khoản khách hàng Bao bì Đức Thành.';
$breadcrumb = [['label' => $pageTitle]];
$jsonLd = null;
$flash = flash_get('customer_auth');
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>
<section class="section section-sm">
    <div class="container" style="max-width:560px">
        <div class="checkout-form">
            <h1><?= e($pageTitle) ?></h1>
            <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($mode === 'profile' && customer_is_logged_in()): ?>
                <h2>Thông tin cá nhân</h2>
                <form method="post" action="<?= e(site_url('tai-khoan')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="account_action" value="update_profile">
                    <div class="form-group"><label class="form-label" for="profile_name">Họ và tên</label><input class="form-control" id="profile_name" name="full_name" required minlength="2" maxlength="200" value="<?= e($_POST['full_name'] ?? $profile['full_name']) ?>"></div>
                    <div class="form-group"><label class="form-label" for="profile_username">Tên tài khoản</label><input class="form-control" id="profile_username" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" value="<?= e($_POST['username'] ?? $profile['username']) ?>"></div>
                    <div class="form-group"><label class="form-label" for="profile_email">Email</label><input class="form-control" id="profile_email" name="email" type="email" required maxlength="200" value="<?= e($_POST['email'] ?? $profile['email']) ?>"></div>
                    <div class="form-group"><label class="form-label" for="profile_phone">Số điện thoại</label><input class="form-control" id="profile_phone" name="phone" type="tel" required maxlength="30" value="<?= e($_POST['phone'] ?? $profile['phone']) ?>"></div>
                    <div class="form-group"><label class="form-label" for="profile_company">Công ty</label><input class="form-control" id="profile_company" name="company" maxlength="200" value="<?= e($_POST['company'] ?? $profile['company']) ?>"></div>
                    <div class="form-group"><label class="form-label" for="profile_address">Địa chỉ</label><input class="form-control" id="profile_address" name="address" maxlength="500" value="<?= e($_POST['address'] ?? $profile['address']) ?>"></div>
                    <button type="submit" class="btn btn-primary">Lưu thông tin</button>
                </form>
                <h2 style="margin-top:28px">Đổi mật khẩu</h2>
                <form method="post" action="<?= e(site_url('tai-khoan')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="account_action" value="change_password">
                    <div class="form-group"><label class="form-label" for="current_password">Mật khẩu hiện tại</label><input class="form-control" id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
                    <div class="form-group"><label class="form-label" for="new_password">Mật khẩu mới</label><input class="form-control" id="new_password" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
                    <div class="form-group"><label class="form-label" for="confirm_password">Nhập lại mật khẩu mới</label><input class="form-control" id="confirm_password" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"></div>
                    <button type="submit" class="btn btn-outline">Đổi mật khẩu</button>
                </form>
            <?php elseif (customer_is_logged_in()): ?>
                <p>Xin chào <?= e($_SESSION['customer_name'] ?? '') ?>.</p>
                <a class="btn btn-primary" href="<?= e(site_url('tai-khoan')) ?>">Mở trang cá nhân</a>
            <?php elseif ($mode === 'forgot'): ?>
                <form method="post" action="<?= e(site_url('quen-mat-khau')) ?>">
                    <?= csrf_field() ?>
                    <div class="form-group"><label class="form-label" for="reset_username">Tên tài khoản</label><input class="form-control" id="reset_username" name="username" required minlength="3" maxlength="50" autocomplete="username"></div>
                    <div class="form-group"><label class="form-label" for="reset_password">Mật khẩu mới</label><input class="form-control" id="reset_password" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
                    <div class="form-group"><label class="form-label" for="reset_confirm">Nhập lại mật khẩu mới</label><input class="form-control" id="reset_confirm" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"></div>
                    <button type="submit" class="btn btn-primary btn-block">Cập nhật mật khẩu</button>
                </form>
                <p style="margin-top:18px;text-align:center"><a href="<?= e(site_url('dang-nhap')) ?>">Quay lại đăng nhập</a></p>
            <?php else: ?>
                <form method="post" action="<?= e(site_url($mode === 'register' ? 'dang-ky' : 'dang-nhap')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="next" value="<?= e($next) ?>">
                    <?php if ($mode === 'register'): ?>
                        <div class="form-group"><label class="form-label" for="account_name">Họ và tên</label><input class="form-control" id="account_name" name="full_name" required minlength="2" maxlength="200" autocomplete="name" value="<?= e($_POST['full_name'] ?? '') ?>"></div>
                        <div class="form-group"><label class="form-label" for="account_username">Tên tài khoản</label><input class="form-control" id="account_username" name="username" required minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>"></div>
                        <div class="form-group"><label class="form-label" for="account_phone">Số điện thoại</label><input class="form-control" id="account_phone" name="phone" type="tel" required pattern="[0-9+\s.\-]{8,30}" autocomplete="tel" value="<?= e($_POST['phone'] ?? '') ?>"></div>
                        <div class="form-group"><label class="form-label" for="account_company">Công ty</label><input class="form-control" id="account_company" name="company" maxlength="200" autocomplete="organization" value="<?= e($_POST['company'] ?? '') ?>"></div>
                    <?php endif; ?>
                    <div class="form-group"><label class="form-label" for="account_email"><?= $mode === 'register' ? 'Email' : 'Email hoặc tên đăng nhập' ?></label><input class="form-control" id="account_email" name="email" type="<?= $mode === 'register' ? 'email' : 'text' ?>" required maxlength="200" autocomplete="<?= $mode === 'register' ? 'email' : 'username' ?>" value="<?= e($_POST['email'] ?? '') ?>"></div>
                    <div class="form-group"><label class="form-label" for="account_password">Mật khẩu</label><input class="form-control" id="account_password" name="password" type="password" required minlength="8" autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>"></div>
                    <button type="submit" class="btn btn-primary btn-block"><?= $mode === 'register' ? 'Tạo tài khoản' : 'Đăng nhập' ?></button>
                </form>
                <p style="margin-top:18px;text-align:center">
                    <?php if ($mode === 'register'): ?>
                        Đã có tài khoản?
                        <a href="<?= e(site_url('dang-nhap' . ($next ? '?next=' . rawurlencode($next) : ''))) ?>">Đăng nhập</a>
                    <?php else: ?>
                        Chưa có tài khoản?
                        <a href="<?= e(site_url('dang-ky' . ($next ? '?next=' . rawurlencode($next) : ''))) ?>">Đăng ký</a>

                        <br>

                        <a href="<?= e(site_url('quen-mat-khau')) ?>">Quên mật khẩu?</a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>