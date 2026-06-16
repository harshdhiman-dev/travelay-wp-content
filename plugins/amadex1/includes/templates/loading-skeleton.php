<?php
/**
 * Loading skeleton template (server-rendered)
 * Used when skeleton UI is fetched via AJAX. Output matches inline skeleton in amadex-streaming-loader.js.
 *
 * @package Amadex
 */

if (!defined('ABSPATH')) {
    exit;
}

$count = isset($count) ? max(1, min(10, (int) $count)) : 5;
?>
<div class="amadex-skeleton-container" id="amadex-skeleton-container">
<?php for ($i = 0; $i < $count; $i++) : ?>
    <div class="amadex-skeleton-card" data-skeleton-index="<?php echo (int) $i; ?>">
        <div class="amadex-skeleton-header">
            <div class="amadex-skeleton-line" style="width: 60%; height: 20px; margin-bottom: 8px;"></div>
            <div class="amadex-skeleton-line" style="width: 30%; height: 16px;"></div>
        </div>
        <div class="amadex-skeleton-content">
            <div class="amadex-skeleton-line" style="width: 80%; height: 14px; margin-bottom: 10px;"></div>
            <div class="amadex-skeleton-line" style="width: 50%; height: 14px; margin-bottom: 10px;"></div>
            <div class="amadex-skeleton-line" style="width: 70%; height: 14px;"></div>
        </div>
        <div class="amadex-skeleton-price">
            <div class="amadex-skeleton-line" style="width: 40%; height: 24px; float: right;"></div>
        </div>
    </div>
<?php endfor; ?>
</div>
