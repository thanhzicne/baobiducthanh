<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$currentPage = 'news';
$pageNum = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($settings['items_per_page'] ?? 9);

try {
    $totalStmt = $pdo->query('SELECT COUNT(*) FROM news WHERE is_published = 1');
    $total = (int)$totalStmt->fetchColumn();
} catch (Exception $e) {
    $total = 0;
}

$totalPages = max(1, (int)ceil($total / $perPage));
$offset = ($pageNum - 1) * $perPage;

$newsList = [];
try {
    $st = $pdo->prepare('SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC LIMIT ' . (int)$offset . ', ' . (int)$perPage);
    $st->execute();
    $newsList = $st->fetchAll();
} catch (Exception $e) {
}

$pageTitle = 'Tin tức & Kiến thức bao bì';
$pageMetaDesc = 'Tin tức, kiến thức về bao bì công nghiệp, mẹo giảm chi phí đóng gói và ưu đãi từ Bao bì Đức Thành.';
$breadcrumb = [['label' => 'Tin tức']];
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Blog',
    'name' => $pageTitle,
    'description' => $pageMetaDesc,
    'url' => site_url('tin-tuc')
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container">
        <?php if (!empty($newsList)): ?>
            <div class="news-grid">
                <?php foreach ($newsList as $news): include __DIR__ . '/../includes/news-card.php';
                endforeach; ?>
            </div>
        <?php
            $paginationUrl = site_url('tin-tuc');
            include __DIR__ . '/../includes/pagination.php';
        else:
        ?>
            <div class="no-products">
                <div class="no-products-icon">📰</div>
                <h3>Chưa có tin tức</h3>
                <p>Nội dung sẽ được cập nhật sớm nhất.</p>
                <a href="<?= e(site_url()) ?>" class="btn btn-primary mt-sm">Về trang chủ</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
