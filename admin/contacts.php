<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
admin_verify_csrf_or_die();
$adminPage = 'contacts.php';
$adminTitle = 'Liên hệ khách hàng';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_rate_limit('contacts', 60, 300);
    $stmt = $pdo->prepare('UPDATE contacts SET is_read = 1 WHERE id = ?');
    $stmt->execute([(int)($_POST['id'] ?? 0)]);
    flash_set('admin_msg', 'Đã đánh dấu liên hệ đã đọc.', 'success');
    redirect(admin_url('contacts.php'));
}
$contacts = $pdo->query('SELECT * FROM contacts ORDER BY is_read ASC, created_at DESC LIMIT 300')->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="card">
    <div class="tblwrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Người gửi</th>
                    <th>Chủ đề / Nội dung</th>
                    <th>Liên hệ</th>
                    <th>Ngày nhận</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php foreach ($contacts as $contact): ?><tr>
                        <td><?= e($contact['name']) ?><div class="mini"><?= e($contact['company']) ?></div>
                        </td>
                        <td><?= e($contact['subject']) ?><div class="mini"><?= nl2br(e($contact['message'])) ?></div>
                        </td>
                        <td><?= e($contact['email']) ?><div class="mini"><?= e($contact['phone']) ?></div>
                        </td>
                        <td><?= e(format_date($contact['created_at'])) ?></td>
                        <td><?= $contact['is_read'] ? 'Đã đọc' : 'Chưa đọc' ?></td>
                        <td><?php if (!$contact['is_read']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$contact['id'] ?>"><button class="btn btn-sm" type="submit">Đánh dấu đã đọc</button></form><?php endif; ?></td>
                    </tr><?php endforeach; ?><?php if (!$contacts): ?><tr>
                        <td colspan="6" class="empty">Chưa có liên hệ.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>