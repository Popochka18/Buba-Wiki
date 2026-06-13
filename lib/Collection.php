<?php

declare(strict_types=1);

/**
 * Коллекции — тематические подборки страниц. Принадлежность задаётся
 * полем `collections` (или `Коллекции`) во фронтматтере страницы.
 */
final class Collection
{
    /**
     * Строит карту: название коллекции => список имён страниц.
     *
     * @return array<string,string[]>
     */
    public static function all(): array
    {
        $map = [];
        foreach (Page::all() as $name) {
            $page = Page::load($name);
            foreach ($page->collections() as $col) {
                $col = trim($col);
                if ($col === '') {
                    continue;
                }
                $map[$col][] = $name;
            }
        }
        foreach ($map as &$pages) {
            sort($pages, SORT_NATURAL | SORT_FLAG_CASE);
        }
        unset($pages);
        ksort($map, SORT_NATURAL | SORT_FLAG_CASE);
        return $map;
    }

    /** @return string[] Страницы конкретной коллекции. */
    public static function pages(string $name): array
    {
        $all = self::all();
        return $all[$name] ?? [];
    }

    /** @return string[] Список всех названий коллекций. */
    public static function names(): array
    {
        return array_keys(self::all());
    }
}
