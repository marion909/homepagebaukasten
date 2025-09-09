<?php
class RSS {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function generateFeed($limit = 20) {
        if (!self::$db) self::init();
        
        // Get site information
        $siteTitle = SITE_NAME ?? 'Meine Website';
        $siteDescription = SITE_DESCRIPTION ?? 'Neueste Artikel und Updates';
        $baseUrl = rtrim(SITE_URL, '/');
        
        // Create RSS XML
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // RSS root
        $rss = $xml->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $rss->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->appendChild($rss);
        
        // Channel
        $channel = $xml->createElement('channel');
        $rss->appendChild($channel);
        
        // Channel info
        $channel->appendChild($xml->createElement('title', htmlspecialchars($siteTitle)));
        $channel->appendChild($xml->createElement('description', htmlspecialchars($siteDescription)));
        $channel->appendChild($xml->createElement('link', $baseUrl));
        $channel->appendChild($xml->createElement('language', 'de-DE'));
        $channel->appendChild($xml->createElement('lastBuildDate', date('r')));
        $channel->appendChild($xml->createElement('generator', 'Baukasten CMS'));
        
        // Self link
        $atomLink = $xml->createElement('atom:link');
        $atomLink->setAttribute('href', $baseUrl . '/rss.xml');
        $atomLink->setAttribute('rel', 'self');
        $atomLink->setAttribute('type', 'application/rss+xml');
        $channel->appendChild($atomLink);
        
        // Get blog posts
        $posts = self::$db->fetchAll("
            SELECT bp.*, u.username as author_name
            FROM blog_posts bp
            LEFT JOIN users u ON bp.created_by = u.id
            WHERE bp.status = 'published'
            ORDER BY bp.created_at DESC
            LIMIT ?
        ", [intval($limit)]);
        
        // Add items
        foreach ($posts as $post) {
            $item = $xml->createElement('item');
            
            // Basic info
            $item->appendChild($xml->createElement('title', htmlspecialchars($post['title'])));
            $item->appendChild($xml->createElement('link', $baseUrl . '/blog/' . $post['slug']));
            $item->appendChild($xml->createElement('pubDate', date('r', strtotime($post['created_at']))));
            $item->appendChild($xml->createElement('author', htmlspecialchars($post['author_name'] ?? 'Unbekannt')));
            
            // GUID
            $guid = $xml->createElement('guid', $baseUrl . '/blog/' . $post['slug']);
            $guid->setAttribute('isPermaLink', 'true');
            $item->appendChild($guid);
            
            // Description (excerpt or content preview)
            $description = '';
            if (!empty($post['excerpt'])) {
                $description = $post['excerpt'];
            } else {
                // Create excerpt from content
                $description = self::createExcerpt($post['content'], 200);
            }
            $item->appendChild($xml->createElement('description', htmlspecialchars($description)));
            
            // Full content
            $contentNode = $xml->createElement('content:encoded');
            $contentNode->appendChild($xml->createCDATASection($post['content']));
            $item->appendChild($contentNode);
            
            // Categories (if available)
            try {
                $categories = self::$db->fetchAll("
                    SELECT bc.name
                    FROM blog_categories bc
                    JOIN blog_post_categories bpc ON bc.id = bpc.category_id
                    WHERE bpc.post_id = ?
                ", [$post['id']]);
                
                foreach ($categories as $category) {
                    $item->appendChild($xml->createElement('category', htmlspecialchars($category['name'])));
                }
            } catch (Exception $e) {
                // Categories table might not exist
            }
            
            $channel->appendChild($item);
        }
        
        return $xml->saveXML();
    }
    
    private static function createExcerpt($content, $length = 200) {
        // Strip HTML tags
        $text = strip_tags($content);
        
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Truncate to length
        if (strlen($text) > $length) {
            $text = substr($text, 0, $length);
            $text = substr($text, 0, strrpos($text, ' ')) . '...';
        }
        
        return $text;
    }
    
    public static function saveToFile($filename = 'rss.xml') {
        $xml = self::generateFeed();
        $filepath = $_SERVER['DOCUMENT_ROOT'] . '/' . $filename;
        
        if (file_put_contents($filepath, $xml)) {
            return $filepath;
        }
        
        return false;
    }
    
    public static function outputFeed($limit = 20) {
        header('Content-Type: application/rss+xml; charset=UTF-8');
        echo self::generateFeed($limit);
        exit;
    }
    
    public static function getStats() {
        if (!self::$db) self::init();
        
        $stats = [];
        
        // Total published posts
        $stats['total_posts'] = self::$db->fetchOne("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'")['count'];
        
        // Latest post date
        $latestPost = self::$db->fetchOne("SELECT created_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 1");
        $stats['latest_post'] = $latestPost ? $latestPost['created_at'] : null;
        
        // Posts this month
        $stats['posts_this_month'] = self::$db->fetchOne("
            SELECT COUNT(*) as count 
            FROM blog_posts 
            WHERE status = 'published' 
            AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
            AND YEAR(created_at) = YEAR(CURRENT_DATE())
        ")['count'];
        
        return $stats;
    }
}
?>
