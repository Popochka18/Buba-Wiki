<?php

declare(strict_types=1);

/**
 * Локализация интерфейса / Interface localisation.
 *
 * Язык выбирается так: параметр ?lang=xx (запоминается в cookie), затем cookie,
 * затем язык по умолчанию. Строки интерфейса считаются доверенными и выводятся
 * как есть; подставляемые значения нужно экранировать самостоятельно.
 */
final class I18n
{
    public const DEFAULT_LANG = 'ru';

    /** @var string Текущий язык. */
    private static string $lang = self::DEFAULT_LANG;

    /** @var array<string,array<string,string>> Строки интерфейса по языкам. */
    private static array $messages = [];

    public static function init(): void
    {
        self::load();

        $lang = null;

        if (isset($_GET['lang'])) {
            $cand = strtolower(trim((string) $_GET['lang']));
            if (isset(self::$messages[$cand])) {
                $lang = $cand;
                // Запоминаем выбор на год.
                if (!headers_sent()) {
                    setcookie('lang', $lang, [
                        'expires'  => time() + 31536000,
                        'path'     => '/',
                        'samesite' => 'Lax',
                    ]);
                }
                $_COOKIE['lang'] = $lang;
            }
        }

        if ($lang === null && isset($_COOKIE['lang'])) {
            $cand = strtolower((string) $_COOKIE['lang']);
            if (isset(self::$messages[$cand])) {
                $lang = $cand;
            }
        }

        self::$lang = $lang ?? self::DEFAULT_LANG;
    }

    public static function lang(): string
    {
        return self::$lang;
    }

    /** @return string[] Коды доступных языков. */
    public static function languages(): array
    {
        self::load();
        return array_keys(self::$messages);
    }

    /**
     * Возвращает локализованную строку по ключу. Дополнительные аргументы
     * подставляются через sprintf (значения должны быть экранированы заранее).
     */
    public static function t(string $key, int|float|string ...$args): string
    {
        self::load();
        $msg = self::$messages[self::$lang][$key]
            ?? self::$messages[self::DEFAULT_LANG][$key]
            ?? $key;
        return $args ? vsprintf($msg, $args) : $msg;
    }

    /** Множественное число для счётных существительных (страницы, коллекции, …). */
    public static function plural(string $key, int $n): string
    {
        self::load();
        $forms = self::$plurals[self::$lang][$key] ?? self::$plurals[self::DEFAULT_LANG][$key] ?? [$key];
        return $forms[self::pluralIndex($n)] ?? $forms[count($forms) - 1];
    }

    private static function pluralIndex(int $n): int
    {
        if (self::$lang === 'ru') {
            $n = abs($n) % 100;
            $n1 = $n % 10;
            if ($n > 10 && $n < 20) {
                return 2;
            }
            if ($n1 === 1) {
                return 0;
            }
            if ($n1 >= 2 && $n1 <= 4) {
                return 1;
            }
            return 2;
        }
        // Языки с двумя формами (en).
        return $n === 1 ? 0 : 1;
    }

