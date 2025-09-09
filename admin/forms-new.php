<?php
require_once "../core/init.php";

$auth = new Auth();
$auth->requireLogin();
$auth->requireAnyRole(['admin', 'editor']); // Admins und Editoren können Formulare verwalten

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
                    $formData = [
                        'name' => $_POST['name'],
                        'form_key' => $_POST['form_key'],
                        'description' => $_POST['description'],
                        'fields' => json_decode($_POST['fields'], true),
                        'settings' => json_decode($_POST['settings'], true),
                        'active' => isset($_POST['active']) ? 1 : 0,
                        'created_by' => $auth->getCurrentUser()['id']
                    ];
                    
                    $id = FormBuilder::create($formData);
                    if (!$id) {
                        $error = 'Fehler beim Erstellen des Formulars';
                    } else {
                        $message = 'Formular erfolgreich erstellt';
                        $action = 'list';
                        $forms = FormBuilder::getAll();
                    }
                    break;
                    
                case 'edit':
                    $formData = [
                        'name' => $_POST['name'],
                        'form_key' => $_POST['form_key'],
                        'description' => $_POST['description'],
                        'fields' => json_decode($_POST['fields'], true),
                        'settings' => json_decode($_POST['settings'], true),
                        'active' => isset($_POST['active']) ? 1 : 0
                    ];
                    
                    if (FormBuilder::update($id, $formData)) {
                        $message = 'Formular erfolgreich aktualisiert';
                        $action = 'list';
                        $forms = FormBuilder::getAll();
                    } else {
                        $error = 'Fehler beim Aktualisieren des Formulars';
                    }
                    break;
                    
                case 'delete':
                    if (FormBuilder::delete($id)) {
                        $message = 'Formular erfolgreich gelöscht';
                    } else {
                        $error = 'Fehler beim Löschen des Formulars';
                    }
                    $action = 'list';
                    $forms = FormBuilder::getAll();
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
        try {
            $forms = FormBuilder::getAll();
        } catch (Exception $e) {
            $forms = [];
            $error = 'Formulare konnten nicht geladen werden. Möglicherweise ist die Datenbank noch nicht eingerichtet.';
        }
        break;
        
    case 'create':
        $formData = [];
        break;
        
    case 'edit':
        if ($id) {
            $formData = FormBuilder::getById($id);
            if (!$formData) {
                $error = 'Formular nicht gefunden';
                $action = 'list';
                $forms = FormBuilder::getAll();
            }
        } else {
            $error = 'Formular-ID fehlt';
            $action = 'list';
            $forms = FormBuilder::getAll();
        }
        break;
        
    case 'submissions':
        if ($id) {
            $formData = FormBuilder::getById($id);
            $submissions = FormBuilder::getSubmissions($id);
        } else {
            $error = 'Formular-ID fehlt';
            $action = 'list';
            $forms = FormBuilder::getAll();
        }
        break;
}

