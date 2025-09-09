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
                    $fields = [];
                    if (!empty($_POST['fields'])) {
                        foreach ($_POST['fields'] as $fieldData) {
                            if (!empty($fieldData['name']) && !empty($fieldData['type'])) {
                                $field = [
                                    'type' => $fieldData['type'],
                                    'name' => $fieldData['name'],
                                    'label' => $fieldData['label'],
                                    'required' => !empty($fieldData['required']),
                                    'placeholder' => $fieldData['placeholder'] ?? ''
                                ];
                                
                                if (!empty($fieldData['help'])) $field['help'] = $fieldData['help'];
                                if (!empty($fieldData['options'])) {
                                    $field['options'] = array_filter(explode("\n", $fieldData['options']));
                                }
                                if (!empty($fieldData['rows'])) $field['rows'] = (int)$fieldData['rows'];
                                if (!empty($fieldData['accept'])) $field['accept'] = $fieldData['accept'];
                                
                                $fields[] = $field;
                            }
                        }
                    }
                    
                    $settings = [
                        'title' => $_POST['form_title'] ?? '',
                        'description' => $_POST['form_description'] ?? '',
                        'submit_text' => $_POST['submit_text'] ?? 'Absenden',
                        'success_message' => $_POST['success_message'] ?? 'Erfolgreich gesendet!',
                        'email_to' => $_POST['email_to'] ?? '',
                        'email_subject' => $_POST['email_subject'] ?? '',
                        'email_from' => $_POST['email_from'] ?? ''
                    ];
                    
                    $formData = [
                        'name' => $_POST['name'],
                        'form_key' => $_POST['form_key'],
                        'description' => $_POST['description'],
                        'fields' => $fields,
                        'settings' => $settings,
                        'active' => isset($_POST['active']) ? 1 : 0,
                        'created_by' => $auth->getCurrentUser()['id']
                    ];
                    
                    FormBuilder::create($formData);
                    $message = 'Formular erfolgreich erstellt.';
                    $action = 'list';
                    break;
                    
                case 'edit':
                    if (!$id) {
                        $error = 'Formular-ID fehlt';
                        break;
                    }
                    
                    // Same as create but update
                    $fields = [];
                    if (!empty($_POST['fields'])) {
                        foreach ($_POST['fields'] as $fieldData) {
                            if (!empty($fieldData['name']) && !empty($fieldData['type'])) {
                                $field = [
                                    'type' => $fieldData['type'],
                                    'name' => $fieldData['name'],
                                    'label' => $fieldData['label'],
                                    'required' => !empty($fieldData['required']),
                                    'placeholder' => $fieldData['placeholder'] ?? ''
                                ];
                                
                                if (!empty($fieldData['help'])) $field['help'] = $fieldData['help'];
                                if (!empty($fieldData['options'])) {
                                    $field['options'] = array_filter(explode("\n", $fieldData['options']));
                                }
                                if (!empty($fieldData['rows'])) $field['rows'] = (int)$fieldData['rows'];
                                if (!empty($fieldData['accept'])) $field['accept'] = $fieldData['accept'];
                                
                                $fields[] = $field;
                            }
                        }
                    }
                    
                    $settings = [
                        'title' => $_POST['form_title'] ?? '',
                        'description' => $_POST['form_description'] ?? '',
                        'submit_text' => $_POST['submit_text'] ?? 'Absenden',
                        'success_message' => $_POST['success_message'] ?? 'Erfolgreich gesendet!',
                        'email_to' => $_POST['email_to'] ?? '',
                        'email_subject' => $_POST['email_subject'] ?? '',
                        'email_from' => $_POST['email_from'] ?? ''
                    ];
                    
                    $formData = [
                        'name' => $_POST['name'],
                        'form_key' => $_POST['form_key'],
                        'description' => $_POST['description'],
                        'fields' => $fields,
                        'settings' => $settings,
                        'active' => isset($_POST['active']) ? 1 : 0
                    ];
                    
                    FormBuilder::update($id, $formData);
                    $message = 'Formular erfolgreich aktualisiert.';
                    $action = 'list';
                    break;
                    
                case 'delete':
                    if (!$id) {
                        $error = 'Formular-ID fehlt';
                        break;
                    }
                    
                    FormBuilder::delete($id);
                    $message = 'Formular erfolgreich gelöscht.';
                    $action = 'list';
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get data for current action
$forms = [];
$formData = null;
$availableTypes = FormBuilder::getFieldTypes();
$submissions = [];

