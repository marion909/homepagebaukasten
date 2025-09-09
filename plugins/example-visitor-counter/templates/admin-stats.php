<?php
/**
 * Template: Admin Statistics Page
 * Zeigt detaillierte Besucher-Statistiken im Admin-Bereich
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap visitor-counter-admin">
    <h1><?= __('Besucher Statistiken', 'visitor-counter') ?></h1>
    
    <!-- Statistik-Übersicht -->
    <div class="visitor-stats-overview">
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= __('Gesamt Aufrufe', 'visitor-counter') ?></h3>
                <div class="stat-number"><?= number_format($stats['total_visits']) ?></div>
            </div>
            
            <div class="stat-card">
                <h3><?= __('Eindeutige Besucher', 'visitor-counter') ?></h3>
                <div class="stat-number"><?= number_format($stats['total_unique']) ?></div>
            </div>
            
            <div class="stat-card">
                <h3><?= __('Tracked Seiten', 'visitor-counter') ?></h3>
                <div class="stat-number"><?= number_format($stats['pages_tracked']) ?></div>
            </div>
            
            <div class="stat-card">
                <h3><?= __('Heute', 'visitor-counter') ?></h3>
                <div class="stat-number"><?= number_format($stats['today_visits']) ?></div>
            </div>
        </div>
    </div>
    
    <!-- Zeitraum-Statistiken -->
    <div class="visitor-stats-timeline">
        <h2><?= __('Zeitraum-Statistiken', 'visitor-counter') ?></h2>
        <div class="timeline-grid">
            <div class="timeline-item">
                <h4><?= __('Diese Woche', 'visitor-counter') ?></h4>
                <span class="timeline-number"><?= number_format($stats['this_week']) ?></span>
            </div>
            <div class="timeline-item">
                <h4><?= __('Dieser Monat', 'visitor-counter') ?></h4>
                <span class="timeline-number"><?= number_format($stats['this_month']) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Top Seiten -->
    <div class="visitor-stats-top-pages">
        <h2><?= __('Beliebteste Seiten', 'visitor-counter') ?></h2>
        <div class="table-container">
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?= __('Seite', 'visitor-counter') ?></th>
                        <th><?= __('Aufrufe', 'visitor-counter') ?></th>
                        <th><?= __('Eindeutige Besucher', 'visitor-counter') ?></th>
                        <th><?= __('Aktionen', 'visitor-counter') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($topPages)): ?>
                        <?php foreach ($topPages as $page): ?>
                        <tr>
                            <td>
                                <strong><?= esc_html($page['title']) ?></strong>
                                <br>
                                <small>/<?= esc_html($page['slug']) ?></small>
                            </td>
                            <td><?= number_format($page['visits']) ?></td>
                            <td><?= number_format($page['unique_visits']) ?></td>
                            <td>
                                <a href="/<?= esc_attr($page['slug']) ?>" target="_blank" class="button button-small">
                                    <?= __('Anzeigen', 'visitor-counter') ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">
                                <?= __('Noch keine Statistiken verfügbar.', 'visitor-counter') ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Neueste Besuche -->
    <div class="visitor-stats-recent">
        <h2><?= __('Neueste Besuche', 'visitor-counter') ?></h2>
        <div class="table-container">
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?= __('Seite', 'visitor-counter') ?></th>
                        <th><?= __('IP-Adresse', 'visitor-counter') ?></th>
                        <th><?= __('Browser', 'visitor-counter') ?></th>
                        <th><?= __('Gerät', 'visitor-counter') ?></th>
                        <th><?= __('Zeit', 'visitor-counter') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentVisits)): ?>
                        <?php foreach ($recentVisits as $visit): ?>
                        <tr>
                            <td><?= esc_html($visit['title']) ?></td>
                            <td>
                                <code><?= esc_html($visit['ip_address']) ?></code>
                            </td>
                            <td><?= esc_html($visit['browser']) ?></td>
                            <td>
                                <span class="device-badge device-<?= strtolower($visit['device']) ?>">
                                    <?= esc_html($visit['device']) ?>
                                </span>
                            </td>
                            <td>
                                <?= date('d.m.Y H:i', strtotime($visit['visit_time'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <?= __('Noch keine Besuche aufgezeichnet.', 'visitor-counter') ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Aktionen -->
    <div class="visitor-stats-actions">
        <h2><?= __('Aktionen', 'visitor-counter') ?></h2>
        <p>
            <button type="button" class="button button-secondary" id="export-stats">
                <?= __('Statistiken exportieren', 'visitor-counter') ?>
            </button>
            <button type="button" class="button button-danger" id="reset-stats" 
                    onclick="return confirm('<?= esc_js(__('Möchten Sie wirklich alle Statistiken zurücksetzen? Diese Aktion kann nicht rückgängig gemacht werden.', 'visitor-counter')) ?>')">
                <?= __('Statistiken zurücksetzen', 'visitor-counter') ?>
            </button>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Export-Funktionalität
    $('#export-stats').on('click', function() {
        window.location.href = ajaxurl + '?action=export_visitor_stats&_wpnonce=' + visitorCounter.nonce;
    });
    
    // Reset-Funktionalität
    $('#reset-stats').on('click', function() {
        if (!confirm('<?= esc_js(__('Sind Sie sicher?', 'visitor-counter')) ?>')) {
            return false;
        }
        
        $.post(ajaxurl, {
            action: 'reset_visitor_stats',
            _wpnonce: visitorCounter.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('<?= esc_js(__('Fehler beim Zurücksetzen der Statistiken.', 'visitor-counter')) ?>');
            }
        });
        
        return false;
    });
});
</script>
