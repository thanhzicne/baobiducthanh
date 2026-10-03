<?php
require_once __DIR__ . '/auth.php';
admin_require_super();
admin_verify_csrf_or_die();
$adminPage = 'settings.php';
$adminTitle = 'Cài đặt website';
$error = '';
$uploadFields = ['logo' => 'upload_logo', 'watermark_image' => 'upload_watermark'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_rate_limit('settings', 30, 300);
    try {
        $settings = get_all_settings($pdo);
        foreach ($uploadFields as $key => $input) {
            if (empty($_FILES[$input]['name'])) continue;
            $file = $_FILES[$input];
            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_UPLOAD_MB * 1024 * 1024) throw new RuntimeException('Tệp tải lên vượt giới hạn hoặc bị lỗi.');
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            if (!isset($extensions[$mime]) || !getimagesize($file['tmp_name'])) throw new RuntimeException('Chỉ nhận ảnh JPG, PNG, WEBP hoặc GIF hợp lệ.');
            if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) throw new RuntimeException('Không thể tạo thư mục uploads.');
            $filename = $key . '-' . bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . DIRECTORY_SEPARATOR . $filename)) throw new RuntimeException('Không thể lưu ảnh tải lên.');
            $settings[$key] = $filename;
        }
        $save = $pdo->prepare('UPDATE settings SET `value` = ? WHERE `key` = ?');
        foreach ($settings as $key => $oldValue) {
            if (array_key_exists($key, $_POST['settings'] ?? [])) $save->execute([trim((string)$_POST['settings'][$key]), $key]);
        }
        foreach ($uploadFields as $key => $_) {
            if (isset($settings[$key])) $save->execute([$settings[$key], $key]);
        }
        flash_set('admin_msg', 'Đã lưu cài đặt website.', 'success');
        redirect(admin_url('settings.php'));
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}
$rows = $pdo->query('SELECT `key`, `value`, `type`, `group` FROM settings ORDER BY `group`, `key`')->fetchAll();
$groupLabels = ['general' => 'Thông tin chung', 'contact' => 'Liên hệ', 'social' => 'Mạng xã hội', 'seo' => 'SEO', 'quote' => 'Báo giá', 'media' => 'Hình ảnh', 'other' => 'Khác'];
require __DIR__ . '/header.php';
?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form class="card" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
    <?php $lastGroup = null;
    foreach ($rows as $row): if ($row['group'] !== $lastGroup): $lastGroup = $row['group']; ?><h2><?= e($groupLabels[$lastGroup] ?? $lastGroup) ?></h2><?php endif;
                                                                                                                                                                            $key = $row['key']; ?>
        <div class="fg-2"><label for="setting_<?= e($key) ?>"><?= e($key) ?></label>
            <?php if (isset($uploadFields[$key])): ?><input class="inp" type="file" name="<?= e($uploadFields[$key]) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
                <div class="hint">Hiện tại: <?= e($row['value']) ?> · Tối đa <?= (int)MAX_UPLOAD_MB ?> MB</div>
            <?php elseif (in_array($row['type'], ['text', 'html'], true)): ?><textarea class="inp" id="setting_<?= e($key) ?>" name="settings[<?= e($key) ?>]" rows="3"><?= e($row['value']) ?></textarea>
            <?php else: ?><input class="inp" id="setting_<?= e($key) ?>" name="settings[<?= e($key) ?>]" value="<?= e($row['value']) ?>" type="<?= $row['type'] === 'number' ? 'number' : 'text' ?>"><?php endif; ?>
        </div>
    <?php endforeach; ?><button class="btn" type="submit">Lưu cài đặt</button>
</form>
<?php require __DIR__ . '/footer.php'; ?>