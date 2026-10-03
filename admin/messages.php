<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
admin_verify_csrf_or_die();
require_once __DIR__ . '/../includes/customer.php';
customer_ensure_tables($pdo);
$adminPage = 'conversations.php';
$adminTitle = 'Chat trực tuyến';
require __DIR__ . '/header.php';
?>
<style>
    .chat-layout {
        display: grid;
        grid-template-columns: minmax(240px, 320px) minmax(0, 1fr);
        gap: 16px;
        height: calc(100vh - 190px);
        min-height: 420px
    }

    .chat-list,
    .chat-thread {
        background: #fff;
        border: 1px solid var(--bd);
        border-radius: 8px;
        min-height: 0;
        display: flex;
        flex-direction: column
    }

    .chat-list h2,
    .chat-thread h2 {
        font-size: 1rem;
        padding: 14px 16px;
        border-bottom: 1px solid var(--bd)
    }

    .chat-conversations {
        overflow: auto
    }

    .chat-conversation {
        display: block;
        width: 100%;
        text-align: left;
        border: 0;
        border-bottom: 1px solid var(--bd);
        background: #fff;
        padding: 12px 14px;
        cursor: pointer;
        color: var(--tx);
        font: inherit
    }

    .chat-conversation:hover,
    .chat-conversation.active {
        background: #edf6f3
    }

    .chat-conversation strong,
    .chat-conversation span {
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap
    }

    .chat-conversation span {
        font-size: .78rem;
        color: var(--mu);
        margin-top: 4px
    }

    .chat-conversation .chat-unread-badge {
        float: right;
        width: auto;
        max-width: 36px;
        margin: 0 0 0 8px;
        padding: 1px 7px;
        border-radius: 10px;
        background: #d97706;
        color: #fff;
        font-weight: 700;
        text-align: center
    }

    .chat-thread-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-right: 14px
    }

    .chat-thread-messages {
        padding: 16px;
        flex: 1;
        overflow: auto;
        background: #f7f9f8;
        display: flex;
        flex-direction: column;
        gap: 9px
    }

    .chat-bubble {
        max-width: 78%;
        padding: 9px 12px;
        border-radius: 10px;
        background: #fff;
        border: 1px solid var(--bd);
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        align-self: flex-start
    }

    .chat-bubble[data-sender="admin"] {
        align-self: flex-end;
        background: #e4f3ed;
        border-color: #c0e2d4
    }

    .chat-reply {
        padding: 12px;
        border-top: 1px solid var(--bd);
        display: flex;
        gap: 8px
    }

    .chat-del-btn {
        float: right;
        margin: 10px 12px 0 0;
        padding: 4px 10px;
        border: 1px solid var(--bd);
        border-radius: 6px;
        background: #fff;
        color: var(--er);
        font-size: .74rem;
        font-weight: 600;
        cursor: pointer;
    }

    .chat-del-btn:hover {
        background: #fef2f2;
    }

    .chat-reply textarea {
        flex: 1;
        min-width: 0;
        resize: vertical;
        min-height: 42px;
        border: 1px solid var(--bd);
        border-radius: 6px;
        padding: 9px;
        font: inherit
    }

    @media(max-width:760px) {
        .chat-layout {
            grid-template-columns: 1fr;
            height: auto
        }

        .chat-list {
            max-height: 220px
        }

        .chat-thread {
            height: 58vh;
            min-height: 360px
        }
    }
</style>
<div class="chat-layout" id="adminChat" data-endpoint="<?= e(site_url('api/conversation.php')) ?>" data-csrf="<?= e(csrf_token()) ?>">
    <section class="chat-list">
        <h2>Hội thoại khách hàng</h2>
        <div class="chat-conversations" id="chatConversations" aria-live="polite">Đang tải...</div>
    </section>
    <section class="chat-thread">
        <div class="chat-thread-head">
            <h2 id="chatCustomerTitle">Chọn một hội thoại</h2><span class="mini" id="chatCustomerEmail"></span>
            <button type="button" class="chat-del-btn" id="chatDeleteBtn" hidden>🗑️ Xoá đoạn chat</button>
        </div>
        <div class="chat-thread-messages" id="chatThreadMessages" aria-live="polite"></div>
        <form class="chat-reply" id="chatReplyForm"><textarea name="message" maxlength="2000" required placeholder="Nhập nội dung trả lời..."></textarea><button class="btn" type="submit">Gửi trả lời</button></form>
    </section>
