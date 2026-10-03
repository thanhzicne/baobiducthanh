<?php
if (!isset($news) || !is_array($news)) return;
$id = (int)($news['id'] ?? 0);
$title = $news['title'] ?? '';
$slug = $news['slug'] ?? '';
$summary = $news['summary'] ?? '';
$image = $news['image'] ?? null;
$createdAt = $news['created_at'] ?? date('Y-m-d H:i:s');
$viewCount = (int)($news['view_count'] ?? 0);
$author = $news['author'] ?? 'Admin';
$imageUrl = !empty($image) ? upload_url($image) : null;
$newsIcons = ['📰', '📝', '📢', '💼', '🔧', '📊', '🎯', '🚀'];
$icon = $newsIcons[($id - 1) % count($newsIcons)] ?? '📰';
?>
<article class="news-card" data-news-id="<?= $id ?>">
    <a href="<?= e(site_url('tin-tuc/' . $slug)) ?>" class="news-image" aria-label="<?= e($title) ?>">
        <?php if ($imageUrl): ?>
            <img src="<?= e($imageUrl) ?>" alt="<?= e($title) ?>" loading="lazy" width="600" height="375">
        <?php else: ?>
            <?= $icon ?>
        <?php endif; ?>
    </a>
    <div class="news-body">
        <div class="news-meta">
            <span>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <?= e(format_date($createdAt, 'd/m/Y')) ?>
            </span>
            <span>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                <?= $viewCount ?> lượt xem
            </span>
        </div>
        <h3 class="news-title">
            <a href="<?= e(site_url('tin-tuc/' . $slug)) ?>"><?= e($title) ?></a>
        </h3>
        <?php if (!empty($summary)): ?>
        <p class="news-summary"><?= e(excerpt($summary, 160)) ?></p>
        <?php endif; ?>
        <div class="mt-sm">
            <a href="<?= e(site_url('tin-tuc/' . $slug)) ?>" class="news-readmore">
                Xem chi tiết
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>
    </div>
</article>
