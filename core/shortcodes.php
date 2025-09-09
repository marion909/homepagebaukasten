<?php
class Shortcodes {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function process($content) {
        if (!self::$db) self::init();
        
        // Process shortcodes
        $content = preg_replace_callback('/\[contact_form\]/', [self::class, 'contactForm'], $content);
        $content = preg_replace_callback('/\[blog_list(?:\s+limit="(\d+)")?(?:\s+category="([^"]+)")?\]/', [self::class, 'blogList'], $content);
        $content = preg_replace_callback('/\[blog_comments(?:\s+post_id="(\d+)")?\]/', [self::class, 'blogComments'], $content);
        $content = preg_replace_callback('/\[blog_categories\]/', [self::class, 'blogCategoriesList'], $content);
        $content = preg_replace_callback('/\[blog_tags(?:\s+limit="(\d+)")?\]/', [self::class, 'blogTagCloud'], $content);
        
        return $content;
    }
    
    public static function contactForm($matches) {
        $message = '';
        $error = '';
        
        // Handle form submission
        if ($_POST && isset($_POST['contact_submit'])) {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $messageText = trim($_POST['message'] ?? '');
            
            if (empty($name) || empty($email) || empty($messageText)) {
                $error = 'Bitte füllen Sie alle Pflichtfelder aus.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            } else {
                // Save to database
                $sql = "INSERT INTO contact_submissions (name, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?)";
                self::$db->query($sql, [$name, $email, $subject, $messageText, $_SERVER['REMOTE_ADDR']]);
                
                // Send email (optional - requires mail configuration)
                $settings = self::getSettings();
                if (!empty($settings['admin_email'])) {
                    $mailSubject = 'Neue Kontaktanfrage: ' . $subject;
                    $mailBody = "Name: $name\nE-Mail: $email\nBetreff: $subject\n\nNachricht:\n$messageText";
                    mail($settings['admin_email'], $mailSubject, $mailBody, "From: $email");
                }
                
                $message = 'Vielen Dank für Ihre Nachricht. Wir werden uns bald bei Ihnen melden.';
            }
        }
        
        ob_start();
        ?>
        <div class="contact-form">
            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (!$message): ?>
                <form method="POST" class="contact-form-form">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-Mail *</label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Betreff</label>
                        <input type="text" id="subject" name="subject" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Nachricht *</label>
                        <textarea id="message" name="message" required rows="5"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" name="contact_submit" class="btn btn-primary">Nachricht senden</button>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function blogList($matches) {
        $limit = isset($matches[1]) ? (int)$matches[1] : 5;
        $category = isset($matches[2]) ? $matches[2] : null;
        
        // Get posts by category or all posts
        if ($category) {
            $posts = Blog::getByCategory($category, 'published', $limit);
            $categoryData = BlogCategory::getBySlug($category);
            $title = $categoryData ? 'Blog - ' . $categoryData['name'] : 'Blog';
        } else {
            $posts = Blog::getAll('published', $limit);
            $title = 'Blog';
        }
        
        ob_start();
        ?>
        <div class="blog-list">
            <?php if ($category && isset($categoryData)): ?>
                <div class="blog-category-header">
                    <h2><?= htmlspecialchars($categoryData['name']) ?></h2>
                    <?php if (!empty($categoryData['description'])): ?>
                        <p class="category-description"><?= htmlspecialchars($categoryData['description']) ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($posts)): ?>
                <p>Noch keine Blog-Beiträge vorhanden<?= $category ? ' in dieser Kategorie' : '' ?>.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <article class="blog-post-preview">
                        <h3><a href="/blog/<?= htmlspecialchars($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                        <div class="blog-meta">
                            <time><?= date('d.m.Y', strtotime($post['created_at'])) ?></time>
                            
                            <?php 
                            $postCategories = BlogCategory::getByPostId($post['id']);
                            if (!empty($postCategories)): ?>
                                <span class="categories">
                                    <?php foreach ($postCategories as $cat): ?>
                                        <a href="/blog/category/<?= htmlspecialchars($cat['slug']) ?>" class="category-link">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </a>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="blog-excerpt"><?= htmlspecialchars($post['excerpt']) ?></p>
                        <?php endif; ?>
                        
                        <?php 
                        $postTags = BlogTag::getByPostId($post['id']);
                        if (!empty($postTags)): ?>
                            <div class="blog-tags">
                                <?php foreach ($postTags as $tag): ?>
                                    <a href="/blog/tag/<?= htmlspecialchars($tag['slug']) ?>" class="tag-link">
                                        #<?= htmlspecialchars($tag['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="read-more">Weiterlesen →</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private static function getSettings() {
        $settings = [];
        $rows = self::$db->fetchAll("SELECT setting_key, setting_value FROM settings");
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
    
