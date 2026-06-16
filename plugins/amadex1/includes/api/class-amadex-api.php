<?php
/**
 * Amadeus API integration with enhanced features
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Amadex API Class
 */
class Amadex_API {
    
    /**
     * API base URL
     */
    private $api_base_url;
    
    /**
     * API credentials
     */
    private $api_key;
    private $api_secret;
    
    /**
     * Token management
     */
    private $token_cache_key = 'amadex_amadeus_token';
    private $token_expiry_key = 'amadex_amadeus_token_expiry';

    /**
     * API request timeout in seconds (from Advanced Settings)
     */
    private $api_timeout = 30;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_api_settings();
    }
    
    /**
     * Initialize API settings
     */
    private function init_api_settings() {
        $api_settings = get_option('amadex_api_settings', array());
        $environment = $api_settings['environment'] ?? 'test';
        
          // Support both 'live' and 'production' values for environment
        $is_production = ($environment === 'live' || $environment === 'production');
        $this->api_base_url = $is_production ? 'https://api.amadeus.com' : 'https://test.api.amadeus.com';
        
        // Trim whitespace from credentials to avoid authentication issues
        $this->api_key = isset($api_settings['api_key']) ? trim($api_settings['api_key']) : '';
        $this->api_secret = isset($api_settings['api_secret']) ? trim($api_settings['api_secret']) : '';
        
        // Log environment and credential status (without exposing secrets)
        amadex_log('Amadex API: Environment: ' . $environment . ' (' . ($is_production ? 'Production' : 'Test') . ')');

        // API timeout from Advanced Settings (1-60 seconds, default 30 for Amadeus long-haul)
        $advanced = get_option('amadex_advanced_settings', array());
        $timeout = isset($advanced['timeout']) ? intval($advanced['timeout']) : 30;
        $this->api_timeout = max(1, min(60, $timeout));

        amadex_log('Amadex API: Base URL: ' . $this->api_base_url);
        amadex_log('Amadex API: API Key Length: ' . strlen($this->api_key));
        amadex_log('Amadex API: API Secret Length: ' . strlen($this->api_secret));
        
        // Warn if using test environment in production
        if (!$is_production) {
            amadex_log('Amadex API: WARNING - Using TEST environment. Test API has limited flight data. Switch to LIVE/PRODUCTION for real flight searches.', 'warning');
        }
    }
    
    /**
     * Get access token with daily caching
     */
    public function get_access_token() {
        // Check if we have a valid token for today
        $today = date('Y-m-d');
        $cached_date = get_transient($this->token_expiry_key);
        $cached_token = get_transient($this->token_cache_key);
        
        // Always clear old tokens to avoid 401 errors
        if ($cached_date !== $today) {
            delete_transient($this->token_cache_key);
            delete_transient($this->token_expiry_key);
            amadex_log('Amadex API: Cleared expired token for date: ' . $cached_date);
        }
        
        if ($cached_token && $cached_date === $today) {
            amadex_log('Amadex API: Using cached token for ' . $today);
            return $cached_token;
        }
        
         // Validate credentials are not empty
        if (empty($this->api_key) || empty($this->api_secret)) {
            amadex_log('Amadex API: Missing credentials - Key: ' . (empty($this->api_key) ? 'EMPTY' : 'SET (' . strlen($this->api_key) . ' chars)') . ', Secret: ' . (empty($this->api_secret) ? 'EMPTY' : 'SET (' . strlen($this->api_secret) . ' chars)'), 'error');
            return new WP_Error('missing_credentials', __('Amadeus API credentials not configured. Please check your API settings.', 'amadex'));
        }
        
        // Additional validation - check if keys look valid (not just whitespace)
        if (trim($this->api_key) === '' || trim($this->api_secret) === '') {
            amadex_log('Amadex API: Credentials contain only whitespace', 'error');
            return new WP_Error('invalid_credentials', __('API credentials appear to be invalid. Please check your API Key and API Secret in settings.', 'amadex'));
        }
        
        $url = $this->api_base_url . '/v1/security/oauth2/token';
        amadex_log('Amadex API: Requesting token from: ' . $url);
        amadex_log('Amadex API: Using API Key: ' . substr($this->api_key, 0, 10) . '... (Length: ' . strlen($this->api_key) . ')');
        
        // Build request body properly
        $body = array(
            'grant_type' => 'client_credentials',
            'client_id' => trim($this->api_key),
            'client_secret' => trim($this->api_secret),
        );
        
        amadex_log('Amadex API: Request body keys: ' . implode(', ', array_keys($body)));
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ),
            'body' => $body,
            'timeout' => 30,
            'sslverify' => true,
        ));
        
        if (is_wp_error($response)) {
            amadex_log('Amadex API: WP Error: ' . $response->get_error_message(), 'error');
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        amadex_log('Amadex API: Response Code: ' . $response_code);
        amadex_log('Amadex API: Response Body: ' . $response_body);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = '';
            
            if (isset($error_data['error'])) {
                if ($error_data['error'] === 'invalid_client') {
                    $error_message = __('API Error: Invalid client credentials. Please verify your API Key and API Secret are correct in the plugin settings. Make sure you are using the correct credentials for the selected environment (Test/Production).', 'amadex');
                    // Clear token cache on invalid credentials
                    delete_transient($this->token_cache_key);
                    delete_transient($this->token_expiry_key);
                } else {
                    $error_message = sprintf(__('API Error (%s): %s', 'amadex'), 
                        $error_data['error'], 
                        isset($error_data['error_description']) ? $error_data['error_description'] : $response_body
                    );
                }
            } else {
                $error_message = sprintf(__('API Error (Code %d): %s', 'amadex'), $response_code, $response_body);
            }
            
            amadex_log('Amadex API: ' . $error_message, 'error');
            amadex_log('Amadex API: Full error response: ' . $response_body, 'error');
            return new WP_Error('api_error', $error_message, array('code' => $response_code, 'response' => $error_data));
        }
        
        $data = json_decode($response_body, true);
        
        if (!isset($data['access_token'])) {
            amadex_log('Amadex API: No access_token in response: ' . print_r($data, true), 'error');
            return new WP_Error('invalid_response', __('Invalid API response - no access token received', 'amadex'));
        }
        
        $token = $data['access_token'];
        amadex_log('Amadex API: Token received successfully');
        
        // Cache token until end of day (24 hours)
        $end_of_day = strtotime('tomorrow') - time();
        set_transient($this->token_cache_key, $token, $end_of_day);
        set_transient($this->token_expiry_key, $today, $end_of_day);
        
        return $token;
    }
    
    /**
     * Force refresh access token
     */
    public function refresh_access_token() {
        // Clear existing tokens
        delete_transient($this->token_cache_key);
        delete_transient($this->token_expiry_key);
        
        // Get new token
        return $this->get_access_token();
    }
    
    /**
     * Clear all cached tokens
     */
    public function clear_token_cache() {
        delete_transient($this->token_cache_key);
        delete_transient($this->token_expiry_key);
        amadex_log('Amadex API: Cleared all token cache');
        return true;
    }
    
    /**
     * Search flights with enhanced filtering
     */
    public function search_flights($params, $retry_count = 0) {
        // Start performance timing
        if (class_exists('Amadex_Performance')) {
            Amadex_Performance::start_timer('api_search_flights');
        }
        
        $token = $this->get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        
        $url = $this->api_base_url . '/v2/shopping/flight-offers';
        
        // Get initial results count from settings (default: 50)
        $initial_count = function_exists('amadex_get_initial_results_count') ? amadex_get_initial_results_count() : 50;
        
        // Check for progressive loading mode (quick results - first 30 immediately)
        $progressive_enabled = false;
        $progressive_count = 30;
        if (isset($params['progressive_loading']) && $params['progressive_loading'] === true) {
            $settings = get_option('amadex_performance_settings', array());
            $progressive_enabled = isset($settings['enable_progressive_loading']) && $settings['enable_progressive_loading'] === '1';
            if ($progressive_enabled) {
                $progressive_count = isset($settings['progressive_load_count']) ? intval($settings['progressive_load_count']) : 30;
                $progressive_count = max(10, min(100, $progressive_count));
            }
        }
        
        // Check if this is initial load or full load
        $is_initial_load = isset($params['initial_load']) ? (bool) $params['initial_load'] : true;
        
        // Progressive loading: fetch only quick results count for immediate display
        if ($progressive_enabled && $is_initial_load) {
            $max_results = $progressive_count; // Fetch only 30 for immediate display
        } else {
            $max_results = $is_initial_load ? $initial_count : 250;
        }
        
        $query_params = array(
            'originLocationCode' => strtoupper($params['origin']),
            'destinationLocationCode' => strtoupper($params['destination']),
            'departureDate' => $params['departure_date'],
            'adults' => intval($params['adults']),
            'max' => $max_results
        );
        
        // Add optional parameters
        if (!empty($params['return_date'])) {
            $query_params['returnDate'] = $params['return_date'];
        }
        
        if (!empty($params['children'])) {
            $query_params['children'] = intval($params['children']);
        }
        
        if (!empty($params['infants'])) {
            $query_params['infants'] = intval($params['infants']);
        }
        
        // Validate and add travel class parameter
        if (!empty($params['travel_class'])) {
            // Normalize travel class to uppercase
            $travel_class = strtoupper(trim($params['travel_class']));
            
            // Map common variations to Amadeus standard values
            $travel_class_map = array(
                'PREMIUM' => 'PREMIUM_ECONOMY',
                'PREMIUM ECONOMY' => 'PREMIUM_ECONOMY',
                'PREMIUM-ECONOMY' => 'PREMIUM_ECONOMY',
                'BUSINESS CLASS' => 'BUSINESS',
                'FIRST CLASS' => 'FIRST'
            );
            
            if (isset($travel_class_map[$travel_class])) {
                $travel_class = $travel_class_map[$travel_class];
            }
            
            // Validate travel class is one of the accepted Amadeus API values
            $valid_classes = array('ECONOMY', 'PREMIUM_ECONOMY', 'BUSINESS', 'FIRST');
            if (in_array($travel_class, $valid_classes)) {
                $query_params['travelClass'] = $travel_class;
                amadex_log('Amadex API: Using travel class: ' . $travel_class);
            } else {
                amadex_log('Amadex API: Invalid travel class "' . $travel_class . '" received. Valid values are: ECONOMY, PREMIUM_ECONOMY, BUSINESS, FIRST. Skipping travelClass parameter.');
                // Don't add invalid travelClass to avoid API errors
            }
        }
        
        if (!empty($params['currency'])) {
            $query_params['currencyCode'] = $params['currency'];
        }
        
        if (!empty($params['non_stop'])) {
            $query_params['nonStop'] = $params['non_stop'];
        }
        
        $url = add_query_arg($query_params, $url);
        
        amadex_log('Amadex API: Searching flights with URL: ' . $url);
        amadex_log('Amadex API: Query parameters: ' . print_r($query_params, true));
        
        // Time the API call
        if (class_exists('Amadex_Performance')) {
            Amadex_Performance::start_timer('amadeus_api_call');
        }
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
            'timeout' => $this->api_timeout,
        ));
        
		if (is_wp_error($response)) {
            amadex_log('Amadex API: WP Error in search: ' . $response->get_error_message(), 'error');
            if (class_exists('Amadex_Performance')) {
                Amadex_Performance::end_timer('amadeus_api_call');
                Amadex_Performance::end_timer('api_search_flights');
            }
			return $response;
		}
        
        // End API call timing
        if (class_exists('Amadex_Performance')) {
            $api_time = Amadex_Performance::end_timer('amadeus_api_call', array(
                'response_size' => strlen(wp_remote_retrieve_body($response))
            ));
		}
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        amadex_log('Amadex API: Search Response Code: ' . $response_code);
        
        // Log response body (truncated for success, full for errors)
        if ($response_code === 200) {
            amadex_log('Amadex API: Search Response Body (first 500 chars): ' . substr($response_body, 0, 500));
        } else {
            amadex_log('Amadex API: Full Error Response: ' . $response_body, 'error');
        }
        
        if ($response_code !== 200) {
            // Check for authentication errors (401, 403) or invalid token (code 6)
            $error_data = json_decode($response_body, true);
            $should_retry = false;
            $invalid_travel_class = false;
            
            if ($response_code === 401 || $response_code === 403) {
                amadex_log('Amadex API: Authentication error (401/403), clearing token cache and retrying');
                $should_retry = true;
            } elseif (isset($error_data['errors']) && is_array($error_data['errors'])) {
                foreach ($error_data['errors'] as $error) {
                    if (isset($error['code']) && $error['code'] == 6) {
                        amadex_log('Amadex API: Invalid token detected (code 6), clearing cache and retrying');
                        $should_retry = true;
                        break;
                    }
                    // Check for invalid parameter errors (often code 47700 or similar for invalid travelClass)
                    if (isset($error['code']) && (strpos($error['code'], '477') !== false || strpos(strtolower($error['detail'] ?? ''), 'travelclass') !== false || strpos(strtolower($error['title'] ?? ''), 'invalid') !== false)) {
                        amadex_log('Amadex API: Invalid travelClass parameter detected. Error: ' . print_r($error, true));
                        $invalid_travel_class = true;
                    }
                }
            }
            
            // If invalid travelClass, try again without it
            if ($invalid_travel_class && !empty($params['travel_class'])) {
                amadex_log('Amadex API: Retrying search without travelClass parameter due to invalid value');
                unset($params['travel_class']);
                if (class_exists('Amadex_Performance')) {
                    Amadex_Performance::end_timer('api_search_flights');
                }
                return $this->search_flights($params, $retry_count);
            }
            
            if ($should_retry && $retry_count < 1) {
                // Clear token cache and retry once
                delete_transient($this->token_cache_key);
                delete_transient($this->token_expiry_key);
                amadex_log('Amadex API: Retrying search with fresh token (attempt ' . ($retry_count + 1) . ')');
                if (class_exists('Amadex_Performance')) {
                    Amadex_Performance::end_timer('api_search_flights');
                }
                return $this->search_flights($params, $retry_count + 1);
            }
            
            $error_message = sprintf(__('Flight search failed (Code %d): %s', 'amadex'), $response_code, $response_body);
            amadex_log('Amadex API: ' . $error_message, 'error');
            if (class_exists('Amadex_Performance')) {
                Amadex_Performance::end_timer('api_search_flights');
            }
            return new WP_Error('api_error', $error_message);
        }
        
        // Time JSON parsing
        if (class_exists('Amadex_Performance')) {
            Amadex_Performance::start_timer('json_parse');
        }
        
        $data = json_decode($response_body, true);
        
        if (class_exists('Amadex_Performance')) {
            Amadex_Performance::end_timer('json_parse', array(
                'response_size' => strlen($response_body),
                'flights_count' => isset($data['data']) ? count($data['data']) : 0
            ));
        }
        
        // Save raw response to debug file for analysis (only if 0 flights)
        if (isset($data['data']) && count($data['data']) === 0) {
            $wp_content_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR : (defined('ABSPATH') ? ABSPATH . 'wp-content' : '');
            if ($wp_content_dir) {
                $debug_file = $wp_content_dir . '/amadeus-debug-response.json';
                @file_put_contents($debug_file, json_encode(array(
                    'timestamp' => date('Y-m-d H:i:s'),
                    'request_params' => $params,
                    'api_url' => $url,
                    'response_code' => $response_code,
                    'response_body' => $response_body,
                    'parsed_data' => $data
                ), JSON_PRETTY_PRINT));
                amadex_log('Amadex API: Saved debug response to: ' . $debug_file);
            }
        }
        
        // Log response data for debugging
        if (isset($data['meta'])) {
            amadex_log('Amadex API: Response meta - count: ' . ($data['meta']['count'] ?? 0) . ', currency: ' . ($data['meta']['currency'] ?? 'N/A'));
        } else {
            amadex_log('Amadex API: Warning - No meta in response. Response structure: ' . print_r(array_keys($data ?? array()), true), 'warning');
        }
        
        if (isset($data['data'])) {
            $flight_count = is_array($data['data']) ? count($data['data']) : 0;
            amadex_log('Amadex API: Response data - ' . $flight_count . ' flight offers returned');
            
            // If 0 flights and travel_class was specified, try again without it as fallback
            if ($flight_count === 0 && !empty($params['travel_class']) && $retry_count === 0) {
                amadex_log('Amadex API: ZERO flights returned with travelClass=' . $params['travel_class'] . '. Retrying without travelClass filter...');
                $fallback_params = $params;
                unset($fallback_params['travel_class']);
                $fallback_result = $this->search_flights($fallback_params, 1); // Use retry_count=1 to prevent infinite loop
                
                // If fallback returns flights, use those instead
                if (!is_wp_error($fallback_result) && isset($fallback_result['flights']) && count($fallback_result['flights']) > 0) {
                    amadex_log('Amadex API: Fallback search (without travelClass) returned ' . count($fallback_result['flights']) . ' flights. Using fallback results.');
                    if (class_exists('Amadex_Performance')) {
                        Amadex_Performance::end_timer('api_search_flights');
                    }
                    return $fallback_result;
                } else {
                    amadex_log('Amadex API: Fallback search also returned 0 flights.');
                }
            }
            
            // If 0 flights, log the search parameters for debugging
            if ($flight_count === 0) {
                amadex_log('Amadex API: ZERO flights returned. Search params: ' . print_r(array(
                    'origin' => $params['origin'] ?? '',
                    'destination' => $params['destination'] ?? '',
                    'departure_date' => $params['departure_date'] ?? '',
                    'return_date' => $params['return_date'] ?? '',
                    'adults' => $params['adults'] ?? 0,
                    'travel_class' => $params['travel_class'] ?? 'NOT SET',
                    'max' => $max_results,
                    'api_base_url' => $this->api_base_url
                ), true));
                amadex_log('Amadex API: Full API URL was: ' . $url);
                amadex_log('Amadex API: Response body (first 1000 chars): ' . substr($response_body, 0, 1000));
            }
        } else {
            amadex_log('Amadex API: Warning - No data array in response. Response keys: ' . print_r(array_keys($data ?? array()), true), 'warning');
            amadex_log('Amadex API: Full response body: ' . $response_body, 'error');
        }
        
        // Time result formatting
        if (class_exists('Amadex_Performance')) {
            Amadex_Performance::start_timer('format_results');
        }
        
        $formatted = $this->format_flight_results($data, $params);
        
        if (class_exists('Amadex_Performance')) {
            Amadex_Performance::end_timer('format_results', array(
                'flights_count' => isset($formatted['flights']) ? count($formatted['flights']) : 0
            ));
            Amadex_Performance::end_timer('api_search_flights');
        }
        
        return $formatted;
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection() {
        $token = $this->get_access_token();
        if (is_wp_error($token)) {
            return array(
                'success' => false,
                'message' => $token->get_error_message(),
                'details' => array(
                    'api_key_set' => !empty($this->api_key),
                    'api_secret_set' => !empty($this->api_secret),
                    'api_base_url' => $this->api_base_url
                )
            );
        }
        
        return array(
            'success' => true,
            'message' => __('API connection successful', 'amadex'),
            'token_length' => strlen($token),
            'details' => array(
                'api_key_set' => !empty($this->api_key),
                'api_secret_set' => !empty($this->api_secret),
                'api_base_url' => $this->api_base_url
            )
        );
    }
    
    /**
     * Format flight results with enhanced data
     */
    private function format_flight_results($data, $search_params = array()) {
        $formatted_results = array(
            'meta' => array(
                'count' => isset($data['meta']['count']) ? $data['meta']['count'] : 0,
                'currency' => isset($data['meta']['currency']) ? $data['meta']['currency'] : 'USD'
            ),
            'flights' => array(),
            'filters' => array(
                'airlines' => array(),
                'stops' => array(),
                'price_range' => array('min' => 0, 'max' => 0),
                'departure_times' => array(),
                'arrival_times' => array()
            )
        );
        
        if (empty($data['data'])) {
            return $formatted_results;
        }
        
        $airlines = array();
        $stops = array();
        $prices = array();
        $departure_times = array();
        $arrival_times = array();
        
        // IMPORTANT: If travelClass was sent to API, don't filter client-side - API already filtered
        // Only filter client-side if we're showing all cabins (no travelClass sent to API)
        $api_filtered_by_cabin = !empty($search_params['travel_class']);
        
        // Get requested cabin class from search params (for logging only if API didn't filter)
        $requested_cabin = isset($search_params['travel_class']) ? strtoupper(trim($search_params['travel_class'])) : '';
        
        // STRICT cabin filtering - only show exact cabin class requested (no MIXED, no downgrades)
        // This is used ONLY if API didn't filter (show_all_cabins = true)
        // When API filters, we trust the API results
        
        foreach ($data['data'] as $offer) {
            // Only do client-side cabin filtering if API didn't filter (i.e., show_all_cabins mode)
            if (!$api_filtered_by_cabin && !empty($requested_cabin)) {
                // Check cabin class from fareDetailsBySegment (this is the correct location)
                $offer_cabins = array();
                if (!empty($offer['travelerPricings'])) {
                    foreach ($offer['travelerPricings'] as $traveler_pricing) {
                        if (!empty($traveler_pricing['fareDetailsBySegment'])) {
                            foreach ($traveler_pricing['fareDetailsBySegment'] as $fare_detail) {
                                $segment_cabin = strtoupper(trim($fare_detail['cabin'] ?? ''));
                                if (!empty($segment_cabin)) {
                                    $offer_cabins[] = $segment_cabin;
                                }
                            }
                        }
                    }
                }
                
                // STRICT matching - only accept exact cabin class (no MIXED, no other cabins)
                $offer_matches = false;
                
                // Check if ALL segments match the requested cabin (strict requirement)
                if (!empty($offer_cabins)) {
                    // All segments must be the requested cabin class
                    $all_match = true;
                    foreach ($offer_cabins as $cabin) {
                        if ($cabin !== $requested_cabin) {
                            $all_match = false;
                            break;
                        }
                    }
                    $offer_matches = $all_match;
                } else {
                    // If no cabin found in fareDetailsBySegment, skip it (we need explicit cabin match)
                    $offer_matches = false;
                }
                
                // Skip this offer if it doesn't match the requested cabin class exactly
                if (!$offer_matches) {
                    amadex_log('Amadex API: STRICT filtering - requested cabin: ' . $requested_cabin . ', offer cabins: ' . (empty($offer_cabins) ? 'NONE' : implode(', ', $offer_cabins)) . ' - SKIPPING (not exact match)');
                    continue;
                } else {
                    amadex_log('Amadex API: STRICT filtering - requested cabin: ' . $requested_cabin . ', offer cabins: ' . implode(', ', $offer_cabins) . ' - INCLUDING (exact match)');
                }
            }
            
            // If API filtered by cabin, log the cabin for debugging but trust API results
            if ($api_filtered_by_cabin && !empty($requested_cabin)) {
                $offer_cabins = array();
                if (!empty($offer['travelerPricings'])) {
                    foreach ($offer['travelerPricings'] as $traveler_pricing) {
                        if (!empty($traveler_pricing['fareDetailsBySegment'])) {
                            foreach ($traveler_pricing['fareDetailsBySegment'] as $fare_detail) {
                                $segment_cabin = strtoupper(trim($fare_detail['cabin'] ?? ''));
                                if (!empty($segment_cabin)) {
                                    $offer_cabins[] = $segment_cabin;
                                }
                            }
                        }
                    }
                }
                amadex_log('Amadex API: API-filtered offer - requested: ' . $requested_cabin . ', actual cabins: ' . (empty($offer_cabins) ? 'NONE' : implode(', ', $offer_cabins)) . ' - INCLUDING (API filtered)');
            }
            
            // Extract detailed meal, baggage, and amenities information from travelerPricings
            $has_meal = false;
            $has_baggage = false;
            $detailed_baggage = array();
            $detailed_amenities = array();
            $segment_amenities = array(); // Amenities per segment
            $fare_basis_codes = array(); // Fare basis codes per segment
            $booking_classes = array(); // Booking class codes per segment
            $branded_fares = array(); // Branded fare names per segment
            
            if (!empty($offer['travelerPricings'])) {
                foreach ($offer['travelerPricings'] as $traveler_pricing) {
                    // Check fareDetailsBySegment for amenities (meal) and baggage
                    if (!empty($traveler_pricing['fareDetailsBySegment'])) {
                        foreach ($traveler_pricing['fareDetailsBySegment'] as $segment_index => $fare_detail) {
                            // Extract fare basis code
                            if (!empty($fare_detail['fareBasis'])) {
                                $fare_basis_codes[$segment_index] = $fare_detail['fareBasis'];
                            }
                            
                            // Extract booking class code
                            if (!empty($fare_detail['class'])) {
                                $booking_classes[$segment_index] = $fare_detail['class'];
                            }
                            
                            // Extract branded fare name
                            if (!empty($fare_detail['brandedFare'])) {
                                $branded_fares[$segment_index] = $fare_detail['brandedFare'];
                            }
                            // Extract detailed amenities per segment
                            if (!empty($fare_detail['amenities'])) {
                                $segment_amenity_list = array();
                                foreach ($fare_detail['amenities'] as $amenity) {
                                    $amenity_code = strtoupper($amenity['code'] ?? '');
                                    $amenity_name = $amenity['name'] ?? $amenity_code;
                                    
                                    // Comprehensive meal codes from Amadeus API
                                    $meal_codes = array('MEAL', 'FOOD', 'SNACK', 'BEVERAGE', 'RESTAURANT', 'FRESH_FOOD', 'MEAL_BOXED', 'MEAL_COLD', 'MEAL_HOT', 'MEAL_REFRESHMENT');
                                    if (in_array($amenity_code, $meal_codes)) {
                                        $has_meal = true;
                                    }
                                    
                                    $segment_amenity_list[] = array(
                                        'code' => $amenity_code,
                                        'name' => $amenity_name
                                    );
                                    $detailed_amenities[$amenity_code] = $amenity_name;
                                }
                                if (!empty($segment_amenity_list)) {
                                    $segment_amenities[$segment_index] = $segment_amenity_list;
                                }
                            }
                            
                            // Extract detailed baggage information
                            if (!empty($fare_detail['includedCheckedBags'])) {
                                $bag_data = $fare_detail['includedCheckedBags'];
                                $quantity = is_array($bag_data) ? ($bag_data['quantity'] ?? 0) : intval($bag_data);
                                
                                if ($quantity > 0) {
                                    $has_baggage = true;
                                    $weight = isset($bag_data['weight']) ? intval($bag_data['weight']) : null;
                                    $weight_unit = isset($bag_data['weightUnit']) ? $bag_data['weightUnit'] : 'KG';
                                    
                                    $detailed_baggage[] = array(
                                        'type' => 'checked',
                                        'quantity' => $quantity,
                                        'weight' => $weight,
                                        'weight_unit' => $weight_unit,
                                        'segment' => $segment_index
                                    );
                                }
                            }
                            
                            // Check carry-on bags
                            if (!empty($fare_detail['includedCarryOnBags'])) {
                                $carry_on_data = $fare_detail['includedCarryOnBags'];
                                $carry_on_qty = is_array($carry_on_data) ? ($carry_on_data['quantity'] ?? 0) : intval($carry_on_data);
                                
                                if ($carry_on_qty > 0) {
                                    $detailed_baggage[] = array(
                                        'type' => 'carry_on',
                                        'quantity' => $carry_on_qty,
                                        'segment' => $segment_index
                                    );
                                }
                            }
                            
                            // Also check includedServices field (alternative location)
                            if (!empty($fare_detail['includedServices'])) {
                                foreach ($fare_detail['includedServices'] as $service) {
                                    $service_code = strtoupper($service['code'] ?? '');
                                    if (in_array($service_code, array('BAGGAGE', 'CARRY_ON', 'CHECKED_BAG', 'BAG'))) {
                                        $has_baggage = true;
                                        if (empty($detailed_baggage)) {
                                            $detailed_baggage[] = array(
                                                'type' => strtolower($service_code),
                                                'quantity' => 1,
                                                'segment' => $segment_index
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Also check at offer level for additional service information
            if (!empty($offer['services'])) {
                foreach ($offer['services'] as $service) {
                    $service_type = strtoupper($service['type'] ?? '');
                    if (in_array($service_type, array('MEAL', 'FOOD', 'SNACK', 'BEVERAGE'))) {
                        $has_meal = true;
                    }
                    if (in_array($service_type, array('BAGGAGE', 'CARRY_ON', 'CHECKED_BAG'))) {
                        $has_baggage = true;
                    }
                }
            }
            
            // Get original price from API
            $original_total = floatval($offer['price']['total'] ?? 0);
            $original_base = floatval($offer['price']['base'] ?? 0);

                 // Calculate taxes: total - base
                 $original_taxes = $original_total - $original_base;
            
            // Get airline code for markup calculation (use first validating airline)
            $airline_code = '';
            if (!empty($offer['validatingAirlineCodes']) && is_array($offer['validatingAirlineCodes'])) {
                $airline_code = $offer['validatingAirlineCodes'][0] ?? '';
            }
            
            // Get currency
            $currency = isset($data['meta']['currency']) ? $data['meta']['currency'] : 'USD';
            
            // Check if Pricing Rules Engine is enabled
            $use_rules_engine = class_exists('Amadex_Pricing_Rules') && Amadex_Pricing_Rules::is_enabled();
            
            if ($use_rules_engine) {
                // Use Pricing Rules Engine (includes markup integration)
                $pricing_result = Amadex_Pricing_Rules::calculate_pricing($original_total, $currency, $airline_code);
                
                // P_display is shown to users
                $adjusted_total = $pricing_result['display_total'];
                
                // Calculate base and taxes proportionally based on P_display
                if ($original_total > 0) {
                    $display_ratio = $adjusted_total / $original_total;
                    $adjusted_base = $original_base * $display_ratio;
                    $adjusted_taxes = $original_taxes * $display_ratio;
                } else {
                    $adjusted_base = $original_base;
                    $adjusted_taxes = $original_taxes;
                }
                
                // Store pricing snapshot for booking (includes markup info)
                $pricing_snapshot = array(
                    'original_total' => $original_total,
                    'display_total' => $pricing_result['display_total'],
                    'charge_total' => $pricing_result['charge_total'],
                    'pricing_rule_id' => $pricing_result['rule_id'],
                    'pricing_rule_name' => $pricing_result['rule_name'],
                    'discount_percent' => $pricing_result['discount_percent'],
                    'flat_fee_amount' => $pricing_result['flat_fee_amount'],
                    'markup_applied' => $pricing_result['markup_applied'] ?? 0,
                    'price_after_markup' => $pricing_result['price_after_markup'] ?? $original_total
                );
                
                // Store original price for reference
                $original_price = $original_total;
            } else {
                // Use legacy pricing markup
             // Apply pricing markup if available - PROPORTIONAL to base fare and taxes
             if (class_exists('Amadex_Pricing')) {
                $price_result = Amadex_Pricing::calculate_price_with_markup($original_total, $airline_code, $original_base, $original_taxes);
                
                // Handle both array (new format) and float (legacy) return types
                if (is_array($price_result)) {
                    $adjusted_total = floatval($price_result['total'] ?? $original_total);
                    $adjusted_base = floatval($price_result['base'] ?? $original_base);
                    $adjusted_taxes = floatval($price_result['taxes'] ?? $original_taxes);
                } else {
                    // Legacy format - backward compatibility
                    $adjusted_total = floatval($price_result);
                    // Calculate adjusted base and taxes proportionally
                    if ($original_total > 0) {
                        $ratio = $adjusted_total / $original_total;
                        $adjusted_base = $original_base * $ratio;
                        $adjusted_taxes = $original_taxes * $ratio;
                    } else {
                        $adjusted_base = $original_base;
                        $adjusted_taxes = $original_taxes;
                    }
                }
                
                // Store original price for reference
                $original_price = $original_total;
            } else {
                // If pricing class not available, use original prices
                $adjusted_total = $original_total;
                $adjusted_base = $original_base;
                $adjusted_taxes = $original_taxes;
                $original_price = $original_total;
                }
            }
            
            // Build price array
            $price_array = array(
                    'total' => $adjusted_total,
                    'original_total' => $original_total, // Keep original for reference
                    'currency' => $offer['price']['currency'] ?? 'USD',
                    'base' => $adjusted_base,
                    'original_base' => $original_base, // Keep original for reference
                    'taxes' => $adjusted_taxes, // Adjusted taxes (proportional discount/markup)
                    'original_taxes' => $original_taxes, // Keep original taxes for reference
                    'fees' => $offer['price']['fees'] ?? array(),
                    // Store price breakdown for easy access in confirmation page
                    'price_breakdown' => array(
                        'base_fare' => $adjusted_base,
                        'taxes' => $adjusted_taxes,
                        'total' => $adjusted_total,
                        'original_base_fare' => $original_base,
                        'original_taxes' => $original_taxes,
                        'original_total' => $original_total,
                        'currency' => $offer['price']['currency'] ?? 'USD'
                    )
            );
            
            // Add pricing snapshot if rules engine was used
            if ($use_rules_engine && isset($pricing_snapshot)) {
                $price_array['pricing_charge_total'] = $pricing_snapshot['charge_total'];
                $price_array['pricing_rule_id'] = $pricing_snapshot['pricing_rule_id'];
                $price_array['pricing_rule_name'] = $pricing_snapshot['pricing_rule_name'];
                $price_array['discount_percent'] = $pricing_snapshot['discount_percent'];
                $price_array['flat_fee_amount'] = $pricing_snapshot['flat_fee_amount'];
                $price_array['pricing_snapshot'] = $pricing_snapshot; // Full snapshot for booking
            }
            
            $flight = array(
                'id' => $offer['id'] ?? '',
                'price' => $price_array,
                'itineraries' => array(),
                'validating_airline_codes' => $offer['validatingAirlineCodes'] ?? array(),
                'traveler_pricings' => $offer['travelerPricings'] ?? array(),
                'last_ticketing_date' => $offer['lastTicketingDate'] ?? '',
                'number_of_bookable_seats' => $offer['numberOfBookableSeats'] ?? 0,
                'source' => $offer['source'] ?? '',
                'has_meal' => $has_meal,
                'has_baggage' => $has_baggage,
                'detailed_baggage' => $detailed_baggage,
                'detailed_amenities' => $detailed_amenities,
                'segment_amenities' => $segment_amenities,
                'fare_basis_codes' => $fare_basis_codes,
                'booking_classes' => $booking_classes,
                'branded_fares' => $branded_fares,
                // Store raw offer for seat selection pricing API
                'rawOffer' => $offer
            );
            
            // Process itineraries
            if (!empty($offer['itineraries'])) {
                foreach ($offer['itineraries'] as $itinerary) {
                    $itinerary_data = array(
                        'duration' => $itinerary['duration'] ?? '',
                        'segments' => array(),
                        'stops' => count($itinerary['segments']) - 1
                    );
                    
                    // Process segments with enhanced layover analysis
                    $segments_count = count($itinerary['segments']);
                    $layovers = array();
                    
                    if (!empty($itinerary['segments'])) {
                        foreach ($itinerary['segments'] as $index => $segment) {
                            // Extract operating carrier details (actual airline operating the flight)
                            $operating_carrier = $segment['operating'] ?? array();
                            $is_operated_by_different = !empty($operating_carrier['carrierCode']) && 
                                                       ($operating_carrier['carrierCode'] !== ($segment['carrierCode'] ?? ''));
                            
                            $segment_data = array(
                                'departure' => array(
                                    'iata_code' => $segment['departure']['iataCode'] ?? '',
                                    'terminal' => $segment['departure']['terminal'] ?? '',
                                    'at' => $segment['departure']['at'] ?? '',
                                    'at_timestamp' => !empty($segment['departure']['at']) ? strtotime($segment['departure']['at']) : null
                                ),
                                'arrival' => array(
                                    'iata_code' => $segment['arrival']['iataCode'] ?? '',
                                    'terminal' => $segment['arrival']['terminal'] ?? '',
                                    'at' => $segment['arrival']['at'] ?? '',
                                    'at_timestamp' => !empty($segment['arrival']['at']) ? strtotime($segment['arrival']['at']) : null
                                ),
                                'carrier_code' => $segment['carrierCode'] ?? '',
                                'number' => $segment['number'] ?? '',
                                'aircraft' => $segment['aircraft']['code'] ?? '',
                                'duration' => $segment['duration'] ?? '',
                                'id' => $segment['id'] ?? '',
                                'operating' => $operating_carrier,
                                'operating_carrier_code' => $operating_carrier['carrierCode'] ?? '',
                                'is_operated_by_different' => $is_operated_by_different,
                                'co2_emissions' => $segment['co2Emissions'] ?? array(),
                                'flight_number' => ($segment['carrierCode'] ?? '') . ($segment['number'] ?? '')
                            );
                            
                            // Calculate layover to next segment
                            if ($index < ($segments_count - 1) && !empty($itinerary['segments'][$index + 1])) {
                                $next_segment = $itinerary['segments'][$index + 1];
                                $arrival_time = $segment_data['arrival']['at_timestamp'];
                                $next_departure_time = !empty($next_segment['departure']['at']) ? strtotime($next_segment['departure']['at']) : null;
                                
                                if ($arrival_time && $next_departure_time) {
                                    $layover_duration_seconds = $next_departure_time - $arrival_time;
                                    $layover_duration_minutes = floor($layover_duration_seconds / 60);
                                    $layover_duration_hours = floor($layover_duration_minutes / 60);
                                    $layover_remaining_minutes = $layover_duration_minutes % 60;
                                    
                                    // Check if terminal change
                                    $arrival_terminal = $segment_data['arrival']['terminal'] ?? '';
                                    $next_departure_terminal = $next_segment['departure']['terminal'] ?? '';
                                    $terminal_change = !empty($arrival_terminal) && !empty($next_departure_terminal) && 
                                                      ($arrival_terminal !== $next_departure_terminal);
                                    
                                    // Check if plane change (different aircraft)
                                    $current_aircraft = $segment_data['aircraft'] ?? '';
                                    $next_aircraft = $next_segment['aircraft']['code'] ?? '';
                                    $plane_change = !empty($current_aircraft) && !empty($next_aircraft) && 
                                                   ($current_aircraft !== $next_aircraft);
                                    
                                    $layover_data = array(
                                        'duration_seconds' => $layover_duration_seconds,
                                        'duration_minutes' => $layover_duration_minutes,
                                        'duration_hours' => $layover_duration_hours,
                                        'duration_remaining_minutes' => $layover_remaining_minutes,
                                        'duration_formatted' => $layover_duration_hours > 0 
                                            ? $layover_duration_hours . 'h ' . $layover_remaining_minutes . 'm'
                                            : $layover_remaining_minutes . 'm',
                                        'airport' => $segment_data['arrival']['iata_code'],
                                        'arrival_terminal' => $arrival_terminal,
                                        'departure_terminal' => $next_departure_terminal,
                                        'terminal_change' => $terminal_change,
                                        'plane_change' => $plane_change,
                                        'segment_index' => $index
                                    );
                                    
                                    $layovers[] = $layover_data;
                                    $segment_data['layover_to_next'] = $layover_data;
                                }
                            }
                            
                            $itinerary_data['segments'][] = $segment_data;
                            
                            // Collect filter data
                            if (!empty($segment['carrierCode'])) {
                                $airlines[$segment['carrierCode']] = $segment['carrierCode'];
                            }
                            
                            // Collect operating carrier if different
                            if ($is_operated_by_different && !empty($operating_carrier['carrierCode'])) {
                                $airlines[$operating_carrier['carrierCode']] = $operating_carrier['carrierCode'];
                            }
                            
                            // Collect time data
                            if (!empty($segment['departure']['at'])) {
                                $departure_times[] = date('H:i', strtotime($segment['departure']['at']));
                            }
                            if (!empty($segment['arrival']['at'])) {
                                $arrival_times[] = date('H:i', strtotime($segment['arrival']['at']));
                            }
                        }
                        
                        // Add layovers summary to itinerary
                        $itinerary_data['layovers'] = $layovers;
                        $itinerary_data['has_terminal_changes'] = !empty(array_filter($layovers, function($l) { return $l['terminal_change']; }));
                        $itinerary_data['has_plane_changes'] = !empty(array_filter($layovers, function($l) { return $l['plane_change']; }));
                    }
                    
                    $flight['itineraries'][] = $itinerary_data;
                    $stops[] = $itinerary_data['stops'];
                }
            }
            
            $formatted_results['flights'][] = $flight;
            // Use adjusted price for filter range
            $prices[] = floatval($flight['price']['total']);
        }
        
        // Build filter data
        $formatted_results['filters']['airlines'] = array_values($airlines);
        $formatted_results['filters']['stops'] = array_unique($stops);
        sort($formatted_results['filters']['stops']);
        
        if (!empty($prices)) {
            $formatted_results['filters']['price_range'] = array(
                'min' => min($prices),
                'max' => max($prices)
            );
        }
        
        // Group times by periods
        $formatted_results['filters']['departure_times'] = $this->group_times_by_period($departure_times);
        $formatted_results['filters']['arrival_times'] = $this->group_times_by_period($arrival_times);
        
        // Log final results
        $final_count = count($formatted_results['flights']);
        if (!empty($search_params['travel_class'])) {
            amadex_log('Amadex API: Cabin filtering complete - Requested: ' . $search_params['travel_class'] . ', Final flights: ' . $final_count . ' out of ' . (isset($total_offers) ? $total_offers : 'unknown') . ' total offers');
        } else {
            amadex_log('Amadex API: Formatting complete - Final flights: ' . $final_count);
        }
        
        return $formatted_results;
    }
    
    /**
     * Group times by period (morning, afternoon, evening, overnight)
     */
    private function group_times_by_period($times) {
        $periods = array(
            'morning' => array('05:00', '11:59'),
            'afternoon' => array('12:00', '17:59'),
            'evening' => array('18:00', '23:59'),
            'overnight' => array('00:00', '04:59')
        );
        
        $grouped = array();
        foreach ($periods as $period => $range) {
            $grouped[$period] = 0;
        }
        
        foreach ($times as $time) {
            $hour = intval(substr($time, 0, 2));
            $minute = intval(substr($time, 3, 2));
            $time_minutes = $hour * 60 + $minute;
            
            if ($time_minutes >= 300 && $time_minutes <= 719) { // 05:00 - 11:59
                $grouped['morning']++;
            } elseif ($time_minutes >= 720 && $time_minutes <= 1079) { // 12:00 - 17:59
                $grouped['afternoon']++;
            } elseif ($time_minutes >= 1080 && $time_minutes <= 1439) { // 18:00 - 23:59
                $grouped['evening']++;
            } else { // 00:00 - 04:59
                $grouped['overnight']++;
            }
        }
        
        return $grouped;
    }
    
    /**
     * Get flight details for popup
     */
    public function get_flight_details($flight_id) {
        $token = $this->get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        
        // This would typically be a separate API call to get detailed flight information
        // For now, we'll return the flight data from our search results
        return array(
            'success' => true,
            'flight_id' => $flight_id,
            'details' => array(
                'baggage' => array(
                    'included' => '1 x Hold Luggage',
                    'weight' => '23kg',
                    'additional' => 'Additional baggage available'
                ),
                'meals' => array(
                    'included' => 'Complimentary meal service',
                    'options' => array('Vegetarian', 'Non-vegetarian', 'Special dietary requirements')
                ),
                'seats' => array(
                    'included' => 'Standard seat included',
                    'options' => array('Window', 'Aisle', 'Extra legroom')
                ),
                'amenities' => array(
                    'wifi' => 'WiFi available (paid)',
                    'entertainment' => 'In-flight entertainment',
                    'power' => 'Power outlets available'
                ),
                'policies' => array(
                    'cancellation' => 'Free cancellation up to 24 hours before departure',
                    'changes' => 'Changes allowed with fee',
                    'refund' => 'Refundable within 24 hours'
                )
            )
        );
    }
    
    /**
     * Search airports with retry logic and local database fallback
     */
    public function search_airports($keyword, $retry_count = 0) {
        amadex_log('Amadex API: Searching airports for keyword: ' . $keyword . ' (Attempt: ' . ($retry_count + 1) . ')');
        
        $token = $this->get_access_token();
		if (is_wp_error($token)) {
            amadex_log('Amadex API: Token error, falling back to local database');
            return $this->search_local_airports($keyword);
		}
        
        $url = $this->api_base_url . '/v1/reference-data/locations';
        $url = add_query_arg(array(
            'subType' => 'AIRPORT,CITY',
            'keyword' => strtoupper($keyword),
            'page[limit]' => 10
        ), $url);
        
        amadex_log('Amadex API: Airport search URL: ' . $url);
        
		$response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ),
			'timeout' => $this->api_timeout,
		));
        
		if (is_wp_error($response)) {
            amadex_log('Amadex API: WP Error in airport search, falling back to local database');
            return $this->search_local_airports($keyword);
		}
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        amadex_log('Amadex API: Airport search response code: ' . $response_code);
        
        if ($response_code !== 200) {
            // Check for token expiration (401 error)
            $error_data = json_decode($response_body, true);
            
            if ($response_code === 401 && $retry_count < 1) {
                amadex_log('Amadex API: Token expired (401), clearing cache and retrying');
                // Clear token cache and retry once
                delete_transient($this->token_cache_key);
                delete_transient($this->token_expiry_key);
                return $this->search_airports($keyword, $retry_count + 1);
            }
            
            amadex_log('Amadex API: Airport search failed, falling back to local database. Error: ' . $response_body);
            return $this->search_local_airports($keyword);
        }
        
        $data = json_decode($response_body, true);
        $airports = $data['data'] ?? array();
        
        // If API returns no results, try local database
        if (empty($airports)) {
            amadex_log('Amadex API: No airports found in API, trying local database');
            return $this->search_local_airports($keyword);
        }
        
        amadex_log('Amadex API: Found ' . count($airports) . ' airports from API');
        return $airports;
    }
    
    /**
     * Search local airport database as fallback
     */
    private function search_local_airports($keyword) {
        global $wpdb;
        
        amadex_log('Amadex API: Searching local airport database for: ' . $keyword);
        
        $table_name = $wpdb->prefix . 'amadex_airports';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            amadex_log('Amadex API: Local airports table does not exist');
            return array();
        }
        
        $keyword = $wpdb->esc_like($keyword);
        $keyword = '%' . $keyword . '%';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT code as iataCode, name, city, country 
             FROM $table_name 
             WHERE code LIKE %s 
                OR name LIKE %s 
                OR city LIKE %s 
             ORDER BY 
                CASE 
                    WHEN code LIKE %s THEN 1
                    WHEN city LIKE %s THEN 2
                    ELSE 3
                END
             LIMIT 10",
            $keyword,
            $keyword,
            $keyword,
            $keyword,
            $keyword
        ), ARRAY_A);
        
        if (empty($results)) {
            amadex_log('Amadex API: No airports found in local database');
            return array();
        }
        
        // Format results to match Amadeus API format
        $formatted_results = array();
        foreach ($results as $row) {
            $formatted_results[] = array(
                'iataCode' => $row['iataCode'],
                'name' => $row['name'],
                'address' => array(
                    'cityName' => $row['city'],
                    'countryName' => $row['country']
                )
            );
        }
        
        amadex_log('Amadex API: Found ' . count($formatted_results) . ' airports from local database');
        return $formatted_results;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $token = $this->get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }
        
        return array(
            'success' => true,
            'message' => __('API connection successful', 'amadex'),
            'token_expires' => get_transient($this->token_expiry_key)
        );
    }
    
    /**
     * Legacy method for backward compatibility
     */
    public function search_offers($params) {
        return $this->search_flights($params);
    }
    
    /**
     * Legacy method for backward compatibility
     */
	private function normalize_offers($data) {
        $formatted = $this->format_flight_results($data);
		$result = array();
        
        foreach ($formatted['flights'] as $flight) {
			$itins = array();
            foreach ($flight['itineraries'] as $itin) {
					$segments = array();
					foreach ($itin['segments'] as $seg) {
						$segments[] = array(
                        'from' => $seg['departure']['iata_code'],
                        'to' => $seg['arrival']['iata_code'],
							'departure' => $seg['departure']['at'],
							'arrival' => $seg['arrival']['at'],
                        'carrier' => $seg['carrier_code'],
						);
					}
					$itins[] = $segments;
				}
            
			$result[] = array(
                'priceTotal' => $flight['price']['total'],
                'currency' => $flight['price']['currency'],
				'itineraries' => $itins,
                'raw' => $flight
			);
		}
        
		return $result;
	}
    
    /**
     * Get seat map for a flight offer
     *
     * @param string $flight_offer_id Flight offer ID from search results
     * @param array|null $raw_offer Full flight offer object (required by API)
     * @param int $retry_count Internal retry count for 401 token expiry
     * @return array|WP_Error Seat map data or error
     */
    public function get_seatmap($flight_offer_id, $raw_offer = null, $retry_count = 0) {
        $access_token = $this->get_access_token();
        
        if (is_wp_error($access_token)) {
            return $access_token;
        }
        
        $endpoint = $this->api_base_url . '/v1/shopping/seatmaps';
        
        // SeatMap Display API requires the FULL flight offer object with travelerPricings
        // According to Amadeus API docs, we must include the complete flight offer
        
        if ($raw_offer && is_array($raw_offer)) {
            // CRITICAL FIX: Ensure all segments have operating.carrierCode
            // Some segments only have operating.carrierName, but API requires carrierCode
            if (isset($raw_offer['itineraries']) && is_array($raw_offer['itineraries'])) {
                foreach ($raw_offer['itineraries'] as $itineraryIndex => $itinerary) {
                    if (isset($itinerary['segments']) && is_array($itinerary['segments'])) {
                        foreach ($itinerary['segments'] as $segmentIndex => $segment) {
                            // Check if operating.carrierCode is missing
                            if (isset($segment['operating'])) {
                                if (!isset($segment['operating']['carrierCode']) || empty($segment['operating']['carrierCode'])) {
                                    // Fill from segment's carrierCode (main carrier code)
                                    if (isset($segment['carrierCode']) && !empty($segment['carrierCode'])) {
                                        $raw_offer['itineraries'][$itineraryIndex]['segments'][$segmentIndex]['operating']['carrierCode'] = $segment['carrierCode'];
                                        amadex_log('Amadex SeatMap API: Fixed missing operating.carrierCode for itinerary ' . $itineraryIndex . ', segment ' . $segmentIndex . ' - used carrierCode: ' . $segment['carrierCode']);
                                    } elseif (isset($raw_offer['validatingAirlineCodes']) && !empty($raw_offer['validatingAirlineCodes'])) {
                                        // Fallback to validating airline code
                                        $validatingCode = is_array($raw_offer['validatingAirlineCodes']) ? $raw_offer['validatingAirlineCodes'][0] : $raw_offer['validatingAirlineCodes'];
                                        $raw_offer['itineraries'][$itineraryIndex]['segments'][$segmentIndex]['operating']['carrierCode'] = $validatingCode;
                                        amadex_log('Amadex SeatMap API: Fixed missing operating.carrierCode for itinerary ' . $itineraryIndex . ', segment ' . $segmentIndex . ' - used validatingAirlineCode: ' . $validatingCode);
                                    }
                                }
                            } else {
                                // operating object is missing entirely - create it
                                if (isset($segment['carrierCode']) && !empty($segment['carrierCode'])) {
                                    $raw_offer['itineraries'][$itineraryIndex]['segments'][$segmentIndex]['operating'] = array(
                                        'carrierCode' => $segment['carrierCode']
                                    );
                                    amadex_log('Amadex SeatMap API: Created missing operating object for itinerary ' . $itineraryIndex . ', segment ' . $segmentIndex . ' - used carrierCode: ' . $segment['carrierCode']);
                                }
                            }
                        }
                    }
                }
            }
            
            // Use the full flight offer object directly (required by API)
            // The API expects the complete flight offer structure
            $request_body = array(
                'data' => array(
                    $raw_offer  // Send the complete flight offer object (now with fixed operating.carrierCode)
                )
            );
            amadex_log('Amadex SeatMap API: Using full flight offer object');
            amadex_log('Amadex SeatMap API: Raw offer has keys: ' . implode(', ', array_keys($raw_offer)));
            if (isset($raw_offer['travelerPricings'])) {
                amadex_log('Amadex SeatMap API: Raw offer includes travelerPricings (count: ' . count($raw_offer['travelerPricings']) . ')');
            } else {
                amadex_log('Amadex SeatMap API: WARNING - Raw offer missing travelerPricings!');
            }
        } else {
            // Cannot proceed without raw offer - API requires it
            amadex_log('Amadex SeatMap API: ERROR - Raw offer not provided! API requires full flight offer object.');
            return new WP_Error('seatmap_error', __('Flight offer data is required for seat map. Please try selecting the flight again.', 'amadex'));
        }
        
        amadex_log('Amadex SeatMap API: Requesting seat map for flight offer ID: ' . $flight_offer_id);
        amadex_log('Amadex SeatMap API: Request body size: ' . strlen(wp_json_encode($request_body)) . ' bytes');
        amadex_log('Amadex SeatMap API: Request body structure: ' . print_r(array('data_count' => count($request_body['data']), 'has_travelerPricings' => isset($request_body['data'][0]['travelerPricings'])), true));
        
        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.amadeus+json'
            ),
            'body' => wp_json_encode($request_body),
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            amadex_log('Amadex SeatMap API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        amadex_log('Amadex SeatMap API: Response status code: ' . $status_code);
        amadex_log('Amadex SeatMap API: Response body (first 1000 chars): ' . substr($body, 0, 1000));
        
        if ($status_code !== 200) {
            $error_message = __('Failed to retrieve seat map', 'amadex');
            $error_code = isset($data['errors'][0]['code']) ? $data['errors'][0]['code'] : '';

            if (isset($data['errors']) && !empty($data['errors'])) {
                $error_detail = $data['errors'][0];
                $error_message = isset($error_detail['detail'])
                    ? $error_detail['detail']
                    : (isset($error_detail['title']) ? $error_detail['title'] : $error_message);
                $error_code = isset($error_detail['code']) ? $error_detail['code'] : '';

                amadex_log('Amadex SeatMap API: Error detail: ' . print_r($error_detail, true));

                // Token expired (401 / 38192): clear cache and retry once
                if (($status_code === 401 || $error_code === 38192) && $retry_count < 1) {
                    amadex_log('Amadex SeatMap API: Token expired (401), clearing cache and retrying');
                    delete_transient($this->token_cache_key);
                    delete_transient($this->token_expiry_key);
                    return $this->get_seatmap($flight_offer_id, $raw_offer, $retry_count + 1);
                }

                // Provide user-friendly messages for common errors
                if (strpos(strtolower($error_message), 'not found') !== false ||
                    strpos(strtolower($error_message), 'invalid') !== false ||
                    $error_code === '492') { // 492 = Invalid flight offer ID
                    $error_message = __('Seat selection is not available for this flight offer. This may be because the flight does not support seat selection, or the flight offer ID is invalid.', 'amadex');
                } elseif (strpos(strtolower($error_message), 'not supported') !== false) {
                    $error_message = __('This flight does not support seat selection through the Amadeus API.', 'amadex');
                }
            }

            amadex_log('Amadex SeatMap API Error: Status ' . $status_code . ' - ' . $error_message);
            amadex_log('Amadex SeatMap API Error Code: ' . $error_code);
            amadex_log('Amadex SeatMap API Full Error Response: ' . $body);

            return new WP_Error('seatmap_error', $error_message, array(
                'status' => $status_code,
                'code' => $error_code,
                'raw_response' => $body
            ));
        }
        
        // Success - log success details
        amadex_log('Amadex SeatMap API: Success! Status 200');
        if (isset($data['data']) && is_array($data['data'])) {
            amadex_log('Amadex SeatMap API: Number of seat maps returned: ' . count($data['data']));
        } else {
            amadex_log('Amadex SeatMap API: Warning - Response data structure unexpected');
            amadex_log('Amadex SeatMap API: Response data keys: ' . (is_array($data) ? implode(', ', array_keys($data)) : 'not an array'));
        }
        
        amadex_log('Amadex SeatMap API: Successfully retrieved seat map data');
        return $data;
    }
    
    /**
     * Price flight offer with selected seats
     *
     * @param array $flight_offer Original flight offer from search
     * @param array $selected_seats Array of selected seats per segment: ['segment_id' => ['traveler_id' => 'seat_number']]
     * @return array|WP_Error Updated flight offer with pricing or error
     */
    public function price_flight_offer_with_seats($flight_offer, $selected_seats) {
        $access_token = $this->get_access_token();
        
        if (is_wp_error($access_token)) {
            return $access_token;
        }
        
        // Clone the flight offer to avoid modifying the original
        $updated_offer = json_decode(wp_json_encode($flight_offer), true);
        
        // Add selected seats to fareDetailsBySegment
        if (isset($updated_offer['travelerPricings']) && is_array($updated_offer['travelerPricings'])) {
            foreach ($updated_offer['travelerPricings'] as $traveler_index => &$traveler_pricing) {
                $traveler_id = $traveler_pricing['travelerId'] ?? ($traveler_index + 1);
                
                if (isset($traveler_pricing['fareDetailsBySegment']) && is_array($traveler_pricing['fareDetailsBySegment'])) {
                    foreach ($traveler_pricing['fareDetailsBySegment'] as $segment_index => &$fare_details) {
                        $segment_id = $fare_details['segmentId'] ?? ($segment_index + 1);
                        
                        // Check if this traveler has a seat selected for this segment
                        if (isset($selected_seats[$segment_id][$traveler_id])) {
                            $seat_number = $selected_seats[$segment_id][$traveler_id];
                            
                            // Initialize services array if not exists (Amadeus API expects 'services' not 'additionalServices')
                            if (!isset($fare_details['services'])) {
                                $fare_details['services'] = array();
                            }
                            
                            // Add seat service - Amadeus API format requires service type and seat number
                            $seat_service = array(
                                'type' => 'SEATS',
                                'quantity' => 1
                            );
                            
                            // Add seat number if available
                            if (!empty($seat_number)) {
                                $seat_service['seatNumber'] = $seat_number;
                            }
                            
                            // Add to services array
                            $fare_details['services'][] = $seat_service;
                            
                            amadex_log('Amadex Price API: Added seat ' . $seat_number . ' for traveler ' . $traveler_id . ' on segment ' . $segment_id);
                        }
                    }
                }
            }
        }
        
        $endpoint = $this->api_base_url . '/v1/shopping/flight-offers/pricing';
        
        $request_body = array(
            'data' => array(
                'type' => 'flight-offer-pricing',
                'flightOffers' => array($updated_offer)
            )
        );
        
        amadex_log('Amadex Price API: Pricing flight offer with seats');
        
        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.amadeus+json'
            ),
            'body' => wp_json_encode($request_body),
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            amadex_log('Amadex Price API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = __('Failed to price flight offer with seats', 'amadex');
            if (isset($data['errors']) && !empty($data['errors'])) {
                $error_message = isset($data['errors'][0]['detail'])
                    ? $data['errors'][0]['detail']
                    : (isset($data['errors'][0]['title']) ? $data['errors'][0]['title'] : $error_message);
            }
            amadex_log('Amadex Price API Error: Status ' . $status_code . ' - ' . $error_message);
            amadex_log('Amadex Price API Error Response: ' . $body);
            return new WP_Error('pricing_error', $error_message, array('status' => $status_code));
        }
        
        // Extract the updated flight offer from response
        if (isset($data['data']['flightOffers']) && !empty($data['data']['flightOffers'])) {
            $priced_offer = $data['data']['flightOffers'][0];
            amadex_log('Amadex Price API: Successfully priced flight offer. New total: ' . ($priced_offer['price']['total'] ?? 'N/A'));
            return array(
                'flightOffer' => $priced_offer,
                'pricing' => $priced_offer['price'] ?? array()
            );
        }
        
        amadex_log('Amadex Price API Error: Invalid response structure');
        return new WP_Error('pricing_error', __('Invalid response from pricing API', 'amadex'));
	}
    
    /**
     * Parse fare rules to extract cancellation, exchange, penalties, and no-show policies
     * NOTE: Excluding refund status as per requirements
     * 
     * @param array $fare_rules Fare rules array from Flight Offers Price API
     * @return array Parsed fare rules with cancellation, exchange, penalties, no-show
     */
    private function parse_fare_rules($fare_rules) {
        $parsed = array(
            'cancellation' => array(
                'allowed' => false,
                'fee' => null,
                'max_penalty_amount' => null,
                'currency' => null,
                'deadline' => null,
                'descriptions' => array(),
                'not_applicable' => true
            ),
            'exchange' => array(
                'allowed' => false,
                'fee' => null,
                'max_penalty_amount' => null,
                'currency' => null,
                'deadline' => null,
                'descriptions' => array(),
                'not_applicable' => true
            ),
            'change' => array(
                'allowed' => false,
                'fee' => null,
                'max_penalty_amount' => null,
                'currency' => null,
                'deadline' => null,
                'descriptions' => array(),
                'not_applicable' => true
            ),
            'no_show' => array(
                'policy' => null,
                'penalty' => null,
                'descriptions' => array()
            ),
            'penalties' => array(),
            'all_descriptions' => array()
        );
        
        if (empty($fare_rules) || !is_array($fare_rules)) {
            return $parsed;
        }
        
        // Process each fare rule
        foreach ($fare_rules as $fare_rule) {
            if (empty($fare_rule['termsAndConditions']) || !is_array($fare_rule['termsAndConditions'])) {
                continue;
            }
            
            foreach ($fare_rule['termsAndConditions'] as $condition) {
                $category = strtoupper($condition['category'] ?? '');
                $not_applicable = $condition['notApplicable'] ?? false;
                
                // Parse CANCELLATION rules
                if ($category === 'CANCELLATION') {
                    $parsed['cancellation']['not_applicable'] = $not_applicable;
                    $parsed['cancellation']['allowed'] = !$not_applicable;
                    
                    if (!empty($condition['maxPenaltyAmount'])) {
                        $penalty_amount = is_array($condition['maxPenaltyAmount']) 
                            ? ($condition['maxPenaltyAmount']['amount'] ?? null)
                            : $condition['maxPenaltyAmount'];
                        $penalty_currency = is_array($condition['maxPenaltyAmount'])
                            ? ($condition['maxPenaltyAmount']['currency'] ?? null)
                            : ($condition['currency'] ?? 'USD');
                        
                        $parsed['cancellation']['max_penalty_amount'] = $penalty_amount;
                        $parsed['cancellation']['currency'] = $penalty_currency;
                        $parsed['cancellation']['fee'] = $penalty_amount;
                    }
                    
                    if (!empty($condition['descriptions'])) {
                        $descriptions = is_array($condition['descriptions']) ? $condition['descriptions'] : array($condition['descriptions']);
                        $parsed['cancellation']['descriptions'] = array_merge($parsed['cancellation']['descriptions'], $descriptions);
                        $parsed['all_descriptions'] = array_merge($parsed['all_descriptions'], $descriptions);
                    }
                    
                    // Extract deadline if available
                    if (!empty($condition['deadline'])) {
                        $parsed['cancellation']['deadline'] = $condition['deadline'];
                    }
                }
                
                // Parse EXCHANGE rules (rebooking/changing flights)
                if ($category === 'EXCHANGE' || $category === 'CHANGE') {
                    $key = strtolower($category);
                    $parsed[$key]['not_applicable'] = $not_applicable;
                    $parsed[$key]['allowed'] = !$not_applicable;
                    
                    if (!empty($condition['maxPenaltyAmount'])) {
                        $penalty_amount = is_array($condition['maxPenaltyAmount']) 
                            ? ($condition['maxPenaltyAmount']['amount'] ?? null)
                            : $condition['maxPenaltyAmount'];
                        $penalty_currency = is_array($condition['maxPenaltyAmount'])
                            ? ($condition['maxPenaltyAmount']['currency'] ?? null)
                            : ($condition['currency'] ?? 'USD');
                        
                        $parsed[$key]['max_penalty_amount'] = $penalty_amount;
                        $parsed[$key]['currency'] = $penalty_currency;
                        $parsed[$key]['fee'] = $penalty_amount;
                    }
                    
                    if (!empty($condition['descriptions'])) {
                        $descriptions = is_array($condition['descriptions']) ? $condition['descriptions'] : array($condition['descriptions']);
                        $parsed[$key]['descriptions'] = array_merge($parsed[$key]['descriptions'], $descriptions);
                        $parsed['all_descriptions'] = array_merge($parsed['all_descriptions'], $descriptions);
                    }
                    
                    if (!empty($condition['deadline'])) {
                        $parsed[$key]['deadline'] = $condition['deadline'];
                    }
                }
                
                // Parse NO-SHOW rules
                if ($category === 'NO_SHOW' || $category === 'NOSHOW') {
                    $parsed['no_show']['policy'] = $not_applicable ? 'not_applicable' : 'applicable';
                    
                    if (!empty($condition['maxPenaltyAmount'])) {
                        $penalty_amount = is_array($condition['maxPenaltyAmount']) 
                            ? ($condition['maxPenaltyAmount']['amount'] ?? null)
                            : $condition['maxPenaltyAmount'];
                        $penalty_currency = is_array($condition['maxPenaltyAmount'])
                            ? ($condition['maxPenaltyAmount']['currency'] ?? null)
                            : ($condition['currency'] ?? 'USD');
                        
                        $parsed['no_show']['penalty'] = array(
                            'amount' => $penalty_amount,
                            'currency' => $penalty_currency
                        );
                    }
                    
                    if (!empty($condition['descriptions'])) {
                        $descriptions = is_array($condition['descriptions']) ? $condition['descriptions'] : array($condition['descriptions']);
                        $parsed['no_show']['descriptions'] = array_merge($parsed['no_show']['descriptions'], $descriptions);
                        $parsed['all_descriptions'] = array_merge($parsed['all_descriptions'], $descriptions);
                    }
                }
                
                // Collect all penalty information
                if (!empty($condition['maxPenaltyAmount'])) {
                    $penalty_amount = is_array($condition['maxPenaltyAmount']) 
                        ? ($condition['maxPenaltyAmount']['amount'] ?? null)
                        : $condition['maxPenaltyAmount'];
                    $penalty_currency = is_array($condition['maxPenaltyAmount'])
                        ? ($condition['maxPenaltyAmount']['currency'] ?? null)
                        : ($condition['currency'] ?? 'USD');
                    
                    if ($penalty_amount !== null) {
                        $parsed['penalties'][] = array(
                            'category' => $category,
                            'amount' => $penalty_amount,
                            'currency' => $penalty_currency,
                            'not_applicable' => $not_applicable
                        );
                    }
                }
            }
        }
        
        return $parsed;
	}
    
    /**
     * Get detailed flight offer pricing with baggage allowances and fare rules
     * 
     * @param array $flight_offer Raw flight offer from search
     * @return array|WP_Error Detailed pricing data with baggage info
     */
    public function get_flight_offers_pricing($flight_offer) {
        $access_token = $this->get_access_token();
        
        if (is_wp_error($access_token)) {
            return $access_token;
        }
        
        $endpoint = $this->api_base_url . '/v1/shopping/flight-offers/pricing';
        
        // Build request with include parameter for detailed data
        $request_body = array(
            'data' => array(
                'type' => 'flight-offer-pricing',
                'flightOffers' => array($flight_offer)
            )
        );
        
        // Add query parameter to include detailed fare rules (if supported)
        $url = add_query_arg(array(
            'include' => 'detailed-fare-rules'
        ), $endpoint);
        
        amadex_log('Amadex Pricing API: Getting detailed pricing for flight offer');
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.amadeus+json'
            ),
            'body' => wp_json_encode($request_body),
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            amadex_log('Amadex Pricing API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = __('Failed to get detailed pricing', 'amadex');
            if (isset($data['errors']) && !empty($data['errors'])) {
                $error_message = isset($data['errors'][0]['detail'])
                    ? $data['errors'][0]['detail']
                    : (isset($data['errors'][0]['title']) ? $data['errors'][0]['title'] : $error_message);
            }
            amadex_log('Amadex Pricing API Error: Status ' . $status_code . ' - ' . $error_message);
            return new WP_Error('pricing_error', $error_message, array('status' => $status_code));
        }
        
        // Extract detailed pricing information
        if (isset($data['data']['flightOffers']) && !empty($data['data']['flightOffers'])) {
            $priced_offer = $data['data']['flightOffers'][0];
            
            // Extract detailed baggage information
            $detailed_baggage = array();
            $fare_rules = array();
            
            if (!empty($priced_offer['travelerPricings'])) {
                foreach ($priced_offer['travelerPricings'] as $traveler_pricing) {
                    if (!empty($traveler_pricing['fareDetailsBySegment'])) {
                        foreach ($traveler_pricing['fareDetailsBySegment'] as $segment_index => $fare_detail) {
                            // Extract checked bags
                            if (!empty($fare_detail['includedCheckedBags'])) {
                                $bag_data = $fare_detail['includedCheckedBags'];
                                $detailed_baggage[] = array(
                                    'type' => 'checked',
                                    'quantity' => is_array($bag_data) ? ($bag_data['quantity'] ?? 1) : intval($bag_data),
                                    'weight' => isset($bag_data['weight']) ? intval($bag_data['weight']) : null,
                                    'weight_unit' => isset($bag_data['weightUnit']) ? $bag_data['weightUnit'] : 'KG',
                                    'segment' => $segment_index
                                );
                            }
                            
                            // Extract carry-on bags
                            if (!empty($fare_detail['includedCarryOnBags'])) {
                                $carry_on_data = $fare_detail['includedCarryOnBags'];
                                $detailed_baggage[] = array(
                                    'type' => 'carry_on',
                                    'quantity' => is_array($carry_on_data) ? ($carry_on_data['quantity'] ?? 1) : intval($carry_on_data),
                                    'segment' => $segment_index
                                );
                            }
                        }
                    }
                }
            }
            
            // Extract and parse fare rules if available
            $parsed_fare_rules = array();
            if (!empty($data['data']['fareRules'])) {
                $fare_rules = $data['data']['fareRules'];
                $parsed_fare_rules = $this->parse_fare_rules($fare_rules);
                amadex_log('Amadex Pricing API: Parsed fare rules - Cancellation: ' . ($parsed_fare_rules['cancellation']['allowed'] ? 'allowed' : 'not allowed') . ', Exchange: ' . ($parsed_fare_rules['exchange']['allowed'] ? 'allowed' : 'not allowed'));
            }
            
            amadex_log('Amadex Pricing API: Successfully retrieved detailed pricing');
            return array(
                'flightOffer' => $priced_offer,
                'pricing' => $priced_offer['price'] ?? array(),
                'detailed_baggage' => $detailed_baggage,
                'fare_rules' => $fare_rules ?? array(),
                'parsed_fare_rules' => $parsed_fare_rules
            );
        }
        
        amadex_log('Amadex Pricing API Error: Invalid response structure');
        return new WP_Error('pricing_error', __('Invalid response from pricing API', 'amadex'));
    }
    
    /**
     * Get branded fare variants (upsell options) for a flight offer
     * Returns different fare family options (Basic, Standard, Flex, etc.)
     * 
     * @param array $flight_offer Raw flight offer from search
     * @return array|WP_Error Branded fare variants with pricing
     */
    public function get_branded_fares_upsell($flight_offer) {
        $access_token = $this->get_access_token();
        
        if (is_wp_error($access_token)) {
            return $access_token;
        }
        
        $endpoint = $this->api_base_url . '/v1/shopping/flight-offers/upselling';
        
        $request_body = array(
            'data' => array(
                'type' => 'flight-offers-upselling',
                'flightOffers' => array($flight_offer)
            )
        );
        
        amadex_log('Amadex Branded Fares API: Getting fare variants for flight offer');
        
        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.amadeus+json'
            ),
            'body' => wp_json_encode($request_body),
            'timeout' => 30,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            amadex_log('Amadex Branded Fares API Error: ' . $response->get_error_message());
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            $error_message = __('Failed to get branded fare variants', 'amadex');
            if (isset($data['errors']) && !empty($data['errors'])) {
                $error_message = isset($data['errors'][0]['detail'])
                    ? $data['errors'][0]['detail']
                    : (isset($data['errors'][0]['title']) ? $data['errors'][0]['title'] : $error_message);
            }
            amadex_log('Amadex Branded Fares API Error: Status ' . $status_code . ' - ' . $error_message);
            return new WP_Error('branded_fares_error', $error_message, array('status' => $status_code));
        }
        
        // Extract branded fare variants
        $branded_variants = array();
        
        if (isset($data['data']['flightOffers']) && !empty($data['data']['flightOffers'])) {
            foreach ($data['data']['flightOffers'] as $variant) {
                $variant_data = array(
                    'id' => $variant['id'] ?? '',
                    'price' => $variant['price'] ?? array(),
                    'brandedFare' => null,
                    'fareBasis' => array(),
                    'cabin' => array(),
                    'includedCheckedBags' => array()
                );
                
                // Extract branded fare name from fareDetailsBySegment
                if (!empty($variant['travelerPricings'])) {
                    foreach ($variant['travelerPricings'] as $traveler_pricing) {
                        if (!empty($traveler_pricing['fareDetailsBySegment'])) {
                            foreach ($traveler_pricing['fareDetailsBySegment'] as $segment_index => $fare_detail) {
                                if (!empty($fare_detail['brandedFare'])) {
                                    $variant_data['brandedFare'] = $fare_detail['brandedFare'];
                                }
                                if (!empty($fare_detail['fareBasis'])) {
                                    $variant_data['fareBasis'][$segment_index] = $fare_detail['fareBasis'];
                                }
                                if (!empty($fare_detail['cabin'])) {
                                    $variant_data['cabin'][$segment_index] = $fare_detail['cabin'];
                                }
                                if (!empty($fare_detail['includedCheckedBags'])) {
                                    $variant_data['includedCheckedBags'][$segment_index] = $fare_detail['includedCheckedBags'];
                                }
                            }
                        }
                    }
                }
                
                $branded_variants[] = $variant_data;
            }
        }
        
        amadex_log('Amadex Branded Fares API: Successfully retrieved ' . count($branded_variants) . ' fare variants');
        
        return array(
            'variants' => $branded_variants,
            'original_offer' => $flight_offer
        );
    }
    
    /**
     * Get aircraft equipment information to translate codes to model names
     * Uses caching to reduce API calls
     * 
     * @param string $aircraft_code Aircraft code (e.g., '320', '737', '787')
     * @return array|WP_Error Aircraft information with name
     */
    public function get_aircraft_equipment($aircraft_code) {
        if (empty($aircraft_code)) {
            return new WP_Error('invalid_code', __('Aircraft code is required', 'amadex'));
        }
        
        // Check cache first (cache for 30 days - aircraft data rarely changes)
        $cache_key = 'amadex_aircraft_' . strtoupper($aircraft_code);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            amadex_log('Amadex Aircraft API: Using cached data for ' . $aircraft_code);
            return $cached;
        }
        
        $access_token = $this->get_access_token();
        
        if (is_wp_error($access_token)) {
            // Return basic info if API fails
            return array(
                'code' => strtoupper($aircraft_code),
                'name' => 'Aircraft ' . strtoupper($aircraft_code)
            );
        }
        
        $endpoint = $this->api_base_url . '/v1/reference-data/aircraft';
        
        $url = add_query_arg(array(
            'aircraftCodes' => strtoupper($aircraft_code)
        ), $endpoint);
        
        amadex_log('Amadex Aircraft API: Looking up aircraft code: ' . $aircraft_code);
        
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/vnd.amadeus+json'
            ),
            'timeout' => $this->api_timeout,
            'sslverify' => true
        ));
        
        if (is_wp_error($response)) {
            amadex_log('Amadex Aircraft API Error: ' . $response->get_error_message());
            // Return basic info on error
            $result = array(
                'code' => strtoupper($aircraft_code),
                'name' => 'Aircraft ' . strtoupper($aircraft_code)
            );
            // Cache the fallback for shorter time (1 hour)
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
            return $result;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($status_code !== 200) {
            amadex_log('Amadex Aircraft API Error: Status ' . $status_code);
            // Return basic info on error
            $result = array(
                'code' => strtoupper($aircraft_code),
                'name' => 'Aircraft ' . strtoupper($aircraft_code)
            );
            set_transient($cache_key, $result, HOUR_IN_SECONDS);
            return $result;
        }
        
        // Extract aircraft information
        if (!empty($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $aircraft) {
                if (strtoupper($aircraft['code'] ?? '') === strtoupper($aircraft_code)) {
                    $result = array(
                        'code' => strtoupper($aircraft['code'] ?? $aircraft_code),
                        'name' => $aircraft['name'] ?? ('Aircraft ' . strtoupper($aircraft_code)),
                        'description' => $aircraft['description'] ?? ''
                    );
                    
                    // Cache for 30 days
                    set_transient($cache_key, $result, 30 * DAY_IN_SECONDS);
                    
                    amadex_log('Amadex Aircraft API: Found aircraft ' . $aircraft_code . ' - ' . $result['name']);
                    return $result;
                }
            }
        }
        
        // If not found, return basic info
        $result = array(
            'code' => strtoupper($aircraft_code),
            'name' => 'Aircraft ' . strtoupper($aircraft_code)
        );
        
        // Cache for shorter time if not found (1 day)
        set_transient($cache_key, $result, DAY_IN_SECONDS);
        return $result;
	}
}