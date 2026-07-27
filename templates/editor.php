<?php
/** @var Page $page */
$editorData = [
    'name'      => $page->name,
    'exists'    => $page->exists,
    'body'      => $page->body,
    'meta'      => (object) $page->meta,
    'reserved'  => RESERVED_KEYS,
    'templates' => I18n::infoboxTemplates(),
    'urls'      => [
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
                <span><?= t('editor.name') ?></span>
                <input type="text" id="page-name" value="<?= e($page->name) ?>" spellcheck="false">
            </label>
        </div>
        <div class="editor__bar-right">
            <span class="editor__status" id="save-status"></span>
            <a class="btn" href="<?= $page->exists ? url_view($page->name) : url('') ?>"><?= t('editor.cancel') ?></a>
            <button class="btn btn--primary" id="btn-save" type="button"><?= t('editor.save') ?></button>
        </div>
    </div>

    <div class="editor__workspace">
        <!-- Визуальный редактор -->
        <section class="editor__main" aria-label="Визуальный редактор">
            <div class="editor__toolbar" id="vis-toolbar">
                <div class="tb-group">
                    <button type="button" class="tb" data-cmd="h2"  title="<?= e(t('editor.tb.h2')) ?>">H2</button>
                    <button type="button" class="tb" data-cmd="h3"  title="<?= e(t('editor.tb.h3')) ?>">H3</button>
                    <button type="button" class="tb" data-cmd="p"   title="<?= e(t('editor.tb.p')) ?>">¶</button>
                </div>
                <div class="tb-group">
                    <button type="button" class="tb" data-cmd="bold"   title="<?= e(t('editor.tb.bold')) ?>"><b>B</b></button>
                    <button type="button" class="tb" data-cmd="italic" title="<?= e(t('editor.tb.italic')) ?>"><i>I</i></button>
                    <button type="button" class="tb" data-cmd="code"   title="<?= e(t('editor.tb.code')) ?>">&lt;/&gt;</button>
                </div>
                <div class="tb-group">
                    <button type="button" class="tb" data-cmd="ul"    title="<?= e(t('editor.tb.ul')) ?>">• —</button>
                    <button type="button" class="tb" data-cmd="ol"    title="<?= e(t('editor.tb.ol')) ?>">1.</button>
                    <button type="button" class="tb" data-cmd="quote" title="<?= e(t('editor.tb.quote')) ?>">❝</button>
                    <button type="button" class="tb" data-cmd="hr"    title="<?= e(t('editor.tb.hr')) ?>">―</button>
                </div>
                <div class="tb-group">
                    <button type="button" class="tb" data-cmd="link"     title="<?= e(t('editor.tb.link')) ?>">🔗</button>
                    <button type="button" class="tb" data-cmd="wikilink" title="<?= e(t('editor.tb.wikilink')) ?>">[[ ]]</button>
                    <button type="button" class="tb" data-cmd="image"    title="<?= e(t('editor.tb.image')) ?>">🖼</button>
                </div>
                <div class="tb-group tb-group--right">
                    <button type="button" class="tb tb--toggle" id="toggle-source" title="<?= e(t('editor.tb.toggle')) ?>"><?= t('editor.tb.source') ?></button>
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
                <h2 class="side-panel__title"><?= t('editor.infobox') ?></h2>

                <div class="side-panel__block">
                    <label class="field-label"><?= t('editor.card_image') ?></label>
                    <div class="ib-images" id="ib-images"><!-- миниатюры добавляются JS --></div>
                    <button type="button" class="btn btn--sm btn--ghost" id="ib-image-add"><?= t('editor.add_image') ?></button>
                    <p class="hint"><?= t('editor.images_hint') ?></p>
                </div>

                <div class="side-panel__block">
                    <label class="field-label" for="ib-template"><?= t('editor.template') ?></label>
                    <select id="ib-template" class="field-input">
                        <option value=""><?= e(t('js.template_none')) ?></option>
                        <?php foreach ($editorData['templates'] as $tpl): ?>
                            <option value="<?= e($tpl['id']) ?>"><?= e($tpl['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hint"><?= t('editor.template_hint') ?></p>
                </div>

                <div class="side-panel__block">
                    <label class="field-label"><?= t('editor.fields') ?></label>
                    <div class="ib-fields" id="ib-fields"><!-- строки добавляются JS --></div>
                    <button type="button" class="btn btn--sm btn--ghost" id="ib-add-field"><?= t('editor.add_field') ?></button>
                </div>

                <div class="side-panel__block">
                    <label class="field-label"><?= t('editor.collections') ?></label>
                    <input type="text" id="ib-collections" class="field-input"
                           placeholder="<?= e(t('editor.collections_ph')) ?>">
                    <p class="hint"><?= t('editor.collections_hint') ?></p>
                </div>
            </div>

            <div class="side-panel side-panel--tips">
                <h3 class="side-panel__title side-panel__title--sm"><?= t('editor.tips') ?></h3>
                <ul class="tips">
                    <li><?= t('editor.tip1') ?></li>
                    <li><?= t('editor.tip2') ?></li>
                    <li><?= t('editor.tip3') ?></li>
                    <li><?= t('editor.tip4') ?></li>
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
            <h2 id="image-modal-title"><?= t('editor.import') ?></h2>
            <button type="button" class="modal__close" data-close="image-modal" aria-label="<?= e(t('editor.close')) ?>">✕</button>
        </div>

        <div class="modal__body">
            <div class="dropzone" id="dropzone">
                <span class="dropzone__icon">⬆</span>
                <p><?= t('editor.drop', '<label class="link" for="file-input">' . e(t('editor.choose_file')) . '</label>') ?></p>
                <input type="file" id="file-input" accept="image/*" multiple hidden>
                <div class="dropzone__progress" id="upload-progress" hidden></div>
            </div>

            <div class="gallery-head">
                <span><?= t('editor.library') ?></span>
                <input type="search" id="gallery-filter" placeholder="<?= e(t('editor.filter')) ?>" class="field-input field-input--sm">
            </div>
            <div class="gallery" id="gallery"><!-- заполняется JS --></div>
        </div>
    </div>
</div>
