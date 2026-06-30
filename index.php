<?php
/**
 * Avroria wiki — единая точка входа и маршрутизация.
 */

declare(strict_types=1);

require __DIR__ . '/config.php';

// Markdown должен знать, какие страницы существуют (для «красных» ссылок).
Markdown::$pageExists = static fn(string $name): bool => Page::existsByName($name);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = urldecode($path);
$path = trim($path, '/');

$segments = $path === '' ? [] : explode('/', $path);
$route    = $segments[0] ?? '';
$arg      = isset($segments[1]) ? implode('/', array_slice($segments, 1)) : '';

switch ($route) {
    case '':
        render_home();
        break;

    case 'wiki':
        render_view($arg !== '' ? $arg : HOME_PAGE, false);
        break;

    case 'edit':
        render_editor($arg !== '' ? $arg : HOME_PAGE);
        break;

    case 'collections':
        render_collections();
        break;

    case 'collection':
        render_collection($arg);
        break;

    case 'all':
        render_all_pages();
        break;

    default:
        // Удобный фолбэк: /Имя_страницы.
        render_view($path, false);
        break;
}

// --- Контроллеры -------------------------------------------------------------

function render_home(): void
{
    $featured    = Page::load(HOME_PAGE);
    $collections = Collection::all();
    $recent      = Page::recent(8);
    $pageCount   = Page::count();
    $imgCount    = 0;
    foreach (glob(IMAGES_DIR . '/*') ?: [] as $f) {
        if (preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $f)) {
            $imgCount++;
        }
    }

    $content = view_template('home', [
        'featured'    => $featured->exists ? $featured : null,
        'collections' => $collections,
        'recent'      => $recent,
        'pageCount'   => $pageCount,
        'imgCount'    => $imgCount,
    ]);
    layout(t('title.home'), $content);
}

function render_view(string $name, bool $isHome): void
{
    $page = Page::load($name);

    if (!$page->exists) {
        http_response_code(404);
        $title   = t('title.not_found');
        $content = view_template('missing', ['name' => $name]);
        layout($title, $content);
        return;
    }

    $content = view_template('view', ['page' => $page, 'isHome' => $isHome]);
    layout($page->title(), $content);
}

function render_editor(string $name): void
{
    $page = Page::load($name);
    $content = view_template('editor', ['page' => $page]);
    layout(t('title.editor', $page->title()), $content, true);
}

function render_collections(): void
{
    $map = Collection::all();
    $content = view_template('collections', ['map' => $map]);
    layout(t('title.collections'), $content);
}

function render_collection(string $name): void
{
    $name  = sanitize_page_name($name);
    $pages = Collection::pages($name);
    $content = view_template('collection', ['name' => $name, 'pages' => $pages]);
    layout(t('title.collection', $name), $content);
}

function render_all_pages(): void
{
    $names = Page::all();
    $content = view_template('all', ['names' => $names]);
    layout(t('title.all'), $content);
}

// --- Рендеринг шаблонов ------------------------------------------------------

function view_template(string $name, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require TPL_DIR . '/' . $name . '.php';
    return (string) ob_get_clean();
}

function layout(string $title, string $content, bool $isEditor = false): void
{
    $vars = ['pageTitle' => $title, 'content' => $content, 'isEditor' => $isEditor];
    extract($vars);
    require TPL_DIR . '/layout.php';
}
