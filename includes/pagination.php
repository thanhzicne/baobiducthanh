<?php
$totalPages = (int)($totalPages ?? 1);
$currentPage = (int)($currentPage ?? 1);
$baseUrl = $paginationUrl ?? '';
if ($totalPages <= 1) return;

$qs = $_GET ?? [];
unset($qs['page']);
$qsPrefix = $qs ? (str_contains($baseUrl, '?') ? '&' : '?') . http_build_query($qs) : '';
$qsPrefix = rtrim($qsPrefix, '&');

$pages = [];
$start = max(1, $currentPage - 2);
$end = min($totalPages, $start + 4);
if ($end - $start < 4) $start = max(1, $end - 4);
for ($i = $start; $i <= $end; $i++) $pages[] = $i;

$pageUrl = function ($p) use ($baseUrl, $qsPrefix) {
    $j = $qsPrefix ? ($qsPrefix . '&page=' . $p) : ('?page=' . $p);
    if (str_contains($baseUrl, 'page=') || empty($baseUrl)) return $j;
    return rtrim($baseUrl, '/') . '/' . ltrim($j, '/');
};
?>
<nav class="pagination" aria-label="Phân trang">
    <?php if ($currentPage > 1): ?>
        <a href="<?= e($pageUrl($currentPage - 1)) ?>" rel="prev" aria-label="Trang trước">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>
    <?php else: ?>
        <span class="disabled" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </span>
    <?php endif; ?>

    <?php if ($start > 1): ?>
        <a href="<?= e($pageUrl(1)) ?>">1</a>
        <?php if ($start > 2) echo '<span class="pagination-ellipsis">...</span>'; ?>
    <?php endif; ?>

    <?php foreach ($pages as $p): ?>
        <?php if ($p === $currentPage): ?>
            <span class="current" aria-current="page"><?= $p ?></span>
        <?php else: ?>
            <a href="<?= e($pageUrl($p)) ?>"><?= $p ?></a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages - 1) echo '<span class="pagination-ellipsis">...</span>'; ?>
        <a href="<?= e($pageUrl($totalPages)) ?>"><?= $totalPages ?></a>
    <?php endif; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="<?= e($pageUrl($currentPage + 1)) ?>" rel="next" aria-label="Trang sau">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </a>
    <?php else: ?>
        <span class="disabled" aria-hidden="true">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </span>
    <?php endif; ?>
</nav>