<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';

start_app_session();

$success = $_SESSION['quote_success'] ?? null;
unset($_SESSION['quote_success']);

$currentPage = 'quote-cart';
$pageTitle = 'Cảm ơn yêu cầu báo giá - Bao bì Đức Thành';
$pageMetaDesc = 'Đã nhận yêu cầu báo giá của bạn. Chúng tôi sẽ liên hệ trong vòng 60 phút làm việc.';
$pageCanonical = site_url('cam-on-yeu-cau');
$breadcrumb = [
    ['label' => 'Giỏ báo giá', 'url' => site_url('gio-bao-gia')],
    ['label' => 'Cảm ơn yêu cầu']
];
$jsonLd = null;

$flash = flash_get('quote_success');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container" style="max-width:860px;">
        <?php if (!$success): ?>
            <div class="quote-cart-summary" style="text-align:center;">
                <div style="font-size:3rem;">📭</div>
                <h3>Không tìm thấy yêu cầu gần nhất</h3>
                <p style="color:var(--c-text-muted);">Yêu cầu của bạn đã được xử lý hoặc bạn chưa gửi yêu cầu nào.</p>
                <a href="<?= e(site_url('gio-bao-gia')) ?>" class="btn btn-primary mt-md">Tạo yêu cầu báo giá mới</a>
            </div>
        <?php else: ?>
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>"><?= $flash['message'] ?></div>
            <?php endif; ?>

            <div class="quote-cart-summary" style="text-align:center;padding:32px 24px;">
                <div style="width:88px;height:88px;margin:0 auto 18px;border-radius:50%;background:rgba(16,185,129,0.12);display:flex;align-items:center;justify-content:center;font-size:2.6rem;"></div>
                <h2 style="margin-bottom:6px;">Cảm ơn bạn đã gửi yêu cầu!</h2>
                <p style="color:var(--c-text-muted);">Mã yêu cầu của bạn là:</p>
                <div style="display:inline-block;margin:12px 0 22px;padding:12px 26px;border:2px dashed var(--c-accent);border-radius:12px;font-size:1.4rem;font-weight:800;letter-spacing:1px;color:var(--c-primary);background:rgba(232,163,61,0.08);">
                    <?= e($success['code']) ?>
                </div>
                <p style="color:var(--c-text-muted);">
                    Chuyên viên báo giá sẽ liên hệ qua số điện thoại <strong style="color:var(--c-text);"><?= e($success['phone']) ?></strong>
                    trong vòng <strong style="color:var(--c-text);">60 phút làm việc</strong>.
                </p>
            </div>

            <div class="quote-cart-summary mt-md">
                <h4>Chi tiết yêu cầu</h4>
                <div class="summary-row">
                    <span class="summary-label">Người gửi:</span>
                    <span class="summary-value"><?= e($success['customer_name']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Email:</span>
                    <span class="summary-value"><?= e($success['email']) ?></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Loại lịch:</span>
                    <span class="summary-value"><?= e($success['schedule_type_label']) ?></span>
                </div>
                <?php if (!empty($success['schedule_date'])): ?>
                    <div class="summary-row">
                        <span class="summary-label">Ngày hẹn:</span>
                        <span class="summary-value"><?= e(format_date($success['schedule_date'], 'd/m/Y')) ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-row">
                    <span class="summary-label">Số mặt hàng:</span>
                    <span class="summary-value"><?= (int)$success['total_items'] ?> loại / <?= (int)$success['total_qty'] ?> sản phẩm</span>
                </div>

                <div style="margin-top:18px;border-top:1px solid var(--c-border-light);padding-top:16px;">
                    <div style="font-weight:600;margin-bottom:10px;color:var(--c-text);">Sản phẩm đã yêu cầu báo giá</div>
                    <?php foreach ($success['items'] as $item): ?>
                        <div class="summary-row" style="font-size:0.92rem;">
                            <span class="summary-label"><?= e($item['name']) ?></span>
                            <span class="summary-value">x<?= (int)$item['quantity'] ?> <?= e($item['unit']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="quote-cart-note" style="margin-top:18px;">
                    💡 Lưu mã yêu cầu <strong><?= e($success['code']) ?></strong> để tra cứu nhanh khi liên hệ tư vấn.
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:22px;">
                    <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-primary" style="flex:1;min-width:180px;">Tiếp tục xem sản phẩm</a>
                    <a href="<?= e(site_url('lien-he')) ?>" class="btn btn-outline" style="flex:1;min-width:180px;">Liên hệ thêm</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
