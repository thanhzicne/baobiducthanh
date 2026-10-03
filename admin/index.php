<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

start_app_session();

$flash = flash_get('admin_login');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Mã xác thực không hợp lệ!';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        try {
            require_once __DIR__ . '/../includes/db.php';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $la = $pdo->prepare('SELECT attempt_count, locked_until FROM login_attempts WHERE ip_address = ? LIMIT 1');
            $la->execute([$ip]);
            $attempt = $la->fetch();
            if ($attempt && $attempt['attempt_count'] >= 5 && strtotime($attempt['locked_until']) > time()) {
                $error = 'Tài khoản bị khóa tạm thời do đăng nhập sai quá nhiều lần. Thử lại sau 15 phút!';
            } else {
                $st = $pdo->prepare('SELECT * FROM admins WHERE username = ? AND is_active = 1 LIMIT 1');
                $st->execute([$username]);
                $admin = $st->fetch();
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
                } else {
                    $cnt = (int)($attempt['attempt_count'] ?? 0) + 1;
                    $locked = $cnt >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
                    $pdo->prepare('INSERT INTO login_attempts (ip_address, attempt_count, locked_until, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE attempt_count = ?, locked_until = ?')
                        ->execute([$ip, $cnt, $locked, $cnt, $locked]);
                    $left = max(0, 5 - $cnt);
                    $error = 'Sai tên đăng nhập hoặc mật khẩu! Còn ' . $left . ' lần thử trước khi khóa.';
                }
            }
        } catch (Exception $e) {
            error_log('Admin login error: ' . $e->getMessage());
            $error = 'Lỗi hệ thống. Vui lòng thử lại sau!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Bao bì Đức Thành</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background: linear-gradient(135deg, #6d4a28, #8B5E34 50%, #E8A33D);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1f1f1f;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #8B5E34, #E8A33D);
            padding: 28px;
            text-align: center;
            color: #fff;
        }

        .login-header h1 {
            font-size: 1.25rem;
            margin: 8px 0 4px;
        }

        .login-header p {
            font-size: 0.88rem;
            opacity: 0.85;
        }

        .logo-icon {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.2);
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            font-weight: 800;
            backdrop-filter: blur(4px);
        }

        .login-body {
            padding: 28px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.88rem;
            line-height: 1.5;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 500;
            margin-bottom: 7px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: 0.2s;
            min-height: 44px;
        }

        .form-control:focus {
            outline: none;
            border-color: #8B5E34;
            box-shadow: 0 0 0 3px rgba(139, 94, 52, 0.12);
        }

        .btn {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            background: #8B5E34;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            font-family: inherit;
            min-height: 46px;
        }

        .btn:hover {
            background: #6d4a28;
            transform: translateY(-1px);
        }

        .hint {
            margin-top: 18px;
            padding: 12px 14px;
            background: #fff8ec;
            border-radius: 10px;
            font-size: 0.8rem;
            color: #7c5a1d;
            line-height: 1.5;
            border: 1px solid #fde68a;
        }

        .hint strong {
            color: #5b4315;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo-icon">
                <img src="<?= e(site_url('assets/images/Logo.png')) ?>" alt="Bao bì Đức Thành">
            </div>
            <h1>Hệ thống quản trị</h1>
            <p>Công ty TNHH Bao bì Đức Thành</p>
        </div>
        <div class="login-body">
            <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <form method="post" autocomplete="off" novalidate>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="u">Tên đăng nhập</label>
                    <input type="text" name="username" id="u" class="form-control" placeholder="admin" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label class="form-label" for="p">Mật khẩu</label>
                    <input type="password" name="password" id="p" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn">Đăng nhập</button>
            </form>
        </div>
    </div>
</body>

</html>