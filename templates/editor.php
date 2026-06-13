<?php
/** @var Page $page */
$editorData = [
    'name'     => $page->name,
    'exists'   => $page->exists,
    'body'     => $page->body,
    'meta'     => (object) $page->meta,
    'reserved' => RESERVED_KEYS,
    'urls'     => [
        'save'     => url('api/save.php'),
        'upload'   => url('api/upload.php'),
        'images'   => url('api/images.php'),
        'viewBase' => url('wiki/'),
    ],
];
?>
<div class="editor" id="editor"
     data-init='<?= e(json_encode($editorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>

    <!-- Верхняя панель редактора -->
    <div class="editor__bar">
        <div class="editor__bar-left">
            <label class="editor__name">
                <span>Название</span>
                <input type="text" id="page-name" value="<?= e($page->name) ?>" spellcheck="false">
            </label>
        </div>
        <div class="editor__bar-right">
            <span class="editor__status" id="save-status"></span>
            <a class="btn" href="<?= $page->exists ? url_view($page->name) : url('') ?>">Отмена</a>
            <button class="btn btn--primary" id="btn-save" type="button">💾 Сохранить</button>
        </div>
    </div>

    <div class="editor__workspace">
        <!-- Визуальный редактор -->
        <section class="editor__main" aria-label="Визуальный редактор">
            <div class="editor__toolbar" id="vis-toolbar">
                <div class="tb-group">
                    <button type="button" class="tb" data-cmd="h2"  title="Заголовок 2">H2</button>
                    <button type="button" class="tb" data-cmd="h3"  title="Заголовок 3">H3</button>
                    <button type="button" class="tb" data-cmd="p"   title="Абзац">¶</button>
                </div>
                <div class="tb-group">
                    <button type="button" class="tb" data-cmd="bold"   title="Жирный (Ctrl+B)"><b>B</b></button>
                    <button type="button" class="tb" data-cmd="italic" title="Курсив (Ctrl+I)"><i>I</i></button>
                    <button type="button" class="tb" data-cmd="code"   title="Моноширинный">&lt;/&gt;</button>
                </div>
                <div class="tb-group">
                    <button type="button" class="tb" data-cmd="ul"    title="Маркированный список">• —</button>
                    <button type="button" class="tb" data-cmd="ol"    title="Нумерованный список">1.</button>
                    <button type="button" class="tb" data-cmd="quote" title="Цитата">❝</button>
                    <button type="button" class="tb" data-cmd="hr"    title="Разделитель">―</button>
                </div>
                <div class="tb-group">
                    <button type="button" class="tb" data-cmd="link"     title="Внешняя ссылка">🔗</button>
                    <button type="button" class="tb" data-cmd="wikilink" title="Вики-ссылка [[…]]">[[ ]]</button>
                    <button type="button" class="tb" data-cmd="image"    title="Вставить изображение">🖼</button>
                </div>
                <div class="tb-group tb-group--right">
                    <button type="button" class="tb tb--toggle" id="toggle-source" title="Переключить режим">⟱ Исходник</button>
                </div>
            </div>

            <div class="editor__surface">
                <div class="editor__canvas" id="visual-editor" contenteditable="true" spellcheck="true"></div>
                <textarea class="editor__source" id="source-editor" spellcheck="false" hidden></textarea>
            </div>
        </section>

        <!-- Редактор инфобокса -->
        <aside class="editor__side" aria-label="Редактор инфобокса">
            <div class="side-panel">
                <h2 class="side-panel__title">⚜ Инфобокс</h2>

                <div class="side-panel__block">
                    <label class="field-label">Изображение карточки</label>
                    <div class="infobox-image-pick" id="infobox-image-pick">
                        <div class="infobox-image-pick__preview" id="ib-image-preview">
                            <span class="muted">нет</span>
                        </div>
                        <div class="infobox-image-pick__actions">
                            <button type="button" class="btn btn--sm" id="ib-image-choose">Выбрать…</button>
                            <button type="button" class="btn btn--sm btn--ghost" id="ib-image-clear">Убрать</button>
                        </div>
                    </div>
                </div>

                <div class="side-panel__block">
                    <label class="field-label">Поля</label>
                    <div class="ib-fields" id="ib-fields"><!-- строки добавляются JS --></div>
                    <button type="button" class="btn btn--sm btn--ghost" id="ib-add-field">＋ Добавить поле</button>
                </div>

                <div class="side-panel__block">
                    <label class="field-label">Коллекции</label>
                    <input type="text" id="ib-collections" class="field-input"
                           placeholder="через запятую, напр.: Фракции, Народы">
                    <p class="hint">Страница появится в этих коллекциях.</p>
                </div>
            </div>

            <div class="side-panel side-panel--tips">
                <h3 class="side-panel__title side-panel__title--sm">Подсказки</h3>
                <ul class="tips">
                    <li><code>[[Страница]]</code> — вики-ссылка</li>
                    <li><code>[[Страница|текст]]</code> — ссылка с подписью</li>
                    <li>Выделите текст и нажмите <b>[[ ]]</b>, чтобы связать</li>
                    <li>«Исходник» — правка в Markdown напрямую</li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<!-- Окно импорта изображений -->
<div class="modal" id="image-modal" hidden>
    <div class="modal__backdrop" data-close="image-modal"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="image-modal-title">
        <div class="modal__head">
            <h2 id="image-modal-title">🖼 Импорт изображений</h2>
            <button type="button" class="modal__close" data-close="image-modal" aria-label="Закрыть">✕</button>
        </div>

        <div class="modal__body">
            <div class="dropzone" id="dropzone">
                <span class="dropzone__icon">⬆</span>
                <p>Перетащите изображение сюда<br>или <label class="link" for="file-input">выберите файл</label></p>
                <input type="file" id="file-input" accept="image/*" multiple hidden>
                <div class="dropzone__progress" id="upload-progress" hidden></div>
            </div>

            <div class="gallery-head">
                <span>Библиотека изображений</span>
                <input type="search" id="gallery-filter" placeholder="фильтр…" class="field-input field-input--sm">
            </div>
            <div class="gallery" id="gallery"><!-- заполняется JS --></div>
        </div>
    </div>
</div>
