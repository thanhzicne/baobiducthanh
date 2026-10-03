<?php
if (!defined('APP_ENV')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/helpers.php';
}

function image_validate_uploaded(string $fileKey)
{
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Không có file hoặc upload lỗi (' . ($_FILES[$fileKey]['error'] ?? 'n/a') . ')'];
    }
    $f = $_FILES[$fileKey];
    if ($f['size'] <= 0) return ['ok' => false, 'error' => 'File rỗng'];
    $maxSize = MAX_UPLOAD_MB * 1024 * 1024;
    if ($f['size'] > $maxSize) return ['ok' => false, 'error' => 'File quá lớn (tối đa ' . MAX_UPLOAD_MB . 'MB)'];

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, $allowedMimes, true)) return ['ok' => false, 'error' => 'Chỉ chấp nhận định dạng JPG, PNG, WebP (MIME: ' . e($mime) . ')'];

    $info = @getimagesize($f['tmp_name']);
    if ($info === false || empty($info[0]) || empty($info[1])) return ['ok' => false, 'error' => 'Không phải file ảnh hợp lệ'];

    $w = (int)$info[0];
    $h = (int)$info[1];
    $type = $info[2];
    if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return ['ok' => false, 'error' => 'Ảnh không phải JPG/PNG/WebP'];
    }

    $extMap = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    return ['ok' => true, 'mime' => $mime, 'type' => $type, 'ext' => $extMap[$type], 'width' => $w, 'height' => $h, 'tmp_name' => $f['tmp_name'], 'size' => $f['size']];
}

function image_create_from(string $path, int $type)
{
    switch ($type) {
        case IMAGETYPE_JPEG:
            $im = @imagecreatefromjpeg($path);
            break;
        case IMAGETYPE_PNG:
            $im = @imagecreatefrompng($path);
            break;
        case IMAGETYPE_WEBP:
            $im = @imagecreatefromwebp($path);
            break;
        default:
            $im = false;
    }
    if (!$im) return false;
    imagealphablending($im, false);
    imagesavealpha($im, true);
    return $im;
}

function image_resize(GdImage $src, int $maxWidth = 1200): GdImage|false
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    if ($sw <= $maxWidth) return $src;
    $ratio = $maxWidth / $sw;
    $nw = $maxWidth;
    $nh = (int)round($sh * $ratio);
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $trans);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $sw, $sh);
    imagedestroy($src);
    return $dst;
}

function image_create_thumbnail(GdImage $src, int $thumbW = 400, int $thumbH = 300, bool $cover = true): GdImage|false
{
    $sw = imagesx($src);
    $sh = imagesy($src);
    $dst = imagecreatetruecolor($thumbW, $thumbH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $trans = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefill($dst, 0, 0, $trans);
    if ($cover) {
        $srcRatio = $sw / $sh;
        $dstRatio = $thumbW / $thumbH;
        if ($srcRatio > $dstRatio) {
            $nh = $sh;
            $nw = (int)round($sh * $dstRatio);
            $sx = (int)round(($sw - $nw) / 2);
            $sy = 0;
        } else {
            $nw = $sw;
            $nh = (int)round($sw / $dstRatio);
            $sx = 0;
            $sy = (int)round(($sh - $nh) / 2);
        }
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $thumbW, $thumbH, $nw, $nh);
    } else {
        if ($sw <= $thumbW && $sh <= $thumbH) {
            imagecopy($dst, $src, (int)round(($thumbW - $sw) / 2), (int)round(($thumbH - $sh) / 2), 0, 0, $sw, $sh);
        } else {
            $ratio = min($thumbW / $sw, $thumbH / $sh);
            $nw = (int)round($sw * $ratio);
            $nh = (int)round($sh * $ratio);
            imagecopyresampled($dst, $src, (int)round(($thumbW - $nw) / 2), (int)round(($thumbH - $nh) / 2), 0, 0, $nw, $nh, $sw, $sh);
        }
    }
    return $dst;
}