$pageTitle = "Formulare";
$currentPage = "forms";
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
            <h2>Formulare verwalten</h2>
            <a href="forms.php?action=create" class="btn">Neues Formular</a>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Formular-Key</th>
                    <th>Status</th>
                    <th>Felder</th>
                    <th>Einträge</th>
                    <th>Erstellt</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forms as $form): ?>
                    <?php $fields = json_decode($form['fields'], true) ?? []; ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($form['name']) ?></strong>
                            <?php if ($form['description']): ?>
                                <br><small style="color: #666;"><?= htmlspecialchars($form['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?= htmlspecialchars($form['form_key']) ?></code>
                            <br><small style="color: #666;">[custom_form key="<?= htmlspecialchars($form['form_key']) ?>"]</small>
                        </td>
                        <td>
                            <span class="status <?= $form['active'] ? 'status-published' : 'status-draft' ?>">
                                <?= $form['active'] ? 'Aktiv' : 'Inaktiv' ?>
                            </span>
                        </td>
                        <td><?= count($fields) ?> Felder</td>
                        <td>
                            <?php 
                            try {
                                $submissionCount = FormBuilder::getSubmissionCount($form['id']);
                                echo $submissionCount;
                            } catch (Exception $e) {
                                echo '0';
                            }
                            ?>
                        </td>
                        <td><?= date('d.m.Y H:i', strtotime($form['created_at'])) ?></td>
                        <td class="actions">
                            <a href="forms.php?action=edit&id=<?= $form['id'] ?>" class="btn btn-small btn-secondary">Bearbeiten</a>
                            <a href="forms.php?action=submissions&id=<?= $form['id'] ?>" class="btn btn-small">Einträge</a>
                            <button onclick="deleteForm(<?= $form['id'] ?>, '<?= htmlspecialchars($form['name']) ?>')" class="btn btn-small btn-danger">Löschen</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($forms)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #666; font-style: italic;">
                            Noch keine Formulare erstellt
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2><?= $action === 'create' ? 'Neues Formular erstellen' : 'Formular bearbeiten' ?></h2>
            <a href="forms.php" class="btn btn-secondary">Zurück zur Übersicht</a>
        </div>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="form_key">Formular-Key (eindeutig)</label>
                    <input type="text" id="form_key" name="form_key" value="<?= htmlspecialchars($formData['form_key'] ?? '') ?>" 
                           pattern="[a-z0-9_-]+" title="Nur Kleinbuchstaben, Zahlen, _ und - erlaubt" required>
                    <small>Nur Kleinbuchstaben, Zahlen, Unterstriche und Bindestriche</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="active" value="1" <?= ($formData['active'] ?? 1) ? 'checked' : '' ?>>
                        Formular ist aktiv
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Beschreibung (optional)</label>
                <input type="text" id="description" name="description" value="<?= htmlspecialchars($formData['description'] ?? '') ?>" placeholder="Kurze Beschreibung des Formulars">
            </div>
            
            <div class="form-group">
                <label for="fields">Felder (JSON)</label>
                <textarea id="fields" name="fields" rows="10" required><?= htmlspecialchars(json_encode(json_decode($formData['fields'] ?? '[]', true), JSON_PRETTY_PRINT)) ?></textarea>
                <small>JSON-Format für Formularfelder. Beispiel: [{"name":"email","label":"E-Mail","type":"email","required":true}]</small>
            </div>
            
            <div class="form-group">
                <label for="settings">Einstellungen (JSON)</label>
                <textarea id="settings" name="settings" rows="6"><?= htmlspecialchars(json_encode(json_decode($formData['settings'] ?? '{}', true), JSON_PRETTY_PRINT)) ?></textarea>
                <small>JSON-Format für Formulareinstellungen. Z.B. {"send_email":true,"email_to":"admin@example.com"}</small>
            </div>
            
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn"><?= $action === 'create' ? 'Formular erstellen' : 'Änderungen speichern' ?></button>
                <a href="forms.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>

<?php elseif ($action === 'submissions'): ?>
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2>Einträge für "<?= htmlspecialchars($formData['name']) ?>"</h2>
            <a href="forms.php" class="btn btn-secondary">Zurück zur Übersicht</a>
        </div>
        
        <?php if (!empty($submissions)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Daten</th>
                        <th>IP-Adresse</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= date('d.m.Y H:i', strtotime($submission['created_at'])) ?></td>
                            <td>
                                <?php 
                                $data = json_decode($submission['data'], true);
                                foreach ($data as $key => $value): 
                                ?>
                                    <strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars($value) ?><br>
                                <?php endforeach; ?>
                            </td>
                            <td><?= htmlspecialchars($submission['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666; font-style: italic;">
                Noch keine Einträge für dieses Formular vorhanden.
            </p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Delete confirmation modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border-radius: 8px; max-width: 400px; width: 90%;">
        <h3>Formular löschen</h3>
        <p>Möchten Sie das Formular <strong id="deleteFormName"></strong> wirklich löschen?</p>
        <p style="color: #dc3545;">Diese Aktion kann nicht rückgängig gemacht werden und löscht auch alle Einträge.</p>
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
function deleteForm(id, name) {
    document.getElementById('deleteFormName').textContent = name;
    document.getElementById('deleteForm').action = 'forms.php?action=delete&id=' + id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Auto-generate form key from name
document.getElementById('name')?.addEventListener('input', function() {
    if (document.getElementById('form_key').value === '') {
        const key = this.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '_')
            .replace(/-+/g, '_');
        document.getElementById('form_key').value = key;
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
