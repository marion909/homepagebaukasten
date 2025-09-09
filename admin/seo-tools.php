<?php
require_once '../config.php';
require_once '../core/db.php';
require_once '../core/auth.php';
require_once '../core/Settings.php';
require_once '../core/SEOTools.php';

// Authentifizierung prüfen
$auth = new Auth();
$auth->requireLogin();

$seoTools = new SEOTools();
$action = $_GET['action'] ?? 'overview';

// AJAX-Anfragen bearbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'analyze_page':
            $pageId = $_POST['page_id'] ?? null;
            if ($pageId) {
                $analysis = $seoTools->analyzePage($pageId);
                echo json_encode(['success' => true, 'data' => $analysis]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Keine Seiten-ID angegeben']);
            }
            exit;
            
        case 'generate_sitemap':
            try {
                $seoTools->generateSitemap();
                echo json_encode(['success' => true, 'message' => 'Sitemap erfolgreich generiert']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'generate_robots':
            try {
                $seoTools->generateRobotsTxt();
                echo json_encode(['success' => true, 'message' => 'robots.txt erfolgreich generiert']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
    }
}

// Seiten für Analyse laden
$db = Database::getInstance();
$pages = $db->fetchAll("SELECT id, title, slug, status FROM pages ORDER BY title");
$blogPosts = $db->fetchAll("SELECT id, title, slug, status FROM blog_posts ORDER BY title");

include 'header.php';
?>

<div class="seo-tools">
    <div class="page-header">
        <h1><i class="fas fa-search-plus"></i> SEO-Tools</h1>
        <p>Analysieren und optimieren Sie Ihre Webseite für Suchmaschinen</p>
    </div>
    
    <div class="seo-tabs">
        <div class="tab-nav">
            <button class="tab-btn active" data-tab="analysis">SEO-Analyse</button>
            <button class="tab-btn" data-tab="sitemap">Sitemap</button>
            <button class="tab-btn" data-tab="robots">Robots.txt</button>
            <button class="tab-btn" data-tab="schema">Schema Markup</button>
            <button class="tab-btn" data-tab="keywords">Keywords</button>
        </div>
        
        <!-- SEO-Analyse Tab -->
        <div class="tab-content active" id="analysis">
            <div class="analysis-controls">
                <div class="form-group">
                    <label for="page-select">Seite auswählen:</label>
                    <select id="page-select" class="form-control">
                        <option value="">-- Seite wählen --</option>
                        <optgroup label="Seiten">
                            <?php foreach ($pages as $page): ?>
                                <option value="<?= $page['id'] ?>" data-type="page">
                                    <?= htmlspecialchars($page['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Blog-Posts">
                            <?php foreach ($blogPosts as $post): ?>
                                <option value="<?= $post['id'] ?>" data-type="blog">
                                    <?= htmlspecialchars($post['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
                <button id="analyze-btn" class="btn btn-primary" disabled>
                    <i class="fas fa-search"></i> Analysieren
                </button>
            </div>
            
            <div id="analysis-results" class="analysis-results" style="display: none;">
                <div class="seo-score-card">
                    <div class="score-circle">
                        <div class="score-text">
                            <span id="overall-score">0</span>
                            <small>/ 100</small>
                        </div>
                    </div>
                    <div class="score-info">
                        <h3>SEO-Score</h3>
                        <p id="score-description">Analyse wird geladen...</p>
                    </div>
                </div>
                
                <div class="analysis-categories">
                    <div class="category-card">
                        <h4><i class="fas fa-tags"></i> Meta-Daten</h4>
                        <div class="category-score" id="meta-score">0/45</div>
                        <div class="category-details" id="meta-details"></div>
                    </div>
                    
                    <div class="category-card">
                        <h4><i class="fas fa-file-alt"></i> Content</h4>
                        <div class="category-score" id="content-score">0/55</div>
                        <div class="category-details" id="content-details"></div>
                    </div>
                    
                    <div class="category-card">
                        <h4><i class="fas fa-cogs"></i> Technisch</h4>
                        <div class="category-score" id="technical-score">0/40</div>
                        <div class="category-details" id="technical-details"></div>
                    </div>
                    
                    <div class="category-card">
                        <h4><i class="fas fa-link"></i> Links</h4>
                        <div class="category-score" id="links-score">0/15</div>
                        <div class="category-details" id="links-details"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sitemap Tab -->
        <div class="tab-content" id="sitemap">
            <div class="tool-section">
                <h3>XML-Sitemap</h3>
                <p>Generieren Sie eine XML-Sitemap für Suchmaschinen.</p>
                
                <div class="sitemap-info">
                    <div class="info-item">
                        <strong>Seiten:</strong> <?= count($pages) ?>
                    </div>
                    <div class="info-item">
                        <strong>Blog-Posts:</strong> <?= count($blogPosts) ?>
                    </div>
                    <div class="info-item">
                        <strong>Sitemap URL:</strong> 
                        <code><?= Settings::getInstance()->get('site_url', 'http://localhost') ?>/sitemap.xml</code>
                    </div>
                </div>
                
                <button id="generate-sitemap" class="btn btn-primary">
                    <i class="fas fa-sitemap"></i> Sitemap generieren
                </button>
                
                <div class="sitemap-preview" id="sitemap-preview" style="display: none;">
                    <h4>Aktuelle Sitemap:</h4>
                    <div class="url-list" id="sitemap-urls"></div>
                </div>
            </div>
        </div>
        
        <!-- Robots.txt Tab -->
        <div class="tab-content" id="robots">
            <div class="tool-section">
                <h3>Robots.txt</h3>
                <p>Konfigurieren Sie robots.txt für Suchmaschinen-Crawler.</p>
                
                <div class="robots-preview">
                    <h4>Aktuelle robots.txt:</h4>
                    <pre id="robots-content">User-agent: *
Disallow: /admin/
Disallow: /core/
Disallow: /install/
Disallow: /uploads/temp/
Allow: /uploads/

Sitemap: <?= Settings::getInstance()->get('site_url', 'http://localhost') ?>/sitemap.xml</pre>
                </div>
                
                <button id="generate-robots" class="btn btn-primary">
                    <i class="fas fa-robot"></i> robots.txt generieren
                </button>
            </div>
        </div>
        
        <!-- Schema Markup Tab -->
        <div class="tab-content" id="schema">
            <div class="tool-section">
                <h3>Schema.org Markup</h3>
                <p>Strukturierte Daten für bessere Suchergebnisse.</p>
                
                <div class="schema-types">
                    <div class="schema-card">
                        <h4>Website Schema</h4>
                        <p>Grundlegende Informationen über Ihre Website</p>
                        <button class="btn btn-secondary" onclick="generateSchema('WebSite')">
                            Generieren
                        </button>
                    </div>
                    
                    <div class="schema-card">
                        <h4>Organization Schema</h4>
                        <p>Informationen über Ihr Unternehmen</p>
                        <button class="btn btn-secondary" onclick="generateSchema('Organization')">
                            Generieren
                        </button>
                    </div>
                    
                    <div class="schema-card">
                        <h4>Article Schema</h4>
                        <p>Schema für Blog-Posts und Artikel</p>
                        <button class="btn btn-secondary" onclick="generateSchema('Article')">
                            Generieren
                        </button>
                    </div>
                </div>
                
                <div class="schema-output" id="schema-output" style="display: none;">
                    <h4>Generiertes Schema:</h4>
                    <pre id="schema-code"></pre>
                    <button class="btn btn-sm btn-secondary" onclick="copySchema()">
                        <i class="fas fa-copy"></i> Kopieren
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Keywords Tab -->
        <div class="tab-content" id="keywords">
            <div class="tool-section">
                <h3>Keyword-Analyse</h3>
                <p>Analysieren Sie Keywords und Lesbarkeit Ihrer Inhalte.</p>
                
                <div class="keyword-input">
                    <label for="keyword-text">Text eingeben:</label>
                    <textarea id="keyword-text" class="form-control" rows="6" 
                              placeholder="Fügen Sie hier Ihren Text ein..."></textarea>
                    <button id="analyze-keywords" class="btn btn-primary">
                        <i class="fas fa-key"></i> Keywords analysieren
                    </button>
                </div>
                
                <div class="keyword-results" id="keyword-results" style="display: none;">
                    <div class="result-section">
                        <h4>Top Keywords</h4>
                        <div id="keyword-list" class="keyword-tags"></div>
                    </div>
                    
                    <div class="result-section">
                        <h4>Lesbarkeits-Score</h4>
                        <div class="readability-score">
                            <div class="score-bar">
                                <div class="score-fill" id="readability-fill"></div>
                            </div>
                            <span id="readability-score">0</span>/100
                        </div>
                        <p id="readability-description"></p>
                    </div>
                    
                    <div class="result-section">
                        <h4>Text-Statistiken</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-value" id="word-count">0</span>
                                <span class="stat-label">Wörter</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="sentence-count">0</span>
                                <span class="stat-label">Sätze</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="paragraph-count">0</span>
                                <span class="stat-label">Absätze</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.seo-tools {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.page-header {
    text-align: center;
    margin-bottom: 40px;
}

.page-header h1 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.seo-tabs {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-nav {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-btn {
    flex: 1;
    padding: 15px 20px;
    border: none;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.tab-btn:hover {
    background: #e9ecef;
}

.tab-btn.active {
    background: white;
    color: #007bff;
    border-bottom: 2px solid #007bff;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.analysis-controls {
    display: flex;
    gap: 20px;
    align-items: end;
    margin-bottom: 30px;
}

.analysis-controls .form-group {
    flex: 1;
}

.seo-score-card {
    display: flex;
    align-items: center;
    gap: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.score-circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.score-text {
    text-align: center;
}

.score-text span {
    font-size: 2.5em;
    font-weight: bold;
}

.score-text small {
    font-size: 0.8em;
    opacity: 0.8;
}

.analysis-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.category-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border-left: 4px solid #007bff;
}

.category-card h4 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.category-score {
    font-size: 1.4em;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 15px;
}

.category-details {
    font-size: 0.9em;
}

.issue {
    color: #dc3545;
    margin-bottom: 5px;
}

.suggestion {
    color: #ffc107;
    margin-bottom: 5px;
}

.tool-section {
    max-width: 800px;
}

.sitemap-info {
    display: flex;
    gap: 30px;
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.info-item strong {
    display: block;
    color: #2c3e50;
}

.robots-preview {
    margin: 20px 0;
}

.robots-preview pre {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.schema-types {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.schema-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.schema-output {
    margin-top: 20px;
}

.schema-output pre {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    max-height: 300px;
    overflow-y: auto;
}

.keyword-input {
    margin-bottom: 30px;
}

.keyword-input textarea {
    margin-bottom: 15px;
}

.keyword-results {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.result-section {
    margin-bottom: 25px;
}

.keyword-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.keyword-tag {
    background: #007bff;
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
}

.readability-score {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
}

.score-bar {
    flex: 1;
    height: 10px;
    background: #e9ecef;
    border-radius: 5px;
    overflow: hidden;
}

.score-fill {
    height: 100%;
    background: linear-gradient(90deg, #dc3545 0%, #ffc107 50%, #28a745 100%);
    transition: width 0.5s ease;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.stat-item {
    text-align: center;
    background: white;
    padding: 15px;
    border-radius: 8px;
}

.stat-value {
    display: block;
    font-size: 1.8em;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    font-size: 0.9em;
    color: #6c757d;
}
</style>

<script>
// Tab-Navigation
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        // Alle Tabs deaktivieren
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Aktiven Tab aktivieren
        btn.classList.add('active');
        document.getElementById(btn.dataset.tab).classList.add('active');
    });
});

// SEO-Analyse
document.getElementById('page-select').addEventListener('change', function() {
    const analyzeBtn = document.getElementById('analyze-btn');
    analyzeBtn.disabled = !this.value;
});

document.getElementById('analyze-btn').addEventListener('click', async function() {
    const pageId = document.getElementById('page-select').value;
    if (!pageId) return;
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Analysiere...';
    
    try {
        const response = await fetch('seo-tools.php?action=analyze_page', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `page_id=${pageId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            displayAnalysisResults(result.data);
        } else {
            alert('Fehler bei der Analyse: ' + result.message);
        }
    } catch (error) {
        alert('Fehler bei der Analyse: ' + error.message);
    }
    
    this.disabled = false;
    this.innerHTML = '<i class="fas fa-search"></i> Analysieren';
});

function displayAnalysisResults(data) {
    document.getElementById('analysis-results').style.display = 'block';
    
    // Gesamtscore
    document.getElementById('overall-score').textContent = data.score;
    
    let scoreDescription = '';
    if (data.score >= 80) scoreDescription = 'Ausgezeichnet! Ihre SEO ist sehr gut optimiert.';
    else if (data.score >= 60) scoreDescription = 'Gut, aber es gibt noch Verbesserungsmöglichkeiten.';
    else if (data.score >= 40) scoreDescription = 'Mittelmäßig. Mehrere Bereiche benötigen Aufmerksamkeit.';
    else scoreDescription = 'Schlecht. Umfassende SEO-Optimierung erforderlich.';
    
    document.getElementById('score-description').textContent = scoreDescription;
    
    // Kategorie-Details
    displayCategoryDetails('meta', data.meta, 45);
    displayCategoryDetails('content', data.content, 55);
    displayCategoryDetails('technical', data.technical, 40);
    displayCategoryDetails('links', data.links, 15);
}

function displayCategoryDetails(category, data, maxScore) {
    document.getElementById(`${category}-score`).textContent = `${data.score}/${maxScore}`;
    
    const detailsEl = document.getElementById(`${category}-details`);
    let html = '';
    
    if (data.issues && data.issues.length > 0) {
        data.issues.forEach(issue => {
            html += `<div class="issue"><i class="fas fa-times-circle"></i> ${issue}</div>`;
        });
    }
    
    if (data.suggestions && data.suggestions.length > 0) {
        data.suggestions.forEach(suggestion => {
            html += `<div class="suggestion"><i class="fas fa-exclamation-triangle"></i> ${suggestion}</div>`;
        });
    }
    
    if (html === '') {
        html = '<div style="color: #28a745;"><i class="fas fa-check-circle"></i> Keine Probleme gefunden</div>';
    }
    
    detailsEl.innerHTML = html;
}

// Sitemap generieren
document.getElementById('generate-sitemap').addEventListener('click', async function() {
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generiere...';
    
    try {
        const response = await fetch('seo-tools.php?action=generate_sitemap', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Sitemap erfolgreich generiert!');
        } else {
            alert('Fehler: ' + result.message);
        }
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
    
    this.disabled = false;
    this.innerHTML = '<i class="fas fa-sitemap"></i> Sitemap generieren';
});

// Robots.txt generieren
document.getElementById('generate-robots').addEventListener('click', async function() {
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generiere...';
    
    try {
        const response = await fetch('seo-tools.php?action=generate_robots', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('robots.txt erfolgreich generiert!');
        } else {
            alert('Fehler: ' + result.message);
        }
    } catch (error) {
        alert('Fehler: ' + error.message);
    }
    
    this.disabled = false;
    this.innerHTML = '<i class="fas fa-robot"></i> robots.txt generieren';
});

// Schema generieren
function generateSchema(type) {
    const schemaData = {
        'WebSite': {
            '@context': 'https://schema.org',
            '@type': 'WebSite',
            'name': '<?= Settings::getInstance()->get('site_title', 'Homepage Baukasten') ?>',
            'description': '<?= Settings::getInstance()->get('site_description', '') ?>',
            'url': '<?= Settings::getInstance()->get('site_url', '') ?>'
        },
        'Organization': {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            'name': '<?= Settings::getInstance()->get('site_title', '') ?>',
            'url': '<?= Settings::getInstance()->get('site_url', '') ?>'
        },
        'Article': {
            '@context': 'https://schema.org',
            '@type': 'Article',
            'headline': 'Artikel-Titel',
            'description': 'Artikel-Beschreibung',
            'author': {
                '@type': 'Person',
                'name': 'Autor-Name'
            },
            'datePublished': new Date().toISOString(),
            'dateModified': new Date().toISOString()
        }
    };
    
    const schema = schemaData[type];
    document.getElementById('schema-code').textContent = JSON.stringify(schema, null, 2);
    document.getElementById('schema-output').style.display = 'block';
}

function copySchema() {
    const schemaCode = document.getElementById('schema-code').textContent;
    navigator.clipboard.writeText(schemaCode).then(() => {
        alert('Schema in Zwischenablage kopiert!');
    });
}

// Keyword-Analyse
document.getElementById('analyze-keywords').addEventListener('click', function() {
    const text = document.getElementById('keyword-text').value;
    if (!text.trim()) {
        alert('Bitte geben Sie Text ein.');
        return;
    }
    
    analyzeKeywords(text);
});

function analyzeKeywords(text) {
    // Einfache Keyword-Extraktion (clientseitig)
    const words = text.toLowerCase()
        .replace(/[^\w\säöüß]/g, ' ')
        .split(/\s+/)
        .filter(word => word.length > 3);
    
    const stopwords = ['dass', 'eine', 'einen', 'einer', 'eines', 'einem', 'sein', 'seine', 'haben', 'wird', 'werden', 'kann', 'können', 'auch', 'aber', 'oder', 'nicht', 'alle', 'mehr', 'sehr', 'noch', 'nur', 'wenn', 'wie', 'dann', 'schon', 'hier', 'dort', 'jetzt', 'immer', 'wieder'];
    
    const filteredWords = words.filter(word => !stopwords.includes(word));
    const wordCount = {};
    
    filteredWords.forEach(word => {
        wordCount[word] = (wordCount[word] || 0) + 1;
    });
    
    const sortedWords = Object.entries(wordCount)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 10);
    
    // Keywords anzeigen
    const keywordList = document.getElementById('keyword-list');
    keywordList.innerHTML = sortedWords
        .map(([word, count]) => `<span class="keyword-tag">${word} (${count})</span>`)
        .join('');
    
    // Lesbarkeits-Score (vereinfacht)
    const sentences = text.split(/[.!?]+/).filter(s => s.trim());
    const avgWordsPerSentence = words.length / sentences.length;
    const readabilityScore = Math.max(0, Math.min(100, 100 - (avgWordsPerSentence - 15) * 2));
    
    document.getElementById('readability-fill').style.width = readabilityScore + '%';
    document.getElementById('readability-score').textContent = Math.round(readabilityScore);
    
    let readabilityDesc = '';
    if (readabilityScore >= 80) readabilityDesc = 'Sehr gut lesbar';
    else if (readabilityScore >= 60) readabilityDesc = 'Gut lesbar';
    else if (readabilityScore >= 40) readabilityDesc = 'Mittelmäßig lesbar';
    else readabilityDesc = 'Schwer lesbar';
    
    document.getElementById('readability-description').textContent = readabilityDesc;
    
    // Statistiken
    const paragraphs = text.split(/\n\s*\n/).filter(p => p.trim());
    document.getElementById('word-count').textContent = words.length;
    document.getElementById('sentence-count').textContent = sentences.length;
    document.getElementById('paragraph-count').textContent = paragraphs.length;
    
    document.getElementById('keyword-results').style.display = 'block';
}
</script>

<?php include 'footer.php'; ?>
