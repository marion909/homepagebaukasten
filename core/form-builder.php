<?php
class FormBuilder {
    private static $db;
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    public static function getAll() {
        if (!self::$db) self::init();
        
        return self::$db->fetchAll("SELECT * FROM custom_forms ORDER BY created_at DESC");
    }
    
    public static function getById($id) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM custom_forms WHERE id = ?", [$id]);
    }
    
    public static function getByKey($key) {
        if (!self::$db) self::init();
        
        return self::$db->fetchOne("SELECT * FROM custom_forms WHERE form_key = ? AND active = 1", [$key]);
    }
    
    public static function create($data) {
        if (!self::$db) self::init();
        
        // Validate required fields
        if (empty($data['name']) || empty($data['form_key'])) {
            throw new Exception('Name und Form-Key sind erforderlich');
        }
        
        // Check if key already exists
        $existing = self::$db->fetchOne("SELECT id FROM custom_forms WHERE form_key = ?", [$data['form_key']]);
        if ($existing) {
            throw new Exception('Form-Key bereits vergeben');
        }
        
        $sql = "INSERT INTO custom_forms (name, form_key, description, fields, settings, active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['name'],
            $data['form_key'],
            $data['description'] ?? null,
            is_array($data['fields']) ? json_encode($data['fields']) : $data['fields'],
            is_array($data['settings']) ? json_encode($data['settings']) : $data['settings'],
            $data['active'] ?? 1,
            $data['created_by']
        ];
        
        self::$db->query($sql, $params);
        return self::$db->lastInsertId();
    }
    
    public static function update($id, $data) {
        if (!self::$db) self::init();
        
        $fields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        
        if (isset($data['form_key'])) {
            // Check if key is taken by another form
            $existing = self::$db->fetchOne("SELECT id FROM custom_forms WHERE form_key = ? AND id != ?", 
                                           [$data['form_key'], $id]);
            if ($existing) {
                throw new Exception('Form-Key bereits vergeben');
            }
            $fields[] = "form_key = ?";
            $params[] = $data['form_key'];
        }
        
        if (isset($data['description'])) {
            $fields[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (isset($data['fields'])) {
            $fields[] = "fields = ?";
            $params[] = is_array($data['fields']) ? json_encode($data['fields']) : $data['fields'];
        }
        
        if (isset($data['settings'])) {
            $fields[] = "settings = ?";
            $params[] = is_array($data['settings']) ? json_encode($data['settings']) : $data['settings'];
        }
        
        if (isset($data['active'])) {
            $fields[] = "active = ?";
            $params[] = $data['active'];
        }
        
        if (empty($fields)) {
            return true; // Nothing to update
        }
        
        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        
        $sql = "UPDATE custom_forms SET " . implode(", ", $fields) . " WHERE id = ?";
        $params[] = $id;
        
        return self::$db->query($sql, $params);
    }
    
    public static function delete($id) {
        if (!self::$db) self::init();
        
        // Also delete form submissions
        self::$db->query("DELETE FROM form_submissions WHERE form_id = ?", [$id]);
        
        return self::$db->query("DELETE FROM custom_forms WHERE id = ?", [$id]);
    }
    
    public static function renderForm($key, $css_classes = '') {
        if (!self::$db) self::init();
        
        $form = self::getByKey($key);
        
        if (!$form) {
            return '<p class="error">Formular nicht gefunden oder inaktiv.</p>';
        }
        
        $fields = json_decode($form['fields'], true);
        $settings = json_decode($form['settings'], true);
        
        if (!$fields) {
            return '<p class="error">Formular hat keine Felder.</p>';
        }
        
        ob_start();
        ?>
        <form class="custom-form <?= htmlspecialchars($css_classes) ?>" 
              method="POST" action="" 
              enctype="multipart/form-data"
              data-form-key="<?= htmlspecialchars($key) ?>">
            
            <input type="hidden" name="form_submission" value="1">
            <input type="hidden" name="form_key" value="<?= htmlspecialchars($key) ?>">
            <input type="hidden" name="csrf_token" value="<?= self::generateCSRFToken() ?>">
            
            <?php if (!empty($settings['title'])): ?>
                <h3><?= htmlspecialchars($settings['title']) ?></h3>
            <?php endif; ?>
            
            <?php if (!empty($settings['description'])): ?>
                <p class="form-description"><?= htmlspecialchars($settings['description']) ?></p>
            <?php endif; ?>
            
            <?php foreach ($fields as $field): ?>
                <div class="form-group">
                    <?= self::renderField($field) ?>
                </div>
            <?php endforeach; ?>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <?= htmlspecialchars($settings['submit_text'] ?? 'Absenden') ?>
                </button>
            </div>
        </form>
        
        <style>
        .custom-form .form-group {
            margin-bottom: 1rem;
        }
        .custom-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .custom-form input[type="text"],
        .custom-form input[type="email"],
        .custom-form input[type="tel"],
        .custom-form input[type="number"],
        .custom-form textarea,
        .custom-form select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .custom-form .required {
            color: red;
        }
        .custom-form .help-text {
            font-size: 0.9em;
            color: #666;
            margin-top: 0.25rem;
        }
        .custom-form .error {
            color: red;
            margin: 1rem 0;
            padding: 0.5rem;
            background: #fee;
            border: 1px solid #fcc;
            border-radius: 4px;
        }
        .custom-form .success {
            color: green;
            margin: 1rem 0;
            padding: 0.5rem;
            background: #efe;
            border: 1px solid #cfc;
            border-radius: 4px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    private static function renderField($field) {
        $html = '';
        $required = !empty($field['required']) ? 'required' : '';
        $requiredMark = !empty($field['required']) ? '<span class="required">*</span>' : '';
        
        // Label
        if (!empty($field['label'])) {
            $html .= '<label for="' . htmlspecialchars($field['name']) . '">';
            $html .= htmlspecialchars($field['label']) . $requiredMark;
            $html .= '</label>';
        }
        
        // Field based on type
        switch ($field['type']) {
            case 'text':
                $html .= '<input type="text" id="' . htmlspecialchars($field['name']) . '" 
                                 name="' . htmlspecialchars($field['name']) . '" 
                                 placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '"
                                 value="' . htmlspecialchars($_POST[$field['name']] ?? '') . '"
                                 ' . $required . '>';
                break;
                
            case 'email':
                $html .= '<input type="email" id="' . htmlspecialchars($field['name']) . '" 
                                 name="' . htmlspecialchars($field['name']) . '" 
                                 placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '"
                                 value="' . htmlspecialchars($_POST[$field['name']] ?? '') . '"
                                 ' . $required . '>';
                break;
                
            case 'tel':
                $html .= '<input type="tel" id="' . htmlspecialchars($field['name']) . '" 
                                 name="' . htmlspecialchars($field['name']) . '" 
                                 placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '"
                                 value="' . htmlspecialchars($_POST[$field['name']] ?? '') . '"
                                 ' . $required . '>';
                break;
                
            case 'number':
                $html .= '<input type="number" id="' . htmlspecialchars($field['name']) . '" 
                                 name="' . htmlspecialchars($field['name']) . '" 
                                 placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '"
                                 value="' . htmlspecialchars($_POST[$field['name']] ?? '') . '"
                                 ' . $required . '>';
                break;
                
            case 'textarea':
                $html .= '<textarea id="' . htmlspecialchars($field['name']) . '" 
                                    name="' . htmlspecialchars($field['name']) . '" 
                                    placeholder="' . htmlspecialchars($field['placeholder'] ?? '') . '"
                                    rows="' . ($field['rows'] ?? 4) . '"
                                    ' . $required . '>';
                $html .= htmlspecialchars($_POST[$field['name']] ?? '');
                $html .= '</textarea>';
                break;
                
            case 'select':
                $html .= '<select id="' . htmlspecialchars($field['name']) . '" 
                                  name="' . htmlspecialchars($field['name']) . '"
                                  ' . $required . '>';
                if (!empty($field['placeholder'])) {
                    $html .= '<option value="">' . htmlspecialchars($field['placeholder']) . '</option>';
                }
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $selected = ($_POST[$field['name']] ?? '') === $option ? 'selected' : '';
                        $html .= '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>';
                        $html .= htmlspecialchars($option);
                        $html .= '</option>';
                    }
                }
                $html .= '</select>';
                break;
                
            case 'checkbox':
                $checked = !empty($_POST[$field['name']]) ? 'checked' : '';
                $html .= '<input type="checkbox" id="' . htmlspecialchars($field['name']) . '" 
                                 name="' . htmlspecialchars($field['name']) . '" 
                                 value="1" ' . $checked . ' ' . $required . '>';
                break;
                
            case 'radio':
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $checked = ($_POST[$field['name']] ?? '') === $option ? 'checked' : '';
                        $html .= '<label>';
                        $html .= '<input type="radio" name="' . htmlspecialchars($field['name']) . '" 
                                         value="' . htmlspecialchars($option) . '" ' . $checked . ' ' . $required . '>';
                        $html .= ' ' . htmlspecialchars($option);
                        $html .= '</label><br>';
                    }
                }
                break;
                
            case 'file':
                $html .= '<input type="file" id="' . htmlspecialchars($field['name']) . '" 
                                 name="' . htmlspecialchars($field['name']) . '"
                                 accept="' . htmlspecialchars($field['accept'] ?? '') . '"
                                 ' . $required . '>';
                break;
        }
        
        // Help text
        if (!empty($field['help'])) {
            $html .= '<div class="help-text">' . htmlspecialchars($field['help']) . '</div>';
        }
        
        return $html;
    }
    
    public static function handleSubmission($formKey, $data) {
        if (!self::$db) self::init();
        
        $form = self::getByKey($formKey);
        if (!$form) {
            throw new Exception('Formular nicht gefunden');
        }
        
        $fields = json_decode($form['fields'], true);
        $settings = json_decode($form['settings'], true);
        
        // Validate required fields
        $errors = [];
        foreach ($fields as $field) {
            if (!empty($field['required']) && empty($data[$field['name']])) {
                $errors[] = 'Das Feld "' . $field['label'] . '" ist erforderlich.';
            }
        }
        
        if (!empty($errors)) {
            throw new Exception(implode('<br>', $errors));
        }
        
        // Save submission
        $submissionData = [
            'form_id' => $form['id'],
            'data' => json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $submissionId = self::saveSubmission($submissionData);
        
        // Send email if configured
        if (!empty($settings['email_to'])) {
            self::sendEmailNotification($form, $data, $settings);
        }
        
        return $submissionId;
    }
    
    private static function saveSubmission($data) {
        if (!self::$db) self::init();
        
        $sql = "INSERT INTO form_submissions (form_id, data, ip_address, user_agent) 
                VALUES (?, ?, ?, ?)";
        
        self::$db->query($sql, [
            $data['form_id'],
            $data['data'],
            $data['ip_address'],
            $data['user_agent']
        ]);
        
        return self::$db->lastInsertId();
    }
    
    private static function sendEmailNotification($form, $data, $settings) {
        $subject = $settings['email_subject'] ?? 'Neue Formular-Übermittlung: ' . $form['name'];
        $to = $settings['email_to'];
        
        $message = "Neue Übermittlung für das Formular: " . $form['name'] . "\n\n";
        $message .= "Übermittelt am: " . date('d.m.Y H:i:s') . "\n\n";
        
        foreach ($data as $key => $value) {
            if ($key !== 'form_submission' && $key !== 'form_key' && $key !== 'csrf_token') {
                $message .= ucfirst($key) . ": " . $value . "\n";
            }
        }
        
        $headers = "From: " . ($settings['email_from'] ?? 'noreply@' . $_SERVER['HTTP_HOST']);
        
        mail($to, $subject, $message, $headers);
    }
    
    public static function getSubmissions($formId, $limit = 50) {
        if (!self::$db) self::init();
        
        return self::$db->fetchAll(
            "SELECT * FROM form_submissions WHERE form_id = ? ORDER BY created_at DESC LIMIT ?",
            [$formId, $limit]
        );
    }
    
    public static function getSubmissionCount($formId) {
        if (!self::$db) self::init();
        
        $result = self::$db->fetchOne(
            "SELECT COUNT(*) as count FROM form_submissions WHERE form_id = ?",
            [$formId]
        );
        
        return $result ? (int)$result['count'] : 0;
    }
    
    public static function getFieldTypes() {
        return [
            'text' => 'Text',
            'email' => 'E-Mail',
            'tel' => 'Telefon',
            'number' => 'Zahl',
            'textarea' => 'Textbereich',
            'select' => 'Auswahlliste',
            'checkbox' => 'Checkbox',
            'radio' => 'Radio Buttons',
            'file' => 'Datei-Upload'
        ];
    }
    
    private static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>
