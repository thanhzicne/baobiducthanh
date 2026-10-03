<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/customer.php';
start_app_session();

$pageTitle = $pageTitle ?? null;
$pageMetaDesc = $pageMetaDesc ?? null;
$pageCanonical = $pageCanonical ?? null;
$currentPage = $currentPage ?? 'home';
$ogType = $ogType ?? 'website';
$ogImage = $ogImage ?? site_url('assets/images/og-default.jpg');

$settings = [];
try {
    require_once __DIR__ . '/db.php';
    $settings = get_all_settings($pdo);
} catch (Exception $e) {
    $settings = [
        'site_name' => 'Công ty Bao bì Đức Thành',
        'site_slogan' => 'Bao bì chất lượng, giá tốt, giao hàng nhanh',
        'working_hours' => 'Thứ 2 - Thứ 7: 08:00 - 18:00',
        'hotline' => '0901 234 567',
        'phone' => '0901 234 567',
        'email' => 'contact@baobithanhdat.vn',
        'facebook_url' => 'https://www.facebook.com/ThanhfPham',
        'seo_default_title' => 'Bao bì Đức Thành - Thùng Carton, Màng PE, Băng Keo',
        'seo_default_description' => 'Cung cấp thùng carton 3 lớp, 5 lớp, màng PE, băng keo chất lượng cao cho doanh nghiệp B2B.',
        'seo_default_keywords' => 'bao bì, thùng carton, màng PE, băng keo'
    ];
}

$siteName = 'Bao bì Đức Thành';
$metaTitle = $pageTitle ? ($pageTitle . ' | ' . $siteName) : ($settings['seo_default_title'] ?? $siteName);
$metaTitle = str_ireplace(['Bao Bì Thành Đạt', 'Bao bì Thành Đạt', 'Bao bì Thành Dạt'], 'Bao bì Đức Thành', $metaTitle);
$metaDesc = $pageMetaDesc ?? ($settings['seo_default_description'] ?? '');
$canonical = $pageCanonical ?? (isset($_SERVER['REQUEST_URI']) ? site_url(ltrim($_SERVER['REQUEST_URI'], '/')) : site_url());
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= e($metaTitle) ?></title>
    <meta name="description" content="<?= e($metaDesc) ?>">
    <meta name="keywords" content="<?= e($settings['seo_default_keywords'] ?? '') ?>">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <link rel="icon" type="image/x-icon" href="<?= e(site_url('assets/images/favicon.ico')) ?>">
    <meta property="og:locale" content="vi_VN">
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:title" content="<?= e($metaTitle) ?>">
    <meta property="og:description" content="<?= e($metaDesc) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($metaTitle) ?>">
    <meta name="twitter:description" content="<?= e($metaDesc) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= e(site_url('assets/css/style.css')) ?>">
    <?php if (!empty($jsonLd)): ?>
        <script type="application/ld+json">
            <?= display_brand_text(is_string($jsonLd) ? $jsonLd : json_encode($jsonLd, JSON_UNESCAPED_UNICODE)) ?>
        </script>
    <?php endif; ?>
</head>

<body>
    <a href="#main-content" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">Đi đến nội dung chính</a>

    <div class="topbar hidden-mobile">
        <div class="container">
            <div class="topbar-info">
                <span title="Giờ làm việc">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <?= e($settings['working_hours'] ?? 'Thứ 2 - Thứ 7: 08:00 - 18:00') ?>
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    Hotline: <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['hotline'] ?? '0901234567')) ?>"><strong style="color:#fff"><?= e($settings['hotline'] ?? '0901 234 567') ?></strong></a>
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <a href="mailto:<?= e($settings['email'] ?? 'contact@baobithanhdat.vn') ?>"><?= e($settings['email'] ?? '') ?></a>
                </span>
            </div>
            <div class="topbar-social">
                <?php if (!empty($settings['facebook_url'])): ?>
                    <a href="<?= e($settings['facebook_url']) ?>" target="_blank" rel="noopener" aria-label="Facebook">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                        </svg>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <header class="site-header">
        <div class="container site-header-inner">
            <a class="logo" href="<?= e(site_url()) ?>" aria-label="<?= e($siteName) ?>">
                <div class="logo-icon">
                    <img src="<?= e(site_url('assets/images/Logo.png')) ?>" alt="Bao bì Đức Thành">
                </div>
                <div class=" logo-text">
                    <span class="logo-brand">Bao bì Đức Thành</span>
                    <span class="logo-slogan"><?= e($settings['site_slogan'] ?? '') ?></span>
                </div>
            </a>
            <nav class="main-nav" aria-label="Điều hướng chính">
                <a href="<?= e(site_url()) ?>" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Trang chủ</a>
                <a href="<?= e(site_url('gioi-thieu')) ?>" class="<?= $currentPage === 'about' ? 'active' : '' ?>">Giới thiệu</a>
                <a href="<?= e(site_url('san-pham')) ?>" class="<?= $currentPage === 'products' ? 'active' : '' ?>">Sản phẩm</a>
                <a href="<?= e(site_url('tin-tuc')) ?>" class="<?= $currentPage === 'news' ? 'active' : '' ?>">Tin tức</a>
                <a href="<?= e(site_url('lien-he')) ?>" class="<?= $currentPage === 'contact' ? 'active' : '' ?>">Liên hệ</a>
            </nav>
            <div class="header-actions">
                <form class="search-box header-search-form hidden-mobile" action="<?= e(site_url('search')) ?>" method="get" role="search">
                    <input type="search" name="q" placeholder="Tìm sản phẩm..." aria-label="Tìm kiếm" value="<?= e($_GET['q'] ?? '') ?>" maxlength="100">
                    <button type="submit" aria-label="Tìm kiếm">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                </form>
                <?php if (!empty($_SESSION['customer_id'])): ?>
                    <a class="btn btn-outline btn-sm" href="<?= e(site_url('tai-khoan')) ?>" aria-label="Trang cá nhân"><?= e($_SESSION['customer_username'] ?? $_SESSION['customer_name'] ?? 'Tài khoản') ?></a>
                    <a class="btn btn-outline btn-sm" href="<?= e(site_url('lich-su-mua-hang')) ?>">Lịch sử mua hàng</a>
                    <form method="post" action="<?= e(site_url('dang-xuat')) ?>" style="display:inline-flex;margin:0">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline btn-sm" type="submit">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-outline btn-sm" href="<?= e(site_url('dang-nhap')) ?>" aria-label="Tài khoản khách hàng">Đăng nhập</a>
                <?php endif; ?>
                <a class="cart-btn" href="<?= e(site_url('gio-bao-gia')) ?>" aria-label="Giỏ báo giá">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <?php $qCount = quote_cart_count();
                    if ($qCount > 0): ?>
                        <span class="cart-badge" id="cartBadge"><?= $qCount ?></span>
                    <?php else: ?>
                        <span class="cart-badge" id="cartBadge" style="display:none">0</span>
                    <?php endif; ?>
                </a>
                <button class="menu-toggle" type="button" aria-label="Mo menu">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>
    <div class="nav-overlay" aria-hidden="true"></div>
    <main id="main-content">