    /**
     * Подмножество строк, нужных клиентскому коду (JS).
     *
     * @return array<string,string>
     */
    public static function jsStrings(): array
    {
        self::load();
        $keys = [
            'js.search_empty', 'js.toggle_source', 'js.toggle_visual',
            'js.link_prompt', 'js.wikilink_prompt', 'js.need_title',
            'js.saving', 'js.saved', 'js.error_prefix', 'js.not_saved', 'js.net_error',
            'js.loading', 'js.load_failed', 'js.gallery_empty',
            'js.uploading', 'js.uploaded', 'js.upload_failed', 'js.net_error_upload',
            'js.img_none', 'js.field_key_ph', 'js.field_val_ph',
            'js.move_up', 'js.move_down', 'js.delete', 'js.template_none',
        ];
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = self::t($k);
        }
        return $out;
    }

    /**
     * Готовые шаблоны инфобоксов: набор предзаполняемых полей по типу страницы.
     * Метки полей локализованы под текущий язык.
     *
     * @return array<int,array{id:string,name:string,fields:string[]}>
     */
    public static function infoboxTemplates(): array
    {
        self::load();
        $defs = [
            'character' => ['race', 'gender', 'status', 'affiliation', 'occupation', 'born', 'died'],
            'faction'   => ['type', 'capital', 'leader', 'founded', 'territory'],
            'location'  => ['type', 'region', 'population', 'ruler'],
            'people'    => ['range', 'language', 'lifespan', 'traits'],
            'creature'  => ['type', 'habitat', 'danger', 'diet'],
            'artifact'  => ['type', 'owner', 'origin', 'properties'],
        ];
        $out = [];
        foreach ($defs as $id => $fieldKeys) {
            $out[] = [
                'id'     => $id,
                'name'   => self::t('tpl.' . $id),
                'fields' => array_map(static fn(string $f) => self::t('tplf.' . $f), $fieldKeys),
            ];
        }
        return $out;
    }

    // --- Данные ---------------------------------------------------------------

    /** @var array<string,array<string,string[]>> */
    private static array $plurals = [];

    private static bool $loaded = false;

    private static function load(): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        self::$plurals = [
            'ru' => [
                'pages'       => ['страница', 'страницы', 'страниц'],
                'collections' => ['коллекция', 'коллекции', 'коллекций'],
                'images'      => ['изображение', 'изображения', 'изображений'],
            ],
            'en' => [
                'pages'       => ['page', 'pages'],
                'collections' => ['collection', 'collections'],
                'images'      => ['image', 'images'],
            ],
        ];

        self::$messages = [
            'ru' => self::ru(),
            'en' => self::en(),
        ];
    }

    /** @return array<string,string> */
    private static function ru(): array
    {
        return [
            'lang.name'            => 'Русский',
            'site.tagline'         => 'Хроники мира Аврории',

            // Шапка и навигация.
            'nav.home'             => 'Главная',
            'nav.collections'      => 'Коллекции',
            'nav.all'              => 'Все страницы',
            'search.placeholder'   => 'Поиск по летописи…',
            'search.submit'        => 'Искать',
            'footer.text'          => '%s · собрано хранителями знаний · %s',

            // Заголовки страниц (для <title>).
            'title.home'           => 'Заглавная страница',
            'title.editor'         => 'Редактор: %s',
            'title.collections'    => 'Коллекции',
            'title.collection'     => 'Коллекция: %s',
            'title.all'            => 'Все страницы',
            'title.not_found'      => 'Страница не найдена',

            // Главная.
            'home.tagline'         => 'Летопись мира <strong>Аврории</strong> — фракции и народы, города и пустыни, книги и герои. Открой свиток и узнай, что хранят хроники.',
            'home.search'          => 'Найти статью в летописи…',
            'home.featured'        => '✶ Избранная статья',
            'home.read'            => 'Читать статью →',
            'home.collections'     => '◆ Коллекции',
            'home.collections_empty' => 'Коллекции ещё не созданы.',
            'home.recent'          => '❧ Недавние страницы',
            'home.more'            => 'Все страницы →',
            'home.recent_empty'    => 'Страниц пока нет.',
            'home.cta'             => 'Знаешь то, чего ещё нет в летописи?',
            'home.cta_button'      => '✎ Написать новую страницу',
            'count.pages_short'    => '%d стр.',

            // Просмотр статьи.
            'view.edit'            => '✎ Редактировать',

            // Коллекции.
            'collections.subtitle' => 'Тематические подборки страниц Аврории.',
            'collections.empty'    => 'Коллекции ещё не созданы. Добавьте странице поле <code>collections</code> в редакторе инфобокса.',
            'collection.back'      => '← Все коллекции',
            'collection.empty'     => 'В этой коллекции пока нет страниц.',

            // Все страницы.
            'all.new'              => '✎ Новая страница',
            'all.empty'            => 'Страниц пока нет.',

            // Несуществующая страница.
            'missing.body'         => 'В летописи Аврории пока нет страницы <strong>%s</strong>.',
            'missing.hint'         => 'Свитки ждут своего автора.',
            'missing.create'       => '✎ Создать эту страницу',

            // Имя новой страницы.
            'page.new_name'        => 'Новая страница',

            // Инфобокс (рендер).
            'infobox.label'        => 'Карточка статьи',
            'infobox.no_image'     => 'Нет изображения',

            // Редактор.
            'editor.name'          => 'Название',
            'editor.cancel'        => 'Отмена',
            'editor.save'          => '💾 Сохранить',
            'editor.tb.h2'         => 'Заголовок 2',
            'editor.tb.h3'         => 'Заголовок 3',
            'editor.tb.p'          => 'Абзац',
            'editor.tb.bold'       => 'Жирный (Ctrl+B)',
            'editor.tb.italic'     => 'Курсив (Ctrl+I)',
            'editor.tb.code'       => 'Моноширинный',
            'editor.tb.ul'         => 'Маркированный список',
            'editor.tb.ol'         => 'Нумерованный список',
            'editor.tb.quote'      => 'Цитата',
            'editor.tb.hr'         => 'Разделитель',
            'editor.tb.link'       => 'Внешняя ссылка',
            'editor.tb.wikilink'   => 'Вики-ссылка [[…]]',
            'editor.tb.image'      => 'Вставить изображение',
            'editor.tb.toggle'     => 'Переключить режим',
            'editor.tb.source'     => '⟱ Исходник',
            'editor.infobox'       => '⚜ Инфобокс',
            'editor.card_image'    => 'Изображение карточки',
            'editor.choose'        => 'Выбрать…',
            'editor.remove'        => 'Убрать',
            'editor.template'      => 'Шаблон',
            'editor.template_hint' => 'Подставит типовые поля выбранного шаблона.',
            'editor.fields'        => 'Поля',
            'editor.add_field'     => '＋ Добавить поле',
            'editor.collections'   => 'Коллекции',
            'editor.collections_ph' => 'через запятую, напр.: Фракции, Народы',
            'editor.collections_hint' => 'Страница появится в этих коллекциях.',
            'editor.tips'          => 'Подсказки',
            'editor.tip1'          => '<code>[[Страница]]</code> — вики-ссылка',
            'editor.tip2'          => '<code>[[Страница|текст]]</code> — ссылка с подписью',
            'editor.tip3'          => 'Выделите текст и нажмите <b>[[ ]]</b>, чтобы связать',
            'editor.tip4'          => '«Исходник» — правка в Markdown напрямую',
            'editor.import'        => '🖼 Импорт изображений',
            'editor.close'         => 'Закрыть',
            'editor.drop'          => 'Перетащите изображение сюда<br>или %s',
            'editor.choose_file'   => 'выберите файл',
            'editor.library'       => 'Библиотека изображений',
            'editor.filter'        => 'фильтр…',

            // Шаблоны инфобоксов.
            'tpl.character'        => 'Персонаж',
            'tpl.faction'          => 'Фракция',
            'tpl.location'         => 'Локация',
            'tpl.people'           => 'Народ',
            'tpl.creature'         => 'Существо',
            'tpl.artifact'         => 'Артефакт',
            'tplf.race'            => 'Раса',
            'tplf.gender'          => 'Пол',
            'tplf.status'          => 'Статус',
            'tplf.affiliation'     => 'Принадлежность',
            'tplf.occupation'      => 'Род занятий',
            'tplf.born'            => 'Родился',
            'tplf.died'            => 'Умер',
            'tplf.type'            => 'Тип',
            'tplf.capital'         => 'Столица',
            'tplf.leader'          => 'Лидер',
            'tplf.founded'         => 'Основана',
            'tplf.territory'       => 'Территория',
            'tplf.region'          => 'Регион',
            'tplf.population'      => 'Население',
            'tplf.ruler'           => 'Правитель',
            'tplf.range'           => 'Ареал',
            'tplf.language'        => 'Язык',
            'tplf.lifespan'        => 'Продолжительность жизни',
            'tplf.traits'          => 'Особенности',
            'tplf.habitat'         => 'Среда обитания',
            'tplf.danger'          => 'Опасность',
            'tplf.diet'            => 'Питание',
            'tplf.owner'           => 'Владелец',
            'tplf.origin'          => 'Происхождение',
            'tplf.properties'      => 'Свойства',

            // API-сообщения.
            'api.need_title'       => 'Укажите название страницы',
            'api.bad_infobox'      => 'Некорректные данные инфобокса',
            'api.write_failed'     => 'Не удалось записать файл страницы',
            'api.post_required'    => 'Требуется метод POST',
            'api.no_file'          => 'Файл не передан',
            'api.bad_size'         => 'недопустимый размер',
            'api.bad_format'       => 'недопустимый формат',
            'api.not_image'        => 'файл не является изображением',
            'api.save_failed'      => 'не удалось сохранить',
            'api.upload_error'     => 'ошибка загрузки',
            'api.upload_failed'    => 'Не удалось загрузить: %s',

            // Строки для JS.
            'js.search_empty'      => 'Ничего не найдено',
            'js.toggle_source'     => '⟱ Исходник',
            'js.toggle_visual'     => '⟰ Визуально',
            'js.link_prompt'       => 'Адрес ссылки (http://…):',
            'js.wikilink_prompt'   => 'Название страницы для вики-ссылки:',
            'js.need_title'        => 'Укажите название',
            'js.saving'            => 'Сохранение…',
            'js.saved'             => '✓ Сохранено',
            'js.error_prefix'      => 'Ошибка: ',
            'js.not_saved'         => 'не сохранено',
            'js.net_error'         => 'Ошибка сети',
            'js.loading'           => 'Загрузка…',
            'js.load_failed'       => 'Не удалось загрузить',
            'js.gallery_empty'     => 'Изображений пока нет. Загрузите первое выше.',
            'js.uploading'         => 'Загрузка %d файл(ов)…',
            'js.uploaded'          => '✓ Загружено: %d',
            'js.upload_failed'     => 'не удалось',
            'js.net_error_upload'  => 'Ошибка сети при загрузке',
            'js.img_none'          => 'нет',
            'js.field_key_ph'      => 'Название поля',
            'js.field_val_ph'      => 'значение (можно [[ссылку]])',
            'js.move_up'           => 'Выше',
            'js.move_down'         => 'Ниже',
            'js.delete'            => 'Удалить',
            'js.template_none'     => '— выберите шаблон —',
        ];
    }

    /** @return array<string,string> */
    private static function en(): array
    {
        return [
            'lang.name'            => 'English',
            'site.tagline'         => 'Chronicles of the world of Avroria',

            // Header & navigation.
            'nav.home'             => 'Home',
            'nav.collections'      => 'Collections',
            'nav.all'              => 'All pages',
            'search.placeholder'   => 'Search the chronicle…',
            'search.submit'        => 'Search',
            'footer.text'          => '%s · compiled by the keepers of lore · %s',

            // Page titles (for <title>).
            'title.home'           => 'Home',
            'title.editor'         => 'Editor: %s',
            'title.collections'    => 'Collections',
            'title.collection'     => 'Collection: %s',
            'title.all'            => 'All pages',
            'title.not_found'      => 'Page not found',

            // Home.
            'home.tagline'         => 'The chronicle of the world of <strong>Avroria</strong> — factions and peoples, cities and deserts, books and heroes. Unroll the scroll and discover what the chronicles keep.',
            'home.search'          => 'Find an article in the chronicle…',
            'home.featured'        => '✶ Featured article',
            'home.read'            => 'Read article →',
            'home.collections'     => '◆ Collections',
            'home.collections_empty' => 'No collections yet.',
            'home.recent'          => '❧ Recent pages',
            'home.more'            => 'All pages →',
            'home.recent_empty'    => 'No pages yet.',
            'home.cta'             => 'Know something the chronicle is missing?',
            'home.cta_button'      => '✎ Write a new page',
            'count.pages_short'    => '%d pp.',

            // Article view.
            'view.edit'            => '✎ Edit',

            // Collections.
            'collections.subtitle' => "Themed selections of Avroria's pages.",
            'collections.empty'    => 'No collections yet. Add a <code>collections</code> field to a page in the infobox editor.',
            'collection.back'      => '← All collections',
            'collection.empty'     => 'This collection has no pages yet.',

            // All pages.
            'all.new'              => '✎ New page',
            'all.empty'            => 'No pages yet.',

            // Missing page.
            'missing.body'         => 'The chronicle of Avroria has no page <strong>%s</strong> yet.',
            'missing.hint'         => 'The scrolls await their author.',
            'missing.create'       => '✎ Create this page',

            // New page name.
            'page.new_name'        => 'New page',

            // Infobox (render).
            'infobox.label'        => 'Article infobox',
            'infobox.no_image'     => 'No image',

            // Editor.
            'editor.name'          => 'Title',
            'editor.cancel'        => 'Cancel',
            'editor.save'          => '💾 Save',
            'editor.tb.h2'         => 'Heading 2',
            'editor.tb.h3'         => 'Heading 3',
            'editor.tb.p'          => 'Paragraph',
            'editor.tb.bold'       => 'Bold (Ctrl+B)',
            'editor.tb.italic'     => 'Italic (Ctrl+I)',
            'editor.tb.code'       => 'Monospace',
            'editor.tb.ul'         => 'Bulleted list',
            'editor.tb.ol'         => 'Numbered list',
            'editor.tb.quote'      => 'Quote',
            'editor.tb.hr'         => 'Divider',
            'editor.tb.link'       => 'External link',
            'editor.tb.wikilink'   => 'Wiki link [[…]]',
            'editor.tb.image'      => 'Insert image',
            'editor.tb.toggle'     => 'Toggle mode',
            'editor.tb.source'     => '⟱ Source',
            'editor.infobox'       => '⚜ Infobox',
            'editor.card_image'    => 'Card image',
            'editor.choose'        => 'Choose…',
            'editor.remove'        => 'Remove',
            'editor.template'      => 'Template',
            'editor.template_hint' => 'Adds the typical fields of the chosen template.',
            'editor.fields'        => 'Fields',
            'editor.add_field'     => '＋ Add field',
            'editor.collections'   => 'Collections',
            'editor.collections_ph' => 'comma-separated, e.g.: Factions, Peoples',
            'editor.collections_hint' => 'The page will appear in these collections.',
            'editor.tips'          => 'Tips',
            'editor.tip1'          => '<code>[[Page]]</code> — wiki link',
            'editor.tip2'          => '<code>[[Page|text]]</code> — link with a label',
            'editor.tip3'          => 'Select text and press <b>[[ ]]</b> to link it',
            'editor.tip4'          => '“Source” — edit Markdown directly',
            'editor.import'        => '🖼 Import images',
            'editor.close'         => 'Close',
            'editor.drop'          => 'Drag an image here<br>or %s',
            'editor.choose_file'   => 'choose a file',
            'editor.library'       => 'Image library',
            'editor.filter'        => 'filter…',

            // Infobox templates.
            'tpl.character'        => 'Character',
            'tpl.faction'          => 'Faction',
            'tpl.location'         => 'Location',
            'tpl.people'           => 'People',
            'tpl.creature'         => 'Creature',
            'tpl.artifact'         => 'Artifact',
            'tplf.race'            => 'Race',
            'tplf.gender'          => 'Gender',
            'tplf.status'          => 'Status',
            'tplf.affiliation'     => 'Affiliation',
            'tplf.occupation'      => 'Occupation',
            'tplf.born'            => 'Born',
            'tplf.died'            => 'Died',
            'tplf.type'            => 'Type',
            'tplf.capital'         => 'Capital',
            'tplf.leader'          => 'Leader',
            'tplf.founded'         => 'Founded',
            'tplf.territory'       => 'Territory',
            'tplf.region'          => 'Region',
            'tplf.population'      => 'Population',
            'tplf.ruler'           => 'Ruler',
            'tplf.range'           => 'Range',
            'tplf.language'        => 'Language',
            'tplf.lifespan'        => 'Lifespan',
            'tplf.traits'          => 'Traits',
            'tplf.habitat'         => 'Habitat',
            'tplf.danger'          => 'Danger',
            'tplf.diet'            => 'Diet',
            'tplf.owner'           => 'Owner',
            'tplf.origin'          => 'Origin',
            'tplf.properties'      => 'Properties',

            // API messages.
            'api.need_title'       => 'Provide a page title',
            'api.bad_infobox'      => 'Invalid infobox data',
            'api.write_failed'     => 'Failed to write the page file',
            'api.post_required'    => 'POST method required',
            'api.no_file'          => 'No file provided',
            'api.bad_size'         => 'invalid size',
            'api.bad_format'       => 'invalid format',
            'api.not_image'        => 'file is not an image',
            'api.save_failed'      => 'failed to save',
            'api.upload_error'     => 'upload error',
            'api.upload_failed'    => 'Upload failed: %s',

            // Strings for JS.
            'js.search_empty'      => 'Nothing found',
            'js.toggle_source'     => '⟱ Source',
            'js.toggle_visual'     => '⟰ Visual',
            'js.link_prompt'       => 'Link URL (http://…):',
            'js.wikilink_prompt'   => 'Page name for the wiki link:',
            'js.need_title'        => 'Enter a title',
            'js.saving'            => 'Saving…',
            'js.saved'             => '✓ Saved',
            'js.error_prefix'      => 'Error: ',
            'js.not_saved'         => 'not saved',
            'js.net_error'         => 'Network error',
            'js.loading'           => 'Loading…',
            'js.load_failed'       => 'Failed to load',
            'js.gallery_empty'     => 'No images yet. Upload the first one above.',
            'js.uploading'         => 'Uploading %d file(s)…',
            'js.uploaded'          => '✓ Uploaded: %d',
            'js.upload_failed'     => 'failed',
            'js.net_error_upload'  => 'Network error during upload',
            'js.img_none'          => 'none',
            'js.field_key_ph'      => 'Field name',
            'js.field_val_ph'      => 'value (you can use [[links]])',
            'js.move_up'           => 'Move up',
            'js.move_down'         => 'Move down',
            'js.delete'            => 'Delete',
            'js.template_none'     => '— choose a template —',
        ];
    }
}
