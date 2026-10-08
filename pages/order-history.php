<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/customer.php';

start_app_session();
customer_require_login();

order_ensure_tables($pdo);

$orders = [];
try {
    $st = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 50');
    $st->execute([(int)$_SESSION['customer_id']]);
    $orders = $st->fetchAll();
    if ($orders) {
        $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        foreach ($orders as &$o) {
            $itemStmt->execute([(int)$o['id']]);
            $o['items'] = $itemStmt->fetchAll();
        }
        unset($o);
    }
} catch (Exception $e) {
    error_log('Order history error: ' . $e->getMessage());
}

$statusLabels = [
    'pending' => ['Chờ xác nhận', 'var(--c-accent)'],
    'confirmed' => ['Đã xác nhận', 'var(--c-primary)'],
    'shipping' => ['Đang giao', '#1e40af'],
    'completed' => ['Hoàn thành', 'var(--c-success)'],
    'cancelled' => ['Đã huỷ', 'var(--c-error)'],
];

$paymentLabels = [
    'cod' => 'Nhận hàng trả tiền (COD)',
    'bank_transfer' => 'Thanh toán qua ngân hàng',
    'e_wallet' => 'Thanh toán qua ví điện tử'
];

$currentPage = 'order-history';
$pageTitle = 'Lịch sử mua hàng - Bao bì Đức Thành';
$pageMetaDesc = 'Xem lại các đơn hàng bạn đã đặt tại Bao bì Đức Thành.';
$breadcrumb = [['label' => 'Lịch sử mua hàng']];
$jsonLd = null;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:22px;">
            <h2 style="margin:0;">Lịch sử mua hàng</h2>
            <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-outline btn-sm">Tiếp tục mua sắm</a>
        </div>

        <?php if (empty($orders)): ?>
            <div class="quote-cart-summary" style="text-align:center;">
                <div style="font-size:3rem;"></div>
                <h3>Bạn chưa có đơn hàng nào</h3>
                <p style="color:var(--c-text-muted);">Các đơn hàng sau khi đặt sẽ hiển thị tại đây.</p>
                <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-primary mt-md">Xem sản phẩm</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $o): ?>
                <div class="checkout-summary" style="margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                        <div>
                            <strong style="color:var(--c-primary);font-size:1.05rem;"><?= e($o['order_code']) ?></strong>
                            <div style="font-size:0.82rem;color:var(--c-text-muted);"><?= e(format_date($o['created_at'])) ?></div>
                        </div>
                        <span style="padding:4px 12px;border-radius:20px;font-size:0.8rem;font-weight:700;background:rgba(232,163,61,0.12);color:<?= e($statusLabels[$o['status']][1] ?? 'var(--c-text)') ?>;">
                            <?= e($statusLabels[$o['status']][0] ?? ucfirst($o['status'])) ?>
                        </span>
                    </div>
                    <div class="checkout-items">
                        <?php foreach ($o['items'] as $item): ?>
                            <div class="checkout-item">
                                <span class="checkout-item-name">
                                    <?php if (!empty($item['product_image'])): ?>
                                        <img src="<?= e(upload_url($item['product_image'])) ?>" alt="" style="width:34px;height:34px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:8px;">
                                    <?php endif; ?>
                                    <?= e($item['product_name']) ?>
                                </span>
                                <span class="checkout-item-qty">x<?= (int)$item['quantity'] ?> <?= e($item['unit'] ?? '') ?> — <?= number_format((float)$item['subtotal'], 0, ',', '.') ?>đ</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-row" style="margin-top:10px;">
                        <span class="summary-label">Nhận hàng:</span>
                        <span class="summary-value"><?= e($o['shipping_name']) ?> — <?= e($o['shipping_phone']) ?>, <?= e($o['shipping_address']) ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Thanh toán:</span>
                        <span class="summary-value"><?= e($paymentLabels[$o['payment_method']] ?? $o['payment_method']) ?></span>
                    </div>
                    <div class="summary-row summary-row-total" style="padding-top:12px;margin-top:8px;border-top:2px dashed var(--c-border-light);">
                        <span class="summary-label">Tổng tiền:</span>
                        <span class="summary-value" style="color:var(--c-accent);"><?= number_format((float)$o['total_amount'], 0, ',', '.') ?>đ</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
