<?php /** @var array<string,string[]> $map */ ?>
<article class="article">
    <h1 class="article__title">Коллекции</h1>
    <div class="article__rule"></div>
    <p class="muted">Тематические подборки страниц Аврории.</p>

    <?php if (empty($map)): ?>
        <p class="muted">Коллекции ещё не созданы. Добавьте странице поле <code>collections</code> в редакторе инфобокса.</p>
    <?php else: ?>
        <div class="collections-grid">
            <?php foreach ($map as $name => $pages): ?>
                <a class="collection-card" href="<?= url_collection($name) ?>">
                    <span class="collection-card__icon">◆</span>
                    <span class="collection-card__name"><?= e($name) ?></span>
                    <span class="collection-card__count"><?= count($pages) ?> стр.</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</article>
