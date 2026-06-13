<?php /** @var Page $page; @var bool $isHome */ ?>
<article class="article">
    <div class="article__head">
        <div class="article__heading">
            <h1 class="article__title"><?= e($page->title()) ?></h1>
            <?php $cols = $page->collections(); if ($cols): ?>
                <div class="article__collections">
                    <?php foreach ($cols as $c): ?>
                        <a class="chip" href="<?= url_collection($c) ?>">◆ <?= e($c) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <a class="btn btn--edit" href="<?= url_edit($page->name) ?>">✎ Редактировать</a>
    </div>

    <div class="article__rule"></div>

    <div class="article__layout">
        <div class="article__body">
            <?= $page->bodyHtml() ?>
        </div>
        <?= Infobox::render($page) ?>
    </div>
</article>
