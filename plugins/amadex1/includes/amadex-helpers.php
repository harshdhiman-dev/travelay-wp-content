<?php
/**
 * Amadex Helper Functions
 * 
 * Shared utility functions for the Amadex plugin
 *
 * @package Amadex
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conditional logging function
 * Only logs if debug logging is enabled in settings
 *
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 * @return void
 */
function amadex_log($message, $level = 'info') {
    // Check if debug logging is enabled
    $settings = get_option('amadex_performance_settings', array());
    $debug_enabled = isset($settings['enable_debug_logging']) && $settings['enable_debug_logging'] === '1';
    
    // Always log errors regardless of setting (for critical issues)
    if ($level === 'error' && (!defined('WP_DEBUG') || !WP_DEBUG)) {
        return; // Don't log errors if WP_DEBUG is off
    }
    
    // If debug logging is disabled, don't log (except errors in debug mode)
    if (!$debug_enabled && $level !== 'error') {
        return;
    }
    
    // Check WP_DEBUG for additional safety
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    // Format message with prefix
    $prefix = '[Amadex]';
    if ($level === 'error') {
        $prefix = '[Amadex ERROR]';
    } elseif ($level === 'warning') {
        $prefix = '[Amadex WARNING]';
    }
    
    error_log($prefix . ' ' . $message);
}

/**
 * Get initial results count from settings
 *
 * @return int
 */
function amadex_get_initial_results_count() {
    $settings = get_option('amadex_performance_settings', array());
    $count = isset($settings['initial_results_count']) ? intval($settings['initial_results_count']) : 50;
    
    // Ensure minimum of 10 and maximum of 250
    $count = max(10, min(250, $count));
    
    return $count;
}

/**
 * Check if performance logging is enabled
 *
 * @return bool
 */
function amadex_is_performance_logging_enabled() {
    $settings = get_option('amadex_performance_settings', array());
    return isset($settings['enable_performance_logging']) && $settings['enable_performance_logging'] === '1';
}

/**
 * Card brand SVG icons for payment method display (crisp at any resolution)
 *
 * @return string Inline SVG markup
 */
function amadex_visa_svg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 16" width="36" height="12" fill="none" aria-hidden="true"><path fill="#1A1F71" d="M20.2 1.4l-2.4 13.2h-3.1L17.1 1.4h3.1zM41.8 5.2c-.3-.2-.8-.3-1.4-.3-1.6 0-2.7 1-2.7 2.5 0 1.1.6 1.7 1.6 2.1.7.3 1 .5 1 .8 0 .4-.4.6-1 .6-1.6h2.9c0 1.6-.5 2.8-1.3 3.5-.8.8-1.9 1.2-3.4 1.2-1.8 0-3.1-.5-4-1.5-.9-1-1.4-2.4-1.4-4.2 0-1.9.5-3.4 1.4-4.3.9-.9 2.2-1.4 3.7-1.4 1.6 0 2.7.5 3.4 1l-.4 2.5zm-.1 6.1c.5-.7.7-1.7.7-2.7-.2-.9-.5-1.7-1-2.2-.4-.4-1-.7-1.7-.7-1 0-1.8.6-2.2 1.5l2.2 4.1zM31.1 1.4l-1.9 13.2h-3.1l1.9-13.2h3.1zM27.2 8.5l1.2-3.3.7 3.3h-1.9zm3.2-7.1l-2.4 13.2h-2.9l.5-2.9s1.3-2.9 3.3-4.1c1.5-1 2.1-1.2 2.1-1.2l-.6 8.2h-3.1l2.4-13.2h3.2z"/></svg>';
}

function amadex_mastercard_svg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32" width="36" height="24" fill="none" aria-hidden="true"><circle cx="18" cy="16" r="10" fill="#EB001B"/><circle cx="30" cy="16" r="10" fill="#F79E1B"/><path fill="#FF5F00" d="M24 7.3a15.2 15.2 0 0 1 0 17.4 15.2 15.2 0 0 1 0-17.4z"/></svg>';
}

function amadex_amex_svg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32" width="36" height="24" fill="none" aria-hidden="true"><rect width="48" height="32" rx="2" fill="#006FCF"/><path fill="#fff" d="M18 10h6v1.5h-4v1.2h3.6v1.4h-3.6v1.2h4V17h-6V10zm-6 7V10h2l1.4 4.2L16.8 10h1.8v7h-1.6v-4l-1 3.2h-1L11 13v4H9zm20 0v-1.4h-3V17h-2V10h5.6l.9 2.6.9-2.6H41v7h-2v-1.4h-3zm-1.6-2.6h2.2l-.8-2.2h-.6l-.8 2.2z"/></svg>';
}

function amadex_maestro_svg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 32" width="36" height="24" fill="none" aria-hidden="true"><rect width="48" height="32" rx="2" fill="#0099DF"/><circle cx="18" cy="16" r="9" fill="#EB001B" opacity=".8"/><circle cx="30" cy="16" r="9" fill="#F79E1B" opacity=".8"/><path fill="#FF5F00" d="M24 8a14 14 0 0 1 0 16 14 14 0 0 1 0-16z" opacity=".9"/></svg>';
}