function image_apply_watermark(GdImage &$image, ?PDO $pdo = null): bool
{
    try {
        $enabled = (int)get_setting($pdo, 'watermark_enabled', '0');
        if (!$enabled) return true;
        $watermarkRel = get_setting($pdo, 'watermark_image', 'watermark.png');
        if (!$watermarkRel) return true;
        $wmPath = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $watermarkRel;
        if (!file_exists($wmPath)) return true;
        $wmInfo = @getimagesize($wmPath);
        if (!$wmInfo) return true;
        $wmType = (int)$wmInfo[2];
        if (!in_array($wmType, [IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_JPEG], true)) return true;
        $wm = image_create_from($wmPath, $wmType);
        if (!$wm) return true;

        $iw = imagesx($image);
        $ih = imagesy($image);
        $wwOrig = imagesx($wm);
        $whOrig = imagesy($wm);

        $widthPct = (float)get_setting($pdo, 'watermark_width_percent', '22');
        if ($widthPct < 5) $widthPct = 5;
        if ($widthPct > 80) $widthPct = 80;
        $targetW = (int)round($iw * $widthPct / 100);
        if ($targetW < 40) $targetW = 40;
        $ratio = $targetW / $wwOrig;
        $targetH = (int)round($whOrig * $ratio);
        $wmScaled = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($wmScaled, false);
        imagesavealpha($wmScaled, true);
        $trans = imagecolorallocatealpha($wmScaled, 0, 0, 0, 127);
        imagefill($wmScaled, 0, 0, $trans);
        imagecopyresampled($wmScaled, $wm, 0, 0, 0, 0, $targetW, $targetH, $wwOrig, $whOrig);
        imagedestroy($wm);
        $wm = $wmScaled;

        $marginPct = (float)get_setting($pdo, 'watermark_margin_percent', '3');
        $mx = (int)round($iw * $marginPct / 100);
        $my = (int)round($ih * $marginPct / 100);
        $dx = $iw - $targetW - $mx;
        $dy = $ih - $targetH - $my;
        if ($dx < 0) $dx = 0;
        if ($dy < 0) $dy = 0;

        $opacity = (int)get_setting($pdo, 'watermark_opacity', '60');
        if ($opacity < 0) $opacity = 0;
        if ($opacity > 100) $opacity = 100;
        $alpha = (int)round(127 - (127 * $opacity / 100));

        imagealphablending($image, true);
        $cut = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($cut, false);
        imagesavealpha($cut, true);
        $tc = imagecolorallocatealpha($cut, 0, 0, 0, 127);
        imagefill($cut, 0, 0, $tc);
        imagecopy($cut, $wm, 0, 0, 0, 0, $targetW, $targetH);
        for ($y = 0; $y < $targetH; $y++) {
            for ($x = 0; $x < $targetW; $x++) {
                $px = imagecolorat($cut, $x, $y);
                $a = ($px >> 24) & 0x7F;
                if ($a >= 127) continue;
                $newA = min(127, $a + (127 - $a) * (127 - $alpha) / 127);
                $r = ($px >> 16) & 0xFF;
                $g = ($px >> 8) & 0xFF;
                $b = $px & 0xFF;
                imagesetpixel($cut, $x, $y, ($newA << 24) | ($r << 16) | ($g << 8) | $b);
            }
        }
        imagecopy($image, $cut, $dx, $dy, 0, 0, $targetW, $targetH);
        imagedestroy($cut);
        imagedestroy($wm);
        return true;
    } catch (Exception $e) {
        error_log('Watermark error: ' . $e->getMessage());
        return false;
    }
}

function image_save(GdImage $image, string $path, int $type, int $qualityJpeg = 88, int $qualityWebp = 88): bool
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) return false;
    }
    imagealphablending($image, false);
    imagesavealpha($image, true);
    switch ($type) {
        case IMAGETYPE_JPEG:
            $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefill($bg, 0, 0, $white);
            imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $r = @imagejpeg($bg, $path, $qualityJpeg);
            imagedestroy($bg);
            break;
        case IMAGETYPE_PNG:
            $r = @imagepng($image, $path, 7);
            break;
        case IMAGETYPE_WEBP:
            $r = @imagewebp($image, $path, $qualityWebp);
            break;
        default:
            $r = false;
    }
    return $r;
}

function image_random_filename(string $ext): string
{
    do {
        $name = date('Ymd') . '-' . substr(bin2hex(random_bytes(10)), 0, 18);
        $path = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . $name . '.' . $ext;
    } while (file_exists($path));
    return $path;
}

