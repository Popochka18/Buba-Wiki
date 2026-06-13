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
            Летопись мира <strong>Аврории</strong> — фракции и народы, города и пустыни,
            книги и герои. Открой свиток и узнай, что хранят хроники.
        </p>

        <form class="searchbar searchbar--hero" onsubmit="return Avroria.search(event)">
            <input type="search" placeholder="Найти статью в летописи…" autocomplete="off">
            <button type="submit" aria-label="Искать">🔎</button>
            <ul class="searchbar__results" hidden></ul>
        </form>

        <div class="home-hero__stats">
            <a href="<?= url('all') ?>"><b><?= $pageCount ?></b> <span><?= plural_ru($pageCount, 'страница', 'страницы', 'страниц') ?></span></a>
            <span class="home-hero__sep">❖</span>
            <a href="<?= url_collections() ?>"><b><?= count($collections) ?></b> <span><?= plural_ru(count($collections), 'коллекция', 'коллекции', 'коллекций') ?></span></a>
            <span class="home-hero__sep">❖</span>
            <span><b><?= $imgCount ?></b> <span><?= plural_ru($imgCount, 'изображение', 'изображения', 'изображений') ?></span></span>
        </div>
    </section>

    <!-- Избранная статья -->
    <?php if ($featured): ?>
        <section class="home-section">
            <h2 class="home-section__title"><span>✶ Избранная статья</span></h2>
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
                    <span class="btn btn--primary">Читать статью →</span>
                </div>
            </a>
        </section>
    <?php endif; ?>

    <div class="home-columns">
        <!-- Коллекции -->
        <section class="home-section">
            <h2 class="home-section__title"><span>◆ Коллекции</span></h2>
            <?php if ($collections): ?>
                <div class="collections-grid">
                    <?php foreach ($collections as $name => $pages): ?>
                        <a class="collection-card" href="<?= url_collection($name) ?>">
                            <span class="collection-card__icon">◆</span>
                            <span class="collection-card__name"><?= e($name) ?></span>
                            <span class="collection-card__count"><?= count($pages) ?> стр.</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="muted">Коллекции ещё не созданы.</p>
            <?php endif; ?>
        </section>

        <!-- Недавние страницы -->
        <section class="home-section">
            <h2 class="home-section__title"><span>❧ Недавние страницы</span></h2>
            <?php if ($recent): ?>
                <ul class="page-list">
                    <?php foreach ($recent as $p): ?>
                        <li><a href="<?= url_view($p) ?>"><?= e($p) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <a class="home-more" href="<?= url('all') ?>">Все страницы →</a>
            <?php else: ?>
                <p class="muted">Страниц пока нет.</p>
            <?php endif; ?>
        </section>
    </div>

    <!-- Призыв к действию -->
    <section class="home-cta">
        <p>Знаешь то, чего ещё нет в летописи?</p>
        <a class="btn btn--primary" href="<?= url_edit('Новая страница') ?>">✎ Написать новую страницу</a>
    </section>
</div>
