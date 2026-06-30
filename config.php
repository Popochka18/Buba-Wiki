<?php
/**
 * Avroria wiki — конфигурация и общие хелперы.
 */

declare(strict_types=1);

mb_internal_encoding('UTF-8');

// --- Основные настройки -----------------------------------------------------

const SITE_NAME    = 'Avroria wiki';
const SITE_TAGLINE = 'Хроники мира Аврории';
const HOME_PAGE    = 'Белая длань';      // Страница, открывающаяся на главной.

// Базовый URL-префикс (пусто при запуске через `php -S ... router.php`).
const BASE = '';

// --- Пути --------------------------------------------------------------------

define('ROOT_DIR',   __DIR__);
define('PAGES_DIR',  ROOT_DIR . '/pages');
define('IMAGES_DIR', ROOT_DIR . '/images');
define('LIB_DIR',    ROOT_DIR . '/lib');
define('TPL_DIR',    ROOT_DIR . '/templates');

// Зарезервированные ключи фронтматтера — не выводятся строками инфобокса.
const RESERVED_KEYS = ['image', 'title', 'collections', 'коллекции'];

// --- Автозагрузка классов ----------------------------------------------------

spl_autoload_register(static function (string $class): void {
    $file = LIB_DIR . '/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// --- Локализация -------------------------------------------------------------

I18n::init();

/** Локализованная строка интерфейса (см. lib/I18n.php). */
function t(string $key, int|float|string ...$args): string
{
    return I18n::t($key, ...$args);
}

/** URL текущей страницы с переключением языка. */
function lang_url(string $lang): string
{
    $uri   = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path  = $parts['path'] ?? '/';
    parse_str($parts['query'] ?? '', $q);
    $q['lang'] = $lang;
    return $path . '?' . http_build_query($q);
}

// --- URL-хелперы -------------------------------------------------------------

function url(string $path = ''): string
{
    return BASE . '/' . ltrim($path, '/');
}

function url_view(string $page): string
{
    return url('wiki/' . rawurlencode($page));
}

function url_edit(string $page): string
{
    return url('edit/' . rawurlencode($page));
}

function url_collections(): string
{
    return url('collections');
}

function url_collection(string $name): string
{
    return url('collection/' . rawurlencode($name));
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function image_url(string $file): string
{
    return url('images/' . rawurlencode($file));
}

// --- Безопасность ------------------------------------------------------------

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Русское склонение существительного по числу (1 страница, 2 страницы, 5 страниц). */
function plural_ru(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    $n1 = $n % 10;
    if ($n > 10 && $n < 20) {
        return $many;
    }
    if ($n1 === 1) {
        return $one;
    }
    if ($n1 >= 2 && $n1 <= 4) {
        return $few;
    }
    return $many;
}

/**
 * Приводит произвольное название к безопасному имени для файловой системы:
 * запрещает обход каталогов и управляющие символы, сохраняя кириллицу,
 * запятые и двоеточия (нужны для названий глав).
 */
function sanitize_page_name(string $name): string
{
    $name = str_replace(["\0", "\r", "\n", "\t"], '', $name);
    $name = trim($name);
    // Убираем разделители путей и попытки выхода вверх.
    $name = str_replace(['/', '\\'], ' ', $name);
    $name = preg_replace('/\.{2,}/u', '.', $name) ?? $name;
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? $name;
    $name = trim($name, " .");
    return $name === '' ? 'Без названия' : $name;
}
