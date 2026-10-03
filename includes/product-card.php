<?php
if (!isset($product) || !is_array($product)) {
    return;
}
$id = (int)($product['id'] ?? 0);
$name = $product['name'] ?? '';
$slug = $product['slug'] ?? '';
$shortDesc = $product['short_description'] ?? '';
$price = $product['price_start'] ?? null;
$image = $product['image'] ?? null;
$unit = $product['unit'] ?? 'cai';
$isFeatured = !empty($product['is_featured']);
$catName = $product['category_name'] ?? ($product['cat_name'] ?? '');
$unitName = match ($unit) {
    'cai' => 'cái',
    'thung' => 'thùng',
    'cuon' => 'cuộn',
    'kg' => 'kg',
    'dozen' => 'dozen',
    'met' => 'mét',
    default => $unit
};
$imageUrl = !empty($image) ? upload_grid_url($image) : null;
$icons = [
    '📦',
    '🗃️',
    '📜',
    '🎗️',
    '🧱',
    '🏷️',
    '📋',
    '🛡️',
    '🎁',
    '🧰',
    '🪢',
    '📐'
];
$icon = $icons[($id - 1) % count($icons)] ?? '📦';
?>
<article class="product-card" data-product-id="<?= $id ?>">
    <div class="product-image">
        <?php if ($imageUrl): ?>
            <img src="<?= e($imageUrl) ?>" alt="<?= e($name) ?>" loading="lazy" width="400" height="300">
        <?php else: ?>
            <div class="product-placeholder" aria-hidden="true"><?= $icon ?></div>
        <?php endif; ?>
        <?php if ($isFeatured): ?>
            <div class="product-badge">
                <span class="badge badge-featured">Nổi bật</span>
            </div>
        <?php endif; ?>
        <div class="product-quick-actions">
            <a href="<?= e(site_url('san-pham/' . $slug)) ?>" aria-label="Xem chi tiết" title="Xem chi tiết">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </a>
            <button type="button" class="product-quick-add" data-id="<?= $id ?>" data-api="<?= e(site_url('api/quote.php?action=add')) ?>" data-csrf="<?= e(csrf_token()) ?>" aria-label="Thêm vào giỏ báo giá" title="Thêm vào giỏ">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
        </div>
    </div>
    <div class="product-body">
        <?php if (!empty($catName)): ?>
            <div class="product-cat"><?= e($catName) ?></div>
        <?php endif; ?>
        <h3 class="product-name">
            <a href="<?= e(site_url('san-pham/' . $slug)) ?>"><?= e($name) ?></a>
        </h3>
        <?php if (!empty($shortDesc)): ?>
            <p class="product-short-desc"><?= e(excerpt($shortDesc, 100)) ?></p>
        <?php endif; ?>
        <div class="product-meta">
            <div>
                <?php if ($price !== null && $price > 0): ?>
                    <div class="product-price">
                        <?= number_format((float)$price, 0, ',', '.') ?>đ
                        <span class="product-unit">/<?= $unitName ?></span>
                    </div>
                <?php else: ?>
                    <div class="product-price" style="font-size:0.95rem;font-weight:600;color:var(--c-accent);">
                        Liên hệ giá
                    </div>
                <?php endif; ?>
            </div>
            <form class="add-to-quote-form" action="<?= e(site_url('api/quote.php?action=add')) ?>" method="post">
                <input type="hidden" name="product_id" value="<?= $id ?>">
                <input type="hidden" name="quantity" value="1">
                <?= csrf_field() ?>
                <button type="submit" class="product-add" aria-label="Thêm vào giỏ báo giá" title="Thêm vào giỏ báo giá">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</article>