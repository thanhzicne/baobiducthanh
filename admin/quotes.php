<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
admin_verify_csrf_or_die();
$adminPage = 'quotes.php';
$adminTitle = 'Yêu cầu báo giá';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_rate_limit('quotes', 60, 300);
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $allowed = ['pending', 'processing', 'quoted', 'completed', 'cancelled'];
    if ($id > 0 && in_array($status, $allowed, true)) {
        $update = $pdo->prepare('UPDATE quote_requests SET status = ?, admin_note = ? WHERE id = ?');
        $update->execute([$status, trim((string)($_POST['admin_note'] ?? '')), $id]);
        flash_set('admin_msg', 'Đã cập nhật trạng thái báo giá.', 'success');
    }
    redirect(admin_url('quotes.php?id=' . $id));
}
$selectedId = (int)($_GET['id'] ?? 0);
$selected = null;
$items = [];
if ($selectedId) {
    $stmt = $pdo->prepare('SELECT * FROM quote_requests WHERE id = ? LIMIT 1');
    $stmt->execute([$selectedId]);
    $selected = $stmt->fetch();
    if ($selected) {
        $stmt = $pdo->prepare('SELECT * FROM quote_items WHERE quote_request_id = ? ORDER BY id');
        $stmt->execute([$selectedId]);
        $items = $stmt->fetchAll();
    }
}
$quotes = $pdo->query('SELECT id, code, customer_name, company, email, phone, status, total_items, created_at FROM quote_requests ORDER BY created_at DESC LIMIT 300')->fetchAll();
require __DIR__ . '/header.php';
?>
<?php if ($selected): ?><div class="card">
        <div class="fg">
            <h2><?= e($selected['code']) ?> · <?= e($selected['customer_name']) ?></h2><a class="btn btn-outline" target="_blank" rel="noopener" href="<?= e(admin_url('quote-print.php?id=' . (int)$selected['id'])) ?>">Bản in</a>
        </div>
        <p><?= e($selected['company']) ?> · <?= e($selected['email']) ?> · <?= e($selected['phone']) ?></p>
        <p><?= e($selected['address']) ?></p>
        <p>Ghi chú khách hàng: <?= e($selected['note']) ?></p>
        <div class="tblwrap">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn vị</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($items as $item): ?><tr>
                            <td><?= e($item['product_name']) ?></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td><?= e($item['unit']) ?></td>
                            <td><?= e($item['note']) ?></td>
                        </tr><?php endforeach; ?></tbody>
            </table>
        </div>
        <form method="post" class="fg"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$selected['id'] ?>"><label>Trạng thái <select class="inp" name="status"><?php foreach (['pending' => 'Mới', 'processing' => 'Đang xử lý', 'quoted' => 'Đã báo giá', 'completed' => 'Hoàn tất', 'cancelled' => 'Đã hủy'] as $value => $label): ?><option value="<?= e($value) ?>" <?= $selected['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><label>Ghi chú nội bộ <input class="inp" name="admin_note" value="<?= e($selected['admin_note']) ?>"></label><button class="btn" type="submit">Cập nhật</button></form>
    </div><?php elseif ($selectedId): ?><div class="alert alert-error">Không tìm thấy báo giá.</div><?php endif; ?>
<div class="card">
    <div class="tblwrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Sản phẩm</th>
                    <th>Trạng thái</th>
                    <th>Ngày nhận</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php foreach ($quotes as $quote): ?><tr>
                        <td><?= e($quote['code']) ?></td>
                        <td><?= e($quote['customer_name']) ?><?= $quote['company'] ? '<div class="mini">' . e($quote['company']) . '</div>' : '' ?></td>
                        <td><?= e($quote['email']) ?><div class="mini"><?= e($quote['phone']) ?></div>
                        </td>
                        <td><?= (int)$quote['total_items'] ?></td>
                        <td><?= e($quote['status']) ?></td>
                        <td><?= e(format_date($quote['created_at'])) ?></td>
                        <td><a class="btn btn-sm btn-outline" href="<?= e(admin_url('quotes.php?id=' . (int)$quote['id'])) ?>">Chi tiết</a></td>
                    </tr><?php endforeach; ?><?php if (!$quotes): ?><tr>
                        <td colspan="7" class="empty">Chưa có yêu cầu báo giá.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>