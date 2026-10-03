<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/customer.php';

start_app_session();
customer_require_login();

$cart = cart_get_items();
if (empty($cart)) {
    flash_set('cart_status', 'Giỏ hàng đang trống. Vui lòng thêm sản phẩm trước khi đặt hàng!', 'warning');
    redirect(site_url('gio-bao-gia'));
}

$cartItems = [];
$totalAmount = 0;
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
            $price = (float)($p['price_start'] ?? 0);
            $qty = max(1, (int)($qItem['quantity'] ?? 1));
            $cartItems[] = [
                'id' => $pid,
                'name' => $p['name'],
                'unit' => $unitName,
                'price' => $price,
                'quantity' => $qty,
                'subtotal' => $price * $qty
            ];
            $totalAmount += $price * $qty;
            $totalQty += $qty;
        }
    }
} catch (Exception $e) {
}

if (empty($cartItems)) {
    flash_set('cart_status', 'Không có sản phẩm hợp lệ trong giỏ!', 'error');
    redirect(site_url('gio-bao-gia'));
}

$customerStmt = $pdo->prepare('SELECT full_name, phone, address FROM customers WHERE id = ? AND is_active = 1 LIMIT 1');
$customerStmt->execute([(int)$_SESSION['customer_id']]);
$customerProfile = $customerStmt->fetch() ?: [];
$of = array_merge($customerProfile, $_SESSION['order_form'] ?? []);
unset($_SESSION['order_form']);

$paymentOptions = [
    'cod' => ['icon' => '', 'title' => 'Nhận hàng trả tiền (COD)', 'desc' => 'Thanh toán bằng tiền mặt khi nhận hàng'],
    'bank_transfer' => ['icon' => '', 'title' => 'Thanh toán qua ngân hàng', 'desc' => 'Chuyển khoản đến tài khoản ngân hàng của chúng tôi'],
    'e_wallet' => ['icon' => '', 'title' => 'Thanh toán qua ví điện tử', 'desc' => 'Momo, ZaloPay, VNPay, ShopeePay...'],
];

$currentPage = 'checkout';
$pageTitle = 'Thanh toán - Bao bì Đức Thành';
$pageMetaDesc = 'Nhập thông tin nhận hàng và thanh toán đơn hàng tại Bao bì Đức Thành.';
$breadcrumb = [
    ['label' => 'Giỏ hàng', 'url' => site_url('gio-bao-gia')],
    ['label' => 'Thanh toán']
];
$jsonLd = null;

$flash = flash_get('order_checkout');

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
                <h3>Thông tin nhận hàng</h3>
                <form method="post" action="<?= e(site_url('api/quote.php?action=submit')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="text" name="customer_verify_hp" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="ck_name">Tên người nhận <span class="required">*</span></label>
                            <input type="text" name="shipping_name" id="ck_name" class="form-control" placeholder="Nguyễn Văn A" required minlength="2" maxlength="200" value="<?= e($of['shipping_name'] ?? $of['full_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="ck_phone">Số điện thoại người nhận <span class="required">*</span></label>
                            <input type="tel" name="shipping_phone" id="ck_phone" class="form-control" placeholder="090xxxxxxx" required pattern="[0-9+\s.\-]{8,20}" maxlength="20" value="<?= e($of['shipping_phone'] ?? $of['phone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ck_address">Địa chỉ nhận hàng <span class="required">*</span></label>
                        <input type="text" name="shipping_address" id="ck_address" class="form-control" placeholder="Số nhà, đường, quận/huyện, tỉnh/thành phố..." required minlength="5" maxlength="500" value="<?= e($of['shipping_address'] ?? $of['address'] ?? '') ?>">
                    </div>

                    <div style="margin:20px 0 6px;padding:16px;border:1.5px solid var(--c-border-light);border-radius:12px;background:#fafafa;">
                        <h4 style="font-size:0.98rem;margin:0 0 12px;color:var(--c-primary);">Hình thức thanh toán</h4>
                        <?php foreach ($paymentOptions as $key => $opt): ?>
                            <label class="payment-option" style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1.5px solid var(--c-border-light);border-radius:10px;margin-bottom:10px;cursor:pointer;background:#fff;">
                                <input type="radio" name="payment_method" value="<?= e($key) ?>" <?= (($of['payment_method'] ?? 'cod') === $key) ? 'checked' : '' ?> style="margin-top:4px;">
                                <span>
                                    <strong><?= $opt['icon'] ?> <?= e($opt['title']) ?></strong>
                                    <span style="display:block;font-size:0.82rem;color:var(--c-text-muted);"><?= e($opt['desc']) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="ck_note">Ghi chú / Yêu cầu đặc biệt</label>
                        <textarea name="note" id="ck_note" class="form-control" rows="3" placeholder="Ví dụ: Giao giờ hành chính, cần xuất hoá đơn..." maxlength="2000"><?= e($of['note'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-accent btn-block btn-lg" style="width:100%;">
                        Xác nhận đặt hàng
                    </button>
                </form>
            </div>

            <aside>
                <div class="checkout-summary">
                    <h3>Đơn hàng của bạn</h3>
                    <div class="checkout-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="checkout-item">
                                <span class="checkout-item-name"><?= e($item['name']) ?></span>
                                <span class="checkout-item-qty">x<?= (int)$item['quantity'] ?> <?= e($item['unit']) ?> — <?= number_format($item['subtotal'], 0, ',', '.') ?>đ</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Tổng số lượng:</span>
                        <span class="summary-value"><?= $totalQty ?> sản phẩm</span>
                    </div>
                    <div class="summary-row summary-row-total" style="padding-top:16px;margin-top:10px;border-top:2px dashed var(--c-border-light);">
                        <span class="summary-label">Tạm tính:</span>
                        <span class="summary-value" style="font-size:1.1rem;color:var(--c-accent);"><?= number_format($totalAmount, 0, ',', '.') ?>đ</span>
                    </div>
                    <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap;">
                        <a href="<?= e(site_url('gio-bao-gia')) ?>" class="btn btn-outline" style="flex:1;min-width:150px;">← Sửa giỏ hàng</a>
                        <a href="tel:0901234567" class="btn btn-primary" style="flex:1;min-width:150px;">Gọi tư vấn</a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
