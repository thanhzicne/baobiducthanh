<?php
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/helpers.php';
}
$currentPage = '404';
$pageTitle = 'Trang không tồn tại - 404';
$pageMetaDesc = 'Trang bạn tìm kiếm không tồn tại hoặc đã bị xóa.';
http_response_code(404);
include __DIR__ . '/../includes/header.php';
?>
<section class="section">
    <div class="container">
        <div class="error-404">
            <div class="error-code">404</div>
            <h2>Trang không tồn tại</h2>
            <p>Trang bạn tìm kiếm có thể đã bị xóa, đổi tên hoặc tạm thời không khả dụng. Hãy thử tìm kiếm hoặc quay lại trang chủ.</p>
            <form class="search-form-big" action="<?= e(site_url('search')) ?>" method="get" role="search" style="margin-bottom:24px;">
                <input type="search" name="q" class="form-control" placeholder="Tìm kiếm sản phẩm, tin tức..." minlength="2" required>
                <button type="submit">Tìm kiếm</button>
            </form>
            <div class="error-actions">
                <a href="<?= e(site_url()) ?>" class="btn btn-primary btn-lg">
                    Quay lại trang chủ
                </a>
                <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-outline btn-lg">
                    Xem sản phẩm
                </a>
                <a href="<?= e(site_url('lien-he')) ?>" class="btn btn-accent btn-lg">
                    Liên hệ tư vấn
                </a>
            </div>
        </div>
    </div>
</section>
<section class="section section-bg" style="padding-top:0;">
    <div class="container">
        <div class="section-title">
            <h2>Sản phẩm nổi bật</h2>
            <p>Các sản phẩm bán chạy được đối tác B2B quan tâm nhiều nhất</p>
        </div>
        <?php
        try {
            if (!isset($pdo)) require_once __DIR__ . '/../includes/db.php';
            $ps = $pdo->query('SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 ORDER BY p.is_featured DESC, p.id DESC LIMIT 4');
            $featuredProducts = $ps->fetchAll();
        } catch (Exception $e) {
            $featuredProducts = [];
        }
        if (!empty($featuredProducts)):
        ?>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): include __DIR__ . '/../includes/product-card.php';
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
include __DIR__ . '/../includes/footer.php';
