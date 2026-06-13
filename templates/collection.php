<?php /** @var string $name; @var string[] $pages */ ?>
<article class="article">
    <div class="article__collections"><a class="chip" href="<?= url_collections() ?>">← Все коллекции</a></div>
    <h1 class="article__title">◆ <?= e($name) ?></h1>
    <div class="article__rule"></div>

    <?php if (empty($pages)): ?>
        <p class="muted">В этой коллекции пока нет страниц.</p>
    <?php else: ?>
        <ul class="page-list">
            <?php foreach ($pages as $p): ?>
                <li><a href="<?= url_view($p) ?>"><?= e($p) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
