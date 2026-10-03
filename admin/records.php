<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
admin_verify_csrf_or_die();

function admin_clean_rich_text(string $html): string
{
    if (!class_exists('DOMDocument')) return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $document->loadHTML('<?xml encoding="UTF-8"><div id="admin-rich-text">' . $html . '</div>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $root = $document->getElementById('admin-rich-text');
    if (!$root) return '';
    $allowed = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h1', 'h2', 'h3', 'ol', 'ul', 'li', 'blockquote', 'a'];
    $clean = function ($node) use (&$clean, $allowed) {
        for ($child = $node->firstChild; $child;) {
            $next = $child->nextSibling;
            if ($child instanceof DOMElement) {
                if (!in_array(strtolower($child->tagName), $allowed, true)) {
                    while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                    $node->removeChild($child);
                } else {
                    $href = strtolower($child->tagName) === 'a' ? trim($child->getAttribute('href')) : '';
                    while ($child->attributes->length) $child->removeAttributeNode($child->attributes->item(0));
                    if ($href !== '') {
                        $scheme = parse_url($href, PHP_URL_SCHEME);
                        if ($scheme === null || in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true)) $child->setAttribute('href', $href);
                    }
                    $clean($child);
                }
            } elseif (!$child instanceof DOMText) {
                $node->removeChild($child);
            }
            $child = $next;
        }
    };
    $clean($root);
    $result = '';
    foreach ($root->childNodes as $child) $result .= $document->saveHTML($child);
    return $result;
}

function admin_normalize_image_url(string $url): string
{
    $url = trim((string)$url);
    if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
        throw new RuntimeException('Link ảnh phải là URL HTTP hoặc HTTPS hợp lệ.');
    }
    $parts = parse_url($url);
    $host = strtolower((string)($parts['host'] ?? ''));
    $isGoogleHost = (bool)preg_match('/(^|\.)google\.[a-z.]+$/', $host);
    parse_str((string)($parts['query'] ?? ''), $query);
    $googlePath = strtolower((string)($parts['path'] ?? ''));
    $isGoogleRedirect = $isGoogleHost && (in_array($googlePath, ['/imgres', '/url'], true)
        || isset($query['imgurl']) || isset($query['url']) || isset($query['q']));
    if ($isGoogleRedirect) {
        foreach (['imgurl', 'url', 'q'] as $key) {
            $candidate = $query[$key] ?? '';
            if (
                is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_URL)
                && in_array(strtolower((string)parse_url($candidate, PHP_URL_SCHEME)), ['http', 'https'], true)
                && !preg_match('/(^|\.)google\.[a-z.]+$/i', (string)parse_url($candidate, PHP_URL_HOST))
            ) {
                return $candidate;
            }
        }
        throw new RuntimeException('Link Google này không chứa URL ảnh trực tiếp. Hãy mở ảnh trên Google rồi sao chép địa chỉ ảnh.');
    }
    if ($isGoogleHost && !preg_match('/\.(?:jpe?g|png|webp|gif|avif)$/i', $googlePath)) {
        throw new RuntimeException('Hãy dán link trực tiếp tới file ảnh, không phải trang tìm kiếm Google.');
    }
    return $url;
}

