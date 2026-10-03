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

$page = null;
try {
    $st = $pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_active = 1 LIMIT 1');
    $st->execute([$slug]);
    $page = $st->fetch();
} catch (Exception $e) {
}

if (!$page) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$currentPage = 'page';
$pageTitle = $page['meta_title'] ?? $page['title'];
$pageMetaDesc = $page['meta_description'] ?? excerpt(strip_tags($page['content']), 160);
$breadcrumb = [
    ['label' => 'Trang'],
    ['label' => $page['title']]
];

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $pageTitle,
    'description' => $pageMetaDesc,
    'url' => site_url('page/' . $slug)
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>
<section class="section">
    <div class="container" style="max-width:900px;">
        <div class="product-content">
            <h1 style="text-align:center;margin-bottom:30px;"><?= e(strip_tags($page['title'])) ?></h1>
            <?= display_brand_text($page['content'] ?? '') ?>
        </div>
        <div class="text-center mt-md">
            <a href="<?= e(site_url()) ?>" class="btn btn-outline btn-lg">← Quay về trang chủ</a>
        </div>
    </div>
</section>
<?php
include __DIR__ . '/../includes/footer.php';
