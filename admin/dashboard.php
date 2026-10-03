<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
$adminPage = 'dashboard.php';
$adminTitle = 'Tổng quan';
$stats = [
    ['Báo giá mới', "SELECT COUNT(*) FROM quote_requests WHERE status = 'pending'", 'quotes.php'],
    ['Liên hệ chưa đọc', 'SELECT COUNT(*) FROM contacts WHERE is_read = 0', 'contacts.php'],
    ['Người đăng ký', 'SELECT COUNT(*) FROM subscribers WHERE is_active = 1', 'subscribers.php'],
    ['Sản phẩm đang bán', 'SELECT COUNT(*) FROM products WHERE is_active = 1', 'products.php'],
];
foreach ($stats as &$stat) $stat[] = (int)$pdo->query($stat[1])->fetchColumn();
unset($stat);
$latest = $pdo->query('SELECT id, code, customer_name, company, status, created_at FROM quote_requests ORDER BY created_at DESC LIMIT 8')->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="stats"><?php foreach ($stats as $stat): ?><div class="stat">
            <div><b><?= $stat[3] ?></b><a href="<?= e(admin_url($stat[2])) ?>"><?= e($stat[0]) ?></a></div>
        </div><?php endforeach; ?></div>
<div class="card">
    <div class="fg">
        <h2>Yêu cầu báo giá gần đây</h2><a class="btn btn-sm btn-outline" href="<?= e(admin_url('quotes.php')) ?>">Xem tất cả</a>
    </div>
    <div class="tblwrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Khách hàng</th>
                    <th>Trạng thái</th>
                    <th>Ngày nhận</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php foreach ($latest as $quote): ?><tr>
                        <td><?= e($quote['code']) ?></td>
                        <td><?= e($quote['customer_name']) ?><?= $quote['company'] ? '<div class="mini">' . e($quote['company']) . '</div>' : '' ?></td>
                        <td><?= e($quote['status']) ?></td>
                        <td><?= e(format_date($quote['created_at'])) ?></td>
                        <td><a class="btn btn-sm btn-outline" href="<?= e(admin_url('quotes.php?id=' . (int)$quote['id'])) ?>">Mở</a></td>
                    </tr><?php endforeach; ?><?php if (!$latest): ?><tr>
                        <td class="empty" colspan="5">Chưa có yêu cầu báo giá.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>