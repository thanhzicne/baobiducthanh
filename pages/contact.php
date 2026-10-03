<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$currentPage = 'contact';
$pageTitle = 'Liên hệ Bao bì Đức Thành - Tư vấn báo giá miễn phí';
$pageMetaDesc = 'Liên hệ Bao bì Đức Thành để được tư vấn và báo giá bao bì miễn phí. Hotline 0901 234 567 hoặc gửi yêu cầu trực tuyến.';
$breadcrumb = [['label' => 'Liên hệ']];
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'ContactPage',
    'name' => $pageTitle,
    'url' => site_url('lien-he')
];

$flash = flash_get('contact_form');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/breadcrumb.php';

$mapEmbed = $settings['map_embed'] ?? '<iframe src="https://maps.google.com/maps?q=ho%20chi%20minh&t=&z=13&ie=UTF8&iwloc=&output=embed" width="100%" height="400" style="border:0;" allowfullscreen loading="lazy"></iframe>';
?>

<section class="section section-sm" style="padding-top:28px;">
    <div class="container">
        <div class="contact-grid">
            <div>
                <div class="contact-info-box">
                    <h3>Thông tin liên hệ</h3>
                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <div>
                            <div class="contact-item-title">Địa chỉ văn phòng</div>
                            <div class="contact-item-value"><?= e($settings['address'] ?? '123 Duong So 1, Quan 9, TP. HCM') ?></div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="contact-item-title">Điện thoại / Hotline</div>
                            <div class="contact-item-value">
                                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['hotline'] ?? '0901234567')) ?>"><?= e($settings['hotline'] ?? '0901 234 567') ?></a>
                                <?php if (!empty($settings['phone2'])): ?>
                                    <br><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $settings['phone2'])) ?>"><?= e($settings['phone2']) ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="contact-item-title">Email</div>
                            <div class="contact-item-value">
                                <a href="mailto:<?= e($settings['email'] ?? 'contact@baobithanhdat.vn') ?>"><?= e($settings['email'] ?? 'contact@baobithanhdat.vn') ?></a>
                                <?php if (!empty($settings['email_sales'])): ?>
                                    <br><a href="mailto:<?= e($settings['email_sales']) ?>"><?= e($settings['email_sales']) ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-item-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div class="contact-item-title">Giờ làm việc</div>
                            <div class="contact-item-value"><?= e($settings['working_hours'] ?? 'Thứ 2 - Thứ 7: 08:00 - 18:00') ?></div>
                        </div>
                    </div>
                </div>

                <div class="map-box" aria-label="Bản đồ vị trí">
                    <?= $mapEmbed ?>
                </div>
            </div>

            <div class="contact-form-box">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endif; ?>

                <h3>Gửi yêu cầu liên hệ</h3>
                <p style="color:var(--c-text-muted);margin-bottom:22px;">Chúng tôi sẽ liên hệ trong vòng 60 phút làm việc. Hoặc gọi <a href="tel:0901234567"><strong>0901 234 567</strong></a> để được hỗ trợ nhanh nhất.</p>

                <form method="post" action="<?= e(site_url('api/contact.php')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="text" name="website_url_hp" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="c_name">Họ và tên <span class="required">*</span></label>
                            <input type="text" name="name" id="c_name" class="form-control" placeholder="Nguyễn Văn A" required maxlength="200">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="c_phone">Số điện thoại <span class="required">*</span></label>
                            <input type="tel" name="phone" id="c_phone" class="form-control" placeholder="090xxxxxxx" required maxlength="20">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="c_email">Email <span class="required">*</span></label>
                            <input type="email" name="email" id="c_email" class="form-control" placeholder="email@example.com" required maxlength="200">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="c_company">Tên công ty</label>
                            <input type="text" name="company" id="c_company" class="form-control" placeholder="Công ty TNHH ..." maxlength="200">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="c_subject">Tiêu đề / Nhu cầu</label>
                        <input type="text" name="subject" id="c_subject" class="form-control" placeholder="Ví dụ: Báo giá thùng carton 5 lớp..." maxlength="255">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="c_message">Nội dung <span class="required">*</span></label>
                        <textarea name="message" id="c_message" class="form-control" rows="6" placeholder="Hãy mô tả nhu cầu: số lượng, kích thước, chất liệu, in ấn, thời gian giao hàng..." required maxlength="2000"></textarea>
                    </div>
                    <div style="font-size:0.82rem;color:var(--c-text-muted);margin-bottom:18px;line-height:1.55;">
                        <strong style="color:var(--c-text);">Cam kết bảo mật:</strong> Thông tin của bạn được bảo mật và chỉ dùng để liên hệ tư vấn.
                    </div>
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Gửi yêu cầu liên hệ</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
