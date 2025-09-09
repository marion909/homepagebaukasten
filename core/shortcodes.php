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
        $content = preg_replace_callback('/\[blog_list(?:\s+limit="(\d+)")?\]/', [self::class, 'blogList'], $content);
        
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
        $posts = Blog::getAll('published', $limit);
        
        ob_start();
        ?>
        <div class="blog-list">
            <?php if (empty($posts)): ?>
                <p>Noch keine Blog-Beiträge vorhanden.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <article class="blog-post-preview">
                        <h3><a href="?page=blog&post=<?= htmlspecialchars($post['slug']) ?>"><?= htmlspecialchars($post['title']) ?></a></h3>
                        <div class="blog-meta">
                            <time><?= date('d.m.Y', strtotime($post['created_at'])) ?></time>
                        </div>
                        <?php if (!empty($post['excerpt'])): ?>
                            <p class="blog-excerpt"><?= htmlspecialchars($post['excerpt']) ?></p>
                        <?php endif; ?>
                        <a href="?page=blog&post=<?= htmlspecialchars($post['slug']) ?>" class="read-more">Weiterlesen →</a>
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
}
