<?php
/**
 * Поиск по названиям и содержимому страниц. GET ?q=…
 */

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$q = trim((string) ($_GET['q'] ?? ''));
if ($q === '' || mb_strlen($q) < 1) {
    json_out(['ok' => true, 'results' => []]);
}

$needle  = mb_strtolower($q);
$results = [];

foreach (Page::all() as $name) {
    $score = null;
    $lowerName = mb_strtolower($name);

    if (mb_strpos($lowerName, $needle) !== false) {
        $score = str_starts_with($lowerName, $needle) ? 0 : 1;
        $snippet = '';
    } else {
        $page = Page::load($name);
        $hay  = mb_strtolower($page->body);
        $pos  = mb_strpos($hay, $needle);
        if ($pos !== false) {
            $score = 2;
            $start = max(0, $pos - 30);
            $snippet = trim(mb_substr($page->body, $start, 90));
        }
    }

    if ($score !== null) {
        $results[] = [
            'name'    => $name,
            'url'     => url_view($name),
            'snippet' => $snippet ?? '',
            'score'   => $score,
        ];
    }
}

usort($results, static fn($a, $b) => [$a['score'], $a['name']] <=> [$b['score'], $b['name']]);
$results = array_slice($results, 0, 10);

json_out(['ok' => true, 'results' => $results]);
