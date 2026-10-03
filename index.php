<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';

$currentPage = 'home';

$banners = [];
$featuredCats = [];
$featuredProducts = [];
$aboutPage = null;
$testimonials = [];
$latestNews = [];
$settings = [];

try {
    $settings = get_all_settings($pdo);

    $bs = $pdo->query('SELECT * FROM banners WHERE position = "home-slider" AND is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 5');
    $banners = $bs->fetchAll();

    $cs = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) as product_count FROM categories c WHERE c.is_active = 1 AND c.is_featured = 1 ORDER BY c.sort_order ASC, c.id ASC LIMIT 4');
    $featuredCats = $cs->fetchAll();
    if (empty($featuredCats)) {
        $cs = $pdo->query('SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.is_active = 1) as product_count FROM categories c WHERE c.is_active = 1 ORDER BY c.sort_order ASC, c.id ASC LIMIT 4');
        $featuredCats = $cs->fetchAll();
    }

    $ps = $pdo->query('SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 AND p.is_featured = 1 ORDER BY p.sort_order ASC, p.id DESC LIMIT 8');
    $featuredProducts = $ps->fetchAll();
    if (count($featuredProducts) < 8) {
        $ps2 = $pdo->query('SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.is_active = 1 ORDER BY p.id DESC LIMIT 8');
        $rest = $ps2->fetchAll();
        $ids = array_column($featuredProducts, 'id');
        foreach ($rest as $rp) {
            if (!in_array($rp['id'], $ids) && count($featuredProducts) < 8) {
                $featuredProducts[] = $rp;
            }
        }
    }

    $ap = $pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_active = 1 LIMIT 1');
    $ap->execute(['about']);
    $aboutPage = $ap->fetch();

    $ts = $pdo->query('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id DESC LIMIT 4');
    $testimonials = $ts->fetchAll();

    $ns = $pdo->query('SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC LIMIT 6');
    $latestNews = $ns->fetchAll();
} catch (Exception $e) {
    error_log('Homepage DB error: ' . $e->getMessage());
}

$siteName = 'Bao bì Đức Thành';
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => $siteName,
    'url' => site_url(),
    'logo' => site_url('assets/images/logo.png'),
    'description' => $settings['seo_default_description'] ?? '',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $settings['address'] ?? '',
        'addressLocality' => 'Ho Chi Minh',
        'addressCountry' => 'VN'
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => $settings['phone'] ?? '',
        'email' => $settings['email'] ?? '',
        'contactType' => 'customer service',
        'availableLanguage' => ['Vietnamese']
    ],
    'sameAs' => array_values(array_filter([
        $settings['facebook_url'] ?? '',
        $settings['zalo_url'] ?? ''
    ]))
];

include __DIR__ . '/includes/header.php';
?>

