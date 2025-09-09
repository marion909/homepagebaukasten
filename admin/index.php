<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

// Get some basic stats
$pageCount = $db->fetchOne("SELECT COUNT(*) as count FROM pages")['count'];
$userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE active = 1")['count'];
$commentCount = $db->fetchOne("SELECT COUNT(*) as count FROM blog_comments")['count'];
$pendingComments = $db->fetchOne("SELECT COUNT(*) as count FROM blog_comments WHERE status = 'pending'")['count'];
$recentPages = $db->fetchAll("SELECT title, created_at FROM pages ORDER BY created_at DESC LIMIT 5");

// Get recent comments
$recentComments = $db->fetchAll("
    SELECT bc.author_name, bc.content, bc.created_at, bc.status, bp.title as post_title
    FROM blog_comments bc
    JOIN blog_posts bp ON bc.post_id = bp.id
    ORDER BY bc.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Baukasten CMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
        .header { background: #007cba; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .nav { background: #005a87; padding: 0; }
        .nav ul { list-style: none; margin: 0; padding: 0; display: flex; }
        .nav li { margin: 0; }
        .nav a { display: block; padding: 1rem 1.5rem; color: white; text-decoration: none; }
        .nav a:hover { background: #004666; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 2rem; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h3 { margin-top: 0; color: #333; }
        .stat { font-size: 2rem; font-weight: bold; color: #007cba; }
        .recent-list { list-style: none; padding: 0; }
        .recent-list li { padding: 0.5rem 0; border-bottom: 1px solid #eee; }
        .recent-list li:last-child { border-bottom: none; }
        .user-info { color: white; }
        .logout { background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .logout:hover { background: #c82333; }
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
            <li><a href="media.php">Medien</a></li>
            <li><a href="settings.php">Einstellungen</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <div class="dashboard-grid">
            <div class="card">
                <h3>Seiten</h3>
                <div class="stat"><?= $pageCount ?></div>
                <p>Gesamt erstellte Seiten</p>
            </div>
            
            <div class="card">
                <h3>Benutzer</h3>
                <div class="stat"><?= $userCount ?></div>
                <p>Aktive Benutzer</p>
            </div>
            
            <div class="card">
                <h3>Kommentare</h3>
                <div class="stat"><?= $commentCount ?></div>
                <p>Gesamt Kommentare</p>
                <?php if ($pendingComments > 0): ?>
                    <small style="color: #ffc107; font-weight: bold;">
                        <?= $pendingComments ?> warten auf Freigabe
                    </small>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>Neueste Seiten</h3>
                <ul class="recent-list">
                    <?php foreach ($recentPages as $page): ?>
                        <li>
                            <?= htmlspecialchars($page['title']) ?>
                            <small style="color: #666; float: right;">
                                <?= date('d.m.Y', strtotime($page['created_at'])) ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="card">
                <h3>Neueste Kommentare</h3>
                <?php if (empty($recentComments)): ?>
                    <p style="color: #666; font-style: italic;">Noch keine Kommentare vorhanden</p>
                <?php else: ?>
                    <ul class="recent-list">
                        <?php foreach ($recentComments as $comment): ?>
                            <li>
                                <strong><?= htmlspecialchars($comment['author_name']) ?></strong> zu 
                                "<?= htmlspecialchars($comment['post_title']) ?>"
                                <small style="color: #666; float: right;">
                                    <span class="status-<?= $comment['status'] ?>"><?= $comment['status'] ?></span>
                                    <?= date('d.m.Y', strtotime($comment['created_at'])) ?>
                                </small>
                                <div style="color: #666; font-size: 0.9rem; margin-top: 0.25rem;">
                                    <?= htmlspecialchars(substr($comment['content'], 0, 80)) ?><?= strlen($comment['content']) > 80 ? '...' : '' ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top: 1rem;">
                        <a href="comments.php" style="background: #007cba; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px;">Alle Kommentare verwalten</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3>Schnellaktionen</h3>
                <p><a href="pages.php?action=new">Neue Seite erstellen</a></p>
                <p><a href="media.php">Dateien hochladen</a></p>
                <p><a href="comments.php">Kommentare verwalten</a></p>
                <p><a href="/" target="_blank">Website anzeigen</a></p>
            </div>
        </div>
    </div>
</body>
</html>
