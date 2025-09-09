<?php
class BlogCategory {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getAll() {
        if (!self::$db) self::init();
        
        return self::$db->fetchAll("SELECT * FROM blog_categories ORDER BY name ASC");
    }
    
    public static function getById($id) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM blog_categories WHERE id = ?", [$id]);
    }
    
    public static function getBySlug($slug) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM blog_categories WHERE slug = ?", [$slug]);
    }
    
    public static function create($data) {
        if (!self::$db) self::init();
        
        $sql = "INSERT INTO blog_categories (name, slug, description) VALUES (?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['slug'],
            $data['description'] ?? ''
        ];
        
        self::$db->query($sql, $params);
        return self::$db->lastInsertId();
    }
    
    public static function update($id, $data) {
        if (!self::$db) self::init();
        
        $sql = "UPDATE blog_categories SET name = ?, slug = ?, description = ? WHERE id = ?";
        
        $params = [
            $data['name'],
            $data['slug'],
            $data['description'] ?? '',
            $id
        ];
        
        return self::$db->query($sql, $params);
    }
    
    public static function delete($id) {
        if (!self::$db) self::init();
        
        return self::$db->query("DELETE FROM blog_categories WHERE id = ?", [$id]);
    }
    
    public static function getWithPostCount() {
        if (!self::$db) self::init();
        
        $sql = "SELECT bc.*, COUNT(bp.id) as post_count 
                FROM blog_categories bc
                LEFT JOIN blog_post_categories bpc ON bc.id = bpc.category_id
                LEFT JOIN blog_posts bp ON bpc.post_id = bp.id AND bp.status = 'published'
                GROUP BY bc.id
                ORDER BY bc.name ASC";
        
        return self::$db->fetchAll($sql);
    }
    
    public static function getByPostId($post_id) {
        if (!self::$db) self::init();
        
        $sql = "SELECT bc.* 
                FROM blog_categories bc
                JOIN blog_post_categories bpc ON bc.id = bpc.category_id
                WHERE bpc.post_id = ?
                ORDER BY bc.name ASC";
        
        return self::$db->fetchAll($sql, [$post_id]);
    }
    
    public static function assignToPost($post_id, $category_ids) {
        if (!self::$db) self::init();
        
        // Erst alle bestehenden Zuordnungen löschen
        self::$db->query("DELETE FROM blog_post_categories WHERE post_id = ?", [$post_id]);
        
        // Neue Zuordnungen hinzufügen
        if (!empty($category_ids)) {
            foreach ($category_ids as $category_id) {
                self::$db->query(
                    "INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)",
                    [$post_id, $category_id]
                );
            }
        }
    }
    
    public static function generateSlug($name) {
        $slug = strtolower($name);
        $slug = preg_replace('/ä/u', 'ae', $slug);
        $slug = preg_replace('/ö/u', 'oe', $slug);
        $slug = preg_replace('/ü/u', 'ue', $slug);
        $slug = preg_replace('/ß/u', 'ss', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/u', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
}
