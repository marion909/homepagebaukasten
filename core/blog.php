<?php
class Blog {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getAll($status = 'published', $limit = null) {
        if (!self::$db) self::init();
        
        $sql = "SELECT * FROM blog_posts WHERE status = ? ORDER BY created_at DESC";
        $params = [$status];
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
        }
        
        return self::$db->fetchAll($sql, $params);
    }
    
    public static function getById($id) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM blog_posts WHERE id = ?", [$id]);
    }
    
    public static function getBySlug($slug) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM blog_posts WHERE slug = ? AND status = 'published'", [$slug]);
    }
    
    public static function create($data) {
        if (!self::$db) self::init();
        
        $sql = "INSERT INTO blog_posts (title, slug, excerpt, content, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['title'],
            $data['slug'],
            $data['excerpt'] ?? '',
            $data['content'],
            $data['status'] ?? 'draft',
            $data['created_by']
        ];
        
        self::$db->query($sql, $params);
        return self::$db->lastInsertId();
    }
    
    public static function update($id, $data) {
        if (!self::$db) self::init();
        
        $sql = "UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, status = ? WHERE id = ?";
        
        $params = [
            $data['title'],
            $data['slug'],
            $data['excerpt'] ?? '',
            $data['content'],
            $data['status'] ?? 'draft',
            $id
        ];
        
        return self::$db->query($sql, $params);
    }
    
    public static function delete($id) {
        if (!self::$db) self::init();
        
        return self::$db->query("DELETE FROM blog_posts WHERE id = ?", [$id]);
    }
    
    public static function generateSlug($title) {
        // Same as Page::generateSlug
        $slug = strtolower($title);
        $slug = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    public static function slugExists($slug, $excludeId = null) {
        if (!self::$db) self::init();
        
        $sql = "SELECT id FROM blog_posts WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return self::$db->fetchOne($sql, $params) !== false;
    }
}