function image_relative_path(string $absPath): string
{
    $up = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, UPLOAD_DIR), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $abs = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absPath);
    if (strpos($abs, $up) === 0) return str_replace(DIRECTORY_SEPARATOR, '/', substr($abs, strlen($up)));
    return str_replace(DIRECTORY_SEPARATOR, '/', basename($absPath));
}

function image_handle_upload(string $fileKey, array $opts = []): array
{
    global $pdo;
    $resizeMax = array_key_exists('resize_max', $opts) ? (int)$opts['resize_max'] : 1200;
    $makeThumb = array_key_exists('thumbnail', $opts) ? !empty($opts['thumbnail']) : true;
    $thumbSize = isset($opts['thumb_size']) ? $opts['thumb_size'] : [400, 300];
    $doWatermark = array_key_exists('watermark', $opts) ? !empty($opts['watermark']) : true;

    $check = image_validate_uploaded($fileKey);
    if (!$check['ok']) return $check;

    $decoderFunctions = [
        IMAGETYPE_JPEG => 'imagecreatefromjpeg',
        IMAGETYPE_PNG => 'imagecreatefrompng',
        IMAGETYPE_WEBP => 'imagecreatefromwebp',
    ];
    $encoderFunctions = [
        IMAGETYPE_JPEG => 'imagejpeg',
        IMAGETYPE_PNG => 'imagepng',
        IMAGETYPE_WEBP => 'imagewebp',
    ];
    if (
        !function_exists('imagecreatetruecolor')
        || !function_exists($decoderFunctions[$check['type']] ?? '')
        || !function_exists($encoderFunctions[$check['type']] ?? '')
    ) {
        $absPath = image_random_filename($check['ext']);
        $targetDir = dirname($absPath);
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true)) {
            return ['ok' => false, 'error' => 'Không thể tạo thư mục lưu ảnh.'];
        }
        if (!move_uploaded_file($check['tmp_name'], $absPath)) {
            return ['ok' => false, 'error' => 'Không thể lưu ảnh đã tải lên.'];
        }
        return [
            'ok' => true,
            'path' => image_relative_path($absPath),
            'abs_path' => $absPath,
            'width' => $check['width'],
            'height' => $check['height'],
            'size' => filesize($absPath),
            'type' => $check['type'],
            'ext' => $check['ext']
        ];
    }

    try {
        $src = image_create_from($check['tmp_name'], $check['type']);
        if (!$src) return ['ok' => false, 'error' => 'Không thể đọc ảnh'];
        if ($resizeMax > 0) {
            $src = image_resize($src, $resizeMax);
        }
        if ($doWatermark && function_exists('get_setting') && $pdo) {
            image_apply_watermark($src, $pdo);
        }

        $absPath = image_random_filename($check['ext']);
        $saved = image_save($src, $absPath, $check['type']);
        if (!$saved) {
            imagedestroy($src);
            return ['ok' => false, 'error' => 'Không thể lưu ảnh đích'];
        }
        $relPath = image_relative_path($absPath);
        $result = [
            'ok' => true,
            'path' => $relPath,
            'abs_path' => $absPath,
            'width' => imagesx($src),
            'height' => imagesy($src),
            'size' => filesize($absPath),
            'type' => $check['type'],
            'ext' => $check['ext']
        ];
        if ($makeThumb) {
            $thumb = image_create_thumbnail($src, (int)$thumbSize[0], (int)$thumbSize[1], true);
            $thumbAbs = substr($absPath, 0, strlen($absPath) - strlen($check['ext']) - 1) . '-thumb.' . $check['ext'];
            image_save($thumb, $thumbAbs, $check['type']);
            imagedestroy($thumb);
            $result['thumb'] = image_relative_path($thumbAbs);
            $result['thumb_abs'] = $thumbAbs;
        }
        imagedestroy($src);
        return $result;
    } catch (Exception $e) {
        return ['ok' => false, 'error' => 'Xử lý ảnh lỗi: ' . $e->getMessage()];
    }
}

function image_delete_file(?string $relPath): bool
{
    if (!$relPath) return true;
    $abs = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $relPath), DIRECTORY_SEPARATOR);
    if (file_exists($abs)) @unlink($abs);
    $tExt = pathinfo($abs, PATHINFO_EXTENSION);
    $thumb = substr($abs, 0, -strlen($tExt) - 1) . '-thumb.' . $tExt;
    if (file_exists($thumb)) @unlink($thumb);
    return true;
}
