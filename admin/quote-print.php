<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM quote_requests WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$quote = $stmt->fetch();
if (!$quote) {
    http_response_code(404);
    exit('Không tìm thấy báo giá.');
}
$stmt = $pdo->prepare('SELECT * FROM quote_items WHERE quote_request_id = ? ORDER BY id');
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($quote['code']) ?> - Báo giá</title>
    <style>
        body {
            font: 14px Arial, sans-serif;
            color: #222;
            max-width: 900px;
            margin: 32px auto;
            padding: 0 20px
        }

        h1 {
            margin-bottom: 4px
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px
        }

        th,
        td {
            border: 1px solid #bbb;
            padding: 10px;
            text-align: left
        }

        th {
            background: #f2f2f2
        }

        .no-print {
            margin-bottom: 24px
        }

        @media print {
            .no-print {
                display: none
            }

            body {
                margin: 0 auto
            }
        }
    </style>
</head>

<body>
    <div class="no-print"><button onclick="window.print()">In báo giá</button> <a href="<?= e(admin_url('quotes.php?id=' . $id)) ?>">Quay lại</a></div>
    <h1>YÊU CẦU BÁO GIÁ</h1>
    <p><strong>Mã yêu cầu:</strong> <?= e($quote['code']) ?> · <strong>Ngày:</strong> <?= e(format_date($quote['created_at'])) ?></p>
    <hr>
    <h2>Thông tin khách hàng</h2>
    <p><?= e($quote['customer_name']) ?><?= $quote['company'] ? ' · ' . e($quote['company']) : '' ?></p>
    <p><?= e($quote['email']) ?> · <?= e($quote['phone']) ?></p>
    <p><?= nl2br(e($quote['address'])) ?></p>
    <table>
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
    <p><strong>Ghi chú:</strong> <?= nl2br(e($quote['note'])) ?></p>
</body>

</html>