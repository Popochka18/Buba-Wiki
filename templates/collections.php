<?php /** @var array<string,string[]> $map */ ?>
<article class="article">
    <h1 class="article__title"><?= t('title.collections') ?></h1>
    <div class="article__rule"></div>
    <p class="muted"><?= t('collections.subtitle') ?></p>

    <?php if (empty($map)): ?>
        <p class="muted"><?= t('collections.empty') ?></p>
    <?php else: ?>
        <div class="collections-grid">
            <?php foreach ($map as $name => $pages): ?>
                <a class="collection-card" href="<?= url_collection($name) ?>">
                    <span class="collection-card__icon">◆</span>
                    <span class="collection-card__name"><?= e($name) ?></span>
                    <span class="collection-card__count"><?= e(t('count.pages_short', count($pages))) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</article>