$entities = [
    'categories' => ['title' => 'Danh mục', 'table' => 'categories', 'name' => 'name', 'fields' => [
        'name' => ['Tên danh mục', 'text', true],
        'slug' => ['Đường dẫn', 'text', true],
        'description' => ['Mô tả', 'textarea'],
        'image' => ['Ảnh sản phẩm', 'image'],
        'parent_id' => ['Danh mục cha ID', 'number'],
        'sort_order' => ['Thứ tự', 'number'],
        'is_featured' => ['Nổi bật', 'checkbox'],
        'is_active' => ['Đang hiển thị', 'checkbox'],
        'meta_title' => ['SEO title', 'text'],
        'meta_description' => ['SEO description', 'textarea'],
    ]],
    'products' => ['title' => 'Sản phẩm', 'table' => 'products', 'name' => 'name', 'fields' => [
        'category_id' => ['Danh mục', 'category', true],
        'name' => ['Tên sản phẩm', 'text', true],
        'slug' => ['Đường dẫn', 'text', true],
        'short_description' => ['Mô tả ngắn', 'textarea'],
        'description' => ['Nội dung', 'quill'],
        'spec' => ['Thông số', 'textarea'],
        'image' => ['Ảnh sản phẩm', 'image'],
        'gallery' => ['Ảnh bộ sưu tập (JSON/đường dẫn)', 'textarea'],
        'min_order' => ['Số lượng tối thiểu', 'number'],
        'unit' => ['Đơn vị', 'text'],
        'price_start' => ['Giá tham khảo', 'number'],
        'is_featured' => ['Nổi bật', 'checkbox'],
        'is_active' => ['Đang bán', 'checkbox'],
        'sort_order' => ['Thứ tự', 'number'],
        'meta_title' => ['SEO title', 'text'],
        'meta_description' => ['SEO description', 'textarea'],
    ]],
    'news' => ['title' => 'Bài viết', 'table' => 'news', 'name' => 'title', 'fields' => [
        'title' => ['Tiêu đề', 'text', true],
        'slug' => ['Đường dẫn', 'text', true],
        'summary' => ['Tóm tắt', 'textarea'],
        'content' => ['Nội dung', 'quill'],
        'image' => ['Ảnh bài viết', 'image'],
        'author' => ['Tác giả', 'text'],
        'is_published' => ['Đã xuất bản', 'checkbox'],
        'sort_order' => ['Thứ tự', 'number'],
        'meta_title' => ['SEO title', 'text'],
        'meta_description' => ['SEO description', 'textarea'],
    ]],
    'pages' => ['title' => 'Trang tĩnh', 'table' => 'pages', 'name' => 'title', 'fields' => [
        'title' => ['Tiêu đề', 'text', true],
        'slug' => ['Đường dẫn', 'text', true],
        'content' => ['Nội dung', 'quill'],
        'is_active' => ['Đang hiển thị', 'checkbox'],
        'sort_order' => ['Thứ tự', 'number'],
        'meta_title' => ['SEO title', 'text'],
        'meta_description' => ['SEO description', 'textarea'],
    ]],
    'banners' => ['title' => 'Banner', 'table' => 'banners', 'name' => 'title', 'fields' => [
        'title' => ['Tiêu đề', 'text', true],
        'subtitle' => ['Tiêu đề phụ', 'text'],
        'description' => ['Mô tả', 'textarea'],
        'image' => ['Ảnh banner', 'image', true],
        'link' => ['Liên kết', 'text'],
        'position' => ['Vị trí', 'text', true],
        'sort_order' => ['Thứ tự', 'number'],
        'is_active' => ['Đang hiển thị', 'checkbox'],
    ]],
    'testimonials' => ['title' => 'Phản hồi khách hàng', 'table' => 'testimonials', 'name' => 'name', 'fields' => [
        'name' => ['Tên khách hàng', 'text', true],
        'title' => ['Chức vụ', 'text'],
        'company' => ['Công ty', 'text'],
        'avatar' => ['Ảnh đại diện', 'image'],
        'content' => ['Nội dung', 'textarea', true],
        'rating' => ['Đánh giá (1-5)', 'number'],
        'sort_order' => ['Thứ tự', 'number'],
        'is_active' => ['Đang hiển thị', 'checkbox'],
    ]],
    'popups' => ['title' => 'Popup', 'table' => 'popups', 'name' => 'title', 'fields' => [
        'title' => ['Tiêu đề', 'text', true],
        'content' => ['Nội dung', 'quill'],
        'image' => ['Ảnh popup', 'image'],
        'link' => ['Liên kết', 'text'],
        'link_text' => ['Nhãn liên kết', 'text'],
        'delay_seconds' => ['Trễ (giây)', 'number'],
        'scope' => ['Phạm vi (sitewide/home)', 'text'],
        'is_active' => ['Đang bật', 'checkbox'],
    ]],
];

