<?php
/**
 * Environment Manager for Amadex Plugin
 * Handles test/production environment separation
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Environment Manager Class
 */
class Amadex_Environment_Manager {
    
    /**
     * Current environment
     */
    private static $current_environment = null;
    
    /**
     * Get current environment
     * 
     * @return string Environment (PRODUCTION, TEST, STAGING)
     */
    public static function get_current_environment() {
        if (self::$current_environment !== null) {
            return self::$current_environment;
        }
        
        // Check user preference (stored in user meta)
        $user_id = get_current_user_id();
        if ($user_id) {
            $user_env = get_user_meta($user_id, 'amadex_environment', true);
            if (in_array($user_env, array('PRODUCTION', 'TEST', 'STAGING'))) {
                self::$current_environment = $user_env;
                return self::$current_environment;
            }
        }
        
        // Check admin setting
        $admin_env = get_option('amadex_default_environment', 'PRODUCTION');
        if (in_array($admin_env, array('PRODUCTION', 'TEST', 'STAGING'))) {
            self::$current_environment = $admin_env;
            return self::$current_environment;
        }
        
        // Default to PRODUCTION
        self::$current_environment = 'PRODUCTION';
        return self::$current_environment;
    }
    
    /**
     * Set current environment
     * 
     * @param string $environment Environment to set
     * @return bool Success
     */
    public static function set_current_environment($environment) {
        if (!in_array($environment, array('PRODUCTION', 'TEST', 'STAGING'))) {
            return false;
        }
        
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'amadex_environment', $environment);
            self::$current_environment = $environment;
            return true;
        }
        
        return false;
    }
    
    /**
     * Detect environment from booking data
     * 
     * @param array $booking_data Booking data
     * @return string Detected environment
     */
    public static function detect_environment($booking_data) {
        // Check email patterns (support both booking and lead data shape)
        $email = $booking_data['contact']['email'] ?? $booking_data['contact_email'] ?? '';
        if (!empty($email)) {
            $email_lower = strtolower($email);
            // Test email patterns
            if (strpos($email_lower, '@test.') !== false ||
                strpos($email_lower, '@test') !== false ||
                strpos($email_lower, 'test@') !== false ||
                strpos($email_lower, '.test') !== false) {
                return 'TEST';
            }
        }
        
        // Check IP address (admin/test IPs)
        $ip = self::get_client_ip();
        $test_ips = get_option('amadex_test_ip_addresses', array());
        if (in_array($ip, $test_ips)) {
            return 'TEST';
        }
        
        // Default to current environment
        return self::get_current_environment();
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Check if environment filter should be applied
     * 
     * @return bool
     */
    public static function should_filter_by_environment() {
        return true; // Always filter by environment
    }
    
    /**
     * Get environment badge HTML
     * 
     * @param string $environment Environment
     * @return string HTML badge
     */
    public static function get_environment_badge($environment) {
        $colors = array(
            'PRODUCTION' => '#10b981', // Green
            'TEST' => '#f59e0b',       // Yellow
            'STAGING' => '#3b82f6'     // Blue
        );
        
        $color = $colors[$environment] ?? '#6b7280';
        
        return sprintf(
            '<span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; background: %s; color: white;">%s</span>',
            $color,
            esc_html($environment)
        );
    }
}
