<?php
/**
 * Сохранение страницы. Принимает JSON: { name, body, meta, originalName }.
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

require_post();

$data = read_json_body();

$name = sanitize_page_name((string) ($data['name'] ?? ''));
$body = (string) ($data['body'] ?? '');
$meta = $data['meta'] ?? [];
$originalName = sanitize_page_name((string) ($data['originalName'] ?? ''));

if ($name === '' || $name === 'Без названия') {
    json_fail(t('api.need_title'));
}
if (!is_array($meta)) {
    json_fail(t('api.bad_infobox'));
}

// Нормализуем мету: отбрасываем пустые ключи, приводим значения к строкам/массивам.
$cleanMeta = [];
foreach ($meta as $key => $value) {
    $key = trim((string) $key);
    if ($key === '') {
        continue;
    }
    if (is_array($value)) {
        $value = array_values(array_filter(
            array_map(static fn($v) => trim((string) $v), $value),
            static fn($v) => $v !== ''
        ));
        if ($value === []) {
            continue;
        }
    } else {
        $value = (string) $value;
    }
    $cleanMeta[$key] = $value;
}

$page = new Page($name);
if (!$page->save($cleanMeta, $body)) {
    json_fail(t('api.write_failed'), 500);
}

// Переименование: удаляем старый файл, если имя изменилось.
if ($originalName !== '' && $originalName !== $name && Page::existsByName($originalName)) {
    @unlink(Page::path($originalName));
}

json_out([
    'ok'   => true,
    'name' => $page->name,
    'url'  => url_view($page->name),
]);
