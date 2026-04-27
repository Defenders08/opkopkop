<?php
/**
 * ChubbyCMS - Main Site
 * Displays articles from notes folder
 */
require_once 'includes/config.php';

// Get article from URL parameter
$articleFile = $_GET['article'] ?? '';
$page = $_GET['page'] ?? 'главная';

// Navigation data (stored in config)
$navConfig = $config['nav'] ?? [
    'header' => [],
    'footer' => []
];

// Collections and tabs
$collections = [];
$articles = [];

// Load all articles
$files = glob(NOTES_PATH . '/*.json');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    if ($data) {
        $filename = basename($file, '.json');
        $articles[] = [
            'filename' => $filename,
            'title' => $data['title'] ?? 'без названия',
            'collection' => $data['collection'] ?? '',
            'tab' => $data['tab'] ?? ''
        ];
        
        // Build collections structure
        if (!empty($data['collection'])) {
            $coll = $data['collection'];
            if (!isset($collections[$coll])) {
                $collections[$coll] = [];
            }
            if (!empty($data['tab']) && !in_array($data['tab'], $collections[$coll])) {
                $collections[$coll][] = $data['tab'];
            }
        }
    }
}

// Sort articles by updated time
usort($articles, function($a, $b) use ($files) {
    $timeA = filemtime(NOTES_PATH . '/' . $a['filename'] . '.json');
    $timeB = filemtime(NOTES_PATH . '/' . $b['filename'] . '.json');
    return $timeB - $timeA;
});

// Find current article
$currentArticle = null;
if (!empty($articleFile)) {
    $articleFile = basename($articleFile);
    $filepath = NOTES_PATH . '/' . $articleFile . '.json';
    if (file_exists($filepath)) {
        $currentArticle = json_decode(file_get_contents($filepath), true);
    }
}

// Get recent articles for sidebar
$recentArticles = array_slice($articles, 0, 10);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $currentArticle ? htmlspecialchars($currentArticle['title']) : htmlspecialchars($config['site_title']); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="body">
        <div class="header">
            <div class="left">
                <a href="index.php" data-page="главная" class="site-title"><?php echo htmlspecialchars($config['site_title']); ?></a>
            </div>
            <div class="right" id="header-nav">
                <?php foreach ($navConfig['header'] as $item): ?>
                    <?php if ($item['type'] === 'link'): ?>
                        <a href="<?php echo htmlspecialchars($item['url']); ?>"><?php echo htmlspecialchars($item['label']); ?></a>
                    <?php elseif ($item['type'] === 'collection'): ?>
                        <span style="color: var(--text-dim);"><?php echo htmlspecialchars($item['label']); ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
                <a href="admin/login.php">вход</a>
            </div>
        </div>
        
        <!-- Collection tabs bar -->
        <div class="tabs-bar <?php echo empty($collections) ? 'hidden' : ''; ?>" id="tabs-bar">
            <?php foreach ($collections as $collName => $tabs): ?>
                <?php foreach ($tabs as $tabName): ?>
                    <button class="tab-btn" data-collection="<?php echo htmlspecialchars($collName); ?>" data-tab="<?php echo htmlspecialchars($tabName); ?>">
                        <?php echo htmlspecialchars($tabName); ?>
                    </button>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>

        <div class="content">
            <div class="left">
                <div class="markdown" id="article-content">
                    <?php if ($currentArticle): ?>
                        <h1><?php echo htmlspecialchars($currentArticle['title']); ?></h1>
                        <?php renderBlocks($currentArticle['blocks'] ?? []); ?>
                    <?php else: ?>
                        <h1><?php echo htmlspecialchars($config['site_title']); ?></h1>
                        <p>добро пожаловать. выбери статью из списка справа или используй навигацию.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="right">
                <div class="name">Последнее</div>
                <div class="list" id="articles-list">
                    <?php foreach ($recentArticles as $article): ?>
                        <a href="?article=<?php echo urlencode($article['filename']); ?>" 
                           class="<?php echo $article['filename'] === $articleFile ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($article['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="left" id="footer-nav">
                <?php foreach ($navConfig['footer'] as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" class="bracketed"><?php echo htmlspecialchars($item['label']); ?></a>
                <?php endforeach; ?>
            </div>
            <div class="right">design by defenders08</div>
        </div>
    </div>
    
    <!-- Theme toggle -->
    <button class="theme-toggle" id="theme-toggle" title="Переключить тему">◑</button>
    
    <!-- Login Modal (triggered by F2) -->
    <div id="login-modal" class="login-modal hidden">
        <div class="login-box">
            <div class="login-title">// ChubbyCMS</div>
            <div class="login-subtitle">нажми F2 для входа</div>
            <button class="cms-btn cms-btn-save login-btn" onclick="window.location.href='admin/login.php'">войти в CMS</button>
        </div>
    </div>
    
    <script>
        // Theme toggle
        const themeToggle = document.getElementById('theme-toggle');
        const body = document.body;
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            body.classList.add('light');
        }
        
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('light');
            localStorage.setItem('theme', body.classList.contains('light') ? 'light' : 'dark');
        });
        
        // F2 to open login modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'F2') {
                e.preventDefault();
                const modal = document.getElementById('login-modal');
                modal.classList.toggle('hidden');
            }
        });
        
        // Close modal on overlay click
        document.getElementById('login-modal').addEventListener('click', (e) => {
            if (e.target.id === 'login-modal') {
                e.target.classList.add('hidden');
            }
        });
        
        // Tab filtering
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const collection = btn.dataset.collection;
                const tab = btn.dataset.tab;
                
                // Filter articles by collection and tab
                // This is a simple implementation - you can enhance it
                console.log('Filter by:', collection, tab);
            });
        });
    </script>
