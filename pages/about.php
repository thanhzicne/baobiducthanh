<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$currentPage = 'about';
$page = null;
try {
    $st = $pdo->prepare('SELECT * FROM pages WHERE slug = ? AND is_active = 1 LIMIT 1');
    $st->execute(['about']);
    $page = $st->fetch();
} catch (Exception $e) {
}

if (!$page) {
    $page = [
        'title' => 'Giới thiệu Bao bì Đức Thành',
        'content' => '<h2>Lịch sử và tầm nhìn</h2><p>Công ty TNHH Bao bì Đức Thành thành lập năm 2014, hướng tới trở thành nhà cung cấp bao bì công nghiệp hàng đầu Việt Nam, phục vụ doanh nghiệp B2B với chất lượng và giá cả cạnh tranh.</p><h2>Sứ mệnh</h2><p>Cung cấp giải pháp bao bì toàn diện, chất lượng cao, giúp doanh nghiệp nâng cao năng lực cạnh tranh trong nước và quốc tế.</p><h2>Giá trị cốt lõi</h2><ul><li><strong>Chất lượng</strong>: Sản phẩm đạt chuẩn ISO 9001:2015</li><li><strong>Giá cả</strong>: Trực tiếp từ nhà máy, không qua trung gian</li><li><strong>Giao hàng</strong>: 24 giờ nội thành, 48 giờ toàn quốc</li><li><strong>Phục vụ</strong>: Tư vấn 24/7, bảo hành sản phẩm</li></ul>'
    ];
}

$pageTitle = $page['meta_title'] ?? $page['title'];
$pageMetaDesc = $page['meta_description'] ?? excerpt(strip_tags($page['content']), 160);
$breadcrumb = [
    ['label' => 'Giới thiệu']
];
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    'name' => $pageTitle,
    'description' => $pageMetaDesc,
    'url' => site_url('gioi-thieu')
];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section">
    <div class="container">
        <div class="intro-grid">
            <div class="intro-media">
                <img src="<?= e(site_url('assets/images/Logo.png')) ?>" alt="Bao bì Đức Thành">
            </div>
            <div class="intro-content">
                <span class="text-accent" style="font-weight:600;font-size:0.9rem;letter-spacing:1px;text-transform:uppercase;">Bao bì Đức Thành</span>
                <h1 style="margin-top:10px"><?= e(strip_tags($page['title'])) ?></h1>
                <div class="intro-stats" style="margin-bottom:28px">
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
                        <div class="stat-number">50<span class="accent">+</span></div>
                        <div class="stat-label">Cán bộ, công nhân</div>
                    </div>
                </div>
                <a href="<?= e(site_url('san-pham')) ?>" class="btn btn-primary btn-lg">Xem sản phẩm của chúng tôi →</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-bg">
    <div class="container" style="max-width:900px">
        <div class="product-content">
            <?= display_brand_text($page['content'] ?? '') ?>
        </div>
        <div class="mt-md" style="display:flex;gap:14px;flex-wrap:wrap;justify-content:center;">
            <a href="<?= e(site_url('lien-he')) ?>" class="btn btn-accent btn-lg">Nhận tư vấn báo giá miễn phí</a>
            <a href="tel:0901234567" class="btn btn-outline btn-lg">Gọi ngay: 0901 234 567</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Vì sao chọn Bao bì Đức Thành?</h2>
            <p>4 lý do hơn 5.000 doanh nghiệp tin tưởng chúng tôi</p>
        </div>
        <div class="cat-grid">
            <div class="cat-card">
                <div class="cat-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="7"></circle>
                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline>
                    </svg>
                </div>
                <div class="cat-name">Chất lượng quốc tế</div>
                <div class="cat-desc">Quy trình sản xuất đạt chuẩn ISO 9001:2015, kiểm tra sản phẩm trước khi giao.</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"></line>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                    </svg>
                </div>
                <div class="cat-name">Giá cả cạnh tranh</div>
                <div class="cat-desc">Sản xuất trực tiếp tại nhà máy 3.000 m², ưu đãi cho đơn hàng số lượng lớn.</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                        <circle cx="5.5" cy="18.5" r="2.5"></circle>
                        <circle cx="18.5" cy="18.5" r="2.5"></circle>
                    </svg>
                </div>
                <div class="cat-name">Giao hàng nhanh toàn quốc</div>
                <div class="cat-desc">Giao nhanh tại TP. HCM và toàn quốc; miễn phí giao hàng cho đơn từ 2 triệu đồng.</div>
            </div>
            <div class="cat-card">
                <div class="cat-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="4" y1="21" x2="4" y2="14"></line>
                        <line x1="4" y1="10" x2="4" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="3"></line>
                        <line x1="20" y1="21" x2="20" y2="16"></line>
                        <line x1="20" y1="12" x2="20" y2="3"></line>
                        <line x1="1" y1="14" x2="7" y2="14"></line>
                        <line x1="9" y1="8" x2="15" y2="8"></line>
                        <line x1="17" y1="16" x2="23" y2="16"></line>
                    </svg>
                </div>
                <div class="cat-name">Sản xuất theo yêu cầu</div>
                <div class="cat-desc">Hỗ trợ in logo, thông tin và tùy chỉnh kích thước theo yêu cầu khách hàng.</div>
            </div>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
