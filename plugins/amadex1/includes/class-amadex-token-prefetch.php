<?php
/**
 * Amadex Token Pre-fetch Class
 * 
 * Pre-fetches Amadeus API tokens using WordPress cron to eliminate token delay on first search
 *
 * @package Amadex
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Token Pre-fetch Class
 */
class Amadex_Token_Prefetch {
    
    /**
     * Cron hook name
     */
    const CRON_HOOK = 'amadex_refresh_token';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register cron event on activation
        register_activation_hook(AMADEX_BASENAME, array($this, 'schedule_token_refresh'));
        
        // Schedule on init (in case activation hook doesn't fire)
        add_action('init', array($this, 'maybe_schedule_token_refresh'));
        
        // Register cron callback
        add_action(self::CRON_HOOK, array($this, 'refresh_token'));
        
        // Unschedule on deactivation
        register_deactivation_hook(AMADEX_BASENAME, array($this, 'unschedule_token_refresh'));
    }
    
    /**
     * Schedule token refresh cron job
     */
    public function schedule_token_refresh() {
        // Check if scheduled
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // Schedule to run every 12 hours
            wp_schedule_event(time(), 'twicedaily', self::CRON_HOOK);
            amadex_log('Amadex Token Prefetch: Scheduled token refresh cron job (every 12 hours)');
        }
    }
    
    /**
     * Maybe schedule token refresh (if not already scheduled)
     */
    public function maybe_schedule_token_refresh() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            $this->schedule_token_refresh();
        }
    }
    
    /**
     * Refresh token via cron
     */
    public function refresh_token() {
        if (!class_exists('Amadex_API')) {
            amadex_log('Amadex Token Prefetch: Amadex_API class not found', 'error');
            return;
        }
        
        $api = new Amadex_API();
        $token = $api->get_access_token();
        
        if (is_wp_error($token)) {
            amadex_log('Amadex Token Prefetch: Failed to refresh token: ' . $token->get_error_message(), 'error');
        } else {
            amadex_log('Amadex Token Prefetch: Successfully refreshed token via cron');
        }
    }
    
    /**
     * Unschedule token refresh cron job
     */
    public function unschedule_token_refresh() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            amadex_log('Amadex Token Prefetch: Unscheduled token refresh cron job');
        }
    }
}
