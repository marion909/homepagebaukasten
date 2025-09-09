<?php
class Sitemap {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function generateXML() {
        if (!self::$db) self::init();
        
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        // Root element
        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xml->appendChild($urlset);
        
        // Base URL from config
        $baseUrl = rtrim(SITE_URL, '/');
        
        // Homepage
        self::addUrl($xml, $urlset, $baseUrl, date('c'), 'daily', '1.0');
        
        // Static pages
        $pages = self::$db->fetchAll("SELECT slug, updated_at FROM pages WHERE status = 'published' ORDER BY slug");
        foreach ($pages as $page) {
            if ($page['slug'] !== 'home') { // Skip home page (already added)
                $url = $baseUrl . '/' . $page['slug'];
                $lastmod = date('c', strtotime($page['updated_at']));
                self::addUrl($xml, $urlset, $url, $lastmod, 'weekly', '0.8');
            }
        }
        
        // Blog overview
        self::addUrl($xml, $urlset, $baseUrl . '/blog', date('c'), 'daily', '0.9');
        
        // Blog posts
        $posts = self::$db->fetchAll("SELECT slug, updated_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC");
        foreach ($posts as $post) {
            $url = $baseUrl . '/blog/' . $post['slug'];
            $lastmod = date('c', strtotime($post['updated_at']));
            self::addUrl($xml, $urlset, $url, $lastmod, 'weekly', '0.7');
        }
        
        // Blog categories
        try {
            $categories = self::$db->fetchAll("SELECT slug, updated_at FROM blog_categories ORDER BY name");
            foreach ($categories as $category) {
                $url = $baseUrl . '/blog/category/' . $category['slug'];
                $lastmod = date('c', strtotime($category['updated_at']));
                self::addUrl($xml, $urlset, $url, $lastmod, 'weekly', '0.6');
            }
        } catch (Exception $e) {
            // Categories table might not exist yet
        }
        
        // Blog tags
        try {
            $tags = self::$db->fetchAll("SELECT slug, created_at FROM blog_tags ORDER BY name");
            foreach ($tags as $tag) {
                $url = $baseUrl . '/blog/tag/' . $tag['slug'];
                $lastmod = date('c', strtotime($tag['created_at']));
                self::addUrl($xml, $urlset, $url, $lastmod, 'monthly', '0.5');
            }
        } catch (Exception $e) {
            // Tags table might not exist yet
        }
        
        return $xml->saveXML();
    }
    
    private static function addUrl($xml, $urlset, $loc, $lastmod, $changefreq, $priority) {
        $url = $xml->createElement('url');
        
        $url->appendChild($xml->createElement('loc', htmlspecialchars($loc)));
        $url->appendChild($xml->createElement('lastmod', $lastmod));
        $url->appendChild($xml->createElement('changefreq', $changefreq));
        $url->appendChild($xml->createElement('priority', $priority));
        
        $urlset->appendChild($url);
    }
    
    public static function saveToFile($filename = 'sitemap.xml') {
        $xml = self::generateXML();
        $filepath = $_SERVER['DOCUMENT_ROOT'] . '/' . $filename;
        
        if (file_put_contents($filepath, $xml)) {
            return $filepath;
        }
        
        return false;
    }
    
    public static function getStats() {
        if (!self::$db) self::init();
        
        $stats = [];
        
        // Count pages
        $stats['pages'] = self::$db->fetchOne("SELECT COUNT(*) as count FROM pages WHERE status = 'published'")['count'];
        
        // Count blog posts
        $stats['blog_posts'] = self::$db->fetchOne("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'")['count'];
        
        // Count categories (if table exists)
        try {
            $stats['categories'] = self::$db->fetchOne("SELECT COUNT(*) as count FROM blog_categories")['count'];
        } catch (Exception $e) {
            $stats['categories'] = 0;
        }
        
        // Count tags (if table exists)
        try {
            $stats['tags'] = self::$db->fetchOne("SELECT COUNT(*) as count FROM blog_tags")['count'];
        } catch (Exception $e) {
            $stats['tags'] = 0;
        }
        
        // Total URLs
        $stats['total_urls'] = 2 + $stats['pages'] + $stats['blog_posts'] + $stats['categories'] + $stats['tags'];
        
        return $stats;
    }
}
?>
