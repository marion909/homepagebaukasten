<?php
require_once "../core/init.php";

$page_slug = $_GET['page'] ?? 'home';

// Check if this is a blog category request
if ($page_slug === 'blog' && isset($_GET['category'])) {
    $category_slug = $_GET['category'];
    $category = BlogCategory::getBySlug($category_slug);
    
    if ($category) {
        $posts = Blog::getByCategory($category_slug, 'published');
        $page_data = [
            'title' => 'Blog - ' . $category['name'],
            'content' => '[blog_list category="' . $category_slug . '"]',
            'meta_description' => $category['description'] ?? 'Blog-Beiträge in der Kategorie ' . $category['name'],
            'meta_keywords' => $category['name'] . ', Blog, Artikel'
        ];
    } else {
        $page_data = [
            'title' => 'Kategorie nicht gefunden',
            'content' => '<h1>404 - Kategorie nicht gefunden</h1><p>Die angeforderte Kategorie existiert nicht.</p><p><a href="/blog">← Zurück zum Blog</a></p>',
            'meta_description' => '',
            'meta_keywords' => ''
        ];
    }
}
// Check if this is a blog tag request
elseif ($page_slug === 'blog' && isset($_GET['tag'])) {
    $tag_slug = $_GET['tag'];
    $tag = BlogTag::getBySlug($tag_slug);
    
    if ($tag) {
        $posts = Blog::getByTag($tag_slug, 'published');
        $page_data = [
            'title' => 'Blog - Tag: ' . $tag['name'],
            'content' => '<h1>Tag: ' . htmlspecialchars($tag['name']) . '</h1>' . 
                        '<div class="blog-list">' . 
                        (empty($posts) ? '<p>Keine Beiträge mit diesem Tag gefunden.</p>' : '') .
                        '</div>',
            'meta_description' => 'Blog-Beiträge mit dem Tag ' . $tag['name'],
            'meta_keywords' => $tag['name'] . ', Blog, Artikel'
        ];
        
        // Add posts manually to content
        if (!empty($posts)) {
            $posts_html = '';
            foreach ($posts as $post) {
                $postCategories = BlogCategory::getByPostId($post['id']);
                $postTags = BlogTag::getByPostId($post['id']);
                
                $categoriesHtml = '';
                if (!empty($postCategories)) {
                    $categoriesHtml = '<span class="categories">';
                    foreach ($postCategories as $cat) {
                        $categoriesHtml .= '<a href="/blog/category/' . htmlspecialchars($cat['slug']) . '" class="category-link">' . htmlspecialchars($cat['name']) . '</a>';
                    }
                    $categoriesHtml .= '</span>';
                }
                
                $tagsHtml = '';
                if (!empty($postTags)) {
                    $tagsHtml = '<div class="blog-tags">';
                    foreach ($postTags as $postTag) {
                        $tagsHtml .= '<a href="/blog/tag/' . htmlspecialchars($postTag['slug']) . '" class="tag-link">#' . htmlspecialchars($postTag['name']) . '</a>';
                    }
                    $tagsHtml .= '</div>';
                }
                
                $posts_html .= '<article class="blog-post-preview">';
                $posts_html .= '<h3><a href="/blog/' . htmlspecialchars($post['slug']) . '">' . htmlspecialchars($post['title']) . '</a></h3>';
                $posts_html .= '<div class="blog-meta"><time>' . date('d.m.Y', strtotime($post['created_at'])) . '</time>' . $categoriesHtml . '</div>';
                if (!empty($post['excerpt'])) {
                    $posts_html .= '<p class="blog-excerpt">' . htmlspecialchars($post['excerpt']) . '</p>';
                }
                $posts_html .= $tagsHtml;
                $posts_html .= '<a href="/blog/' . htmlspecialchars($post['slug']) . '" class="read-more">Weiterlesen →</a>';
                $posts_html .= '</article>';
            }
            
            $page_data['content'] = '<h1>Tag: ' . htmlspecialchars($tag['name']) . '</h1>' . 
                                   '<div class="blog-list">' . $posts_html . '</div>';
        }
    } else {
        $page_data = [
            'title' => 'Tag nicht gefunden',
            'content' => '<h1>404 - Tag nicht gefunden</h1><p>Das angeforderte Tag existiert nicht.</p><p><a href="/blog">← Zurück zum Blog</a></p>',
            'meta_description' => '',
            'meta_keywords' => ''
        ];
    }
}
// Check if this is a blog post request
elseif ($page_slug === 'blog' && isset($_GET['post'])) {
    $post_slug = $_GET['post'];
    $blog_post = Blog::getBySlug($post_slug);
    
    if ($blog_post) {
        // Get categories and tags for this post
        $postCategories = BlogCategory::getByPostId($blog_post['id']);
        $postTags = BlogTag::getByPostId($blog_post['id']);
        
        // Build categories HTML
        $categoriesHtml = '';
        if (!empty($postCategories)) {
            $categoriesHtml = '<div class="post-categories"><strong>Kategorien:</strong> ';
            foreach ($postCategories as $cat) {
                $categoriesHtml .= '<a href="/blog/category/' . htmlspecialchars($cat['slug']) . '" class="category-link">' . htmlspecialchars($cat['name']) . '</a> ';
            }
            $categoriesHtml .= '</div>';
        }
        
        // Build tags HTML
        $tagsHtml = '';
        if (!empty($postTags)) {
            $tagsHtml = '<div class="post-tags"><strong>Tags:</strong> ';
            foreach ($postTags as $tag) {
                $tagsHtml .= '<a href="/blog/tag/' . htmlspecialchars($tag['slug']) . '" class="tag-link">#' . htmlspecialchars($tag['name']) . '</a> ';
            }
            $tagsHtml .= '</div>';
        }
        
        $page_data = [
            'title' => $blog_post['title'],
            'content' => $blog_post['content'] . $categoriesHtml . $tagsHtml . '[blog_comments]',
            'meta_description' => $blog_post['excerpt'],
            'meta_keywords' => implode(', ', array_column($postTags, 'name'))
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
                            <a href="/<?= htmlspecialchars($nav_page['slug']) ?>" 
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
