<?php
class Blog {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getAll($status = 'published', $limit = null, $category_id = null) {
        if (!self::$db) self::init();
        
        $sql = "SELECT bp.* FROM blog_posts bp";
        $params = [];
        
        if ($category_id) {
            $sql .= " JOIN blog_post_categories bpc ON bp.id = bpc.post_id";
            $sql .= " WHERE bp.status = ? AND bpc.category_id = ?";
            $params = [$status, $category_id];
        } else {
            $sql .= " WHERE status = ?";
            $params = [$status];
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit) {
            // LIMIT als Teil der SQL-Query hinzufügen (sicher, da wir (int) casten)
            $sql .= " LIMIT " . (int)$limit;
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
    
    public static function getByCategory($category_slug, $status = 'published', $limit = null) {
        if (!self::$db) self::init();
        
        $sql = "SELECT bp.* 
                FROM blog_posts bp
                JOIN blog_post_categories bpc ON bp.id = bpc.post_id
                JOIN blog_categories bc ON bpc.category_id = bc.id
                WHERE bp.status = ? AND bc.slug = ?
                ORDER BY bp.created_at DESC";
        
        $params = [$status, $category_slug];
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return self::$db->fetchAll($sql, $params);
    }
    
    public static function getByTag($tag_slug, $status = 'published', $limit = null) {
        if (!self::$db) self::init();
        
        $sql = "SELECT bp.* 
                FROM blog_posts bp
                JOIN blog_post_tags bpt ON bp.id = bpt.post_id
                JOIN blog_tags bt ON bpt.tag_id = bt.id
                WHERE bp.status = ? AND bt.slug = ?
                ORDER BY bp.created_at DESC";
        
        $params = [$status, $tag_slug];
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return self::$db->fetchAll($sql, $params);
    }
    
    public static function search($query, $status = 'published', $limit = null) {
        if (!self::$db) self::init();
        
        $sql = "SELECT bp.* 
                FROM blog_posts bp
                WHERE bp.status = ? AND (
                    bp.title LIKE ? OR 
                    bp.excerpt LIKE ? OR 
                    bp.content LIKE ?
                )
                ORDER BY bp.created_at DESC";
        
        $searchTerm = '%' . $query . '%';
        $params = [$status, $searchTerm, $searchTerm, $searchTerm];
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return self::$db->fetchAll($sql, $params);
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
