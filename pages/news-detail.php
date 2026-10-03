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

try {
    $st = $pdo->prepare('SELECT * FROM news WHERE slug = ? AND is_published = 1 LIMIT 1');
    $st->execute([$slug]);
    $news = $st->fetch();
} catch (Exception $e) {
    $news = null;
}

if (!$news) {
    http_response_code(404);
    include __DIR__ . '/404.php';
    exit;
}

$nid = (int)$news['id'];
try {
    $pdo->prepare('UPDATE news SET view_count = view_count + 1 WHERE id = ?')->execute([$nid]);
    $news['view_count'] = ($news['view_count'] ?? 0) + 1;
} catch (Exception $e) {
}

$currentPage = 'news';
$pageTitle = $news['meta_title'] ?? $news['title'];
$pageMetaDesc = $news['meta_description'] ?? excerpt(strip_tags($news['content'] ?? $news['summary'] ?? ''), 160);
$imageUrl = !empty($news['image']) ? upload_url($news['image']) : site_url('assets/images/og-default.jpg');
$ogType = 'article';
$breadcrumb = [
    ['label' => 'Tin tức', 'url' => site_url('tin-tuc')],
    ['label' => $news['title']]
];
$ogImage = $imageUrl;

try {
    $rp = $pdo->prepare('SELECT * FROM news WHERE is_published = 1 AND id != ? ORDER BY RAND() LIMIT 3');
    $rp->execute([$nid]);
    $related = $rp->fetchAll();
} catch (Exception $e) {
    $related = [];
}

$newsIcons = ['📰', '📝', '📢', '💼', '🔧', '📊', '🎯', '🚀'];
$icon = $newsIcons[($nid - 1) % count($newsIcons)] ?? '📰';

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $news['title'],
    'description' => $pageMetaDesc,
    'image' => [$imageUrl],
    'author' => ['@type' => 'Person', 'name' => $news['author'] ?? 'Admin'],
    'publisher' => ['@type' => 'Organization', 'name' => 'Bao bì Đức Thành', 'logo' => ['@type' => 'ImageObject', 'url' => site_url('assets/images/logo.png')]],
    'datePublished' => date('Y-m-d\TH:i:s', strtotime($news['created_at'])),
    'dateModified' => date('Y-m-d\TH:i:s', strtotime($news['updated_at'] ?? $news['created_at'])),
    'mainEntityOfPage' => site_url('tin-tuc/' . $slug)
];

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container">
        <div class="news-detail-layout">
            <article class="news-detail-main">
                <div class="news-detail-meta">
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <?= e(format_date($news['created_at'], 'd/m/Y H:i')) ?>
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Tác giả: <?= e($news['author'] ?? 'Admin') ?>
                    </span>
                    <span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <?= (int)$news['view_count'] ?> lượt xem
                    </span>
                </div>
                <h1 style="font-size:clamp(1.4rem,2.6vw,2rem);margin-bottom:20px;line-height:1.35;"><?= e($news['title']) ?></h1>

                <div class="news-image-big">
                    <?php if (!empty($news['image'])): ?>
                        <img src="<?= e($imageUrl) ?>" alt="<?= e($news['title']) ?>" style="max-height:100%;object-fit:cover;">
                    <?php else: echo $icon;
                    endif; ?>
                </div>

                <?php if (!empty($news['summary'])): ?>
                    <div style="padding:16px 20px;background:var(--c-bg-muted);border-left:3px solid var(--c-accent);border-radius:8px;margin:22px 0;">
                        <p style="margin:0;font-style:italic;color:#4b5563;line-height:1.7;">
                            <strong style="color:var(--c-primary);">Tóm tắt: </strong>
                            <?= e($news['summary']) ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="news-detail-content">
                    <?= display_brand_text($news['content'] ?? '') ?>
                </div>

                <div class="news-share">
                    <span class="news-share-label">Chia sẻ bài viết:</span>
                    <div class="news-share-btns">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(site_url('tin-tuc/' . $slug)) ?>" target="_blank" rel="noopener" aria-label="Share Facebook">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </a>
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($news['title']) ?>&url=<?= urlencode(site_url('tin-tuc/' . $slug)) ?>" target="_blank" rel="noopener" aria-label="Share X">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                        </a>
                        <a href="mailto:?subject=<?= urlencode($news['title']) ?>&body=<?= urlencode(site_url('tin-tuc/' . $slug)) ?>" aria-label="Share Email">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </a>
                        <a href="https://zalo.me/share?url=<?= urlencode(site_url('tin-tuc/' . $slug)) ?>" target="_blank" rel="noopener" aria-label="Share Zalo">Z</a>
                    </div>
                </div>
            </article>

            <aside class="sidebar toc-wrap">
                <div class="widget">
                    <div class="toc-box">
                        <div class="toc-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="8" y1="6" x2="21" y2="6"></line>
                                <line x1="8" y1="12" x2="21" y2="12"></line>
                                <line x1="8" y1="18" x2="21" y2="18"></line>
                                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                                <line x1="3" y1="18" x2="3.01" y2="18"></line>
                            </svg>
                            Nội dung bài viết
                        </div>
                        <ul class="toc-list"></ul>
                    </div>
                </div>
                <div class="widget">
                    <h3 class="widget-title">Can tu van?</h3>
                    <p style="font-size:0.9rem;color:var(--c-text-muted);line-height:1.6;margin-bottom:16px;">Cần đặt hàng hoặc tư vấn sản phẩm? Gọi cho chúng tôi ngay.</p>
                    <a href="tel:0901234567" class="btn btn-primary btn-block" style="margin-bottom:10px;">
                        Gọi ngay 0901 234 567
                    </a>
                    <a href="<?= e(site_url('lien-he')) ?>" class="btn btn-outline btn-block">Gửi liên hệ</a>
                </div>
            </aside>
        </div>

        <?php if (!empty($related)): ?>
            <div class="related-news" style="margin-top:56px;">
                <div class="section-title" style="text-align:left;margin-bottom:24px;">
                    <h2 style="font-size:1.45rem;padding-bottom:10px;">Bài viết liên quan</h2>
                    <p style="margin:0;">Các bài viết khác có thể bạn quan tâm</p>
                </div>
                <div class="news-grid">
                    <?php foreach ($related as $news): include __DIR__ . '/../includes/news-card.php';
                    endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
