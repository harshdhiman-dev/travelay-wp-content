<?php

/**
 * Frontend shortcodes for Amadex plugin
 *
 * @package Amadex
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
// Font Awesome is loaded via wp_enqueue_style in enqueue_assets()
class Amadex_Shortcodes
{

    public function __construct()
    {
        add_shortcode('amadex_flight_search', array($this, 'render_search'));
        add_shortcode('amadex_search_modern', array($this, 'render_modern_search'));
        //add_shortcode('amadex_search_v2', array($this, 'render_search_v2'));
        add_shortcode('amadex_flight_results', array($this, 'render_results_page'));
        add_shortcode('amadex_flight_booking', array($this, 'render_booking_page'));
        add_shortcode('amadex_booking_confirmation', array($this, 'render_booking_confirmation_page'));
        add_shortcode('amadex_payment', array($this, 'render_payment_page'));
        add_shortcode('amadex_test_form', array($this, 'render_test_form'));
        add_shortcode('amadex_api_test', array($this, 'render_api_test'));
        add_shortcode('amadex_regional_settings', array($this, 'render_regional_settings_button'));
        // add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('enqueue_block_editor_assets', function () {
            if (! defined('AMADEX_BLOCK_EDITOR_ACTIVE')) define('AMADEX_BLOCK_EDITOR_ACTIVE', true);
        }, 1);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('enqueue_block_editor_assets', array($this, 'dequeue_from_block_editor'));
        add_action('wp_ajax_amadex_airports', array($this, 'ajax_airports'));
        add_action('wp_ajax_nopriv_amadex_airports', array($this, 'ajax_airports'));
        add_action('wp_ajax_amadex_search', array($this, 'ajax_search'));
        add_action('wp_ajax_nopriv_amadex_search', array($this, 'ajax_search'));
        add_action('wp_ajax_amadex_checkout', array($this, 'ajax_checkout'));
        add_action('wp_ajax_nopriv_amadex_checkout', array($this, 'ajax_checkout'));
        // add_action('wp_footer', array($this, 'render_dropdown_script'));
        if (! defined('REST_REQUEST') || ! REST_REQUEST) {
            add_action('wp_footer', array($this, 'render_dropdown_script'));
            add_action('wp_footer', array($this, 'auto_inject_regional_button'), 5);
        }
    }
    public function dequeue_from_block_editor()
    {
        // Remove all frontend assets from the block editor
        remove_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    public function enqueue_assets()
    {
        // Don't load plugin assets in block editor / admin context
        // if ( defined('AMADEX_BLOCK_EDITOR_ACTIVE') && AMADEX_BLOCK_EDITOR_ACTIVE ) return;
        // if ( is_admin() ) {
        //     return;
        // }
        if (isset($_GET['postId']) || isset($_GET['postType'])) return;
        if (isset($_GET['canvas'])) return;
        if (is_admin()) return;
        // Don't load in Gutenberg block editor iframe preview
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        // Check for block editor context via referer
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            if (
                strpos($referer, 'wp-admin/post.php') !== false ||
                strpos($referer, 'wp-admin/site-editor.php') !== false ||
                strpos($referer, 'wp-admin/widgets.php') !== false
            ) {
                return;
            }
        }

        if ( ! amadex_page_needs_assets() ) {
            wp_enqueue_style('amadex-regional-settings', AMADEX_URL . 'assets/css/amadex-regional-settings.css', array(), AMADEX_VERSION);
            wp_enqueue_script('amadex-regional-settings', AMADEX_URL . 'assets/js/amadex-regional-settings.js', array('jquery'), AMADEX_VERSION, true);
            return;
        }

        // public function enqueue_assets()
        // {
        wp_enqueue_style('amadex-front', AMADEX_URL . 'assets/css/amadex.css', array(), AMADEX_VERSION);
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css', array(), '7.0.1');
        wp_enqueue_style('amadex-flight-results', AMADEX_URL . 'assets/css/amadex-flight-results.css', array('amadex-front'), AMADEX_VERSION);
        wp_enqueue_style('amadex-new-layout', AMADEX_URL . 'assets/css/amadex-new-layout.css', array('amadex-front'), AMADEX_VERSION);
        // wp_enqueue_style('amadex-booking', AMADEX_URL . 'assets/css/amadex-booking.css', array('amadex-front'), AMADEX_VERSION);
        wp_enqueue_style('amadex-deals', AMADEX_URL . 'assets/css/amadex-deals.css', array(), AMADEX_VERSION);
        wp_enqueue_style('amadex-seat-map', AMADEX_URL . 'assets/css/amadex-seat-map.css', array('amadex-booking'), AMADEX_VERSION);

        wp_enqueue_style('amadex-search-modern', AMADEX_URL . 'assets/css/amadex-search-modern.css', array(), AMADEX_VERSION);

        // wp_enqueue_style('amadex-mobile-responsive', AMADEX_URL . 'assets/css/amadex-mobile-responsive.css', array('amadex-front', 'amadex-search-modern'), AMADEX_VERSION);
        // wp_enqueue_style('amadex-mobile-search-fix', AMADEX_URL . 'assets/css/amadex-mobile-search-fix.css', array('amadex-search-modern'), AMADEX_VERSION);
        // wp_enqueue_style('amadex-mobile-force-display', AMADEX_URL . 'assets/css/amadex-mobile-force-display.css', array('amadex-search-modern', 'amadex-mobile-search-fix'), AMADEX_VERSION);
        wp_enqueue_style('amadex-regional-settings', AMADEX_URL . 'assets/css/amadex-regional-settings.css', array('amadex-front'), AMADEX_VERSION);
        wp_enqueue_style('amadex-loading-animations', AMADEX_URL . 'assets/css/amadex-loading-animations.css', array('amadex-front'), AMADEX_VERSION);
        wp_enqueue_style('amadex-virtual-scroll', AMADEX_URL . 'assets/css/amadex-virtual-scroll.css', array('amadex-front'), AMADEX_VERSION);
        wp_enqueue_style('amadex-creative-experience', AMADEX_URL . 'assets/css/amadex-creative-experience.css', array('amadex-booking'), AMADEX_VERSION);
        wp_enqueue_style('amadex-step-elements', AMADEX_URL . 'assets/css/amadex-step-elements.css', array('amadex-creative-experience', 'amadex-booking'), AMADEX_VERSION);

        // Load confirmation page CSS only on confirmation page
        $general_settings = get_option('amadex_general_settings', array());
        $confirmation_page_id = isset($general_settings['booking_confirmation_page']) ? intval($general_settings['booking_confirmation_page']) : 0;
        $is_confirmation_page = false;

        // Check if current page is confirmation page
        if ($confirmation_page_id && is_page($confirmation_page_id)) {
            $is_confirmation_page = true;
        } elseif (isset($_GET['reference']) || (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'booking-confirmation') !== false)) {
            $is_confirmation_page = true;
        }

        if ($is_confirmation_page) {
            // wp_enqueue_style('amadex-confirmation', AMADEX_URL . 'assets/css/amadex-confirmation.css', array('amadex-booking'), AMADEX_VERSION);
            // wp_enqueue_style('amadex-confirmation-mobile-fix', AMADEX_URL . 'assets/css/amadex-confirmation-mobile-fix.css', array('amadex-confirmation'), AMADEX_VERSION);
            wp_enqueue_script('amadex-confirmation', AMADEX_URL . 'assets/js/amadex-confirmation.js', array('jquery', 'amadex-booking'), AMADEX_VERSION, true);
        }

        wp_enqueue_script('amadex-matrix', AMADEX_URL . 'assets/js/amadex-matrix.js', array('jquery'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-filters', AMADEX_URL . 'assets/js/amadex-filters.js', array('jquery'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-fraud-detection', AMADEX_URL . 'assets/js/amadex-fraud-detection.js', array('jquery'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-booking', AMADEX_URL . 'assets/js/amadex-booking.js', array('jquery', 'amadex-fraud-detection'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-deals', AMADEX_URL . 'assets/js/amadex-deals.js', array('jquery'), AMADEX_VERSION, true);
        // wp_enqueue_script('amadex-search-modern', AMADEX_URL . 'assets/js/amadex-search-modern.js', array('jquery'), time(), true);
        wp_enqueue_script('amadex-search-modern', AMADEX_URL . 'assets/js/amadex-search-modern.js', array('jquery'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-dropdown-fix', AMADEX_URL . 'assets/js/amadex-dropdown-fix.js', array('jquery', 'amadex-search-modern'), AMADEX_VERSION, true);
        // Enqueue shared promotional container renderer (must load before amadex.js)
        wp_enqueue_script('amadex-container-types', AMADEX_URL . 'assets/js/amadex-container-types.js', array(), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-promo-templates', AMADEX_URL . 'assets/js/amadex-promo-templates.js', array('amadex-container-types'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-promo-renderer', AMADEX_URL . 'assets/js/amadex-promo-renderer.js', array('amadex-promo-templates', 'amadex-container-types'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-front', AMADEX_URL . 'assets/js/amadex.js', array('jquery', 'amadex-matrix', 'amadex-filters', 'amadex-promo-renderer'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-mobile-filters', AMADEX_URL . 'assets/js/amadex-mobile-filters.js', array('jquery', 'amadex-filters'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-regional-settings', AMADEX_URL . 'assets/js/amadex-regional-settings.js', array('jquery'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-virtual-scroll', AMADEX_URL . 'assets/js/amadex-virtual-scroll.js', array('jquery'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-streaming-loader', AMADEX_URL . 'assets/js/amadex-streaming-loader.js', array('jquery'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-creative-experience', AMADEX_URL . 'assets/js/amadex-creative-experience.js', array('jquery'), AMADEX_VERSION, true);
        wp_enqueue_script('amadex-step-elements', AMADEX_URL . 'assets/js/amadex-step-elements.js', array('jquery', 'amadex-booking'), AMADEX_VERSION, true);
        // Update amadex-front dependencies to include streaming loader
        wp_deregister_script('amadex-front');
        wp_enqueue_script('amadex-front', AMADEX_URL . 'assets/js/amadex.js', array('jquery', 'amadex-matrix', 'amadex-filters', 'amadex-promo-renderer', 'amadex-streaming-loader', 'amadex-virtual-scroll'), AMADEX_VERSION, true);
        // Get settings
        $general_settings = get_option('amadex_general_settings', array());
        $popup_settings = get_option('amadex_popup_settings', array());
        $payment_settings = get_option('amadex_payment_settings', array());

        $booking_page_url = home_url('/flight-booking/');
        $confirmation_page_id = isset($general_settings['booking_confirmation_page']) ? intval($general_settings['booking_confirmation_page']) : 0;
        $confirmation_page_url = $confirmation_page_id ? get_permalink($confirmation_page_id) : home_url('/booking-confirmation/');
        $notification_email = isset($general_settings['notification_email']) && is_email($general_settings['notification_email'])
            ? sanitize_email($general_settings['notification_email'])
            : get_option('admin_email');
        $support_phone = isset($general_settings['call_now_number']) ? sanitize_text_field($general_settings['call_now_number']) : '+1-866-960-2626';

        // Get payment settings for bypass mode
        $payment_settings = get_option('amadex_payment_settings', array());
        $bypass_payment = isset($payment_settings['nmi_bypass_for_testing']) && $payment_settings['nmi_bypass_for_testing'] == 1;

        // Get currency data for results page (if currency class exists)
        $currency_data = array();
        $default_currency = 'USD';
        $detected_country = 'US';
        $detected_currency = 'USD';
        $detected_language = 'en-GB';
        $language_data = array();
        $country_data = array();

        if (class_exists('Amadex_Currency')) {
            try {
                $currencies = Amadex_Currency::get_supported_currencies();
                foreach ($currencies as $code => $info) {
                    $currency_data[$code] = array(
                        'name' => $info['name'],
                        'symbol' => $info['symbol']
                    );
                }

                // Get languages
                $languages = Amadex_Currency::get_supported_languages();
                foreach ($languages as $code => $info) {
                    $language_data[$code] = $info;
                }

                // Get countries
                $countries = Amadex_Currency::get_supported_countries();
                foreach ($countries as $code => $info) {
                    $country_data[$code] = $info;
                }

                // Check if regional settings are enabled
                $currency_settings = get_option('amadex_currency_settings', array());
                $regional_settings_enabled = isset($currency_settings['enable_regional_settings']) ? (bool) $currency_settings['enable_regional_settings'] : true;

                if ($regional_settings_enabled) {
                    // Regional settings enabled - use geolocation (first priority)
                    $default_currency = Amadex_Currency::get_default_currency();
                    $detected_country = Amadex_Currency::get_country_from_ip();
                    $detected_currency = Amadex_Currency::get_currency_by_country($detected_country);
                    $detected_language = Amadex_Currency::get_default_language();
                } else {
                    // Regional settings disabled - force USA/USD/en-US
                    $default_currency = 'USD';
                    $detected_country = 'US';
                    $detected_currency = 'USD';
                    $detected_language = 'en-US';
                }

                if (!Amadex_Currency::is_valid_currency($detected_currency)) {
                    $detected_currency = $default_currency;
                }
            } catch (Exception $e) {
                error_log('Amadex Currency Error: ' . $e->getMessage());
            }
        }

        // Load add-ons from backend and format for JavaScript
        $backend_addons = get_option('amadex_addon_services', array());
        $formatted_addons = array();

        if (!empty($backend_addons) && is_array($backend_addons)) {
            foreach ($backend_addons as $addon_id => $addon) {
                // Only include enabled add-ons
                if (isset($addon['enabled']) && $addon['enabled'] == 1) {
                    $formatted_addons[] = array(
                        'id' => isset($addon['id']) ? sanitize_text_field($addon['id']) : $addon_id,
                        'title' => isset($addon['title']) ? sanitize_text_field($addon['title']) : '',
                        'description' => isset($addon['description']) ? sanitize_textarea_field($addon['description']) : '',
                        'price' => isset($addon['price']) ? floatval($addon['price']) : 0,
                        'currency' => isset($addon['currency']) ? sanitize_text_field($addon['currency']) : 'USD',
                        'enabled' => true, // Convert 1/0 to true/false for JavaScript
                        'displayOrder' => isset($addon['display_order']) ? intval($addon['display_order']) : 0 // Convert display_order to displayOrder
                    );
                }
            }

            // Sort by display_order (displayOrder in JS format)
            usort($formatted_addons, function ($a, $b) {
                return $a['displayOrder'] - $b['displayOrder'];
            });
        }

        // Get default card gateway setting (NMI or Stripe) - MUST BE BEFORE $amadex_config
        $default_card_gateway = isset($payment_settings['default_card_gateway']) ? sanitize_text_field($payment_settings['default_card_gateway']) : 'nmi';
        $default_card_gateway = strtolower(trim($default_card_gateway));

        // Validate gateway (must be 'nmi' or 'stripe') - case-insensitive so "NMI" works when NMI is enabled
        if (!in_array($default_card_gateway, array('nmi', 'stripe'))) {
            $default_card_gateway = 'nmi'; // Fallback to NMI if invalid
        }

        // Get call banner HTML from settings
        $call_banner_settings = get_option('amadex_call_banner_settings');

        // If option doesn't exist or is empty, initialize it with default
        if (empty($call_banner_settings) || empty($call_banner_settings['call_banner_html'])) {
            // Get the default HTML from the settings class using a helper
            require_once AMADEX_PATH . 'includes/admin/class-amadex-settings.php';
            $settings = new Amadex_Settings();
            $reflection = new ReflectionClass($settings);
            $method = $reflection->getMethod('get_default_call_banner_html');
            $method->setAccessible(true);
            $default_html = $method->invoke($settings);

            // Save the default to database
            update_option('amadex_call_banner_settings', array('call_banner_html' => $default_html));
            $call_banner_html = $default_html;
        } else {
            $call_banner_html = $call_banner_settings['call_banner_html'];
        }

        // Get performance settings for progressive loading
        $performance_settings = get_option('amadex_performance_settings', array());
        $progressive_loading = isset($performance_settings['enable_progressive_loading']) && $performance_settings['enable_progressive_loading'] === '1';
        $progressive_load_count = isset($performance_settings['progressive_load_count']) ? intval($performance_settings['progressive_load_count']) : 30;

        // Get streaming response settings
        $streaming_response = isset($performance_settings['enable_streaming_response']) && $performance_settings['enable_streaming_response'] === '1';
        $streaming_initial_count = isset($performance_settings['streaming_initial_count']) ? intval($performance_settings['streaming_initial_count']) : 5;

        // Localize AmadexConfig for both scripts to ensure availability
        $amadex_config = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amadex_nonce'),
            'bookingPageUrl' => esc_url_raw($booking_page_url),
            'confirmationPageUrl' => esc_url_raw($confirmation_page_url),
            'currency' => array(
                'currencies' => $currency_data,
                'default' => $default_currency,
                'detected' => $detected_currency,
                'detectedCountry' => $detected_country,
                'detectedLanguage' => $detected_language,
                'languages' => $language_data,
                'countries' => $country_data,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('amadex_currency_nonce'),
                'regionalSettingsEnabled' => $regional_settings_enabled
            ),
            'pluginUrl' => AMADEX_URL,
            'supportEmail' => $notification_email,
            'supportPhone' => $support_phone,
            'brandName' => get_bloginfo('name'),
            'bypassPayment' => $bypass_payment,
            'addons' => $formatted_addons, // Add backend add-ons to config
            'defaultCardGateway' => $default_card_gateway, // Add gateway selection to config
            'callBannerHtml' => $call_banner_html, // Add call banner HTML to config
            'progressiveLoading' => $progressive_loading, // Phase 2: Progressive loading enabled
            'progressiveLoadCount' => $progressive_load_count, // Number of initial results for progressive load
            'streamingResponse' => $streaming_response, // Streaming response enabled
            'streamingInitialCount' => $streaming_initial_count, // Number of flights to show immediately
            'enableVirtualScrolling' => isset($performance_settings['enable_virtual_scrolling']) && $performance_settings['enable_virtual_scrolling'] === '1',
            'enableSkeletonUi' => isset($performance_settings['enable_skeleton_ui']) && $performance_settings['enable_skeleton_ui'] === '1',
            'enableLoadingAnimation' => isset($performance_settings['enable_loading_animation']) && $performance_settings['enable_loading_animation'] === '1',
            'enablePaypal' => isset($payment_settings['enable_paypal']) ? (int) $payment_settings['enable_paypal'] : 1,
            'paypalClientId' => isset($payment_settings['paypal_client_id']) ? trim($payment_settings['paypal_client_id']) : '',
            'paypalMode' => isset($payment_settings['paypal_mode']) && $payment_settings['paypal_mode'] === 'live' ? 'live' : 'sandbox',
            'enableCryptoCom' => isset($payment_settings['enable_crypto_com']) ? (int) $payment_settings['enable_crypto_com'] : 0,
            'cryptoComPublishableKey' => isset($payment_settings['crypto_com_publishable_key']) ? trim($payment_settings['crypto_com_publishable_key']) : '',
            'enableMoonPayCommerce' => isset($payment_settings['enable_moonpay_commerce']) ? (int) $payment_settings['enable_moonpay_commerce'] : 0,
            'enableMoonPayOnramp' => isset($payment_settings['enable_moonpay_onramp']) ? (int) $payment_settings['enable_moonpay_onramp'] : 0,
            'moonpayOnrampPublishableKey' => isset($payment_settings['moonpay_onramp_environment']) && $payment_settings['moonpay_onramp_environment'] === 'live'
                ? trim($payment_settings['moonpay_onramp_publishable_key_live'] ?? '')
                : trim($payment_settings['moonpay_onramp_publishable_key_test'] ?? ''),
            'moonpayOnrampEnvironment' => (isset($payment_settings['moonpay_onramp_environment']) && $payment_settings['moonpay_onramp_environment'] === 'live') ? 'production' : 'sandbox',
        );

        wp_localize_script('amadex-front', 'AmadexConfig', $amadex_config);
        wp_localize_script('amadex-booking', 'AmadexConfig', $amadex_config);

        // Get pricing settings for JavaScript
        $pricing_settings = Amadex_Pricing::get_pricing_settings_for_js();

        // Get display settings for Viewers Badge
        $display_settings = get_option('amadex_display_settings', array());

        wp_localize_script('amadex-front', 'amadexSettings', array(
            'call_now_number' => isset($general_settings['call_now_number']) ? $general_settings['call_now_number'] : '+1-866-960-2626',
            'auto_popup_enabled' => isset($popup_settings['auto_popup_enabled']) ? $popup_settings['auto_popup_enabled'] : 1,
            'auto_popup_delay' => isset($popup_settings['auto_popup_delay']) ? $popup_settings['auto_popup_delay'] : 420,
            'popup_title' => isset($popup_settings['popup_title']) ? $popup_settings['popup_title'] : 'Exclusive Deals',
            'popup_description' => isset($popup_settings['popup_description']) ? $popup_settings['popup_description'] : 'Get the best deals on flights by calling our travel experts.',
            'popup_logo_url' => isset($popup_settings['popup_logo_url']) ? $popup_settings['popup_logo_url'] : '',
            'popup_price_type' => isset($popup_settings['popup_price_type']) ? $popup_settings['popup_price_type'] : 'none',
            'popup_price_fixed' => isset($popup_settings['popup_price_fixed']) ? $popup_settings['popup_price_fixed'] : '',
            'popup_discount_percent' => isset($popup_settings['popup_discount_percent']) ? $popup_settings['popup_discount_percent'] : '',
            'popup_customer_service_image' => isset($popup_settings['popup_customer_service_image']) ? $popup_settings['popup_customer_service_image'] : '',
            'popup_trust_years' => isset($popup_settings['popup_trust_years']) ? $popup_settings['popup_trust_years'] : '20+',
            'popup_trustpilot_rating' => isset($popup_settings['popup_trustpilot_rating']) ? $popup_settings['popup_trustpilot_rating'] : '4.4',
            'popup_countdown_minutes' => isset($popup_settings['popup_countdown_minutes']) ? $popup_settings['popup_countdown_minutes'] : 12,
            'pricing' => $pricing_settings,
            'viewersBadge' => array(
                'enabled' => isset($display_settings['viewers_badge_enabled']) ? intval($display_settings['viewers_badge_enabled']) : 0,
                'min' => isset($display_settings['viewers_badge_min']) ? intval($display_settings['viewers_badge_min']) : 12,
                'max' => isset($display_settings['viewers_badge_max']) ? intval($display_settings['viewers_badge_max']) : 89,
                'text' => isset($display_settings['viewers_badge_text']) ? sanitize_text_field($display_settings['viewers_badge_text']) : 'people exploring',
                'position' => isset($display_settings['viewers_badge_position']) ? sanitize_text_field($display_settings['viewers_badge_position']) : 'top-left'
            )
        ));

        // Conditionally load payment gateway scripts based on selection
        if ($default_card_gateway === 'nmi') {
            // NMI Collect.js settings
            $tokenization_key = isset($payment_settings['nmi_tokenization_key']) ? trim($payment_settings['nmi_tokenization_key']) : '';

            // Remove any hidden characters (non-printable characters, BOM, etc.)
            $tokenization_key = preg_replace('/[\x00-\x1F\x7F]/', '', $tokenization_key);
            $tokenization_key = trim($tokenization_key);

            // Debug: Log tokenization key status (partial for security)
            if (empty($tokenization_key)) {
                error_log('Amadex Warning: NMI Tokenization Key is not configured. Payment fields will not work.');
            } else {
                error_log('Amadex: NMI Tokenization Key is configured. Length: ' . strlen($tokenization_key) . ', Preview: ' . substr($tokenization_key, 0, 10) . '...');
                // Check for common issues
                if (strlen($tokenization_key) < 10) {
                    error_log('Amadex Warning: Tokenization key seems too short (' . strlen($tokenization_key) . ' chars). Expected at least 10 characters.');
                }
                if (preg_match('/\s/', $tokenization_key)) {
                    error_log('Amadex Warning: Tokenization key contains whitespace. This may cause authentication issues.');
                }
            }
            // Get sandbox mode setting
            $sandbox_mode = isset($payment_settings['nmi_sandbox']) && $payment_settings['nmi_sandbox'] == 1;

            // Safely parse the 3DS configuration option flag from admin settings
            // Use new enable_3ds toggle (defaults to enabled if not set)
            $three_ds_enabled = isset($payment_settings['enable_3ds']) ? (bool)(int)$payment_settings['enable_3ds'] : true;

            // NMI tokenization uses secure.nmi.com for BOTH test and live; the key type (test vs live) determines behavior.
            // sandbox.nmi.com does not host the token API (returns 404).
            $bypass_payment = isset($payment_settings['nmi_bypass_for_testing']) && $payment_settings['nmi_bypass_for_testing'] == 1;
            wp_localize_script('amadex-booking', 'AmadexNMI', array(
                'tokenizationKey' => $tokenization_key,
                'collectJsUrl' => 'https://secure.nmi.com/token/Collect.js',
                'gatewayJsUrl' => 'https://secure.networkmerchants.com/js/v1/Gateway.js',
                'sandboxMode' => $sandbox_mode,
                'threeDSEnabled' => $three_ds_enabled,
                'bypassPayment' => $bypass_payment
            ));
        } elseif ($default_card_gateway === 'stripe') {
            // Stripe in-page flow: Load Stripe.js and pass publishable key so flight-booking page can show card element and charge in-page (no redirect)
            $stripe_publishable_key = isset($payment_settings['stripe_publishable_key']) ? trim($payment_settings['stripe_publishable_key']) : '';
            if (!empty($stripe_publishable_key)) {
                wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, false);
                wp_localize_script('amadex-booking', 'AmadexStripe', array(
                    'publishableKey' => $stripe_publishable_key,
                    'mode' => isset($payment_settings['stripe_mode']) ? $payment_settings['stripe_mode'] : 'test'
                ));
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex: Stripe gateway selected - in-page card form on flight-booking');
            }
        }

        // PayPal: Pre-load SDK when enabled (Pay Later, Venmo, Credit + buttons)
        $enable_paypal = isset($payment_settings['enable_paypal']) ? (int) $payment_settings['enable_paypal'] : 1;
        $paypal_client_id = isset($payment_settings['paypal_client_id']) ? trim($payment_settings['paypal_client_id']) : '';
        // Validate PayPal client ID: must not be an email and must look like a real ID (real IDs are ~80 chars, alphanumeric)
        $paypal_client_id_valid = !empty($paypal_client_id)
            && filter_var($paypal_client_id, FILTER_VALIDATE_EMAIL) === false
            && strpos($paypal_client_id, '@') === false
            && strlen($paypal_client_id) >= 10;

        if ($enable_paypal && $paypal_client_id_valid && $default_card_gateway === 'nmi') {
            $paypal_base = (isset($payment_settings['paypal_mode']) && $payment_settings['paypal_mode'] === 'live') ? 'https://www.paypal.com/sdk/js' : 'https://www.sandbox.paypal.com/sdk/js';
            $paypal_sdk_url = $paypal_base . '?client-id=' . esc_attr($paypal_client_id) . '&currency=USD&intent=capture&components=buttons,messages&enable-funding=paylater,venmo,credit';
            wp_enqueue_script('paypal-sdk', $paypal_sdk_url, array(), null, true);
        } elseif ($enable_paypal && !empty($paypal_client_id) && !$paypal_client_id_valid) {
            error_log('Amadex Warning: PayPal SDK not loaded — client ID appears invalid (email or too short). Please enter a valid PayPal Client ID in Amadex Settings → Payment Settings.');
        }

        // Get currency data for JavaScript (only if class exists)
        $currency_data = array();
        $default_currency = 'USD';

        if (class_exists('Amadex_Currency')) {
            try {
                $currencies = Amadex_Currency::get_supported_currencies();
                foreach ($currencies as $code => $info) {
                    $currency_data[$code] = array(
                        'name' => $info['name'],
                        'symbol' => $info['symbol']
                    );
                }
                $default_currency = Amadex_Currency::get_default_currency();
            } catch (Exception $e) {
                error_log('Amadex Currency Error: ' . $e->getMessage());
                // Fallback: basic currency list
                $currency_data = array(
                    'USD' => array('name' => 'US Dollar', 'symbol' => '$'),
                    'EUR' => array('name' => 'Euro', 'symbol' => '€'),
                    'GBP' => array('name' => 'British Pound', 'symbol' => '£'),
                );
            }
        } else {
            // Fallback: basic currency list if class not loaded
            $currency_data = array(
                'USD' => array('name' => 'US Dollar', 'symbol' => '$'),
                'EUR' => array('name' => 'Euro', 'symbol' => '€'),
                'GBP' => array('name' => 'British Pound', 'symbol' => '£'),
            );
        }

        wp_localize_script('amadex-booking', 'AmadexCurrency', array(
            'currencies' => $currency_data,
            'default' => $default_currency,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amadex_currency_nonce')
        ));
    }

    // public function render_search($atts = array())
    // {
    //     $atts = shortcode_atts(array(
    //         'results_page' => site_url('/flight-results/')
    //     ), $atts, 'amadex_flight_search');
    private function is_block_editor()
    {
        return (defined('REST_REQUEST') && REST_REQUEST) || is_admin();
    }
    public function render_search($atts = array())
    {
        if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin()) {
            return '<div style="padding:20px;background:#f0f9ff;border:2px dashed #0E7D3F;text-align:center;color:#0E7D3F;border-radius:8px;font-family:sans-serif;"><strong>&#9992; Amadex Flight Search</strong><br><small style="color:#666;">Renders on the frontend only</small></div>';
        }
        $atts = shortcode_atts(array(
            'results_page' => site_url('/flight-results/')
        ), $atts, 'amadex_flight_search');
        ob_start();
?>
        <div class="amadex-search">
            <form class="amadex-form" id="amadex-form" data-results="<?php echo esc_url($atts['results_page']); ?>">
                <div class="amadex-field amadex-trip">
                    <label><?php echo esc_html__('Trip', 'amadex'); ?></label>
                    <div class="amadex-trip-types">
                        <label><input type="radio" name="tripType" value="oneway" checked />
                            <?php echo esc_html__('One Way', 'amadex'); ?></label>
                        <label><input type="radio" name="tripType" value="round" />
                            <?php echo esc_html__('Round Trip', 'amadex'); ?></label>
                        <label><input type="radio" name="tripType" value="multi-city" />
                            <?php echo esc_html__('Multi-City', 'amadex'); ?></label>
                    </div>
                </div>
                <div class="amadex-field">
                    <label><?php echo esc_html__('From', 'amadex'); ?></label>
                    <input type="text" id="amadex-from" placeholder="City or Airport" autocomplete="off" />
                    <input type="hidden" id="amadex-from-code" />
                </div>
                <div class="amadex-field">
                    <label><?php echo esc_html__('To', 'amadex'); ?></label>
                    <input type="text" id="amadex-to" placeholder="City or Airport" autocomplete="off" />
                    <input type="hidden" id="amadex-to-code" />
                </div>
                <div class="amadex-field">
                    <label><?php echo esc_html__('Departure', 'amadex'); ?></label>
                    <input type="date" id="amadex-departure" />
                </div>
                <div class="amadex-field" id="amadex-return-wrap" style="display:none">
                    <label><?php echo esc_html__('Return', 'amadex'); ?></label>
                    <input type="date" id="amadex-return" />
                </div>
                <div class="amadex-field amadex-passengers-cabin-field">
                    <label><?php echo esc_html__('Passengers & Cabin', 'amadex'); ?></label>
                    <div class="amadex-passengers-cabin-trigger" id="amadex-passengers-cabin-trigger">
                        <span class="amadex-pax-icon">👤</span>
                        <span class="amadex-pax-summary" id="amadex-pax-summary">1 Adult</span>
                        <span class="amadex-cabin-summary" id="amadex-cabin-summary">Economy</span>
                        <span class="amadex-trigger-arrow">▼</span>
                    </div>
                    <!-- Hidden inputs for form submission -->
                    <input type="hidden" id="amadex-adults" value="1" />
                    <input type="hidden" id="amadex-children" value="0" />
                    <input type="hidden" id="amadex-infants-lap" value="0" />
                    <input type="hidden" id="amadex-infants-seat" value="0" />
                    <input type="hidden" id="amadex-infants" value="0" />
                    <input type="hidden" id="amadex-cabin" value="ECONOMY" />
                </div>

                <!-- <div class="amadex-field">
                        <select id="amadex-currency">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="INR">INR</option>
                            <option value="CAD">CAD</option>
                            <option value="AUD">AUD</option>
                       </select>
                </div> -->

                <button type="submit" class="amadex-button"><?php echo esc_html__('Search', 'amadex'); ?></button>
            </form>

            <!-- Passengers & Cabin Selection Modal -->
            <div class="amadex-modal" id="amadex-passengers-cabin-modal">
                <div class="amadex-modal-content amadex-passengers-cabin-modal-content">
                    <button class="amadex-modal-close">&times;</button>

                    <div class="amadex-modal-body">
                        <!-- Cabin Section -->
                        <div class="amadex-cabin-section">
                            <h4><?php echo esc_html__('Cabin', 'amadex'); ?></h4>
                            <div class="amadex-cabin-options">
                                <button type="button" class="amadex-cabin-btn active" data-cabin="ECONOMY">
                                    <?php echo esc_html__('Economy', 'amadex'); ?>
                                </button>
                                <button type="button" class="amadex-cabin-btn" data-cabin="BUSINESS">
                                    <?php echo esc_html__('Business', 'amadex'); ?>
                                </button>
                                <button type="button" class="amadex-cabin-btn" data-cabin="PREMIUM_ECONOMY">
                                    <?php echo esc_html__('Premium Economy', 'amadex'); ?>
                                </button>
                                <button type="button" class="amadex-cabin-btn" data-cabin="FIRST">
                                    <?php echo esc_html__('First', 'amadex'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Travelers Section -->
                        <div class="amadex-travelers-section">
                            <h4><?php echo esc_html__('Traveler(s)', 'amadex'); ?></h4>

                            <!-- Adults -->
                            <div class="amadex-traveler-row">
                                <div class="amadex-traveler-info">
                                    <span class="amadex-traveler-label"><?php echo esc_html__('Adults', 'amadex'); ?></span>
                                    <span class="amadex-traveler-age"><?php echo esc_html__('(18-64 yrs)', 'amadex'); ?></span>
                                </div>
                                <div class="amadex-traveler-counter">
                                    <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                        data-target="adults">−</button>
                                    <span class="amadex-counter-value" id="amadex-adults-count">1</span>
                                    <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                        data-target="adults">+</button>
                                </div>
                            </div>

                            <!-- Children -->
                            <div class="amadex-traveler-row">
                                <div class="amadex-traveler-info">
                                    <span class="amadex-traveler-label"><?php echo esc_html__('Children', 'amadex'); ?></span>
                                    <span class="amadex-traveler-age"><?php echo esc_html__('(2-11 Yrs)', 'amadex'); ?></span>
                                </div>
                                <div class="amadex-traveler-counter">
                                    <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                        data-target="children">−</button>
                                    <span class="amadex-counter-value" id="amadex-children-count">0</span>
                                    <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                        data-target="children">+</button>
                                </div>
                            </div>

                            <!-- Infants (lap) -->
                            <div class="amadex-traveler-row">
                                <div class="amadex-traveler-info">
                                    <span class="amadex-traveler-label"><?php echo esc_html__('Infants', 'amadex'); ?></span>
                                    <span class="amadex-traveler-age"><?php echo esc_html__('(lap)', 'amadex'); ?></span>
                                </div>
                                <div class="amadex-traveler-counter">
                                    <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                        data-target="infants-lap">−</button>
                                    <span class="amadex-counter-value" id="amadex-infants-lap-count">0</span>
                                    <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                        data-target="infants-lap">+</button>
                                </div>
                            </div>

                            <!-- Infants (On Seat) -->
                            <div class="amadex-traveler-row">
                                <div class="amadex-traveler-info">
                                    <span class="amadex-traveler-label"><?php echo esc_html__('Infants', 'amadex'); ?></span>
                                    <span class="amadex-traveler-age"><?php echo esc_html__('(On Seat)', 'amadex'); ?></span>
                                </div>
                                <div class="amadex-traveler-counter">
                                    <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                        data-target="infants-seat">−</button>
                                    <span class="amadex-counter-value" id="amadex-infants-seat-count">0</span>
                                    <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                        data-target="infants-seat">+</button>
                                </div>
                            </div>

                            <div class="amadex-unaccompanied-minor">
                                <a href="#"
                                    class="amadex-unaccompanied-link"><?php echo esc_html__('Unaccompanied Minor', 'amadex'); ?></a>
                            </div>
                        </div>

                        <!-- Done Button -->
                        <div class="amadex-modal-actions">
                            <button type="button" class="amadex-done-btn" id="amadex-passengers-done">
                                <?php echo esc_html__('Done', 'amadex'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="amadex-results" class="amadex-results"></div>


            <!-- Sticky Call Now Banner - DISABLED: Now using Gutenberg block -->
            <?php // echo $this->render_call_now_banner(); 
            ?>
        </div>
    <?php
        return ob_get_clean();
    }



    // new flytravelay.com search bar design

    /**
     * Render V2 modern search form
     */
    // public function render_search_v2($atts = array()) {
    //     $atts = shortcode_atts(array(
    //         'results_page' => site_url('/flight-results/'),
    //         'button_text' => 'SEARCH',
    //         'is_results_page' => false
    //     ), $atts, 'amadex_search_v2');

    //     // Enqueue V2 assets
    //     wp_enqueue_style('amadex-search-v2', 
    //         AMADEX_PLUGIN_URL . 'assets/css/amadex-search-v2.css', 
    //         array(), 
    //         AMADEX_VERSION
    //     );

    //     wp_enqueue_script('amadex-search-v2', 
    //         AMADEX_PLUGIN_URL . 'assets/js/amadex-search-v2.js', 
    //         array('jquery'), 
    //         AMADEX_VERSION, 
    //         true
    //     );

    //     wp_localize_script('amadex-search-v2', 'amadexData', array(
    //         'ajaxUrl' => admin_url('admin-ajax.php'),
    //         'nonce' => wp_create_nonce('amadex_search_nonce')
    //     ));

    //     ob_start();
    //     include AMADEX_PLUGIN_DIR . 'templates/flight-search-modern-v2.php';
    //     return ob_get_clean();
    // }

    // public function render_results_page()
    // {
    //     ob_start();
    public function render_results_page()
    {
        if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin()) return '';
        ob_start();
    ?>
        <div class="amadex-overlay1"></div>
        <!-- Desktop search form: visible on desktop, hidden on mobile -->

        <div id="amadex-results-page" class="amadex-results-page">

            <!-- ── Search Summary Banner ───────────────────────────────────────── -->
            <div class="amadex-search-summary-banner" id="amadex-search-summary-banner">
                <div class="amadex-ssb-inner">

                    <!-- Route pill -->
                    <div class="amadex-ssb-route">
                        <span class="amadex-ssb-iata" id="amadex-ssb-origin-iata">—</span>
                        <span class="amadex-ssb-arrow">
                            <svg width="32" height="10" viewBox="0 0 32 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 5h29M26 1l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="amadex-ssb-iata" id="amadex-ssb-dest-iata">—</span>
                    </div>

                    <div class="amadex-ssb-divider"></div>

                    <!-- Dates -->
                    <div class="amadex-ssb-chip" id="amadex-ssb-dates">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <line x1="16" y1="2" x2="16" y2="6" />
                            <line x1="8" y1="2" x2="8" y2="6" />
                            <line x1="3" y1="10" x2="21" y2="10" />
                        </svg>
                        <span id="amadex-ssb-dates-text">—</span>
                    </div>

                    <div class="amadex-ssb-divider"></div>

                    <!-- Travellers -->
                    <div class="amadex-ssb-chip" id="amadex-ssb-travellers">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4" />
                            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" />
                        </svg>
                        <span id="amadex-ssb-travellers-text">—</span>
                    </div>

                    <div class="amadex-ssb-divider"></div>

                    <!-- Cabin -->
                    <div class="amadex-ssb-chip" id="amadex-ssb-cabin">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12l2-2 4 4 8-8 2 2-10 10z" />
                        </svg>
                        <span id="amadex-ssb-cabin-text">Economy</span>
                    </div>

                    <!-- Spacer -->
                    <div class="amadex-ssb-spacer"></div>

                    <!-- Full route name (subtle) -->
                    <div class="amadex-ssb-fullroute" id="amadex-ssb-fullroute">—</div>

                    <div class="amadex-ssb-divider"></div>

                    <!-- Edit button -->
                    <button type="button" id="amadex-ssb-edit-btn" class="amadex-ssb-edit-btn" aria-label="Edit search">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                        Modify
                    </button>

                </div>
            </div>
            <!-- ── End Search Summary Banner ───────────────────────────────────── -->

            <!-- Mobile Route Header -->
            <div class="amadex-route-header">
                <div class="outer-route">
                    <div class="amadex-route-info">
                        <div class="amadex-route-title" id="amadex-mobile-route-title">Loading...</div>
                        <div class="amadex-route-details" id="amadex-mobile-route-details">Loading...</div>
                    </div>
                    <button class="amadex-route-edit" id="amadex-mobile-edit-search" type="button" aria-label="Edit Search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                </div>

                <div class="desktop-search-bar">
                    <div class="amadex-search-bar-container amadex-desktop-only">
                        <div class="amadex-search-summary-modern" id="amadex-search-summary">
                            <?php
                            $results_atts = array(
                                'results_page' => get_permalink(),
                                'button_text' => 'Modify',
                                'is_results_page' => true,
                                'show_icon' => false
                            );
                            echo $this->render_modern_search($results_atts);
                            ?>
                        </div>
                    </div>
                </div>
                <!-- <div class="desktop-search-bar">
                    <div class="amadex-search-bar-container amadex-desktop-only">
                        <div class="amadex-search-summary-modern" id="amadex-search-summary">
                            <?php
                            // $results_atts = array(
                            //     'results_page' => get_permalink(),
                            //     'button_text' => 'Modify',
                            //     'is_results_page' => true,
                            //     'show_icon' => false
                            // );
                            // echo $this->render_modern_search($results_atts);
                            ?>
                        </div>
                    </div>
                </div> -->
                <!-- <div class="desktop-search-bar">
            <div class="amadex-search-bar-container amadex-desktop-only">
                <div class="amadex-search-summary-modern" id="amadex-search-summary">
                    <?php
                    // $results_atts = array(
                    //     'results_page' => get_permalink(),
                    //     'button_text' => 'Modify',
                    //     'is_results_page' => true,
                    //     'show_icon' => false
                    // );
                    // echo $this->render_modern_search($results_atts);
                    ?>
                </div>
            </div>
        </div> -->
            </div>





            <!-- Modern Search Bar Wrapper - Full Width -->
            <div class="amadex-search-bar-wrapper">

                <!-- Mobile Only: Modify Search Header -->
                <div class="amadex-search-bar-header-mobile">
                    <div class="amadex-search-bar-title">
                        Modify Search
                    </div>
                    <button type="button" class="amadex-search-bar-close" aria-label="Close search">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" aria-hidden="true"
                            focusable="false">
                            <g transform="translate(-340 -317)">
                                <g transform="translate(340 317)" fill="#ffffff" stroke="#707070" stroke-width="1">
                                    <circle cx="9" cy="9" r="9" stroke="none" />
                                    <circle cx="9" cy="9" r="8.5" fill="none" />
                                </g>
                                <g transform="translate(344.979 321.979)">
                                    <path
                                        d="M4.881,4.261,8.093,1.048A.534.534,0,1,0,7.337.293L4.125,3.505.913.293a.534.534,0,0,0-.756.756L3.369,4.261.157,7.473a.534.534,0,1,0,.756.756L4.125,5.017,7.337,8.229a.534.534,0,0,0,.756-.756Z"
                                        transform="translate(0 -0.136)" fill="#707070" />
                                </g>
                            </g>
                        </svg>
                    </button>
                </div>

                <!-- Single form: desktop bar on large screens, popup on mobile -->

                <!-- <div class="desktop-search-bar">
                    <div class="amadex-search-bar-container amadex-desktop-only">
                        <div class="amadex-search-summary-modern" id="amadex-search-summary">
                            <?php
                            // $results_atts = array(
                            //     'results_page' => get_permalink(),
                            //     'button_text' => 'Modify',
                            //     'is_results_page' => true,
                            //     'show_icon' => false
                            // );
                            // echo $this->render_modern_search($results_atts);
                            ?>
                        </div>
                    </div>
                </div> -->
                <div class="amadex-search-bar-container">
                    <div class="amadex-search-summary-modern" id="amadex-search-summary">
                        <?php
                        $results_atts = array(
                            'results_page' => get_permalink(),
                            'button_text' => 'Modify',
                            'is_results_page' => true,
                            'show_icon' => false
                        );
                        echo $this->render_modern_search($results_atts);
                        ?>
                    </div>
                </div>
            </div>



            <!-- Mobile Results Header -->
            <div class="amadex-results-header-mobile">
                <div class="amadex-results-count-mobile">
                    <strong id="amadex-mobile-results-count-display">0</strong> Flights Available
                </div>
            </div>

            <!-- Results Content Wrapper - Full Width -->
            <div class="amadex-results-content-wrapper">
                <!-- Results Content -->
                <div class="amadex-results-content">


                    <!-- Left Sidebar Airline Promotions -->
                    <!-- <div class="amadex-airline-promotions-left">
                       
                        <div class="amadex-airline-promotion-card amadex-sticky-promotion">
                            <a href="https://www.flytravelay.com/lufthansa-airlines/" target="_blank" rel="noopener noreferrer" class="amadex-airline-promotion-link">
                                <div class="amadex-airline-promotion-image">
                                    <img src="<?php echo AMADEX_URL; ?>assets/images/Lufthansa Airlines@2x.png" alt="Lufthansa Airlines" class="amadex-airline-promotion-plane">
                                </div>
                            </a>
                        </div>

                        <div class="amadex-airline-promotion-card amadex-sticky-promotion">
                            <a href="https://www.flytravelay.com/emirates-airlines/" target="_blank" rel="noopener noreferrer" class="amadex-airline-promotion-link">
                                <div class="amadex-airline-promotion-image">
                                    <img src="<?php echo AMADEX_URL; ?>assets/images/Emirates Airlines@2x.png" alt="Emirates Airlines" class="amadex-airline-promotion-plane">
                                </div>
                            </a>
                        </div>
                    </div> -->

                    <!-- Left Sidebar Filters -->
                    <div class="amadex-filters-sidebar">
                        <div class="amadex-filters-header">
                            <div>
                                <p class="amadex-filters-label">Filters</p>
                                <span class="amadex-results-count">Showing <span id="amadex-results-count">0</span>
                                    Results</span>
                            </div>
                            <button class="amadex-clear-filters" id="amadex-clear-filters">Clear All</button>
                        </div>

                        <div class="amadex-filter-tags" id="amadex-active-filters"></div>

                        <!-- Popular Filters -->
                        <div class="amadex-filter-group amadex-filter-group-popular-main">
                            <div class="amadex-filter-group-heading">
                                <h4>Popular Filters</h4>
                                <label class="amadex-switch">
                                    <input type="checkbox" class="amadex-popular-toggle" id="amadex-popular-toggle">
                                    <span class="amadex-switch-slider"></span>
                                </label>
                            </div>
                            <div class="amadex-filter-options" id="amadex-popular-filter">
                                <!-- Populated dynamically -->
                            </div>
                            <button type="button" class="amadex-popular-show-all" id="amadex-popular-show-all" style="display:none;">
                                <span class="amadex-popular-show-all-label">Show all</span>
                            </button>
                        </div>

                        <!-- Price Filter -->
                        <div class="amadex-filter-group amadex-filter-group-price">
                            <div class="amadex-filter-group-heading">
                                <h4>Price</h4>
                                <!-- <div class="amadex-price-values">
                                <span id="amadex-price-min-display">$0</span>
                                <span class="amadex-price-divider"></span>
                                <span id="amadex-price-max-display">$1000</span>
                            </div> -->
                            </div>
                            <div class="amadex-price-range">
                                <div class="amadex-price-slider">
                                    <div class="amadex-price-values">
                                        <div class="amadex-price-slider-track"></div>
                                        <input type="range" id="amadex-price-min" min="130" max="2891" value="130" step="1">
                                        <span id="amadex-price-min-display">$0</span>
                                        <input type="range" id="amadex-price-max" min="130" max="2891" value="2891" step="1">
                                        <span id="amadex-price-max-display">$1000</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stop Filter -->
                        <div class="amadex-filter-group amadex-filter-group-popular">
                            <div class="amadex-filter-group-heading">
                                <h4>Number of stops</h4>
                                <label class="amadex-switch">
                                    <input type="checkbox" class="amadex-filter-toggle" data-target="#amadex-stops-filter"
                                        checked>
                                    <span class="amadex-switch-slider"></span>
                                </label>
                            </div>
                            <div class="amadex-filter-options amadex-filter-chip-grid" id="amadex-stops-filter">
                                <!-- Stops will be populated dynamically -->
                            </div>
                        </div>






                        <!-- Departure Time - DEL Filter -->
                        <div class="amadex-filter-group">
                            <div class="amadex-filter-group-heading">
                                <h4>Departure <span id="amadex-departure-city">Time</span></h4>
                            </div>
                            <div class="amadex-filter-options amadex-filter-time-grid">
                                <label class="amadex-filter-option amadex-time-card">
                                    <input type="checkbox" name="departure_time" value="early_morning">
                                    <span class="amadex-time-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="35.333" height="18.702"
                                            viewBox="0 0 35.333 18.702">
                                            <g id="Group_183" data-name="Group 183" transform="translate(0 -120.501)">
                                                <path id="Path_14" data-name="Path 14"
                                                    d="M32.966,129.334a1.035,1.035,0,0,0-1.414-.379l-2.881,1.663a1.035,1.035,0,1,0,1.035,1.793l2.881-1.663A1.035,1.035,0,0,0,32.966,129.334ZM26.5,122.868a1.035,1.035,0,0,0-1.414.379l-1.663,2.881a1.035,1.035,0,1,0,1.793,1.035l1.663-2.881A1.035,1.035,0,0,0,26.5,122.868ZM17.667,120.5a1.035,1.035,0,0,0-1.035,1.035v3.326a1.035,1.035,0,0,0,2.07,0v-3.326A1.035,1.035,0,0,0,17.667,120.5Zm-5.756,5.626-1.663-2.881a1.035,1.035,0,1,0-1.793,1.035l1.663,2.881a1.035,1.035,0,1,0,1.793-1.035Zm-5.249,4.491-2.881-1.663a1.035,1.035,0,1,0-1.035,1.793l2.881,1.663a1.035,1.035,0,1,0,1.035-1.793ZM34.3,137.132H25.284a7.688,7.688,0,0,0-15.236,0H1.035a1.035,1.035,0,0,0,0,2.07H34.3a1.035,1.035,0,0,0,0-2.07Zm-22.152,0a5.616,5.616,0,0,1,11.04,0Z"
                                                    transform="translate(0 0)" fill="currentcolor" />
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="amadex-filter-label">Before 6 AM</span>
                                    <span class="amadex-filter-time">12 AM – 6 AM</span>
                                </label>
                                <label class="amadex-filter-option amadex-time-card">
                                    <input type="checkbox" name="departure_time" value="morning">
                                    <span class="amadex-time-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 25 25">
                                            <g id="Group_184" data-name="Group 184" transform="translate(-1 -1)">
                                                <path id="Path_15" data-name="Path 15"
                                                    d="M13.5,20.318A6.818,6.818,0,1,0,6.682,13.5,6.818,6.818,0,0,0,13.5,20.318Zm0-11.364A4.545,4.545,0,1,1,8.955,13.5,4.545,4.545,0,0,1,13.5,8.955Zm0-4.545a1.136,1.136,0,0,0,1.136-1.136V2.136a1.136,1.136,0,1,0-2.273,0V3.273A1.136,1.136,0,0,0,13.5,4.409ZM12.364,23.727v1.136a1.136,1.136,0,1,0,2.273,0V23.727a1.136,1.136,0,1,0-2.273,0ZM20.732,7.4a1.136,1.136,0,0,0,.8-.333l.8-.8a1.136,1.136,0,1,0-1.607-1.607l-.8.8a1.136,1.136,0,0,0,.8,1.94ZM5.465,19.928l-.8.8a1.136,1.136,0,1,0,1.607,1.607l.8-.8a1.136,1.136,0,1,0-1.607-1.607Zm19.4-7.565H23.727a1.136,1.136,0,1,0,0,2.273h1.136a1.136,1.136,0,1,0,0-2.273ZM2.136,14.636H3.273a1.136,1.136,0,1,0,0-2.273H2.136a1.136,1.136,0,1,0,0,2.273Zm17.792,5.292a1.136,1.136,0,0,0,0,1.607l.8.8a1.136,1.136,0,1,0,1.607-1.607l-.8-.8A1.136,1.136,0,0,0,19.928,19.928ZM5.465,7.072A1.136,1.136,0,1,0,7.072,5.465l-.8-.8A1.136,1.136,0,1,0,4.661,6.268Z"
                                                    fill="currentcolor" />
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="amadex-filter-label">6 AM to 12 PM</span>
                                    <span class="amadex-filter-time">Morning</span>
                                </label>
                                <label class="amadex-filter-option amadex-time-card">
                                    <input type="checkbox" name="departure_time" value="afternoon">
                                    <span class="amadex-time-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28.523" height="24.626"
                                            viewBox="0 0 28.523 24.626">
                                            <g id="Group_187" data-name="Group 187" transform="translate(0 -34.974)">
                                                <path id="Path_18" data-name="Path 18"
                                                    d="M10.292,38.328a.836.836,0,0,0,.836-.836V35.81a.836.836,0,1,0-1.671,0v1.683A.836.836,0,0,0,10.292,38.328ZM2.711,41.387l-1.457-.841a.836.836,0,1,0-.836,1.447l1.457.841a.836.836,0,1,0,.836-1.447Zm-.836,9.236-1.457.841a.836.836,0,1,0,.836,1.447l1.457-.841a.836.836,0,1,0-.836-1.447Zm16.833-7.789,1.457-.841a.836.836,0,0,0-.836-1.447l-1.457.841a.836.836,0,1,0,.836,1.447Zm3.514,5.081A6.282,6.282,0,0,0,16.393,44,6.684,6.684,0,1,0,6.7,52.363,4.717,4.717,0,0,0,10.678,59.6c13.148-.014,12.429.034,12.658-.037a5.85,5.85,0,0,0-1.113-11.645ZM5.278,46.729a5.012,5.012,0,0,1,9.371-2.476,6.268,6.268,0,0,0-4.5,5.95,4.628,4.628,0,0,0-2.287.908,5.017,5.017,0,0,1-2.581-4.382ZM23.02,57.912c-.3.024-7.881.01-12.341.014A3.045,3.045,0,0,1,8.63,52.639a3.008,3.008,0,0,1,2.328-.78.836.836,0,0,0,.9-.948,4.6,4.6,0,0,1,8.986-1.853.836.836,0,0,0,.976.6,4.179,4.179,0,1,1,1.2,8.255Z"
                                                    transform="translate(0)" fill="currentcolor" />
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="amadex-filter-label">12 PM to 6 PM</span>
                                    <span class="amadex-filter-time">Afternoon</span>
                                </label>
                                <label class="amadex-filter-option amadex-time-card">
                                    <input type="checkbox" name="departure_time" value="evening">
                                    <span class="amadex-time-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" id="Group_186" data-name="Group 186"
                                            width="23.157" height="22.977" viewBox="0 0 23.157 22.977">
                                            <path id="Path_17" data-name="Path 17"
                                                d="M21.8,8.093a4.78,4.78,0,0,1-6.739-6.739L16.223,0,14.458.244A7.493,7.493,0,0,0,8,7.664c0,.1,0,.2.007.307a4.347,4.347,0,0,0-4.132,4.336V13.58A4.559,4.559,0,0,0,0,18.082v.343a4.557,4.557,0,0,0,4.552,4.552H16.6a3.821,3.821,0,1,0,0-7.641h-.1q-.018-.122-.042-.242A7.493,7.493,0,0,0,22.913,8.7l.244-1.765Zm-5.208,8.6a2.468,2.468,0,1,1,0,4.936H4.552a3.2,3.2,0,0,1-3.2-3.2v-.343a3.2,3.2,0,0,1,3.2-3.2h.676V12.307A2.992,2.992,0,0,1,8.217,9.318H8.29a2.992,2.992,0,0,1,2.989,2.989v.676h.946a2.966,2.966,0,0,1,2.962,2.963v.742Zm2.965-4.426a6.132,6.132,0,0,1-3.6,1.524,4.321,4.321,0,0,0-3.378-2.14A4.353,4.353,0,0,0,9.369,8.1c-.01-.146-.016-.292-.016-.438A6.144,6.144,0,0,1,13.07,2.022a6.132,6.132,0,0,0,8.064,8.065,6.157,6.157,0,0,1-1.573,2.175Zm0,0"
                                                fill="currentcolor" />
                                        </svg>
                                    </span>
                                    <span class="amadex-filter-label">After 6 PM</span>
                                    <span class="amadex-filter-time">Evening / Night</span>
                                </label>
                            </div>
                        </div>

                        <!-- Return Time - DXB Filter -->
                        <div class="amadex-filter-group">
                            <div class="amadex-filter-group-heading">
                                <h4>Arrival <span id="amadex-return-city">Time</span></h4>
                            </div>
                            <div class="amadex-filter-options amadex-filter-time-grid">
                                <label class="amadex-filter-option amadex-time-card">
                                    <input type="checkbox" name="return_time" value="early_morning">
                                    <span class="amadex-time-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="35.333" height="18.702"
                                            viewBox="0 0 35.333 18.702">
                                            <g id="Group_183" data-name="Group 183" transform="translate(0 -120.501)">
                                                <path id="Path_14" data-name="Path 14"
                                                    d="M32.966,129.334a1.035,1.035,0,0,0-1.414-.379l-2.881,1.663a1.035,1.035,0,1,0,1.035,1.793l2.881-1.663A1.035,1.035,0,0,0,32.966,129.334ZM26.5,122.868a1.035,1.035,0,0,0-1.414.379l-1.663,2.881a1.035,1.035,0,1,0,1.793,1.035l1.663-2.881A1.035,1.035,0,0,0,26.5,122.868ZM17.667,120.5a1.035,1.035,0,0,0-1.035,1.035v3.326a1.035,1.035,0,0,0,2.07,0v-3.326A1.035,1.035,0,0,0,17.667,120.5Zm-5.756,5.626-1.663-2.881a1.035,1.035,0,1,0-1.793,1.035l1.663,2.881a1.035,1.035,0,1,0,1.793-1.035Zm-5.249,4.491-2.881-1.663a1.035,1.035,0,1,0-1.035,1.793l2.881,1.663a1.035,1.035,0,1,0,1.035-1.793ZM34.3,137.132H25.284a7.688,7.688,0,0,0-15.236,0H1.035a1.035,1.035,0,0,0,0,2.07H34.3a1.035,1.035,0,0,0,0-2.07Zm-22.152,0a5.616,5.616,0,0,1,11.04,0Z"
                                                    transform="translate(0 0)" fill="currentcolor"></path>
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="amadex-filter-label">Before 6 AM</span>
                                    <span class="amadex-filter-time">12 AM – 6 AM</span>
                                </label>
                                <label class="amadex-filter-option amadex-time-card">
                                    <input type="checkbox" name="return_time" value="morning">
                                    <span class="amadex-time-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 25 25">
                                            <g id="Group_184" data-name="Group 184" transform="translate(-1 -1)">
                                                <path id="Path_15" data-name="Path 15"
                                                    d="M13.5,20.318A6.818,6.818,0,1,0,6.682,13.5,6.818,6.818,0,0,0,13.5,20.318Zm0-11.364A4.545,4.545,0,1,1,8.955,13.5,4.545,4.545,0,0,1,13.5,8.955Zm0-4.545a1.136,1.136,0,0,0,1.136-1.136V2.136a1.136,1.136,0,1,0-2.273,0V3.273A1.136,1.136,0,0,0,13.5,4.409ZM12.364,23.727v1.136a1.136,1.136,0,1,0,2.273,0V23.727a1.136,1.136,0,1,0-2.273,0ZM20.732,7.4a1.136,1.136,0,0,0,.8-.333l.8-.8a1.136,1.136,0,1,0-1.607-1.607l-.8.8a1.136,1.136,0,0,0,.8,1.94ZM5.465,19.928l-.8.8a1.136,1.136,0,1,0,1.607,1.607l.8-.8a1.136,1.136,0,1,0-1.607-1.607Zm19.4-7.565H23.727a1.136,1.136,0,1,0,0,2.273h1.136a1.136,1.136,0,1,0,0-2.273ZM2.136,14.636H3.273a1.136,1.136,0,1,0,0-2.273H2.136a1.136,1.136,0,1,0,0,2.273Zm17.792,5.292a1.136,1.136,0,0,0,0,1.607l.8.8a1.136,1.136,0,1,0,1.607-1.607l-.8-.8A1.136,1.136,0,0,0,19.928,19.928ZM5.465,7.072A1.136,1.136,0,1,0,7.072,5.465l-.8-.8A1.136,1.136,0,1,0,4.661,6.268Z"
                                                    fill="currentcolor" />
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="amadex-filter-label">6 AM to 12 PM</span>
                                    <span class="amadex-filter-time">Morning</span>
                                </label>
                                <label class="amadex-filter-option amadex-time-card">
                                    <input type="checkbox" name="return_time" value="afternoon">
                                    <span class="amadex-time-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="28.523" height="24.626"
                                            viewBox="0 0 28.523 24.626">
                                            <g id="Group_187" data-name="Group 187" transform="translate(0 -34.974)">
                                                <path id="Path_18" data-name="Path 18"
                                                    d="M10.292,38.328a.836.836,0,0,0,.836-.836V35.81a.836.836,0,1,0-1.671,0v1.683A.836.836,0,0,0,10.292,38.328ZM2.711,41.387l-1.457-.841a.836.836,0,1,0-.836,1.447l1.457.841a.836.836,0,1,0,.836-1.447Zm-.836,9.236-1.457.841a.836.836,0,1,0,.836,1.447l1.457-.841a.836.836,0,1,0-.836-1.447Zm16.833-7.789,1.457-.841a.836.836,0,0,0-.836-1.447l-1.457.841a.836.836,0,1,0,.836,1.447Zm3.514,5.081A6.282,6.282,0,0,0,16.393,44,6.684,6.684,0,1,0,6.7,52.363,4.717,4.717,0,0,0,10.678,59.6c13.148-.014,12.429.034,12.658-.037a5.85,5.85,0,0,0-1.113-11.645ZM5.278,46.729a5.012,5.012,0,0,1,9.371-2.476,6.268,6.268,0,0,0-4.5,5.95,4.628,4.628,0,0,0-2.287.908,5.017,5.017,0,0,1-2.581-4.382ZM23.02,57.912c-.3.024-7.881.01-12.341.014A3.045,3.045,0,0,1,8.63,52.639a3.008,3.008,0,0,1,2.328-.78.836.836,0,0,0,.9-.948,4.6,4.6,0,0,1,8.986-1.853.836.836,0,0,0,.976.6,4.179,4.179,0,1,1,1.2,8.255Z"
                                                    transform="translate(0)" fill="currentcolor" />
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="amadex-filter-label">12 PM to 6 PM</span>
                                    <span class="amadex-filter-time">Afternoon</span>
                                </label>
                                <label class="amadex-filter-option amadex-time-card">
                                    <input type="checkbox" name="return_time" value="evening">
                                    <span class="amadex-time-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" id="Group_186" data-name="Group 186"
                                            width="23.157" height="22.977" viewBox="0 0 23.157 22.977">
                                            <path id="Path_17" data-name="Path 17"
                                                d="M21.8,8.093a4.78,4.78,0,0,1-6.739-6.739L16.223,0,14.458.244A7.493,7.493,0,0,0,8,7.664c0,.1,0,.2.007.307a4.347,4.347,0,0,0-4.132,4.336V13.58A4.559,4.559,0,0,0,0,18.082v.343a4.557,4.557,0,0,0,4.552,4.552H16.6a3.821,3.821,0,1,0,0-7.641h-.1q-.018-.122-.042-.242A7.493,7.493,0,0,0,22.913,8.7l.244-1.765Zm-5.208,8.6a2.468,2.468,0,1,1,0,4.936H4.552a3.2,3.2,0,0,1-3.2-3.2v-.343a3.2,3.2,0,0,1,3.2-3.2h.676V12.307A2.992,2.992,0,0,1,8.217,9.318H8.29a2.992,2.992,0,0,1,2.989,2.989v.676h.946a2.966,2.966,0,0,1,2.962,2.963v.742Zm2.965-4.426a6.132,6.132,0,0,1-3.6,1.524,4.321,4.321,0,0,0-3.378-2.14A4.353,4.353,0,0,0,9.369,8.1c-.01-.146-.016-.292-.016-.438A6.144,6.144,0,0,1,13.07,2.022a6.132,6.132,0,0,0,8.064,8.065,6.157,6.157,0,0,1-1.573,2.175Zm0,0"
                                                fill="currentcolor"></path>
                                        </svg>
                                    </span>
                                    <span class="amadex-filter-label">After 6 PM</span>
                                    <span class="amadex-filter-time">Evening / Night</span>
                                </label>
                            </div>
                        </div>

                        <!-- Duration Filter -->
                        <!-- <div class="amadex-filter-group amadex-filter-group-duration">
                            <div class="amadex-filter-group-heading">
                                <h4>Fly Duration</h4>
                                <div class="amadex-duration-values">
                                <span id="amadex-duration-min-display">1h</span>
                                <span class="amadex-price-divider"></span>
                                <span id="amadex-duration-max-display">22h</span>
                            </div>
                            </div>
                            <div class="amadex-duration-range">
                                <div class="amadex-duration-slider">
                                    <div class="amadex-price-values">
                                        <div class="amadex-duration-slider-track"></div>
                                        <input type="range" id="amadex-duration-min" min="1" max="22" value="1" step="1">
                                        <span id="amadex-duration-min-display">1h</span>
                                        <input type="range" id="amadex-duration-max" min="1" max="22" value="22" step="1">
                                        <span id="amadex-duration-max-display">22h</span>
                                    </div>
                                </div>
                            </div>
                        </div> -->


                        <div class="amadex-filter-group amadex-filter-group-duration">
                            <div class="amadex-filter-group-heading">
                                <h4>Fly Duration</h4>
                            </div>

                            <!-- Outbound duration slider -->
                            <div class="amadex-duration-leg">
                                <p class="amadex-duration-leg-label">Depart From <span id="amadex-duration-from-city">Origin</span></p>
                                <div class="amadex-duration-range">
                                    <div class="amadex-duration-slider">
                                        <div class="amadex-duration-slider-track"></div>
                                        <input type="range" id="amadex-duration-min" min="1" max="22" value="1" step="1">
                                        <input type="range" id="amadex-duration-max" min="1" max="22" value="22" step="1">
                                    </div>
                                    <div class="amadex-duration-labels">
                                        <span id="amadex-duration-min-display">1h</span>
                                        <span id="amadex-duration-max-display">22h</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Return duration slider (hidden for one-way) -->
                            <div class="amadex-duration-leg" id="amadex-return-duration-leg" style="display:none;">
                                <p class="amadex-duration-leg-label">Return From <span id="amadex-duration-to-city">Destination</span></p>
                                <div class="amadex-duration-range">
                                    <div class="amadex-duration-slider">
                                        <div class="amadex-return-duration-slider-track"></div>
                                        <input type="range" id="amadex-return-duration-min" min="1" max="22" value="1" step="1">
                                        <input type="range" id="amadex-return-duration-max" min="1" max="22" value="22" step="1">
                                    </div>
                                    <div class="amadex-duration-labels">
                                        <span id="amadex-return-duration-min-display">1h</span>
                                        <span id="amadex-return-duration-max-display">22h</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Meal Filter -->
                        <!-- <div class="amadex-filter-group amadex-filter-group-meal">
                            <div class="amadex-filter-group-heading">
                                <h4>Meals</h4>
                            </div>
                            <div class="amadex-filter-options">
                                <label class="amadex-filter-option">
                                    <input type="checkbox" name="meal" value="free_meal" id="amadex-filter-meal">
                                    <span class="amadex-filter-label">Free Meal Included</span>
                                </label>
                            </div>
                        </div> -->

                        <div class="amadex-filter-group amadex-filter-group-airlines">
                            <!-- <div class="amadex-filter-group-heading">
                            <h4>Airlines</h4>
                        </div> -->
                            <div class="amadex-filter-search">
                                <input type="text" id="amadex-airlines-search" placeholder="Search Airlines">
                                <span class="amadex-filter-search-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15.002" height="15" viewBox="0 0 15.002 15">
                                        <g id="Group_39" data-name="Group 39" transform="translate(0 -0.035)">
                                            <path id="Path_7" data-name="Path 7"
                                                d="M6.038,12.1a6.023,6.023,0,0,0,3.7-1.271l3.991,3.991a.75.75,0,0,0,1.061-1.061L10.8,9.772A6.035,6.035,0,1,0,6.038,12.1ZM2.831,2.864a4.535,4.535,0,1,1,0,6.414h0a4.519,4.519,0,0,1-.023-6.39l.023-.023Z"
                                                transform="translate(0 0)" fill="#707070" />
                                        </g>
                                    </svg>
                                </span>
                            </div>

                            <!-- <div class="amadex-filter-group-heading">
                                <h4>Airlines</h4>
                            </div>



                            <div class="amadex-filter-options amadex-filter-list-scroll" id="amadex-airlines-filter"> -->

                            <div class="amadex-filter-group-heading">
                                <h4>Airlines</h4>
                                <label class="amadex-switch">
                                    <input type="checkbox" id="amadex-airlines-toggle">
                                    <span class="amadex-switch-slider"></span>
                                </label>
                            </div>

                            <div class="amadex-filter-options amadex-filter-list-scroll" id="amadex-airlines-filter"></div>
                            <!-- Airlines will be populated dynamically -->
                        </div>
                    </div>
                    <!-- Main Results Area -->
                    <div class="amadex-results-main">
                        <div class="amadex-loading" id="amadex-loading" style="display: none;">
                            <div class="amadex-loader-wrap">
                                <div class="amadex-loader-plane">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                                        <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" fill="#0e7d3f" />
                                    </svg>
                                </div>
                                <div class="amadex-loader-dots">
                                    <span></span><span></span><span></span><span></span><span></span>
                                </div>
                                <p class="amadex-loader-text">Searching for the best flights...</p>
                                <p class="amadex-loader-sub">Comparing prices across airlines</p>
                            </div>
                        </div>

                        <!-- No Results State -->
                        <!-- <div class="amadex-no-results" id="amadex-no-results" style="display: none;">
                        <p>No flights found for your search criteria.</p>
                    
                    </div> -->

                        <!-- Flight Results List -->
                        <div class="amadex-flights-list" id="amadex-flights-list">
                            <!-- Results Top Header -->
                            <div class="amadex-results-top-header">
                                <h2>Showing <span id="amadex-total-flights">0</span> flights From <span
                                        id="amadex-from-city">New York City</span> to <span id="amadex-to-city">London</span>
                                </h2>
                            </div>

                            <!-- No Results State - Positioned at top after header -->
                            <div class="amadex-no-results" id="amadex-no-results" style="display: none;">
                                <img src="https://travelay.dsstaging1.com/wp-content/uploads/2026/05/Group-32425.svg" alt="">
                                <h3>No flights found</h3>
                                <p>There are no flights available for the selected route and date</p>
                            </div>

                            <div class="amadex-booking-progress" data-booking-stage="booking">
                                <div class="booking-step is-active" data-step="booking">
                                    <span class="booking-step-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18.668" height="14"
                                            viewBox="0 0 18.668 14">
                                            <g id="Group_194" data-name="Group 194" transform="translate(0 -51.208)">
                                                <path id="Path_19" data-name="Path 19"
                                                    d="M18.653,53.8c-.149-.846-1.414-1.331-2.823-1.082L5.745,54.5l-3.739-3.29-1.915.337L1.4,55.263,0,55.51l.135.766A7.942,7.942,0,0,0,6.092,57.6l1.342-.237-.685,3.28,2.3-.406,4.515-3.955,2.809-.5c1.409-.249,2.432-1.137,2.283-1.983ZM0,63.652H18.668v1.556H0Z"
                                                    fill="#0e7d3f" />
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="booking-step-label">Booking Flight</span>
                                </div>
                                <span class="booking-step-divider"></span>
                                <div class="booking-step is-active" data-step="passengers">
                                    <span class="booking-step-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" id="Group_195" data-name="Group 195"
                                            width="18.661" height="18.24" viewBox="0 0 18.661 18.24">
                                            <path id="Path_21" data-name="Path 21"
                                                d="M60.054,64.189h-.74a2.267,2.267,0,0,1-2.265-2.265V58.7a2.267,2.267,0,0,1,2.265-2.265h.74A2.267,2.267,0,0,1,62.318,58.7v3.22A2.267,2.267,0,0,1,60.054,64.189ZM61.509,58.7a1.457,1.457,0,0,0-1.456-1.456h-.74A1.457,1.457,0,0,0,57.858,58.7v3.22a1.457,1.457,0,0,0,1.456,1.456h.74a1.457,1.457,0,0,0,1.456-1.456Zm-7.488,2.772a.723.723,0,0,1,.4.125.759.759,0,0,1,.32.494l.609,3.239a.3.3,0,0,0,.26.245l3.407.437a.761.761,0,0,1,.644.856.676.676,0,0,1-.567.65l-2.623-.255-.868-.091h-.045a.535.535,0,0,1-.067,0l-.186-.018a1.667,1.667,0,0,1-1.4-1.48l-.51-2.717-.109-.594a.762.762,0,0,1,.591-.871A.725.725,0,0,1,54.021,61.477Z"
                                                transform="translate(-49.865 -56.44)" fill="#0E7D3F" />
                                            <path id="Path_22" data-name="Path 22"
                                                d="M55.121,110.177a1.7,1.7,0,0,1-1.515-1.384l-1.6-8.717a1.132,1.132,0,0,1,.91-1.317,1.173,1.173,0,0,1,.207-.019,1.133,1.133,0,0,1,1.111.929l.576,3.142c0,.019,0,.037.008.057l.109.567.548,3a2.069,2.069,0,0,0,1.828,1.679l.081.008c.057.008.114.013.172.017l.993.1,2.438.255a1.315,1.315,0,0,0,.162.01c.021,0,.042,0,.063-.005l1.092.106a1.133,1.133,0,1,1-.22,2.254l-2.064-.2v3.582h3.8l-.352-3.318a1.741,1.741,0,0,0-1.1-2.922l-.216-.021a1.488,1.488,0,0,0,.244-.667c0-.032.005-.064.007-.1.163.018.362.046.59.079a2.137,2.137,0,0,1,1.865,1.987l.32,4.957h5.083a.4.4,0,0,1,0,.809h-17.3a.4.4,0,0,1,0-.809h2.872v-3.99Z"
                                                transform="translate(-51.988 -96.807)" fill="#0E7D3F" />
                                            <ellipse id="Ellipse_19" data-name="Ellipse 19" cx="1.49" cy="1.49" rx="1.49"
                                                ry="1.49" transform="translate(5.875 4.507) rotate(-176.83)" fill="#0E7D3F" />
                                        </svg>
                                    </span>
                                    <span class="booking-step-label">Passenger Details</span>
                                </div>
                                <span class="booking-step-divider"></span>
                                <div class="booking-step" data-step="payment">
                                    <span class="booking-step-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20.001" height="14.376"
                                            viewBox="0 0 20.001 14.376">
                                            <g id="Group_196" data-name="Group 196" transform="translate(0 -9)">
                                                <path id="Path_23" data-name="Path 23"
                                                    d="M16.876,10.563A1.564,1.564,0,0,0,15.313,9H1.563A1.564,1.564,0,0,0,0,10.563v.313H16.876Zm-.313,5.313a2.584,2.584,0,0,1,.313.016V13.375H0v5.313a1.563,1.563,0,0,0,1.563,1.563H12.516a2.584,2.584,0,0,1-.016-.313,4.066,4.066,0,0,1,4.063-4.063ZM4.375,19H1.563a.313.313,0,1,1,0-.625H4.375a.313.313,0,1,1,0,.625Zm2.813-1.25H1.563a.313.313,0,0,1,0-.625H7.188a.313.313,0,0,1,0,.625ZM0,11.5H16.876v1.25H0Zm17.032,8.75h-.156v.938h.156a.469.469,0,1,0,0-.938Zm-1.406-1.094a.469.469,0,0,0,.469.469h.156v-.938h-.156a.469.469,0,0,0-.469.469Z"
                                                    fill="#707070" />
                                                <path id="Path_24" data-name="Path 24"
                                                    d="M45.438,33a3.438,3.438,0,1,0,3.438,3.438A3.438,3.438,0,0,0,45.438,33Zm.469,5.313H45.75v.313a.313.313,0,0,1-.625,0v-.313H44.5a.313.313,0,0,1,0-.625h.625V36.75h-.156a1.094,1.094,0,0,1,0-2.188h.156V34.25a.313.313,0,0,1,.625,0v.313h.625a.313.313,0,0,1,0,.625H45.75v.938h.156a1.094,1.094,0,0,1,0,2.188Z"
                                                    transform="translate(-28.874 -16.5)" fill="#707070" />
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="booking-step-label">Payment</span>
                                </div>
                            </div>

                            <!-- Currency Selector for Results Page - REMOVED: Use Regional Settings Modal instead -->

                            <div class="amadex-results-sort-bar">
                                <div class="amadex-sort-tabs" id="amadex-sort-tabs">
                                    <button class="sort-tab is-active" data-sort="low_to_high">Low to High ↑</button>
                                    <button class="sort-tab" data-sort="high_to_low">High to Low ↓</button>
                                    <button class="sort-tab" data-sort="nearest">Nearest Airport</button>
                                    <button class="sort-tab" data-sort="shortest">Shortest Duration</button>
                                </div>
                                <div class="amadex-results-available">
                                    <span id="amadex-results-available-count">0</span> Flights Available
                                </div>
                                <input type="hidden" id="amadex-sort-by" value="low_to_high">
                            </div>

                            <!-- Airline Price Matrix -->
                            <!-- <div class="amadex-airline-matrix" id="amadex-airline-matrix">
                             Matrix will be populated dynamically 
                        </div> -->

                            <!-- Disclaimer Note -->
                            <div class="amadex-disclaimer-note">
                                <strong>Note:</strong> All fares displayed are quoted in
                                <?php echo esc_html($selected_currency ?? 'USD'); ?> and inclusive of base fare, taxes and all
                                fees. Additional baggage fees may apply as per the airline policies. Some flights displayed
                                either may be for flexible dates or nearby airport(s). <span
                                    class="amadex-currency-payment-note">Payment will be processed in USD.</span>
                            </div>

                            <!-- Filter Pills -->
                            <!-- <div class="amadex-filter-pills-wrapper">
                            <div class="amadex-active-filters" id="amadex-active-filters">
                                Active filters will appear here 
                            </div>
                            <div class="amadex-sort-pills" id="amadex-sort-pills">
                                <button class="amadex-pill" data-sort="cheapest">Cheapest</button>
                                <button class="amadex-pill amadex-pill-recommended active" data-sort="recommended">
                                    <span class="amadex-pill-icon">👍</span> Recommended <span id="amadex-recommended-price">$375.75</span>
                                </button>
                                <button class="amadex-pill" data-sort="shortest">
                                    <span class="amadex-pill-icon">⏱</span> Shortest flights
                                </button>
                            </div>
                        </div> -->

                            <!-- Flight Cards Container -->
                            <div class="amadex-flight-cards-container" id="amadex-flight-cards-container">
                                <!-- Flight cards will be populated here -->
                            </div>
                        </div>

                        <!-- Load More -->
                        <div class="amadex-load-more-wrap" id="amadex-load-more-wrap" style="display:none;">
                            <button class="amadex-btn amadex-btn-outline" id="amadex-load-more">View More</button>
                        </div>
                    </div>

                </div>



            </div>
        </div>
        </div>

        <!-- Flight Details Modal -->
        <div class="amadex-modal" id="amadex-flight-details-modal">
            <div class="amadex-modal-content amadex-flight-details-modal-wrapper">
                <div class="amadex-modal-header amadex-flight-details-header">
                    <h3>Flight Details</h3>
                    <button class="amadex-modal-close amadex-flight-details-close"><svg xmlns="http://www.w3.org/2000/svg"
                            width="30" height="30" viewBox="0 0 30 30">
                            <g id="Group_434" data-name="Group 434" transform="translate(-340 -317)">
                                <g id="Ellipse_9" data-name="Ellipse 9" transform="translate(340 317)" fill="#fff"
                                    stroke="#0e7d3f" stroke-width="1">
                                    <circle cx="15" cy="15" r="15" stroke="none" />
                                    <circle cx="15" cy="15" r="14.5" fill="none" />
                                </g>
                                <g id="Group_146" data-name="Group 146" transform="translate(348 325)">
                                    <path id="Path_12" data-name="Path 12"
                                        d="M8.283,7.136l5.451-5.451A.907.907,0,0,0,12.451.4L7,5.853,1.549.4A.907.907,0,0,0,.266,1.684L5.717,7.136.266,12.587A.907.907,0,1,0,1.549,13.87L7,8.418l5.452,5.451a.907.907,0,0,0,1.283-1.283Zm0,0"
                                        transform="translate(0 -0.136)" fill="#0e7d3f" />
                                </g>
                            </g>
                        </svg></button>
                </div>
                <div class="amadex-modal-body amadex-flight-details-body" id="amadex-flight-details-content">
                    <!-- Flight details will be populated here -->
                </div>
            </div>
        </div>

        <!-- Booking Modal -->
        <div class="amadex-modal" id="amadex-booking-modal">
            <div class="amadex-modal-content">
                <div class="amadex-modal-header">
                    <h3>Book Flight</h3>
                    <button class="amadex-modal-close">&times;</button>
                </div>
                <div class="amadex-modal-body">
                    <div class="amadex-booking-form">
                        <h4>Passenger Information</h4>
                        <div class="amadex-form-group">
                            <label>Full Name</label>
                            <input type="text" id="amadex-passenger-name" required>
                        </div>
                        <div class="amadex-form-group">
                            <label>Email</label>
                            <input type="email" id="amadex-passenger-email" required>
                        </div>
                        <div class="amadex-form-group">
                            <label>Phone</label>
                            <input type="tel" id="amadex-passenger-phone" required>
                        </div>
                        <div class="amadex-booking-actions">
                            <button class="amadex-btn amadex-btn-primary" id="amadex-book-now">Book Now</button>
                            <button class="amadex-btn amadex-btn-secondary" id="amadex-call-now">
                                <img src="<?php echo AMADEX_URL; ?>assets/images/call-icon.gif" alt="Call"
                                    class="amadex-call-icon-btn" />
                                Call Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    public function ajax_airports()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        global $wpdb;
        $q = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
        if ($q === '') {
            wp_send_json_success(array());
        }
        $table = $wpdb->prefix . 'amadex_airports';
        $like = '%' . $wpdb->esc_like($q) . '%';
        $rows = $wpdb->get_results($wpdb->prepare("SELECT code, name, city, country FROM $table WHERE code LIKE %s OR name LIKE %s OR city LIKE %s ORDER BY city LIMIT 10", $like, $like, $like));
        $items = array();
        foreach ($rows as $r) {
            $items[] = array(
                'code' => $r->code,
                'label' => $r->city . ', ' . $r->country . ' (' . $r->code . ')',
            );
        }
        wp_send_json_success($items);
    }

    public function ajax_search()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $params = array(
            'origin' => sanitize_text_field($_POST['origin']),
            'destination' => sanitize_text_field($_POST['destination']),
            'departure_date' => sanitize_text_field($_POST['departure']),
            'adults' => max(1, intval($_POST['adults'])),
            'children' => intval($_POST['children']),
            'infants' => intval($_POST['infants']),
            'travel_class' => sanitize_text_field($_POST['cabin']),
            'currency' => sanitize_text_field($_POST['currency'])
        );
        if (!empty($_POST['return'])) {
            $params['return_date'] = sanitize_text_field($_POST['return']);
        }
        $api = amadex()->api;
        $result = $api->search_flights($params);
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        wp_send_json_success($result);
    }

    public function ajax_checkout()
    {
        check_ajax_referer('amadex_nonce', 'nonce');
        $pay = sanitize_text_field($_POST['pay']);
        $offer = json_decode(wp_unslash($_POST['offer']), true);
        $price = isset($offer['price']['total']) ? $offer['price']['total'] : '0.00';
        $currency = isset($offer['price']['currency']) ? $offer['price']['currency'] : 'USD';

        $payments = get_option('amadex_payment_settings', array());
        if ($pay === 'stripe') {
            $pk = isset($payments['stripe_pk']) ? $payments['stripe_pk'] : '';
            if (empty($pk)) {
                wp_send_json_error(__('Stripe keys not configured', 'amadex'));
            }
            wp_send_json_success(array(
                'provider' => 'stripe',
                'publishableKey' => $pk,
                'amount' => $price,
                'currency' => $currency
            ));
        }

        if ($pay === 'paypal') {
            $client = isset($payments['paypal_client_id']) ? $payments['paypal_client_id'] : (isset($payments['paypal_client']) ? $payments['paypal_client'] : '');
            if (empty($client)) {
                wp_send_json_error(__('PayPal client not configured', 'amadex'));
            }
            wp_send_json_success(array(
                'provider' => 'paypal',
                'clientId' => $client,
                'amount' => $price,
                'currency' => $currency
            ));
        }

        wp_send_json_error(__('Unsupported payment method', 'amadex'));
    }

    /**
     * Render test form for debugging
     */
    public function render_test_form()
    {
        ob_start();
    ?>
        <div class="amadex-test-form">
            <h3>Amadex Test Form</h3>
            <form id="amadex-test-form">
                <div class="amadex-field">
                    <label>From (Origin)</label>
                    <input type="text" id="test-from" placeholder="Enter airport code (e.g., DEL)" />
                    <input type="hidden" id="test-from-code" />
                </div>
                <div class="amadex-field">
                    <label>To (Destination)</label>
                    <input type="text" id="test-to" placeholder="Enter airport code (e.g., DXB)" />
                    <input type="hidden" id="test-to-code" />
                </div>
                <div class="amadex-field">
                    <label>Departure Date</label>
                    <input type="date" id="test-departure" />
                </div>
                <div class="amadex-field">
                    <label>Adults</label>
                    <input type="number" id="test-adults" value="1" min="1" />
                </div>
                <button type="submit" class="amadex-button">Test Search</button>
            </form>
            <div id="test-results"></div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#amadex-test-form').on('submit', function(e) {
                    e.preventDefault();

                    const testData = {
                        origin: $('#test-from-code').val() || $('#test-from').val(),
                        destination: $('#test-to-code').val() || $('#test-to').val(),
                        departure: $('#test-departure').val(),
                        adults: $('#test-adults').val()
                    };

                    console.log('Test Data:', testData);

                    if (!testData.origin || !testData.destination || !testData.departure) {
                        alert('Please fill all fields');
                        return;
                    }

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'amadex_search_flights',
                            origin: testData.origin,
                            destination: testData.destination,
                            departure_date: testData.departure,
                            adults: testData.adults,
                            nonce: '<?php echo wp_create_nonce('amadex_nonce'); ?>'
                        },
                        success: function(response) {
                            console.log('Test Response:', response);
                            $('#test-results').html('<pre>' + JSON.stringify(response, null, 2) +
                                '</pre>');
                        },
                        error: function(xhr, status, error) {
                            console.error('Test Error:', xhr.responseText);
                            $('#test-results').html('<div style="color: red;">Error: ' + xhr
                                .responseText + '</div>');
                        }
                    });
                });
            });
        </script>
    <?php
        return ob_get_clean();
    }

    /**
     * Render modern search form
     */
    // public function render_modern_search($atts = array())
    // {
    //     $atts = shortcode_atts(array(
    //         'results_page' => site_url('/flight-results/'),
    //         'button_text' => 'Search',

    public function render_modern_search($atts = array())
    {
        if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin()) {
            return '<div style="padding:20px;background:#f0f9ff;border:2px dashed #0E7D3F;text-align:center;color:#0E7D3F;border-radius:8px;font-family:sans-serif;"><strong>&#9992; Amadex Flight Search</strong><br><small style="color:#666;">Renders on the frontend only</small></div>';
        }
        $atts = shortcode_atts(array(
            'results_page' => site_url('/flight-results/'),
            'button_text' => 'Search',
            'is_results_page' => false,
            'show_icon' => true
        ), $atts, 'amadex_search_modern');

        ob_start();
        $form_id = $atts['is_results_page'] ? 'amadex-modern-form-results' : 'amadex-modern-form';
        $trip_round_id = $atts['is_results_page'] ? 'trip-round-results' : 'trip-round';
        $trip_oneway_id = $atts['is_results_page'] ? 'trip-oneway-results' : 'trip-oneway';
        $trip_multi_id = $atts['is_results_page'] ? 'trip-multi-results' : 'trip-multi';
        $button_classes = 'amadex-search-btn';
        if (!empty($atts['is_results_page'])) {
            $button_classes .= ' amadex-search-btn-results';
        }
    ?>

        <!-- New search bar visit start -->
        <div id="vsb-search-container">
            <div class="vsb-loader-wrap" id="vsb-loader">
                <div class="vsb-loader">
                    <div class="vsb-loader__spinner"></div>
                    <span class="vsb-loader__text">Loading search...</span>
                </div>
            </div>
            <div class="vsb-wrap vsb-loading">
                <form class="amadex-modern-form" id="<?php echo esc_attr($form_id); ?>"
                    data-results="<?php echo esc_url($atts['results_page']); ?>">
                    <!-- Trip Type Tabs -->
                    <div class="visit-main-nav">
                        <div class="amadex-confirmation-nav">
                            <div class="amadex-trip-selector">
                                <div class="amadex-trip-option">
                                    <input type="radio" class="radio-input" id="<?php echo esc_attr($trip_round_id); ?>" name="tripType" value="round"
                                        checked>
                                    <label for="<?php echo esc_attr($trip_round_id); ?>" class="amadex-trip-label">Round Trip</label>
                                </div>
                                <div class="amadex-trip-option">
                                    <input type="radio" class="radio-input" id="<?php echo esc_attr($trip_oneway_id); ?>" name="tripType" value="oneway">
                                    <label for="<?php echo esc_attr($trip_oneway_id); ?>" class="amadex-trip-label">One way</label>
                                </div>
                                <div class="amadex-trip-option">
                                    <input type="radio" class="radio-input" id="<?php echo esc_attr($trip_multi_id); ?>" name="tripType" value="multi-city">
                                    <label for="<?php echo esc_attr($trip_multi_id); ?>" class="amadex-trip-label">Multi-City</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Search Card -->
                    <div class="vsb-card main-form">
                        <div class="vsb-fields">
                            <div class="visit-location">
                                <!-- Origin -->
                                <div class="vsb-field vsb-field--origin">
                                    <span class="vsb-field__label">Origin</span>
                                    <input type="text" class="vsb-field__value" id="modern-origin"
                                        placeholder="Departure City" autocomplete="off">
                                    <span class="vsb-field__sub" id="origin-description">Description</span>
                                    <div class="amadex-suggestions-dropdown" id="origin-suggestions"></div>
                                </div>


                                <!-- Swap Button -->
                                <button type="button" class="amadex-swap-button" id="swap-locations"
                                    aria-label="Swap locations">
                                    <svg id="swap-locations" xmlns="http://www.w3.org/2000/svg"
                                        xmlns:xlink="http://www.w3.org/1999/xlink" width="48" height="48" viewBox="0 0 48 48">
                                        <defs>
                                            <filter id="Ellipse_1" x="0" y="0" width="32" height="32"
                                                filterUnits="userSpaceOnUse">
                                                <feOffset input="SourceAlpha" />
                                                <feGaussianBlur stdDeviation="3" result="blur" />
                                                <feFlood flood-opacity="0.161" />
                                                <feComposite operator="in" in2="blur" />
                                                <feComposite in="SourceGraphic" />
                                            </filter>
                                        </defs>
                                        <g id="Group_43" data-name="Group 43" transform="translate(-651 -632)">
                                            <g transform="matrix(1, 0, 0, 1, 651, 632)" filter="url(#Ellipse_1)">
                                                <circle id="Ellipse_1-2" data-name="Ellipse 1" cx="15" cy="15" r="15"
                                                    transform="translate(9 9)" fill="#0e7d3f" />
                                            </g>
                                            <g id="Group_2" data-name="Group 2" transform="translate(666 646.004)">
                                                <path id="Path_1" data-name="Path 1"
                                                    d="M4.841,15.754a.768.768,0,0,1-.545-.223L1.223,12.459a.773.773,0,0,1,.545-1.314H17.133a.768.768,0,0,1,0,1.536H3.62l1.767,1.759a.768.768,0,0,1-.545,1.314ZM17.133,9.609H1.768a.768.768,0,1,1,0-1.536H15.282L13.515,6.313a.771.771,0,1,1,1.091-1.091l3.073,3.073a.773.773,0,0,1-.545,1.314Z"
                                                    transform="translate(0 0)" fill="#fff" />
                                            </g>
                                        </g>
                                    </svg>
                                </button>

                                <!-- Destination -->
                                <div class="vsb-field vsb-field--destination">
                                    <span class="vsb-field__label">Destination</span>
                                    <input type="text" class="vsb-field__value" id="modern-destination"
                                        placeholder="Arrival City" autocomplete="off">
                                    <span class="vsb-field__sub" id="destination-description">Description</span>
                                    <div class="amadex-suggestions-dropdown" id="destination-suggestions"></div>
                                </div>
                            </div>

                            <div class="visit-date">
                                <!-- Departure Date -->
                                <div class="vsb-field vsb-field--departure">
                                    <span class="vsb-field__label">Departure Date</span>
                                    <div class="vsb-field__value" id="departure-display">5 Nov, 25</div>
                                    <span class="vsb-field__sub" id="departure-day">Tuesday</span>
                                    <input type="date" id="vsb-departure-date" class="vsb-date-hidden">
                                </div>

                                <!-- Return Date -->
                                <div class="vsb-field vsb-field--return">
                                    <span class="vsb-field__label">Return Date</span>
                                    <div class="vsb-field__value" id="return-display">10 Nov, 25</div>
                                    <span class="vsb-field__sub" id="return-day">Monday</span>
                                    <input type="date" id="vsb-return-date" class="vsb-date-hidden">
                                </div>
                            </div>
                            <!-- Travellers & Cabin -->
                            <div class="vsb-field vsb-field--travellers" id="travellers-field">
                                <span class="vsb-field__label">Travellers &amp; Cabin</span>
                                <div class="amadex-travellers-trigger">
                                    <div class="amadex-travellers-value" id="travellers-display">
                                        <!--                            <svg xmlns="http://www.w3.org/2000/svg" width="21.983" height="18.5" viewBox="0 0 21.983 18.5">-->
                                        <!--  <g id="Group_55" data-name="Group 55" transform="translate(0 -6.348)">-->
                                        <!--    <path id="Path_10" data-name="Path 10" d="M13.266,9.523a4,4,0,0,1,1.859,2.968,3.223,3.223,0,1,0-1.859-2.968Zm-2.112,6.6A3.224,3.224,0,1,0,7.929,12.9,3.224,3.224,0,0,0,11.154,16.125Zm1.368.22H9.786a4.133,4.133,0,0,0-4.128,4.128v3.346l.009.052.23.072a18.791,18.791,0,0,0,5.613.905,11.523,11.523,0,0,0,4.9-.92l.215-.109h.023V20.473a4.132,4.132,0,0,0-4.127-4.128Zm5.334-3.328H15.141a3.973,3.973,0,0,1-1.226,2.768,4.905,4.905,0,0,1,3.5,4.694v1.031a11.109,11.109,0,0,0,4.327-.909l.215-.109h.023V17.144a4.133,4.133,0,0,0-4.128-4.128ZM5.5,12.8a3.2,3.2,0,0,0,1.715-.5,3.99,3.99,0,0,1,1.5-2.545c0-.06.009-.12.009-.181A3.224,3.224,0,1,0,5.5,12.8Zm2.9,2.987a3.976,3.976,0,0,1-1.226-2.752c-.1-.007-.2-.015-.3-.015H4.128A4.133,4.133,0,0,0,0,17.144V20.49l.009.052.23.073a19.355,19.355,0,0,0,4.649.874v-1.01A4.906,4.906,0,0,1,8.392,15.784Z" fill="#666"/>-->
                                        <!--  </g>-->
                                        <!--</svg>-->
                                        <span> 1 Traveler </span> <i class="fa-solid fa-angle-down"></i>
                                    </div>
                                    <div class="amadex-cabin-value" id="cabin-display"></div>
                                </div>

                                <!-- Travellers Dropdown -->
                                <div class="amadex-travellers-dropdown" id="travellers-dropdown">
                                    <!-- Adults -->
                                    <div class="amadex-traveller-row">
                                        <div class="amadex-traveller-label">
                                            <div class="amadex-traveller-type">Adults</div>
                                            <div class="amadex-traveller-age">Above 12 years</div>
                                        </div>
                                        <div class="amadex-traveller-counter">
                                            <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                                data-action="minus" data-target="adults">−</button>
                                            <span class="amadex-counter-value" id="adults-count">1</span>
                                            <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                                data-action="plus" data-target="adults">+</button>
                                        </div>
                                    </div>

                                    <!-- Children -->
                                    <div class="amadex-traveller-row">
                                        <div class="amadex-traveller-label">
                                            <div class="amadex-traveller-type">Children</div>
                                            <div class="amadex-traveller-age">2 - 12 years</div>
                                        </div>
                                        <div class="amadex-traveller-counter">
                                            <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                                data-action="minus" data-target="children">−</button>
                                            <span class="amadex-counter-value" id="children-count">0</span>
                                            <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                                data-action="plus" data-target="children">+</button>
                                        </div>
                                    </div>

                                    <!-- Infants -->
                                    <div class="amadex-traveller-row">
                                        <div class="amadex-traveller-label">
                                            <div class="amadex-traveller-type">Infants</div>
                                            <div class="amadex-traveller-age">Below 2 years</div>
                                        </div>
                                        <div class="amadex-traveller-counter">
                                            <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                                data-action="minus" data-target="infants">−</button>
                                            <span class="amadex-counter-value" id="infants-count">0</span>
                                            <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                                data-action="plus" data-target="infants">+</button>
                                        </div>
                                    </div>

                                    <!-- Cabin Class -->
                                    <div class="amadex-cabin-selector">
                                        <div class="amadex-cabin-label">Cabin</div>
                                        <div class="amadex-cabin-options">
                                            <button type="button" class="amadex-cabin-btn active"
                                                data-cabin="ECONOMY">Economy</button>
                                            <button type="button" class="amadex-cabin-btn"
                                                data-cabin="PREMIUM_ECONOMY">Premium / Economy</button>
                                            <button type="button" class="amadex-cabin-btn"
                                                data-cabin="BUSINESS">Business</button>
                                            <button type="button" class="amadex-cabin-btn" data-cabin="FIRST">First
                                                Class</button>
                                        </div>
                                    </div>

                                    <!-- Apply Button -->
                                    <button type="button" class="amadex-travellers-apply-btn"
                                        id="travellers-apply">Apply</button>
                                </div>

                                <!-- Hidden Inputs -->
                                <input type="hidden" id="modern-adults" value="1">
                                <input type="hidden" id="modern-children" value="0">
                                <input type="hidden" id="modern-infants" value="0">
                                <input type="hidden" id="modern-cabin" value="ECONOMY">
                            </div>

                        </div>
                        <!-- MULTI CITY -->
                        <div id="amadex-multicity-wrapper" style="display:none;"></div>
                        <button type="button" id="amadex-add-city" class="amadex-add-city-btn" style="display:none;">
                            + Add City
                        </button>
                        <!-- Search Button -->
                        <div class="submit-btn-amadex">
                            <button type="submit" class="<?php echo esc_attr($button_classes); ?>"
                                id="amadex-modify-search-btn">
                                <?php echo esc_html($atts['button_text']); ?>
                                <?php if (!empty($atts['show_icon'])) : ?>
                                    <span class="amadex-search-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="search-icon-svg" viewBox="0 0 24 24"
                                            fill="none">
                                            <path
                                                d="M21 21L16.65 16.65M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z"
                                                stroke="white" stroke-width="2.5" stroke-linecap="round"
                                                stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                <?php endif; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- New search bar visit end -->

        <div class="amadex-search-modern" style="display: none;">
            <div class="close_amadex_form"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18">
                    <g id="Group_179" data-name="Group 179" transform="translate(-340 -317)">
                        <g id="Ellipse_9" data-name="Ellipse 9" transform="translate(340 317)" fill="#fff" stroke="#707070"
                            stroke-width="1">
                            <circle cx="9" cy="9" r="9" stroke="none" />
                            <circle cx="9" cy="9" r="8.5" fill="none" />
                        </g>
                        <g id="Group_146" data-name="Group 146" transform="translate(344.979 321.979)">
                            <path id="Path_12" data-name="Path 12"
                                d="M4.881,4.261,8.093,1.048A.534.534,0,1,0,7.337.293L4.125,3.505.913.293a.534.534,0,0,0-.756.756L3.369,4.261.157,7.473a.534.534,0,1,0,.756.756L4.125,5.017,7.337,8.229a.534.534,0,0,0,.756-.756Zm0,0"
                                transform="translate(0 -0.136)" fill="#707070" />
                        </g>
                    </g>
                </svg></div>
            <form class="amadex-modern-form" id="<?php echo esc_attr($form_id); ?>"
                data-results="<?php echo esc_url($atts['results_page']); ?>">

                <!-- Trip Type Selector -->
                <div class="amadex-confirmation-nav">
                    <div class="amadex-trip-selector">
                        <div class="amadex-trip-option">
                            <input type="radio" id="<?php echo esc_attr($trip_round_id); ?>" name="tripType" value="round"
                                checked>
                            <label for="<?php echo esc_attr($trip_round_id); ?>" class="amadex-trip-label">Round Trip</label>
                        </div>
                        <div class="amadex-trip-option">
                            <input type="radio" id="<?php echo esc_attr($trip_oneway_id); ?>" name="tripType" value="oneway">
                            <label for="<?php echo esc_attr($trip_oneway_id); ?>" class="amadex-trip-label">One way</label>
                        </div>
                        <div class="amadex-trip-option">
                            <input type="radio" id="<?php echo esc_attr($trip_multi_id); ?>" name="tripType" value="multi-city">
                            <label for="<?php echo esc_attr($trip_multi_id); ?>" class="amadex-trip-label">Multi-City</label>
                        </div>
                    </div>
                </div>

                <!-- Search Fields Container -->
                <div class="amadex-search-container">

                    <!-- Multi-City Flights Container -->
                    <div class="amadex-multi-city-flights" id="multi-city-flights">

                        <!-- Flight Segment 1 (Default) -->
                        <div class="amadex-flight-segment" data-segment="1">

                            <div class="amadex-search-fields">

                                <!-- Origin Field -->
                                <div class="amadex-modern-field amadex-location-field test" id="origin-field">
                                    <!--<label class="amadex-field-label">Origin</label>-->
                                    <div class="amadex-field-input-wrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24.001" height="10.885"
                                            viewBox="0 0 24.001 10.885">
                                            <g id="Group_51" data-name="Group 51" transform="translate(-5.002 -18.663)">
                                                <path id="Path_8" data-name="Path 8"
                                                    d="M7.012,26.663a2.111,2.111,0,0,0,1.709.869,2.214,2.214,0,0,0,.5-.058c1.68-.408,4.81-1.186,7.843-2.026l-1.454,3.432a.472.472,0,0,0,.038.451.486.486,0,0,0,.4.216,4.05,4.05,0,0,0,3.12-1.464,17.671,17.671,0,0,0,2.707-4.071c.307-.106.6-.206.874-.3a25.818,25.818,0,0,0,5.707-2.486,1.349,1.349,0,0,0,.494-1.445,1.329,1.329,0,0,0-1.171-.965L26.2,18.676a3.709,3.709,0,0,0-1.68.25L18.547,21.3a36.119,36.119,0,0,0-5.832-1.013,2.935,2.935,0,0,0-2.448.888.47.47,0,0,0-.125.442.494.494,0,0,0,.307.346l3.427,1.2-3.9,1.55L7.041,22.9a.46.46,0,0,0-.4-.043l-1.31.442a.481.481,0,0,0-.23.739Z"
                                                    transform="translate(0 0)" fill="#666" />
                                            </g>
                                        </svg>
                                        <input type="text" class="amadex-field-value" id="modern-origin"
                                            placeholder="Departure City" autocomplete="off">
                                        <!--<div class="amadex-field-description" id="origin-description">Sacramento International...</div>-->
                                        <input type="hidden" id="modern-origin-code">
                                    </div>
                                    <div class="amadex-suggestions-dropdown" id="origin-suggestions"></div>
                                </div>

                                <!-- Swap Button -->
                                <button type="button" class="amadex-swap-button" id="swap-locations"
                                    aria-label="Swap locations">
                                    <svg id="swap-locations" xmlns="http://www.w3.org/2000/svg"
                                        xmlns:xlink="http://www.w3.org/1999/xlink" width="48" height="48" viewBox="0 0 48 48">
                                        <defs>
                                            <filter id="Ellipse_1" x="0" y="0" width="32" height="32"
                                                filterUnits="userSpaceOnUse">
                                                <feOffset input="SourceAlpha" />
                                                <feGaussianBlur stdDeviation="3" result="blur" />
                                                <feFlood flood-opacity="0.161" />
                                                <feComposite operator="in" in2="blur" />
                                                <feComposite in="SourceGraphic" />
                                            </filter>
                                        </defs>
                                        <g id="Group_43" data-name="Group 43" transform="translate(-651 -632)">
                                            <g transform="matrix(1, 0, 0, 1, 651, 632)" filter="url(#Ellipse_1)">
                                                <circle id="Ellipse_1-2" data-name="Ellipse 1" cx="15" cy="15" r="15"
                                                    transform="translate(9 9)" fill="#0e7d3f" />
                                            </g>
                                            <g id="Group_2" data-name="Group 2" transform="translate(666 646.004)">
                                                <path id="Path_1" data-name="Path 1"
                                                    d="M4.841,15.754a.768.768,0,0,1-.545-.223L1.223,12.459a.773.773,0,0,1,.545-1.314H17.133a.768.768,0,0,1,0,1.536H3.62l1.767,1.759a.768.768,0,0,1-.545,1.314ZM17.133,9.609H1.768a.768.768,0,1,1,0-1.536H15.282L13.515,6.313a.771.771,0,1,1,1.091-1.091l3.073,3.073a.773.773,0,0,1-.545,1.314Z"
                                                    transform="translate(0 0)" fill="#fff" />
                                            </g>
                                        </g>
                                    </svg>
                                </button>

                                <!-- Destination Field -->
                                <div class="amadex-modern-field amadex-location-field" id="destination-field">
                                    <!--<label class="amadex-field-label">Destination</label>-->
                                    <div class="amadex-field-input-wrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24.001" height="10.885"
                                            viewBox="0 0 24.001 10.885">
                                            <g id="Group_51" data-name="Group 51" transform="translate(-5.002 -18.663)">
                                                <path id="Path_8" data-name="Path 8"
                                                    d="M7.012,26.663a2.111,2.111,0,0,0,1.709.869,2.214,2.214,0,0,0,.5-.058c1.68-.408,4.81-1.186,7.843-2.026l-1.454,3.432a.472.472,0,0,0,.038.451.486.486,0,0,0,.4.216,4.05,4.05,0,0,0,3.12-1.464,17.671,17.671,0,0,0,2.707-4.071c.307-.106.6-.206.874-.3a25.818,25.818,0,0,0,5.707-2.486,1.349,1.349,0,0,0,.494-1.445,1.329,1.329,0,0,0-1.171-.965L26.2,18.676a3.709,3.709,0,0,0-1.68.25L18.547,21.3a36.119,36.119,0,0,0-5.832-1.013,2.935,2.935,0,0,0-2.448.888.47.47,0,0,0-.125.442.494.494,0,0,0,.307.346l3.427,1.2-3.9,1.55L7.041,22.9a.46.46,0,0,0-.4-.043l-1.31.442a.481.481,0,0,0-.23.739Z"
                                                    transform="translate(0 0)" fill="#666" />
                                            </g>
                                        </svg>
                                        <input type="text" class="amadex-field-value" id="modern-destination"
                                            placeholder="Arrival City" autocomplete="off">
                                        <!--<div class="amadex-field-description" id="destination-description">Ronald Reagan Washington...</div>-->
                                        <input type="hidden" id="modern-destination-code">
                                    </div>
                                    <div class="amadex-suggestions-dropdown" id="destination-suggestions"></div>
                                </div>
                                <div class="amadex-modern-field amadex-date-field" id="dateID">
                                    <!--<label class="amadex-field-label">Departure Date</label>-->
                                    <div class="amadex-field-input-wrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17.143" height="18"
                                            viewBox="0 0 17.143 18">
                                            <g id="Group_53" data-name="Group 53" transform="translate(-4 -3)">
                                                <rect id="Rectangle_36" data-name="Rectangle 36" width="2" height="3" rx="1"
                                                    transform="translate(6.571 3)" fill="#666" />
                                                <rect id="Rectangle_37" data-name="Rectangle 37" width="2" height="3" rx="1"
                                                    transform="translate(16.571 3)" fill="#666" />
                                                <path id="Path_9" data-name="Path 9"
                                                    d="M4,11.143V21a1.714,1.714,0,0,0,1.714,1.714H19.429A1.714,1.714,0,0,0,21.143,21V11.143Zm5.143,8.571a.857.857,0,0,1-.857.857H7.429a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,7.429,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857H7.429A.857.857,0,0,1,6.571,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm4.714,4.714a.857.857,0,0,1-.857.857h-.857a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,12.143,18H13a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,11.286,15v-.857a.857.857,0,0,1,.857-.857H13a.857.857,0,0,1,.857.857Zm4.714,4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,19.714v-.857A.857.857,0,0,1,16.857,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm2.571-4.714V7.714A1.714,1.714,0,0,0,19.429,6H19v.429a1.714,1.714,0,0,1-3.429,0V6h-6v.429a1.714,1.714,0,1,1-3.429,0V6H5.714A1.714,1.714,0,0,0,4,7.714v2.571Z"
                                                    transform="translate(0 -1.714)" fill="#666" />
                                            </g>
                                        </svg>
                                        <div class="amadex-field-value" id="departure-display">
                                            5 Nov, 25</div>
                                        <!--<div class="amadex-field-description" id="departure-day">Tuesday</div>-->
                                        <input type="date" id="modern-departure">
                                    </div>
                                </div>

                                <div class="amadex-modern-field amadex-date-field" id="return-field">
                                    <!--<label class="amadex-field-label">Return Date</label>-->
                                    <div class="amadex-field-input-wrap">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="17.143" height="18"
                                            viewBox="0 0 17.143 18">
                                            <g id="Group_53" data-name="Group 53" transform="translate(-4 -3)">
                                                <rect id="Rectangle_36" data-name="Rectangle 36" width="2" height="3" rx="1"
                                                    transform="translate(6.571 3)" fill="#666" />
                                                <rect id="Rectangle_37" data-name="Rectangle 37" width="2" height="3" rx="1"
                                                    transform="translate(16.571 3)" fill="#666" />
                                                <path id="Path_9" data-name="Path 9"
                                                    d="M4,11.143V21a1.714,1.714,0,0,0,1.714,1.714H19.429A1.714,1.714,0,0,0,21.143,21V11.143Zm5.143,8.571a.857.857,0,0,1-.857.857H7.429a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,7.429,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857H7.429A.857.857,0,0,1,6.571,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm4.714,4.714a.857.857,0,0,1-.857.857h-.857a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,12.143,18H13a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,11.286,15v-.857a.857.857,0,0,1,.857-.857H13a.857.857,0,0,1,.857.857Zm4.714,4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,19.714v-.857A.857.857,0,0,1,16.857,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm2.571-4.714V7.714A1.714,1.714,0,0,0,19.429,6H19v.429a1.714,1.714,0,0,1-3.429,0V6h-6v.429a1.714,1.714,0,1,1-3.429,0V6H5.714A1.714,1.714,0,0,0,4,7.714v2.571Z"
                                                    transform="translate(0 -1.714)" fill="#666" />
                                            </g>
                                        </svg>
                                        <div class="amadex-field-value" id="return-display"> 10 Nov, 25</div>
                                        <!--<div class="amadex-field-description" id="return-day">Monday</div>-->
                                        <input type="date" id="modern-return">
                                    </div>
                                </div>
                                <!-- Departure Date -->
                                <div class="main-calnder-mobile">
                                    <!--                    <div class="amadex-modern-field amadex-date-field">-->
                                    <!--<label class="amadex-field-label">Departure Date</label>-->
                                    <!--                        <div class="amadex-field-input-wrap">-->
                                    <!--                        <svg xmlns="http://www.w3.org/2000/svg" width="17.143" height="18" viewBox="0 0 17.143 18">-->
                                    <!--  <g id="Group_53" data-name="Group 53" transform="translate(-4 -3)">-->
                                    <!--    <rect id="Rectangle_36" data-name="Rectangle 36" width="2" height="3" rx="1" transform="translate(6.571 3)" fill="#666"/>-->
                                    <!--    <rect id="Rectangle_37" data-name="Rectangle 37" width="2" height="3" rx="1" transform="translate(16.571 3)" fill="#666"/>-->
                                    <!--    <path id="Path_9" data-name="Path 9" d="M4,11.143V21a1.714,1.714,0,0,0,1.714,1.714H19.429A1.714,1.714,0,0,0,21.143,21V11.143Zm5.143,8.571a.857.857,0,0,1-.857.857H7.429a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,7.429,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857H7.429A.857.857,0,0,1,6.571,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm4.714,4.714a.857.857,0,0,1-.857.857h-.857a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,12.143,18H13a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,11.286,15v-.857a.857.857,0,0,1,.857-.857H13a.857.857,0,0,1,.857.857Zm4.714,4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,19.714v-.857A.857.857,0,0,1,16.857,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm2.571-4.714V7.714A1.714,1.714,0,0,0,19.429,6H19v.429a1.714,1.714,0,0,1-3.429,0V6h-6v.429a1.714,1.714,0,1,1-3.429,0V6H5.714A1.714,1.714,0,0,0,4,7.714v2.571Z" transform="translate(0 -1.714)" fill="#666"/>-->
                                    <!--  </g>-->
                                    <!--</svg>-->
                                    <!--                            <div class="amadex-field-value" id="departure-display"> -->
                                    <!--                               5 Nov, 25</div>-->
                                    <!--<div class="amadex-field-description" id="departure-day">Tuesday</div>-->
                                    <!--                            <input type="date" id="modern-departure">-->
                                    <!--                        </div>-->
                                    <!--                    </div>-->

                                    <!-- Return Date -->
                                    <!--                    <div class="amadex-modern-field amadex-date-field" id="return-field">-->
                                    <!--<label class="amadex-field-label">Return Date</label>-->
                                    <!--                        <div class="amadex-field-input-wrap">-->
                                    <!--                        <svg xmlns="http://www.w3.org/2000/svg" width="17.143" height="18" viewBox="0 0 17.143 18">-->
                                    <!--  <g id="Group_53" data-name="Group 53" transform="translate(-4 -3)">-->
                                    <!--    <rect id="Rectangle_36" data-name="Rectangle 36" width="2" height="3" rx="1" transform="translate(6.571 3)" fill="#666"/>-->
                                    <!--    <rect id="Rectangle_37" data-name="Rectangle 37" width="2" height="3" rx="1" transform="translate(16.571 3)" fill="#666"/>-->
                                    <!--    <path id="Path_9" data-name="Path 9" d="M4,11.143V21a1.714,1.714,0,0,0,1.714,1.714H19.429A1.714,1.714,0,0,0,21.143,21V11.143Zm5.143,8.571a.857.857,0,0,1-.857.857H7.429a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,7.429,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857H7.429A.857.857,0,0,1,6.571,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm4.714,4.714a.857.857,0,0,1-.857.857h-.857a.857.857,0,0,1-.857-.857v-.857A.857.857,0,0,1,12.143,18H13a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,11.286,15v-.857a.857.857,0,0,1,.857-.857H13a.857.857,0,0,1,.857.857Zm4.714,4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,19.714v-.857A.857.857,0,0,1,16.857,18h.857a.857.857,0,0,1,.857.857Zm0-4.714a.857.857,0,0,1-.857.857h-.857A.857.857,0,0,1,16,15v-.857a.857.857,0,0,1,.857-.857h.857a.857.857,0,0,1,.857.857Zm2.571-4.714V7.714A1.714,1.714,0,0,0,19.429,6H19v.429a1.714,1.714,0,0,1-3.429,0V6h-6v.429a1.714,1.714,0,1,1-3.429,0V6H5.714A1.714,1.714,0,0,0,4,7.714v2.571Z" transform="translate(0 -1.714)" fill="#666"/>-->
                                    <!--  </g>-->
                                    <!--</svg>-->
                                    <!--<div class="amadex-field-value" id="return-display"> 10 Nov, 25</div>-->
                                    <!--<div class="amadex-field-description" id="return-day">Monday</div>-->
                                    <!--                            <input type="date" id="modern-return">-->
                                    <!--                        </div>-->
                                    <!--                    </div>-->

                                </div>

                                <!-- Travellers & Cabin -->
                                <div class="amadex-modern-field amadex-travellers-field" id="travellers-field">
                                    <!--<label class="amadex-field-label">Travelers & Cabin</label>-->
                                    <div class="amadex-travellers-trigger">
                                        <div class="amadex-travellers-value" id="travellers-display">
                                            <!--                            <svg xmlns="http://www.w3.org/2000/svg" width="21.983" height="18.5" viewBox="0 0 21.983 18.5">-->
                                            <!--  <g id="Group_55" data-name="Group 55" transform="translate(0 -6.348)">-->
                                            <!--    <path id="Path_10" data-name="Path 10" d="M13.266,9.523a4,4,0,0,1,1.859,2.968,3.223,3.223,0,1,0-1.859-2.968Zm-2.112,6.6A3.224,3.224,0,1,0,7.929,12.9,3.224,3.224,0,0,0,11.154,16.125Zm1.368.22H9.786a4.133,4.133,0,0,0-4.128,4.128v3.346l.009.052.23.072a18.791,18.791,0,0,0,5.613.905,11.523,11.523,0,0,0,4.9-.92l.215-.109h.023V20.473a4.132,4.132,0,0,0-4.127-4.128Zm5.334-3.328H15.141a3.973,3.973,0,0,1-1.226,2.768,4.905,4.905,0,0,1,3.5,4.694v1.031a11.109,11.109,0,0,0,4.327-.909l.215-.109h.023V17.144a4.133,4.133,0,0,0-4.128-4.128ZM5.5,12.8a3.2,3.2,0,0,0,1.715-.5,3.99,3.99,0,0,1,1.5-2.545c0-.06.009-.12.009-.181A3.224,3.224,0,1,0,5.5,12.8Zm2.9,2.987a3.976,3.976,0,0,1-1.226-2.752c-.1-.007-.2-.015-.3-.015H4.128A4.133,4.133,0,0,0,0,17.144V20.49l.009.052.23.073a19.355,19.355,0,0,0,4.649.874v-1.01A4.906,4.906,0,0,1,8.392,15.784Z" fill="#666"/>-->
                                            <!--  </g>-->
                                            <!--</svg>-->
                                            <span> 1 Traveler </span> <i class="fa-solid fa-angle-down"></i>
                                        </div>
                                        <!--<div class="amadex-cabin-value" id="cabin-display"></div>-->
                                    </div>

                                    <!-- Travellers Dropdown -->
                                    <div class="amadex-travellers-dropdown" id="travellers-dropdown">
                                        <!-- Adults -->
                                        <div class="amadex-traveller-row">
                                            <div class="amadex-traveller-label">
                                                <div class="amadex-traveller-type">Adults</div>
                                                <div class="amadex-traveller-age">Above 12 years</div>
                                            </div>
                                            <div class="amadex-traveller-counter">
                                                <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                                    data-action="minus" data-target="adults">−</button>
                                                <span class="amadex-counter-value" id="adults-count">1</span>
                                                <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                                    data-action="plus" data-target="adults">+</button>
                                            </div>
                                        </div>

                                        <!-- Children -->
                                        <div class="amadex-traveller-row">
                                            <div class="amadex-traveller-label">
                                                <div class="amadex-traveller-type">Children</div>
                                                <div class="amadex-traveller-age">2 - 12 years</div>
                                            </div>
                                            <div class="amadex-traveller-counter">
                                                <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                                    data-action="minus" data-target="children">−</button>
                                                <span class="amadex-counter-value" id="children-count">0</span>
                                                <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                                    data-action="plus" data-target="children">+</button>
                                            </div>
                                        </div>

                                        <!-- Infants -->
                                        <div class="amadex-traveller-row">
                                            <div class="amadex-traveller-label">
                                                <div class="amadex-traveller-type">Infants</div>
                                                <div class="amadex-traveller-age">Below 2 years</div>
                                            </div>
                                            <div class="amadex-traveller-counter">
                                                <button type="button" class="amadex-counter-btn amadex-counter-minus"
                                                    data-action="minus" data-target="infants">−</button>
                                                <span class="amadex-counter-value" id="infants-count">0</span>
                                                <button type="button" class="amadex-counter-btn amadex-counter-plus"
                                                    data-action="plus" data-target="infants">+</button>
                                            </div>
                                        </div>

                                        <!-- Cabin Class -->
                                        <div class="amadex-cabin-selector">
                                            <div class="amadex-cabin-label">Cabin</div>
                                            <div class="amadex-cabin-options">
                                                <button type="button" class="amadex-cabin-btn active"
                                                    data-cabin="ECONOMY">Economy</button>
                                                <button type="button" class="amadex-cabin-btn"
                                                    data-cabin="PREMIUM_ECONOMY">Premium / Economy</button>
                                                <button type="button" class="amadex-cabin-btn"
                                                    data-cabin="BUSINESS">Business</button>
                                                <button type="button" class="amadex-cabin-btn" data-cabin="FIRST">First
                                                    Class</button>
                                            </div>
                                        </div>

                                        <!-- Apply Button -->
                                        <button type="button" class="amadex-travellers-apply-btn"
                                            id="travellers-apply">Apply</button>
                                    </div>

                                    <!-- Hidden Inputs -->
                                    <input type="hidden" id="modern-adults" value="1">
                                    <input type="hidden" id="modern-children" value="0">
                                    <input type="hidden" id="modern-infants" value="0">
                                    <input type="hidden" id="modern-cabin" value="ECONOMY">
                                </div>

                                <!-- Search Button -->
                                <div class="submit-btn-amadex">
                                    <button type="submit" class="<?php echo esc_attr($button_classes); ?>"
                                        id="amadex-modify-search-btn">
                                        <?php echo esc_html($atts['button_text']); ?>
                                        <?php if (!empty($atts['show_icon'])) : ?>
                                            <span class="amadex-search-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="search-icon-svg" viewBox="0 0 24 24"
                                                    fill="none">
                                                    <path
                                                        d="M21 21L16.65 16.65M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z"
                                                        stroke="white" stroke-width="2.5" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </div>


                        </div>

                        <!-- End Flight Segment 1 -->

                    </div>
                    <button type="button" class="amadex-add-city-btn" id="add-city-btn" style="display:none;">
                        Add City
                        <span class="amadex-add-city-icon">+</span>
                    </button>

                </div>
            </form>


            <!-- Sticky Call Now Banner - DISABLED: Now using Gutenberg block -->
            <?php // echo $this->render_call_now_banner(); 
            ?>
        </div>
    <?php
        return ob_get_clean();
    }

    /**
     * Render API test form
     */
    public function render_api_test()
    {
        ob_start();
    ?>
        <div class="amadex-api-test">
            <h3>Amadex API Test</h3>
            <div id="api-test-results"></div>
            <button id="test-api-btn" class="amadex-button">Test API Connection</button>
        </div>

        <script>
            jQuery(document).ready(function($) {
                $('#test-api-btn').on('click', function() {
                    $('#api-test-results').html('<p>Testing API connection...</p>');

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'amadex_test_api',
                            nonce: '<?php echo wp_create_nonce('amadex_nonce'); ?>'
                        },
                        success: function(response) {
                            console.log('API Test Response:', response);
                            if (response.success) {
                                $('#api-test-results').html(
                                    '<div style="color: green; padding: 10px; border: 1px solid green; background: #f0fff0;">' +
                                    '<strong>Success:</strong> ' + response.data.message + '<br>' +
                                    '<strong>Token Length:</strong> ' + response.data.token_length +
                                    '<br>' +
                                    '<strong>API Key Set:</strong> ' + (response.data.details
                                        .api_key_set ? 'Yes' : 'No') + '<br>' +
                                    '<strong>API Secret Set:</strong> ' + (response.data.details
                                        .api_secret_set ? 'Yes' : 'No') + '<br>' +
                                    '<strong>Base URL:</strong> ' + response.data.details
                                    .api_base_url +
                                    '</div>'
                                );
                            } else {
                                $('#api-test-results').html(
                                    '<div style="color: red; padding: 10px; border: 1px solid red; background: #fff0f0;">' +
                                    '<strong>Error:</strong> ' + response.data.message + '<br>' +
                                    '<strong>API Key Set:</strong> ' + (response.data.details
                                        .api_key_set ? 'Yes' : 'No') + '<br>' +
                                    '<strong>API Secret Set:</strong> ' + (response.data.details
                                        .api_secret_set ? 'Yes' : 'No') + '<br>' +
                                    '<strong>Base URL:</strong> ' + response.data.details
                                    .api_base_url +
                                    '</div>'
                                );
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('API Test Error:', xhr.responseText);
                            $('#api-test-results').html(
                                '<div style="color: red; padding: 10px; border: 1px solid red; background: #fff0f0;">' +
                                '<strong>AJAX Error:</strong> ' + xhr.responseText +
                                '</div>'
                            );
                        }
                    });
                });
            });
        </script>
    <?php
        return ob_get_clean();
    }

    /**
     * Render booking page
     */
    // public function render_booking_page()
    // {

    public function render_booking_page()
    {
        if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin()) return '';

        // Enqueue Google Places API for address autocomplete (only on booking page)

        // Enqueue Google Places API for address autocomplete (only on booking page)
        $general_settings = get_option('amadex_general_settings', array());
        $google_api_key = isset($general_settings['google_places_api_key']) ? trim($general_settings['google_places_api_key']) : '';

        if (!empty($google_api_key)) {
            // Enqueue Google Places API script for address autocomplete
            wp_enqueue_script('google-places-api', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_api_key) . '&libraries=places', array(), null, true);
            // Note: The JavaScript code in amadex-booking.js already handles initialization with retry logic
        }

        // Enqueue fraud detection script (required for device fingerprinting)
        wp_enqueue_script('amadex-fraud-detection', AMADEX_URL . 'assets/js/amadex-fraud-detection.js', array('jquery'), AMADEX_VERSION, true);

        // Get payment settings
        $payment_settings = get_option('amadex_payment_settings', array());
        $enable_credit_card = isset($payment_settings['enable_credit_card']) ? $payment_settings['enable_credit_card'] : 1;
        $enable_crypto_transfer = isset($payment_settings['enable_crypto_transfer']) ? $payment_settings['enable_crypto_transfer'] : 1;
        $enable_crypto_com = isset($payment_settings['enable_crypto_com']) ? $payment_settings['enable_crypto_com'] : 1;
        $enable_moonpay_commerce = isset($payment_settings['enable_moonpay_commerce']) ? $payment_settings['enable_moonpay_commerce'] : 0;
        $enable_moonpay_onramp = isset($payment_settings['enable_moonpay_onramp']) ? $payment_settings['enable_moonpay_onramp'] : 0;
        $enable_paypal = isset($payment_settings['enable_paypal']) ? $payment_settings['enable_paypal'] : 1;
        $default_card_gateway = isset($payment_settings['default_card_gateway']) ? strtolower(trim(sanitize_text_field($payment_settings['default_card_gateway']))) : 'nmi';
        if (!in_array($default_card_gateway, array('nmi', 'stripe'))) {
            $default_card_gateway = 'nmi';
        }

        ob_start();
    ?>
        <div id="amadex-booking-page" class="amadex-booking-page">
            <!-- Header with Back Button -->
            <div class="amadex-booking-header">
                <a href="#" class="amadex-back-link" id="amadex-back-to-results">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17.573" height="13.75" viewBox="0 0 17.573 13.75">
                        <g id="Group_3634" data-name="Group 3634" transform="translate(0)">
                            <path id="Path_185" data-name="Path 185"
                                d="M.325,138.09h0l5.794-5.766a1.109,1.109,0,0,1,1.564,1.572l-3.89,3.871H16.464a1.109,1.109,0,0,1,0,2.218H3.795l3.89,3.871a1.109,1.109,0,0,1-1.564,1.572L.326,139.661h0A1.11,1.11,0,0,1,.325,138.09Z"
                                transform="translate(0 -132)" fill="#707070" />
                        </g>
                    </svg>
                    <span>Back to search results</span></a>
                <div class="amadex-booking-progress" data-booking-stage="flights">
                    <div class="booking-step is-active" data-step="flights">
                        <span class="booking-step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18.668" height="14" viewBox="0 0 18.668 14">
                                <g id="Group_194" data-name="Group 194" transform="translate(0 -51.208)">
                                    <path id="Path_19" data-name="Path 19"
                                        d="M18.653,53.8c-.149-.846-1.414-1.331-2.823-1.082L5.745,54.5l-3.739-3.29-1.915.337L1.4,55.263,0,55.51l.135.766A7.942,7.942,0,0,0,6.092,57.6l1.342-.237-.685,3.28,2.3-.406,4.515-3.955,2.809-.5c1.409-.249,2.432-1.137,2.283-1.983ZM0,63.652H18.668v1.556H0Z"
                                        fill="#0e7d3f" />
                                </g>
                            </svg>
                        </span>
                        <span class="booking-step-label">Check your flights</span>
                    </div>
                    <span class="booking-step-divider"></span>
                    <div class="booking-step" data-step="passengers">
                        <span class="booking-step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" id="Group_195" data-name="Group 195" width="18.661"
                                height="18.24" viewBox="0 0 18.661 18.24">
                                <path id="Path_21" data-name="Path 21"
                                    d="M60.054,64.189h-.74a2.267,2.267,0,0,1-2.265-2.265V58.7a2.267,2.267,0,0,1,2.265-2.265h.74A2.267,2.267,0,0,1,62.318,58.7v3.22A2.267,2.267,0,0,1,60.054,64.189ZM61.509,58.7a1.457,1.457,0,0,0-1.456-1.456h-.74A1.457,1.457,0,0,0,57.858,58.7v3.22a1.457,1.457,0,0,0,1.456,1.456h.74a1.457,1.457,0,0,0,1.456-1.456Zm-7.488,2.772a.723.723,0,0,1,.4.125.759.759,0,0,1,.32.494l.609,3.239a.3.3,0,0,0,.26.245l3.407.437a.761.761,0,0,1,.644.856.676.676,0,0,1-.567.65l-2.623-.255-.868-.091h-.045a.535.535,0,0,1-.067,0l-.186-.018a1.667,1.667,0,0,1-1.4-1.48l-.51-2.717-.109-.594a.762.762,0,0,1,.591-.871A.725.725,0,0,1,54.021,61.477Z"
                                    transform="translate(-49.865 -56.44)" fill="#707070" />
                                <path id="Path_22" data-name="Path 22"
                                    d="M55.121,110.177a1.7,1.7,0,0,1-1.515-1.384l-1.6-8.717a1.132,1.132,0,0,1,.91-1.317,1.173,1.173,0,0,1,.207-.019,1.133,1.133,0,0,1,1.111.929l.576,3.142c0,.019,0,.037.008.057l.109.567.548,3a2.069,2.069,0,0,0,1.828,1.679l.081.008c.057.008.114.013.172.017l.993.1,2.438.255a1.315,1.315,0,0,0,.162.01c.021,0,.042,0,.063-.005l1.092.106a1.133,1.133,0,1,1-.22,2.254l-2.064-.2v3.582h3.8l-.352-3.318a1.741,1.741,0,0,0-1.1-2.922l-.216-.021a1.488,1.488,0,0,0,.244-.667c0-.032.005-.064.007-.1.163.018.362.046.59.079a2.137,2.137,0,0,1,1.865,1.987l.32,4.957h5.083a.4.4,0,0,1,0,.809h-17.3a.4.4,0,0,1,0-.809h2.872v-3.99Z"
                                    transform="translate(-51.988 -96.807)" fill="#707070" />
                                <ellipse id="Ellipse_19" data-name="Ellipse 19" cx="1.49" cy="1.49" rx="1.49" ry="1.49"
                                    transform="translate(5.875 4.507) rotate(-176.83)" fill="#707070" />
                            </svg>
                        </span>
                        <span class="booking-step-label">Fill passenger details</span>
                    </div>
                    <span class="booking-step-divider"></span>
                    <div class="booking-step amadex-step-locked" data-step="seats">
                        <span class="booking-step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="5" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="9" y1="5" x2="9" y2="23"></line>
                                <line x1="15" y1="5" x2="15" y2="23"></line>
                                <line x1="3" y1="12" x2="21" y2="12"></line>
                                <line x1="3" y1="17" x2="21" y2="17"></line>
                            </svg>
                        </span>
                        <span class="booking-step-label">Select seats</span>
                    </div>
                    <span class="booking-step-divider"></span>
                    <div class="booking-step amadex-step-locked" data-step="addons">
                        <span class="booking-step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </span>
                        <span class="booking-step-label">Add-ons</span>
                    </div>
                    <span class="booking-step-divider"></span>
                    <div class="booking-step amadex-step-locked" data-step="review">
                        <span class="booking-step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20.001" height="14.376" viewBox="0 0 20.001 14.376">
                                <g id="Group_196" data-name="Group 196" transform="translate(0 -9)">
                                    <path id="Path_23" data-name="Path 23"
                                        d="M16.876,10.563A1.564,1.564,0,0,0,15.313,9H1.563A1.564,1.564,0,0,0,0,10.563v.313H16.876Zm-.313,5.313a2.584,2.584,0,0,1,.313.016V13.375H0v5.313a1.563,1.563,0,0,0,1.563,1.563H12.516a2.584,2.584,0,0,1-.016-.313,4.066,4.066,0,0,1,4.063-4.063ZM4.375,19H1.563a.313.313,0,1,1,0-.625H4.375a.313.313,0,1,1,0,.625Zm2.813-1.25H1.563a.313.313,0,0,1,0-.625H7.188a.313.313,0,0,1,0,.625ZM0,11.5H16.876v1.25H0Zm17.032,8.75h-.156v.938h.156a.469.469,0,1,0,0-.938Zm-1.406-1.094a.469.469,0,0,0,.469.469h.156v-.938h-.156a.469.469,0,0,0-.469.469Z"
                                        fill="#707070" />
                                    <path id="Path_24" data-name="Path 24"
                                        d="M45.438,33a3.438,3.438,0,1,0,3.438,3.438A3.438,3.438,0,0,0,45.438,33Zm.469,5.313H45.75v.313a.313.313,0,0,1-.625,0v-.313H44.5a.313.313,0,0,1,0-.625h.625V36.75h-.156a1.094,1.094,0,0,1,0-2.188h.156V34.25a.313.313,0,0,1,.625,0v.313h.625a.313.313,0,0,1,0,.625H45.75v.938h.156a1.094,1.094,0,0,1,0,2.188Z"
                                        transform="translate(-28.874 -16.5)" fill="#707070" />
                                </g>
                            </svg>
                        </span>
                        <span class="booking-step-label">Review & Pay</span>
                    </div>
                </div>
            </div>


            <div class="amadex-booking-content">
                <!-- Left Column - Forms -->
                <div class="amadex-booking-main">
                    <!-- Review your flight details - Step 1 -->
                    <div class="amadex-booking-section amadex-pagination-step active" id="amadex-section-flights"
                        data-section="flights" data-step="1">
                        <h3>Review your flight details</h3>
                        <div id="amadex-booking-itinerary" class="amadex-flight-details-list">
                            <!-- Will be populated by JavaScript with collapsible cards -->
                        </div>
                        <!-- Pagination Navigation -->
                        <div class="amadex-pagination-nav">
                            <button type="button" class="amadex-pagination-btn amadex-pagination-next"
                                data-next-step="2">Next</button>
                        </div>
                    </div>

                    <!-- Enter passenger details - Step 2 -->
                    <div class="amadex-booking-section amadex-pagination-step" id="amadex-section-passengers"
                        data-section="passengers" data-step="2">
                        <div class="enter-pass-details-header">
                            <div class="pass-details-title">
                                <h3>Enter passenger details</h3>
                                <p class="amadex-section-subtitle">Please enter your name as per your Passport</p>
                            </div>
                            <div class="amadex-top-add-passenger-wrapper">
                                <button type="button" id="amadex-top-add-passenger-btn" class="amadex-add-passenger-btn">
                                    Add Passenger
                                </button>
                                <div id="amadex-booking-travellers-popup" style="display:none;"></div>
                            </div>
                        </div>
                        <div id="amadex-passenger-forms">
                            <!-- Passenger forms will be populated by JavaScript -->
                        </div>
                        <!-- Pagination Navigation -->
                        <div class="amadex-pagination-nav">
                            <button type="button" class="amadex-pagination-btn amadex-pagination-back"
                                data-prev-step="1">Back</button>
                            <button type="button" class="amadex-pagination-btn amadex-pagination-next"
                                data-next-step="3">Next</button>
                        </div>
                    </div>

                    <!-- Seat Selection Section - Step 3 -->
                    <div class="amadex-booking-section amadex-pagination-step" id="amadex-seat-selection-section"
                        data-section="seats" data-step="3">
                        <h3><?php echo esc_html__('Select Your Seats', 'amadex'); ?></h3>
                        <p class="amadex-section-subtitle">
                            <?php echo esc_html__('Choose your preferred seats for this flight. You can skip this step if you don\'t have a preference.', 'amadex'); ?>
                        </p>
                        <!-- Loading State -->
                        <div id="amadex-seat-map-loading" class="amadex-seat-map-loading" style="display: none;">
                            <div class="amadex-loading-spinner"></div>
                            <p><?php echo esc_html__('Loading seat map...', 'amadex'); ?></p>
                        </div>

                        <!-- Not Available State -->
                        <div id="amadex-seat-map-unavailable" class="amadex-seat-map-unavailable" style="display: block;">
                            <div class="amadex-notice amadex-notice-info">
                                <p>
                                    <strong><?php echo esc_html__('Seat Selection Not Available', 'amadex'); ?></strong><br>
                                    <?php echo esc_html__('Seat selection is not available for this flight. You will be assigned seats at check-in.', 'amadex'); ?>
                                </p>
                            </div>
                        </div>
                        <!-- <div class="flight-booking-modify">
                            Selected Seats Summary
                            <div id="amadex-selected-seats-summary" class="amadex-selected-seats-summary" style="display: none;">
                                <h4><?php //echo esc_html__('Selected Seats', 'amadex'); 
                                    ?></h4>
                                <ul id="amadex-selected-seats-list"></ul>
                                <p id="amadex-seat-total-price" class="amadex-seat-total-price"></p>
                            </div>
                            <div id="amadex-seat-maps-container">
                            </div>
                        </div> -->

                        <div class="flight-booking-modify">
                            <div class="amadex-seat-split-layout">
                                <!-- LEFT: Passenger panel -->
                                <div class="amadex-seat-passenger-panel" id="amadex-seat-passenger-panel">
                                    <!-- Populated by JS -->
                                </div>
                                <!-- RIGHT: Seat map -->
                                <div class="amadex-seat-map-panel">
                                    <div id="amadex-seat-maps-container"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Seat selection badge (passenger + seat info popover, shown on available seat click) -->
                        <div id="amadex-seat-selection-badge" class="amadex-seat-selection-badge" style="display: none;"
                            aria-hidden="true">
                            <div class="amadex-seat-badge-passengers"></div>
                            <div class="amadex-seat-badge-features"></div>
                            <div class="amadex-seat-badge-footer">
                                <span class="amadex-seat-badge-seat-number"></span>
                                <span class="amadex-seat-badge-price"></span>
                            </div>
                        </div>

                        <!-- Selected Seats Summary
                            <div id="amadex-selected-seats-summary" class="amadex-selected-seats-summary" style="display: none;">
                               
                                <ul id="amadex-selected-seats-list"></ul>
                               
                            </div> -->

                        <!-- Actions -->
                        <div class="amadex-seat-selection-actions">
                            <button type="button" id="amadex-skip-seat-selection" class="amadex-btn amadex-btn-secondary">
                                <?php echo esc_html__('Skip Seat Selection', 'amadex'); ?>
                            </button>
                        </div>
                        <!-- Pagination Navigation -->
                        <div class="amadex-pagination-nav">
                            <button type="button" class="amadex-pagination-btn amadex-pagination-back"
                                data-prev-step="2">Back</button>
                            <button type="button" class="amadex-pagination-btn amadex-pagination-next"
                                data-next-step="4">Next</button>
                        </div>
                    </div>

                    <!-- Add-ons Section - Step 4 -->
                    <div class="amadex-booking-section amadex-pagination-step" id="amadex-addons-section" data-section="addons"
                        data-step="4">
                        <h3><?php echo esc_html__('Add-ons', 'amadex'); ?></h3>
                        <p class="amadex-section-subtitle">
                            <?php echo esc_html__('Enhance your travel experience with optional add-ons.', 'amadex'); ?>
                        </p>

                        <div id="amadex-addons-list" class="amadex-addons-list">
                            <!-- Add-ons will be populated by JavaScript -->
                            <div class="amadex-addons-empty" id="amadex-addons-empty" style="display: none;">
                                <p><?php echo esc_html__('No add-ons available at this time.', 'amadex'); ?></p>
                            </div>
                        </div>
                        <!-- Pagination Navigation -->
                        <div class="amadex-pagination-nav">
                            <button type="button" class="amadex-pagination-btn amadex-pagination-back"
                                data-prev-step="3">Back</button>
                            <button type="button" class="amadex-pagination-btn amadex-pagination-next"
                                data-next-step="5">Next</button>
                        </div>
                    </div>

                    <!-- Review & Summary Section - Step 5 -->
                    <div class="amadex-booking-section amadex-pagination-step" id="amadex-review-section" data-section="review"
                        data-step="5">
                        <h3><?php echo esc_html__('Review & Confirm', 'amadex'); ?></h3>
                        <p class="amadex-section-subtitle">
                            <?php echo esc_html__('Please review your booking details before confirming.', 'amadex'); ?>
                        </p>

                        <!-- Flight Itinerary removed - already shown in "Review your flight details" section above -->

                        <!-- Passenger Details Summary -->
                        <div class="amadex-review-section" id="amadex-review-passengers">
                            <h4><?php echo esc_html__('Passenger Details', 'amadex'); ?></h4>
                            <div id="amadex-review-passengers-content">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <p class="amadex-review-edit-link">
                                <a href="#" class="amadex-step-link"
                                    data-step="passengers"><?php echo esc_html__('Edit', 'amadex'); ?></a>
                            </p>
                        </div>

                        <!-- Selected Seats Summary -->
                        <div class="amadex-review-section" id="amadex-review-seats">
                            <h4><?php echo esc_html__('Selected Seats', 'amadex'); ?></h4>
                            <div id="amadex-review-seats-content">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <p class="amadex-review-edit-link">
                                <a href="#" class="amadex-step-link"
                                    data-step="seats"><?php echo esc_html__('Edit', 'amadex'); ?></a>
                            </p>
                        </div>

                        <!-- Add-ons Summary -->
                        <div class="amadex-review-section" id="amadex-review-addons">
                            <h4><?php echo esc_html__('Add-ons', 'amadex'); ?></h4>
                            <div id="amadex-review-addons-content">
                                <!-- Will be populated by JavaScript -->
                            </div>
                            <p class="amadex-review-edit-link">
                                <a href="#" class="amadex-step-link"
                                    data-step="addons"><?php echo esc_html__('Edit', 'amadex'); ?></a>
                            </p>
                        </div>

                        <!-- Full Price Breakdown (always visible on review) -->
                        <div class="amadex-review-section" id="amadex-review-price">
                            <h4><?php echo esc_html__('Price Breakdown', 'amadex'); ?></h4>
                            <div id="amadex-review-price-content" class="amadex-price-breakdown">
                                <!-- Will be populated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info (Part of Step 5) -->
                    <div class="amadex-booking-section amadex-step5-section" id="amadex-contact-section" data-section="review">
                        <h3>Contact Info</h3>
                        <p class="amadex-section-subtitle">Booking details will be sent to</p>
                        <div class="amadex-required-banner">Required Field</div>
                        <div class="amadex-contact-fields-row">
                            <div class="amadex-form-field">
                                <label>Country Code <span class="required">*</span></label>
                                <select id="contact-country-code" required data-default-country="US">
                                    <option value="">Loading country codes...</option>
                                </select>
                            </div>
                            <div class="amadex-form-field">
                                <label>Mobile Number <span class="required">*</span></label>
                                <input type="tel" id="contact-phone" placeholder="Mobile Number" required
                                    pattern="[0-9]{10,15}">
                                <small class="amadex-field-hint"
                                    style="display: block; margin-top: 4px; font-size: 12px; color: #666;">Enter digits only
                                    (10-15 digits). Country code selected above.</small>
                            </div>
                            <div class="amadex-form-field">
                                <label>Email <span class="required">*</span></label>
                                <input type="email" id="contact-email" placeholder="Email" required>
                                <small class="amadex-field-hint"
                                    style="display: block; margin-top: 4px; font-size: 12px; color: #666;">Please enter a valid
                                    email address.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Information (Part of Step 5) -->
                    <div class="amadex-booking-section amadex-step5-section" id="amadex-billing-section" data-section="review">
                        <h3>Billing Information</h3>
                        <div class="amadex-form-row">
                            <div class="amadex-form-field full-width">
                                <label>Country <span class="required">*</span></label>
                                <select id="billing-country" required data-default-country="US">
                                    <option value="">Loading countries...</option>
                                </select>
                            </div>
                        </div>

                        <div class="amadex-form-row">
                            <div class="amadex-form-field full-width">
                                <label>Address 1 <span class="required">*</span></label>
                                <input type="text" id="billing-address1" placeholder="Address Line 1" required>
                            </div>
                        </div>

                        <div class="amadex-form-row">
                            <div class="amadex-form-field full-width">
                                <label>State <span class="required">*</span></label>
                                <select id="billing-state" required data-placeholder="State / Province"
                                    data-loading-label="Loading State / Province..." data-empty-label="State / Province">
                                    <option value="">Select State / Province</option>
                                </select>
                            </div>
                        </div>



                        <div class="amadex-form-row">
                            <div class="amadex-form-field full-width">
                                <label>Address 2</label>
                                <input type="text" id="billing-address2" placeholder="Address Line 2">
                            </div>
                        </div>

                        <div class="amadex-form-row">
                            <div class="amadex-form-field">
                                <label>City <span class="required">*</span></label>
                                <input type="text" id="billing-city" placeholder="City" required>
                            </div>
                            <div class="amadex-form-field">
                                <label>Postal Code <span class="required">*</span></label>
                                <input type="text" id="billing-postal" placeholder="Postal/Zip Code" required>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method (Part of Step 5) -->
                    <div class="amadex-booking-section amadex-step5-section" id="amadex-payment-section" data-section="review"
                        data-default-gateway="<?php echo esc_attr($default_card_gateway); ?>">
                        <h3>Payment method</h3>

                        <p class="amadex-section-subtitle">Safe and Secure Payment</p>

                        <!-- Payment: One main capsule with sub-capsules inside -->
                        <div class="amadex-payment-container">
                            <div class="amadex-payment-label">Payment</div>
                            <?php if ($default_card_gateway === 'stripe') : ?>
                                <div class="amadex-stripe-payment-message" id="amadex-stripe-payment-message" role="status">
                                    <span class="amadex-stripe-message-icon" aria-hidden="true"><svg
                                            xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                        </svg></span>
                                    <span
                                        class="amadex-stripe-message-text"><?php esc_html_e("Enter your card details below. Check the agreement box and click Pay Securely to charge your card. Payment is processed securely by Stripe—no redirect.", 'amadex'); ?></span>
                                </div>
                            <?php endif; ?>
                            <!-- Payment accordion list: each item = tab + form (mobile: form under its tab) -->
                            <div class="amadex-payment-accordion-list amadex-payment-capsule" id="amadex-payment-methods-list">
                                <?php
                                $payment_logo_base = (defined('AMADEX_URL') ? AMADEX_URL : '');
                                $payment_logo_url = $payment_logo_base . 'assets/images/';
                                $chevron_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>';
                                ?>
                                <?php if (!empty($enable_credit_card)) : ?>
                                    <div class="amadex-payment-accordion-item" data-method="credit_card">
                                        <div class="amadex-payment-tab amadex-payment-sub is-active" data-method="credit_card"
                                            data-gateway-card>
                                            <div class="amadex-payment-tab-content">
                                                <span class="amadex-payment-tab-logo">
                                                    <img src="<?php echo esc_url($payment_logo_url . 'Card-Design.png'); ?>"
                                                        alt="Credit/Debit Card"
                                                        class="amadex-payment-logo-img amadex-payment-logo-desktop">
                                                    <img src="<?php echo esc_url($payment_logo_url . 'Mobile card.png'); ?>"
                                                        alt="Credit/Debit Card"
                                                        class="amadex-payment-logo-img amadex-payment-logo-mobile">
                                                </span>
                                                <span
                                                    class="amadex-payment-tab-label"><?php esc_html_e('Credit/Debit Card', 'amadex'); ?></span>
                                            </div>
                                            <span class="amadex-payment-tab-chevron"
                                                aria-hidden="true"><?php echo $chevron_svg; ?></span>
                                        </div>
                                        <?php if ($default_card_gateway === 'stripe') : ?>
                                            <div id="amadex-card-form-stripe"
                                                class="amadex-payment-form amadex-card-gateway-form amadex-payment-accordion-panel"
                                                data-gateway="stripe">
                                                <h3 class="amadex-stripe-card-heading"><?php esc_html_e('Card details', 'amadex'); ?>
                                                </h3>
                                                <div id="amadex-payment-error" class="amadex-payment-error" style="display: none;">
                                                </div>
                                                <div class="amadex-form-row">
                                                    <div class="amadex-form-field full-width">
                                                        <label><?php esc_html_e('Card Holder Name', 'amadex'); ?> <span
                                                                class="required">*</span></label>
                                                        <input type="text" id="card-name-stripe" class="amadex-stripe-card-name"
                                                            placeholder="<?php esc_attr_e('John Smith', 'amadex'); ?>"
                                                            autocomplete="cc-name">
                                                    </div>
                                                </div>
                                                <div id="amadex-stripe-card-element" class="amadex-stripe-card-element"></div>
                                                <div id="amadex-stripe-card-errors" class="amadex-stripe-card-errors" role="alert">
                                                </div>
                                                <input type="hidden" id="payment-method" name="payment_method" value="credit_card">
                                            </div>
                                        <?php endif; ?>
                                        <div class="amadex-payment-form-wrapper amadex-payment-accordion-panel"
                                            id="amadex-credit-card-form"
                                            <?php echo $default_card_gateway === 'stripe' ? 'style="display: none;"' : ''; ?>>
                                            <div class="amadex-payment-form amadex-card-form-compact">
                                                <div id="amadex-payment-error-nmi" class="amadex-payment-error"
                                                    style="display: none;"></div>
                                                <div class="amadex-card-fields-only">
                                                    <div class="amadex-form-row">
                                                        <div class="amadex-form-field full-width">
                                                            <label>Card Holder Name <span class="required">*</span></label>
                                                            <input type="text" id="card-name" placeholder="John Smith" required
                                                                autocomplete="cc-name">
                                                        </div>
                                                        <div class="amadex-form-field full-width">
                                                            <label>Credit/Debit Card Number <span class="required">*</span></label>
                                                            <input type="text" id="card-number" placeholder="0000 0000 0000 0000"
                                                                autocomplete="cc-number" maxlength="19"
                                                                style="width:100%;padding:12px 15px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                                                            <div id="amadex-card-number-field" class="amadex-collect-field" style="display:none;"></div>
                                                        </div>
                                                    </div>
                                                    <div class="amadex-form-row amadex-collect-inline">
                                                        <div class="amadex-form-field">
                                                            <label>Expiry Month <span class="required">*</span></label>
                                                            <input type="text" id="card-month" placeholder="MM"
                                                                autocomplete="cc-exp-month" maxlength="2"
                                                                style="width:100%;padding:12px 15px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                                                            <div id="amadex-card-exp-field" class="amadex-collect-field" style="display:none;"></div>
                                                        </div>
                                                        <div class="amadex-form-field">
                                                            <label>Expiry Year <span class="required">*</span></label>
                                                            <input type="text" id="card-year" placeholder="YY"
                                                                autocomplete="cc-exp-year" maxlength="2"
                                                                style="width:100%;padding:12px 15px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                                                        </div>
                                                        <div class="amadex-form-field">
                                                            <label>CVV <span class="required">*</span></label>
                                                            <input type="text" id="card-cvv" placeholder="***"
                                                                autocomplete="cc-csc" maxlength="4"
                                                                style="width:100%;padding:12px 15px;border:1px solid #ddd;border-radius:6px;font-size:14px;">
                                                            <div id="amadex-card-cvv-field" class="amadex-collect-field" style="display:none;"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" id="payment-token" name="payment_token" value="">
                                                <input type="hidden" id="payment-method-nmi" name="payment_method"
                                                    value="credit_card">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($enable_paypal)) : ?>
                                    <div class="amadex-payment-accordion-item" data-method="paypal">
                                        <div class="amadex-payment-tab amadex-payment-sub amadex-payment-tab-alt"
                                            data-method="paypal">
                                            <div class="amadex-payment-tab-content">
                                                <span class="amadex-payment-tab-logo">
                                                    <img src="<?php echo esc_url($payment_logo_url . 'paypal-icon.png'); ?>"
                                                        alt="PayPal" class="amadex-payment-logo-img">
                                                </span>
                                                <span
                                                    class="amadex-payment-tab-label"><?php esc_html_e('PayPal', 'amadex'); ?></span>
                                            </div>
                                            <span class="amadex-payment-tab-chevron"
                                                aria-hidden="true"><?php echo $chevron_svg; ?></span>
                                        </div>
                                        <div class="amadex-payment-form-wrapper amadex-payment-accordion-panel"
                                            id="amadex-paypal-form" style="display: none;">
                                            <div class="amadex-payment-form">
                                                <p>Pay with PayPal, Pay in 4, Venmo, or PayPal Credit.</p>
                                                <div id="paypal-paylater-message" class="amadex-paypal-paylater-message"
                                                    aria-live="polite"></div>
                                                <div id="paypal-button-container" class="amadex-paypal-button-container"></div>
                                                <input type="hidden" id="payment-method-paypal" name="payment_method"
                                                    value="paypal">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($enable_crypto_transfer)) : ?>
                                    <div class="amadex-payment-accordion-item" data-method="crypto_transfer">
                                        <div class="amadex-payment-tab amadex-payment-sub amadex-payment-tab-alt"
                                            data-method="crypto_transfer">
                                            <div class="amadex-payment-tab-content">
                                                <span class="amadex-payment-tab-logo">
                                                    <img src="<?php echo esc_url($payment_logo_url . 'pay-with-crypto.png'); ?>"
                                                        alt="Pay with Crypto" class="amadex-payment-logo-img">
                                                </span>
                                                <span
                                                    class="amadex-payment-tab-label"><?php esc_html_e('Pay with Crypto', 'amadex'); ?></span>
                                            </div>
                                            <span class="amadex-payment-tab-chevron"
                                                aria-hidden="true"><?php echo $chevron_svg; ?></span>
                                        </div>
                                        <div class="amadex-payment-form-wrapper amadex-payment-accordion-panel"
                                            id="amadex-crypto-transfer-form" style="display: none;">
                                            <div class="amadex-payment-form">
                                                <div class="amadex-form-row">
                                                    <div class="amadex-form-field">
                                                        <label>Select Cryptocurrency<span class="required">*</span></label>
                                                        <select id="crypto-currency-select" required>
                                                            <option value="">Select 60+ Cryptocurrencies (BTC, ETH, BNB, AVA...)
                                                            </option>
                                                            <option value="BTC">Bitcoin (BTC)</option>
                                                            <option value="ETH">Ethereum (ETH)</option>
                                                            <option value="BNB">Binance Coin (BNB)</option>
                                                            <option value="USDT">Tether (USDT)</option>
                                                            <option value="SOL">Solana (SOL)</option>
                                                            <option value="ADA">Cardano (ADA)</option>
                                                            <option value="XRP">Ripple (XRP)</option>
                                                            <option value="DOT">Polkadot (DOT)</option>
                                                            <option value="DOGE">Dogecoin (DOGE)</option>
                                                            <option value="AVAX">Avalanche (AVAX)</option>
                                                        </select>
                                                    </div>
                                                    <div class="amadex-form-field">
                                                        <label>Select Network<span class="required">*</span></label>
                                                        <select id="crypto-network-select" required>
                                                            <option value="">Select Network</option>
                                                            <option value="mainnet">Mainnet</option>
                                                            <option value="ethereum">Ethereum</option>
                                                            <option value="bsc">Binance Smart Chain</option>
                                                            <option value="polygon">Polygon</option>
                                                            <option value="avalanche">Avalanche</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <input type="hidden" id="payment-method-crypto" name="payment_method"
                                                    value="crypto_transfer">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($enable_crypto_com)) : ?>
                                    <div class="amadex-payment-accordion-item" data-method="crypto_com">
                                        <div class="amadex-payment-tab amadex-payment-sub amadex-payment-tab-alt"
                                            data-method="crypto_com">
                                            <div class="amadex-payment-tab-content">
                                                <span class="amadex-payment-tab-logo">
                                                    <img src="<?php echo esc_url($payment_logo_url . 'Bitcoin-Logo.png'); ?>"
                                                        alt="Crypto.com" class="amadex-payment-logo-img">
                                                </span>
                                                <span
                                                    class="amadex-payment-tab-label"><?php esc_html_e('Crypto.com', 'amadex'); ?></span>
                                            </div>
                                            <span class="amadex-payment-tab-chevron"
                                                aria-hidden="true"><?php echo $chevron_svg; ?></span>
                                        </div>
                                        <div class="amadex-payment-form-wrapper amadex-payment-accordion-panel"
                                            id="amadex-crypto-com-form" style="display: none;">
                                            <div class="amadex-payment-form amadex-crypto-com-compact">
                                                <p class="amadex-crypto-com-hint">Click <strong>Confirm &amp; Book</strong> below to
                                                    create your booking, then pay with Crypto.com.</p>
                                                <div id="cryptocom-pay-button-container"
                                                    class="amadex-cryptocom-pay-button-container" style="display: none;">
                                                    <div id="cryptocom-pay-button-mount"></div>
                                                </div>
                                                <input type="hidden" id="payment-method-crypto-com" name="payment_method"
                                                    value="crypto_com">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($enable_moonpay_onramp)) : ?>
                                    <div class="amadex-payment-accordion-item" data-method="moonpay_onramp">
                                        <div class="amadex-payment-tab amadex-payment-sub amadex-payment-tab-alt"
                                            data-method="moonpay_onramp">
                                            <div class="amadex-payment-tab-content">
                                                <span class="amadex-payment-tab-logo">
                                                    <img src="<?php echo esc_url($payment_logo_url . 'Moonpay Logo.png'); ?>"
                                                        alt="MoonPay" class="amadex-payment-logo-img">
                                                </span>
                                                <span
                                                    class="amadex-payment-tab-label"><?php esc_html_e('Pay with Card', 'amadex'); ?></span>
                                            </div>
                                            <span class="amadex-payment-tab-chevron"
                                                aria-hidden="true"><?php echo $chevron_svg; ?></span>
                                        </div>
                                        <div class="amadex-payment-form-wrapper amadex-payment-accordion-panel"
                                            id="amadex-moonpay-onramp-form" style="display: none;">
                                            <div class="amadex-payment-form amadex-crypto-com-compact">
                                                <p class="amadex-crypto-com-hint">Click <strong>Confirm &amp; Book</strong> below to
                                                    create your booking, then pay with your card in the MoonPay window. You buy
                                                    crypto that we receive as payment.</p>
                                                <input type="hidden" id="payment-method-moonpay-onramp" name="payment_method"
                                                    value="moonpay_onramp">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($enable_moonpay_commerce)) : ?>
                                    <div class="amadex-payment-accordion-item" data-method="moonpay">
                                        <div class="amadex-payment-tab amadex-payment-sub amadex-payment-tab-alt"
                                            data-method="moonpay">
                                            <div class="amadex-payment-tab-content">
                                                <span class="amadex-payment-tab-logo">
                                                    <img src="<?php echo esc_url($payment_logo_url . 'Moonpay Logo.png'); ?>"
                                                        alt="MoonPay" class="amadex-payment-logo-img">
                                                </span>
                                                <span
                                                    class="amadex-payment-tab-label"><?php esc_html_e('Card or Crypto', 'amadex'); ?></span>
                                            </div>
                                            <span class="amadex-payment-tab-chevron"
                                                aria-hidden="true"><?php echo $chevron_svg; ?></span>
                                        </div>
                                        <div class="amadex-payment-form-wrapper amadex-payment-accordion-panel"
                                            id="amadex-moonpay-form" style="display: none;">
                                            <div class="amadex-payment-form amadex-crypto-com-compact">
                                                <p class="amadex-crypto-com-hint">Click <strong>Confirm &amp; Book</strong> below to
                                                    create your booking, then pay with card or crypto via MoonPay Commerce.</p>
                                                <input type="hidden" id="payment-method-moonpay" name="payment_method"
                                                    value="moonpay">
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div><!-- .amadex-payment-container -->
                    </div>

                    <!-- Agreement and Confirmation (Part of Step 5) -->
                    <div class="amadex-booking-section amadex-step5-section" id="amadex-agreement-section"
                        data-section="review">
                        <!-- <?php if ($default_card_gateway === 'stripe') : ?>
                            <div class="amadex-stripe-payment-message" id="amadex-stripe-payment-message" role="status">
                                <span class="amadex-stripe-message-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                                <span class="amadex-stripe-message-text"><?php esc_html_e("Enter your card details below. Check the agreement box and click Pay Securely to charge your card. Payment is processed securely by Stripe—no redirect.", 'amadex'); ?></span>
                            </div>
                            <?php endif; ?> -->
                        <div class="amadex-form-row">
                            <div class="amadex-form-field full-width">
                                <label class="amadex-checkbox-label">
                                    <input type="checkbox" id="amadex-updates-consent" name="updates_consent">
                                    <span>By proceeding with this booking, I agree to Travelay's Terms of Use and Privacy
                                        Policy.</span>
                                </label>
                            </div>
                        </div>
                        <!-- <p class="amadex-legal-text">By proceeding with this booking, I agree to Travelay's Terms of Use and Privacy Policy.</p> -->
                        <!-- Stripe: Use #amadex-payment-submit for "Pay Securely" (in-page charge, no redirect). NMI: Show "Confirm & Book". -->
                        <?php if ($default_card_gateway === 'stripe') : ?>
                            <button type="button" id="amadex-payment-submit" class="amadex-payment-submit-btn amadex-stripe-cta"
                                data-gateway="stripe" disabled><?php esc_html_e('Pay Securely', 'amadex'); ?></button>
                            <button type="button" id="amadex-confirm-book" class="amadex-confirm-book-stripe-hidden"
                                style="display: none !important;" tabindex="-1" aria-hidden="true">Confirm & Book</button>
                        <?php else : ?>
                            <button type="button" id="amadex-confirm-book" class="amadex-confirm-book-btn"
                                data-gateway="nmi">Confirm & Book</button>
                            <button type="button" id="amadex-payment-submit" class="amadex-payment-submit-btn"
                                style="display: none;" tabindex="-1" aria-hidden="true">Continue To Payment</button>
                        <?php endif; ?>
                        <!-- Email Confirmation Message (Compact) -->
                        <div class="amadex-booking-action amadex-booking-action-compact">
                            <div class="amadex-form-row">
                                <div class="amadex-form-field full-width">
                                    <label class="amadex-checkbox-label amadex-email-confirmation">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18.911" height="18"
                                            viewBox="0 0 19.911 14">
                                            <g id="Group_3605" data-name="Group 3605" transform="translate(0 -76)">
                                                <path id="Path_370" data-name="Path 370"
                                                    d="M38.108,277.251l-1.668,1.673a1.846,1.846,0,0,1-2.557,0l-1.668-1.673-5.99,6.009a1.733,1.733,0,0,0,.73.165H43.367a1.731,1.731,0,0,0,.73-.165Z"
                                                    transform="translate(-25.206 -193.425)" fill="#0e7d3f" />
                                                <path id="Path_371" data-name="Path 371"
                                                    d="M18.161,76H1.75a1.732,1.732,0,0,0-.73.165l6.4,6.421h0L9.5,84.676a.7.7,0,0,0,.9,0l2.081-2.088h0l6.4-6.421A1.731,1.731,0,0,0,18.161,76ZM.186,76.98A1.729,1.729,0,0,0,0,77.75v10.5a1.728,1.728,0,0,0,.186.77l6-6.019Zm19.539,0-6,6.02,6,6.019a1.729,1.729,0,0,0,.186-.77V77.75A1.729,1.729,0,0,0,19.725,76.98Z"
                                                    fill="#0e7d3f" />
                                            </g>
                                        </svg>
                                        <span>We'll send confirmation of your booking to</span><span
                                            id="amadex-confirmation-email">your email</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination Navigation for Step 5 -->
                        <!-- <div class="amadex-pagination-nav">
                                <button type="button" class="amadex-pagination-btn amadex-pagination-back" data-prev-step="4">Back</button>
                                <button type="button" class="amadex-pagination-btn amadex-pagination-confirm" id="amadex-confirm-book-pagination">Confirm & Book</button>
                            </div> -->
                    </div>
                </div>

                <!-- Right Sidebar - Timer, Price Summary & Pay Later -->
                <div class="amadex-booking-sidebar">
                    <!-- Booking Timer Container - Top of Sidebar (Compact Two-Line Design) -->
                    <div class="amadex-timer-container">
                        <div class="amadex-timer-card" id="amadex-booking-timer-badge">
                            <!-- Line 1: Title and Timer -->
                            <div class="amadex-timer-line1">
                                <h3 class="amadex-timer-title">Book before you miss it!</h3>
                                <div class="amadex-timer-display-inline">
                                    <span class="timer-display">20:00</span>
                                    <span class="amadex-timer-icon" aria-label="Alarm clock">🔔</span>
                                </div>
                            </div>
                            <!-- Line 2: Subtitle -->
                            <p class="amadex-timer-subtitle">This price will expire after 20 minutes.</p>
                        </div>
                    </div>

                    <!-- Currency Selector - REMOVED: Use Regional Settings Modal instead -->

                    <!-- Price Summary -->
                    <div class="amadex-price-summary-card amadex-price-summary-sticky">
                        <div class="amadex-price-summary-header">
                            <h4>Price Summary</h4>
                        </div>
                        <div class="amadex-price-breakdown" id="amadex-price-breakdown"></div>
                        <!-- <div class="amadex-price-total">
                                <span>Total Amount</span>
                                <span class="amadex-price-total-value" id="amadex-price-total-value">$0</span>
                            </div> -->
                    </div>

                    <!-- Premium Services Card -->
                    <div class="amadex-premium-services-card">
                        <div class="amadex-premium-services-header">
                            <div class="amadex-premium-services-badge">OFFER</div>
                            <div class="amadex-premium-services-title-bar">
                                <span class="amadex-premium-services-title">$25 Premium Services</span>
                                <button type="button" class="amadex-premium-services-btn" id="amadex-premium-services-btn">
                                    <span class="btn-text">Add</span>
                                </button>
                            </div>
                        </div>
                        <div class="amadex-premium-services-content">
                            <div class="amadex-premium-services-logo">
                                <!-- <strong>TravelayGent™</strong>
                                    <span class="amadex-premium-services-tagline">ONE VOICE. EVERY MILE.</span> -->
                                <img src="<?php echo AMADEX_URL; ?>assets/images/sidebar-logo.png" alt="TravelayGent"
                                    class="amadex-premium-services-logo-img">

                            </div>
                            <ul class="amadex-premium-services-features">
                                <li>
                                    <strong>Get a Cost Estimate:</strong> Just request a personal agent and share your details
                                    like number of passengers, journey details or additional requirements for an estimate.
                                </li>
                                <li>
                                    <strong>Sit and Relax:</strong> With transparent pricing, instant booking confirmations and
                                    round-the-clock human support, TravelayGent™ makes travel effortless and stress free.
                                </li>
                                <li>
                                    <strong>Pricing Made Simple:</strong> We believe travel should be simple and so should the
                                    pricing. No hidden fees, no upfront costs- just easy and stress-free travel with your
                                    personal travel agent.
                                </li>
                            </ul>
                            <div class="amadex-premium-services-read-more">
                                <a href="https://www.flytravelay.com/travelaygent/" target="_blank" rel="noopener noreferrer"
                                    class="amadex-read-more-btn" id="amadex-premium-read-more-btn">
                                    <span class="read-more-text">Read More</span>
                                    <span class="read-more-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"
                                            fill="none">
                                            <circle cx="8" cy="8" r="7.5" stroke="#000" fill="#fff" />
                                            <path d="M6 4L10 8L6 12" stroke="#000" stroke-width="1.5" stroke-linecap="round"
                                                stroke-linejoin="round" fill="none" />
                                        </svg>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Compact WhatsApp Button - Bottom of Sidebar -->
                    <a href="https://wa.me/18885262920" class="amadex-whatsapp-compact-btn" target="_blank">
                        <span class="amadex-whatsapp-icon"><svg xmlns="http://www.w3.org/2000/svg" id="Group_3846"
                                data-name="Group 3846" width="24" height="24" viewBox="0 0 15 15">
                                <path id="Path_605" data-name="Path 605"
                                    d="M7.5,0h0a7.5,7.5,0,0,0-6.07,11.9L.493,14.682l2.883-.922A7.5,7.5,0,1,0,7.5,0Zm4.364,10.591a2.116,2.116,0,0,1-1.472,1.058c-.392.083-.9.15-2.627-.564a9.4,9.4,0,0,1-3.734-3.3,4.284,4.284,0,0,1-.891-2.262A2.394,2.394,0,0,1,3.909,3.7a1.09,1.09,0,0,1,.767-.269c.093,0,.176,0,.251.008.22.009.331.023.476.37.181.436.622,1.512.674,1.623a.446.446,0,0,1,.032.406,1.3,1.3,0,0,1-.243.344c-.111.128-.216.225-.326.362-.1.119-.216.247-.088.467A6.657,6.657,0,0,0,6.669,8.523,5.514,5.514,0,0,0,8.428,9.608a.474.474,0,0,0,.529-.083,9.074,9.074,0,0,0,.586-.776.419.419,0,0,1,.538-.163c.2.07,1.274.6,1.494.71s.366.163.419.256A1.868,1.868,0,0,1,11.866,10.591Z"
                                    fill="#fff" />
                            </svg></span>
                        <span>Chat on WhatsApp</span>
                    </a>
                </div>
            </div>
            <!-- </div> -->
        </div>
        <?php
        // Ensure Regional Settings modal template is always present in the DOM on the booking page
        // so JavaScript does not need to inject/fallback-load it.
        if (shortcode_exists('amadex_regional_settings')) {
            echo do_shortcode('[amadex_regional_settings mode="modal"]');
        }

        return ob_get_clean();
    }

    // public function render_booking_confirmation_page($atts = array())
    // {
    //     $reference = isset($_GET['reference']) ? sanitize_text_field(wp_unslash($_GET['reference'])) : '';

    public function render_booking_confirmation_page($atts = array())
    {
        if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin()) return '';
        $reference = isset($_GET['reference']) ? sanitize_text_field(wp_unslash($_GET['reference'])) : '';
        $database = new Amadex_Database();

        // ── Hotel booking detection ──────────────────────────────────────
        if ($reference && substr($reference, 0, 4) === 'AMDH') {
            global $wpdb;
            $hotel_lead = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}amadex_leads WHERE confirmation_number = %s AND booking_type = 'HOTEL' LIMIT 1",
                    $reference
                ),
                ARRAY_A
            );
            if ($hotel_lead) {
                return $this->render_hotel_confirmation($hotel_lead, $reference);
            }
        }
        // ────────────────────────────────────────────────────────────────

        $booking = $reference ? $database->get_booking_by_reference($reference) : null;

        // If booking is PENDING and we have a Crypto.com payment ID, verify payment and confirm (idempotent: skip if already confirmed)
        $already_crypto_confirmed = $booking && !empty($booking['payment']['payment_method']) && $booking['payment']['payment_method'] === 'CRYPTO_COM';
        if ($booking && (isset($booking['status']) && $booking['status'] === 'PENDING') && !$already_crypto_confirmed) {
            $cryptocom_payment_id = get_transient('amadex_cryptocom_payment_' . $reference);
            if ($cryptocom_payment_id) {
                $payment_settings = get_option('amadex_payment_settings', array());
                $secret_key = isset($payment_settings['crypto_com_secret_key']) ? trim($payment_settings['crypto_com_secret_key']) : '';
                if (!empty($secret_key)) {
                    try {
                        $verify = wp_remote_get(
                            'https://pay.crypto.com/api/payments/' . urlencode($cryptocom_payment_id),
                            array(
                                'timeout' => 15,
                                'headers' => array('Authorization' => 'Basic ' . base64_encode($secret_key . ':')),
                            )
                        );
                        if (!is_wp_error($verify)) {
                            $code = wp_remote_retrieve_response_code($verify);
                            $body = json_decode(wp_remote_retrieve_body($verify), true);
                            $status = isset($body['status']) ? strtolower($body['status']) : '';
                            if ($code === 200 && ($status === 'succeeded' || $status === 'captured')) {
                                $booking_id = isset($booking['id']) ? (int) $booking['id'] : 0;
                                if ($booking_id) {
                                    $database->update_booking_status($booking_id, 'CONFIRMED');
                                    $database->create_payment($booking_id, array(
                                        'transaction_id' => $cryptocom_payment_id,
                                        'payment_status' => 'CAPTURED',
                                        'payment_method' => 'CRYPTO_COM',
                                        'amount' => isset($booking['total_amount']) ? floatval($booking['total_amount']) : 0,
                                        'currency' => isset($booking['currency']) ? $booking['currency'] : 'USD',
                                    ));
                                    delete_transient('amadex_cryptocom_payment_' . $reference);
                                    $booking = $database->get_booking_by_reference($reference);
                                    do_action('amadex_booking_confirmed_cryptocom', $booking_id, $booking, $database);
                                }
                            }
                        }
                    } catch (Exception $e) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('Amadex Crypto.com confirmation verify error: ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        // If booking is PENDING and we have MoonPay transactionStatus=completed in URL, mark booking as paid (idempotent)
        $transaction_status = isset($_GET['transactionStatus']) ? sanitize_text_field(wp_unslash($_GET['transactionStatus'])) : '';
        $transaction_id = isset($_GET['transactionId']) ? sanitize_text_field(wp_unslash($_GET['transactionId'])) : '';
        $already_moonpay_confirmed = $booking && !empty($booking['payment']['payment_method']) && $booking['payment']['payment_method'] === 'MOONPAY_ONRAMP';
        if (
            $booking && (isset($booking['status']) && $booking['status'] === 'PENDING') && !$already_moonpay_confirmed
            && $transaction_status === 'completed' && !empty($transaction_id) && $reference
        ) {
            $ext_ref = isset($_GET['externalTransactionId']) ? sanitize_text_field(wp_unslash($_GET['externalTransactionId'])) : '';
            if (strtoupper($ext_ref) === strtoupper($reference) || $reference === $ext_ref || empty($ext_ref)) {
                $booking_id = isset($booking['id']) ? (int) $booking['id'] : 0;
                if ($booking_id) {
                    $database->update_booking_status($booking_id, 'CONFIRMED');
                    $base_amount = isset($_GET['baseCurrencyAmount']) ? floatval($_GET['baseCurrencyAmount']) : (isset($booking['total_amount']) ? floatval($booking['total_amount']) : 0);
                    $database->create_payment($booking_id, array(
                        'transaction_id' => $transaction_id,
                        'payment_status' => 'COMPLETED',
                        'payment_method' => 'MOONPAY_ONRAMP',
                        'amount' => $base_amount > 0 ? $base_amount : (isset($booking['total_amount']) ? floatval($booking['total_amount']) : 0),
                        'currency' => isset($_GET['baseCurrencyCode']) ? sanitize_text_field(wp_unslash($_GET['baseCurrencyCode'])) : (isset($booking['currency']) ? $booking['currency'] : 'USD'),
                    ));
                    $booking = $database->get_booking_by_reference($reference);
                    do_action('amadex_booking_confirmed_moonpay_onramp', $booking_id, $booking, $database);
                }
            }
        }

        // MoonPay Commerce (Card or Crypto): when booking is already CONFIRMED with MOONPAY/MOONPAY_COMMERCE (e.g. after webhook), send confirmation emails on page load
        $pm = $booking && !empty($booking['payment']['payment_method']) ? $booking['payment']['payment_method'] : '';
        if ($booking && (isset($booking['status']) && $booking['status'] === 'CONFIRMED') && ($pm === 'MOONPAY' || $pm === 'MOONPAY_COMMERCE')) {
            $booking_id = isset($booking['id']) ? (int) $booking['id'] : 0;
            if ($booking_id) {
                do_action('amadex_booking_confirmed_moonpay_commerce', $booking_id, $booking, $database);
            }
        }

        // Payment method label for display (per payment method, avoid wrong default for Crypto.com)
        $payment_method_label = __('Credit/Debit Card', 'amadex');
        if ($booking && !empty($booking['payment']['payment_method'])) {
            $pm = $booking['payment']['payment_method'];
            if ($pm === 'CRYPTO_COM') {
                $payment_method_label = 'Crypto.com Pay';
            } elseif ($pm === 'MOONPAY_ONRAMP') {
                $payment_method_label = 'MoonPay (Pay with card)';
            } elseif ($pm === 'PAYPAL') {
                $payment_method_label = 'PayPal';
            } elseif ($pm === 'CREDIT_CARD') {
                $payment_method_label = __('Credit/Debit Card', 'amadex');
            } elseif ($pm === 'MOONPAY' || $pm === 'MOONPAY_COMMERCE') {
                $payment_method_label = __('Card or Crypto', 'amadex');
            } else {
                $payment_method_label = ucwords(str_replace('_', ' ', $pm));
            }
        } elseif ($booking && isset($booking['status']) && $booking['status'] === 'PENDING' && $reference && get_transient('amadex_cryptocom_payment_' . $reference)) {
            $payment_method_label = 'Crypto.com Pay (' . __('pending', 'amadex') . ')';
        } elseif ($booking && $reference && get_transient('amadex_moonpay_paylink_' . $reference)) {
            // Booking created via MoonPay Commerce (Card or Crypto) but no payment record yet (user returned from pay link)
            $payment_method_label = __('Card or Crypto', 'amadex');
        }

        $general_settings = get_option('amadex_general_settings', array());
        $support_phone = isset($general_settings['call_now_number']) ? sanitize_text_field($general_settings['call_now_number']) : '+1-877-721-0410';
        $support_email = isset($general_settings['notification_email']) && is_email($general_settings['notification_email'])
            ? sanitize_email($general_settings['notification_email'])
            : get_option('admin_email');
        $brand = get_bloginfo('name');

        // Check if booking confirmation link has expired (24 hours)
        $is_expired = false;
        $expiration_message = '';
        if ($booking && !empty($booking['created_at'])) {
            try {
                $created_at = new DateTime($booking['created_at']);
                $now = new DateTime();

                // Calculate the difference in seconds for accuracy
                $diff_seconds = $now->getTimestamp() - $created_at->getTimestamp();
                $hours_passed = $diff_seconds / 3600; // Convert seconds to hours

                // Check if 24 hours or more have passed
                if ($hours_passed >= 24) {
                    $is_expired = true;
                    $expiration_message = sprintf(
                        __('This confirmation link has expired. It was valid for 24 hours from the booking date (%s). Please contact our support team for assistance.', 'amadex'),
                        $created_at->format('F j, Y \a\t g:i A')
                    );
                }
            } catch (Exception $e) {
                error_log('Amadex: Error checking booking expiration: ' . $e->getMessage());
                // If date parsing fails, don't block access but log the error
            }
        }

        // Helper function to format duration
        $format_duration = function ($duration) {
            if (preg_match('/PT(\d+)H(?:(\d+)M)?/', $duration, $matches)) {
                $hours = isset($matches[1]) ? intval($matches[1]) : 0;
                $minutes = isset($matches[2]) ? intval($matches[2]) : 0;
                return $hours . 'h ' . $minutes . 'm';
            }
            return $duration;
        };

        // Helper function to format date
        $format_date = function ($date_string) {
            $date = new DateTime($date_string);
            $day = $date->format('j');
            $month = $date->format('M');
            $year = $date->format('y');
            $day_name = $date->format('l');
            return $day . ' ' . $month . ', ' . $year . ' ' . $day_name;
        };

        // Helper function to format date short (for itinerary header)
        $format_date_short = function ($date_string) {
            $date = new DateTime($date_string);
            $day = $date->format('j');
            $month = $date->format('M');
            $year = $date->format('y');
            $day_name = $date->format('l');
            return $day . ' ' . $month . ', ' . $year . ' ' . $day_name;
        };

        // Helper function to format time
        $format_time = function ($date_string) {
            $date = new DateTime($date_string);
            return $date->format('H:i');
        };

        ob_start();
        ?>
        <div id="amadex-confirmation-page" class="amadex-confirmation-page">
            <!-- Top Navigation Bar with Progress -->
            <div class="amadex-confirmation-nav">
                <div class="amadex-booking-progress" data-booking-stage="booking">
                    <div class="booking-step is-active" data-step="booking">
                        <span class="booking-step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18.668" height="14" viewBox="0 0 18.668 14">
                                <g id="Group_194" data-name="Group 194" transform="translate(0 -51.208)">
                                    <path id="Path_19" data-name="Path 19"
                                        d="M18.653,53.8c-.149-.846-1.414-1.331-2.823-1.082L5.745,54.5l-3.739-3.29-1.915.337L1.4,55.263,0,55.51l.135.766A7.942,7.942,0,0,0,6.092,57.6l1.342-.237-.685,3.28,2.3-.406,4.515-3.955,2.809-.5c1.409-.249,2.432-1.137,2.283-1.983ZM0,63.652H18.668v1.556H0Z"
                                        fill="#0e7d3f" />
                                </g>
                            </svg>
                        </span>
                        <span class="booking-step-label">Booking Flight</span>
                    </div>
                    <span class="booking-step-divider"></span>
                    <div class="booking-step is-active" data-step="passengers">
                        <span class="booking-step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" id="Group_195" data-name="Group 195" width="18.661"
                                height="18.24" viewBox="0 0 18.661 18.24">
                                <path id="Path_21" data-name="Path 21"
                                    d="M60.054,64.189h-.74a2.267,2.267,0,0,1-2.265-2.265V58.7a2.267,2.267,0,0,1,2.265-2.265h.74A2.267,2.267,0,0,1,62.318,58.7v3.22A2.267,2.267,0,0,1,60.054,64.189ZM61.509,58.7a1.457,1.457,0,0,0-1.456-1.456h-.74A1.457,1.457,0,0,0,57.858,58.7v3.22a1.457,1.457,0,0,0,1.456,1.456h.74a1.457,1.457,0,0,0,1.456-1.456Zm-7.488,2.772a.723.723,0,0,1,.4.125.759.759,0,0,1,.32.494l.609,3.239a.3.3,0,0,0,.26.245l3.407.437a.761.761,0,0,1,.644.856.676.676,0,0,1-.567.65l-2.623-.255-.868-.091h-.045a.535.535,0,0,1-.067,0l-.186-.018a1.667,1.667,0,0,1-1.4-1.48l-.51-2.717-.109-.594a.762.762,0,0,1,.591-.871A.725.725,0,0,1,54.021,61.477Z"
                                    transform="translate(-49.865 -56.44)" fill="#0E7D3F" />
                                <path id="Path_22" data-name="Path 22"
                                    d="M55.121,110.177a1.7,1.7,0,0,1-1.515-1.384l-1.6-8.717a1.132,1.132,0,0,1,.91-1.317,1.173,1.173,0,0,1,.207-.019,1.133,1.133,0,0,1,1.111.929l.576,3.142c0,.019,0,.037.008.057l.109.567.548,3a2.069,2.069,0,0,0,1.828,1.679l.081.008c.057.008.114.013.172.017l.993.1,2.438.255a1.315,1.315,0,0,0,.162.01c.021,0,.042,0,.063-.005l1.092.106a1.133,1.133,0,1,1-.22,2.254l-2.064-.2v3.582h3.8l-.352-3.318a1.741,1.741,0,0,0-1.1-2.922l-.216-.021a1.488,1.488,0,0,0,.244-.667c0-.032.005-.064.007-.1.163.018.362.046.59.079a2.137,2.137,0,0,1,1.865,1.987l.32,4.957h5.083a.4.4,0,0,1,0,.809h-17.3a.4.4,0,0,1,0-.809h2.872v-3.99Z"
                                    transform="translate(-51.988 -96.807)" fill="#0E7D3F" />
                                <ellipse id="Ellipse_19" data-name="Ellipse 19" cx="1.49" cy="1.49" rx="1.49" ry="1.49"
                                    transform="translate(5.875 4.507) rotate(-176.83)" fill="#707070" />
                            </svg>
                        </span>
                        <span class="booking-step-label">Passenger Details</span>
                    </div>
                    <span class="booking-step-divider"></span>
                    <div class="booking-step is-active" data-step="payment">
                        <span class="booking-step-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20.001" height="14.376" viewBox="0 0 20.001 14.376">
                                <g id="Group_196" data-name="Group 196" transform="translate(0 -9)">
                                    <path id="Path_23" data-name="Path 23"
                                        d="M16.876,10.563A1.564,1.564,0,0,0,15.313,9H1.563A1.564,1.564,0,0,0,0,10.563v.313H16.876Zm-.313,5.313a2.584,2.584,0,0,1,.313.016V13.375H0v5.313a1.563,1.563,0,0,0,1.563,1.563H12.516a2.584,2.584,0,0,1-.016-.313,4.066,4.066,0,0,1,4.063-4.063ZM4.375,19H1.563a.313.313,0,1,1,0-.625H4.375a.313.313,0,1,1,0,.625Zm2.813-1.25H1.563a.313.313,0,0,1,0-.625H7.188a.313.313,0,0,1,0,.625ZM0,11.5H16.876v1.25H0Zm17.032,8.75h-.156v.938h.156a.469.469,0,1,0,0-.938Zm-1.406-1.094a.469.469,0,0,0,.469.469h.156v-.938h-.156a.469.469,0,0,0-.469.469Z"
                                        fill="#0E7D3F" />
                                    <path id="Path_24" data-name="Path 24"
                                        d="M45.438,33a3.438,3.438,0,1,0,3.438,3.438A3.438,3.438,0,0,0,45.438,33Zm.469,5.313H45.75v.313a.313.313,0,0,1-.625,0v-.313H44.5a.313.313,0,0,1,0-.625h.625V36.75h-.156a1.094,1.094,0,0,1,0-2.188h.156V34.25a.313.313,0,0,1,.625,0v.313h.625a.313.313,0,0,1,0,.625H45.75v.938h.156a1.094,1.094,0,0,1,0,2.188Z"
                                        transform="translate(-28.874 -16.5)" fill="#0E7D3F" />
                                </g>
                            </svg>
                        </span>
                        <span class="booking-step-label">Payment</span>
                    </div>

                </div>

                <?php if ($booking): ?>
                    <?php if ($is_expired): ?>
                        <!-- Expired Link Message -->
                        <div class="amadex-confirmation-content">
                            <div class="amadex-confirmation-main">
                                <div class="amadex-expired-message"
                                    style="background:#fff;border:2px solid #EF4444;border-radius:12px;padding:40px;margin:40px 20px;text-align:center;">
                                    <div style="margin-bottom:20px;">
                                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                            style="margin:0 auto;">
                                            <circle cx="12" cy="12" r="10" stroke="#EF4444" stroke-width="2" fill="none" />
                                            <path d="M12 8v4M12 16h.01" stroke="#EF4444" stroke-width="2" stroke-linecap="round" />
                                        </svg>
                                    </div>
                                    <h2 style="color:#EF4444;font-size:24px;font-weight:700;margin:0 0 16px 0;">
                                        <?php echo esc_html__('Confirmation Link Expired', 'amadex'); ?></h2>
                                    <p style="color:#6B7280;font-size:16px;line-height:1.6;margin:0 0 24px 0;">
                                        <?php echo esc_html($expiration_message); ?>
                                    </p>
                                    <div style="background:#F9FAFB;border-radius:8px;padding:20px;margin-top:24px;">
                                        <p style="color:#111827;font-size:14px;font-weight:600;margin:0 0 12px 0;">
                                            <?php echo esc_html__('Need Assistance?', 'amadex'); ?></p>
                                        <p style="color:#6B7280;font-size:14px;margin:0 0 16px 0;">
                                            <?php echo esc_html__('Our support team is available 24/7 to help you with your booking.', 'amadex'); ?>
                                        </p>
                                        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
                                            <?php if ($support_phone): ?>
                                                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $support_phone)); ?>"
                                                    style="display:inline-block;background:#0E7D3F;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">
                                                    <?php echo esc_html__('Call Us', 'amadex'); ?>: <?php echo esc_html($support_phone); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($support_email): ?>
                                                <a href="mailto:<?php echo esc_attr($support_email); ?>"
                                                    style="display:inline-block;background:#fff;color:#0E7D3F;border:2px solid #0E7D3F;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;">
                                                    <?php echo esc_html__('Email Us', 'amadex'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($reference): ?>
                                            <p style="color:#6B7280;font-size:12px;margin:24px 0 0 0;">
                                                <?php echo esc_html__('Booking Reference:', 'amadex'); ?>
                                                <strong><?php echo esc_html(strtoupper($reference)); ?></strong>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Normal Booking Content -->
                        <div class="amadex-confirmation-content">
                            <!-- Main Content Area -->
                            <div class="amadex-confirmation-main">
                                <!-- Greeting Section - Green Banner -->
                                <div class="amadex-confirmation-greeting amadex-greeting-banner">
                                    <div class="amadex-greeting-content">
                                        <div class="amadex-greeting-left">
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                                width="135" height="108" viewBox="0 0 135 108">
                                                <defs>
                                                    <clipPath id="clip-path">
                                                        <path id="Rectangle_937" data-name="Rectangle 937"
                                                            d="M15,0H120a15,15,0,0,1,15,15v93a0,0,0,0,1,0,0H0a0,0,0,0,1,0,0V15A15,15,0,0,1,15,0Z"
                                                            transform="translate(390 225)" fill="#fff" stroke="#0e7d3f"
                                                            stroke-width="1"></path>
                                                    </clipPath>
                                                    <clipPath id="clip-path-2">
                                                        <rect id="Rectangle_932" data-name="Rectangle 932" width="85.165"
                                                            height="95.394" fill="none"></rect>
                                                    </clipPath>
                                                    <clipPath id="clip-path-4">
                                                        <rect id="Rectangle_929" data-name="Rectangle 929" width="28.394"
                                                            height="28.872" fill="none"></rect>
                                                    </clipPath>
                                                    <clipPath id="clip-path-5">
                                                        <rect id="Rectangle_930" data-name="Rectangle 930" width="26.074"
                                                            height="30.449" fill="none"></rect>
                                                    </clipPath>
                                                    <clipPath id="clip-path-6">
                                                        <rect id="Rectangle_936" data-name="Rectangle 936" width="73.939"
                                                            height="98.184" fill="none"></rect>
                                                    </clipPath>
                                                    <clipPath id="clip-path-8">
                                                        <rect id="Rectangle_933" data-name="Rectangle 933" width="20.492"
                                                            height="22.101" fill="none"></rect>
                                                    </clipPath>
                                                    <clipPath id="clip-path-9">
                                                        <rect id="Rectangle_934" data-name="Rectangle 934" width="26.584"
                                                            height="37.041" fill="none"></rect>
                                                    </clipPath>
                                                </defs>
                                                <g id="Mask_Group_15" data-name="Mask Group 15" transform="translate(-390 -225)"
                                                    clip-path="url(#clip-path)">
                                                    <g id="Group_5351" data-name="Group 5351">
                                                        <g id="Group_5339" data-name="Group 5339"
                                                            transform="translate(1100.918 221.606)">
                                                            <g id="Group_5338" data-name="Group 5338" clip-path="url(#clip-path-2)">
                                                                <g id="Group_5337" data-name="Group 5337">
                                                                    <g id="Group_5336" data-name="Group 5336"
                                                                        clip-path="url(#clip-path-2)">
                                                                        <path id="Path_910" data-name="Path 910"
                                                                            d="M102.414,100.53l-28.5-22.156s-1.239-9.909-3.674-16.347c-1.2-3.163-5.549-8.6-8.456-12.785-1.491-2.147-3.2,1.792-2.331,4.153a62.966,62.966,0,0,0,3.437,6.792l-.719.174L57.375,57.59s-16.438-11.5-18.736-12.957c-2.36-1.5-4.162.137-1.974,2.452,2.57,2.72,12.269,10.309,14.609,12.692.6.611-.113.731-.113.731s-11.505-9.69-17.509-14.659c-2.739-2.267-4.629.17-2.291,2.358C34.8,51.425,44.5,60.985,47.456,63.692c.716.655-.074.747-.074.747S36.578,53.68,32.155,49.918c-1.892-1.612-3.334.746-1.89,2.413,2.436,2.809,10.871,13.236,13.482,15.58.706.633-.366.138-1.028-.533,0,0-5.7-5.979-9.9-10.349-1.091-1.134-2.917-.093-1.357,1.71,2.422,2.8,7.146,9.388,12.23,15.031A69.124,69.124,0,0,0,54.4,83.513c5.477,3.7,6.133,2.127,11.43,4.494l26.52,25.725,10.059-6.2Z"
                                                                            transform="translate(-17.249 -25.606)" fill="#df845d">
                                                                        </path>
                                                                        <path id="Path_911" data-name="Path 911"
                                                                            d="M157.413,171.669V148.211l-23.971-16.975-9.135,11.883,27.877,28.55Z"
                                                                            transform="translate(-72.248 -76.275)" fill="#005e86">
                                                                        </path>
                                                                        <g id="Group_5332" data-name="Group 5332"
                                                                            transform="translate(56.771 54.961)"
                                                                            style="mix-blend-mode: multiply;isolation: isolate">
                                                                            <g id="Group_5331" data-name="Group 5331">
                                                                                <g id="Group_5330" data-name="Group 5330"
                                                                                    clip-path="url(#clip-path-4)">
                                                                                    <path id="Path_912" data-name="Path 912"
                                                                                        d="M163.953,148.212l-23.971-16.975-4.422,5.753,28.394,23.119Z"
                                                                                        transform="translate(-135.559 -131.236)"
                                                                                        fill="#b3c6d0"></path>
                                                                                </g>
                                                                            </g>
                                                                        </g>
                                                                        <path id="Path_913" data-name="Path 913"
                                                                            d="M67.392,83.152,81.821,113.29H99.207l1.512-.3L78.31,76.917s2.274-9.724,2.222-16.607c-.026-3.382-2.225-9.989-3.5-14.924-.653-2.53-3.625.57-3.625,3.087a62.894,62.894,0,0,0,.868,7.562l-.735-.087-3.536-4.26S58.573,35.205,56.923,33.039c-1.693-2.224-3.95-1.314-2.7,1.615,1.468,3.442,7.934,13.924,9.3,16.97.351.782-.358.646-.358.646s-7.431-13.078-11.34-19.82c-1.783-3.076-4.4-1.445-2.966,1.417,2.109,4.211,7.889,16.539,9.726,20.1.446.864-.328.676-.328.676s-6.4-13.838-9.246-18.9c-1.217-2.168-3.387-.456-2.609,1.608,1.31,3.478,5.607,16.184,7.243,19.288.442.839-.392,0-.78-.856,0,0-3.269-7.582-5.7-13.14-.63-1.441-2.7-1.1-1.866,1.133,1.3,3.467,3.449,11.284,6.261,18.34A69.081,69.081,0,0,0,58.23,74.973c3.853,5.371,5.015,4.122,9.161,8.178"
                                                                            transform="translate(-26.215 -17.897)" fill="#f7a67b">
                                                                        </path>
                                                                        <path id="Path_914" data-name="Path 914"
                                                                            d="M136.969,185.525l-19.844-30.449-12.868,7.685,9.529,22.764Z"
                                                                            transform="translate(-60.595 -90.131)" fill="#006b99">
                                                                        </path>
                                                                        <g id="Group_5335" data-name="Group 5335"
                                                                            transform="translate(50.3 64.945)"
                                                                            style="mix-blend-mode: multiply;isolation: isolate">
                                                                            <g id="Group_5334" data-name="Group 5334">
                                                                                <g id="Group_5333" data-name="Group 5333"
                                                                                    clip-path="url(#clip-path-5)">
                                                                                    <path id="Path_915" data-name="Path 915"
                                                                                        d="M146.182,185.525l-19.844-30.449-6.23,3.721,14.964,26.729Z"
                                                                                        transform="translate(-120.107 -155.076)"
                                                                                        fill="#b3c6d0"></path>
                                                                                </g>
                                                                            </g>
                                                                        </g>
                                                                        <path id="Path_916" data-name="Path 916"
                                                                            d="M135.707,184.413a1.607,1.607,0,1,0,2.092-.889,1.607,1.607,0,0,0-2.092.889"
                                                                            transform="translate(-78.806 -106.598)" fill="#fff"></path>
                                                                        <path id="Path_917" data-name="Path 917"
                                                                            d="M129.31,173.556a1.607,1.607,0,1,0,2.091-.889,1.608,1.608,0,0,0-2.091.889"
                                                                            transform="translate(-75.088 -100.287)" fill="#fff"></path>
                                                                        <line id="Line_106" data-name="Line 106" y1="2.377" x2="7.242"
                                                                            transform="translate(49.546 20.951)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_107" data-name="Line 107" x1="5.525" y2="1.695"
                                                                            transform="translate(6.04 43.35)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_108" data-name="Line 108" x1="2.93" y1="5.23"
                                                                            transform="translate(15.15 3.524)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_109" data-name="Line 109" y1="5.767" x2="3.273"
                                                                            transform="translate(42.615 7.864)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_110" data-name="Line 110" x1="6.828" y1="2.336"
                                                                            transform="translate(2.663 18.85)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_111" data-name="Line 111" y1="7.077" x2="0.365"
                                                                            transform="translate(32.753 0.39)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_112" data-name="Line 112" x1="5.587" y2="0.205"
                                                                            transform="translate(0.39 33.072)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_113" data-name="Line 113" x1="4.804" y2="5.659"
                                                                            transform="translate(14.25 51.182)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                    </g>
                                                                </g>
                                                            </g>
                                                        </g>
                                                        <g id="Group_5349" data-name="Group 5349" transform="translate(390 235.027)">
                                                            <g id="Group_5348" data-name="Group 5348" clip-path="url(#clip-path-6)">
                                                                <g id="Group_5347" data-name="Group 5347">
                                                                    <g id="Group_5346" data-name="Group 5346"
                                                                        clip-path="url(#clip-path-6)">
                                                                        <path id="Path_918" data-name="Path 918"
                                                                            d="M48.992,66.514c2.458-2.207,10.4-12.024,12.692-14.668,1.36-1.569,0-3.789-1.779-2.271C55.742,53.117,45.57,63.245,45.57,63.245s-.744-.086-.069-.7c2.787-2.549,11.915-11.549,15.153-14.578,2.2-2.059.421-4.354-2.157-2.22-5.652,4.678-16.484,13.8-16.484,13.8s-.671-.112-.106-.688c2.2-2.244,11.335-9.388,13.754-11.95,2.059-2.178.363-3.718-1.858-2.308-2.164,1.374-17.639,12.2-17.639,12.2L31.649,59.4l-.677-.163a59.324,59.324,0,0,0,3.235-6.394c.821-2.224-.791-5.931-2.194-3.91C29.277,52.88,25.179,58,24.052,60.974c-2.293,6.061-3.459,15.39-3.459,15.39L0,92.373v15.289l3.231,1.991L28.2,85.435c4.986-2.229,5.6-.747,10.76-4.231a65.072,65.072,0,0,0,10.084-9.172c4.786-5.314,9.233-11.516,11.514-14.152,1.468-1.7-.25-2.677-1.278-1.61-3.958,4.114-9.319,9.743-9.319,9.743-.623.632-1.632,1.1-.968.5"
                                                                            transform="translate(0 -26.687)" fill="#df845d"></path>
                                                                        <path id="Path_919" data-name="Path 919"
                                                                            d="M0,167.955l24.929-25.531-8.6-11.187L0,142.8Z"
                                                                            transform="translate(0 -79.494)" fill="#91b508"></path>
                                                                        <g id="Group_5342" data-name="Group 5342"
                                                                            transform="translate(0 51.743)"
                                                                            style="mix-blend-mode: multiply;isolation: isolate">
                                                                            <g id="Group_5341" data-name="Group 5341">
                                                                                <g id="Group_5340" data-name="Group 5340"
                                                                                    clip-path="url(#clip-path-8)">
                                                                                    <path id="Path_920" data-name="Path 920"
                                                                                        d="M0,153.338l20.492-16.685-4.164-5.416L0,142.8Z"
                                                                                        transform="translate(0 -131.237)"
                                                                                        fill="#b3c6d0"></path>
                                                                                </g>
                                                                            </g>
                                                                        </g>
                                                                        <path id="Path_921" data-name="Path 921"
                                                                            d="M30.729,74.217s-2.141-9.155-2.092-15.634c.024-3.184,2.094-9.4,3.294-14.051.615-2.382,3.412.537,3.414,2.907a59.273,59.273,0,0,1-.817,7.119l.692-.082,3.329-4.01S49.311,34.947,50.864,32.908c1.594-2.094,3.719-1.238,2.543,1.521C52.025,37.669,45.938,47.537,44.65,50.4c-.331.736.338.609.338.609s7-12.313,10.676-18.66c1.679-2.9,4.143-1.36,2.793,1.334-1.986,3.964-7.427,15.571-9.157,18.927-.419.813.309.636.309.636s6.03-13.028,8.7-17.794c1.146-2.041,3.189-.43,2.457,1.513-1.233,3.275-5.279,15.237-6.819,18.159-.416.79.37,0,.735-.806,0,0,3.077-7.138,5.364-12.37.593-1.357,2.545-1.034,1.757,1.067-1.225,3.263-3.246,10.623-5.894,17.266a65.069,65.069,0,0,1-6.278,12.1c-3.628,5.056-4.722,3.88-8.625,7.7L25.987,111.46,9.632,108.174Z"
                                                                            transform="translate(-5.834 -18.652)" fill="#f7a67b"></path>
                                                                        <path id="Path_922" data-name="Path 922"
                                                                            d="M20.357,192.117l12.477-29.807-12.115-7.235L0,186.868v5.25Z"
                                                                            transform="translate(0 -93.934)" fill="#91b508"></path>
                                                                        <g id="Group_5345" data-name="Group 5345"
                                                                            transform="translate(0 61.142)"
                                                                            style="mix-blend-mode: multiply;isolation: isolate">
                                                                            <g id="Group_5344" data-name="Group 5344">
                                                                                <g id="Group_5343" data-name="Group 5343"
                                                                                    clip-path="url(#clip-path-9)">
                                                                                    <path id="Path_923" data-name="Path 923"
                                                                                        d="M7.807,192.117l18.777-33.539-5.865-3.5L0,186.868v5.25Z"
                                                                                        transform="translate(0 -155.076)"
                                                                                        fill="#b3c6d0"></path>
                                                                                </g>
                                                                            </g>
                                                                        </g>
                                                                        <path id="Path_924" data-name="Path 924"
                                                                            d="M47.185,184.355a1.513,1.513,0,1,1-1.969-.837,1.513,1.513,0,0,1,1.969.837"
                                                                            transform="translate(-26.814 -111.095)" fill="#fff"></path>
                                                                        <path id="Path_925" data-name="Path 925"
                                                                            d="M53.582,173.5a1.513,1.513,0,1,1-1.969-.837,1.514,1.514,0,0,1,1.969.837"
                                                                            transform="translate(-30.689 -104.519)" fill="#fff"></path>
                                                                        <line id="Line_114" data-name="Line 114" x1="6.817" y1="2.238"
                                                                            transform="translate(20.477 19.725)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_115" data-name="Line 115" x2="5.201" y2="1.596"
                                                                            transform="translate(63.051 40.812)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_116" data-name="Line 116" y1="4.924" x2="2.759"
                                                                            transform="translate(56.917 3.317)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_117" data-name="Line 117" x1="3.081" y1="5.43"
                                                                            transform="translate(30.738 7.404)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_118" data-name="Line 118" y1="2.199" x2="6.429"
                                                                            transform="translate(65.004 17.747)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_119" data-name="Line 119" x1="0.344" y1="6.663"
                                                                            transform="translate(42.761 0.367)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_120" data-name="Line 120" x2="5.26" y2="0.193"
                                                                            transform="translate(68.313 31.136)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                        <line id="Line_121" data-name="Line 121" x2="4.523" y2="5.327"
                                                                            transform="translate(56 48.186)" fill="none"
                                                                            stroke="#0e7d3f" stroke-linecap="round" stroke-width="1.86">
                                                                        </line>
                                                                    </g>
                                                                </g>
                                                            </g>
                                                        </g>
                                                    </g>
                                                </g>
                                            </svg>
                                            <div class="amadex-greeting-icons">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 23 23">
                                                    <g id="Group_5352" data-name="Group 5352" transform="translate(-4 -4)">
                                                        <path id="Path_926" data-name="Path 926"
                                                            d="M27,15.5c0,.981-1.206,1.79-1.447,2.695-.249.935.381,2.239-.092,3.057s-1.928.932-2.6,1.606-.774,2.122-1.606,2.6-2.122-.157-3.057.092C17.29,25.794,16.481,27,15.5,27s-1.79-1.206-2.695-1.447c-.935-.249-2.239.381-3.057-.092s-.932-1.928-1.606-2.6-2.122-.774-2.6-1.606.157-2.122-.092-3.057C5.206,17.29,4,16.481,4,15.5s1.206-1.79,1.447-2.695c.249-.935-.381-2.239.092-3.057s1.928-.932,2.6-1.606.774-2.122,1.606-2.6,2.122.157,3.057-.092C13.71,5.206,14.519,4,15.5,4s1.79,1.206,2.695,1.447c.935.249,2.239-.381,3.057.092s.932,1.928,1.606,2.6,2.122.774,2.6,1.606-.157,2.122.092,3.057C25.794,13.71,27,14.519,27,15.5Z"
                                                            fill="#0e7d3f"></path>
                                                        <path id="Path_927" data-name="Path 927"
                                                            d="M44.223,44.562,39.846,48.94l-2.269-2.267a1.262,1.262,0,0,0-1.784,1.784l3.184,3.184a1.229,1.229,0,0,0,1.736,0l5.294-5.294a1.261,1.261,0,0,0-1.782-1.784Z"
                                                            transform="translate(-25.4 -32.489)" fill="#fffcee"></path>
                                                    </g>
                                                </svg>
                                            </div>
                                            <div class="amadex-greeting-text">
                                                <h1 class="amadex-greeting-title">Dear
                                                    <?php echo esc_html($booking['lead']['contact_name'] ?? 'Guest'); ?></h1>
                                                <p class="amadex-greeting-message">Thank you for choosing
                                                    <?php echo esc_html($brand); ?> and for trusting your travel plans with us.</p>
                                            </div>

                                        </div>
                                        <!-- <div class="amadex-greeting-right">
                                     <span class="amadex-wave-icon">
                                        
                                    </span> 
                                </div> -->
                                    </div>
                                    <!--<div class="amadex-booking-status">
                                <p class="amadex-status-text"><strong>Your Booking is In Progress</strong></p>
                                <div class="amadex-booking-id">
                                    <span class="amadex-booking-id-label">Your booking Id is</span>
                                    <strong class="amadex-booking-id-value"><?php echo esc_html(strtoupper($reference)); ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Flight Itinerary Section  -->
                                    <div class="amadex-booking-section">
                                        <?php
                                        $booking_status = isset($booking['status']) ? strtoupper($booking['status']) : '';
                                        $is_moonpay_paid = ($transaction_status === 'completed' && !empty($transaction_id)) || ($booking && !empty($booking['payment']['payment_method']) && $booking['payment']['payment_method'] === 'MOONPAY_ONRAMP');
                                        if ($booking_status === 'CONFIRMED') {
                                            $status_heading = $is_moonpay_paid
                                                ? __('Your payment has been successfully received. Thank you for your booking!', 'amadex')
                                                : __('Your booking is confirmed', 'amadex');
                                        } else {
                                            $status_heading = __('Your Booking is In Progress', 'amadex');
                                        }
                                        ?>
                                        <p class="amadex-status-text"><strong><?php echo esc_html($status_heading); ?></strong></p>
                                        <div class="amadex-booking-id">
                                            <span
                                                class="amadex-booking-id-label"><?php esc_html_e('Your booking ID is', 'amadex'); ?></span>
                                            <strong
                                                class="amadex-booking-id-value"><?php echo esc_html(strtoupper($reference)); ?></strong>
                                        </div>
                                        <?php if (!empty($booking['flight_data']['itineraries'])): ?>
                                            <div class="amadex-flight-details-list">
                                                <?php foreach ($booking['flight_data']['itineraries'] as $index => $itinerary): ?>
                                                    <?php
                                                    $first_segment = $itinerary['segments'][0] ?? null;
                                                    $last_segment = end($itinerary['segments']);
                                                    // Use correct API keys (snake_case from database)
                                                    $origin_code = $first_segment['departure']['iata_code'] ?? $first_segment['departure']['iataCode'] ?? '';
                                                    $destination_code = $last_segment['arrival']['iata_code'] ?? $last_segment['arrival']['iataCode'] ?? '';

                                                    // Get airport city names (same as booking page)
                                                    $dep_airport_info = $this->get_airport_info($origin_code);
                                                    $arr_airport_info = $this->get_airport_info($destination_code);
                                                    $dep_city = $dep_airport_info['city'] ?? $origin_code;
                                                    $arr_city = $arr_airport_info['city'] ?? $destination_code;

                                                    $total_duration = $itinerary['duration'] ?? '';
                                                    $stops = count($itinerary['segments']) - 1;
                                                    $departure_date = $first_segment['departure']['at'] ?? '';
                                                    ?>
                                                    <div class="amadex-flight-detail-card">
                                                        <div class="amadex-flight-card-header"
                                                            data-toggle="flight-<?php echo esc_attr($index); ?>">
                                                            <div class="amadex-flight-card-summary">
                                                                <div class="amadex-flight-summary-top">
                                                                    <div>
                                                                        <p class="amadex-flight-route-title"><?php echo esc_html($dep_city); ?>
                                                                            - <?php echo esc_html($arr_city); ?></p>
                                                                        <p class="amadex-flight-meta-line">
                                                                            <?php echo esc_html($format_duration($total_duration)); ?> ·
                                                                            <?php echo $stops > 0 ? sprintf(_n('%d Stop', '%d Stops', $stops, 'amadex'), $stops) : esc_html__('Non-stop', 'amadex'); ?>
                                                                            · <?php echo esc_html($format_date($departure_date)); ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="amadex-flight-card-toggle" type="button">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="9.148"
                                                                    viewBox="0 0 16 9.148" class="amadex-chevron-icon">
                                                                    <path
                                                                        d="M13.032,17.143a1.15,1.15,0,0,1-.815-.331L5.333,9.955A1.15,1.15,0,0,1,6.963,8.332l6.07,6.057L19.1,8.343A1.145,1.145,0,0,1,20.72,9.955l-6.884,6.857a1.15,1.15,0,0,1-.8.331Z"
                                                                        transform="translate(-4.996 -7.996)"></path>
                                                                </svg>
                                                            </div>
                                                        </div>

                                                        <div id="flight-<?php echo esc_attr($index); ?>-content"
                                                            class="amadex-flight-card-content" style="display: block !important;">
                                                            <?php foreach ($itinerary['segments'] as $seg_index => $segment): ?>
                                                                <?php
                                                                $dep_time = $format_time($segment['departure']['at']);
                                                                $arr_time = $format_time($segment['arrival']['at']);
                                                                $dep_date = $format_date($segment['departure']['at']);
                                                                $arr_date = $format_date($segment['arrival']['at']);
                                                                $duration = $format_duration($segment['duration']);
                                                                // Use correct API keys (support both snake_case and camelCase)
                                                                $carrier_code = $segment['carrier_code'] ?? $segment['carrierCode'] ?? '';
                                                                $flight_number = $segment['number'] ?? '';
                                                                $dep_terminal = $segment['departure']['terminal'] ?? '';
                                                                $arr_terminal = $segment['arrival']['terminal'] ?? '';
                                                                $dep_airport_code = $segment['departure']['iata_code'] ?? $segment['departure']['iataCode'] ?? '';
                                                                $arr_airport_code = $segment['arrival']['iata_code'] ?? $segment['arrival']['iataCode'] ?? '';

                                                                // Get cabin class - ALWAYS prioritize travel_class/cabin from search_params (user's selection)
                                                                // The segment cabin may not reflect the selected class, so we use the original search selection
                                                                $cabin = 'ECONOMY'; // Default fallback

                                                                // First priority: Get travel_class or cabin from booking's lead search_params (the original search selection)
                                                                $travel_class = '';
                                                                if (!empty($booking['lead']) && !empty($booking['lead']['search_params'])) {
                                                                    $search_params = $booking['lead']['search_params'];
                                                                    // Check both travel_class and cabin fields (searchData may use either)
                                                                    $travel_class = !empty($search_params['travel_class'])
                                                                        ? strtoupper(trim($search_params['travel_class']))
                                                                        : (!empty($search_params['cabin']) ? strtoupper(trim($search_params['cabin'])) : '');
                                                                }

                                                                // Also check flight_data for travel_class (backup)
                                                                if (empty($travel_class) && !empty($booking['flight_data']['travel_class'])) {
                                                                    $travel_class = strtoupper(trim($booking['flight_data']['travel_class']));
                                                                }

                                                                // ALWAYS use travel_class/cabin from search_params if available (this is what user selected)
                                                                // This ensures the cabin class selected in search is always displayed on confirmation page
                                                                if (!empty($travel_class) && $travel_class !== 'ANY' && $travel_class !== '') {
                                                                    $cabin = $travel_class;
                                                                } else {
                                                                    // Fallback: Check segment cabin only if search_params not available
                                                                    $segment_cabin = $segment['cabin'] ?? '';
                                                                    if (!empty($segment_cabin)) {
                                                                        $cabin = strtoupper(trim($segment_cabin));
                                                                    }
                                                                }

                                                                $cabin_class = ucwords(strtolower(str_replace('_', ' ', $cabin)));

                                                                // Get airport city names from airport info (matching booking page)
                                                                $dep_airport_info = $this->get_airport_info($dep_airport_code);
                                                                $arr_airport_info = $this->get_airport_info($arr_airport_code);
                                                                $dep_city = $dep_airport_info['city'] ?? $dep_airport_code;
                                                                $arr_city = $arr_airport_info['city'] ?? $arr_airport_code;

                                                                // Format date for segment label
                                                                $segment_date = $format_date_short($segment['departure']['at']);
                                                                $segment_label = ($seg_index == 0) ? 'Flight Departure' : 'Flight Arrival';
                                                                ?>
                                                                <div class="amadex-segment-detail">
                                                                    <div class="amadex-segment-header">
                                                                        <div class="amadex-segment-label-date">
                                                                            <span
                                                                                class="amadex-segment-type-label"><?php echo esc_html($segment_label); ?></span>
                                                                            <span
                                                                                class="amadex-segment-date-label"><?php echo esc_html($segment_date); ?></span>
                                                                        </div>
                                                                        <div class="amadex-segment-airline-info">
                                                                            <div class="amadex-segment-airline">
                                                                                <?php
                                                                                // Get airline logo URL using IATA code from Amadeus API
                                                                                $airline_code = strtoupper(trim($carrier_code));
                                                                                $logo_url = $this->get_airline_logo_url($airline_code);
                                                                                ?>
                                                                                <img src="<?php echo esc_url($logo_url); ?>"
                                                                                    alt="<?php echo esc_attr($carrier_code); ?>"
                                                                                    onerror="this.onerror=null; this.src='<?php echo esc_js($this->get_airline_logo_fallback($airline_code)); ?>';"
                                                                                    class="amadex-airline-logo">
                                                                                <div class="amadex-airline-info">
                                                                                    <span
                                                                                        class="amadex-airline-name"><?php echo esc_html($this->get_airline_name($carrier_code)); ?></span>
                                                                                    <span
                                                                                        class="amadex-airline-code"><?php echo esc_html($carrier_code . '-' . $flight_number); ?></span>
                                                                                </div>
                                                                            </div>
                                                                            <div class="amadex-segment-travel-class-right">
                                                                                <span>Travel Class:
                                                                                    <strong><?php echo esc_html($cabin_class); ?></strong></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="amadex-segment-route-grid">
                                                                        <div class="amadex-segment-point">
                                                                            <span
                                                                                class="segment-point-label"><?php echo esc_html($dep_city); ?></span>
                                                                            <strong
                                                                                class="segment-point-time"><?php echo esc_html($dep_time); ?></strong>
                                                                            <span
                                                                                class="segment-point-airport"><?php echo esc_html($dep_airport_code); ?><?php echo $dep_terminal ? ' · Terminal ' . esc_html($dep_terminal) : ''; ?></span>
                                                                            <span
                                                                                class="segment-point-date"><?php echo esc_html($dep_date); ?></span>
                                                                        </div>
                                                                        <div class="amadex-segment-timeline">
                                                                            <span
                                                                                class="timeline-duration"><?php echo esc_html($duration); ?></span>
                                                                            <div class="amadex-timeline-line-vertical"></div>
                                                                            <svg xmlns="http://www.w3.org/2000/svg" id="group-480"
                                                                                data-name="Group 480" width="18" height="12.33"
                                                                                viewBox="0 0 18 12.33" class="amadex-timeline-airplane">
                                                                                <path id="Path_216" data-name="Path 216"
                                                                                    d="M17.885,5.786a2.3,2.3,0,0,0-1.307-.839,18.631,18.631,0,0,0-2.519-.162,2.491,2.491,0,0,1-.276-.016,1.859,1.859,0,0,1-.216-.221L10.23.409A2.063,2.063,0,0,0,9.918.086,1.293,1.293,0,0,0,9.3.006L9.211.013a1.294,1.294,0,0,0-.454.1c-.063.037-.255.15-.078.7,0,0,1.272,3.939,1.277,3.957-.019,0-1.83,0-1.83,0a2.566,2.566,0,0,1-.281-.015,1.82,1.82,0,0,1-.223-.217L6.446,3.145a2.032,2.032,0,0,0-.328-.316A.9.9,0,0,0,5.63,2.78a.832.832,0,0,0-.3.119c-.146.106-.171.337-.074.688.264.952.641,2.362.669,2.578C5.9,6.38,5.52,7.791,5.256,8.742A1.472,1.472,0,0,0,5.19,9.2c.042.27.424.345.44.348a1.163,1.163,0,0,0,.308.014c.2-.024.458-.32.508-.379L7.623,7.793a2.508,2.508,0,0,1,.191-.2,1.9,1.9,0,0,1,.313-.029H9.956c0,.018-1.276,3.957-1.276,3.957a1.322,1.322,0,0,0-.083.457c.027.2.234.31.615.339l.091.007c.058,0,.143.009.227.009a1.172,1.172,0,0,0,.221-.016c.19-.041.451-.359.48-.4L13.568,7.78a2.559,2.559,0,0,1,.185-.206,1.831,1.831,0,0,1,.306-.03,18.627,18.627,0,0,0,2.519-.162,2.491,2.491,0,0,1,.276-.016,1.859,1.859,0,0,1,.216-.221l3.237-4.139a2.063,2.063,0,0,0,.312-.323,1.293,1.293,0,0,0,.618-.08l.091-.007a1.294,1.294,0,0,0,.454-.1c.063-.037.255-.15.078-.7Z"
                                                                                    fill="#707070" />
                                                                            </svg>
                                                                        </div>
                                                                        <div class="amadex-segment-point is-arrival">
                                                                            <span
                                                                                class="segment-point-label"><?php echo esc_html($arr_city); ?></span>
                                                                            <strong
                                                                                class="segment-point-time"><?php echo esc_html($arr_time); ?></strong>
                                                                            <span
                                                                                class="segment-point-airport"><?php echo esc_html($arr_airport_code); ?><?php echo $arr_terminal ? ' · Terminal ' . esc_html($arr_terminal) : ''; ?></span>
                                                                            <span
                                                                                class="segment-point-date"><?php echo esc_html($arr_date); ?></span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <?php if ($seg_index < count($itinerary['segments']) - 1): ?>
                                                                    <?php
                                                                    $next_segment = $itinerary['segments'][$seg_index + 1];
                                                                    $connecting_time = '';
                                                                    if (isset($segment['arrival']['at']) && isset($next_segment['departure']['at'])) {
                                                                        $arr = new DateTime($segment['arrival']['at']);
                                                                        $dep = new DateTime($next_segment['departure']['at']);
                                                                        $diff = $arr->diff($dep);
                                                                        $connecting_time = $diff->h . 'h ' . $diff->i . 'm';
                                                                    }
                                                                    ?>
                                                                    <div class="amadex-stopover-banner">
                                                                        Change planes at
                                                                        <?php echo esc_html($segment['arrival']['iata_code'] ?? $segment['arrival']['iataCode'] ?? ''); ?>
                                                                        International Connecting Time: <?php echo esc_html($connecting_time); ?>m
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>


                                    <!-- Passenger Details Section -->
                                    <section class="amadex-card amadex-passenger-details-card">
                                        <h2>Passenger Detail</h2>
                                        <!-- <p class="amadex-section-subtitle">Passenger Detail as you filed</p> -->
                                        <?php if (!empty($booking['passengers'])): ?>
                                            <?php
                                            // Get seat selection data from flight_data
                                            $flight_data = isset($booking['flight_data']) ? (is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data']) : array();
                                            $seat_selection_data = $flight_data['seat_selection'] ?? array();
                                            $seat_segments = $seat_selection_data['segments'] ?? array();

                                            // Build a map of segment_id => route info from flight itinerary
                                            $segment_route_map = array();
                                            if (!empty($flight_data['itineraries'])) {
                                                foreach ($flight_data['itineraries'] as $itinerary) {
                                                    if (!empty($itinerary['segments'])) {
                                                        foreach ($itinerary['segments'] as $segment) {
                                                            // Try to get segment ID from various possible fields
                                                            $seg_id = $segment['id'] ?? $segment['segment_id'] ?? '';
                                                            $dep_iata = $segment['departure']['iata_code'] ?? $segment['departure']['iataCode'] ?? '';
                                                            $arr_iata = $segment['arrival']['iata_code'] ?? $segment['arrival']['iataCode'] ?? '';

                                                            if (!empty($seg_id)) {
                                                                // Build route display string
                                                                $route_display = '';
                                                                if (!empty($dep_iata) && !empty($arr_iata)) {
                                                                    $route_display = $dep_iata . ' → ' . $arr_iata;
                                                                } elseif (!empty($dep_iata)) {
                                                                    $route_display = $dep_iata . ' → ?';
                                                                } elseif (!empty($arr_iata)) {
                                                                    $route_display = '? → ' . $arr_iata;
                                                                } else {
                                                                    $route_display = 'Flight Segment';
                                                                }
                                                                $segment_route_map[(string)$seg_id] = $route_display;
                                                            }
                                                        }
                                                    }
                                                }
                                            }

                                            // Build a map of traveler_id => array of seat info (with route info) for all segments
                                            $traveler_seats = array();
                                            foreach ($seat_segments as $segment_id => $segment_data) {
                                                $seats = $segment_data['seats'] ?? array();

                                                // Get route information - first try from segment_route_map, then from segment_data
                                                $route_display = '';
                                                $seg_id_str = (string)$segment_id;
                                                if (isset($segment_route_map[$seg_id_str])) {
                                                    $route_display = $segment_route_map[$seg_id_str];
                                                } else {
                                                    // Fallback to segment_data departure/arrival
                                                    $dep_iata = '';
                                                    $arr_iata = '';
                                                    if (isset($segment_data['departure']['iataCode'])) {
                                                        $dep_iata = $segment_data['departure']['iataCode'];
                                                    } elseif (isset($segment_data['departure']['iata_code'])) {
                                                        $dep_iata = $segment_data['departure']['iata_code'];
                                                    } elseif (isset($segment_data['departure']['code'])) {
                                                        $dep_iata = $segment_data['departure']['code'];
                                                    }

                                                    if (isset($segment_data['arrival']['iataCode'])) {
                                                        $arr_iata = $segment_data['arrival']['iataCode'];
                                                    } elseif (isset($segment_data['arrival']['iata_code'])) {
                                                        $arr_iata = $segment_data['arrival']['iata_code'];
                                                    } elseif (isset($segment_data['arrival']['code'])) {
                                                        $arr_iata = $segment_data['arrival']['code'];
                                                    }

                                                    // Build route display string
                                                    if (!empty($dep_iata) && !empty($arr_iata)) {
                                                        $route_display = $dep_iata . ' → ' . $arr_iata;
                                                    } elseif (!empty($dep_iata)) {
                                                        $route_display = $dep_iata . ' → ?';
                                                    } elseif (!empty($arr_iata)) {
                                                        $route_display = '? → ' . $arr_iata;
                                                    } else {
                                                        $route_display = 'Flight Segment';
                                                    }
                                                }

                                                foreach ($seats as $seat) {
                                                    $traveler_id = isset($seat['traveler_id']) ? (string)$seat['traveler_id'] : '';
                                                    $seat_number = $seat['seat_number'] ?? '';
                                                    if (!empty($traveler_id) && !empty($seat_number)) {
                                                        if (!isset($traveler_seats[$traveler_id])) {
                                                            $traveler_seats[$traveler_id] = array();
                                                        }
                                                        $traveler_seats[$traveler_id][] = array(
                                                            'seat_number' => $seat_number,
                                                            'route' => $route_display,
                                                            'segment_id' => $segment_id
                                                        );
                                                    }
                                                }
                                            }
                                            ?>
                                            <table class="amadex-passenger-table">
                                                <thead>
                                                    <tr>
                                                        <th>Traveler List</th>
                                                        <th>Passenger Type</th>
                                                        <th>Name</th>
                                                        <th>Gender</th>
                                                        <th>Date of Birth</th>
                                                        <th>Seat Selection</th>
                                                        <th>Contact</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($booking['passengers'] as $pass_index => $passenger): ?>
                                                        <?php
                                                        // Get traveler ID - try multiple formats for matching
                                                        $traveler_id_1 = (string)($pass_index + 1);
                                                        $traveler_id_2 = (string)$pass_index;
                                                        $traveler_id_3 = $pass_index + 1;

                                                        // Try to match seat data using different traveler_id formats
                                                        $seat_data = $traveler_seats[$traveler_id_1] ?? $traveler_seats[$traveler_id_2] ?? $traveler_seats[$traveler_id_3] ?? array();

                                                        // Also try matching by passenger ID if available
                                                        if (empty($seat_data) && isset($passenger['id'])) {
                                                            $pass_id = (string)$passenger['id'];
                                                            $seat_data = $traveler_seats[$pass_id] ?? array();
                                                        }

                                                        // Format seat display with route information
                                                        $seat_display_parts = array();
                                                        if (!empty($seat_data)) {
                                                            foreach ($seat_data as $seat_info) {
                                                                if (is_array($seat_info)) {
                                                                    $seat_number = $seat_info['seat_number'] ?? '';
                                                                    $route = $seat_info['route'] ?? '';
                                                                    if (!empty($seat_number) && !empty($route)) {
                                                                        $seat_display_parts[] = $route . ': ' . $seat_number;
                                                                    } elseif (!empty($seat_number)) {
                                                                        $seat_display_parts[] = $seat_number;
                                                                    }
                                                                } else {
                                                                    // Backward compatibility: if it's just a string
                                                                    $seat_display_parts[] = $seat_info;
                                                                }
                                                            }
                                                        }
                                                        $seat_display = !empty($seat_display_parts) ? implode(' | ', $seat_display_parts) : 'Not Selected';
                                                        ?>
                                                        <tr>
                                                            <td data-label="Traveler List"><?php echo esc_html($pass_index + 1); ?></td>
                                                            <td data-label="Passenger Type">
                                                                <?php echo esc_html(ucwords(strtolower($passenger['passenger_type'] ?? 'Adult'))); ?>
                                                            </td>
                                                            <td data-label="Name">
                                                                <?php echo esc_html(trim(($passenger['title'] ?? '') . ' ' . ($passenger['first_name'] ?? '') . ' ' . ($passenger['last_name'] ?? ''))); ?>
                                                            </td>
                                                            <td data-label="Gender">
                                                                <?php echo esc_html(ucwords(strtolower($passenger['gender'] ?? ''))); ?></td>
                                                            <td data-label="Date of Birth">
                                                                <?php echo esc_html($passenger['date_of_birth'] ?? ''); ?></td>
                                                            <td data-label="Seat Selection"><?php echo esc_html($seat_display); ?></td>
                                                            <td data-label="Contact">
                                                                <?php echo esc_html($booking['lead']['contact_phone'] ?? ''); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <p><?php esc_html_e('Passenger details will be available once our team finalizes the reservation.', 'amadex'); ?>
                                            </p>
                                        <?php endif; ?>
                                    </section>
                                </div>
                            </div>

                            <!-- Right Sidebar -->
                            <aside class="amadex-confirmation-sidebar">
                                <!-- Price Summary -->
                                <section class="amadex-card amadex-price-summary-card">
                                    <h2>Price Summary</h2>
                                    <div class="amadex-price-breakdown">
                                        <div class="amadex-price-row">
                                            <div class="amadex-price-info">
                                                <span class="amadex-price-label">Payment Method</span>
                                            </div>
                                            <span class="amadex-price-value"><?php echo esc_html($payment_method_label); ?></span>
                                        </div>
                                        <?php
                                        // Get price breakdown using unified function (extracts adjusted prices from flight_data)
                                        // This matches exactly what the booking page shows (adjusted prices from price management)
                                        $price_breakdown = $this->get_price_breakdown($booking);
                                        $base_fare = $price_breakdown['base_fare'];
                                        $taxes = $price_breakdown['taxes'];
                                        $premium_service = $price_breakdown['premium_service'] ?? 0;
                                        $premium_service_added_flag = $price_breakdown['premium_service_added'] ?? false;
                                        $seat_charges = $price_breakdown['seat_selection'] ?? 0;
                                        $seat_selection_in_data = $price_breakdown['seat_selection_in_data'] ?? false; // Flag from unified function
                                        // Total already includes seat charges (stored_total includes everything)
                                        $total = $price_breakdown['total'];
                                        $currency_code = $price_breakdown['currency'];

                                        // Get user's selected currency from booking data (regional settings)
                                        $selected_currency = 'USD';
                                        if (class_exists('Amadex_Currency')) {
                                            $selected_currency = Amadex_Currency::get_user_selected_currency($booking);
                                        }

                                        // Check if regional settings system is enabled (using cached helper method)
                                        $regional_settings_enabled = class_exists('Amadex_Currency')
                                            ? Amadex_Currency::is_regional_settings_enabled()
                                            : true; // Default to enabled if class doesn't exist (backward compatibility)

                                        // If regional settings disabled, force USD and skip conversion
                                        if (!$regional_settings_enabled) {
                                            $selected_currency = 'USD';
                                            amadex_log('Regional Settings System disabled on confirmation page - forcing USD, skipping currency conversion');
                                        }

                                        // CRITICAL FIX: get_unified_price_breakdown() ALREADY returns amounts in display currency
                                        // when currency_conversion exists in flight_data. Do NOT convert again or totals become wrong.
                                        $exchange_rate = floatval($price_breakdown['exchange_rate'] ?? 1.0);
                                        $flight_data_for_conversion = isset($booking['flight_data']) ? (is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data']) : array();
                                        $conversion_info = $flight_data_for_conversion['currency_conversion'] ?? array();

                                        // Only convert when breakdown is in USD but user selected another currency (legacy path)
                                        if ($regional_settings_enabled && $selected_currency !== 'USD' && class_exists('Amadex_Currency')) {
                                            if ($currency_code === $selected_currency) {
                                                // Breakdown is already in display currency (INR etc.) - use as-is, no conversion
                                                if (!empty($conversion_info['exchange_rate'])) {
                                                    $exchange_rate = floatval($conversion_info['exchange_rate']);
                                                }
                                                // base_fare, taxes, total, etc. already correct from get_unified_price_breakdown
                                            } elseif ($currency_code === 'USD') {
                                                // Breakdown is in USD (no conversion info or legacy) - convert to selected currency
                                                $base_fare = Amadex_Currency::convert($base_fare, 'USD', $selected_currency);
                                                $taxes = Amadex_Currency::convert($taxes, 'USD', $selected_currency);
                                                $premium_service = Amadex_Currency::convert($premium_service, 'USD', $selected_currency);
                                                $seat_charges = Amadex_Currency::convert($seat_charges, 'USD', $selected_currency);
                                                $total = Amadex_Currency::convert($total, 'USD', $selected_currency);
                                                $exchange_rate = Amadex_Currency::get_exchange_rate('USD', $selected_currency);
                                                $currency_code = $selected_currency;
                                            }
                                        }

                                        // Format currency symbol (USD -> $, CAD -> C$, etc.) to match booking page
                                        $currency_symbol = $this->format_currency_symbol($currency_code);

                                        // Build passenger summary to match booking page format (e.g., "1 Adult" or "2 Adults, 1 Child")
                                        $passenger_count = count($booking['passengers'] ?? []);
                                        $adults = 0;
                                        $children = 0;
                                        $infants = 0;

                                        foreach ($booking['passengers'] ?? [] as $passenger) {
                                            $type = strtolower($passenger['passenger_type'] ?? 'adult');
                                            if ($type === 'adult') {
                                                $adults++;
                                            } elseif ($type === 'child') {
                                                $children++;
                                            } elseif ($type === 'infant') {
                                                $infants++;
                                            }
                                        }

                                        $passenger_summary_parts = array();
                                        if ($adults > 0) {
                                            $passenger_summary_parts[] = $adults . ' Adult' . ($adults > 1 ? 's' : '');
                                        }
                                        if ($children > 0) {
                                            $passenger_summary_parts[] = $children . ' Child' . ($children > 1 ? 'ren' : '');
                                        }
                                        if ($infants > 0) {
                                            $passenger_summary_parts[] = $infants . ' Infant' . ($infants > 1 ? 's' : '');
                                        }
                                        $passenger_summary = !empty($passenger_summary_parts) ? implode(', ', $passenger_summary_parts) : 'Passengers';
                                        ?>
                                        <div class="amadex-price-row">
                                            <div class="amadex-price-info">
                                                <span class="amadex-price-label">Base Fare</span>
                                                <span class="amadex-price-subtext"><?php echo esc_html($passenger_summary); ?></span>
                                            </div>
                                            <span
                                                class="amadex-price-value"><?php echo esc_html($currency_symbol); ?><?php echo number_format($base_fare, 2); ?></span>
                                        </div>
                                        <div class="amadex-price-row">
                                            <div class="amadex-price-info">
                                                <span class="amadex-price-label">Taxes</span>
                                                <span class="amadex-price-subtext">Taxes & Fees</span>
                                            </div>
                                            <span
                                                class="amadex-price-value"><?php echo esc_html($currency_symbol); ?><?php echo number_format($taxes, 2); ?></span>
                                        </div>
                                        <?php
                                        // Use breakdown values so confirmation matches booking-flight page exactly
                                        // Formula: Total = Base Fare + Taxes + Seat Selection + Premium Service + Addons
                                        $display_seat_charges = $seat_charges;   // From breakdown
                                        $display_premium_amount = $premium_service;

                                        $flight_data_direct = isset($booking['flight_data']) ? (is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data']) : array();

                                        // Seat Selection: ensure we show display-currency amount (e.g. INR), not raw USD
                                        // flight_data['seat_selection']['total_seat_charges'] is stored in USD; convert when display is INR etc.
                                        if ($currency_code !== 'USD' && ! empty($flight_data_direct['seat_selection']) && class_exists('Amadex_Currency')) {
                                            $raw_seat_usd = isset($flight_data_direct['seat_selection']['total_seat_charges'])
                                                ? floatval($flight_data_direct['seat_selection']['total_seat_charges'])
                                                : 0;
                                            if ($raw_seat_usd > 0) {
                                                $display_seat_charges = Amadex_Currency::convert($raw_seat_usd, 'USD', $currency_code);
                                            }
                                        }

                                        // Only use flight_data to determine if we should SHOW the row (has seats selected)
                                        $has_seat_selection = false;
                                        if (! empty($flight_data_direct['seat_selection']) && isset($flight_data_direct['seat_selection']['segments']) && is_array($flight_data_direct['seat_selection']['segments'])) {
                                            foreach ($flight_data_direct['seat_selection']['segments'] as $segment_data) {
                                                if (! empty($segment_data['seats']) && is_array($segment_data['seats']) && ! empty($segment_data['seats'])) {
                                                    $has_seat_selection = true;
                                                    break;
                                                }
                                            }
                                        }

                                        // PRIORITY 1: Get ALL add-ons from flight_data['addons'] (new system)
                                        $all_addons_from_data = array();
                                        if (!empty($flight_data_direct['addons']) && is_array($flight_data_direct['addons'])) {
                                            $all_addons_from_data = $flight_data_direct['addons'];
                                        }

                                        // Legacy: If premium_service exists in flight_data, add it to addons (backward compatibility)
                                        if (!empty($flight_data_direct['premium_service'])) {
                                            $premium_service_data = $flight_data_direct['premium_service'];
                                            if (isset($premium_service_data['added']) && $premium_service_data['added'] === true) {
                                                $legacy_premium_amount = isset($premium_service_data['amount']) && $premium_service_data['amount'] > 0
                                                    ? floatval($premium_service_data['amount'])
                                                    : 25.00;
                                                // Check if premium-services already exists in addons array
                                                $has_premium = false;
                                                foreach ($all_addons_from_data as $addon) {
                                                    if (($addon['id'] ?? '') === 'premium-services' || ($addon['title'] ?? '') === 'Premium Services') {
                                                        $has_premium = true;
                                                        break;
                                                    }
                                                }
                                                if (!$has_premium) {
                                                    // Add legacy premium service to addons array
                                                    $all_addons_from_data[] = array(
                                                        'id' => 'premium-services',
                                                        'title' => 'Premium Services',
                                                        'price' => $legacy_premium_amount,
                                                        'currency' => 'USD'
                                                    );
                                                }
                                            }
                                        }

                                        // CRITICAL FIX: Calculate addons total for difference calculation
                                        // Priority 1: Use addons from breakdown (new field from get_unified_price_breakdown)
                                        $addons_total_for_diff = floatval($price_breakdown['addons'] ?? 0);

                                        // Priority 2: If not in breakdown, calculate from flight_data
                                        if ($addons_total_for_diff <= 0 && !empty($all_addons_from_data)) {
                                            $addons_total_for_diff = 0;
                                            foreach ($all_addons_from_data as $addon) {
                                                $addon_price = floatval($addon['price'] ?? 0);
                                                if ($addon_price > 0) {
                                                    $addons_total_for_diff += $addon_price;
                                                }
                                            }
                                        }

                                        // Step 3: Calculate the difference (what's left after base + taxes + addons)
                                        // CRITICAL: Subtract addons from difference to prevent incorrect assignment to seats/premium
                                        // $base_and_taxes = $base_fare + $taxes;
                                        // $difference = $total - $base_and_taxes - $addons_total_for_diff;

                                        // // PRIORITY 2: If difference exists, ensure we account for it
                                        // // This is the KEY: if total > base + taxes + addons, we MUST have seat selection and/or premium
                                        // if (abs($difference) > 0.01) {
                                        //     $detected_total = $display_seat_charges + $display_premium_amount;
                                        //     $remaining = $difference - $detected_total;

                                        //     // If we haven't detected enough, assign the remaining difference
                                        //     if (abs($remaining) > 0.01) {
                                        //         // Simple rule: if difference > $25, split: $25 premium, rest seats
                                        //         // Otherwise, if premium flag is set, it's premium; else it's seats
                                        //         if ($difference > 25 && $display_premium_amount <= 0 && $display_seat_charges <= 0) {
                                        //             // Both missing: split $25 premium, rest seats
                                        //             $display_premium_amount = 25.00;
                                        //             $display_seat_charges = round($difference - 25.00, 2);
                                        //         } elseif ($premium_service_added_flag && $display_premium_amount <= 0) {
                                        //             // Premium missing: assign to premium
                                        //             $display_premium_amount = round($remaining, 2);
                                        //         } elseif ($seat_selection_in_data && $display_seat_charges <= 0) {
                                        //             // Seats missing: assign to seats
                                        //             $display_seat_charges = round($remaining, 2);
                                        //         } elseif ($display_premium_amount <= 0 && abs($remaining - 25.00) < 1.00) {
                                        //             // Remaining is ~$25: it's premium
                                        //             $display_premium_amount = round($remaining, 2);
                                        //         } elseif ($display_seat_charges <= 0 && $remaining > 0) {
                                        //             // Otherwise: assign to seats
                                        //             $display_seat_charges = round($remaining, 2);
                                        //         } elseif ($display_premium_amount <= 0 && $remaining > 0) {
                                        //             // Last resort: assign to premium
                                        //             $display_premium_amount = round($remaining, 2);
                                        //         }
                                        //     }

                                        //     // FINAL FALLBACK: If difference still not accounted for, force assignment
                                        //     $final_check = $display_seat_charges + $display_premium_amount;
                                        //     $final_remaining = $difference - $final_check;
                                        //     if (abs($final_remaining) > 0.01) {
                                        //         // If both are zero, split the difference
                                        //         if ($display_seat_charges <= 0 && $display_premium_amount <= 0) {
                                        //             if ($difference > 25) {
                                        //                 $display_premium_amount = 25.00;
                                        //                 $display_seat_charges = round($difference - 25.00, 2);
                                        //             } else {
                                        //                 $display_premium_amount = round($difference, 2);
                                        //             }
                                        //         } elseif ($display_seat_charges <= 0) {
                                        //             // Only seats missing
                                        //             $display_seat_charges = round($final_remaining, 2);
                                        //         } elseif ($display_premium_amount <= 0) {
                                        //             // Only premium missing
                                        //             $display_premium_amount = round($final_remaining, 2);
                                        //         }
                                        //     }
                                        // }
                                        // Step 3: Calculate the difference (what's left after base + taxes + addons)
                                        // CRITICAL: Subtract addons from difference to prevent incorrect assignment to seats/premium
                                        $base_and_taxes = $base_fare + $taxes;
                                        $difference = $total - $base_and_taxes - $addons_total_for_diff;

                                        // PRIORITY 2: Only assign difference to seats/premium if we have explicit flags
                                        // Never guess - only assign if seat_selection_in_data or premium_service_added_flag is set
                                        if (abs($difference) > 0.01) {
                                            $detected_total = $display_seat_charges + $display_premium_amount;
                                            $remaining = $difference - $detected_total;

                                            if (abs($remaining) > 0.01) {
                                                if ($seat_selection_in_data && $premium_service_added_flag) {
                                                    // Both: premium gets its known amount, rest goes to seats
                                                    if ($display_premium_amount <= 0) $display_premium_amount = 25.00;
                                                    if ($display_seat_charges <= 0) $display_seat_charges = round($remaining - $display_premium_amount, 2);
                                                } elseif ($seat_selection_in_data && $display_seat_charges <= 0) {
                                                    // Only seats selected: assign remaining to seats
                                                    $display_seat_charges = round($remaining, 2);
                                                } elseif ($premium_service_added_flag && $display_premium_amount <= 0) {
                                                    // Only premium: assign remaining to premium
                                                    $display_premium_amount = round($remaining, 2);
                                                }
                                                // If neither flag is set: do NOT assign remaining to seats or premium
                                                // The difference is fully accounted for by addons
                                            }
                                        }
                                        // Step 4: SIMPLE DISPLAY LOGIC - Show items based on actual data

                                        // Display Seat Selection (show even if charges = $0 when seats are selected)
                                        if ($has_seat_selection || $display_seat_charges > 0): ?>
                                            <div class="amadex-price-row seat-selection">
                                                <div class="amadex-price-info">
                                                    <span class="amadex-price-label">Seat Selection</span>
                                                    <span class="amadex-price-subtext">Selected Seats</span>
                                                </div>
                                                <span
                                                    class="amadex-price-value"><?php echo esc_html($currency_symbol); ?><?php echo number_format($display_seat_charges, 2); ?></span>
                                            </div>
                                        <?php endif; ?>

                                        <?php
                                        // Display ALL add-ons from flight_data (not just premium_service)
                                        if (!empty($all_addons_from_data)) {
                                            foreach ($all_addons_from_data as $addon) {
                                                $addon_id = $addon['id'] ?? '';
                                                $addon_title = $addon['title'] ?? 'Add-on';
                                                $addon_price = floatval($addon['price'] ?? 0);
                                                $addon_currency = $addon['currency'] ?? 'USD';

                                                // Convert price if currency differs from display currency
                                                if ($addon_currency !== $currency_code && class_exists('Amadex_Currency')) {
                                                    $addon_price = Amadex_Currency::convert($addon_price, $addon_currency, $currency_code);
                                                }

                                                if ($addon_price > 0) {
                                                    // Determine subtext based on addon ID or title
                                                    $addon_subtext = 'Optional';
                                                    if ($addon_id === 'premium-services' || $addon_title === 'Premium Services') {
                                                        $addon_subtext = 'TravelayGent™';
                                                    } elseif ($addon_id === 'travelaysurance' || stripos($addon_title, 'TravelaySurance') !== false) {
                                                        $addon_subtext = 'Travel Insurance';
                                                    }
                                        ?>
                                                    <div class="amadex-price-row premium-service addon">
                                                        <div class="amadex-price-info">
                                                            <span class="amadex-price-label"><?php echo esc_html($addon_title); ?></span>
                                                            <span class="amadex-price-subtext"><?php echo esc_html($addon_subtext); ?></span>
                                                        </div>
                                                        <span
                                                            class="amadex-price-value"><?php echo esc_html($currency_symbol); ?><?php echo number_format($addon_price, 2); ?></span>
                                                    </div>
                                            <?php
                                                }
                                            }
                                        }

                                        // Legacy: Display Premium Service if it exists but wasn't in addons array (backward compatibility)
                                        if (empty($all_addons_from_data) && $display_premium_amount > 0): ?>
                                            <div class="amadex-price-row premium-service">
                                                <div class="amadex-price-info">
                                                    <span class="amadex-price-label">Premium Service</span>
                                                    <span class="amadex-price-subtext">TravelayGent™</span>
                                                </div>
                                                <span
                                                    class="amadex-price-value"><?php echo esc_html($currency_symbol); ?><?php echo number_format($display_premium_amount, 2); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="amadex-price-row total">
                                            <div class="amadex-price-info">
                                                <span class="amadex-price-label">Total Amount</span>
                                            </div>
                                            <span
                                                class="amadex-price-value amadex-price-value--highlight"><?php echo esc_html($currency_symbol); ?><span
                                                    data-amadex-ce-count="<?php echo esc_attr($total); ?>" data-amadex-ce-decimals="2"
                                                    data-amadex-ce-duration="1200"><?php echo number_format($total, 2); ?></span></span>
                                        </div>

                                        <?php
                                        // Show currency conversion note if currency was converted
                                        // IMPORTANT: Use the stored USD amount from database (same as NMI transaction)
                                        // This ensures the displayed USD amount matches exactly what was sent to NMI
                                        $original_currency = $price_breakdown['original_currency'] ?? 'USD';
                                        $exchange_rate = $price_breakdown['exchange_rate'] ?? 1.0;

                                        // Get stored USD amount directly from booking (this is what was sent to NMI)
                                        $stored_usd_total = floatval($booking['total_amount'] ?? 0);

                                        // Also check flight_data for currency conversion info
                                        $flight_data = isset($booking['flight_data']) ? (is_string($booking['flight_data']) ? json_decode($booking['flight_data'], true) : $booking['flight_data']) : array();
                                        $conversion_info = $flight_data['currency_conversion'] ?? array();
                                        $stored_usd_from_conversion = floatval($conversion_info['usd_amount'] ?? 0);

                                        // Use stored USD amount from conversion if available, otherwise use booking total_amount
                                        // Both should be the same, but conversion info is more specific
                                        $usd_total = ($stored_usd_from_conversion > 0) ? $stored_usd_from_conversion : $stored_usd_total;

                                        // Get the actual exchange rate used (from conversion info if available)
                                        $actual_exchange_rate = floatval($conversion_info['exchange_rate'] ?? $exchange_rate);
                                        $charge_total_was_usd = isset($conversion_info['charge_total_was_usd']) && $conversion_info['charge_total_was_usd'] === true;

                                        // Determine correct exchange rate direction for display
                                        // The stored rate is always USD -> display_currency (e.g., USD -> INR = 90.97)
                                        // For display, we want to show "1 USD = X display_currency" (e.g., "1 USD = 90.97 INR")
                                        if ($charge_total_was_usd && $actual_exchange_rate > 0) {
                                            // Rate stored is USD -> display_currency, use it directly for display
                                            $display_rate = $actual_exchange_rate;
                                        } else {
                                            // Rate might be display_currency -> USD, convert to USD -> display_currency
                                            if ($actual_exchange_rate > 0 && $actual_exchange_rate < 1) {
                                                // Rate is display_currency -> USD (e.g., 0.011), convert to USD -> display_currency
                                                $display_rate = 1.0 / $actual_exchange_rate;
                                            } else {
                                                // Rate is already USD -> display_currency or invalid
                                                $display_rate = $actual_exchange_rate;
                                            }
                                        }

                                        // Only show conversion note if currency was converted and we have valid USD amount
                                        if ($currency_code !== 'USD' && $display_rate > 0 && $display_rate != 1.0 && $usd_total > 0):
                                        ?>
                                            <div class="amadex-currency-conversion-note"
                                                style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed #E8ECE9;">
                                                <p style="margin: 0; font-size: 12px; color: #7A8B81; line-height: 1.5;">
                                                    <?php
                                                    if (class_exists('Amadex_Currency')) {
                                                        $usd_formatted = Amadex_Currency::format($usd_total, 'USD');
                                                    } else {
                                                        $usd_formatted = '$' . number_format($usd_total, 2);
                                                    }
                                                    // FIXED: Show correct rate direction "1 USD = X display_currency" instead of backwards
                                                    echo esc_html(sprintf(
                                                        __('Payment processed in USD: %s (Exchange rate: 1 USD = %s %s)', 'amadex'),
                                                        $usd_formatted,
                                                        number_format($display_rate, 4),
                                                        $currency_code
                                                    ));
                                                    ?>
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </section>

                                <!-- What Happens Next -->
                                <section class="amadex-card amadex-what-happens-next">
                                    <h2>What Happens Next?</h2>
                                    <ol class="amadex-next-steps">
                                        <li><?php esc_html_e('A verification specialist reviews your request and secures live inventory.', 'amadex'); ?>
                                        </li>
                                        <li><?php esc_html_e('We call you to confirm traveler details, baggage, and any upgrades.', 'amadex'); ?>
                                        </li>
                                        <!-- <li><?php esc_html_e('Payment is captured only after you approve the final itinerary.', 'amadex'); ?></li>
                                <li><?php esc_html_e('Tickets are issued manually in GDS/NDC and shared via email/SMS.', 'amadex'); ?></li> -->
                                    </ol>
                                </section>

                                <!-- Need Assistance -->
                                <section class="amadex-card amadex-support-card">
                                    <div class="amadex-support-header">
                                        <h2>Need assistance?</h2>
                                        <p><?php esc_html_e('Our team is on standby 24/7. Share your booking reference for faster service.', 'amadex'); ?>
                                        </p>
                                    </div>
                                    <div class="amadex-support-content">
                                        <div class="amadex-support-links">
                                            <a class="amadex-support-link" href="tel:+18777210410">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14">
                                                    <g id="Group_5356" data-name="Group 5356" transform="translate(-0.006)">
                                                        <path id="Path_101" data-name="Path 101"
                                                            d="M10.649,9.261a.981.981,0,0,0-1.483,0c-.347.344-.693.688-1.034,1.037a.2.2,0,0,1-.286.052c-.224-.122-.463-.221-.679-.355a10.755,10.755,0,0,1-2.593-2.36,6.143,6.143,0,0,1-.929-1.489A.213.213,0,0,1,3.7,5.873c.347-.335.685-.679,1.025-1.023a.985.985,0,0,0,0-1.518c-.271-.274-.542-.542-.813-.816s-.556-.562-.839-.839a.987.987,0,0,0-1.483,0c-.35.344-.685.7-1.04,1.034a1.688,1.688,0,0,0-.53,1.139A4.826,4.826,0,0,0,.389,5.931a12.622,12.622,0,0,0,2.24,3.732A13.864,13.864,0,0,0,7.22,13.255,6.64,6.64,0,0,0,9.764,14a1.864,1.864,0,0,0,1.6-.609c.3-.332.632-.635.947-.953a.991.991,0,0,0,.006-1.509q-.83-.835-1.666-1.663Zm-.556-2.322,1.075-.184A4.825,4.825,0,0,0,7.086,2.8L6.935,3.878a3.729,3.729,0,0,1,3.158,3.062Zm1.681-4.673A7.927,7.927,0,0,0,7.229,0L7.078,1.081a6.917,6.917,0,0,1,5.853,5.672l1.075-.184a7.985,7.985,0,0,0-2.232-4.3Z"
                                                            transform="translate(0)" />
                                                    </g>
                                                </svg>
                                                +1-877-721-0410
                                            </a>
                                            <a class="amadex-support-link" href="mailto:<?php echo esc_attr($support_email); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14.942" height="10.799"
                                                    viewBox="0 0 14.942 10.799">
                                                    <g id="Group_5357" data-name="Group 5357" transform="translate(-2 -6.4)">
                                                        <path id="Path_928" data-name="Path 928"
                                                            d="M10.844,12.638a2.348,2.348,0,0,1-1.4.512,2.149,2.149,0,0,1-1.4-.512L3.4,16.455l-.605.465a1.678,1.678,0,0,0,.931.279h11.4a1.678,1.678,0,0,0,.931-.279l-.559-.465Zm5.772-5.493-.512.512-4.7,4.469,4.655,3.864.559.465a1.755,1.755,0,0,0,.326-.978V8.122a2.214,2.214,0,0,0-.326-.978ZM8.238,11.893l.047.047.093.093a1.519,1.519,0,0,0,2.095,0l.093-.093.047-.047,4.981-4.7.512-.512a1.678,1.678,0,0,0-.931-.279H3.769a1.721,1.721,0,0,0-.978.279l.512.512Zm-.745.233-4.7-4.469-.512-.465A1.573,1.573,0,0,0,2,8.122v7.355a1.721,1.721,0,0,0,.279.978l.559-.465Z"
                                                            transform="translate(0)" />
                                                    </g>
                                                </svg>
                                                <?php echo esc_html($support_email); ?>
                                            </a>
                                        </div>
                                        <div class="amadex-support-illustration">
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                                width="324" height="87" viewBox="0 0 324 87" class="amadex-support-svg">
                                                <defs>
                                                    <clipPath id="clip-path">
                                                        <rect id="Rectangle_942" data-name="Rectangle 942" width="324" height="69"
                                                            rx="20" transform="translate(1206 928)" fill="#fff" stroke="#fff"
                                                            stroke-width="1" />
                                                    </clipPath>
                                                    <clipPath id="clip-path-2">
                                                        <rect id="Rectangle_943" data-name="Rectangle 943" width="324" height="18"
                                                            rx="9" transform="translate(1206 910)" fill="#fff" stroke="#fff"
                                                            stroke-width="1" />
                                                    </clipPath>
                                                </defs>
                                                <g id="Group_5390" data-name="Group 5390" transform="translate(-1206 -910)">
                                                    <g id="Mask_Group_16" data-name="Mask Group 16" clip-path="url(#clip-path)">
                                                        <g id="Group_5373" data-name="Group 5373"
                                                            transform="translate(-15296.633 -243.125)">
                                                            <g id="Group_5361" data-name="Group 5361"
                                                                transform="translate(16730.133 1175.249)">
                                                                <g id="Group_5360" data-name="Group 5360" transform="translate(0 0)">
                                                                    <path id="Path_929" data-name="Path 929"
                                                                        d="M95.878,118.573a21.784,21.784,0,0,0-17.26,12.294c-5.977,11.8-12.148,8.115-15.793,18.757s3.7,22.744,8.631,24.055,61.2,0,61.2,0,7.435-5.083,8.018-18.286-10.5-17.613-15.088-19.49S113.915,118.281,95.878,118.573Z"
                                                                        transform="translate(-57.308 -114.454)" fill="#f3f5f4" />
                                                                    <path id="Path_930" data-name="Path 930"
                                                                        d="M107.429,223.419c13-8.745,15.517-13.235,21.932-14.07,15.625-2.034,4.177,38.356-31.6,46.987-17,4.1-38.058-7.084-41.07-15.774-2.912-8.4,4.927-11.553,16.5-7.047S100.807,227.875,107.429,223.419Z"
                                                                        transform="translate(-52.692 -187.036)" fill="none"
                                                                        stroke="#bbbfbd" stroke-width="1" />
                                                                </g>
                                                            </g>
                                                        </g>
                                                    </g>
                                                </g>
                                            </svg>
                                        </div>
                                    </div>
                                </section>
                            </aside>
                        </div>
                    <?php endif; // End of is_expired check 
                    ?>
                <?php else: ?>
                    <div class="amadex-card amadex-confirmation-empty">
                        <h2><?php esc_html_e('Still waiting on your reference?', 'amadex'); ?></h2>
                        <p><?php esc_html_e('As soon as your payment is authorized you will receive an email and SMS with your booking reference. Use that link to review the itinerary any time.', 'amadex'); ?>
                        </p>
                        <p><?php esc_html_e('If you believe this is an error, please reach out with the cardholder name and payment time so we can locate the request.', 'amadex'); ?>
                        </p>
                        <div class="amadex-empty-actions">
                            <a class="amadex-button"
                                href="tel:<?php echo esc_attr(preg_replace('/[^0-9\+]/', '', $support_phone)); ?>">
                                <?php esc_html_e('Call Support', 'amadex'); ?>
                            </a>
                            <a class="amadex-button amadex-button-outline" href="mailto:<?php echo esc_attr($support_email); ?>">
                                <?php esc_html_e('Email Support', 'amadex'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    // Smooth scroll to top on page load
                    $('html, body').animate({
                        scrollTop: 0
                    }, 300);

                    // Collapsible flight card functionality (matching booking page)
                    $('.amadex-flight-card-header[data-toggle]').on('click', function() {
                        var toggleId = $(this).data('toggle');
                        var $content = $('#' + toggleId + '-content');
                        var $chevron = $(this).find('.amadex-chevron-icon');
                        var $card = $(this).closest('.amadex-flight-detail-card');

                        if ($content.is(':visible')) {
                            $content.slideUp(300);
                            $chevron.css('transform', 'rotate(0deg)');
                            $card.removeClass('is-expanded');
                        } else {
                            $content.slideDown(300);
                            $chevron.css('transform', 'rotate(180deg)');
                            $card.addClass('is-expanded');
                        }
                    });

                    // Smooth animations for cards on scroll
                    if (typeof IntersectionObserver !== 'undefined') {
                        const observerOptions = {
                            threshold: 0.1,
                            rootMargin: '0px 0px -50px 0px'
                        };

                        const observer = new IntersectionObserver(function(entries) {
                            entries.forEach(function(entry) {
                                if (entry.isIntersecting) {
                                    entry.target.style.opacity = '1';
                                    entry.target.style.transform = 'translateY(0)';
                                }
                            });
                        }, observerOptions);

                        // Observe all cards
                        $('.amadex-card').each(function() {
                            $(this).css({
                                'opacity': '0',
                                'transform': 'translateY(20px)',
                                'transition': 'opacity 0.6s ease, transform 0.6s ease'
                            });
                            observer.observe(this);
                        });
                    }

                    // Add smooth hover effects
                    $('.amadex-card').hover(
                        function() {
                            $(this).css('transform', 'translateY(-2px)');
                        },
                        function() {
                            $(this).css('transform', 'translateY(0)');
                        }
                    );
                });
            </script>
        <?php
        return ob_get_clean();
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
     * Get airline logo URL from Amadeus API airline codes (IATA format)
     * Uses IATA codes from Amadeus API response to fetch logos
     */
    private function get_airline_logo_url($airline_code)
    {
        // Normalize airline code - ensure uppercase and remove whitespace (IATA standard)
        if (empty($airline_code)) {
            return $this->get_airline_logo_placeholder($airline_code);
        }

        $normalized_code = strtoupper(trim($airline_code));

        // Primary source: Kiwi.com CDN (most reliable for Amadeus IATA airline codes)
        // This matches the airline codes from Amadeus API (IATA format)
        return 'https://images.kiwi.com/airlines/64/' . $normalized_code . '.png';
    }

    /**
     * Get fallback airline logo URL
     */
    private function get_airline_logo_fallback($airline_code)
    {
        if (empty($airline_code)) {
            return $this->get_airline_logo_placeholder($airline_code);
        }

        $normalized_code = strtoupper(trim($airline_code));

        // Fallback to Google Flights CDN (highly reliable secondary source)
        return 'https://www.gstatic.com/flights/airline_logos/70px/' . $normalized_code . '.png';
    }

    /**
     * Get placeholder logo SVG
     */
    private function get_airline_logo_placeholder($airline_code)
    {
        $code = !empty($airline_code) ? strtoupper(trim($airline_code)) : 'N/A';
        return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><rect fill="#e0e0e0" width="64" height="64"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial" font-size="12" fill="#999">' . esc_html($code) . '</text></svg>');
    }

    /**
     * Get airport info (city name) from IATA code
     * Matches the JavaScript getAirportInfo function
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

        // Return default if not found
        return array('city' => $iata_code, 'airport' => $iata_code);
    }

    /**
     * Format currency code to symbol
     * 
     * @param string $currency_code Currency code (e.g., 'USD', 'EUR')
     * @return string Currency symbol (e.g., '$', '€')
     */
    private function format_currency_symbol($currency_code)
    {
        // Use Amadex_Currency class if available for consistent formatting
        if (class_exists('Amadex_Currency') && method_exists('Amadex_Currency', 'get_currency_symbol')) {
            try {
                return Amadex_Currency::get_currency_symbol($currency_code);
            } catch (Exception $e) {
                // Fall through to fallback
            }
        }

        // Fallback to basic map
        $currency_map = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        );

        $code = strtoupper(trim($currency_code));
        return isset($currency_map[$code]) ? $currency_map[$code] : $code . ' ';
    }

    /**
     * Get price breakdown from booking/flight data
     * Extracts adjusted prices (with markup/discount) from flight_data
     * Matches exactly what the booking page shows (proportional discount/markup)
     * 
     * @param array $booking Booking data
     * @return array Price breakdown: ['base_fare' => float, 'taxes' => float, 'premium_service' => float, 'total' => float, 'currency' => string]
     */
    private function get_price_breakdown($booking)
    {
        // Use unified price breakdown function (matches JavaScript logic exactly)
        // This ensures consistency between booking page, confirmation page, and emails
        return Amadex_Pricing::get_unified_price_breakdown($booking);
    }

    /**
     * @deprecated This function is kept for backward compatibility but now uses unified function
     * The actual calculation is done in Amadex_Pricing::get_unified_price_breakdown()
     */
    private function get_price_breakdown_legacy($booking)
    {
        // PRIORITY 1: Use stored total_amount from booking (same as NMI transaction)
        // This ensures total matches exactly what was sent to NMI
        $stored_total = floatval($booking['total_amount'] ?? 0);
        $currency = $booking['currency'] ?? 'USD';

        // Get flight_data (contains original and adjusted prices)
        // This is the EXACT same data structure used by the booking page JavaScript
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
        // P_charge already includes: original_price + markup + flat_fee
        // We need to break down P_charge into base_fare + taxes (absorbing markup and flat_fee)
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
            // This ratio will be used to split base_total into base_fare and taxes
            $base_ratio = ($original_total > 0) ? ($original_base / $original_total) : 0.9; // Default 90% base, 10% taxes

            // base_total = stored_total - premium_service - seat_charges
            // This is the flight price (base + taxes) that we need to split
            // Break down base_total using original ratio (maintains proportion from original prices)
            $final_base = round($base_total * $base_ratio, 2);
            $final_taxes = round($base_total - $final_base, 2);

            // The flat_fee from pricing rules engine should be absorbed into taxes
            // This ensures the breakdown matches what was calculated during booking
            if ($flat_fee_amount > 0) {
                // Add flat fee to taxes (absorbed, not shown separately)
                $final_taxes = round($final_taxes + $flat_fee_amount, 2);
            }

            // Recalculate base if taxes are negative (safety check)
            if ($final_taxes < 0) {
                $final_taxes = round($base_total * 0.10, 2);
                $final_base = round($base_total - $final_taxes, 2);
            }

            // Ensure final_base + final_taxes = base_total (accounting for rounding)
            $calculated_base_total = $final_base + $final_taxes;
            if (abs($calculated_base_total - $base_total) > 0.01) {
                // Adjust to match exactly (small rounding differences)
                $difference = $base_total - $calculated_base_total;
                $final_taxes = round($final_taxes + $difference, 2);
            }

            // Verify total: base_fare + taxes + premium_service + seat_charges = stored_total
            $calculated_final_total = $final_base + $final_taxes + ($premium_service_added ? $premium_service_amount : 0) + $seat_charges;
            if (abs($calculated_final_total - $stored_total) > 0.01) {
                // Adjust taxes to match stored_total exactly
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
                'premium_service_added' => $return_premium_added, // Flag to indicate if premium service was added
                'seat_selection' => round($seat_charges, 2),
                'total' => round($stored_total, 2), // P_charge (includes markup + flat fee, absorbed into base/taxes)
                'original_total' => 0, // Don't show original price
                'markup' => 0, // Don't show markup separately
                'flat_fee' => 0, // Don't show flat fee separately
                'markup_enabled' => false, // Hide markup from user
                'discount' => 0,
                'discount_enabled' => false, // Hide discount from user
                'currency' => $currency
            );
        }

        // PRIORITY 4: Check if flight_data has already-calculated prices from booking page
        // The booking page JavaScript calculates prices and may store them - use them if available
        if (isset($flight_data['price']) && is_array($flight_data['price'])) {
            $currency = $flight_data['price']['currency'] ?? $currency;

            // Check for stored calculated prices (exact prices shown on booking page)
            // These would be stored after JavaScript calculatePriceWithMarkup() runs
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

        // PRIORITY 5: Fallback to legacy calculation if stored prices not available
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

        // Recalculate using SAME logic as booking page JavaScript
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

                $markup_amount = 0;
                $final_total_with_markup = $stored_total;

                if (!$use_rules_engine) {
                    // Use legacy markup system
                    $pricing_settings = get_option('amadex_pricing_settings', array());
                    $markup_enabled = isset($pricing_settings['enable_confirmation_discount']) && $pricing_settings['enable_confirmation_discount'] == 1;
                    $markup_percentage = isset($pricing_settings['confirmation_discount_percentage']) ? floatval($pricing_settings['confirmation_discount_percentage']) : 10;

                    $original_total_before_markup = $base_total + ($premium_service_added ? $premium_service_amount : 0);

                    if ($markup_enabled && $markup_percentage > 0 && $original_total_before_markup > 0) {
                        $markup_amount = round($original_total_before_markup * ($markup_percentage / 100), 2);
                        $final_total_with_markup = $stored_total;
                    }
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
                    'premium_service_added' => $return_premium_added, // Flag to indicate if premium service was added
                    'seat_selection' => round($seat_charges, 2),
                    'total' => round($stored_total, 2), // Use stored_total (includes premium service + seat charges, same as NMI)
                    'original_total' => round($original_total_before_markup, 2), // Original price before markup/flat fee (for dashboard)
                    'markup' => round($markup_amount, 2), // Markup amount (for dashboard only, not shown to user)
                    'flat_fee' => round($flat_fee_amount, 2), // Flat fee from pricing rules (for dashboard only, not shown to user)
                    'markup_enabled' => false, // Don't show markup/discount breakdown to users
                    'discount' => 0,
                    'discount_enabled' => false, // Don't show discount breakdown to users
                    'currency' => $currency
                );
            } elseif ($calculated_total > 0) {
                // Use calculated values if stored_total not available
                $calculated_total_with_premium = $calculated_total + ($premium_service_added ? $premium_service_amount : 0);

                // Calculate markup (10% added to original price, but not shown to user)
                $pricing_settings = get_option('amadex_pricing_settings', array());
                $markup_enabled = isset($pricing_settings['enable_confirmation_discount']) && $pricing_settings['enable_confirmation_discount'] == 1;
                $markup_percentage = isset($pricing_settings['confirmation_discount_percentage']) ? floatval($pricing_settings['confirmation_discount_percentage']) : 10;

                $original_total_before_markup = $calculated_total_with_premium;
                $markup_amount = 0;
                $final_total_with_markup = $calculated_total_with_premium;

                if ($markup_enabled && $markup_percentage > 0) {
                    $markup_amount = round($calculated_total_with_premium * ($markup_percentage / 100), 2);
                    $final_total_with_markup = round($calculated_total_with_premium + $markup_amount, 2);
                }

                return array(
                    'base_fare' => round($calculated_base, 2),
                    'taxes' => round($calculated_taxes, 2),
                    'premium_service' => $premium_service_added ? round($premium_service_amount, 2) : 0,
                    'premium_service_added' => $premium_service_added, // Flag to indicate if premium service was added
                    'seat_selection' => round($seat_charges, 2),
                    'total' => round($final_total_with_markup, 2),
                    'original_total' => round($original_total_before_markup, 2),
                    'markup' => round($markup_amount, 2),
                    'markup_enabled' => $markup_enabled,
                    'discount' => 0,
                    'discount_enabled' => false,
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

                // Absorb flat fee into taxes (don't show separately to users)
                $adjusted_taxes = round($adjusted_taxes + $flat_fee_amount, 2);
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

            return array(
                'base_fare' => round($adjusted_base, 2),
                'taxes' => round($adjusted_taxes, 2),
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
     * Render Regional Settings Button Shortcode
     * Usage: [amadex_regional_settings]
     * 
     * When Regional Settings System is disabled, this shortcode returns empty string
     * (button is hidden), ensuring the UI doesn't show regional settings options.
     * 
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string HTML output or empty string if regional settings disabled
     * 
     * @example
     * // Display regional settings button (only if enabled)
     * echo do_shortcode('[amadex_regional_settings]');
     * // Returns empty string if regional settings disabled
     */
    // public function render_regional_settings_button($atts)
    // {
    //     if (class_exists('Amadex_Currency')) {

    public function auto_inject_regional_button()
    {
        if (defined('REST_REQUEST') && REST_REQUEST) return;
        if (is_admin()) return;
        // Only inject if regional settings are enabled
        if (class_exists('Amadex_Currency') && !Amadex_Currency::is_regional_settings_enabled()) return;
        // Only inject if the shortcode was NOT already rendered on this page
        if (did_action('amadex_regional_button_rendered')) return;
        // Output the button + modal into the footer
        echo do_shortcode('[amadex_regional_settings position="floating" variant="capsule"]');
    }

    public function render_regional_settings_button($atts)
    {
        if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin()) return '';
        // Check if regional settings system is enabled (using cached helper method)
        if (class_exists('Amadex_Currency')) {
            if (!Amadex_Currency::is_regional_settings_enabled()) {
                // Regional settings disabled - return empty string (hide button)
                amadex_log('Regional Settings System disabled - hiding regional settings button shortcode');
                return '';
            }
        }

        $atts = shortcode_atts(array(
            'style' => 'button', // 'button' or 'text'
            'position' => 'header', // 'header', 'inline', 'floating'
            'mode' => 'both', // 'button', 'modal', 'both'
            'variant' => 'capsule' // 'capsule' or 'default'
        ), $atts);

        // Get current regional settings
        $current_language = 'en-US';
        $current_country = 'US';
        $current_currency = 'USD';
        $language_name = 'English (United States)';
        $country_name = 'United States';
        $currency_symbol = '$';

        if (class_exists('Amadex_Currency')) {
            $regional_settings = Amadex_Currency::get_user_regional_settings();
            $current_language = $regional_settings['language'];
            $current_country = $regional_settings['country'];
            $current_currency = $regional_settings['currency'];
            $language_name = $regional_settings['language_name'];
            $country_name = $regional_settings['country_name'];
            $currency_symbol = $regional_settings['currency_symbol'];
        }

        $country_code = strtoupper($current_country);
        $currency_code = strtoupper($current_currency);
        $language_code = strtoupper(substr($current_language, 0, 2));

        // Include modal template
        ob_start();
        include AMADEX_PATH . 'templates/regional-settings-modal.php';
        $modal_html = ob_get_clean();

        // Build button HTML
        $button_class = 'amadex-regional-settings-btn';
        if ($atts['position'] === 'floating') {
            $button_class .= ' amadex-regional-settings-btn-floating';
        } elseif ($atts['position'] === 'header') {
            $button_class .= ' amadex-regional-settings-btn-header';
        }

        if ($atts['variant'] === 'capsule') {
            $button_class .= ' amadex-regional-settings-btn--capsule';
            $button_html = '<button type="button" class="' . esc_attr($button_class) . ' amadex-regional-settings-trigger" id="amadex-regional-settings-trigger" data-testid="header-culture-selector-button" aria-label="Regional settings: ' . esc_attr($country_name) . ', ' . esc_attr($currency_code) . ', ' . esc_attr($language_code) . '">';
            $button_html .= '<span class="amadex-regional-settings-code amadex-regional-settings-code--country">' . esc_html($country_code) . '</span>';
            $button_html .= '<span class="amadex-regional-settings-divider">|</span>';
            $button_html .= '<span class="amadex-regional-settings-code amadex-regional-settings-code--currency">' . esc_html($currency_code) . '</span>';
            $button_html .= '<span class="amadex-regional-settings-divider">|</span>';
            $button_html .= '<span class="amadex-regional-settings-code amadex-regional-settings-code--language">' . esc_html($language_code) . '</span>';
            $button_html .= '<span class="screen-reader-text">' . esc_html($language_name) . ' ' . esc_html($country_name) . ' ' . esc_html($currency_code) . '</span>';
            $button_html .= '</button>';
        } else {
            $button_html = '<button type="button" class="' . esc_attr($button_class) . ' amadex-regional-settings-trigger" id="amadex-regional-settings-trigger" data-testid="header-culture-selector-button">';
            $button_html .= '<div class="amadex-regional-settings-labels">';
            $button_html .= '<span class="amadex-regional-settings-language">' . esc_html($language_name) . '</span>';
            $button_html .= '<span class="amadex-regional-settings-country">' . esc_html($country_name) . '</span>';
            $button_html .= '<span class="amadex-regional-settings-currency">' . esc_html($current_currency) . '</span>';
            $button_html .= '</div>';
            $button_html .= '</button>';
        }

        // Use static flag to track if modal has been included to prevent duplicates
        static $modal_included = false;

        if ($atts['mode'] === 'modal') {
            // If modal mode, always return modal HTML (even if already included elsewhere)
            // This allows explicit modal placement in header.php
            return $modal_html;
        }

        if ($atts['mode'] === 'button') {
            // CRITICAL FIX: Always include modal HTML directly with button
            // This ensures modal is in DOM immediately when button is rendered
            // Only include modal once to prevent duplicates
            $output = $button_html;
            if (!$modal_included) {
                $modal_included = true;
                // Include modal HTML directly after button
                // This ensures it's available when button is clicked
                $output .= "\n" . '<!-- Amadex Regional Settings Modal (included with button) -->' . "\n";
                $output .= $modal_html;
            } else {
                // Modal already included elsewhere, add comment for debugging
                $output .= "\n" . '<!-- Amadex Regional Settings Modal already included -->' . "\n";
            }
            return $output;
        }

        // Default: both button and modal
        $modal_included = true;
        return $button_html . "\n" . '<!-- Amadex Regional Settings Modal -->' . "\n" . $modal_html;
    }


    /**
     * Render payment page (Stripe only - separate page flow)
     */
    // public function render_payment_page($atts = array())
    // {
    //     $payment_settings = get_option('amadex_payment_settings', array());

    public function render_payment_page($atts = array())
    {
        if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin()) return '';
        // Only show if Stripe is the default gateway (normalize to lowercase)
        $payment_settings = get_option('amadex_payment_settings', array());
        $default_gateway = isset($payment_settings['default_card_gateway']) ? strtolower(trim($payment_settings['default_card_gateway'])) : 'nmi';

        if ($default_gateway !== 'stripe') {
            // If not Stripe, redirect to booking page or show message
            return '<div class="amadex-payment-error"><p>Payment page is only available when Stripe is selected as the payment gateway.</p></div>';
        }

        // Get booking token from URL
        $booking_token = isset($_GET['st']) ? sanitize_text_field($_GET['st']) : '';

        if (empty($booking_token)) {
            return '<div class="amadex-payment-error"><p>Invalid payment session. Please start your booking again.</p><p><a href="' . esc_url(home_url('/flight-booking/')) . '">Return to Booking</a></p></div>';
        }

        // Retrieve booking data
        $booking_data = $this->get_booking_data_by_token($booking_token);

        if (!$booking_data) {
            return '<div class="amadex-payment-error"><p>Booking session expired or not found. Please start your booking again.</p><p><a href="' . esc_url(home_url('/flight-booking/')) . '">Return to Booking</a></p></div>';
        }

        // Check if booking data is expired (60 minutes)
        $created_time = isset($booking_data['created_at']) ? intval($booking_data['created_at']) : 0;
        $expiry_time = $created_time + (60 * 60); // 60 minutes
        if (time() > $expiry_time) {
            // Delete expired booking
            delete_transient('amadex_booking_' . $booking_token);
            return '<div class="amadex-payment-error"><p>Your booking session has expired. Please start your booking again.</p><p><a href="' . esc_url(home_url('/flight-booking/')) . '">Return to Booking</a></p></div>';
        }

        // Enqueue payment page specific assets
        // wp_enqueue_style('amadex-payment-page', AMADEX_URL . 'assets/css/amadex-payment-page.css', array('amadex-booking'), AMADEX_VERSION);
        // wp_enqueue_script('amadex-payment-page', AMADEX_URL . 'assets/js/amadex-payment-page.js', array('jquery', 'amadex-booking'), AMADEX_VERSION, true);

        // Enqueue Stripe.js v3 (required for redirectToCheckout fallback, though we primarily use direct URL redirect)
        $stripe_publishable_key = isset($payment_settings['stripe_publishable_key']) ? trim($payment_settings['stripe_publishable_key']) : '';
        if (empty($stripe_publishable_key)) {
            // Log warning if key is missing
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex Payment Page: Stripe Publishable Key is not set. Please configure it in Amadex Settings → Payment Settings.');
            }
        } else {
            // Use standard Stripe.js v3 for Checkout redirect flow
            wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, false);
            wp_localize_script('amadex-payment-page', 'AmadexStripe', array(
                'publishableKey' => $stripe_publishable_key,
                'mode' => isset($payment_settings['stripe_mode']) ? $payment_settings['stripe_mode'] : 'test'
            ));

            // Log for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Amadex Payment Page: Stripe.js v3 enqueued. Publishable Key: ' . substr($stripe_publishable_key, 0, 7) . '...');
            }
        }

        // Localize booking data for JavaScript
        wp_localize_script('amadex-payment-page', 'AmadexPaymentData', array(
            'bookingToken' => $booking_token,
            'bookingData' => $booking_data,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amadex_nonce'),
            'confirmationUrl' => isset($booking_data['confirmation_url']) ? $booking_data['confirmation_url'] : home_url('/booking-confirmation/')
        ));

        // Extract data for template
        $flight_data = isset($booking_data['flight']) ? $booking_data['flight'] : array();
        $passengers = isset($booking_data['passengers']) ? $booking_data['passengers'] : array();
        $contact = isset($booking_data['contact']) ? $booking_data['contact'] : array();
        $billing = isset($booking_data['billing']) ? $booking_data['billing'] : array();
        $pricing = isset($booking_data['pricing']) ? $booking_data['pricing'] : array();

        // Calculate totals - CRITICAL: Use exact total from stored booking data (includes addons, seats, premium)
        $fare_total = isset($pricing['fare']) ? floatval($pricing['fare']) : 0;
        $tax_total = isset($pricing['tax']) ? floatval($pricing['tax']) : 0;
        $surcharge = isset($pricing['surcharge']) ? floatval($pricing['surcharge']) : 0;
        $addons_total = isset($pricing['addons']) ? floatval($pricing['addons']) : 0;
        $seat_charges = isset($pricing['seat_charges']) ? floatval($pricing['seat_charges']) : 0;
        $premium_service = isset($pricing['premium_service']) ? floatval($pricing['premium_service']) : 0;

        // Use exact total if provided (includes all components)
        $grand_total = isset($pricing['total']) ? floatval($pricing['total']) : 0;

        // If total not provided, calculate from components
        if ($grand_total <= 0) {
            $grand_total = $fare_total + $tax_total + $surcharge + $addons_total + $seat_charges + $premium_service;
        }

        // Start output buffering
        ob_start();
        ?>
            <div class="amadex-payment-page-container">
                <!-- Header Section -->
                <div class="amadex-payment-header">
                    <div class="amadex-payment-header-top">
                        <div class="amadex-payment-logo">
                            <a href="<?php echo esc_url(home_url('/')); ?>">
                                <?php echo esc_html(get_bloginfo('name')); ?>
                            </a>
                        </div>
                        <div class="amadex-payment-timer" id="amadex-booking-timer">
                            <span class="timer-icon">⏱</span>
                            <span class="timer-text" id="timer-display">60:00</span>
                        </div>
                        <div class="amadex-payment-total">
                            <span class="total-label">Total</span>
                            <span class="total-amount"
                                id="payment-total-amount">$<?php echo number_format($grand_total, 2); ?></span>
                            <button class="view-summary-btn" id="mobile-view-summary">View summary</button>
                        </div>
                    </div>
                    <div class="amadex-payment-route-summary">
                        <?php
                        if (!empty($flight_data['itineraries'][0]['segments'][0])) {
                            $first_segment = $flight_data['itineraries'][0]['segments'][0];
                            $dep_code = isset($first_segment['departure']['iataCode']) ? $first_segment['departure']['iataCode'] : (isset($first_segment['departure']['iata_code']) ? $first_segment['departure']['iata_code'] : '');
                            $arr_code = isset($first_segment['arrival']['iataCode']) ? $first_segment['arrival']['iataCode'] : (isset($first_segment['arrival']['iata_code']) ? $first_segment['arrival']['iata_code'] : '');
                            $dep_date = isset($first_segment['departure']['at']) ? date('D M d', strtotime($first_segment['departure']['at'])) : '';
                            $passenger_count = count($passengers);
                            $passenger_text = $passenger_count == 1 ? '1 Adult' : $passenger_count . ' Passengers';
                        ?>
                            <div class="route-info">
                                <span class="route-codes"><?php echo esc_html($dep_code); ?> →
                                    <?php echo esc_html($arr_code); ?></span>
                                <span class="route-separator">•</span>
                                <span class="route-date"><?php echo esc_html($dep_date); ?></span>
                                <span class="route-separator">•</span>
                                <span class="route-passengers"><?php echo esc_html($passenger_text); ?></span>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- Main Content: Two-column on desktop, single-column on mobile -->
                <div class="amadex-payment-content">
                    <!-- Left Column: Flight Summary (Desktop) / Collapsible (Mobile) -->
                    <div class="amadex-payment-left-column">
                        <!-- Mobile Close Button -->
                        <button class="mobile-summary-close" id="mobile-summary-close" aria-label="Close summary">
                            <span class="close-icon">✕</span>
                            <span class="close-text">Close</span>
                        </button>

                        <!-- Summary Section -->
                        <div class="amadex-payment-summary-section">
                            <h3 class="summary-title">Summary</h3>
                            <div class="summary-content">
                                <div class="summary-row">
                                    <span class="summary-label">Fare (inc Tax):</span>
                                    <span
                                        class="summary-value">$<?php echo number_format($fare_total + $tax_total, 2); ?></span>
                                </div>
                                <?php if ($surcharge > 0): ?>
                                    <div class="summary-row">
                                        <span class="summary-label">Surcharge:</span>
                                        <span class="summary-value">$<?php echo number_format($surcharge, 2); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="summary-row summary-total">
                                    <span class="summary-label">Total:</span>
                                    <span class="summary-value">$<?php echo number_format($grand_total, 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Flight Details Section -->
                        <div class="amadex-payment-flight-section">
                            <h3 class="section-title">Flight details</h3>
                            <div class="flight-details-content">
                                <?php
                                if (!empty($flight_data['itineraries'][0])) {
                                    $itinerary = $flight_data['itineraries'][0];
                                    $segment_index = 0;

                                    foreach ($itinerary['segments'] as $segment) {
                                        $dep = $segment['departure'];
                                        $arr = $segment['arrival'];

                                        // Carrier and flight number
                                        $carrier_code = isset($segment['carrierCode']) ? $segment['carrierCode'] : (isset($segment['carrier_code']) ? $segment['carrier_code'] : '');
                                        $flight_number = isset($segment['number']) ? $segment['number'] : '';

                                        // Cabin class
                                        $cabin = isset($segment['cabin']) ? ucfirst(strtolower($segment['cabin'])) : 'Economy';

                                        // Airport codes
                                        $dep_code = isset($dep['iataCode']) ? $dep['iataCode'] : (isset($dep['iata_code']) ? $dep['iata_code'] : '');
                                        $arr_code = isset($arr['iataCode']) ? $arr['iataCode'] : (isset($arr['iata_code']) ? $arr['iata_code'] : '');

                                        // Airport names
                                        $dep_name = '';
                                        if (isset($dep['airport']) && isset($dep['airport']['name'])) {
                                            $dep_name = $dep['airport']['name'];
                                        } elseif (isset($dep['name'])) {
                                            $dep_name = $dep['name'];
                                        }

                                        $arr_name = '';
                                        if (isset($arr['airport']) && isset($arr['airport']['name'])) {
                                            $arr_name = $arr['airport']['name'];
                                        } elseif (isset($arr['name'])) {
                                            $arr_name = $arr['name'];
                                        }

                                        // Times and dates
                                        $dep_time = '';
                                        $dep_timestamp = null;
                                        if (isset($dep['at']) && !empty($dep['at'])) {
                                            $dep_timestamp = strtotime($dep['at']);
                                            if ($dep_timestamp !== false) {
                                                $dep_time = date('H:i', $dep_timestamp);
                                            }
                                        }

                                        $arr_time = '';
                                        $arr_timestamp = null;
                                        if (isset($arr['at']) && !empty($arr['at'])) {
                                            $arr_timestamp = strtotime($arr['at']);
                                            if ($arr_timestamp !== false) {
                                                $arr_time = date('H:i', $arr_timestamp);
                                            }
                                        }

                                        $dep_date = '';
                                        if ($dep_timestamp !== null) {
                                            $dep_date = date('D, d M', $dep_timestamp);
                                        }

                                        // Terminals
                                        $dep_terminal = isset($dep['terminal']) ? $dep['terminal'] : '';
                                        $arr_terminal = isset($arr['terminal']) ? $arr['terminal'] : '';

                                        // Aircraft type
                                        $aircraft = '';
                                        if (isset($segment['aircraft']) && isset($segment['aircraft']['code'])) {
                                            $aircraft = $segment['aircraft']['code'];
                                        } elseif (isset($segment['aircraft_code'])) {
                                            $aircraft = $segment['aircraft_code'];
                                        }

                                        // Duration
                                        $duration = '';
                                        if (isset($segment['duration'])) {
                                            $duration = $segment['duration'];
                                        } elseif ($dep_timestamp !== null && $arr_timestamp !== null && $arr_timestamp > $dep_timestamp) {
                                            $diff_seconds = $arr_timestamp - $dep_timestamp;
                                            $hours = floor($diff_seconds / 3600);
                                            $minutes = floor(($diff_seconds % 3600) / 60);
                                            $duration = $hours . 'h ' . $minutes . 'm';
                                        }

                                        // Layover (if not first segment)
                                        $show_layover = false;
                                        $layover_duration = '';
                                        $layover_airport = '';
                                        if ($segment_index > 0 && isset($itinerary['segments'][$segment_index - 1])) {
                                            $prev_segment = $itinerary['segments'][$segment_index - 1];
                                            if (isset($prev_segment['arrival']['at']) && isset($dep['at']) && !empty($prev_segment['arrival']['at']) && !empty($dep['at'])) {
                                                $prev_arrival = strtotime($prev_segment['arrival']['at']);
                                                $next_departure = strtotime($dep['at']);
                                                if ($prev_arrival !== false && $next_departure !== false && $next_departure > $prev_arrival) {
                                                    $layover_seconds = $next_departure - $prev_arrival;
                                                    if ($layover_seconds > 0) {
                                                        $layover_hours = floor($layover_seconds / 3600);
                                                        $layover_mins = floor(($layover_seconds % 3600) / 60);
                                                        $layover_duration = $layover_hours . 'h ' . $layover_mins . 'm';
                                                        $show_layover = true;
                                                        $layover_airport = isset($prev_segment['arrival']['iataCode']) ? $prev_segment['arrival']['iataCode'] : (isset($prev_segment['arrival']['iata_code']) ? $prev_segment['arrival']['iata_code'] : $arr_code);
                                                    }
                                                }
                                            }
                                        }

                                        if ($show_layover && !empty($layover_duration) && !empty($layover_airport)) {
                                ?>
                                            <div class="flight-layover">
                                                <span class="layover-icon">⏱</span>
                                                <span class="layover-text">Layover in <?php echo esc_html($layover_airport); ?>:
                                                    <?php echo esc_html($layover_duration); ?></span>
                                            </div>
                                        <?php
                                        }
                                        ?>
                                        <div class="flight-segment">
                                            <div class="flight-airline-code">
                                                <?php echo esc_html($carrier_code . ' ' . $flight_number); ?></div>
                                            <?php if (!empty($aircraft)): ?>
                                                <div class="flight-aircraft">Aircraft: <?php echo esc_html($aircraft); ?></div>
                                            <?php endif; ?>
                                            <div class="flight-cabin"><?php echo esc_html($cabin); ?></div>
                                            <div class="flight-route">
                                                <div class="flight-departure">
                                                    <span class="flight-time"><?php echo esc_html($dep_time); ?></span>
                                                    <span class="flight-airport-code"><?php echo esc_html($dep_code); ?></span>
                                                    <?php if (!empty($dep_name)): ?>
                                                        <span class="flight-airport-name"><?php echo esc_html($dep_name); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($dep_terminal)): ?>
                                                        <span class="flight-terminal">Terminal <?php echo esc_html($dep_terminal); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($duration)): ?>
                                                    <div class="flight-duration-info">
                                                        <span class="flight-duration"><?php echo esc_html($duration); ?></span>
                                                        <span class="flight-arrow">→</span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="flight-arrow">→</div>
                                                <?php endif; ?>
                                                <div class="flight-arrival">
                                                    <span class="flight-time"><?php echo esc_html($arr_time); ?></span>
                                                    <span class="flight-airport-code"><?php echo esc_html($arr_code); ?></span>
                                                    <?php if (!empty($arr_name)): ?>
                                                        <span class="flight-airport-name"><?php echo esc_html($arr_name); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($arr_terminal)): ?>
                                                        <span class="flight-terminal">Terminal <?php echo esc_html($arr_terminal); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flight-date"><?php echo esc_html($dep_date); ?></div>
                                        </div>
                                <?php
                                        $segment_index++;
                                    }
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Passengers Section -->
                        <div class="amadex-payment-passengers-section">
                            <h3 class="section-title">Passengers</h3>
                            <div class="passengers-content">
                                <?php
                                foreach ($passengers as $index => $passenger) {
                                    // Handle both naming conventions (first_name/last_name and firstname/lastname)
                                    $first_name = isset($passenger['first_name']) ? $passenger['first_name'] : (isset($passenger['firstname']) ? $passenger['firstname'] : '');
                                    $middle_name = isset($passenger['middle_name']) ? $passenger['middle_name'] : (isset($passenger['middlename']) ? $passenger['middlename'] : '');
                                    $last_name = isset($passenger['last_name']) ? $passenger['last_name'] : (isset($passenger['lastname']) ? $passenger['lastname'] : '');
                                    $full_name = trim($first_name . ' ' . ($middle_name ? $middle_name . ' ' : '') . $last_name);

                                    // Date of birth
                                    $dob = '';
                                    if (isset($passenger['date_of_birth']) && !empty($passenger['date_of_birth'])) {
                                        $dob_timestamp = strtotime($passenger['date_of_birth']);
                                        if ($dob_timestamp !== false) {
                                            $dob = date('d M Y', $dob_timestamp);
                                        }
                                    } elseif (isset($passenger['dob']) && is_array($passenger['dob'])) {
                                        // Handle structured DOB (day/month/year)
                                        $dob_day = isset($passenger['dob']['day']) ? $passenger['dob']['day'] : '';
                                        $dob_month = isset($passenger['dob']['month']) ? $passenger['dob']['month'] : '';
                                        $dob_year = isset($passenger['dob']['year']) ? $passenger['dob']['year'] : '';
                                        if ($dob_day && $dob_month && $dob_year) {
                                            $dob_timestamp = strtotime($dob_year . '-' . $dob_month . '-' . $dob_day);
                                            if ($dob_timestamp !== false) {
                                                $dob = date('d M Y', $dob_timestamp);
                                            }
                                        }
                                    }

                                    // Passenger type
                                    $type = isset($passenger['type']) ? ucfirst(strtolower($passenger['type'])) : 'Adult';

                                    // Gender (optional)
                                    $gender = '';
                                    if (isset($passenger['gender']) && !empty($passenger['gender'])) {
                                        $gender = ucfirst(strtolower($passenger['gender']));
                                    }

                                    // Nationality (optional)
                                    $nationality = isset($passenger['nationality']) ? $passenger['nationality'] : '';

                                ?>
                                    <div class="passenger-item">
                                        <div class="passenger-name"><?php echo esc_html($full_name); ?></div>
                                        <div class="passenger-details">
                                            <?php if (!empty($dob)): ?>
                                                <span class="passenger-dob"><?php echo esc_html($dob); ?></span>
                                            <?php endif; ?>
                                            <span class="passenger-type"><?php echo esc_html($type); ?>
                                                <?php echo ($index + 1); ?></span>
                                            <?php if (!empty($gender)): ?>
                                                <span class="passenger-gender"><?php echo esc_html($gender); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($nationality)): ?>
                                                <span class="passenger-nationality"><?php echo esc_html($nationality); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Payment Form -->
                    <div class="amadex-payment-right-column">
                        <!-- Payment Method Selector -->
                        <div class="amadex-payment-method-selector">
                            <div class="payment-method-display">
                                <span class="payment-method-icon">💳</span>
                                <span class="payment-method-name">Visa</span>
                            </div>
                            <button class="change-payment-method" id="change-payment-method">Change</button>
                        </div>

                        <!-- Payment Form -->
                        <div class="amadex-payment-form-container">
                            <h3 class="payment-form-title">Please enter your details below</h3>

                            <!-- Stripe Payment Element Container -->
                            <div id="stripe-payment-element-container">
                                <div id="stripe-payment-element">
                                    <!-- Stripe Payment Element will be mounted here -->
                                </div>
                                <div id="stripe-payment-element-errors" role="alert"></div>
                            </div>

                            <!-- Payment Button -->
                            <button type="button" id="amadex-payment-submit" class="amadex-payment-submit-btn">
                                Pay now & book your flights
                            </button>
                            <div id="amadex-payment-status-message" class="amadex-payment-status" role="status"
                                style="display: none; margin-top: 12px; padding: 12px; border-radius: 8px; font-weight: 500;">
                            </div>

                            <!-- Trust Indicators -->
                            <div class="amadex-payment-trust-indicators">
                                <div class="trust-badges">
                                    <span class="trust-badge">Protected & encrypted</span>
                                    <span class="trust-badge">Verified by Visa</span>
                                    <span class="trust-badge">Mastercard Securecode</span>
                                </div>
                            </div>
                        </div>

                        <!-- Help Section -->
                        <div class="amadex-payment-help">
                            <h4>Need help?</h4>
                            <?php
                            $general_settings = get_option('amadex_general_settings', array());
                            $support_phone = isset($general_settings['call_now_number']) ? sanitize_text_field($general_settings['call_now_number']) : '+1-866-960-2626';
                            ?>
                            <p class="help-phone"><?php echo esc_html($support_phone); ?></p>
                            <p class="help-hours">Lines are open 9am - 12am (GMT) 7 days a week</p>
                        </div>
                    </div>
                </div>
            </div>

            <script type="text/javascript">
                // Initialize booking timer (60 minutes countdown)
                (function() {
                    var expiryTime = <?php echo $expiry_time; ?>;
                    var timerElement = document.getElementById('timer-display');

                    function updateTimer() {
                        var now = Math.floor(Date.now() / 1000);
                        var remaining = expiryTime - now;

                        if (remaining <= 0) {
                            timerElement.textContent = '00:00';
                            alert('Your booking session has expired. Please start over.');
                            window.location.href = '<?php echo esc_url(home_url('/flight-booking/')); ?>';
                            return;
                        }

                        var minutes = Math.floor(remaining / 60);
                        var seconds = remaining % 60;
                        timerElement.textContent = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') +
                            seconds;
                    }

                    updateTimer();
                    setInterval(updateTimer, 1000);
                })();
            </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Store booking data temporarily for payment page
     */
    public function store_booking_data_for_payment($booking_data)
    {
        // Generate secure token
        $token = wp_generate_password(32, false);

        // Store in transient (expires in 60 minutes)
        $data_to_store = array(
            'flight' => isset($booking_data['flight']) ? $booking_data['flight'] : array(),
            'passengers' => isset($booking_data['passengers']) ? $booking_data['passengers'] : array(),
            'contact' => isset($booking_data['contact']) ? $booking_data['contact'] : array(),
            'billing' => isset($booking_data['billing']) ? $booking_data['billing'] : array(),
            'pricing' => isset($booking_data['pricing']) ? $booking_data['pricing'] : array(),
            'search_params' => isset($booking_data['search_params']) ? $booking_data['search_params'] : array(),
            'created_at' => time(),
            'confirmation_url' => isset($booking_data['confirmation_url']) ? $booking_data['confirmation_url'] : ''
        );

        set_transient('amadex_booking_' . $token, $data_to_store, 60 * 60); // 60 minutes

        return $token;
    }

    /**
     * Retrieve booking data by token
     */
    public function get_booking_data_by_token($token)
    {
        if (empty($token)) {
            return false;
        }

        $booking_data = get_transient('amadex_booking_' . $token);

        if (!$booking_data) {
            return false;
        }

        return $booking_data;
    }

    /**
     * Output the airport suggestion dropdown toggle script in the footer.
     * Handles multiple form instances, mutual exclusion with calendar/travellers,
     * outside-click, Escape key, and proper wrapper-level hit testing.
     */
    public function render_dropdown_script()
    {
        ?>
            <script>
                (function() {
                    'use strict';

                    function initAmadexDropdowns() {
                        // Support multiple form instances (homepage + results page)
                        var originInputs = document.querySelectorAll('#modern-origin');

                        originInputs.forEach(function(originInput) {
                            // Scope to the nearest flight segment or search container
                            var scope = originInput.closest('.amadex-flight-segment') ||
                                originInput.closest('.amadex-search-container') ||
                                originInput.closest('.amadex-search-modern') ||
                                document;

                            var destinationInput = scope.querySelector('#modern-destination');
                            var originDropdown = scope.querySelector('#origin-suggestions');
                            var destinationDropdown = scope.querySelector('#destination-suggestions');

                            if (!destinationInput || !originDropdown || !destinationDropdown) return;

                            var originWrap = originInput.closest('.amadex-modern-field') || originInput.parentElement;
                            var destinationWrap = destinationInput.closest('.amadex-modern-field') || destinationInput
                                .parentElement;

                            // ── helpers ──────────────────────────────────────────────
                            function closeAirportDropdowns() {
                                originDropdown.style.display = 'none';
                                destinationDropdown.style.display = 'none';
                            }

                            function closeCalendars() {
                                scope.querySelectorAll(
                                    '#departure-calendar, #return-calendar, .amadex-calendar-widget'
                                ).forEach(function(cal) {
                                    cal.classList.remove('active');
                                    cal.style.display = 'none';
                                });
                                scope.querySelectorAll(
                                    '#departure-field, #return-field, .amadex-date-field'
                                ).forEach(function(f) {
                                    f.classList.remove('field-active');
                                });
                            }

                            function closeTravellers() {
                                scope.querySelectorAll(
                                    '.amadex-travellers-dropdown'
                                ).forEach(function(dd) {
                                    dd.classList.remove('active');
                                });
                                scope.querySelectorAll(
                                    '#travellers-field, .amadex-travellers-field'
                                ).forEach(function(f) {
                                    f.classList.remove('field-active');
                                    f.classList.remove('active');
                                });
                            }

                            // ── open/close airport suggestions ───────────────────────
                            originInput.addEventListener('focus', function() {
                                closeCalendars();
                                closeTravellers();
                                originDropdown.style.display = 'block';
                                destinationDropdown.style.display = 'none';
                            });

                            destinationInput.addEventListener('focus', function() {
                                closeCalendars();
                                closeTravellers();
                                destinationDropdown.style.display = 'block';
                                originDropdown.style.display = 'none';
                            });

                            // Close suggestions after a suggestion is clicked
                            // (use mousedown so it fires before blur)
                            originDropdown.addEventListener('mousedown', function() {
                                setTimeout(closeAirportDropdowns, 150);
                            });
                            destinationDropdown.addEventListener('mousedown', function() {
                                setTimeout(closeAirportDropdowns, 150);
                            });

                            // ── outside-click ────────────────────────────────────────
                            document.addEventListener('click', function(e) {
                                var inOrigin = originWrap.contains(e.target) ||
                                    originDropdown.contains(e.target);
                                var inDestination = destinationWrap.contains(e.target) ||
                                    destinationDropdown.contains(e.target);
                                if (!inOrigin && !inDestination) {
                                    closeAirportDropdowns();
                                }
                            });

                            // ── Escape key ───────────────────────────────────────────
                            document.addEventListener('keydown', function(e) {
                                if (e.key === 'Escape') {
                                    closeAirportDropdowns();
                                    originInput.blur();
                                    destinationInput.blur();
                                }
                            });
                        });
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initAmadexDropdowns);
                    } else {
                        initAmadexDropdowns();
                    }
                })();

                const swapBtn = document.getElementById("swap-locations");
                if (swapBtn) {
                    const icon = swapBtn.querySelector("svg");
                    let rotation = 0;

                    swapBtn.addEventListener("click", () => {
                        rotation += 180;
                        if (icon) icon.style.transform = `rotate(${rotation}deg)`;
                        swapBtn.classList.add("active");
                        setTimeout(() => {
                            swapBtn.classList.remove("active");
                        }, 400);
                    });
                }

                document.addEventListener("click", function(e) {
                    const popup = document.querySelector(".amadex-suggestions-scroll");

                    if (!popup) return;

                    if (!popup.contains(e.target)) {
                        popup.style.display = "none";
                    }
                });

                document.addEventListener("click", function(e) {

                const searchContainer = document.querySelector(".amadex-search-container");
                const popups = document.querySelectorAll(".amadex-suggestions-scroll");

                if (!searchContainer || !searchContainer.contains(e.target)) {
                    popups.forEach(function(popup) {
                        popup.style.display = "none";
                    });
                }

                });
            </script>
        <?php
    }

    private function render_hotel_confirmation($lead, $reference)
    {
        $hd = json_decode($lead['hotel_data'] ?? '{}', true) ?: [];
        $hh = $hd['hotel']   ?? [];
        $hp = $hd['payment'] ?? [];
        $hg = $hh['room_guests'] ?? [];

        $general_settings = get_option('amadex_general_settings', []);
        $support_phone    = $general_settings['call_now_number'] ?? '+1-877-721-0410';

        ob_start(); ?>
            <div id="amadex-confirmation-page" class="amadex-confirmation-page">
                <div class="amadex-confirmation-nav">
                    <div class="amadex-booking-progress" data-booking-stage="booking">
                        <div class="booking-step is-active" data-step="booking">
                            <span class="booking-step-icon">🏨</span>
                            <span class="booking-step-label">Hotel Search</span>
                        </div>
                        <span class="booking-step-divider"></span>
                        <div class="booking-step is-active" data-step="passengers">
                            <span class="booking-step-icon">👤</span>
                            <span class="booking-step-label">Guest Details</span>
                        </div>
                        <span class="booking-step-divider"></span>
                        <div class="booking-step is-active" data-step="payment">
                            <span class="booking-step-icon">💳</span>
                            <span class="booking-step-label">Payment</span>
                        </div>
                    </div>
                </div>
                <div class="amadex-confirmation-content">
                    <div class="amadex-confirmation-main">
                        <div class="amadex-confirmation-greeting amadex-greeting-banner" style="background:#0e7d3f;border-radius:14px;padding:28px 32px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;grid-column:1/-1;">
                            <div>
                                <div style="font-size:13px;color:rgba(255,255,255,.75);margin-bottom:4px;">Booking Confirmed</div>
                                <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">
                                    <?php echo esc_html__('Thank you,', 'amadex'); ?> <?php echo esc_html($lead['contact_name']); ?>! 🎉
                                </h1>
                                <div style="margin-top:10px;font-size:13px;color:rgba(255,255,255,.85);">
                                    Confirmation sent to <strong><?php echo esc_html($lead['contact_email']); ?></strong>
                                </div>
                            </div>
                            <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:14px 20px;text-align:center;">
                                <div style="font-size:11px;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Booking Reference</div>
                                <div style="font-size:20px;font-weight:600;color:#fff;letter-spacing:1px;"><?php echo esc_html(strtoupper($reference)); ?></div>
                            </div>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin-bottom:16px;">
                            <h2 style="margin:0 0 18px;font-size:16px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">🏨 Hotel Details</h2>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;">
                                <?php foreach (['Hotel' => $hh['name'] ?? '—', 'Destination' => $hh['destination'] ?? '—', 'Room Type' => $hh['room_name'] ?? '—', 'Check-in' => $hh['check_in'] ?? '—', 'Check-out' => $hh['check_out'] ?? '—', 'Nights' => $hh['nights'] ?? '—', 'Rooms' => $hh['rooms'] ?? '—', 'Guests' => $hh['guests'] ?? '—'] as $label => $val): ?>
                                    <div style="padding:9px 0;border-bottom:1px solid #f8fafc;display:flex;gap:8px;">
                                        <span style="font-size:13px;color:#64748b;min-width:110px;"><?php echo esc_html($label); ?></span>
                                        <span style="font-size:13px;font-weight:600;color:#0f172a;"><?php echo esc_html($val); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php if (!empty($hg)): ?>
                            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin-bottom:16px;">
                                <h2 style="margin:0 0 18px;font-size:16px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">👤 Who's Staying</h2>
                                <?php foreach ($hg as $g): ?>
                                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:13px;">
                                        <span style="color:#64748b;">Room <?php echo esc_html($g['room']); ?></span>
                                        <span style="font-weight:600;color:#0f172a;"><?php echo esc_html(trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? ''))); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($hd['special_request'])): ?>
                                    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                                        <span style="color:#64748b;">Special Request</span>
                                        <span style="font-weight:600;color:#0f172a;"><?php echo esc_html($hd['special_request']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin-bottom:16px;">
                            <h2 style="margin:0 0 18px;font-size:16px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">💰 Price Summary</h2>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:13px;">
                                <span style="color:#64748b;">Base Fare (<?php echo esc_html($hh['rooms'] ?? 1); ?> room × <?php echo esc_html($hh['nights'] ?? 1); ?> nights)</span>
                                <span style="font-weight:600;">$<?php echo esc_html(number_format((float)($hh['base_fare'] ?? 0), 2)); ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:13px;">
                                <span style="color:#64748b;">Taxes &amp; Fees</span>
                                <span style="font-weight:600;">$<?php echo esc_html(number_format((float)($hh['tax'] ?? 0), 2)); ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:12px 0 0;font-size:16px;font-weight:700;color:#0f172a;border-top:2px solid #0f172a;margin-top:4px;">
                                <span>Total Paid</span>
                                <span style="color:#0e7d3f;">$<?php echo esc_html(number_format((float)($hh['total'] ?? 0), 2)); ?></span>
                            </div>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin-bottom:16px;">
                            <h2 style="margin:0 0 18px;font-size:16px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">💳 Payment</h2>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:13px;">
                                <span style="color:#64748b;">Method</span>
                                <span style="font-weight:600;"><?php echo esc_html($hp['method'] ?? 'Card'); ?></span>
                            </div>
                            <?php if (!empty($hp['card_holder'])): ?>
                                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:13px;">
                                    <span style="color:#64748b;">Card Holder</span>
                                    <span style="font-weight:600;"><?php echo esc_html($hp['card_holder']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($hp['card_last4'])): ?>
                                <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                                    <span style="color:#64748b;">Card</span>
                                    <span style="font-weight:600;">•••• •••• •••• <?php echo esc_html($hp['card_last4']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:24px;margin-bottom:16px;">
                            <h2 style="margin:0 0 18px;font-size:16px;font-weight:700;color:#0f172a;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">📞 Contact Information</h2>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:13px;">
                                <span style="color:#64748b;">Name</span><span style="font-weight:600;"><?php echo esc_html($lead['contact_name']); ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:13px;">
                                <span style="color:#64748b;">Email</span><span style="font-weight:600;"><?php echo esc_html($lead['contact_email']); ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:13px;">
                                <span style="color:#64748b;">Phone</span><span style="font-weight:600;"><?php echo esc_html($lead['contact_phone']); ?></span>
                            </div>
                        </div>

                    </div><!-- /.amadex-confirmation-main -->

                    <aside class="amadex-confirmation-sidebar">
                        <!-- Price Summary -->
                        <section class="amadex-card amadex-price-summary-card">
                            <h2>Price Summary</h2>
                            <div class="amadex-price-breakdown">
                                <div class="amadex-price-row">
                                    <div class="amadex-price-info">
                                        <span class="amadex-price-label">Base Fare</span>
                                        <span class="amadex-price-subtext"><?php echo esc_html(($hh['rooms'] ?? 1) . ' room × ' . ($hh['nights'] ?? 1) . ' nights'); ?></span>
                                    </div>
                                    <span class="amadex-price-value">$<?php echo esc_html(number_format((float)($hh['base_fare'] ?? 0), 2)); ?></span>
                                </div>
                                <div class="amadex-price-row">
                                    <div class="amadex-price-info">
                                        <span class="amadex-price-label">Taxes</span>
                                        <span class="amadex-price-subtext">Taxes &amp; Fees</span>
                                    </div>
                                    <span class="amadex-price-value">$<?php echo esc_html(number_format((float)($hh['tax'] ?? 0), 2)); ?></span>
                                </div>
                                <div class="amadex-price-row total">
                                    <div class="amadex-price-info">
                                        <span class="amadex-price-label">Total Amount</span>
                                    </div>
                                    <span class="amadex-price-value amadex-price-value--highlight">$<?php echo esc_html(number_format((float)($hh['total'] ?? 0), 2)); ?></span>
                                </div>
                            </div>
                        </section>

                        <!-- What Happens Next -->
                        <section class="amadex-card amadex-what-happens-next">
                            <h2>What Happens Next?</h2>
                            <ol class="amadex-next-steps">
                                <li><?php esc_html_e('A verification specialist reviews your request and secures live inventory.', 'amadex'); ?></li>
                                <li><?php esc_html_e('We call you to confirm guest details, room preferences, and any upgrades.', 'amadex'); ?></li>
                            </ol>
                        </section>

                        <!-- Need Assistance -->
                        <section class="amadex-card amadex-support-card">
                            <div class="amadex-support-header">
                                <h2>Need assistance?</h2>
                                <p><?php esc_html_e('Our team is on standby 24/7. Share your booking reference for faster service.', 'amadex'); ?></p>
                            </div>
                            <div class="amadex-support-content">
                                <div class="amadex-support-links">
                                    <a class="amadex-support-link" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $support_phone)); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14">
                                            <g transform="translate(-0.006)">
                                                <path d="M10.649,9.261a.981.981,0,0,0-1.483,0c-.347.344-.693.688-1.034,1.037a.2.2,0,0,1-.286.052c-.224-.122-.463-.221-.679-.355a10.755,10.755,0,0,1-2.593-2.36,6.143,6.143,0,0,1-.929-1.489A.213.213,0,0,1,3.7,5.873c.347-.335.685-.679,1.025-1.023a.985.985,0,0,0,0-1.518c-.271-.274-.542-.542-.813-.816s-.556-.562-.839-.839a.987.987,0,0,0-1.483,0c-.35.344-.685.7-1.04,1.034a1.688,1.688,0,0,0-.53,1.139A4.826,4.826,0,0,0,.389,5.931a12.622,12.622,0,0,0,2.24,3.732A13.864,13.864,0,0,0,7.22,13.255,6.64,6.64,0,0,0,9.764,14a1.864,1.864,0,0,0,1.6-.609c.3-.332.632-.635.947-.953a.991.991,0,0,0,.006-1.509q-.83-.835-1.666-1.663Z" fill="currentColor" />
                                            </g>
                                        </svg>
                                        <?php echo esc_html($support_phone); ?>
                                    </a>
                                </div>
                            </div>
                        </section>
                    </aside>

                </div><!-- /.amadex-confirmation-content -->
            </div>
    <?php
        return ob_get_clean();
    }
}
