<?php
/**
 * Template: Counter Display
 * Zeigt den Besucher-Counter auf Frontend-Seiten an
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="visitor-counter" id="visitor-counter-<?= $pageId ?>">
    <div class="counter-content">
        <span class="counter-icon">👁️</span>
        <span class="counter-text">
            <?= sprintf(
                _n('%d Aufruf', '%d Aufrufe', $stats['visits'], 'visitor-counter'),
                number_format($stats['visits'])
            ) ?>
        </span>
        <?php if ($stats['unique_visits'] > 0): ?>
        <span class="counter-unique">
            (<?= sprintf(
                _n('%d eindeutig', '%d eindeutig', $stats['unique_visits'], 'visitor-counter'),
                number_format($stats['unique_visits'])
            ) ?>)
        </span>
        <?php endif; ?>
    </div>
</div>
