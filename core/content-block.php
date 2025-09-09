<?php
class ContentBlock {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getAll() {
        if (!self::$db) self::init();
        
        return self::$db->fetchAll("SELECT * FROM content_blocks ORDER BY name ASC");
    }
    
    public static function getById($id) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM content_blocks WHERE id = ?", [$id]);
    }
    
    public static function getByKey($key) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM content_blocks WHERE block_key = ? AND active = 1", [$key]);
    }
    
    public static function create($data) {
        if (!self::$db) self::init();
        
        // Validate required fields
        if (empty($data['name']) || empty($data['block_key'])) {
            throw new Exception('Name und Block-Key sind erforderlich');
        }
        
        // Check if key already exists
        $existing = self::$db->fetchOne("SELECT id FROM content_blocks WHERE block_key = ?", [$data['block_key']]);
        if ($existing) {
            throw new Exception('Block-Key bereits vergeben');
        }
        
        $sql = "INSERT INTO content_blocks (name, block_key, content, description, type, active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['block_key'],
            $data['content'],
            $data['description'] ?? null,
            $data['type'] ?? 'html',
            $data['active'] ?? 1,
            $data['created_by']
        ];
        
        self::$db->query($sql, $params);
        return self::$db->lastInsertId();
    }
    
    public static function update($id, $data) {
        if (!self::$db) self::init();
        
        $fields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        
        if (isset($data['block_key'])) {
            // Check if key is taken by another block
            $existing = self::$db->fetchOne("SELECT id FROM content_blocks WHERE block_key = ? AND id != ?", 
                                           [$data['block_key'], $id]);
            if ($existing) {
                throw new Exception('Block-Key bereits vergeben');
            }
            $fields[] = "block_key = ?";
            $params[] = $data['block_key'];
        }
        
        if (isset($data['content'])) {
            $fields[] = "content = ?";
            $params[] = $data['content'];
        }
        
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (isset($data['type'])) {
            $fields[] = "type = ?";
            $params[] = $data['type'];
        }
        
        if (isset($data['active'])) {
            $fields[] = "active = ?";
            $params[] = $data['active'];
        }
        
        if (empty($fields)) {
            return true; // Nothing to update
        }
        
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        
        $sql = "UPDATE content_blocks SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;
        
        return self::$db->query($sql, $params);
    }
    
    public static function delete($id) {
        if (!self::$db) self::init();
        
        return self::$db->query("DELETE FROM content_blocks WHERE id = ?", [$id]);
    }
    
    public static function render($key, $defaultContent = '') {
        if (!self::$db) self::init();
        
        $block = self::getByKey($key);
        
        if (!$block) {
            return $defaultContent;
        }
        
        $content = $block['content'];
        
        // Process shortcodes if content type is html
        if ($block['type'] === 'html') {
            $content = Shortcodes::process($content);
        }
        
        return $content;
    }
    
    public static function getTypes() {
        return [
            'html' => 'HTML/Text mit Shortcodes',
            'text' => 'Reiner Text',
            'css' => 'CSS Code',
            'javascript' => 'JavaScript Code'
        ];
    }
    
    public static function getStats() {
        if (!self::$db) self::init();
        
        return [
            'total' => self::$db->fetchOne("SELECT COUNT(*) as count FROM content_blocks")['count'],
            'active' => self::$db->fetchOne("SELECT COUNT(*) as count FROM content_blocks WHERE active = 1")['count'],
            'inactive' => self::$db->fetchOne("SELECT COUNT(*) as count FROM content_blocks WHERE active = 0")['count']
        ];
    }
}
?>
