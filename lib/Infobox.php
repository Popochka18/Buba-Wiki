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
        $images = $page->images();
        $valid  = array_values(array_filter(
            $images,
            static fn(string $f) => is_file(IMAGES_DIR . '/' . $f)
        ));

        if (empty($images) && empty($fields)) {
            return '';
        }

        $h  = '<aside class="infobox" aria-label="' . e(t('infobox.label')) . '">';
        $h .= '<div class="infobox__title">' . e($page->title()) . '</div>';

        // Изображение: одно — статичное, несколько — карусель.
        $h .= '<div class="infobox__image">';
        if (count($valid) > 1) {
            $h .= self::carousel($valid, $page->title());
        } elseif (count($valid) === 1) {
            $h .= '<img src="' . image_url($valid[0]) . '" alt="' . e($page->title()) . '">';
        } else {
            $h .= '<div class="infobox__noimage" title="' . (!empty($images) ? e($images[0]) : '') . '">'
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

    /**
     * Карусель изображений: слайды, стрелки, точки. Управление — в app.js
     * по атрибуту data-carousel; без JS показывается первый слайд.
     *
     * @param string[] $files
     */
    private static function carousel(array $files, string $title): string
    {
        $h = '<div class="carousel" data-carousel>';

        $h .= '<div class="carousel__viewport">';
        foreach ($files as $i => $file) {
            $h .= '<img class="carousel__slide' . ($i === 0 ? ' is-active' : '') . '" '
                . 'src="' . image_url($file) . '" '
                . 'alt="' . e($title) . ' — ' . ($i + 1) . '/' . count($files) . '"'
                . ($i === 0 ? '' : ' loading="lazy"') . '>';
        }
        $h .= '<button type="button" class="carousel__btn carousel__btn--prev" data-dir="-1" '
            . 'aria-label="' . e(t('infobox.prev')) . '">‹</button>';
        $h .= '<button type="button" class="carousel__btn carousel__btn--next" data-dir="1" '
            . 'aria-label="' . e(t('infobox.next')) . '">›</button>';
        $h .= '<span class="carousel__counter">1 / ' . count($files) . '</span>';
        $h .= '</div>';

        $h .= '<div class="carousel__dots" role="tablist">';
        foreach ($files as $i => $file) {
            $h .= '<button type="button" class="carousel__dot' . ($i === 0 ? ' is-active' : '') . '" '
                . 'data-index="' . $i . '" aria-label="' . e(t('infobox.goto', $i + 1)) . '"></button>';
        }
        $h .= '</div>';

        $h .= '</div>';
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
