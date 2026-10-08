<?php
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/helpers.php';
}
$settings = $settings ?? [];
if (empty($settings)) {
    try {
        if (!isset($pdo)) require_once __DIR__ . '/db.php';
        $settings = get_all_settings($pdo);
    } catch (Exception $e) {
        $settings = [
            'site_name' => 'Công ty Bao bì Đức Thành',
            'site_slogan' => 'Bao bì chất lượng, giá tốt, giao hàng nhanh',
            'address' => '975 Kha Vạn Cân, Thủ Đức, TP. Ho Chi Minh',
            'phone' => '0987 654 321',
            'phone2' => '0123 456 789',
            'hotline' => '090 123 4567',
            'email' => 'contact@baobithanhdat.vn',
            'email_sales' => 'sales@baobithanhdat.vn',
            'working_hours' => 'Thứ 2 - Thứ 7: 08:00 - 18:00',
            'facebook_url' => 'https://www.facebook.com/ThanhfPham',
            'zalo_url' => 'https://zalo.me/039299212',
            'messenger_url' => 'https://m.me/ThanhfPham',
        ];
    }
}
$settings['site_name'] = 'Công ty Bao bì Đức Thành';
$navCategories = [];
try {
    if (!isset($pdo)) require_once __DIR__ . '/db.php';
    $stmt = $pdo->query('SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 6');
    $navCategories = $stmt->fetchAll();
} catch (Exception $e) {
}

