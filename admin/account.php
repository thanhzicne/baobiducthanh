<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
admin_verify_csrf_or_die();
$adminPage = 'account.php';
$adminTitle = 'Tài khoản';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_rate_limit('account', 10, 300);
    $current = (string)($_POST['current_password'] ?? '');
    $password = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    $stmt = $pdo->prepare('SELECT password FROM admins WHERE id = ? AND is_active = 1');
    $stmt->execute([(int)$_SESSION['admin_id']]);
    $hash = $stmt->fetchColumn();
    if (!$hash || !password_verify($current, $hash)) $error = 'Mật khẩu hiện tại không chính xác.';
    elseif (strlen($password) < 12) $error = 'Mật khẩu mới cần tối thiểu 12 ký tự.';
    elseif ($password !== $confirm) $error = 'Mật khẩu xác nhận không khớp.';
    else {
        $update = $pdo->prepare('UPDATE admins SET password = ?, must_change_password = 0 WHERE id = ?');
        $update->execute([password_hash($password, PASSWORD_DEFAULT), (int)$_SESSION['admin_id']]);
        $_SESSION['admin_must_change_password'] = false;
        flash_set('admin_msg', 'Đã đổi mật khẩu thành công.', 'success');
        redirect(admin_url('account.php'));
    }
}
require __DIR__ . '/header.php';
?>
<div class="card">
    <h2>Đổi mật khẩu</h2><?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="fg-2"><label for="current_password">Mật khẩu hiện tại</label><input class="inp" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
        <div class="fg-2"><label for="new_password">Mật khẩu mới (tối thiểu 12 ký tự)</label><input class="inp" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="12" required></div>
        <div class="fg-2"><label for="confirm_password">Nhập lại mật khẩu mới</label><input class="inp" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="12" required></div><button class="btn" type="submit">Cập nhật mật khẩu</button>
    </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>