    public static function blogComments($matches) {
        // Get post_id from URL or shortcode parameter
        $post_id = null;
        
        if (isset($matches[1]) && is_numeric($matches[1])) {
            $post_id = (int)$matches[1];
        } elseif (isset($_GET['post'])) {
            // Get post ID from current blog post
            $post_slug = $_GET['post'];
            $post = Blog::getBySlug($post_slug);
            if ($post) {
                $post_id = $post['id'];
            }
        }
        
        if (!$post_id) {
            return '<p>Kommentare können nur bei Blog-Beiträgen angezeigt werden.</p>';
        }
        
        $message = '';
        $error = '';
        
        // Handle comment submission
        if ($_POST && isset($_POST['comment_submit']) && isset($_POST['post_id']) && $_POST['post_id'] == $post_id) {
            $author_name = trim($_POST['author_name'] ?? '');
            $author_email = trim($_POST['author_email'] ?? '');
            $content = trim($_POST['content'] ?? '');
            
            // Validation
            if (empty($author_name) || empty($author_email) || empty($content)) {
                $error = 'Bitte füllen Sie alle Felder aus.';
            } elseif (!filter_var($author_email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            } elseif (strlen($content) < 10) {
                $error = 'Der Kommentar muss mindestens 10 Zeichen lang sein.';
            } elseif (strlen($content) > 1000) {
                $error = 'Der Kommentar darf maximal 1000 Zeichen lang sein.';
            } else {
                // Basic spam protection
                $spam_words = ['viagra', 'casino', 'lottery', 'winner', 'congratulations'];
                $content_lower = strtolower($content);
                $is_spam = false;
                
                foreach ($spam_words as $spam_word) {
                    if (strpos($content_lower, $spam_word) !== false) {
                        $is_spam = true;
                        break;
                    }
                }
                
                // Create comment
                $comment_data = [
                    'post_id' => $post_id,
                    'author_name' => $author_name,
                    'author_email' => $author_email,
                    'content' => $content,
                    'status' => $is_spam ? 'spam' : 'pending'
                ];
                
                try {
                    BlogComment::create($comment_data);
                    $message = 'Vielen Dank für Ihren Kommentar! Er wird nach Prüfung freigeschaltet.';
                    
                    // Clear form data on success
                    $_POST = [];
                } catch (Exception $e) {
                    $error = 'Fehler beim Speichern des Kommentars. Bitte versuchen Sie es später erneut.';
                }
            }
        }
        
        // Get approved comments
        $comments = BlogComment::getByPostId($post_id, 'approved');
        
        ob_start();
        ?>
        <div class="blog-comments">
            <h3>Kommentare (<?= count($comments) ?>)</h3>
            
            <!-- Existing Comments -->
            <?php if (empty($comments)): ?>
                <p class="no-comments">Noch keine Kommentare vorhanden. Seien Sie der Erste!</p>
            <?php else: ?>
                <div class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="comment-header">
                                <strong class="comment-author"><?= htmlspecialchars($comment['author_name']) ?></strong>
                                <time class="comment-date"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></time>
                            </div>
                            <div class="comment-content">
                                <?= nl2br(htmlspecialchars($comment['content'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Comment Form -->
            <div class="comment-form-section">
                <h4>Kommentar hinterlassen</h4>
                
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if (!$message): ?>
                    <form method="POST" class="comment-form">
                        <input type="hidden" name="post_id" value="<?= $post_id ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="author_name">Name *</label>
                                <input type="text" id="author_name" name="author_name" required 
                                       value="<?= htmlspecialchars($_POST['author_name'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="author_email">E-Mail * <small>(wird nicht veröffentlicht)</small></label>
                                <input type="email" id="author_email" name="author_email" required 
                                       value="<?= htmlspecialchars($_POST['author_email'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Kommentar *</label>
                            <textarea id="content" name="content" required rows="4" 
                                      placeholder="Schreiben Sie hier Ihren Kommentar..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                            <small class="char-counter">Maximal 1000 Zeichen</small>
                        </div>
                        
                        <button type="submit" name="comment_submit" class="btn btn-primary">Kommentar absenden</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        // Character counter for comment textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('content');
            const counter = document.querySelector('.char-counter');
            
            if (textarea && counter) {
                function updateCounter() {
                    const remaining = 1000 - textarea.value.length;
                    counter.textContent = `${remaining} Zeichen übrig`;
                    counter.style.color = remaining < 50 ? '#dc3545' : '#666';
                }
                
                textarea.addEventListener('input', updateCounter);
                updateCounter(); // Initial count
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public static function blogCategoriesList($matches) {
        $categories = BlogCategory::getWithPostCount();
        
        ob_start();
        ?>
        <div class="blog-categories-list">
            <h3>Kategorien</h3>
            <?php if (empty($categories)): ?>
                <p>Noch keine Kategorien vorhanden.</p>
            <?php else: ?>
                <ul class="categories-list">
                    <?php foreach ($categories as $category): ?>
                        <?php if ($category['post_count'] > 0): ?>
                            <li>
                                <a href="/blog/category/<?= htmlspecialchars($category['slug']) ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                    <span class="post-count">(<?= $category['post_count'] ?>)</span>
                                </a>
                                <?php if (!empty($category['description'])): ?>
                                    <small class="category-description"><?= htmlspecialchars($category['description']) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function blogTagCloud($matches) {
        $limit = isset($matches[1]) ? (int)$matches[1] : 20;
        $tags = BlogTag::getPopular($limit);
        
        ob_start();
        ?>
        <div class="blog-tag-cloud">
            <h3>Tags</h3>
            <?php if (empty($tags)): ?>
                <p>Noch keine Tags vorhanden.</p>
            <?php else: ?>
                <div class="tag-cloud">
                    <?php foreach ($tags as $tag): ?>
                        <a href="/blog/tag/<?= htmlspecialchars($tag['slug']) ?>" 
                           class="tag-cloud-item"
                           style="font-size: <?= min(1.5, 0.8 + ($tag['usage_count'] * 0.1)) ?>rem;">
                            <?= htmlspecialchars($tag['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