$popupData = null;
try {
    if (!isset($pdo)) require_once __DIR__ . '/db.php';
    $ps = $pdo->query('SELECT * FROM popups WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    $popupData = $ps->fetch();
} catch (Exception $e) {
}

$hotlineClean = preg_replace('/[^0-9+]/', '', $settings['hotline'] ?? $settings['phone'] ?? '0901234567');
$zalo = $settings['zalo_url'] ?? ('https://zalo.me/' . $hotlineClean);
$fbMsg = $settings['messenger_url'] ?? '#';
$fb = $settings['facebook_url'] ?? '#';
$mapUrl = 'https://maps.google.com/maps?q=' . urlencode($settings['address'] ?? 'Ho Chi Minh') . '&t=&z=15&ie=UTF8&iwloc=&output=embed';
$chatRequestPath = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$siteBasePath = rtrim((string)(parse_url(SITE_URL, PHP_URL_PATH) ?: ''), '/');
if ($siteBasePath !== '' && strpos($chatRequestPath, $siteBasePath . '/') === 0) {
    $chatRequestPath = substr($chatRequestPath, strlen($siteBasePath));
} elseif ($siteBasePath !== '' && $chatRequestPath === $siteBasePath) {
    $chatRequestPath = '/';
}
$chatNext = trim($chatRequestPath, '/') ?: '__home__';
$chatLoginUrl = site_url('dang-nhap?next=' . rawurlencode($chatNext));
?>
</main>

<?php if ($popupData && !empty($popupData)): ?>
    <div class="popup-overlay" id="sitePopup" data-delay="<?= (int)($popupData['delay_seconds'] ?? 3) * 1000 ?>" data-scope="<?= e($popupData['scope'] ?? 'sitewide') ?>">
        <div class="popup-box" role="dialog" aria-modal="true" aria-labelledby="popupTitle">
            <button type="button" class="popup-close" data-popup-close aria-label="Đóng">&times;</button>
            <?php if (!empty($popupData['image'])): ?>
                <div class="popup-banner" style="background-image:url('<?= e(upload_url($popupData['image'])) ?>');background-size:cover;background-position:center;"></div>
            <?php else: ?>
                <div class="popup-banner"></div>
            <?php endif; ?>
            <div class="popup-body">
                <h3 id="popupTitle"><?= e($popupData['title']) ?></h3>
                <div><?= $popupData['content'] ?? '' ?></div>
                <?php if (!empty($popupData['link'])): ?>
                    <a href="<?= e($popupData['link']) ?>" class="btn btn-accent btn-lg mt-md" data-popup-close><?= e($popupData['link_text'] ?? 'Xem chi tiet') ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="floating-contact" aria-label="Liên hệ nhanh">
    <a href="tel:<?= e($hotlineClean) ?>" class="fc-call" data-label="Gọi điện" aria-label="Gọi điện">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
        </svg>
    </a>
    <a href="sms:<?= e($hotlineClean) ?>" class="fc-sms" data-label="SMS" aria-label="SMS">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
    </a>
    <a href="<?= e($zalo) ?>" target="_blank" rel="noopener" class="fc-zalo" data-label="Zalo" aria-label="Zalo">Z</a>
    <a href="<?= e($fbMsg) ?>" target="_blank" rel="noopener" class="fc-messenger" data-label="Messenger" aria-label="Messenger">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.36 2 2 6.16 2 11.4c0 2.64 1.28 5.02 3.28 6.64V22l3.02-1.74c.82.22 1.68.34 2.7.34 5.64 0 10-4.16 10-9.4S17.64 2 12 2zm.66 12.66l-2.72-2.9-5.3 2.9 5.84-6.22 2.78 2.9 5.28-2.9-5.88 6.22z" />
        </svg>
    </a>
    <button type="button" class="fc-chat" data-site-chat-open data-label="Trò chuyện trực tuyến" aria-label="Trò chuyện trực tuyến">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"></path>
        </svg>
    </button>
    <a href="<?= e($mapUrl) ?>" target="_blank" rel="noopener" class="fc-map" data-label="Bản đồ" aria-label="Bản đồ">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
            <circle cx="12" cy="10" r="3"></circle>
        </svg>
    </a>
</div>

<div class="mobile-contact-widget">
    <div class="mobile-contact-popup" id="mobileContactPopup" role="group" aria-label="Các cách liên hệ" hidden>
        <a href="tel:<?= e($hotlineClean) ?>" class="mc-option">
            <span class="mc-icon" style="background:#10b981"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                </svg></span>
            <span>Gọi điện</span>
        </a>
        <a href="sms:<?= e($hotlineClean) ?>" class="mc-option">
            <span class="mc-icon" style="background:#3b82f6"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg></span>
            <span>SMS</span>
        </a>
        <a href="<?= e($zalo) ?>" target="_blank" rel="noopener" class="mc-option">
            <span class="mc-icon" style="background:#0068ff">Z</span><span>Zalo</span>
        </a>
        <a href="<?= e($fbMsg) ?>" target="_blank" rel="noopener" class="mc-option">
            <span class="mc-icon" style="background:#0084ff"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.36 2 2 6.16 2 11.4c0 2.64 1.28 5.02 3.28 6.64V22l3.02-1.74c.82.22 1.68.34 2.7.34 5.64 0 10-4.16 10-9.4S17.64 2 12 2z"></path>
                </svg></span>
            <span>Nhắn tin</span>
        </a>
        <button type="button" class="mc-option" data-site-chat-open>
            <span class="mc-icon" style="background:#176b59"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"></path>
                </svg></span>
            <span>Chat</span>
        </button>
        <a href="<?= e(site_url('lien-he')) ?>" class="mc-option">
            <span class="mc-icon" style="background:var(--c-accent)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg></span>
            <span>Liên hệ</span>
        </a>
    </div>
    <button type="button" class="mobile-contact-toggle" aria-label="Mở các cách liên hệ" aria-controls="mobileContactPopup" aria-expanded="false">
        <svg class="mc-toggle-open" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"></path>
        </svg>
        <svg class="mc-toggle-close" width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12"></path>
        </svg>
        <span>Liên hệ</span>
    </button>
</div>
<button type="button" class="back-to-top" aria-label="Lên đầu trang" title="Lên đầu trang">
    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m18 15-6-6-6 6"></path>
    </svg>
</button>

<style>
    .site-chat {
        position: fixed;
        right: 22px;
        bottom: 92px;
        z-index: 1100;
        font-family: inherit
    }

    .site-chat-toggle {
        border: 0;
        border-radius: 24px;
        background: #176b59;
        color: #fff;
        padding: 12px 18px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 5px 18px #0003;
        cursor: pointer
    }

    .site-chat-panel {
        width: min(350px, calc(100vw - 28px));
        height: 420px;
        margin-bottom: 10px;
        background: #fff;
        border: 1px solid #d7e2df;
        border-radius: 12px;
        box-shadow: 0 14px 40px #172c2633;
        display: flex;
        flex-direction: column;
        overflow: hidden
    }

    .site-chat-panel[hidden] {
        display: none
    }

    .site-chat-head {
        padding: 14px 16px;
        background: #176b59;
        color: #fff;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center
    }

    .site-chat-close {
        border: 0;
        background: none;
        color: #fff;
        font-size: 22px;
        cursor: pointer
    }

    .site-chat-messages {
        padding: 14px;
        flex: 1;
        overflow-y: auto;
        background: #f5f8f7;
        display: flex;
        flex-direction: column;
        gap: 8px
    }

    .site-chat-message {
        max-width: 85%;
        padding: 9px 11px;
        border-radius: 10px;
        background: #fff;
        align-self: flex-start;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        font-size: 14px
    }

    .site-chat-message[data-sender="customer"] {
        background: #176b59;
        color: #fff;
        align-self: flex-end
    }

    .site-chat-form {
        display: flex;
        gap: 8px;
        padding: 10px;
        border-top: 1px solid #e3e9e7
    }

    .site-chat-form textarea {
        min-width: 0;
        flex: 1;
        resize: none;
        border: 1px solid #cbd5d1;
        border-radius: 8px;
        padding: 9px;
        font: inherit
    }

    .site-chat-form button {
        border: 0;
        border-radius: 8px;
        background: #176b59;
        color: #fff;
        padding: 0 14px;
        font-weight: 700;
        cursor: pointer
    }

    @media(max-width:600px) {
        .site-chat {
            right: 12px;
            bottom: 82px
        }

        .site-chat-panel {
            height: min(420px, 65vh)
        }
    }
</style>
<aside class="site-chat" id="siteChat" data-endpoint="<?= e(site_url('api/conversation.php')) ?>" data-csrf="<?= e(csrf_token()) ?>" data-authenticated="<?= !empty($_SESSION['customer_id']) ? '1' : '0' ?>" data-login-url="<?= e($chatLoginUrl) ?>">
    <div class="site-chat-panel" id="siteChatPanel" hidden>
        <div class="site-chat-head"><span>Trò chuyện với đội ngũ</span><button type="button" class="site-chat-close" id="siteChatClose" aria-label="Đóng chat">×</button></div>
        <div class="site-chat-messages" id="siteChatMessages" aria-live="polite">
            <p>Tin nhắn của bạn sẽ hiển thị tại đây.</p>
        </div>
        <?php if (!empty($_SESSION['customer_id'])): ?>
            <form class="site-chat-form" id="siteChatForm"><textarea name="message" rows="2" maxlength="2000" required placeholder="Nhập tin nhắn..."></textarea><button type="submit" aria-label="Gửi tin nhắn">Gửi</button></form>
        <?php else: ?>
            <div class="site-chat-login"><span>Đăng nhập để bắt đầu trò chuyện.</span><a href="<?= e($chatLoginUrl) ?>">Đăng nhập</a></div>
        <?php endif; ?>
    </div>
</aside>
<script>
    (function() {
        var root = document.getElementById('siteChat');
        if (!root) return;
        var endpoint = root.dataset.endpoint,
            panel = document.getElementById('siteChatPanel');
        var list = document.getElementById('siteChatMessages'),
            form = document.getElementById('siteChatForm');
        var authenticated = root.dataset.authenticated === '1';
        var lastId = 0,
            loaded = false,
            polling = false;

        function poll() {
            if (!authenticated || polling) return;
            polling = true;
            fetch(endpoint + '?action=poll&after_id=' + lastId, {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json'
                    }
                })
                .then(function(response) {
                    return response.json()
                })
                .then(function(data) {
                    if (!data.success) return;
                    if (!loaded) {
                        list.textContent = '';
                        loaded = true;
                    }
                    data.messages.forEach(function(message) {
                        var item = document.createElement('div');
                        item.className = 'site-chat-message';
                        item.dataset.sender = message.sender_type;
                        item.textContent = message.message;
                        list.appendChild(item);
                        lastId = Math.max(lastId, Number(message.id));
                    });
                    if (data.messages.length) list.scrollTop = list.scrollHeight;
                }).catch(function() {}).finally(function() {
                    polling = false;
                });
        }
        document.querySelectorAll('[data-site-chat-open]').forEach(function(button) {
            button.addEventListener('click', function() {
                if (!authenticated) {
                    window.location.href = root.dataset.loginUrl;
                    return;
                }
                panel.hidden = !panel.hidden;
                if (!panel.hidden) poll();
            });
        });
        document.getElementById('siteChatClose').addEventListener('click', function() {
            panel.hidden = true;
        });
        if (form) form.addEventListener('submit', function(event) {
            event.preventDefault();
            var input = form.elements.message,
                body = new FormData();
            body.append('message', input.value);
            body.append('csrf_token', root.dataset.csrf);
            fetch(endpoint + '?action=send', {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json'
                    }
                })
                .then(function(response) {
                    return response.json()
                }).then(function(data) {
                    if (data.success) {
                        input.value = '';
                        poll();
                    } else window.alert(data.message || 'Không gửi được tin nhắn.');
                })
                .catch(function() {
                    window.alert('Không thể kết nối. Vui lòng thử lại.');
                });
        });
        if (authenticated) {
            poll();
            window.setInterval(poll, 2000);
        }
    })();
