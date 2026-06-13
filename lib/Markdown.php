<?php

declare(strict_types=1);

/**
 * Небольшой, но достаточный для вики разбор Markdown с поддержкой
 * вики-ссылок [[Страница]] и [[Страница|Текст]], изображений и базового
 * блочного/строчного форматирования. Весь пользовательский текст
 * экранируется перед вставкой тегов.
 */
final class Markdown
{
    /** @var callable|null function(string $name): bool — существует ли страница */
    public static $pageExists = null;

    public static function render(string $text): string
    {
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        $lines = explode("\n", $text);

        $html   = [];
        $i      = 0;
        $count  = count($lines);

        while ($i < $count) {
            $line = $lines[$i];

            // Пустая строка — разделитель блоков.
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Горизонтальная линия.
            if (preg_match('/^\s*(---|\*\*\*|___)\s*$/', $line)) {
                $html[] = '<hr>';
                $i++;
                continue;
            }

            // Заголовки ## .. ###### (h1 зарезервирован под заголовок страницы).
            if (preg_match('/^(#{2,6})\s+(.*)$/', $line, $m)) {
                $level   = strlen($m[1]);
                $content = self::inline(trim($m[2]));
                $id      = self::slug(trim($m[2]));
                $html[]  = "<h{$level} id=\"{$id}\">{$content}</h{$level}>";
                $i++;
                continue;
            }

            // Цитата.
            if (preg_match('/^\s*>\s?(.*)$/', $line)) {
                $buf = [];
                while ($i < $count && preg_match('/^\s*>\s?(.*)$/', $lines[$i], $m)) {
                    $buf[] = $m[1];
                    $i++;
                }
                $inner  = self::render(implode("\n", $buf));
                $html[] = "<blockquote>{$inner}</blockquote>";
                continue;
            }

            // Маркированный список.
            if (preg_match('/^\s*[-*+]\s+/', $line)) {
                [$out, $i] = self::parseList($lines, $i, $count, false);
                $html[] = $out;
                continue;
            }

            // Нумерованный список.
            if (preg_match('/^\s*\d+[.)]\s+/', $line)) {
                [$out, $i] = self::parseList($lines, $i, $count, true);
                $html[] = $out;
                continue;
            }

            // Абзац: собираем подряд идущие непустые строки.
            $buf = [];
            while ($i < $count && trim($lines[$i]) !== ''
                && !preg_match('/^(#{2,6}\s|\s*>|\s*[-*+]\s|\s*\d+[.)]\s)/', $lines[$i])
                && !preg_match('/^\s*(---|\*\*\*|___)\s*$/', $lines[$i])) {
                $buf[] = $lines[$i];
                $i++;
            }
            $paragraph = implode("\n", $buf);
            $html[] = '<p>' . self::inline($paragraph) . '</p>';
        }

        return implode("\n", $html);
    }

    /**
     * @return array{0:string,1:int}
     */
    private static function parseList(array $lines, int $i, int $count, bool $ordered): array
    {
        $tag    = $ordered ? 'ol' : 'ul';
        $items  = [];
        $marker = $ordered ? '/^\s*\d+[.)]\s+(.*)$/' : '/^\s*[-*+]\s+(.*)$/';

        while ($i < $count && preg_match($marker, $lines[$i], $m)) {
            $items[] = '<li>' . self::inline($m[1]) . '</li>';
            $i++;
        }

        return ['<' . $tag . '>' . implode('', $items) . '</' . $tag . '>', $i];
    }

    /**
     * Строчное форматирование. Текст сначала экранируется, затем в него
     * вставляются безопасные теги.
     */
    public static function inline(string $text): string
    {
        $text = e($text);

        // Изображения: ![alt](src)
        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)\s]+)(?:\s+&quot;([^&]*)&quot;)?\)/u',
            static function (array $m): string {
                $alt = $m[1];
                $src = self::resolveImage($m[2]);
                return '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">';
            },
            $text
        );

        // Вики-ссылки: [[Страница]] и [[Страница|Текст]]
        $text = preg_replace_callback(
            '/\[\[([^\]|]+)(?:\|([^\]]+))?\]\]/u',
            static function (array $m): string {
                $target = trim($m[1]);
                $label  = isset($m[2]) ? trim($m[2]) : $target;
                $exists = self::$pageExists ? (bool) (self::$pageExists)($target) : true;
                $cls    = $exists ? 'wikilink' : 'wikilink wikilink--new';
                $href   = $exists ? url_view($target) : url_edit($target);
                $title  = $exists ? $target : $target . ' (создать страницу)';
                return '<a class="' . $cls . '" href="' . $href . '" title="' . $title . '">' . $label . '</a>';
            },
            $text
        );

        // Обычные ссылки: [текст](url)
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:[^)\s]+|\/[^)\s]*)\)/u',
            static function (array $m): string {
                $ext = str_starts_with($m[2], 'http');
                $rel = $ext ? ' rel="noopener noreferrer" target="_blank"' : '';
                return '<a href="' . $m[2] . '"' . $rel . '>' . $m[1] . '</a>';
            },
            $text
        );

        // Жирный, курсив, код.
        $text = preg_replace('/\*\*([^*]+)\*\*/u', '<strong>$1</strong>', $text);
        $text = preg_replace('/__([^_]+)__/u', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<![\*\w])\*([^*\n]+)\*(?![\*\w])/u', '<em>$1</em>', $text);
        $text = preg_replace('/(?<![_\w])_([^_\n]+)_(?![_\w])/u', '<em>$1</em>', $text);
        $text = preg_replace('/`([^`]+)`/u', '<code>$1</code>', $text);

        // Мягкий перенос строки внутри абзаца.
        $text = nl2br($text, false);

        return $text;
    }

    private static function resolveImage(string $src): string
    {
        if (str_starts_with($src, 'http') || str_starts_with($src, '/')) {
            return $src;
        }
        // Голое имя файла трактуем как файл из каталога images/.
        $src = preg_replace('#^images/#', '', $src);
        return image_url($src);
    }

    public static function slug(string $text): string
    {
        $text = mb_strtolower(strip_tags($text));
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? '';
        return trim($text, '-');
    }
}
