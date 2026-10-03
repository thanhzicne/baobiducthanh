<?php
function generateTOC($content, $minHeadings = 3) {
    if (empty($content)) return '';

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//h2 | //h3');
    if (!$nodes || $nodes->length < $minHeadings) return '';

    $items = [];
    $usedSlugs = [];
    $idx = 0;

    foreach ($nodes as $node) {
        $text = trim($node->nodeValue);
        $level = (int)str_replace('h', '', strtolower($node->nodeName));
        $slug = 'toc-' . $idx . '-' . slugify(mb_substr($text, 0, 40, 'UTF-8'));
        while (isset($usedSlugs[$slug])) { $idx++; $slug = 'toc-' . $idx . '-' . slugify(mb_substr($text, 0, 40, 'UTF-8')); }
        $usedSlugs[$slug] = true;
        $node->setAttribute('id', $slug);
        $idx++;
        $items[] = ['level' => $level, 'text' => $text, 'slug' => $slug];
    }

    if (count($items) < $minHeadings) return '';

    $newHtml = $dom->saveHTML();
    $tocHtml = '<div class="toc-wrap"><div class="toc-box"><div class="toc-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
        Mục lục</div><ul class="toc-list">';

    $currentH2 = false;
    foreach ($items as $it) {
        if ($it['level'] === 2) {
            if ($currentH2) $tocHtml .= '</ul>';
            $tocHtml .= '<li><a href="#' . htmlspecialchars($it['slug'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($it['text'], ENT_QUOTES, 'UTF-8') . '</a><ul>';
            $currentH2 = true;
        } elseif ($it['level'] === 3) {
            if (!$currentH2) { $tocHtml .= '<li><ul>'; $currentH2 = true; }
            $tocHtml .= '<li><a href="#' . htmlspecialchars($it['slug'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($it['text'], ENT_QUOTES, 'UTF-8') . '</a></li>';
        }
    }
    if ($currentH2) $tocHtml .= '</ul>';
    $tocHtml .= '</ul></div></div>';

    return ['toc' => $tocHtml, 'content' => $newHtml];
}
