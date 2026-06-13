<?php
/**
 * Загрузка изображений. Принимает multipart-поле `file` (можно несколько).
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

require_post();

if (empty($_FILES['file'])) {
    json_fail('Файл не передан');
}

if (!is_dir(IMAGES_DIR)) {
    mkdir(IMAGES_DIR, 0775, true);
}

const ALLOWED_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
const MAX_UPLOAD_BYTES   = 12 * 1024 * 1024; // 12 МБ

/** Приводит имя файла к безопасному виду, сохраняя расширение. */
function safe_image_name(string $name): string
{
    $name = basename($name);
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $base) ?? 'image';
    $base = trim($base, '._-');
    if ($base === '') {
        $base = 'image_' . date('YmdHis');
    }
    return $base . '.' . $ext;
}

function unique_path(string $name): string
{
    $target = IMAGES_DIR . '/' . $name;
    if (!file_exists($target)) {
        return $target;
    }
    $ext  = pathinfo($name, PATHINFO_EXTENSION);
    $base = pathinfo($name, PATHINFO_FILENAME);
    $n    = 2;
    do {
        $target = IMAGES_DIR . '/' . $base . '_' . $n . '.' . $ext;
        $n++;
    } while (file_exists($target));
    return $target;
}

// Нормализуем структуру $_FILES к списку.
$files = $_FILES['file'];
$items = is_array($files['name']) ? $files['name'] : [$files['name']];
$count = count($items);

$saved  = [];
$errors = [];

for ($i = 0; $i < $count; $i++) {
    $get = static fn(string $k) => is_array($files[$k]) ? $files[$k][$i] : $files[$k];

    $origName = (string) $get('name');
    $tmp      = (string) $get('tmp_name');
    $error    = (int) $get('error');
    $size     = (int) $get('size');

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = $origName . ': ошибка загрузки (' . $error . ')';
        continue;
    }
    if ($size <= 0 || $size > MAX_UPLOAD_BYTES) {
        $errors[] = $origName . ': недопустимый размер';
        continue;
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXT, true)) {
        $errors[] = $origName . ': недопустимый формат';
        continue;
    }

    // Проверяем, что это действительно изображение (svg проверяем отдельно).
    if ($ext !== 'svg') {
        $info = @getimagesize($tmp);
        if ($info === false) {
            $errors[] = $origName . ': файл не является изображением';
            continue;
        }
    }

    $name   = safe_image_name($origName);
    $target = unique_path($name);

    if (!move_uploaded_file($tmp, $target)) {
        // На случай запуска не через обычный upload (например, тесты).
        if (!@rename($tmp, $target)) {
            $errors[] = $origName . ': не удалось сохранить';
            continue;
        }
    }
    @chmod($target, 0644);

    $saved[] = [
        'name' => basename($target),
        'url'  => image_url(basename($target)),
    ];
}

if ($saved === []) {
    json_fail('Не удалось загрузить: ' . implode('; ', $errors));
}

json_out(['ok' => true, 'files' => $saved, 'errors' => $errors]);
