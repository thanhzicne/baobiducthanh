(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initMobileMenu();
        initBannerSlider();
        initQuantityControls();
        initDetailTabs();
        initNewsletter();
        initQuoteCartActions();
        initPopup();
        initFloatingContact();
        initAutoTOC();
        initSearchForms();
        initSmoothAnchors();
    });

    function initMobileMenu() {
        var toggle = document.querySelector('.menu-toggle');
        var nav = document.querySelector('.main-nav');
        var overlay = document.querySelector('.nav-overlay');

        if (!toggle || !nav) return;

        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'nav-overlay';
            document.body.appendChild(overlay);
        }

        toggle.addEventListener('click', function () {
            toggle.classList.toggle('active');
            nav.classList.toggle('open');
            overlay.classList.toggle('show');
            document.body.style.overflow = nav.classList.contains('open') ? 'hidden' : '';
        });

        overlay.addEventListener('click', closeMenu);
        nav.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', closeMenu);
        });

        function closeMenu() {
            toggle.classList.remove('active');
            nav.classList.remove('open');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    function initBannerSlider() {
        var slider = document.querySelector('.banner-slider');
        if (!slider) return;

        var slides = slider.querySelectorAll('.banner-slide');
        var dotsContainer = slider.querySelector('.slider-dots');
        var prevBtn = slider.querySelector('.slider-prev');
        var nextBtn = slider.querySelector('.slider-next');
        var current = 0;
        var timer = null;
        var interval = 6000;

        if (!slides.length) return;

        if (dotsContainer) {
            slides.forEach(function (_, i) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                dot.addEventListener('click', function () { goTo(i); resetTimer(); });
                dotsContainer.appendChild(dot);
            });
        }

        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(current - 1); resetTimer(); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(current + 1); resetTimer(); });

        function goTo(index) {
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;
            current = index;
            slides.forEach(function (s, i) { s.classList.toggle('active', i === current); });
            var dots = dotsContainer ? dotsContainer.querySelectorAll('.slider-dot') : [];
            dots.forEach(function (d, i) { d.classList.toggle('active', i === current); });
        }

        function resetTimer() {
            if (timer) clearInterval(timer);
            timer = setInterval(function () { goTo(current + 1); }, interval);
        }

        if (slides.length > 1) resetTimer();
    }

    function initQuantityControls() {
        document.querySelectorAll('.quantity-input').forEach(function (wrap) {
            var input = wrap.querySelector('input');
            var minus = wrap.querySelector('.qty-minus');
            var plus = wrap.querySelector('.qty-plus');
            if (!input) return;

            function maybeSubmitForm() {
                var form = wrap.closest('.quote-cart-update-form');
                if (form) {
                    if (typeof form.requestSubmit === 'function') form.requestSubmit();
                    else form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                }
            }

            if (minus) minus.addEventListener('click', function () {
                var val = parseInt(input.value, 10) || 0;
                var min = parseInt(input.getAttribute('min') || '1', 10);
                if (val > min) input.value = val - 1;
                else input.value = min;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                maybeSubmitForm();
            });

            if (plus) plus.addEventListener('click', function () {
                var val = parseInt(input.value, 10) || 0;
                var max = parseInt(input.getAttribute('max') || '1000000', 10);
                var next = val + 1;
                if (next <= max) input.value = next;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                maybeSubmitForm();
            });

            input.addEventListener('input', function () {
                var val = parseInt(input.value, 10);
                var min = parseInt(input.getAttribute('min') || '1', 10);
                if (isNaN(val) || val < min) input.value = min;
            });
        });
    }

    function initDetailTabs() {
        var tabBtns = document.querySelectorAll('.detail-tab');
        var tabContents = document.querySelectorAll('.detail-tab-content');
        if (!tabBtns.length) return;

        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-tab');
                tabBtns.forEach(function (b) { b.classList.remove('active'); });
                tabContents.forEach(function (c) { c.classList.remove('active'); });
                btn.classList.add('active');
                var targetEl = document.querySelector('.detail-tab-content[data-tab="' + target + '"]');
                if (targetEl) targetEl.classList.add('active');
            });
        });
    }

    function initNewsletter() {
        var form = document.querySelector('.newsletter-form');
        if (!form) return;

        var input = form.querySelector('.form-control');
        var btn = form.querySelector('.newsletter-btn');
        var msg = form.querySelector('.newsletter-msg');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            submitNewsletter();
        });

        function submitNewsletter() {
            if (!validateEmail(input.value.trim())) {
                showMsg('Vui lòng nhập email hợp lệ!', 'error');
                return;
            }
            if (btn) btn.disabled = true;
            var data = new FormData(form);

            fetch(form.getAttribute('action') || '/api/subscribe', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: data,
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json().catch(function () { return { success: false, message: 'Lỗi hệ thống' }; }); })
                .then(function (res) {
                    showMsg(res.message || (res.success ? 'Đăng ký thành công!' : 'Có lỗi xảy ra'), res.success ? 'success' : 'error');
                    if (res.success) {
                        input.value = '';
                        var hp = form.querySelector('.hp-field');
                        if (hp) hp.value = '';
                    }
                })
                .catch(function () {
                    showMsg('Không thể kết nối. Vui lòng thử lại!', 'error');
                })
                .finally(function () { if (btn) btn.disabled = false; });
        }

        function showMsg(text, type) {
            if (!msg) return alert(text);
            msg.textContent = text;
            msg.className = 'newsletter-msg ' + (type || '');
            clearTimeout(showMsg._t);
            showMsg._t = setTimeout(function () { msg.textContent = ''; msg.className = 'newsletter-msg'; }, 6000);
        }

        function validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    }

    function initQuoteCartActions() {
        // Lấy CSRF token từ form gần nhất trong giỏ (dùng cho nút xoá gọi AJAX)
        function getCsrfToken() {
            var f = document.querySelector('.quote-cart-update-form, form[action*="api/quote.php"]');
            if (!f) return null;
            var input = f.querySelector('input[name="csrf_token"]');
            return input ? input.value : null;
        }

        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (form.classList.contains('add-to-quote-form')) {
                e.preventDefault();
                handleAddToQuote(form);
            }
            if (form.classList.contains('quote-cart-update-form')) {
                e.preventDefault();
                handleUpdateItem(form);
            }
        });

        document.addEventListener('click', function (e) {
            var removeBtn = e.target.closest('.quote-cart-remove, .quote-item-remove');
            if (removeBtn) {
                e.preventDefault();
                handleRemoveItem(removeBtn.getAttribute('data-id'), removeBtn.getAttribute('data-csrf') || getCsrfToken(), removeBtn.getAttribute('data-api'));
            }
            var quickAdd = e.target.closest('.product-quick-add');
            if (quickAdd) {
                e.preventDefault();
                handleQuickAdd(quickAdd);
            }
        });
    }

    function handleAddToQuote(form) {
        var data = new FormData(form);
        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: data,
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json().catch(function () { return { success: false }; }); })
            .then(function (res) {
                if (res.success) {
                    updateCartBadge(res.count || 0);
                    showToast(res.message || 'Đã thêm vào giỏ báo giá!', 'success');
                } else {
                    showToast(res.message || 'Có lỗi xảy ra', 'error');
                }
            })
            .catch(function () { showToast('Không thể kết nối', 'error'); });
    }

    function handleQuickAdd(btn) {
        var data = new FormData();
        data.append('product_id', btn.getAttribute('data-id'));
        data.append('quantity', '1');
        if (btn.getAttribute('data-csrf')) data.append('csrf_token', btn.getAttribute('data-csrf'));
        fetch(btn.getAttribute('data-api'), {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: data,
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json().catch(function () { return { success: false }; }); })
            .then(function (res) {
                if (res.success) {
                    updateCartBadge(res.count || 0);
                    showToast(res.message || 'Đã thêm!', 'success');
                } else {
                    showToast(res.message || 'Lỗi', 'error');
                }
            })
            .catch(function () { showToast('Lỗi kết nối', 'error'); });
    }

    function handleUpdateItem(form) {
        var data = new FormData(form);
        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: data,
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    updateCartBadge(res.count || 0);
                    if (res.redirect) { window.location.href = res.redirect; return; }
                    var subEl = document.getElementById('item-subtotal-' + (data.get('id') || ''));
                    if (subEl && res.subtotal) subEl.textContent = res.subtotal;
                    showToast(res.message || 'Cập nhật thành công!', 'success');
                } else {
                    showToast(res.message || 'Lỗi', 'error');
                }
            })
            .catch(function () { showToast('Lỗi kết nối', 'error'); });
    }

    function handleRemoveItem(id, csrf, endpoint) {
        if (!id) return;
        if (!confirm('Xóa sản phẩm này khỏi giỏ báo giá?')) return;
        var data = new FormData();
        data.append('id', id);
        if (csrf) data.append('csrf_token', csrf);
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: data,
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    if (res.redirect) { window.location.href = res.redirect; return; }
                    var row = document.querySelector('[data-row-id="' + id + '"]');
                    if (row) row.remove();
                    // Giỏ đã trống: tải lại để hiện gợi ý thay vì bảng rỗng
                    if (!document.querySelector('[data-row-id]')) { window.location.href = res.redirect || '/gio-bao-gia'; return; }
                    updateCartBadge(res.count || 0);
                    showToast(res.message || 'Đã xóa!', 'success');
                } else {
                    showToast(res.message || 'Lỗi', 'error');
                }
            })
            .catch(function () { showToast('Lỗi kết nối', 'error'); });
    }

    function updateCartBadge(count) {
        var badge = document.querySelector('.cart-badge');
        if (!badge) return;
        badge.textContent = count;
        if (count == 0) badge.style.display = 'none';
        else { badge.style.display = ''; }
    }

    function showToast(message, type) {
        var toast = document.querySelector('.app-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'app-toast';
            document.body.appendChild(toast);
            var style = document.createElement('style');
            style.textContent = [
                '.app-toast{position:fixed;top:90px;right:20px;z-index:10000;padding:14px 20px;border-radius:10px;color:#fff;font-size:0.92rem;font-weight:500;min-width:260px;max-width:400px;box-shadow:0 12px 30px rgba(0,0,0,0.18);transform:translateX(120%);transition:transform .35s cubic-bezier(.34,1.56,.64,1);}',
                '.app-toast.show{transform:translateX(0);}',
                '.app-toast.success{background:linear-gradient(135deg,#10b981,#059669);}',
                '.app-toast.error{background:linear-gradient(135deg,#ef4444,#dc2626);}',
                '.app-toast.info{background:linear-gradient(135deg,#3b82f6,#2563eb);}',
                '.app-toast.warning{background:linear-gradient(135deg,#f59e0b,#d97706);}',
                '@media(max-width:768px){.app-toast{top:16px;left:16px;right:16px;min-width:auto;max-width:none;}}'
            ].join('');
            document.head.appendChild(style);
        }
        toast.className = 'app-toast ' + (type || 'info');
        toast.textContent = message;
        requestAnimationFrame(function () { toast.classList.add('show'); });
        clearTimeout(showToast._t);
        showToast._t = setTimeout(function () { toast.classList.remove('show'); }, 3500);
    }

    function initPopup() {
        var overlay = document.getElementById('sitePopup');
        if (!overlay) return;
        try {
            var closed = localStorage.getItem('popup_closed_at');
            if (closed && (Date.now() - parseInt(closed, 10) < 24 * 60 * 60 * 1000)) {
                overlay.remove();
                return;
            }
        } catch (e) {}

        var delay = parseInt(overlay.getAttribute('data-delay') || '3000', 10);
        setTimeout(function () { overlay.classList.add('show'); document.body.style.overflow = 'hidden'; }, delay);

        overlay.querySelectorAll('[data-popup-close], .popup-close, .popup-overlay').forEach(function (el) {
            el.addEventListener('click', function (e) {
                if (el.classList.contains('popup-overlay') && e.target !== el) return;
                closePopup();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('show')) closePopup();
        });

        function closePopup() {
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            try { localStorage.setItem('popup_closed_at', String(Date.now())); } catch (e) {}
        }
    }

    function initFloatingContact() {
        var widget = document.querySelector('.mobile-contact-widget');
        var toggle = document.querySelector('.mobile-contact-toggle');
        var popup = document.querySelector('#mobileContactPopup');
        var backToTop = document.querySelector('.back-to-top');

        if (widget && toggle && popup) {
            function closeContactPopup() {
                popup.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', function () {
                var isOpen = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', String(!isOpen));
                popup.hidden = isOpen;
            });

            popup.addEventListener('click', function (event) {
                if (event.target.closest('.mc-option')) closeContactPopup();
            });

            document.addEventListener('click', function (event) {
                if (!widget.contains(event.target)) closeContactPopup();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeContactPopup();
            });
        }

        if (backToTop) {
            function updateBackToTop() {
                backToTop.classList.toggle('is-visible', window.scrollY > 300);
            }

            window.addEventListener('scroll', updateBackToTop, { passive: true });
            updateBackToTop();
            backToTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    }

    function initAutoTOC() {
        var tocList = document.querySelector('.toc-list');
        var contentEl = document.querySelector('.product-content, .news-detail-content');
        if (!tocList || !contentEl) return;

        var headings = contentEl.querySelectorAll('h2, h3');
        if (headings.length < 3) {
            var wrap = tocList.closest('.toc-wrap, .toc-box');
            if (wrap) wrap.style.display = 'none';
            return;
        }

        var html = '';
        var currentH2 = null;
        headings.forEach(function (h, i) {
            var slug = 'heading-' + i + '-' + slugify(h.textContent.trim().slice(0, 40));
            h.id = slug;
            if (h.tagName === 'H2') {
                if (currentH2) html += '</ul>';
                html += '<li><a href="#' + slug + '">' + escapeHtml(h.textContent.trim()) + '</a><ul>';
                currentH2 = true;
            } else {
                if (!currentH2) { html += '<li><ul>'; currentH2 = true; }
                html += '<li><a href="#' + slug + '">' + escapeHtml(h.textContent.trim()) + '</a></li>';
            }
        });
        if (currentH2) html += '</ul>';
        tocList.innerHTML = html;

        var links = tocList.querySelectorAll('a');
        var headingMap = {};
        headings.forEach(function (h) { headingMap[h.id] = h; });

        links.forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var id = link.getAttribute('href').slice(1);
                var el = headingMap[id];
                if (el) {
                    window.scrollTo({ top: el.getBoundingClientRect().top + window.pageYOffset - 90, behavior: 'smooth' });
                    history.replaceState(null, '', '#' + id);
                }
            });
        });

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        links.forEach(function (l) { l.classList.remove('active'); });
                        var active = tocList.querySelector('a[href="#' + entry.target.id + '"]');
                        if (active) {
                            active.classList.add('active');
                            var parentUl = active.closest('ul');
                            if (parentUl) {
                                var parentA = parentUl.parentElement.querySelector(':scope > a');
                                if (parentA) parentA.classList.add('active');
                            }
                        }
                    }
                });
            }, { rootMargin: '-100px 0px -70% 0px', threshold: 0 });
            headings.forEach(function (h) { observer.observe(h); });
        }

        function slugify(s) {
            return (s || '').toString().toLowerCase()
                .replace(/á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/g, 'a')
                .replace(/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/g, 'e')
                .replace(/í|ì|ỉ|ĩ|ị/g, 'i')
                .replace(/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/g, 'o')
                .replace(/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/g, 'u')
                .replace(/ý|ỳ|ỷ|ỹ|ỵ/g, 'y')
                .replace(/đ/g, 'd')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '') || 'h';
        }

        function escapeHtml(s) {
            var div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }
    }

    function initSearchForms() {
        document.querySelectorAll('form[action*="search"], .search-form-big, .header-search-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var input = form.querySelector('input[type="search"], input[name="q"]');
                if (!input) return;
                var q = (input.value || '').trim();
                if (q.length < 2) {
                    e.preventDefault();
                    showToast('Vui lòng nhập từ khóa tìm kiếm ít nhất 2 ký tự!', 'warning');
                    if (input.classList) input.focus();
                }
            });
        });
    }

    function initSmoothAnchors() {
        document.querySelectorAll('a[href^="#"]:not([href="#"])').forEach(function (a) {
            if (a.getAttribute('data-skip-smooth')) return;
            a.addEventListener('click', function (e) {
                var hash = a.getAttribute('href');
                if (!hash || hash.length < 2) return;
                var target = document.querySelector(hash);
                if (!target) return;
                e.preventDefault();
                var offset = target.getBoundingClientRect().top + window.pageYOffset - 80;
                window.scrollTo({ top: offset, behavior: 'smooth' });
                history.replaceState(null, '', hash);
            });
        });
    }

})();
