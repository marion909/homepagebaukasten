<?php
class Page {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getBySlug($slug) {
        if (!self::$db) self::init();
        
        $page = self::$db->fetchOne(
            "SELECT * FROM pages WHERE slug = ? AND status = 'published'", 
            [$slug]
        );
        
        if (!$page) {
            // Return 404 page or default content
            return [
                'title' => 'Seite nicht gefunden',
                'content' => '<h1>404 - Seite nicht gefunden</h1><p>Die angeforderte Seite existiert nicht.</p>',
                'meta_description' => '',
                'meta_keywords' => ''
            ];
        }
        
        return $page;
    }
    
    public static function getAll($status = null) {
        if (!self::$db) self::init();
        
        $sql = "SELECT * FROM pages";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY sort_order ASC, created_at DESC";
        
        return self::$db->fetchAll($sql, $params);
    }
    
    public static function getById($id) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM pages WHERE id = ?", [$id]);
    }
    
    public static function create($data) {
        if (!self::$db) self::init();
        
        $sql = "INSERT INTO pages (title, slug, content, meta_description, meta_keywords, status, sort_order, show_in_nav, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['meta_description'] ?? '',
            $data['meta_keywords'] ?? '',
            $data['status'] ?? 'draft',
            $data['sort_order'] ?? 0,
            $data['show_in_nav'] ?? 1,
            $data['created_by']
        ];
        
        self::$db->query($sql, $params);
        return self::$db->lastInsertId();
    }
    
    public static function update($id, $data) {
        if (!self::$db) self::init();
        
        $sql = "UPDATE pages SET title = ?, slug = ?, content = ?, meta_description = ?, 
                meta_keywords = ?, status = ?, sort_order = ?, show_in_nav = ? WHERE id = ?";
        
        $params = [
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['meta_description'] ?? '',
            $data['meta_keywords'] ?? '',
            $data['status'] ?? 'draft',
            $data['sort_order'] ?? 0,
            $data['show_in_nav'] ?? 1,
            $id
        ];
        
        return self::$db->query($sql, $params);
    }
    
    public static function delete($id) {
        if (!self::$db) self::init();
        
        return self::$db->query("DELETE FROM pages WHERE id = ?", [$id]);
    }
    
    public static function generateSlug($title) {
        // Convert to lowercase and replace special characters
        $slug = strtolower($title);
        $slug = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    public static function slugExists($slug, $excludeId = null) {
        if (!self::$db) self::init();
        
        $sql = "SELECT id FROM pages WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return self::$db->fetchOne($sql, $params) !== false;
    }
}
