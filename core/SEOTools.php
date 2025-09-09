<?php
/**
 * SEO-Tools und Analyse für Homepage Baukasten CMS
 */

class SEOTools {
    private $db;
    private $settings;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->settings = Settings::getInstance();
    }
    
    /**
     * SEO-Analyse für eine Seite durchführen
     */
    public function analyzePage($pageId) {
        $page = $this->db->fetchOne("SELECT * FROM pages WHERE id = ?", [$pageId]);
        
        if (!$page) {
            return false;
        }
        
        $analysis = [
            'score' => 0,
            'issues' => [],
            'suggestions' => [],
            'meta' => $this->analyzeMetaData($page),
            'content' => $this->analyzeContent($page),
            'technical' => $this->analyzeTechnical($page),
            'links' => $this->analyzeLinks($page)
        ];
        
        // Gesamtscore berechnen
        $analysis['score'] = $this->calculateSEOScore($analysis);
        
        return $analysis;
    }
    
    /**
     * Meta-Daten analysieren
     */
    private function analyzeMetaData($page) {
        $issues = [];
        $suggestions = [];
        $score = 0;
        
        // Title-Tag
        $titleLength = strlen($page['title']);
        if (empty($page['title'])) {
            $issues[] = 'Kein Titel definiert';
        } elseif ($titleLength < 30) {
            $suggestions[] = 'Titel ist sehr kurz (unter 30 Zeichen)';
            $score += 10;
        } elseif ($titleLength > 60) {
            $issues[] = 'Titel ist zu lang (über 60 Zeichen)';
            $score += 5;
        } else {
            $score += 20;
        }
        
        // Meta Description
        $descLength = strlen($page['meta_description'] ?? '');
        if (empty($page['meta_description'])) {
            $issues[] = 'Keine Meta-Beschreibung definiert';
        } elseif ($descLength < 120) {
            $suggestions[] = 'Meta-Beschreibung ist kurz (unter 120 Zeichen)';
            $score += 10;
        } elseif ($descLength > 160) {
            $issues[] = 'Meta-Beschreibung ist zu lang (über 160 Zeichen)';
            $score += 5;
        } else {
            $score += 20;
        }
        
        // Meta Keywords
        if (!empty($page['meta_keywords'])) {
            $keywords = explode(',', $page['meta_keywords']);
            if (count($keywords) > 10) {
                $suggestions[] = 'Zu viele Meta-Keywords (über 10)';
            } else {
                $score += 5;
            }
        }
        
        return [
            'score' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'title_length' => $titleLength,
            'description_length' => $descLength
        ];
    }
    
    /**
     * Content-Analyse
     */
    private function analyzeContent($page) {
        $issues = [];
        $suggestions = [];
        $score = 0;
        
        $content = strip_tags($page['content']);
        $wordCount = str_word_count($content);
        
        // Wort-Anzahl
        if ($wordCount < 300) {
            $issues[] = "Zu wenig Content (nur {$wordCount} Wörter)";
        } elseif ($wordCount < 500) {
            $suggestions[] = "Content könnte länger sein ({$wordCount} Wörter)";
            $score += 10;
        } else {
            $score += 20;
        }
        
        // Überschriften analysieren
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', $page['content'], $headings);
        $headingCount = count($headings[0]);
        
        if ($headingCount === 0) {
            $issues[] = 'Keine Überschriften gefunden';
        } elseif ($headingCount < 3) {
            $suggestions[] = 'Mehr Überschriften verwenden für bessere Struktur';
            $score += 5;
        } else {
            $score += 15;
        }
        
        // H1-Tag prüfen
        $h1Count = substr_count(strtolower($page['content']), '<h1');
        if ($h1Count === 0) {
            $issues[] = 'Keine H1-Überschrift gefunden';
        } elseif ($h1Count > 1) {
            $issues[] = 'Mehrere H1-Überschriften gefunden';
        } else {
            $score += 10;
        }
        
        // Bilder analysieren
        preg_match_all('/<img[^>]*>/i', $page['content'], $images);
        $imageCount = count($images[0]);
        $imagesWithAlt = preg_match_all('/<img[^>]*alt=["\']/i', $page['content']);
        
        if ($imageCount > 0 && $imagesWithAlt < $imageCount) {
            $issues[] = 'Nicht alle Bilder haben Alt-Texte';
        } elseif ($imageCount > 0) {
            $score += 10;
        }
        
        return [
            'score' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'word_count' => $wordCount,
            'heading_count' => $headingCount,
            'h1_count' => $h1Count,
            'image_count' => $imageCount,
            'images_with_alt' => $imagesWithAlt
        ];
    }
    
    /**
     * Technische SEO-Analyse
     */
    private function analyzeTechnical($page) {
        $issues = [];
        $suggestions = [];
        $score = 0;
        
        // URL-Struktur
        $slug = $page['slug'];
        if (strlen($slug) > 50) {
            $suggestions[] = 'URL ist sehr lang';
        } elseif (preg_match('/[^a-z0-9\-]/', $slug)) {
            $issues[] = 'URL enthält ungültige Zeichen';
        } else {
            $score += 15;
        }
        
        // SSL-Check (wenn verfügbar)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $score += 10;
        } else {
            $suggestions[] = 'SSL/HTTPS sollte aktiviert werden';
        }
        
        // Mobile-Optimierung (vereinfacht)
        if (strpos($page['content'], 'viewport') !== false || 
            $this->settings->get('responsive_design', '1') === '1') {
            $score += 15;
        } else {
            $suggestions[] = 'Mobile-Optimierung prüfen';
        }
        
        return [
            'score' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions
        ];
    }
    
    /**
     * Link-Analyse
     */
    private function analyzeLinks($page) {
        $issues = [];
        $suggestions = [];
        $score = 0;
        
        // Interne Links
        preg_match_all('/<a[^>]*href=["\'](\/[^"\']*|[^"\']*\.html?)["\'][^>]*>/i', $page['content'], $internalLinks);
        $internalLinkCount = count($internalLinks[0]);
        
        // Externe Links
        preg_match_all('/<a[^>]*href=["\']https?:\/\/[^"\']*["\'][^>]*>/i', $page['content'], $externalLinks);
        $externalLinkCount = count($externalLinks[0]);
        
        if ($internalLinkCount === 0) {
            $suggestions[] = 'Keine internen Verlinkungen gefunden';
        } else {
            $score += 10;
        }
        
        if ($externalLinkCount > 0) {
            // Prüfen auf nofollow bei externen Links
            $nofollowCount = preg_match_all('/rel=["\'][^"\']*nofollow[^"\']*["\']/', $page['content']);
            if ($nofollowCount < $externalLinkCount) {
                $suggestions[] = 'Externe Links sollten nofollow-Attribut haben';
            } else {
                $score += 5;
            }
        }
        
        return [
            'score' => $score,
            'issues' => $issues,
            'suggestions' => $suggestions,
            'internal_links' => $internalLinkCount,
            'external_links' => $externalLinkCount
        ];
    }
    
    /**
     * Gesamtscore berechnen
     */
    private function calculateSEOScore($analysis) {
        $totalScore = 0;
        $maxScore = 0;
        
        foreach (['meta', 'content', 'technical', 'links'] as $category) {
            $totalScore += $analysis[$category]['score'];
            // Maximale mögliche Punkte pro Kategorie
            $maxScore += [
                'meta' => 45,
                'content' => 55,
                'technical' => 40,
                'links' => 15
            ][$category];
        }
        
        return round(($totalScore / $maxScore) * 100);
    }
    
    /**
     * Sitemap generieren
     */
    public function generateSitemap() {
        $pages = $this->db->fetchAll(
            "SELECT slug, updated_at FROM pages WHERE status = 'published' ORDER BY updated_at DESC"
        );
        
        $blogPosts = $this->db->fetchAll(
            "SELECT slug, updated_at FROM blog_posts WHERE status = 'published' ORDER BY updated_at DESC"
        );
        
        $baseUrl = $this->settings->get('site_url', 'http://localhost');
        
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Homepage
        $sitemap .= "  <url>\n";
        $sitemap .= "    <loc>{$baseUrl}/</loc>\n";
        $sitemap .= "    <changefreq>daily</changefreq>\n";
        $sitemap .= "    <priority>1.0</priority>\n";
        $sitemap .= "  </url>\n";
        
        // Seiten
        foreach ($pages as $page) {
            $sitemap .= "  <url>\n";
            $sitemap .= "    <loc>{$baseUrl}/{$page['slug']}</loc>\n";
            $sitemap .= "    <lastmod>" . date('Y-m-d', strtotime($page['updated_at'])) . "</lastmod>\n";
            $sitemap .= "    <changefreq>weekly</changefreq>\n";
            $sitemap .= "    <priority>0.8</priority>\n";
            $sitemap .= "  </url>\n";
        }
        
        // Blog-Posts
        foreach ($blogPosts as $post) {
            $sitemap .= "  <url>\n";
            $sitemap .= "    <loc>{$baseUrl}/blog/{$post['slug']}</loc>\n";
            $sitemap .= "    <lastmod>" . date('Y-m-d', strtotime($post['updated_at'])) . "</lastmod>\n";
            $sitemap .= "    <changefreq>monthly</changefreq>\n";
            $sitemap .= "    <priority>0.6</priority>\n";
            $sitemap .= "  </url>\n";
        }
        
        $sitemap .= '</urlset>';
        
        // Sitemap speichern
        file_put_contents(dirname(__DIR__) . '/sitemap.xml', $sitemap);
        
        return true;
    }
    
    /**
     * Robots.txt generieren
     */
    public function generateRobotsTxt() {
        $baseUrl = $this->settings->get('site_url', 'http://localhost');
        $sitemapEnabled = $this->settings->get('sitemap_enabled', '1') === '1';
        
        $robots = "User-agent: *\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /core/\n";
        $robots .= "Disallow: /install/\n";
        $robots .= "Disallow: /uploads/temp/\n";
        $robots .= "Allow: /uploads/\n";
        
        if ($sitemapEnabled) {
            $robots .= "\nSitemap: {$baseUrl}/sitemap.xml\n";
        }
        
        file_put_contents(dirname(__DIR__) . '/robots.txt', $robots);
        
        return true;
    }
    
    /**
     * Schema.org Markup generieren
     */
    public function generateSchemaMarkup($type, $data) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $type
        ];
        
        switch ($type) {
            case 'WebSite':
                $schema['name'] = $this->settings->get('site_title', 'Homepage Baukasten');
                $schema['description'] = $this->settings->get('site_description', '');
                $schema['url'] = $this->settings->get('site_url', '');
                break;
                
            case 'Article':
                $schema['headline'] = $data['title'] ?? '';
                $schema['description'] = $data['excerpt'] ?? '';
                $schema['author'] = [
                    '@type' => 'Person',
                    'name' => $data['author'] ?? 'Administrator'
                ];
                $schema['datePublished'] = date('c', strtotime($data['created_at'] ?? 'now'));
                $schema['dateModified'] = date('c', strtotime($data['updated_at'] ?? 'now'));
                break;
                
            case 'Organization':
                $schema['name'] = $this->settings->get('site_title', '');
                $schema['url'] = $this->settings->get('site_url', '');
                $contactInfo = $this->settings->getContactInfo();
                if ($contactInfo['email']) {
                    $schema['email'] = $contactInfo['email'];
                }
                if ($contactInfo['phone']) {
                    $schema['telephone'] = $contactInfo['phone'];
                }
                break;
        }
        
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * SEO-Keywords extrahieren
     */
    public function extractKeywords($text, $limit = 10) {
        // Text bereinigen
        $text = strip_tags($text);
        $text = strtolower($text);
        $text = preg_replace('/[^a-züäöß\s]/', ' ', $text);
        
        // Stoppwörter (vereinfacht)
        $stopwords = [
            'der', 'die', 'das', 'und', 'oder', 'aber', 'ist', 'sind', 'war', 'waren',
            'hat', 'haben', 'wird', 'werden', 'kann', 'können', 'soll', 'sollte',
            'ein', 'eine', 'einer', 'eines', 'einen', 'einem', 'auf', 'in', 'an',
            'bei', 'mit', 'von', 'zu', 'für', 'als', 'wie', 'über', 'unter', 'vor',
            'nach', 'zwischen', 'durch', 'um', 'ohne', 'gegen', 'bis', 'seit'
        ];
        
        // Wörter extrahieren
        $words = str_word_count($text, 1, 'äöüß');
        $words = array_filter($words, function($word) use ($stopwords) {
            return strlen($word) > 3 && !in_array($word, $stopwords);
        });
        
        // Häufigkeit zählen
        $wordCount = array_count_values($words);
        arsort($wordCount);
        
        return array_slice(array_keys($wordCount), 0, $limit);
    }
    
    /**
     * Readability Score berechnen (vereinfacht)
     */
    public function calculateReadabilityScore($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text);
        $sentences = array_filter($sentences, 'trim');
        $sentenceCount = count($sentences);
        
        $words = str_word_count($text, 1);
        $wordCount = count($words);
        
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += $this->countSyllables($word);
        }
        
        if ($sentenceCount === 0 || $wordCount === 0) {
            return 0;
        }
        
        // Flesch Reading Ease (angepasst für Deutsch)
        $score = 180 - (1.015 * ($wordCount / $sentenceCount)) - (84.6 * ($syllables / $wordCount));
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Silben zählen (vereinfacht)
     */
    private function countSyllables($word) {
        $word = strtolower($word);
        $vowels = 'aeiouäöü';
        $syllables = 0;
        $previous = false;
        
        for ($i = 0; $i < strlen($word); $i++) {
            $current = strpos($vowels, $word[$i]) !== false;
            if ($current && !$previous) {
                $syllables++;
            }
            $previous = $current;
        }
        
        return max(1, $syllables);
    }
}
