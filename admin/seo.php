<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$message = '';
$error = '';

// Handle actions
$action = $_GET['action'] ?? 'overview';

if ($_POST && $action === 'generate') {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        try {
            // Generate sitemap
            if (isset($_POST['generate_sitemap'])) {
                $sitemapPath = Sitemap::saveToFile();
                if ($sitemapPath) {
                    $message .= 'Sitemap erfolgreich generiert: ' . basename($sitemapPath) . '<br>';
                } else {
                    $error .= 'Fehler beim Generieren der Sitemap.<br>';
                }
            }
            
            // Generate RSS feed
            if (isset($_POST['generate_rss'])) {
                $rssPath = RSS::saveToFile();
                if ($rssPath) {
                    $message .= 'RSS Feed erfolgreich generiert: ' . basename($rssPath) . '<br>';
                } else {
                    $error .= 'Fehler beim Generieren des RSS Feeds.<br>';
                }
            }
        } catch (Exception $e) {
            $error = 'Fehler: ' . $e->getMessage();
        }
    }
}

// Get statistics
$sitemapStats = Sitemap::getStats();
$rssStats = RSS::getStats();

// Check if files exist
$sitemapExists = file_exists($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml');
$rssExists = file_exists($_SERVER['DOCUMENT_ROOT'] . '/rss.xml');

// Get file dates
$sitemapDate = $sitemapExists ? filemtime($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml') : false;
$rssDate = $rssExists ? filemtime($_SERVER['DOCUMENT_ROOT'] . '/rss.xml') : false;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO & Feeds - Baukasten CMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: #007cba; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav { background: #005a87; padding: 0; }
        .nav ul { list-style: none; margin: 0; padding: 0; display: flex; }
        .nav li { margin: 0; }
        .nav a { display: block; padding: 1rem 1.5rem; color: white; text-decoration: none; }
        .nav a:hover, .nav a.active { background: #004666; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 1rem 0; }
        .stat-card { background: #f8f9fa; padding: 1rem; border-radius: 6px; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #007cba; }
        .stat-label { color: #666; font-size: 0.9rem; }
        .btn { background: #007cba; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 0.5rem; }
        .btn:hover { background: #005a87; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .message { background: #d4edda; color: #155724; padding: 1rem; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 1rem; }
        .user-info { color: white; }
        .logout { background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .logout:hover { background: #c82333; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .page-header h2 { margin: 0; }
        .status { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem; font-weight: bold; }
        .status-exists { background: #d4edda; color: #155724; }
        .status-missing { background: #f8d7da; color: #721c24; }
        .form-group { margin-bottom: 1rem; }
        .checkbox-group { display: flex; gap: 1rem; align-items: center; }
        .checkbox-group input[type="checkbox"] { margin-right: 0.5rem; }
        .file-info { background: #e9ecef; padding: 0.75rem; border-radius: 4px; margin: 0.5rem 0; font-family: monospace; font-size: 0.9rem; }
        .urls { background: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0; }
        .urls a { color: #007cba; text-decoration: none; }
        .urls a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Baukasten CMS</h1>
        <div class="user-info">
            Willkommen, <?= htmlspecialchars($user['username']) ?> 
            <a href="logout.php" class="logout">Abmelden</a>
        </div>
    </div>
    
    <nav class="nav">
        <ul>
            <li><a href="index.php">Dashboard</a></li>
            <li><a href="pages.php">Seiten</a></li>
            <li><a href="blog.php">Blog</a></li>
            <li><a href="media.php">Medien</a></li>
            <li><a href="comments.php">Kommentare</a></li>
            <li><a href="seo.php" class="active">SEO & Feeds</a></li>
            <li><a href="settings.php">Einstellungen</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>SEO & Feeds Verwaltung</h2>
        </div>
        
        <!-- Statistics Overview -->
        <div class="card">
            <h3>📊 Inhalts-Übersicht</h3>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $sitemapStats['total_urls'] ?></div>
                    <div class="stat-label">Gesamt URLs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $sitemapStats['pages'] ?></div>
                    <div class="stat-label">Seiten</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $sitemapStats['blog_posts'] ?></div>
                    <div class="stat-label">Blog Posts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $sitemapStats['categories'] ?></div>
                    <div class="stat-label">Kategorien</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $sitemapStats['tags'] ?></div>
                    <div class="stat-label">Tags</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $rssStats['posts_this_month'] ?></div>
                    <div class="stat-label">Posts diesen Monat</div>
                </div>
            </div>
        </div>
        
        <div class="grid">
            <!-- Sitemap Management -->
            <div class="card">
                <h3>🗺️ XML Sitemap</h3>
                
                <div>
                    <strong>Status:</strong> 
                    <span class="status <?= $sitemapExists ? 'status-exists' : 'status-missing' ?>">
                        <?= $sitemapExists ? 'Vorhanden' : 'Nicht vorhanden' ?>
                    </span>
                </div>
                
                <?php if ($sitemapExists && $sitemapDate): ?>
                    <div class="file-info">
                        Letzte Generierung: <?= date('d.m.Y H:i:s', $sitemapDate) ?>
                    </div>
                <?php endif; ?>
                
                <div class="urls">
                    <strong>URLs:</strong><br>
                    <a href="/sitemap.xml.php" target="_blank">🔗 Dynamische Sitemap</a><br>
                    <?php if ($sitemapExists): ?>
                        <a href="/sitemap.xml" target="_blank">📄 Statische Sitemap</a>
                    <?php endif; ?>
                </div>
                
                <p>Die Sitemap hilft Suchmaschinen beim Indexieren Ihrer Website.</p>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="generate">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="generate_sitemap" checked>
                            Statische sitemap.xml generieren
                        </label>
                    </div>
                    <br>
                    <button type="submit" class="btn btn-success">Sitemap generieren</button>
                    <a href="/sitemap.xml.php" class="btn btn-secondary" target="_blank">Vorschau anzeigen</a>
                </form>
            </div>
            
            <!-- RSS Feed Management -->
            <div class="card">
                <h3>📡 RSS Feed</h3>
                
                <div>
                    <strong>Status:</strong> 
                    <span class="status <?= $rssExists ? 'status-exists' : 'status-missing' ?>">
                        <?= $rssExists ? 'Vorhanden' : 'Nicht vorhanden' ?>
                    </span>
                </div>
                
                <?php if ($rssExists && $rssDate): ?>
                    <div class="file-info">
                        Letzte Generierung: <?= date('d.m.Y H:i:s', $rssDate) ?>
                    </div>
                <?php endif; ?>
                
                <div class="urls">
                    <strong>URLs:</strong><br>
                    <a href="/rss.xml.php" target="_blank">🔗 Dynamischer RSS Feed</a><br>
                    <?php if ($rssExists): ?>
                        <a href="/rss.xml" target="_blank">📄 Statischer RSS Feed</a>
                    <?php endif; ?>
                </div>
                
                <p>Der RSS Feed ermöglicht es Lesern, Ihre neuesten Blog-Posts zu abonnieren.</p>
                
                <div style="margin: 1rem 0;">
                    <strong>Feed-Statistiken:</strong>
                    <ul>
                        <li>Veröffentlichte Posts: <?= $rssStats['total_posts'] ?></li>
                        <li>Neuester Post: <?= $rssStats['latest_post'] ? date('d.m.Y', strtotime($rssStats['latest_post'])) : 'Keine Posts' ?></li>
                    </ul>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="generate">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="generate_rss" checked>
                            Statische rss.xml generieren
                        </label>
                    </div>
                    <br>
                    <button type="submit" class="btn btn-success">RSS Feed generieren</button>
                    <a href="/rss.xml.php" class="btn btn-secondary" target="_blank">Vorschau anzeigen</a>
                </form>
            </div>
        </div>
        
        <!-- SEO Tips -->
        <div class="card">
            <h3>💡 SEO Tipps</h3>
            <div class="grid">
                <div>
                    <h4>Sitemap</h4>
                    <ul>
                        <li>Melden Sie Ihre Sitemap bei Google Search Console an</li>
                        <li>URL: <code><?= SITE_URL ?>/sitemap.xml.php</code></li>
                        <li>Generieren Sie die Sitemap nach größeren Änderungen neu</li>
                        <li>Die dynamische Version ist immer aktuell</li>
                    </ul>
                </div>
                <div>
                    <h4>RSS Feed</h4>
                    <ul>
                        <li>Fügen Sie einen RSS-Link in Ihr Theme ein</li>
                        <li>URL: <code><?= SITE_URL ?>/rss.xml.php</code></li>
                        <li>RSS hilft bei der Content-Distribution</li>
                        <li>Automatische Updates für Abonnenten</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Integration Code -->
        <div class="card">
            <h3>🔧 Integration</h3>
            <p>Fügen Sie diese Meta-Tags in den &lt;head&gt; Bereich Ihres Themes ein:</p>
            <div class="file-info">
&lt;!-- SEO Meta Tags --&gt;<br>
&lt;link rel="sitemap" type="application/xml" href="/sitemap.xml.php"&gt;<br>
&lt;link rel="alternate" type="application/rss+xml" title="RSS Feed" href="/rss.xml.php"&gt;<br>
&lt;meta name="robots" content="index, follow"&gt;
            </div>
        </div>
    </div>
</body>
</html>
