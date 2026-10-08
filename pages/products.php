<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$currentPage = 'products';
$pageNum = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($settings['items_per_page'] ?? 12);
$sort = $_GET['sort'] ?? 'default';
$categorySlug = $categorySlug ?? ($_GET['category_slug'] ?? $_GET['slug'] ?? null);

$categories = [];
$catCounts = [];
try {
    $cs = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) AS cnt FROM categories c WHERE c.is_active = 1 ORDER BY c.sort_order ASC, c.id ASC');
    $categories = $cs->fetchAll();
    foreach ($categories as $c) $catCounts[$c['id']] = (int)($c['cnt'] ?? 0);
} catch (Exception $e) {
}

$where = ['p.is_active = 1'];
$params = [];
$currentCat = null;

if ($categorySlug) {
    foreach ($categories as $c) {
        if ($c['slug'] === $categorySlug) {
            $currentCat = $c;
            break;
        }
    }
    if ($currentCat) {
        $where[] = 'p.category_id = ?';
        $params[] = $currentCat['id'];
    }
}

$orderBy = 'p.sort_order ASC, p.id DESC';
if ($sort === 'price-asc') $orderBy = 'p.price_start ASC, p.id DESC';
elseif ($sort === 'price-desc') $orderBy = 'p.price_start DESC, p.id DESC';
elseif ($sort === 'newest') $orderBy = 'p.id DESC';
elseif ($sort === 'name-asc') $orderBy = 'p.name ASC';

$whereSql = implode(' AND ', $where);

$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM products p WHERE ' . $whereSql);
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();

$totalPages = max(1, (int)ceil($total / $perPage));
$offset = ($pageNum - 1) * $perPage;

$sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE ' . $whereSql . '
        ORDER BY ' . $orderBy . '
        LIMIT ' . (int)$offset . ', ' . (int)$perPage;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

if ($currentCat) {
    $pageTitle = $currentCat['meta_title'] ?? ('Danh mục: ' . $currentCat['name']);
    $pageMetaDesc = $currentCat['meta_description'] ?? excerpt($currentCat['description'] ?? 'Sản phẩm ' . $currentCat['name'], 160);
    $h1Text = $currentCat['name'];
    $breadcrumb = [
        ['label' => 'Sản phẩm', 'url' => site_url('san-pham')],
        ['label' => $currentCat['name']]
    ];
} else {
    $pageTitle = 'Tất cả sản phẩm';
    $pageMetaDesc = 'Danh sách sản phẩm Bao bì Đức Thành: thùng carton 3 lớp, 5 lớp, màng PE, băng keo. Giao hàng toàn quốc.';
    $h1Text = 'Tất cả sản phẩm';
    $breadcrumb = [['label' => 'Sản phẩm']];
    $currentPage = 'products';
}

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $h1Text,
    'itemListElement' => array_values(array_map(function ($idx, $p) {
        return [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'name' => $p['name'],
            'url' => site_url('san-pham/' . $p['slug'])
        ];
    }, array_keys($products), $products))
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container">
        <div class="shop-layout">
            <aside class="sidebar" aria-label="Bộ lọc">
                <div class="widget">
                    <h3 class="widget-title">Danh mục sản phẩm</h3>
                    <ul class="cat-list">
                        <li>
                            <a href="<?= e(site_url('san-pham')) ?>" class="<?= !$currentCat ? 'active' : '' ?>">
                                <span>Tất cả sản phẩm</span>
                                <span class="cat-count"><?= $total ?></span>
                            </a>
                        </li>
                        <?php foreach ($categories as $c): ?>
                            <li>
                                <a href="<?= e(site_url('danh-muc/' . $c['slug'])) ?>" class="<?= ($currentCat && $currentCat['id'] == $c['id']) ? 'active' : '' ?>">
                                    <span><?= e($c['name']) ?></span>
                                    <span class="cat-count"><?= (int)($catCounts[$c['id']] ?? 0) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="widget">
                    <h3 class="widget-title">Cần tư vấn?</h3>
                    <p style="font-size:0.9rem;color:var(--c-text-muted);line-height:1.6;margin-bottom:16px;">Chưa chọn được sản phẩm? Gọi để được tư vấn miễn phí.</p>
                    <div style="padding:14px;background:linear-gradient(135deg,rgba(139,94,52,0.08),rgba(232,163,61,0.15));border-radius:10px;display:flex;align-items:center;gap:12px;">
                        <div style="width:42px;height:42px;border-radius:50%;background:var(--c-primary);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <div>
                            <div style="font-size:0.78rem;color:var(--c-text-muted);">Hotline</div>
                            <a href="tel:0901234567" style="font-weight:700;color:var(--c-primary);font-size:1rem;">0901 234 567</a>
                        </div>
                    </div>
                </div>
            </aside>

            <div>
                <div class="shop-toolbar">
                    <div class="shop-count">
                        Hiển thị <strong><?= ($offset + 1) ?></strong> - <strong><?= min($offset + $perPage, $total) ?></strong> / <strong><?= $total ?></strong> sản phẩm
                    </div>
                    <form method="get" class="shop-sort" style="margin:0;">
                        <?php if ($categorySlug): ?><input type="hidden" name="slug" value="<?= e($categorySlug) ?>"><?php endif; ?>
                        <label for="sort-select">Sắp xếp:</label>
                        <select id="sort-select" name="sort" onchange="this.form.submit()">
                            <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Mặc định</option>
                            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Giá: thấp → cao</option>
                            <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Giá: cao → thấp</option>
                            <option value="name-asc" <?= $sort === 'name-asc' ? 'selected' : '' ?>>Tên: A → Z</option>
                        </select>
                    </form>
                </div>

                <?php if (!empty($products)): ?>
                    <div class="product-grid">
                        <?php foreach ($products as $product):
                            include __DIR__ . '/../includes/product-card.php';
                        endforeach; ?>
                    </div>
                <?php
                    $paginationUrl = $currentCat ? site_url('danh-muc/' . $currentCat['slug']) : site_url('san-pham');
                    include __DIR__ . '/../includes/pagination.php';
                else:
                ?>
                    <div class="no-products">
                        <div class="no-products-icon"></div>
                        <h3>Chưa có sản phẩm trong danh mục này</h3>
                        <p>Chúng tôi đang cập nhật sản phẩm. Vui lòng quay lại sau.</p>
                        <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-primary mt-sm">Xem tất cả sản phẩm</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
