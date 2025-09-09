<?php
class BlogComment {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getByPostId($post_id, $status = 'approved') {
        if (!self::$db) self::init();
        
        $sql = "SELECT * FROM blog_comments 
                WHERE post_id = ? AND status = ? 
                ORDER BY created_at ASC";
        
        return self::$db->fetchAll($sql, [$post_id, $status]);
    }
    
    public static function getById($id) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM blog_comments WHERE id = ?", [$id]);
    }
    
    public static function create($data) {
        if (!self::$db) self::init();
        
        $sql = "INSERT INTO blog_comments (post_id, author_name, author_email, content, ip_address, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['post_id'],
            $data['author_name'],
            $data['author_email'],
            $data['content'],
            $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
            $data['status'] ?? 'pending'
        ];
        
        self::$db->query($sql, $params);
        return self::$db->lastInsertId();
    }
    
    public static function updateStatus($id, $status) {
        if (!self::$db) self::init();
        
        $sql = "UPDATE blog_comments SET status = ? WHERE id = ?";
        return self::$db->query($sql, [$status, $id]);
    }
    
    public static function delete($id) {
        if (!self::$db) self::init();
        
        return self::$db->query("DELETE FROM blog_comments WHERE id = ?", [$id]);
    }
    
    public static function getCount($status = null) {
        if (!self::$db) self::init();
        
        if ($status) {
            return self::$db->fetchOne("SELECT COUNT(*) as count FROM blog_comments WHERE status = ?", [$status])['count'];
        } else {
            return self::$db->fetchOne("SELECT COUNT(*) as count FROM blog_comments")['count'];
        }
    }
    
    public static function getRecent($limit = 5) {
        if (!self::$db) self::init();
        
        $sql = "SELECT bc.*, bp.title as post_title 
                FROM blog_comments bc
                JOIN blog_posts bp ON bc.post_id = bp.id
                ORDER BY bc.created_at DESC 
                LIMIT " . (int)$limit;
        
        return self::$db->fetchAll($sql);
    }
    
    public static function approve($id) {
        return self::updateStatus($id, 'approved');
    }
    
    public static function reject($id) {
        return self::updateStatus($id, 'rejected');
    }
    
    public static function spam($id) {
        return self::updateStatus($id, 'spam');
    }
}
