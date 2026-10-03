<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

try {
    $st = $pdo->prepare('SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.is_active = 1 LIMIT 1');
    $st->execute([$slug]);
    $product = $st->fetch();
} catch (Exception $e) {
    $product = null;
}

if (!$product) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$pid = (int)$product['id'];
try {
    $pdo->prepare('UPDATE products SET view_count = view_count + 1 WHERE id = ?')->execute([$pid]);
    $product['view_count'] = ($product['view_count'] ?? 0) + 1;
} catch (Exception $e) {
}

$currentPage = 'products';
$pageTitle = $product['meta_title'] ?? $product['name'];
$pageMetaDesc = $product['meta_description'] ?? excerpt(strip_tags($product['short_description'] ?? $product['description'] ?? ''), 160);
$breadcrumb = [
    ['label' => 'Sản phẩm', 'url' => site_url('san-pham')],
    ['label' => $product['category_name'] ?? '', 'url' => $product['category_slug'] ? site_url('danh-muc/' . $product['category_slug']) : ''],
    ['label' => $product['name']]
];
$ogType = 'product';

$specLines = [];
if (!empty($product['spec'])) {
    foreach (explode("\n", trim($product['spec'])) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = explode(':', $line, 2);
        if (count($parts) === 2) $specLines[] = [trim($parts[0]), trim($parts[1])];
        else $specLines[] = [$line, ''];
    }
}

try {
    $rp = $pdo->prepare('SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT 4');
    $rp->execute([(int)$product['category_id'], $pid]);
    $related = $rp->fetchAll();
} catch (Exception $e) {
    $related = [];
}

