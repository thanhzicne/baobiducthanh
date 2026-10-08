<?php
// Bắt buộc: file này chỉ dùng sau khi đã require_once __DIR__ . '/auth.php';
$adminPage = $adminPage ?? '';
$adminTitle = $adminTitle ?? 'Quản trị';

$adminMenu = [
    ['group' => 'Tổng quan', 'items' => [
        ['file' => 'dashboard.php', 'label' => 'Bảng điều khiển', 'icon' => 'M3 12l9-9 9 9M5 10v10h14V10'],
    ]],
    ['group' => 'Đơn hàng', 'items' => [
        ['file' => 'quotes.php', 'label' => 'Yêu cầu báo giá', 'icon' => 'M9 7h6m-7 4h8m-9 4h10M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z', 'badge' => 'pending_quotes'],
        ['file' => 'contacts.php', 'label' => 'Liên hệ', 'icon' => 'M3 8l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z', 'badge' => 'unread_contacts'],
        ['file' => 'conversations.php', 'label' => 'Chat trực tuyến', 'icon' => 'M4 6h16v12H4zM4 7l8 6 8-6', 'badge' => 'open_chats'],
        ['file' => 'subscribers.php', 'label' => 'Nhắn tin', 'icon' => 'M4 6h16v12H4zM4 7l8 6 8-6', 'badge' => 'total_subscribers'],
    ]],
    ['group' => 'Danh mục', 'items' => [
        ['file' => 'products.php', 'label' => 'Sản phẩm', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['file' => 'categories.php', 'label' => 'Danh mục', 'icon' => 'M3 7h6l2 2h10v10H3z'],
    ]],
    ['group' => 'Nội dung', 'items' => [
        ['file' => 'news.php', 'label' => 'Tin tức', 'icon' => 'M4 4h13v16H4zM8 8h5M8 12h5M8 16h3M17 8h3v9h-3'],
        ['file' => 'pages.php', 'label' => 'Trang tĩnh', 'icon' => 'M6 3h9l4 4v14H6zM14 3v5h5'],
        ['file' => 'banners.php', 'label' => 'Banner', 'icon' => 'M3 5h18v10H3zM7 19h10'],
        ['file' => 'testimonials.php', 'label' => 'Phản hồi', 'icon' => 'M7 10h.01M12 10h.01M17 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['file' => 'popups.php', 'label' => 'Popup', 'icon' => 'M4 4h16v16H4zM12 8v.01M8 12h8'],
    ]],
    ['group' => 'Hệ thống', 'items' => [
        ['file' => 'settings.php', 'label' => 'Cài đặt', 'icon' => 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-2.9 1.2V21a2 2 0 11-4 0v-.1A1.7 1.7 0 007 19.4a1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.7 1.7 0 002.6 14H2a2 2 0 110-4h.1A1.7 1.7 0 004.6 7a1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1A1.7 1.7 0 009 2.6V2a2 2 0 114 0v.1A1.7 1.7 0 0017 4.6a1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1A1.7 1.7 0 0021.4 10H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z'],
        ['file' => 'account.php', 'label' => 'Tài khoản', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    ]],
];

// Badge đếm nhanh
$adminBadges = [];
try {
    $adminBadges['pending_quotes'] = (int)$pdo->query("SELECT COUNT(*) FROM quote_requests WHERE status = 'pending'")->fetchColumn();
    $adminBadges['unread_contacts'] = (int)$pdo->query('SELECT COUNT(*) FROM contacts WHERE is_read = 0')->fetchColumn();
    $adminBadges['total_subscribers'] = (int)$pdo->query('SELECT COUNT(*) FROM subscribers WHERE is_active = 1')->fetchColumn();
} catch (Exception $e) {
    $adminBadges = [];
}
try {
    $adminBadges['open_chats'] = (int)$pdo->query("SELECT COUNT(*) FROM chat_messages m JOIN chat_conversations c ON c.id = m.conversation_id WHERE m.sender_type = 'customer' AND m.id > c.last_admin_read_message_id")->fetchColumn();
} catch (Exception $e) {
    $adminBadges['open_chats'] = 0;
}

$adminFlash = flash_get('admin_msg');
$mustChangePwd = !empty($_SESSION['admin_must_change_password']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?> - Quản trị Bao bì Đức Thành</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/quill@1.7.3/dist/quill.snow.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --a: #8B5E34;
            --a2: #6d4a28;
            --ac: #E8A33D;
            --bg: #f4f6f8;
            --tx: #1f2933;
            --mu: #6b7280;
            --bd: #e5e7eb;
            --er: #dc2626;
            --ok: #059669;
        }

        body {
            font-family: 'Be Vietnam Pro', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--tx);
            font-size: 14px;
        }

        .adm {
            display: flex;
            min-height: 100vh;
        }

        .side {
            width: 250px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #6d4a28, #8B5E34);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 60;
        }

        .side-brand {
            padding: 20px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, .15);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .side-brand .logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: .85rem;
            flex-shrink: 0;
            overflow: hidden;
        }

        .side-brand .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .side-brand b {
            font-size: .9rem;
            display: block;
        }

        .side-brand span {
            font-size: .72rem;
            opacity: .8;
        }

        .side nav {
            flex: 1;
            padding: 10px 0 20px;
        }

        .nav-group {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            opacity: .6;
            padding: 14px 18px 6px;
        }

        .side a.nav-i {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 18px;
            color: #fff;
            text-decoration: none;
            font-size: .86rem;
            border-left: 3px solid transparent;
        }

        .admin-side-overlay,
        .admin-menu-toggle {
            display: none;
        }

        .side a.nav-i:hover {
            background: rgba(255, 255, 255, .08);
        }

        .side a.nav-i.active {
            background: rgba(0, 0, 0, .18);
            border-left-color: var(--ac);
            font-weight: 600;
        }

        /* Form đăng xuất: đẩy xuống cuối, có đường kẻ ngăn cách */
        .side .logout-form {
            margin: 8px 0 0;
            padding: 8px 0 0;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        /* Reset style mặc định của <button> để giống các mục menu */
        .side .logout-btn {
            appearance: none;
            -webkit-appearance: none;
            background: transparent;
            border: 0;
            border-left: 3px solid transparent;
            /* khớp thanh vàng của mục active */
            border-radius: 0;
            width: 100%;
            margin: 0;

            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 18px;

            font: inherit;
            font-size: 14px;
            font-weight: 500;
            color: inherit;
            text-align: left;
            line-height: 1.2;
            cursor: pointer;
            transition: background .2s ease, color .2s ease, border-color .2s ease;
        }

        .side .logout-btn span {
            flex: 1;
            white-space: nowrap;
        }

        .side .logout-btn:hover,
        .side .logout-btn:focus-visible {
            background: rgba(255, 255, 255, 0.08);
            color: #ffd9d2;
            /* hơi đỏ nhạt để báo hiệu hành động thoát */
            border-left-color: #e5736a;
            outline: none;
        }

        .side .logout-btn:hover svg,
        .side .logout-btn:focus-visible svg {
            transform: translateX(2px);
        }

        .side .logout-btn svg {
            transition: transform .2s ease;
        }

        /* Icon: giữ nguyên rule cũ của bạn */
        .side .nav-i svg {
            width: 17px;
            height: 17px;
            min-width: 17px;
            min-height: 17px;

            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;

            flex-shrink: 0;
            display: block;
        }

        .side .nav-i svg path {
            fill: none;
            stroke: currentColor;
        }

        .nav-badge {
            margin-left: auto;
            background: var(--ac);
            color: #4a3208;
            font-size: .7rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 10px;
        }

        .main {
            flex: 1;
            margin-left: 250px;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .top {
            background: #fff;
            border-bottom: 1px solid var(--bd);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .top h1 {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .crumb {
            font-size: .8rem;
            color: var(--mu);
        }

        .crumb a {
            color: var(--a);
        }

        .userm {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: .85rem;
        }

        .avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--a);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .8rem;
            flex-shrink: 0;
        }

        .pad {
            padding: 24px;
            flex: 1;
        }

        .card {
            background: #fff;
            border: 1px solid var(--bd);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 18px;
        }

        .card h2 {
            font-size: 1rem;
            margin-bottom: 14px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 9px;
            margin-bottom: 16px;
            font-size: .87rem;
        }

        .alert a {
            font-weight: 700;
        }

        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-warning {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
        }

        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .stat {
            background: #fff;
            border: 1px solid var(--bd);
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .stat .ic {
            width: 44px;
            height: 44px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat .ic svg {
            width: 21px;
            height: 21px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .stat b {
            display: block;
            font-size: 1.5rem;
            line-height: 1.2;
        }

        .stat a {
            font-size: .78rem;
            color: var(--mu);
            text-decoration: none;
            display: block;
        }

        .stat a:hover {
            color: var(--a);
            text-decoration: underline;
        }

        table.tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: .86rem;
        }

        table.tbl th {
            text-align: left;
            padding: 10px 12px;
            background: #f9fafb;
            border-bottom: 2px solid var(--bd);
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--mu);
            white-space: nowrap;
        }

        table.tbl td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--bd);
            vertical-align: middle;
        }

        table.tbl tr:hover td {
            background: #fcfcfd;
        }

        table.tbl img.th {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 6px;
            background: #f3f4f6;
        }

        .tblwrap {
            overflow-x: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 15px;
            border-radius: 9px;
            border: 1px solid transparent;
            background: var(--a);
            color: #fff;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
            min-height: 38px;
            white-space: nowrap;
        }

        .btn:hover {
            background: var(--a2);
            color: #fff;
        }

        .btn-sm {
            padding: 6px 11px;
            font-size: .78rem;
            min-height: 32px;
        }

        .btn-outline {
            background: #fff;
            border-color: var(--bd);
            color: var(--tx);
        }

        .btn-outline:hover {
            background: #f9fafb;
            color: var(--tx);
        }

        .btn-ac {
            background: var(--ac);
            color: #4a3208;
        }

        .btn-ac:hover {
            background: #d99329;
            color: #4a3208;
        }

        .btn-danger {
            background: var(--er);
        }

        .btn-danger:hover {
            background: #b91c1c;
            color: #fff;
        }

        .btn-ok {
            background: var(--ok);
        }

        .btn-ok:hover {
            background: #047857;
            color: #fff;
        }

        .btn svg {
            width: 15px;
            height: 15px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .tag {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .tag-ok {
            background: #ecfdf5;
            color: #065f46;
        }

        .tag-er {
            background: #fef2f2;
            color: #991b1b;
        }

        .tag-wa {
            background: #fffbeb;
            color: #92400e;
        }

        .tag-in {
            background: #eff6ff;
            color: #1e40af;
        }

        .tag-gy {
            background: #f3f4f6;
            color: #4b5563;
        }

        .fg {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .fg form {
            display: inline;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        @media(max-width:860px) {
            .row {
                grid-template-columns: 1fr;
            }

            .main {
                margin-left: 0;
            }

            .side {
                display: flex;
                width: min(280px, 85vw);
                transform: translateX(-100%);
                transition: transform .22s ease;
            }

            .side.is-open {
                transform: translateX(0);
            }

            .admin-side-overlay.is-visible {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 55;
                border: 0;
                background: rgba(17, 24, 39, .48);
            }

            .admin-menu-toggle {
                display: inline-flex;
                width: 40px;
                height: 40px;
                flex: 0 0 40px;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--bd);
                border-radius: 8px;
                background: #fff;
                color: var(--tx);
                cursor: pointer;
            }

            .top {
                justify-content: flex-start;
                padding: 12px 16px;
            }

            .top-title {
                flex: 1;
                min-width: 0;
            }

            .top-title h1 {
                overflow-wrap: anywhere;
            }
        }

        @media(max-width:480px) {
            .userm>div:last-child {
                display: none;
            }
        }

        .fg-2 {
            margin-bottom: 14px;
        }

        .fg-2 label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .fg-2 .req {
            color: var(--er);
        }

        .inp,
        select.inp,
        textarea.inp {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid var(--bd);
            border-radius: 9px;
            font-family: inherit;
            font-size: .88rem;
            min-height: 40px;
            background: #fff;
            color: var(--tx);
        }

        .inp:focus {
            outline: none;
            border-color: var(--a);
            box-shadow: 0 0 0 3px rgba(139, 94, 52, .12);
        }

        textarea.inp {
            min-height: 96px;
            resize: vertical;
        }

        .hint {
            font-size: .76rem;
            color: var(--mu);
            margin-top: 4px;
        }

        .ql-toolbar.ql-snow {
            border-radius: 9px 9px 0 0;
        }

        .ql-container.ql-snow {
            border-radius: 0 0 9px 9px;
            font-family: inherit;
        }

        .ql-editor {
            min-height: 220px;
        }

        .imgprev {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 9px;
            border: 1px solid var(--bd);
            background: #f9fafb;
        }

        .pgn {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .pgn a,
        .pgn span {
            padding: 7px 12px;
            border: 1px solid var(--bd);
            border-radius: 8px;
            background: #fff;
            text-decoration: none;
            color: var(--tx);
            font-size: .84rem;
        }

        .pgn .on {
            background: var(--a);
            color: #fff;
            border-color: var(--a);
            font-weight: 600;
        }

        .empty {
            text-align: center;
            padding: 44px 20px;
            color: var(--mu);
        }

        .empty .ic {
            font-size: 2.4rem;
            margin-bottom: 10px;
        }

        .mini {
            font-size: .76rem;
            color: var(--mu);
        }

        .mono {
            font-family: ui-monospace, Menlo, monospace;
            font-size: .82rem;
        }

        .st {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            margin-right: 5px;
        }

        @media print {

            .side,
            .top,
            .no-print {
                display: none !important;
            }

            .main {
                margin-left: 0;
            }

            .pad {
                padding: 0;
            }

            body {
                background: #fff;
            }

            .card {
                border: 1px solid #ddd;
            }


        }
    </style>
</head>

<body>
    <div class="adm">
        <button type="button" class="admin-side-overlay" aria-label="Đóng menu" hidden></button>
        <aside class="side" id="adminSidebar">
            <div class="side-brand">
                <div class="logo-icon">
                    <img src="<?= e(site_url('assets/images/Logo.png')) ?>" alt="Bao bì Đức Thành">
                </div>
                <div><b>Bao bì Đức Thành</b><span>Trang quản trị</span></div>
            </div>
            <nav>
                <?php foreach ($adminMenu as $g): ?>
                    <div class="nav-group"><?= e($g['group']) ?></div>
                    <?php foreach ($g['items'] as $it):
                        $on = ($adminPage === $it['file']);
                        $bc = !empty($it['badge']) ? (int)($adminBadges[$it['badge']] ?? 0) : 0;
                    ?>
                        <a class="nav-i <?= $on ? 'active' : '' ?>" href="<?= e(admin_url($it['file'])) ?>">
                            <svg viewBox="0 0 24 24">
                                <path d="<?= e($it['icon']) ?>" />
                            </svg>
                            <?= e($it['label']) ?>
                            <?php if (!empty($it['badge'])): ?><span class="nav-badge" data-admin-badge="<?= e($it['badge']) ?>" <?= $bc > 0 ? '' : 'hidden' ?>><?= $bc > 99 ? '99+' : $bc ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <div class="nav-group">Tài khoản</div>
                <a class="nav-i" href="<?= e(site_url()) ?>" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24">
                        <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3" />
                    </svg>Xem website</a>
                <form method="post" action="<?= e(admin_url('logout.php')) ?>" class="logout-form">
                    <?= csrf_field() ?>

                    <button type="submit" class="nav-i logout-btn">
                        <svg
                            class="logout-icon"
                            viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path d="M16 17l5-5-5-5" />
                            <path d="M21 12H9" />
                        </svg>

                        <span>Đăng xuất</span>
                    </button>
                </form>
            </nav>
        </aside>

        <div class="main">
            <header class="top">
                <button type="button" class="admin-menu-toggle" aria-label="Mở menu quản trị" aria-controls="adminSidebar" aria-expanded="false">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="top-title">
                    <h1><?= e($adminTitle) ?></h1>
                    <?php if (!empty($adminSubtitle)): ?><div class="crumb"><?= $adminSubtitle ?></div><?php endif; ?>
                </div>
                <div class="userm">
                    <div class="avatar"><?= e(mb_substr($_SESSION['admin_name'] ?? 'A', 0, 1, 'UTF-8')) ?></div>
                    <div>
                        <b style="font-size:.86rem;"><?= e($_SESSION['admin_name'] ?? '') ?></b>
                        <div class="mini"><?= e($_SESSION['admin_role'] ?? '') ?></div>
                    </div>
                </div>
            </header>

            <div class="pad">
                <?php if ($adminFlash): ?>
                    <div class="alert alert-<?= e($adminFlash['type']) ?>"><?= e($adminFlash['message']) ?></div>
                <?php endif; ?>
                <?php if ($mustChangePwd && $adminPage !== 'account.php'): ?>
                    <div class="alert alert-warning">
                        Bạn đang dùng mật khẩu mặc định.
                        <a href="<?= e(admin_url('account.php')) ?>" style="text-decoration:underline;">Đổi mật khẩu ngay</a>
                        để bảo mật hệ thống.
                    </div>
                <?php endif; ?>