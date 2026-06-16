<?php
/**
 * Loading animation template (server-rendered)
 * Used when loading animation is fetched via AJAX. Output matches inline animation in amadex-streaming-loader.js.
 *
 * @package Amadex
 */

if (!defined('ABSPATH')) {
    exit;
}

$origin      = isset($origin) ? $origin : '';
$destination = isset($destination) ? $destination : '';
if ($origin && $destination) {
    $message = sprintf(
        /* translators: 1: origin, 2: destination */
        __('Searching %1$s to %2$s...', 'amadex'),
        esc_html($origin),
        esc_html($destination)
    );
} else {
    $message = __('Searching your flights...', 'amadex');
}
?>
<div class="amadex-loading-animation" id="amadex-loading-animation">
    <div class="amadex-loading-content">
        <div class="amadex-airplane-container">
            <div class="amadex-loading-spinner"></div>
        </div>
        <div class="amadex-loading-message">
            <span class="amadex-message-text" id="amadex-loading-message-text"><?php echo esc_html($message); ?></span>
        </div>
        <div class="amadex-progress-container">
            <div class="amadex-progress-bar" id="amadex-loading-progress-bar"></div>
        </div>
    </div>
</div>
