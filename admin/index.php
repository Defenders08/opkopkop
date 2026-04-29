<?php
/**
 * ChubbyCMS - Modern Admin Dashboard
 */
require_once '../includes/config.php';
require_once '../core/Auth.php';
require_once '../core/Content.php';

use Core\Auth;
use Core\Content;

Auth::initSession();
Auth::requireLogin();

$contentEngine = new Content(NOTES_PATH);
$articles = $contentEngine->getArticles();
$csrfToken = Auth::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChubbyCMS - Dashboard</title>
    <!-- Tailwind CSS CDN for modern minimal look without installation -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <nav class="bg-white border-b border-gray-200 py-4 px-6 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-2">
            <span class="text-xl font-bold tracking-tight text-indigo-600">// ChubbyCMS</span>
            <span class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-500 font-mono">v2.0-stable</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="../index.php" target="_blank" class="text-sm text-gray-600 hover:text-indigo-600 transition-colors">Просмотр сайта</a>
            <a href="logout.php" class="text-sm font-semibold text-red-500 hover:text-red-700 transition-colors">Выйти</a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto p-6 lg:p-10">
        <header class="mb-10 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Панель управления</h1>
                <p class="text-gray-500 mt-2">Управление вашими статьями и контентом.</p>
            </div>
            <a href="editor.php" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-semibold shadow-sm hover:bg-indigo-700 transition-all focus:ring-4 focus:ring-indigo-100">
                + Новая статья
            </a>
        </header>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2 shadow-sm animate-pulse">
                <span>✓</span> Статья успешно удалена.
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Заголовок</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Категория</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Дата изменения</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($articles)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 italic">
                                Статей пока нет. Создайте свою первую статью!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($articles as $article): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($article['title'] ?? 'Без названия'); ?></div>
                                    <div class="text-xs text-gray-400 mt-0.5 font-mono"><?php echo htmlspecialchars($article['filename']); ?>.md</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                        <?php echo htmlspecialchars($article['path'] ?: 'Корень'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?php echo date('d.m.Y H:i', $article['updated']); ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a href="editor.php?path=<?php echo urlencode($article['path']); ?>&file=<?php echo urlencode($article['filename']); ?>"
                                           class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
                                            Изменить
                                        </a>
                                        <button onclick="confirmDelete('<?php echo addslashes($article['path']); ?>', '<?php echo addslashes($article['filename']); ?>', '<?php echo htmlspecialchars(addslashes($article['title'])); ?>')"
                                                class="text-red-500 hover:text-red-700 text-sm font-semibold">
                                            Удалить
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Всего статей</h3>
                <p class="text-3xl font-bold text-gray-900"><?php echo count($articles); ?></p>
            </div>
            <a href="media.php" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:border-indigo-300 transition-all group">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Медиафайлы</h3>
                <p class="text-indigo-600 font-semibold flex items-center gap-1">Управление →</p>
            </a>
            <a href="settings.php" class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:border-indigo-300 transition-all group">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Настройки</h3>
                <p class="text-indigo-600 font-semibold flex items-center gap-1">Конфигурация →</p>
            </a>
        </div>
    </main>

    <!-- Hidden form for POST deletion -->
    <form id="delete-form" action="delete.php" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
        <input type="hidden" name="path" id="delete-path">
        <input type="hidden" name="filename" id="delete-filename">
    </form>

    <script>
        function confirmDelete(path, filename, title) {
            if (confirm(`Вы действительно хотите удалить статью "${title}"?\nЭто действие нельзя будет отменить.`)) {
                document.getElementById('delete-path').value = path;
                document.getElementById('delete-filename').value = filename;
                document.getElementById('delete-form').submit();
            }
        }
    </script>
</body>
</html>