</script>

<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col footer-about">
                <div class="footer-logo">
                    <div class="logo-icon">
                        <img src="<?= e(site_url('assets/images/Logo.png')) ?>" alt="Bao bì Đức Thành">
                    </div>
                    <div class="footer-logo-text">
                        <span class="brand"><?= e($settings['site_name']) ?></span>
                        <span class="tag">Chuyên bao bì B2B</span>
                    </div>
                </div>
                <p><?= e($settings['site_slogan'] ?? '') ?> Chúng tôi phục vụ hơn 5.000 doanh nghiệp B2B trên toàn quốc, đảm bảo chất lượng, giá cả cạnh tranh và giao hàng nhanh.</p>
                <ul class="footer-contact-list">
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                        <span><?= e($settings['address'] ?? '') ?></span>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <a href="tel:<?= e($hotlineClean) ?>"><?= e($settings['hotline'] ?? $settings['phone'] ?? '') ?></a>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <a href="mailto:<?= e($settings['email'] ?? '') ?>"><?= e($settings['email'] ?? '') ?></a>
                    </li>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span><?= e($settings['working_hours'] ?? '') ?></span>
                    </li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Liên kết nhanh</h4>
                <ul class="footer-links">
                    <li><a href="<?= e(site_url()) ?>">Trang chủ</a></li>
                    <li><a href="<?= e(site_url('gioi-thieu')) ?>">Giới thiệu</a></li>
                    <li><a href="<?= e(site_url('san-pham')) ?>">Tất cả sản phẩm</a></li>
                    <li><a href="<?= e(site_url('tin-tuc')) ?>">Tin tức & Kiến thức</a></li>
                    <li><a href="<?= e(site_url('lien-he')) ?>">Liên hệ</a></li>
                    <li><a href="<?= e(site_url('gio-bao-gia')) ?>">Giỏ báo giá</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Danh mục sản phẩm</h4>
                <ul class="footer-links">
                    <?php if (!empty($navCategories)): foreach ($navCategories as $nc): ?>
                            <li><a href="<?= e(site_url('danh-muc/' . $nc['slug'])) ?>"><?= e($nc['name']) ?></a></li>
                        <?php endforeach;
                    else: ?>
                        <li><a href="<?= e(site_url('danh-muc/thung-carton-3-lop')) ?>">Thùng Carton 3 Lớp</a></li>
                        <li><a href="<?= e(site_url('danh-muc/thung-carton-5-lop')) ?>">Thùng Carton 5 Lớp</a></li>
                        <li><a href="<?= e(site_url('danh-muc/mang-pe')) ?>">Màng PE</a></li>
                        <li><a href="<?= e(site_url('danh-muc/bang-keo')) ?>">Băng Keo</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Nhận tin ưu đãi</h4>
                <p class="newsletter-desc">Đăng ký email để nhận những thông tin và khuyến mãi ưu đãi sớm nhất từ Bao bì Đức Thành!</p>
                <form class="newsletter-form" action="<?= e(site_url('api/subscribe')) ?>" method="post" novalidate>
                    <?= csrf_field() ?>
                    <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off">
                    <input type="email" name="email" class="form-control" placeholder="Email của bạn..." required aria-label="Email nhận tin">
                    <button type="submit" class="newsletter-btn">Gửi ngay</button>
                </form>
                <div class="newsletter-msg" role="status" aria-live="polite"></div>
                <div class="footer-social">
                    <?php if (!empty($fb) && $fb !== '#'): ?>
                        <a href="<?= e($fb) ?>" target="_blank" rel="noopener" aria-label="Facebook">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($zalo)): ?>
                        <a href="<?= e($zalo) ?>" target="_blank" rel="noopener" aria-label="Zalo" style="font-weight:800;font-size:0.85rem">Z</a>
                    <?php endif; ?>
                    <?php if (!empty($fbMsg) && $fbMsg !== '#'): ?>
                        <a href="<?= e($fbMsg) ?>" target="_blank" rel="noopener" aria-label="Messenger">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.36 2 2 6.16 2 11.4c0 2.64 1.28 5.02 3.28 6.64V22l3.02-1.74c.82.22 1.68.34 2.7.34 5.64 0 10-4.16 10-9.4S17.64 2 12 2z" />
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="copyright">© <?= date('Y') ?> <?= e($settings['site_name']) ?>. Bản quyền được bảo lưu.</div>
            <div class="footer-bottom-links">
                <a href="<?= e(site_url('page/chinh-sach-giao-hang')) ?>">Chính sách giao hàng</a>
                <a href="<?= e(site_url('page/chinh-sach-bao-hanh-doi-tra')) ?>">Bảo hành & Đổi trả</a>
            </div>
        </div>
    </div>
</footer>

<script src="<?= e(site_url('assets/js/main.js?v=' . filemtime(BASE_PATH . '/assets/js/main.js'))) ?>" defer></script>
</body>

</html>