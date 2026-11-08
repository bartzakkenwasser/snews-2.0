<?php
/*------------------------------------------------------------------------------
  sNews Version:	2.0 - Official (Gecorrigeerd voor PHP 8.1+)
  CodeName:     	REVISITED
  Last Update:		2026 Modernization (Gecorrigeerd voor PHP 8.1+)
  Developers: 		Rui Mendes, Stephane Fritsch(Skiane), Nukpana
  Thanks to:		  @RobsWebsites
  Copyright (C):	Solucija.com
  Licence:      	sNews is licensed under a Creative Commons License.
  Revised by:     zakkenwasser
-------------------------------------------------------------------------------- */
<?php
ob_start();
// SECURITY: never show errors to visitors (they reveal file paths, queries and sometimes credentials).
// Errors go to the PHP error log. Set display_errors to '1' only temporarily while debugging.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// snews.php reads config.php, lang/ and addons/ with relative paths, so make sure we run from this folder
chdir(__DIR__);

// Laad het sNews core-bestand veilig
require_once __DIR__ . '/snews.php';

// Page language for the <html lang=""> attribute, taken from the site language setting (EN -> en, NL -> nl)
$page_lang = strtolower((string)s('language'));
if (!preg_match('/^[a-z]{2,3}$/', $page_lang)) {
    $page_lang = 'en';
}

// Sidebar headings: use the language file when it has the text, otherwise the English default
$label = static function (string $key, string $fallback): string {
    $text = l($key);
    return $text !== '' ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : $fallback;
};
?>
<!DOCTYPE html>
<html lang="<?= $page_lang ?>">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Titel + meta tags veilig renderen -->
    <?php title(); ?>

    <meta name="robots" content="index,follow">
    <meta name="author" content="Solucija.com">

    <link rel="stylesheet" href="css/style.css?v=<?= (file_exists(__DIR__ . '/css/style.css') ? (int)filemtime(__DIR__ . '/css/style.css') : 0) ?>">
</head>
<body>
<div id="wrapper">

    <header id="header">
        <div id="top">
            <ul id="topmenu">
                <?php pages(); ?>
            </ul>

            <div id="search">
                <?php searchform(); ?>
            </div>
        </div>

        <div id="logo">
            <h1><?= htmlspecialchars(s('website_title') ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars(s('website_description') ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </header>

    <div id="crumbs">
        <?php breadcrumbs(); ?>
    </div>

    <div id="content">
        <main id="main">
            <?php center(); ?>
        </main>

        <aside id="side">

            <div class="single">
                <h3><?= $label('categories', 'Categories') ?></h3>
                <ul>
                    <?php categories(); ?>
                </ul>

                <h3><?= $label('rss_feeds', 'RSS Feeds') ?></h3>
                <ul>
                    <?php rss_links(); ?>
                </ul>

                <?php extra(); ?>
            </div>

            <div class="single">
                <h3><?= $label('new_articles', 'New Posts') ?></h3>
                <ul>
                    <?php menu_articles(0, 3, 1); ?>
                </ul>

                <h3><?= $label('new_comments', 'New Comments') ?></h3>
                <ul>
                    <?php new_comments(5, 30); ?>
                </ul>

            </div>

        </aside>
    </div>

    <footer id="footer">
        <p>
            This site is powered by
            <a href="https://snewscms.com/" target="_blank" rel="noopener noreferrer">sNews</a>
            |
            <?php login_link(); ?>
        </p>
    </footer>

</div>
</body>
</html>

