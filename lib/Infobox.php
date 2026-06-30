<?php

declare(strict_types=1);

/**
 * Рендер инфобокса в фэнтези-оформлении на основе фронтматтера страницы.
 */
final class Infobox
{
    public static function render(Page $page): string
    {
        $fields = $page->infoboxFields();
        $image  = $page->image();

        if ($image === null && empty($fields)) {
            return '';
        }

        $h  = '<aside class="infobox" aria-label="' . e(t('infobox.label')) . '">';
        $h .= '<div class="infobox__title">' . e($page->title()) . '</div>';

        // Изображение.
        $h .= '<div class="infobox__image">';
        if ($image !== null && is_file(IMAGES_DIR . '/' . $image)) {
            $h .= '<img src="' . image_url($image) . '" alt="' . e($page->title()) . '">';
        } else {
            $h .= '<div class="infobox__noimage" title="' . ($image !== null ? e($image) : '') . '">'
                . '<span class="infobox__noimage-ic">⚜</span>'
                . '<span>' . e(t('infobox.no_image')) . '</span></div>';
        }
        $h .= '</div>';

        // Строки.
        if (!empty($fields)) {
            $h .= '<table class="infobox__table"><tbody>';
            foreach ($fields as $key => $value) {
                $h .= '<tr><th scope="row">' . e((string) $key) . '</th><td>'
                    . self::value($value) . '</td></tr>';
            }
            $h .= '</tbody></table>';
        }

        $h .= '</aside>';
        return $h;
    }

    private static function value(mixed $value): string
    {
        if (is_array($value)) {
            $parts = array_map(static fn($v) => Markdown::inline((string) $v), $value);
            return implode(', ', $parts);
        }
        $value = trim((string) $value);
        if ($value === '') {
            return '<span class="infobox__empty">—</span>';
        }
        return Markdown::inline($value);
    }
}
