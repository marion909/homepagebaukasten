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

// Try to get content block stats (may not exist yet)
try {
    $contentBlockStats = ContentBlock::getStats();
} catch (Exception $e) {
    $contentBlockStats = ['active' => 0, 'total' => 0];
}

// Try to get form count (may not exist yet)
try {
    $formCount = $db->fetchOne("SELECT COUNT(*) as count FROM custom_forms WHERE active = 1")['count'];
} catch (Exception $e) {
    $formCount = 0;
}

$recentPages = $db->fetchAll("SELECT title, created_at FROM pages ORDER BY created_at DESC LIMIT 5");

// Get recent comments
$recentComments = $db->fetchAll("
    SELECT bc.author_name, bc.content, bc.created_at, bc.status, bp.title as post_title
    FROM blog_comments bc
    JOIN blog_posts bp ON bc.post_id = bp.id
    ORDER BY bc.created_at DESC 
    LIMIT 5
");

$pageTitle = "Dashboard";
$currentPage = "dashboard";
include 'header.php';
?>

<div class="dashboard-grid">
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
                <h3>Content-Blöcke</h3>
                <div class="stat"><?= $contentBlockStats['active'] ?></div>
                <p>Aktive Blöcke (<?= $contentBlockStats['total'] ?> gesamt)</p>
            </div>
            
            <div class="card">
                <h3>Formulare</h3>
                <div class="stat"><?= $formCount ?></div>
                <p>Aktive Formulare</p>
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

<?php include 'footer.php'; ?>
