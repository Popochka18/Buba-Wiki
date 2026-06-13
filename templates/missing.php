<?php /** @var string $name */ ?>
<article class="article article--missing">
    <h1 class="article__title"><?= e($name) ?></h1>
    <div class="article__rule"></div>
    <div class="missing-card">
        <span class="missing-card__sigil">✶</span>
        <p>В летописи Аврории пока нет страницы <strong><?= e($name) ?></strong>.</p>
        <p class="muted">Свитки ждут своего автора.</p>
        <a class="btn btn--primary" href="<?= url_edit($name) ?>">✎ Создать эту страницу</a>
    </div>
</article>