$key = $entityKey ?? ($_GET['type'] ?? '');
if (!isset($entities[$key])) {
    http_response_code(404);
    exit('Không tìm thấy mục quản lý.');
}
$entity = $entities[$key];
$adminPage = $key . '.php';
$adminTitle = 'Quản lý ' . $entity['title'];
$adminSubtitle = '<a href="' . e(admin_url($adminPage)) . '">' . e($entity['title']) . '</a>';
$table = $entity['table'];
$nameField = $entity['name'];
$editId = (int)($_GET['edit'] ?? 0);
$record = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_check_rate_limit('records_' . $key, 60, 300);
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($key === 'categories') {
                $used = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
                $used->execute([$id]);
                if ((int)$used->fetchColumn() > 0) throw new RuntimeException('Không thể xóa danh mục đang có sản phẩm.');
            }
            $delete = $pdo->prepare("DELETE FROM `$table` WHERE id = ?");
            $delete->execute([$id]);
            flash_set('admin_msg', 'Đã xóa mục thành công.', 'success');
            redirect(admin_url($adminPage));
        }
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $currentRecord = [];
            if ($id > 0) {
                $currentStmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
                $currentStmt->execute([$id]);
                $currentRecord = $currentStmt->fetch() ?: [];
                if (!$currentRecord) throw new RuntimeException('Không tìm thấy mục cần chỉnh sửa.');
            }
            $values = [];
            foreach ($entity['fields'] as $field => $definition) {
                $type = $definition[1];
                $value = $type === 'checkbox' ? (isset($_POST[$field]) ? 1 : 0) : trim((string)($_POST[$field] ?? ''));
                if ($id > 0 && $value === '' && !empty($definition[2])) {
                    $value = (string)($currentRecord[$field] ?? '');
                }
                if ($id === 0 && !empty($definition[2]) && $type !== 'image' && $value === '') throw new RuntimeException('Vui lòng nhập ' . $definition[0] . '.');
                if ($type === 'quill' && $value !== '') {
                    $value = admin_clean_rich_text($value);
                }
                if ($type === 'number' && $value !== '' && !is_numeric($value)) throw new RuntimeException($definition[0] . ' phải là số.');
                $values[$field] = $value === '' && $type !== 'checkbox' ? null : $value;
            }
            foreach ($entity['fields'] as $field => $definition) {
                if ($definition[1] !== 'image') continue;
                $fileKey = $field . '_file';
                $imageUrl = trim((string)($_POST[$field] ?? ''));
                $uploadError = $_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE;
                if ($uploadError === UPLOAD_ERR_OK) {
                    require_once __DIR__ . '/../includes/image.php';
                    $upload = image_handle_upload($fileKey, ['watermark' => $key === 'products', 'thumbnail' => true]);
                    if (empty($upload['ok'])) throw new RuntimeException($upload['error'] ?? 'Không thể tải ảnh lên.');
                    $values[$field] = $upload['path'];
                } elseif ($uploadError !== UPLOAD_ERR_NO_FILE) {
                    throw new RuntimeException('Tải ảnh thất bại. Hãy kiểm tra kích thước và thử lại.');
                } elseif ($imageUrl !== '') {
                    $values[$field] = admin_normalize_image_url($imageUrl);
                } elseif (!empty($_POST['remove_' . $field])) {
                    $values[$field] = null;
                } elseif ($id > 0) {
                    $values[$field] = $currentRecord[$field] ?? null;
                } else {
                    $values[$field] = null;
                }
                if ($id === 0 && !empty($definition[2]) && empty($values[$field])) {
                    throw new RuntimeException('Vui lòng tải lên hoặc nhập link ' . $definition[0] . '.');
                }
            }
            if (isset($entity['fields']['slug'])) {
                if (empty($values['slug']) && !empty($values['name'])) $values['slug'] = slugify($values['name']);
                if (empty($values['slug']) && !empty($values['title'])) $values['slug'] = slugify($values['title']);
            }
            if ($key === 'products' && (int)$values['category_id'] < 1) throw new RuntimeException('Vui lòng chọn danh mục sản phẩm.');
            if ($key === 'testimonials' && (int)$values['rating'] > 5) throw new RuntimeException('Đánh giá tối đa là 5.');
            if ($id > 0) {
                $assignments = implode(', ', array_map(function ($field) {
                    return '`' . $field . '` = ?';
                }, array_keys($values)));
                $stmt = $pdo->prepare("UPDATE `$table` SET $assignments WHERE id = ?");
                $stmt->execute(array_merge(array_values($values), [$id]));
                flash_set('admin_msg', 'Đã cập nhật mục thành công.', 'success');
            } else {
                $columns = implode(', ', array_map(function ($field) {
                    return '`' . $field . '`';
                }, array_keys($values)));
                $placeholders = implode(', ', array_fill(0, count($values), '?'));
                $stmt = $pdo->prepare("INSERT INTO `$table` ($columns) VALUES ($placeholders)");
                $stmt->execute(array_values($values));
                flash_set('admin_msg', 'Đã tạo mục thành công.', 'success');
            }
            redirect(admin_url($adminPage));
        }
    } catch (Throwable $error) {
        $message = $error instanceof PDOException ? 'Không thể lưu dữ liệu. Vui lòng kiểm tra slug đã tồn tại chưa.' : $error->getMessage();
    }
}

if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ? LIMIT 1");
    $stmt->execute([$editId]);
    $record = $stmt->fetch() ?: [];
}
$records = $pdo->query("SELECT * FROM `$table` ORDER BY id DESC LIMIT 200")->fetchAll();
$categories = $key === 'products' ? $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll() : [];
require __DIR__ . '/header.php';
?>
<div class="fg">
    <h2><?= e($entity['title']) ?></h2><a class="btn" href="<?= e(admin_url($adminPage)) ?>?new=1">Tạo mới</a>
