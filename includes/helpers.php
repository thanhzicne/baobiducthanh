<?php
require_once __DIR__ . '/config.php';

function e(mixed $value): string
{
    return htmlspecialchars(display_brand_text($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function display_brand_text(mixed $value): string
{
    return str_ireplace(
        ['Bao Bì Thành Đạt', 'Bao Bì Thành Dạt', 'Bao Bi Thanh Dat'],
        'Bao bì Đức Thành',
        (string)$value
    );
}

function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

function start_app_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) return;
    ini_set('session.use_strict_mode', '1');
    $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_app_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    start_app_session();
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function site_url(string $path = ''): string
{
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function upload_url(string $path = ''): string
{
    if (preg_match('#^https?://#i', (string)$path)) return $path;
    return rtrim(UPLOAD_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Đường dẫn thumbnail (-thumb) của một ảnh đã upload.
 * Trả về null nếu file thumbnail không tồn tại trên đĩa.
 */
function upload_thumb_url(string $path = ''): ?string
{
    if (empty($path)) return null;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($ext === '') return null;
    $rel = preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '', $path);
    $thumbRel = $rel . '-thumb.' . $ext;
    $abs = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $thumbRel);
    if (!file_exists($abs)) return null;
    return upload_url($thumbRel);
}

/** Ảnh dùng cho lưới danh sách: ưu tiên thumbnail, không có thì dùng ảnh gốc. */
function upload_grid_url(string $path = ''): string
{
    return upload_thumb_url($path) ?: upload_url($path);
}

function flash_set(string $key, string $message, string $type = 'success'): void
{
    start_app_session();
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type
    ];
}

function flash_get(string $key): ?array
{
    start_app_session();
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }
    return null;
}

function format_date(int|string $datetime, string $format = 'd/m/Y H:i'): string
{
    $ts = is_numeric($datetime) ? $datetime : strtotime($datetime);
    return date($format, $ts);
}

function excerpt(string $text, int $length = 160): string
{
    $text = strip_tags($text);
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length, 'UTF-8') . '...';
}

function get_setting(PDO $pdo, string $key, string $default = ''): string
{
    $stmt = $pdo->prepare('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['value'] : $default;
}

function get_all_settings(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT `key`, `value` FROM settings');
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['key']] = $row['value'];
    }
    return $result;
}

// cart_get_items / cart_set_items / cart_count đã chuyển sang includes/customer.php
// (giỏ hàng được lưu vào DB theo tài khoản khách nên tồn tại kể cả khi đăng xuất).

function cart_count()
{
    $count = 0;
    foreach (cart_get_items() as $item) {
        $count += max(0, (int)($item['quantity'] ?? 0));
    }
    return $count;
}

/** Tên gọi cũ, giữ lại để các template cũ không bị lỗi. */
function quote_cart_count()
{
    return cart_count();
}

function rate_limit_check(string $key, int $maxRequests, int $windowSeconds): bool
{
    global $pdo;
    start_app_session();
    $now = time();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $bucket = hash('sha256', $key . '|' . $ip);
    try {
        require_once __DIR__ . '/db.php';
        $cutoff = $now - $windowSeconds;
        $upsert = $pdo->prepare('INSERT INTO rate_limits (bucket_key, window_started, request_count) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE request_count = IF(window_started <= ?, 1, request_count + 1), window_started = IF(window_started <= ?, ?, window_started)');
        $upsert->execute([$bucket, $now, $cutoff, $cutoff, $now]);
        $count = $pdo->prepare('SELECT request_count FROM rate_limits WHERE bucket_key = ?');
        $count->execute([$bucket]);
        return (int)$count->fetchColumn() <= $maxRequests;
    } catch (Throwable $error) {
    }
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key],
        function ($ts) use ($now, $windowSeconds) {
            return ($now - $ts) < $windowSeconds;
        }
    );
    if (count($_SESSION['rate_limit'][$key]) >= $maxRequests) {
        return false;
    }
    $_SESSION['rate_limit'][$key][] = $now;
    return true;
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
