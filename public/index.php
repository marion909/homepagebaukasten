<?php
require_once "../core/init.php";

$page_slug = $_GET['page'] ?? 'home';

// Check if this is a blog post request
if ($page_slug === 'blog' && isset($_GET['post'])) {
    $post_slug = $_GET['post'];
    $blog_post = Blog::getBySlug($post_slug);
    
    if ($blog_post) {
        $page_data = [
            'title' => $blog_post['title'],
            'content' => $blog_post['content'],
            'meta_description' => $blog_post['excerpt'],
            'meta_keywords' => ''
        ];
    } else {
        $page_data = [
            'title' => 'Beitrag nicht gefunden',
            'content' => '<h1>404 - Beitrag nicht gefunden</h1><p>Der angeforderte Blog-Beitrag existiert nicht.</p>',
            'meta_description' => '',
            'meta_keywords' => ''
        ];
    }
} else {
    $page_data = Page::getBySlug($page_slug);
}

// Process shortcodes in content
if (!empty($page_data['content'])) {
    $page_data['content'] = Shortcodes::process($page_data['content']);
}

// Get site settings
$db = Database::getInstance();
$settings = [];
$setting_rows = $db->fetchAll("SELECT setting_key, setting_value FROM settings");
foreach ($setting_rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$site_title = $settings['site_title'] ?? 'Baukasten CMS';
$site_description = $settings['site_description'] ?? '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_data['title'] ?? 'Seite') ?> - <?= htmlspecialchars($site_title) ?></title>
    
    <?php if (!empty($page_data['meta_description'])): ?>
        <meta name="description" content="<?= htmlspecialchars($page_data['meta_description']) ?>">
    <?php endif; ?>
    
    <?php if (!empty($page_data['meta_keywords'])): ?>
        <meta name="keywords" content="<?= htmlspecialchars($page_data['meta_keywords']) ?>">
    <?php endif; ?>
    
    <link rel="stylesheet" href="../themes/default/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><?= htmlspecialchars($site_title) ?></h1>
            <nav>
                <?php
                // Get published pages for navigation
                $nav_pages = Page::getAll('published');
                ?>
                <ul>
                    <?php foreach ($nav_pages as $nav_page): ?>
                        <li>
                            <a href="?page=<?= htmlspecialchars($nav_page['slug']) ?>" 
                               <?= $nav_page['slug'] === $page_slug ? 'class="active"' : '' ?>>
                                <?= htmlspecialchars($nav_page['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <div class="content">
            <?= $page_data['content'] ?? '' ?>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($site_title) ?></p>
        </div>
    </footer>
</body>
</html>
