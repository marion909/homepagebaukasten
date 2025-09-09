<?php
class BlogTag {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getAll() {
        if (!self::$db) self::init();
        
        return self::$db->fetchAll("SELECT * FROM blog_tags ORDER BY name ASC");
    }
    
    public static function getById($id) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM blog_tags WHERE id = ?", [$id]);
    }
    
    public static function getBySlug($slug) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM blog_tags WHERE slug = ?", [$slug]);
    }
    
    public static function getByName($name) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM blog_tags WHERE name = ?", [$name]);
    }
    
    public static function create($data) {
        if (!self::$db) self::init();
        
        $sql = "INSERT INTO blog_tags (name, slug) VALUES (?, ?)";
        
        $params = [
            $data['name'],
            $data['slug']
        ];
        
        self::$db->query($sql, $params);
        return self::$db->lastInsertId();
    }
    
    public static function update($id, $data) {
        if (!self::$db) self::init();
        
        $sql = "UPDATE blog_tags SET name = ?, slug = ? WHERE id = ?";
        
        $params = [
            $data['name'],
            $data['slug'],
            $id
        ];
        
        return self::$db->query($sql, $params);
    }
    
    public static function delete($id) {
        if (!self::$db) self::init();
        
        return self::$db->query("DELETE FROM blog_tags WHERE id = ?", [$id]);
    }
    
    public static function getWithPostCount() {
        if (!self::$db) self::init();
        
        $sql = "SELECT bt.*, COUNT(bp.id) as post_count 
                FROM blog_tags bt
                LEFT JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
                LEFT JOIN blog_posts bp ON bpt.post_id = bp.id AND bp.status = 'published'
                GROUP BY bt.id
                ORDER BY bt.name ASC";
        
        return self::$db->fetchAll($sql);
    }
    
    public static function getByPostId($post_id) {
        if (!self::$db) self::init();
        
        $sql = "SELECT bt.* 
                FROM blog_tags bt
                JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
                WHERE bpt.post_id = ?
                ORDER BY bt.name ASC";
        
        return self::$db->fetchAll($sql, [$post_id]);
    }
    
    public static function assignToPost($post_id, $tag_names) {
        if (!self::$db) self::init();
        
        // Erst alle bestehenden Zuordnungen löschen
        self::$db->query("DELETE FROM blog_post_tags WHERE post_id = ?", [$post_id]);
        
        // Tags verarbeiten und zuordnen
        if (!empty($tag_names)) {
            $tag_array = array_map('trim', explode(',', $tag_names));
            $tag_array = array_filter($tag_array); // Leere Werte entfernen
            
            foreach ($tag_array as $tag_name) {
                // Prüfen ob Tag bereits existiert
                $existing_tag = self::getByName($tag_name);
                
                if ($existing_tag) {
                    $tag_id = $existing_tag['id'];
                } else {
                    // Neuen Tag erstellen
                    $tag_data = [
                        'name' => $tag_name,
                        'slug' => self::generateSlug($tag_name)
                    ];
                    $tag_id = self::create($tag_data);
                }
                
                // Tag mit Post verknüpfen
                self::$db->query(
                    "INSERT INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)",
                    [$post_id, $tag_id]
                );
            }
        }
    }
    
    public static function getTagsAsString($post_id) {
        $tags = self::getByPostId($post_id);
        return implode(', ', array_column($tags, 'name'));
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
    
    public static function getPopular($limit = 10) {
        if (!self::$db) self::init();
        
        $sql = "SELECT bt.*, COUNT(bpt.post_id) as usage_count
                FROM blog_tags bt
                JOIN blog_post_tags bpt ON bt.id = bpt.tag_id
                JOIN blog_posts bp ON bpt.post_id = bp.id AND bp.status = 'published'
                GROUP BY bt.id
                ORDER BY usage_count DESC, bt.name ASC
                LIMIT " . (int)$limit;
        
        return self::$db->fetchAll($sql);
    }
}
