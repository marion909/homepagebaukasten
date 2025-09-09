<?php
/**
 * Settings Backup und Restore Funktionalität
 */

if (!defined('ABSPATH')) {
    exit;
}

class SettingsBackup {
    private $db;
    private $backupDir;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->backupDir = dirname(__DIR__) . '/backups/settings';
        
        // Backup-Verzeichnis erstellen falls es nicht existiert
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Erstellt ein Backup aller Einstellungen
     * @return string|false Pfad zur Backup-Datei oder false bei Fehler
     */
    public function createBackup() {
        try {
            $settings = $this->db->fetchAll("SELECT * FROM settings ORDER BY setting_key");
            
            $backup = [
                'created_at' => date('Y-m-d H:i:s'),
                'version' => '1.0',
                'settings_count' => count($settings),
                'settings' => $settings
            ];
            
            $filename = 'settings_backup_' . date('Y-m-d_H-i-s') . '.json';
            $filepath = $this->backupDir . '/' . $filename;
            
            if (file_put_contents($filepath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                return $filepath;
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Stellt Einstellungen aus einem Backup wieder her
     * @param string $backupFile Pfad zur Backup-Datei
     * @return bool True bei Erfolg, False bei Fehler
     */
    public function restoreBackup($backupFile) {
        try {
            if (!file_exists($backupFile)) {
                return false;
            }
            
            $backupData = json_decode(file_get_contents($backupFile), true);
            
            if (!$backupData || !isset($backupData['settings'])) {
                return false;
            }
            
            // Beginne Transaktion
            $this->db->query("START TRANSACTION");
            
            // Lösche alte Einstellungen
            $this->db->query("DELETE FROM settings");
            
            // Stelle Einstellungen wieder her
            foreach ($backupData['settings'] as $setting) {
                $this->db->query(
                    "INSERT INTO settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, ?, ?)",
                    [$setting['setting_key'], $setting['setting_value'], $setting['created_at'], $setting['updated_at']]
                );
            }
            
            // Bestätige Transaktion
            $this->db->query("COMMIT");
            
            // Aktualisiere Settings-Cache
            Settings::getInstance()->refresh();
            
            return true;
        } catch (Exception $e) {
            // Rollback bei Fehler
            $this->db->query("ROLLBACK");
            return false;
        }
    }
    
    /**
     * Listet alle verfügbaren Backups auf
     * @return array Array mit Backup-Informationen
     */
    public function listBackups() {
        $backups = [];
        
        if (!is_dir($this->backupDir)) {
            return $backups;
        }
        
        $files = glob($this->backupDir . '/settings_backup_*.json');
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'created_at' => $data['created_at'] ?? 'Unbekannt',
                'settings_count' => $data['settings_count'] ?? 0,
                'filesize' => filesize($file),
                'filesize_human' => $this->formatFileSize(filesize($file))
            ];
        }
        
        // Sortiere nach Erstellungsdatum (neuste zuerst)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }
    
    /**
     * Löscht ein Backup
     * @param string $filename Name der Backup-Datei
     * @return bool True bei Erfolg, False bei Fehler
     */
    public function deleteBackup($filename) {
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (file_exists($filepath) && strpos($filename, 'settings_backup_') === 0) {
            return unlink($filepath);
        }
        
        return false;
    }
    
    /**
     * Formatiert Dateigröße in lesbares Format
     * @param int $size Dateigröße in Bytes
     * @return string Formatierte Dateigröße
     */
    private function formatFileSize($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
    
    /**
     * Automatische Bereinigung alter Backups
     * @param int $keepCount Anzahl der zu behaltenden Backups
     */
    public function cleanupOldBackups($keepCount = 10) {
        $backups = $this->listBackups();
        
        if (count($backups) > $keepCount) {
            $toDelete = array_slice($backups, $keepCount);
            
            foreach ($toDelete as $backup) {
                $this->deleteBackup($backup['filename']);
            }
        }
    }
}
