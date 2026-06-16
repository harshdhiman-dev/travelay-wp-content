<?php
/**
 * Amadex Performance Measurement Class
 * 
 * Handles timing instrumentation and performance metrics collection
 *
 * @package Amadex
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performance Measurement Class
 */
class Amadex_Performance {
    
    /**
     * Performance metrics storage
     *
     * @var array
     */
    private static $metrics = array();
    
    /**
     * Timers storage
     *
     * @var array
     */
    private static $timers = array();
    
    /**
     * Whether performance logging is enabled
     *
     * @var bool
     */
    private static $enabled = null;
    
    /**
     * Check if performance logging is enabled
     *
     * @return bool
     */
    private static function is_enabled() {
        if (self::$enabled === null) {
            $settings = get_option('amadex_performance_settings', array());
            self::$enabled = isset($settings['enable_performance_logging']) && $settings['enable_performance_logging'] === '1';
        }
        return self::$enabled;
    }
    
    /**
     * Start a timer
     *
     * @param string $label Timer label
     * @return void
     */
    public static function start_timer($label) {
        if (!self::is_enabled()) {
            return;
        }
        
        self::$timers[$label] = array(
            'start' => microtime(true),
            'start_memory' => memory_get_usage(true)
        );
    }
    
    /**
     * End a timer and record metric
     *
     * @param string $label Timer label
     * @param array $additional_data Additional data to record
     * @return float|false Elapsed time in seconds, or false if timer not found
     */
    public static function end_timer($label, $additional_data = array()) {
        if (!self::is_enabled()) {
            return false;
        }
        
        if (!isset(self::$timers[$label])) {
            return false;
        }
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        
        $elapsed = $end_time - self::$timers[$label]['start'];
        $memory_used = $end_memory - self::$timers[$label]['start_memory'];
        
        self::$metrics[$label] = array(
            'time' => round($elapsed, 4),
            'memory' => round($memory_used / 1024 / 1024, 2), // MB
            'timestamp' => $end_time,
            'data' => $additional_data
        );
        
        unset(self::$timers[$label]);
        
        return $elapsed;
    }
    
    /**
     * Record a metric without timing
     *
     * @param string $label Metric label
     * @param mixed $value Metric value
     * @return void
     */
    public static function record_metric($label, $value) {
        if (!self::is_enabled()) {
            return;
        }
        
        self::$metrics[$label] = array(
            'value' => $value,
            'timestamp' => microtime(true)
        );
    }
    
    /**
     * Get all metrics
     *
     * @return array
     */
    public static function get_metrics() {
        return self::$metrics;
    }
    
    /**
     * Get a specific metric
     *
     * @param string $label Metric label
     * @return mixed|null
     */
    public static function get_metric($label) {
        return isset(self::$metrics[$label]) ? self::$metrics[$label] : null;
    }
    
    /**
     * Clear all metrics
     *
     * @return void
     */
    public static function clear_metrics() {
        self::$metrics = array();
        self::$timers = array();
    }
    
