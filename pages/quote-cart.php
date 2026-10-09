<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/customer.php';

start_app_session();

$currentPage = 'quote-cart';
$pageTitle = 'Giỏ báo giá - Bao bì Đức Thành';
$pageMetaDesc = 'Xem giỏ báo giá, chỉnh sửa số lượng sản phẩm và gửi yêu cầu báo giá Bao bì Đức Thành.';
$breadcrumb = [['label' => 'Giỏ báo giá']];
$jsonLd = null;

$cart = cart_get_items();
$cartItems = [];
$totalQty = 0;
if (!empty($cart)) {
    $ids = array_keys($cart);
    try {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare('SELECT * FROM products WHERE id IN (' . $ph . ') AND is_active = 1');
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
                    'slug' => $p['slug'],
                    'image' => $p['image'] ?? null,
                    'unit' => $unitName,
                    'price' => (float)($p['price_start'] ?? 0),
                    'quantity' => max(1, (int)($qItem['quantity'] ?? 1)),
                    'note' => $qItem['note'] ?? ''
                ];
                $totalQty += max(1, (int)($qItem['quantity'] ?? 1));
            }
        }
    } catch (Exception $e) {
        $cartItems = [];
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';

$flash = flash_get('cart_status');
?>
<section class="section section-sm" style="padding-top:28px;">
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="quote-cart-empty">
                <div class="quote-cart-empty-icon"></div>
                <h3>Giỏ báo giá của bạn đang trống</h3>
                <p>Hãy thêm sản phẩm cần báo giá vào giỏ để gửi yêu cầu.</p>
                <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                    <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-primary btn-lg">Xem tất cả sản phẩm</a>
                    <a href="<?= e(site_url('lien-he')) ?>" class="btn btn-outline btn-lg">Liên hệ tư vấn</a>
                </div>
            </div>
        <?php else: ?>
            <?php
            $totalAmount = 0;
            foreach ($cartItems as $item) $totalAmount += $item['price'] * $item['quantity'];
            ?>
            <div style="display:grid;grid-template-columns:1fr 380px;gap:28px;align-items:start;" class="shop-layout">
                <div>
                    <div class="quote-cart-table">
                        <table style="width:100%;border-collapse:collapse;min-width:600px;">
                            <thead>
                                <tr style="background:var(--c-bg-muted);">
                                    <th style="padding:14px 16px;text-align:left;border-bottom:2px solid var(--c-border-light);">Sản phẩm</th>
                                    <th style="padding:14px 16px;text-align:center;border-bottom:2px solid var(--c-border-light);width:140px;">Số lượng</th>
                                    <th style="padding:14px 16px;text-align:right;border-bottom:2px solid var(--c-border-light);width:140px;">Giá (từ)</th>
                                    <th style="padding:14px 16px;text-align:center;border-bottom:2px solid var(--c-border-light);width:60px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartItems as $item):
                                    $priceStr = $item['price'] > 0 ? (number_format($item['price'], 0, ',', '.') . 'đ') : 'Liên hệ';
                                    $icons = ['📦', '🗃️', '📜', '🎗️', '🧱', '🏷️'];
                                    $icon = $icons[($item['id'] - 1) % count($icons)] ?? '📦';
                                ?>
                                    <tr data-row-id="<?= (int)$item['id'] ?>" style="border-bottom:1px solid var(--c-border-light);">
                                        <td style="padding:14px 16px;">
                                            <div style="display:flex;align-items:center;gap:14px;">
                                                <div style="width:68px;height:68px;border-radius:8px;background:linear-gradient(135deg,rgba(139,94,52,0.14),rgba(232,163,61,0.18));display:flex;align-items:center;justify-content:center;font-size:2rem;flex-shrink:0;">
                                                    <?php if ($item['image']): ?>
                                                        <img src="<?= e(upload_url($item['image'])) ?>" alt="<?= e($item['name']) ?>" style="width:64px;height:64px;border-radius:6px;object-fit:cover;">
                                                    <?php else: echo $icon;
                                                    endif; ?>
                                                </div>
                                                <div style="min-width:0;flex:1;">
                                                    <a href="<?= e(site_url('san-pham/' . $item['slug'])) ?>" style="font-weight:600;color:var(--c-text);line-height:1.45;"><?= e($item['name']) ?></a>
                                                    <div style="font-size:0.82rem;color:var(--c-text-muted);margin-top:4px;">Đơn vị: <?= e($item['unit']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding:14px 16px;">
                                            <form method="post" action="<?= e(site_url('api/quote.php?action=update')) ?>" class="quote-cart-update-form" style="display:flex;justify-content:center;">
                                                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                                <?= csrf_field() ?>
                                                <div class="quantity-input">
                                                    <button type="button" class="qty-minus" aria-label="Giảm">−</button>
                                                    <input type="number" name="quantity" value="<?= (int)$item['quantity'] ?>" min="1" max="1000000" data-qty-input>
                                                    <button type="button" class="qty-plus" aria-label="Tang">+</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td style="padding:14px 16px;text-align:right;font-weight:600;color:var(--c-primary);"><?= $priceStr ?></td>
                                        <td style="padding:14px 16px;text-align:center;">
                                            <button type="button" class="quote-cart-remove" data-id="<?= (int)$item['id'] ?>" data-api="<?= e(site_url('api/quote.php?action=remove')) ?>" data-csrf="<?= e(csrf_token()) ?>" aria-label="Xóa" style="border:none;background:rgba(239,68,68,0.08);color:var(--c-error);width:34px;height:34px;border-radius:50%;cursor:pointer;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:18px;display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap;">
                        <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-outline btn-sm" style="min-height:44px;">
                            Tiếp tục thêm sản phẩm
                        </a>
                        <form method="post" action="<?= e(site_url('api/quote.php?action=clear')) ?>" onsubmit="return confirm('Xóa tất cả sản phẩm khỏi giỏ?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger-soft btn-sm">Xóa tất cả</button>
                        </form>
                    </div>
                </div>

                <div>
                    <div class="quote-cart-summary">
                        <h4>Tổng quan giỏ hàng</h4>
                        <div class="summary-row">
                            <span class="summary-label">Tổng số mặt hàng:</span>
                            <span class="summary-value"><?= count($cartItems) ?> mặt hàng</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tổng số lượng:</span>
                            <span class="summary-value"><?= $totalQty ?> sản phẩm</span>
                        </div>
                        <div class="summary-row summary-row-total">
                            <span class="summary-label">Tổng tiền:</span>
                            <span class="summary-value" style="color:var(--c-accent);font-size:1.15rem;"><?= number_format($totalAmount, 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="quote-cart-note">
                            Giá hiển thị là giá khởi điểm. Tổng tiền cuối cùng sẽ được xác nhận khi đặt hàng.
                        </div>
                        <a href="<?= e(site_url('thanh-toan')) ?>" class="btn btn-accent btn-block btn-lg mt-md" style="width:100%;">
                            Tiến hành thanh toán →
                        </a>
                        <div style="text-align:center;margin-top:14px;">
                            <span style="font-size:0.84rem;color:var(--c-text-muted);">Hoặc gọi: </span>
                            <a href="tel:0901234567" style="font-weight:700;color:var(--c-primary);">0901 234 567</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