switch ($action) {
    case 'list':
        $forms = FormBuilder::getAll();
        break;
        
    case 'create':
        // Nothing special needed
        break;
        
    case 'edit':
        if ($id) {
            $formData = FormBuilder::getById($id);
            if (!$formData) {
                $error = 'Formular nicht gefunden';
                $action = 'list';
                $forms = FormBuilder::getAll();
            } else {
                // Parse JSON fields
                $formData['fields'] = json_decode($formData['fields'], true) ?: [];
                $formData['settings'] = json_decode($formData['settings'], true) ?: [];
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
            if ($formData) {
                $submissions = FormBuilder::getSubmissions($id);
            } else {
                $error = 'Formular nicht gefunden';
                $action = 'list';
                $forms = FormBuilder::getAll();
            }
        }
        break;
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulare-Builder - Baukasten CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .field-editor {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .field-editor h6 {
            margin: 0 0 10px 0;
            padding: 5px 10px;
            background: #007cba;
            color: white;
            border-radius: 3px;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        .shortcode-example {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            margin-top: 10px;
        }
        .form-preview {
            border: 1px solid #ddd;
            padding: 20px;
            background: white;
            border-radius: 5px;
            margin-top: 20px;
        }
        .drag-handle {
            cursor: move;
            color: #666;
        }
        .submissions-table {
            font-size: 0.9em;
        }
        .submission-data {
            max-width: 300px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-cogs"></i> Baukasten CMS
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="../public/index.php" target="_blank">Website ansehen</a>
                <a class="nav-link" href="logout.php">Logout (<?= htmlspecialchars($currentUser['username']) ?>)</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="pages.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt"></i> Seiten
                    </a>
                    <a href="blog.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-blog"></i> Blog
                    </a>
                    <a href="content-blocks.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-cubes"></i> Content-Blöcke
                    </a>
                    <a href="forms.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-wpforms"></i> Formulare
                    </a>
                    <?php if ($auth->canManageComments()): ?>
                    <a href="comments.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-comments"></i> Kommentare
                    </a>
                    <?php endif; ?>
                    <a href="media.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-images"></i> Medien
                    </a>
                    <?php if ($auth->canManageUsers()): ?>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Benutzer
                    </a>
                    <?php endif; ?>
                    <?php if ($auth->canManageSystem()): ?>
                    <a href="seo.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-search"></i> SEO
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-9">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($action === 'list'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Formulare-Builder</h2>
                        <a href="?action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Neues Formular
                        </a>
                    </div>

                    <div class="alert alert-info">
                        <strong>Info:</strong> Erstellen Sie benutzerdefinierte Formulare mit dem Drag & Drop Builder. 
                        Verwenden Sie den Shortcode <code>[custom_form key="ihr_key"]</code> in Seiten oder Blog-Posts.
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Form-Key</th>
                                            <th>Felder</th>
                                            <th>Status</th>
                                            <th>Erstellt</th>
                                            <th>Aktionen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($forms as $form): ?>
                                            <?php 
                                            $fields = json_decode($form['fields'], true);
                                            $fieldCount = is_array($fields) ? count($fields) : 0;
                                            ?>
                                            <tr>
                                                <td><?= $form['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($form['name']) ?></strong>
                                                    <?php if ($form['description']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($form['description']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code><?= htmlspecialchars($form['form_key']) ?></code>
                                                    <br><small class="text-muted">
                                                        [custom_form key="<?= htmlspecialchars($form['form_key']) ?>"]
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $fieldCount ?> Felder</span>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $form['active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $form['active'] ? 'Aktiv' : 'Inaktiv' ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d.m.Y H:i', strtotime($form['created_at'])) ?></td>
                                                <td>
                                                    <a href="?action=edit&id=<?= $form['id'] ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Bearbeiten">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=submissions&id=<?= $form['id'] ?>" 
                                                       class="btn btn-sm btn-outline-info" title="Übermittlungen">
                                                        <i class="fas fa-inbox"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                            onclick="deleteForm(<?= $form['id'] ?>, '<?= htmlspecialchars($form['name']) ?>')" title="Löschen">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($action === 'submissions'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2>Formular-Übermittlungen: <?= htmlspecialchars($formData['name']) ?></h2>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Zurück
                        </a>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Übermittlungen (<?= count($submissions) ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($submissions)): ?>
                                <p class="text-muted">Noch keine Übermittlungen für dieses Formular.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped submissions-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Datum</th>
                                                <th>IP-Adresse</th>
                                                <th>Daten</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($submissions as $submission): ?>
                                                <?php 
                                                $data = json_decode($submission['data'], true);
                                                $dataText = '';
                                                if ($data) {
                                                    foreach ($data as $key => $value) {
                                                        if (!in_array($key, ['form_submission', 'form_key', 'csrf_token'])) {
                                                            $dataText .= ucfirst($key) . ": " . $value . "\n";
                                                        }
                                                    }
                                                }
                                                ?>
                                                <tr>
                                                    <td><?= $submission['id'] ?></td>
                                                    <td><?= date('d.m.Y H:i', strtotime($submission['created_at'])) ?></td>
                                                    <td><?= htmlspecialchars($submission['ip_address']) ?></td>
                                                    <td>
                                                        <div class="submission-data"><?= htmlspecialchars($dataText) ?></div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($action === 'create' || $action === 'edit'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><?= $action === 'create' ? 'Neues Formular erstellen' : 'Formular bearbeiten' ?></h2>
                        <a href="?action=list" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Zurück
                        </a>
                    </div>

                    <form method="POST" id="formBuilderForm">
                        <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Formular-Grundeinstellungen</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="name">Formular-Name</label>
                                                    <input type="text" class="form-control" id="name" name="name" 
                                                           value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group mb-3">
                                                    <label for="form_key">Form-Key (eindeutig)</label>
                                                    <input type="text" class="form-control" id="form_key" name="form_key" 
                                                           value="<?= htmlspecialchars($formData['form_key'] ?? '') ?>" 
                                                           pattern="[a-z0-9_-]+" title="Nur Kleinbuchstaben, Zahlen, _ und - erlaubt" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="description">Beschreibung</label>
                                            <input type="text" class="form-control" id="description" name="description" 
                                                   value="<?= htmlspecialchars($formData['description'] ?? '') ?>">
                                        </div>

                                        <div class="form-check mb-3">
                                            <input type="checkbox" class="form-check-input" id="active" name="active" 
                                                   value="1" <?= ($formData['active'] ?? 1) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="active">
                                                Formular aktiv
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="card mt-3">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5>Formular-Felder</h5>
                                        <button type="button" class="btn btn-sm btn-primary" onclick="addField()">
                                            <i class="fas fa-plus"></i> Feld hinzufügen
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div id="fieldsContainer">
                                            <?php if (!empty($formData['fields'])): ?>
                                                <?php foreach ($formData['fields'] as $index => $field): ?>
                                                    <?= renderFieldEditor($index, $field, $availableTypes) ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Darstellung & E-Mail</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group mb-3">
                                            <label for="form_title">Formular-Titel</label>
                                            <input type="text" class="form-control" id="form_title" name="form_title" 
                                                   value="<?= htmlspecialchars($formData['settings']['title'] ?? '') ?>">
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="form_description">Beschreibung</label>
                                            <textarea class="form-control" id="form_description" name="form_description" rows="3"><?= htmlspecialchars($formData['settings']['description'] ?? '') ?></textarea>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="submit_text">Submit-Button Text</label>
                                            <input type="text" class="form-control" id="submit_text" name="submit_text" 
                                                   value="<?= htmlspecialchars($formData['settings']['submit_text'] ?? 'Absenden') ?>">
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="success_message">Erfolgsnachricht</label>
                                            <textarea class="form-control" id="success_message" name="success_message" rows="2"><?= htmlspecialchars($formData['settings']['success_message'] ?? 'Erfolgreich gesendet!') ?></textarea>
                                        </div>

                                        <hr>

                                        <div class="form-group mb-3">
                                            <label for="email_to">E-Mail an</label>
                                            <input type="email" class="form-control" id="email_to" name="email_to" 
                                                   value="<?= htmlspecialchars($formData['settings']['email_to'] ?? '') ?>"
                                                   placeholder="admin@example.com">
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="email_subject">E-Mail Betreff</label>
                                            <input type="text" class="form-control" id="email_subject" name="email_subject" 
                                                   value="<?= htmlspecialchars($formData['settings']['email_subject'] ?? '') ?>"
                                                   placeholder="Neue Formular-Übermittlung">
                                        </div>

                                        <div class="form-group mb-3">
                                            <label for="email_from">E-Mail von</label>
                                            <input type="email" class="form-control" id="email_from" name="email_from" 
                                                   value="<?= htmlspecialchars($formData['settings']['email_from'] ?? '') ?>"
                                                   placeholder="noreply@example.com">
                                        </div>
                                    </div>
                                </div>

                                <?php if ($action === 'edit' && $formData): ?>
                                    <div class="shortcode-example mt-3">
                                        <strong>Shortcode:</strong><br>
                                        [custom_form key="<?= htmlspecialchars($formData['form_key']) ?>"]
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> 
                                <?= $action === 'create' ? 'Erstellen' : 'Aktualisieren' ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Formular löschen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Möchten Sie das Formular <strong id="deleteFormName"></strong> wirklich löschen?</p>
                    <p class="text-danger">Diese Aktion löscht auch alle Übermittlungen und kann nicht rückgängig gemacht werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                    <form method="POST" style="display: inline;" id="deleteForm">
                        <input type="hidden" name="csrf_token" value="<?= $auth->generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-danger">Löschen</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let fieldCounter = <?= !empty($formData['fields']) ? count($formData['fields']) : 0 ?>;
        
        function deleteForm(id, name) {
            document.getElementById('deleteFormName').textContent = name;
            document.getElementById('deleteForm').action = '?action=delete&id=' + id;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        
        function addField() {
            const container = document.getElementById('fieldsContainer');
            const fieldHtml = `<?= addslashes(renderFieldEditor('${fieldCounter}', [], $availableTypes)) ?>`.replace(/\${fieldCounter}/g, fieldCounter);
            container.insertAdjacentHTML('beforeend', fieldHtml);
            fieldCounter++;
        }
        
        function removeField(index) {
            const fieldElement = document.getElementById('field_' + index);
            if (fieldElement) {
                fieldElement.remove();
            }
        }
        
        function toggleFieldOptions(index, type) {
            const optionsDiv = document.getElementById('field_options_' + index);
            const rowsDiv = document.getElementById('field_rows_' + index);
            const acceptDiv = document.getElementById('field_accept_' + index);
            
            // Hide all optional fields first
            if (optionsDiv) optionsDiv.style.display = 'none';
            if (rowsDiv) rowsDiv.style.display = 'none';
            if (acceptDiv) acceptDiv.style.display = 'none';
            
            // Show relevant fields
            if (type === 'select' || type === 'radio') {
                if (optionsDiv) optionsDiv.style.display = 'block';
            }
            if (type === 'textarea') {
                if (rowsDiv) rowsDiv.style.display = 'block';
            }
            if (type === 'file') {
                if (acceptDiv) acceptDiv.style.display = 'block';
            }
        }
        
        // Auto-generate form key from name
        document.getElementById('name').addEventListener('input', function() {
            if (document.getElementById('form_key').value === '') {
                const key = this.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '_')
                    .replace(/-+/g, '_');
                document.getElementById('form_key').value = key;
            }
        });
    </script>
</body>
</html>

<?php
function renderFieldEditor($index, $field = [], $availableTypes = []) {
    ob_start();
    ?>
    <div class="field-editor" id="field_<?= $index ?>">
        <h6>
            <span class="drag-handle"><i class="fas fa-grip-vertical"></i></span>
            Feld #<?= $index + 1 ?>
            <button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="removeField(<?= $index ?>)">
                <i class="fas fa-trash"></i>
            </button>
        </h6>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group mb-2">
                    <label>Feldtyp</label>
                    <select class="form-control" name="fields[<?= $index ?>][type]" onchange="toggleFieldOptions(<?= $index ?>, this.value)" required>
                        <option value="">Wählen...</option>
                        <?php foreach ($availableTypes as $typeKey => $typeName): ?>
                            <option value="<?= $typeKey ?>" <?= ($field['type'] ?? '') === $typeKey ? 'selected' : '' ?>>
                                <?= htmlspecialchars($typeName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group mb-2">
                    <label>Feldname (technisch)</label>
                    <input type="text" class="form-control" name="fields[<?= $index ?>][name]" 
                           value="<?= htmlspecialchars($field['name'] ?? '') ?>" 
                           pattern="[a-z0-9_]+" title="Nur Kleinbuchstaben, Zahlen und _" required>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group mb-2">
                    <label>Label</label>
                    <input type="text" class="form-control" name="fields[<?= $index ?>][label]" 
                           value="<?= htmlspecialchars($field['label'] ?? '') ?>" required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-2">
                    <label>Platzhalter</label>
                    <input type="text" class="form-control" name="fields[<?= $index ?>][placeholder]" 
                           value="<?= htmlspecialchars($field['placeholder'] ?? '') ?>">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group mb-2">
                    <label>Hilfetext</label>
                    <input type="text" class="form-control" name="fields[<?= $index ?>][help]" 
                           value="<?= htmlspecialchars($field['help'] ?? '') ?>">
                </div>
            </div>
        </div>
        
        <div class="form-check">
            <input type="checkbox" class="form-check-input" name="fields[<?= $index ?>][required]" 
                   value="1" <?= !empty($field['required']) ? 'checked' : '' ?>>
            <label class="form-check-label">Pflichtfeld</label>
        </div>
        
        <!-- Field-specific options -->
        <div id="field_options_<?= $index ?>" style="display: <?= in_array($field['type'] ?? '', ['select', 'radio']) ? 'block' : 'none' ?>">
            <div class="form-group mt-2">
                <label>Optionen (eine pro Zeile)</label>
                <textarea class="form-control" name="fields[<?= $index ?>][options]" rows="3"><?= !empty($field['options']) ? implode("\n", $field['options']) : '' ?></textarea>
            </div>
        </div>
        
        <div id="field_rows_<?= $index ?>" style="display: <?= ($field['type'] ?? '') === 'textarea' ? 'block' : 'none' ?>">
            <div class="form-group mt-2">
                <label>Zeilen</label>
                <input type="number" class="form-control" name="fields[<?= $index ?>][rows]" 
                       value="<?= $field['rows'] ?? 4 ?>" min="2" max="20">
            </div>
        </div>
        
        <div id="field_accept_<?= $index ?>" style="display: <?= ($field['type'] ?? '') === 'file' ? 'block' : 'none' ?>">
            <div class="form-group mt-2">
                <label>Erlaubte Dateitypen</label>
                <input type="text" class="form-control" name="fields[<?= $index ?>][accept]" 
                       value="<?= htmlspecialchars($field['accept'] ?? '') ?>" 
                       placeholder=".pdf,.doc,.docx">
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
