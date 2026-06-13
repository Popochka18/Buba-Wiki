<?php
/**
 * Список изображений библиотеки.
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
$images  = [];

if (is_dir(IMAGES_DIR)) {
    foreach (scandir(IMAGES_DIR) ?: [] as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $path = IMAGES_DIR . '/' . $file;
        if (!is_file($path)) {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            continue;
        }
        $images[] = [
            'name'  => $file,
            'url'   => image_url($file),
            'size'  => filesize($path) ?: 0,
            'mtime' => filemtime($path) ?: 0,
        ];
    }
}

// Сначала недавно изменённые.
usort($images, static fn($a, $b) => $b['mtime'] <=> $a['mtime']);

json_out(['ok' => true, 'images' => $images]);