</div>
<?php if ($message): ?><div class="alert alert-error"><?= e($message) ?></div><?php endif; ?>
<?php if ($editId || isset($_GET['new'])): ?>
    <div class="card">
        <h2><?= $editId ? 'Chỉnh sửa' : 'Tạo mới' ?> <?= e($entity['title']) ?></h2>
        <form method="post" <?= array_filter($entity['fields'], function ($definition) {
                                return $definition[1] === 'image';
                            }) ? 'enctype="multipart/form-data"' : '' ?> action="<?= e(admin_url($adminPage)) ?><?= $editId ? '?edit=' . $editId : '?new=1' ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$editId ?>">
            <div class="row">
                <?php foreach ($entity['fields'] as $field => $definition): $type = $definition[1];
                    $value = $_POST[$field] ?? ($record[$field] ?? ''); ?>
                    <div class="fg-2"><label for="f_<?= e($field) ?>"><?= e($definition[0]) ?><?= !empty($definition[2]) && !$editId ? ' *' : '' ?></label>
                        <?php if ($type === 'checkbox'): ?><label><input id="f_<?= e($field) ?>" type="checkbox" name="<?= e($field) ?>" value="1" <?= $value ? 'checked' : '' ?>> Có</label>
                        <?php elseif ($type === 'category'): ?><select class="inp" id="f_<?= e($field) ?>" name="<?= e($field) ?>" <?= !$editId ? 'required' : '' ?>>
                                <option value="">Chọn danh mục</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= (string)$value === (string)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?>
                            </select>
                        <?php elseif ($type === 'image'): ?>
                            <?php $currentImage = $record[$field] ?? $value; ?>
                            <?php if (!empty($currentImage)): ?><div><img class="imgprev" src="<?= e(upload_url($currentImage)) ?>" alt="<?= e($definition[0]) ?> hiện tại"></div><?php endif; ?>
                            <label for="f_<?= e($field) ?>">Dán link ảnh trực tiếp (HTTP/HTTPS)</label><input class="inp" id="f_<?= e($field) ?>" type="url" name="<?= e($field) ?>" value="<?= e(preg_match('#^https?://#i', (string)$value) ? $value : '') ?>" placeholder="https://example.com/anh.jpg">
                            <label for="f_<?= e($field) ?>_file">Hoặc chọn ảnh từ máy (JPG, PNG, WebP)</label><input class="inp" id="f_<?= e($field) ?>_file" type="file" name="<?= e($field) ?>_file" accept="image/jpeg,image/png,image/webp">
                            <?php if (!empty($currentImage)): ?><label><input type="checkbox" name="remove_<?= e($field) ?>" value="1" <?= !empty($_POST['remove_' . $field]) ? 'checked' : '' ?>> Gỡ ảnh hiện tại</label><?php endif; ?>
                            <div class="hint">Nếu chọn cả file và link, file tải lên được ưu tiên. Để giữ ảnh hiện tại, bỏ trống hai ô. Link từ Google cần là địa chỉ ảnh trực tiếp.</div>
                        <?php elseif ($type === 'textarea' || $type === 'quill'): ?><textarea class="inp" id="f_<?= e($field) ?>" name="<?= e($field) ?>" <?= $type === 'quill' ? 'data-quill' : '' ?>><?= e($value) ?></textarea>
                        <?php else: ?><input class="inp" id="f_<?= e($field) ?>" type="<?= $type === 'number' ? 'number' : 'text' ?>" name="<?= e($field) ?>" value="<?= e($value) ?>" <?= !empty($definition[2]) && !$editId ? 'required' : '' ?>><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div><button class="btn" type="submit">Lưu</button> <a class="btn btn-outline" href="<?= e(admin_url($adminPage)) ?>">Hủy</a>
        </form>
    </div>
<?php endif; ?>
<div class="card">
    <div class="tblwrap">
        <table class="tbl">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= e($entity['title']) ?></th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $item): ?><tr>
                        <td><?= (int)$item['id'] ?></td>
                        <td><?= e($item[$nameField] ?? '') ?><div class="mini"><?= e($item['slug'] ?? '') ?></div>
                        </td>
                        <td><?= !empty($item['is_active']) || !empty($item['is_published']) ? 'Đang bật' : 'Ẩn' ?></td>
                        <td><a class="btn btn-sm btn-outline" href="<?= e(admin_url($adminPage)) ?>?edit=<?= (int)$item['id'] ?>">Sửa</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Xóa mục này?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn btn-sm btn-danger" type="submit">Xóa</button></form>
                        </td>
                    </tr><?php endforeach; ?>
                <?php if (!$records): ?><tr>
                        <td colspan="4" class="empty">Chưa có dữ liệu.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>