<?php if (!empty($banners)): ?>
    <section class="banner-slider" aria-label="Banner noi bat">
        <?php foreach ($banners as $idx => $b): ?>
            <div class="banner-slide <?= $idx === 0 ? 'active' : '' ?>">
                <?php if (!empty($b['image'])): ?><img class="banner-slide-image" src="<?= e(upload_url($b['image'])) ?>" alt="" aria-hidden="true"><?php endif; ?>
                <div class="container">
                    <div class="banner-content">
                        <?php if (!empty($b['subtitle'])): ?>
                            <span class="banner-subtitle"><?= e($b['subtitle']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($b['title'])): ?>
                            <h1 class="banner-title"><?= e($b['title']) ?></h1>
                        <?php endif; ?>
                        <?php if (!empty($b['description'])): ?>
                            <p class="banner-desc"><?= e($b['description']) ?></p>
                        <?php endif; ?>
                        <div class="banner-actions">
                            <a href="<?= e(!empty($b['link']) ? $b['link'] : site_url('san-pham')) ?>" class="btn btn-light btn-lg">Xem sản phẩm</a>
                            <a href="<?= e(site_url('lien-he')) ?>" class="btn btn-outline-light btn-lg">Liên hệ tư vấn</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (count($banners) > 1): ?>
            <button type="button" class="slider-nav slider-prev" aria-label="Trang trước">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <button type="button" class="slider-nav slider-next" aria-label="Trang sau">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            <div class="slider-dots" role="tablist" aria-label="Chọn trang trình chiếu"></div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Danh mục nổi bật</h2>
            <p>Đầy đủ các loại bao bì công nghiệp, phù hợp với nhu cầu doanh nghiệp B2B</p>
        </div>
        <div class="cat-grid">
            <?php
            $catIcons = ['📦', '🗃️', '🎞️', '📎', '🏷️', '🛡️'];
            if (!empty($featuredCats)):
                foreach ($featuredCats as $idx => $c):
                    $icon = $catIcons[$idx % count($catIcons)] ?? '📦';
                    $count = (int)($c['product_count'] ?? 0);
            ?>
                    <a href="<?= e(site_url('danh-muc/' . $c['slug'])) ?>" class="cat-card">
                        <div class="cat-icon"><?php if (!empty($c['image'])): ?><img src="<?= e(upload_url($c['image'])) ?>" alt=""><?php else: ?><?= $icon ?><?php endif; ?></div>
                        <div class="cat-name"><?= e($c['name']) ?></div>
                        <?php if (!empty($c['description'])): ?>
                            <div class="cat-desc"><?= e(excerpt($c['description'], 70)) ?></div>
                        <?php else: ?>
                            <div class="cat-desc text-muted">Gồm <?= $count ?> sản phẩm đa dạng</div>
                        <?php endif; ?>
                    </a>
                <?php endforeach;
            else: ?>
                <a href="<?= e(site_url('danh-muc/thung-carton-3-lop')) ?>" class="cat-card">
                    <div class="cat-icon">📦</div>
                    <div class="cat-name">Thùng Carton 3 Lớp</div>
                    <div class="cat-desc">Nhiều kích thước, giá từ 2.500đ/cái</div>
                </a>
                <a href="<?= e(site_url('danh-muc/thung-carton-5-lop')) ?>" class="cat-card">
                    <div class="cat-icon">🗃️</div>
                    <div class="cat-name">Thùng Carton 5 Lớp</div>
                    <div class="cat-desc">Chịu lực cao, chống sốc, giá từ 9.500đ</div>
                </a>
                <a href="<?= e(site_url('danh-muc/mang-pe')) ?>" class="cat-card">
                    <div class="cat-icon">🎞️</div>
                    <div class="cat-name">Màng PE</div>
                    <div class="cat-desc">Trong suốt, chống nước, nhiều độ dày</div>
                </a>
                <a href="<?= e(site_url('danh-muc/bang-keo')) ?>" class="cat-card">
                    <div class="cat-icon">📎</div>
                    <div class="cat-name">Băng Kéo</div>
                    <div class="cat-desc">Dính cao, bền dẻo, giá 18.000đ/cuộn</div>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section-bg">
    <div class="container">
        <div class="section-title">
            <h2>Sản phẩm nổi bật</h2>
            <p>Top sản phẩm bán chạy B2B, chất lượng đảm bảo, giá cạnh tranh, giao hàng nhanh</p>
        </div>
        <div class="product-grid">
            <?php
            if (!empty($featuredProducts)):
                foreach ($featuredProducts as $product):
                    include __DIR__ . '/includes/product-card.php';
                endforeach;
            else:
                echo '<div class="no-products" style="grid-column:1/-1"><div class="no-products-icon">📦</div><h3>Chưa có sản phẩm</h3><p>Sản phẩm sẽ được cập nhật sớm nhất.</p></div>';
            endif;
            ?>
        </div>
        <div class="view-all-wrap">
            <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-outline btn-lg">Xem tất cả sản phẩm →</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="intro-grid">
            <div class="intro-media">
                <img src="<?= e(site_url('assets/images/Logo.png')) ?>" alt="Bao bì Đức Thành">
            </div>
            <div class="intro-content">
                <span class="text-accent" style="font-weight:600;font-size:0.9rem;letter-spacing:1px;text-transform:uppercase;">Về chúng tôi</span>
                <?php if ($aboutPage): ?>
                    <h2 style="margin-top:10px"><?= e(strip_tags($aboutPage['title'])) ?></h2>

                    <p class="lead">
                        <?= e($settings['site_slogan'] ?? 'Bao bì chất lượng, giá tốt, giao hàng nhanh') ?>
                    </p>
                    <div style="color:#4b5563;line-height:1.75;">
                        <?php
                        $aboutContent = strip_tags($aboutPage['content'] ?? '');
                        if (mb_strlen($aboutContent, 'UTF-8') > 650) {
                            echo e(mb_substr($aboutContent, 0, 650, 'UTF-8')) . '...';
                        } else {
                            echo e($aboutContent);
                        }
                        ?>
                    </div>
                <?php else: ?>
                    <h2 style="margin-top:10px">Bao bì Đức Thành - Nhà cung cấp bao bì B2B uy tín hơn 10 năm</h2>
                    <p class="lead">Với hơn 10 năm kinh nghiệm cung cấp bao bì công nghiệp, chúng tôi phục vụ hơn 5.000 đối tác toàn quốc.</p>
                    <ul class="feature-list">
                        <li><span class="feature-icon">✓</span><span class="feature-text"><strong>Chất lượng chuẩn quốc tế</strong> - Sản xuất theo quy trình đạt ISO 9001:2015</span></li>
                        <li><span class="feature-icon">✓</span><span class="feature-text"><strong>Giá cả cạnh tranh</strong> - Trực tiếp từ nhà máy, không qua trung gian</span></li>
                        <li><span class="feature-icon">✓</span><span class="feature-text"><strong>Giao hàng nhanh 24/48 giờ</strong> - Nội thành 24 giờ, toàn quốc 48 giờ</span></li>
                        <li><span class="feature-icon">✓</span><span class="feature-text"><strong>Hỗ trợ đặt hàng số lượng lớn</strong> - In logo, tùy chỉnh kích thước theo yêu cầu</span></li>
                    </ul>
                <?php endif; ?>

                <div class="intro-stats">
                    <div class="stat-card">
                        <div class="stat-number">10<span class="accent">+</span></div>
                        <div class="stat-label">Năm kinh nghiệm</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">5000<span class="accent">+</span></div>
                        <div class="stat-label">Doanh nghiệp đối tác</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">3000<span class="accent">m²</span></div>
                        <div class="stat-label">Xưởng sản xuất</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">24<span class="accent">/7</span></div>
                        <div class="stat-label">Tư vấn hỗ trợ</div>
                    </div>
                </div>
                <div style="margin-top:28px;display:flex;gap:12px;flex-wrap:wrap;">
                    <a href="<?= e(site_url('gioi-thieu')) ?>" class="btn btn-primary btn-lg">Tìm hiểu thêm</a>
                    <a href="<?= e(site_url('lien-he')) ?>" class="btn btn-outline btn-lg">Liên hệ tư vấn</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($testimonials)): ?>
    <section class="section testimonials">
        <div class="container">
            <div class="section-title">
                <h2>Đánh giá từ đối tác</h2>
                <p>Trải nghiệm thực tế từ hơn 5.000 doanh nghiệp đang sử dụng dịch vụ Bao bì Đức Thành</p>
            </div>
            <div class="testimonial-grid">
                <?php foreach ($testimonials as $t): ?>
                    <div class="testimonial-card">
                        <div class="rating" aria-label="Đánh giá <?= (int)($t['rating'] ?? 5) ?> sao">
                            <?php
                            $stars = (int)($t['rating'] ?? 5);
                            for ($si = 1; $si <= 5; $si++):
                                $fill = $si <= $stars ? 'currentColor' : 'none';
                                echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="' . $fill . '" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>';
                            endfor;
                            ?>
                        </div>
                        <div class="testimonial-content"><?= e($t['content']) ?></div>
                        <div class="testimonial-author">
                            <div class="testimonial-avatar" aria-hidden="true"><?php if (!empty($t['avatar'])): ?><img src="<?= e(upload_url($t['avatar'])) ?>" alt=""><?php else: ?><?= e(mb_substr($t['name'], 0, 1, 'UTF-8')) ?><?php endif; ?></div>
                            <div class="testimonial-meta">
                                <div class="testimonial-name"><?= e($t['name']) ?></div>
                                <?php if (!empty($t['title'])): ?><div class="testimonial-title"><?= e($t['title']) ?></div><?php endif; ?>
                                <?php if (!empty($t['company'])): ?><div class="testimonial-company"><?= e($t['company']) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($latestNews)): ?>
    <section class="section section-bg">
        <div class="container">
            <div class="section-title">
                <h2>Tin tức & Kiến thức</h2>
                <p>Cập nhật kiến thức bao bì, mẹo giảm chi phí và ưu đãi đặc biệt</p>
            </div>
            <div class="news-grid">
                <?php
                foreach ($latestNews as $news):
                    include __DIR__ . '/includes/news-card.php';
                endforeach;
                ?>
            </div>
            <div class="view-all-wrap">
                <a href="<?= e(site_url('tin-tuc')) ?>" class="btn btn-outline btn-lg">Xem tin tức mới nhất →</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php
include __DIR__ . '/includes/footer.php';