$priceFormatted = $product['price_start'] > 0 ? number_format((float)$product['price_start'], 0, ',', '.') . 'đ' : 'Liên hệ';
$unitName = match ($product['unit'] ?? 'cai') {
    'cai' => 'cái',
    'thung' => 'thùng',
    'cuon' => 'cuộn',
    'kg' => 'kg',
    default => $product['unit']
};
$icons = ['📦', '🗃️', '📜', '🎗️', '🧱', '🏷️', '📋', '🛡️', '🎁', '🧰', '🪢', '📐'];
$icon = $icons[($pid - 1) % count($icons)] ?? '📦';
$imageUrl = !empty($product['image']) ? upload_url($product['image']) : null;

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'description' => $pageMetaDesc,
    'image' => $imageUrl ?: site_url('assets/images/og-default.jpg'),
    'sku' => 'SP-' . str_pad($pid, 5, '0', STR_PAD_LEFT),
    'brand' => ['@type' => 'Brand', 'name' => 'Bao bì Đức Thành'],
    'category' => $product['category_name'] ?? '',
    'offers' => [
        '@type' => 'Offer',
        'url' => site_url('san-pham/' . $slug),
        'priceCurrency' => 'VND',
        'price' => (float)($product['price_start'] ?? 0),
        'availability' => 'https://schema.org/InStock',
        'itemCondition' => 'https://schema.org/NewCondition'
    ]
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container">
        <div class="product-detail-layout">
            <div>
                <div class="product-gallery-main" id="productMainImage">
                    <?php if ($imageUrl): ?>
                        <img src="<?= e($imageUrl) ?>" alt="<?= e($product['name']) ?>" style="max-height:100%;object-fit:contain;">
                    <?php else: echo $icon;
                    endif; ?>
                </div>
            </div>
            <div class="product-info">
                <?php if (!empty($product['category_name'])): ?>
                    <span class="pd-cat">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <?= e($product['category_name']) ?>
                    </span>
                <?php endif; ?>
                <h1><?= e($product['name']) ?></h1>
                <div class="product-detail-meta">
                    <span class="pd-sku">Mã SP: SP-<?= str_pad($pid, 5, '0', STR_PAD_LEFT) ?></span>
                    <span class="pd-views">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <?= (int)$product['view_count'] ?> lượt xem
                    </span>
                </div>

                <div class="product-price-box">
                    <span class="price-label">Gía bắt đầu (liên hệ để được báo giá chính xác)</span>
                    <div class="pd-price">
                        <?php if ($product['price_start'] > 0): ?>
                            <span class="price-from">Từ </span><?= $priceFormatted ?>
                            <span class="pd-price-unit"> / <?= $unitName ?></span>
                        <?php else: ?>
                            <span style="color:var(--c-accent);font-size:1.2rem;"><?= $priceFormatted ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($product['min_order'])): ?>
                        <div class="pd-min-order">Đặt hàng tối thiểu: <strong><?= (int)$product['min_order'] ?> <?= $unitName ?></strong></div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($product['short_description'])): ?>
                    <div class="pd-short-desc">
                        <p><?= e($product['short_description']) ?></p>
                    </div>
                <?php endif; ?>

                <form class="add-to-quote-form" method="post" action="<?= e(site_url('api/quote.php?action=add')) ?>" style="display:contents;">
                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                    <?= csrf_field() ?>
                    <div class="quantity-wrap">
                        <div class="qty-label">Số lượng:</div>
                        <div class="quantity-input">
                            <button type="button" class="qty-minus" aria-label="Giảm">−</button>
                            <input type="number" name="quantity" value="<?= max(1, (int)($product['min_order'] ?? 1)) ?>" min="<?= max(1, (int)($product['min_order'] ?? 1)) ?>" max="100000" required>
                            <button type="button" class="qty-plus" aria-label="Tăng">+</button>
                        </div>
                        <span class="text-muted" style="font-size:0.9rem;">(Đơn vị: <?= $unitName ?>)</span>
                    </div>
                    <div class="pd-actions">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Thêm vào giỏ hàng
                        </button>
                        <a href="<?= e(site_url('gio-bao-gia')) ?>" class="btn btn-outline btn-lg btn-block">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            Xem giỏ hàng
                        </a>
                    </div>
                </form>
                <form method="post" action="<?= e(site_url('api/quote.php?action=buy-now')) ?>" style="margin-top:12px;">
                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                    <input type="hidden" name="quantity" value="<?= max(1, (int)($product['min_order'] ?? 1)) ?>" data-buy-now-qty>
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-accent btn-lg btn-block">
                        Mua ngay
                    </button>
                </form>
                <script>
                    (function() {
                        var mainInput = document.querySelector('.add-to-quote-form input[name=quantity]');
                        var buyQty = document.querySelector('[data-buy-now-qty]');
                        if (mainInput && buyQty) mainInput.addEventListener('input', function() {
                            buyQty.value = mainInput.value;
                        });
                    })();
                </script>

                <div class="mt-md" style="padding:14px 16px;background:rgba(16,185,129,0.08);border-radius:10px;display:flex;align-items:center;gap:10px;">
                    <div style="width:34px;height:34px;border-radius:50%;background:var(--c-success);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div style="font-size:0.9rem;color:#065f46;line-height:1.55;">
                        <strong>Bảo hành 30 ngày</strong> với lỗi sản xuất. Giao hàng 24-48h. Trả hàng nếu không đúng yêu cầu.
                    </div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:280px 1fr;gap:30px;margin-top:40px;align-items:start;" class="shop-layout">
            <aside class="toc-wrap sidebar" aria-label="Mục lục">
                <div class="toc-box">
                    <div class="toc-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                        Mục lục
                    </div>
                    <ul class="toc-list"></ul>
                </div>
            </aside>

            <div class="product-detail-body">
                <div class="detail-tabs" role="tablist">
                    <button type="button" class="detail-tab active" data-tab="description" role="tab">Mô tả chi tiết</button>
                    <button type="button" class="detail-tab" data-tab="spec" role="tab">Thông số kỹ thuật</button>
                </div>

                <div class="detail-tab-content active" data-tab="description">
                    <div class="product-content">
                        <?= display_brand_text($product['description'] ?? '<p>Thông tin chi tiết về sản phẩm sẽ được cập nhật sớm nhất. Vui lòng liên hệ để được tư vấn.</p>') ?>
                    </div>
                </div>

                <div class="detail-tab-content" data-tab="spec">
                    <?php if (!empty($specLines)): ?>
                        <table class="spec-table">
                            <tbody>
                                <?php foreach ($specLines as [$k, $v]): ?>
                                    <tr>
                                        <th><?= e($k) ?></th>
                                        <td><?= e($v) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">Thông số chi tiết sẽ được cập nhật. Vui lòng liên hệ để được báo giá.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($related)): ?>
            <div class="related-news" style="margin-top:56px;">
                <div class="section-title" style="text-align:left;margin-bottom:24px;">
                    <h2 style="font-size:1.45rem;padding-bottom:10px;">Sản phẩm liên quan</h2>
                    <p style="margin:0;">Sản phẩm cùng danh mục có thể bạn quan tâm</p>
                </div>
                <div class="product-grid">
                    <?php foreach ($related as $product): include __DIR__ . '/../includes/product-card.php';
                    endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
