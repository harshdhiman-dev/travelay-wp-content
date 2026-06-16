<?php
/**
 * WordPress Function Stubs
 * 
 * This file contains function declarations for WordPress functions
 * to help IDEs and static analysis tools understand WordPress functions.
 * 
 * ⚠️ IMPORTANT: This file should NOT be included in production!
 * It's only for development tools (IDEs, PHPStan, etc.)
 * 
 * To use this file:
 * 1. Configure your IDE to include this file in its analysis
 * 2. Or add it to your PHPStan configuration
 * 3. Never require/include this file in your plugin code
 * 
 * @package Amadex
 * @since 1.0.0
 */

// WordPress Core Functions
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {}
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) { return true; }
}

if (!function_exists('check_ajax_referer')) {
    function check_ajax_referer($action = -1, $query_arg = false, $die = true) { return true; }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null, $status_code = null) {}
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null, $status_code = null) {}
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return $str; }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) { return $email; }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) { return $str; }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability, ...$args) { return true; }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) { return false; }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = array()) { return array(); }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = array()) { return array(); }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) { return ''; }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) { return $default; }
}

if (!function_exists('is_email')) {
    function is_email($email) { return true; }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '', $filter = 'raw') { return ''; }
}

if (!function_exists('get_site_url')) {
    function get_site_url($blog_id = null, $path = '', $scheme = null) { return ''; }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) { return true; }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string, $remove_breaks = false) { return $string; }
}

if (!function_exists('dbDelta')) {
    function dbDelta($queries, $execute = true) { return array(); }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}

if (!function_exists('esc_html')) {
    function esc_html($text) { return $text; }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return $text; }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) { return $text; }
}

if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $_context = 'display') { return $url; }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) { return $text; }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) { return ''; }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) { return ''; }
}

if (!function_exists('plugin_basename')) {
    function plugin_basename($file) { return ''; }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {}
}

if (!function_exists('get_permalink')) {
    function get_permalink($post = 0, $leavename = false) { return ''; }
}

if (!function_exists('home_url')) {
    function home_url($path = '', $scheme = null) { return ''; }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) { return ''; }
}

// WordPress Constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', '');
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 1);
}

if (!defined('ARRAY_N')) {
    define('ARRAY_N', 2);
}