    /**
     * Log metrics to WordPress debug log
     *
     * @param string $context Context label (e.g., 'flight_search')
     * @return void
     */
    public static function log_metrics($context = 'amadex') {
        if (!self::is_enabled() || !defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_message = "[AMADEX_PERF] {$context}:\n";
        foreach (self::$metrics as $label => $metric) {
            if (isset($metric['time'])) {
                $log_message .= "  {$label}: {$metric['time']}s";
                if (isset($metric['memory'])) {
                    $log_message .= " ({$metric['memory']}MB)";
                }
                if (!empty($metric['data'])) {
                    $log_message .= " | " . json_encode($metric['data']);
                }
            } else {
                $log_message .= "  {$label}: " . json_encode($metric['value']);
            }
            $log_message .= "\n";
        }
        
        error_log($log_message);
    }
    
    /**
     * Capture current Advanced Settings snapshot
     *
     * @return array Settings snapshot for performance logging
     */
    public static function capture_settings_snapshot() {
        $advanced_settings = get_option('amadex_advanced_settings', array());
        $performance_settings = get_option('amadex_performance_settings', array());
        
        // Build a clean snapshot of relevant settings
        $snapshot = array(
            // API Settings
            'api_timeout' => isset($advanced_settings['timeout']) ? intval($advanced_settings['timeout']) : 10,
            'error_logging' => isset($advanced_settings['error_logging']) && $advanced_settings['error_logging'] === '1',
            'debug_mode' => isset($advanced_settings['debug_mode']) && $advanced_settings['debug_mode'] === '1',
            
            // Performance Settings
            'debug_logging' => isset($performance_settings['enable_debug_logging']) && $performance_settings['enable_debug_logging'] === '1',
            'initial_results_count' => isset($performance_settings['initial_results_count']) ? intval($performance_settings['initial_results_count']) : 50,
            'performance_logging' => isset($performance_settings['enable_performance_logging']) && $performance_settings['enable_performance_logging'] === '1',
            
            // Redis
            'redis_enabled' => isset($performance_settings['enable_redis_cache']) && $performance_settings['enable_redis_cache'] === '1',
            'redis_connected' => class_exists('Amadex_Redis_Cache') ? Amadex_Redis_Cache::is_available() : false,
            
            // Progressive Loading
            'progressive_loading' => isset($performance_settings['enable_progressive_loading']) && $performance_settings['enable_progressive_loading'] === '1',
            'progressive_count' => isset($performance_settings['progressive_initial_count']) ? intval($performance_settings['progressive_initial_count']) : 30,
            
            // Streaming
            'streaming_response' => isset($performance_settings['enable_streaming_response']) && $performance_settings['enable_streaming_response'] === '1',
            'streaming_count' => isset($performance_settings['streaming_initial_count']) ? intval($performance_settings['streaming_initial_count']) : 5,
            
            // UI Performance
            'virtual_scrolling' => isset($performance_settings['enable_virtual_scrolling']) && $performance_settings['enable_virtual_scrolling'] === '1',
            'skeleton_ui' => isset($performance_settings['enable_skeleton_ui']) && $performance_settings['enable_skeleton_ui'] === '1',
            'loading_animation' => isset($performance_settings['enable_loading_animation']) && $performance_settings['enable_loading_animation'] === '1',
        );
        
        return $snapshot;
    }
    
    /**
     * Save metrics to database for admin panel display
     *
     * @param string $search_id Unique search identifier
     * @param array $search_params Optional search parameters (origin, destination, etc.)
     * @return void
     */
    public static function save_metrics($search_id = null, $search_params = array()) {
        if (!self::is_enabled()) {
            return;
        }
        
        if ($search_id === null) {
            $search_id = 'search_' . time() . '_' . wp_generate_password(8, false);
        }
        
        $metrics_data = array(
            'search_id' => $search_id,
            'metrics' => self::$metrics,
            'timestamp' => current_time('mysql'),
            'total_time' => self::calculate_total_time(),
            'settings_snapshot' => self::capture_settings_snapshot(),
            'search_params' => $search_params
        );
        
        // Get existing metrics
        $all_metrics = get_option('amadex_performance_metrics', array());
        
        // Add new metrics (keep last 100 searches)
        $all_metrics[] = $metrics_data;
        if (count($all_metrics) > 100) {
            $all_metrics = array_slice($all_metrics, -100);
        }
        
        update_option('amadex_performance_metrics', $all_metrics);
    }
    
    /**
     * Calculate total time from all metrics
     *
     * @return float
     */
    private static function calculate_total_time() {
        $total = 0;
        foreach (self::$metrics as $metric) {
            if (isset($metric['time'])) {
                $total += $metric['time'];
            }
        }
        return round($total, 4);
    }
    
    /**
     * Delete metrics by search IDs
     *
     * @param array $search_ids Array of search_id values to delete
     * @return int Number of deleted items
     */
    public static function delete_metrics_by_ids($search_ids) {
        if (empty($search_ids) || !is_array($search_ids)) {
            return 0;
        }
        
        $search_ids = array_map('sanitize_text_field', $search_ids);
        $all_metrics = get_option('amadex_performance_metrics', array());
        $original_count = count($all_metrics);
        $all_metrics = array_values(array_filter($all_metrics, function ($item) use ($search_ids) {
            return !isset($item['search_id']) || !in_array($item['search_id'], $search_ids, true);
        }));
        $deleted = $original_count - count($all_metrics);
        
        if ($deleted > 0) {
            update_option('amadex_performance_metrics', $all_metrics);
        }
        
        return $deleted;
    }
    
    /**
     * Get recent metrics for admin panel
     *
     * @param int $limit Number of recent searches to return
     * @return array
     */
    public static function get_recent_metrics($limit = 20) {
        $all_metrics = get_option('amadex_performance_metrics', array());
        return array_slice(array_reverse($all_metrics), 0, $limit);
    }
    
    /**
     * Get average metrics
     *
     * @param int $limit Number of recent searches to average
     * @return array
     */
    public static function get_average_metrics($limit = 20) {
        $recent = self::get_recent_metrics($limit);
        
        if (empty($recent)) {
            return array();
        }
        
        $averages = array();
        $count = count($recent);
        
        foreach ($recent as $search) {
            if (!isset($search['metrics'])) {
                continue;
            }
            
            foreach ($search['metrics'] as $label => $metric) {
                if (!isset($averages[$label])) {
                    $averages[$label] = array(
                        'time' => 0,
                        'count' => 0
                    );
                }
                
                if (isset($metric['time'])) {
                    $averages[$label]['time'] += $metric['time'];
                    $averages[$label]['count']++;
                }
            }
        }
        
        // Calculate averages
        foreach ($averages as $label => &$avg) {
            if ($avg['count'] > 0) {
                $avg['time'] = round($avg['time'] / $avg['count'], 4);
            }
        }
        
        return $averages;
    }
}
