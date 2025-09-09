-- Blog Categories & Tags System
-- Führe diesen SQL-Code in phpMyAdmin aus

-- Blog Categories Tabelle
CREATE TABLE blog_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog Tags Tabelle
CREATE TABLE blog_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zwischentabelle: Blog Posts <-> Categories (Many-to-Many)
CREATE TABLE blog_post_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    category_id INT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_category (post_id, category_id),
    INDEX idx_post_id (post_id),
    INDEX idx_category_id (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Zwischentabelle: Blog Posts <-> Tags (Many-to-Many)
CREATE TABLE blog_post_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_tag (post_id, tag_id),
    INDEX idx_post_id (post_id),
    INDEX idx_tag_id (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beispiel-Kategorien hinzufügen
INSERT INTO blog_categories (name, slug, description) VALUES 
('Technologie', 'technologie', 'Artikel über neue Technologien, Software und digitale Trends'),
('Lifestyle', 'lifestyle', 'Tipps und Artikel rund um Lifestyle, Gesundheit und Wohlbefinden'),
('Business', 'business', 'Geschäftstipps, Unternehmensführung und Wirtschaftsthemen'),
('Tutorial', 'tutorial', 'Schritt-für-Schritt Anleitungen und How-To Artikel'),
('News', 'news', 'Aktuelle Nachrichten und Neuigkeiten');

-- Beispiel-Tags hinzufügen
INSERT INTO blog_tags (name, slug) VALUES 
('PHP', 'php'),
('JavaScript', 'javascript'),
('CSS', 'css'),
('HTML', 'html'),
('MySQL', 'mysql'),
('Tutorial', 'tutorial'),
('Anfänger', 'anfaenger'),
('Fortgeschritten', 'fortgeschritten'),
('Webentwicklung', 'webentwicklung'),
('CMS', 'cms'),
('Open Source', 'open-source'),
('Performance', 'performance'),
('Sicherheit', 'sicherheit'),
('SEO', 'seo'),
('Responsive Design', 'responsive-design');

-- Beispiel-Zuordnungen (falls bereits Blog-Posts vorhanden)
-- Post 1 zu Kategorien und Tags zuordnen
INSERT IGNORE INTO blog_post_categories (post_id, category_id) VALUES 
(1, 1), -- Post 1 -> Technologie
(1, 4); -- Post 1 -> Tutorial

INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES 
(1, 1),  -- Post 1 -> PHP
(1, 6),  -- Post 1 -> Tutorial
(1, 10), -- Post 1 -> CMS
(1, 9);  -- Post 1 -> Webentwicklung

-- Statistiken anzeigen
SELECT 'Categories' as type, COUNT(*) as count FROM blog_categories
UNION ALL
SELECT 'Tags' as type, COUNT(*) as count FROM blog_tags
UNION ALL
SELECT 'Post-Category Relations' as type, COUNT(*) as count FROM blog_post_categories
UNION ALL
SELECT 'Post-Tag Relations' as type, COUNT(*) as count FROM blog_post_tags;
