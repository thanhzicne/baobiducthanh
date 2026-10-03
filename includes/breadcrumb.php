<?php
$items = $breadcrumb ?? [];
if (empty($items)) return;
?>
<div class="page-header">
    <div class="container page-header-inner">
        <h1 class="page-title"><?= e($pageTitle ?? end($items)['label']) ?></h1>
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="<?= e(site_url()) ?>">Trang chủ</a>
            <?php foreach ($items as $idx => $crumb): ?>
                <span class="breadcrumb-sep" aria-hidden="true">›</span>
                <?php if ($idx === count($items) - 1 || empty($crumb['url'])): ?>
                    <span class="breadcrumb-current"><?= e($crumb['label']) ?></span>
                <?php else: ?>
                    <a href="<?= e($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
