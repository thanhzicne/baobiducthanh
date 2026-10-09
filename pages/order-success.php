<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/customer.php';

start_app_session();

$success = $_SESSION['order_success'] ?? null;
unset($_SESSION['order_success']);

$currentPage = 'checkout';
$pageTitle = 'Đặt hàng thành công - Bao bì Đức Thành';
$pageMetaDesc = 'Đơn hàng của bạn đã được tiếp nhận. Chúng tôi sẽ liên hệ xác nhận trong vòng 60 phút.';
$pageCanonical = site_url('dat-hang-thanh-cong');
$breadcrumb = [
    ['label' => 'Giỏ hàng', 'url' => site_url('gio-bao-gia')],
    ['label' => 'Đặt hàng thành công']
];
$jsonLd = null;

$flash = flash_get('order_success');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container" style="max-width:860px;">
        <?php if (!$success): ?>
            <div class="checkout-summary" style="text-align:center;">

                <h3>Không tìm thấy đơn hàng gần nhất</h3>
                <p style="color:var(--c-text-muted);">Đơn hàng của bạn đã được xử lý hoặc bạn chưa đặt đơn nào.</p>
                <?php if (customer_is_logged_in()): ?>
                    <a href="<?= e(site_url('lich-su-mua-hang')) ?>" class="btn btn-primary mt-md">Xem lịch sử mua hàng</a>
                <?php else: ?>
                    <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-primary mt-md">Tiếp tục mua sắm</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>
            <div class="checkout-summary mt-md">
                <h4>Chi tiết đơn hàng</h4>
                <div class="summary-row">
                    <span class="summary-label">Người nhận:</span>
                    <span class="summary-value"><?= e($success['shipping_name']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Điện thoại:</span>
                    <span class="summary-value"><?= e($success['shipping_phone']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Địa chỉ nhận hàng:</span>
                    <span class="summary-value" style="text-align:right;max-width:60%;"><?= e($success['shipping_address']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Thanh toán:</span>
                    <span class="summary-value"><?= e($success['payment_label']) ?></span>
                </div>
                <div class="summary-row summary-row-total">
                    <span class="summary-label">Tổng tiền:</span>
                    <span class="summary-value" style="color:var(--c-accent);"><?= number_format((float)$success['total_amount'], 0, ',', '.') ?>đ</span>
                </div>

                <div style="margin-top:18px;border-top:1px solid var(--c-border-light);padding-top:16px;">
                    <div style="font-weight:600;margin-bottom:10px;color:var(--c-text);">Sản phẩm đã đặt</div>
                    <?php foreach ($success['items'] as $item): ?>
                        <div class="summary-row" style="font-size:0.92rem;">
                            <span class="summary-label"><?= e($item['name']) ?></span>
                            <span class="summary-value">x<?= (int)$item['quantity'] ?> <?= e($item['unit']) ?> — <?= number_format((float)($item['price'] ?? 0) * (int)$item['quantity'], 0, ',', '.') ?>đ</span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:22px;">
                    <a href="<?= e(site_url('lich-su-mua-hang')) ?>" class="btn btn-primary" style="flex:1;min-width:180px;">Xem lịch sử mua hàng</a>
                    <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-outline" style="flex:1;min-width:180px;">Tiếp tục mua sắm</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
