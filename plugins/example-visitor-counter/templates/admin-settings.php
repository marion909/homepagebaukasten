<?php
/**
 * Template: Admin Settings Page
 * Konfiguration des Visitor Counter Plugins
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap visitor-counter-settings">
    <h1><?= __('Visitor Counter Einstellungen', 'visitor-counter') ?></h1>
    
    <?php if (isset($message)): ?>
    <div class="notice notice-success is-dismissible">
        <p><?= esc_html($message) ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('visitor_counter_settings', 'visitor_counter_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <!-- Counter anzeigen -->
                <tr>
                    <th scope="row">
                        <label for="show_counter">
                            <?= __('Counter anzeigen', 'visitor-counter') ?>
                        </label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       id="show_counter" 
                                       name="show_counter" 
                                       value="1" 
                                       <?= checked($settings['show_counter'], true, false) ?>>
                                <?= __('Besucher-Counter auf Seiten anzeigen', 'visitor-counter') ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Counter-Position -->
                <tr>
                    <th scope="row">
                        <label for="counter_position">
                            <?= __('Counter-Position', 'visitor-counter') ?>
                        </label>
                    </th>
                    <td>
                        <select id="counter_position" name="counter_position">
                            <option value="top" <?= selected($settings['counter_position'], 'top', false) ?>>
                                <?= __('Oben im Inhalt', 'visitor-counter') ?>
                            </option>
                            <option value="bottom" <?= selected($settings['counter_position'], 'bottom', false) ?>>
                                <?= __('Unten im Inhalt', 'visitor-counter') ?>
                            </option>
                            <option value="manual" <?= selected($settings['counter_position'], 'manual', false) ?>>
                                <?= __('Manuell (Shortcode)', 'visitor-counter') ?>
                            </option>
                        </select>
                        <p class="description">
                            <?= __('Bei "Manuell" verwenden Sie den Shortcode [visitor_counter] in Ihren Inhalten.', 'visitor-counter') ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Admins ausschließen -->
                <tr>
                    <th scope="row">
                        <label for="exclude_admin">
                            <?= __('Administratoren ausschließen', 'visitor-counter') ?>
                        </label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       id="exclude_admin" 
                                       name="exclude_admin" 
                                       value="1" 
                                       <?= checked($settings['exclude_admin'], true, false) ?>>
                                <?= __('Aufrufe von eingeloggten Administratoren nicht zählen', 'visitor-counter') ?>
                            </label>
                            <p class="description">
                                <?= __('Empfohlen, um genauere Besucherstatistiken zu erhalten.', 'visitor-counter') ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Counter-Stil -->
                <tr>
                    <th scope="row">
                        <label for="counter_style">
                            <?= __('Counter-Stil', 'visitor-counter') ?>
                        </label>
                    </th>
                    <td>
                        <select id="counter_style" name="counter_style">
                            <option value="default" <?= selected($settings['counter_style'] ?? 'default', 'default', false) ?>>
                                <?= __('Standard', 'visitor-counter') ?>
                            </option>
                            <option value="minimal" <?= selected($settings['counter_style'] ?? 'default', 'minimal', false) ?>>
                                <?= __('Minimal', 'visitor-counter') ?>
                            </option>
                            <option value="detailed" <?= selected($settings['counter_style'] ?? 'default', 'detailed', false) ?>>
                                <?= __('Detailliert', 'visitor-counter') ?>
                            </option>
                        </select>
                        <p class="description">
                            <?= __('Wählen Sie das Aussehen des Counters.', 'visitor-counter') ?>
                        </p>
                    </td>
                </tr>
                
                <!-- Cache-Zeit -->
                <tr>
                    <th scope="row">
                        <label for="cache_time">
                            <?= __('Cache-Zeit (Minuten)', 'visitor-counter') ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="cache_time" 
                               name="cache_time" 
                               value="<?= esc_attr($settings['cache_time'] ?? 5) ?>" 
                               min="0" 
                               max="60" 
                               class="small-text">
                        <p class="description">
                            <?= __('Zeit in Minuten, wie lange Statistiken gecacht werden. 0 = kein Cache.', 'visitor-counter') ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?= __('Erweiterte Einstellungen', 'visitor-counter') ?></h2>
        
        <table class="form-table">
            <tbody>
                <!-- IP-Anonymisierung -->
                <tr>
                    <th scope="row">
                        <label for="anonymize_ip">
                            <?= __('IP-Adressen anonymisieren', 'visitor-counter') ?>
                        </label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       id="anonymize_ip" 
                                       name="anonymize_ip" 
                                       value="1" 
                                       <?= checked($settings['anonymize_ip'] ?? true, true, false) ?>>
                                <?= __('IP-Adressen vor der Speicherung anonymisieren (DSGVO-konform)', 'visitor-counter') ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Bot-Erkennung -->
                <tr>
                    <th scope="row">
                        <label for="exclude_bots">
                            <?= __('Bots ausschließen', 'visitor-counter') ?>
                        </label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" 
                                       id="exclude_bots" 
                                       name="exclude_bots" 
                                       value="1" 
                                       <?= checked($settings['exclude_bots'] ?? true, true, false) ?>>
                                <?= __('Bekannte Suchmaschinen-Bots nicht zählen', 'visitor-counter') ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                
                <!-- Daten-Aufbewahrung -->
                <tr>
                    <th scope="row">
                        <label for="data_retention">
                            <?= __('Daten-Aufbewahrung (Tage)', 'visitor-counter') ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="data_retention" 
                               name="data_retention" 
                               value="<?= esc_attr($settings['data_retention'] ?? 365) ?>" 
                               min="30" 
                               max="3650" 
                               class="small-text">
                        <p class="description">
                            <?= __('Wie lange sollen detaillierte Besucherdaten gespeichert werden? (30-3650 Tage)', 'visitor-counter') ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?= __('Shortcode-Optionen', 'visitor-counter') ?></h2>
        
        <div class="shortcode-examples">
            <h3><?= __('Verfügbare Shortcodes:', 'visitor-counter') ?></h3>
            <ul>
                <li>
                    <code>[visitor_counter]</code> - 
                    <?= __('Zeigt den Standard-Counter für die aktuelle Seite', 'visitor-counter') ?>
                </li>
                <li>
                    <code>[visitor_counter style="minimal"]</code> - 
                    <?= __('Counter im minimalen Stil', 'visitor-counter') ?>
                </li>
                <li>
                    <code>[visitor_counter show="visits"]</code> - 
                    <?= __('Zeigt nur die Gesamtaufrufe', 'visitor-counter') ?>
                </li>
                <li>
                    <code>[visitor_counter show="unique"]</code> - 
                    <?= __('Zeigt nur die eindeutigen Besucher', 'visitor-counter') ?>
                </li>
            </ul>
        </div>
        
        <p class="submit">
            <input type="submit" 
                   name="save_settings" 
                   class="button-primary" 
                   value="<?= esc_attr(__('Einstellungen speichern', 'visitor-counter')) ?>">
        </p>
    </form>
    
    <!-- Plugin-Informationen -->
    <div class="visitor-counter-info">
        <h2><?= __('Plugin-Informationen', 'visitor-counter') ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><?= __('Plugin-Version', 'visitor-counter') ?></td>
                    <td><?= VISITOR_COUNTER_VERSION ?></td>
                </tr>
                <tr>
                    <td><?= __('PHP-Version', 'visitor-counter') ?></td>
                    <td><?= PHP_VERSION ?></td>
                </tr>
                <tr>
                    <td><?= __('Database-Tabellen', 'visitor-counter') ?></td>
                    <td>
                        <code>visitor_stats</code>, <code>visitor_logs</code>
                    </td>
                </tr>
                <tr>
                    <td><?= __('Unterstützung', 'visitor-counter') ?></td>
                    <td>
                        <a href="https://github.com/baukasten-cms/visitor-counter" target="_blank">
                            <?= __('GitHub Repository', 'visitor-counter') ?>
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
