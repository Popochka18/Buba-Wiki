<?php /** @var string[] $names */ ?>
<article class="article">
    <div class="article__head">
        <h1 class="article__title"><?= t('title.all') ?></h1>
        <a class="btn btn--primary" href="<?= url_edit(t('page.new_name')) ?>"><?= t('all.new') ?></a>
    </div>
    <div class="article__rule"></div>

    <?php if (empty($names)): ?>
        <p class="muted"><?= t('all.empty') ?></p>
    <?php else: ?>
        <ul class="page-list page-list--columns">
            <?php foreach ($names as $n): ?>
                <li><a href="<?= url_view($n) ?>"><?= e($n) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
