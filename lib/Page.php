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

    /** Язык, на котором фактически загружена страница (после фолбэка). */
    public string $lang = '';

    /**
     * Путь к файлу страницы на заданном языке. Если язык не указан — берётся
     * текущий язык интерфейса. При отсутствии языковых папок используется
     * «плоское» хранилище pages/<имя>.md (обратная совместимость).
     */
    public static function path(string $name, ?string $lang = null): string
    {
        $name = sanitize_page_name($name);
        $lang = $lang ?? I18n::lang();
        if (self::localized()) {
            return PAGES_DIR . '/' . $lang . '/' . $name . '.md';
        }
        return PAGES_DIR . '/' . $name . '.md';
    }

    /** Существуют ли языковые подпапки pages/<lang>/. */
    private static function localized(): bool
    {
        return is_dir(PAGES_DIR . '/' . I18n::DEFAULT_LANG);
    }

    /** Языки, в которых может существовать страница (от текущего к запасным). */
    private static function langChain(?string $lang = null): array
    {
        $lang  = $lang ?? I18n::lang();
        $chain = [$lang];
        if ($lang !== I18n::DEFAULT_LANG) {
            $chain[] = I18n::DEFAULT_LANG;
        }
        return $chain;
    }

    /** Папки со страницами (по одной на язык, либо одна «плоская»). */
    private static function dirs(): array
    {
        if (!self::localized()) {
            return is_dir(PAGES_DIR) ? [PAGES_DIR] : [];
        }
        $dirs = [];
        foreach (I18n::languages() as $l) {
            $d = PAGES_DIR . '/' . $l;
            if (is_dir($d)) {
                $dirs[] = $d;
            }
        }
        return $dirs;
    }

    /** Страница существует хотя бы на одном языке. */
    public static function existsByName(string $name): bool
    {
        foreach (self::langChain() as $lang) {
            if (is_file(self::path($name, $lang))) {
                return true;
            }
        }
        return false;
    }

    public static function load(string $name): self
    {
        $page = new self($name);
        foreach (self::langChain() as $lang) {
            $file = self::path($name, $lang);
            if (is_file($file)) {
                $page->exists = true;
                $page->lang   = self::localized() ? $lang : I18n::lang();
                $raw = (string) file_get_contents($file);
                [$page->meta, $page->body] = self::parse($raw);
                return $page;
            }
        }
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
        $file = self::path($this->name);
        $dir  = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $text = self::serialize($meta, $body);
        $ok = file_put_contents($file, $text) !== false;
        if ($ok) {
            $this->exists = true;
            [$this->meta, $this->body] = self::parse($text);
        }
        return $ok;
    }

    /**
     * Карта: имя страницы => самое позднее время изменения среди языковых
     * версий. Объединяет все языковые папки (или «плоское» хранилище).
     *
     * @return array<string,int>
     */
    private static function index(): array
    {
        $index = [];
        foreach (self::dirs() as $dir) {
            foreach (glob($dir . '/*.md') ?: [] as $file) {
                $name  = basename($file, '.md');
                $mtime = filemtime($file) ?: 0;
                if (!isset($index[$name]) || $mtime > $index[$name]) {
                    $index[$name] = $mtime;
                }
            }
        }
        return $index;
    }

    /** @return string[] Имена всех существующих страниц. */
    public static function all(): array
    {
        $names = array_keys(self::index());
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        return $names;
    }

    /** @return string[] Недавно изменённые страницы (по времени файла). */
    public static function recent(int $limit = 8): array
    {
        $index = self::index();
        arsort($index, SORT_NUMERIC);
        return array_slice(array_keys($index), 0, $limit);
    }

    public static function count(): int
    {
        return count(self::index());
    }
}
