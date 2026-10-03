<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Email', 'Họ tên', 'Trạng thái', 'Ngày đăng ký']);
    $rows = $pdo->query('SELECT email, name, is_active, created_at FROM subscribers ORDER BY created_at DESC');
    while ($row = $rows->fetch()) {
        $values = [$row['email'], $row['name'], $row['is_active'] ? 'Đang nhận tin' : 'Đã hủy', $row['created_at']];
        $values = array_map(function ($value) {
            $value = (string)$value;
            return preg_match('/^[\t\r\n ]*[=+@-]/', $value) ? "'" . $value : $value;
        }, $values);
        fputcsv($output, $values);
    }
    fclose($output);
    exit;
}
$adminPage = 'subscribers.php';
$adminTitle = 'Danh sách nhận tin';
$rows = $pdo->query('SELECT * FROM subscribers ORDER BY created_at DESC LIMIT 1000')->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="fg"><a class="btn" href="<?= e(admin_url('subscribers.php?export=csv')) ?>">Xuất CSV</a></div>
<div class="card">
    <div class="tblwrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Họ tên</th>
                    <th>Trạng thái</th>
                    <th>Ngày đăng ký</th>
                </tr>
            </thead>
            <tbody><?php foreach ($rows as $row): ?><tr>
                        <td><?= e($row['email']) ?></td>
                        <td><?= e($row['name']) ?></td>
                        <td><?= $row['is_active'] ? 'Đang nhận tin' : 'Đã hủy' ?></td>
                        <td><?= e(format_date($row['created_at'])) ?></td>
                    </tr><?php endforeach; ?><?php if (!$rows): ?><tr>
                        <td colspan="4" class="empty">Chưa có người đăng ký.</td>
                    </tr><?php endif; ?></tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>