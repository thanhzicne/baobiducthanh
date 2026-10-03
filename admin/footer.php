</div><!-- /.pad -->
</div><!-- /.main -->
</div><!-- /.adm -->

<script src="https://cdn.jsdelivr.net/npm/quill@1.7.3/dist/quill.min.js"></script>
<script>
    /**
     * Khởi tạo trình soạn thảo Quill cho mọi textarea[data-quill].
     * - data-quill      : bật/tắt soạn thảo WYSIWYG
     * - data-form       : id của <form> chứa textarea (dùng khi textarea nằm ngoài form)
     * - data-placeholder : gợi ý
     * Khi submit, Quill đồng bộ HTML về textarea qua hidden input nên giá trị luôn an toàn.
     */
    (function() {
        var editors = [];
        document.querySelectorAll('textarea[data-quill]').forEach(function(ta) {
            if (!window.Quill) return;
            var quill = new Quill(ta, {
                theme: 'snow',
                placeholder: ta.getAttribute('data-placeholder') || 'Nhập nội dung...',
                modules: {
                    toolbar: [
                        [{
                            header: [1, 2, 3, false]
                        }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{
                            list: 'ordered'
                        }, {
                            list: 'bullet'
                        }],
                        [{
                            align: []
                        }],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });
            editors.push({
                quill: quill,
                ta: ta
            });
            // Nạp sẵn nội dung cũ (Quill tự đọc value của textarea)
            if (ta.value.trim() !== '') {
                quill.clipboard.dangerouslyPasteHTML(ta.value);
                ta.value = '';
            }
        });

        function syncAll() {
            editors.forEach(function(item) {
                var html = item.quill.root.innerHTML;
                // Tránh lưu <p><br></p> rỗng
                item.ta.value = (html === '<p><br></p>' || html === '<div><br></div>') ? '' : html;
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('form').forEach(function(f) {
                f.addEventListener('submit', syncAll);
            });
            // Trường hợp submit bằng JS hoặc form không có (nút ngoài form)
            document.addEventListener('click', function(ev) {
                var btn = ev.target.closest('button[type="submit"], input[type="submit"]');
                if (!btn) return;
                var f = btn.form || (btn.getAttribute('data-form') ? document.getElementById(btn.getAttribute('data-form')) : null);
                if (f) f.addEventListener('submit', syncAll, {
                    once: true
                });
            });
        });
    })();

    // Xác nhận trước khi xóa
    document.addEventListener('click', function(ev) {
        var el = ev.target.closest('[data-confirm]');
        if (el && !confirm(el.getAttribute('data-confirm'))) {
            ev.preventDefault();
            ev.stopPropagation();
        }
    }, true);

    // Tự bỏ chọn checkbox hàng loạt
    document.addEventListener('change', function(ev) {
        var all = ev.target;
        if (!all.matches('[data-check-all]')) return;
        document.querySelectorAll('[data-check-item]').forEach(function(c) {
            c.checked = all.checked;
        });
    });
</script>
<script>
    (function() {
        var badge = document.querySelector('[data-admin-badge="open_chats"]');
        if (!badge) return;
        var endpoint = <?= json_encode(site_url('api/chat.php?side=admin&action=unread_count')) ?>;

        function refreshUnreadBadge() {
            fetch(endpoint, {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json'
                },
                cache: 'no-store'
            }).then(function(response) {
                if (!response.ok) return null;
                return response.json();
            }).then(function(data) {
                if (!data || !data.success) return;
                var count = Math.max(0, Number(data.unread_total) || 0);
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.hidden = count === 0;
            }).catch(function() {});
        }

        refreshUnreadBadge();
        window.setInterval(refreshUnreadBadge, 3000);
    })();
</script>
</body>

</html>