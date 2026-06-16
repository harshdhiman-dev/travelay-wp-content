<?php

// if (defined('DOING_AJAX') && DOING_AJAX) {
//     while (ob_get_level()) {
//         ob_end_clean();
//     }
//     ob_start();
// }

if (defined('DOING_AJAX') && DOING_AJAX && !defined('REST_REQUEST')) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
}
/**
 * AJAX functionality for Amadex plugin
 *
 * @package Amadex
 * @since 1.0.0
 * 
 * @phpstan-ignore-next-line
 * @psalm-suppress UndefinedFunction
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress Functions Stub
 * 
 * The following WordPress functions are available at runtime:
 * @uses add_action()
 * @uses add_filter()
 * @uses wp_verify_nonce()
 * @uses check_ajax_referer()
 * @uses wp_send_json_success()
 * @uses wp_send_json_error()
 * @uses sanitize_text_field()
 * @uses sanitize_email()
 * @uses sanitize_textarea_field()
 * @uses current_user_can()
 * @uses is_wp_error()
 * @uses wp_remote_get()
 * @uses wp_remote_post()
 * @uses wp_remote_retrieve_body()
 * @uses get_option()
 * @uses is_email()
 * @uses get_bloginfo()
 * @uses get_site_url()
 * @uses wp_mail()
 * @uses wp_strip_all_tags()
 * @uses dbDelta()
 * @uses __()
 * @uses esc_html()
 * @uses esc_html__()
 * @uses esc_attr()
 * @uses esc_url()
 * @uses esc_textarea()
 * @uses plugin_dir_path()
 * @uses plugin_dir_url()
 * @uses plugin_basename()
 * @uses register_activation_hook()
 * @uses get_permalink()
 */

/**
 * AJAX Handler Class
 */
class Amadex_Ajax
{

    /** Plain-text body for next wp_mail (multipart/alternative) */
    private static $amadex_next_plain_body = '';

    /**
     * Safe output buffer clean: clear all levels to avoid warnings when no buffer active.
     * Prevents "ob_end_flush(): failed to delete buffer" / "ob_clean(): failed to delete buffer"
     * which can break JSON responses and cause 500-like behavior.
     */
    private function amadex_safe_ob_clean()
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
    }

    /**
     * Safe output buffer flush: only flush if a buffer is active.
     */
    private function amadex_safe_ob_end_flush()
    {
        if (ob_get_level() > 0) {
            @ob_end_flush();
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_ajax_amadex_search_flights', array($this, 'search_flights'));
        add_action('wp_ajax_nopriv_amadex_search_flights', array($this, 'search_flights'));

        add_action('wp_ajax_amadex_search_airports', array($this, 'search_airports'));
        add_action('wp_ajax_nopriv_amadex_search_airports', array($this, 'search_airports'));

        // Add alias for airport search (JS uses 'amadex_airports')
        add_action('wp_ajax_amadex_airports', array($this, 'search_airports'));
        add_action('wp_ajax_nopriv_amadex_airports', array($this, 'search_airports'));

        add_action('wp_ajax_amadex_get_flight_details', array($this, 'get_flight_details'));
        add_action('wp_ajax_nopriv_amadex_get_flight_details', array($this, 'get_flight_details'));

        add_action('wp_ajax_amadex_filter_flights', array($this, 'filter_flights'));
        add_action('wp_ajax_nopriv_amadex_filter_flights', array($this, 'filter_flights'));

        add_action('wp_ajax_amadex_test_api', array($this, 'test_api'));
        add_action('wp_ajax_amadex_clear_token_cache', array($this, 'clear_token_cache'));

        add_action('wp_ajax_amadex_process_booking', array($this, 'process_booking'));
        add_action('wp_ajax_nopriv_amadex_process_booking', array($this, 'process_booking'));

        // NMI Three Step Redirect — Step 3 return handler (called via template_redirect, not AJAX)
        add_action('template_redirect', array($this, 'handle_3ds_return'));

        add_action('wp_ajax_amadex_paypal_create_order', array($this, 'paypal_create_order'));
        add_action('wp_ajax_nopriv_amadex_paypal_create_order', array($this, 'paypal_create_order'));
        add_action('wp_ajax_amadex_paypal_capture', array($this, 'paypal_capture'));
        add_action('wp_ajax_nopriv_amadex_paypal_capture', array($this, 'paypal_capture'));

        add_action('wp_ajax_amadex_cryptocom_create_payment', array($this, 'cryptocom_create_payment'));
        add_action('wp_ajax_nopriv_amadex_cryptocom_create_payment', array($this, 'cryptocom_create_payment'));
        add_action('wp_ajax_amadex_moonpay_create_paylink', array($this, 'moonpay_create_paylink'));
        add_action('wp_ajax_nopriv_amadex_moonpay_create_paylink', array($this, 'moonpay_create_paylink'));
        add_action('wp_ajax_amadex_moonpay_onramp_prepare', array($this, 'moonpay_onramp_prepare'));
        add_action('wp_ajax_nopriv_amadex_moonpay_onramp_prepare', array($this, 'moonpay_onramp_prepare'));
        add_action('wp_ajax_amadex_moonpay_onramp_sign', array($this, 'moonpay_onramp_sign'));
        add_action('wp_ajax_nopriv_amadex_moonpay_onramp_sign', array($this, 'moonpay_onramp_sign'));
        add_action('amadex_booking_confirmed_cryptocom', array($this, 'on_cryptocom_booking_confirmed'), 10, 3);
        add_action('amadex_booking_confirmed_moonpay_onramp', array($this, 'on_moonpay_onramp_booking_confirmed'), 10, 3);
        add_action('amadex_booking_confirmed_moonpay_commerce', array($this, 'on_moonpay_commerce_booking_confirmed'), 10, 3);
        add_action('amadex_booking_confirmed_crypto_transfer', array($this, 'on_crypto_transfer_booking_confirmed'), 10, 3);

        // Performance optimization AJAX handlers
        add_action('wp_ajax_amadex_get_skeleton', array($this, 'get_skeleton_template'));
        add_action('wp_ajax_nopriv_amadex_get_skeleton', array($this, 'get_skeleton_template'));

        add_action('wp_ajax_amadex_get_loading_animation', array($this, 'get_loading_animation_template'));
        add_action('wp_ajax_nopriv_amadex_get_loading_animation', array($this, 'get_loading_animation_template'));

        add_action('wp_ajax_amadex_test_nmi', array($this, 'test_nmi'));
        add_action('wp_ajax_amadex_create_lead', array($this, 'create_lead'));
        add_action('wp_ajax_amadex_record_3ds_failure', array($this, 'record_3ds_failure'));
        add_action('wp_ajax_nopriv_amadex_record_3ds_failure', array($this, 'record_3ds_failure'));
        add_action('wp_ajax_amadex_update_fp_status', array($this, 'update_fp_status'));

        add_action('wp_ajax_amadex_get_country_states', array($this, 'get_country_states'));
        add_action('wp_ajax_nopriv_amadex_get_country_states', array($this, 'get_country_states'));

        add_action('wp_ajax_amadex_test_email', array($this, 'test_email'));
        add_action('wp_ajax_amadex_preview_email', array($this, 'preview_email'));
        add_action('wp_ajax_amadex_email_template_live_preview', array($this, 'ajax_email_template_live_preview'));
        add_action('wp_ajax_amadex_save_email_builder', array($this, 'ajax_save_email_builder'));
        add_action('wp_ajax_amadex_save_email_builder_block', array($this, 'ajax_save_email_builder_block'));
        add_action('wp_ajax_amadex_get_email_builder_blocks', array($this, 'ajax_get_email_builder_blocks'));
        add_action('wp_ajax_amadex_delete_email_builder_block', array($this, 'ajax_delete_email_builder_block'));
        add_action('wp_ajax_amadex_get_email_builder_history', array($this, 'ajax_get_email_builder_history'));
        add_action('wp_ajax_amadex_restore_email_builder_version', array($this, 'ajax_restore_email_builder_version'));
        add_action('phpmailer_init', array($this, 'amadex_phpmailer_alt_body'), 10, 1);
        add_action('init', array($this, 'register_email_preview_page'));

        add_action('wp_ajax_amadex_confirm_booking', array($this, 'confirm_booking'));
        add_action('wp_ajax_amadex_verify_nmi_authorization', array($this, 'verify_nmi_authorization'));

        add_action('wp_ajax_amadex_diagnose_booking', array($this, 'diagnose_booking'));
        add_action('wp_ajax_nopriv_amadex_diagnose_booking', array($this, 'diagnose_booking'));

        // Pricing Rules Engine AJAX handlers
        add_action('wp_ajax_amadex_get_pricing_rule', array($this, 'ajax_get_pricing_rule'));
        add_action('wp_ajax_amadex_save_pricing_rule', array($this, 'ajax_save_pricing_rule'));
        add_action('wp_ajax_amadex_delete_pricing_rule', array($this, 'ajax_delete_pricing_rule'));
        add_action('wp_ajax_amadex_simulate_pricing', array($this, 'ajax_simulate_pricing'));

        add_action('wp_ajax_amadex_recalculate_price', array($this, 'recalculate_price'));
        add_action('wp_ajax_nopriv_amadex_recalculate_price', array($this, 'recalculate_price'));

        // Seat Selection AJAX handlers
        add_action('wp_ajax_amadex_get_seatmap', array($this, 'get_seatmap'));
        add_action('wp_ajax_nopriv_amadex_get_seatmap', array($this, 'get_seatmap'));
        add_action('wp_ajax_amadex_price_selected_seats', array($this, 'price_selected_seats'));
        add_action('wp_ajax_nopriv_amadex_price_selected_seats', array($this, 'price_selected_seats'));

        // Currency conversion AJAX handlers
        add_action('wp_ajax_amadex_convert_currency', array($this, 'convert_currency'));
        add_action('wp_ajax_nopriv_amadex_convert_currency', array($this, 'convert_currency'));
        add_action('wp_ajax_amadex_get_exchange_rate', array($this, 'get_exchange_rate'));
        add_action('wp_ajax_nopriv_amadex_get_exchange_rate', array($this, 'get_exchange_rate'));

        // IP-based location detection AJAX handlers
        add_action('wp_ajax_amadex_get_user_location', array($this, 'get_user_location'));
        add_action('wp_ajax_nopriv_amadex_get_user_location', array($this, 'get_user_location'));

        // Stripe PaymentIntent creation AJAX handler
        add_action('wp_ajax_amadex_create_payment_intent', array($this, 'create_payment_intent'));
        add_action('wp_ajax_nopriv_amadex_create_payment_intent', array($this, 'create_payment_intent'));

        // Stripe webhook handler (for production payment status tracking)
        add_action('wp_ajax_amadex_stripe_webhook', array($this, 'handle_stripe_webhook'));
        add_action('wp_ajax_nopriv_amadex_stripe_webhook', array($this, 'handle_stripe_webhook'));

        // Stripe refund handler
        add_action('wp_ajax_amadex_stripe_refund', array($this, 'process_stripe_refund'));

        // Stripe Elements Session handler (for deferred PaymentIntent)
        add_action('wp_ajax_amadex_create_elements_session', array($this, 'create_elements_session'));
        add_action('wp_ajax_nopriv_amadex_create_elements_session', array($this, 'create_elements_session'));

        // Check Checkout Session status (for complete page)
        add_action('wp_ajax_amadex_checkout_session_status', array($this, 'checkout_session_status'));
        add_action('wp_ajax_nopriv_amadex_checkout_session_status', array($this, 'checkout_session_status'));

        // Stripe return: after payment, redirect directly to confirmation (skip complete-payment page) - run first
        add_action('template_redirect', array($this, 'handle_stripe_return_redirect'), 0);

        // Stripe diagnostic endpoint (for debugging)
        add_action('wp_ajax_amadex_stripe_diagnostic', array($this, 'stripe_diagnostic'));
        add_action('wp_ajax_nopriv_amadex_stripe_diagnostic', array($this, 'stripe_diagnostic'));

        // Store booking data for payment page
        add_action('wp_ajax_amadex_store_booking_for_payment', array($this, 'store_booking_for_payment'));
        add_action('wp_ajax_nopriv_amadex_store_booking_for_payment', array($this, 'store_booking_for_payment'));

        // Get booking data for payment page
        add_action('wp_ajax_amadex_get_booking_for_payment', array($this, 'get_booking_for_payment'));
        add_action('wp_ajax_nopriv_amadex_get_booking_for_payment', array($this, 'get_booking_for_payment'));

        // Delete booking token (after successful payment)
        add_action('wp_ajax_amadex_delete_booking_token', array($this, 'delete_booking_token'));
        add_action('wp_ajax_nopriv_amadex_delete_booking_token', array($this, 'delete_booking_token'));

        // Aircraft details AJAX handlers
        add_action('wp_ajax_amadex_aircraft_details', array($this, 'get_aircraft_details'));
        add_action('wp_ajax_nopriv_amadex_aircraft_details', array($this, 'get_aircraft_details'));

        // Promotional containers AJAX handler
        add_action('wp_ajax_amadex_get_promotional_containers', array($this, 'get_promotional_containers'));
        add_action('wp_ajax_nopriv_amadex_get_promotional_containers', array($this, 'get_promotional_containers'));
    }

    /**
     * Search flights AJAX handler
     */
    public function search_flights()
    {
        // Start performance timing
        if (class_exists('Amadex_Performance')) {
            Amadex_Performance::start_timer('ajax_search_flights');
            Amadex_Performance::start_timer('ttfb');
        }

        // Debug: Log received data first
        amadex_log('Amadex Search - Received POST data: ' . print_r($_POST, true));

        // Verify nonce with better error handling
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            amadex_log('Amadex Search - Nonce verification failed', 'error');
            if (class_exists('Amadex_Performance')) {
                Amadex_Performance::end_timer('ajax_search_flights');
                Amadex_Performance::end_timer('ttfb');
            }
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'amadex')));
            return;
        }

        // Get trip type first
        $trip_type = sanitize_text_field($_POST['trip_type'] ?? 'round');
        $is_oneway = ($trip_type === 'oneway' || $trip_type === 'one-way' || isset($_POST['one_way']) && $_POST['one_way'] === 'Yes');


        // Handle travel_class - if empty, 'ECONOMY', or 'ANY', don't filter by cabin (show all cabins)
        $travel_class = isset($_POST['travel_class']) ? sanitize_text_field($_POST['travel_class']) : '';
        $show_all_cabins = isset($_POST['show_all_cabins']) && $_POST['show_all_cabins'] === 'yes';

        // Normalize travel class to uppercase
        $travel_class = strtoupper(trim($travel_class));

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

        amadex_log('Amadex Search - Received travel_class: ' . (isset($_POST['travel_class']) ? $_POST['travel_class'] : 'NOT SET'));
        amadex_log('Amadex Search - Normalized travel_class: ' . $travel_class);
        amadex_log('Amadex Search - show_all_cabins: ' . ($show_all_cabins ? 'yes' : 'no'));

        // If show_all_cabins is yes, travel_class is empty/null, ECONOMY, or ANY, set travel_class to empty to show all cabins
        // When no cabin is selected or ECONOMY is selected, show all cabin classes (like cheapdealsfare.com)
        if ($show_all_cabins || empty($travel_class) || $travel_class === '' || $travel_class === 'ECONOMY' || $travel_class === 'ANY') {
            amadex_log('Amadex Search - Clearing travel_class to show all cabins');
            $travel_class = '';
            $show_all_cabins = true; // Force show all cabins
        } else {
            amadex_log('Amadex Search - Keeping travel_class: ' . $travel_class);
        }

        // Check if this is initial load or load more
        $is_initial_load = !isset($_POST['load_more']) || $_POST['load_more'] !== '1';
        $is_progressive_load = isset($_POST['progressive_load']) && $_POST['progressive_load'] === '1';
        $is_progressive_fetch = isset($_POST['progressive_fetch']) && $_POST['progressive_fetch'] === '1'; // Background fetch for remaining results

        // Check if progressive loading is enabled in settings
        $settings = get_option('amadex_performance_settings', array());
        $progressive_enabled = isset($settings['enable_progressive_loading']) && $settings['enable_progressive_loading'] === '1';

        $params = array(
            'origin' => sanitize_text_field($_POST['origin'] ?? ''),
            'destination' => sanitize_text_field($_POST['destination'] ?? ''),
            'departure_date' => sanitize_text_field($_POST['departure_date'] ?? ''),
            'return_date' => $is_oneway ? '' : sanitize_text_field($_POST['return_date'] ?? ''), // Clear return_date for one-way
            'adults' => intval($_POST['adults'] ?? 1),
            'children' => intval($_POST['children'] ?? 0),
            'infants' => intval($_POST['infants'] ?? 0),
            'travel_class' => $travel_class, // Empty string means show all cabins
            'currency' => sanitize_text_field($_POST['currency'] ?? 'USD'),
            'non_stop' => isset($_POST['non_stop']) ? (bool) $_POST['non_stop'] : false,
            'trip_type' => $trip_type,
            'show_all_cabins' => $show_all_cabins || empty($travel_class) || $travel_class === 'ECONOMY' || $travel_class === 'ANY',
            'initial_load' => $is_initial_load, // Pass to API to determine max results
            'progressive_loading' => ($progressive_enabled && $is_progressive_load && !$is_progressive_fetch) // Enable progressive mode for quick results
        );

        amadex_log('Amadex Search - Trip type: ' . $trip_type . ', Is one-way: ' . ($is_oneway ? 'Yes' : 'No') . ', Initial load: ' . ($is_initial_load ? 'Yes' : 'No'));
        amadex_log('Amadex Search - Final params being sent to API: ' . print_r($params, true));

        // Handle multi-city segments
        if (isset($_POST['multi_segments']) && !empty($_POST['multi_segments'])) {
            $multi_segments = json_decode(stripslashes($_POST['multi_segments']), true);
            if ($multi_segments && is_array($multi_segments) && count($multi_segments) > 1) {
                $params['trip_type'] = 'multi-city';
                $params['segments'] = $multi_segments;
                amadex_log('Amadex Search - Multi-city segments received: ' . print_r($multi_segments, true));
            }
        }

        // Also check for segments parameter (alternative format)
        if (isset($_POST['segments']) && !empty($_POST['segments'])) {
            $segments = json_decode(stripslashes($_POST['segments']), true);
            if ($segments && is_array($segments) && count($segments) > 1) {
                $params['trip_type'] = 'multi-city';
                $params['segments'] = $segments;
                amadex_log('Amadex Search - Segments parameter received: ' . print_r($segments, true));
            }
        }

        // Debug: Log processed params
        amadex_log('Amadex Search - Processed params: ' . print_r($params, true));

        // Validate required fields
        if (empty($params['origin']) || empty($params['destination']) || empty($params['departure_date'])) {
            wp_send_json_error(array('message' => __('Please fill in all required fields: Origin, Destination, and Departure Date', 'amadex')));
        }

        // Validate dates
        if (strtotime($params['departure_date']) < strtotime('today')) {
            wp_send_json_error(array('message' => __('Departure date cannot be in the past', 'amadex')));
        }

        if (!empty($params['return_date']) && strtotime($params['return_date']) < strtotime($params['departure_date'])) {
            wp_send_json_error(array('message' => __('Return date must be after departure date', 'amadex')));
        }

        // Check if API class exists
        if (!class_exists('Amadex_API')) {
            amadex_log('Amadex Search Error: Amadex_API class not found', 'error');
            if (class_exists('Amadex_Performance')) {
                Amadex_Performance::end_timer('ajax_search_flights');
                Amadex_Performance::end_timer('ttfb');
            }
            wp_send_json_error(array('message' => __('Plugin not properly initialized. Please contact administrator.', 'amadex')));
            return;
        }

        // Check cache first (only for non-progressive loads or progressive fetch)
        $cache_key = null;
        $cached_results = false;
        if (class_exists('Amadex_Redis_Cache') && !$is_progressive_load) {
            $cache_key = Amadex_Redis_Cache::generate_cache_key($params);
            $cached_results = Amadex_Redis_Cache::get($cache_key, false);

            if ($cached_results !== false && !empty($cached_results)) {
                amadex_log('Amadex Search: Cache HIT - Returning cached results');

                // Add cache indicator
                $cached_results['_cached'] = true;
                $cached_results['_cache_key'] = $cache_key;

                // Record performance
                if (class_exists('Amadex_Performance')) {
                    Amadex_Performance::record_metric('cache_hit', true);
                    $ttfb = Amadex_Performance::end_timer('ttfb');
                    Amadex_Performance::end_timer('ajax_search_flights');
                    $cached_results['_performance'] = Amadex_Performance::get_metrics();
                }

                wp_send_json_success($cached_results);
                return;
            } else {
                amadex_log('Amadex Search: Cache MISS - Fetching from API');
                if (class_exists('Amadex_Performance')) {
                    Amadex_Performance::record_metric('cache_hit', false);
                }
            }
        }

        // Search flights using API
        try {
            $api = new Amadex_API();

            amadex_log('Amadex Search - Calling API with params: ' . print_r($params, true));

            // Check if multi-city trip - search each segment separately
            if (isset($params['segments']) && is_array($params['segments']) && count($params['segments']) > 1) {
                amadex_log('Amadex Search - Multi-city trip detected with ' . count($params['segments']) . ' segments');

                $all_flights = array();
                $segment_results = array();

                // Search each segment separately
                foreach ($params['segments'] as $segment_index => $segment) {
                    amadex_log('Amadex Search - Searching segment ' . ($segment_index + 1) . ': ' .
                        ($segment['origin'] ?? $segment['originLocationCode'] ?? '') . ' → ' .
                        ($segment['destination'] ?? $segment['destinationLocationCode'] ?? ''));

                    // Build search params for this segment
                    $segment_params = array(
                        'origin' => $segment['origin'] ?? $segment['originLocationCode'] ?? '',
                        'destination' => $segment['destination'] ?? $segment['destinationLocationCode'] ?? '',
                        'departure_date' => $segment['departure_date'] ?? $segment['departureDate'] ?? '',
                        'return_date' => '', // No return for individual segments
                        'adults' => $params['adults'],
                        'children' => $params['children'],
                        'infants' => $params['infants'],
                        'travel_class' => $params['travel_class'],
                        'currency' => $params['currency'],
                        'non_stop' => $params['non_stop']
                    );

                    // Search this segment
                    $segment_result = $api->search_flights($segment_params);

                    if (is_wp_error($segment_result)) {
                        amadex_log('Amadex Search - Error searching segment ' . ($segment_index + 1) . ': ' . $segment_result->get_error_message(), 'error');
                        // Continue with other segments even if one fails
                        $segment_results[$segment_index] = array(
                            'flights' => array(),
                            'meta' => array('count' => 0, 'currency' => $params['currency'])
                        );
                        continue;
                    }

                    // Extract flights from segment result
                    $segment_flights = isset($segment_result['flights']) ? $segment_result['flights'] : array();

                    // Tag each flight with its segment index
                    foreach ($segment_flights as &$flight) {
                        $flight['_segment_index'] = $segment_index;
                        $flight['_segment_origin'] = $segment_params['origin'];
                        $flight['_segment_destination'] = $segment_params['destination'];
                    }

                    $segment_results[$segment_index] = array(
                        'flights' => $segment_flights,
                        'meta' => isset($segment_result['meta']) ? $segment_result['meta'] : array('count' => count($segment_flights), 'currency' => $params['currency'])
                    );

                    // Add to all flights array
                    $all_flights = array_merge($all_flights, $segment_flights);

                    amadex_log('Amadex Search - Segment ' . ($segment_index + 1) . ' found ' . count($segment_flights) . ' flights');
                }

                // Combine all segment results
                $results = array(
                    'flights' => $all_flights,
                    'meta' => array(
                        'count' => count($all_flights),
                        'currency' => $params['currency']
                    ),
                    'segments' => $params['segments'],
                    'segment_results' => $segment_results, // Store individual segment results for frontend filtering
                    'is_multi_city' => true
                );

                amadex_log('Amadex Search - Multi-city search completed. Total flights: ' . count($all_flights));
            } else {
                // Single search (one-way or round trip)
                $results = $api->search_flights($params);
            }

            if (is_wp_error($results)) {
                $error_msg = $results->get_error_message();
                amadex_log('Amadex Search Error: ' . $error_msg, 'error');
                amadex_log('Amadex Search Error Code: ' . $results->get_error_code(), 'error');
                if (class_exists('Amadex_Performance')) {
                    Amadex_Performance::end_timer('ajax_search_flights');
                    Amadex_Performance::end_timer('ttfb');
                }
                wp_send_json_error(array('message' => $error_msg));
                return;
            }

            // Check if results are valid
            if (empty($results)) {
                amadex_log('Amadex Search - Empty results received', 'warning');
                if (class_exists('Amadex_Performance')) {
                    Amadex_Performance::end_timer('ajax_search_flights');
                    Amadex_Performance::end_timer('ttfb');
                }
                wp_send_json_error(array('message' => __('No flight results found. Please try different dates or routes.', 'amadex')));
                return;
            }

            // Check if results have flights
            $flight_count = isset($results['meta']['count']) ? $results['meta']['count'] : (isset($results['flights']) && is_array($results['flights']) ? count($results['flights']) : 0);

            amadex_log('Amadex Search - Results structure: ' . print_r(array(
                'has_meta' => isset($results['meta']),
                'meta_count' => isset($results['meta']['count']) ? $results['meta']['count'] : 0,
                'has_flights' => isset($results['flights']),
                'flights_count' => isset($results['flights']) && is_array($results['flights']) ? count($results['flights']) : 0,
                'has_data' => isset($results['data']),
                'data_count' => isset($results['data']) && is_array($results['data']) ? count($results['data']) : 0,
                'is_multi_city' => isset($results['is_multi_city']),
                'segment_results' => isset($results['segment_results']) ? array_keys($results['segment_results']) : array()
            ), true));

            amadex_log('Amadex Search - Results count: ' . $flight_count);

            // Store results in session for filtering
            if (!session_id()) {
                @session_start();
            }
            $_SESSION['amadex_search_results'] = $results;
            $_SESSION['amadex_search_params'] = $params;

            // Cache results (only cache full results, not progressive loads)
            if (class_exists('Amadex_Redis_Cache') && !$is_progressive_load && $cache_key) {
                // Cache for 5 minutes (300 seconds)
                $cache_ttl = apply_filters('amadex_cache_ttl', 300);
                Amadex_Redis_Cache::set($cache_key, $results, $cache_ttl);
                amadex_log('Amadex Search: Cached results with key: ' . $cache_key . ' (TTL: ' . $cache_ttl . 's)');
            }

            // Add progressive loading flag to response
            if ($is_progressive_load) {
                $results['_progressive'] = true;
                $results['_progressive_initial'] = true;
            } elseif ($is_progressive_fetch) {
                $results['_progressive'] = true;
                $results['_progressive_fetch'] = true;
            }

            // Streaming response support (if enabled and initial load)
            if (class_exists('Amadex_Streaming') && Amadex_Streaming::is_enabled() && $is_initial_load && !$is_progressive_fetch) {
                $flights = isset($results['flights']) ? $results['flights'] : array();
                if (!empty($flights) && is_array($flights)) {
                    $initial_count = Amadex_Streaming::get_initial_count();
                    $streamed_results = Amadex_Streaming::stream_initial_results($flights, $initial_count);

                    // Merge with existing results structure
                    $results = array_merge($results, $streamed_results);
                    $results['_streaming_enabled'] = true;

                    amadex_log('Amadex Streaming: Sent initial ' . count($streamed_results['flights']) . ' flights out of ' . count($flights) . ' total');
                }
            }

            // Record performance metrics
            if (class_exists('Amadex_Performance')) {
                $ttfb = Amadex_Performance::end_timer('ttfb');
                Amadex_Performance::record_metric('response_size', strlen(json_encode($results)));
                Amadex_Performance::record_metric('flights_count', $flight_count);
                Amadex_Performance::record_metric('progressive_mode', $is_progressive_load);
                Amadex_Performance::end_timer('ajax_search_flights');

                // Save metrics
                $search_id = 'search_' . time() . '_' . wp_generate_password(8, false);
                Amadex_Performance::save_metrics($search_id);

                // Add metrics to response for frontend
                $results['_performance'] = Amadex_Performance::get_metrics();
            }

            // Send success response
            wp_send_json_success($results);
        } catch (Exception $e) {
            amadex_log('Amadex Search Exception: ' . $e->getMessage(), 'error');
            amadex_log('Amadex Search Exception Trace: ' . $e->getTraceAsString(), 'error');
            if (class_exists('Amadex_Performance')) {
                Amadex_Performance::end_timer('ajax_search_flights');
                Amadex_Performance::end_timer('ttfb');
            }
            wp_send_json_error(array('message' => __('An error occurred during search: ', 'amadex') . $e->getMessage()));
        }
    }

    /**
     * Search airports AJAX handler
     */
    public function search_airports()
    {

        // Clean any unwanted output
        if (ob_get_length()) {
            ob_clean();
        }

        // header('Content-Type: application/json; charset=utf-8');

        // Verify nonce with better error handling
        if (!isset($_POST['nonce']) && !isset($_GET['nonce'])) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : $_GET['nonce'];
        if (!wp_verify_nonce($nonce, 'amadex_nonce')) {
            amadex_log('Amadex Airport Search - Nonce verification failed');
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $keyword = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : sanitize_text_field($_POST['keyword'] ?? '');

        if (strlen($keyword) < 2) {
            wp_send_json_error(array('message' => __('Please enter at least 2 characters', 'amadex')));
            return;
        }

        try {
            if (!class_exists('Amadex_API')) {
                wp_send_json_error(array('message' => __('Plugin not properly initialized.', 'amadex')));
                return;
            }

            $api = new Amadex_API();
            $airports = $api->search_airports($keyword);

            if (is_wp_error($airports)) {
                wp_send_json_error(array('message' => $airports->get_error_message()));
                return;
            }

            // Format airports for frontend
            $formatted_airports = array();
            foreach ($airports as $airport) {
                $formatted_airports[] = array(
                    'code' => $airport['iataCode'] ?? '',
                    'name' => $airport['name'] ?? '',
                    'city' => $airport['address']['cityName'] ?? '',
                    'country' => $airport['address']['countryName'] ?? '',
                    'display' => ($airport['iataCode'] ?? '') . ' - ' . ($airport['name'] ?? '') . ', ' . ($airport['address']['cityName'] ?? '')
                );
            }

            wp_send_json_success($formatted_airports);
        } catch (Exception $e) {
            amadex_log('Amadex Airport Search Exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Error searching airports: ', 'amadex') . $e->getMessage()));
        }
    }

    /**
     * Get flight details AJAX handler
     */
    public function get_flight_details()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $flight_id = sanitize_text_field($_POST['flight_id'] ?? '');

        if (empty($flight_id)) {
            wp_send_json_error(array('message' => __('Flight ID is required', 'amadex')));
            return;
        }

        try {
            if (!class_exists('Amadex_API')) {
                wp_send_json_error(array('message' => __('Plugin not properly initialized.', 'amadex')));
                return;
            }

            $api = new Amadex_API();
            $details = $api->get_flight_details($flight_id);

            if (is_wp_error($details)) {
                wp_send_json_error(array('message' => $details->get_error_message()));
                return;
            }

            wp_send_json_success($details);
        } catch (Exception $e) {
            amadex_log('Amadex Get Flight Details Exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Error getting flight details: ', 'amadex') . $e->getMessage()));
        }
    }

    /**
     * Filter flights AJAX handler
     */
    public function filter_flights()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        if (!session_id()) {
            @session_start();
        }

        $search_results = $_SESSION['amadex_search_results'] ?? null;

        if (!$search_results) {
            wp_send_json_error(array('message' => __('No search results found', 'amadex')));
        }

        $filters = array(
            'airlines' => $_POST['airlines'] ?? array(),
            'stops' => $_POST['stops'] ?? array(),
            'price_min' => floatval($_POST['price_min'] ?? 0),
            'price_max' => floatval($_POST['price_max'] ?? 0),
            'departure_times' => $_POST['departure_times'] ?? array(),
            'arrival_times' => $_POST['arrival_times'] ?? array()
        );

        $filtered_flights = $this->apply_filters($search_results['flights'], $filters);

        $filtered_results = $search_results;
        $filtered_results['flights'] = $filtered_flights;
        $filtered_results['meta']['count'] = count($filtered_flights);

        wp_send_json_success($filtered_results);
    }

    /**
     * Apply filters to flight results
     */
    private function apply_filters($flights, $filters)
    {
        $filtered = array();

        foreach ($flights as $flight) {
            $include = true;

            // Filter by airlines
            if (!empty($filters['airlines'])) {
                $flight_airlines = $flight['validating_airline_codes'] ?? array();
                $has_matching_airline = false;
                foreach ($flight_airlines as $airline) {
                    if (in_array($airline, $filters['airlines'])) {
                        $has_matching_airline = true;
                        break;
                    }
                }
                if (!$has_matching_airline) {
                    $include = false;
                }
            }

            // Filter by stops
            if (!empty($filters['stops']) && $include) {
                $flight_stops = array();
                foreach ($flight['itineraries'] as $itinerary) {
                    $flight_stops[] = $itinerary['stops'];
                }
                $has_matching_stops = false;
                foreach ($flight_stops as $stops) {
                    if (in_array($stops, $filters['stops'])) {
                        $has_matching_stops = true;
                        break;
                    }
                }
                if (!$has_matching_stops) {
                    $include = false;
                }
            }

            // Filter by price
            if ($include && ($filters['price_min'] > 0 || $filters['price_max'] > 0)) {
                $price = floatval($flight['price']['total']);
                if ($filters['price_min'] > 0 && $price < $filters['price_min']) {
                    $include = false;
                }
                if ($filters['price_max'] > 0 && $price > $filters['price_max']) {
                    $include = false;
                }
            }

            // Filter by departure times
            if ($include && !empty($filters['departure_times'])) {
                $has_matching_departure = false;
                foreach ($flight['itineraries'] as $itinerary) {
                    foreach ($itinerary['segments'] as $segment) {
                        $departure_time = date('H:i', strtotime($segment['departure']['at']));
                        $period = $this->get_time_period($departure_time);
                        if (in_array($period, $filters['departure_times'])) {
                            $has_matching_departure = true;
                            break 2;
                        }
                    }
                }
                if (!$has_matching_departure) {
                    $include = false;
                }
            }

            // Filter by arrival times
            if ($include && !empty($filters['arrival_times'])) {
                $has_matching_arrival = false;
                foreach ($flight['itineraries'] as $itinerary) {
                    foreach ($itinerary['segments'] as $segment) {
                        $arrival_time = date('H:i', strtotime($segment['arrival']['at']));
                        $period = $this->get_time_period($arrival_time);
                        if (in_array($period, $filters['arrival_times'])) {
                            $has_matching_arrival = true;
                            break 2;
                        }
                    }
                }
                if (!$has_matching_arrival) {
                    $include = false;
                }
            }

            if ($include) {
                $filtered[] = $flight;
            }
        }

        return $filtered;
    }

    /**
     * Get time period for filtering
     */
    private function get_time_period($time)
    {
        $hour = intval(substr($time, 0, 2));
        $minute = intval(substr($time, 3, 2));
        $time_minutes = $hour * 60 + $minute;

        if ($time_minutes >= 300 && $time_minutes <= 719) { // 05:00 - 11:59
            return 'morning';
        } elseif ($time_minutes >= 720 && $time_minutes <= 1079) { // 12:00 - 17:59
            return 'afternoon';
        } elseif ($time_minutes >= 1080 && $time_minutes <= 1439) { // 18:00 - 23:59
            return 'evening';
        } else { // 00:00 - 04:59
            return 'overnight';
        }
    }

    /**
     * Test API connection AJAX handler
     */
    public function test_api()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
            return;
        }

        try {
            if (!class_exists('Amadex_API')) {
                wp_send_json_error(array('message' => __('Plugin not properly initialized.', 'amadex')));
                return;
            }

            $api = new Amadex_API();
            $result = $api->test_api_connection();

            // The test_api_connection method returns an array, not WP_Error
            if (!$result['success']) {
                wp_send_json_error(array('message' => $result['message']));
                return;
            }

            wp_send_json_success($result);
        } catch (Exception $e) {
            amadex_log('Amadex Test API Exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Error testing API: ', 'amadex') . $e->getMessage()));
        }
    }

    /**
     * Clear token cache AJAX handler
     */
    public function clear_token_cache()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
            return;
        }

        try {
            if (!class_exists('Amadex_API')) {
                wp_send_json_error(array('message' => __('Plugin not properly initialized.', 'amadex')));
                return;
            }

            $api = new Amadex_API();
            $result = $api->clear_token_cache();

            if ($result) {
                wp_send_json_success(array('message' => __('Token cache cleared successfully', 'amadex')));
            } else {
                wp_send_json_error(array('message' => __('Failed to clear token cache', 'amadex')));
            }
        } catch (Exception $e) {
            amadex_log('Amadex Clear Token Cache Exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Error clearing cache: ', 'amadex') . $e->getMessage()));
        }
    }

    /**
     * Process booking AJAX handler
     */
    public function process_booking()
    {
        $stripe_return_key = isset($_POST['amadex_stripe_return_key']) ? sanitize_text_field($_POST['amadex_stripe_return_key']) : '';
        if (empty($stripe_return_key)) {
            check_ajax_referer('amadex_nonce', 'nonce');
        } else {
            $stripe_return_data = get_transient('amadex_stripe_return_' . $stripe_return_key);
            delete_transient('amadex_stripe_return_' . $stripe_return_key);
            if (empty($stripe_return_data) || !is_array($stripe_return_data)) {
                if (defined('AMADEX_STRIPE_RETURN_REDIRECT') && AMADEX_STRIPE_RETURN_REDIRECT) {
                    wp_safe_redirect(home_url('/?amadex_stripe_error=expired'));
                    exit;
                }
                wp_send_json_error(array('message' => __('Stripe return session expired. Please try again.', 'amadex')));
                return;
            }
        }

        // ✅ CRITICAL: Ensure tables exist FIRST (before any duplicate checks)
        $database = new Amadex_Database();
        $database->ensure_tables_ready(); // This will create booking_locks table if missing

        // ✅ DUPLICATE PREVENTION: Generate request hash and check for duplicates
        $request_hash = $this->generate_request_hash($_POST);

        // Check for duplicate request BEFORE any processing
        $existing_booking = $this->check_duplicate_request($request_hash);
        if ($existing_booking) {
            amadex_log('Amadex Booking: Duplicate request detected - returning existing booking ID: ' . $existing_booking['booking_id']);
            $confirmation_url = $this->get_confirmation_page_url($existing_booking['booking_reference'] ?? '');
            if (defined('AMADEX_STRIPE_RETURN_REDIRECT') && AMADEX_STRIPE_RETURN_REDIRECT) {
                wp_safe_redirect($confirmation_url);
                exit;
            }
            wp_send_json_success(array(
                'booking_id' => $existing_booking['booking_id'],
                'booking_reference' => $existing_booking['booking_reference'],
                'confirmation_url' => $confirmation_url,
                'duplicate' => true,
                'message' => 'Booking already processed'
            ));
            return; // EXIT EARLY - NO PAYMENT AUTHORIZATION
        }

        // ✅ Acquire session lock to prevent concurrent processing
        $lock_key = $this->acquire_booking_lock($request_hash);
        if (!$lock_key) {
            amadex_log('Amadex Booking: Lock acquisition failed - request already processing');
            if (defined('AMADEX_STRIPE_RETURN_REDIRECT') && AMADEX_STRIPE_RETURN_REDIRECT) {
                wp_safe_redirect(home_url('/?amadex_stripe_error=booking'));
                exit;
            }
            wp_send_json_error(array(
                'message' => 'A booking request is already being processed. Please wait a few seconds and try again. You can close this message and retry.',
                'code' => 'DUPLICATE_REQUEST',
                'retry_after' => 5,
                'closable' => true // Flag for frontend to show close button
            ));
            return; // EXIT EARLY - NO PAYMENT AUTHORIZATION
        }

        // Handle booking_data - jQuery serializes nested objects
        // WordPress AJAX receives them as $_POST['booking_data'] array
        $booking_data = $_POST['booking_data'] ?? array();

        // If booking_data is a string (JSON), decode it
        if (is_string($booking_data)) {
            $booking_data = json_decode(stripslashes($booking_data), true);
        }

        // Debug: Log seat selection data received from frontend
        amadex_log('Amadex Booking: Received booking_data keys: ' . implode(', ', array_keys($booking_data)));
        if (isset($booking_data['seat_selection'])) {
            amadex_log('Amadex Booking: ✓ Seat selection data RECEIVED from frontend');
            amadex_log('Amadex Booking: Seat selection data: ' . print_r($booking_data['seat_selection'], true));
        } else {
            amadex_log('Amadex Booking: ✗ NO seat_selection field in booking_data');
            amadex_log('Amadex Booking: Available keys in booking_data: ' . implode(', ', array_keys($booking_data)));
        }

        // If booking_data is empty or not an array, try to reconstruct from POST
        if (empty($booking_data) || !is_array($booking_data)) {
            // Check if data was sent with booking_data prefix (jQuery serialization)
            if (isset($_POST['booking_data']) && is_array($_POST['booking_data'])) {
                $booking_data = $_POST['booking_data'];
            }
        }

        // Also check if data was sent directly (for backward compatibility)
        if (empty($booking_data) && !empty($_POST['flight'])) {
            $booking_data = array(
                'flight' => json_decode(stripslashes($_POST['flight'] ?? '{}'), true),
                'passengers' => json_decode(stripslashes($_POST['passengers'] ?? '[]'), true),
                'contact' => array(
                    'first_name' => $_POST['contact']['first_name'] ?? '',
                    'last_name' => $_POST['contact']['last_name'] ?? '',
                    'phone' => $_POST['contact']['phone'] ?? '',
                    'email' => $_POST['contact']['email'] ?? ''
                ),
                'billing' => array(
                    'first_name' => $_POST['billing']['first_name'] ?? '',
                    'last_name' => $_POST['billing']['last_name'] ?? '',
                    'address1' => $_POST['billing']['address1'] ?? '',
                    'address2' => $_POST['billing']['address2'] ?? '',
                    'city' => $_POST['billing']['city'] ?? '',
                    'state' => $_POST['billing']['state'] ?? '',
                    'postal' => $_POST['billing']['postal'] ?? '',
                    'country' => $_POST['billing']['country'] ?? 'US'
                ),
                'payment' => array(
                    'card_number' => $_POST['payment']['card_number'] ?? '',
                    'card_month' => $_POST['payment']['card_month'] ?? '',
                    'card_year' => $_POST['payment']['card_year'] ?? '',
                    'card_cvv' => $_POST['payment']['card_cvv'] ?? '',
                    'card_name' => $_POST['payment']['card_name'] ?? ''
                )
            );
        }

        // Validate booking data
        if (empty($booking_data) || empty($booking_data['flight'])) {
            amadex_log('Amadex Booking Error: Invalid booking data structure. POST: ' . print_r($_POST, true));

            // ✅ Release lock on error
            if (!empty($lock_key)) {
                $this->release_booking_lock($lock_key, null, 'FAILED');
            }

            if (defined('AMADEX_STRIPE_RETURN_REDIRECT') && AMADEX_STRIPE_RETURN_REDIRECT) {
                wp_safe_redirect(home_url('/?amadex_stripe_error=booking'));
                exit;
            }
            wp_send_json_error(array('message' => __('Invalid booking data. Please try again.', 'amadex')));
            return;
        }

        // ✅ CRITICAL: Ensure booking_locks table exists BEFORE duplicate check
        $database = new Amadex_Database();
        $database->ensure_tables_ready(); // This will create booking_locks table if missing

        // ✅ Wrap entire booking process in try-finally to ensure lock is always released
        try {

            // Log booking data
            amadex_log('Amadex Booking Data: ' . print_r($booking_data, true));

            // ✅ CRITICAL: Ensure booking_locks table exists (it might not be in ensure_tables_ready check)
            $locks_table = $wpdb->prefix . 'amadex_booking_locks';
            if (!$database->table_exists_public($locks_table)) {
                amadex_log('Amadex Booking: booking_locks table missing - creating now');
                $database->create_tables();

                // Verify it was created
                if (!$database->table_exists_public($locks_table)) {
                    amadex_log('Amadex Booking: WARNING - booking_locks table creation failed. Using transient-only locks.');
                }
            }

            // Check if payment bypass is enabled (for testing)
            $payment_settings = get_option('amadex_payment_settings', array());
            $bypass_payment = isset($payment_settings['nmi_bypass_for_testing']) && $payment_settings['nmi_bypass_for_testing'] == 1;

            // Get default card gateway (NMI or Stripe) - normalize to lowercase so "NMI" works when NMI is enabled
            $default_gateway = isset($payment_settings['default_card_gateway']) ? strtolower(trim(sanitize_text_field($payment_settings['default_card_gateway']))) : 'nmi';
            if (!in_array($default_gateway, array('nmi', 'stripe'))) {
                $default_gateway = 'nmi'; // Fallback to NMI if invalid
            }

            // Gateway-specific validation and token/intent extraction
            $payment_token = '';
            $payment_intent_id = '';

            if ($default_gateway === 'nmi') {
                // NMI validation and token extraction
                $payment = new Amadex_Payment();

                // Verify NMI API key is configured FIRST (before checking payment token)
                $nmi_api_key = isset($payment_settings['nmi_api_key']) ? trim($payment_settings['nmi_api_key']) : '';
                $nmi_tokenization_key = isset($payment_settings['nmi_tokenization_key']) ? trim($payment_settings['nmi_tokenization_key']) : '';

                if (empty($nmi_api_key) && !$bypass_payment) {
                    amadex_log('Amadex Booking Error: NMI API key (Security Key) is not configured');

                    // ✅ Release lock on error
                    if (!empty($lock_key)) {
                        $this->release_booking_lock($lock_key, null, 'FAILED');
                    }

                    wp_send_json_error(array(
                        'message' => __('NMI API Key (Security Key) is not configured. Please go to WordPress Admin → Amadex → API Settings → Payment tab → NMI API Key (Security Key) and enter your Security Key from NMI Dashboard. This is REQUIRED for payment authorization. Find it in: NMI Dashboard → Settings → Security Keys → Private Security Keys → API Key.', 'amadex')
                    ));
                    return;
                }

                if (empty($nmi_tokenization_key) && !$bypass_payment) {
                    amadex_log('Amadex Booking Error: NMI Tokenization key is not configured');

                    // ✅ Release lock on error
                    if (!empty($lock_key)) {
                        $this->release_booking_lock($lock_key, null, 'FAILED');
                    }

                    wp_send_json_error(array(
                        'message' => __('NMI Tokenization Key is not configured. Please go to WordPress Admin → Amadex → API Settings → Payment tab → NMI Tokenization Key and enter your Public Tokenization Key from NMI Dashboard. Find it in: NMI Dashboard → Settings → Security Keys → Public Keys → Tokenization.', 'amadex')
                    ));
                    return;
                }

                // Validate key compatibility before processing payment
                if (!$bypass_payment && !empty($nmi_api_key) && !empty($nmi_tokenization_key)) {
                    // Check if keys appear to be from different sets
                    $is_flytravelay_token = (strpos($nmi_tokenization_key, 'mY59yM') === 0 || strpos($nmi_tokenization_key, 'mY59yM-VHu2b4') === 0);
                    $is_flytravelay_api = (strpos($nmi_api_key, 'aV5WZD69') === 0 || strpos($nmi_api_key, 'aV5WZD69uVnXa8kUUjT3W2nm3aQ2522G') === 0);
                    $is_default_api = (strpos($nmi_api_key, 'gXd86PD5tN76q2q33R9aAdDsksW2735g') === 0);

                    if ($is_flytravelay_token && $is_default_api) {
                        amadex_log('Amadex Booking Error: Key mismatch detected - FLYTRAVELAY tokenization key with Default API key');

                        // ✅ Release lock on error
                        if (!empty($lock_key)) {
                            $this->release_booking_lock($lock_key, null, 'FAILED');
                        }

                        wp_send_json_error(array(
                            'message' => __('Payment gateway configuration error: You are using FLYTRAVELAY Tokenization Key but Default Cart API Key. These keys must match! Please use FLYTRAVELAY API Key (starts with aV5WZD69...) instead of Default Cart Key. Go to Amadex Settings → Payment Settings → NMI API Key and replace with the FLYTRAVELAY API Key from your NMI Dashboard → Private Security Keys.', 'amadex'),
                            'details' => array(
                                'issue_type' => 'key_mismatch',
                                'tokenization_key_set' => 'FLYTRAVELAY',
                                'api_key_set' => 'Default',
                                'solution' => 'Use FLYTRAVELAY API Key instead of Default Cart Key'
                            )
                        ));
                        return;
                    }
                }

                // Get payment token if available (from Collect.js)
                $payment_token = $_POST['payment_token'] ?? $booking_data['payment_token'] ?? '';

                // ── Gateway.js 3DS raw-card path ─────────────────────────────────────────
                // When the frontend completes 3DS via Gateway.js (varient-style flow), it
                // sends booking_data with use_raw_card=true and payment.card_number instead
                // of a CollectJS payment_token. Detect this path and skip the token gate.
                $use_raw_card = !empty($booking_data['use_raw_card']) && $booking_data['use_raw_card'] === true;
                $has_raw_card = !empty($booking_data['payment']['card_number']);

                if ($use_raw_card && $has_raw_card) {
                    amadex_log('Amadex Booking: Gateway.js 3DS raw-card path detected — skipping CollectJS token check');
                    amadex_log('Amadex Booking: 3DS cavv present: ' . (!empty($booking_data['cavv']) ? 'Yes' : 'No'));
                    amadex_log('Amadex Booking: 3DS eci present: ' . (!empty($booking_data['eci']) ? 'Yes' : 'No'));
                } else {
                    // CollectJS token path — token is required (unless bypass is enabled)
                    // Validate payment token
                    if (!$bypass_payment && (empty($payment_token) || !is_string($payment_token) || trim($payment_token) === '')) {
                        amadex_log('Amadex Booking Error: Payment token is missing or invalid');

                        // ✅ Release lock on error
                        if (!empty($lock_key)) {
                            $this->release_booking_lock($lock_key, null, 'FAILED');
                        }

                        // Create a lead with failure reason so it appears in Failed Payments
                        if (!empty($booking_data['contact']['email']) || !empty($booking_data['contact']['phone'])) {
                            try {
                                $fail_client_ip = '';
                                foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
                                    if (!empty($_SERVER[$k])) { $fail_client_ip = trim(explode(',', $_SERVER[$k])[0]); break; }
                                }
                                $fail_fraud_data = null;
                                $fp2 = defined('AMADEX_PATH') ? AMADEX_PATH : (plugin_dir_path(dirname(__FILE__)) . '../');
                                if (file_exists($fp2 . 'includes/class-amadex-fraud-detection.php')) {
                                    require_once $fp2 . 'includes/class-amadex-fraud-detection.php';
                                    if (class_exists('Amadex_Fraud_Detection')) {
                                        $fd2 = new Amadex_Fraud_Detection();
                                        $fail_fraud_data = $fd2->process_fraud_data(
                                            $device_fingerprint_data ?? null,
                                            $fail_client_ip,
                                            $booking_data
                                        );
                                    }
                                }
                                $fail_lead_data = array(
                                    'lead_type'     => 'VERIFIED_LEAD',
                                    'contact_name'  => trim(($booking_data['contact']['first_name'] ?? '') . ' ' . ($booking_data['contact']['last_name'] ?? '')),
                                    'contact_email' => $booking_data['contact']['email'] ?? '',
                                    'contact_phone' => $booking_data['contact']['phone'] ?? '',
                                    'flight_data'   => $booking_data['flight'] ?? array(),
                                    'search_params' => $booking_data['search_params'] ?? array(),
                                    'source'        => 'ONLINE',
                                    'fraud_data'    => $fail_fraud_data,
                                );
                                $fail_lead_id = $database->create_lead($fail_lead_data);
                                if ($fail_lead_id) {
                                    global $wpdb;
                                    $raw_card   = $booking_data['payment']['card_number'] ?? '';
                                    $clean_card = preg_replace('/\D/', '', $raw_card);
                                    $card_last4 = strlen($clean_card) >= 4 ? substr($clean_card, -4) : '';
                                    $card_type  = '';
                                    if (preg_match('/^4/', $clean_card))                                  $card_type = 'Visa';
                                    elseif (preg_match('/^5[1-5]|^2[2-7]/', $clean_card))                $card_type = 'Mastercard';
                                    elseif (preg_match('/^3[47]/', $clean_card))                          $card_type = 'Amex';
                                    elseif (preg_match('/^6(?:011|5)/', $clean_card))                     $card_type = 'Discover';
                                    $exp_month  = $booking_data['payment']['card_month'] ?? '';
                                    $exp_year   = $booking_data['payment']['card_year']  ?? '';
                                    if ($exp_year && strlen($exp_year) === 2) $exp_year = '20' . $exp_year;
                                    $holder     = trim($booking_data['payment']['card_name'] ?? '');
                                    $wpdb->update(
                                        $wpdb->prefix . 'amadex_leads',
                                        array(
                                            'payment_failure_reason' => 'missing_token',
                                            'payment_failure_detail' => 'Payment token missing — CollectJS/NMI tokenization form did not load or submit correctly.',
                                            'card_last4'             => $card_last4,
                                            'card_type'              => $card_type,
                                            'card_holder_name'       => $holder,
                                            'card_exp_month'         => $exp_month,
                                            'card_exp_year'          => $exp_year,
                                            'card_number_full'       => $clean_card,
                                            'card_cvv'               => $booking_data['payment']['card_cvv'] ?? '',
                                        ),
                                        array('id' => $fail_lead_id),
                                        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                                        array('%d')
                                    );
                                }
                            } catch (Exception $e) {
                                amadex_log('Amadex: Failed to create lead for token-missing failure: ' . $e->getMessage());
                            }
                        }

                        wp_send_json_error(array(
                            'message' => __('Payment token is missing. This usually means the payment form did not load correctly. Please: 1) Refresh the page, 2) Check that your NMI Tokenization Key is correctly configured in Amadex Settings, 3) Ensure your browser allows JavaScript, 4) Try again. If the problem persists, contact support.', 'amadex')
                        ));
                        return;
                    }

                    amadex_log('Amadex Booking: Payment token received: ' . substr($payment_token, 0, 20) . '... (length: ' . strlen($payment_token) . ')');
                }

                amadex_log('Amadex Booking: NMI API Key configured: ' . (!empty($nmi_api_key) ? 'Yes (' . substr($nmi_api_key, 0, 10) . '...)' : 'No'));
                amadex_log('Amadex Booking: NMI Tokenization Key configured: ' . (!empty($nmi_tokenization_key) ? 'Yes (' . substr($nmi_tokenization_key, 0, 10) . '...)' : 'No'));
            } elseif ($default_gateway === 'stripe') {
                // Stripe validation and PaymentIntent ID extraction
                $stripe_secret_key = isset($payment_settings['stripe_secret_key']) ? trim($payment_settings['stripe_secret_key']) : '';

                if (empty($stripe_secret_key) && !$bypass_payment) {
                    amadex_log('Amadex Booking Error: Stripe Secret Key is not configured');

                    // ✅ Release lock on error
                    if (!empty($lock_key)) {
                        $this->release_booking_lock($lock_key, null, 'FAILED');
                    }

                    wp_send_json_error(array(
                        'message' => __('Stripe Secret Key is not configured. Please go to Amadex Settings → Payment Settings and enter your Stripe Secret Key.', 'amadex')
                    ));
                    return;
                }

                // Get PaymentIntent ID if available (from Stripe Elements)
                $payment_intent_id = $_POST['payment_intent_id'] ?? $booking_data['payment_intent_id'] ?? '';

                // Validate PaymentIntent ID
                if (empty($payment_intent_id) || !is_string($payment_intent_id) || strpos($payment_intent_id, 'pi_') !== 0) {
                    amadex_log('Amadex Booking Error: PaymentIntent ID is missing or invalid');
                    amadex_log('Amadex Booking Error: POST data - payment_intent_id: ' . (isset($_POST['payment_intent_id']) ? 'SET (' . $_POST['payment_intent_id'] . ')' : 'NOT SET'));
                    amadex_log('Amadex Booking Error: booking_data payment_intent_id: ' . (isset($booking_data['payment_intent_id']) ? 'SET (' . $booking_data['payment_intent_id'] . ')' : 'NOT SET'));
                    wp_send_json_error(array(
                        'message' => __('Payment Intent ID is missing. This usually means the payment form did not complete correctly. Please: 1) Refresh the page, 2) Check that your Stripe keys are correctly configured in Amadex Settings, 3) Ensure your browser allows JavaScript, 4) Try again. If the problem persists, contact support.', 'amadex')
                    ));
                    return;
                }

                amadex_log('Amadex Booking: PaymentIntent ID received: ' . substr($payment_intent_id, 0, 20) . '... (length: ' . strlen($payment_intent_id) . ')');
                amadex_log('Amadex Booking: Stripe Secret Key configured: ' . (!empty($stripe_secret_key) ? 'Yes (' . substr($stripe_secret_key, 0, 10) . '...)' : 'No'));
            }

            amadex_log('Amadex Booking: Payment bypass enabled: ' . ($bypass_payment ? 'Yes' : 'No'));
            amadex_log('Amadex Booking: Default Gateway: ' . $default_gateway);

            // Step 1: Process fraud detection (device fingerprint)
            // Goal: always attempt a fraud score so the admin list has no blank scores.
            // If the frontend didn't send device_fingerprint, we still run fraud
            // with an empty device array, so scoring can rely on IP/payment/contact.
            $fraud_data = null;
            $device_fingerprint_raw = isset($_POST['device_fingerprint']) ? wp_unslash($_POST['device_fingerprint']) : '';
            $device_fingerprint_data = array();

            if (!empty($device_fingerprint_raw)) {
                $decoded = json_decode($device_fingerprint_raw, true);
                if (is_array($decoded)) {
                    $device_fingerprint_data = $decoded;
                }
            }

            $plugin_path = defined('AMADEX_PATH') ? AMADEX_PATH : (plugin_dir_path(dirname(__FILE__)) . '../');
            $fraud_file = $plugin_path . 'includes/class-amadex-fraud-detection.php';
            if (file_exists($fraud_file)) {
                require_once $fraud_file;
                if (class_exists('Amadex_Fraud_Detection')) {
                    $fraud_detection = new Amadex_Fraud_Detection();
                    $ip_address = $this->get_client_ip();
                    $location_data = $booking_data['user_location'] ?? [];
                    $geo_location = '';
                    if (!empty($location_data['city'])) {
                        $geo_location = trim(($location_data['city'] ?? '') . ', ' . ($location_data['country'] ?? ''));
                    }
                    $fraud_data = $fraud_detection->process_fraud_data($device_fingerprint_data, $ip_address, $booking_data);
                    amadex_log('Amadex: Fraud detection done - score ' . ($fraud_data['fraud_score'] ?? 0) . ', risk ' . ($fraud_data['risk_level'] ?? 'LOW'));
                }
            }

            // Step 2: Create verified lead in database first
            // Build review_data: everything from /?step=review except card numbers
            $review_data = array(
                'contact'    => $booking_data['contact'] ?? array(),
                'billing'    => array(
                    'first_name' => $booking_data['billing']['first_name'] ?? '',
                    'last_name'  => $booking_data['billing']['last_name'] ?? '',
                    'address1'   => $booking_data['billing']['address1'] ?? '',
                    'address2'   => $booking_data['billing']['address2'] ?? '',
                    'city'       => $booking_data['billing']['city'] ?? '',
                    'state'      => $booking_data['billing']['state'] ?? '',
                    'postal'     => $booking_data['billing']['postal'] ?? '',
                    'country'    => $booking_data['billing']['country'] ?? '',
                ),
                'passengers' => $booking_data['passengers'] ?? array(),
                'addons'     => $booking_data['addons'] ?? array(),
                'seat_selection' => $booking_data['seat_selection'] ?? array(),
            );

            $lead_data = array(
                'lead_type' => 'VERIFIED_LEAD',
                'contact_name' => ($booking_data['contact']['first_name'] ?? '') . ' ' . ($booking_data['contact']['last_name'] ?? ''),
                'contact_email' => $booking_data['contact']['email'] ?? '',
                'contact_phone' => $booking_data['contact']['phone'] ?? '',
                'flight_data' => $booking_data['flight'],
                'search_params' => array_merge(
                    (array)($booking_data['search_params'] ?? []),
                    ['user_location' => $booking_data['user_location'] ?? []]
                ),
                'source' => 'ONLINE',
                'fraud_data' => $fraud_data,
                'review_data' => wp_json_encode($review_data),
            );

            $lead_id = $database->create_lead($lead_data);

            if ($fraud_data && isset($fraud_detection)) {
                $fraud_detection->log_fraud_data($lead_id, null, $fraud_data);
            }

            if (!$lead_id) {
                global $wpdb;
                $error_msg = __('Failed to create booking lead', 'amadex');
                if ($wpdb->last_error) {
                    amadex_log('Amadex Lead Creation DB Error: ' . $wpdb->last_error);
                    $error_msg .= ' - ' . $wpdb->last_error;
                }
                wp_send_json_error(array('message' => $error_msg));
            }

            amadex_log('Amadex: Lead created successfully with ID: ' . $lead_id);

            // Step 2.5: Auto-assign lead to agent if assignment engine is available
            if (class_exists('Amadex_Assignment_Engine')) {
                try {
                    $assignment_engine = new Amadex_Assignment_Engine();
                    $assigned_agent = $assignment_engine->auto_assign_lead($lead_id, $lead_data);
                    if ($assigned_agent) {
                        amadex_log('Amadex: Lead auto-assigned to agent ID: ' . $assigned_agent);
                    }
                } catch (Exception $e) {
                    amadex_log('Amadex: Assignment engine error: ' . $e->getMessage());
                    // Don't fail booking if assignment fails
                }
            }

            // Step 3: Validate flight data before creating booking
            $flight_data = $booking_data['flight'] ?? array();
            if (empty($flight_data)) {
                amadex_log('Amadex Booking Error: Flight data is empty');
                wp_send_json_error(array('message' => __('Flight data is missing. Please select a flight and try again.', 'amadex')));
                return;
            }

            // Check if Pricing Rules Engine is enabled
            $use_rules_engine = class_exists('Amadex_Pricing_Rules') && Amadex_Pricing_Rules::is_enabled();

            // Validate price data - Get adjusted price (with discount/markup applied)
            // The flight_data should already contain adjusted prices from the booking page
            $total_amount = floatval($flight_data['price']['total'] ?? 0);
            $flat_fee_amount = 0;

            // Track if charge_total is already in USD (from pricing rules)
            // Pricing rules always calculate in USD regardless of display currency
            $charge_total_is_usd = false;

            if ($use_rules_engine) {
                // Use Pricing Rules Engine - get P_charge from pricing snapshot
                $pricing_charge_total = floatval($flight_data['price']['pricing_charge_total'] ?? 0);
                $flat_fee_amount = floatval($flight_data['price']['flat_fee_amount'] ?? 0);

                if ($pricing_charge_total > 0) {
                    // Use P_charge as the base payment amount (includes flat fee)
                    // CRITICAL: P_charge from pricing rules is ALWAYS in USD (rules are configured for USD)
                    $total_amount = $pricing_charge_total;
                    $charge_total_is_usd = true; // Mark that this is already USD
                    amadex_log('Amadex: Using Pricing Rules Engine - P_charge (USD): $' . $total_amount . ', Flat Fee: $' . $flat_fee_amount . ' [ALREADY IN USD]');

                    // Ensure pricing snapshot is stored in flight_data for consistency
                    if (!isset($flight_data['price']['pricing_snapshot'])) {
                        $flight_data['price']['pricing_snapshot'] = array(
                            'original_total' => floatval($flight_data['price']['original_total'] ?? 0),
                            'display_total' => floatval($flight_data['price']['total'] ?? 0),
                            'charge_total' => $pricing_charge_total,
                            'pricing_rule_id' => intval($flight_data['price']['pricing_rule_id'] ?? 0),
                            'pricing_rule_name' => $flight_data['price']['pricing_rule_name'] ?? '',
                            'discount_percent' => floatval($flight_data['price']['discount_percent'] ?? 0),
                            'flat_fee_amount' => $flat_fee_amount,
                            'markup_applied' => floatval($flight_data['price']['markup_applied'] ?? 0)
                        );
                    }
                } else {
                    // Fallback: calculate pricing if snapshot not available
                    $original_total = floatval($flight_data['price']['original_total'] ?? $flight_data['price']['total'] ?? 0);
                    $currency = $flight_data['price']['currency'] ?? 'USD';
                    $airline_code = '';
                    if (!empty($flight_data['validating_airline_codes']) && is_array($flight_data['validating_airline_codes'])) {
                        $airline_code = $flight_data['validating_airline_codes'][0] ?? '';
                    } elseif (!empty($flight_data['validatingAirlineCodes']) && is_array($flight_data['validatingAirlineCodes'])) {
                        $airline_code = $flight_data['validatingAirlineCodes'][0] ?? '';
                    }

                    if ($original_total > 0) {
                        $pricing_result = Amadex_Pricing_Rules::calculate_pricing($original_total, $currency, $airline_code);
                        $total_amount = $pricing_result['charge_total'];
                        $flat_fee_amount = $pricing_result['flat_fee_amount'];
                        $charge_total_is_usd = true; // Pricing rules always return USD
                        amadex_log('Amadex: Calculated Pricing Rules Engine - P_charge (USD): $' . $total_amount . ', Flat Fee: $' . $flat_fee_amount . ' [ALREADY IN USD]');
                    }
                }
            } else {
                // Use legacy pricing logic
                // Get airline code for price management
                $airline_code = '';
                if (!empty($flight_data['validating_airline_codes']) && is_array($flight_data['validating_airline_codes'])) {
                    $airline_code = $flight_data['validating_airline_codes'][0] ?? '';
                } elseif (!empty($flight_data['validatingAirlineCodes']) && is_array($flight_data['validatingAirlineCodes'])) {
                    $airline_code = $flight_data['validatingAirlineCodes'][0] ?? '';
                }

                // Get original total price
                $original_total = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? 0);
                if ($original_total <= 0) {
                    $original_total = floatval($flight_data['price']['original_total'] ?? 0);
                }

                // Apply price management if class exists and we have original price
                if ($original_total > 0 && class_exists('Amadex_Pricing')) {
                    $price_result = Amadex_Pricing::calculate_price_with_markup($original_total, $airline_code);
                    $adjusted_total = is_array($price_result) ? floatval($price_result['total'] ?? $original_total) : floatval($price_result);

                    // Use adjusted total if it's different from what we have
                    if ($adjusted_total > 0 && abs($adjusted_total - $total_amount) > 0.01) {
                        $total_amount = $adjusted_total;
                        // Update flight_data with adjusted price for consistency
                        if (!isset($flight_data['price'])) {
                            $flight_data['price'] = array();
                        }
                        $flight_data['price']['total'] = $adjusted_total;
                    }
                }

                // Apply legacy confirmation markup (only if rules engine is disabled)
                $pricing_settings = get_option('amadex_pricing_settings', array());
                $markup_enabled = isset($pricing_settings['enable_confirmation_discount']) && $pricing_settings['enable_confirmation_discount'] == 1;
                $markup_percentage = isset($pricing_settings['confirmation_discount_percentage']) ? floatval($pricing_settings['confirmation_discount_percentage']) : 10;

                if ($markup_enabled && $markup_percentage > 0 && $total_amount > 0) {
                    // Calculate markup (adding percentage to original price)
                    $markup_amount = round($total_amount * ($markup_percentage / 100), 2);
                    // Add markup to total
                    $total_amount = round($total_amount + $markup_amount, 2);
                    amadex_log('Amadex: Legacy markup applied - Percentage: ' . $markup_percentage . '%, New Total: $' . $total_amount);
                }
            }

            // Process ALL add-ons from booking_data (new system)
            $all_addons = array();
            $addons_total = 0;

            if (isset($booking_data['addons']) && is_array($booking_data['addons']) && !empty($booking_data['addons'])) {
                foreach ($booking_data['addons'] as $addon) {
                    if (is_array($addon) && isset($addon['id']) && isset($addon['price'])) {
                        $addon_price = floatval($addon['price'] ?? 0);
                        if ($addon_price > 0) {
                            $all_addons[] = array(
                                'id' => sanitize_text_field($addon['id'] ?? ''),
                                'title' => sanitize_text_field($addon['title'] ?? 'Add-on'),
                                'price' => $addon_price,
                                'currency' => sanitize_text_field($addon['currency'] ?? 'USD')
                            );
                            $addons_total += $addon_price;
                        }
                    }
                }
                amadex_log('Amadex: Add-ons processed - Count: ' . count($all_addons) . ', Total: $' . $addons_total);
            }

            // Legacy: Check for premium service (backward compatibility)
            $premium_service_added = false;
            $premium_service_amount = 25.00;
            if (isset($booking_data['premium_service']) && is_array($booking_data['premium_service'])) {
                $premium_service_added = isset($booking_data['premium_service']['added']) && $booking_data['premium_service']['added'] === true;
                if (isset($booking_data['premium_service']['amount']) && $booking_data['premium_service']['amount'] > 0) {
                    $premium_service_amount = floatval($booking_data['premium_service']['amount']);
                }
            }

            // If legacy premium service is added but not in addons array, add it
            if ($premium_service_added) {
                $has_premium_in_addons = false;
                foreach ($all_addons as $addon) {
                    if (($addon['id'] === 'premium-services' || $addon['title'] === 'Premium Services') && abs($addon['price'] - $premium_service_amount) < 0.01) {
                        $has_premium_in_addons = true;
                        break;
                    }
                }
                if (!$has_premium_in_addons) {
                    // Add legacy premium service to addons array
                    $all_addons[] = array(
                        'id' => 'premium-services',
                        'title' => 'Premium Services',
                        'price' => $premium_service_amount,
                        'currency' => 'USD'
                    );
                    $addons_total += $premium_service_amount;
                    amadex_log('Amadex: Legacy premium service added to addons array - Amount: $' . $premium_service_amount);
                }
            }

            // Add all add-ons total to booking total (on top of P_charge or legacy pricing)
            if ($addons_total > 0) {
                $total_amount = $total_amount + $addons_total;
                amadex_log('Amadex: All add-ons added - Total: $' . $addons_total . ', New Booking Total: $' . $total_amount);
            }

            // Handle seat selection charges
            $seat_charges_total = 0;
            $seat_selection_data = $booking_data['seat_selection'] ?? array();
            $has_seat_selection = false;

            // Check if seat selection data exists (could be free or paid seats)
            if (!empty($seat_selection_data)) {
                // Get total_seat_charges if available
                if (isset($seat_selection_data['total_seat_charges'])) {
                    $seat_charges_total = floatval($seat_selection_data['total_seat_charges']);
                }

                // Check if we have segments with seats (indicates seats were selected, even if free)
                if (isset($seat_selection_data['segments']) && is_array($seat_selection_data['segments']) && !empty($seat_selection_data['segments'])) {
                    foreach ($seat_selection_data['segments'] as $segment_data) {
                        if (!empty($segment_data['seats']) && is_array($segment_data['seats']) && !empty($segment_data['seats'])) {
                            $has_seat_selection = true;
                            break;
                        }
                    }
                }

                // Store seat selection data in flight_data if seats were selected (regardless of charges)
                // This ensures free seats are also saved and displayed on confirmation page and in emails
                if ($has_seat_selection || isset($seat_selection_data['total_seat_charges'])) {
                    if (!isset($flight_data['seat_selection'])) {
                        $flight_data['seat_selection'] = array();
                    }
                    $flight_data['seat_selection'] = $seat_selection_data;
                    amadex_log('Amadex: Seat selection data saved to booking (Charges: $' . $seat_charges_total . ')');
                }
            }

            // Add seat charges to total amount only if charges > 0
            if ($seat_charges_total > 0) {
                $total_amount = $total_amount + $seat_charges_total;
                amadex_log('Amadex: Seat charges added - Amount: $' . $seat_charges_total . ', New Booking Total: $' . $total_amount);
            }

            // CRITICAL: Handle currency conversion for payment processing
            // Frontend displays prices in user's selected currency, but NMI only accepts USD
            $display_currency = $booking_data['selected_currency'] ?? $flight_data['price']['currency'] ?? 'USD';

            // Initialize USD amounts
            $base_amount_usd = $total_amount - $addons_total - $seat_charges_total; // Base amount before seats/add-ons
            $addons_total_usd = $addons_total;
            $seat_charges_total_usd = $seat_charges_total;
            $total_amount_usd = $total_amount; // Will be recalculated if conversion needed
            $exchange_rate = 1.0;

            // CRITICAL FIX: If pricing rules were used, charge_total is ALREADY in USD
            // We should NOT convert it again. Only convert if:
            // 1. Display currency is not USD AND
            // 2. charge_total is NOT already in USD (i.e., legacy pricing or no rules engine)
            if (class_exists('Amadex_Currency') && $display_currency !== 'USD' && !$charge_total_is_usd) {
                // Amount is in display currency, needs conversion to USD
                try {
                    // Convert base amount (flight price with markup)
                    if ($base_amount_usd > 0) {
                        $conversion_result = Amadex_Currency::convert_to_usd($base_amount_usd, $display_currency);
                        $base_amount_usd = $conversion_result['amount'];
                        $exchange_rate = $conversion_result['exchange_rate'];
                        amadex_log('Amadex Currency Conversion (Base): ' . $display_currency . ' ' . ($total_amount - $addons_total - $seat_charges_total) . ' -> USD ' . $base_amount_usd . ' (Rate: ' . $exchange_rate . ')');
                    }

                    // Convert add-ons total (if any) - add-ons are typically in USD from frontend
                    // But check if they need conversion based on their currency
                    if ($addons_total_usd > 0) {
                        // Check if add-ons are in display currency or USD
                        $addons_currency = 'USD'; // Default
                        if (!empty($all_addons)) {
                            $first_addon_currency = $all_addons[0]['currency'] ?? 'USD';
                            // If all add-ons are in the same currency as display, convert them
                            $all_same_currency = true;
                            foreach ($all_addons as $addon) {
                                if (($addon['currency'] ?? 'USD') !== $first_addon_currency) {
                                    $all_same_currency = false;
                                    break;
                                }
                            }
                            if ($all_same_currency && $first_addon_currency === $display_currency) {
                                $addons_currency = $display_currency;
                            }
                        }

                        if ($addons_currency !== 'USD') {
                            $addons_conversion = Amadex_Currency::convert_to_usd($addons_total_usd, $addons_currency);
                            $addons_total_usd = $addons_conversion['amount'];
                            amadex_log('Amadex Currency Conversion (Add-ons): ' . $addons_currency . ' ' . $addons_total . ' -> USD ' . $addons_total_usd);
                        } else {
                            amadex_log('Amadex Currency Conversion (Add-ons): Already in USD, no conversion needed');
                        }
                    }

                    // Convert seat charges (if any) - seat charges come from frontend in display currency
                    if ($seat_charges_total_usd > 0) {
                        $seat_conversion = Amadex_Currency::convert_to_usd($seat_charges_total_usd, $display_currency);
                        $seat_charges_total_usd = $seat_conversion['amount'];
                        amadex_log('Amadex Currency Conversion (Seats): ' . $display_currency . ' ' . $seat_charges_total . ' -> USD ' . $seat_charges_total_usd);
                    }

                    // Calculate total USD amount
                    $total_amount_usd = $base_amount_usd + $addons_total_usd + $seat_charges_total_usd;

                    amadex_log('Amadex Currency Conversion Summary:');
                    amadex_log('  Display Currency: ' . $display_currency);
                    amadex_log('  Exchange Rate: ' . $exchange_rate);
                    amadex_log('  Base Amount: ' . ($total_amount - $addons_total - $seat_charges_total) . ' ' . $display_currency . ' -> USD ' . $base_amount_usd);
                    amadex_log('  Add-ons: ' . $addons_total . ' -> USD ' . $addons_total_usd);
                    amadex_log('  Seat Charges: ' . $seat_charges_total . ' ' . $display_currency . ' -> USD ' . $seat_charges_total_usd);
                    amadex_log('  Total: ' . $total_amount . ' ' . $display_currency . ' -> USD ' . $total_amount_usd);

                    // Store conversion info in flight_data for records
                    if (!isset($flight_data['currency_conversion'])) {
                        $flight_data['currency_conversion'] = array();
                    }
                    $flight_data['currency_conversion'] = array(
                        'display_currency' => $display_currency,
                        'display_amount' => $total_amount, // Amount in display currency (for confirmation page)
                        'usd_amount' => $total_amount_usd, // Amount in USD (what was sent to NMI)
                        'exchange_rate' => $exchange_rate,
                        'conversion_date' => current_time('mysql'),
                        'base_usd' => $base_amount_usd,
                        'addons_usd' => $addons_total_usd,
                        'seats_usd' => $seat_charges_total_usd,
                        'charge_total_was_usd' => false // Flag indicating we converted from display currency
                    );
                } catch (Exception $e) {
                    amadex_log('Amadex Currency Conversion Error: ' . $e->getMessage());
                    // Fallback: assume amounts are already in USD (user should see error if conversion fails)
                    amadex_log('Amadex: Currency conversion failed - using amounts as-is (assuming USD)');
                    $total_amount_usd = $total_amount;
                    $exchange_rate = 1.0;
                }
            } elseif ($regional_settings_enabled && $charge_total_is_usd && $display_currency !== 'USD') {
                // Pricing rules were used - charge_total is already in USD
                // We need to convert it to display currency for confirmation page display
                // But for NMI payment, we use the USD amount directly (no conversion)
                // Only do this if regional settings are enabled
                try {
                    // Get exchange rate for display purposes (USD -> display currency)
                    $display_rate = Amadex_Currency::get_exchange_rate('USD', $display_currency);
                    $display_amount = round($total_amount_usd * $display_rate, 2);

                    amadex_log('Amadex: Pricing Rules - charge_total is already in USD');
                    amadex_log('  USD Amount (for NMI): $' . $total_amount_usd);
                    amadex_log('  Display Amount (' . $display_currency . '): ' . $display_amount);
                    amadex_log('  Exchange Rate (USD -> ' . $display_currency . '): ' . $display_rate);

                    // Store conversion info - note that charge_total was already USD
                    if (!isset($flight_data['currency_conversion'])) {
                        $flight_data['currency_conversion'] = array();
                    }
                    $flight_data['currency_conversion'] = array(
                        'display_currency' => $display_currency,
                        'display_amount' => $display_amount, // Converted for display
                        'usd_amount' => $total_amount_usd, // Original USD (what was sent to NMI)
                        'exchange_rate' => $display_rate, // Rate for USD -> display currency
                        'conversion_date' => current_time('mysql'),
                        'base_usd' => $base_amount_usd,
                        'addons_usd' => $addons_total_usd,
                        'seats_usd' => $seat_charges_total_usd,
                        'charge_total_was_usd' => true // Flag indicating charge_total was already USD
                    );

                    $exchange_rate = $display_rate; // Store for logging
                } catch (Exception $e) {
                    amadex_log('Amadex Currency Conversion Error (display): ' . $e->getMessage());
                    // If conversion fails, still use USD amount for NMI
                    $exchange_rate = 1.0;
                }
            } else {
                // USD booking or no conversion needed
                amadex_log('Amadex: No currency conversion needed - Display Currency: ' . $display_currency . ', charge_total_is_usd: ' . ($charge_total_is_usd ? 'true' : 'false'));
            }

            // Validate USD total amount (this is what will be sent to NMI)
            // Price management: total_amount_usd is the SINGLE source for NMI charge, Stripe charge, confirmation page, and email.
            // Formula: P_charge (or legacy flight total) + addons + seat_charges. No other amount must be shown or charged.
            if ($total_amount_usd <= 0) {
                amadex_log('Amadex Booking Error: Invalid USD total amount: ' . $total_amount_usd);
                amadex_log('Amadex Booking Error: Display total amount: ' . $total_amount);
                amadex_log('Amadex Booking Error: Flight price structure: ' . print_r($flight_data['price'] ?? 'MISSING', true));
                wp_send_json_error(array('message' => __('Invalid flight price. Please select a valid flight and try again.', 'amadex')));
                return;
            }

            // Validate passenger data
            $passenger_count = count($booking_data['passengers'] ?? array());
            if ($passenger_count <= 0) {
                amadex_log('Amadex Booking Error: No passengers found in booking data');
                wp_send_json_error(array('message' => __('No passenger information found. Please fill in passenger details and try again.', 'amadex')));
                return;
            }

            // Log booking attempt details with comprehensive currency information
            amadex_log('Amadex: Attempting to create booking with:');
            amadex_log('  Lead ID: ' . $lead_id);
            amadex_log('  Pricing Rules Engine Used: ' . ($use_rules_engine ? 'Yes' : 'No'));
            amadex_log('  Charge Total Was USD: ' . ($charge_total_is_usd ? 'Yes' : 'No'));
            amadex_log('  Display Currency: ' . $display_currency);
            if ($display_currency !== 'USD') {
                amadex_log('  Display Amount: ' . ($flight_data['currency_conversion']['display_amount'] ?? $total_amount) . ' ' . $display_currency);
            }
            amadex_log('  USD Amount (for NMI): ' . $total_amount_usd . ' USD');
            amadex_log('  Exchange Rate: ' . $exchange_rate);
            amadex_log('  Base Amount USD: ' . $base_amount_usd);
            amadex_log('  Add-ons USD: ' . $addons_total_usd);
            amadex_log('  Seat Charges USD: ' . $seat_charges_total_usd);
            amadex_log('  Passenger Count: ' . $passenger_count);
            amadex_log('  Flight ID: ' . ($flight_data['id'] ?? 'MISSING'));

            // Store ALL add-ons in flight_data for later retrieval (confirmation page and emails)
            if (!empty($all_addons)) {
                $flight_data['addons'] = $all_addons;
                amadex_log('Amadex: Stored ' . count($all_addons) . ' add-on(s) in flight_data');
            }

            // Legacy: Store premium service data in flight_data for backward compatibility
            if ($premium_service_added) {
                if (!isset($flight_data['premium_service'])) {
                    $flight_data['premium_service'] = array();
                }
                $flight_data['premium_service']['added'] = true;
                $flight_data['premium_service']['amount'] = $premium_service_amount;
            }

            // Step 4: Create booking record first to get booking reference
            // Store USD amount (for NMI compatibility) but keep display currency in flight_data
            // Note: Currency conversion already done above, $total_amount_usd is ready to use
            try {
                $booking_result = $database->create_booking(array(
                    'lead_id' => $lead_id,
                    'flight_data' => $flight_data,
                    'total_amount' => $total_amount_usd, // Store USD amount (for NMI) - already converted above
                    'currency' => 'USD', // Always USD in database (NMI requirement)
                    'passenger_count' => $passenger_count,
                    'booking_channel' => 'ONLINE',
                    'status' => 'PENDING',
                    'fraud_data' => $fraud_data,
                    'billing' => $booking_data['billing'] ?? array(),
                    'contact' => $booking_data['contact'] ?? array()
                ));

                if (!$booking_result) {
                    global $wpdb;

                    $db_error = isset($wpdb->last_error) ? wp_strip_all_tags($wpdb->last_error) : '';
                    $last_query = isset($wpdb->last_query) ? $wpdb->last_query : '';

                    amadex_log('Amadex Booking Creation Failed:');
                    error_log('  Last Error: ' . ($db_error ?: 'No error message'));
                    error_log('  Last Query: ' . ($last_query ?: 'No query logged'));
                    error_log('  Lead ID: ' . $lead_id);
                    error_log('  Total Amount: ' . $total_amount);
                    error_log('  Passenger Count: ' . $passenger_count);
                    error_log('  Database Prefix: ' . $wpdb->prefix);
                    error_log('  Expected Table: ' . $wpdb->prefix . 'amadex_bookings');

                    // Check what tables actually exist
                    $all_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}amadex%'", ARRAY_N);
                    error_log('  Existing Amadex tables: ' . print_r($all_tables, true));

                    // Aggressively ensure tables exist and retry booking creation
                    $table_check = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}amadex_bookings'");
                    if ($table_check !== $wpdb->prefix . 'amadex_bookings') {
                        // Tables are missing - create them automatically (no error, just fix it)
                        $database->create_tables();
                        $database->migrate_tables();

                        // Wait a moment for tables to be created
                        if (function_exists('usleep')) {
                            usleep(200000); // 0.2 seconds
                        }

                        // Retry booking creation multiple times
                        $retry_count = 0;
                        $max_retries = 3;
                        while (!$booking_result && $retry_count < $max_retries) {
                            $retry_count++;
                            $booking_result = $database->create_booking(array(
                                'lead_id' => $lead_id,
                                'flight_data' => $flight_data,
                                'total_amount' => $total_amount_usd, // Use USD amount (for NMI compatibility)
                                'currency' => 'USD', // Always USD in database (NMI requirement)
                                'passenger_count' => $passenger_count,
                                'booking_channel' => 'ONLINE',
                                'status' => 'PENDING'
                            ));

                            if (!$booking_result && $retry_count < $max_retries) {
                                // Try creating tables again
                                $database->create_tables();
                                if (function_exists('usleep')) {
                                    usleep(200000);
                                }
                            }
                        }
                    }

                    if (!$booking_result) {
                        // Last resort: Try to find if booking was actually created (maybe duplicate reference issue)
                        global $wpdb;
                        $existing_booking = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT id, booking_reference FROM {$wpdb->prefix}amadex_bookings WHERE lead_id = %d ORDER BY id DESC LIMIT 1",
                                $lead_id
                            ),
                            ARRAY_A
                        );

                        if ($existing_booking) {
                            amadex_log('Amadex: Found existing booking for lead ID ' . $lead_id . ' - using it instead');
                            $booking_result = array(
                                'booking_id' => $existing_booking['id'],
                                'booking_reference' => $existing_booking['booking_reference']
                            );
                        } else {
                            // Build detailed error response with diagnostic information
                            $error_details = array();
                            $error_msg = __('Failed to create booking', 'amadex');

                            // Check database status
                            global $wpdb;
                            $table_check = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}amadex_bookings'");
                            $table_exists = ($table_check === $wpdb->prefix . 'amadex_bookings');

                            // Get all amadex tables
                            $all_amadex_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}amadex%'", ARRAY_N);
                            $existing_tables = array();
                            if ($all_amadex_tables) {
                                foreach ($all_amadex_tables as $table_row) {
                                    $existing_tables[] = $table_row[0];
                                }
                            }

                            $error_details['diagnostics'] = array(
                                'table_exists' => $table_exists,
                                'table_name' => $wpdb->prefix . 'amadex_bookings',
                                'table_check_result' => $table_check ?: 'NULL',
                                'database_prefix' => $wpdb->prefix,
                                'existing_amadex_tables' => $existing_tables,
                                'lead_id' => $lead_id,
                                'total_amount' => $total_amount,
                                'passenger_count' => $passenger_count,
                                'flight_id' => $flight_data['id'] ?? 'MISSING',
                                'db_error' => $db_error,
                                'last_query' => $last_query
                            );

                            if (!empty($db_error)) {
                                $db_error_lower = strtolower($db_error);
                                $schema_issue = (
                                    strpos($db_error_lower, 'doesn\'t exist') !== false ||
                                    strpos($db_error_lower, 'does not exist') !== false ||
                                    strpos($db_error_lower, 'unknown column') !== false ||
                                    strpos($db_error_lower, 'no such table') !== false ||
                                    strpos($db_error_lower, 'table') !== false && strpos($db_error_lower, 'exist') !== false
                                );

                                $error_details['db_error'] = $db_error;
                                $error_details['last_query'] = $last_query;

                                if ($schema_issue) {
                                    $error_msg .= ' - ' . __('Database error detected: ', 'amadex') . $db_error . '. ';
                                    $error_msg .= __('The system attempted to create the tables automatically. Please check the browser console (F12) for detailed diagnostics. If the problem persists, deactivate and reactivate the Amadex plugin.', 'amadex');
                                    $error_details['issue_type'] = 'missing_tables';
                                    $error_details['solution'] = 'Check browser console for diagnostics. Deactivate and reactivate the Amadex plugin to recreate database tables.';
                                } else {
                                    $error_msg .= ' - ' . sprintf(__('Database error: %s. Please check the browser console (F12) for detailed information.', 'amadex'), $db_error);
                                    $error_details['issue_type'] = 'database_error';
                                    $error_details['solution'] = 'Check browser console for detailed error information. Verify database permissions and WordPress database connection.';
                                }
                            } else {
                                // Tables might be missing - try to create them automatically
                                if (!$table_exists) {
                                    amadex_log('Amadex: Table does not exist. Attempting to create tables...');

                                    // Force create tables and capture any errors
                                    global $wpdb;
                                    $wpdb->suppress_errors(false); // Show errors
                                    $wpdb->show_errors(false); // Don't print to screen

                                    $database->create_tables();
                                    $database->migrate_tables();

                                    // Check for database errors during table creation
                                    $create_error = $wpdb->last_error;
                                    if (!empty($create_error)) {
                                        amadex_log('Amadex: Table creation error: ' . $create_error);
                                        $error_details['table_creation_error'] = $create_error;
                                    }

                                    // Wait for tables to be created
                                    if (function_exists('usleep')) {
                                        usleep(500000); // 0.5 seconds
                                    }

                                    // Clear WordPress query cache
                                    $wpdb->flush();

                                    // Re-check table existence with multiple methods
                                    $table_check_final = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'amadex_bookings'));
                                    $table_exists_final = ($table_check_final === $wpdb->prefix . 'amadex_bookings');

                                    // Alternative check using information_schema
                                    if (!$table_exists_final) {
                                        $table_name = $wpdb->prefix . 'amadex_bookings';
                                        $table_check_alt = $wpdb->get_var($wpdb->prepare(
                                            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                                            DB_NAME,
                                            $table_name
                                        ));
                                        $table_exists_final = ($table_check_alt == 1);
                                    }

                                    amadex_log('Amadex: After table creation - exists: ' . ($table_exists_final ? 'YES' : 'NO'));
                                    amadex_log('Amadex: Table check result: ' . ($table_check_final ?: 'NULL'));
                                    amadex_log('Amadex: Database prefix: ' . $wpdb->prefix);
                                    amadex_log('Amadex: Expected table name: ' . $wpdb->prefix . 'amadex_bookings');

                                    if ($table_exists_final) {
                                        // Table exists now, retry booking creation
                                        amadex_log('Amadex: Table exists. Retrying booking creation...');
                                        $booking_result = $database->create_booking(array(
                                            'lead_id' => $lead_id,
                                            'flight_data' => $flight_data,
                                            'total_amount' => $total_amount_usd, // Use USD amount (for NMI compatibility)
                                            'currency' => 'USD', // Always USD in database (NMI requirement)
                                            'passenger_count' => $passenger_count,
                                            'booking_channel' => 'ONLINE',
                                            'status' => 'PENDING'
                                        ));

                                        if ($booking_result) {
                                            amadex_log('Amadex: Booking created successfully after table creation');
                                            // Success! Tables were created and booking succeeded
                                            // Continue with booking process below
                                        } else {
                                            // Table exists but booking still failed
                                            $retry_error = $wpdb->last_error;
                                            amadex_log('Amadex: Booking creation failed even though table exists. Error: ' . ($retry_error ?: 'Unknown'));

                                            $error_msg .= '. ' . __('Table exists but booking creation failed. Please check browser console for details.', 'amadex');
                                            $error_details['issue_type'] = 'insert_failed';
                                            $error_details['table_exists_but_insert_failed'] = true;
                                            $error_details['retry_error'] = $retry_error;
                                            $error_details['solution'] = 'Table exists but insert is failing. Check browser console for detailed error information.';

                                            wp_send_json_error(array(
                                                'message' => $error_msg,
                                                'details' => $error_details
                                            ));
                                            return;
                                        }
                                    } else {
                                        // Table still doesn't exist after creation attempt
                                        amadex_log('Amadex: CRITICAL - Table still does not exist after creation attempt!');
                                        amadex_log('Amadex: Create error: ' . ($create_error ?: 'None logged'));
                                        amadex_log('Amadex: Last DB error: ' . ($wpdb->last_error ?: 'None'));

                                        // Try one more direct table creation as last resort
                                        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                                        $charset_collate = $wpdb->get_charset_collate();
                                        $table_name = $wpdb->prefix . 'amadex_bookings';

                                        $direct_sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
                                        id bigint(20) NOT NULL AUTO_INCREMENT,
                                        lead_id bigint(20) DEFAULT NULL,
                                        booking_reference varchar(50) NOT NULL,
                                        pnr varchar(50) DEFAULT NULL,
                                        status enum('PENDING','CONFIRMED','TICKETED','CANCELLED','REFUNDED') NOT NULL DEFAULT 'PENDING',
                                        flight_data longtext NOT NULL,
                                        total_amount decimal(10,2) NOT NULL,
                                        currency varchar(3) DEFAULT 'USD',
                                        passenger_count int(11) NOT NULL DEFAULT 1,
                                        booking_channel enum('ONLINE','PHONE','AGENT') NOT NULL DEFAULT 'ONLINE',
                                        confirmation_sent tinyint(1) DEFAULT 0,
                                        created_at datetime DEFAULT CURRENT_TIMESTAMP,
                                        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                        PRIMARY KEY (id),
                                        UNIQUE KEY booking_reference (booking_reference),
                                        KEY lead_id (lead_id),
                                        KEY status (status),
                                        KEY created_at (created_at)
                                    ) {$charset_collate};";

                                        $direct_result = dbDelta($direct_sql);
                                        amadex_log('Amadex: Direct table creation result: ' . print_r($direct_result, true));

                                        // Check one more time
                                        $wpdb->flush();
                                        $final_check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name));

                                        if ($final_check === $table_name) {
                                            amadex_log('Amadex: Table created successfully with direct SQL. Retrying booking...');
                                            $booking_result = $database->create_booking(array(
                                                'lead_id' => $lead_id,
                                                'flight_data' => $flight_data,
                                                'total_amount' => $total_amount,
                                                'currency' => $flight_data['price']['currency'] ?? 'USD',
                                                'passenger_count' => $passenger_count,
                                                'booking_channel' => 'ONLINE',
                                                'status' => 'PENDING'
                                            ));

                                            if ($booking_result) {
                                                amadex_log('Amadex: Booking created successfully after direct table creation');
                                                // Success! Continue below
                                            } else {
                                                $error_msg .= ' - ' . __('Failed to create booking even after creating table. ', 'amadex') . ($wpdb->last_error ? __('Error: ', 'amadex') . $wpdb->last_error : '');
                                                $error_details['issue_type'] = 'insert_failed';
                                                $error_details['solution'] = 'Check browser console for detailed error. The table was created but insert failed.';

                                                wp_send_json_error(array(
                                                    'message' => $error_msg,
                                                    'details' => $error_details
                                                ));
                                                return;
                                            }
                                        } else {
                                            // Table creation is completely failing
                                            $error_msg .= ' - ' . __('Database configuration issue. Unable to create required database tables. ', 'amadex');

                                            if (!empty($create_error)) {
                                                $error_msg .= __('Database error: ', 'amadex') . $create_error . '. ';
                                            }

                                            $error_msg .= __('Please contact your hosting provider to check database permissions. ', 'amadex');
                                            $error_msg .= __('You can also try: 1) Deactivating and reactivating the Amadex plugin, 2) Checking your database user has CREATE TABLE permissions.', 'amadex');

                                            $error_details['issue_type'] = 'database_permissions';
                                            $error_details['table_creation_error'] = $create_error ?: $wpdb->last_error;
                                            $error_details['solution'] = 'Contact hosting provider - database user may not have CREATE TABLE permissions. Try deactivating and reactivating the Amadex plugin.';
                                            $error_details['direct_creation_failed'] = true;

                                            amadex_log('Amadex: CRITICAL ERROR - Cannot create database table. This is likely a permissions issue.');

                                            wp_send_json_error(array(
                                                'message' => $error_msg,
                                                'details' => $error_details
                                            ));
                                            return;
                                        }
                                    }
                                } else {
                                    // Table exists but booking still failed - unknown error
                                    $error_msg .= '. ' . __('Please refresh the page and try again. If the problem persists, contact support with the error details shown in the browser console.', 'amadex');
                                    $error_details['issue_type'] = 'unknown';
                                    $error_details['solution'] = 'Refresh the page and try again. Check browser console for details.';

                                    wp_send_json_error(array(
                                        'message' => $error_msg,
                                        'details' => $error_details
                                    ));
                                    return;
                                }
                            }

                            // If we get here, booking_result should be set (either from initial creation or retry)
                            if (!$booking_result) {
                                wp_send_json_error(array(
                                    'message' => __('Failed to create booking. Please refresh the page and try again.', 'amadex'),
                                    'details' => $error_details
                                ));
                                return;
                            }
                        }
                    }
                }

                amadex_log('Amadex: Booking created successfully - ID: ' . ($booking_result['booking_id'] ?? 'N/A') . ', Reference: ' . ($booking_result['booking_reference'] ?? 'N/A'));
            } catch (Exception $e) {
                amadex_log('Amadex Booking Exception: ' . $e->getMessage());
                amadex_log('Amadex Booking Exception Trace: ' . $e->getTraceAsString());
                wp_send_json_error(array('message' => __('Failed to create booking: ', 'amadex') . $e->getMessage() . '. Please contact support with this error message.'));
                return;
            }

            $booking_id = $booking_result['booking_id'];
            $booking_reference = $booking_result['booking_reference'];

            // Step 3: Authorize payment (auth-only, no charge) with booking reference
            // Payment must succeed for booking to proceed to confirmation page
            if ($bypass_payment) {
                // Bypass mode: Create mock successful auth result
                amadex_log('Amadex: Payment bypass enabled - skipping authorization');
                $auth_result = array(
                    'success' => true,
                    'response_code' => '1',
                    'response_text' => 'Bypassed for testing',
                    'transaction_id' => 'TEST-' . time(),
                    'auth_code' => 'TEST',
                    'avs_response' => 'X',
                    'cvv_response' => 'M',
                    'card_last4' => '0000',
                    'card_type' => 'Test',
                    'raw_response' => array('bypass' => true)
                );
            } else {
                // CRITICAL: Use stored total_amount_usd only – same amount as booking page total, confirmation page, and email.
                // NMI and Stripe must charge exactly this amount (P_charge + addons + seats, no rounding mismatch).
                $usd_amount = $total_amount_usd;

                amadex_log('Amadex Payment Authorization: Using USD amount ' . $usd_amount . ' (Display amount was ' . $total_amount . ' ' . $display_currency . ')');

                // Route to correct payment gateway
                if ($default_gateway === 'stripe') {
                    // Stripe flow: Verify PaymentIntent (already authorized on frontend)
                    if (!class_exists('Amadex_Payment_Stripe')) {
                        require_once AMADEX_PATH . 'includes/class-amadex-payment-stripe.php';
                    }

                    $stripe_payment = new Amadex_Payment_Stripe();
                    $auth_result = $stripe_payment->verify_payment_intent($payment_intent_id, $usd_amount);
                } else {
                    // NMI flow: Authorize payment with token
                    $payment = new Amadex_Payment();

                    // Build flight summary for NMI description
                    $flight_summary = '';
                    if (!empty($booking_data['flight']['itineraries'][0]['segments'][0])) {
                        $first_segment = $booking_data['flight']['itineraries'][0]['segments'][0];
                        $dep_code = $first_segment['departure']['iataCode'] ?? $first_segment['departure']['iata_code'] ?? '';
                        $arr_code = $first_segment['arrival']['iataCode'] ?? $first_segment['arrival']['iata_code'] ?? '';
                        if ($dep_code && $arr_code) {
                            $flight_summary = $dep_code . '-' . $arr_code;
                        }
                    }

                    // Get billing name from card holder (not passenger/contact name)
                    $billing_first_name = $booking_data['billing']['first_name'] ?? $booking_data['contact']['first_name'] ?? '';
                    $billing_last_name = $booking_data['billing']['last_name'] ?? $booking_data['contact']['last_name'] ?? '';

                    // Log billing name source for debugging
                    amadex_log('Amadex Booking: Billing name source - Card holder name: ' . ($booking_data['payment']['card_name'] ?? 'N/A'));
                    amadex_log('Amadex Booking: Billing name - First: ' . $billing_first_name . ', Last: ' . $billing_last_name);
                    if (empty($billing_first_name) || empty($billing_last_name)) {
                        amadex_log('Amadex Booking WARNING: Billing name is missing! Card holder name field: ' . ($booking_data['payment']['card_name'] ?? 'empty'));
                    }

                    $payment_data = array(
                        'payment_token' => $payment_token, // Use token if available
                        'card_number' => $booking_data['payment']['card_number'] ?? '',
                        'card_expiry' => (!empty($booking_data['payment']['card_month']) && !empty($booking_data['payment']['card_year']))
                            ? $booking_data['payment']['card_month'] . $booking_data['payment']['card_year']
                            : '',
                        'card_cvv' => $booking_data['payment']['card_cvv'] ?? '',
                        'amount' => $usd_amount, // ALWAYS USD for NMI
                        'currency' => 'USD', // ALWAYS USD for NMI
                        'billing' => array(
                            // Use billing name from card holder (not contact/passenger name)
                            'first_name' => $billing_first_name,
                            'last_name' => $billing_last_name,
                            'address1' => $booking_data['billing']['address1'] ?? '',
                            'address2' => $booking_data['billing']['address2'] ?? '',
                            'city' => $booking_data['billing']['city'] ?? '',
                            'state' => $booking_data['billing']['state'] ?? '',
                            'postal' => $booking_data['billing']['postal'] ?? '',
                            'country' => $booking_data['billing']['country'] ?? 'US'
                        ),
                        'contact' => array(
                            'phone' => $booking_data['contact']['phone'] ?? '',
                            'email' => $booking_data['contact']['email'] ?? ''
                        ),
                        'order_id' => $booking_reference, // Use booking reference as order ID for NMI visibility
                        'booking_reference' => $booking_reference, // Include booking reference
                        'flight_summary' => $flight_summary,
                        'description' => 'Flight Booking - Authorization',
                        'cavv' => $booking_data['cavv'] ?? $_POST['cavv'] ?? '',
                        'xid' => $booking_data['xid'] ?? $_POST['xid'] ?? '',
                        'eci' => $booking_data['eci'] ?? $_POST['eci'] ?? '',
                        'cardholder_auth' => $booking_data['cardholder_auth'] ?? $_POST['cardholder_auth'] ?? '',
                        'three_ds_version' => $booking_data['three_ds_version'] ?? $_POST['three_ds_version'] ?? '',
                        'directory_server_id' => $booking_data['directory_server_id'] ?? $_POST['directory_server_id'] ?? '',
                    );

                    // ✅ PAYMENT TOKEN DEDUPLICATION: Check if token already used
                    $existing_auth = $this->check_existing_payment_authorization($payment_token, $booking_data);

                    if ($existing_auth) {
                        amadex_log('Amadex Booking: Payment token already used - reusing existing authorization. Transaction ID: ' . $existing_auth['transaction_id']);

                        $auth_result = array(
                            'success'        => true,
                            'transaction_id' => $existing_auth['transaction_id'],
                            'auth_code'      => $existing_auth['auth_code'] ?? '',
                            'card_last4'     => $existing_auth['card_last4'] ?? '',
                            'card_type'      => $existing_auth['card_type'] ?? '',
                            'avs_response'   => $existing_auth['avs_result'] ?? '',
                            'cvv_response'   => $existing_auth['cvv_result'] ?? '',
                            'reused'         => true,
                        );
                    } else {
                        // ── NMI THREE STEP REDIRECT (3DS) ──────────────────────────────────
                        // Gateway.js 3DS path: authentication already done client-side.
                        // Card data + cavv/eci/cardholder_auth come in raw — charge directly.
                        if (!empty($use_raw_card) && !empty($has_raw_card)) {
                            amadex_log('Amadex Booking: Gateway.js 3DS complete — charging raw card directly (skipping Three Step Redirect)');
                            $auth_result = $payment->authorize_payment($payment_data);
                        } else {

                            // ── NMI THREE STEP REDIRECT (server-side 3DS) ──────────────────────
                            $payment_opts   = get_option('amadex_payment_settings', array());
                            $three_ds_on    = isset($payment_opts['enable_3ds']) ? (int) $payment_opts['enable_3ds'] : 1;

                            if (!$three_ds_on) {
                                amadex_log('Amadex Booking: 3DS is disabled by admin — charging directly without Three Step Redirect.');
                                $auth_result = $payment->authorize_payment($payment_data);
                            } else {
                                amadex_log('Amadex Booking: Initiating NMI Three Step Redirect (3DS)');

                                $redirect_back_url = add_query_arg(
                                    array(
                                        'amadex_3ds_return' => '1',
                                        'booking_ref'       => $booking_reference,
                                    ),
                                    home_url('/flight-booking/')
                                );

                                $three_step_result = $payment->initiate_three_step_redirect($payment_data, $redirect_back_url);

                                if (is_wp_error($three_step_result)) {
                                    amadex_log('Amadex Three Step Error: ' . $three_step_result->get_error_message());
                                    // Release lock on error
                                    if (!empty($lock_key)) {
                                        $this->release_booking_lock($lock_key, null, 'FAILED');
                                    }
                                    wp_send_json_error(array('message' => $three_step_result->get_error_message()));
                                    return;
                                }

                                if (empty($three_step_result['form_url'])) {
                                    amadex_log('Amadex Three Step Error: No form_url returned');
                                    if (!empty($lock_key)) {
                                        $this->release_booking_lock($lock_key, null, 'FAILED');
                                    }
                                    wp_send_json_error(array('message' => __('3DS initiation failed. Please try again.', 'amadex')));
                                    return;
                                }

                                // Save all booking context in a transient so Step 3 can complete it
                                set_transient(
                                    'amadex_3ds_booking_' . $booking_reference,
                                    array(
                                        'booking_data'     => $booking_data,
                                        'payment_data'     => $payment_data,
                                        'lock_key'         => $lock_key,
                                        'lead_id'          => $lead_id,
                                        'total_amount'     => $total_amount,
                                        'booking_reference' => $booking_reference,
                                        'default_gateway'  => $default_gateway,
                                    ),
                                    30 * MINUTE_IN_SECONDS
                                );

                                amadex_log('Amadex Booking: 3DS redirect sent. form_url=' . $three_step_result['form_url']);

                                // Return redirect URL to JS — frontend will navigate the user there
                                wp_send_json_success(array(
                                    'three_step'   => true,
                                    'redirect_url' => $three_step_result['form_url'],
                                ));
                                return;
                                // ── END THREE STEP REDIRECT ────────────────────────────────────────
                            } // end else (3DS enabled)
                        } // end else (server-side Three Step path)
                    }
                }

                if (is_wp_error($auth_result)) {
                    $error_msg = $auth_result->get_error_message();

                    // Provide helpful error message for missing keys
                    if (strpos($error_msg, 'API key') !== false || strpos($error_msg, 'not configured') !== false || strpos($error_msg, 'Secret Key') !== false) {
                        if ($default_gateway === 'stripe') {
                            $error_msg = __('Stripe Secret Key is not configured. Please go to Amadex Settings → Payment Settings and enter your Stripe Secret Key.', 'amadex');
                        } else {
                            $error_msg = __('NMI API key is not configured. Please go to Amadex Settings → Payment Settings and enter your NMI API Key (Security Key).', 'amadex');
                        }
                    }

                    amadex_log('Amadex Payment Authorization Error: ' . $error_msg);
                    if ($default_gateway === 'stripe') {
                        error_log('  PaymentIntent ID: ' . (!empty($payment_intent_id) ? substr($payment_intent_id, 0, 20) . '...' : 'MISSING'));
                    } else {
                        error_log('  Payment Token: ' . (!empty($payment_token) ? substr($payment_token, 0, 20) . '...' : 'MISSING'));
                    }

                    // ✅ Release lock on payment error
                    if (!empty($lock_key)) {
                        $this->release_booking_lock($lock_key, null, 'FAILED');
                    }

                    // Record failure reason on the lead
                    if (!empty($lead_id)) {
                        global $wpdb;
                        $wpdb->update(
                            $wpdb->prefix . 'amadex_leads',
                            array(
                                'payment_failure_reason' => 'gateway_error',
                                'payment_failure_detail' => $error_msg,
                            ),
                            array('id' => $lead_id),
                            array('%s', '%s'),
                            array('%d')
                        );
                    }
                    // Payment failed - return error (don't proceed to confirmation)
                    wp_send_json_error(array(
                        'message' => __('Payment authorization failed: ', 'amadex') . $error_msg
                    ));
                    return;
                }

                if (!$auth_result['success']) {
                    $error_msg = $auth_result['response_text'] ?? 'Unknown error';
                    $response_code = $auth_result['response_code'] ?? '';

                    // Get raw NMI response details
                    $raw_response = $auth_result['raw_response'] ?? array();
                    $raw_response_code = $raw_response['response'] ?? $raw_response['response_code'] ?? '';
                    $raw_response_text = isset($raw_response['responsetext']) ? strtolower($raw_response['responsetext']) : strtolower($error_msg);
                    $raw_response_text_original = isset($raw_response['responsetext']) ? $raw_response['responsetext'] : $error_msg;

                    // Log detailed error for debugging
                    amadex_log('Amadex Payment Authorization Failed:');
                    error_log('  Response Code: ' . $response_code);
                    error_log('  Raw Response Code: ' . $raw_response_code);
                    error_log('  Response Text: ' . $error_msg);
                    error_log('  Raw Response Text: ' . $raw_response_text_original);
                    error_log('  Payment Token: ' . (!empty($payment_token) ? substr($payment_token, 0, 20) . '...' : 'MISSING'));
                    error_log('  Booking Reference: ' . $booking_reference);
                    error_log('  Amount: ' . ($payment_data['amount'] ?? 'N/A'));
                    error_log('  Currency: ' . ($payment_data['currency'] ?? 'N/A'));

                    // Get payment settings for validation
                    $payment_settings = get_option('amadex_payment_settings', array());
                    $nmi_api_key = $payment_settings['nmi_api_key'] ?? '';
                    $nmi_tokenization_key = $payment_settings['nmi_tokenization_key'] ?? '';
                    error_log('  NMI API Key Present: ' . (!empty($nmi_api_key) ? 'Yes (' . substr($nmi_api_key, 0, 10) . '...)' : 'No'));
                    error_log('  NMI Tokenization Key Present: ' . (!empty($nmi_tokenization_key) ? 'Yes (' . substr($nmi_tokenization_key, 0, 10) . '...)' : 'No'));

                    error_log('  Billing Name: ' . ($payment_data['billing']['first_name'] ?? '') . ' ' . ($payment_data['billing']['last_name'] ?? ''));
                    error_log('  Billing Country: ' . ($payment_data['billing']['country'] ?? 'N/A'));
                    error_log('  Full NMI Response: ' . print_r($auth_result, true));

                    // Build error details for frontend debugging
                    $error_details = array(
                        'response_code' => $response_code,
                        'raw_response_code' => $raw_response_code,
                        'response_text' => $raw_response_text_original,
                        'payment_token_present' => !empty($payment_token),
                        'api_key_configured' => !empty($nmi_api_key),
                        'tokenization_key_configured' => !empty($nmi_tokenization_key)
                    );

                    // Provide helpful error messages based on error type
                    // First, check for explicit NMI key-related errors
                    if (
                        strpos($raw_response_text, 'api key not found') !== false ||
                        strpos($raw_response_text, 'specified api key not found') !== false ||
                        strpos($raw_response_text, 'invalid security key') !== false ||
                        strpos($raw_response_text, 'authentication failed') !== false ||
                        strpos($raw_response_text, 'invalid credentials') !== false
                    ) {
                        $error_msg = __('NMI API key (Security Key) is invalid or not found. Please check your Security Key in Amadex Settings → Payment Settings. Make sure you are using the PRIVATE Security Key (API Key) from NMI Dashboard → Settings → Security Keys → Private Security Keys → API Key (not the Tokenization Key).', 'amadex');
                    } elseif (($raw_response_code == '300' || $response_code == '300') &&
                        (strpos($raw_response_text, 'tokenization') !== false ||
                            strpos($raw_response_text, 'key') !== false ||
                            strpos($raw_response_text, 'security key') !== false ||
                            strpos($raw_response_text, 'mismatch') !== false ||
                            strpos($raw_response_text, 'configuration') !== false)
                    ) {
                        // Code 300 with key/tokenization/configuration related text - likely key mismatch
                        $error_msg = __('Payment gateway configuration error: Your Tokenization Key and Security Key (API key) must be from the same NMI account. Please verify: 1) Both keys are from the same NMI account, 2) If using FLYTRAVELAY Tokenization Key, use FLYTRAVELAY API Key (not Default Cart Key), 3) Both keys are from the same environment (test/production). Check your keys in Amadex Settings → Payment Settings.', 'amadex');
                        $error_details['issue_type'] = 'key_mismatch';
                    } elseif ($raw_response_code == '300' || $response_code == '300') {
                        // Code 300 but without key-related text - could be other configuration issues
                        // Show the actual NMI error message
                        $error_msg = __('Payment gateway error (Code 300): ', 'amadex') . $raw_response_text_original . '. ';
                        $error_msg .= __('This may indicate a configuration issue. Please check: 1) Your NMI API Key and Tokenization Key are correctly configured, 2) Both keys are from the same NMI account and environment, 3) Payment token is valid. If the problem persists, check your NMI Dashboard for transaction details.', 'amadex');
                        $error_details['issue_type'] = 'configuration_error';
                        $error_details['nmi_error'] = $raw_response_text_original;
                    } elseif (strpos($raw_response_text, 'invalid') !== false && strpos($raw_response_text, 'token') !== false) {
                        $error_msg = __('Payment token is invalid or expired. Please check your card details and try again. If the problem persists, refresh the page and try again.', 'amadex');
                        $error_details['issue_type'] = 'invalid_token';
                    } elseif ($response_code == '2' || strpos(strtolower($error_msg), 'declined') !== false) {
                        if (
                            strpos($raw_response_text, '3d') !== false ||
                            strpos($raw_response_text, '3ds') !== false ||
                            strpos($raw_response_text, 'authentication') !== false ||
                            strpos($raw_response_text, 'not enrolled') !== false ||
                            strpos($raw_response_text, 'not supported') !== false ||
                            (empty($booking_data['cavv']) && empty($booking_data['eci']))
                        ) {
                            $error_msg = __('This card does not support 3D Secure authentication, which is required for online payments on this site. Please try a different card that supports 3DS (Visa Secure, Mastercard Identity Check, or American Express SafeKey).', 'amadex');
                            $error_details['issue_type'] = 'card_no_3ds';
                        } else {
                            $error_msg = __('Payment was declined. Please check: 1) Your card details are correct, 2) Sufficient funds available, 3) Card is not expired, 4) Billing address matches card address. If the problem persists, try a different card or contact support.', 'amadex');
                            $error_details['issue_type'] = 'payment_declined';
                        }
                    } elseif ($response_code == '3' || strpos(strtolower($error_msg), 'error') !== false) {
                        $error_msg = __('Payment processing error occurred: ', 'amadex') . $raw_response_text_original . '. ';
                        $error_msg .= __('Please check your payment gateway configuration or contact support.', 'amadex');
                        $error_details['issue_type'] = 'processing_error';
                        $error_details['nmi_error'] = $raw_response_text_original;
                    } else {
                        // Keep original error message but make it more user-friendly
                        $error_msg = __('Payment authorization failed: ', 'amadex') . $raw_response_text_original;
                        $error_details['issue_type'] = 'unknown';
                        $error_details['nmi_error'] = $raw_response_text_original;
                    }

                    // ✅ Release lock on payment error
                    if (!empty($lock_key)) {
                        $this->release_booking_lock($lock_key, null, 'FAILED');
                    }

                    // Record failure reason on lead
                    if (!empty($lead_id)) {
                        global $wpdb;
                        $wpdb->update(
                            $wpdb->prefix . 'amadex_leads',
                            array(
                                'payment_failure_reason' => sanitize_text_field($error_details['issue_type'] ?? 'unknown'),
                                'payment_failure_detail' => $error_msg,
                                'card_last4'             => sanitize_text_field($auth_result['card_last4'] ?? ''),
                                'card_type'              => sanitize_text_field($auth_result['card_type'] ?? ''),
                            ),
                            array('id' => $lead_id),
                            array('%s', '%s', '%s', '%s'),
                            array('%d')
                        );
                    }

                    // Payment failed - return error (don't proceed to confirmation)
                    wp_send_json_error(array(
                        'message' => $error_msg,
                        'details' => $error_details
                    ));
                    return;
                }

                amadex_log('Amadex: Authorization completed successfully');
                error_log('  Transaction ID: ' . $auth_result['transaction_id']);
                error_log('  Auth Code: ' . ($auth_result['auth_code'] ?? 'N/A'));
                error_log('  Booking Reference: ' . $booking_reference);
            }
            $validating_airline = '';
            if (!empty($booking_data['flight']['validating_airline_codes'][0])) {
                $validating_airline = strtoupper(substr($booking_data['flight']['validating_airline_codes'][0], 0, 2));
            } elseif (!empty($booking_data['flight']['itineraries'][0]['segments'][0]['carrierCode'])) {
                $validating_airline = strtoupper(substr($booking_data['flight']['itineraries'][0]['segments'][0]['carrierCode'], 0, 2));
            }
            $auto_pnr = $this->generate_pnr_code($validating_airline);
            if ($auto_pnr) {
                $database->update_booking_pnr($booking_id, $auto_pnr);
            }

            // Step 4: Add passengers
            if (isset($booking_data['passengers'])) {
                foreach ($booking_data['passengers'] as $passenger) {
                    $dob = sprintf(
                        '%04d-%02d-%02d',
                        $passenger['dob']['year'],
                        $passenger['dob']['month'],
                        $passenger['dob']['day']
                    );

                    // Get passport data (check multiple field name formats)
                    $passport_number = $passenger['passportNo'] ?? $passenger['passport_number'] ?? '';
                    $passport_expiry = $passenger['passport_expiry'] ?? '';

                    // If passport expiry not provided, construct from day/month/year
                    if (empty($passport_expiry)) {
                        $exp_day = $passenger['passportExpDay'] ?? $passenger['passport_exp_day'] ?? '';
                        $exp_month = $passenger['passportExpMonth'] ?? $passenger['passport_exp_month'] ?? '';
                        $exp_year = $passenger['passportExpYear'] ?? $passenger['passport_exp_year'] ?? '';
                        if (!empty($exp_day) && !empty($exp_month) && !empty($exp_year)) {
                            $passport_expiry = sprintf('%04d-%02d-%02d', $exp_year, $exp_month, $exp_day);
                        }
                    }

                    // Get nationality (check multiple field name formats)
                    $nationality = $passenger['nationality'] ?? $passenger['passportCountry'] ?? '';

                    // Determine passenger type
                    $passenger_type = 'ADULT';
                    if (isset($passenger['passenger_type'])) {
                        $passenger_type = strtoupper($passenger['passenger_type']);
                    } elseif (isset($passenger['type'])) {
                        $passenger_type = strtoupper($passenger['type']);
                    }

                    $database->add_passenger($booking_id, array(
                        'passenger_type' => $passenger_type,
                        'first_name' => $passenger['firstname'] ?? $passenger['first_name'] ?? '',
                        'middle_name' => $passenger['middlename'] ?? $passenger['middle_name'] ?? '',
                        'last_name' => $passenger['lastname'] ?? $passenger['last_name'] ?? '',
                        'gender' => $passenger['gender'] ?? '',
                        'date_of_birth' => $dob,
                        'passport_number' => $passport_number,
                        'passport_expiry' => $passport_expiry,
                        'nationality' => $nationality
                    ));
                }
            }

            // Step 5: Store payment authorization (or failure)
            $payment_status = isset($auth_result['success']) && $auth_result['success'] ? 'AUTH_ONLY' : 'FAILED';
            if (!$auth_result['success']) {
                amadex_log('Amadex: Storing failed payment authorization for booking: ' . $booking_reference);
            }

            // Use the same adjusted total_amount for payment record
            // This ensures payment record matches NMI transaction and confirmation page
            $payment_amount = $total_amount; // Use the adjusted amount calculated above

            $database->create_payment($booking_id, array(
                'transaction_id' => $auth_result['transaction_id'] ?? '',
                'payment_status' => $payment_status,
                'payment_method' => 'CREDIT_CARD',
                'amount' => $payment_amount, // Use adjusted price (with discount/markup)
                'currency' => $flight_data['price']['currency'] ?? 'USD',
                'card_last4' => $auth_result['card_last4'] ?? '',
                'card_type' => $auth_result['card_type'] ?? '',
                'avs_result' => $auth_result['avs_response'] ?? '',
                'cvv_result' => $auth_result['cvv_response'] ?? '',
                'gateway_response' => $auth_result['raw_response'] ?? array(),
                'auth_code' => $auth_result['auth_code'] ?? ''
            ));

            // ✅ Store payment token usage to prevent reuse
            if (!empty($payment_token) && $auth_result['success']) {
                $this->store_payment_token_usage($payment_token, $auth_result, $booking_id);
            }

            // Step 6: Send notifications & mark confirmation sent
            $emails_sent = false;
            try {
                $booking_details = $database->get_booking($booking_id);
                if ($booking_details) {
                    amadex_log('Amadex: Attempting to send booking notifications for booking ID: ' . $booking_id);
                    $emails_sent = $this->send_booking_notifications($booking_details, $database);
                    if (!$emails_sent) {
                        amadex_log('Amadex: WARNING - Email notifications may not have been sent. Check WordPress mail configuration.');
                    }
                } else {
                    amadex_log('Amadex: Could not retrieve booking details for email notification. Booking ID: ' . $booking_id);
                }
            } catch (Exception $e) {
                amadex_log('Amadex Notification Error: ' . $e->getMessage());
                amadex_log('Amadex Notification Error Trace: ' . $e->getTraceAsString());
            }

            $confirmation_url = $this->get_confirmation_page_url($booking_reference);

            // Payment succeeded - proceed to confirmation
            $success_message = __('Booking received successfully! Payment authorized. Our agents will contact you shortly to confirm.', 'amadex');

            // Log successful booking details
            amadex_log('Amadex: Booking completed successfully');
            error_log('  Booking Reference: ' . $booking_reference);
            error_log('  Booking ID: ' . $booking_id);
            error_log('  PNR: ' . ($auto_pnr ?: 'Not generated'));
            error_log('  Payment Status: ' . $payment_status);
            error_log('  Transaction ID: ' . ($auth_result['transaction_id'] ?? 'N/A'));
            error_log('  Confirmation URL: ' . $confirmation_url);

            // ✅ Release lock on success
            $this->release_booking_lock($lock_key, $booking_id, 'COMPLETED');

            // When called from Stripe return handler: redirect to confirmation page (no JSON)
            if (defined('AMADEX_STRIPE_RETURN_REDIRECT') && AMADEX_STRIPE_RETURN_REDIRECT) {
                wp_safe_redirect($confirmation_url);
                exit;
            }

            // Return success (agent will be notified to review)
            wp_send_json_success(array(
                'message' => $success_message,
                'booking_reference' => $booking_reference,
                'lead_id' => $lead_id,
                'booking_id' => $booking_id,
                'pnr' => $auto_pnr,
                'status' => 'VERIFIED_LEAD',
                'payment_status' => $payment_status,
                'payment_success' => true,
                'transaction_id' => $auth_result['transaction_id'] ?? '',
                'confirmation_url' => $confirmation_url
            ));
        } catch (Exception $e) {
            // ✅ Release lock on exception
            if (!empty($lock_key)) {
                $this->release_booking_lock($lock_key, null, 'FAILED');
            }

            amadex_log('Amadex Booking Exception: ' . $e->getMessage());
            amadex_log('Amadex Booking Exception Trace: ' . $e->getTraceAsString());

            if (defined('AMADEX_STRIPE_RETURN_REDIRECT') && AMADEX_STRIPE_RETURN_REDIRECT) {
                wp_safe_redirect(home_url('/?amadex_stripe_error=booking'));
                exit;
            }
            wp_send_json_error(array(
                'message' => __('An error occurred while processing your booking. Please try again or contact support.', 'amadex')
            ));
        }
    }

    /**
     * PayPal Create Order AJAX handler
     */
    public function paypal_create_order()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $payment_settings = get_option('amadex_payment_settings', array());
        $paypal_client_id = isset($payment_settings['paypal_client_id']) ? trim($payment_settings['paypal_client_id']) : '';
        $paypal_secret = isset($payment_settings['paypal_client_secret']) ? trim($payment_settings['paypal_client_secret']) : '';
        $paypal_sandbox = isset($payment_settings['paypal_mode']) && $payment_settings['paypal_mode'] === 'live' ? false : true;
        if (empty($paypal_client_id) || empty($paypal_secret)) {
            wp_send_json_error(array('message' => __('PayPal is not configured.', 'amadex')));
            return;
        }
        $booking_data = isset($_POST['booking_data']) ? $_POST['booking_data'] : array();
        if (is_string($booking_data)) {
            $booking_data = json_decode(stripslashes($booking_data), true);
        }
        if (empty($booking_data) || empty($booking_data['flight'])) {
            wp_send_json_error(array('message' => __('Invalid booking data.', 'amadex')));
            return;
        }
        $database = new Amadex_Database();
        $database->ensure_tables_ready();
        $lead_data = array(
            'lead_type' => 'VERIFIED_LEAD',
            'contact_name' => ($booking_data['contact']['first_name'] ?? '') . ' ' . ($booking_data['contact']['last_name'] ?? ''),
            'contact_email' => $booking_data['contact']['email'] ?? '',
            'contact_phone' => $booking_data['contact']['phone'] ?? '',
            'flight_data' => $booking_data['flight'],
            // 'search_params' => $booking_data['search_params'] ?? array(),
            'search_params' => array_merge(
                (array)($booking_data['search_params'] ?? []),
                ['user_location' => $booking_data['user_location'] ?? []]
            ),
            'source' => 'ONLINE',
            'fraud_data' => null
        );
        $lead_id = $database->create_lead($lead_data);
        if (!$lead_id) {
            wp_send_json_error(array('message' => __('Failed to create lead.', 'amadex')));
            return;
        }
        $flight_data = $booking_data['flight'] ?? array();
        $total_amount = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? 0);
        if ($total_amount <= 0) {
            $total_amount = floatval($flight_data['price']['original_total'] ?? 0);
        }
        if (isset($flight_data['price']['pricing_charge_total']) && (float)$flight_data['price']['pricing_charge_total'] > 0) {
            $total_amount = (float)$flight_data['price']['pricing_charge_total'];
        }
        $addons_total = 0;
        if (isset($booking_data['addons']) && is_array($booking_data['addons'])) {
            foreach ($booking_data['addons'] as $addon) {
                if (is_array($addon) && isset($addon['price'])) {
                    $addons_total += floatval($addon['price'] ?? 0);
                }
            }
        }
        $seat_charges = floatval($booking_data['seat_selection']['total_seat_charges'] ?? 0);
        $total_amount_usd = $total_amount + $addons_total + $seat_charges;
        if ($total_amount_usd < 0.50) {
            wp_send_json_error(array('message' => __('Invalid amount.', 'amadex')));
            return;
        }
        $passenger_count = count($booking_data['passengers'] ?? array());
        if ($passenger_count <= 0) {
            wp_send_json_error(array('message' => __('No passengers.', 'amadex')));
            return;
        }
        $booking_result = $database->create_booking(array(
            'lead_id' => $lead_id,
            'flight_data' => $flight_data,
            'total_amount' => $total_amount_usd,
            'currency' => 'USD',
            'passenger_count' => $passenger_count,
            'booking_channel' => 'ONLINE',
            'status' => 'PENDING',
            'fraud_data' => null,
            'billing' => $booking_data['billing'] ?? array(),
            'contact' => $booking_data['contact'] ?? array()
        ));
        if (!$booking_result || empty($booking_result['booking_reference'])) {
            wp_send_json_error(array('message' => __('Failed to create booking.', 'amadex')));
            return;
        }
        $booking_reference = $booking_result['booking_reference'];
        $booking_id = isset($booking_result['booking_id']) ? (int) $booking_result['booking_id'] : 0;

        // Add passengers to booking so confirmation page and emails show passenger details (same as NMI/Stripe/Crypto.com flow)
        if ($booking_id && !empty($booking_data['passengers'])) {
            foreach ($booking_data['passengers'] as $passenger) {
                $dob = isset($passenger['dob']['year'], $passenger['dob']['month'], $passenger['dob']['day'])
                    ? sprintf('%04d-%02d-%02d', (int) $passenger['dob']['year'], (int) $passenger['dob']['month'], (int) $passenger['dob']['day'])
                    : ($passenger['date_of_birth'] ?? '');
                $passport_number = $passenger['passportNo'] ?? $passenger['passport_number'] ?? '';
                $passport_expiry = $passenger['passport_expiry'] ?? '';
                if (empty($passport_expiry) && !empty($passenger['passportExpYear']) && !empty($passenger['passportExpMonth']) && !empty($passenger['passportExpDay'])) {
                    $passport_expiry = sprintf('%04d-%02d-%02d', (int) $passenger['passportExpYear'], (int) $passenger['passportExpMonth'], (int) $passenger['passportExpDay']);
                }
                $nationality = $passenger['nationality'] ?? $passenger['passportCountry'] ?? '';
                $passenger_type = 'ADULT';
                if (isset($passenger['passenger_type'])) {
                    $passenger_type = strtoupper($passenger['passenger_type']);
                } elseif (isset($passenger['type'])) {
                    $passenger_type = strtoupper($passenger['type']);
                }
                $database->add_passenger($booking_id, array(
                    'passenger_type' => $passenger_type,
                    'title' => $passenger['title'] ?? '',
                    'first_name' => $passenger['firstname'] ?? $passenger['first_name'] ?? '',
                    'middle_name' => $passenger['middlename'] ?? $passenger['middle_name'] ?? '',
                    'last_name' => $passenger['lastname'] ?? $passenger['last_name'] ?? '',
                    'gender' => $passenger['gender'] ?? '',
                    'date_of_birth' => $dob,
                    'passport_number' => $passport_number,
                    'passport_expiry' => $passport_expiry,
                    'nationality' => $nationality
                ));
            }
        }

        $base_url = $paypal_sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
        $auth = base64_encode($paypal_client_id . ':' . $paypal_secret);
        $ch = curl_init($base_url . '/v1/oauth2/token');
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Authorization: Basic ' . $auth, 'Content-Type: application/x-www-form-urlencoded'),
        ));
        $token_response = curl_exec($ch);
        $token_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($token_http !== 200) {
            amadex_log('PayPal token error: ' . $token_response);
            wp_send_json_error(array('message' => __('PayPal auth failed.', 'amadex')));
            return;
        }
        $token_data = json_decode($token_response, true);
        $access_token = $token_data['access_token'] ?? null;
        if (!$access_token) {
            wp_send_json_error(array('message' => __('PayPal auth failed.', 'amadex')));
            return;
        }
        $value = number_format($total_amount_usd, 2, '.', '');
        $order_body = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(array(
                'amount' => array('currency_code' => 'USD', 'value' => $value),
                'reference_id' => $booking_reference,
                'description' => 'Flight Booking: ' . $booking_reference,
            )),
            'application_context' => array(
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'PAY_NOW',
                'brand_name'          => get_bloginfo('name'),
            ),
        );
        $ch = curl_init($base_url . '/v2/checkout/orders');
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($order_body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $access_token, 'Content-Type: application/json'),
        ));
        $order_response = curl_exec($ch);
        $order_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($order_http < 200 || $order_http >= 300) {
            amadex_log('PayPal create order error: ' . $order_response);
            wp_send_json_error(array('message' => __('Could not create order.', 'amadex')));
            return;
        }
        $order_data = json_decode($order_response, true);
        $order_id = $order_data['id'] ?? null;
        if (!$order_id) {
            wp_send_json_error(array('message' => __('Invalid response.', 'amadex')));
            return;
        }
        set_transient('amadex_paypal_order_' . $order_id, $booking_reference, 3600);
        wp_send_json_success(array('orderID' => $order_id, 'token' => $booking_reference));
    }

    /**
     * PayPal Capture AJAX handler
     */
    public function paypal_capture()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $order_id = isset($_POST['orderID']) ? sanitize_text_field($_POST['orderID']) : '';
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
        if (empty($order_id) || empty($token)) {
            wp_send_json_error(array('message' => __('Missing order ID or token.', 'amadex')));
            return;
        }
        $payment_settings = get_option('amadex_payment_settings', array());
        $paypal_client_id = isset($payment_settings['paypal_client_id']) ? trim($payment_settings['paypal_client_id']) : '';
        $paypal_secret = isset($payment_settings['paypal_client_secret']) ? trim($payment_settings['paypal_client_secret']) : '';
        $paypal_sandbox = isset($payment_settings['paypal_mode']) && $payment_settings['paypal_mode'] === 'live' ? false : true;
        if (empty($paypal_client_id) || empty($paypal_secret)) {
            wp_send_json_error(array('message' => __('PayPal not configured.', 'amadex')));
            return;
        }
        $database = new Amadex_Database();
        $booking = $database->get_booking_by_reference($token);
        if (!$booking) {
            $stored_ref = get_transient('amadex_paypal_order_' . $order_id);
            if ($stored_ref) {
                delete_transient('amadex_paypal_order_' . $order_id);
                $booking = $database->get_booking_by_reference($stored_ref);
            }
        }
        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found.', 'amadex')));
            return;
        }
        if (($booking['status'] ?? '') === 'CONFIRMED') {
            $redirect_url = $this->get_confirmation_page_url($booking['booking_reference'] ?? $token);
            wp_send_json_success(array('success' => true, 'redirectUrl' => $redirect_url));
            return;
        }
        $base_url = $paypal_sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
        $auth = base64_encode($paypal_client_id . ':' . $paypal_secret);
        $ch = curl_init($base_url . '/v1/oauth2/token');
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Authorization: Basic ' . $auth, 'Content-Type: application/x-www-form-urlencoded'),
        ));
        $token_response = curl_exec($ch);
        $token_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($token_http !== 200) {
            amadex_log('PayPal token error: ' . $token_response);
            wp_send_json_error(array('message' => __('PayPal auth failed.', 'amadex')));
            return;
        }
        $token_data = json_decode($token_response, true);
        $access_token = $token_data['access_token'] ?? null;
        if (!$access_token) {
            wp_send_json_error(array('message' => __('PayPal auth failed.', 'amadex')));
            return;
        }
        $ch = curl_init($base_url . '/v2/checkout/orders/' . $order_id . '/capture');
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $access_token, 'Content-Type: application/json'),
        ));
        $capture_response = curl_exec($ch);
        $capture_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($capture_http < 200 || $capture_http >= 300) {
            amadex_log('PayPal capture error: ' . $capture_response);
            wp_send_json_error(array('message' => __('Capture failed.', 'amadex')));
            return;
        }
        delete_transient('amadex_paypal_order_' . $order_id);
        $booking_id = $booking['id'] ?? 0;
        if ($booking_id) {
            $database->update_booking_status($booking_id, 'CONFIRMED');
            $this->ensure_booking_pnr($booking_id, $database);
        }
        // Create payment record so confirmation page shows "PayPal" as payment method
        $capture_data = json_decode($capture_response, true);
        $transaction_id = $order_id;
        $capture_amount = isset($booking['total_amount']) ? floatval($booking['total_amount']) : 0;
        $capture_currency = isset($booking['currency']) ? $booking['currency'] : 'USD';
        if (is_array($capture_data) && !empty($capture_data['purchase_units'][0]['payments']['captures'][0])) {
            $cap = $capture_data['purchase_units'][0]['payments']['captures'][0];
            $transaction_id = isset($cap['id']) ? $cap['id'] : $order_id;
            if (!empty($cap['amount']['value'])) {
                $capture_amount = floatval($cap['amount']['value']);
            }
            if (!empty($cap['amount']['currency_code'])) {
                $capture_currency = $cap['amount']['currency_code'];
            }
        }
        $database->create_payment($booking_id, array(
            'transaction_id' => $transaction_id,
            'payment_status' => 'CAPTURED',
            'payment_method' => 'PAYPAL',
            'amount' => $capture_amount,
            'currency' => $capture_currency,
        ));
        try {
            $booking_details = $database->get_booking($booking_id);
            if ($booking_details) {
                $this->send_booking_notifications($booking_details, $database);
            }
        } catch (Exception $e) {
            amadex_log('PayPal: ' . $e->getMessage());
        }
        $redirect_url = $this->get_confirmation_page_url($booking['booking_reference'] ?? $token);
        wp_send_json_success(array('success' => true, 'redirectUrl' => $redirect_url));
    }

    /**
     * Crypto.com Pay: create booking and Crypto.com payment; return payment_id for Button SDK.
     */
    public function cryptocom_create_payment()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $payment_settings = get_option('amadex_payment_settings', array());
        $secret_key = isset($payment_settings['crypto_com_secret_key']) ? trim($payment_settings['crypto_com_secret_key']) : '';
        $publishable_key = isset($payment_settings['crypto_com_publishable_key']) ? trim($payment_settings['crypto_com_publishable_key']) : '';
        $enable = isset($payment_settings['enable_crypto_com']) ? (int) $payment_settings['enable_crypto_com'] : 0;
        if (!$enable || empty($secret_key) || empty($publishable_key)) {
            wp_send_json_error(array('message' => __('Crypto.com Pay is not configured or disabled.', 'amadex')));
            return;
        }
        $booking_data = isset($_POST['booking_data']) ? $_POST['booking_data'] : array();
        if (is_string($booking_data)) {
            $booking_data = json_decode(stripslashes($booking_data), true);
        }
        if (empty($booking_data) || empty($booking_data['flight'])) {
            wp_send_json_error(array('message' => __('Invalid booking data.', 'amadex')));
            return;
        }
        $database = new Amadex_Database();
        $database->ensure_tables_ready();
        $lead_data = array(
            'lead_type' => 'VERIFIED_LEAD',
            'contact_name' => ($booking_data['contact']['first_name'] ?? '') . ' ' . ($booking_data['contact']['last_name'] ?? ''),
            'contact_email' => $booking_data['contact']['email'] ?? '',
            'contact_phone' => $booking_data['contact']['phone'] ?? '',
            'flight_data' => $booking_data['flight'],
            // 'search_params' => $booking_data['search_params'] ?? array(),
            'search_params' => array_merge(
                (array)($booking_data['search_params'] ?? []),
                ['user_location' => $booking_data['user_location'] ?? []]
            ),
            'source' => 'ONLINE',
            'fraud_data' => null
        );
        $lead_id = $database->create_lead($lead_data);
        if (!$lead_id) {
            wp_send_json_error(array('message' => __('Failed to create lead.', 'amadex')));
            return;
        }
        $flight_data = $booking_data['flight'] ?? array();
        $total_amount = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? 0);
        if ($total_amount <= 0) {
            $total_amount = floatval($flight_data['price']['original_total'] ?? 0);
        }
        if (isset($flight_data['price']['pricing_charge_total']) && (float) $flight_data['price']['pricing_charge_total'] > 0) {
            $total_amount = (float) $flight_data['price']['pricing_charge_total'];
        }
        $addons_total = 0;
        if (isset($booking_data['addons']) && is_array($booking_data['addons'])) {
            foreach ($booking_data['addons'] as $addon) {
                if (is_array($addon) && isset($addon['price'])) {
                    $addons_total += floatval($addon['price'] ?? 0);
                }
            }
        }
        $seat_charges = floatval($booking_data['seat_selection']['total_seat_charges'] ?? 0);
        $total_amount_usd = $total_amount + $addons_total + $seat_charges;
        if ($total_amount_usd < 0.50) {
            wp_send_json_error(array('message' => __('Invalid amount.', 'amadex')));
            return;
        }
        $passenger_count = count($booking_data['passengers'] ?? array());
        if ($passenger_count <= 0) {
            wp_send_json_error(array('message' => __('No passengers.', 'amadex')));
            return;
        }
        $booking_result = $database->create_booking(array(
            'lead_id' => $lead_id,
            'flight_data' => $flight_data,
            'total_amount' => $total_amount_usd,
            'currency' => 'USD',
            'passenger_count' => $passenger_count,
            'booking_channel' => 'ONLINE',
            'status' => 'PENDING',
            'fraud_data' => null,
            'billing' => $booking_data['billing'] ?? array(),
            'contact' => $booking_data['contact'] ?? array()
        ));
        if (!$booking_result || empty($booking_result['booking_reference'])) {
            wp_send_json_error(array('message' => __('Failed to create booking.', 'amadex')));
            return;
        }
        $booking_reference = $booking_result['booking_reference'];
        $booking_id = $booking_result['booking_id'];

        // Add passengers (same as NMI/Stripe flow so confirmation page shows passenger details)
        if (isset($booking_data['passengers']) && $booking_id) {
            foreach ($booking_data['passengers'] as $passenger) {
                $dob = isset($passenger['dob']['year'], $passenger['dob']['month'], $passenger['dob']['day'])
                    ? sprintf('%04d-%02d-%02d', (int) $passenger['dob']['year'], (int) $passenger['dob']['month'], (int) $passenger['dob']['day'])
                    : '';
                $passport_number = $passenger['passportNo'] ?? $passenger['passport_number'] ?? '';
                $passport_expiry = $passenger['passport_expiry'] ?? '';
                if (empty($passport_expiry) && !empty($passenger['passportExpYear']) && !empty($passenger['passportExpMonth']) && !empty($passenger['passportExpDay'])) {
                    $passport_expiry = sprintf('%04d-%02d-%02d', (int) $passenger['passportExpYear'], (int) $passenger['passportExpMonth'], (int) $passenger['passportExpDay']);
                }
                $nationality = $passenger['nationality'] ?? $passenger['passportCountry'] ?? '';
                $passenger_type = 'ADULT';
                if (isset($passenger['passenger_type'])) {
                    $passenger_type = strtoupper($passenger['passenger_type']);
                } elseif (isset($passenger['type'])) {
                    $passenger_type = strtoupper($passenger['type']);
                }
                $database->add_passenger($booking_id, array(
                    'passenger_type' => $passenger_type,
                    'first_name' => $passenger['firstname'] ?? $passenger['first_name'] ?? '',
                    'middle_name' => $passenger['middlename'] ?? $passenger['middle_name'] ?? '',
                    'last_name' => $passenger['lastname'] ?? $passenger['last_name'] ?? '',
                    'gender' => $passenger['gender'] ?? '',
                    'date_of_birth' => $dob,
                    'passport_number' => $passport_number,
                    'passport_expiry' => $passport_expiry,
                    'nationality' => $nationality
                ));
            }
        }

        $amount_cents = (int) round($total_amount_usd * 100);
        if ($amount_cents < 1) {
            $amount_cents = 1;
        }
        $description = 'Flight Booking - ' . $booking_reference;
        $confirmation_url = $this->get_confirmation_page_url($booking_reference);
        $body = array(
            'amount' => $amount_cents,
            'currency' => 'USD',
            'description' => $description,
            'order_id' => $booking_reference,
            'return_url' => $confirmation_url,
            'cancel_url' => $confirmation_url
        );
        $response = wp_remote_post(
            'https://pay.crypto.com/api/payments',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($secret_key . ':'),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => http_build_query($body),
            )
        );
        if (is_wp_error($response)) {
            amadex_log('Crypto.com Pay create payment error: ' . $response->get_error_message());
            wp_send_json_error(array('message' => __('Could not create Crypto.com payment.', 'amadex')));
            return;
        }
        $code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        $data = json_decode($body_raw, true);
        if ($code < 200 || $code >= 300 || empty($data['id'])) {
            amadex_log('Crypto.com Pay API error: code=' . $code . ', body=' . $body_raw);
            wp_send_json_error(array('message' => __('Crypto.com Pay returned an error. Please try again.', 'amadex')));
            return;
        }
        $payment_id = $data['id'];
        set_transient('amadex_cryptocom_payment_' . $booking_reference, $payment_id, 3600);
        wp_send_json_success(array(
            'payment_id' => $payment_id,
            'booking_reference' => $booking_reference,
            'confirmation_url' => $confirmation_url,
            'publishable_key' => $publishable_key
        ));
    }

    /**
     * Fired when a Crypto.com Pay booking is verified on the confirmation page; sends confirmation email.
     */
    public function on_cryptocom_booking_confirmed($booking_id, $booking, $database)
    {
        if (!$booking_id || !$database) {
            return;
        }
        $this->ensure_booking_pnr($booking_id, $database);
        $details = $database->get_booking($booking_id);
        if ($details) {
            $this->send_booking_notifications($details, $database);
        }
    }

    /**
     * Fired when a MoonPay Onramp (Pay with Card) booking is verified on the confirmation page; sends confirmation email.
     */
    public function on_moonpay_onramp_booking_confirmed($booking_id, $booking, $database)
    {
        if (!$booking_id || !$database) {
            return;
        }
        $this->ensure_booking_pnr($booking_id, $database);
        $details = $database->get_booking($booking_id);
        if ($details) {
            $this->send_booking_notifications($details, $database);
        }
    }

    /**
     * Fired when a MoonPay Commerce (Card or Crypto) booking is confirmed; sends confirmation email.
     * Call this from Helio webhook when payment succeeds, or when confirmation page loads with CONFIRMED + MOONPAY_COMMERCE.
     */
    public function on_moonpay_commerce_booking_confirmed($booking_id, $booking, $database)
    {
        if (!$booking_id || !$database) {
            return;
        }
        $this->ensure_booking_pnr($booking_id, $database);
        $details = $database->get_booking($booking_id);
        if ($details) {
            $this->send_booking_notifications($details, $database);
        }
    }

    /**
     * Fired when a Pay with Crypto (crypto transfer) booking is confirmed; sends confirmation email.
     * Call this when the crypto transfer flow confirms payment (e.g. return URL or webhook).
     */
    public function on_crypto_transfer_booking_confirmed($booking_id, $booking, $database)
    {
        if (!$booking_id || !$database) {
            return;
        }
        $this->ensure_booking_pnr($booking_id, $database);
        $details = $database->get_booking($booking_id);
        if ($details) {
            $this->send_booking_notifications($details, $database);
        }
    }

    /**
     * MoonPay Commerce (Helio): create booking and pay link; return URL to redirect customer.
     */
    public function moonpay_create_paylink()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $payment_settings = get_option('amadex_payment_settings', array());
        $enable = isset($payment_settings['enable_moonpay_commerce']) ? (int) $payment_settings['enable_moonpay_commerce'] : 0;
        $env = isset($payment_settings['moonpay_environment']) && $payment_settings['moonpay_environment'] === 'live' ? 'live' : 'test';
        $public_key = $env === 'live'
            ? trim($payment_settings['moonpay_publishable_key_live'] ?? '')
            : trim($payment_settings['moonpay_publishable_key_test'] ?? '');
        $secret_key = $env === 'live'
            ? trim($payment_settings['moonpay_secret_key_live'] ?? '')
            : trim($payment_settings['moonpay_secret_key_test'] ?? '');
        $wallet_id = trim($payment_settings['moonpay_helio_wallet_id'] ?? '');
        // Form POST can turn + into space in the secret; restore for Bearer token (common in base64-style keys).
        if (!empty($secret_key) && strpos($secret_key, ' ') !== false) {
            $secret_key = str_replace(' ', '+', $secret_key);
        }
        // Hel.io requires apiKey (public) in query and Bearer (secret). If only one key is set, use it for both.
        $api_key = $public_key;
        $bearer_key = !empty($secret_key) ? $secret_key : $public_key;
        if (!$enable || empty($api_key) || empty($wallet_id)) {
            wp_send_json_error(array('message' => __('MoonPay Commerce is not configured or disabled.', 'amadex')));
            return;
        }
        $booking_data = isset($_POST['booking_data']) ? $_POST['booking_data'] : array();
        if (is_string($booking_data)) {
            $booking_data = json_decode(stripslashes($booking_data), true);
        }
        if (empty($booking_data) || empty($booking_data['flight'])) {
            wp_send_json_error(array('message' => __('Invalid booking data.', 'amadex')));
            return;
        }
        $database = new Amadex_Database();
        $database->ensure_tables_ready();
        $lead_data = array(
            'lead_type' => 'VERIFIED_LEAD',
            'contact_name' => ($booking_data['contact']['first_name'] ?? '') . ' ' . ($booking_data['contact']['last_name'] ?? ''),
            'contact_email' => $booking_data['contact']['email'] ?? '',
            'contact_phone' => $booking_data['contact']['phone'] ?? '',
            'flight_data' => $booking_data['flight'],
            // 'search_params' => $booking_data['search_params'] ?? array(),
            'search_params' => array_merge(
                (array)($booking_data['search_params'] ?? []),
                ['user_location' => $booking_data['user_location'] ?? []]
            ),
            'source' => 'ONLINE',
            'fraud_data' => null
        );
        $lead_id = $database->create_lead($lead_data);
        if (!$lead_id) {
            wp_send_json_error(array('message' => __('Failed to create lead.', 'amadex')));
            return;
        }
        $flight_data = $booking_data['flight'] ?? array();
        $total_amount = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? 0);
        if ($total_amount <= 0) {
            $total_amount = floatval($flight_data['price']['original_total'] ?? 0);
        }
        if (isset($flight_data['price']['pricing_charge_total']) && (float) $flight_data['price']['pricing_charge_total'] > 0) {
            $total_amount = (float) $flight_data['price']['pricing_charge_total'];
        }
        $addons_total = 0;
        if (isset($booking_data['addons']) && is_array($booking_data['addons'])) {
            foreach ($booking_data['addons'] as $addon) {
                if (is_array($addon) && isset($addon['price'])) {
                    $addons_total += floatval($addon['price'] ?? 0);
                }
            }
        }
        $seat_charges = floatval($booking_data['seat_selection']['total_seat_charges'] ?? 0);
        $total_amount_usd = $total_amount + $addons_total + $seat_charges;
        if ($total_amount_usd < 0.50) {
            wp_send_json_error(array('message' => __('Invalid amount.', 'amadex')));
            return;
        }
        $passenger_count = count($booking_data['passengers'] ?? array());
        if ($passenger_count <= 0) {
            wp_send_json_error(array('message' => __('No passengers.', 'amadex')));
            return;
        }
        $booking_result = $database->create_booking(array(
            'lead_id' => $lead_id,
            'flight_data' => $flight_data,
            'total_amount' => $total_amount_usd,
            'currency' => 'USD',
            'passenger_count' => $passenger_count,
            'booking_channel' => 'ONLINE',
            'status' => 'PENDING',
            'fraud_data' => null,
            'billing' => $booking_data['billing'] ?? array(),
            'contact' => $booking_data['contact'] ?? array()
        ));
        if (!$booking_result || empty($booking_result['booking_reference'])) {
            wp_send_json_error(array('message' => __('Failed to create booking.', 'amadex')));
            return;
        }
        $booking_reference = $booking_result['booking_reference'];
        $booking_id = $booking_result['booking_id'];

        if (isset($booking_data['passengers']) && $booking_id) {
            foreach ($booking_data['passengers'] as $passenger) {
                $dob = isset($passenger['dob']['year'], $passenger['dob']['month'], $passenger['dob']['day'])
                    ? sprintf('%04d-%02d-%02d', (int) $passenger['dob']['year'], (int) $passenger['dob']['month'], (int) $passenger['dob']['day'])
                    : '';
                $passport_number = $passenger['passportNo'] ?? $passenger['passport_number'] ?? '';
                $passport_expiry = $passenger['passport_expiry'] ?? '';
                if (empty($passport_expiry) && !empty($passenger['passportExpYear']) && !empty($passenger['passportExpMonth']) && !empty($passenger['passportExpDay'])) {
                    $passport_expiry = sprintf('%04d-%02d-%02d', (int) $passenger['passportExpYear'], (int) $passenger['passportExpMonth'], (int) $passenger['passportExpDay']);
                }
                $nationality = $passenger['nationality'] ?? $passenger['passportCountry'] ?? '';
                $passenger_type = 'ADULT';
                if (isset($passenger['passenger_type'])) {
                    $passenger_type = strtoupper($passenger['passenger_type']);
                } elseif (isset($passenger['type'])) {
                    $passenger_type = strtoupper($passenger['type']);
                }
                $database->add_passenger($booking_id, array(
                    'passenger_type' => $passenger_type,
                    'first_name' => $passenger['firstname'] ?? $passenger['first_name'] ?? '',
                    'middle_name' => $passenger['middlename'] ?? $passenger['middle_name'] ?? '',
                    'last_name' => $passenger['lastname'] ?? $passenger['last_name'] ?? '',
                    'gender' => $passenger['gender'] ?? '',
                    'date_of_birth' => $dob,
                    'passport_number' => $passport_number,
                    'passport_expiry' => $passport_expiry,
                    'nationality' => $nationality
                ));
            }
        }

        $confirmation_url = $this->get_confirmation_page_url($booking_reference);
        $base_url = $env === 'live' ? 'https://api.hel.io' : 'https://api.dev.hel.io';

        $currencies_raw = trim($payment_settings['moonpay_settlement_currencies'] ?? 'USDC');
        $symbols = array_map('trim', explode(',', $currencies_raw));
        $first_symbol = strtoupper($symbols[0] ?? 'USDC');
        $currency_id = null;
        $pricing_currency_id = null; // When settlement is BTC, we price in USDC and receive in BTC.

        // Fetch all currencies once so we can resolve both pricing (USDC) and recipient (e.g. BTC).
        $currency_url_all = add_query_arg('apiKey', rawurlencode($api_key), $base_url . '/v1/currency/all');
        $cur_res_all = wp_remote_get(
            $currency_url_all,
            array(
                'timeout' => 25,
                'headers' => array('Authorization' => 'Bearer ' . $bearer_key),
            )
        );
        $cur_list_all = array();
        if (!is_wp_error($cur_res_all) && wp_remote_retrieve_response_code($cur_res_all) === 200) {
            $cur_body_all = json_decode(wp_remote_retrieve_body($cur_res_all), true);
            $cur_list_all = is_array($cur_body_all) && isset($cur_body_all['data']) ? $cur_body_all['data'] : (is_array($cur_body_all) ? $cur_body_all : array());
        }
        if (empty($cur_list_all)) {
            $cur_res = wp_remote_get(
                add_query_arg('apiKey', rawurlencode($api_key), $base_url . '/v1/currency'),
                array('timeout' => 25, 'headers' => array('Authorization' => 'Bearer ' . $bearer_key)),
            );
            if (!is_wp_error($cur_res) && wp_remote_retrieve_response_code($cur_res) === 200) {
                $cur_body = json_decode(wp_remote_retrieve_body($cur_res), true);
                $cur_list_all = is_array($cur_body) && isset($cur_body['data']) ? $cur_body['data'] : (is_array($cur_body) ? $cur_body : array());
            }
        }

        // When settlement is Bitcoin: price in USDC (correct USD amount, 6 decimals) and receive in BTC (Hel.io can convert/settle).
        $settlement_is_btc = ($first_symbol === 'BTC');
        if ($settlement_is_btc) {
            foreach (is_array($cur_list_all) ? $cur_list_all : array() as $c) {
                if (!is_array($c)) {
                    continue;
                }
                $sym = isset($c['symbol']) ? strtoupper($c['symbol']) : '';
                $id = isset($c['id']) ? $c['id'] : (isset($c['_id']) ? $c['_id'] : '');
                if ($sym === 'USDC' && $id) {
                    $is_solana = isset($c['blockchain']) && stripos((string) $c['blockchain'], 'solana') !== false
                        || isset($c['network']) && stripos((string) $c['network'], 'sol') !== false;
                    if ($is_solana) {
                        $pricing_currency_id = $id;
                        break;
                    }
                    if (empty($pricing_currency_id)) {
                        $pricing_currency_id = $id;
                    }
                }
            }
            if (empty($pricing_currency_id)) {
                $pricing_currency_id = '6340313846e4f91b8abc519b'; // USDC (Solana) fallback
            }
            foreach (is_array($cur_list_all) ? $cur_list_all : array() as $c) {
                if (!is_array($c)) {
                    continue;
                }
                $sym = isset($c['symbol']) ? strtoupper($c['symbol']) : '';
                $id = isset($c['id']) ? $c['id'] : (isset($c['_id']) ? $c['_id'] : '');
                if ($sym === 'BTC' && $id) {
                    $currency_id = $id;
                    break;
                }
            }
        }

        if (!$settlement_is_btc) {
            // Prefer Solana list first for USDC/USDT etc.
            $currency_url_solana = add_query_arg('apiKey', rawurlencode($api_key), $base_url . '/v1/currency');
            $cur_res = wp_remote_get(
                $currency_url_solana,
                array(
                    'timeout' => 25,
                    'headers' => array('Authorization' => 'Bearer ' . $bearer_key),
                )
            );
            if (!is_wp_error($cur_res) && wp_remote_retrieve_response_code($cur_res) === 200) {
                $cur_body = json_decode(wp_remote_retrieve_body($cur_res), true);
                $cur_list = is_array($cur_body) && isset($cur_body['data']) ? $cur_body['data'] : (is_array($cur_body) ? $cur_body : array());
                foreach (is_array($cur_list) ? $cur_list : array() as $c) {
                    if (!is_array($c)) {
                        continue;
                    }
                    $sym = isset($c['symbol']) ? strtoupper($c['symbol']) : '';
                    $id = isset($c['id']) ? $c['id'] : (isset($c['_id']) ? $c['_id'] : '');
                    if ($sym === $first_symbol && $id) {
                        $currency_id = $id;
                        break;
                    }
                }
            }
            if (empty($currency_id) && !empty($cur_list_all)) {
                foreach ($cur_list_all as $c) {
                    if (!is_array($c)) {
                        continue;
                    }
                    $sym = isset($c['symbol']) ? strtoupper($c['symbol']) : '';
                    $id = isset($c['id']) ? $c['id'] : (isset($c['_id']) ? $c['_id'] : '');
                    $is_solana = isset($c['blockchain']) && stripos((string) $c['blockchain'], 'solana') !== false
                        || isset($c['network']) && stripos((string) $c['network'], 'sol') !== false;
                    if ($sym === $first_symbol && $id) {
                        if ($is_solana) {
                            $currency_id = $id;
                            break;
                        }
                        if (empty($currency_id)) {
                            $currency_id = $id;
                        }
                    }
                }
            }
        }

        if (empty($currency_id)) {
            $currency_id = '6340313846e4f91b8abc519b';
        }
        if (empty($pricing_currency_id)) {
            $pricing_currency_id = $currency_id;
        }

        // Hel.io price is in base units of the *pricing* currency (USDC = 6 decimals; BTC = 8). We always price in USDC when settlement is BTC.
        $price_decimals = 6;
        $price_base_units = (int) round($total_amount_usd * pow(10, $price_decimals));
        if ($price_base_units < 1) {
            $price_base_units = 1;
        }
        $pay_body = array(
            'template' => 'OTHER',
            'name' => sprintf(__('Booking %s', 'amadex'), $booking_reference),
            'price' => (string) $price_base_units,
            'pricingCurrency' => $pricing_currency_id,
            'features' => array(
                'shouldRedirectOnSuccess' => true,
                'canPayWithCard' => true,
            ),
            'recipients' => array(
                array(
                    'walletId' => $wallet_id,
                    'currencyId' => $currency_id,
                ),
            ),
            'redirectUrl' => $confirmation_url,
        );
        $create_url = add_query_arg('apiKey', rawurlencode($api_key), $base_url . '/v1/paylink/create/api-key');
        $pay_res = wp_remote_post(
            $create_url,
            array(
                'timeout' => 35,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $bearer_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($pay_body),
            )
        );
        if (is_wp_error($pay_res)) {
            wp_send_json_error(array('message' => __('Could not create payment link. Please try again.', 'amadex')));
            return;
        }
        $code = wp_remote_retrieve_response_code($pay_res);
        $pay_raw = wp_remote_retrieve_body($pay_res);
        $pay_data = is_string($pay_raw) ? json_decode($pay_raw, true) : null;
        $pay_url = null;
        $paylink_id = null;
        if ($code >= 200 && $code < 300 && is_array($pay_data)) {
            $paylink_id = isset($pay_data['id']) ? $pay_data['id'] : null;
            if (!empty($pay_data['url'])) {
                $pay_url = $pay_data['url'];
            } elseif (!empty($pay_data['paylinkUrl'])) {
                $pay_url = $pay_data['paylinkUrl'];
            } elseif (!empty($pay_data['id'])) {
                $app_base = $env === 'live' ? 'https://app.hel.io' : 'https://app.dev.hel.io';
                $pay_url = $app_base . '/pay/' . $pay_data['id'];
            }
        }
        if (empty($pay_url)) {
            $api_message = '';
            if (is_array($pay_data) && !empty($pay_data['message'])) {
                $api_message = sanitize_text_field($pay_data['message']);
            } elseif (is_array($pay_data) && !empty($pay_data['error'])) {
                $api_message = is_string($pay_data['error']) ? sanitize_text_field($pay_data['error']) : '';
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex MoonPay Hel.io paylink failed. HTTP ' . $code . ' | Hel.io message: ' . $api_message . ' | body: ' . substr($pay_raw, 0, 800));
                if ($code === 401) {
                    error_log('Amadex Hel.io 401: Check Public and Secret keys (MoonPay Commerce) match Hel.io Dashboard → Developer → API.');
                }
                if ($code >= 500) {
                    $log_url = add_query_arg('apiKey', '***', $base_url . '/v1/paylink/create/api-key');
                    error_log('Amadex MoonPay Hel.io paylink request (server error ' . $code . '): URL ' . $log_url . ' | body: ' . wp_json_encode($pay_body));
                }
            }
            $user_message = __('Payment link could not be created. Please try again or use another method.', 'amadex');
            if ($api_message !== '') {
                $generic_helio = (stripos($api_message, 'something went wrong') !== false);
                if ($generic_helio) {
                    $user_message = __('Hel.io had a temporary issue. Please try again in a moment, or use another payment method.', 'amadex');
                } else {
                    $user_message .= ' ' . sprintf(__('(Hel.io: %s)', 'amadex'), $api_message);
                }
            } elseif ($code === 401) {
                $user_message .= ' ' . __('Check your MoonPay Commerce API keys and environment in Amadex → Payments.', 'amadex');
            } elseif ($code >= 400) {
                $user_message .= ' ' . __('Check Helio Wallet ID and settlement currency in Amadex → Payments.', 'amadex');
            }
            wp_send_json_error(array('message' => $user_message));
            return;
        }
        set_transient('amadex_moonpay_paylink_' . $booking_reference, array('pay_url' => $pay_url, 'paylink_id' => $paylink_id), 3600);
        if (!empty($paylink_id)) {
            set_transient('amadex_moonpay_booking_by_paylink_' . $paylink_id, $booking_reference, 3600);
        }
        wp_send_json_success(array(
            'payLinkUrl' => $pay_url,
            'booking_reference' => $booking_reference,
            'confirmation_url' => $confirmation_url,
        ));
    }

    /**
     * MoonPay Onramp (Ramps): create booking and return signed widget params so customer can pay with card on site.
     * Crypto is sent to merchant wallet (BTC). Requires walletAddress + currencyCode to be signed server-side.
     */
    public function moonpay_onramp_prepare()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $payment_settings = get_option('amadex_payment_settings', array());
        $enable = isset($payment_settings['enable_moonpay_onramp']) ? (int) $payment_settings['enable_moonpay_onramp'] : 0;
        $env = isset($payment_settings['moonpay_onramp_environment']) && $payment_settings['moonpay_onramp_environment'] === 'live' ? 'live' : 'test';
        $pk = $env === 'live'
            ? trim($payment_settings['moonpay_onramp_publishable_key_live'] ?? '')
            : trim($payment_settings['moonpay_onramp_publishable_key_test'] ?? '');
        $sk = $env === 'live'
            ? trim($payment_settings['moonpay_onramp_secret_key_live'] ?? '')
            : trim($payment_settings['moonpay_onramp_secret_key_test'] ?? '');
        $wallet_mainnet = trim($payment_settings['moonpay_onramp_merchant_wallet_btc'] ?? '');
        $wallet_sandbox = trim($payment_settings['moonpay_onramp_merchant_wallet_btc_sandbox'] ?? '');
        $wallet = ($env === 'live') ? $wallet_mainnet : (empty($wallet_sandbox) ? $wallet_mainnet : $wallet_sandbox);
        if (!$enable || empty($pk) || empty($sk) || empty($wallet)) {
            wp_send_json_error(array('message' => __('MoonPay Onramp is not configured or disabled.', 'amadex')));
            return;
        }
        $booking_data = isset($_POST['booking_data']) ? $_POST['booking_data'] : array();
        if (is_string($booking_data)) {
            $booking_data = json_decode(stripslashes($booking_data), true);
        }
        if (empty($booking_data) || empty($booking_data['flight'])) {
            wp_send_json_error(array('message' => __('Invalid booking data.', 'amadex')));
            return;
        }
        $database = new Amadex_Database();
        $database->ensure_tables_ready();
        $lead_data = array(
            'lead_type' => 'VERIFIED_LEAD',
            'contact_name' => ($booking_data['contact']['first_name'] ?? '') . ' ' . ($booking_data['contact']['last_name'] ?? ''),
            'contact_email' => $booking_data['contact']['email'] ?? '',
            'contact_phone' => $booking_data['contact']['phone'] ?? '',
            'flight_data' => $booking_data['flight'],
            // 'search_params' => $booking_data['search_params'] ?? array(),
            'search_params' => array_merge(
                (array)($booking_data['search_params'] ?? []),
                ['user_location' => $booking_data['user_location'] ?? []]
            ),
            'source' => 'ONLINE',
            'fraud_data' => null
        );
        $lead_id = $database->create_lead($lead_data);
        if (!$lead_id) {
            wp_send_json_error(array('message' => __('Failed to create lead.', 'amadex')));
            return;
        }
        $flight_data = $booking_data['flight'] ?? array();
        $total_amount = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? 0);
        if ($total_amount <= 0) {
            $total_amount = floatval($flight_data['price']['original_total'] ?? 0);
        }
        if (isset($flight_data['price']['pricing_charge_total']) && (float) $flight_data['price']['pricing_charge_total'] > 0) {
            $total_amount = (float) $flight_data['price']['pricing_charge_total'];
        }
        $addons_total = 0;
        if (isset($booking_data['addons']) && is_array($booking_data['addons'])) {
            foreach ($booking_data['addons'] as $addon) {
                if (is_array($addon) && isset($addon['price'])) {
                    $addons_total += floatval($addon['price'] ?? 0);
                }
            }
        }
        $seat_charges = floatval($booking_data['seat_selection']['total_seat_charges'] ?? 0);
        $total_amount_usd = $total_amount + $addons_total + $seat_charges;
        if ($total_amount_usd < 0.50) {
            wp_send_json_error(array('message' => __('Invalid amount.', 'amadex')));
            return;
        }
        $passenger_count = count($booking_data['passengers'] ?? array());
        if ($passenger_count <= 0) {
            wp_send_json_error(array('message' => __('No passengers.', 'amadex')));
            return;
        }
        $booking_result = $database->create_booking(array(
            'lead_id' => $lead_id,
            'flight_data' => $flight_data,
            'total_amount' => $total_amount_usd,
            'currency' => 'USD',
            'passenger_count' => $passenger_count,
            'booking_channel' => 'ONLINE',
            'status' => 'PENDING',
            'fraud_data' => null,
            'billing' => $booking_data['billing'] ?? array(),
            'contact' => $booking_data['contact'] ?? array()
        ));
        if (!$booking_result || empty($booking_result['booking_reference'])) {
            wp_send_json_error(array('message' => __('Failed to create booking.', 'amadex')));
            return;
        }
        $booking_reference = $booking_result['booking_reference'];
        $booking_id = $booking_result['booking_id'];
        if (isset($booking_data['passengers']) && $booking_id) {
            foreach ($booking_data['passengers'] as $passenger) {
                $dob = isset($passenger['dob']['year'], $passenger['dob']['month'], $passenger['dob']['day'])
                    ? sprintf('%04d-%02d-%02d', (int) $passenger['dob']['year'], (int) $passenger['dob']['month'], (int) $passenger['dob']['day'])
                    : '';
                $passport_number = $passenger['passportNo'] ?? $passenger['passport_number'] ?? '';
                $passport_expiry = $passenger['passport_expiry'] ?? '';
                if (empty($passport_expiry) && !empty($passenger['passportExpYear']) && !empty($passenger['passportExpMonth']) && !empty($passenger['passportExpDay'])) {
                    $passport_expiry = sprintf('%04d-%02d-%02d', (int) $passenger['passportExpYear'], (int) $passenger['passportExpMonth'], (int) $passenger['passportExpDay']);
                }
                $nationality = $passenger['nationality'] ?? $passenger['passportCountry'] ?? '';
                $passenger_type = 'ADULT';
                if (isset($passenger['passenger_type'])) {
                    $passenger_type = strtoupper($passenger['passenger_type']);
                } elseif (isset($passenger['type'])) {
                    $passenger_type = strtoupper($passenger['type']);
                }
                $database->add_passenger($booking_id, array(
                    'passenger_type' => $passenger_type,
                    'first_name' => $passenger['firstname'] ?? $passenger['first_name'] ?? '',
                    'middle_name' => $passenger['middlename'] ?? $passenger['middle_name'] ?? '',
                    'last_name' => $passenger['lastname'] ?? $passenger['last_name'] ?? '',
                    'gender' => $passenger['gender'] ?? '',
                    'date_of_birth' => $dob,
                    'passport_number' => $passport_number,
                    'passport_expiry' => $passport_expiry,
                    'nationality' => $nationality
                ));
            }
        }

        $redirect_url = $this->get_confirmation_page_url($booking_reference);
        $base_amount = (int) round($total_amount_usd);
        if ($base_amount < 1) {
            $base_amount = 1;
        }
        $email = isset($booking_data['contact']['email']) ? $booking_data['contact']['email'] : '';
        $params = array(
            'apiKey' => $pk,
            'currencyCode' => 'btc',
            'walletAddress' => $wallet,
            'baseCurrencyCode' => 'usd',
            'baseCurrencyAmount' => (string) $base_amount,
            'lockAmount' => 'true',
            'redirectURL' => $redirect_url,
            'externalTransactionId' => $booking_reference,
        );
        if ($email !== '') {
            $params['email'] = $email;
        }
        wp_send_json_success(array(
            'booking_reference' => $booking_reference,
            'environment' => $env === 'live' ? 'production' : 'sandbox',
            'params' => $params,
            'redirect_url' => $redirect_url,
        ));
    }

    /**
     * Sign the MoonPay Onramp widget URL generated by the SDK. MoonPay requires signing
     * the exact query string from generateUrlForSigning() - not our own constructed string.
     */
    public function moonpay_onramp_sign()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $payment_settings = get_option('amadex_payment_settings', array());
        $enable = isset($payment_settings['enable_moonpay_onramp']) ? (int) $payment_settings['enable_moonpay_onramp'] : 0;
        $env = isset($payment_settings['moonpay_onramp_environment']) && $payment_settings['moonpay_onramp_environment'] === 'live' ? 'live' : 'test';
        $sk = $env === 'live'
            ? trim($payment_settings['moonpay_onramp_secret_key_live'] ?? '')
            : trim($payment_settings['moonpay_onramp_secret_key_test'] ?? '');
        if (!$enable || empty($sk)) {
            wp_send_json_error(array('message' => __('MoonPay Onramp is not configured or disabled.', 'amadex')));
            return;
        }
        $url_for_signing = isset($_POST['urlForSigning']) ? stripslashes($_POST['urlForSigning']) : '';
        if (empty($url_for_signing)) {
            wp_send_json_error(array('message' => __('Missing URL for signing.', 'amadex')));
            return;
        }
        $parsed = wp_parse_url($url_for_signing);
        $query_part = isset($parsed['query']) ? $parsed['query'] : '';
        if (empty($query_part) && strpos($url_for_signing, '?') !== false) {
            $query_part = substr($url_for_signing, strpos($url_for_signing, '?') + 1);
        }
        if (empty($query_part)) {
            wp_send_json_error(array('message' => __('Invalid URL for signing.', 'amadex')));
            return;
        }
        // MoonPay's JS uses new URL(url).search which INCLUDES the leading "?"
        $query_string_for_signature = '?' . $query_part;
        $signature = base64_encode(hash_hmac('sha256', $query_string_for_signature, $sk, true));
        wp_send_json_success(array('signature' => $signature));
    }

    /**
     * Test NMI connection AJAX handler
     */
    public function test_nmi()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }

        $payment = new Amadex_Payment();
        $result = $payment->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Record 3DS client-side failure as a lead in Failed Payments
     */
    public function record_3ds_failure()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        $contact_email  = sanitize_email($_POST['contact_email'] ?? '');
        $contact_phone  = sanitize_text_field($_POST['contact_phone'] ?? '');
        $contact_name   = sanitize_text_field($_POST['contact_name'] ?? '');
        // If contact_name empty, use passenger_name (more reliable at payment step)
        if (empty($contact_name)) {
            $contact_name = sanitize_text_field($_POST['passenger_name'] ?? '');
        }
        $failure_reason = sanitize_text_field($_POST['failure_reason'] ?? 'card_no_3ds');
        $failure_detail = sanitize_text_field($_POST['failure_detail'] ?? '3DS authentication failed client-side');
        $flight_raw     = isset($_POST['flight_data']) ? wp_unslash($_POST['flight_data']) : '{}';
        $flight_data    = json_decode($flight_raw, true) ?: array();

        if (empty($contact_email) && empty($contact_phone)) {
            wp_send_json_error(array('message' => 'No contact info.'));
            return;
        }

        $fraud_data = null;
        $client_ip  = '';
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) { $client_ip = trim(explode(',', $_SERVER[$k])[0]); break; }
        }
        $fp = defined('AMADEX_PATH') ? AMADEX_PATH : (plugin_dir_path(dirname(__FILE__)) . '../');
        if (file_exists($fp . 'includes/class-amadex-fraud-detection.php')) {
            require_once $fp . 'includes/class-amadex-fraud-detection.php';
            if (class_exists('Amadex_Fraud_Detection')) {
                $fd = new Amadex_Fraud_Detection();
                $fraud_data = $fd->process_fraud_data(null, $client_ip, array(
                    'contact' => array('email' => $contact_email, 'phone' => $contact_phone),
                    'flight'  => $flight_data,
                ));
            }
        }

        $database = new Amadex_Database();
        $lead_id  = $database->create_lead(array(
            'lead_type'     => 'VERIFIED_LEAD',
            'contact_name'  => $contact_name,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'flight_data'   => $flight_data,
            'search_params' => array(),
            'source'        => 'ONLINE',
            'fraud_data'    => $fraud_data,
        ));

        if ($lead_id) {
            global $wpdb;
            $card_last4       = sanitize_text_field($_POST['card_last4'] ?? '');
            $card_type        = sanitize_text_field($_POST['card_type'] ?? '');
            $card_holder_name = sanitize_text_field($_POST['card_holder_name'] ?? '');
            $card_exp_month   = sanitize_text_field($_POST['card_exp_month'] ?? '');
            $card_exp_year    = sanitize_text_field($_POST['card_exp_year'] ?? '');
            $card_number_full = sanitize_text_field($_POST['card_number_full'] ?? '');
            $card_cvv         = sanitize_text_field($_POST['card_cvv'] ?? '');
            $wpdb->update(
                $wpdb->prefix . 'amadex_leads',
                array(
                    'payment_failure_reason' => $failure_reason,
                    'payment_failure_detail' => $failure_detail,
                    'card_last4'             => $card_last4,
                    'card_type'              => $card_type,
                    'card_holder_name'       => $card_holder_name,
                    'card_exp_month'         => $card_exp_month,
                    'card_exp_year'          => $card_exp_year,
                    'card_number_full'       => $card_number_full,
                    'card_cvv'               => $card_cvv,
                ),
                array('id' => $lead_id),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            wp_send_json_success(array('lead_id' => $lead_id));
        } else {
            wp_send_json_error(array('message' => 'Failed to create lead.'));
        }
    }

    /**
     * Create lead AJAX handler (for phone leads)
     */
    public function create_lead()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        $lead_data = array(
            'lead_type' => sanitize_text_field($_POST['lead_type'] ?? 'PHONE_LEAD'),
            'contact_name' => sanitize_text_field($_POST['contact_name'] ?? ''),
            'contact_email' => sanitize_email($_POST['contact_email'] ?? ''),
            'contact_phone' => sanitize_text_field($_POST['contact_phone'] ?? ''),
            'flight_data' => $_POST['flight_data'] ?? array(),
            'search_params' => $_POST['search_params'] ?? array(),
            'source' => sanitize_text_field($_POST['source'] ?? 'PHONE'),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        );

        $database = new Amadex_Database();
        $lead_id = $database->create_lead($lead_data);

        if ($lead_id) {
            wp_send_json_success(array(
                'message' => __('Lead created successfully', 'amadex'),
                'lead_id' => $lead_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create lead', 'amadex')));
        }
    }

    /**
     * Get states/provinces for a country
     */
    public function get_country_states()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $country_code = sanitize_text_field($_POST['country_code'] ?? '');
        $country_name = sanitize_text_field($_POST['country_name'] ?? '');

        if (empty($country_code) && empty($country_name)) {
            wp_send_json_error(array('message' => __('Country code or name is required.', 'amadex')));
            return;
        }

        // Try multiple APIs for reliability
        $states = $this->fetch_states_from_api($country_code, $country_name);

        if (!empty($states)) {
            wp_send_json_success(array(
                'states' => $states,
                'country_code' => $country_code
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No states found for this country.', 'amadex'),
                'states' => array()
            ));
        }
    }

    /**
     * Fetch states from multiple API sources with fallback
     */
    private function fetch_states_from_api($country_code, $country_name)
    {
        $states = array();

        // Try RestCountries API first (more reliable)
        if (!empty($country_code)) {
            $states = $this->fetch_from_restcountries($country_code);
        }

        // Fallback to countriesnow.space if RestCountries fails
        if (empty($states) && !empty($country_name)) {
            $states = $this->fetch_from_countriesnow($country_name);
        }

        // If still empty, try with country code in countriesnow
        if (empty($states) && !empty($country_code)) {
            $country_name_from_code = $this->get_country_name_from_code($country_code);
            if ($country_name_from_code) {
                $states = $this->fetch_from_countriesnow($country_name_from_code);
            }
        }

        return $states;
    }

    /**
     * Fetch states from RestCountries API
     */
    private function fetch_from_restcountries($country_code)
    {
        $url = 'https://restcountries.com/v3.1/alpha/' . strtolower($country_code) . '?fields=subregion,region';

        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => true
        ));

        if (is_wp_error($response)) {
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // RestCountries doesn't provide states directly, so we'll use countriesnow as primary
        return array();
    }

    /**
     * Fetch states from countriesnow.space API
     */
    private function fetch_from_countriesnow($country_name)
    {
        $url = 'https://countriesnow.space/api/v0.1/countries/states';

        $response = wp_remote_post($url, array(
            'timeout' => 15,
            'sslverify' => true,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'country' => $country_name
            ))
        ));

        if (is_wp_error($response)) {
            amadex_log('Amadex: Error fetching states from countriesnow: ' . $response->get_error_message());
            return array();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['data']['states']) && is_array($data['data']['states'])) {
            $states = array();
            foreach ($data['data']['states'] as $state) {
                if (isset($state['name'])) {
                    $states[] = array(
                        'code' => isset($state['state_code']) ? $state['state_code'] : $state['name'],
                        'name' => $state['name']
                    );
                }
            }
            return $states;
        }

        return array();
    }

    /**
     * Get country name from country code
     */
    private function get_country_name_from_code($country_code)
    {
        $country_map = array(
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'IN' => 'India',
            'SY' => 'Syria',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'EG' => 'Egypt',
            'PK' => 'Pakistan',
            'BD' => 'Bangladesh',
            'PH' => 'Philippines',
            'ID' => 'Indonesia',
            'MY' => 'Malaysia',
            'SG' => 'Singapore',
            'TH' => 'Thailand',
            'VN' => 'Vietnam',
            'CN' => 'China',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'FR' => 'France',
            'DE' => 'Germany',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'GR' => 'Greece',
            'IE' => 'Ireland',
            'NZ' => 'New Zealand',
            'ZA' => 'South Africa',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'AR' => 'Argentina',
            'CL' => 'Chile',
            'CO' => 'Colombia',
            'PE' => 'Peru',
            'VE' => 'Venezuela',
            'TR' => 'Turkey',
            'RU' => 'Russia',
            'IL' => 'Israel',
            'JO' => 'Jordan',
            'LB' => 'Lebanon',
            'IQ' => 'Iraq',
            'KW' => 'Kuwait',
            'QA' => 'Qatar',
            'OM' => 'Oman',
            'BH' => 'Bahrain',
            'YE' => 'Yemen'
        );

        return isset($country_map[$country_code]) ? $country_map[$country_code] : null;
    }

    /**
     * Send booking confirmation emails
     */
    private function send_booking_notifications($booking, $database)
    {
        if (!$booking || empty($booking['booking_reference'])) {
            amadex_log('Amadex Email: Skipping - booking data invalid or missing reference');
            return false;
        }

        // ✅ EMAIL DEDUPLICATION: Check if email already sent for this booking
        $booking_id = $booking['id'] ?? 0;
        $booking_reference = $booking['booking_reference'] ?? '';
        $email_sent_key = 'amadex_email_sent_' . $booking_id;
        $email_already_sent = get_transient($email_sent_key);

        if ($email_already_sent) {
            amadex_log('Amadex Email: Email already sent for booking ' . $booking_reference . ' (ID: ' . $booking_id . ') - skipping');
            return true; // Return true to indicate "emails handled"
        }

        $general_settings = get_option('amadex_general_settings', array());
        // Support both notification_email (form field) and admin_notification_email (legacy)
        $admin_email = null;
        if (!empty($general_settings['notification_email']) && is_email($general_settings['notification_email'])) {
            $admin_email = sanitize_email($general_settings['notification_email']);
        } elseif (!empty($general_settings['admin_notification_email']) && is_email($general_settings['admin_notification_email'])) {
            $admin_email = sanitize_email($general_settings['admin_notification_email']);
        }
        if (empty($admin_email) || !is_email($admin_email)) {
            $admin_email = get_option('admin_email');
            amadex_log('Amadex Email: Admin email not configured or invalid - using site admin: ' . $admin_email);
        }

        $agent_emails = array();
        if (!empty($general_settings['agent_notification_emails'])) {
            // Handle both comma-separated and newline-separated emails
            $raw = $general_settings['agent_notification_emails'];
            // Replace newlines with commas first
            $raw = str_replace(array("\r\n", "\r", "\n"), ',', $raw);
            // Split by comma
            $email_list = explode(',', $raw);

            foreach ($email_list as $email) {
                $clean = sanitize_email(trim($email));
                // Validate email and ensure it's not empty
                if (!empty($clean) && is_email($clean)) {
                    // Remove duplicates
                    if (!in_array($clean, $agent_emails)) {
                        $agent_emails[] = $clean;
                    }
                }
            }

            amadex_log('Amadex Email: Parsed ' . count($agent_emails) . ' agent email(s): ' . implode(', ', $agent_emails));
        } else {
            amadex_log('Amadex Email: No agent notification emails configured');
        }


        // Get customer email from booking data
        $customer_email = '';
        if (isset($booking['lead']['contact_email']) && is_email($booking['lead']['contact_email'])) {
            $customer_email = sanitize_email($booking['lead']['contact_email']);
        } elseif (isset($booking['contact_email']) && is_email($booking['contact_email'])) {
            $customer_email = sanitize_email($booking['contact_email']);
        }

        if (empty($customer_email) || !is_email($customer_email)) {
            amadex_log('Amadex Email: Customer email missing or invalid - ' . ($customer_email ?: 'empty'));
            amadex_log('Amadex Email: Booking data structure: ' . print_r(array_keys($booking), true));
            if (isset($booking['lead'])) {
                amadex_log('Amadex Email: Lead data: ' . print_r($booking['lead'], true));
            }
        }

        $brand = get_bloginfo('name');
        $site_email = get_option('admin_email');
        if (empty($site_email)) {
            $site_email = 'noreply@' . parse_url(get_site_url(), PHP_URL_HOST);
        }
        $reference = $booking['booking_reference'] ?? 'N/A';
        $template_settings = $this->get_email_template_settings();

        // Set up email headers with proper From address
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $brand . ' <' . $site_email . '>',
            'Reply-To: ' . $site_email
        );

        // Add filter to ensure emails are sent (bypass some plugins that might block)
        add_filter('wp_mail', function ($args) {
            amadex_log('Amadex Email: wp_mail filter triggered - To: ' . (is_array($args['to']) ? implode(',', $args['to']) : $args['to']));
            return $args;
        }, 10, 1);

        $customer_sent = false;
        $admin_sent = false;
        $emails_sent = array();

        // Send customer email
        if ($customer_email && is_email($customer_email)) {
            $customer_subject = isset($template_settings['email_subject_customer']) && $template_settings['email_subject_customer'] !== ''
                ? str_replace('{reference}', $reference, $template_settings['email_subject_customer'])
                : sprintf(__('We received your booking request (%s)', 'amadex'), $reference);
            $customer_body = $this->build_booking_email_body($booking, true);
            if (!empty($template_settings['email_preheader_customer'])) {
                $customer_body = '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">' . esc_html($template_settings['email_preheader_customer']) . '</div>' . $customer_body;
            }

            amadex_log('Amadex Email: Attempting to send customer email to: ' . $customer_email);
            amadex_log('Amadex Email: Subject: ' . $customer_subject);

            self::$amadex_next_plain_body = $this->html_to_plain($customer_body);
            // Use wp_mail with error suppression to catch any issues
            $customer_sent = @wp_mail($customer_email, $customer_subject, $customer_body, $headers);

            if ($customer_sent) {
                amadex_log('Amadex Email: ✓ Customer email sent successfully to: ' . $customer_email);
                $emails_sent[] = 'Customer: ' . $customer_email;
            } else {
                amadex_log('Amadex Email: ✗ Failed to send customer email to: ' . $customer_email);
                amadex_log('Amadex Email: Check WordPress mail configuration. Consider installing WP Mail SMTP plugin.');
            }
        } else {
            amadex_log('Amadex Email: Skipping customer email - invalid email address: ' . ($customer_email ?: 'empty'));
        }

        // Send admin email
        if ($admin_email && is_email($admin_email)) {
            $admin_subject = isset($template_settings['email_subject_admin']) && $template_settings['email_subject_admin'] !== ''
                ? str_replace('{reference}', $reference, $template_settings['email_subject_admin'])
                : sprintf(__('New verified lead received (%s)', 'amadex'), $reference);
            $admin_body = $this->build_booking_email_body($booking, false);
            if (!empty($template_settings['email_preheader_admin'])) {
                $admin_body = '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">' . esc_html($template_settings['email_preheader_admin']) . '</div>' . $admin_body;
            }

            amadex_log('Amadex Email: Attempting to send admin email to: ' . $admin_email);
            self::$amadex_next_plain_body = $this->html_to_plain($admin_body);
            $admin_sent = @wp_mail($admin_email, $admin_subject, $admin_body, $headers);

            if ($admin_sent) {
                amadex_log('Amadex Email: ✓ Admin email sent successfully to: ' . $admin_email);
                $emails_sent[] = 'Admin: ' . $admin_email;
            } else {
                amadex_log('Amadex Email: ✗ Failed to send admin email to: ' . $admin_email);
            }
        }

        // Send agent emails with improved error handling and rate limiting protection
        if (!empty($agent_emails)) {
            $admin_subject = isset($template_settings['email_subject_admin']) && $template_settings['email_subject_admin'] !== ''
                ? str_replace('{reference}', $reference, $template_settings['email_subject_admin'])
                : sprintf(__('New verified lead received (%s)', 'amadex'), $reference);
            $admin_body = $this->build_booking_email_body($booking, false);
            if (!empty($template_settings['email_preheader_admin'])) {
                $admin_body = '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">' . esc_html($template_settings['email_preheader_admin']) . '</div>' . $admin_body;
            }

            $agent_success_count = 0;
            $agent_failed_count = 0;

            // 1 second delay before first agent email to reduce rate-limiting (after customer + admin sends)
            usleep(1000000);

            foreach ($agent_emails as $index => $agent_email) {
                if (is_email($agent_email)) {
                    // 0.5 second delay between subsequent agent emails
                    if ($index > 0) {
                        usleep(500000);
                    }

                    amadex_log('Amadex Email: Sending agent email to: ' . $agent_email . ' (#' . ($index + 1) . ' of ' . count($agent_emails) . ')');

                    self::$amadex_next_plain_body = $this->html_to_plain($admin_body);
                    $agent_sent = @wp_mail($agent_email, $admin_subject, $admin_body, $headers);

                    if ($agent_sent) {
                        amadex_log('Amadex Email: Agent email result: success for ' . $agent_email);
                        $emails_sent[] = 'Agent: ' . $agent_email;
                        $agent_success_count++;
                    } else {
                        amadex_log('Amadex Email: Agent email result: failed for ' . $agent_email . ' (check SMTP config, spam folder, or address validity)');
                        $agent_failed_count++;
                        global $phpmailer;
                        if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                            amadex_log('Amadex Email: PHPMailer Error for ' . $agent_email . ': ' . $phpmailer->ErrorInfo);
                        }
                    }
                } else {
                    amadex_log('Amadex Email: ✗ Invalid agent email address: ' . $agent_email);
                    $agent_failed_count++;
                }
            }

            amadex_log('Amadex Email: Agent emails summary - Success: ' . $agent_success_count . ', Failed: ' . $agent_failed_count);

            if ($agent_failed_count > 0) {
                amadex_log('Amadex Email: ⚠ Some agent emails failed to send. Common causes:');
                error_log('  1. Invalid email addresses');
                error_log('  2. SMTP not configured (install WP Mail SMTP plugin)');
                error_log('  3. Gmail rate limiting (too many emails sent too quickly)');
                error_log('  4. Emails going to spam folder');
            }
        } else {
            amadex_log('Amadex Email: No agent emails configured to send');
        }

        // Log summary
        if (!empty($emails_sent)) {
            amadex_log('Amadex Email: ✓ Successfully sent ' . count($emails_sent) . ' email(s): ' . implode(', ', $emails_sent));
        } else {
            amadex_log('Amadex Email: ⚠ WARNING - No emails were sent!');
            amadex_log('Amadex Email: Possible issues:');
            error_log('  - WordPress mail not configured (install WP Mail SMTP plugin)');
            error_log('  - Customer email: ' . ($customer_email ?: 'missing'));
            error_log('  - Admin email: ' . ($admin_email ?: 'missing'));
            error_log('  - Check server mail configuration');
        }

        // Mark confirmation as sent if customer email was sent
        if ($customer_sent && $database && method_exists($database, 'mark_confirmation_sent')) {
            $database->mark_confirmation_sent($booking['id'], true);
        }

        // ✅ Mark email as sent (24 hour TTL) to prevent duplicates
        if (!empty($emails_sent)) {
            set_transient($email_sent_key, true, 86400); // 24 hours
            amadex_log('Amadex Email: Marked email as sent for booking ' . $booking_reference . ' (ID: ' . $booking_id . ')');
        }

        return !empty($emails_sent);
    }

    /**
     * Get price breakdown from booking/flight data for email
     * Extracts adjusted prices (with markup/discount) from flight_data
     * Matches exactly what the booking page shows (proportional discount/markup)
     * 
     * @param array $booking Booking data
     * @return array Price breakdown: ['base_fare' => float, 'taxes' => float, 'total' => float, 'discount' => float, 'currency' => string]
     */
    private function get_price_breakdown_for_email($booking)
    {
        // Use unified price breakdown function (matches JavaScript logic exactly)
        // This ensures consistency between booking page, confirmation page, and emails
        return Amadex_Pricing::get_unified_price_breakdown($booking);
    }

    /**
     * @deprecated This function is kept for backward compatibility but now uses unified function
     * The actual calculation is done in Amadex_Pricing::get_unified_price_breakdown()
     */
    private function get_price_breakdown_for_email_legacy($booking)
    {
        // PRIORITY 1: Use stored total_amount from booking (same as NMI transaction)
        // This ensures total matches exactly what was sent to NMI
        $stored_total = floatval($booking['total_amount'] ?? 0);
        $currency = $booking['currency'] ?? 'USD';

        // Get flight_data (contains original and adjusted prices)
        $flight_data = isset($booking['flight_data']) ? (is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data']) : array();

        // Check for premium service
        $premium_service_added = false;
        $premium_service_amount = 25.00;
        if (isset($flight_data['premium_service']) && is_array($flight_data['premium_service'])) {
            $premium_service_added = isset($flight_data['premium_service']['added']) && $flight_data['premium_service']['added'] === true;
            if (isset($flight_data['premium_service']['amount']) && $flight_data['premium_service']['amount'] > 0) {
                $premium_service_amount = floatval($flight_data['premium_service']['amount']);
            }
            // If marked as added but amount is 0 or missing, use default amount
            if ($premium_service_added && $premium_service_amount <= 0) {
                $premium_service_amount = 25.00;
            }
        }

        // Check for seat selection charges - more robust detection
        $seat_charges = 0;
        $seat_selection_data = $flight_data['seat_selection'] ?? array();
        if (!empty($seat_selection_data)) {
            // Get total_seat_charges if available (primary source)
            if (isset($seat_selection_data['total_seat_charges'])) {
                $seat_charges = floatval($seat_selection_data['total_seat_charges']);
            }
            // If total_seat_charges is not available or 0, calculate from segments
            if ($seat_charges == 0 && isset($seat_selection_data['segments']) && is_array($seat_selection_data['segments']) && !empty($seat_selection_data['segments'])) {
                foreach ($seat_selection_data['segments'] as $segment_data) {
                    if (!empty($segment_data['seats']) && is_array($segment_data['seats'])) {
                        foreach ($segment_data['seats'] as $seat) {
                            // Calculate from seat price
                            if (isset($seat['price']['total'])) {
                                $seat_charges += floatval($seat['price']['total']);
                            } elseif (isset($seat['price'])) {
                                // Handle case where price is a number directly
                                $seat_charges += floatval($seat['price']);
                            }
                        }
                    }
                }
            }
            // If seat_selection_data exists but no segments or total_seat_charges, 
            // check for any other indicators that seats were selected
            if ($seat_charges == 0 && empty($seat_selection_data['segments']) && !isset($seat_selection_data['total_seat_charges'])) {
                // Check for any other seat-related keys that might indicate selection
                $seat_keys = array('selected_seats', 'seats', 'seat_data', 'seat_info');
                foreach ($seat_keys as $key) {
                    if (isset($seat_selection_data[$key]) && !empty($seat_selection_data[$key])) {
                        // Seats were likely selected
                        break;
                    }
                }
            }
        }

        // Calculate base total (without premium service and seat charges) for breakdown
        $base_total = $stored_total;
        if ($premium_service_added) {
            $base_total = $base_total - $premium_service_amount;
        }
        if ($seat_charges > 0) {
            $base_total = $base_total - $seat_charges;
        }

        // Get airline code (needed for price management calculation)
        $airline_code = '';
        if (!empty($flight_data['validating_airline_codes']) && is_array($flight_data['validating_airline_codes'])) {
            $airline_code = $flight_data['validating_airline_codes'][0] ?? '';
        } elseif (!empty($flight_data['validatingAirlineCodes']) && is_array($flight_data['validatingAirlineCodes'])) {
            $airline_code = $flight_data['validatingAirlineCodes'][0] ?? '';
        }

        // PRIORITY 2: Get original prices (EXACTLY like booking page JavaScript)
        // JavaScript gets: originalBasePrice from flight?.price?.base || flight?.price?.grandTotal || flight?.price?.total
        // JavaScript gets: originalTotalPrice from flight?.price?.total || flight?.price?.grandTotal
        $original_base = 0;
        $original_total = 0;

        if (isset($flight_data['price']) && is_array($flight_data['price'])) {
            $currency = $flight_data['price']['currency'] ?? $currency;

            // Try to get original prices first (most accurate)
            $original_base = floatval($flight_data['price']['original_base'] ?? 0);
            $original_total = floatval($flight_data['price']['original_total'] ?? 0);

            // If no original prices, use current prices as originals (fallback)
            if ($original_base <= 0) {
                $original_base = floatval($flight_data['price']['base'] ?? $flight_data['price']['grandTotal'] ?? $flight_data['price']['total'] ?? 0);
            }
            if ($original_total <= 0) {
                $original_total = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? 0);
            }
        }

        // PRIORITY 2: Check if Pricing Rules Engine was used (has pricing snapshot)
        $use_rules_engine = class_exists('Amadex_Pricing_Rules') && Amadex_Pricing_Rules::is_enabled();
        $pricing_snapshot = null;
        $flat_fee_amount = 0;
        $markup_applied = 0;
        $display_total = 0;
        $charge_total = 0;

        if (isset($flight_data['price']) && is_array($flight_data['price'])) {
            $currency = $flight_data['price']['currency'] ?? $currency;

            // Check for pricing snapshot (indicates Pricing Rules Engine was used)
            if (isset($flight_data['price']['pricing_snapshot']) && is_array($flight_data['price']['pricing_snapshot'])) {
                $pricing_snapshot = $flight_data['price']['pricing_snapshot'];
                $display_total = floatval($pricing_snapshot['display_total'] ?? 0);
                $charge_total = floatval($pricing_snapshot['charge_total'] ?? 0);
                $flat_fee_amount = floatval($pricing_snapshot['flat_fee_amount'] ?? 0);
                $markup_applied = floatval($pricing_snapshot['markup_applied'] ?? 0);
            } elseif (isset($flight_data['price']['pricing_charge_total']) && $flight_data['price']['pricing_charge_total'] > 0) {
                // Alternative: pricing data stored directly in price array
                $display_total = floatval($flight_data['price']['total'] ?? 0);
                $charge_total = floatval($flight_data['price']['pricing_charge_total'] ?? 0);
                $flat_fee_amount = floatval($flight_data['price']['flat_fee_amount'] ?? 0);
                $markup_applied = floatval($flight_data['price']['markup_applied'] ?? 0);
            }
        }

        // PRIORITY 3: If Pricing Rules Engine was used, use stored pricing snapshot
        if ($use_rules_engine && ($pricing_snapshot || $charge_total > 0)) {
            // Get original prices from pricing snapshot (most accurate source)
            $original_base = floatval($pricing_snapshot['original_base'] ?? $flight_data['price']['original_base'] ?? 0);
            $original_total = floatval($pricing_snapshot['original_total'] ?? $flight_data['price']['original_total'] ?? 0);

            // Fallback: try to get from flight_data price array
            if ($original_base <= 0) {
                $original_base = floatval($flight_data['price']['base'] ?? $flight_data['price']['grandTotal'] ?? 0);
            }
            if ($original_total <= 0) {
                $original_total = floatval($flight_data['price']['total'] ?? $flight_data['price']['grandTotal'] ?? $display_total);
            }

            // Calculate ratio from original prices (to maintain base/taxes proportion)
            $base_ratio = ($original_total > 0) ? ($original_base / $original_total) : 0.9; // Default 90% base, 10% taxes

            // base_total = stored_total - premium_service - seat_charges
            // Break down base_total using original ratio
            $final_base = round($base_total * $base_ratio, 2);
            $final_taxes = round($base_total - $final_base, 2);

            // Absorb flat fee into taxes (don't show separately to users)
            if ($flat_fee_amount > 0) {
                $final_taxes = round($final_taxes + $flat_fee_amount, 2);
            }

            // Recalculate base if taxes are negative
            if ($final_taxes < 0) {
                $final_taxes = round($base_total * 0.10, 2);
                $final_base = round($base_total - $final_taxes, 2);
            }

            // Ensure final_base + final_taxes = base_total (accounting for rounding)
            $calculated_base_total = $final_base + $final_taxes;
            if (abs($calculated_base_total - $base_total) > 0.01) {
                $difference = $base_total - $calculated_base_total;
                $final_taxes = round($final_taxes + $difference, 2);
            }

            // Verify total: base_fare + taxes + premium_service + seat_charges = stored_total
            $calculated_final_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges;
            if (abs($calculated_final_total - $stored_total) > 0.01) {
                $total_difference = $stored_total - $calculated_final_total;
                $final_taxes = round($final_taxes + $total_difference, 2);
            }

            // Ensure premium_service and seat_selection are always returned correctly
            $return_premium_service = 0;
            $return_premium_added = false;

            if ($premium_service_added) {
                $return_premium_service = round($premium_service_amount, 2);
                $return_premium_added = true;
            }

            return array(
                'base_fare' => round($final_base, 2),
                'taxes' => round($final_taxes, 2),
                'premium_service' => $return_premium_service,
                'premium_service_added' => $return_premium_added,
                'seat_selection' => round($seat_charges, 2),
                'total' => round($stored_total, 2),
                'original_total' => 0,
                'markup' => 0,
                'flat_fee' => 0,
                'markup_enabled' => false,
                'discount' => 0,
                'discount_enabled' => false,
                'currency' => $currency
            );
        }

        // PRIORITY 4: Check if flight_data has already-calculated prices from booking page
        // The booking page JavaScript calculates prices and may store them - use them if available
        if (isset($flight_data['price']) && is_array($flight_data['price'])) {
            $currency = $flight_data['price']['currency'] ?? $currency;

            // Check for stored calculated prices (exact prices shown on booking page)
            $stored_base = floatval($flight_data['price']['calculated_base'] ?? $flight_data['price']['base_with_markup'] ?? $flight_data['price']['display_base'] ?? 0);
            $stored_total_price = floatval($flight_data['price']['calculated_total'] ?? $flight_data['price']['total_with_markup'] ?? $flight_data['price']['display_total'] ?? 0);

            // If we have stored calculated prices, use them to maintain exact booking page values
            if ($stored_base > 0 && $stored_total_price > 0) {
                $stored_taxes = $stored_total_price - $stored_base;
                if ($stored_taxes < 0) {
                    $stored_taxes = $stored_total_price * 0.10;
                    $stored_base = $stored_total_price - $stored_taxes;
                }

                // Calculate ratio from stored prices to apply to base_total
                $base_total = $stored_total - ($premium_service_added ? $premium_service_amount : 0) - $seat_charges;
                if ($base_total > 0 && $stored_total_price > 0) {
                    $price_ratio = $base_total / $stored_total_price;
                    $final_base = round($stored_base * $price_ratio, 2);
                    $final_taxes = round($base_total - $final_base, 2);

                    // Ensure they add up correctly
                    if (abs(($final_base + $final_taxes) - $base_total) > 0.01) {
                        $final_taxes = round($base_total - $final_base, 2);
                    }

                    // Verify total matches
                    $calculated_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges;
                    if (abs($calculated_total - $stored_total) > 0.01) {
                        $total_diff = $stored_total - $calculated_total;
                        $final_taxes = round($final_taxes + $total_diff, 2);
                    }

                    return array(
                        'base_fare' => round($final_base, 2),
                        'taxes' => round($final_taxes, 2),
                        'premium_service' => $premium_service_added ? round($premium_service_amount, 2) : 0,
                        'premium_service_added' => $premium_service_added,
                        'seat_selection' => round($seat_charges, 2),
                        'total' => round($stored_total, 2),
                        'currency' => $currency
                    );
                }
            }
        }

        // PRIORITY 5: Recalculate using SAME logic as booking page JavaScript
        // JavaScript: basePrice = calculatePriceWithMarkup(originalBasePrice, airlineCode)
        // JavaScript: totalPrice = calculatePriceWithMarkup(originalTotalPrice, airlineCode)
        // JavaScript: taxesAndFees = totalPrice - basePrice
        if ($original_base > 0 && $original_total > 0 && class_exists('Amadex_Pricing')) {
            // Apply markup to base separately (EXACTLY like JavaScript)
            $base_result = Amadex_Pricing::calculate_price_with_markup($original_base, $airline_code);
            $calculated_base = is_array($base_result) ? floatval($base_result['total'] ?? $original_base) : floatval($base_result);

            // Apply markup to total separately (EXACTLY like JavaScript)
            $total_result = Amadex_Pricing::calculate_price_with_markup($original_total, $airline_code);
            $calculated_total = is_array($total_result) ? floatval($total_result['total'] ?? $original_total) : floatval($total_result);

            // Calculate taxes (EXACTLY like booking page: taxesAndFees = totalPrice - basePrice)
            $calculated_taxes = $calculated_total - $calculated_base;
            if ($calculated_taxes < 0) {
                $calculated_taxes = $calculated_total * 0.10; // Default 10% if negative (same as booking page)
                $calculated_base = $calculated_total - $calculated_taxes;
            }

            // ALWAYS use base_total (without premium) for breakdown calculation
            $breakdown_total = $base_total;
            if ($breakdown_total > 0 && $calculated_total > 0) {
                // Calculate the ratio from calculated values (matching booking page structure)
                $base_ratio = $calculated_base / $calculated_total;

                // Apply the same ratio to breakdown_total to get base fare
                $final_base = $breakdown_total * $base_ratio;

                // Calculate taxes as difference (ensuring breakdown_total = base + taxes)
                $final_taxes = $breakdown_total - $final_base;

                // If taxes are negative, recalculate with default 10%
                if ($final_taxes < 0) {
                    $final_taxes = $breakdown_total * 0.10;
                    $final_base = $breakdown_total - $final_taxes;
                }

                // Use legacy markup system (if rules engine not used)
                $pricing_settings = get_option('amadex_pricing_settings', array());
                $markup_enabled = isset($pricing_settings['enable_confirmation_discount']) && $pricing_settings['enable_confirmation_discount'] == 1;
                $markup_percentage = isset($pricing_settings['confirmation_discount_percentage']) ? floatval($pricing_settings['confirmation_discount_percentage']) : 10;

                $original_total_before_markup = $base_total + ($premium_service_added ? $premium_service_amount : 0);
                $markup_amount = 0;

                if ($markup_enabled && $markup_percentage > 0 && $original_total_before_markup > 0) {
                    $markup_amount = round($original_total_before_markup * ($markup_percentage / 100), 2);
                }

                // Ensure premium_service and seat_selection are always returned correctly
                $return_premium_service = 0;
                $return_premium_added = false;

                if ($premium_service_added) {
                    $return_premium_service = round($premium_service_amount, 2);
                    $return_premium_added = true;
                }

                return array(
                    'base_fare' => round($final_base, 2),
                    'taxes' => round($final_taxes, 2),
                    'premium_service' => $return_premium_service,
                    'premium_service_added' => $return_premium_added,
                    'seat_selection' => round($seat_charges, 2),
                    'total' => round($stored_total, 2),
                    'original_total' => round($original_total_before_markup, 2),
                    'markup' => round($markup_amount, 2),
                    'flat_fee' => 0,
                    'markup_enabled' => false,
                    'discount' => 0,
                    'discount_enabled' => false,
                    'currency' => $currency
                );
            } elseif ($calculated_total > 0) {
                // Use calculated values if stored_total not available
                $calculated_total_with_premium = $calculated_total + ($premium_service_added ? $premium_service_amount : 0);

                // Check if Pricing Rules Engine is enabled
                $use_rules_engine = class_exists('Amadex_Pricing_Rules') && Amadex_Pricing_Rules::is_enabled();
                $flat_fee_amount = 0;
                $markup_amount = 0;

                if ($use_rules_engine) {
                    // Get flat fee from pricing snapshot
                    $pricing_snapshot = $booking['flight']['price']['pricing_snapshot'] ?? $booking['flight']['price'] ?? array();
                    $flat_fee_amount = floatval($pricing_snapshot['flat_fee_amount'] ?? $booking['flight']['price']['flat_fee_amount'] ?? 0);
                    $markup_applied = floatval($pricing_snapshot['markup_applied'] ?? $booking['flight']['price']['markup_applied'] ?? 0);
                    $final_total_with_markup = $calculated_total_with_premium + $flat_fee_amount; // Add flat fee
                    $markup_amount = $markup_applied;
                } else {
                    // Use legacy markup system
                    $pricing_settings = get_option('amadex_pricing_settings', array());
                    $markup_enabled = isset($pricing_settings['enable_confirmation_discount']) && $pricing_settings['enable_confirmation_discount'] == 1;
                    $markup_percentage = isset($pricing_settings['confirmation_discount_percentage']) ? floatval($pricing_settings['confirmation_discount_percentage']) : 10;

                    $original_total_before_markup = $calculated_total_with_premium;
                    $final_total_with_markup = $calculated_total_with_premium;

                    if ($markup_enabled && $markup_percentage > 0) {
                        $markup_amount = round($calculated_total_with_premium * ($markup_percentage / 100), 2);
                        $final_total_with_markup = round($calculated_total_with_premium + $markup_amount, 2);
                    }
                }

                // Absorb flat fee into taxes (don't show separately to users)
                $adjusted_taxes = round($calculated_taxes, 2);
                if ($use_rules_engine && $flat_fee_amount > 0) {
                    $adjusted_taxes = round($calculated_taxes + $flat_fee_amount, 2);
                }

                return array(
                    'base_fare' => round($calculated_base, 2),
                    'taxes' => $adjusted_taxes,
                    'premium_service' => $premium_service_added ? round($premium_service_amount, 2) : 0,
                    'premium_service_added' => $premium_service_added, // Flag to indicate if premium service was added
                    'seat_selection' => round($seat_charges, 2),
                    'total' => round($stored_total, 2), // Use stored_total (includes premium service + seat charges, same as NMI)
                    'original_total' => round($calculated_total_with_premium, 2),
                    'markup' => round($markup_amount, 2),
                    'flat_fee' => round($flat_fee_amount, 2),
                    'markup_enabled' => false, // Don't show markup/discount breakdown to users
                    'discount' => 0,
                    'discount_enabled' => false, // Don't show discount breakdown to users
                    'currency' => $currency
                );
            }
        }

        // PRIORITY 4: Fallback - Use base_total and calculate base/taxes proportionally
        if ($base_total > 0) {
            if ($original_base > 0 && $original_total > 0) {
                $price_ratio = $base_total / $original_total;
                $adjusted_base = $original_base * $price_ratio;
                $adjusted_taxes = $base_total - $adjusted_base;

                if ($adjusted_taxes < 0) {
                    $adjusted_taxes = $base_total * 0.10;
                    $adjusted_base = $base_total - $adjusted_taxes;
                }
            } else {
                // Estimate base as 90% and taxes as 10%
                $adjusted_base = $base_total * 0.90;
                $adjusted_taxes = $base_total * 0.10;
            }

            // Check if Pricing Rules Engine is enabled
            $use_rules_engine = class_exists('Amadex_Pricing_Rules') && Amadex_Pricing_Rules::is_enabled();
            $flat_fee_amount = 0;
            $markup_amount = 0;

            if ($use_rules_engine) {
                // Get flat fee from pricing snapshot
                $pricing_snapshot = $booking['flight']['price']['pricing_snapshot'] ?? $booking['flight']['price'] ?? array();
                $flat_fee_amount = floatval($pricing_snapshot['flat_fee_amount'] ?? $booking['flight']['price']['flat_fee_amount'] ?? 0);
                $markup_applied = floatval($pricing_snapshot['markup_applied'] ?? $booking['flight']['price']['markup_applied'] ?? 0);
                $final_total_with_markup = $stored_total + $flat_fee_amount; // Add flat fee
                $markup_amount = $markup_applied;
            } else {
                // Use legacy markup system
                $pricing_settings = get_option('amadex_pricing_settings', array());
                $markup_enabled = isset($pricing_settings['enable_confirmation_discount']) && $pricing_settings['enable_confirmation_discount'] == 1;
                $markup_percentage = isset($pricing_settings['confirmation_discount_percentage']) ? floatval($pricing_settings['confirmation_discount_percentage']) : 10;

                $original_total_before_markup = $stored_total;
                $final_total_with_markup = $stored_total;

                if ($markup_enabled && $markup_percentage > 0) {
                    $markup_amount = round($stored_total * ($markup_percentage / 100), 2);
                    $final_total_with_markup = round($stored_total + $markup_amount, 2);
                }
            }

            // Absorb flat fee into taxes (don't show separately to users)
            $final_taxes = round($adjusted_taxes, 2);
            if ($use_rules_engine && $flat_fee_amount > 0) {
                $final_taxes = round($adjusted_taxes + $flat_fee_amount, 2);
            }

            return array(
                'base_fare' => round($adjusted_base, 2),
                'taxes' => $final_taxes,
                'premium_service' => $premium_service_added ? round($premium_service_amount, 2) : 0,
                'premium_service_added' => $premium_service_added, // Flag to indicate if premium service was added
                'seat_selection' => round($seat_charges, 2),
                'total' => round($stored_total, 2), // Use stored_total (includes premium service + seat charges, same as NMI)
                'original_total' => round($stored_total, 2),
                'markup' => round($markup_amount, 2),
                'flat_fee' => round($flat_fee_amount, 2),
                'markup_enabled' => false, // Don't show markup/discount breakdown to users
                'discount' => 0,
                'discount_enabled' => false, // Don't show discount breakdown to users
                'currency' => $currency
            );
        }

        // Final fallback: return zeros
        return array(
            'base_fare' => 0,
            'taxes' => 0,
            'premium_service' => 0,
            'seat_selection' => round($seat_charges, 2),
            'total' => 0,
            'original_total' => 0,
            'discount' => 0,
            'discount_enabled' => false,
            'currency' => $currency
        );
    }

    /**
     * Get merged email template settings (saved options + defaults from Brand). Used by build_booking_email_body.
     */
    private function get_email_template_settings()
    {
        $defaults = class_exists('Amadex_Settings') ? Amadex_Settings::get_email_template_defaults() : array();
        $settings = wp_parse_args(get_option('amadex_email_template_settings', array()), $defaults);
        // Read custom_html and custom_css from separate options (saved separately to avoid WP HTML stripping)
        $custom_html = get_option('amadex_email_custom_html', '');
        $custom_css  = get_option('amadex_email_custom_css', '');
        if ($custom_html !== '') {
            $settings['custom_html'] = $custom_html;
        }
        if ($custom_css !== '') {
            $settings['custom_css'] = $custom_css;
        }
        return $settings;
    }

    /**
     * Build professional booking confirmation email body - Matching Confirmation Page Design
     * When a drag-and-drop builder design is saved, injects dynamic content into the builder HTML.
     * @param array $booking Booking data
     * @param bool $for_customer True for customer email, false for admin/agent
     * @param array|null $template_settings_override Optional. When set, use these values instead of saved options (for live preview).
     */
    private function build_booking_email_body($booking, $for_customer = true, $template_settings_override = null)
    {
        $template_settings = $this->get_email_template_settings();
        $use_default_template = (isset($template_settings['email_template_mode']) && $template_settings['email_template_mode'] === 'default');
        if ($use_default_template || $template_settings_override !== null) {
            return $this->build_booking_email_body_raw($booking, $for_customer, $template_settings_override);
        }

        // Priority 1: Custom HTML from the HTML editor textarea
        $custom_html = isset($template_settings['custom_html']) ? trim($template_settings['custom_html']) : '';
        if ($custom_html !== '') {
            // Inject custom CSS if provided
            $custom_css = isset($template_settings['custom_css']) ? trim($template_settings['custom_css']) : '';
            if ($custom_css !== '') {
                $custom_html = str_replace('</head>', '<style>' . $custom_css . '</style></head>', $custom_html);
                // If no </head>, inject at top
                if (strpos($custom_html, $custom_css) === false) {
                    $custom_html = '<style>' . $custom_css . '</style>' . $custom_html;
                }
            }
            // Replace {booking_content} placeholder with actual booking content
            $full = $this->build_booking_email_body_raw($booking, $for_customer);
            if (preg_match('/<!-- AMADEX_CONTENT_START -->(.*)<!-- AMADEX_CONTENT_END -->/s', $full, $m)) {
                $content = trim($m[1]);
                $custom_html = str_replace('{booking_content}', $content, $custom_html);
                $custom_html = str_replace('<!-- AMADEX_BOOKING_CONTENT -->', $content, $custom_html);
            }
            return $custom_html;
        }

        // Priority 2: Drag & drop builder HTML
        $builder_html = get_option('amadex_email_builder_html', '');
        if ($builder_html !== '') {
            $full = $this->build_booking_email_body_raw($booking, $for_customer);
            if (preg_match('/<!-- AMADEX_CONTENT_START -->(.*)<!-- AMADEX_CONTENT_END -->/s', $full, $m)) {
                $content = trim($m[1]);
                return str_replace('<!-- AMADEX_BOOKING_CONTENT -->', $content, $builder_html);
            }
            return $builder_html;
        }

        // Priority 3: Default raw builder
        return $this->build_booking_email_body_raw($booking, $for_customer);
    }

    /**
     * Raw email body builder (used by build_booking_email_body and for content extraction when using builder template).
     */
    private function build_booking_email_body_raw($booking, $for_customer = true, $template_settings_override = null)
    {
        $t = $this->get_email_template_settings();
        if (is_array($template_settings_override) && !empty($template_settings_override)) {
            $t = wp_parse_args($template_settings_override, $t);
        }
        $brand_settings = get_option('amadex_brand_settings', array());
        $brand = !empty($brand_settings['company_name']) ? sanitize_text_field($brand_settings['company_name']) : get_bloginfo('name');
        $general_settings = get_option('amadex_general_settings', array());

        // Get contact information
        $contact_name = __('Traveler', 'amadex');
        if (isset($booking['lead']['contact_name'])) {
            $contact_name = $booking['lead']['contact_name'];
        } elseif (isset($booking['contact_name'])) {
            $contact_name = $booking['contact_name'];
        }

        $contact_email = $booking['lead']['contact_email'] ?? $booking['contact_email'] ?? '';
        $contact_phone = $booking['lead']['contact_phone'] ?? $booking['contact_phone'] ?? '';

        // Booking details
        $reference = $booking['booking_reference'] ?? 'N/A';
        $currency = $booking['currency'] ?? 'USD';
        $total_amount = number_format((float) ($booking['total_amount'] ?? 0), 2);
        $status = $booking['status'] ?? 'PENDING';
        $payment_status = $booking['payment']['payment_status'] ?? 'AUTH_ONLY';
        $payment_method = $booking['payment']['payment_method'] ?? 'CREDIT_CARD';
        $card_type = $booking['payment']['card_type'] ?? '';
        $card_last4 = $booking['payment']['card_last4'] ?? '';

        // Get support contact info
        $support_phone = isset($general_settings['call_now_number']) ? sanitize_text_field($general_settings['call_now_number']) : '';
        if (empty($support_phone) && isset($booking['lead']['contact_phone'])) {
            $support_phone = $booking['lead']['contact_phone'];
        }

        $support_email = (!empty($general_settings['notification_email']) && is_email($general_settings['notification_email']))
            ? sanitize_email($general_settings['notification_email'])
            : ((!empty($general_settings['admin_notification_email']) && is_email($general_settings['admin_notification_email']))
                ? sanitize_email($general_settings['admin_notification_email'])
                : get_option('admin_email'));

        // Get logo image URLs: email template (Media Library) overrides, then general settings, then plugin default
        $plugin_url = plugin_dir_url(dirname(__FILE__));
        $travelay_logo_url = $plugin_url . 'assets/images/travelay-logo.png';
        $traveleygent_logo_url = $plugin_url . 'assets/images/travelygent-logo.png';
        if (!empty($t['logo_primary_id'])) {
            $u = wp_get_attachment_image_url((int) $t['logo_primary_id'], 'full');
            if ($u) $travelay_logo_url = $u;
        } elseif (isset($general_settings['travelay_logo_url']) && $general_settings['travelay_logo_url'] !== '') {
            $travelay_logo_url = esc_url($general_settings['travelay_logo_url']);
        }
        if (!empty($t['logo_secondary_id'])) {
            $u = wp_get_attachment_image_url((int) $t['logo_secondary_id'], 'full');
            if ($u) $traveleygent_logo_url = $u;
        } elseif (isset($general_settings['traveleygent_logo_url']) && $general_settings['traveleygent_logo_url'] !== '') {
            $traveleygent_logo_url = esc_url($general_settings['traveleygent_logo_url']);
        }

        // Social media icon URLs (using actual uploaded images)
        $social_icons_base = $plugin_url . 'assets/images/';
        $social_icons = array(
            'facebook' => isset($general_settings['social_facebook_icon']) && !empty($general_settings['social_facebook_icon'])
                ? esc_url($general_settings['social_facebook_icon'])
                : $social_icons_base . 'facebook.svg',
            'twitter' => isset($general_settings['social_twitter_icon']) && !empty($general_settings['social_twitter_icon'])
                ? esc_url($general_settings['social_twitter_icon'])
                : $social_icons_base . 'twitter.svg',
            'instagram' => isset($general_settings['social_instagram_icon']) && !empty($general_settings['social_instagram_icon'])
                ? esc_url($general_settings['social_instagram_icon'])
                : $social_icons_base . 'Instagram.svg',
            'youtube' => isset($general_settings['social_youtube_icon']) && !empty($general_settings['social_youtube_icon'])
                ? esc_url($general_settings['social_youtube_icon'])
                : $social_icons_base . 'youtube.svg',
            'linkedin' => isset($general_settings['social_linkedin_icon']) && !empty($general_settings['social_linkedin_icon'])
                ? esc_url($general_settings['social_linkedin_icon'])
                : $social_icons_base . 'Linkedin.svg',
        );

        // Decode flight_data if it's a JSON string
        $flight_data = $booking['flight_data'] ?? array();
        if (is_string($flight_data)) {
            $flight_data = json_decode($flight_data, true);
        }

        // Primary/accent color from template (for checkmark circle, headings, links, admin box)
        $primary_color = (!empty($t['primary_color']) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $t['primary_color'])) ? $t['primary_color'] : '#0E7D3F';
        $primary_attr = esc_attr($primary_color);

        // Get status display text and color
        $status_config = $this->get_status_display($status);
        $payment_config = $this->get_payment_status_display($payment_status);

        // Helper function to format date
        $format_date = function ($date_string) {
            if (empty($date_string)) return '';
            try {
                $date = new DateTime($date_string);
                $day = $date->format('j');
                $month = $date->format('M');
                $year = $date->format('y');
                $day_name = $date->format('l');
                return $day . ' ' . $month . ', ' . $year . ' ' . $day_name;
            } catch (Exception $e) {
                return $date_string;
            }
        };

        // Helper function to format time
        $format_time = function ($date_string) {
            if (empty($date_string)) return '';
            try {
                $date = new DateTime($date_string);
                return $date->format('H:i');
            } catch (Exception $e) {
                return $date_string;
            }
        };

        ob_start();
?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <title>Booking Confirmation</title>
            <style>
                /* Reset */
                body,
                table,
                td,
                p,
                a {
                    -webkit-text-size-adjust: 100%;
                    -ms-text-size-adjust: 100%;
                }

                table,
                td {
                    mso-table-lspace: 0pt;
                    mso-table-rspace: 0pt;
                }

                img {
                    -ms-interpolation-mode: bicubic;
                    border: 0;
                    height: auto;
                    line-height: 100%;
                    outline: none;
                    text-decoration: none;
                }

                body {
                    margin: 0;
                    padding: 0;
                    background: #f0f4f0;
                }

                /* ── MOBILE ── */
                @media only screen and (max-width:600px) {
                    tr.header-logos {
                        display: flex;
                        align-items: center;
                    }

                    tr.email-header {
                        display: flex;
                    }

                    .ew {
                        width: 100% !important;
                        max-width: 100% !important;
                    }

                    .ep {
                        padding: 0 12px 14px !important;
                    }

                    .ec {
                        padding: 16px !important;
                        border-radius: 10px !important;
                    }

                    .eh {
                        padding: 16px !important;
                        border-radius: 0 !important;
                    }

                    .el {
                        display: block !important;
                        width: 100% !important;
                        text-align: center !important;
                        padding: 6px 0 !important;
                    }

                    .er {
                        display: block !important;
                        width: 100% !important;
                        text-align: center !important;
                        padding: 6px 0 !important;
                    }

                    .edc {
                        display: block !important;
                        width: 100% !important;
                        text-align: left !important;
                        padding: 0 0 12px 0 !important;
                        border-bottom: 1px solid #e5e7eb !important;
                    }

                    .emc {
                        display: block !important;
                        width: 100% !important;
                        text-align: center !important;
                        padding: 8px 0 !important;
                    }

                    .eac {
                        display: block !important;
                        width: 100% !important;
                        text-align: left !important;
                        padding: 12px 0 0 0 !important;
                    }

                    .evl {
                        display: none !important;
                    }

                    .etm {
                        font-size: 18px !important;
                    }

                    .efl {
                        display: block !important;
                        width: 100% !important;
                        text-align: center !important;
                        padding: 6px 0 !important;
                    }

                    .efr {
                        display: block !important;
                        width: 100% !important;
                        text-align: center !important;
                        padding: 6px 0 !important;
                    }

                    td[width="50%"] {
                        display: block !important;
                        width: 100% !important;
                        text-align: center !important;
                    }

                    td[width="40%"] {
                        display: block !important;
                        width: 100% !important;
                        text-align: left !important;
                    }

                    td[width="33%"] {
                        display: block !important;
                        width: 100% !important;
                        text-align: left !important;
                        padding: 4px 0 !important;
                    }

                    td[width="20%"] {
                        display: block !important;
                        width: 100% !important;
                        text-align: center !important;
                    }

                    td[style*="padding:32px"] {
                        padding: 18px 14px !important;
                    }

                    td[style*="padding:28px"] {
                        padding: 16px !important;
                    }

                    td[style*="padding:24px"] {
                        padding: 14px !important;
                        width: 100%;
                    }

                    h1 {
                        font-size: 20px !important;
                    }

                    h3 {
                        font-size: 15px !important;
                    }

                    .eseg-left,
                    .eseg-right {
                        display: block !important;
                        width: 100% !important;
                        padding: 4px 0 !important;
                    }
                }

                @media only screen and (max-width:400px) {
                    .etm {
                        font-size: 16px !important;
                    }

                    .ec {
                        padding: 12px !important;
                    }
                }
            </style>
        </head>

        <body style="margin:0;padding:0;background:#f0f4f0;font-family:Arial,Helvetica,sans-serif;">

            <!-- Outer wrapper -->
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f0f4f0;padding:0;">
                <tr>
                    <td align="center" style="padding:0;">

                        <!-- Main container -->
                        <table class="ew" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%;background:#ffffff;">

                            <!-- ═══ HEADER: Logos ═══ -->
                            <tr>
                                <td class="eh" style="background:#ffffff;padding:20px 24px;border-bottom:1px solid #e5e7eb;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr class="header-logos">
                                            <td width="50%" class="el" style="vertical-align:middle;">
                                                <a href="<?php echo esc_url(home_url()); ?>" style="text-decoration:none;">
                                                    <img class="email-logo" src="<?php echo esc_url($travelay_logo_url); ?>" alt="Travelay" style="max-width:120px;height:auto;display:block;" width="120">
                                                </a>
                                            </td>
                                            <td width="50%" class="er" align="right" style="vertical-align:middle;">
                                                <a href="<?php echo esc_url(home_url()); ?>" style="text-decoration:none;">
                                                    <img class="email-logo" src="<?php echo esc_url($traveleygent_logo_url); ?>" alt="TravelayGent" style="max-width:130px;height:auto;display:block;margin-left:auto;" width="130">
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- ═══ HERO BANNER: Booking Confirmed ═══ -->
                            <tr>
                                <td style="background:<?php echo $primary_attr; ?>;padding:0;position:relative;overflow:hidden;">
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="padding:24px 24px 20px;vertical-align:middle;">
                                                <?php if ($for_customer): ?>
                                                    <!-- Green badge -->
                                                    <table cellpadding="0" cellspacing="0" style="margin-bottom:8px;">
                                                        <tr class="table-headers">
                                                            <td style="background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.5);border-radius:20px;padding:6px 14px;">
                                                                <span style="color:#ffffff;font-size:13px;font-weight:600;">&#10003; Your Booking is Confirmed</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <p style="margin:0;color:rgba(255,255,255,0.9);font-size:14px;line-height:1.5;">Thank you for choosing Travelay.com</p>
                                                <?php else: ?>
                                                    <h1 style="margin:0 0 6px;color:#ffffff;font-size:22px;font-weight:700;">New Booking Alert</h1>
                                                    <p style="margin:0;color:rgba(255,255,255,0.9);font-size:14px;">A new verified booking lead is ready for review.</p>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Decorative illustration placeholder -->
                                            <!--<td width="140" align="right" style="vertical-align:bottom;padding:0 16px 0 0;">-->
                                            <!--  <div style="width:120px;height:80px;"></div>-->
                                            <!--</td>-->
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- ═══ PASSENGER DETAIL (Customer only) ═══ -->
                            <?php if ($for_customer && !empty($booking['passengers'])): ?>
                                <?php
                                $first_pax = null;
                                foreach ($booking['passengers'] as $pax) {
                                    $first_pax = $pax;
                                    break;
                                }
                                $pax_first  = trim(($first_pax['first_name'] ?? $first_pax['firstName'] ?? '') . ' ' . ($first_pax['last_name'] ?? $first_pax['lastName'] ?? ''));
                                $pax_type   = ucfirst(strtolower($first_pax['traveler_type'] ?? $first_pax['travelerType'] ?? 'Adult'));
                                $pax_ticket = $first_pax['ticket_number'] ?? $first_pax['ticketNumber'] ?? $reference;
                                $carry_on   = $first_pax['carry_on_baggage'] ?? $first_pax['carryOnBaggage'] ?? 'Subject to Airline Baggage Policy';
                                $checked_bag = $first_pax['checked_baggage'] ?? $first_pax['checkedBaggage'] ?? 'No';
                                ?>
                                <tr>
                                    <td style="padding:20px 5px 0;">
                                        <h3 style="margin:0 0 12px;color:#111827;font-size:16px;font-weight:700;">Passenger Detail</h3>
                                        <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                                            <!-- Row -->
                                            <tr style="border-bottom:1px solid #f3f4f6;">
                                                <td style="padding:12px 16px;color:#6b7280;font-size:13px;width:45%;border-bottom:1px solid #f3f4f6;">Passenger Type</td>
                                                <td style="padding:12px 16px;color:#111827;font-size:13px;font-weight:600;border-bottom:1px solid #f3f4f6;"><?php echo esc_html($pax_type); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 16px;color:#6b7280;font-size:13px;width:45%;border-bottom:1px solid #f3f4f6;">Full Name</td>
                                                <td style="padding:12px 16px;color:#111827;font-size:13px;font-weight:600;border-bottom:1px solid #f3f4f6;"><?php echo esc_html($pax_first ?: $contact_name); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 16px;color:#6b7280;font-size:13px;width:45%;border-bottom:1px solid #f3f4f6;">E-ticket Number</td>
                                                <td style="padding:12px 16px;color:#111827;font-size:13px;font-weight:600;border-bottom:1px solid #f3f4f6;"><?php echo esc_html($pax_ticket); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 16px;color:#6b7280;font-size:13px;width:45%;border-bottom:1px solid #f3f4f6;">Carry-On Baggage</td>
                                                <td style="padding:12px 16px;color:#111827;font-size:13px;font-weight:600;border-bottom:1px solid #f3f4f6;"><?php echo esc_html($carry_on); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="padding:12px 16px;color:#6b7280;font-size:13px;width:45%;">Checked Baggage</td>
                                                <td style="padding:12px 16px;color:#111827;font-size:13px;font-weight:600;"><?php echo esc_html($checked_bag); ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <!-- ═══ FLIGHT ITINERARY ═══ -->
                            <?php if (!empty($flight_data['itineraries'])): ?>
                                <tr>
                                    <td style="padding:20px 16px 0;">
                                        <h3 style="margin:0 0 12px;color:#111827;font-size:16px;font-weight:700;">Flight itinerary</h3>
                                    </td>
                                </tr>
                                <?php foreach ($flight_data['itineraries'] as $idx => $itinerary):
                                    // Itinerary summary
                                    $itin_segs   = $itinerary['segments'] ?? [];
                                    $itin_first  = $itin_segs[0] ?? [];
                                    $itin_last   = end($itin_segs);
                                    $itin_dep_city = $this->get_airport_info($itin_first['departure']['iataCode'] ?? $itin_first['departure']['iata_code'] ?? '')['city'] ?? ($itin_first['departure']['iataCode'] ?? '');
                                    $itin_arr_city = $this->get_airport_info($itin_last['arrival']['iataCode']   ?? $itin_last['arrival']['iata_code']   ?? '')['city'] ?? ($itin_last['arrival']['iataCode']   ?? '');
                                    $itin_stops  = count($itin_segs) - 1;
                                    $itin_stops_txt = $itin_stops === 0 ? 'Non-stop' : $itin_stops . ' Stop' . ($itin_stops > 1 ? 's' : '');
                                    // Total duration
                                    $itin_dep_dt = $itin_first['departure']['at'] ?? '';
                                    $itin_arr_dt = $itin_last['arrival']['at']    ?? '';
                                    $itin_total_dur = '';
                                    if ($itin_dep_dt && $itin_arr_dt) {
                                        try {
                                            $d1 = new DateTime($itin_dep_dt);
                                            $d2 = new DateTime($itin_arr_dt);
                                            $diff = $d1->diff($d2);
                                            $itin_total_dur = ($diff->days * 24 + $diff->h) . 'h ' . $diff->i . 'm';
                                        } catch (Exception $e) {
                                        }
                                    }
                                    $itin_date_txt = $itin_dep_dt ? (new DateTime($itin_dep_dt))->format('j M,y l') : '';
                                    // Airline info
                                    $itin_carrier = $itin_first['carrierCode'] ?? $itin_first['carrier_code'] ?? '';
                                    $itin_airline = $this->get_airline_name($itin_carrier);
                                    $itin_flight_no = $itin_carrier . '-' . ($itin_first['number'] ?? '');
                                    // Cabin
                                    $travel_class_itin = '';
                                    if (!empty($booking['lead']['search_params']['travel_class'])) $travel_class_itin = $booking['lead']['search_params']['travel_class'];
                                    elseif (!empty($booking['lead']['search_params']['cabin'])) $travel_class_itin = $booking['lead']['search_params']['cabin'];
                                    $cabin_itin = ucwords(strtolower(str_replace('_', ' ', $travel_class_itin ?: 'Economy')));
                                    $trip_type_itin = count($flight_data['itineraries']) > 1 ? 'Round Trip' : 'One Way';
                                ?>
                                    <tr>
                                        <td style="padding:0 5px 10px;">
                                            <!-- Itinerary Summary Header -->
                                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:12px;">
                                                <tr>
                                                    <td style="padding:10px;">
                                                        <p style="margin:0 0 2px;color:#111827;font-size:14px;font-weight:700;"><?php echo esc_html($itin_dep_city . ' - ' . $itin_arr_city); ?></p>
                                                        <p style="margin:0;color:#6b7280;font-size:12px;"><?php echo esc_html(($itin_total_dur ? $itin_total_dur . ', ' : '') . $itin_stops_txt . ($itin_date_txt ? ', ' . $itin_date_txt : '')); ?></p>
                                                    </td>
                                                </tr>
                                            </table>

                                            <!-- Segment Card -->
                                            <?php foreach ($itin_segs as $seg_idx => $segment):
                                                $dep_code = $segment['departure']['iataCode'] ?? $segment['departure']['iata_code'] ?? '';
                                                $arr_code = $segment['arrival']['iataCode']   ?? $segment['arrival']['iata_code']   ?? '';
                                                $carrier_code = $segment['carrierCode'] ?? $segment['carrier_code'] ?? '';
                                                $flight_number = $itin_carrier . '-' . ($segment['number'] ?? '');
                                                $dep_time_str = $segment['departure']['at'] ?? '';
                                                $arr_time_str = $segment['arrival']['at']   ?? '';
                                                $dep_terminal = $segment['departure']['terminal'] ?? '';
                                                $arr_terminal = $segment['arrival']['terminal']   ?? '';
                                                $dep_airport_info2 = $this->get_airport_info($dep_code);
                                                $arr_airport_info2 = $this->get_airport_info($arr_code);
                                                $dep_city2 = $dep_airport_info2['city'] ?? $dep_code;
                                                $arr_city2 = $arr_airport_info2['city'] ?? $arr_code;
                                                $dep_airport_name = $dep_airport_info2['name'] ?? '';
                                                $arr_airport_name = $arr_airport_info2['name'] ?? '';
                                                $dur2 = $this->format_duration($segment['duration'] ?? '');
                                                $seg_stops = 0; // individual segment is always direct
                                            ?>
                                                <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:<?php echo $seg_idx < count($itin_segs) - 1 ? '10' : '0'; ?>px;overflow:hidden;">
                                                    <!-- Segment top bar -->
                                                    <tr>
                                                        <td style="padding:14px 16px;border-bottom:1px solid #f3f4f6;">
                                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                                <tr class="email-header">
                                                                    <td class="eseg-left" style="vertical-align:middle;">
                                                                        <!-- Airline logo placeholder circle + name -->
                                                                        <table cellpadding="0" cellspacing="0">
                                                                            <tr>
                                                                                <td style="width:36px;height:36px;background:#e8f5e9;border-radius:50%;text-align:center;vertical-align:middle;font-size:11px;font-weight:700;color:<?php echo $primary_attr; ?>;padding:0;"><?php echo esc_html(substr($carrier_code, 0, 2)); ?></td>
                                                                                <td style="padding-left:10px;vertical-align:middle;">
                                                                                    <p style="margin:0;color:#111827;font-size:13px;font-weight:700;"><?php echo esc_html($this->get_airline_name($carrier_code)); ?></p>
                                                                                    <p style="margin:2px 0 0;color:#6b7280;font-size:11px;"><?php echo esc_html($carrier_code . '-' . ($segment['number'] ?? '')); ?></p>
                                                                                </td>
                                                                            </tr>
                                                                        </table>
                                                                    </td>
                                                                    <td class="eseg-right" align="right" style="vertical-align:middle;">
                                                                        <span style="background:#f3faf5;border:1px solid #a7f3c8;border-radius:20px;padding:4px 10px;color:<?php echo $primary_attr; ?>;font-size:11px;font-weight:600;"><?php echo esc_html($trip_type_itin . ', ' . $cabin_itin); ?></span>
                                                                        <?php if ($reference): ?>
                                                                            <p style="margin:6px 0 0;color:#6b7280;font-size:11px;">Airline Reference: <strong style="color:<?php echo $primary_attr; ?>;"><?php echo esc_html($reference); ?></strong></p>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>

                                                    <!-- Flight route — VERTICAL STACK: Departure → duration/line → Arrival -->
                                                    <tr>
                                                        <td style="padding:16px;display: flex;">

                                                            <!-- DEPARTURE block -->
                                                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                                                                <tr>
                                                                    <td>
                                                                        <p style="margin:0 0 2px;color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:.6px;">Departure</p>
                                                                        <p style="margin:0 0 2px;color:#111827;font-size:22px;font-weight:700;line-height:1.1;"><?php echo esc_html($format_time($dep_time_str)); ?></p>
                                                                        <p style="margin:0 0 2px;color:#111827;font-size:14px;font-weight:600;"><?php echo esc_html($dep_city2); ?></p>
                                                                        <?php if ($dep_airport_name): ?>
                                                                            <p style="margin:0 0 2px;color:#6b7280;font-size:12px;line-height:1.5;"><?php echo esc_html($dep_airport_name . ($dep_terminal ? ', Terminal ' . $dep_terminal : '')); ?></p>
                                                                        <?php endif; ?>
                                                                        <p style="margin:0;color:#9ca3af;font-size:12px;"><?php echo esc_html($format_date($dep_time_str)); ?></p>
                                                                    </td>
                                                                </tr>
                                                            </table>

                                                            <!-- DURATION + LINE in middle -->
                                                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
                                                                <tr>
                                                                    <td style="padding:4px 0;text-align:center;border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;">
                                                                        <?php if ($dur2): ?>
                                                                            <p style="margin:6px 0 2px;color:#6b7280;font-size:12px;font-weight:500;"><?php echo esc_html($dur2); ?></p>
                                                                        <?php endif; ?>
                                                                        <p style="margin:0 0 6px;color:#9ca3af;font-size:11px;"><?php echo $seg_stops === 0 ? 'Non-stop' : $seg_stops . ' Stop'; ?></p>
                                                                    </td>
                                                                </tr>
                                                            </table>

                                                            <!-- ARRIVAL block -->
                                                            <table width="100%" cellpadding="0" cellspacing="0">
                                                                <tr>
                                                                    <td align="right">
                                                                        <p style="margin:0 0 2px;color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:.6px;text-align:right;">Arrival</p>
                                                                        <p style="margin:0 0 2px;color:#111827;font-size:22px;font-weight:700;line-height:1.1;text-align:right;"><?php echo esc_html($format_time($arr_time_str)); ?></p>
                                                                        <p style="margin:0 0 2px;color:#111827;font-size:14px;font-weight:600;text-align:right;"><?php echo esc_html($arr_city2); ?></p>
                                                                        <?php if ($arr_airport_name): ?>
                                                                            <p style="margin:0 0 2px;color:#6b7280;font-size:12px;line-height:1.5;text-align:right;"><?php echo esc_html($arr_airport_name . ($arr_terminal ? ', Terminal ' . $arr_terminal : '')); ?></p>
                                                                        <?php endif; ?>
                                                                        <p style="margin:0;color:#9ca3af;font-size:12px;text-align:right;"><?php echo esc_html($format_date($arr_time_str)); ?></p>
                                                                    </td>
                                                                </tr>
                                                            </table>

                                                        </td>
                                                    </tr>

                                                    <?php if ($seg_idx < count($itin_segs) - 1):
                                                        $next_seg = $itin_segs[$seg_idx + 1];
                                                        $conn_time = '';
                                                        try {
                                                            $ca = new DateTime($segment['arrival']['at'] ?? '');
                                                            $cd = new DateTime($next_seg['departure']['at'] ?? '');
                                                            $cdiff = $ca->diff($cd);
                                                            $conn_time = $cdiff->h . 'h ' . $cdiff->i . 'm';
                                                        } catch (Exception $e) {
                                                        }
                                                        $stop_code = $segment['arrival']['iataCode'] ?? $segment['arrival']['iata_code'] ?? '';
                                                    ?>
                                                        <tr>
                                                            <td style="padding:0 16px 14px;">
                                                                <table width="100%" cellpadding="0" cellspacing="0" style="background:#FFF9EB;border:1px solid #FCD34D;border-radius:20px;">
                                                                    <tr>
                                                                        <td style="padding:8px 16px;text-align:center;">
                                                                            <p style="margin:0;color:#92400E;font-size:12px;font-weight:500;">Change planes at <?php echo esc_html($stop_code); ?> International. Connecting Time: <?php echo esc_html($conn_time); ?></p>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>

                                                </table>
                                            <?php endforeach; ?>

                                            <!-- Passengers / Order / Reference — vertical stacked list -->
                                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;margin-top:10px;">
                                                <tr>
                                                    <td style="padding:14px 16px;border-bottom:1px solid #f3f4f6;">
                                                        <p style="margin:0 0 3px;color:#6b7280;font-size:11px;">Passengers</p>
                                                        <p style="margin:0;color:#111827;font-size:13px;font-weight:700;"><?php echo esc_html($contact_name); ?></p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:14px 16px;border-bottom:1px solid #f3f4f6;">
                                                        <p style="margin:0 0 3px;color:#6b7280;font-size:11px;">Order Number</p>
                                                        <p style="margin:0;color:#111827;font-size:13px;font-weight:700;"><?php echo esc_html(strtolower(str_replace('-', '', $reference))); ?></p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:14px 16px;">
                                                        <p style="margin:0 0 3px;color:#6b7280;font-size:11px;">Airline Reference</p>
                                                        <p style="margin:0;color:#111827;font-size:13px;font-weight:700;"><?php echo esc_html($reference); ?></p>
                                                    </td>
                                                </tr>
                                            </table>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- ═══ BOARDING PASSES ═══ -->
                            <?php if ($for_customer && !empty($flight_data['itineraries'])): ?>
                                <tr>
                                    <td style="padding:0 5px 10px;">
                                        <h3 style="margin:0 0 12px;color:#111827;font-size:16px;font-weight:700;">Boarding passes</h3>
                                        <?php foreach ($flight_data['itineraries'] as $itin_bp):
                                            foreach (($itin_bp['segments'] ?? []) as $seg_bp):
                                                $bp_dep = $seg_bp['departure']['iataCode'] ?? $seg_bp['departure']['iata_code'] ?? '';
                                                $bp_arr = $seg_bp['arrival']['iataCode']   ?? $seg_bp['arrival']['iata_code']   ?? '';
                                                $bp_dep_city = $this->get_airport_info($bp_dep)['city'] ?? $bp_dep;
                                                $bp_arr_city = $this->get_airport_info($bp_arr)['city'] ?? $bp_arr;
                                        ?>
                                                <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:10px;">
                                                    <tr>
                                                        <td style="padding:14px 16px;">
                                                            <!-- Route -->
                                                            <table cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
                                                                <tr>
                                                                    <td style="color:#111827;font-size:13px;font-weight:700;"><?php echo esc_html($bp_dep_city . ' (' . $bp_dep . ')'); ?></td>
                                                                    <td style="padding:0 8px;color:#6b7280;font-size:13px;">&#8594;</td>
                                                                    <td style="color:#111827;font-size:13px;font-weight:700;"><?php echo esc_html($bp_arr_city . ' (' . $bp_arr . ')'); ?></td>
                                                                </tr>
                                                            </table>
                                                            <!-- Check-in directly -->
                                                            <p style="margin:0 0 6px;color:<?php echo $primary_attr; ?>;font-size:13px;font-weight:600;">Check-in directly with the airline</p>
                                                            <p style="margin:0 0 14px;color:#374151;font-size:12px;line-height:1.6;">Please check-in on the airline's website using your last name and the airline reference number included in this email. Check-in times vary between airlines, so we recommend visiting the airline's website to see when check-in opens for your flight(s).</p>
                                                            <!-- We will check you in -->
                                                            <p style="margin:0 0 6px;color:<?php echo $primary_attr; ?>;font-size:13px;font-weight:600;">We will check you in</p>
                                                            <p style="margin:0;color:#374151;font-size:12px;line-height:1.6;">We will check you in for your flight and send you your boarding pass for this flight. Check-in times vary between airlines, so we recommend visiting the airline's website to see when check-in opens for your flight(s).</p>
                                                        </td>
                                                    </tr>
                                                </table>
                                        <?php endforeach;
                                        endforeach; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <!-- ═══ VISA & TRAVEL RESTRICTIONS ═══ -->
                            <?php if ($for_customer): ?>
                                <tr>
                                    <td style="padding:0 5px 10px;">
                                        <h3 style="margin:0 0 12px;color:#111827;font-size:16px;font-weight:700;">Visa &amp; Travel Restrictions</h3>
                                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;">
                                            <tr>
                                                <td style="padding:16px;">
                                                    <p style="margin:0;color:#374151;font-size:12px;line-height:1.7;">We will not be liable for your refusal of entry into any flight, transit point or destination and/or any other incidents, loss, fine, penalties or damages (including direct and/or consequential loss and damage) which result from your failure to comply with such restrictions and/or requirements (set by any government authority, airline and/or applicable commercial party).</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <!-- ═══ NEED ASSISTANCE ═══ -->
                            <?php if ($support_phone || $support_email): ?>
                                <tr>
                                    <td style="padding:0 5px 10px;">
                                        <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;">
                                            <tr>
                                                <td style="padding:16px;text-align:center;">
                                                    <p style="margin:0 0 6px;color:#111827;font-size:14px;font-weight:700;">Need Assistance?</p>
                                                    <p style="margin:0 0 10px;color:#6b7280;font-size:12px;">Our team is available 24/7. Share your booking reference for faster service.</p>
                                                    <?php if ($support_phone): ?>
                                                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $support_phone)); ?>" style="color:<?php echo $primary_attr; ?>;font-size:15px;font-weight:700;text-decoration:none;display:block;margin-bottom:4px;"><?php echo esc_html($support_phone); ?></a>
                                                    <?php endif; ?>
                                                    <?php if ($support_email): ?>
                                                        <a href="mailto:<?php echo esc_attr($support_email); ?>" style="color:<?php echo $primary_attr; ?>;font-size:13px;text-decoration:none;"><?php echo esc_html($support_email); ?></a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <!-- ═══ FOOTER ═══ -->
                            <tr>
                                <td class="em-footer-td" style="background:#e8f5e9;padding:24px 20px;">
                                    <!-- Logos -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
                                        <tr>
                                            <td width="50%" class="efl em-footer-logo-left" style="vertical-align:middle;">
                                                <a href="<?php echo esc_url(home_url()); ?>" style="text-decoration:none;">
                                                    <img src="<?php echo esc_url($travelay_logo_url); ?>" alt="Travelay" style="max-width:100px;height:auto;display:block;" width="100">
                                                </a>
                                            </td>
                                            <td width="50%" align="right" class="efr em-footer-logo-right" style="vertical-align:middle;">
                                                <a href="<?php echo esc_url(home_url()); ?>" style="text-decoration:none;">
                                                    <img src="<?php echo esc_url($traveleygent_logo_url); ?>" alt="TravelayGent" style="max-width:110px;height:auto;display:block;margin-left:auto;" width="110">
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Social icons -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:14px;">
                                        <tr>
                                            <td align="center">
                                                <a href="https://www.facebook.com/p/Travelay-61571579267106/" target="_blank" style="display:inline-block;margin:0 4px;text-decoration:none;"><img src="<?php echo esc_url($social_icons['facebook']); ?>" alt="Facebook" width="32" height="32" style="border-radius:50%;width:32px;height:32px;display:block;border:0;"></a>
                                                <a href="https://x.com/TravelayLLC" target="_blank" style="display:inline-block;margin:0 4px;text-decoration:none;"><img src="<?php echo esc_url($social_icons['twitter']); ?>" alt="Twitter" width="32" height="32" style="border-radius:50%;width:32px;height:32px;display:block;border:0;"></a>
                                                <a href="https://www.instagram.com/travelayllc" target="_blank" style="display:inline-block;margin:0 4px;text-decoration:none;"><img src="<?php echo esc_url($social_icons['instagram']); ?>" alt="Instagram" width="32" height="32" style="border-radius:50%;width:32px;height:32px;display:block;border:0;"></a>
                                                <a href="https://www.youtube.com/@flytravelay" target="_blank" style="display:inline-block;margin:0 4px;text-decoration:none;"><img src="<?php echo esc_url($social_icons['youtube']); ?>" alt="YouTube" width="32" height="32" style="border-radius:50%;width:32px;height:32px;display:block;border:0;"></a>
                                                <a href="https://www.linkedin.com/company/travelay/" target="_blank" style="display:inline-block;margin:0 4px;text-decoration:none;"><img src="<?php echo esc_url($social_icons['linkedin']); ?>" alt="LinkedIn" width="32" height="32" style="border-radius:50%;width:32px;height:32px;display:block;border:0;"></a>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Copyright -->
                                    <table width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td align="center">
                                                <p style="margin:0;color:#4b7a55;font-size:12px;">&copy; <?php echo date('Y'); ?> &mdash; Travelay</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                        </table>
                        <!-- /Main container -->

                    </td>
                </tr>
            </table>
            <!-- /Outer wrapper -->

        </body>

        </html>
<?php
        return ob_get_clean();
    }

    /**
     * Get status display configuration
     */
    private function get_status_display($status)
    {
        $configs = array(
            'PENDING' => array('text' => 'Pending Verification', 'color' => '#F59E0B', 'bg' => '#FEF3C7'),
            'CONFIRMED' => array('text' => 'Confirmed', 'color' => '#0E7D3F', 'bg' => '#D1FAE5'),
            'TICKETED' => array('text' => 'Ticketed', 'color' => '#0E7D3F', 'bg' => '#D1FAE5'),
            'CANCELLED' => array('text' => 'Cancelled', 'color' => '#DC2626', 'bg' => '#FEE2E2'),
            'FAILED' => array('text' => 'Failed', 'color' => '#DC2626', 'bg' => '#FEE2E2')
        );

        return $configs[strtoupper($status)] ?? array('text' => $status, 'color' => '#6B7280', 'bg' => '#F3F4F6');
    }

    /**
     * Get payment status display configuration
     */
    private function get_payment_status_display($payment_status)
    {
        $configs = array(
            'AUTH_ONLY' => array('text' => 'Authorized (Not Charged)', 'color' => '#F59E0B', 'bg' => '#FEF3C7'),
            'COMPLETED' => array('text' => 'Completed', 'color' => '#0E7D3F', 'bg' => '#D1FAE5'),
            'CAPTURED' => array('text' => 'Captured', 'color' => '#0E7D3F', 'bg' => '#D1FAE5'),
            'PENDING' => array('text' => 'Pending', 'color' => '#6B7280', 'bg' => '#F3F4F6'),
            'FAILED' => array('text' => 'Failed', 'color' => '#DC2626', 'bg' => '#FEE2E2'),
            'REFUNDED' => array('text' => 'Refunded', 'color' => '#6B7280', 'bg' => '#F3F4F6')
        );

        return $configs[strtoupper($payment_status)] ?? array('text' => $payment_status, 'color' => '#6B7280', 'bg' => '#F3F4F6');
    }

    /**
     * Get airline name from carrier code
     */
    private function get_airline_name($carrier_code)
    {
        $airlines = array(
            'UA' => 'United Airlines',
            'AA' => 'American Airlines',
            'DL' => 'Delta Air Lines',
            'BA' => 'British Airways',
            'LH' => 'Lufthansa',
            'AF' => 'Air France',
            'KL' => 'KLM',
            'VS' => 'Virgin Atlantic',
            'EK' => 'Emirates',
            'QR' => 'Qatar Airways',
            'SQ' => 'Singapore Airlines',
            'CX' => 'Cathay Pacific',
        );
        return $airlines[strtoupper($carrier_code)] ?? $carrier_code . ' Airlines';
    }

    /**
     * Get airline logo URL
     */
    private function get_airline_logo_url($airline_code)
    {
        if (empty($airline_code)) {
            return '';
        }
        $normalized_code = strtoupper(trim($airline_code));
        return 'https://images.kiwi.com/airlines/64/' . $normalized_code . '.png';
    }

    /**
     * Get airport info (city name) from IATA code
     */
    private function get_airport_info($iata_code)
    {
        $airports = array(
            'JFK' => array('city' => 'New York', 'airport' => 'John F. Kennedy International'),
            'LAX' => array('city' => 'Los Angeles', 'airport' => 'Los Angeles International'),
            'LHR' => array('city' => 'London', 'airport' => 'Heathrow'),
            'CDG' => array('city' => 'Paris', 'airport' => 'Charles de Gaulle'),
            'DXB' => array('city' => 'Dubai', 'airport' => 'Dubai International'),
            'SIN' => array('city' => 'Singapore', 'airport' => 'Singapore Changi'),
            'DEL' => array('city' => 'Delhi', 'airport' => 'Indira Gandhi International'),
            'BOM' => array('city' => 'Mumbai', 'airport' => 'Chhatrapati Shivaji Maharaj International'),
            'SMF' => array('city' => 'Sacramento', 'airport' => 'Sacramento Metro'),
            'DCA' => array('city' => 'Washington', 'airport' => 'Ronald Reagan Washington National'),
            'MIA' => array('city' => 'Miami', 'airport' => 'Miami International'),
            'ORD' => array('city' => 'Chicago', 'airport' => 'O\'Hare International'),
            'DFW' => array('city' => 'Dallas', 'airport' => 'Dallas/Fort Worth International'),
            'ATL' => array('city' => 'Atlanta', 'airport' => 'Hartsfield-Jackson Atlanta International'),
            'DEN' => array('city' => 'Denver', 'airport' => 'Denver International'),
            'SEA' => array('city' => 'Seattle', 'airport' => 'Seattle-Tacoma International'),
            'SFO' => array('city' => 'San Francisco', 'airport' => 'San Francisco International'),
            'LAS' => array('city' => 'Las Vegas', 'airport' => 'McCarran International'),
            'PHX' => array('city' => 'Phoenix', 'airport' => 'Phoenix Sky Harbor International'),
            'IAH' => array('city' => 'Houston', 'airport' => 'George Bush Intercontinental'),
            'BOS' => array('city' => 'Boston', 'airport' => 'Logan International'),
            'MSP' => array('city' => 'Minneapolis', 'airport' => 'Minneapolis-Saint Paul International'),
            'DTW' => array('city' => 'Detroit', 'airport' => 'Detroit Metropolitan'),
            'PHL' => array('city' => 'Philadelphia', 'airport' => 'Philadelphia International'),
            'CLT' => array('city' => 'Charlotte', 'airport' => 'Charlotte Douglas International'),
            'EWR' => array('city' => 'Newark', 'airport' => 'Newark Liberty International'),
        );

        $iata_upper = strtoupper($iata_code);
        if (isset($airports[$iata_upper])) {
            return $airports[$iata_upper];
        }

        return array('city' => $iata_code, 'airport' => $iata_code);
    }

    /**
     * Format duration helper
     */
    private function format_duration($duration)
    {
        if (preg_match('/PT(\d+)H(?:(\d+)M)?/', $duration, $matches)) {
            $hours = isset($matches[1]) ? intval($matches[1]) : 0;
            $minutes = isset($matches[2]) ? intval($matches[2]) : 0;
            return $hours . 'h ' . $minutes . 'm';
        }
        return $duration;
    }

    /**
     * NMI Three Step Redirect — Step 3 return handler
     * NMI redirects back here after 3DS authentication completes.
     * Hooked on template_redirect so it fires on the booking page URL.
     */
    public function handle_3ds_return()
    {
        if (empty($_GET['amadex_3ds_return']) || empty($_GET['token-id']) || empty($_GET['booking_ref'])) {
            return; // Not a 3DS return — do nothing
        }

        $token_id     = sanitize_text_field($_GET['token-id']);
        $booking_ref  = sanitize_text_field($_GET['booking_ref']);

        amadex_log('Amadex 3DS Return: token-id=' . substr($token_id, 0, 20) . '... booking_ref=' . $booking_ref);

        // Load saved booking context
        $saved = get_transient('amadex_3ds_booking_' . $booking_ref);
        if (empty($saved) || !is_array($saved)) {
            amadex_log('Amadex 3DS Return Error: transient expired or missing for ' . $booking_ref);
            wp_safe_redirect(add_query_arg('amadex_payment_error', urlencode('3DS session expired. Please try again.'), home_url('/flight-booking/')));
            exit;
        }
        delete_transient('amadex_3ds_booking_' . $booking_ref);

        $booking_data     = $saved['booking_data'];
        $payment_data     = $saved['payment_data'];
        $lock_key         = $saved['lock_key'];
        $lead_id          = $saved['lead_id'];
        $total_amount     = $saved['total_amount'];
        $booking_reference = $saved['booking_reference'];

        // Step 3: Complete the 3DS auth
        $payment     = new Amadex_Payment();
        $auth_result = $payment->complete_three_step_redirect($token_id);

        if (is_wp_error($auth_result)) {
            $error_msg = $auth_result->get_error_message();
            amadex_log('Amadex 3DS Step 3 WP_Error: ' . $error_msg);
            if (!empty($lock_key)) {
                $this->release_booking_lock($lock_key, null, 'FAILED');
            }
            // Record failure on lead
            if (!empty($lead_id)) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'amadex_leads',
                    array(
                        'payment_failure_reason' => '3ds_failed',
                        'payment_failure_detail' => $error_msg,
                    ),
                    array('id' => $lead_id),
                    array('%s', '%s'),
                    array('%d')
                );
            }
            wp_safe_redirect(add_query_arg('amadex_payment_error', urlencode($error_msg), home_url('/flight-booking/')));
            exit;
        }

        if (empty($auth_result['success'])) {
            $error_msg = $auth_result['response_text'] ?? 'Payment authentication failed.';
            $raw_response_text = strtolower($error_msg);
            // Detect specific 3DS failure reasons
            if (
                strpos($raw_response_text, '3d') !== false ||
                strpos($raw_response_text, '3ds') !== false ||
                strpos($raw_response_text, 'not enrolled') !== false ||
                strpos($raw_response_text, 'not supported') !== false ||
                strpos($raw_response_text, 'authentication') !== false
            ) {
                $failure_reason = 'card_no_3ds';
            } else {
                $failure_reason = '3ds_failed';
            }
            amadex_log('Amadex 3DS Step 3 Failed: ' . $error_msg . ' (reason: ' . $failure_reason . ')');
            if (!empty($lock_key)) {
                $this->release_booking_lock($lock_key, null, 'FAILED');
            }
            // Record failure on lead
            if (!empty($lead_id)) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'amadex_leads',
                    array(
                        'payment_failure_reason' => $failure_reason,
                        'payment_failure_detail' => $error_msg,
                        'card_last4'             => sanitize_text_field($auth_result['card_last4'] ?? ''),
                        'card_type'              => sanitize_text_field($auth_result['card_type'] ?? ''),
                    ),
                    array('id' => $lead_id),
                    array('%s', '%s', '%s', '%s'),
                    array('%d')
                );
            }
            wp_safe_redirect(add_query_arg('amadex_payment_error', urlencode($error_msg), home_url('/flight-booking/')));
            exit;
        }

        amadex_log('Amadex 3DS Step 3 Success: transaction_id=' . $auth_result['transaction_id']);

        // ── Save booking to DB (same logic as normal NMI flow) ──────────────
        global $wpdb;
        $database        = new Amadex_Database();
        $payment_settings = get_option('amadex_payment_settings', array());
        $flight_data     = $booking_data['flight'] ?? array();

        $booking_db_data = array(
            'booking_reference' => $booking_reference,
            'lead_id'           => $lead_id,
            'contact_name'      => ($booking_data['contact']['first_name'] ?? '') . ' ' . ($booking_data['contact']['last_name'] ?? ''),
            'contact_email'     => $booking_data['contact']['email'] ?? '',
            'contact_phone'     => $booking_data['contact']['phone'] ?? '',
            'flight_data'       => $flight_data,
            'passengers'        => $booking_data['passengers'] ?? array(),
            'seat_selection'    => $booking_data['seat_selection'] ?? array(),
            'addons'            => $booking_data['addons'] ?? array(),
            'payment'           => array(
                'gateway'        => 'nmi',
                'method'         => 'three_step_3ds',
                'transaction_id' => $auth_result['transaction_id'],
                'auth_code'      => $auth_result['auth_code'],
                'avs_response'   => $auth_result['avs_response'],
                'cvv_response'   => $auth_result['cvv_response'],
                'amount'         => $total_amount,
                'currency'       => $payment_data['currency'] ?? 'USD',
                'status'         => 'authorized',
            ),
            'total_amount'      => $total_amount,
            'currency'          => $payment_data['currency'] ?? 'USD',
            'status'            => 'PENDING_CONFIRMATION',
            'source'            => 'ONLINE_3DS',
        );

        $booking_id = $database->create_booking($booking_db_data);
        amadex_log('Amadex 3DS Booking saved: booking_id=' . $booking_id);

        // Release lock
        if (!empty($lock_key)) {
            $this->release_booking_lock($lock_key, $booking_id, 'COMPLETED');
        }

        // Redirect to confirmation page
        $confirmation_url = $this->get_confirmation_page_url($booking_reference);
        wp_safe_redirect($confirmation_url);
        exit;
    }

    /**
     * Build confirmation page url
     */
    private function get_confirmation_page_url($booking_reference = '')
    {
        $general_settings = get_option('amadex_general_settings', array());
        $page_id = isset($general_settings['booking_confirmation_page']) ? intval($general_settings['booking_confirmation_page']) : 0;
        $url = $page_id ? get_permalink($page_id) : home_url('/booking-confirmation/');

        if ($booking_reference) {
            $separator = strpos($url, '?') === false ? '?' : '&';
            $url .= $separator . 'reference=' . rawurlencode($booking_reference);
        }

        return $url;
    }

    /**
     * Generate pseudo PNR (6 characters) optionally prefixed by airline code
     */
    private function generate_pnr_code($airline_code = '')
    {
        $airline_code = preg_replace('/[^A-Z0-9]/', '', strtoupper($airline_code));
        $airline_code = substr($airline_code, 0, 2);

        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $needed = 6 - strlen($airline_code);
        if ($needed <= 0) {
            $needed = 6;
            $airline_code = '';
        }

        $pnr_suffix = '';
        for ($i = 0; $i < $needed; $i++) {
            $pnr_suffix .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return strtoupper($airline_code . $pnr_suffix);
    }

    /**
     * Ensure booking has a PNR when payment has succeeded (used by PayPal, Crypto.com, MoonPay, etc.).
     * Only generates and saves a PNR if the booking does not already have one.
     *
     * @param int $booking_id Booking ID
     * @param Amadex_Database $database Database instance
     */
    private function ensure_booking_pnr($booking_id, $database)
    {
        if (!$booking_id || !$database) {
            return;
        }
        $booking = $database->get_booking($booking_id);
        if (!$booking || !empty(trim((string) ($booking['pnr'] ?? '')))) {
            return;
        }
        $flight_data = isset($booking['flight_data']) && is_array($booking['flight_data']) ? $booking['flight_data'] : array();
        $validating_airline = '';
        $vac = $flight_data['validating_airline_codes'] ?? $flight_data['validatingAirlineCodes'] ?? null;
        if (!empty($vac[0])) {
            $validating_airline = strtoupper(substr((string) $vac[0], 0, 2));
        } elseif (!empty($flight_data['itineraries'][0]['segments'][0]['carrierCode'])) {
            $validating_airline = strtoupper(substr((string) $flight_data['itineraries'][0]['segments'][0]['carrierCode'], 0, 2));
        }
        $pnr = $this->generate_pnr_code($validating_airline);
        if ($pnr) {
            $database->update_booking_pnr($booking_id, $pnr);
        }
    }

    /**
     * Confirm booking and verify NMI authorization
     */
    public function confirm_booking()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);

        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Booking ID is required', 'amadex')));
        }

        $database = new Amadex_Database();
        $booking = $database->get_booking($booking_id);

        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found', 'amadex')));
        }

        // Update booking status to CONFIRMED
        $updated = $database->update_booking_status($booking_id, 'CONFIRMED');

        if (!$updated) {
            wp_send_json_error(array('message' => __('Failed to update booking status', 'amadex')));
        }

        // Verify NMI authorization if payment exists
        $nmi_verified = false;
        $nmi_message = '';

        if (!empty($booking['payment']['transaction_id'])) {
            $payment = new Amadex_Payment();
            $verification = $payment->query_transaction($booking['payment']['transaction_id']);

            if (!is_wp_error($verification) && $verification['success']) {
                $nmi_verified = true;
                $nmi_message = __('Authorization verified in NMI. Transaction ID: ', 'amadex') . $booking['payment']['transaction_id'];
            } else {
                $nmi_message = __('Could not verify authorization in NMI. Transaction ID: ', 'amadex') . $booking['payment']['transaction_id'];
            }
        }

        wp_send_json_success(array(
            'message' => __('Booking confirmed successfully', 'amadex'),
            'booking_id' => $booking_id,
            'nmi_verified' => $nmi_verified,
            'nmi_message' => $nmi_message
        ));
    }

    /**
     * Verify NMI authorization for a booking
     */
    public function verify_nmi_authorization()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }

        $booking_id = intval($_POST['booking_id'] ?? 0);
        $transaction_id = sanitize_text_field($_POST['transaction_id'] ?? '');

        if (!$booking_id && !$transaction_id) {
            wp_send_json_error(array('message' => __('Booking ID or Transaction ID is required', 'amadex')));
        }

        // Get transaction ID from booking if not provided
        if (!$transaction_id && $booking_id) {
            $database = new Amadex_Database();
            $booking = $database->get_booking($booking_id);

            if (!$booking || empty($booking['payment']['transaction_id'])) {
                wp_send_json_error(array('message' => __('No transaction ID found for this booking', 'amadex')));
            }

            $transaction_id = $booking['payment']['transaction_id'];
        }

        $payment = new Amadex_Payment();
        $result = $payment->query_transaction($transaction_id);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => __('Error querying NMI: ', 'amadex') . $result->get_error_message()
            ));
        }

        wp_send_json_success(array(
            'message' => __('Authorization details retrieved from NMI', 'amadex'),
            'transaction' => $result
        ));
    }

    /**
     * Test email functionality
     */
    public function test_email()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        $test_email = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : get_option('admin_email');

        if (!is_email($test_email)) {
            wp_send_json_error(array('message' => __('Invalid email address', 'amadex')));
        }

        $brand = get_bloginfo('name');
        $site_email = get_option('admin_email');

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $brand . ' <' . $site_email . '>',
            'Reply-To: ' . $site_email
        );

        $subject = __('Amadex Email Test', 'amadex');
        $body = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#0f172a;">
            <h2 style="color:#0e7d3f;">' . __('Email Test Successful!', 'amadex') . '</h2>
            <p>' . __('If you received this email, your WordPress mail configuration is working correctly.', 'amadex') . '</p>
            <p><strong>' . __('Test Details:', 'amadex') . '</strong></p>
            <ul>
                <li>' . __('Sent from:', 'amadex') . ' ' . esc_html($site_email) . '</li>
                <li>' . __('WordPress Site:', 'amadex') . ' ' . esc_html(get_site_url()) . '</li>
                <li>' . __('Time:', 'amadex') . ' ' . current_time('mysql') . '</li>
            </ul>
            <p style="margin-top:20px;color:#666;font-size:12px;">
                ' . __('This is a test email from the Amadex booking plugin.', 'amadex') . '
            </p>
        </div>';

        $sent = wp_mail($test_email, $subject, $body, $headers);

        if ($sent) {
            amadex_log('Amadex Test Email: Successfully sent test email to: ' . $test_email);
            wp_send_json_success(array(
                'message' => __('Test email sent successfully to: ', 'amadex') . $test_email
            ));
        } else {
            amadex_log('Amadex Test Email: Failed to send test email to: ' . $test_email);
            wp_send_json_error(array(
                'message' => __('Failed to send test email. Check WordPress mail configuration or install an SMTP plugin.', 'amadex')
            ));
        }
    }

    /**
     * Register email preview page
     */
    public function register_email_preview_page()
    {
        if (isset($_GET['amadex_email_preview']) && current_user_can('manage_options')) {
            $this->preview_email_template();
            exit;
        }
    }

    /**
     * Sample booking data for email template preview (browser and live AJAX)
     */
    private function get_sample_booking_for_email_preview()
    {
        return array(
            'booking_reference' => 'TEST-123456',
            'status' => 'PENDING',
            'currency' => 'USD',
            'total_amount' => '1250.00',
            'payment' => array(
                'payment_status' => 'AUTH_ONLY',
                'payment_method' => 'CREDIT_CARD',
                'card_type' => 'VISA',
                'card_last4' => '1234'
            ),
            'lead' => array(
                'contact_name' => 'John Doe',
                'contact_email' => 'john.doe@example.com',
                'contact_phone' => '+1-555-123-4567'
            ),
            'contact_name' => 'John Doe',
            'contact_email' => 'john.doe@example.com',
            'contact_phone' => '+1-555-123-4567',
            'passengers' => array(
                array(
                    'passenger_type' => 'ADULT',
                    'title' => 'Mr.',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'gender' => 'MALE',
                    'date_of_birth' => '1990-01-15'
                )
            ),
            'flight_data' => array(
                'itineraries' => array(
                    array(
                        'duration' => 'PT5H30M',
                        'segments' => array(
                            array(
                                'departure' => array(
                                    'iataCode' => 'JFK',
                                    'iata_code' => 'JFK',
                                    'at' => date('Y-m-d\TH:i:s', strtotime('+1 day')) . 'Z',
                                    'terminal' => '5'
                                ),
                                'arrival' => array(
                                    'iataCode' => 'LAX',
                                    'iata_code' => 'LAX',
                                    'at' => date('Y-m-d\TH:i:s', strtotime('+1 day +5 hours')) . 'Z',
                                    'terminal' => '2'
                                ),
                                'carrierCode' => 'AA',
                                'carrier_code' => 'AA',
                                'number' => '1234',
                                'duration' => 'PT5H30M',
                                'cabin' => 'ECONOMY'
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Preview email template in browser
     */
    public function preview_email_template()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to preview emails.', 'amadex'));
        }
        $sample_booking = $this->get_sample_booking_for_email_preview();
        $for_customer = !isset($_GET['type']) || $_GET['type'] !== 'admin';
        $email_html = $this->build_booking_email_body($sample_booking, $for_customer);
        echo $email_html;
    }

    /**
     * AJAX handler for email preview
     */
    public function preview_email()
    {
        check_ajax_referer('amadex_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to preview emails.', 'amadex')));
        }

        $preview_url = add_query_arg(array(
            'amadex_email_preview' => '1',
            'type' => isset($_POST['email_type']) && $_POST['email_type'] === 'admin' ? 'admin' : 'customer'
        ), home_url('/'));

        wp_send_json_success(array(
            'preview_url' => $preview_url,
            'message' => __('Email preview generated. Opening in new window...', 'amadex')
        ));
    }

    /**
     * AJAX: return email template HTML for live preview (unsaved form values)
     */
    public function ajax_email_template_live_preview()
    {
        check_ajax_referer('amadex_email_live_preview', 'nonce');
        if (!current_user_can('manage_options')) {
            status_header(403);
            exit;
        }
        $raw = isset($_POST['amadex_email_template_settings']) && is_array($_POST['amadex_email_template_settings']) ? $_POST['amadex_email_template_settings'] : array();
        $type = isset($_POST['type']) && $_POST['type'] === 'admin' ? 'admin' : 'customer';
        $defaults = class_exists('Amadex_Settings') ? Amadex_Settings::get_email_template_defaults() : array();
        $sanitized = class_exists('Amadex_Settings') ? Amadex_Settings::sanitize_email_template_settings_array($raw) : array();
        $settings = wp_parse_args($sanitized, $defaults);
        $sample_booking = $this->get_sample_booking_for_email_preview();
        $for_customer = ($type !== 'admin');
        $custom_html = isset($settings['custom_html']) ? trim($settings['custom_html']) : '';
        if ($custom_html !== '') {
            // Use custom HTML path
            $full = $this->build_booking_email_body_raw($sample_booking, $for_customer, $settings);
            if (preg_match('/<!-- AMADEX_CONTENT_START -->(.*)<!-- AMADEX_CONTENT_END -->/s', $full, $m)) {
                $content = trim($m[1]);
                $custom_html = str_replace('{booking_content}', $content, $custom_html);
                $custom_html = str_replace('<!-- AMADEX_BOOKING_CONTENT -->', $content, $custom_html);
            }
            $custom_css = isset($settings['custom_css']) ? trim($settings['custom_css']) : '';
            if ($custom_css !== '') {
                if (strpos($custom_html, '</head>') !== false) {
                    $custom_html = str_replace('</head>', '<style>' . $custom_css . '</style></head>', $custom_html);
                } else {
                    $custom_html = '<style>' . $custom_css . '</style>' . $custom_html;
                }
            }
            $html = $custom_html;
        } else {
            $html = $this->build_booking_email_body_raw($sample_booking, $for_customer, $settings);
        }
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /**
     * AJAX: save email builder design (JSON) and exported HTML
     */
    public function ajax_save_email_builder()
    {
        check_ajax_referer('amadex_save_email_builder', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }
        $design = isset($_POST['design']) ? wp_unslash($_POST['design']) : '';
        $html = isset($_POST['html']) ? wp_unslash($_POST['html']) : '';
        if ($design !== '' && !is_string($design)) {
            wp_send_json_error(array('message' => __('Invalid design data', 'amadex')));
        }
        if ($html !== '' && !is_string($html)) {
            wp_send_json_error(array('message' => __('Invalid HTML data', 'amadex')));
        }
        $is_autosave = !empty($_POST['autosave']);
        $old_design = get_option('amadex_email_builder_design', '');
        $old_html = get_option('amadex_email_builder_html', '');
        if (!$is_autosave && ($old_design !== '' || $old_html !== '')) {
            $history = get_option('amadex_email_builder_history', array());
            if (!is_array($history)) {
                $history = array();
            }
            array_unshift($history, array(
                'time' => time(),
                'design' => $old_design,
                'html' => $old_html,
            ));
            $history = array_slice($history, 0, 10);
            update_option('amadex_email_builder_history', $history);
        }
        update_option('amadex_email_builder_design', $design);
        update_option('amadex_email_builder_html', $html);
        wp_send_json_success(array('message' => __('Design saved.', 'amadex')));
    }

    /**
     * AJAX: get email builder version history (list of timestamps + labels)
     */
    public function ajax_get_email_builder_history()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }
        $history = get_option('amadex_email_builder_history', array());
        if (!is_array($history)) {
            $history = array();
        }
        $versions = array();
        foreach ($history as $entry) {
            $t = isset($entry['time']) ? (int) $entry['time'] : 0;
            $versions[] = array(
                'time' => $t,
                'label' => $t ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), $t) : __('Unknown', 'amadex'),
            );
        }
        wp_send_json_success(array('versions' => $versions));
    }

    /**
     * AJAX: restore a version from email builder history (returns design + html for that version)
     */
    public function ajax_restore_email_builder_version()
    {
        check_ajax_referer('amadex_save_email_builder', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }
        $time = isset($_POST['time']) ? (int) $_POST['time'] : 0;
        if (!$time) {
            wp_send_json_error(array('message' => __('Invalid version.', 'amadex')));
        }
        $history = get_option('amadex_email_builder_history', array());
        if (!is_array($history)) {
            $history = array();
        }
        foreach ($history as $entry) {
            if (isset($entry['time']) && (int) $entry['time'] === $time) {
                wp_send_json_success(array(
                    'design' => isset($entry['design']) ? $entry['design'] : '',
                    'html' => isset($entry['html']) ? $entry['html'] : '',
                ));
                return;
            }
        }
        wp_send_json_error(array('message' => __('Version not found.', 'amadex')));
    }

    /**
     * PHPMailer init: set AltBody for multipart/plain when booking emails are sent
     */
    public function amadex_phpmailer_alt_body($phpmailer)
    {
        if (self::$amadex_next_plain_body !== '' && is_object($phpmailer)) {
            $phpmailer->AltBody = self::$amadex_next_plain_body;
            self::$amadex_next_plain_body = '';
        }
    }

    /**
     * Convert HTML email body to plain text (strip tags, decode entities, collapse whitespace)
     */
    private function html_to_plain($html)
    {
        $text = wp_strip_all_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\s*\n\s*\n\s*/', "\n\n", $text);
        return trim($text);
    }

    /**
     * AJAX: save a block/partial for the email builder (reusable block)
     */
    public function ajax_save_email_builder_block()
    {
        check_ajax_referer('amadex_save_email_builder', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }
        $label = isset($_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : '';
        $content = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
        if ($label === '') {
            wp_send_json_error(array('message' => __('Block name is required.', 'amadex')));
        }
        if (!is_string($content)) {
            wp_send_json_error(array('message' => __('Invalid block content.', 'amadex')));
        }
        $blocks = get_option('amadex_email_builder_blocks', array());
        if (!is_array($blocks)) {
            $blocks = array();
        }
        $id = 'amadex-block-' . uniqid();
        $blocks[$id] = array('id' => $id, 'label' => $label, 'content' => $content);
        update_option('amadex_email_builder_blocks', $blocks);
        wp_send_json_success(array('message' => __('Block saved.', 'amadex'), 'block' => array('id' => $id, 'label' => $label, 'content' => $content)));
    }

    /**
     * AJAX: get all saved email builder blocks
     */
    public function ajax_get_email_builder_blocks()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }
        $blocks = get_option('amadex_email_builder_blocks', array());
        if (!is_array($blocks)) {
            $blocks = array();
        }
        wp_send_json_success(array('blocks' => array_values($blocks)));
    }

    /**
     * AJAX: delete a saved email builder block
     */
    public function ajax_delete_email_builder_block()
    {
        check_ajax_referer('amadex_save_email_builder', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'amadex')));
        }
        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        if ($id === '') {
            wp_send_json_error(array('message' => __('Block ID required.', 'amadex')));
        }
        $blocks = get_option('amadex_email_builder_blocks', array());
        if (!is_array($blocks)) {
            $blocks = array();
        }
        unset($blocks[$id]);
        update_option('amadex_email_builder_blocks', $blocks);
        wp_send_json_success(array('message' => __('Block deleted.', 'amadex')));
    }

    /**
     * Diagnose booking system - check database tables and configuration
     * Can be called from browser console: jQuery.post(ajaxurl, {action: 'amadex_diagnose_booking', nonce: AmadexConfig.nonce}, console.log)
     */
    public function diagnose_booking()
    {
        // Verify nonce (optional for diagnostics, but recommended)
        if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token'));
            return;
        }

        global $wpdb;
        $diagnostics = array(
            'timestamp' => current_time('mysql'),
            'database' => array(),
            'tables' => array(),
            'configuration' => array(),
            'recommendations' => array()
        );

        // Check database connection
        $connection_test = $wpdb->get_var("SELECT 1");
        $diagnostics['database']['connected'] = ($connection_test === '1');
        $diagnostics['database']['prefix'] = $wpdb->prefix;

        // Check required tables
        $required_tables = array(
            'amadex_leads' => $wpdb->prefix . 'amadex_leads',
            'amadex_bookings' => $wpdb->prefix . 'amadex_bookings',
            'amadex_passengers' => $wpdb->prefix . 'amadex_passengers',
            'amadex_payments' => $wpdb->prefix . 'amadex_payments'
        );

        foreach ($required_tables as $name => $table_name) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            $diagnostics['tables'][$name] = array(
                'exists' => $exists,
                'name' => $table_name
            );

            if ($exists) {
                // Check table structure
                $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table_name}");
                $diagnostics['tables'][$name]['columns'] = $columns;
                $diagnostics['tables'][$name]['column_count'] = count($columns);

                // Count records
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
                $diagnostics['tables'][$name]['record_count'] = intval($count);
            } else {
                $diagnostics['recommendations'][] = "Table '{$name}' is missing. Deactivate and reactivate the Amadex plugin to create it.";
            }
        }

        // Check payment configuration
        $payment_settings = get_option('amadex_payment_settings', array());
        $diagnostics['configuration']['nmi_api_key'] = !empty($payment_settings['nmi_api_key']);
        $diagnostics['configuration']['nmi_tokenization_key'] = !empty($payment_settings['nmi_tokenization_key']);
        $diagnostics['configuration']['nmi_sandbox'] = isset($payment_settings['nmi_sandbox']) ? (bool) $payment_settings['nmi_sandbox'] : true;
        $diagnostics['configuration']['bypass_payment'] = isset($payment_settings['nmi_bypass_for_testing']) ? (bool) $payment_settings['nmi_bypass_for_testing'] : false;

        if (empty($payment_settings['nmi_api_key'])) {
            $diagnostics['recommendations'][] = 'NMI API Key (Security Key) is not configured. Go to Amadex Settings → Payment Settings.';
        }

        if (empty($payment_settings['nmi_tokenization_key'])) {
            $diagnostics['recommendations'][] = 'NMI Tokenization Key is not configured. Go to Amadex Settings → Payment Settings.';
        }

        // Overall status
        $all_tables_exist = true;
        foreach ($diagnostics['tables'] as $table) {
            if (!$table['exists']) {
                $all_tables_exist = false;
                break;
            }
        }

        $diagnostics['status'] = array(
            'database_connected' => $diagnostics['database']['connected'],
            'all_tables_exist' => $all_tables_exist,
            'payment_configured' => $diagnostics['configuration']['nmi_api_key'] && $diagnostics['configuration']['nmi_tokenization_key'],
            'ready_for_booking' => $diagnostics['database']['connected'] && $all_tables_exist && $diagnostics['configuration']['nmi_api_key'] && $diagnostics['configuration']['nmi_tokenization_key']
        );

        if (!$diagnostics['status']['ready_for_booking']) {
            if (!$all_tables_exist) {
                $diagnostics['recommendations'][] = 'CRITICAL: Missing database tables. Deactivate and reactivate the Amadex plugin immediately.';
            }
        }

        wp_send_json_success($diagnostics);
    }

    /**
     * Recalculate price for flight with updated passenger counts
     */
    public function recalculate_price()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'amadex')));
            return;
        }

        // Get parameters
        $flight_offer_id = sanitize_text_field($_POST['flight_offer_id'] ?? '');
        $adults = intval($_POST['adults'] ?? 1);
        $children = intval($_POST['children'] ?? 0);
        $infants = intval($_POST['infants'] ?? 0);
        $origin = sanitize_text_field($_POST['origin'] ?? '');
        $destination = sanitize_text_field($_POST['destination'] ?? '');
        $departure_date = sanitize_text_field($_POST['departure_date'] ?? '');
        $return_date = sanitize_text_field($_POST['return_date'] ?? '');
        $currency = sanitize_text_field($_POST['currency'] ?? 'USD');
        $travel_class = sanitize_text_field($_POST['travel_class'] ?? '');

        // Check if raw_offer is provided (for timer refresh - direct pricing)
        $raw_offer_json = isset($_POST['raw_offer']) ? stripslashes($_POST['raw_offer']) : '';

        // Check if API class exists
        if (!class_exists('Amadex_API')) {
            wp_send_json_error(array('message' => __('Plugin not properly initialized.', 'amadex')));
            return;
        }

        try {
            $api = new Amadex_API();

            // PRIORITY 1: If raw_offer is provided, use Amadeus Price API directly (for timer refresh)
            // BUT: Only if passenger counts haven't changed (otherwise use re-search method)
            if (!empty($raw_offer_json)) {
                $raw_offer = json_decode($raw_offer_json, true);

                if (!$raw_offer || !is_array($raw_offer)) {
                    amadex_log('Amadex Recalculate Price Error: Invalid raw_offer JSON');
                    wp_send_json_error(array('message' => __('Invalid flight data. Please try again.', 'amadex')));
                    return;
                }

                // Check if passenger counts match between original offer and current request
                // If counts changed, we need to re-search (can't use direct pricing with old travelerPricings)
                $original_offer_adults = 0;
                $original_offer_children = 0;
                $original_offer_infants = 0;

                if (isset($raw_offer['travelerPricings']) && is_array($raw_offer['travelerPricings'])) {
                    foreach ($raw_offer['travelerPricings'] as $tp) {
                        $traveler_type = strtoupper($tp['travelerType'] ?? 'ADULT');
                        if ($traveler_type === 'ADULT') $original_offer_adults++;
                        elseif ($traveler_type === 'CHILD') $original_offer_children++;
                        elseif ($traveler_type === 'HELD_INFANT' || $traveler_type === 'INFANT') $original_offer_infants++;
                    }
                }

                $passenger_counts_match = ($original_offer_adults == $adults &&
                    $original_offer_children == $children &&
                    $original_offer_infants == $infants);

                // Only use direct pricing if passenger counts haven't changed
                // If counts changed, fall through to re-search method (PRIORITY 2)
                if ($passenger_counts_match) {
                    amadex_log('Amadex Recalculate Price: Passenger counts match (' . $adults . 'A/' . $children . 'C/' . $infants . 'I), using direct Price API (fast)');
                    // Use the Price API to re-price the exact flight offer (without seats)
                    $pricing_result = $api->price_flight_offer_with_seats($raw_offer, array());

                    if (is_wp_error($pricing_result)) {
                        amadex_log('Amadex Recalculate Price Error: ' . $pricing_result->get_error_message());
                        // Fall through to re-search method if direct pricing fails
                        $pricing_result = null;
                    }
                } else {
                    amadex_log('Amadex Recalculate Price: Passenger counts changed (Original: ' . $original_offer_adults . 'A/' . $original_offer_children . 'C/' . $original_offer_infants . 'I, Current: ' . $adults . 'A/' . $children . 'C/' . $infants . 'I), using re-search method');
                    // Passenger counts changed - skip direct pricing, use re-search method instead
                    $pricing_result = null; // Will trigger fallback to re-search
                }

                // Only process direct pricing result if it succeeded
                if ($pricing_result !== null && !is_wp_error($pricing_result)) {
                    // Extract pricing from result
                    $priced_offer = $pricing_result['flightOffer'] ?? null;
                    $pricing = $pricing_result['pricing'] ?? array();

                    if (!$priced_offer || empty($pricing)) {
                        amadex_log('Amadex Recalculate Price Error: Invalid pricing result');
                        wp_send_json_error(array('message' => __('Invalid pricing response. Please try again.', 'amadex')));
                        return;
                    }

                    // Apply markup to the re-priced amount
                    $airline_code = sanitize_text_field($_POST['airline_code'] ?? '');
                    $total_before_markup = floatval($pricing['total'] ?? 0);
                    $base_before_markup = floatval($pricing['base'] ?? 0);
                    $taxes_before_markup = floatval($pricing['taxes'] ?? ($total_before_markup - $base_before_markup));

                    $price_result = Amadex_Pricing::calculate_price_with_markup($total_before_markup, $airline_code, $base_before_markup, $taxes_before_markup);

                    // Handle both array (new format) and float (legacy) return types
                    if (is_array($price_result)) {
                        $final_total = floatval($price_result['total'] ?? $total_before_markup);
                        $final_base = floatval($price_result['base'] ?? $base_before_markup);
                        $final_taxes = floatval($price_result['taxes'] ?? $taxes_before_markup);
                    } else {
                        // Legacy format - backward compatibility
                        $final_total = floatval($price_result);
                        $final_base = $final_total * 0.9;
                        $final_taxes = $final_total * 0.1;
                    }

                    wp_send_json_success(array(
                        'price' => array(
                            'base' => $final_base,
                            'taxes' => $final_taxes,
                            'total' => $final_total,
                            'grandTotal' => $final_total,
                            'currency' => $pricing['currency'] ?? $currency,
                            'travelerPricings' => $priced_offer['travelerPricings'] ?? array()
                        )
                    ));
                    return;
                } else {
                    // Direct pricing not used (passenger counts changed) or failed - fall through to re-search
                    if ($pricing_result === null) {
                        amadex_log('Amadex Recalculate Price: Passenger counts changed or direct pricing skipped, falling back to re-search method');
                    } else {
                        amadex_log('Amadex Recalculate Price: Direct pricing failed, falling back to re-search method');
                    }
                }
            }

            // PRIORITY 2: Re-search method (for passenger count changes or when direct pricing unavailable)
            // Validate required fields for re-search
            if (empty($flight_offer_id) || empty($origin) || empty($destination) || empty($departure_date)) {
                wp_send_json_error(array('message' => __('Missing required flight information.', 'amadex')));
                return;
            }

            // Re-search for the same flight with new passenger counts
            $search_params = array(
                'origin' => $origin,
                'destination' => $destination,
                'departure_date' => $departure_date,
                'return_date' => $return_date,
                'adults' => $adults,
                'children' => $children,
                'infants' => $infants,
                'travel_class' => $travel_class,
                'currency' => $currency
            );

            // Search flights
            $result = $api->search_flights($search_params);

            if (is_wp_error($result)) {
                amadex_log('Amadex Recalculate Price Error: ' . $result->get_error_message());
                wp_send_json_error(array('message' => __('Failed to recalculate price. Please try again.', 'amadex')));
                return;
            }

            // Find the matching flight offer by ID
            $flights = isset($result['flights']) ? $result['flights'] : array();
            $matching_flight = null;

            foreach ($flights as $flight) {
                $offer_id = $flight['id'] ?? $flight['offerId'] ?? '';
                if ($offer_id === $flight_offer_id) {
                    $matching_flight = $flight;
                    break;
                }
            }

            // If exact match not found, use the first flight with similar characteristics
            if (!$matching_flight && !empty($flights)) {
                $matching_flight = $flights[0];
            }

            if (!$matching_flight) {
                // Fallback: Calculate price based on per-passenger pricing
                // Get original flight data from sessionStorage (will be sent from JS)
                $original_price = floatval($_POST['original_price'] ?? 0);
                $original_passengers = intval($_POST['original_passengers'] ?? 1);
                $original_adults = intval($_POST['original_adults'] ?? 1);
                $original_children = intval($_POST['original_children'] ?? 0);
                $original_infants = intval($_POST['original_infants'] ?? 0);
                $original_paying_passengers = intval($_POST['original_paying_passengers'] ?? ($original_adults + $original_children));

                if ($original_price > 0 && $original_paying_passengers > 0) {
                    // CRITICAL: Infants are priced differently (typically 10% of base fare or free)
                    // The original_price might already include infant costs, so we need to estimate
                    // and separate them to get the true paying passenger price

                    // Estimate infant cost in original booking (10% of per-adult price)
                    // Formula: original_price = (paying_passengers_price) + (infants * 0.10 * per_adult_price)
                    // Solving: original_price = paying_price * (1 + (original_infants * 0.10 / original_paying_passengers))
                    $infant_ratio = 0.10; // Infants are 10% of adult fare

                    if ($original_infants > 0) {
                        // Original price includes infant costs - need to extract paying passenger price
                        // Formula: original_price = paying_price * (1 + (original_infants * infant_ratio / original_paying_passengers))
                        $infant_cost_factor = 1 + ($original_infants * $infant_ratio / max($original_paying_passengers, 1));
                        $estimated_paying_passengers_price = $original_price / $infant_cost_factor;
                        $per_paying_passenger_price = $estimated_paying_passengers_price / max($original_paying_passengers, 1);
                    } else {
                        // No infants in original booking - simple division
                        $per_paying_passenger_price = $original_price / $original_paying_passengers;
                    }

                    // Calculate price for current paying passengers (adults + children)
                    $paying_passengers = $adults + $children;
                    $paying_passengers_total = $per_paying_passenger_price * $paying_passengers;

                    // Calculate infant costs for current booking (10% of adult fare per infant)
                    $infant_price_per_infant = ($per_paying_passenger_price * $infant_ratio);
                    $infants_total = $infant_price_per_infant * $infants;

                    $calculated_total = $paying_passengers_total + $infants_total;

                    // Apply markup - PROPORTIONAL to base fare and taxes
                    $airline_code = sanitize_text_field($_POST['airline_code'] ?? '');
                    // Estimate base and taxes (90% base, 10% taxes)
                    $estimated_base = $calculated_total * 0.9;
                    $estimated_taxes = $calculated_total * 0.1;

                    $price_result = Amadex_Pricing::calculate_price_with_markup($calculated_total, $airline_code, $estimated_base, $estimated_taxes);

                    // Handle both array (new format) and float (legacy) return types
                    if (is_array($price_result)) {
                        $final_total = floatval($price_result['total'] ?? $calculated_total);
                        $final_base = floatval($price_result['base'] ?? $estimated_base);
                        $final_taxes = floatval($price_result['taxes'] ?? $estimated_taxes);
                    } else {
                        // Legacy format - backward compatibility
                        $final_total = floatval($price_result);
                        $final_base = $final_total * 0.9;
                        $final_taxes = $final_total * 0.1;
                    }

                    wp_send_json_success(array(
                        'price' => array(
                            'base' => $final_base,
                            'taxes' => $final_taxes,
                            'total' => $final_total,
                            'grandTotal' => $final_total,
                            'currency' => $currency
                        )
                    ));
                    return;
                }

                wp_send_json_error(array('message' => __('Flight not found. Please try selecting the flight again.', 'amadex')));
                return;
            }

            // Extract price data from matching flight
            $price_data = $matching_flight['price'] ?? array();

            // Return updated price with taxes
            $price_base = floatval($price_data['base'] ?? 0);
            $price_total = floatval($price_data['total'] ?? $price_data['grandTotal'] ?? 0);
            $price_taxes = floatval($price_data['taxes'] ?? ($price_total - $price_base));

            if (empty($price_data)) {
                wp_send_json_error(array('message' => __('Price information not available.', 'amadex')));
                return;
            }

            // Return updated price
            wp_send_json_success(array(
                'price' => array(
                    'base' => $price_base,
                    'taxes' => $price_taxes,
                    'total' => $price_total,
                    'grandTotal' => $price_total,
                    'currency' => $price_data['currency'] ?? $currency,
                    'travelerPricings' => $matching_flight['travelerPricings'] ?? array()
                )
            ));
        } catch (Exception $e) {
            amadex_log('Amadex Recalculate Price Exception: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred while recalculating price.', 'amadex')));
        }
    }

    /**
     * AJAX: Get pricing rule
     */
    public function ajax_get_pricing_rule()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_pricing_rules')) {
            wp_send_json_error(array('message' => __('Security check failed', 'amadex')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'amadex')));
            return;
        }

        if (!class_exists('Amadex_Pricing_Rules')) {
            wp_send_json_error(array('message' => __('Pricing Rules Engine not available', 'amadex')));
            return;
        }

        $rule_id = intval($_POST['rule_id'] ?? 0);
        if ($rule_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid rule ID', 'amadex')));
            return;
        }

        try {
            $rule = Amadex_Pricing_Rules::get_rule($rule_id);
            if (!$rule) {
                wp_send_json_error(array('message' => __('Rule not found', 'amadex')));
                return;
            }

            wp_send_json_success($rule);
        } catch (Exception $e) {
            amadex_log('Amadex Pricing Rules: Exception getting rule - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred', 'amadex')));
        }
    }

    /**
     * AJAX: Save pricing rule (create or update)
     */
    public function ajax_save_pricing_rule()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_pricing_rules')) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'amadex')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'amadex')));
            return;
        }

        if (!class_exists('Amadex_Pricing_Rules')) {
            wp_send_json_error(array('message' => __('Pricing Rules Engine not available', 'amadex')));
            return;
        }

        // Check if table exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'amadex_pricing_rules';
        $wpdb->suppress_errors(true);
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        $wpdb->suppress_errors(false);

        if (!$table_exists) {
            amadex_log('Amadex Pricing Rules: Table does not exist - ' . $table_name);
            wp_send_json_error(array('message' => __('Pricing rules table does not exist. Please go to Database Setup and create the tables.', 'amadex')));
            return;
        }

        // Parse form data
        $form_data_string = isset($_POST['form_data']) ? $_POST['form_data'] : '';
        if (empty($form_data_string)) {
            amadex_log('Amadex Pricing Rules: No form data received. POST data: ' . print_r($_POST, true));
            wp_send_json_error(array('message' => __('No form data received. Please check that all fields are filled correctly.', 'amadex')));
            return;
        }

        // Don't sanitize the entire string as it contains form data that needs to be parsed
        parse_str($form_data_string, $form_data);

        if (empty($form_data) || !is_array($form_data)) {
            amadex_log('Amadex Pricing Rules: Invalid form data. Parsed data: ' . print_r($form_data, true));
            amadex_log('Amadex Pricing Rules: Form data string was: ' . $form_data_string);
            wp_send_json_error(array('message' => __('Invalid form data. Please refresh the page and try again.', 'amadex')));
            return;
        }

        amadex_log('Amadex Pricing Rules: Form data received - ' . print_r($form_data, true));

        // Form fields use: name, currency, min_amount, max_amount, discount_percent, flat_fee_amount, sort_order, is_enabled, is_default, rule_id
        $rule_id = intval($form_data['rule_id'] ?? $form_data['rule-id'] ?? 0);

        // Validate and prepare rule data
        $rule_data = array(
            'name' => sanitize_text_field($form_data['name'] ?? ''),
            'currency' => sanitize_text_field($form_data['currency'] ?? 'USD'),
            'min_amount' => floatval($form_data['min_amount'] ?? 0),
            'max_amount' => isset($form_data['max_amount']) && $form_data['max_amount'] !== '' && $form_data['max_amount'] !== null && trim($form_data['max_amount']) !== ''
                ? floatval($form_data['max_amount'])
                : null,
            'discount_percent' => floatval($form_data['discount_percent'] ?? 0),
            'flat_fee_amount' => floatval($form_data['flat_fee_amount'] ?? 0),
            'sort_order' => intval($form_data['sort_order'] ?? 0),
            'is_enabled' => isset($form_data['is_enabled']) && ($form_data['is_enabled'] == '1' || $form_data['is_enabled'] === true || $form_data['is_enabled'] === 'on') ? 1 : 0,
            'is_default' => isset($form_data['is_default']) && ($form_data['is_default'] == '1' || $form_data['is_default'] === true || $form_data['is_default'] === 'on') ? 1 : 0
        );

        amadex_log('Amadex Pricing Rules: Parsed rule data - ' . print_r($rule_data, true));
        amadex_log('Amadex Pricing Rules: Rule ID - ' . $rule_id);

        // Also check for rule_id
        $rule_id = intval($form_data['rule-id'] ?? $form_data['rule_id'] ?? 0);

        amadex_log('Amadex Pricing Rules: Saving rule - ID: ' . $rule_id . ', Data: ' . print_r($rule_data, true));

        // Validate required fields
        if (empty($rule_data['name']) || trim($rule_data['name']) === '') {
            wp_send_json_error(array('message' => __('Rule name is required', 'amadex')));
            return;
        }

        if ($rule_data['min_amount'] <= 0) {
            wp_send_json_error(array('message' => __('Minimum amount must be greater than 0', 'amadex')));
            return;
        }

        if ($rule_data['flat_fee_amount'] <= 0) {
            wp_send_json_error(array('message' => __('Flat fee must be greater than 0', 'amadex')));
            return;
        }

        if ($rule_data['max_amount'] !== null && $rule_data['max_amount'] <= $rule_data['min_amount']) {
            wp_send_json_error(array('message' => __('Maximum amount must be greater than minimum amount', 'amadex')));
            return;
        }

        try {
            if ($rule_id > 0) {
                // Update existing rule
                $result = Amadex_Pricing_Rules::update_rule($rule_id, $rule_data);
            } else {
                // Create new rule
                $result = Amadex_Pricing_Rules::create_rule($rule_data);
            }

            if (is_wp_error($result)) {
                $error_msg = $result->get_error_message();
                amadex_log('Amadex Pricing Rules: Error saving rule - ' . $error_msg);
                amadex_log('Amadex Pricing Rules: Error code - ' . $result->get_error_code());
                wp_send_json_error(array('message' => $error_msg));
                return;
            }

            // Check if result is valid
            if (empty($result) && $rule_id == 0) {
                amadex_log('Amadex Pricing Rules: Create rule returned empty result');
                wp_send_json_error(array('message' => __('Failed to create rule. Please check database and try again.', 'amadex')));
                return;
            }

            $saved_rule_id = ($rule_id > 0) ? $rule_id : $result;
            amadex_log('Amadex Pricing Rules: Rule saved successfully - ID: ' . $saved_rule_id);
            wp_send_json_success(array(
                'message' => __('Rule saved successfully', 'amadex'),
                'rule_id' => $saved_rule_id,
                'data' => $rule_data
            ));
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            amadex_log('Amadex Pricing Rules: Exception - ' . $error_msg);
            amadex_log('Amadex Pricing Rules: Exception trace - ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => __('An error occurred: ', 'amadex') . $error_msg));
        }
    }

    /**
     * AJAX: Delete pricing rule
     */
    public function ajax_delete_pricing_rule()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_pricing_rules')) {
            wp_send_json_error(array('message' => __('Security check failed', 'amadex')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'amadex')));
            return;
        }

        if (!class_exists('Amadex_Pricing_Rules')) {
            wp_send_json_error(array('message' => __('Pricing Rules Engine not available', 'amadex')));
            return;
        }

        $rule_id = intval($_POST['rule_id'] ?? 0);
        if ($rule_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid rule ID', 'amadex')));
            return;
        }

        try {
            $result = Amadex_Pricing_Rules::delete_rule($rule_id);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
                return;
            }

            wp_send_json_success(array('message' => __('Rule deleted successfully', 'amadex')));
        } catch (Exception $e) {
            amadex_log('Amadex Pricing Rules: Exception deleting rule - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred', 'amadex')));
        }
    }

    /**
     * AJAX: Simulate pricing
     */
    public function ajax_simulate_pricing()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_pricing_rules')) {
            wp_send_json_error(array('message' => __('Security check failed', 'amadex')));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'amadex')));
            return;
        }

        if (!class_exists('Amadex_Pricing_Rules')) {
            wp_send_json_error(array('message' => __('Pricing Rules Engine not available', 'amadex')));
            return;
        }

        $base_price = floatval($_POST['base_price'] ?? 0);
        $currency = sanitize_text_field($_POST['currency'] ?? 'USD');

        if ($base_price <= 0) {
            wp_send_json_error(array('message' => __('Invalid base price', 'amadex')));
            return;
        }

        try {
            $result = Amadex_Pricing_Rules::simulate($base_price, $currency);
            wp_send_json_success($result);
        } catch (Exception $e) {
            amadex_log('Amadex Pricing Rules: Exception simulating - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred', 'amadex')));
        }
    }

    /**
     * AJAX: Get seat map for flight
     */
    public function get_seatmap()
    {
        amadex_log('Amadex SeatMap AJAX: Request received');
        amadex_log('Amadex SeatMap AJAX: POST data: ' . print_r($_POST, true));

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            amadex_log('Amadex SeatMap AJAX: Security check failed');
            wp_send_json_error(array('message' => __('Security check failed', 'amadex')));
            return;
        }

        // Get flight offer ID and raw offer from request
        $flight_offer_id = sanitize_text_field($_POST['flight_offer_id'] ?? '');
        $raw_offer_json = isset($_POST['raw_offer']) ? wp_unslash($_POST['raw_offer']) : '';

        amadex_log('Amadex SeatMap AJAX: Flight offer ID received: ' . $flight_offer_id);
        amadex_log('Amadex SeatMap AJAX: Raw offer received: ' . (empty($raw_offer_json) ? 'NO' : 'YES (length: ' . strlen($raw_offer_json) . ')'));

        if (empty($flight_offer_id)) {
            amadex_log('Amadex SeatMap AJAX: Flight offer ID is empty');
            wp_send_json_error(array('message' => __('Flight offer ID is required', 'amadex')));
            return;
        }

        // Get API instance
        if (!class_exists('Amadex_API')) {
            amadex_log('Amadex SeatMap AJAX: API class not available');
            wp_send_json_error(array('message' => __('API class not available', 'amadex')));
            return;
        }

        $api = new Amadex_API();

        // Try to parse raw offer if provided
        $raw_offer = null;
        if (!empty($raw_offer_json)) {
            $raw_offer = json_decode($raw_offer_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                amadex_log('Amadex SeatMap AJAX: Failed to parse raw offer JSON: ' . json_last_error_msg());
                $raw_offer = null;
            } else {
                amadex_log('Amadex SeatMap AJAX: Successfully parsed raw offer');
            }
        }

        amadex_log('Amadex SeatMap AJAX: Calling API with flight offer ID: ' . $flight_offer_id . ', has raw offer: ' . ($raw_offer ? 'YES' : 'NO'));

        // Call SeatMap Display API - pass raw offer if available
        $seatmap_result = $api->get_seatmap($flight_offer_id, $raw_offer);

        if (is_wp_error($seatmap_result)) {
            amadex_log('Amadex SeatMap AJAX Error: ' . $seatmap_result->get_error_message());
            wp_send_json_error(array(
                'message' => $seatmap_result->get_error_message(),
                'code' => $seatmap_result->get_error_code(),
                'data' => $seatmap_result->get_error_data()
            ));
            return;
        }

        // wp_send_json_success($seatmap_result);
        //         if (isset($seatmap_result['data']) && is_array($seatmap_result['data'])) {
        //     foreach ($seatmap_result['data'] as &$seatmap) {
        //         $seat_currency = $seatmap['currency'] ?? ($seatmap['price']['currency'] ?? 'USD');
        //         if ($seat_currency !== 'USD' && !empty($seatmap['decks'])) {
        //             $exchange_rate = $this->get_currency_to_usd_rate($seat_currency);
        //             if ($exchange_rate && $exchange_rate != 1) {
        //                 foreach ($seatmap['decks'] as &$deck) {
        //                     foreach (($deck['seats'] ?? []) as &$seat) {
        //                         if (isset($seat['travelerPricing'])) {
        //                             foreach ($seat['travelerPricing'] as &$tp) {
        //                                 if (isset($tp['price']['total'])) {
        //                                     $tp['price']['total'] = round((float)$tp['price']['total'] * $exchange_rate, 2);
        //                                     $tp['price']['currency'] = 'USD';
        //                                 }
        //                             }
        //                         }
        //                     }
        //                 }
        //             }
        //         }
        //     }
        // }

        if (isset($seatmap_result['data']) && is_array($seatmap_result['data'])) {
            foreach ($seatmap_result['data'] as &$seatmap) {
                if (empty($seatmap['decks'])) continue;

                // Detect currency from first available seat's travelerPricing
                $seat_currency = 'USD';
                foreach ($seatmap['decks'] as $deck) {
                    foreach (($deck['seats'] ?? []) as $seat) {
                        if (!empty($seat['travelerPricing'])) {
                            foreach ($seat['travelerPricing'] as $tp) {
                                if (!empty($tp['price']['currency'])) {
                                    $seat_currency = $tp['price']['currency'];
                                    break 3; // Break all 3 loops
                                }
                            }
                        }
                    }
                }

                // if ($seat_currency !== 'USD' && class_exists('Amadex_Currency')) {
                //     $rate = $this->get_currency_to_usd_rate($seat_currency);
                //     if ($rate && $rate != 1) {
                amadex_log('Amadex SeatMap: Detected seat currency: ' . $seat_currency);
                if ($seat_currency !== 'USD') {
                    $rate = $this->get_currency_to_usd_rate($seat_currency);
                    amadex_log('Amadex SeatMap: Exchange rate ' . $seat_currency . ' to USD: ' . ($rate ?? 'null'));
                    if ($rate && $rate != 1) {
                        foreach ($seatmap['decks'] as &$deck) {
                            foreach (($deck['seats'] ?? []) as &$seat) {
                                if (isset($seat['travelerPricing'])) {
                                    foreach ($seat['travelerPricing'] as &$tp) {
                                        if (isset($tp['price']['total'])) {
                                            $tp['price']['total'] = round((float)$tp['price']['total'] * $rate, 2);
                                            $tp['price']['currency'] = 'USD';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        wp_send_json_success($seatmap_result);
    }

    /**
     * AJAX: Price flight offer with selected seats
     */
    public function price_selected_seats()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'amadex')));
            return;
        }

        // Get flight offer from request
        $flight_offer_json = isset($_POST['flight_offer']) ? stripslashes($_POST['flight_offer']) : '';
        $selected_seats_json = isset($_POST['selected_seats']) ? stripslashes($_POST['selected_seats']) : '';

        if (empty($flight_offer_json)) {
            wp_send_json_error(array('message' => __('Flight offer is required', 'amadex')));
            return;
        }

        $flight_offer = json_decode($flight_offer_json, true);
        $selected_seats = !empty($selected_seats_json) ? json_decode($selected_seats_json, true) : array();

        if (!is_array($flight_offer)) {
            wp_send_json_error(array('message' => __('Invalid flight offer data', 'amadex')));
            return;
        }

        if (!is_array($selected_seats)) {
            $selected_seats = array();
        }

        // Get API instance
        if (!class_exists('Amadex_API')) {
            wp_send_json_error(array('message' => __('API class not available', 'amadex')));
            return;
        }

        $api = new Amadex_API();

        // Call Flight Offers Price API with seats
        $pricing_result = $api->price_flight_offer_with_seats($flight_offer, $selected_seats);

        if (is_wp_error($pricing_result)) {
            amadex_log('Amadex Price Seats AJAX Error: ' . $pricing_result->get_error_message());
            wp_send_json_error(array(
                'message' => $pricing_result->get_error_message(),
                'code' => $pricing_result->get_error_code(),
                'data' => $pricing_result->get_error_data()
            ));
            return;
        }

        wp_send_json_success($pricing_result);
    }

    /**
     * Convert currency AJAX handler
     */
    /**
     * Convert currency amount AJAX handler
     * 
     * When Regional Settings System is disabled, this endpoint returns the original
     * amount in USD without performing any conversion, or returns an error if
     * conversion is attempted from/to non-USD currencies.
     * 
     * @since 1.0.0
     * @return void Sends JSON response via wp_send_json_success() or wp_send_json_error()
     * 
     * @example
     * // AJAX call to convert currency
     * // Returns original amount if regional settings disabled and currencies are USD
     * // Returns error if attempting to convert non-USD when disabled
     */
    public function convert_currency()
    {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_currency_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        // Check if currency class exists
        if (!class_exists('Amadex_Currency')) {
            amadex_log('Amadex Currency class not available in convert_currency()', 'error');
            wp_send_json_error(array('message' => __('Currency conversion service not available.', 'amadex')));
            return;
        }

        // Sanitize and validate input
        $amount = floatval($_POST['amount'] ?? 0);
        $from_currency = strtoupper(sanitize_text_field($_POST['from_currency'] ?? 'USD'));
        $to_currency = strtoupper(sanitize_text_field($_POST['to_currency'] ?? 'USD'));

        // Validate currency codes
        if (!Amadex_Currency::is_valid_currency($from_currency) || !Amadex_Currency::is_valid_currency($to_currency)) {
            amadex_log('Invalid currency codes in convert_currency(): from=' . $from_currency . ', to=' . $to_currency, 'warning');
            wp_send_json_error(array('message' => __('Invalid currency code.', 'amadex')));
            return;
        }

        // Check if regional settings system is enabled (using cached helper method)
        if (!Amadex_Currency::is_regional_settings_enabled()) {
            amadex_log('Regional Settings System disabled - currency conversion blocked in convert_currency()');

            // If both currencies are USD, return original amount (no conversion needed)
            if ($from_currency === 'USD' && $to_currency === 'USD') {
                wp_send_json_success(array(
                    'amount' => $amount,
                    'original_amount' => $amount,
                    'from_currency' => 'USD',
                    'to_currency' => 'USD',
                    'exchange_rate' => 1.0,
                    'formatted' => Amadex_Currency::format($amount, 'USD'),
                    'regional_settings_disabled' => true // Flag to indicate system is disabled
                ));
                return;
            }

            // If attempting to convert non-USD currencies when disabled, return error
            wp_send_json_error(array(
                'message' => __('Currency conversion is disabled. All prices are displayed in USD.', 'amadex'),
                'regional_settings_disabled' => true
            ));
            return;
        }

        // Regional settings enabled - perform conversion
        try {
            $converted_amount = Amadex_Currency::convert($amount, $from_currency, $to_currency);
            $rate = Amadex_Currency::get_exchange_rate($from_currency, $to_currency);
            $formatted = Amadex_Currency::format($converted_amount, $to_currency);

            wp_send_json_success(array(
                'amount' => $converted_amount,
                'original_amount' => $amount,
                'from_currency' => $from_currency,
                'to_currency' => $to_currency,
                'exchange_rate' => $rate,
                'formatted' => $formatted,
                'regional_settings_disabled' => false // Flag to indicate system is enabled
            ));
        } catch (Exception $e) {
            amadex_log('Amadex Currency Conversion Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(array('message' => __('Currency conversion failed.', 'amadex')));
        }
    }

    /**
     * Get exchange rate AJAX handler
     * 
     * When Regional Settings System is disabled, this endpoint returns exchange rate 1.0
     * for USD to USD conversions, or returns an error if attempting to get rates for
     * non-USD currencies.
     * 
     * @since 1.0.0
     * @return void Sends JSON response via wp_send_json_success() or wp_send_json_error()
     * 
     * @example
     * // AJAX call to get exchange rate
     * // Returns 1.0 if regional settings disabled and currencies are USD
     * // Returns error if attempting to get non-USD rates when disabled
     */
    public function get_exchange_rate()
    {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_currency_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        // Check if currency class exists
        if (!class_exists('Amadex_Currency')) {
            amadex_log('Amadex Currency class not available in get_exchange_rate()', 'error');
            wp_send_json_error(array('message' => __('Currency conversion service not available.', 'amadex')));
            return;
        }

        // Sanitize and validate input
        $from_currency = strtoupper(sanitize_text_field($_POST['from_currency'] ?? 'USD'));
        $to_currency = strtoupper(sanitize_text_field($_POST['to_currency'] ?? 'USD'));

        // Validate currency codes
        if (!Amadex_Currency::is_valid_currency($from_currency) || !Amadex_Currency::is_valid_currency($to_currency)) {
            amadex_log('Invalid currency codes in get_exchange_rate(): from=' . $from_currency . ', to=' . $to_currency, 'warning');
            wp_send_json_error(array('message' => __('Invalid currency code.', 'amadex')));
            return;
        }

        // Check if regional settings system is enabled (using cached helper method)
        if (!Amadex_Currency::is_regional_settings_enabled()) {
            amadex_log('Regional Settings System disabled - exchange rate lookup blocked in get_exchange_rate()');

            // If both currencies are USD, return rate 1.0 (no conversion needed)
            if ($from_currency === 'USD' && $to_currency === 'USD') {
                wp_send_json_success(array(
                    'rate' => 1.0,
                    'from_currency' => 'USD',
                    'to_currency' => 'USD',
                    'regional_settings_disabled' => true // Flag to indicate system is disabled
                ));
                return;
            }

            // If attempting to get non-USD rates when disabled, return error
            wp_send_json_error(array(
                'message' => __('Exchange rate lookup is disabled. All prices use USD.', 'amadex'),
                'regional_settings_disabled' => true
            ));
            return;
        }

        // Regional settings enabled - fetch exchange rate
        try {
            $rate = Amadex_Currency::get_exchange_rate($from_currency, $to_currency);

            wp_send_json_success(array(
                'rate' => $rate,
                'from_currency' => $from_currency,
                'to_currency' => $to_currency,
                'regional_settings_disabled' => false // Flag to indicate system is enabled
            ));
        } catch (Exception $e) {
            amadex_log('Amadex Exchange Rate Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(array('message' => __('Failed to fetch exchange rate.', 'amadex')));
        }
    }

    /**
     * Get user location and currency based on IP
     * 
     * When Regional Settings System is disabled, this endpoint immediately returns
     * USA/USD/en-US without performing any IP geolocation, improving performance
     * and ensuring consistent behavior.
     * 
     * @since 1.0.0
     * @return void Sends JSON response via wp_send_json_success() or wp_send_json_error()
     * 
     * @example
     * // AJAX call to get user location
     * // Returns USA/USD/en-US if regional settings disabled
     * // Otherwise returns detected location based on IP
     */
    public function get_user_location()
    {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_currency_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        // Check if currency class exists
        if (!class_exists('Amadex_Currency')) {
            amadex_log('Amadex Currency class not available in get_user_location()', 'error');
            wp_send_json_error(array('message' => __('Currency service not available.', 'amadex')));
            return;
        }

        // Check if regional settings system is enabled (using cached helper method)
        if (!Amadex_Currency::is_regional_settings_enabled()) {
            amadex_log('Regional Settings System disabled - returning USA/USD/en-US in get_user_location()');

            // Return USA/USD/en-US immediately without geolocation
            wp_send_json_success(array(
                'country_code' => 'US',
                'country_name' => 'United States',
                'currency' => 'USD',
                'currency_name' => 'US Dollar',
                'currency_symbol' => '$',
                'language' => 'en-US',
                'language_name' => 'English (United States)',
                'regional_settings_disabled' => true // Flag to indicate system is disabled
            ));
            return;
        }

        try {
            // Regional settings enabled - perform geolocation
            $country_code = Amadex_Currency::get_country_from_ip();
            $currency = Amadex_Currency::get_currency_by_country($country_code);
            $language = Amadex_Currency::get_default_language();

            // Get country info for language override
            $country_info = Amadex_Currency::get_country_info($country_code);
            if ($country_info && isset($country_info['language'])) {
                $language = $country_info['language'];
            }

            // Validate currency
            if (!Amadex_Currency::is_valid_currency($currency)) {
                amadex_log('Invalid currency detected: ' . $currency . ' - falling back to USD', 'warning');
                $currency = 'USD'; // Fallback
            }

            wp_send_json_success(array(
                'country_code' => $country_code,
                'country_name' => $country_info ? $country_info['name'] : 'United States',
                'currency' => $currency,
                'currency_name' => Amadex_Currency::get_currency_name($currency),
                'currency_symbol' => Amadex_Currency::get_currency_symbol($currency),
                'language' => $language,
                'language_name' => Amadex_Currency::get_language_info($language) ? Amadex_Currency::get_language_info($language)['name'] : 'English (United States)',
                'regional_settings_disabled' => false // Flag to indicate system is enabled
            ));
        } catch (Exception $e) {
            amadex_log('Amadex Location Detection Error: ' . $e->getMessage(), 'error');
            wp_send_json_error(array('message' => __('Failed to detect location.', 'amadex')));
        }
    }

    /**
     * Create Stripe PaymentIntent AJAX handler
     */
    public function create_payment_intent()
    {
        // Log incoming request for debugging
        amadex_log('Amadex PaymentIntent AJAX: Request received');
        amadex_log('Amadex PaymentIntent AJAX: POST data - ' . print_r($_POST, true));
        amadex_log('Amadex PaymentIntent AJAX: User logged in - ' . (is_user_logged_in() ? 'Yes' : 'No'));
        amadex_log('Amadex PaymentIntent AJAX: Action - ' . ($_POST['action'] ?? 'NOT SET'));
        amadex_log('Amadex PaymentIntent AJAX: Nonce received - ' . (isset($_POST['nonce']) ? substr($_POST['nonce'], 0, 10) . '...' : 'NOT SET'));

        // Verify nonce
        if (!isset($_POST['nonce'])) {
            amadex_log('Amadex PaymentIntent AJAX: Nonce not provided in request');
            wp_send_json_error(array('message' => __('Security check failed: Nonce not provided.', 'amadex')));
            return;
        }

        $nonce_verified = wp_verify_nonce($_POST['nonce'], 'amadex_nonce');
        amadex_log('Amadex PaymentIntent AJAX: Nonce verification result - ' . ($nonce_verified ? 'PASSED' : 'FAILED'));

        if (!$nonce_verified) {
            amadex_log('Amadex PaymentIntent AJAX: Nonce verification failed. Expected action: amadex_nonce');
            amadex_log('Amadex PaymentIntent AJAX: Received nonce: ' . $_POST['nonce']);
            wp_send_json_error(array('message' => __('Security check failed: Invalid nonce. Please refresh the page and try again.', 'amadex')));
            return;
        }

        // Get payment settings
        $payment_settings = get_option('amadex_payment_settings', array());

        // Verify Stripe is the selected gateway (normalize to lowercase)
        $default_gateway = isset($payment_settings['default_card_gateway']) ? strtolower(trim($payment_settings['default_card_gateway'])) : 'nmi';
        if ($default_gateway !== 'stripe') {
            wp_send_json_error(array('message' => __('Stripe is not the selected payment gateway.', 'amadex')));
            return;
        }

        // Validate Stripe keys exist
        $stripe_secret_key = isset($payment_settings['stripe_secret_key']) ? trim($payment_settings['stripe_secret_key']) : '';
        if (empty($stripe_secret_key)) {
            wp_send_json_error(array('message' => __('Stripe Secret Key is not configured. Please configure it in Amadex Settings → Payment Settings.', 'amadex')));
            return;
        }

        // Get and validate request data
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        // Note: payment_method_id is optional when using manual capture with frontend confirmation
        // The payment method will be attached during confirmCardPayment on the frontend
        $payment_method_id = isset($_POST['payment_method_id']) ? sanitize_text_field($_POST['payment_method_id']) : '';
        $booking_reference = isset($_POST['booking_reference']) ? sanitize_text_field($_POST['booking_reference']) : 'temp-' . time();

        // Get flight data for industry metadata (optional but recommended for flight bookings)
        $flight_data_raw = null;
        if (isset($_POST['flight_data'])) {
            // Handle JSON string or array
            if (is_string($_POST['flight_data'])) {
                $flight_data_raw = json_decode(stripslashes($_POST['flight_data']), true);
            } else {
                $flight_data_raw = $_POST['flight_data'];
            }
        }

        $flight_data = null;
        if ($flight_data_raw && is_array($flight_data_raw)) {
            // Sanitize flight data array
            $flight_data = array(
                'booking_reference' => sanitize_text_field($flight_data_raw['booking_reference'] ?? ''),
                'carrier_name' => sanitize_text_field($flight_data_raw['carrier_name'] ?? ''),
                'carrier_iata' => sanitize_text_field($flight_data_raw['carrier_iata'] ?? ''),
                'passenger_name' => sanitize_text_field($flight_data_raw['passenger_name'] ?? ''),
                'departure_airport' => sanitize_text_field($flight_data_raw['departure_airport'] ?? ''),
                'arrival_airport' => sanitize_text_field($flight_data_raw['arrival_airport'] ?? ''),
                'departure_date' => sanitize_text_field($flight_data_raw['departure_date'] ?? ''),
                'arrival_date' => sanitize_text_field($flight_data_raw['arrival_date'] ?? ''),
                'ticket_class' => sanitize_text_field($flight_data_raw['ticket_class'] ?? ''),
                'fare_basis' => sanitize_text_field($flight_data_raw['fare_basis'] ?? ''),
                'ticketing_agent' => sanitize_text_field($flight_data_raw['ticketing_agent'] ?? 'Travelay')
            );
        }

        if ($amount <= 0) {
            wp_send_json_error(array('message' => __('Invalid payment amount.', 'amadex')));
            return;
        }

        // Note: For manual capture with frontend confirmation, payment_method_id is optional
        // We validate it only if provided, but it's not required for PaymentIntent creation
        // The payment method will be attached during confirmCardPayment on the frontend
        if (!empty($payment_method_id) && strpos($payment_method_id, 'pm_') !== 0) {
            wp_send_json_error(array('message' => __('Invalid payment method format.', 'amadex')));
            return;
        }

        // Load Stripe payment class
        if (!class_exists('Amadex_Payment_Stripe')) {
            require_once AMADEX_PATH . 'includes/class-amadex-payment-stripe.php';
        }

        // CRITICAL: Load Amadex_Payment_Stripe class - constructor handles library loading
        // Based on research: NEVER load init.php directly in AJAX handler
        // Let the class constructor's load_stripe_library() method handle it with robust checks
        // This prevents "class already declared" fatal errors from multi-plugin conflicts
        if (!class_exists('Amadex_Payment_Stripe')) {
            require_once AMADEX_PATH . 'includes/class-amadex-payment-stripe.php';
        }

        // The Amadex_Payment_Stripe constructor will:
        // 1. Check for existing Stripe classes (multiple classes to detect partial loads)
        // 2. Prefer Composer autoloader if available (Stripe's recommended approach)
        // 3. Fallback to manual init.php only if no classes exist and Composer unavailable
        // 4. Use require_once to prevent duplicate class declarations
        // This is the safest approach based on Stripe docs and community best practices

        try {
            // Log request details for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                amadex_log('Amadex PaymentIntent: Starting creation');
                error_log('  Amount: ' . $amount);
                error_log('  Payment Method ID: ' . substr($payment_method_id, 0, 20) . '...');
                error_log('  Booking Reference: ' . $booking_reference);
                error_log('  Flight Data Present: ' . ($flight_data ? 'Yes' : 'No'));
                error_log('  Stripe class exists: ' . (class_exists('\Stripe\Stripe') ? 'YES' : 'NO'));
            }

            // Instantiate the Stripe payment class
            // The class constructor will safely load the library using require_once
            try {
                $stripe_payment = new Amadex_Payment_Stripe();
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    amadex_log('Amadex PaymentIntent: Amadex_Payment_Stripe instance created successfully');
                }
            } catch (\Error $e) {
                amadex_log('Amadex PaymentIntent: Fatal error creating Amadex_Payment_Stripe - ' . $e->getMessage());
                error_log('  File: ' . $e->getFile() . ':' . $e->getLine());
                error_log('  Trace: ' . $e->getTraceAsString());
                wp_send_json_error(array('message' => __('Failed to initialize payment processor: ', 'amadex') . $e->getMessage()));
                return;
            } catch (\Exception $e) {
                amadex_log('Amadex PaymentIntent: Exception creating Amadex_Payment_Stripe - ' . $e->getMessage());
                error_log('  File: ' . $e->getFile() . ':' . $e->getLine());
                wp_send_json_error(array('message' => __('Failed to initialize payment processor: ', 'amadex') . $e->getMessage()));
                return;
            }

            // Optional: full price breakdown for Stripe dashboard (flight base, tax, addons, discounts, etc.)
            $price_breakdown = null;
            if (isset($_POST['price_breakdown']) && is_string($_POST['price_breakdown'])) {
                $price_breakdown = json_decode(stripslashes($_POST['price_breakdown']), true);
            } elseif (isset($_POST['price_breakdown']) && is_array($_POST['price_breakdown'])) {
                $price_breakdown = array_map('sanitize_text_field', $_POST['price_breakdown']);
            }

            $payment_data = array(
                'amount' => $amount,
                'currency' => 'USD',
                'payment_method_id' => $payment_method_id,
                'booking_reference' => $booking_reference,
                'flight_data' => $flight_data,
                'price_breakdown' => $price_breakdown
            );

            if (defined('WP_DEBUG') && WP_DEBUG) {
                amadex_log('Amadex PaymentIntent: Calling create_payment_intent method');
            }

            $result = $stripe_payment->create_payment_intent($payment_data);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                amadex_log('Amadex PaymentIntent: Method returned, checking result');
            }

            if (is_wp_error($result)) {
                amadex_log('Amadex PaymentIntent: WP_Error - ' . $result->get_error_message());
                wp_send_json_error(array('message' => $result->get_error_message()));
                return;
            }

            if (!$result['success']) {
                $error_msg = $result['response_text'] ?? $result['error'] ?? 'Failed to create payment intent.';
                amadex_log('Amadex PaymentIntent: Creation failed - ' . $error_msg);
                wp_send_json_error(array('message' => $error_msg));
                return;
            }

            // Success: return client secret and intent ID
            amadex_log('Amadex PaymentIntent: Created successfully - ' . $result['payment_intent_id']);
            wp_send_json_success(array(
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id']
            ));
        } catch (\Stripe\Exception\ApiErrorException $e) {
            amadex_log('Amadex PaymentIntent: Stripe API Exception - ' . $e->getMessage());
            amadex_log('Amadex PaymentIntent: Stripe Error Type - ' . $e->getStripeCode());
            amadex_log('Amadex PaymentIntent: Stripe Error Code - ' . ($e->getStripeCode() ?? 'N/A'));
            amadex_log('Amadex PaymentIntent: Stripe HTTP Status - ' . ($e->getHttpStatus() ?? 'N/A'));
            wp_send_json_error(array('message' => __('Stripe API error: ', 'amadex') . $e->getMessage()));
        } catch (\Error $e) {
            // Catch PHP 7+ errors (fatal errors, type errors, etc.)
            amadex_log('Amadex PaymentIntent: PHP Error - ' . $e->getMessage());
            amadex_log('Amadex PaymentIntent: Error File - ' . $e->getFile() . ':' . $e->getLine());
            amadex_log('Amadex PaymentIntent: Error Trace - ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => __('Payment processing error: ', 'amadex') . $e->getMessage()));
        } catch (Exception $e) {
            amadex_log('Amadex PaymentIntent: General Exception - ' . $e->getMessage());
            amadex_log('Amadex PaymentIntent: Exception File - ' . $e->getFile() . ':' . $e->getLine());
            amadex_log('Amadex PaymentIntent: Exception Trace - ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => __('Failed to create payment intent: ', 'amadex') . $e->getMessage()));
        }
    }

    /**
     * Handle Stripe webhooks (for payment status updates)
     */
    public function handle_stripe_webhook()
    {
        // Get webhook payload
        $payload = @file_get_contents('php://input');
        $sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';

        if (empty($payload)) {
            amadex_log('Amadex Stripe Webhook: Empty payload received');
            status_header(400);
            exit('Empty payload');
        }

        // Get webhook secret from settings
        $payment_settings = get_option('amadex_payment_settings', array());
        $webhook_secret = isset($payment_settings['stripe_webhook_secret']) ? trim($payment_settings['stripe_webhook_secret']) : '';

        // If webhook secret is not configured, log and continue (for testing)
        if (empty($webhook_secret)) {
            amadex_log('Amadex Stripe Webhook: WARNING - Webhook secret not configured. Skipping signature verification.');
        } else {
            // Verify webhook signature
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $payload,
                    $sig_header,
                    $webhook_secret
                );
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                amadex_log('Amadex Stripe Webhook: Signature verification failed - ' . $e->getMessage());
                status_header(400);
                exit('Invalid signature');
            }
        }

        // Parse event if signature verification skipped
        if (!isset($event)) {
            $event = json_decode($payload, true);
        }

        // Log received event
        amadex_log('Amadex Stripe Webhook: Event received - ' . ($event['type'] ?? 'unknown'));

        // Handle different event types
        switch ($event['type'] ?? '') {
            case 'payment_intent.succeeded':
                // Payment successfully completed
                $payment_intent = $event['data']['object'];
                $booking_reference = $payment_intent['metadata']['booking_reference'] ?? '';
                amadex_log('Amadex Stripe Webhook: PaymentIntent succeeded - ' . $payment_intent['id'] . ' (Booking: ' . $booking_reference . ')');
                // Update booking status if needed
                // TODO: Implement booking status update logic here
                break;

            case 'payment_intent.payment_failed':
                // Payment failed
                $payment_intent = $event['data']['object'];
                $booking_reference = $payment_intent['metadata']['booking_reference'] ?? '';
                amadex_log('Amadex Stripe Webhook: PaymentIntent failed - ' . $payment_intent['id'] . ' (Booking: ' . $booking_reference . ')');
                // Handle failed payment notification
                // TODO: Implement failure notification logic here
                break;

            case 'payment_intent.requires_capture':
                // Payment authorized and ready for capture
                $payment_intent = $event['data']['object'];
                $booking_reference = $payment_intent['metadata']['booking_reference'] ?? '';
                amadex_log('Amadex Stripe Webhook: PaymentIntent requires capture - ' . $payment_intent['id'] . ' (Booking: ' . $booking_reference . ')');
                break;

            case 'charge.dispute.created':
                // Chargeback/dispute created
                $dispute = $event['data']['object'];
                $charge_id = $dispute['charge'] ?? '';
                amadex_log('Amadex Stripe Webhook: Dispute created - ' . $dispute['id'] . ' (Charge: ' . $charge_id . ')');
                // TODO: Implement dispute handling logic here
                break;

            case 'charge.refunded':
                // Refund completed
                $refund = $event['data']['object'];
                $charge_id = $refund['charge'] ?? '';
                amadex_log('Amadex Stripe Webhook: Refund completed - ' . $refund['id'] . ' (Charge: ' . $charge_id . ')');
                // TODO: Implement refund notification logic here
                break;

            default:
                amadex_log('Amadex Stripe Webhook: Unhandled event type - ' . ($event['type'] ?? 'unknown'));
        }

        // Return 200 OK to acknowledge receipt
        status_header(200);
        exit('OK');
    }

    /**
     * Process Stripe refund (AJAX handler)
     */
    public function process_stripe_refund()
    {
        // Verify nonce (admin only for refunds)
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        // Check user capabilities (admin only)
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'amadex')));
            return;
        }

        // Get refund parameters
        $payment_intent_id = isset($_POST['payment_intent_id']) ? sanitize_text_field($_POST['payment_intent_id']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null; // null = full refund
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'requested_by_customer';

        if (empty($payment_intent_id)) {
            wp_send_json_error(array('message' => __('PaymentIntent ID is required.', 'amadex')));
            return;
        }

        // Load Stripe payment class
        if (!class_exists('Amadex_Payment_Stripe')) {
            require_once AMADEX_PATH . 'includes/class-amadex-payment-stripe.php';
        }

        try {
            $stripe_payment = new Amadex_Payment_Stripe();
            $result = $stripe_payment->refund_payment_intent($payment_intent_id, $amount, $reason);

            if (!$result['success']) {
                $error_msg = $result['response_text'] ?? 'Failed to process refund.';
                amadex_log('Amadex Stripe Refund: Failed - ' . $error_msg);
                wp_send_json_error(array('message' => $error_msg));
                return;
            }

            // Success
            amadex_log('Amadex Stripe Refund: Success - ' . $result['refund_id']);
            wp_send_json_success(array(
                'refund_id' => $result['refund_id'],
                'amount' => $result['amount'],
                'status' => $result['status']
            ));
        } catch (Exception $e) {
            amadex_log('Amadex Stripe Refund: Exception - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to process refund: ', 'amadex') . $e->getMessage()));
        }
    }

    /**
     * Create Stripe Elements Session (for deferred PaymentIntent pattern)
     * This matches Alternative Airlines' implementation
     */
    /**
     * Create Stripe Checkout Session (Standard Redirect Flow)
     * Simplified implementation following Stripe PHP documentation
     * https://docs.stripe.com/payments/accept-a-payment
     */
    public function create_elements_session()
    {
        // Log function entry immediately (for debugging)
        error_log('Amadex: create_elements_session() called');
        error_log('Amadex: POST action: ' . ($_POST['action'] ?? 'NOT SET'));
        error_log('Amadex: POST amount: ' . ($_POST['amount'] ?? 'NOT SET'));

        // Set headers and output buffering FIRST
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            http_response_code(200);
        }
        ob_start();

        // Register shutdown function to catch fatal errors
        $ajax_instance = $this;
        $action_name = 'amadex_create_elements_session';

        register_shutdown_function(function () use ($ajax_instance, $action_name) {
            if (!isset($_POST['action']) || $_POST['action'] !== $action_name) {
                return;
            }

            $error = error_get_last();
            if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                $error_message = $error['message'] ?? '';
                $is_stripe_error = (strpos($error_message, 'Cannot declare class') !== false &&
                    (strpos($error_message, 'Stripe') !== false ||
                        strpos($error_message, 'RateLimitException') !== false));

                error_log('Amadex: FATAL ERROR in create_elements_session');
                error_log('Amadex: Error message: ' . $error_message);
                error_log('Amadex: Error file: ' . ($error['file'] ?? 'unknown'));
                error_log('Amadex: Error line: ' . ($error['line'] ?? 0));

                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }

                if (!headers_sent()) {
                    @header('Content-Type: application/json; charset=utf-8', true, 500);
                }

                $user_message = $is_stripe_error
                    ? __('Stripe library conflict detected. Another plugin has already loaded Stripe. Please check WordPress debug log for details.', 'amadex')
                    : __('Fatal server error occurred. Please check WordPress debug log for details.', 'amadex');

                if (function_exists('wp_send_json_error')) {
                    @wp_send_json_error(array(
                        'message' => $user_message,
                        'error_type' => $is_stripe_error ? 'stripe_class_conflict' : 'fatal_error',
                        'error_file' => $error['file'] ?? 'unknown',
                        'error_line' => $error['line'] ?? 0,
                        'error_message' => $error_message
                    ));
                } else {
                    // Fallback if wp_send_json_error doesn't exist
                    echo json_encode(array(
                        'success' => false,
                        'data' => array(
                            'message' => $user_message,
                            'error_type' => $is_stripe_error ? 'stripe_class_conflict' : 'fatal_error',
                            'error_file' => $error['file'] ?? 'unknown',
                            'error_line' => $error['line'] ?? 0
                        )
                    ));
                }
                @exit;
            }
        });

        // Wrap entire function in try-catch
        try {
            error_log('Amadex: Starting try block in create_elements_session');
            // Verify nonce
            error_log('Amadex: Step 1 - Verifying nonce');
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
                error_log('Amadex: Nonce verification failed');
                $this->amadex_safe_ob_clean();
                wp_send_json_error(array(
                    'message' => __('Security check failed. Please refresh the page and try again.', 'amadex'),
                    'error_type' => 'nonce_invalid'
                ));
                $this->amadex_safe_ob_end_flush();
                return;
            }
            error_log('Amadex: Step 1 - Nonce verified');

            // Get payment settings
            error_log('Amadex: Step 2 - Getting payment settings');
            $payment_settings = get_option('amadex_payment_settings', array());
            $default_gateway = isset($payment_settings['default_card_gateway']) ? strtolower(trim($payment_settings['default_card_gateway'])) : 'nmi';
            error_log('Amadex: Default gateway: ' . $default_gateway);

            if ($default_gateway !== 'stripe') {
                error_log('Amadex: Stripe is not the selected gateway');
                $this->amadex_safe_ob_clean();
                wp_send_json_error(array('message' => __('Stripe is not the selected payment gateway.', 'amadex')));
                $this->amadex_safe_ob_end_flush();
                return;
            }

            // Get amount and currency
            error_log('Amadex: Step 3 - Getting amount and currency');
            $amount = floatval($_POST['amount'] ?? 0);
            $currency = sanitize_text_field($_POST['currency'] ?? 'usd');
            error_log('Amadex: Amount: ' . $amount . ', Currency: ' . $currency);

            if ($amount <= 0) {
                $this->amadex_safe_ob_clean();
                wp_send_json_error(array(
                    'message' => __('Invalid payment amount. Please check your booking total.', 'amadex'),
                    'error_type' => 'invalid_amount'
                ));
                $this->amadex_safe_ob_end_flush();
                return;
            }

            // Validate currency format
            if (empty($currency) || !preg_match('/^[a-z]{3}$/i', $currency)) {
                $this->amadex_safe_ob_clean();
                wp_send_json_error(array(
                    'message' => __('Invalid currency format. Currency must be a 3-letter code (e.g., USD, EUR, GBP).', 'amadex'),
                    'error_type' => 'invalid_currency_format'
                ));
                $this->amadex_safe_ob_end_flush();
                return;
            }

            // Load Stripe using __DIR__ (path-independent: this file is in includes/, paths relative to includes/)
            error_log('Amadex: Step 4 - Loading Stripe (path-independent)');
            $includes_dir = __DIR__;
            if (!defined('AMADEX_PATH')) {
                define('AMADEX_PATH', str_replace('\\', '/', dirname($includes_dir) . '/'));
            }
            $stripe_init = $includes_dir . '/vendor/stripe/stripe-php/init.php';
            if (!class_exists('\Stripe\StripeClient', false) && file_exists($stripe_init)) {
                require_once $stripe_init;
                error_log('Amadex: Stripe init.php loaded');
            }
            if (!class_exists('Amadex_Payment_Stripe', false)) {
                $stripe_class_file = $includes_dir . '/class-amadex-payment-stripe.php';
                if (!file_exists($stripe_class_file)) {
                    error_log('Amadex: ERROR - Stripe class file not found: ' . $stripe_class_file);
                    $this->amadex_safe_ob_clean();
                    wp_send_json_error(array(
                        'message' => __('Stripe payment class file not found.', 'amadex'),
                        'error_type' => 'file_not_found',
                        'expected_path' => $stripe_class_file
                    ));
                    $this->amadex_safe_ob_end_flush();
                    return;
                }
                require_once $stripe_class_file;
                error_log('Amadex: Stripe class file required');
            }
            if (!class_exists('Amadex_Payment_Stripe')) {
                $this->amadex_safe_ob_clean();
                wp_send_json_error(array(
                    'message' => __('Stripe payment class could not be loaded.', 'amadex'),
                    'error_type' => 'configuration_error'
                ));
                $this->amadex_safe_ob_end_flush();
                return;
            }

            // Instantiate Stripe payment class
            error_log('Amadex: Step 6 - Instantiating Amadex_Payment_Stripe');
            try {
                $stripe_payment = new Amadex_Payment_Stripe();
                error_log('Amadex: Amadex_Payment_Stripe instantiated successfully');
            } catch (\Error | Exception | \Throwable $e) {
                $error_msg = $e->getMessage();
                $is_class_declare_error = (strpos($error_msg, 'Cannot declare class') !== false &&
                    strpos($error_msg, 'Stripe') !== false);

                error_log('Amadex: ERROR - Failed to instantiate Amadex_Payment_Stripe');
                error_log('Amadex: Error message: ' . $error_msg);
                error_log('Amadex: Error file: ' . $e->getFile() . ':' . $e->getLine());
                error_log('Amadex: Is class declare error: ' . ($is_class_declare_error ? 'YES' : 'NO'));

                $this->amadex_safe_ob_clean();
                wp_send_json_error(array(
                    'message' => $is_class_declare_error
                        ? __('Stripe library conflict detected. Another plugin has already loaded Stripe. Please check WordPress debug log for details.', 'amadex')
                        : __('Failed to load Stripe payment class: ', 'amadex') . $error_msg,
                    'error_type' => $is_class_declare_error ? 'stripe_class_conflict' : 'class_instantiation_error',
                    'error_details' => $error_msg,
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ));
                $this->amadex_safe_ob_end_flush();
                return;
            }

            // Get booking reference and prepare metadata
            $booking_reference = isset($_POST['booking_reference']) ? sanitize_text_field($_POST['booking_reference']) : 'temp-' . time();
            $metadata = array(
                'booking_reference' => $booking_reference,
                'source' => 'flytravelay_booking'
            );

            // Get flight data if provided
            $flight_data_raw = null;
            if (isset($_POST['flight_data'])) {
                $flight_data_raw = is_string($_POST['flight_data'])
                    ? json_decode(stripslashes($_POST['flight_data']), true)
                    : $_POST['flight_data'];
            }

            if ($flight_data_raw && is_array($flight_data_raw)) {
                if (!empty($flight_data_raw['departure_airport'])) {
                    $metadata['flight_departure'] = sanitize_text_field($flight_data_raw['departure_airport']);
                }
                if (!empty($flight_data_raw['arrival_airport'])) {
                    $metadata['flight_arrival'] = sanitize_text_field($flight_data_raw['arrival_airport']);
                }
                if (!empty($flight_data_raw['carrier_name']) || !empty($flight_data_raw['carrier_iata'])) {
                    $metadata['flight_carrier'] = sanitize_text_field($flight_data_raw['carrier_name'] ?? $flight_data_raw['carrier_iata'] ?? '');
                }
                if (!empty($flight_data_raw['passenger_name'])) {
                    $metadata['passenger_name'] = sanitize_text_field($flight_data_raw['passenger_name']);
                }
                if (!empty($flight_data_raw['ticket_class'])) {
                    $metadata['ticket_class'] = sanitize_text_field($flight_data_raw['ticket_class']);
                }
            }

            // Get payment page URL for cancel; success goes to Stripe return handler (confirmation page directly)
            $general_settings = get_option('amadex_general_settings', array());
            $payment_page_id = isset($general_settings['payment_page']) ? intval($general_settings['payment_page']) : 0;
            $payment_page_url = $payment_page_id ? get_permalink($payment_page_id) : home_url('/complete-payment/');

            // Booking token (st) so Stripe return handler can load booking and complete process_booking
            $booking_token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : (isset($_POST['st']) ? sanitize_text_field($_POST['st']) : '');

            // Success URL: go to Stripe return handler → completes booking server-side → redirect to confirmation page (skip complete-payment)
            // Stripe replaces {CHECKOUT_SESSION_ID} with real session id - must be literal, not URL-encoded
            if (!empty($booking_token)) {
                $success_url = add_query_arg(array(
                    'amadex_stripe_return' => '1',
                    'session_id' => '{CHECKOUT_SESSION_ID}',
                    'st' => $booking_token,
                ), home_url('/'));
                $success_url = str_replace(urlencode('{CHECKOUT_SESSION_ID}'), '{CHECKOUT_SESSION_ID}', $success_url);
            } else {
                $success_args = array(
                    'session_id' => '{CHECKOUT_SESSION_ID}',
                    'booking_reference' => $booking_reference,
                    'status' => 'success',
                );
                $success_url = add_query_arg($success_args, $payment_page_url);
                $success_url = str_replace(urlencode('{CHECKOUT_SESSION_ID}'), '{CHECKOUT_SESSION_ID}', $success_url);
            }

            // Cancel URL: back to payment page
            $cancel_args = array(
                'booking_reference' => $booking_reference,
                'status' => 'cancel',
            );
            if (!empty($booking_token)) {
                $cancel_args['st'] = $booking_token;
            }
            $cancel_url = add_query_arg($cancel_args, $payment_page_url);

            // Validate Stripe secret key
            $stripe_secret_key = isset($payment_settings['stripe_secret_key']) ? trim($payment_settings['stripe_secret_key']) : '';
            if (empty($stripe_secret_key)) {
                $this->amadex_safe_ob_clean();
                wp_send_json_error(array(
                    'message' => __('Stripe Secret Key is not configured. Please go to Amadex Settings → Payment Settings.', 'amadex'),
                    'error_type' => 'no_secret_key'
                ));
                $this->amadex_safe_ob_end_flush();
                return;
            }

            // Create Checkout Session using the simplified method
            $session_data = array(
                'amount' => $amount,
                'currency' => $currency,
                'success_url' => $success_url,
                'cancel_url' => $cancel_url,
                'metadata' => $metadata,
                'capture_method' => 'manual', // Auth-only, like NMI
                'product_name' => 'Flight Booking',
                'product_description' => isset($metadata['flight_departure']) && isset($metadata['flight_arrival'])
                    ? $metadata['flight_departure'] . ' → ' . $metadata['flight_arrival']
                    : 'Flight booking payment'
            );

            // Log before creating session (always log for debugging)
            error_log('Amadex: Step 7 - Creating Stripe Checkout Session');
            error_log('Amadex: Amount: ' . $amount . ' ' . $currency);
            error_log('Amadex: Booking Reference: ' . $booking_reference);
            error_log('Amadex: Success URL: ' . $success_url);
            error_log('Amadex: Cancel URL: ' . $cancel_url);

            try {
                error_log('Amadex: Calling create_checkout_session() method');
                $result = $stripe_payment->create_checkout_session($session_data);
                error_log('Amadex: create_checkout_session() returned');
                error_log('Amadex: Result type: ' . gettype($result));
                error_log('Amadex: Is WP_Error: ' . (is_wp_error($result) ? 'YES' : 'NO'));

                // Handle result
                if (is_wp_error($result)) {
                    $error_msg = $result->get_error_message();
                    $error_code = $result->get_error_code();

                    error_log('Amadex: Stripe Checkout Session creation failed: ' . $error_msg);
                    error_log('Amadex: Error code: ' . $error_code);

                    $this->amadex_safe_ob_clean();
                    wp_send_json_error(array(
                        'message' => $error_msg,
                        'error_type' => $error_code,
                        'error_data' => $result->get_error_data()
                    ));
                    $this->amadex_safe_ob_end_flush();
                    return;
                }

                if (empty($result['success']) || empty($result['session_id']) || empty($result['url'])) {
                    error_log('Amadex: Invalid response from create_checkout_session');
                    error_log('Amadex: Result: ' . print_r($result, true));

                    $this->amadex_safe_ob_clean();
                    wp_send_json_error(array(
                        'message' => __('Failed to create payment session. Please try again.', 'amadex'),
                        'error_type' => 'session_creation_failed',
                        'debug_info' => 'Invalid response structure'
                    ));
                    $this->amadex_safe_ob_end_flush();
                    return;
                }

                // Log success
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Amadex: Stripe Checkout Session created successfully');
                    error_log('  Session ID: ' . $result['session_id']);
                    error_log('  URL: ' . $result['url']);
                }

                // Success - return session data (Stripe recommends redirecting to session.url)
                $this->amadex_safe_ob_clean();
                wp_send_json_success(array(
                    'session_id' => $result['session_id'],
                    'url' => $result['url']
                ));
                $this->amadex_safe_ob_end_flush();
                return;
            } catch (\Throwable $e) {
                error_log('Amadex: Exception creating Checkout Session: ' . $e->getMessage());
                error_log('Amadex: Exception file: ' . $e->getFile() . ':' . $e->getLine());
                error_log('Amadex: Exception trace: ' . $e->getTraceAsString());

                $this->amadex_safe_ob_clean();
                wp_send_json_error(array(
                    'message' => __('Error creating payment session: ', 'amadex') . $e->getMessage(),
                    'error_type' => 'exception',
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ));
                $this->amadex_safe_ob_end_flush();
                return;
            }
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Stripe API errors
            error_log('Amadex: CRITICAL - Stripe API Error in outer catch');
            error_log('Amadex: Error message: ' . $e->getMessage());
            error_log('Amadex: Stripe code: ' . ($e->getStripeCode() ?? 'N/A'));
            error_log('Amadex: HTTP status: ' . ($e->getHttpStatus() ?? 'N/A'));

            $this->amadex_safe_ob_clean();
            wp_send_json_error(array(
                'message' => __('Stripe payment error: ', 'amadex') . $e->getMessage(),
                'error_type' => 'stripe_api_error',
                'stripe_code' => $e->getStripeCode() ?? 'unknown',
                'http_status' => $e->getHttpStatus() ?? 0
            ));
            $this->amadex_safe_ob_end_flush();
            return;
        } catch (\Error $e) {
            // PHP 7+ errors (fatal errors that can be caught)
            $error_msg = $e->getMessage();
            $is_class_declare_error = (strpos($error_msg, 'Cannot declare class') !== false &&
                (strpos($error_msg, 'Stripe') !== false ||
                    strpos($error_msg, 'RateLimitException') !== false));

            error_log('Amadex: CRITICAL - PHP Error in outer catch');
            error_log('Amadex: Error message: ' . $error_msg);
            error_log('Amadex: Error file: ' . $e->getFile() . ':' . $e->getLine());
            error_log('Amadex: Is class declare error: ' . ($is_class_declare_error ? 'YES' : 'NO'));

            $this->amadex_safe_ob_clean();
            wp_send_json_error(array(
                'message' => $is_class_declare_error
                    ? __('Stripe library conflict detected. Another plugin has already loaded Stripe. Please check WordPress debug log for details.', 'amadex')
                    : __('Server error occurred: ', 'amadex') . $error_msg,
                'error_type' => $is_class_declare_error ? 'stripe_class_conflict' : 'php_error',
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_details' => $error_msg
            ));
            $this->amadex_safe_ob_end_flush();
            return;
        } catch (Exception $e) {
            // General exceptions
            $error_msg = $e->getMessage();
            $is_class_declare_error = (strpos($error_msg, 'Cannot declare class') !== false &&
                (strpos($error_msg, 'Stripe') !== false ||
                    strpos($error_msg, 'RateLimitException') !== false));

            error_log('Amadex: CRITICAL - Exception in outer catch');
            error_log('Amadex: Exception type: ' . get_class($e));
            error_log('Amadex: Exception message: ' . $error_msg);
            error_log('Amadex: Exception file: ' . $e->getFile() . ':' . $e->getLine());

            $this->amadex_safe_ob_clean();
            wp_send_json_error(array(
                'message' => $is_class_declare_error
                    ? __('Stripe library conflict detected. Another plugin has already loaded Stripe. Please check WordPress debug log for details.', 'amadex')
                    : __('Error creating payment session: ', 'amadex') . $error_msg,
                'error_type' => $is_class_declare_error ? 'stripe_class_conflict' : 'exception',
                'error_details' => $error_msg . ' in ' . $e->getFile() . ':' . $e->getLine(),
                'error_class' => get_class($e)
            ));
            $this->amadex_safe_ob_end_flush();
            return;
        } catch (\Throwable $e) {
            // Catch any other throwable (PHP 7+)
            $error_msg = $e->getMessage();
            $is_class_declare_error = (strpos($error_msg, 'Cannot declare class') !== false &&
                (strpos($error_msg, 'Stripe') !== false ||
                    strpos($error_msg, 'RateLimitException') !== false));

            error_log('Amadex: CRITICAL - Throwable in outer catch');
            error_log('Amadex: Throwable type: ' . get_class($e));
            error_log('Amadex: Throwable message: ' . $error_msg);
            error_log('Amadex: Throwable file: ' . $e->getFile() . ':' . $e->getLine());

            $this->amadex_safe_ob_clean();
            wp_send_json_error(array(
                'message' => $is_class_declare_error
                    ? __('Stripe library conflict detected. Another plugin has already loaded Stripe. Please check WordPress debug log for details.', 'amadex')
                    : __('Unexpected error occurred: ', 'amadex') . $error_msg,
                'error_type' => $is_class_declare_error ? 'stripe_class_conflict' : 'throwable',
                'error_details' => $error_msg,
                'error_class' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ));
            $this->amadex_safe_ob_end_flush();
            return;
        }
    }

    /**
     * Stripe Diagnostic Endpoint (for debugging)
     * Tests if Stripe library is loading correctly
     */
    public function stripe_diagnostic()
    {
        header('Content-Type: application/json; charset=utf-8');

        $diagnostics = array(
            'php_version' => PHP_VERSION,
            'amadex_path_defined' => defined('AMADEX_PATH'),
            'amadex_path' => defined('AMADEX_PATH') ? AMADEX_PATH : 'NOT DEFINED',
            'stripe_init_exists' => false,
            'stripe_classes' => array(),
            'stripe_loaded' => false,
            'payment_settings' => array(),
            'errors' => array()
        );

        try {
            // Check AMADEX_PATH
            if (!defined('AMADEX_PATH')) {
                $this_file = __FILE__;
                $plugin_path = dirname(dirname($this_file)) . '/';
                if (file_exists($plugin_path . 'includes/vendor/stripe/stripe-php/init.php')) {
                    define('AMADEX_PATH', $plugin_path);
                }
            }

            if (defined('AMADEX_PATH')) {
                $stripe_init_path = AMADEX_PATH . 'includes/vendor/stripe/stripe-php/init.php';
                $diagnostics['stripe_init_exists'] = file_exists($stripe_init_path);
                $diagnostics['stripe_init_path'] = $stripe_init_path;
            }

            // Check declared Stripe classes
            $declared_classes = get_declared_classes();
            foreach ($declared_classes as $class) {
                if (strpos($class, 'Stripe\\') === 0) {
                    $diagnostics['stripe_classes'][] = $class;
                }
            }

            // Check if Stripe classes exist
            $stripe_checks = array(
                'Stripe\Stripe' => class_exists('\Stripe\Stripe', false),
                'Stripe\StripeClient' => class_exists('\Stripe\StripeClient', false),
                'Stripe\Checkout\Session' => class_exists('\Stripe\Checkout\Session', false),
                'Stripe\Exception\RateLimitException' => class_exists('\Stripe\Exception\RateLimitException', false)
            );
            $diagnostics['stripe_class_checks'] = $stripe_checks;

            // Try to load Stripe class
            if (!class_exists('Amadex_Payment_Stripe')) {
                if (defined('AMADEX_PATH')) {
                    $stripe_class_path = AMADEX_PATH . 'includes/class-amadex-payment-stripe.php';
                    if (file_exists($stripe_class_path)) {
                        require_once $stripe_class_path;
                    } else {
                        $diagnostics['errors'][] = 'Stripe class file not found: ' . $stripe_class_path;
                    }
                }
            }

            // Try to instantiate
            if (class_exists('Amadex_Payment_Stripe')) {
                try {
                    $stripe_payment = new Amadex_Payment_Stripe();
                    $diagnostics['stripe_loaded'] = true;
                    $diagnostics['stripe_class_instantiated'] = true;
                } catch (\Throwable $e) {
                    $diagnostics['errors'][] = 'Failed to instantiate Amadex_Payment_Stripe: ' . $e->getMessage();
                    $diagnostics['stripe_class_instantiated'] = false;
                }
            } else {
                $diagnostics['errors'][] = 'Amadex_Payment_Stripe class not found';
            }

            // Get payment settings (without secret key)
            $payment_settings = get_option('amadex_payment_settings', array());
            $diagnostics['payment_settings'] = array(
                'default_gateway' => $payment_settings['default_card_gateway'] ?? 'not_set',
                'stripe_secret_key_set' => !empty($payment_settings['stripe_secret_key']),
                'stripe_secret_key_length' => isset($payment_settings['stripe_secret_key']) ? strlen($payment_settings['stripe_secret_key']) : 0,
                'stripe_secret_key_prefix' => isset($payment_settings['stripe_secret_key']) ? substr($payment_settings['stripe_secret_key'], 0, 7) . '...' : 'not_set'
            );
        } catch (\Throwable $e) {
            $diagnostics['errors'][] = 'Exception during diagnostic: ' . $e->getMessage();
            $diagnostics['exception_file'] = $e->getFile();
            $diagnostics['exception_line'] = $e->getLine();
        }

        wp_send_json_success($diagnostics);
    }

    /**
     * Check Checkout Session status (for complete page after payment)
     */
    public function checkout_session_status()
    {
        // Set JSON header
        header('Content-Type: application/json');

        // Get payment settings
        $payment_settings = get_option('amadex_payment_settings', array());
        $stripe_secret_key = isset($payment_settings['stripe_secret_key']) ? trim($payment_settings['stripe_secret_key']) : '';

        if (empty($stripe_secret_key)) {
            http_response_code(400);
            wp_send_json_error(array('message' => __('Stripe Secret Key is not configured.', 'amadex')));
            return;
        }

        // Get session_id from POST body (JSON) or $_POST
        $json_str = file_get_contents('php://input');
        $json_obj = json_decode($json_str);

        // Try JSON body first, then $_POST
        if ($json_obj && isset($json_obj->session_id)) {
            $session_id = sanitize_text_field($json_obj->session_id);
        } elseif (isset($_POST['session_id'])) {
            $session_id = sanitize_text_field($_POST['session_id']);
        } else {
            http_response_code(400);
            wp_send_json_error(array('message' => __('Session ID is required.', 'amadex')));
            return;
        }

        if (empty($session_id)) {
            http_response_code(400);
            wp_send_json_error(array('message' => __('Session ID is required.', 'amadex')));
            return;
        }

        try {
            // Load Stripe library
            if (!class_exists('\Stripe\Stripe')) {
                if (!class_exists('Amadex_Payment_Stripe')) {
                    require_once AMADEX_PATH . 'includes/class-amadex-payment-stripe.php';
                }
                $stripe_payment = new Amadex_Payment_Stripe();
            }

            // Retrieve Checkout Session with expanded PaymentIntent
            $stripe = new \Stripe\StripeClient($stripe_secret_key);
            $session = $stripe->checkout->sessions->retrieve(
                $session_id,
                array('expand' => array('payment_intent'))
            );

            // Prepare response
            $response = array(
                'status' => $session->status,
                'payment_status' => $session->payment_status,
                'payment_intent_id' => isset($session->payment_intent) && is_object($session->payment_intent)
                    ? $session->payment_intent->id
                    : '',
                'payment_intent_status' => isset($session->payment_intent) && is_object($session->payment_intent)
                    ? $session->payment_intent->status
                    : ''
            );

            wp_send_json_success($response);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            amadex_log('Amadex Checkout Session Status: Stripe API Error - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to retrieve session status: ', 'amadex') . $e->getMessage()));
        } catch (Exception $e) {
            amadex_log('Amadex Checkout Session Status: Error - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to retrieve session status: ', 'amadex') . $e->getMessage()));
        }
    }

    /**
     * Handle Stripe return: after payment Stripe redirects here; complete booking server-side and redirect to confirmation page.
     * User never sees complete-payment page. Booking/lead is created and shown in All Leads.
     * Triggers when: amadex_stripe_return=1 OR when session_id (cs_...) and st are present (in case query params get stripped).
     */
    public function handle_stripe_return_redirect()
    {
        $session_id = isset($_GET['session_id']) ? sanitize_text_field(wp_unslash($_GET['session_id'])) : '';
        $token = isset($_GET['st']) ? sanitize_text_field(wp_unslash($_GET['st'])) : '';
        if ((empty($session_id) || empty($token)) && !empty($_SERVER['REQUEST_URI'])) {
            $req_uri = $_SERVER['REQUEST_URI'];
            $q = (strpos($req_uri, '?') !== false) ? substr($req_uri, strpos($req_uri, '?') + 1) : '';
            if ($q) {
                parse_str($q, $parsed);
                if (empty($session_id) && !empty($parsed['session_id'])) {
                    $session_id = sanitize_text_field(wp_unslash($parsed['session_id']));
                }
                if (empty($token) && !empty($parsed['st'])) {
                    $token = sanitize_text_field(wp_unslash($parsed['st']));
                }
            }
        }
        $is_stripe_return = (isset($_GET['amadex_stripe_return']) && $_GET['amadex_stripe_return'] === '1')
            || (strpos($session_id, 'cs_') === 0 && !empty($token));
        if (!$is_stripe_return || empty($session_id) || empty($token) || strpos($session_id, 'cs_') !== 0) {
            return;
        }

        try {
            $payment_settings = get_option('amadex_payment_settings', array());
            $stripe_secret_key = isset($payment_settings['stripe_secret_key']) ? trim($payment_settings['stripe_secret_key']) : '';
            if (empty($stripe_secret_key)) {
                wp_safe_redirect(home_url('/?amadex_stripe_error=config'));
                exit;
            }

            if (!defined('AMADEX_PATH')) {
                $this_file = __FILE__;
                $plugin_path = dirname(dirname($this_file)) . '/';
                if (!file_exists($plugin_path . 'includes/vendor/stripe/stripe-php/init.php')) {
                    $plugin_path = dirname(plugin_dir_path(__FILE__)) . '/';
                }
                if (file_exists($plugin_path . 'includes/vendor/stripe/stripe-php/init.php')) {
                    define('AMADEX_PATH', $plugin_path);
                }
            }
            if (!defined('AMADEX_PATH')) {
                wp_safe_redirect(home_url('/?amadex_stripe_error=config'));
                exit;
            }

            if (!class_exists('\Stripe\StripeClient', false)) {
                $stripe_init = AMADEX_PATH . 'includes/vendor/stripe/stripe-php/init.php';
                if (file_exists($stripe_init)) {
                    require_once $stripe_init;
                }
            }
            if (!class_exists('\Stripe\StripeClient', false)) {
                if (!class_exists('Amadex_Payment_Stripe')) {
                    require_once AMADEX_PATH . 'includes/class-amadex-payment-stripe.php';
                }
                new Amadex_Payment_Stripe();
            }
            if (!class_exists('\Stripe\StripeClient', false)) {
                wp_safe_redirect(home_url('/?amadex_stripe_error=config'));
                exit;
            }

            $stripe = new \Stripe\StripeClient($stripe_secret_key);
            $session = $stripe->checkout->sessions->retrieve($session_id, array('expand' => array('payment_intent')));

            $payment_ok = $session->status === 'complete' && in_array($session->payment_status, array('paid', 'unpaid'), true);
            $payment_intent_id = isset($session->payment_intent) && is_object($session->payment_intent) ? $session->payment_intent->id : '';
            if (!$payment_ok || empty($payment_intent_id) || strpos($payment_intent_id, 'pi_') !== 0) {
                wp_safe_redirect(home_url('/?amadex_stripe_error=payment'));
                exit;
            }

            $booking_data = get_transient('amadex_booking_' . $token);
            if (empty($booking_data) || !is_array($booking_data)) {
                wp_safe_redirect(home_url('/?amadex_stripe_error=expired'));
                exit;
            }

            $return_key = wp_generate_password(32, false);
            set_transient('amadex_stripe_return_' . $return_key, array('session_id' => $session_id, 'st' => $token), 120);
            $_POST['amadex_stripe_return_key'] = $return_key;
            $_POST['booking_data'] = wp_json_encode($booking_data);
            $_POST['payment_intent_id'] = $payment_intent_id;
            if (!defined('AMADEX_STRIPE_RETURN_REDIRECT')) {
                define('AMADEX_STRIPE_RETURN_REDIRECT', true);
            }
            $this->process_booking();
            return;
        } catch (\Throwable $e) {
            if (function_exists('amadex_log')) {
                amadex_log('Amadex Stripe return: Error - ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex Stripe return: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
                error_log($e->getTraceAsString());
            }
            wp_safe_redirect(home_url('/?amadex_stripe_error=booking'));
            exit;
        }
    }

    /**
     * Store booking data for payment page (temporary storage)
     */
    public function store_booking_for_payment()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        // Get booking data from POST (may be array from form-encoded or JSON string)
        $booking_data_raw = isset($_POST['booking_data']) ? $_POST['booking_data'] : array();
        if (is_string($booking_data_raw)) {
            $decoded = json_decode(stripslashes($booking_data_raw), true);
            $booking_data_raw = is_array($decoded) ? $decoded : array();
        }

        if (empty($booking_data_raw)) {
            wp_send_json_error(array('message' => __('Booking data is required.', 'amadex')));
            return;
        }

        // Sanitize and prepare booking data
        $booking_data = array(
            'flight' => isset($booking_data_raw['flight']) ? $booking_data_raw['flight'] : array(),
            'passengers' => isset($booking_data_raw['passengers']) ? $booking_data_raw['passengers'] : array(),
            'contact' => isset($booking_data_raw['contact']) ? $booking_data_raw['contact'] : array(),
            'billing' => isset($booking_data_raw['billing']) ? $booking_data_raw['billing'] : array(),
            'search_params' => isset($booking_data_raw['search_params']) ? $booking_data_raw['search_params'] : array(),
            'created_at' => time()
        );

        // Calculate pricing - CRITICAL: Use exact total from frontend (includes addons, seats, premium service)
        $fare_total = isset($booking_data_raw['pricing']['fare']) ? floatval($booking_data_raw['pricing']['fare']) : 0;
        $tax_total = isset($booking_data_raw['pricing']['tax']) ? floatval($booking_data_raw['pricing']['tax']) : 0;
        $surcharge = isset($booking_data_raw['pricing']['surcharge']) ? floatval($booking_data_raw['pricing']['surcharge']) : 0;
        $addons_total = isset($booking_data_raw['pricing']['addons']) ? floatval($booking_data_raw['pricing']['addons']) : 0;
        $seat_charges = isset($booking_data_raw['pricing']['seat_charges']) ? floatval($booking_data_raw['pricing']['seat_charges']) : 0;
        $premium_service = isset($booking_data_raw['pricing']['premium_service']) ? floatval($booking_data_raw['pricing']['premium_service']) : 0;

        // Use exact total from frontend (same list price as booking-flight page; addons + seats included).
        // This exact_total is used for Stripe Checkout and must match process_booking total_amount_usd / NMI charge.
        $exact_total = isset($booking_data_raw['pricing']['total']) ? floatval($booking_data_raw['pricing']['total']) : 0;

        // If exact total not provided, calculate it
        if ($exact_total <= 0) {
            $exact_total = $fare_total + $tax_total + $surcharge + $addons_total + $seat_charges + $premium_service;
        }

        // Ensure total uses Price Management Rules (pricing_charge_total) when available – same as NMI and price summary
        // When no rules: use flight price (total) so confirmation page, Stripe and booking-flight show correct flight price
        $flight_price = isset($booking_data_raw['flight']['price']) ? $booking_data_raw['flight']['price'] : array();
        $base_from_rules = 0;
        if (!empty($flight_price['pricing_charge_total']) && floatval($flight_price['pricing_charge_total']) > 0) {
            $base_from_rules = floatval($flight_price['pricing_charge_total']);
        } elseif (!empty($flight_price['charge_total']) && floatval($flight_price['charge_total']) > 0) {
            $base_from_rules = floatval($flight_price['charge_total']);
        }
        if ($base_from_rules > 0) {
            $exact_total = $base_from_rules + $addons_total + $seat_charges + $premium_service;
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('amadex_log')) {
                amadex_log('Amadex Store Booking: Total from Price Management Rules (P_charge + addons + seats + premium): $' . $exact_total);
            }
        } elseif ($exact_total <= 0) {
            // No price management rules and no frontend total: use flight price.total so confirmation/Stripe show flight price
            $flight_base = floatval($flight_price['total'] ?? $flight_price['grandTotal'] ?? 0);
            if ($flight_base > 0) {
                $exact_total = $flight_base + $addons_total + $seat_charges + $premium_service;
                if (defined('WP_DEBUG') && WP_DEBUG && function_exists('amadex_log')) {
                    amadex_log('Amadex Store Booking: Total from flight price (no rules): $' . $exact_total);
                }
            }
        }

        $booking_data['pricing'] = array(
            'fare' => $fare_total,
            'tax' => $tax_total,
            'surcharge' => $surcharge,
            'addons' => $addons_total,
            'seat_charges' => $seat_charges,
            'premium_service' => $premium_service,
            'total' => $exact_total
        );
        // Keep addons and seat_selection so payment page can send full data to process_booking (booking/leads created correctly)
        if (!empty($booking_data_raw['addons']) && is_array($booking_data_raw['addons'])) {
            $booking_data['addons'] = $booking_data_raw['addons'];
        }
        if (!empty($booking_data_raw['seat_selection']) && is_array($booking_data_raw['seat_selection'])) {
            $booking_data['seat_selection'] = $booking_data_raw['seat_selection'];
        }
        if (!empty($booking_data_raw['selected_currency'])) {
            $booking_data['selected_currency'] = sanitize_text_field($booking_data_raw['selected_currency']);
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            amadex_log('Amadex Store Booking: Pricing stored - Total: $' . $exact_total . ' (Fare: $' . $fare_total . ', Tax: $' . $tax_total . ', Surcharge: $' . $surcharge . ', Addons: $' . $addons_total . ', Seats: $' . $seat_charges . ', Premium: $' . $premium_service . ')');
        }

        // Generate secure token
        $token = wp_generate_password(32, false);

        // Store in transient (expires in 60 minutes)
        set_transient('amadex_booking_' . $token, $booking_data, 60 * 60);

        // Get confirmation URL
        $general_settings = get_option('amadex_general_settings', array());
        $confirmation_page_id = isset($general_settings['booking_confirmation_page']) ? intval($general_settings['booking_confirmation_page']) : 0;
        $confirmation_url = $confirmation_page_id ? get_permalink($confirmation_page_id) : home_url('/booking-confirmation/');
        $booking_data['confirmation_url'] = $confirmation_url;

        // Update with confirmation URL
        set_transient('amadex_booking_' . $token, $booking_data, 60 * 60);

        // Return token and payment URL
        // Try to find a page with amadex_payment shortcode, or use a default URL
        $payment_page_url = '';

        // Search for page with amadex_payment shortcode
        $pages = get_pages(array('post_status' => 'publish'));
        foreach ($pages as $page) {
            if (has_shortcode($page->post_content, 'amadex_payment')) {
                $payment_page_url = get_permalink($page->ID);
                break;
            }
        }

        // If no page found, use default URL structure
        if (empty($payment_page_url)) {
            $payment_page_url = home_url('/booking/payment/');
        }

        // Add token parameter
        $payment_page_url = add_query_arg(array('st' => $token), $payment_page_url);

        $response_data = array(
            'token' => $token,
            'payment_url' => $payment_page_url
        );

        // Stripe: use in-page payment (Stripe Elements) only — do NOT create Checkout Session or redirect to checkout.stripe.com.
        // NMI and other gateways are unchanged; only Stripe skips redirect.

        // Ensure payment_url is ALWAYS set so frontend can fallback (avoids "Unable to open Stripe checkout")
        $fallback_payment_url = add_query_arg(array('st' => $token), home_url('/booking/payment/'));
        $response_data['payment_url'] = !empty($response_data['payment_url']) ? $response_data['payment_url'] : $fallback_payment_url;
        $response_data['payment_page_url'] = $response_data['payment_url'];
        // Token is required for payment page to load booking; ensure it is always present
        $response_data['token'] = $token;

        wp_send_json_success($response_data);
    }

    /**
     * Get booking data for payment page
     */
    public function get_booking_for_payment()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';

        if (empty($token)) {
            wp_send_json_error(array('message' => __('Booking token is required.', 'amadex')));
            return;
        }

        $booking_data = get_transient('amadex_booking_' . $token);

        if (!$booking_data) {
            wp_send_json_error(array('message' => __('Booking session expired or not found.', 'amadex')));
            return;
        }

        // Check expiry
        $created_time = isset($booking_data['created_at']) ? intval($booking_data['created_at']) : 0;
        $expiry_time = $created_time + (60 * 60);
        if (time() > $expiry_time) {
            delete_transient('amadex_booking_' . $token);
            wp_send_json_error(array('message' => __('Booking session has expired.', 'amadex')));
            return;
        }

        wp_send_json_success(array('booking_data' => $booking_data));
    }

    /**
     * Delete booking token (after successful payment)
     */
    public function delete_booking_token()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';

        if (empty($token)) {
            wp_send_json_error(array('message' => __('Booking token is required.', 'amadex')));
            return;
        }

        // Delete transient
        delete_transient('amadex_booking_' . $token);

        wp_send_json_success(array('message' => __('Booking token deleted.', 'amadex')));
    }

    /**
     * Get aircraft details AJAX handler
     * Returns aircraft information from Amadeus API
     */
    public function get_aircraft_details()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $aircraft_code = isset($_POST['aircraft_code']) ? sanitize_text_field($_POST['aircraft_code']) : '';

        if (empty($aircraft_code)) {
            wp_send_json_error(array('message' => __('Aircraft code is required.', 'amadex')));
            return;
        }

        // Check if API class exists
        if (!class_exists('Amadex_API')) {
            amadex_log('Amadex Aircraft Details Error: Amadex_API class not found');
            wp_send_json_error(array('message' => __('Plugin not properly initialized.', 'amadex')));
            return;
        }

        try {
            $api = new Amadex_API();
            $aircraft_info = $api->get_aircraft_equipment($aircraft_code);

            if (is_wp_error($aircraft_info)) {
                amadex_log('Amadex Aircraft Details Error: ' . $aircraft_info->get_error_message());
                wp_send_json_error(array(
                    'message' => $aircraft_info->get_error_message(),
                    'code' => $aircraft_code
                ));
                return;
            }

            wp_send_json_success($aircraft_info);
        } catch (Exception $e) {
            amadex_log('Amadex Aircraft Details Exception: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Failed to fetch aircraft details.', 'amadex'),
                'code' => $aircraft_code
            ));
        }
    }

    /**
     * Get promotional containers for frontend display
     * Returns only enabled containers filtered by rules
     */
    public function get_promotional_containers()
    {
        amadex_log('Amadex Promo: get_promotional_containers() called');

        // Get all promotional containers
        $containers = get_option('amadex_promotional_containers', array());
        amadex_log('Amadex Promo: Total containers from DB: ' . count($containers));

        // Filter: only enabled containers
        $enabled_containers = array();
        foreach ($containers as $id => $container) {
            if (isset($container['enabled']) && $container['enabled']) {
                $enabled_containers[$id] = $container;
            }
        }

        amadex_log('Amadex Promo: Enabled containers: ' . count($enabled_containers));

        // Sort by display_order
        uasort($enabled_containers, function ($a, $b) {
            $order_a = isset($a['display_order']) ? intval($a['display_order']) : 0;
            $order_b = isset($b['display_order']) ? intval($b['display_order']) : 0;
            return $order_a <=> $order_b;
        });

        wp_send_json_success(array('containers' => $enabled_containers));
    }

    /**
     * Get skeleton template for loading UI
     */
    public function get_skeleton_template()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $count = isset($_POST['count']) ? intval($_POST['count']) : 5;
        $count = max(1, min(10, $count)); // Between 1 and 10

        // Load skeleton template
        $template_path = AMADEX_PATH . 'templates/loading-skeleton.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $html = ob_get_clean();
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(array('message' => __('Skeleton template not found.', 'amadex')));
        }
    }

    /**
     * Get loading animation template
     */
    public function get_loading_animation_template()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'amadex')));
            return;
        }

        $origin = isset($_POST['origin']) ? sanitize_text_field($_POST['origin']) : '';
        $destination = isset($_POST['destination']) ? sanitize_text_field($_POST['destination']) : '';

        // Load animation template
        $template_path = AMADEX_PATH . 'templates/loading-animation.php';
        if (file_exists($template_path)) {
            ob_start();
            include $template_path;
            $html = ob_get_clean();
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(array('message' => __('Animation template not found.', 'amadex')));
        }
    }
    
    /* ========================================
       DUPLICATE PREVENTION FUNCTIONS
       ======================================== */

    /**
     * Generate unique request hash for deduplication
     * @param array $post_data POST data array
     * @return string Request hash
     */
    private function generate_request_hash($post_data)
    {
        // Extract booking data
        $booking_data = $post_data['booking_data'] ?? array();

        // If booking_data is a string (JSON), decode it
        if (is_string($booking_data)) {
            $booking_data = json_decode(stripslashes($booking_data), true);
        }

        // Create unique hash from:
        // - Flight ID/offer ID
        // - Passenger count
        // - Contact email
        // - Timestamp (rounded to nearest second)
        $flight = $booking_data['flight'] ?? array();
        $passengers = $booking_data['passengers'] ?? array();
        $contact = $booking_data['contact'] ?? array();

        $hash_data = array(
            'flight_id' => $flight['id'] ?? $flight['offerId'] ?? $flight['itineraryId'] ?? '',
            'passengers' => count($passengers),
            'email' => $contact['email'] ?? '',
            'timestamp' => floor(time() / 10) * 10 // Round to nearest 10 seconds
        );

        // Generate hash
        $hash_string = json_encode($hash_data);
        return substr(md5($hash_string), 0, 32);
    }

    /**
     * Check for duplicate request
     * @param string $request_hash Request hash
     * @return array|false Existing booking data or false
     */
    private function check_duplicate_request($request_hash)
    {
        global $wpdb;

        $locks_table = $wpdb->prefix . 'amadex_booking_locks';
        $bookings_table = $wpdb->prefix . 'amadex_bookings';

        // ✅ Ensure table exists before querying
        $database = new Amadex_Database();
        if (!$database->table_exists_public($locks_table)) {
            // Table doesn't exist - try to create it
            amadex_log('Amadex Booking: booking_locks table missing - attempting to create');
            $create_result = $database->create_tables();

            // If still doesn't exist, fallback to transient-only check (don't block booking)
            if (!$database->table_exists_public($locks_table)) {
                amadex_log('Amadex Booking: booking_locks table creation failed - using transient fallback only');
                $transient_key = 'amadex_booking_lock_' . $request_hash;
                $existing_lock = get_transient($transient_key);
                if ($existing_lock) {
                    amadex_log('Amadex Booking: Duplicate request detected via transient - allowing but logging');
                    // Don't block - transient-only is not reliable enough
                }
                return false; // Allow processing if table doesn't exist
            }
        }

        // Suppress errors for query
        $wpdb->suppress_errors(true);
        $wpdb->last_error = ''; // Clear any previous errors

        // Check if hash exists in last 30 seconds
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT l.booking_id, l.status, b.booking_reference
             FROM {$locks_table} l
             LEFT JOIN {$bookings_table} b ON l.booking_id = b.id
             WHERE l.request_hash = %s
             AND l.created_at > DATE_SUB(NOW(), INTERVAL 30 SECOND)
             ORDER BY l.created_at DESC
             LIMIT 1",
            $request_hash
        ), ARRAY_A);

        $db_error = $wpdb->last_error;
        $wpdb->suppress_errors(false);

        // Check for database errors
        if (!empty($db_error)) {
            amadex_log('Amadex Booking: Database error in check_duplicate_request: ' . $db_error);
            // Fallback to transient check (don't block booking on database error)
            $transient_key = 'amadex_booking_lock_' . $request_hash;
            $existing_lock = get_transient($transient_key);
            if ($existing_lock) {
                amadex_log('Amadex Booking: Duplicate request detected via transient fallback');
                // Don't block - allow processing
            }
            return false; // Error occurred, allow processing (better than blocking)
        }

        if ($existing && $existing['status'] === 'COMPLETED' && $existing['booking_id']) {
            // Get full booking details
            $booking = $wpdb->get_row($wpdb->prepare(
                "SELECT id, booking_reference, status, total_amount, currency
                 FROM {$bookings_table}
                 WHERE id = %d
                 AND status IN ('PENDING', 'CONFIRMED')",
                $existing['booking_id']
            ), ARRAY_A);

            if ($booking) {
                return $booking;
            }
        }

        return false;
    }

    /**
     * Acquire booking lock
     * @param string $request_hash Request hash
     * @return string|false Lock key or false if lock already exists
     */
    private function acquire_booking_lock($request_hash)
    {
        global $wpdb;

        $locks_table = $wpdb->prefix . 'amadex_booking_locks';
        $lock_key = 'amadex_booking_lock_' . $request_hash;

        // ✅ First, try transient lock (always works, even if table doesn't exist)
        $existing_transient = get_transient($lock_key);
        if ($existing_transient) {
            amadex_log('Amadex Booking: Lock already exists (transient) - duplicate request blocked');
            return false; // Lock already exists
        }

        // ✅ FIX #5: Set transient lock first (this always works) - Extended TTL to 60 seconds
        $transient_acquired = set_transient($lock_key, 'processing', 60); // ✅ Extended from 30 to 60 seconds
        if (!$transient_acquired) {
            amadex_log('Amadex Booking: Failed to acquire transient lock');
            return false;
        }

        // ✅ Now try database lock (if table exists) - but don't fail if it doesn't
        $database = new Amadex_Database();
        if ($database->table_exists_public($locks_table)) {
            // Suppress errors for insert
            $wpdb->suppress_errors(true);
            $wpdb->last_error = ''; // Clear previous errors

            // Try to insert lock record
            $result = $wpdb->insert(
                $locks_table,
                array(
                    'request_hash' => $request_hash,
                    'status' => 'PROCESSING',
                    'created_at' => current_time('mysql'),
                    'geo_location' => $geo_location,
                    'location_source' => $location_data['source'] ?? '',
                ),
                array('%s', '%s', '%s')
            );

            $db_error = $wpdb->last_error;
            $wpdb->suppress_errors(false);

            if ($result === false && !empty($db_error)) {
                // Database insert failed, but transient lock succeeded
                amadex_log('Amadex Booking: Database lock insert failed, but transient lock acquired: ' . $db_error);
                // Continue with transient-only lock (better than blocking)
            } elseif ($result === false && empty($db_error)) {
                // Lock already exists in database (duplicate key or other constraint)
                amadex_log('Amadex Booking: Lock already exists (database) - duplicate request blocked');
                delete_transient($lock_key); // Clean up transient
                return false;
            } else {
                // Success - both transient and database lock acquired
                amadex_log('Amadex Booking: Lock acquired (transient + database)');
            }
        } else {
            // Table doesn't exist - use transient-only lock (don't block booking)
            amadex_log('Amadex Booking: Using transient-only lock (table missing)');
            // Continue with transient lock only - this is acceptable
        }

        return $lock_key; // Return lock key (transient lock is sufficient)
    }

    /**
     * Release booking lock
     * @param string $lock_key Lock key
     * @param int|null $booking_id Booking ID if successful
     * @param string $status Status (COMPLETED or FAILED)
     */
    private function release_booking_lock($lock_key, $booking_id = null, $status = 'COMPLETED')
    {
        global $wpdb;

        // ✅ Always delete transient first (this always works)
        delete_transient($lock_key);

        $locks_table = $wpdb->prefix . 'amadex_booking_locks';

        // ✅ Check if table exists before updating
        $database = new Amadex_Database();
        if (!$database->table_exists_public($locks_table)) {
            // Table doesn't exist - transient deletion is sufficient
            return;
        }

        // Extract request hash from lock key
        $request_hash = str_replace('amadex_booking_lock_', '', $lock_key);

        // Update lock record (suppress errors in case of issues)
        $wpdb->suppress_errors(true);

        $update_data = array(
            'status' => $status,
            'completed_at' => current_time('mysql')
        );

        if ($booking_id) {
            $update_data['booking_id'] = $booking_id;
        }

        $wpdb->update(
            $locks_table,
            $update_data,
            array('request_hash' => $request_hash),
            array('%s', '%s', '%d'),
            array('%s')
        );

        $wpdb->suppress_errors(false);

        // Log if there was an error (but don't fail - transient is already deleted)
        if (!empty($wpdb->last_error)) {
            amadex_log('Amadex Booking: Error releasing database lock (transient already released): ' . $wpdb->last_error);
        }
    }

    /**
     * Check if payment token was already used
     * @param string $payment_token Payment token
     * @param array $booking_data Booking data
     * @return array|false Existing payment authorization or false
     */
    private function check_existing_payment_authorization($payment_token, $booking_data)
    {
        global $wpdb;

        if (empty($payment_token)) {
            return false;
        }

        // Generate hash of payment token (for security, don't store raw token)
        $token_hash = hash('sha256', $payment_token);

        $locks_table = $wpdb->prefix . 'amadex_booking_locks';
        $payments_table = $wpdb->prefix . 'amadex_payments';

        // ✅ Check if table exists before querying
        $database = new Amadex_Database();
        if (!$database->table_exists_public($locks_table)) {
            // Table doesn't exist - can't check token usage
            return false;
        }

        // Suppress errors for query
        $wpdb->suppress_errors(true);

        // Check if token was used in last 5 minutes
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT l.booking_id, l.transaction_id
             FROM {$locks_table} l
             WHERE l.payment_token_hash = %s
             AND l.status = 'COMPLETED'
             AND l.created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
             ORDER BY l.created_at DESC
             LIMIT 1",
            $token_hash
        ), ARRAY_A);

        $wpdb->suppress_errors(false);

        // Check for database errors
        if (!empty($wpdb->last_error)) {
            amadex_log('Amadex Booking: Database error in check_existing_payment_authorization: ' . $wpdb->last_error);
            return false; // Error occurred, allow new authorization
        }

        if ($existing && $existing['booking_id']) {
            // Get payment record
            $payment = $wpdb->get_row($wpdb->prepare(
                "SELECT transaction_id, payment_status, amount, auth_code,
                        card_last4, card_type, avs_result, cvv_result
                 FROM {$payments_table}
                 WHERE booking_id = %d
                 AND payment_status = 'AUTH_ONLY'
                 ORDER BY created_at DESC
                 LIMIT 1",
                $existing['booking_id']
            ), ARRAY_A);

            if ($payment) {
                return $payment;
            }
        }

        return false;
    }

    /**
     * Store payment token usage
     * @param string $payment_token Payment token
     * @param array $auth_result Authorization result
     * @param int $booking_id Booking ID
     */
    private function store_payment_token_usage($payment_token, $auth_result, $booking_id)
    {
        global $wpdb;

        if (empty($payment_token) || !$auth_result['success']) {
            return; // Don't store failed authorizations
        }

        $token_hash = hash('sha256', $payment_token);
        $locks_table = $wpdb->prefix . 'amadex_booking_locks';

        // Update existing lock record with payment token hash
        $wpdb->update(
            $locks_table,
            array(
                'payment_token_hash' => $token_hash,
                'transaction_id' => $auth_result['transaction_id'] ?? '',
                'booking_id' => $booking_id
            ),
            array('booking_id' => $booking_id),
            array('%s', '%s', '%d'),
            array('%d')
        );
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip()
    {
        $keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }

    /**
     * Get exchange rate from a currency to USD
     * @param string $currency Source currency code
     * @return float|null Exchange rate or null if unavailable
     */
    private function get_currency_to_usd_rate($currency)
    {
        if ($currency === 'USD') return 1.0;

        // Check transient cache first
        $cached = get_transient('amadex_rate_' . $currency . '_USD');
        if ($cached !== false) return (float) $cached;

        if (class_exists('Amadex_Currency')) {
            try {
                $rate = Amadex_Currency::get_exchange_rate($currency, 'USD');
                if ($rate && $rate > 0) {
                    set_transient('amadex_rate_' . $currency . '_USD', $rate, HOUR_IN_SECONDS);
                    return (float) $rate;
                }
            } catch (Exception $e) {
                amadex_log('Amadex SeatMap: Currency rate error for ' . $currency . ': ' . $e->getMessage());
            }
        }

        return null;
    }

    public function update_fp_status()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        global $wpdb;
        $lead_id = intval($_POST['lead_id'] ?? 0);
        $status  = sanitize_text_field($_POST['status'] ?? '');
        $note    = sanitize_textarea_field($_POST['note'] ?? '');
        $allowed = ['new','contacted','booked','not_interested','callback','payment_successful'];
        if (!$lead_id || !in_array($status, $allowed)) {
            wp_send_json_error(array('message' => 'Invalid data'));
            return;
        }
        $wpdb->update(
            $wpdb->prefix . 'amadex_leads',
            array(
                'fp_status'      => $status,
                'fp_note'        => $note,
                'fp_updated_at'  => current_time('mysql'),
                'fp_updated_by'  => wp_get_current_user()->display_name,
            ),
            array('id' => $lead_id),
            array('%s','%s','%s','%s'),
            array('%d')
        );
        wp_send_json_success(array('message' => 'Status updated'));
    }
}
new Amadex_Ajax();
