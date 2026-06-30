<?php
/**
 * @var Page|null $featured
 * @var array<string,string[]> $collections
 * @var string[] $recent
 * @var int $pageCount
 * @var int $imgCount
 */
?>
<div class="home">

    <!-- Герой -->
    <section class="home-hero">
        <div class="home-hero__sigil">⚜</div>
        <h1 class="home-hero__title"><?= e(SITE_NAME) ?></h1>
        <p class="home-hero__tagline">
            <?= t('home.tagline') ?>
        </p>

        <form class="searchbar searchbar--hero" onsubmit="return Avroria.search(event)">
            <input type="search" placeholder="<?= e(t('home.search')) ?>" autocomplete="off">
            <button type="submit" aria-label="<?= e(t('search.submit')) ?>">🔎</button>
            <ul class="searchbar__results" hidden></ul>
        </form>

        <div class="home-hero__stats">
            <a href="<?= url('all') ?>"><b><?= $pageCount ?></b> <span><?= e(I18n::plural('pages', $pageCount)) ?></span></a>
            <span class="home-hero__sep">❖</span>
            <a href="<?= url_collections() ?>"><b><?= count($collections) ?></b> <span><?= e(I18n::plural('collections', count($collections))) ?></span></a>
            <span class="home-hero__sep">❖</span>
            <span><b><?= $imgCount ?></b> <span><?= e(I18n::plural('images', $imgCount)) ?></span></span>
        </div>
    </section>

    <!-- Избранная статья -->
    <?php if ($featured): ?>
        <section class="home-section">
            <h2 class="home-section__title"><span><?= t('home.featured') ?></span></h2>
            <a class="featured" href="<?= url_view($featured->name) ?>">
                <div class="featured__media">
                    <?php $img = $featured->image(); ?>
                    <?php if ($img && is_file(IMAGES_DIR . '/' . $img)): ?>
                        <img src="<?= image_url($img) ?>" alt="<?= e($featured->title()) ?>">
                    <?php else: ?>
                        <span class="featured__sigil">⚜</span>
                    <?php endif; ?>
                </div>
                <div class="featured__body">
                    <h3 class="featured__title"><?= e($featured->title()) ?></h3>
                    <?php $cols = $featured->collections(); if ($cols): ?>
                        <div class="featured__cols">
                            <?php foreach ($cols as $c): ?><span class="chip">◆ <?= e($c) ?></span><?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <p class="featured__excerpt"><?= e($featured->excerpt(260)) ?></p>
                    <span class="btn btn--primary"><?= t('home.read') ?></span>
                </div>
            </a>
        </section>
    <?php endif; ?>

    <div class="home-columns">
        <!-- Коллекции -->
        <section class="home-section">
            <h2 class="home-section__title"><span><?= t('home.collections') ?></span></h2>
            <?php if ($collections): ?>
                <div class="collections-grid">
                    <?php foreach ($collections as $name => $pages): ?>
                        <a class="collection-card" href="<?= url_collection($name) ?>">
                            <span class="collection-card__icon">◆</span>
                            <span class="collection-card__name"><?= e($name) ?></span>
                            <span class="collection-card__count"><?= e(t('count.pages_short', count($pages))) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted"><?= t('home.collections_empty') ?></p>
            <?php endif; ?>
        </section>

        <!-- Недавние страницы -->
        <section class="home-section">
            <h2 class="home-section__title"><span><?= t('home.recent') ?></span></h2>
            <?php if ($recent): ?>
                <ul class="page-list">
                    <?php foreach ($recent as $p): ?>
                        <li><a href="<?= url_view($p) ?>"><?= e($p) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <a class="home-more" href="<?= url('all') ?>"><?= t('home.more') ?></a>
            <?php else: ?>
                <p class="muted"><?= t('home.recent_empty') ?></p>
            <?php endif; ?>
        </section>
    </div>

    <!-- Призыв к действию -->
    <section class="home-cta">
        <p><?= t('home.cta') ?></p>
        <a class="btn btn--primary" href="<?= url_edit(t('page.new_name')) ?>"><?= t('home.cta_button') ?></a>
    </section>
</div>
