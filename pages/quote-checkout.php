<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/customer.php';

start_app_session();
customer_require_login();

$cart = cart_get_items();
if (empty($cart)) {
    flash_set('cart_status', 'Giỏ báo giá đang trống. Vui lòng thêm sản phẩm trước khi gửi yêu cầu!', 'warning');
    redirect(site_url('gio-bao-gia'));
}

$cartItems = [];
$totalQty = 0;
$ids = array_keys($cart);
try {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare('SELECT id, name, slug, image, unit, price_start FROM products WHERE id IN (' . $ph . ') AND is_active = 1');
    $st->execute($ids);
    $products = $st->fetchAll();
    $idMap = [];
    foreach ($products as $p) $idMap[$p['id']] = $p;
    foreach ($cart as $pid => $qItem) {
        if (isset($idMap[$pid])) {
            $p = $idMap[$pid];
            $unitName = match ($p['unit'] ?? 'cai') {
                'cai' => 'cái',
                'thung' => 'thùng',
                'cuon' => 'cuộn',
                'kg' => 'kg',
                default => $p['unit']
            };
            $cartItems[] = [
                'id' => $pid,
                'name' => $p['name'],
                'unit' => $unitName,
                'price' => (float)($p['price_start'] ?? 0),
                'quantity' => max(1, (int)($qItem['quantity'] ?? 1))
            ];
            $totalQty += max(1, (int)($qItem['quantity'] ?? 1));
        }
    }
} catch (Exception $e) {
}

if (empty($cartItems)) {
    // Loại bỏ sản phẩm không còn tồn tại hoặc đã ngừng bán khỏi giỏ.
    foreach ($cart as $pid => $_) {
        unset($cart[$pid]);
    }
    cart_set_items($cart);
    flash_set('cart_status', 'Không có sản phẩm hợp lệ trong giỏ!', 'error');
    redirect(site_url('gio-bao-gia'));
}

$customerStmt = $pdo->prepare('SELECT full_name, email, phone, company, address FROM customers WHERE id = ? AND is_active = 1 LIMIT 1');
$customerStmt->execute([(int)$_SESSION['customer_id']]);
$customerProfile = $customerStmt->fetch() ?: [];
$qf = array_merge($customerProfile, $_SESSION['quote_form'] ?? []);
unset($_SESSION['quote_form']);

$currentPage = 'quote-cart';
$pageTitle = 'Gửi yêu cầu báo giá - Bao bì Đức Thành';
$pageMetaDesc = 'Gửi yêu cầu báo giá chi tiết và nhận tư vấn từ chuyên gia Bao bì Đức Thành.';
$breadcrumb = [
    ['label' => 'Giỏ báo giá', 'url' => site_url('gio-bao-gia')],
    ['label' => 'Gửi yêu cầu báo giá']
];
$jsonLd = null;

