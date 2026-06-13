<?php

declare(strict_types=1);

/**
 * Страница вики: разбор и запись файлов pages/<имя>.md с YAML-подобным
 * фронтматтером и Markdown-телом.
 */
final class Page
{
    public string $name;
    /** @var array<string,mixed> Поля фронтматтера в порядке объявления. */
    public array $meta = [];
    public string $body = '';
    public bool $exists = false;

    public function __construct(string $name)
    {
        $this->name = sanitize_page_name($name);
    }

    public static function path(string $name): string
    {
        return PAGES_DIR . '/' . sanitize_page_name($name) . '.md';
    }

    public static function existsByName(string $name): bool
    {
        return is_file(self::path($name));
    }

    public static function load(string $name): self
    {
        $page = new self($name);
        $file = self::path($name);
        if (!is_file($file)) {
            return $page;
        }
        $page->exists = true;
        $raw = (string) file_get_contents($file);
        [$page->meta, $page->body] = self::parse($raw);
        return $page;
    }

    /**
     * Разбирает сырой текст на [мета, тело].
     *
     * @return array{0:array<string,mixed>,1:string}
     */
    public static function parse(string $raw): array
    {
        $raw  = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw; // BOM
        $raw  = str_replace("\r\n", "\n", $raw);
        $meta = [];
        $body = $raw;

        if (preg_match('/^---\n(.*?)\n---\n?(.*)$/s', $raw, $m)) {
            $meta = self::parseFrontmatter($m[1]);
            $body = ltrim($m[2], "\n");
        }

        return [$meta, $body];
    }

    /**
     * @return array<string,mixed>
     */
    private static function parseFrontmatter(string $block): array
    {
        $meta  = [];
        foreach (explode("\n", $block) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $pos = mb_strpos($line, ':');
            if ($pos === false) {
                continue;
            }
            $key = trim(mb_substr($line, 0, $pos));
            $val = trim(mb_substr($line, $pos + 1));

            $val = self::unquote($val);

            // Списки в формате [a, b, c]. Не путаем с вики-ссылками [[Страница]]:
            // открывающая скобка не должна сопровождаться второй, а закрывающая —
            // следовать за другой закрывающей.
            if (preg_match('/^\[(?!\[)(.*)(?<!\])\]$/s', $val, $lm)) {
                $items = array_map(
                    static fn($s) => self::unquote(trim($s)),
                    array_filter(explode(',', $lm[1]), static fn($s) => trim($s) !== '')
                );
                $meta[$key] = array_values($items);
                continue;
            }

            $meta[$key] = $val;
        }
        return $meta;
    }

    private static function unquote(string $v): string
    {
        if (strlen($v) >= 2) {
            $first = $v[0];
            $last  = $v[strlen($v) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($v, 1, -1);
            }
        }
        return $v;
    }

    /** Заголовок страницы: явный title или имя файла. */
    public function title(): string
    {
        if (!empty($this->meta['title'])) {
            return (string) $this->meta['title'];
        }
        return $this->name;
    }

    /** Имя файла изображения инфобокса (если задано). */
    public function image(): ?string
    {
        return !empty($this->meta['image']) ? (string) $this->meta['image'] : null;
    }

    /** @return string[] Коллекции, которым принадлежит страница. */
    public function collections(): array
    {
        $raw = $this->meta['collections'] ?? $this->meta['коллекции'] ?? [];
        if (is_string($raw)) {
            $raw = array_filter(array_map('trim', explode(',', $raw)));
        }
        return array_values(array_map('strval', (array) $raw));
    }

    /** Поля, отображаемые строками инфобокса (без зарезервированных). */
    public function infoboxFields(): array
    {
        $fields = [];
        foreach ($this->meta as $key => $value) {
            if (in_array(mb_strtolower($key), RESERVED_KEYS, true)) {
                continue;
            }
            $fields[$key] = $value;
        }
        return $fields;
    }

    public function bodyHtml(): string
    {
        return Markdown::render($this->body);
    }

    /** Краткий анонс: тело статьи, очищенное от разметки. */
    public function excerpt(int $length = 220): string
    {
        $text = $this->body;
        $text = preg_replace('/^#{1,6}\s+.*$/mu', '', $text) ?? $text;     // заголовки
        $text = preg_replace('/^\s*>\s?/mu', '', $text) ?? $text;          // цитаты
        $text = preg_replace('/^\s*[-*+]\s+/mu', '', $text) ?? $text;      // маркеры
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/u', '', $text) ?? $text; // картинки
        $text = preg_replace('/\[\[[^\]|]+\|([^\]]+)\]\]/u', '$1', $text) ?? $text; // [[A|B]]
        $text = preg_replace('/\[\[([^\]]+)\]\]/u', '$1', $text) ?? $text;  // [[A]]
        $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $text) ?? $text; // [t](u)
        $text = preg_replace('/[*_`#]+/u', '', $text) ?? $text;            // выделения
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);

        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length);
            $text = (preg_replace('/\s+\S*$/u', '', $text) ?? $text) . '…';
        }
        return $text;
    }

    /**
     * Сериализует страницу обратно в текст файла.
     *
     * @param array<string,mixed> $meta
     */
    public static function serialize(array $meta, string $body): string
    {
        $out = '';
        if (!empty($meta)) {
            $out .= "---\n";
            foreach ($meta as $key => $value) {
                $out .= $key . ': ' . self::dumpValue($value) . "\n";
            }
            $out .= "---\n\n";
        }
        $out .= rtrim($body) . "\n";
        return $out;
    }

    private static function dumpValue(mixed $value): string
    {
        if (is_array($value)) {
            return '[' . implode(', ', array_map(static fn($v) => (string) $v, $value)) . ']';
        }
        $value = (string) $value;
        // Заключаем в кавычки значения с вики-ссылками или спецсимволами.
        if ($value === '' || str_contains($value, '[[') || str_contains($value, ':') || str_contains($value, '#')) {
            return '"' . str_replace('"', '\"', $value) . '"';
        }
        return $value;
    }

    public function save(array $meta, string $body): bool
    {
        if (!is_dir(PAGES_DIR)) {
            mkdir(PAGES_DIR, 0775, true);
        }
        $text = self::serialize($meta, $body);
        $ok = file_put_contents(self::path($this->name), $text) !== false;
        if ($ok) {
            $this->exists = true;
            [$this->meta, $this->body] = self::parse($text);
        }
        return $ok;
    }

    /** @return string[] Имена всех существующих страниц. */
    public static function all(): array
    {
        if (!is_dir(PAGES_DIR)) {
            return [];
        }
        $names = [];
        foreach (glob(PAGES_DIR . '/*.md') ?: [] as $file) {
            $names[] = basename($file, '.md');
        }
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        return $names;
    }

    /** @return string[] Недавно изменённые страницы (по времени файла). */
    public static function recent(int $limit = 8): array
    {
        if (!is_dir(PAGES_DIR)) {
            return [];
        }
        $files = glob(PAGES_DIR . '/*.md') ?: [];
        usort($files, static fn($a, $b) => filemtime($b) <=> filemtime($a));
        $names = array_map(static fn($f) => basename($f, '.md'), $files);
        return array_slice($names, 0, $limit);
    }

    public static function count(): int
    {
        return is_dir(PAGES_DIR) ? count(glob(PAGES_DIR . '/*.md') ?: []) : 0;
    }
}