</div>
<script>
    (function() {
        var root = document.getElementById('adminChat'),
            endpoint = root.dataset.endpoint,
            list = document.getElementById('chatConversations');
        var thread = document.getElementById('chatThreadMessages'),
            form = document.getElementById('chatReplyForm');
        var selected = 0,
            lastId = 0,
            csrf = root.dataset.csrf,
            polling = false;

        function apiUrl(action, extra) {
            var params = new URLSearchParams(Object.assign({
                side: 'admin',
                action: action
            }, extra || {}));
            return endpoint + '?' + params.toString();
        }

        function updateUnreadBadge(count) {
            var badge = document.querySelector('[data-admin-badge="open_chats"]');
            if (!badge) return;
            count = Math.max(0, Number(count) || 0);
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count === 0;
        }

        function loadConversations() {
            fetch(apiUrl('conversations'), {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json'
                }
            }).then(function(r) {
                return r.json()
            }).then(function(data) {
                if (!data.success) return;
                updateUnreadBadge(data.unread_total);
                list.textContent = '';
                if (!data.conversations.length) {
                    list.textContent = 'Chưa có hội thoại.';
                    return;
                }
                data.conversations.forEach(function(conversation) {
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'chat-conversation' + (Number(conversation.id) === selected ? ' active' : '');
                    button.dataset.id = conversation.id;
                    button.dataset.email = conversation.email;
                    var del = document.createElement('button');
                    del.type = 'button';
                    del.className = 'chat-del-btn';
                    del.textContent = 'Xoá';
                    del.addEventListener('click', function(e) {
                        e.stopPropagation();
                        if (!window.confirm('Xoá toàn bộ đoạn chat này khỏi hệ thống?')) return;
                        var body = new FormData();
                        body.append('conversation_id', conversation.id);
                        body.append('csrf_token', csrf);
                        fetch(apiUrl('delete'), {
                                method: 'POST',
                                body: body,
                                credentials: 'same-origin',
                                headers: {
                                    Accept: 'application/json'
                                }
                            })
                            .then(function(r) {
                                return r.json();
                            })
                            .then(function(d) {
                                if (!d.success) {
                                    window.alert(d.message || 'Không xoá được.');
                                    return;
                                }
                                if (Number(conversation.id) === selected) {
                                    selected = 0;
                                    thread.textContent = '';
                                    document.getElementById('chatCustomerTitle').textContent = 'Chọn một hội thoại';
                                    document.getElementById('chatCustomerEmail').textContent = '';
                                    document.getElementById('chatDeleteBtn').hidden = true;
                                }
                                loadConversations();
                            })
                            .catch(function() {
                                window.alert('Không thể kết nối.');
                            });
                    });
                    button.appendChild(del);
                    var name = document.createElement('strong');
                    name.textContent = conversation.full_name;
                    var preview = document.createElement('span');
                    preview.textContent = (conversation.last_sender === 'customer' ? 'Khách: ' : 'Tư vấn: ') + (conversation.last_message || 'Chưa có tin nhắn');
                    button.append(name, preview);
                    var unreadCount = Number(conversation.unread_count) || 0;
                    if (unreadCount > 0) {
                        var unreadBadge = document.createElement('span');
                        unreadBadge.className = 'chat-unread-badge';
                        unreadBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                        button.appendChild(unreadBadge);
                    }
                    list.appendChild(button);
                });
            }).catch(function() {});
        }

        function poll() {
            if (!selected || polling) return;
            polling = true;
            var requestedConversation = selected;
            fetch(apiUrl('poll', {
                conversation_id: requestedConversation,
                after_id: lastId
            }), {
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json'
                }
            }).then(function(r) {
                return r.json()
            }).then(function(data) {
                if (selected !== requestedConversation) return;
                if (!data.success) return;
                updateUnreadBadge(data.unread_total);
                data.messages.forEach(function(message) {
                    var bubble = document.createElement('div');
                    bubble.className = 'chat-bubble';
                    bubble.dataset.sender = message.sender_type;
                    bubble.textContent = message.message;
                    thread.appendChild(bubble);
                    lastId = Math.max(lastId, Number(message.id));
                });
                if (data.messages.length) thread.scrollTop = thread.scrollHeight;
            }).catch(function() {}).finally(function() {
                polling = false;
            });
        }
        list.addEventListener('click', function(event) {
            var button = event.target.closest('[data-id]');
            if (!button) return;
            selected = Number(button.dataset.id);
            lastId = 0;
            thread.textContent = '';
            var unreadBadge = button.querySelector('.chat-unread-badge');
            if (unreadBadge) unreadBadge.remove();
            document.getElementById('chatCustomerTitle').textContent = button.querySelector('strong').textContent;
            document.getElementById('chatCustomerEmail').textContent = button.dataset.email;
            document.getElementById('chatDeleteBtn').hidden = false;
            loadConversations();
            poll();
        });
        document.getElementById('chatDeleteBtn').addEventListener('click', function() {
            if (!selected) return;
            if (!window.confirm('Xoá toàn bộ đoạn chat này khỏi hệ thống?')) return;
            var body = new FormData();
            body.append('conversation_id', selected);
            body.append('csrf_token', csrf);
            fetch(apiUrl('delete'), {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(d) {
                    if (!d.success) {
                        window.alert(d.message || 'Không xoá được.');
                        return;
                    }
                    selected = 0;
                    thread.textContent = '';
                    document.getElementById('chatCustomerTitle').textContent = 'Chọn một hội thoại';
                    document.getElementById('chatCustomerEmail').textContent = '';
                    document.getElementById('chatDeleteBtn').hidden = true;
                    loadConversations();
                })
                .catch(function() {
                    window.alert('Không thể kết nối.');
                });
        });
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            if (!selected) return;
            var input = form.elements.message,
                body = new FormData();
            body.append('message', input.value);
            body.append('conversation_id', selected);
            body.append('csrf_token', csrf);
            fetch(apiUrl('send'), {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json'
                }
            }).then(function(r) {
                return r.json()
            }).then(function(data) {
                if (data.success) {
                    input.value = '';
                    poll();
                    loadConversations();
                } else window.alert(data.message || 'Không gửi được tin nhắn.');
            }).catch(function() {
                window.alert('Không thể kết nối.');
            });
        });
        loadConversations();
        window.setInterval(loadConversations, 5000);
        window.setInterval(poll, 2000);
    })();
</script>
<?php require __DIR__ . '/footer.php'; ?>