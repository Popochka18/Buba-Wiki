<?php
/**
 * Роутер для встроенного сервера PHP:
 *   php -S localhost:8000 router.php
 *
 * Отдаёт статические файлы напрямую, остальное направляет в index.php.
 */

declare(strict_types=1);

$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$file = __DIR__ . $uri;

// Прямая отдача существующих статических файлов (css/js/images и т.п.).
if ($uri !== '/' && is_file($file)) {
    $ext  = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'ico'  => 'image/x-icon',
        'woff2'=> 'font/woff2',
    ][$ext] ?? null;

    // Не отдаём напрямую php-файлы как текст.
    if ($ext === 'php') {
        return false;
    }
    if ($mime) {
        header('Content-Type: ' . $mime);
    }
    readfile($file);
    return true;
}

// Всё остальное — через единую точку входа.
require __DIR__ . '/index.php';
