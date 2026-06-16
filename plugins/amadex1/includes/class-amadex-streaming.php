<?php
/**
 * Amadex Streaming Response Class
 * 
 * Handles streaming of flight results - sends initial batch immediately while processing rest
 *
 * @package Amadex
 * @since 1.1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Streaming Response Class
 */
class Amadex_Streaming {
    
    /**
     * Stream initial results to client
     * 
     * Formats and returns first N flights immediately for fast display
     * 
     * @param array $formatted_flights All formatted flights
     * @param int $initial_count Number of flights to send initially (default: 5)
     * @return array Initial batch of flights with metadata
     */
    public static function stream_initial_results($formatted_flights, $initial_count = 5) {
        if (empty($formatted_flights) || !is_array($formatted_flights)) {
            return array(
                'flights' => array(),
                'meta' => array('count' => 0, 'currency' => 'USD'),
                '_streaming' => true,
                '_streaming_initial' => true,
                '_total_count' => 0,
                '_remaining_count' => 0
            );
        }
        
        // Ensure initial_count is valid
        $initial_count = max(1, min(50, intval($initial_count)));
        
        // Get first N flights
        $initial_flights = array_slice($formatted_flights, 0, $initial_count);
        $total_count = count($formatted_flights);
        $remaining_count = max(0, $total_count - $initial_count);
        
        // Extract meta from first flight if available
        $currency = 'USD';
        if (!empty($initial_flights) && isset($initial_flights[0]['price']['currency'])) {
            $currency = $initial_flights[0]['price']['currency'];
        }
        
        return array(
            'flights' => $initial_flights,
            'meta' => array(
                'count' => count($initial_flights),
                'currency' => $currency,
                'total_available' => $total_count
            ),
            '_streaming' => true,
            '_streaming_initial' => true,
            '_total_count' => $total_count,
            '_remaining_count' => $remaining_count,
            '_initial_count' => count($initial_flights)
        );
    }
    
    /**
     * Stream remaining results
     * 
     * Returns remaining flights after initial batch
     * 
     * @param array $formatted_flights All formatted flights
     * @param int $initial_count Number of flights already sent
     * @return array Remaining flights with metadata
     */
    public static function stream_remaining_results($formatted_flights, $initial_count = 5) {
        if (empty($formatted_flights) || !is_array($formatted_flights)) {
            return array(
                'flights' => array(),
                'meta' => array('count' => 0, 'currency' => 'USD'),
                '_streaming' => true,
                '_streaming_remaining' => true
            );
        }
        
        // Ensure initial_count is valid
        $initial_count = max(0, min(50, intval($initial_count)));
        
        // Get remaining flights
        $remaining_flights = array_slice($formatted_flights, $initial_count);
        
        // Extract currency from first flight if available
        $currency = 'USD';
        if (!empty($remaining_flights) && isset($remaining_flights[0]['price']['currency'])) {
            $currency = $remaining_flights[0]['price']['currency'];
        }
        
        return array(
            'flights' => $remaining_flights,
            'meta' => array(
                'count' => count($remaining_flights),
                'currency' => $currency
            ),
            '_streaming' => true,
            '_streaming_remaining' => true,
            '_remaining_count' => count($remaining_flights)
        );
    }
    
    /**
     * Check if streaming is enabled in settings
     * 
     * @return bool True if streaming is enabled
     */
    public static function is_enabled() {
        $settings = get_option('amadex_performance_settings', array());
        return isset($settings['enable_streaming_response']) && $settings['enable_streaming_response'] === '1';
    }
    
    /**
     * Get streaming initial count from settings
     * 
     * @return int Number of flights to stream initially (default: 5)
     */
    public static function get_initial_count() {
        $settings = get_option('amadex_performance_settings', array());
        $count = isset($settings['streaming_initial_count']) ? intval($settings['streaming_initial_count']) : 5;
        
        // Ensure minimum of 1 and maximum of 50
        return max(1, min(50, $count));
    }
    
    /**
     * Format flights in batches for streaming
     * 
     * Formats first batch quickly, returns immediately
     * Can be used to format remaining flights in background
     * 
     * @param array $raw_flights Raw flight data from API
     * @param array $search_params Search parameters
     * @param int $batch_size Number of flights to format in this batch
     * @param int $offset Starting index for batch
     * @return array Formatted flights for this batch
     */
    public static function format_flights_batch($raw_flights, $search_params, $batch_size = 10, $offset = 0) {
        if (empty($raw_flights) || !is_array($raw_flights)) {
            return array();
        }
        
        // Ensure valid parameters
        $batch_size = max(1, min(50, intval($batch_size)));
        $offset = max(0, intval($offset));
        
        // Get batch slice
        $batch = array_slice($raw_flights, $offset, $batch_size);
        
        if (empty($batch)) {
            return array();
        }
        
        // Format this batch using API class formatter
        // Note: This requires access to Amadex_API::format_flight_results()
        // For now, return raw batch - actual formatting will be done by API class
        return $batch;
    }
}
