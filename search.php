<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';

$q = trim($_GET['q'] ?? '');
$tab = $_GET['tab'] ?? 'all';
$pageNum = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$validTabs = ['all', 'product', 'news'];
if (!in_array($tab, $validTabs)) $tab = 'all';

$currentPage = 'search';
$pageTitle = $q ? ('Tìm kiếm: "' . $q . '"') : 'Tìm kiếm';
$pageMetaDesc = 'Tìm kiếm sản phẩm, tin tức Bao bì Đức Thành và khám phá kết quả phù hợp với từ khóa của bạn.';
$breadcrumb = [
    ['label' => 'Tìm kiếm' . ($q ? ': "' . e($q) . '"' : '')]
];

$minLength = 2;
$tooShort = $q !== '' && mb_strlen($q, 'UTF-8') < $minLength;
$products = [];
$newsResults = [];
$totalProducts = 0;
$totalNews = 0;

if ($q && !$tooShort) {
    $qEscaped = str_replace(['%', '_'], ['\\%', '\\_'], $q);
    $like = '%' . $qEscaped . '%';

    try {
        if ($tab === 'all' || $tab === 'product') {
            $psTotal = $pdo->prepare('SELECT COUNT(*) FROM products WHERE is_active = 1 AND (name LIKE ? OR short_description LIKE ? OR description LIKE ?)');
            $psTotal->execute([$like, $like, $like]);
            $totalProducts = (int)$psTotal->fetchColumn();

            $offset = ($pageNum - 1) * $perPage;
            $ps = $pdo->prepare('SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND (p.name LIKE ? OR p.short_description LIKE ? OR p.description LIKE ?) ORDER BY p.is_featured DESC, p.id DESC LIMIT ' . (int)$offset . ', ' . (int)$perPage);
            $ps->execute([$like, $like, $like]);
            $products = $ps->fetchAll();
        }

        if ($tab === 'all' || $tab === 'news') {
            $nsTotal = $pdo->prepare('SELECT COUNT(*) FROM news WHERE is_published = 1 AND (title LIKE ? OR summary LIKE ? OR content LIKE ?)');
            $nsTotal->execute([$like, $like, $like]);
            $totalNews = (int)$nsTotal->fetchColumn();

            if ($tab === 'news') {
                $offset = ($pageNum - 1) * $perPage;
            } else {
                $offset = 0;
            }
            $limit = ($tab === 'news') ? $perPage : 4;
            $ns = $pdo->prepare('SELECT * FROM news WHERE is_published = 1 AND (title LIKE ? OR summary LIKE ? OR content LIKE ?) ORDER BY created_at DESC LIMIT ' . (int)$offset . ', ' . (int)$limit);
            $ns->execute([$like, $like, $like]);
            $newsResults = $ns->fetchAll();
        }
    } catch (Exception $e) {
        error_log('Search error: ' . $e->getMessage());
    }
}

$totalAll = $totalProducts + $totalNews;
if ($tab === 'product') $totalForPagination = $totalProducts;
elseif ($tab === 'news') $totalForPagination = $totalNews;
else $totalForPagination = $totalProducts;

