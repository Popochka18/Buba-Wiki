<?php /** @var string $pageTitle, $content; @var bool $isEditor */ ?>
<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;900&family=Cinzel+Decorative:wght@700;900&family=EB+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
</head>
<body class="<?= $isEditor ? 'is-editor' : '' ?>">
<div class="page-frame">
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="<?= url('') ?>">
                <span class="brand__sigil">⚜</span>
                <span class="brand__text">
                    <span class="brand__name"><?= e(SITE_NAME) ?></span>
                    <span class="brand__tagline"><?= e(SITE_TAGLINE) ?></span>
                </span>
            </a>

            <form class="searchbar" onsubmit="return Avroria.search(event)">
                <input type="search" id="site-search" placeholder="Поиск по летописи…" autocomplete="off">
                <button type="submit" aria-label="Искать">🔎</button>
                <ul class="searchbar__results" id="search-results" hidden></ul>
            </form>

            <nav class="site-nav">
                <a href="<?= url('') ?>">Главная</a>
                <a href="<?= url_collections() ?>">Коллекции</a>
                <a href="<?= url('all') ?>">Все страницы</a>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <span class="site-footer__rule"></span>
        <p><?= e(SITE_NAME) ?> · собрано хранителями знаний · <?= date('Y') ?></p>
    </footer>
</div>
<script>window.AVRORIA_BASE = <?= json_encode(BASE) ?>;</script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if ($isEditor): ?>
    <script src="<?= asset('js/markdown.js') ?>"></script>
    <script src="<?= asset('js/image-import.js') ?>"></script>
    <script src="<?= asset('js/infobox-editor.js') ?>"></script>
    <script src="<?= asset('js/editor.js') ?>"></script>
<?php endif; ?>
</body>
</html>
