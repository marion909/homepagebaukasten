-- Homepage Baukasten CMS - Vollständige Datenbankinstallation
-- Version: 2.0
-- Datum: September 2025

-- Datenbank erstellen (optional - auskommentiert da bereits vorhanden)
-- CREATE DATABASE IF NOT EXISTS sql_baukasten_ne CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE sql_baukasten_ne;

-- =============================================
-- BENUTZER-SYSTEM
-- =============================================

-- Benutzer Tabelle
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor') DEFAULT 'editor',
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SEITEN-SYSTEM
-- =============================================

-- Seiten Tabelle
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    meta_description TEXT,
    meta_keywords TEXT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_sort_order (sort_order),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- BLOG-SYSTEM
-- =============================================

-- Blog Posts Tabelle
CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content LONGTEXT,
    featured_image VARCHAR(500),
    status ENUM('draft', 'published') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Kategorien Tabelle
CREATE TABLE IF NOT EXISTS blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Tags Tabelle
CREATE TABLE IF NOT EXISTS blog_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Post Kategorien Zuordnung (Many-to-Many)
CREATE TABLE IF NOT EXISTS blog_post_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    category_id INT NOT NULL,
    UNIQUE KEY unique_post_category (post_id, category_id),
    INDEX idx_post_id (post_id),
    INDEX idx_category_id (category_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Post Tags Zuordnung (Many-to-Many)
CREATE TABLE IF NOT EXISTS blog_post_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    UNIQUE KEY unique_post_tag (post_id, tag_id),
    INDEX idx_post_id (post_id),
    INDEX idx_tag_id (tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Kommentare Tabelle
CREATE TABLE IF NOT EXISTS blog_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(100) NOT NULL,
    author_website VARCHAR(255),
    content TEXT NOT NULL,
    ip_address VARCHAR(45),
    status ENUM('pending', 'approved', 'rejected', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_post_status (post_id, status),
    INDEX idx_status_created (status, created_at),
    INDEX idx_post_id (post_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- MEDIEN-SYSTEM
-- =============================================

-- Medien Tabelle
CREATE TABLE IF NOT EXISTS media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    INDEX idx_mime_type (mime_type),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- FORMULAR-SYSTEM
-- =============================================

-- Kontakt-Formular Einträge
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Custom Forms Tabelle
CREATE TABLE IF NOT EXISTS custom_forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    form_key VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    fields JSON NOT NULL,
    settings JSON,
    active BOOLEAN DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_form_key (form_key),
    INDEX idx_active (active),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Form Submissions Tabelle
CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    data JSON NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_form_id (form_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (form_id) REFERENCES custom_forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- CONTENT-BLOCKS SYSTEM
-- =============================================

-- Content Blocks Tabelle
CREATE TABLE IF NOT EXISTS content_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    block_key VARCHAR(100) UNIQUE NOT NULL,
    content LONGTEXT,
    description TEXT,
    type ENUM('html', 'text', 'css', 'javascript') DEFAULT 'html',
    active BOOLEAN DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_block_key (block_key),
    INDEX idx_type (type),
    INDEX idx_active (active),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- EINSTELLUNGEN-SYSTEM
-- =============================================

-- Settings Tabelle
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- NAVIGATION-SYSTEM
-- =============================================

-- Navigation Menüs Tabelle
CREATE TABLE IF NOT EXISTS navigation_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(50) NOT NULL,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_location (location),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Navigation Items Tabelle
CREATE TABLE IF NOT EXISTS navigation_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(500) NOT NULL,
    target VARCHAR(20) DEFAULT '_self',
    sort_order INT DEFAULT 0,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_menu_id (menu_id),
    INDEX idx_sort_order (sort_order),
    INDEX idx_parent_id (parent_id),
    FOREIGN KEY (menu_id) REFERENCES navigation_menus(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES navigation_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- STANDARD-EINSTELLUNGEN
-- =============================================

-- Grundlegende Systemeinstellungen einfügen
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
-- Website Grundeinstellungen
('site_title', 'Homepage Baukasten'),
('site_description', 'Ein professionelles Content Management System'),
('site_keywords', 'cms, website, content management, homepage baukasten'),
('admin_email', 'admin@example.com'),
('contact_email', 'kontakt@example.com'),
('contact_phone', '+49 123 456789'),
('contact_address', 'Musterstraße 1, 12345 Musterstadt'),
('theme', 'default'),
('timezone', 'Europe/Berlin'),
('language', 'de'),
('items_per_page', '10'),

-- Benutzer-Einstellungen
('allow_registration', '1'),
('require_email_verification', '0'),

-- Sicherheitseinstellungen
('session_timeout', '3600'),
('max_login_attempts', '5'),
('lockout_duration', '900'),
('force_ssl', '0'),
('enable_2fa', '0'),

-- Social Media URLs (leer)
('facebook_url', ''),
('twitter_url', ''),
('instagram_url', ''),
('linkedin_url', ''),
('youtube_url', ''),
('github_url', ''),
('discord_url', ''),
('twitch_url', ''),

-- SEO Einstellungen
('google_analytics_id', ''),
('google_tag_manager_id', ''),
('meta_robots', 'index,follow'),
('sitemap_enabled', '1'),
('rss_enabled', '1'),

-- Performance Einstellungen
('cache_enabled', '1'),
('cache_lifetime', '3600'),
('gzip_compression', '1'),

-- Wartungsmodus
('maintenance_mode', '0'),
('maintenance_message', 'Die Website wird gerade gewartet. Bitte versuchen Sie es später erneut.'),

-- Kommentare
('comments_enabled', '1'),
('comments_moderation', '1'),
('comments_guest_allowed', '1'),

-- Upload Einstellungen
('max_upload_size', '10485760'),
('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,txt'),

-- Newsletter
('newsletter_enabled', '1'),
('newsletter_double_optin', '1');

-- =============================================
-- STANDARD NAVIGATION
-- =============================================

-- Haupt-Navigation erstellen
INSERT IGNORE INTO navigation_menus (id, name, location, active) VALUES 
(1, 'Hauptmenü', 'header', 1),
(2, 'Footer-Menü', 'footer', 1);

-- =============================================
-- INSTALLATION ABGESCHLOSSEN
-- =============================================

-- Bestätige erfolgreiche Installation
SELECT 'Homepage Baukasten CMS - Installation erfolgreich abgeschlossen!' as Status;

-- Zeige Tabellen-Statistiken
SELECT 
    TABLE_NAME as 'Tabelle',
    TABLE_ROWS as 'Einträge'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
ORDER BY TABLE_NAME;
