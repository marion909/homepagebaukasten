<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();

$db = Database::getInstance();
$user = $auth->getCurrentUser();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        switch ($action) {
            case 'approve':
                if ($id) {
                    BlogComment::approve($id);
                    $message = 'Kommentar wurde genehmigt.';
                }
                break;
                
            case 'reject':
                if ($id) {
                    BlogComment::reject($id);
                    $message = 'Kommentar wurde abgelehnt.';
                }
                break;
                
            case 'spam':
                if ($id) {
                    BlogComment::spam($id);
                    $message = 'Kommentar wurde als Spam markiert.';
                }
                break;
                
            case 'delete':
                if ($id) {
                    BlogComment::delete($id);
                    $message = 'Kommentar wurde gelöscht.';
                }
                break;
        }
        
        // Redirect to avoid form resubmission
        if ($message && in_array($action, ['approve', 'reject', 'spam', 'delete'])) {
            header('Location: comments.php?message=' . urlencode($message));
            exit;
        }
    }
}

// Get message from redirect
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Get comments based on status filter
$status_filter = $_GET['status'] ?? 'all';
$comments = [];

if ($status_filter === 'all') {
    $comments = $db->fetchAll("
        SELECT bc.*, bp.title as post_title, bp.slug as post_slug 
        FROM blog_comments bc
        JOIN blog_posts bp ON bc.post_id = bp.id
        ORDER BY bc.created_at DESC
    ");
} else {
    $comments = $db->fetchAll("
        SELECT bc.*, bp.title as post_title, bp.slug as post_slug 
        FROM blog_comments bc
        JOIN blog_posts bp ON bc.post_id = bp.id
        WHERE bc.status = ?
        ORDER BY bc.created_at DESC
    ", [$status_filter]);
}

// Get comment counts
$pending_count = BlogComment::getCount('pending');
$approved_count = BlogComment::getCount('approved');
$rejected_count = BlogComment::getCount('rejected');
$spam_count = BlogComment::getCount('spam');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kommentare - Baukasten CMS</title>
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
        
        .filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; }
        .filter-tab { 
            padding: 0.75rem 1.5rem; 
            background: #e9ecef; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .filter-tab.active { background: #007cba; color: white; }
        .filter-tab:hover { background: #dee2e6; }
        .filter-tab.active:hover { background: #005a87; }
        .badge { 
            background: #6c757d; 
            color: white; 
            padding: 0.25rem 0.5rem; 
            border-radius: 12px; 
            font-size: 0.75rem; 
            font-weight: bold;
        }
        .badge.pending { background: #ffc107; color: #000; }
        .badge.approved { background: #28a745; }
        .badge.rejected { background: #dc3545; }
        .badge.spam { background: #6f42c1; }
        
        .comment-list { list-style: none; padding: 0; }
        .comment-item { 
            border: 1px solid #dee2e6; 
            border-radius: 8px; 
            margin-bottom: 1rem; 
            padding: 1rem;
            background: white;
        }
        .comment-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .comment-meta { color: #666; font-size: 0.9rem; }
        .comment-content { 
            background: #f8f9fa; 
            padding: 1rem; 
            border-radius: 4px; 
            margin: 1rem 0;
            border-left: 4px solid #007cba;
        }
        .comment-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .comment-post { font-size: 0.9rem; color: #666; margin-bottom: 0.5rem; }
        
        .btn { 
            background: #007cba; 
            color: white; 
            padding: 0.5rem 1rem; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block;
            font-size: 0.85rem;
        }
        .btn:hover { background: #005a87; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-warning:hover { background: #e0a800; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        
        .status-badge { 
            padding: 0.25rem 0.5rem; 
            border-radius: 4px; 
            font-size: 0.75rem; 
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-spam { background: #e2d9f3; color: #6f42c1; }
        
        .message { background: #d4edda; color: #155724; padding: 1rem; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 1rem; }
        .user-info { color: white; }
        .logout { background: #dc3545; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 4px; }
        .logout:hover { background: #c82333; }
        
        .empty-state { text-align: center; color: #666; padding: 3rem; }
        
        @media (max-width: 768px) {
            .comment-header { flex-direction: column; align-items: flex-start; }
            .comment-actions { width: 100%; }
            .filter-tabs { flex-wrap: wrap; }
        }
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
            <li><a href="comments.php">Kommentare</a></li>
            <li><a href="seo.php">SEO & Feeds</a></li>
            <li><a href="settings.php">Einstellungen</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="comments.php?status=all" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">
                Alle
                <span class="badge"><?= count($comments) ?></span>
            </a>
            <a href="comments.php?status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">
                Wartend
                <span class="badge pending"><?= $pending_count ?></span>
            </a>
            <a href="comments.php?status=approved" class="filter-tab <?= $status_filter === 'approved' ? 'active' : '' ?>">
                Genehmigt
                <span class="badge approved"><?= $approved_count ?></span>
            </a>
            <a href="comments.php?status=rejected" class="filter-tab <?= $status_filter === 'rejected' ? 'active' : '' ?>">
                Abgelehnt
                <span class="badge rejected"><?= $rejected_count ?></span>
            </a>
            <a href="comments.php?status=spam" class="filter-tab <?= $status_filter === 'spam' ? 'active' : '' ?>">
                Spam
                <span class="badge spam"><?= $spam_count ?></span>
            </a>
        </div>
        
        <div class="card">
            <h2>Kommentare verwalten</h2>
            
            <?php if (empty($comments)): ?>
                <div class="empty-state">
                    <h3>Keine Kommentare gefunden</h3>
                    <p>Es sind noch keine Kommentare vorhanden<?= $status_filter !== 'all' ? ' mit dem Status "' . $status_filter . '"' : '' ?>.</p>
                </div>
            <?php else: ?>
                <ul class="comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <li class="comment-item">
                            <div class="comment-header">
                                <div>
                                    <strong><?= htmlspecialchars($comment['author_name']) ?></strong>
                                    <span class="comment-meta">
                                        (<?= htmlspecialchars($comment['author_email']) ?>) - 
                                        <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                                    </span>
                                    <span class="status-badge status-<?= $comment['status'] ?>">
                                        <?= $comment['status'] ?>
                                    </span>
                                </div>
                                <div class="comment-actions">
                                    <?php if ($comment['status'] !== 'approved'): ?>
                                        <form method="POST" action="comments.php?action=approve&id=<?= $comment['id'] ?>" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                            <button type="submit" class="btn btn-success">Genehmigen</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($comment['status'] !== 'rejected'): ?>
                                        <form method="POST" action="comments.php?action=reject&id=<?= $comment['id'] ?>" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                            <button type="submit" class="btn btn-warning">Ablehnen</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($comment['status'] !== 'spam'): ?>
                                        <form method="POST" action="comments.php?action=spam&id=<?= $comment['id'] ?>" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                            <button type="submit" class="btn btn-secondary">Spam</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" action="comments.php?action=delete&id=<?= $comment['id'] ?>" style="display: inline;" 
                                          onsubmit="return confirm('Kommentar wirklich löschen?')">
                                        <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                                        <button type="submit" class="btn btn-danger">Löschen</button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="comment-post">
                                Beitrag: <a href="/blog/<?= htmlspecialchars($comment['post_slug']) ?>" target="_blank">
                                    <?= htmlspecialchars($comment['post_title']) ?>
                                </a>
                            </div>
                            
                            <div class="comment-content">
                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
