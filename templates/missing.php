<?php /** @var string $name */ ?>
<article class="article article--missing">
    <h1 class="article__title"><?= e($name) ?></h1>
    <div class="article__rule"></div>
    <div class="missing-card">
        <span class="missing-card__sigil">✶</span>
        <p><?= t('missing.body', e($name)) ?></p>
        <p class="muted"><?= t('missing.hint') ?></p>
        <a class="btn btn--primary" href="<?= url_edit($name) ?>"><?= t('missing.create') ?></a>
    </div>
</article>
