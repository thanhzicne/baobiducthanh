<?php
require_once __DIR__ . '/db.php';

function customer_ensure_tables(PDO $pdo): void
{
    static $ready = false;
    if ($ready || (int)($_SESSION['customer_schema_version'] ?? 0) >= 3) {
        $ready = true;
        return;
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(200) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(200) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        company VARCHAR(200) DEFAULT NULL,
        address VARCHAR(500) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $usernameColumn = $pdo->query("SHOW COLUMNS FROM customers LIKE 'username'")->fetch();
    if (!$usernameColumn) {
        $pdo->exec('ALTER TABLE customers ADD username VARCHAR(50) NULL AFTER full_name');
    }
    $pdo->exec("UPDATE customers SET username = CONCAT('customer', id) WHERE username IS NULL OR username = ''");
    $usernameIndex = false;
    foreach ($pdo->query('SHOW INDEX FROM customers')->fetchAll() as $index) {
        if ($index['Column_name'] === 'username' && (int)$index['Non_unique'] === 0) {
            $usernameIndex = true;
            break;
        }
    }
    if (!$usernameIndex) $pdo->exec('ALTER TABLE customers ADD UNIQUE KEY uq_customers_username (username)');
    $pdo->exec('ALTER TABLE customers MODIFY username VARCHAR(50) NOT NULL');
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_conversations (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        customer_id BIGINT UNSIGNED NOT NULL UNIQUE,
        status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_chat_conversation_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_messages (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        conversation_id BIGINT UNSIGNED NOT NULL,
        sender_type ENUM('customer', 'admin') NOT NULL,
        sender_id BIGINT UNSIGNED DEFAULT NULL,
        message TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_chat_messages_conversation (conversation_id, id),
        CONSTRAINT fk_chat_message_conversation FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $readCursorColumn = $pdo->query("SHOW COLUMNS FROM chat_conversations LIKE 'last_admin_read_message_id'")->fetch();
    if (!$readCursorColumn) {
        $pdo->exec('ALTER TABLE chat_conversations ADD last_admin_read_message_id BIGINT UNSIGNED NOT NULL DEFAULT 0');
        $pdo->exec("UPDATE chat_conversations c SET last_admin_read_message_id = COALESCE((SELECT MAX(m.id) FROM chat_messages m WHERE m.conversation_id = c.id AND m.sender_type = 'customer'), 0)");
    }
    $ready = true;
    if (session_status() === PHP_SESSION_ACTIVE) $_SESSION['customer_schema_version'] = 3;
}

function customer_is_logged_in()
{
    return !empty($_SESSION['customer_id']);
}

function customer_set_session(array $customer): void
{
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int)$customer['id'];
    $_SESSION['customer_name'] = $customer['full_name'];
    $_SESSION['customer_username'] = $customer['username'] ?? '';
    $_SESSION['customer_email'] = $customer['email'];
    $_SESSION['customer_last_active'] = time();
}

function customer_require_login()
{
    if (!customer_is_logged_in()) {
        flash_set('customer_auth', 'Vui lòng đăng nhập để tiếp tục.', 'warning');
        redirect(site_url('dang-nhap?next=gio-bao-gia/checkout'));
    }
    if (time() - (int)($_SESSION['customer_last_active'] ?? 0) > CUSTOMER_SESSION_TIMEOUT) {
        customer_logout();
        flash_set('customer_auth', 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.', 'warning');
        redirect(site_url('dang-nhap?next=gio-bao-gia/checkout'));
    }
    $_SESSION['customer_last_active'] = time();
}

function customer_logout()
{
    unset(
        $_SESSION['customer_id'],
        $_SESSION['customer_name'],
        $_SESSION['customer_username'],
        $_SESSION['customer_email'],
        $_SESSION['customer_last_active'],
        $_SESSION['cart'],
        $_SESSION['cart_backup'],
        $_SESSION['order_form']
    );
    session_regenerate_id(true);
}

function order_ensure_tables(PDO $pdo): void
{
    static $ready = false;
    if ($ready) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_code VARCHAR(30) NOT NULL UNIQUE,
        customer_id BIGINT UNSIGNED DEFAULT NULL,
        customer_name VARCHAR(200) NOT NULL,
        customer_phone VARCHAR(30) NOT NULL,
        shipping_name VARCHAR(200) NOT NULL,
        shipping_phone VARCHAR(30) NOT NULL,
        shipping_address VARCHAR(500) NOT NULL,
        payment_method VARCHAR(30) NOT NULL DEFAULT 'cod',
        note TEXT,
        total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order_id BIGINT UNSIGNED NOT NULL,
        product_id BIGINT UNSIGNED DEFAULT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_image VARCHAR(255) DEFAULT NULL,
        unit VARCHAR(50) DEFAULT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        unit_price DECIMAL(15,2) NOT NULL DEFAULT 0,
        subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
        INDEX idx_order_items_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $cartCols = $pdo->query("SHOW TABLES LIKE 'customer_carts'")->fetch();
    if (!$cartCols) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS customer_carts (
            customer_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
            items TEXT NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $ready = true;
}

/** Lấy giỏ hàng: ưu tiên DB theo customer (giữ khi đăng xuất), fallback session cho khách vãng lai.
 * Khi đăng nhập: gộp giỏ session (khách vãng lai) vào giỏ DB, giữ số lượng lớn hơn và lưu lại DB.
 */
function cart_get_items()
{
    start_app_session();
    $sessionItems = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? $_SESSION['cart'] : [];
    if (!empty($_SESSION['customer_id'])) {
        global $pdo;
        try {
            order_ensure_tables($pdo);
            $st = $pdo->prepare('SELECT items FROM customer_carts WHERE customer_id = ?');
            $st->execute([(int)$_SESSION['customer_id']]);
            $raw = $st->fetchColumn();
            $dbItems = [];
            if ($raw !== false && $raw !== null && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) $dbItems = $decoded;
            }
            if (!empty($sessionItems)) {
                // Gộp: với mỗi sản phẩm lấy quantity lớn giữa session và DB
                foreach ($sessionItems as $pid => $item) {
                    $pid = (int)$pid;
                    if (!isset($dbItems[$pid]) || ((int)($item['quantity'] ?? 0) > (int)($dbItems[$pid]['quantity'] ?? 0))) {
                        $dbItems[$pid] = $item;
                    }
                }
                $st = $pdo->prepare('INSERT INTO customer_carts (customer_id, items, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE items = VALUES(items), updated_at = NOW()');
                $st->execute([(int)$_SESSION['customer_id'], json_encode($dbItems, JSON_UNESCAPED_UNICODE)]);
            }
            $_SESSION['cart'] = $dbItems;
            return $dbItems;
        } catch (Exception $e) {
        }
    }
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

/** Lưu giỏ hàng: nếu đăng nhập thì lưu DB (theo customer_id), luôn lưu session. */
function cart_set_items(array $items): void
{
    start_app_session();
    $_SESSION['cart'] = is_array($items) ? $items : [];
    if (!empty($_SESSION['customer_id'])) {
        global $pdo;
        try {
            order_ensure_tables($pdo);
            $st = $pdo->prepare('INSERT INTO customer_carts (customer_id, items, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE items = VALUES(items), updated_at = NOW()');
            $st->execute([(int)$_SESSION['customer_id'], json_encode($_SESSION['cart'], JSON_UNESCAPED_UNICODE)]);
        } catch (Exception $e) {
        }
    }
}

/** Xoá giỏ hàng khỏi DB + session (dùng sau khi đặt hàng thành công). */
function cart_clear_all()
{
    start_app_session();
    if (!empty($_SESSION['customer_id'])) {
        global $pdo;
        try {
            $pdo->prepare('DELETE FROM customer_carts WHERE customer_id = ?')->execute([(int)$_SESSION['customer_id']]);
        } catch (Exception $e) {
        }
    }
    $_SESSION['cart'] = [];
}
