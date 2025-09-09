<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();
$auth->requireAnyRole(['admin', 'editor']); // Admins und Editoren können Content-Blöcke verwalten

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (!$auth->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token';
    } else {
        try {
            switch ($action) {
                case 'create':
                    $blockData = [
                        'name' => $_POST['name'],
                        'block_key' => $_POST['block_key'],
                        'content' => $_POST['content'],
                        'description' => $_POST['description'],
                        'type' => $_POST['type'],
                        'active' => isset($_POST['active']) ? 1 : 0,
                        'created_by' => $auth->getCurrentUser()['id']
                    ];
                    
                    $id = ContentBlock::create($blockData);
                    if (!$id) {
                        $error = 'Fehler beim Erstellen des Content-Blocks';
                    } else {
                        $message = 'Content-Block erfolgreich erstellt';
                        $action = 'list';
                        $blocks = ContentBlock::getAll();
                    }
                    break;
                    
                case 'edit':
                    $blockData = [
                        'name' => $_POST['name'],
                        'block_key' => $_POST['block_key'],
                        'content' => $_POST['content'],
                        'description' => $_POST['description'],
                        'type' => $_POST['type'],
                        'active' => isset($_POST['active']) ? 1 : 0
                    ];
                    
                    if (ContentBlock::update($id, $blockData)) {
                        $message = 'Content-Block erfolgreich aktualisiert';
                        $action = 'list';
                        $blocks = ContentBlock::getAll();
                    } else {
                        $error = 'Fehler beim Aktualisieren des Content-Blocks';
                    }
                    break;
                    
                case 'delete':
                    if (ContentBlock::delete($id)) {
                        $message = 'Content-Block erfolgreich gelöscht';
                    } else {
                        $error = 'Fehler beim Löschen des Content-Blocks';
                    }
                    $action = 'list';
                    $blocks = ContentBlock::getAll();
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Load data based on action
switch ($action) {
    case 'list':
    default:
        $blocks = ContentBlock::getAll();
        break;
        
    case 'create':
        $blockData = [];
        break;
        
    case 'edit':
        if ($id) {
            $blockData = ContentBlock::getById($id);
            if (!$blockData) {
                $error = 'Content-Block nicht gefunden';
                $action = 'list';
                $blocks = ContentBlock::getAll();
            }
        } else {
            $error = 'Block-ID fehlt';
            $action = 'list';
            $blocks = ContentBlock::getAll();
        }
        break;
}

$pageTitle = "Content-Blöcke";
$currentPage = "content-blocks";
include 'header.php';
?>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Content-Blöcke verwalten</h2>
            <a href="content-blocks.php?action=create" class="btn">Neuer Content-Block</a>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Block-Key</th>
                    <th>Typ</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocks as $block): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($block['name']) ?></strong>
                            <?php if ($block['description']): ?>
                                <br><small style="color: #666;"><?= htmlspecialchars($block['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($block['block_key']) ?></code>
                            <br><small style="color: #666;">[content_block key="<?= htmlspecialchars($block['block_key']) ?>"]</small>
                        </td>
                        <td>
                            <span class="status status-<?= $block['block_type'] ?>"><?= ucfirst($block['block_type']) ?></span>
                        </td>
                        <td>
                            <span class="status <?= $block['active'] ? 'status-published' : 'status-draft' ?>">
                                <?= $block['active'] ? 'Aktiv' : 'Inaktiv' ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($block['created_at'])) ?></td>
                        <td class="actions">
                            <a href="content-blocks.php?action=edit&id=<?= $block['id'] ?>" class="btn btn-small btn-secondary">Bearbeiten</a>
                            <button onclick="deleteBlock(<?= $block['id'] ?>, '<?= htmlspecialchars($block['name']) ?>')" class="btn btn-small btn-danger">Löschen</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($blocks)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #666; font-style: italic;">
                            Noch keine Content-Blöcke erstellt
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2><?= $action === 'create' ? 'Neuen Content-Block erstellen' : 'Content-Block bearbeiten' ?></h2>
            <a href="content-blocks.php" class="btn btn-secondary">Zurück zur Übersicht</a>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($blockData['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="block_key">Block-Key (eindeutig)</label>
                    <input type="text" id="block_key" name="block_key" value="<?= htmlspecialchars($blockData['block_key'] ?? '') ?>" 
                           pattern="[a-z0-9_-]+" title="Nur Kleinbuchstaben, Zahlen, _ und - erlaubt" required>
                    <small>Nur Kleinbuchstaben, Zahlen, Unterstriche und Bindestriche</small>
                </div>
                
                <div class="form-group">
                    <label for="type">Typ</label>
                    <select id="type" name="type" required>
                        <option value="text" <?= ($blockData['block_type'] ?? '') === 'text' ? 'selected' : '' ?>>Text</option>
                        <option value="html" <?= ($blockData['block_type'] ?? '') === 'html' ? 'selected' : '' ?>>HTML</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Beschreibung (optional)</label>
                <input type="text" id="description" name="description" value="<?= htmlspecialchars($blockData['description'] ?? '') ?>" placeholder="Kurze Beschreibung des Content-Blocks">
            </div>
            
            <div class="form-group">
                <label for="content">Inhalt</label>
                <textarea id="content" name="content" rows="8" required><?= htmlspecialchars($blockData['content'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="active" value="1" <?= ($blockData['active'] ?? 1) ? 'checked' : '' ?>>
                    Content-Block ist aktiv
                </label>
            </div>
            
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn"><?= $action === 'create' ? 'Content-Block erstellen' : 'Änderungen speichern' ?></button>
                <a href="content-blocks.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Delete confirmation modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 400px; width: 90%;">
        <h3>Content-Block löschen</h3>
        <p>Möchten Sie den Content-Block <strong id="deleteBlockName"></strong> wirklich löschen?</p>
        <p style="color: #dc3545;">Diese Aktion kann nicht rückgängig gemacht werden.</p>
        <div style="margin-top: 1.5rem; text-align: right;">
            <button onclick="closeDeleteModal()" class="btn btn-secondary" style="margin-right: 0.5rem;">Abbrechen</button>
            <form method="POST" style="display: inline;" id="deleteForm">
                <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                <button type="submit" class="btn btn-danger">Löschen</button>
            </form>
        </div>
    </div>
</div>

<script>
function deleteBlock(id, name) {
    document.getElementById('deleteBlockName').textContent = name;
    document.getElementById('deleteForm').action = 'content-blocks.php?action=delete&id=' + id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Auto-generate block key from name
document.getElementById('name')?.addEventListener('input', function() {
    if (document.getElementById('block_key').value === '') {
        const key = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '_')
            .replace(/-+/g, '_');
        document.getElementById('block_key').value = key;
    }
});

// Close modal when clicking outside
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php include 'footer.php'; ?>
