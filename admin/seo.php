<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin'); // Nur Admins können SEO-Einstellungen verwalten

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

$pageTitle = "SEO & Feeds";
$currentPage = "seo";
include 'header.php';
?>

<style>
    .stat-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
        gap: 1rem; 
        margin: 1rem 0; 
    }
    .stat-card { 
        background: #f8f9fa; 
        padding: 1rem; 
        border-radius: 6px; 
        text-align: center; 
        border: 1px solid #e9ecef;
    }
    .stat-number { 
        font-size: 2rem; 
        font-weight: bold; 
        color: #007cba; 
        display: block;
        margin-bottom: 0.25rem;
    }
    .stat-label { 
        color: #666; 
        font-size: 0.9rem; 
    }
    .status { 
        padding: 0.25rem 0.75rem; 
        border-radius: 15px; 
        font-size: 0.85rem; 
        font-weight: bold; 
        display: inline-block;
    }
    .status-exists { 
        background: #d4edda; 
        color: #155724; 
    }
    .status-missing { 
        background: #f8d7da; 
        color: #721c24; 
    }
    .checkbox-group { 
        display: flex; 
        gap: 1rem; 
        align-items: center; 
        margin: 1rem 0;
    }
    .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
    }
    .file-info { 
        background: #e9ecef; 
        padding: 0.75rem; 
        border-radius: 4px; 
        margin: 0.5rem 0; 
        font-family: monospace; 
        font-size: 0.9rem; 
        border: 1px solid #ced4da;
    }
    .urls { 
        background: #f8f9fa; 
        padding: 1rem; 
        border-radius: 4px; 
        margin: 1rem 0; 
        border: 1px solid #e9ecef;
    }
    .urls a { 
        color: #007cba; 
        text-decoration: none; 
        display: block;
        margin: 0.25rem 0;
    }
    .urls a:hover { 
        text-decoration: underline; 
    }
    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }
    @media (max-width: 768px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php if ($message): ?>
    <div class="message"><?= $message ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?= $error ?></div>
<?php endif; ?>

<div class="card">
    <h2>📊 Inhalts-Übersicht</h2>
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

<div class="grid-2">
    <!-- Sitemap Management -->
    <div class="card">
        <h3>🗺️ XML Sitemap</h3>
        
        <div style="margin-bottom: 1rem;">
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
            <button type="submit" class="btn btn-success">Sitemap generieren</button>
            <a href="/sitemap.xml.php" class="btn btn-secondary" target="_blank">Vorschau anzeigen</a>
        </form>
    </div>
    
    <!-- RSS Feed Management -->
    <div class="card">
        <h3>📡 RSS Feed</h3>
        
        <div style="margin-bottom: 1rem;">
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
            <button type="submit" class="btn btn-success">RSS Feed generieren</button>
            <a href="/rss.xml.php" class="btn btn-secondary" target="_blank">Vorschau anzeigen</a>
        </form>
    </div>
</div>

<!-- SEO Tips -->
<div class="card">
    <h3>💡 SEO Tipps</h3>
    <div class="grid-2">
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

<?php include 'footer.php'; ?>