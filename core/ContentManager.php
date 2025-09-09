<?php
/**
 * Content Management System - Erweiterte Inhaltsverwaltung
 */

class ContentManager {
    private $db;
    private $settings;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->settings = Settings::getInstance();
    }
    
    /**
     * Content-Revisionen verwalten
     */
    public function saveRevision($contentType, $contentId, $content, $userId) {
        $stmt = $this->db->prepare("
            INSERT INTO content_revisions (content_type, content_id, content, user_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $revisionId = $stmt->execute([$contentType, $contentId, json_encode($content), $userId]);
        
        // Alte Revisionen begrenzen (nur letzte 10 behalten)
        $this->cleanupRevisions($contentType, $contentId);
        
        return $revisionId;
    }
    
    /**
     * Content-Revision abrufen
     */
    public function getRevision($revisionId) {
        return $this->db->fetchOne("
            SELECT r.*, u.username 
            FROM content_revisions r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.id = ?
        ", [$revisionId]);
    }
    
    /**
     * Alle Revisionen für Content abrufen
     */
    public function getRevisions($contentType, $contentId, $limit = 10) {
        return $this->db->fetchAll("
            SELECT r.*, u.username 
            FROM content_revisions r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.content_type = ? AND r.content_id = ? 
            ORDER BY r.created_at DESC 
            LIMIT ?
        ", [$contentType, $contentId, $limit]);
    }
    
    /**
     * Revision wiederherstellen
     */
    public function restoreRevision($revisionId, $userId) {
        $revision = $this->getRevision($revisionId);
        if (!$revision) {
            return false;
        }
        
        $content = json_decode($revision['content'], true);
        $contentType = $revision['content_type'];
        $contentId = $revision['content_id'];
        
        // Aktuelle Version als Revision speichern
        $current = $this->getCurrentContent($contentType, $contentId);
        if ($current) {
            $this->saveRevision($contentType, $contentId, $current, $userId);
        }
        
        // Content wiederherstellen
        return $this->updateContent($contentType, $contentId, $content);
    }
    
    /**
     * Alte Revisionen aufräumen
     */
    private function cleanupRevisions($contentType, $contentId, $keepCount = 10) {
        $this->db->execute("
            DELETE FROM content_revisions 
            WHERE content_type = ? AND content_id = ? 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM content_revisions 
                    WHERE content_type = ? AND content_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?
                ) as keep_revisions
            )
        ", [$contentType, $contentId, $contentType, $contentId, $keepCount]);
    }
    
    /**
     * Content-Planung (Scheduled Publishing)
     */
    public function scheduleContent($contentType, $contentId, $publishAt, $action = 'publish') {
        return $this->db->execute("
            INSERT INTO content_schedule (content_type, content_id, action, scheduled_at, status)
            VALUES (?, ?, ?, ?, 'pending')
            ON DUPLICATE KEY UPDATE
            action = VALUES(action),
            scheduled_at = VALUES(scheduled_at),
            status = 'pending'
        ", [$contentType, $contentId, $action, $publishAt]);
    }
    
    /**
     * Geplante Inhalte verarbeiten
     */
    public function processScheduledContent() {
        $scheduled = $this->db->fetchAll("
            SELECT * FROM content_schedule 
            WHERE status = 'pending' AND scheduled_at <= NOW()
        ");
        
        foreach ($scheduled as $item) {
            $success = false;
            
            switch ($item['action']) {
                case 'publish':
                    $success = $this->publishContent($item['content_type'], $item['content_id']);
                    break;
                case 'unpublish':
                    $success = $this->unpublishContent($item['content_type'], $item['content_id']);
                    break;
                case 'delete':
                    $success = $this->deleteContent($item['content_type'], $item['content_id']);
                    break;
            }
            
            // Status aktualisieren
            $this->db->execute("
                UPDATE content_schedule 
                SET status = ?, processed_at = NOW() 
                WHERE id = ?
            ", [$success ? 'completed' : 'failed', $item['id']]);
        }
    }
    
    /**
     * Content-Kategorien verwalten
     */
    public function createCategory($name, $description = '', $parentId = null) {
        return $this->db->execute("
            INSERT INTO content_categories (name, description, parent_id, slug, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ", [$name, $description, $parentId, $this->createSlug($name)]);
    }
    
    public function getCategories($hierarchical = true) {
        $categories = $this->db->fetchAll("
            SELECT * FROM content_categories 
            ORDER BY parent_id ASC, name ASC
        ");
        
        if ($hierarchical) {
            return $this->buildCategoryTree($categories);
        }
        
        return $categories;
    }
    
    private function buildCategoryTree($categories, $parentId = null) {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $category['children'] = $this->buildCategoryTree($categories, $category['id']);
                $tree[] = $category;
            }
        }
        
        return $tree;
    }
    
    /**
     * Content-Tags verwalten
     */
    public function addTag($name, $color = '#007bff') {
        $slug = $this->createSlug($name);
        
        return $this->db->execute("
            INSERT INTO content_tags (name, slug, color, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE name = VALUES(name), color = VALUES(color)
        ", [$name, $slug, $color]);
    }
    
    public function getTags() {
        return $this->db->fetchAll("
            SELECT t.*, COUNT(ct.content_id) as usage_count
            FROM content_tags t
            LEFT JOIN content_tag_relations ct ON t.id = ct.tag_id
            GROUP BY t.id
            ORDER BY t.name
        ");
    }
    
    public function tagContent($contentType, $contentId, $tagIds) {
        // Alte Tags entfernen
        $this->db->execute("
            DELETE FROM content_tag_relations 
            WHERE content_type = ? AND content_id = ?
        ", [$contentType, $contentId]);
        
        // Neue Tags hinzufügen
        foreach ($tagIds as $tagId) {
            $this->db->execute("
                INSERT INTO content_tag_relations (content_type, content_id, tag_id)
                VALUES (?, ?, ?)
            ", [$contentType, $contentId, $tagId]);
        }
    }
    
    /**
     * Content-Workflow (Draft -> Review -> Published)
     */
    public function submitForReview($contentType, $contentId, $userId) {
        return $this->db->execute("
            UPDATE {$this->getContentTable($contentType)} 
            SET status = 'review', submitted_by = ?, submitted_at = NOW()
            WHERE id = ?
        ", [$userId, $contentId]);
    }
    
    public function approveContent($contentType, $contentId, $userId, $notes = '') {
        $success = $this->db->execute("
            UPDATE {$this->getContentTable($contentType)} 
            SET status = 'published', approved_by = ?, approved_at = NOW()
            WHERE id = ?
        ", [$userId, $contentId]);
        
        if ($success && $notes) {
            $this->addWorkflowNote($contentType, $contentId, 'approved', $notes, $userId);
        }
        
        return $success;
    }
    
    public function rejectContent($contentType, $contentId, $userId, $reason) {
        $success = $this->db->execute("
            UPDATE {$this->getContentTable($contentType)} 
            SET status = 'draft'
            WHERE id = ?
        ", [$contentId]);
        
        if ($success) {
            $this->addWorkflowNote($contentType, $contentId, 'rejected', $reason, $userId);
        }
        
        return $success;
    }
    
    private function addWorkflowNote($contentType, $contentId, $action, $note, $userId) {
        return $this->db->execute("
            INSERT INTO content_workflow_notes (content_type, content_id, action, note, user_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ", [$contentType, $contentId, $action, $note, $userId]);
    }
    
    /**
     * Content-Duplikation
     */
    public function duplicateContent($contentType, $contentId, $newTitle = null) {
        $content = $this->getCurrentContent($contentType, $contentId);
        if (!$content) {
            return false;
        }
        
        // Titel anpassen
        if ($newTitle) {
            $content['title'] = $newTitle;
        } else {
            $content['title'] .= ' (Kopie)';
        }
        
        // Slug generieren
        $content['slug'] = $this->createUniqueSlug($contentType, $content['title']);
        $content['status'] = 'draft';
        $content['created_at'] = date('Y-m-d H:i:s');
        
        // ID entfernen für INSERT
        unset($content['id']);
        
        return $this->createContent($contentType, $content);
    }
    
    /**
     * Content-Templates
     */
    public function saveAsTemplate($contentType, $contentId, $templateName, $description = '') {
        $content = $this->getCurrentContent($contentType, $contentId);
        if (!$content) {
            return false;
        }
        
        // Sensitive Daten entfernen
        unset($content['id'], $content['created_at'], $content['updated_at']);
        $content['title'] = '[Template] ' . $content['title'];
        
        return $this->db->execute("
            INSERT INTO content_templates (name, description, content_type, template_data, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ", [$templateName, $description, $contentType, json_encode($content)]);
    }
    
    public function getTemplates($contentType = null) {
        $query = "SELECT * FROM content_templates";
        $params = [];
        
        if ($contentType) {
            $query .= " WHERE content_type = ?";
            $params[] = $contentType;
        }
        
        $query .= " ORDER BY name";
        
        return $this->db->fetchAll($query, $params);
    }
    
    public function createFromTemplate($templateId, $newTitle) {
        $template = $this->db->fetchOne("
            SELECT * FROM content_templates WHERE id = ?
        ", [$templateId]);
        
        if (!$template) {
            return false;
        }
        
        $content = json_decode($template['template_data'], true);
        $content['title'] = $newTitle;
        $content['slug'] = $this->createUniqueSlug($template['content_type'], $newTitle);
        $content['status'] = 'draft';
        
        return $this->createContent($template['content_type'], $content);
    }
    
    /**
     * Content-Suche und Filter
     */
    public function searchContent($query, $contentType = null, $filters = []) {
        $sql = "SELECT * FROM ";
        $params = [];
        
        if ($contentType) {
            $sql .= $this->getContentTable($contentType);
        } else {
            // Union aller Content-Typen
            $sql .= "(
                SELECT 'page' as content_type, id, title, content, status, created_at FROM pages
                UNION ALL
                SELECT 'blog_post' as content_type, id, title, content, status, created_at FROM blog_posts
            ) as all_content";
        }
        
        $whereConditions = [];
        
        // Text-Suche
        if ($query) {
            $whereConditions[] = "(title LIKE ? OR content LIKE ?)";
            $searchTerm = "%{$query}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Status-Filter
        if (isset($filters['status'])) {
            $whereConditions[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        // Datum-Filter
        if (isset($filters['date_from'])) {
            $whereConditions[] = "created_at >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $whereConditions[] = "created_at <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Bulk-Operationen
     */
    public function bulkUpdateStatus($contentType, $contentIds, $status) {
        if (empty($contentIds)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($contentIds) - 1) . '?';
        $params = array_merge([$status], $contentIds);
        
        return $this->db->execute("
            UPDATE {$this->getContentTable($contentType)} 
            SET status = ? 
            WHERE id IN ({$placeholders})
        ", $params);
    }
    
    public function bulkDelete($contentType, $contentIds) {
        if (empty($contentIds)) {
            return false;
        }
        
        $placeholders = str_repeat('?,', count($contentIds) - 1) . '?';
        
        return $this->db->execute("
            DELETE FROM {$this->getContentTable($contentType)} 
            WHERE id IN ({$placeholders})
        ", $contentIds);
    }
    
    /**
     * Helper-Methoden
     */
    private function getContentTable($contentType) {
        $tables = [
            'page' => 'pages',
            'blog_post' => 'blog_posts',
            'blog' => 'blog_posts'
        ];
        
        return $tables[$contentType] ?? 'pages';
    }
    
    private function getCurrentContent($contentType, $contentId) {
        return $this->db->fetchOne("
            SELECT * FROM {$this->getContentTable($contentType)} WHERE id = ?
        ", [$contentId]);
    }
    
    private function updateContent($contentType, $contentId, $content) {
        $table = $this->getContentTable($contentType);
        $fields = array_keys($content);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        $params = array_merge(array_values($content), [$contentId]);
        
        return $this->db->execute("
            UPDATE {$table} SET {$setClause} WHERE id = ?
        ", $params);
    }
    
    private function createContent($contentType, $content) {
        $table = $this->getContentTable($contentType);
        $fields = array_keys($content);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        return $this->db->execute("
            INSERT INTO {$table} (" . implode(', ', $fields) . ") 
            VALUES ({$placeholders})
        ", array_values($content));
    }
    
    private function publishContent($contentType, $contentId) {
        return $this->db->execute("
            UPDATE {$this->getContentTable($contentType)} 
            SET status = 'published' 
            WHERE id = ?
        ", [$contentId]);
    }
    
    private function unpublishContent($contentType, $contentId) {
        return $this->db->execute("
            UPDATE {$this->getContentTable($contentType)} 
            SET status = 'draft' 
            WHERE id = ?
        ", [$contentId]);
    }
    
    private function deleteContent($contentType, $contentId) {
        return $this->db->execute("
            DELETE FROM {$this->getContentTable($contentType)} 
            WHERE id = ?
        ", [$contentId]);
    }
    
    private function createSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
    
    private function createUniqueSlug($contentType, $title) {
        $baseSlug = $this->createSlug($title);
        $slug = $baseSlug;
        $counter = 1;
        
        while ($this->slugExists($contentType, $slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    private function slugExists($contentType, $slug) {
        $result = $this->db->fetchOne("
            SELECT id FROM {$this->getContentTable($contentType)} 
            WHERE slug = ?
        ", [$slug]);
        
        return $result !== null;
    }
}