$flash = flash_get('quote_checkout');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <div class="checkout-grid">
            <div class="checkout-form">
                <h3>Thông tin người đặt</h3>
                <form method="post" action="<?= e(site_url('api/quote.php?action=submit')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="text" name="customer_verify_hp" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="qc_name">Họ và tên <span class="required">*</span></label>
                            <input type="text" name="customer_name" id="qc_name" class="form-control" placeholder="Nguyễn Văn A" required minlength="2" maxlength="200" value="<?= e($qf['customer_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="qc_company">Tên công ty</label>
                            <input type="text" name="company" id="qc_company" class="form-control" placeholder="Công ty TNHH..." maxlength="200" value="<?= e($qf['company'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="qc_email">Email <span class="required">*</span></label>
                            <input type="email" name="email" id="qc_email" class="form-control" placeholder="email@example.com" required maxlength="200" value="<?= e($qf['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="qc_phone">Số điện thoại <span class="required">*</span></label>
                            <input type="tel" name="phone" id="qc_phone" class="form-control" placeholder="090xxxxxxx" required pattern="[0-9+\s.\-]{8,20}" maxlength="20" value="<?= e($qf['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="qc_address">Địa chỉ giao hàng <span class="required">*</span></label>
                        <input type="text" name="address" id="qc_address" class="form-control" placeholder="Số nhà, đường, quận/huyện, tỉnh/thành phố..." required minlength="5" maxlength="500" value="<?= e($qf['address'] ?? '') ?>">
                    </div>

                    <div class="schedule-box" style="margin-bottom:20px;padding:20px;border:1.5px solid var(--c-border-light);border-radius:12px;background:#fafafa;">
                        <h4 style="font-size:0.98rem;margin:0 0 14px;color:var(--c-primary);">Loại yêu cầu và lịch hẹn</h4>
                        <div class="form-row">
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label" for="qc_schedule_type">Loại lịch <span class="required">*</span></label>
                                <select id="qc_schedule_type" name="schedule_type" class="form-control" required onchange="toggleScheduleDate(this.value)">
                                    <option value="quote" <?= (($qf['schedule_type'] ?? 'quote') === 'quote') ? 'selected' : '' ?>>Báo giá chỉ dẫn</option>
                                    <option value="sample_view" <?= (($qf['schedule_type'] ?? '') === 'sample_view') ? 'selected' : '' ?>>Hẹn xem mẫu tại xưởng</option>
                                    <option value="delivery" <?= (($qf['schedule_type'] ?? '') === 'delivery') ? 'selected' : '' ?>>Hẹn giao hàng tận nơi</option>
                                </select>
                            </div>
                            <div class="form-group" style="margin-bottom:0;<?= in_array($qf['schedule_type'] ?? 'quote', ['sample_view', 'delivery']) ? '' : 'display:none;' ?>" id="qc_schedule_date_box">
                                <label class="form-label" for="qc_schedule_date">Ngày hẹn <span class="required">*</span></label>
                                <?php $minDate = date('Y-m-d');
                                $maxDate = date('Y-m-d', strtotime('+1 year')); ?>
                                <input type="date" name="schedule_date" id="qc_schedule_date" class="form-control" min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e($qf['schedule_date'] ?? '') ?>">
                            </div>
                        </div>
                        <script>
                            function toggleScheduleDate(v) {
                                var box = document.getElementById('qc_schedule_date_box');
                                var dt = document.getElementById('qc_schedule_date');
                                if (v === 'quote') {
                                    box.style.display = 'none';
                                    dt.removeAttribute('required');
                                } else {
                                    box.style.display = '';
                                    dt.setAttribute('required', 'required');
                                }
                            }
                            document.addEventListener('DOMContentLoaded', function() {
                                toggleScheduleDate(document.getElementById('qc_schedule_type').value);
                            });
                        </script>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="qc_note">Ghi chú / Yêu cầu đặc biệt</label>
                        <textarea name="note" id="qc_note" class="form-control" rows="4" placeholder="Ví dụ: Cần in logo, yêu cầu màu sắc, thời gian giao hàng, điều kiện thanh toán..." maxlength="2000"><?= e($qf['note'] ?? '') ?></textarea>
                    </div>
                    <div style="padding:14px 16px;background:rgba(232,163,61,0.12);border-radius:10px;margin-bottom:20px;font-size:0.86rem;line-height:1.6;color:#7c5a1d;border:1.5px solid rgba(232,163,61,0.30);">
                        <strong style="color:#5b4315;">Quy trình xử lý:</strong>
                        <ol style="margin:6px 0 0 18px;padding:0;">
                            <li>Bạn gửi yêu cầu và nhận mã số tự động</li>
                            <li>Chuyên gia báo giá liên hệ trong vòng <strong>60 phút</strong> làm việc</li>
                            <li>Tư vấn chi tiết, điều chỉnh yêu cầu (nếu có)</li>
                            <li>Bạn xác nhận, chúng tôi xử lý đơn và giao hàng</li>
                        </ol>
                    </div>
                    <button type="submit" class="btn btn-accent btn-block btn-lg" style="width:100%;">
                        Xác nhận gửi yêu cầu báo giá
                    </button>
                </form>
            </div>

            <aside>
                <div class="checkout-summary">
                    <h3>Thông tin đơn hàng</h3>
                    <div class="checkout-items">
                        <?php foreach ($cartItems as $item):
                            $priceStr = $item['price'] > 0 ? (number_format($item['price'], 0, ',', '.') . 'đ') : 'Liên hệ';
                        ?>
                            <div class="checkout-item">
                                <span class="checkout-item-name"><?= e($item['name']) ?></span>
                                <span class="checkout-item-qty">x<?= (int)$item['quantity'] ?> <?= e($item['unit']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Tổng mặt hàng:</span>
                        <span class="summary-value"><?= count($cartItems) ?> loại</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Tổng số lượng:</span>
                        <span class="summary-value"><?= $totalQty ?> cái</span>
                    </div>
                    <div class="summary-row summary-row-total" style="padding-top:16px;margin-top:10px;border-top:2px dashed var(--c-border-light);">
                        <span class="summary-label">Báo giá cuối cùng:</span>
                        <span class="summary-value" style="font-size:1.1rem;color:var(--c-accent);">Liên hệ</span>
                    </div>
                    <div style="margin-top:20px;padding:14px 16px;background:rgba(16,185,129,0.08);border-radius:10px;font-size:0.84rem;color:#065f46;line-height:1.6;">
                        <strong> Ưu đãi đặc biệt B2B:</strong>
                        <ul style="margin:6px 0 0 18px;padding:0;">
                            <li>Giảm 5-15% theo số lượng đơn hàng</li>
                            <li>Giao hàng miễn phí tại TP. HCM (đơn từ 2 triệu đồng)</li>
                            <li>In logo miễn phí (đơn từ 1.000 cái)</li>
                            <li>Bảo hành sản phẩm 30 ngày</li>
                        </ul>
                    </div>
                    <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
                        <a href="<?= e(site_url('gio-bao-gia')) ?>" class="btn btn-outline" style="flex:1;min-width:150px;">Sửa giỏ hàng</a>
                        <a href="tel:0901234567" class="btn btn-primary" style="flex:1;min-width:150px;">Hoặc gọi ngay</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
