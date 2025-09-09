-- Blog Comments Tabelle hinzufügen
-- Führe diesen SQL-Code in phpMyAdmin aus

CREATE TABLE blog_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    ip_address VARCHAR(45),
    status ENUM('pending', 'approved', 'rejected', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    INDEX idx_post_status (post_id, status),
    INDEX idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beispiel-Kommentare hinzufügen (optional)
INSERT INTO blog_comments (post_id, author_name, author_email, content, status) VALUES 
(1, 'Max Mustermann', 'max@example.com', 'Toller Blog-Beitrag! Sehr informativ und gut geschrieben. Freue mich auf weitere Artikel.', 'approved'),
(1, 'Anna Schmidt', 'anna@example.com', 'Danke für die wertvollen Tipps. Hat mir sehr weitergeholfen bei meinem Projekt.', 'approved'),
(1, 'Peter Wagner', 'peter@example.com', 'Könnt ihr vielleicht auch mal über das Thema XYZ schreiben? Das würde mich sehr interessieren.', 'pending');

-- Kommentar-Statistiken anzeigen
SELECT 
    status,
    COUNT(*) as anzahl
FROM blog_comments 
GROUP BY status;
