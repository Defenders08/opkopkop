<?php
/**
 * ChubbyCMS - Frontend Router
 */
require_once 'includes/config.php';
require_once 'core/Content.php';
require_once 'core/Renderer.php';

use Core\Content;
use Core\Renderer;

$contentEngine = new Content(NOTES_PATH);
$articles = $contentEngine->getArticles();
$categories = $contentEngine->getCategories();

// Route
$requestArticle = $_GET['article'] ?? '';
$currentCategory = $_GET['category'] ?? '';
$currentArticle = null;

if ($requestArticle) {
    foreach ($articles as $art) {
        if ($art['filename'] === $requestArticle || ($art['path'] . '/' . $art['filename'] === $requestArticle)) {
            $currentArticle = $art;
            break;
        }
    }
}

// Filter articles by category if requested
if ($currentCategory) {
    $articles = array_filter($articles, function($art) use ($currentCategory) {
        return strpos($art['path'], $currentCategory) === 0;
    });
}

// Metadata
$siteTitle = $config['site_title'] ?? 'ChubbyCMS';
$pageTitle = $currentArticle ? $currentArticle['title'] : $siteTitle;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="body">
        <div class="header">
            <div class="left">
                <a href="index.php" class="site-title"><?php echo htmlspecialchars($siteTitle); ?></a>
            </div>
            <div class="right">
                <?php foreach ($categories as $cat => $subs): ?>
                    <a href="?category=<?php echo urlencode($cat); ?>"><?php echo htmlspecialchars($cat); ?></a>
                <?php endforeach; ?>
                <a href="admin/login.php">вход</a>
            </div>
        </div>

        <div class="content">
            <div class="left">
                <?php if ($currentCategory && !$currentArticle): ?>
                    <h1>Категория: <?php echo htmlspecialchars($currentCategory); ?></h1>
                    <div class="tabs-bar">
                        <?php
                        $parts = explode('/', $currentCategory);
                        $mainCat = $parts[0];
                        if (isset($categories[$mainCat])):
                            foreach ($categories[$mainCat] as $sub): ?>
                                <a href="?category=<?php echo urlencode($mainCat . '/' . $sub); ?>" class="tab-btn <?php echo $currentCategory === ($mainCat . '/' . $sub) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($sub); ?>
                                </a>
                            <?php endforeach;
                        endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($currentArticle): ?>
                    <h1><?php echo htmlspecialchars($currentArticle['title']); ?></h1>
                    <?php echo Renderer::render($currentArticle['blocks'] ?? []); ?>
                <?php else: ?>
                    <?php if (!$currentCategory): ?>
                        <h1>Добро пожаловать</h1>
                        <p>Выберите статью из списка или воспользуйтесь навигацией.</p>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach ($articles as $art): ?>
                                <a href="?article=<?php echo urlencode($art['path'] ? $art['path'] . '/' . $art['filename'] : $art['filename']); ?>">
                                    <h3><?php echo htmlspecialchars($art['title']); ?></h3>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="right">
                <div class="name">Последние статьи</div>
                <div class="list">
                    <?php foreach (array_slice($articles, 0, 10) as $art): ?>
                        <a href="?article=<?php echo urlencode($art['path'] ? $art['path'] . '/' . $art['filename'] : $art['filename']); ?>">
                            <?php echo htmlspecialchars($art['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="left">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteTitle); ?>
            </div>
            <div class="right">design by defenders08</div>
        </div>
    </div>
    
    <button class="theme-toggle" id="theme-toggle">◑</button>

    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;
        
        if (localStorage.getItem('theme') === 'light') body.classList.add('light');
        
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('light');
            localStorage.setItem('theme', body.classList.contains('light') ? 'light' : 'dark');
        });

        // F2 for quick login
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') window.location.href = 'admin/login.php';
        });
    </script>
</body>
</html>
