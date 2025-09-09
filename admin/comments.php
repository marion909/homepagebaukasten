<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();
$auth->requireAnyRole(['admin', 'moderator']); // Nur Admins und Moderatoren können Kommentare verwalten

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

$pageTitle = "Kommentare";
$currentPage = "comments";
include 'header.php';
?>

<style>
    .filter-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        background: white;
        padding: 0.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .filter-tab {
        padding: 0.75rem 1.5rem;
        background: #f8f9fa;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        color: #666;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .filter-tab:hover {
        background: #e9ecef;
        color: #333;
    }
    .filter-tab.active {
        background: #007cba;
        color: white;
    }
    .badge {
        background: rgba(0,0,0,0.1);
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        min-width: 20px;
        text-align: center;
    }
    .filter-tab.active .badge {
        background: rgba(255,255,255,0.2);
    }
    .comment-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .comment-item {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 1rem;
        padding: 1rem;
        background: #fafafa;
    }
    .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    .comment-meta {
        color: #666;
        font-size: 0.9rem;
    }
    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    .status-pending {
        background: #fff3cd;
        color: #856404;
    }
    .status-approved {
        background: #d4edda;
        color: #155724;
    }
    .status-rejected {
        background: #f8d7da;
        color: #721c24;
    }
    .status-spam {
        background: #f8d7da;
        color: #721c24;
    }
    .comment-actions {
        display: flex;
        gap: 0.5rem;
    }
    .comment-content {
        background: white;
        padding: 1rem;
        border-radius: 4px;
        margin: 0.5rem 0;
        border-left: 4px solid #007cba;
    }
    .post-link {
        color: #007cba;
        text-decoration: none;
        font-size: 0.9rem;
    }
    .post-link:hover {
        text-decoration: underline;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #666;
    }
    .empty-state h3 {
        color: #333;
        margin-bottom: 0.5rem;
    }
</style>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="comments.php?status=all" class="filter-tab <?= $status_filter === 'all' ? 'active' : '' ?>">
        Alle <span class="badge"><?= count($comments) ?></span>
    </a>
    <a href="comments.php?status=pending" class="filter-tab <?= $status_filter === 'pending' ? 'active' : '' ?>">
        Wartend <span class="badge pending"><?= $pending_count ?></span>
    </a>
    <a href="comments.php?status=approved" class="filter-tab <?= $status_filter === 'approved' ? 'active' : '' ?>">
        Genehmigt <span class="badge approved"><?= $approved_count ?></span>
    </a>
    <a href="comments.php?status=rejected" class="filter-tab <?= $status_filter === 'rejected' ? 'active' : '' ?>">
        Abgelehnt <span class="badge rejected"><?= $rejected_count ?></span>
    </a>
    <a href="comments.php?status=spam" class="filter-tab <?= $status_filter === 'spam' ? 'active' : '' ?>">
        Spam <span class="badge spam"><?= $spam_count ?></span>
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

<?php include 'footer.php'; ?>
