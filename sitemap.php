<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Content-Type-Options: nosniff');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

function urlNode($loc, $lastmod = '', $changefreq = 'weekly', $priority = '0.5') {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . '</loc>' . "\n";
    if ($lastmod) echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
    echo '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
    echo '    <priority>' . $priority . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

$now = date('Y-m-d');

urlNode(site_url(), $now, 'daily', '1.0');
urlNode(site_url('gioi-thieu'), $now, 'monthly', '0.7');
urlNode(site_url('san-pham'), $now, 'weekly', '0.9');
urlNode(site_url('tin-tuc'), $now, 'weekly', '0.8');
urlNode(site_url('lien-he'), $now, 'monthly', '0.6');
urlNode(site_url('gio-bao-gia'), $now, 'weekly', '0.5');
urlNode(site_url('search'), $now, 'monthly', '0.3');

try {
    require_once __DIR__ . '/includes/db.php';

    $cats = $pdo->query('SELECT slug, updated_at FROM categories WHERE is_active = 1 ORDER BY id ASC');
    while ($c = $cats->fetch()) {
        $lm = !empty($c['updated_at']) ? date('Y-m-d', strtotime($c['updated_at'])) : $now;
        urlNode(site_url('danh-muc/' . $c['slug']), $lm, 'weekly', '0.7');
    }

    $prods = $pdo->query('SELECT slug, updated_at FROM products WHERE is_active = 1 ORDER BY id DESC');
    while ($p = $prods->fetch()) {
        $lm = !empty($p['updated_at']) ? date('Y-m-d', strtotime($p['updated_at'])) : $now;
        urlNode(site_url('san-pham/' . $p['slug']), $lm, 'weekly', '0.6');
    }

    $news = $pdo->query('SELECT slug, updated_at FROM news WHERE is_published = 1 ORDER BY created_at DESC');
    while ($n = $news->fetch()) {
        $lm = !empty($n['updated_at']) ? date('Y-m-d', strtotime($n['updated_at'])) : $now;
        urlNode(site_url('tin-tuc/' . $n['slug']), $lm, 'monthly', '0.5');
    }

    $pages = $pdo->query('SELECT slug, updated_at FROM pages WHERE is_active = 1');
    while ($pg = $pages->fetch()) {
        $lm = !empty($pg['updated_at']) ? date('Y-m-d', strtotime($pg['updated_at'])) : $now;
        urlNode(site_url('page/' . $pg['slug']), $lm, 'yearly', '0.4');
    }
} catch (Exception $e) {
    error_log('Sitemap error: ' . $e->getMessage());
}

echo '</urlset>' . "\n";