</body>
</html>

<?php
// Render blocks function
function renderBlocks($blocks, $isNested = false) {
    foreach ($blocks as $block) {
        $type = $block['type'] ?? '';
        $content = $block['content'] ?? '';
        
        switch ($type) {
            case 'h3':
                echo '<h3>' . htmlspecialchars($content) . '</h3>';
                break;
            case 'text':
                echo '<p>' . nl2br(htmlspecialchars($content)) . '</p>';
                break;
            case 'tab':
                echo '<div class="tab">';
                if (!empty($block['blocks'])) {
                    renderBlocks($block['blocks'], true);
                } else {
                    echo htmlspecialchars($content);
                }
                echo '</div>';
                break;
            case 'ul':
                echo '<ul>';
                if (is_array($content)) {
                    foreach ($content as $item) {
                        echo '<li>' . htmlspecialchars($item) . '</li>';
                    }
                }
                echo '</ul>';
                break;
            case 'img':
                $imgSrc = htmlspecialchars($content);
                echo '<img src="' . $imgSrc . '" alt="">';
                break;
            case 'img_little':
                $imgSrc = htmlspecialchars($block['image'] ?? '');
                $textContent = $block['text'] ?? '';
                echo '<div class="tab"><div class="img-little-wrap">';
                if (!empty($imgSrc)) {
                    echo '<img src="' . $imgSrc . '" alt="">';
                }
                echo '<div>';
                if (!empty($block['blocks'])) {
                    renderBlocks($block['blocks'], true);
                } else {
                    echo nl2br(htmlspecialchars($textContent));
                }
                echo '</div></div></div>';
                break;
            case 'hr':
                echo '<hr>';
                break;
            case 'link':
                $url = htmlspecialchars($block['url'] ?? '#');
                $label = htmlspecialchars($block['label'] ?? $content);
                echo '<a href="' . $url . '">' . $label . '</a>';
                break;
            case 'group':
                echo '<div class="group">';
                if (!empty($block['blocks'])) {
                    renderBlocks($block['blocks'], true);
                }
                echo '</div>';
                break;
        }
        
        if (!$isNested) {
            echo ''; // Spacer between blocks
        }
    }
}
?>