$totalPages = max(1, (int)ceil($totalForPagination / $perPage));

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/breadcrumb.php';
?>
<section class="section section-sm" style="padding-top:28px;">
    <div class="container" style="max-width:1100px;">
        <form class="search-form-big" action="<?= e(site_url('search')) ?>" method="get" role="search">
            <input type="search" name="q" class="form-control" placeholder="Tìm kiếm sản phẩm, tin tức (ít nhất 2 ký tự)..." value="<?= e($q) ?>" minlength="<?= $minLength ?>" maxlength="100" required>
            <button type="submit">Tìm kiếm</button>
        </form>

        <?php if ($tooShort): ?>
            <div class="alert alert-warning">
                Vui lòng nhập từ khóa tìm kiếm ít nhất <strong><?= $minLength ?> ký tự</strong> để kết quả chính xác hơn.
            </div>
        <?php endif; ?>

        <?php if ($q && !$tooShort): ?>
            <div class="search-result-head">
                <div class="search-result-count">
                    Có <strong><?= $totalAll ?></strong> kết quả phù hợp với: <strong style="color:var(--c-primary);">"<?= e($q) ?>"</strong>
                </div>
                <div class="tab-filters" role="tablist">
                    <button type="button" class="tab-filter <?= $tab === 'all' ? 'active' : '' ?>" onclick="location.href='<?= e(site_url('search?q=' . urlencode($q) . '&tab=all')) ?>'">
                        Tất cả (<?= $totalAll ?>)
                    </button>
                    <button type="button" class="tab-filter <?= $tab === 'product' ? 'active' : '' ?>" onclick="location.href='<?= e(site_url('search?q=' . urlencode($q) . '&tab=product')) ?>'">
                        Sản phẩm (<?= $totalProducts ?>)
                    </button>
                    <button type="button" class="tab-filter <?= $tab === 'news' ? 'active' : '' ?>" onclick="location.href='<?= e(site_url('search?q=' . urlencode($q) . '&tab=news')) ?>'">
                        Tin tức (<?= $totalNews ?>)
                    </button>
                </div>
            </div>

            <?php if ($totalAll === 0): ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>Không tìm thấy kết quả</h3>
                    <p>Không có kết quả phù hợp với "<strong><?= e($q) ?></strong>". Hãy thử từ khóa khác hoặc xem gợi ý bên dưới.</p>
                    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                        <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-primary">Xem tất cả sản phẩm</a>
                        <a href="<?= e(site_url('tin-tuc')) ?>" class="btn btn-outline">Đọc tin tức</a>
                    </div>
                </div>
            <?php else: ?>

                <?php if (($tab === 'all' || $tab === 'product') && !empty($products)): ?>
                    <div class="mb-md">
                        <h3 style="font-size:1.1rem;margin-bottom:16px;color:var(--c-primary);">
                            <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:rgba(139,94,52,0.08);">Sản phẩm (<?= $totalProducts ?>)</span>
                        </h3>
                        <div class="product-grid">
                            <?php foreach ($products as $product): include __DIR__ . '/includes/product-card.php';
                            endforeach; ?>
                        </div>
                        <?php if ($tab === 'product' && $totalPages > 1):
                            $paginationUrl = site_url('search');
                            include __DIR__ . '/includes/pagination.php';
                        endif; ?>
                        <?php if ($tab === 'all' && $totalProducts > count($products)): ?>
                            <div style="text-align:center;margin-top:20px;">
                                <a href="<?= e(site_url('search?q=' . urlencode($q) . '&tab=product')) ?>" class="btn btn-outline">Xem tất cả <?= $totalProducts ?> sản phẩm →</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (($tab === 'all' || $tab === 'news') && !empty($newsResults)): ?>
                    <div style="margin-top:36px;">
                        <h3 style="font-size:1.1rem;margin-bottom:16px;color:var(--c-accent);">
                            <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:rgba(232,163,61,0.12);">Tin tức & Kiến thức (<?= $totalNews ?>)</span>
                        </h3>
                        <div class="news-grid">
                            <?php foreach ($newsResults as $news): include __DIR__ . '/includes/news-card.php';
                            endforeach; ?>
                        </div>
                        <?php if ($tab === 'news' && $totalPages > 1):
                            $paginationUrl = site_url('search');
                            include __DIR__ . '/includes/pagination.php';
                        endif; ?>
                        <?php if ($tab === 'all' && $totalNews > count($newsResults)): ?>
                            <div style="text-align:center;margin-top:20px;">
                                <a href="<?= e(site_url('search?q=' . urlencode($q) . '&tab=news')) ?>" class="btn btn-outline">Xem tất cả <?= $totalNews ?> tin tức →</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php
include __DIR__ . '/includes/footer.php';
