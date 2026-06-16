<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Amadex Settings Class
 */
class Amadex_Settings
{

    /**
     * Settings tabs
     *
     * @var array
     */
    private $tabs;

    /**
     * Current tab
     *
     * @var string
     */
    private $current_tab;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize tabs
        $this->tabs = array(
            'general' => __('General', 'amadex'),
            'api' => __('API Settings', 'amadex'),
            'popup' => __('Popup Settings', 'amadex'),
            'display' => __('Display Options', 'amadex'),
            'pricing' => __('Price Management', 'amadex'),
            'currency'  => __('Currency Conversion', 'amadex'),
            'payment' => __('Payment Settings', 'amadex'),
            'email_template' => __('Email Template', 'amadex'),
            'brand' => __('Brand', 'amadex'),
            // 'call_banner' => __('CALL BANNER', 'amadex'),
            'addons' => __('Add On Services', 'amadex'),
            'promotional_containers' => __('Promotional Containers', 'amadex'),
            'advanced' => __('Advanced', 'amadex')
        );

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Handle settings redirect to show success message
        add_action('admin_init', array($this, 'handle_settings_redirect'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook)
    {
        // Only on our settings page
        if (strpos($hook, 'amadex-settings') === false) {
            return;
        }

        // Only on promotional containers, email template or brand tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        if ($current_tab === 'brand' || $current_tab === 'email_template') {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_media();
            // Use Cloudflare cdnjs for GrapesJS (often allowed when jsdelivr is blocked); preset from unpkg
            wp_enqueue_style('grapesjs-css', 'https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/css/grapes.min.css', array(), '0.21.10');
            wp_enqueue_script('grapesjs', 'https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.21.10/grapes.min.js', array('jquery'), '0.21.10', true);
            wp_enqueue_script('grapesjs-preset-newsletter', 'https://unpkg.com/grapesjs-preset-newsletter@1.0.2/dist/grapesjs-preset-newsletter.min.js', array('grapesjs'), '1.0.2', true);
        }
        if ($current_tab === 'promotional_containers') {
            // Enqueue WordPress color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');

            // Also enqueue iris (color picker dependency)
            wp_enqueue_script('iris', admin_url('js/iris.min.js'), array('jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch'), false, 1);

            // Enqueue container type definitions (must load before templates and renderer)
            wp_enqueue_script('amadex-container-types', AMADEX_URL . 'assets/js/amadex-container-types.js', array(), AMADEX_VERSION, true);

            // Enqueue template definitions (must load before renderer)
            wp_enqueue_script('amadex-promo-templates', AMADEX_URL . 'assets/js/amadex-promo-templates.js', array('amadex-container-types'), AMADEX_VERSION, true);

            // Enqueue promo topics (must load before renderer)
            wp_enqueue_script('amadex-promo-topics', AMADEX_URL . 'assets/js/amadex-promo-topics.js', array('amadex-promo-templates'), AMADEX_VERSION, true);

            // Enqueue shared promotional container renderer for 1:1 preview parity
            wp_enqueue_script('amadex-promo-renderer', AMADEX_URL . 'assets/js/amadex-promo-renderer.js', array('amadex-promo-templates', 'amadex-container-types', 'amadex-promo-topics'), AMADEX_VERSION, true);

            // Enqueue frontend CSS for accurate preview
            wp_enqueue_style('amadex-front', AMADEX_URL . 'assets/css/amadex.css', array(), AMADEX_VERSION);
        }
    }

    /**
     * Handle settings redirect after save
     */
    public function handle_settings_redirect()
    {
        // Only process on our settings page
        if (!isset($_GET['page']) || $_GET['page'] !== 'amadex-settings') {
            return;
        }

        // Check if settings were updated
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            // Get current tab
            $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

            // Add success message based on tab
            if ($current_tab === 'api') {
                add_settings_error(
                    'amadex_api_settings',
                    'amadex_api_settings_saved',
                    __('API settings saved successfully! Your API keys have been updated.', 'amadex'),
                    'updated'
                );
            } else {
                add_settings_error(
                    'amadex_general_settings',
                    'amadex_settings_saved',
                    __('Settings saved successfully!', 'amadex'),
                    'updated'
                );
            }
        }
    }





    /**
     * Register settings
     */
    public function register_settings()
    {
        // Register settings sections and fields

        // General settings with sanitization callback
        register_setting('amadex_general_settings', 'amadex_general_settings', array($this, 'sanitize_general_settings'));

        add_settings_section(
            'amadex_general_section',
            __('General Settings', 'amadex'),
            array($this, 'general_section_callback'),
            'amadex_general_settings'
        );

        add_settings_field(
            'call_now_number',
            __('Call Now Number', 'amadex'),
            array($this, 'call_now_number_callback'),
            'amadex_general_settings',
            'amadex_general_section'
        );

        // Popup Settings Tab
        register_setting('amadex_popup_settings', 'amadex_popup_settings');

        add_settings_section(
            'amadex_popup_section',
            __('Popup Settings', 'amadex'),
            array($this, 'popup_section_callback'),
            'amadex_popup_settings'
        );

        add_settings_field(
            'auto_popup_enabled',
            __('Enable Automatic Popup', 'amadex'),
            array($this, 'auto_popup_enabled_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'auto_popup_delay',
            __('Popup Delay (seconds)', 'amadex'),
            array($this, 'auto_popup_delay_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_title',
            __('Popup Title', 'amadex'),
            array($this, 'popup_title_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_description',
            __('Popup Description', 'amadex'),
            array($this, 'popup_description_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_logo_url',
            __('Popup Logo URL', 'amadex'),
            array($this, 'popup_logo_url_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_price_type',
            __('Popup Price Type', 'amadex'),
            array($this, 'popup_price_type_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_price_fixed',
            __('Popup Fixed Price', 'amadex'),
            array($this, 'popup_price_fixed_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_discount_percent',
            __('Popup Discount (%)', 'amadex'),
            array($this, 'popup_discount_percent_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_customer_service_image',
            __('Customer Service Image URL', 'amadex'),
            array($this, 'popup_customer_service_image_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_trust_years',
            __('Trust Badge Years', 'amadex'),
            array($this, 'popup_trust_years_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_trustpilot_rating',
            __('Trustpilot Rating', 'amadex'),
            array($this, 'popup_trustpilot_rating_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );

        add_settings_field(
            'popup_countdown_minutes',
            __('Countdown Timer (minutes)', 'amadex'),
            array($this, 'popup_countdown_minutes_callback'),
            'amadex_popup_settings',
            'amadex_popup_section'
        );



        add_settings_field(
            'enable_plugin',
            __('Enable Plugin', 'amadex'),
            array($this, 'enable_plugin_callback'),
            'amadex_general_settings',
            'amadex_general_section'
        );

        add_settings_field(
            'cache_duration',
            __('Cache Duration (minutes)', 'amadex'),
            array($this, 'cache_duration_callback'),
            'amadex_general_settings',
            'amadex_general_section'
        );

        add_settings_field(
            'booking_confirmation_page',
            __('Booking Confirmation Page', 'amadex'),
            array($this, 'booking_confirmation_page_callback'),
            'amadex_general_settings',
            'amadex_general_section'
        );

        add_settings_field(
            'notification_email',
            __('Admin Notification Email', 'amadex'),
            array($this, 'notification_email_callback'),
            'amadex_general_settings',
            'amadex_general_section'
        );

        add_settings_field(
            'agent_notification_emails',
            __('Agent Notification Emails', 'amadex'),
            array($this, 'agent_notification_emails_callback'),
            'amadex_general_settings',
            'amadex_general_section'
        );

        add_settings_field(
            'google_places_api_key',
            __('Google Places API Key', 'amadex'),
            array($this, 'google_places_api_key_callback'),
            'amadex_general_settings',
            'amadex_general_section'
        );

        // API settings with sanitize callback
        register_setting('amadex_api_settings', 'amadex_api_settings', array(
            'sanitize_callback' => array($this, 'sanitize_api_settings')
        ));

        add_settings_section(
            'amadex_api_section',
            __('Amadeus API Credentials', 'amadex'),
            array($this, 'api_section_callback'),
            'amadex_api_settings'
        );

        add_settings_field(
            'api_key',
            __('API Key', 'amadex'),
            array($this, 'api_key_callback'),
            'amadex_api_settings',
            'amadex_api_section'
        );

        add_settings_field(
            'api_secret',
            __('API Secret', 'amadex'),
            array($this, 'api_secret_callback'),
            'amadex_api_settings',
            'amadex_api_section'
        );

        add_settings_field(
            'environment',
            __('API Environment', 'amadex'),
            array($this, 'environment_callback'),
            'amadex_api_settings',
            'amadex_api_section'
        );

        add_settings_field(
            'clear_token_cache',
            __('Token Management', 'amadex'),
            array($this, 'clear_token_cache_callback'),
            'amadex_api_settings',
            'amadex_api_section'
        );

        // Display options
        register_setting('amadex_display_settings', 'amadex_display_settings');

        add_settings_section(
            'amadex_display_section',
            __('Display Options', 'amadex'),
            array($this, 'display_section_callback'),
            'amadex_display_settings'
        );

        add_settings_field(
            'search_form_title',
            __('Search Form Title', 'amadex'),
            array($this, 'search_form_title_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        add_settings_field(
            'button_text',
            __('Search Button Text', 'amadex'),
            array($this, 'button_text_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        add_settings_field(
            'default_theme',
            __('Default Theme', 'amadex'),
            array($this, 'default_theme_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        add_settings_field(
            'custom_css',
            __('Custom CSS', 'amadex'),
            array($this, 'custom_css_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        // Viewers Badge Settings (Social Proof)
        add_settings_field(
            'viewers_badge_enabled',
            __('Enable Viewers Badge', 'amadex'),
            array($this, 'viewers_badge_enabled_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        add_settings_field(
            'viewers_badge_min',
            __('Viewers Count - Minimum', 'amadex'),
            array($this, 'viewers_badge_min_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        add_settings_field(
            'viewers_badge_max',
            __('Viewers Count - Maximum', 'amadex'),
            array($this, 'viewers_badge_max_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        add_settings_field(
            'viewers_badge_text',
            __('Viewers Badge Text', 'amadex'),
            array($this, 'viewers_badge_text_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        add_settings_field(
            'viewers_badge_position',
            __('Badge Position', 'amadex'),
            array($this, 'viewers_badge_position_callback'),
            'amadex_display_settings',
            'amadex_display_section'
        );

        // =========================
        // Price Management Tab
        // =========================
        register_setting('amadex_pricing_settings', 'amadex_pricing_settings');
        register_setting('amadex_pricing_settings', 'amadex_pricing_rules_settings', array($this, 'sanitize_pricing_rules_settings'));

        add_settings_section(
            'amadex_pricing_section',
            __('Price Management & Markup Settings', 'amadex'),
            array($this, 'pricing_section_callback'),
            'amadex_pricing_settings'
        );

        add_settings_section(
            'amadex_pricing_rules_section',
            __('Pricing Rules Engine', 'amadex'),
            array($this, 'pricing_rules_section_callback'),
            'amadex_pricing_settings'
        );

        add_settings_field(
            'enable_pricing_rules_engine',
            __('Enable Pricing Rules Engine', 'amadex'),
            array($this, 'enable_pricing_rules_engine_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_rules_section'
        );

        // Price Markup settings moved to Pricing Rules Engine section
        add_settings_field(
            'enable_price_markup',
            __('Enable Price Markup', 'amadex'),
            array($this, 'enable_price_markup_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_rules_section'
        );

        add_settings_field(
            'price_markup_type',
            __('Markup Type', 'amadex'),
            array($this, 'price_markup_type_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_rules_section'
        );

        add_settings_field(
            'price_markup_percentage',
            __('Markup Percentage (%)', 'amadex'),
            array($this, 'price_markup_percentage_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_rules_section'
        );

        add_settings_field(
            'price_markup_fixed',
            __('Fixed Markup Amount', 'amadex'),
            array($this, 'price_markup_fixed_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_rules_section'
        );

        add_settings_field(
            'airline_specific_markup',
            __('Airline-Specific Markup', 'amadex'),
            array($this, 'airline_specific_markup_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_rules_section'
        );

        add_settings_field(
            'round_prices',
            __('Round Prices', 'amadex'),
            array($this, 'round_prices_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_section'
        );

        add_settings_field(
            'enable_confirmation_discount',
            __('Enable Discount on Confirmation', 'amadex'),
            array($this, 'enable_confirmation_discount_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_section'
        );

        add_settings_field(
            'confirmation_discount_percentage',
            __('Discount Percentage (%)', 'amadex'),
            array($this, 'confirmation_discount_percentage_callback'),
            'amadex_pricing_settings',
            'amadex_pricing_section'
        );

   // =========================
// Currency Conversion Tab
// =========================
        /**
         * Sanitize currency settings and clear cache when regional settings toggle changes
         * 
         * This function ensures proper sanitization of currency settings and clears
         * the cached regional settings enabled status when the toggle is changed,
         * ensuring immediate effect across all pages.
         * 
         * @since 1.1.0
         * @param array $input Raw input from form submission
         * @return array Sanitized settings array
         */
        function amadex_sanitize_currency_settings($input)
        {
            // Ensure input is an array
            if (!is_array($input)) {
                $input = array();
            }

            // Get existing settings to detect changes
            $existing = get_option('amadex_currency_settings', array());
            if (!is_array($existing)) {
                $existing = array();
            }

            // Start with existing settings to preserve any fields not in input
            $sanitized = $existing;

            // Sanitize enable_regional_settings checkbox
            // Checkboxes only send value when checked, so if not set, it's unchecked
            if (isset($input['enable_regional_settings'])) {
                $value = $input['enable_regional_settings'];
                // Accept '1', 1, or true
                $sanitized['enable_regional_settings'] = ($value == '1' || $value == 1 || $value === true) ? 1 : 0;
            } else {
                // Checkbox not checked - set to 0
                $sanitized['enable_regional_settings'] = 0;
            }

            // Check if regional settings toggle changed
            $old_value = isset($existing['enable_regional_settings']) ? (bool) $existing['enable_regional_settings'] : true;
            $new_value = (bool) $sanitized['enable_regional_settings'];

            // Always clear cache on save to ensure immediate effect
            if (class_exists('Amadex_Currency')) {
                Amadex_Currency::clear_regional_settings_cache();
            } else {
                delete_transient('amadex_regional_settings_enabled');
            }
            amadex_log('Regional Settings toggle saved as ' . ($new_value ? 'enabled' : 'disabled') . ' - cache cleared');

            // Sanitize other currency settings if present
            if (isset($input['default_currency'])) {
                $sanitized['default_currency'] = strtoupper(sanitize_text_field($input['default_currency']));
            }

            if (isset($input['auto_detect_currency'])) {
                $sanitized['auto_detect_currency'] = isset($input['auto_detect_currency']) ? 1 : 0;
            }

            // Sanitize exchange rate overrides if present
            if (isset($input['exchange_rate_overrides']) && is_array($input['exchange_rate_overrides'])) {
                $sanitized['exchange_rate_overrides'] = array();
                foreach ($input['exchange_rate_overrides'] as $currency => $rate) {
                    $currency = strtoupper(sanitize_text_field($currency));
                    $rate = floatval($rate);
                    if ($currency && $rate > 0) {
                        $sanitized['exchange_rate_overrides'][$currency] = $rate;
                    }
                }
            }

            if (isset($input['manual_rates']) && is_array($input['manual_rates'])) {
                $sanitized['manual_rates'] = array();
                foreach ($input['manual_rates'] as $key => $rate) {
                    $key = sanitize_key($key);
                    $rate = floatval($rate);
                    if ($key && $rate > 0) {
                        $sanitized['manual_rates'][$key] = $rate;
                    }
                }
            }

            return $sanitized;
        }

        register_setting('amadex_currency_settings', 'amadex_currency_settings', 'amadex_sanitize_currency_settings');
        register_setting('ft_amadeus_settings_group', 'ft_amadeus_settings');


        add_settings_section(
            'amadex_currency_section',
            __('Multi-Currency Settings', 'amadex'),
            function () {
                echo '<p>' . __('Configure multi-currency support. Customers can view prices in their preferred currency. Payments are automatically converted to USD for NMI processing.', 'amadex') . '</p>';
                echo '<p><strong>' . __('Note:', 'amadex') . '</strong> ' . __('Exchange rates are fetched automatically from an API. You can override specific rates below if needed.', 'amadex') . '</p>';
            },
            'amadex_currency_settings'
        );

        // Enable Regional Settings Toggle
        add_settings_field(
            'enable_regional_settings',
            __('Enable Regional Settings System', 'amadex'),
            function () {
                $options = get_option('amadex_currency_settings');
                $value = isset($options['enable_regional_settings']) ? (bool) $options['enable_regional_settings'] : true;
?>
            <label>
                <input type="checkbox" name="amadex_currency_settings[enable_regional_settings]" value="1" <?php checked($value, true); ?>>
                <?php echo __('Enable automatic country, currency, and language detection based on user location', 'amadex'); ?>
            </label>
            <p class="description">
                <?php echo __('When enabled, the system will automatically detect user location via IP geolocation and apply appropriate country, currency, and language settings. When disabled, all users will see USA/USD/English (US) by default.', 'amadex'); ?>
            </p>
        <?php
            },
            'amadex_currency_settings',
            'amadex_currency_section'
        );

        // Default Currency
        add_settings_field(
            'default_currency',
            __('Default Currency', 'amadex'),
            function () {
                $options = get_option('amadex_currency_settings');
                $value = isset($options['default_currency']) ? $options['default_currency'] : 'USD';

                // Get currencies with fallback
                if (class_exists('Amadex_Currency') && method_exists('Amadex_Currency', 'get_supported_currencies')) {
                    try {
                        $currencies = Amadex_Currency::get_supported_currencies();
                    } catch (Exception $e) {
                        $currencies = array('USD' => array('name' => 'US Dollar'));
                    }
                } else {
                    $currencies = array('USD' => array('name' => 'US Dollar'));
                }
        ?>
            <select name="amadex_currency_settings[default_currency]">
                <?php foreach ($currencies as $code => $info): ?>
                    <option value="<?php echo esc_attr($code); ?>" <?php selected($value, $code); ?>>
                        <?php echo esc_html($code . ' - ' . ($info['name'] ?? $code)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php echo __('Default currency shown to customers when they first visit the booking page.', 'amadex'); ?></p>
        <?php
            },
            'amadex_currency_settings',
            'amadex_currency_section'
        );

        // From Currency
        add_settings_field(
            'from_currency',
            __('From Currency', 'amadex'),
            function () {
                $options = get_option('amadex_currency_settings');
                $value = isset($options['from_currency']) ? $options['from_currency'] : 'INR';
        ?>
            <select name="amadex_currency_settings[from_currency]">
                <option value="INR" <?php selected($value, 'INR'); ?>>INR</option>
                <option value="USD" <?php selected($value, 'USD'); ?>>USD</option>
                <option value="EUR" <?php selected($value, 'EUR'); ?>>EUR</option>
                <option value="GBP" <?php selected($value, 'GBP'); ?>>GBP</option>
                <option value="CAD" <?php selected($value, 'CAD'); ?>>CAD</option>
                <option value="AUD" <?php selected($value, 'AUD'); ?>>AUD</option>
                <option value="BDT" <?php selected($value, 'BDT'); ?>>BDT</option>
            </select>
        <?php
            },
            'amadex_currency_settings',
            'amadex_currency_section'
        );

        // To Currency
        add_settings_field(
            'to_currency',
            __('To Currency', 'amadex'),
            function () {
                $options = get_option('amadex_currency_settings');
                $value = isset($options['to_currency']) ? $options['to_currency'] : 'USD';
        ?>
            <select name="amadex_currency_settings[to_currency]">
                <option value="USD" <?php selected($value, 'USD'); ?>>USD</option>
                <option value="INR" <?php selected($value, 'INR'); ?>>INR</option>
                <option value="EUR" <?php selected($value, 'EUR'); ?>>EUR</option>
                <option value="GBP" <?php selected($value, 'GBP'); ?>>GBP</option>
                <option value="CAD" <?php selected($value, 'CAD'); ?>>CAD</option>
                <option value="AUD" <?php selected($value, 'AUD'); ?>>AUD</option>
                <option value="BDT" <?php selected($value, 'BDT'); ?>>BDT</option>
            </select>
        <?php
            },
            'amadex_currency_settings',
            'amadex_currency_section'
        );

        // INR → USD rate
        add_settings_field(
            'inr_to_usd',
            __('INR to USD Rate', 'amadex'),
            function () {
                $options = get_option('amadex_currency_settings');
                $value = isset($options['inr_to_usd']) ? $options['inr_to_usd'] : '0.0081';
                echo '<input type="number" step="0.00000001" name="amadex_currency_settings[inr_to_usd]" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'amadex_currency_settings',
            'amadex_currency_section'
        );

        // USD → INR rate
        add_settings_field(
            'usd_to_inr',
            __('USD to INR Rate', 'amadex'),
            function () {
                $options = get_option('amadex_currency_settings');
                $value = isset($options['usd_to_inr']) ? $options['usd_to_inr'] : '123.31';
                echo '<input type="number" step="0.00000001" name="amadex_currency_settings[usd_to_inr]" value="' . esc_attr($value) . '" class="regular-text">';
                echo '<p class="description">' . __('Manual override for USD to INR rate (used as fallback if API fails).', 'amadex') . '</p>';
            },
            'amadex_currency_settings',
            'amadex_currency_section'
        );

        // Manual Exchange Rates Override Section
        add_settings_field(
            'manual_rates',
            __('Manual Exchange Rate Overrides', 'amadex'),
            function () {
                $options = get_option('amadex_currency_settings');
                $manual_rates = isset($options['manual_rates']) && is_array($options['manual_rates']) ? $options['manual_rates'] : array();

                // Get all supported currencies (with error handling)
                $currencies = array();
                if (class_exists('Amadex_Currency') && method_exists('Amadex_Currency', 'get_supported_currencies')) {
                    try {
                        $currencies = Amadex_Currency::get_supported_currencies();
                    } catch (Exception $e) {
                        error_log('Amadex Currency Settings Error: ' . $e->getMessage());
                        $currencies = array();
                    }
                }

                // Common currency pairs to USD
                $common_pairs = array(
                    'EUR_USD' => array('from' => 'EUR', 'to' => 'USD', 'default' => '1.08', 'label' => 'EUR → USD'),
                    'GBP_USD' => array('from' => 'GBP', 'to' => 'USD', 'default' => '1.27', 'label' => 'GBP → USD'),
                    'INR_USD' => array('from' => 'INR', 'to' => 'USD', 'default' => '0.012', 'label' => 'INR → USD'),
                    'CAD_USD' => array('from' => 'CAD', 'to' => 'USD', 'default' => '0.74', 'label' => 'CAD → USD'),
                    'AUD_USD' => array('from' => 'AUD', 'to' => 'USD', 'default' => '0.66', 'label' => 'AUD → USD'),
                    'JPY_USD' => array('from' => 'JPY', 'to' => 'USD', 'default' => '0.0067', 'label' => 'JPY → USD'),
                    'CNY_USD' => array('from' => 'CNY', 'to' => 'USD', 'default' => '0.14', 'label' => 'CNY → USD'),
                    'SGD_USD' => array('from' => 'SGD', 'to' => 'USD', 'default' => '0.74', 'label' => 'SGD → USD'),
                    'AED_USD' => array('from' => 'AED', 'to' => 'USD', 'default' => '0.27', 'label' => 'AED → USD'),
                    'BDT_USD' => array('from' => 'BDT', 'to' => 'USD', 'default' => '0.0091', 'label' => 'BDT → USD'),
                    'PKR_USD' => array('from' => 'PKR', 'to' => 'USD', 'default' => '0.0036', 'label' => 'PKR → USD'),
                    'MXN_USD' => array('from' => 'MXN', 'to' => 'USD', 'default' => '0.059', 'label' => 'MXN → USD'),
                    'BRL_USD' => array('from' => 'BRL', 'to' => 'USD', 'default' => '0.20', 'label' => 'BRL → USD'),
                );

                echo '<div class="amadex-manual-rates-container" style="max-width: 800px;">';
                echo '<p class="description" style="margin-bottom: 15px;">' . __('Override exchange rates manually. Leave empty to use automatic API rates. Rates are stored as "Currency → USD" (e.g., 1 INR = 0.012 USD). The system automatically calculates inverse rates (USD → Currency) when needed for display conversions, so you only need to enter rates in one direction.', 'amadex') . '</p>';
                echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">';
                echo '<thead><tr><th style="width: 200px;">' . __('Currency Pair', 'amadex') . '</th><th>' . __('Rate (to USD)', 'amadex') . '</th></tr></thead>';
                echo '<tbody>';

                foreach ($common_pairs as $key => $pair) {
                    $rate_key = $pair['from'] . '_' . $pair['to'];
                    $current_value = isset($manual_rates[$rate_key]) ? $manual_rates[$rate_key] : '';
                    $field_name = 'amadex_currency_settings[manual_rates][' . esc_attr($rate_key) . ']';
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($pair['label']) . '</strong></td>';
                    echo '<td>';
                    echo '<input type="number" step="0.00000001" name="' . $field_name . '" value="' . esc_attr($current_value) . '" class="regular-text" placeholder="' . esc_attr($pair['default']) . '">';
                    echo '<span class="description" style="margin-left: 10px;">' . __('Default: ', 'amadex') . $pair['default'] . '</span>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
                echo '<p class="description" style="margin-top: 10px;">' . __('<strong>Note:</strong> These rates are used as fallback when the API fails. Leave empty to use automatic API rates.', 'amadex') . '</p>';
                echo '</div>';
            },
            'amadex_currency_settings',
            'amadex_currency_section'
        );


        // Advanced settings
        register_setting('amadex_advanced_settings', 'amadex_advanced_settings', array(
            'sanitize_callback' => array($this, 'sanitize_advanced_settings'),
        ));

        // Performance settings (same option group so both save when Advanced tab form submits)
        register_setting('amadex_advanced_settings', 'amadex_performance_settings', array(
            'sanitize_callback' => array($this, 'sanitize_performance_settings'),
        ));

        add_settings_section(
            'amadex_advanced_section',
            __('Advanced Settings', 'amadex'),
            array($this, 'advanced_section_callback'),
            'amadex_advanced_settings'
        );

        add_settings_field(
            'timeout',
            __('API Timeout (seconds)', 'amadex'),
            array($this, 'timeout_callback'),
            'amadex_advanced_settings',
            'amadex_advanced_section'
        );

        add_settings_field(
            'error_logging',
            __('Error Logging', 'amadex'),
            array($this, 'error_logging_callback'),
            'amadex_advanced_settings',
            'amadex_advanced_section'
        );

        add_settings_field(
            'debug_mode',
            __('Debug Mode', 'amadex'),
            array($this, 'debug_mode_callback'),
            'amadex_advanced_settings',
            'amadex_advanced_section'
        );

        add_settings_section(
            'amadex_performance_section',
            __('Performance Optimization Settings', 'amadex'),
            array($this, 'performance_section_callback'),
            'amadex_advanced_settings'
        );

        add_settings_field(
            'enable_debug_logging',
            __('Enable Debug Logging', 'amadex'),
            array($this, 'enable_debug_logging_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'initial_results_count',
            __('Initial Results Count', 'amadex'),
            array($this, 'initial_results_count_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'enable_performance_logging',
            __('Enable Performance Logging', 'amadex'),
            array($this, 'enable_performance_logging_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'enable_redis_cache',
            __('Enable Redis Cache', 'amadex'),
            array($this, 'enable_redis_cache_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'redis_host',
            __('Redis Host', 'amadex'),
            array($this, 'redis_host_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'redis_port',
            __('Redis Port', 'amadex'),
            array($this, 'redis_port_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'redis_password',
            __('Redis Password', 'amadex'),
            array($this, 'redis_password_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'redis_username',
            __('Redis Username', 'amadex'),
            array($this, 'redis_username_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'redis_database',
            __('Redis Database', 'amadex'),
            array($this, 'redis_database_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'redis_use_tls',
            __('Use TLS/SSL for Redis', 'amadex'),
            array($this, 'redis_use_tls_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'enable_progressive_loading',
            __('Enable Progressive Loading', 'amadex'),
            array($this, 'enable_progressive_loading_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'progressive_load_count',
            __('Initial Results Count (Progressive)', 'amadex'),
            array($this, 'progressive_load_count_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'enable_streaming_response',
            __('Enable Streaming Response', 'amadex'),
            array($this, 'enable_streaming_response_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'streaming_initial_count',
            __('Streaming Initial Count', 'amadex'),
            array($this, 'streaming_initial_count_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'enable_virtual_scrolling',
            __('Enable Virtual Scrolling', 'amadex'),
            array($this, 'enable_virtual_scrolling_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'enable_skeleton_ui',
            __('Enable Skeleton UI', 'amadex'),
            array($this, 'enable_skeleton_ui_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );

        add_settings_field(
            'enable_loading_animation',
            __('Enable Loading Animation', 'amadex'),
            array($this, 'enable_loading_animation_callback'),
            'amadex_advanced_settings',
            'amadex_performance_section'
        );



        add_settings_field(
            'ft_amadeus_currency',
            'Default Currency',
            function () {
                $options = get_option('ft_amadeus_settings');
                $val = $options['currency'] ?? 'USD';
        ?>
            <select name="ft_amadeus_settings[currency]">
                <?php
                $currencies = ['USD', 'EUR', 'INR', 'GBP', 'CAD', 'AUD'];
                foreach ($currencies as $c) {
                    printf('<option value="%s"%s>%s</option>', $c, selected($val, $c, false), $c);
                }
                ?>
            </select>
        <?php
            },
            'ft_amadeus_settings_page',
            'ft_amadeus_settings_section'
        );

        add_settings_field(
            'ft_amadeus_cabin',
            'Default Cabin Class',
            function () {
                $options = get_option('ft_amadeus_settings');
                $val = $options['cabin'] ?? 'ECONOMY';
        ?>
            <select name="ft_amadeus_settings[cabin]">
                <?php
                $cabins = ['ECONOMY', 'PREMIUM_ECONOMY', 'BUSINESS', 'FIRST'];
                foreach ($cabins as $c) {
                    printf('<option value="%s"%s>%s</option>', $c, selected($val, $c, false), ucwords(strtolower(str_replace('_', ' ', $c))));
                }
                ?>
            </select>
        <?php
            },
            'ft_amadeus_settings_page',
            'ft_amadeus_settings_section'
        );

        // Payment settings with sanitization callback
        register_setting('amadex_payment_settings', 'amadex_payment_settings', array(
            'sanitize_callback' => array($this, 'sanitize_payment_settings')
        ));

        // Email Template settings (Booking confirmation email design)
        register_setting('amadex_email_template_settings', 'amadex_email_template_settings', array(
            'sanitize_callback' => array($this, 'sanitize_email_template_settings')
        ));
        // Allow HTML in custom_html field - bypass WordPress kses filtering for this option
        add_filter('sanitize_option_amadex_email_template_settings', function ($value) {
            if (isset($_POST['amadex_email_template_settings']['custom_html'])) {
                $value['custom_html'] = wp_unslash($_POST['amadex_email_template_settings']['custom_html']);
            }
            if (isset($_POST['amadex_email_template_settings']['custom_css'])) {
                $value['custom_css'] = wp_unslash($_POST['amadex_email_template_settings']['custom_css']);
            }
            return $value;
        }, 20);
        register_setting('amadex_brand_settings', 'amadex_brand_settings', array(
            'sanitize_callback' => array($this, 'sanitize_brand_settings')
        ));

        // ========== GENERAL PAYMENT SETTINGS SECTION ==========
        // Always visible section for general payment configuration
        add_settings_section(
            'amadex_payment_general_section',
            __('General Payment Settings', 'amadex'),
            array($this, 'payment_general_section_callback'),
            'amadex_payment_settings'
        );

        // Default Card Processor Gateway Selector
        add_settings_field(
            'default_card_gateway',
            __('Default Card Processor', 'amadex'),
            array($this, 'default_card_gateway_callback'),
            'amadex_payment_settings',
            'amadex_payment_general_section'
        );

        // Payment Method Options
        add_settings_field(
            'enable_credit_card',
            __('Enable Credit/Debit Card', 'amadex'),
            array($this, 'enable_credit_card_callback'),
            'amadex_payment_settings',
            'amadex_payment_general_section'
        );

        add_settings_field(
            'nmi_bypass_for_testing',
            __('Bypass Payment for Testing', 'amadex'),
            array($this, 'nmi_bypass_for_testing_callback'),
            'amadex_payment_settings',
            'amadex_payment_general_section'
        );

        add_settings_field(
            'enable_3ds',
            __('3D Secure (3DS) Authentication', 'amadex'),
            array($this, 'enable_3ds_callback'),
            'amadex_payment_settings',
            'amadex_payment_general_section'
        );

        // ========== NMI PAYMENT GATEWAY SECTION ==========
        add_settings_section(
            'amadex_payment_nmi_section',
            __('NMI Payment Gateway', 'amadex'),
            array($this, 'payment_nmi_section_callback'),
            'amadex_payment_settings'
        );

        // NMI Gateway Settings
        add_settings_field(
            'nmi_api_key',
            __('NMI API Key (Security Key)', 'amadex'),
            array($this, 'nmi_api_key_callback'),
            'amadex_payment_settings',
            'amadex_payment_nmi_section'
        );

        add_settings_field(
            'nmi_tokenization_key',
            __('NMI Tokenization Key (Collect.js)', 'amadex'),
            array($this, 'nmi_tokenization_key_callback'),
            'amadex_payment_settings',
            'amadex_payment_nmi_section'
        );

        add_settings_field(
            'nmi_sandbox',
            __('NMI Sandbox Mode', 'amadex'),
            array($this, 'nmi_sandbox_callback'),
            'amadex_payment_settings',
            'amadex_payment_nmi_section'
        );

        add_settings_field(
            'nmi_test_connection',
            __('Test NMI Connection', 'amadex'),
            array($this, 'nmi_test_connection_callback'),
            'amadex_payment_settings',
            'amadex_payment_nmi_section'
        );

        // ========== STRIPE PAYMENT GATEWAY SECTION ==========
        add_settings_section(
            'amadex_payment_stripe_section',
            __('Stripe Payment Gateway', 'amadex'),
            array($this, 'payment_stripe_section_callback'),
            'amadex_payment_settings'
        );

        add_settings_field(
            'stripe_publishable_key',
            __('Stripe Publishable Key', 'amadex'),
            array($this, 'stripe_publishable_key_callback'),
            'amadex_payment_settings',
            'amadex_payment_stripe_section'
        );

        add_settings_field(
            'stripe_secret_key',
            __('Stripe Secret Key', 'amadex'),
            array($this, 'stripe_secret_key_callback'),
            'amadex_payment_settings',
            'amadex_payment_stripe_section'
        );

        add_settings_field(
            'stripe_mode',
            __('Stripe Mode', 'amadex'),
            array($this, 'stripe_mode_callback'),
            'amadex_payment_settings',
            'amadex_payment_stripe_section'
        );

        add_settings_field(
            'stripe_webhook_secret',
            __('Stripe Webhook Secret (optional)', 'amadex'),
            array($this, 'stripe_webhook_secret_callback'),
            'amadex_payment_settings',
            'amadex_payment_stripe_section'
        );

        // ========== PAYPAL PAYMENT GATEWAY SECTION ==========
        add_settings_section(
            'amadex_payment_paypal_section',
            __('PayPal Payment Gateway', 'amadex'),
            array($this, 'payment_paypal_section_callback'),
            'amadex_payment_settings'
        );

        add_settings_field(
            'paypal_client_id',
            __('PayPal Client ID', 'amadex'),
            array($this, 'paypal_client_id_callback'),
            'amadex_payment_settings',
            'amadex_payment_paypal_section'
        );

        add_settings_field(
            'paypal_client_secret',
            __('PayPal Client Secret', 'amadex'),
            array($this, 'paypal_client_secret_callback'),
            'amadex_payment_settings',
            'amadex_payment_paypal_section'
        );

        add_settings_field(
            'paypal_mode',
            __('PayPal Mode', 'amadex'),
            array($this, 'paypal_mode_callback'),
            'amadex_payment_settings',
            'amadex_payment_paypal_section'
        );

        add_settings_field(
            'paypal_redirect_url',
            __('PayPal Redirect URL', 'amadex'),
            array($this, 'paypal_redirect_url_callback'),
            'amadex_payment_settings',
            'amadex_payment_paypal_section'
        );

        // ========== PAY WITH CRYPTO (Cryptocurrency Transfer) SECTION ==========
        add_settings_section(
            'amadex_payment_crypto_transfer_section',
            __('Pay with Crypto', 'amadex'),
            array($this, 'payment_crypto_transfer_section_callback'),
            'amadex_payment_settings'
        );
        add_settings_field(
            'crypto_transfer_redirect_url',
            __('Cryptocurrency Transfer Redirect URL', 'amadex'),
            array($this, 'crypto_transfer_redirect_url_callback'),
            'amadex_payment_settings',
            'amadex_payment_crypto_transfer_section'
        );

        // ========== CRYPTO.COM PAY SECTION ==========
        add_settings_section(
            'amadex_payment_crypto_com_section',
            __('Crypto.com Pay', 'amadex'),
            array($this, 'payment_crypto_com_section_callback'),
            'amadex_payment_settings'
        );
        add_settings_field(
            'crypto_com_redirect_url',
            __('Crypto.com Pay Redirect URL', 'amadex'),
            array($this, 'crypto_com_redirect_url_callback'),
            'amadex_payment_settings',
            'amadex_payment_crypto_com_section'
        );
        add_settings_field(
            'crypto_com_publishable_key',
            __('Crypto.com Pay Publishable Key', 'amadex'),
            array($this, 'crypto_com_publishable_key_callback'),
            'amadex_payment_settings',
            'amadex_payment_crypto_com_section'
        );
        add_settings_field(
            'crypto_com_secret_key',
            __('Crypto.com Pay Secret Key', 'amadex'),
            array($this, 'crypto_com_secret_key_callback'),
            'amadex_payment_settings',
            'amadex_payment_crypto_com_section'
        );

        // ========== CALL BANNER SETTINGS ==========
        register_setting('amadex_call_banner_settings', 'amadex_call_banner_settings', array($this, 'sanitize_call_banner_settings'));

        add_settings_section(
            'amadex_call_banner_section',
            __('Call Banner Settings', 'amadex'),
            array($this, 'call_banner_section_callback'),
            'amadex_call_banner_settings'
        );

        add_settings_field(
            'call_banner_html',
            __('Call Banner HTML/CSS', 'amadex'),
            array($this, 'call_banner_html_callback'),
            'amadex_call_banner_settings',
            'amadex_call_banner_section'
        );

        // ========== MOONPAY COMMERCE (HELIO) SECTION ==========
        add_settings_section(
            'amadex_payment_moonpay_section',
            __('MoonPay Commerce (Pay with Card → Crypto)', 'amadex'),
            array($this, 'payment_moonpay_section_callback'),
            'amadex_payment_settings'
        );
        add_settings_field(
            'moonpay_environment',
            __('Environment', 'amadex'),
            array($this, 'moonpay_environment_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_section'
        );
        add_settings_field(
            'moonpay_public_key',
            __('Public API Key', 'amadex'),
            array($this, 'moonpay_public_key_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_section'
        );
        add_settings_field(
            'moonpay_secret_key',
            __('Secret API Key', 'amadex'),
            array($this, 'moonpay_secret_key_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_section'
        );
        add_settings_field(
            'moonpay_helio_wallet_id',
            __('Helio Wallet ID', 'amadex'),
            array($this, 'moonpay_helio_wallet_id_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_section'
        );
        add_settings_field(
            'moonpay_settlement_currencies',
            __('Settlement currencies', 'amadex'),
            array($this, 'moonpay_settlement_currencies_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_section'
        );
        add_settings_field(
            'moonpay_helio_webhook_secret',
            __('Webhook shared secret (optional)', 'amadex'),
            array($this, 'moonpay_helio_webhook_secret_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_section'
        );

        // ========== MOONPAY ONRAMP (RAMPS) SECTION ==========
        add_settings_section(
            'amadex_payment_moonpay_onramp_section',
            __('MoonPay Onramp (Pay with card on site)', 'amadex'),
            array($this, 'payment_moonpay_onramp_section_callback'),
            'amadex_payment_settings'
        );
        add_settings_field(
            'moonpay_onramp_environment',
            __('Environment', 'amadex'),
            array($this, 'moonpay_onramp_environment_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_onramp_section'
        );
        add_settings_field(
            'moonpay_onramp_publishable_key_test',
            __('Publishable Key (Test)', 'amadex'),
            array($this, 'moonpay_onramp_publishable_key_test_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_onramp_section'
        );
        add_settings_field(
            'moonpay_onramp_secret_key_test',
            __('Secret Key (Test)', 'amadex'),
            array($this, 'moonpay_onramp_secret_key_test_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_onramp_section'
        );
        add_settings_field(
            'moonpay_onramp_publishable_key_live',
            __('Publishable Key (Live)', 'amadex'),
            array($this, 'moonpay_onramp_publishable_key_live_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_onramp_section'
        );
        add_settings_field(
            'moonpay_onramp_secret_key_live',
            __('Secret Key (Live)', 'amadex'),
            array($this, 'moonpay_onramp_secret_key_live_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_onramp_section'
        );
        add_settings_field(
            'moonpay_onramp_merchant_wallet_btc',
            __('Merchant BTC wallet address (Production)', 'amadex'),
            array($this, 'moonpay_onramp_merchant_wallet_btc_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_onramp_section'
        );
        add_settings_field(
            'moonpay_onramp_merchant_wallet_btc_sandbox',
            __('Merchant BTC wallet (Sandbox / Testnet)', 'amadex'),
            array($this, 'moonpay_onramp_merchant_wallet_btc_sandbox_callback'),
            'amadex_payment_settings',
            'amadex_payment_moonpay_onramp_section'
        );
    }

    /**
     * General section callback
     */
    public function general_section_callback()
    {
        echo '<p>' . __('Configure general plugin settings.', 'amadex') . '</p>';
    }

    /**
     * Popup section callback
     */
    public function popup_section_callback()
    {
        echo '<p>' . __('Configure automatic call now popup settings that appear on flight results page.', 'amadex') . '</p>';
    }

    /**
     * Call Now Number callback
     */
    public function call_now_number_callback()
    {
        $options = get_option('amadex_general_settings');
        $value = isset($options['call_now_number']) ? $options['call_now_number'] : '+1-866-960-2626';
        ?>
        <input type="text" id="call_now_number" name="amadex_general_settings[call_now_number]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Phone number displayed for "Call Now" functionality.', 'amadex'); ?></p>
    <?php
    }

    /**
     * Call Now Enabled callback
     */
    public function call_now_enabled_callback()
    {
        $options = get_option('amadex_general_settings');
        $value = isset($options['call_now_enabled']) ? $options['call_now_enabled'] : 1;
    ?>
        <label for="call_now_enabled">
            <input type="checkbox" id="call_now_enabled" name="amadex_general_settings[call_now_enabled]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Show popup instead of direct call link', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('If enabled, clicking "Call Now" will show a popup with flight details and call options.', 'amadex'); ?></p>
    <?php
    }

    /**
     * Auto Popup Enabled callback
     */
    public function auto_popup_enabled_callback()
    {
        $options = get_option('amadex_popup_settings');
        $value = isset($options['auto_popup_enabled']) ? $options['auto_popup_enabled'] : 1;
    ?>
        <label for="auto_popup_enabled">
            <input type="checkbox" id="auto_popup_enabled" name="amadex_popup_settings[auto_popup_enabled]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable automatic popup on flight results page', 'amadex'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, call now popup will automatically appear after specified delay on results page.', 'amadex'); ?>
            <br>
            <strong><?php _e('Default delay:', 'amadex'); ?></strong> <?php _e('7 minutes (420 seconds)', 'amadex'); ?>
        </p> <?php
            }

            /**
             * Auto Popup Delay callback
             */
            public function auto_popup_delay_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['auto_popup_delay']) ? $options['auto_popup_delay'] : 420;
                $minutes = floor($value / 60);
                $seconds = $value % 60;
                ?>
        <input type="number" id="auto_popup_delay" name="amadex_popup_settings[auto_popup_delay]" value="<?php echo esc_attr($value); ?>" min="1" max="600" class="small-text"> seconds
        <p class="description">
            <?php _e('Delay in seconds before automatic popup appears (1-600 seconds = 1 second to 10 minutes).', 'amadex'); ?>
            <br>
            <strong><?php _e('Current setting:', 'amadex'); ?></strong>
            <?php
                if ($minutes > 0) {
                    echo sprintf(_n('%d minute', '%d minutes', $minutes, 'amadex'), $minutes);
                    if ($seconds > 0) {
                        echo ' ' . sprintf(_n('and %d second', 'and %d seconds', $seconds, 'amadex'), $seconds);
                    }
                } else {
                    echo sprintf(_n('%d second', '%d seconds', $value, 'amadex'), $value);
                }
            ?>
            <br>
            <em><?php _e('Recommended: 420 seconds (7 minutes) for better user experience.', 'amadex'); ?></em>
        </p>
    <?php
            }

            /**
             * Popup Title callback
             */
            public function popup_title_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_title']) ? $options['popup_title'] : 'Exclusive Deals';
    ?>
        <input type="text" id="popup_title" name="amadex_popup_settings[popup_title]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Title displayed in the automatic call now popup.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup Description callback
             */
            public function popup_description_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_description']) ? $options['popup_description'] : 'Get the best deals on flights by calling our travel experts.';
    ?>
        <textarea id="popup_description" name="amadex_popup_settings[popup_description]" rows="3" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php _e('Description text displayed in the popup.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup Logo URL callback
             */
            public function popup_logo_url_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_logo_url']) ? $options['popup_logo_url'] : '';
    ?>
        <input type="url" id="popup_logo_url" name="amadex_popup_settings[popup_logo_url]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('URL of the logo image to display in the popup header.', 'amadex'); ?></p>
        <?php if ($value): ?>
            <div style="margin-top: 10px;">
                <strong>Preview:</strong><br>
                <img src="<?php echo esc_url($value); ?>" alt="Logo Preview" style="max-height: 40px; max-width: 120px; border: 1px solid #ddd; padding: 5px; background: #fff;">
            </div>
        <?php endif; ?>
    <?php
            }

            /**
             * Popup Price Type callback
             */
            public function popup_price_type_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_price_type']) ? $options['popup_price_type'] : 'none';
    ?>
        <select id="popup_price_type" name="amadex_popup_settings[popup_price_type]">
            <option value="none" <?php selected($value, 'none'); ?>><?php _e('Use API Price', 'amadex'); ?></option>
            <option value="fixed" <?php selected($value, 'fixed'); ?>><?php _e('Fixed Price', 'amadex'); ?></option>
            <option value="discount_percent" <?php selected($value, 'discount_percent'); ?>><?php _e('Discount % from API Price', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Choose how the popup price is calculated.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup Fixed Price callback
             */
            public function popup_price_fixed_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_price_fixed']) ? $options['popup_price_fixed'] : '';
    ?>
        <input type="number" step="0.01" min="0" id="popup_price_fixed" name="amadex_popup_settings[popup_price_fixed]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('If set to Fixed Price, this amount will be shown in the popup.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup Discount Percent callback
             */
            public function popup_discount_percent_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_discount_percent']) ? $options['popup_discount_percent'] : '';
    ?>
        <input type="number" step="0.01" min="0" max="100" id="popup_discount_percent" name="amadex_popup_settings[popup_discount_percent]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('If set to Discount %, the popup will show API Price minus this percent.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup Customer Service Image callback
             */
            public function popup_customer_service_image_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_customer_service_image']) ? $options['popup_customer_service_image'] : '';
    ?>
        <input type="url" id="popup_customer_service_image" name="amadex_popup_settings[popup_customer_service_image]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('URL of the customer service representative image to display in the popup right column.', 'amadex'); ?></p>
        <?php if ($value): ?>
            <div style="margin-top: 10px;">
                <strong>Preview:</strong><br>
                <img src="<?php echo esc_url($value); ?>" alt="Customer Service Preview" style="max-height: 150px; max-width: 200px; border: 1px solid #ddd; padding: 5px; background: #fff; border-radius: 8px;">
            </div>
        <?php endif; ?>
    <?php
            }

            /**
             * Popup Trust Years callback
             */
            public function popup_trust_years_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_trust_years']) ? $options['popup_trust_years'] : '20+';
    ?>
        <input type="text" id="popup_trust_years" name="amadex_popup_settings[popup_trust_years]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Text to display in the trust badge (e.g., "20+", "15+", etc.).', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup Trustpilot Rating callback
             */
            public function popup_trustpilot_rating_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_trustpilot_rating']) ? $options['popup_trustpilot_rating'] : '4.4';
    ?>
        <input type="text" id="popup_trustpilot_rating" name="amadex_popup_settings[popup_trustpilot_rating]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Trustpilot rating to display (e.g., "4.4", "4.5", etc.).', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup Countdown Minutes callback
             */
            public function popup_countdown_minutes_callback()
            {
                $options = get_option('amadex_popup_settings');
                $value = isset($options['popup_countdown_minutes']) ? $options['popup_countdown_minutes'] : 12;
    ?>
        <input type="number" min="1" max="60" id="popup_countdown_minutes" name="amadex_popup_settings[popup_countdown_minutes]" value="<?php echo esc_attr($value); ?>" class="small-text"> minutes
        <p class="description"><?php _e('Number of minutes for the countdown timer (1-60 minutes).', 'amadex'); ?></p>
    <?php
            }

            /**
             * Call Now Title callback
             */
            public function call_now_title_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['call_now_title']) ? $options['call_now_title'] : 'Exclusive Deals';
    ?>
        <input type="text" id="call_now_title" name="amadex_general_settings[call_now_title]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Title displayed in the call now popup.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Call Now Description callback
             */
            public function call_now_description_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['call_now_description']) ? $options['call_now_description'] : 'Get the best deals on flights by calling our travel experts.';
    ?>
        <textarea id="call_now_description" name="amadex_general_settings[call_now_description]" rows="3" cols="50" class="large-text"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php _e('Description text displayed in the call now popup.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Call Now Logo URL callback
             */
            public function call_now_logo_url_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['call_now_logo_url']) ? $options['call_now_logo_url'] : '';
    ?>
        <input type="url" id="call_now_logo_url" name="amadex_general_settings[call_now_logo_url]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('URL of the logo image to display in the call now popup header.', 'amadex'); ?></p>
        <?php if ($value): ?>
            <div style="margin-top: 10px;">
                <strong>Preview:</strong><br>
                <img src="<?php echo esc_url($value); ?>" alt="Logo Preview" style="max-height: 40px; max-width: 120px; border: 1px solid #ddd; padding: 5px; background: #fff;">
            </div>
        <?php endif; ?>
    <?php
            }

            /**
             * Popup price type callback
             */
            public function call_now_price_type_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['call_now_price_type']) ? $options['call_now_price_type'] : 'none';
    ?>
        <select id="call_now_price_type" name="amadex_general_settings[call_now_price_type]">
            <option value="none" <?php selected($value, 'none'); ?>><?php _e('Use API Price', 'amadex'); ?></option>
            <option value="fixed" <?php selected($value, 'fixed'); ?>><?php _e('Fixed Price', 'amadex'); ?></option>
            <option value="discount_percent" <?php selected($value, 'discount_percent'); ?>><?php _e('Discount % from API Price', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Choose how the popup price is calculated.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup fixed price callback
             */
            public function call_now_price_fixed_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['call_now_price_fixed']) ? $options['call_now_price_fixed'] : '';
    ?>
        <input type="number" step="0.01" min="0" id="call_now_price_fixed" name="amadex_general_settings[call_now_price_fixed]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('If set to Fixed Price, this amount will be shown in the popup.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Popup discount percent callback
             */
            public function call_now_price_discount_percent_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['call_now_price_discount_percent']) ? $options['call_now_price_discount_percent'] : '';
    ?>
        <input type="number" step="0.01" min="0" max="100" id="call_now_price_discount_percent" name="amadex_general_settings[call_now_price_discount_percent]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('If set to Discount %, the popup will show API Price minus this percent.', 'amadex'); ?></p>
    <?php
            }

// public function currency_conversion(){
//     $var = get_class_vars('amedex_currency_conversion');
    
// }


            /**
             * API section callback
             */
            public function api_section_callback()
            {
                echo '<p>' . __('Enter your Amadeus API credentials. You can obtain these from the <a href="https://developers.amadeus.com" target="_blank">Amadeus for Developers</a> portal.', 'amadex') . '</p>';
            }

            /**
             * Display section callback
             */
            public function display_section_callback()
            {
                echo '<p>' . __('Customize the appearance of the flight search form.', 'amadex') . '</p>';
            }

            /**
             * Pricing section callback
             */
            public function pricing_section_callback()
            {
                echo '<p>' . __('Configure price markup and adjustments for flight searches. Use positive values to increase prices (markup) or negative values to decrease prices (discount). These settings allow you to adjust the prices displayed to customers.', 'amadex') . '</p>';
                echo '<div class="notice notice-info inline"><p><strong>Note:</strong> Price adjustments are applied to all displayed prices in flight results. Negative values (e.g., -10) will decrease the price.</p></div>';
            }

            /**
             * Enable price markup callback
             */
            public function enable_price_markup_callback()
            {
                $options = get_option('amadex_pricing_settings');
                $value = isset($options['enable_price_markup']) ? $options['enable_price_markup'] : 0;
    ?>
        <label for="enable_price_markup">
            <input type="checkbox" id="enable_price_markup" name="amadex_pricing_settings[enable_price_markup]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable automatic price markup on all flights', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, markup will be applied to all flights before pricing rules are evaluated. This markup is applied globally to all flights and can be overridden by airline-specific markup settings below.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Price markup type callback
             */
            public function price_markup_type_callback()
            {
                $options = get_option('amadex_pricing_settings');
                $value = isset($options['price_markup_type']) ? $options['price_markup_type'] : 'percentage';
    ?>
        <select id="price_markup_type" name="amadex_pricing_settings[price_markup_type]">
            <option value="percentage" <?php selected($value, 'percentage'); ?>><?php _e('Percentage (%)', 'amadex'); ?></option>
            <option value="fixed" <?php selected($value, 'fixed'); ?>><?php _e('Fixed Amount ($)', 'amadex'); ?></option>
            <option value="both" <?php selected($value, 'both'); ?>><?php _e('Percentage + Fixed', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Choose how markup is calculated: percentage of price, fixed amount, or both combined.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Price markup percentage callback
             */
            public function price_markup_percentage_callback()
            {
                $options = get_option('amadex_pricing_settings');
                $value = isset($options['price_markup_percentage']) ? $options['price_markup_percentage'] : '10';
    ?>
        <input type="number" step="0.01" min="-100" max="100" id="price_markup_percentage" name="amadex_pricing_settings[price_markup_percentage]" value="<?php echo esc_attr($value); ?>" class="small-text"> %
        <p class="description"><?php _e('Percentage to adjust the original price. Use positive values to increase (markup) or negative values to decrease (discount) prices. Range: -100% to +100%.', 'amadex'); ?></p>
        <p class="description"><strong>Examples:</strong><br>
            • Positive: Original price $500 + 10% markup = $550 displayed to customer<br>
            • Negative: Original price $500 - 10% (enter -10) = $450 displayed to customer</p>
    <?php
            }

            /**
             * Price markup fixed callback
             */
            public function price_markup_fixed_callback()
            {
                $options = get_option('amadex_pricing_settings');
                $value = isset($options['price_markup_fixed']) ? $options['price_markup_fixed'] : '0';
    ?>
        <input type="number" step="0.01" id="price_markup_fixed" name="amadex_pricing_settings[price_markup_fixed]" value="<?php echo esc_attr($value); ?>" class="regular-text"> USD
        <p class="description"><?php _e('Fixed amount to adjust each ticket price. Use positive values to increase or negative values to decrease prices. Applied per passenger.', 'amadex'); ?></p>
        <p class="description"><strong>Examples:</strong><br>
            • Positive: Original price $500 + $25 fixed markup = $525 displayed to customer<br>
            • Negative: Original price $500 - $25 (enter -25) = $475 displayed to customer</p>
    <?php
            }

            /**
             * Airline specific markup callback
             */
            public function airline_specific_markup_callback()
            {
                $options = get_option('amadex_pricing_settings');
                $airlines = isset($options['airline_specific_markup']) ? $options['airline_specific_markup'] : '';
    ?>
        <textarea id="airline_specific_markup" name="amadex_pricing_settings[airline_specific_markup]" rows="8" cols="50" class="large-text code" placeholder="AA:15&#10;DL:12.5&#10;UA:-10"><?php echo esc_textarea($airlines); ?></textarea>
        <p class="description"><?php _e('Enter airline-specific markup percentages. One per line in format: AIRLINE_CODE:PERCENTAGE. Use positive values to increase or negative values to decrease prices.', 'amadex'); ?></p>
        <p class="description"><strong>Examples:</strong><br>
            AA:15 (American Airlines gets 15% markup/increase)<br>
            DL:12.5 (Delta gets 12.5% markup/increase)<br>
            UA:-10 (United gets 10% discount/decrease)<br>
            F9:-5 (Frontier gets 5% discount/decrease)</p>
        <p class="description" style="color: #d63638;"><strong>Note:</strong> Airline-specific markup overrides the global markup settings for that airline.</p>
    <?php
            }

            /**
             * Round prices callback
             */
            public function round_prices_callback()
            {
                $options = get_option('amadex_pricing_settings');
                $value = isset($options['round_prices']) ? $options['round_prices'] : 'none';
    ?>
        <select id="round_prices" name="amadex_pricing_settings[round_prices]">
            <option value="none" <?php selected($value, 'none'); ?>><?php _e('No Rounding', 'amadex'); ?></option>
            <option value="nearest_1" <?php selected($value, 'nearest_1'); ?>><?php _e('Round to Nearest $1', 'amadex'); ?></option>
            <option value="nearest_5" <?php selected($value, 'nearest_5'); ?>><?php _e('Round to Nearest $5', 'amadex'); ?></option>
            <option value="nearest_10" <?php selected($value, 'nearest_10'); ?>><?php _e('Round to Nearest $10', 'amadex'); ?></option>
            <option value="round_up_5" <?php selected($value, 'round_up_5'); ?>><?php _e('Round Up to Nearest $5', 'amadex'); ?></option>
            <option value="round_up_10" <?php selected($value, 'round_up_10'); ?>><?php _e('Round Up to Nearest $10', 'amadex'); ?></option>
            <option value="ending_99" <?php selected($value, 'ending_99'); ?>><?php _e('Psychological Pricing (ends in .99)', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Choose how final prices should be rounded for display.', 'amadex'); ?></p>
        <p class="description"><strong>Example:</strong> $523.45 rounded to nearest $10 = $520.00</p>
    <?php
            }

            /**
             * Enable confirmation discount callback
             */
            public function enable_confirmation_discount_callback()
            {
                $options = get_option('amadex_pricing_settings');
                $value = isset($options['enable_confirmation_discount']) ? $options['enable_confirmation_discount'] : 0;
    ?>
        <label for="enable_confirmation_discount">
            <input type="checkbox" id="enable_confirmation_discount" name="amadex_pricing_settings[enable_confirmation_discount]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable markup on confirmation page, NMI payment, and emails (hidden from users)', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, a markup percentage will be added to the original price. The final price (with markup) will be shown to users, but the markup breakdown is NOT displayed to users on confirmation page, NMI payment gateway, or confirmation emails. The markup details are only visible in the dashboard/admin view.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Confirmation discount percentage callback
             */
            public function confirmation_discount_percentage_callback()
            {
                $options = get_option('amadex_pricing_settings');
                $value = isset($options['confirmation_discount_percentage']) ? $options['confirmation_discount_percentage'] : '10';
    ?>
        <input type="number" step="0.01" min="0" max="100" id="confirmation_discount_percentage" name="amadex_pricing_settings[confirmation_discount_percentage]" value="<?php echo esc_attr($value); ?>" class="small-text"> %
        <p class="description"><?php _e('Percentage markup to add to the original price. This markup is applied to the price calculation but NOT shown to users on confirmation page, NMI payment gateway, or confirmation emails. The markup breakdown is only visible in the dashboard/admin view. Range: 0% to 100%.', 'amadex'); ?></p>
        <p class="description"><strong>Example:</strong> Original price $500 with 10% markup = $550 final price (markup of $50). Users will see $550 as the total, but the markup row is hidden from them.</p>
    <?php
            }

            /**
             * Pricing Rules Engine section callback
             */
            public function pricing_rules_section_callback()
            {
                echo '<p>' . __('The Pricing Rules Engine allows you to create dynamic pricing rules based on base price ranges. Each rule applies a discount/markup percentage (P_display) and a flat fee (P_charge).', 'amadex') . '</p>';
                echo '<div class="notice notice-info inline"><p><strong>How it works:</strong> When "Enable Price Markup" is enabled, markup is applied to all flights first, then pricing rules are evaluated. P_display = B_markup × (1 ± discount%) is shown to users (positive = discount/decrease, negative = markup/increase). P_charge = B_markup + flat_fee is charged for payment. Rules engine overrides legacy pricing when enabled.</p></div>';

                // Render pricing rules management UI
                $this->render_pricing_rules_ui();
            }

            /**
             * Enable Pricing Rules Engine callback
             */
            public function enable_pricing_rules_engine_callback()
            {
                $options = get_option('amadex_pricing_rules_settings', array());
                $value = isset($options['enable_pricing_rules_engine']) ? $options['enable_pricing_rules_engine'] : 0;
    ?>
        <label for="enable_pricing_rules_engine">
            <input type="checkbox" id="enable_pricing_rules_engine" name="amadex_pricing_rules_settings[enable_pricing_rules_engine]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable Pricing Rules Engine', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, the Pricing Rules Engine will apply markup settings (configured below) and pricing rules to all flights. Configure markup settings and rules below.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Render Pricing Rules Management UI
             */
            private function render_pricing_rules_ui()
            {
                $has_class = class_exists('Amadex_Pricing_Rules');
                $rules = array();

                if (!$has_class) {
                    echo '<div class="notice notice-error"><p>' . __('Pricing Rules Engine class not found. Please ensure the plugin is properly installed.', 'amadex') . '</p></div>';
                } else {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'amadex_pricing_rules';

                    // Check if table exists before querying
                    try {
                        $wpdb->suppress_errors(true);
                        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
                        $wpdb->suppress_errors(false);

                        if (!$table_exists) {
                            echo '<div class="notice notice-warning"><p>' . __('Pricing rules table does not exist. Please deactivate and reactivate the plugin to create the table.', 'amadex') . '</p></div>';
                        } else {
                            $wpdb->suppress_errors(true);
                            $rules = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY sort_order ASC, id ASC", ARRAY_A);
                            $wpdb->suppress_errors(false);

                            if ($wpdb->last_error) {
                                error_log('Amadex Pricing Rules: Database error - ' . $wpdb->last_error);
                                $rules = array();
                            }
                        }
                    } catch (Exception $e) {
                        error_log('Amadex Pricing Rules: Exception loading rules - ' . $e->getMessage());
                        echo '<div class="notice notice-error"><p>' . __('Error loading pricing rules. Please check error logs.', 'amadex') . '</p></div>';
                        $rules = array();
                    }
                }
    ?>
        <div id="amadex-pricing-rules-container" style="margin-top: 20px;">
            <h3><?php _e('Pricing Rules', 'amadex'); ?></h3>
            <p class="description"><?php _e('Create rules that match base prices and apply discount + flat fee. Rules are evaluated in priority order (lower sort_order = higher priority).', 'amadex'); ?></p>

            <table class="wp-list-table widefat fixed striped" id="amadex-pricing-rules-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php _e('Priority', 'amadex'); ?></th>
                        <th><?php _e('Name', 'amadex'); ?></th>
                        <th><?php _e('Currency', 'amadex'); ?></th>
                        <th><?php _e('Min Amount', 'amadex'); ?></th>
                        <th><?php _e('Max Amount', 'amadex'); ?></th>
                        <th><?php _e('Discount %', 'amadex'); ?></th>
                        <th><?php _e('Flat Fee', 'amadex'); ?></th>
                        <th><?php _e('Status', 'amadex'); ?></th>
                        <th><?php _e('Default', 'amadex'); ?></th>
                        <th style="width: 150px;"><?php _e('Actions', 'amadex'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rules)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 20px;">
                                <?php _e('No pricing rules found. Click "Add New Rule" to create one.', 'amadex'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rules as $rule): ?>
                            <tr data-rule-id="<?php echo esc_attr($rule['id']); ?>">
                                <td><?php echo esc_html($rule['sort_order']); ?></td>
                                <td><strong><?php echo esc_html($rule['name']); ?></strong></td>
                                <td><?php echo esc_html($rule['currency']); ?></td>
                                <td>$<?php echo number_format(floatval($rule['min_amount']), 2); ?></td>
                                <td><?php echo $rule['max_amount'] !== null ? '$' . number_format(floatval($rule['max_amount']), 2) : '∞'; ?></td>
                                <td><?php echo number_format(floatval($rule['discount_percent']), 2); ?>%</td>
                                <td>$<?php echo number_format(floatval($rule['flat_fee_amount']), 2); ?></td>
                                <td>
                                    <span class="status-<?php echo $rule['is_enabled'] ? 'enabled' : 'disabled'; ?>">
                                        <?php echo $rule['is_enabled'] ? __('Enabled', 'amadex') : __('Disabled', 'amadex'); ?>
                                    </span>
                                </td>
                                <td><?php echo $rule['is_default'] ? '✓' : '-'; ?></td>
                                <td>
                                    <button type="button" class="button button-small edit-rule" data-rule-id="<?php echo esc_attr($rule['id']); ?>"><?php _e('Edit', 'amadex'); ?></button>
                                    <button type="button" class="button button-small delete-rule" data-rule-id="<?php echo esc_attr($rule['id']); ?>"><?php _e('Delete', 'amadex'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p style="margin-top: 15px;">
                <button type="button" class="button button-primary" id="amadex-add-rule-btn"><?php _e('Add New Rule', 'amadex'); ?></button>
                <button type="button" class="button" id="amadex-simulate-pricing-btn"><?php _e('Pricing Simulator', 'amadex'); ?></button>
            </p>
        </div>
    <?php
                // Note: Modal is rendered outside settings form to avoid nested form issue
                // See render_pricing_rules_modal() called after settings form closes
            }

            /**
             * Render Pricing Rules Modal (outside settings form to avoid nested form issue)
             */
            private function render_pricing_rules_modal()
            {
    ?>
        <!-- Rule Form Modal - Rendered outside settings form to avoid nested form issue -->
        <div id="amadex-rule-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000; overflow-y: auto;">
            <div class="amadex-modal-content" style="max-width: 600px; margin: 50px auto; background: white; padding: 20px; border: 1px solid #ccc; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2 id="amadex-rule-modal-title"><?php _e('Add Pricing Rule', 'amadex'); ?></h2>
                <form id="amadex-rule-form" method="post" action="">
                    <input type="hidden" id="rule-id" name="rule_id" value="">
                    <table class="form-table">
                        <tr>
                            <th><label for="rule-name"><?php _e('Rule Name', 'amadex'); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="rule-name" name="name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="rule-currency"><?php _e('Currency', 'amadex'); ?></label></th>
                            <td>
                                <select id="rule-currency" name="currency">
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="INR">INR</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="rule-min-amount"><?php _e('Min Amount', 'amadex'); ?> <span class="required">*</span></label></th>
                            <td><input type="number" id="rule-min-amount" name="min_amount" step="0.01" min="0" class="small-text" required> <span class="description">Base price minimum</span></td>
                        </tr>
                        <tr>
                            <th><label for="rule-max-amount"><?php _e('Max Amount', 'amadex'); ?></label></th>
                            <td><input type="number" id="rule-max-amount" name="max_amount" step="0.01" min="0" class="small-text"> <span class="description">Leave empty for unlimited</span></td>
                        </tr>
                        <tr>
                            <th><label for="rule-discount"><?php _e('Discount/Markup %', 'amadex'); ?> <span class="required">*</span></label></th>
                            <td>
                                <input type="number" id="rule-discount" name="discount_percent" step="0.01" min="-100" max="100" class="small-text" required>
                                <p class="description">
                                    <?php _e('Percentage to adjust the original price. Use positive values to decrease (discount) or negative values to increase (markup) prices. Range: -100% to +100%.', 'amadex'); ?>
                                </p>
                                <p class="description" style="margin-top: 5px;">
                                    <strong><?php _e('Examples:', 'amadex'); ?></strong><br>
                                    <?php _e('• Positive: Original price $500 - 10% discount = $450 displayed to customer', 'amadex'); ?><br>
                                    <?php _e('• Negative: Original price $500 + 10% markup (enter -10) = $550 displayed to customer', 'amadex'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="rule-flat-fee"><?php _e('Flat Fee', 'amadex'); ?> <span class="required">*</span></label></th>
                            <td><input type="number" id="rule-flat-fee" name="flat_fee_amount" step="0.01" min="0.01" class="small-text" required> <span class="description">Must be > 0</span></td>
                        </tr>
                        <tr>
                            <th><label for="rule-sort-order"><?php _e('Priority (Sort Order)', 'amadex'); ?></label></th>
                            <td><input type="number" id="rule-sort-order" name="sort_order" step="1" min="0" value="0" class="small-text"> <span class="description">Lower = higher priority</span></td>
                        </tr>
                        <tr>
                            <th><label for="rule-enabled"><?php _e('Status', 'amadex'); ?></label></th>
                            <td>
                                <label><input type="checkbox" id="rule-enabled" name="is_enabled" value="1" checked> <?php _e('Enabled', 'amadex'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="rule-default"><?php _e('Default Rule', 'amadex'); ?></label></th>
                            <td>
                                <label><input type="checkbox" id="rule-default" name="is_default" value="1"> <?php _e('Use as fallback if no rule matches', 'amadex'); ?></label>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Save Rule', 'amadex'); ?></button>
                        <button type="button" class="button" id="amadex-cancel-rule"><?php _e('Cancel', 'amadex'); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <!-- Simulator Modal -->
        <div id="amadex-simulator-modal" style="display: none;">
            <div class="amadex-modal-content" style="max-width: 500px; margin: 50px auto; background: white; padding: 20px; border: 1px solid #ccc; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <h2><?php _e('Pricing Simulator', 'amadex'); ?></h2>
                <p class="description"><?php _e('Test how a base price will be calculated with current rules.', 'amadex'); ?></p>
                <table class="form-table">
                    <tr>
                        <th><label for="sim-base-price"><?php _e('Base Price (B)', 'amadex'); ?></label></th>
                        <td><input type="number" id="sim-base-price" step="0.01" min="0" class="regular-text" placeholder="500.00"></td>
                    </tr>
                    <tr>
                        <th><label for="sim-currency"><?php _e('Currency', 'amadex'); ?></label></th>
                        <td>
                            <select id="sim-currency">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="INR">INR</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="amadex-run-simulation"><?php _e('Calculate', 'amadex'); ?></button>
                    <button type="button" class="button" id="amadex-close-simulator"><?php _e('Close', 'amadex'); ?></button>
                </p>
                <div id="amadex-simulation-result" style="margin-top: 20px; padding: 15px; background: #f5f5f5; display: none;">
                    <h3><?php _e('Result', 'amadex'); ?></h3>
                    <div id="amadex-simulation-output"></div>
                </div>
            </div>
        </div>

        <style>
            .status-enabled {
                color: #46b450;
                font-weight: bold;
            }

            .status-disabled {
                color: #dc3232;
            }

            #amadex-rule-modal,
            #amadex-simulator-modal {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background: rgba(0, 0, 0, 0.5) !important;
                z-index: 100000 !important;
                overflow-y: auto !important;
            }

            #amadex-rule-modal .amadex-modal-content {
                position: relative;
                margin: 50px auto;
                background: white;
                padding: 20px;
                border: 1px solid #ccc;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                max-width: 600px;
            }
        </style>

        <script type="text/javascript">
            // Ensure ajaxurl is defined for WordPress admin
            if (typeof ajaxurl === 'undefined') {
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            }

            if (typeof jQuery !== 'undefined') {
                jQuery(document).ready(function($) {
                    // Function to check for form with retries
                    function checkFormExists(retries) {
                        retries = retries || 0;
                        var maxRetries = 10;

                        var $modal = $('#amadex-rule-modal');
                        var $form = $modal.length > 0 ? $modal.find('form#amadex-rule-form') : $('#amadex-rule-form');

                        // If form not found in modal, try finding any form in modal
                        if ($form.length === 0 && $modal.length > 0) {
                            $form = $modal.find('form').first();
                            if ($form.length > 0) {
                                $form.attr('id', 'amadex-rule-form');
                                console.log('Amadex Pricing Rules: Found form in modal and set ID');
                            }
                        }

                        if ($form.length === 0 && retries < maxRetries) {
                            console.log('Amadex Pricing Rules: Form not found, retrying... (' + (retries + 1) + '/' + maxRetries + ')');
                            setTimeout(function() {
                                checkFormExists(retries + 1);
                            }, 200);
                            return;
                        }

                        var $addBtn = $('#amadex-add-rule-btn');
                        console.log('Amadex Pricing Rules: Checking elements on load...');
                        console.log('Form exists:', $form.length > 0);
                        console.log('Modal exists:', $modal.length > 0);
                        console.log('Add button exists:', $addBtn.length > 0);

                        if ($form.length === 0) {
                            console.warn('Amadex Pricing Rules: Form not found in DOM after ' + maxRetries + ' retries');
                            console.log('All forms on page:', $('form').map(function() {
                                return this.id || '(no id)';
                            }).get());
                            console.log('Modal HTML preview:', $modal.length > 0 ? $modal.html().substring(0, 500) : 'Modal not found');
                        } else {
                            console.log('Amadex Pricing Rules: Form found successfully!');
                        }
                    }

                    // Start checking after a short delay
                    setTimeout(function() {
                        checkFormExists(0);
                    }, 100);

                    // Sync checkbox with hidden field for pricing rules engine
                    $('#enable_pricing_rules_engine').on('change', function() {
                        $('#hidden-enable-pricing-rules-engine').val($(this).is(':checked') ? '1' : '0');
                    });

                    // Add Rule
                    $(document).on('click', '#amadex-add-rule-btn', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        console.log('=== Add Rule Button Clicked ===');

                        // Always re-query elements fresh
                        var $modal = $('#amadex-rule-modal');
                        console.log('Modal found:', $modal.length);

                        if ($modal.length === 0) {
                            console.error('Modal #amadex-rule-modal not found');
                            alert('<?php _e('Error: Modal not found. Please refresh the page.', 'amadex'); ?>');
                            return false;
                        }

                        // Look for form inside modal - this is the key fix
                        var $form = $modal.find('form#amadex-rule-form');
                        console.log('Form found by ID in modal:', $form.length);

                        // If not found by ID, try finding any form in modal
                        if ($form.length === 0) {
                            $form = $modal.find('form').first();
                            console.log('Form found (any form) in modal:', $form.length);
                            if ($form.length > 0) {
                                $form.attr('id', 'amadex-rule-form');
                                console.log('Set form ID to amadex-rule-form');
                            }
                        }

                        // If still not found, check the modal content
                        if ($form.length === 0) {
                            var $modalContent = $modal.find('.amadex-modal-content');
                            console.log('Modal content found:', $modalContent.length);
                            $form = $modalContent.find('form').first();
                            console.log('Form in modal content:', $form.length);
                        }

                        // Last resort: check if form elements exist without form tag, or create form
                        if ($form.length === 0) {
                            console.warn('Form #amadex-rule-form not found, checking modal content...');
                            var $modalContent = $modal.find('.amadex-modal-content');
                            console.log('Modal content found:', $modalContent.length);
                            console.log('Modal HTML length:', $modalContent.length > 0 ? $modalContent.html().length : 0);

                            // Check if form elements exist but form tag is missing
                            var $existingInputs = $modalContent.find('input, select, textarea, button[type="submit"]');
                            console.log('Existing form elements found:', $existingInputs.length);

                            if ($existingInputs.length > 0) {
                                // Form elements exist but form tag is missing - wrap them in a form
                                console.log('Found form elements without form tag, wrapping in form');
                                var $title = $modalContent.find('h2').first();
                                var $afterTitle = $title.nextAll();

                                // Create form and move elements into it
                                $form = $('<form>', {
                                    id: 'amadex-rule-form',
                                    method: 'post',
                                    action: ''
                                });

                                $title.after($form);
                                $afterTitle.appendTo($form);
                                console.log('Form created by wrapping existing elements');
                            } else {
                                // No form elements found - create complete form from scratch
                                console.log('No form elements found, creating complete form from scratch');
                                var $title = $modalContent.find('h2');
                                var formHTML = '<form id="amadex-rule-form" method="post" action="">' +
                                    '<input type="hidden" id="rule-id" name="rule_id" value="">' +
                                    '<table class="form-table">' +
                                    '<tr><th><label for="rule-name">Rule Name <span class="required">*</span></label></th>' +
                                    '<td><input type="text" id="rule-name" name="name" class="regular-text" required></td></tr>' +
                                    '<tr><th><label for="rule-currency">Currency</label></th>' +
                                    '<td><select id="rule-currency" name="currency"><option value="USD">USD</option><option value="EUR">EUR</option><option value="GBP">GBP</option><option value="INR">INR</option></select></td></tr>' +
                                    '<tr><th><label for="rule-min-amount">Min Amount <span class="required">*</span></label></th>' +
                                    '<td><input type="number" id="rule-min-amount" name="min_amount" step="0.01" min="0" class="small-text" required> <span class="description">Base price minimum</span></td></tr>' +
                                    '<tr><th><label for="rule-max-amount">Max Amount</label></th>' +
                                    '<td><input type="number" id="rule-max-amount" name="max_amount" step="0.01" min="0" class="small-text"> <span class="description">Leave empty for unlimited</span></td></tr>' +
                                    '<tr><th><label for="rule-discount">Discount % <span class="required">*</span></label></th>' +
                                    '<td><input type="number" id="rule-discount" name="discount_percent" step="0.01" min="-100" max="100" class="small-text" required> <span class="description">-100% to +100%</span></td></tr>' +
                                    '<tr><th><label for="rule-flat-fee">Flat Fee <span class="required">*</span></label></th>' +
                                    '<td><input type="number" id="rule-flat-fee" name="flat_fee_amount" step="0.01" min="0.01" class="small-text" required> <span class="description">Must be > 0</span></td></tr>' +
                                    '<tr><th><label for="rule-sort-order">Priority (Sort Order)</label></th>' +
                                    '<td><input type="number" id="rule-sort-order" name="sort_order" step="1" min="0" value="0" class="small-text"> <span class="description">Lower = higher priority</span></td></tr>' +
                                    '<tr><th><label for="rule-enabled">Status</label></th>' +
                                    '<td><label><input type="checkbox" id="rule-enabled" name="is_enabled" value="1" checked> Enabled</label></td></tr>' +
                                    '<tr><th><label for="rule-default">Default Rule</label></th>' +
                                    '<td><label><input type="checkbox" id="rule-default" name="is_default" value="1"> Use as fallback if no rule matches</label></td></tr>' +
                                    '</table>' +
                                    '<p class="submit">' +
                                    '<button type="submit" class="button button-primary">Save Rule</button> ' +
                                    '<button type="button" class="button" id="amadex-cancel-rule">Cancel</button>' +
                                    '</p>' +
                                    '</form>';

                                if ($title.length > 0) {
                                    $title.after(formHTML);
                                } else {
                                    $modalContent.prepend(formHTML);
                                }
                                $form = $modal.find('form#amadex-rule-form');
                                console.log('Form created from scratch, found:', $form.length);
                            }

                            if ($form.length === 0) {
                                console.error('CRITICAL: Failed to create form dynamically');
                                console.error('Modal content HTML:', $modalContent.length > 0 ? $modalContent.html().substring(0, 1000) : 'No modal content');
                                alert('<?php _e('Error: Unable to create form. Please refresh the page and check for PHP errors.', 'amadex'); ?>');
                                return false;
                            } else {
                                console.log('Form created/found successfully after dynamic creation');
                            }
                        } else {
                            console.log('Form found successfully on click');
                        }

                        // Final check - form must exist
                        if ($form.length === 0) {
                            console.error('CRITICAL: Form still not found after all attempts');
                            console.error('Modal HTML preview:', $modal.html().substring(0, 500));
                            alert('<?php _e('Critical Error: Form not found. Please refresh the page.', 'amadex'); ?>');
                            return false;
                        }

                        console.log('=== Form is ready, proceeding with setup ===');

                        // Reset form - use form context
                        $form.find('#rule-id').val('');
                        if ($form[0] && typeof $form[0].reset === 'function') {
                            $form[0].reset();
                        }

                        // Set default values - use form context
                        $form.find('#rule-enabled').prop('checked', true);
                        $form.find('#rule-default').prop('checked', false);
                        $form.find('#rule-sort-order').val('0');
                        $form.find('#rule-currency').val('USD');

                        // Update modal title and show
                        var $title = $modal.find('#amadex-rule-modal-title');
                        if ($title.length > 0) {
                            $title.text('<?php _e('Add Pricing Rule', 'amadex'); ?>');
                        } else {
                            console.warn('Modal title element not found');
                        }

                        // Show modal with proper styling
                        $modal.css({
                            'display': 'block',
                            'position': 'fixed',
                            'top': '0',
                            'left': '0',
                            'width': '100%',
                            'height': '100%',
                            'z-index': '100000',
                            'background': 'rgba(0,0,0,0.5)'
                        }).show();

                        console.log('Modal displayed. Form ready:', $form.length, 'Form ID:', $form.attr('id'));

                        return false;
                    });

                    // Edit Rule (use event delegation for dynamically added buttons)
                    $(document).on('click', '.edit-rule', function() {
                        var ruleId = $(this).data('rule-id');
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'amadex_get_pricing_rule',
                                rule_id: ruleId,
                                nonce: '<?php echo wp_create_nonce('amadex_pricing_rules'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    var rule = response.data;
                                    $('#rule-id').val(rule.id);
                                    $('#rule-name').val(rule.name);
                                    $('#rule-currency').val(rule.currency);
                                    $('#rule-min-amount').val(rule.min_amount);
                                    $('#rule-max-amount').val(rule.max_amount || '');
                                    $('#rule-discount').val(rule.discount_percent);
                                    $('#rule-flat-fee').val(rule.flat_fee_amount);
                                    $('#rule-sort-order').val(rule.sort_order);
                                    $('#rule-enabled').prop('checked', rule.is_enabled == 1);
                                    $('#rule-default').prop('checked', rule.is_default == 1);
                                    $('#amadex-rule-modal-title').text('<?php _e('Edit Pricing Rule', 'amadex'); ?>');
                                    $('#amadex-rule-modal').show();
                                } else {
                                    alert(response.data && response.data.message ? response.data.message : '<?php _e('Error loading rule', 'amadex'); ?>');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', error);
                                alert('<?php _e('An error occurred while loading the rule.', 'amadex'); ?>');
                            }
                        });
                    });

                    // Save Rule (use event delegation)
                    $(document).on('submit', '#amadex-rule-form', function(e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var $form = $(this);
                        if ($form.length === 0) {
                            console.error('Form not found');
                            alert('<?php _e('Error: Form not found. Please refresh the page.', 'amadex'); ?>');
                            return false;
                        }

                        // Get form data - ensure all fields are included
                        var formData = $form.serialize();

                        // Also manually collect checkbox values if they're not in serialized data
                        if ($('#rule-enabled').is(':checked')) {
                            if (formData.indexOf('is_enabled') === -1) {
                                formData += (formData ? '&' : '') + 'is_enabled=1';
                            }
                        }
                        if ($('#rule-default').is(':checked')) {
                            if (formData.indexOf('is_default') === -1) {
                                formData += (formData ? '&' : '') + 'is_default=1';
                            }
                        }
                        var $submitBtn = $form.find('button[type="submit"]');
                        var originalBtnText = $submitBtn.length > 0 ? $submitBtn.text() : '<?php _e('Save Rule', 'amadex'); ?>';

                        if ($submitBtn.length > 0) {
                            $submitBtn.prop('disabled', true).text('<?php _e('Saving...', 'amadex'); ?>');
                        }

                        console.log('Saving rule with data:', formData);

                        // Validate required fields
                        var ruleName = $('#rule-name').val();
                        if (!ruleName || ruleName.trim() === '') {
                            alert('<?php _e('Rule name is required', 'amadex'); ?>');
                            if ($submitBtn.length > 0) {
                                $submitBtn.prop('disabled', false).text(originalBtnText);
                            }
                            return false;
                        }

                        var minAmount = parseFloat($('#rule-min-amount').val());
                        if (!minAmount || minAmount <= 0) {
                            alert('<?php _e('Minimum amount must be greater than 0', 'amadex'); ?>');
                            if ($submitBtn.length > 0) {
                                $submitBtn.prop('disabled', false).text(originalBtnText);
                            }
                            return false;
                        }

                        var flatFee = parseFloat($('#rule-flat-fee').val());
                        if (!flatFee || flatFee <= 0) {
                            alert('<?php _e('Flat fee must be greater than 0', 'amadex'); ?>');
                            if ($submitBtn.length > 0) {
                                $submitBtn.prop('disabled', false).text(originalBtnText);
                            }
                            return false;
                        }

                        var discountPercent = parseFloat($('#rule-discount').val());
                        if (isNaN(discountPercent) || discountPercent < -100 || discountPercent > 100) {
                            alert('<?php _e('Percentage must be between -100% and +100%. Use positive values for discount (decrease) or negative values for markup (increase).', 'amadex'); ?>');
                            if ($submitBtn.length > 0) {
                                $submitBtn.prop('disabled', false).text(originalBtnText);
                            }
                            return false;
                        }

                        // Ensure ajaxurl is defined
                        var ajaxUrl = typeof ajaxurl !== 'undefined' ? ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';

                        console.log('Submitting form to:', ajaxUrl);
                        console.log('Form data:', formData);
                        console.log('Rule name:', ruleName);
                        console.log('Min amount:', minAmount);
                        console.log('Flat fee:', flatFee);

                        // Show loading state
                        console.log('Sending AJAX request to save rule...');
                        console.log('AJAX URL:', ajaxUrl);
                        console.log('Form data string:', formData);

                        $.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            dataType: 'json',
                            timeout: 30000, // 30 second timeout
                            data: {
                                action: 'amadex_save_pricing_rule',
                                form_data: formData,
                                nonce: '<?php echo wp_create_nonce('amadex_pricing_rules'); ?>'
                            },
                            beforeSend: function() {
                                console.log('AJAX request starting...');
                            },
                            success: function(response) {
                                console.log('AJAX Success - Full response:', response);
                                console.log('Response success:', response ? response.success : 'no response');
                                console.log('Response data:', response ? response.data : 'no data');

                                if (response && response.success) {
                                    alert('<?php _e('Rule saved successfully!', 'amadex'); ?>');
                                    setTimeout(function() {
                                        location.reload();
                                    }, 500);
                                } else {
                                    var errorMsg = '<?php _e('Error saving rule', 'amadex'); ?>';
                                    if (response && response.data) {
                                        if (response.data.message) {
                                            errorMsg = response.data.message;
                                        } else if (typeof response.data === 'string') {
                                            errorMsg = response.data;
                                        }
                                    }
                                    alert(errorMsg);
                                    if ($submitBtn.length > 0) {
                                        $submitBtn.prop('disabled', false).text(originalBtnText);
                                    }
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', status, error);
                                console.error('Status Code:', xhr.status);
                                console.error('Response Text:', xhr.responseText);
                                console.error('Request URL:', ajaxUrl);
                                console.error('Request Data:', {
                                    action: 'amadex_save_pricing_rule',
                                    form_data: formData,
                                    nonce: '<?php echo wp_create_nonce('amadex_pricing_rules'); ?>'
                                });

                                var errorMsg = '<?php _e('An error occurred while saving the rule.', 'amadex'); ?>';
                                if (xhr.responseText) {
                                    try {
                                        var errorResponse = JSON.parse(xhr.responseText);
                                        if (errorResponse.data && errorResponse.data.message) {
                                            errorMsg = errorResponse.data.message;
                                        } else if (errorResponse.message) {
                                            errorMsg = errorResponse.message;
                                        }
                                    } catch (e) {
                                        console.error('Could not parse error response:', e);
                                        // Try to extract error message from HTML response
                                        var htmlMatch = xhr.responseText.match(/<p[^>]*>(.*?)<\/p>/i);
                                        if (htmlMatch && htmlMatch[1]) {
                                            errorMsg = htmlMatch[1];
                                        }
                                    }
                                }

                                // Show detailed error
                                alert(errorMsg + '\n\nStatus: ' + status + '\nError: ' + error + '\n\nPlease check the browser console and WordPress debug logs for more details.');

                                if ($submitBtn.length > 0) {
                                    $submitBtn.prop('disabled', false).text(originalBtnText);
                                }
                            }
                        });

                        return false;
                    });

                    // Delete Rule (use event delegation)
                    $(document).on('click', '.delete-rule', function() {
                        if (!confirm('<?php _e('Are you sure you want to delete this rule?', 'amadex'); ?>')) return;
                        var ruleId = $(this).data('rule-id');
                        var $btn = $(this);
                        $btn.prop('disabled', true);

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'amadex_delete_pricing_rule',
                                rule_id: ruleId,
                                nonce: '<?php echo wp_create_nonce('amadex_pricing_rules'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('<?php _e('Rule deleted successfully!', 'amadex'); ?>');
                                    location.reload();
                                } else {
                                    alert(response.data && response.data.message ? response.data.message : '<?php _e('Error deleting rule', 'amadex'); ?>');
                                    $btn.prop('disabled', false);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('AJAX Error:', error);
                                alert('<?php _e('An error occurred while deleting the rule.', 'amadex'); ?>');
                                $btn.prop('disabled', false);
                            }
                        });
                    });

                    // Simulator
                    $('#amadex-simulate-pricing-btn').on('click', function() {
                        $('#amadex-simulator-modal').show();
                    });

                    $('#amadex-run-simulation').on('click', function() {
                        var basePrice = parseFloat($('#sim-base-price').val());
                        var currency = $('#sim-currency').val();
                        if (!basePrice || basePrice <= 0) {
                            alert('<?php _e('Please enter a valid base price', 'amadex'); ?>');
                            return;
                        }
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'amadex_simulate_pricing',
                                base_price: basePrice,
                                currency: currency,
                                nonce: '<?php echo wp_create_nonce('amadex_pricing_rules'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    var result = response.data;
                                    var html = '<p><strong>Matched Rule:</strong> ' + (result.matched_rule ? result.matched_rule.name : '<?php _e('None', 'amadex'); ?>') + '</p>';
                                    html += '<p><strong>P_display:</strong> $' + parseFloat(result.p_display).toFixed(2) + '</p>';
                                    html += '<p><strong>P_charge:</strong> $' + parseFloat(result.p_charge).toFixed(2) + '</p>';
                                    var discountPercent = parseFloat(result.discount_percent);
                                    var discountLabel = discountPercent >= 0 ? 'Discount' : 'Markup';
                                    html += '<p><strong>' + discountLabel + ':</strong> ' + Math.abs(discountPercent).toFixed(2) + '% ($' + parseFloat(result.discount_amount).toFixed(2) + ')</p>';
                                    html += '<p><strong>Flat Fee:</strong> $' + parseFloat(result.flat_fee).toFixed(2) + '</p>';
                                    $('#amadex-simulation-output').html(html);
                                    $('#amadex-simulation-result').show();
                                }
                            }
                        });
                    });

                    // Close modal handlers (use event delegation)
                    $(document).on('click', '#amadex-cancel-rule', function(e) {
                        e.preventDefault();
                        $('#amadex-rule-modal').hide();
                    });

                    $(document).on('click', '#amadex-close-simulator', function(e) {
                        e.preventDefault();
                        $('#amadex-simulator-modal').hide();
                    });

                    // Close modal when clicking outside
                    $(document).on('click', '#amadex-rule-modal', function(e) {
                        if ($(e.target).attr('id') === 'amadex-rule-modal') {
                            $(this).hide();
                        }
                    });
                });
            } else {
                console.error('Amadex Pricing Rules: jQuery is not available');
            }
        </script>
    <?php
            }

            /**
             * Advanced section callback
             */
            public function advanced_section_callback()
            {
                echo '<p>' . __('Advanced settings for experienced users. Only modify these if you know what you\'re doing.', 'amadex') . '</p>';
            }

            /**
             * Enable plugin callback
             */
            public function enable_plugin_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['enable_plugin']) ? $options['enable_plugin'] : 1;
    ?>
        <label for="enable_plugin">
            <input type="checkbox" id="enable_plugin" name="amadex_general_settings[enable_plugin]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable Amadex plugin functionality', 'amadex'); ?>
        </label>
    <?php
            }

            /**
             * Cache duration callback
             */
            public function cache_duration_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['cache_duration']) ? $options['cache_duration'] : 60;
    ?>
        <input type="number" id="cache_duration" name="amadex_general_settings[cache_duration]" value="<?php echo esc_attr($value); ?>" min="0" step="1" class="small-text">
        <p class="description"><?php _e('Duration in minutes to cache API responses. Set to 0 to disable caching.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Booking confirmation page callback
             */
            public function booking_confirmation_page_callback()
            {
                $options = get_option('amadex_general_settings');
                $selected = isset($options['booking_confirmation_page']) ? intval($options['booking_confirmation_page']) : 0;

                wp_dropdown_pages(array(
                    'name' => 'amadex_general_settings[booking_confirmation_page]',
                    'selected' => $selected,
                    'show_option_none' => __('— Select a page —', 'amadex'),
                    'option_none_value' => 0
                ));
    ?>
        <p class="description">
            <?php _e('Select the page that contains the [amadex_booking_confirmation] shortcode. This is where customers are redirected after submitting the booking form.', 'amadex'); ?>
        </p>
    <?php
            }

            /**
             * Admin notification email callback
             */
            public function notification_email_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');
    ?>
        <input type="email" id="notification_email" name="amadex_general_settings[notification_email]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <button type="button" class="button" id="amadex-test-email-btn" style="margin-left: 10px;"><?php _e('Test Email', 'amadex'); ?></button>
        <span class="spinner" id="amadex-test-email-spinner" style="float: none; margin: 0 10px;"></span>
        <span id="amadex-test-email-result" style="margin-left: 10px;"></span>
        <p class="description"><?php _e('Primary email that receives verified lead alerts and confirmation copies. Defaults to the site admin email.', 'amadex'); ?></p>
        <p class="description" style="color: #d63638;">
            <strong><?php _e('Note:', 'amadex'); ?></strong>
            <?php _e('If emails are not being sent, install the', 'amadex'); ?>
            <a href="https://wordpress.org/plugins/wp-mail-smtp/" target="_blank"><?php _e('WP Mail SMTP', 'amadex'); ?></a>
            <?php _e('plugin to configure proper email delivery.', 'amadex'); ?>
        </p>
        <script>
            jQuery(document).ready(function($) {
                $('#amadex-test-email-btn').on('click', function() {
                    var btn = $(this);
                    var spinner = $('#amadex-test-email-spinner');
                    var result = $('#amadex-test-email-result');
                    var email = $('#notification_email').val();

                    if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                        result.html('<span style="color: red;"><?php _e('Please enter a valid email address', 'amadex'); ?></span>');
                        return;
                    }

                    btn.prop('disabled', true);
                    spinner.addClass('is-active');
                    result.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'amadex_test_email',
                            nonce: '<?php echo wp_create_nonce('amadex_nonce'); ?>',
                            test_email: email
                        },
                        success: function(response) {
                            if (response.success) {
                                result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                            } else {
                                result.html('<span style="color: red;">✗ ' + (response.data.message || '<?php _e('Failed to send test email', 'amadex'); ?>') + '</span>');
                            }
                        },
                        error: function() {
                            result.html('<span style="color: red;">✗ <?php _e('Error: Could not send test email', 'amadex'); ?></span>');
                        },
                        complete: function() {
                            btn.prop('disabled', false);
                            spinner.removeClass('is-active');
                        }
                    });
                });
            });
        </script>
    <?php
            }

            /**
             * Agent notification emails callback
             */
            public function agent_notification_emails_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['agent_notification_emails']) ? $options['agent_notification_emails'] : '';
    ?>
        <textarea id="agent_notification_emails" name="amadex_general_settings[agent_notification_emails]" rows="3" class="large-text" placeholder="agent1@example.com, agent2@example.com"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php _e('Optional: comma-separated list of agent emails to receive new booking alerts.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Google Places API Key callback
             */
            public function google_places_api_key_callback()
            {
                $options = get_option('amadex_general_settings');
                $value = isset($options['google_places_api_key']) ? $options['google_places_api_key'] : '';
    ?>
        <input type="text" id="google_places_api_key" name="amadex_general_settings[google_places_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" style="width: 100%; max-width: 500px;" placeholder="Enter your Google Places API Key">
        <button type="button" class="button button-small" onclick="document.getElementById('google_places_api_key').type = document.getElementById('google_places_api_key').type === 'password' ? 'text' : 'password';" style="margin-left: 5px;">
            <span class="dashicons dashicons-visibility"></span> Show/Hide
        </button>
        <p class="description">
            <?php _e('Your Google Cloud API Key with Places API enabled. This key is used for address autocomplete in the billing information form.', 'amadex'); ?>
        </p>
        <p class="description" style="margin-top: 10px;">
            <strong><?php _e('How to get your API Key:', 'amadex'); ?></strong>
        </p>
        <ol style="margin-left: 20px; margin-top: 5px;">
            <li><?php _e('Go to', 'amadex'); ?> <a href="https://console.cloud.google.com/" target="_blank"><?php _e('Google Cloud Console', 'amadex'); ?></a></li>
            <li><?php _e('Create a new project or select an existing one', 'amadex'); ?></li>
            <li><?php _e('Enable the "Places API" for your project', 'amadex'); ?></li>
            <li><?php _e('Go to "APIs & Services" → "Credentials"', 'amadex'); ?></li>
            <li><?php _e('Click "Create Credentials" → "API Key"', 'amadex'); ?></li>
            <li><?php _e('Copy the API key and paste it above', 'amadex'); ?></li>
            <li><?php _e('(Optional) Restrict the API key to "Places API" for security', 'amadex'); ?></li>
        </ol>
        <p class="description" style="color: #d63638; font-weight: 600; margin-top: 10px;">
            ⚠️ <strong><?php _e('Important:', 'amadex'); ?></strong> <?php _e('Make sure the Places API is enabled in your Google Cloud project, otherwise address autocomplete will not work.', 'amadex'); ?>
        </p>
        <?php if (!empty($value)): ?>
            <p class="description" style="color: #46b450; margin-top: 10px;">
                ✓ <?php _e('API Key is set. Address autocomplete will be enabled on the booking page.', 'amadex'); ?>
            </p>
        <?php else: ?>
            <p class="description" style="color: #d63638; margin-top: 10px;">
                ⚠️ <?php _e('API Key is not set. Address autocomplete will be disabled until you add a valid key.', 'amadex'); ?>
            </p>
        <?php endif; ?>
    <?php
            }

            /**
             * API key callback
             */
            public function api_key_callback()
            {
                $options = get_option('amadex_api_settings');
                $value = isset($options['api_key']) ? $options['api_key'] : '';
    ?>
        <input type="text" id="api_key" name="amadex_api_settings[api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" style="width: 100%; max-width: 500px;">
        <p class="description"><?php _e('Your Amadeus API Key (Client ID). Make sure there are no extra spaces before or after the key.', 'amadex'); ?></p>
    <?php
            }

            /**
             * API secret callback
             */
            public function api_secret_callback()
            {
                $options = get_option('amadex_api_settings');
                $value = isset($options['api_secret']) ? $options['api_secret'] : '';
    ?>
        <input type="password" id="api_secret" name="amadex_api_settings[api_secret]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Your Amadeus API Secret (Client Secret)', 'amadex'); ?></p>
        <p class="description" style="color: #d63638; font-weight: 600; margin-top: 10px;">
            ⚠️ <strong>Important:</strong> Make sure your API Key and API Secret match the selected Environment (Test/Production).
            Production keys will NOT work with Test environment and vice versa.
        </p>
    <?php
            }

            /**
             * Environment callback
             */
            public function environment_callback()
            {
                $options = get_option('amadex_api_settings');
                $value = isset($options['environment']) ? $options['environment'] : 'test';
    ?>
        <select id="environment" name="amadex_api_settings[environment]">
            <option value="test" <?php selected('test', $value); ?>><?php _e('Test', 'amadex'); ?></option>
            <option value="production" <?php selected('production', $value); ?>><?php _e('Production', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Choose between test and production environments. Use test for development.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Clear token cache callback
             */
            public function clear_token_cache_callback()
            {
    ?>
        <button type="button" id="clear-token-cache" class="button button-secondary">
            <?php _e('Clear Token Cache', 'amadex'); ?>
        </button>
        <button type="button" id="test-api-connection" class="button button-primary">
            <?php _e('Test API Connection', 'amadex'); ?>
        </button>
        <div id="api-test-result" style="margin-top: 10px;"></div>
        <p class="description">
            <?php _e('Clear cached access tokens if you\'re experiencing 401 errors. Test connection to verify your API credentials.', 'amadex'); ?>
        </p>

        <script>
            jQuery(document).ready(function($) {
                $('#clear-token-cache').on('click', function() {
                    if (confirm('<?php _e('Are you sure you want to clear the token cache?', 'amadex'); ?>')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'amadex_clear_token_cache',
                                nonce: '<?php echo wp_create_nonce('amadex_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#api-test-result').html('<div class="notice notice-success"><p><?php _e('Token cache cleared successfully!', 'amadex'); ?></p></div>');
                                } else {
                                    $('#api-test-result').html('<div class="notice notice-error"><p><?php _e('Error clearing token cache:', 'amadex'); ?> ' + response.data + '</p></div>');
                                }
                            }
                        });
                    }
                });

                $('#test-api-connection').on('click', function() {
                    $('#api-test-result').html('<p><?php _e('Testing API connection...', 'amadex'); ?></p>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'amadex_test_api',
                            nonce: '<?php echo wp_create_nonce('amadex_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#api-test-result').html(
                                    '<div class="notice notice-success">' +
                                    '<p><strong><?php _e('Success:', 'amadex'); ?></strong> ' + response.data.message + '</p>' +
                                    '<p><strong><?php _e('Token Length:', 'amadex'); ?></strong> ' + response.data.token_length + '</p>' +
                                    '<p><strong><?php _e('API Key Set:', 'amadex'); ?></strong> ' + (response.data.details.api_key_set ? '<?php _e('Yes', 'amadex'); ?>' : '<?php _e('No', 'amadex'); ?>') + '</p>' +
                                    '<p><strong><?php _e('API Secret Set:', 'amadex'); ?></strong> ' + (response.data.details.api_secret_set ? '<?php _e('Yes', 'amadex'); ?>' : '<?php _e('No', 'amadex'); ?>') + '</p>' +
                                    '</div>'
                                );
                            } else {
                                $('#api-test-result').html(
                                    '<div class="notice notice-error">' +
                                    '<p><strong><?php _e('Error:', 'amadex'); ?></strong> ' + response.data.message + '</p>' +
                                    '<p><strong><?php _e('API Key Set:', 'amadex'); ?></strong> ' + (response.data.details.api_key_set ? '<?php _e('Yes', 'amadex'); ?>' : '<?php _e('No', 'amadex'); ?>') + '</p>' +
                                    '<p><strong><?php _e('API Secret Set:', 'amadex'); ?></strong> ' + (response.data.details.api_secret_set ? '<?php _e('Yes', 'amadex'); ?>' : '<?php _e('No', 'amadex'); ?>') + '</p>' +
                                    '</div>'
                                );
                            }
                        }
                    });
                });
            });
        </script>
    <?php
            }

            /**
             * Search form title callback
             */
            public function search_form_title_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['search_form_title']) ? $options['search_form_title'] : __('Flight Search', 'amadex');
    ?>
        <input type="text" id="search_form_title" name="amadex_display_settings[search_form_title]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Title displayed above the search form', 'amadex'); ?></p>
    <?php
            }

            /**
             * Button text callback
             */
            public function button_text_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['button_text']) ? $options['button_text'] : __('Search Flights', 'amadex');
    ?>
        <input type="text" id="button_text" name="amadex_display_settings[button_text]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Text for the search button', 'amadex'); ?></p>
    <?php
            }

            /**
             * Default theme callback
             */
            public function default_theme_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['default_theme']) ? $options['default_theme'] : 'light';
    ?>
        <select id="default_theme" name="amadex_display_settings[default_theme]">
            <option value="light" <?php selected('light', $value); ?>><?php _e('Light', 'amadex'); ?></option>
            <option value="dark" <?php selected('dark', $value); ?>><?php _e('Dark', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Default theme for the search form. Can be overridden in the shortcode.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Custom CSS callback
             */
            public function custom_css_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['custom_css']) ? $options['custom_css'] : '';
    ?>
        <textarea id="custom_css" name="amadex_display_settings[custom_css]" rows="8" cols="50" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php _e('Add custom CSS to style the search form and results', 'amadex'); ?></p>
    <?php
            }

            /**
             * Viewers Badge Enabled callback
             */
            public function viewers_badge_enabled_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['viewers_badge_enabled']) ? $options['viewers_badge_enabled'] : 0;
    ?>
        <label for="viewers_badge_enabled">
            <input type="checkbox" id="viewers_badge_enabled" name="amadex_display_settings[viewers_badge_enabled]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable "Viewers" Badge on Flight Cards', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, a badge showing random viewer counts will appear on the left corner edge of each flight card to create social proof and urgency.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Viewers Badge Minimum callback
             */
            public function viewers_badge_min_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['viewers_badge_min']) ? $options['viewers_badge_min'] : 12;
    ?>
        <input type="number" id="viewers_badge_min" name="amadex_display_settings[viewers_badge_min]" value="<?php echo esc_attr($value); ?>" min="1" max="999" step="1" class="small-text">
        <p class="description"><?php _e('Minimum number of viewers to display (random numbers will be generated between min and max). Range: 1-999', 'amadex'); ?></p>
    <?php
            }

            /**
             * Viewers Badge Maximum callback
             */
            public function viewers_badge_max_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['viewers_badge_max']) ? $options['viewers_badge_max'] : 89;
    ?>
        <input type="number" id="viewers_badge_max" name="amadex_display_settings[viewers_badge_max]" value="<?php echo esc_attr($value); ?>" min="1" max="999" step="1" class="small-text">
        <p class="description"><?php _e('Maximum number of viewers to display (random numbers will be generated between min and max). Range: 1-999', 'amadex'); ?></p>
    <?php
            }

            /**
             * Viewers Badge Text callback
             */
            public function viewers_badge_text_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['viewers_badge_text']) ? $options['viewers_badge_text'] : 'people exploring';
    ?>
        <input type="text" id="viewers_badge_text" name="amadex_display_settings[viewers_badge_text]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="people exploring">
        <p class="description"><?php _e('Text to display after the viewer count (e.g., "people exploring", "people viewing", "viewers"). The badge will display as: "[number] [text]".', 'amadex'); ?></p>
    <?php
            }

            /**
             * Viewers Badge Position callback
             */
            public function viewers_badge_position_callback()
            {
                $options = get_option('amadex_display_settings');
                $value = isset($options['viewers_badge_position']) ? $options['viewers_badge_position'] : 'top-left';
    ?>
        <select id="viewers_badge_position" name="amadex_display_settings[viewers_badge_position]">
            <option value="top-left" <?php selected($value, 'top-left'); ?>><?php _e('Top Left Corner', 'amadex'); ?></option>
            <option value="top-right" <?php selected($value, 'top-right'); ?>><?php _e('Top Right Corner', 'amadex'); ?></option>
            <option value="bottom-left" <?php selected($value, 'bottom-left'); ?>><?php _e('Bottom Left Corner', 'amadex'); ?></option>
            <option value="bottom-right" <?php selected($value, 'bottom-right'); ?>><?php _e('Bottom Right Corner', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Position of the viewers badge on flight cards. Recommended: Top Left Corner for maximum visibility.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Timeout callback
             */
            public function timeout_callback()
            {
                $options = get_option('amadex_advanced_settings');
                $value = isset($options['timeout']) ? $options['timeout'] : 10;
    ?>
        <input type="number" id="timeout" name="amadex_advanced_settings[timeout]" value="<?php echo esc_attr($value); ?>" min="1" max="60" step="1" class="small-text">
        <p class="description"><?php _e('API request timeout in seconds (1-60)', 'amadex'); ?></p>
    <?php
            }

            /**
             * Performance section callback
             */
            public function performance_section_callback()
            {
                echo '<p>' . __('Optimize search performance by configuring these settings. Disabling debug logging in production can significantly improve search speed.', 'amadex') . '</p>';
            }

            /**
             * Enable debug logging callback
             */
            public function enable_debug_logging_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['enable_debug_logging']) ? $options['enable_debug_logging'] : '0';
    ?>
        <label>
            <input type="checkbox" id="enable_debug_logging" name="amadex_performance_settings[enable_debug_logging]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable debug logging (disabling this in production can improve performance by 2-3 seconds)', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When disabled, all error_log() calls are skipped except critical errors. Recommended: OFF in production, ON for debugging.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Initial results count callback
             */
            public function initial_results_count_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['initial_results_count']) ? intval($options['initial_results_count']) : 50;
    ?>
        <input type="number" id="initial_results_count" name="amadex_performance_settings[initial_results_count]" value="<?php echo esc_attr($value); ?>" min="10" max="250" step="10" class="small-text">
        <p class="description"><?php _e('Number of flight results to load initially. Remaining results will load automatically via infinite scroll. Lower values = faster initial load. Recommended: 50. Range: 10-250', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable performance logging callback
             */
            public function enable_performance_logging_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['enable_performance_logging']) ? $options['enable_performance_logging'] : '0';
    ?>
        <label>
            <input type="checkbox" id="enable_performance_logging" name="amadex_performance_settings[enable_performance_logging]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable performance metrics collection', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, performance metrics (timing, memory usage) are collected and displayed in the Performance Metrics admin page. This has minimal overhead.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable Redis cache callback
             */
            public function enable_redis_cache_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['enable_redis_cache']) ? $options['enable_redis_cache'] : '0';
                $is_available = false;
                if (class_exists('Amadex_Redis_Cache')) {
                    try {
                        $is_available = Amadex_Redis_Cache::is_available();
                    } catch (Throwable $e) {
                        $is_available = false;
                    }
                }
    ?>
        <label>
            <input type="checkbox" id="enable_redis_cache" name="amadex_performance_settings[enable_redis_cache]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable Redis caching for flight search results', 'amadex'); ?>
        </label>
        <?php if ($is_available): ?>
            <p class="description" style="color: #46b450;"><strong>✓ Redis is connected and available</strong></p>
        <?php else: ?>
            <p class="description" style="color: #dc3232;"><strong>⚠ Redis is not available. Will fall back to WordPress transients.</strong></p>
        <?php endif; ?>
        <p class="description"><?php _e('When enabled, search results are cached in Redis for faster subsequent searches. Falls back to WordPress transients if Redis is unavailable.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Redis host callback
             */
            public function redis_host_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['redis_host']) ? $options['redis_host'] : '127.0.0.1';
    ?>
        <input type="text" id="redis_host" name="amadex_performance_settings[redis_host]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Redis server hostname or IP address. Default: 127.0.0.1 (localhost)', 'amadex'); ?></p>
    <?php
            }

            /**
             * Redis port callback
             */
            public function redis_port_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['redis_port']) ? intval($options['redis_port']) : 6379;
    ?>
        <input type="number" id="redis_port" name="amadex_performance_settings[redis_port]" value="<?php echo esc_attr($value); ?>" min="1" max="65535" step="1" class="small-text">
        <p class="description"><?php _e('Redis server port. Default: 6379', 'amadex'); ?></p>
    <?php
            }

            /**
             * Redis password callback
             */
            public function redis_password_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['redis_password']) ? $options['redis_password'] : '';
    ?>
        <input type="password" id="redis_password" name="amadex_performance_settings[redis_password]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Redis server password (leave empty if no password required)', 'amadex'); ?></p>
    <?php
            }

            /**
             * Redis username callback (Redis 6+ ACL, required for Redis Cloud)
             */
            public function redis_username_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['redis_username']) ? $options['redis_username'] : '';
    ?>
        <input type="text" id="redis_username" name="amadex_performance_settings[redis_username]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="default">
        <p class="description"><?php _e('Redis username (Redis 6+ ACL). Use "default" for Redis Cloud. Leave empty for older Redis.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Redis database callback
             */
            public function redis_database_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['redis_database']) ? intval($options['redis_database']) : 0;
    ?>
        <input type="number" id="redis_database" name="amadex_performance_settings[redis_database]" value="<?php echo esc_attr($value); ?>" min="0" max="15" step="1" class="small-text">
        <p class="description"><?php _e('Redis database number (0-15). Default: 0', 'amadex'); ?></p>
    <?php
            }

            /**
             * Redis Use TLS callback
             */
            public function redis_use_tls_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['redis_use_tls']) ? $options['redis_use_tls'] : '0';
    ?>
        <label>
            <input type="checkbox" id="redis_use_tls" name="amadex_performance_settings[redis_use_tls]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Use TLS/SSL for Redis connection (required for Redis Cloud and other managed Redis)', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('Enable for Redis Cloud (redislabs.com) and other TLS-enabled Redis servers. Auto-enabled for redislabs.com hosts.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable progressive loading callback
             */
            public function enable_progressive_loading_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['enable_progressive_loading']) ? $options['enable_progressive_loading'] : '0';
    ?>
        <label>
            <input type="checkbox" id="enable_progressive_loading" name="amadex_performance_settings[enable_progressive_loading]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable progressive loading (show first results immediately)', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, the first 30 results are displayed immediately while remaining results load in the background. Significantly improves perceived performance.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Progressive load count callback
             */
            public function progressive_load_count_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['progressive_load_count']) ? intval($options['progressive_load_count']) : 30;
    ?>
        <input type="number" id="progressive_load_count" name="amadex_performance_settings[progressive_load_count]" value="<?php echo esc_attr($value); ?>" min="10" max="100" step="5" class="small-text">
        <p class="description"><?php _e('Number of results to show immediately (before loading remaining results). Recommended: 30. Range: 10-100', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable streaming response callback
             */
            public function enable_streaming_response_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['enable_streaming_response']) ? $options['enable_streaming_response'] : '0';
    ?>
        <label>
            <input type="checkbox" id="enable_streaming_response" name="amadex_performance_settings[enable_streaming_response]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable streaming response (show first flights immediately)', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, the first 5 flights are sent immediately while remaining flights are processed in the background. Dramatically improves time-to-first-results.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Streaming initial count callback
             */
            public function streaming_initial_count_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['streaming_initial_count']) ? intval($options['streaming_initial_count']) : 5;
    ?>
        <input type="number" id="streaming_initial_count" name="amadex_performance_settings[streaming_initial_count]" value="<?php echo esc_attr($value); ?>" min="1" max="50" step="1" class="small-text">
        <p class="description"><?php _e('Number of flights to send in initial stream. Recommended: 5. Range: 1-50', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable virtual scrolling callback
             */
            public function enable_virtual_scrolling_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['enable_virtual_scrolling']) ? $options['enable_virtual_scrolling'] : '0';
    ?>
        <label>
            <input type="checkbox" id="enable_virtual_scrolling" name="amadex_performance_settings[enable_virtual_scrolling]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable virtual scrolling (render only visible items)', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, only visible flight cards are rendered in the DOM. Improves performance for large result sets (50+ flights).', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable skeleton UI callback
             */
            public function enable_skeleton_ui_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['enable_skeleton_ui']) ? $options['enable_skeleton_ui'] : '0';
    ?>
        <label>
            <input type="checkbox" id="enable_skeleton_ui" name="amadex_performance_settings[enable_skeleton_ui]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable skeleton UI (show placeholders while loading)', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, animated placeholder cards are shown immediately while flights are loading. Creates instant perceived performance.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable loading animation callback
             */
            public function enable_loading_animation_callback()
            {
                $options = get_option('amadex_performance_settings', array());
                $value = isset($options['enable_loading_animation']) ? $options['enable_loading_animation'] : '0';
    ?>
        <label>
            <input type="checkbox" id="enable_loading_animation" name="amadex_performance_settings[enable_loading_animation]" value="1" <?php checked($value, '1'); ?>>
            <?php _e('Enable creative loading animation', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, an engaging animated loading screen with airplane, route map, and rotating messages is shown during search. Keeps users engaged.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Sanitize advanced settings (API Timeout, Error Logging, Debug Mode)
             *
             * @param array $input Raw POST data for amadex_advanced_settings.
             * @return array Sanitized array.
             */
            public function sanitize_advanced_settings($input)
            {
                if (!is_array($input)) {
                    $input = array();
                }
                $sanitized = array();

                // Timeout: integer 1-60, default 10
                if (isset($input['timeout'])) {
                    $timeout = intval($input['timeout']);
                    $sanitized['timeout'] = max(1, min(60, $timeout));
                } else {
                    $sanitized['timeout'] = 10;
                }

                // Error logging: checkbox, default 1 (on)
                if (isset($input['error_logging']) && $input['error_logging'] === '1') {
                    $sanitized['error_logging'] = '1';
                } else {
                    $sanitized['error_logging'] = '0';
                }

                // Debug mode: checkbox, default 0 (off)
                if (isset($input['debug_mode']) && $input['debug_mode'] === '1') {
                    $sanitized['debug_mode'] = '1';
                } else {
                    $sanitized['debug_mode'] = '0';
                }

                return $sanitized;
            }

            /**
             * Sanitize performance settings
             */
            public function sanitize_performance_settings($input)
            {
                $sanitized = array();

                if (isset($input['enable_debug_logging'])) {
                    $sanitized['enable_debug_logging'] = $input['enable_debug_logging'] === '1' ? '1' : '0';
                } else {
                    $sanitized['enable_debug_logging'] = '0';
                }

                if (isset($input['initial_results_count'])) {
                    $count = intval($input['initial_results_count']);
                    $sanitized['initial_results_count'] = max(10, min(250, $count));
                } else {
                    $sanitized['initial_results_count'] = 50;
                }

                if (isset($input['enable_performance_logging'])) {
                    $sanitized['enable_performance_logging'] = $input['enable_performance_logging'] === '1' ? '1' : '0';
                } else {
                    $sanitized['enable_performance_logging'] = '0';
                }

                // Redis settings
                if (isset($input['enable_redis_cache'])) {
                    $sanitized['enable_redis_cache'] = $input['enable_redis_cache'] === '1' ? '1' : '0';
                } else {
                    $sanitized['enable_redis_cache'] = '0';
                }

                if (isset($input['redis_host'])) {
                    $sanitized['redis_host'] = sanitize_text_field($input['redis_host']);
                } else {
                    $sanitized['redis_host'] = '127.0.0.1';
                }

                if (isset($input['redis_port'])) {
                    $port = intval($input['redis_port']);
                    $sanitized['redis_port'] = max(1, min(65535, $port));
                } else {
                    $sanitized['redis_port'] = 6379;
                }

                if (isset($input['redis_password'])) {
                    $sanitized['redis_password'] = sanitize_text_field($input['redis_password']);
                } else {
                    $sanitized['redis_password'] = '';
                }

                if (isset($input['redis_username'])) {
                    $sanitized['redis_username'] = sanitize_text_field($input['redis_username']);
                } else {
                    $sanitized['redis_username'] = '';
                }

                if (isset($input['redis_database'])) {
                    $db = intval($input['redis_database']);
                    $sanitized['redis_database'] = max(0, min(15, $db));
                } else {
                    $sanitized['redis_database'] = 0;
                }

                if (isset($input['redis_use_tls'])) {
                    $sanitized['redis_use_tls'] = $input['redis_use_tls'] === '1' ? '1' : '0';
                } else {
                    $sanitized['redis_use_tls'] = '0';
                }

                // Progressive loading settings
                if (isset($input['enable_progressive_loading'])) {
                    $sanitized['enable_progressive_loading'] = $input['enable_progressive_loading'] === '1' ? '1' : '0';
                } else {
                    $sanitized['enable_progressive_loading'] = '0';
                }

                if (isset($input['progressive_load_count'])) {
                    $count = intval($input['progressive_load_count']);
                    $sanitized['progressive_load_count'] = max(10, min(100, $count));
                } else {
                    $sanitized['progressive_load_count'] = 30;
                }

                // Streaming response settings
                if (isset($input['enable_streaming_response'])) {
                    $sanitized['enable_streaming_response'] = $input['enable_streaming_response'] === '1' ? '1' : '0';
                } else {
                    $sanitized['enable_streaming_response'] = '0';
                }

                if (isset($input['streaming_initial_count'])) {
                    $count = intval($input['streaming_initial_count']);
                    $sanitized['streaming_initial_count'] = max(1, min(50, $count));
                } else {
                    $sanitized['streaming_initial_count'] = 5;
                }

                // Virtual scrolling settings
                if (isset($input['enable_virtual_scrolling'])) {
                    $sanitized['enable_virtual_scrolling'] = $input['enable_virtual_scrolling'] === '1' ? '1' : '0';
                } else {
                    $sanitized['enable_virtual_scrolling'] = '0';
                }

                // Skeleton UI settings
                if (isset($input['enable_skeleton_ui'])) {
                    $sanitized['enable_skeleton_ui'] = $input['enable_skeleton_ui'] === '1' ? '1' : '0';
                } else {
                    $sanitized['enable_skeleton_ui'] = '0';
                }

                // Loading animation settings
                if (isset($input['enable_loading_animation'])) {
                    $sanitized['enable_loading_animation'] = $input['enable_loading_animation'] === '1' ? '1' : '0';
                } else {
                    $sanitized['enable_loading_animation'] = '0';
                }

                return $sanitized;
            }

            /**
             * Error logging callback
             */
            public function error_logging_callback()
            {
                $options = get_option('amadex_advanced_settings');
                $value = isset($options['error_logging']) ? $options['error_logging'] : 1;
    ?>
        <label for="error_logging">
            <input type="checkbox" id="error_logging" name="amadex_advanced_settings[error_logging]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable error logging', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('Log API errors and plugin issues for debugging purposes', 'amadex'); ?></p>
    <?php
            }

            /**
             * Debug mode callback
             */
            public function debug_mode_callback()
            {
                $options = get_option('amadex_advanced_settings');
                $value = isset($options['debug_mode']) ? $options['debug_mode'] : 0;
    ?>
        <label for="debug_mode">
            <input type="checkbox" id="debug_mode" name="amadex_advanced_settings[debug_mode]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable debug mode', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('Display additional debugging information in the browser console', 'amadex'); ?></p>
    <?php
            }

            /**
             * Payment General section callback
             */
            public function payment_general_section_callback()
            {
                echo '<p>' . __('Configure general payment settings that apply to all payment methods.', 'amadex') . '</p>';
            }

            /**
             * Payment NMI section callback
             */
            public function payment_nmi_section_callback()
            {
                $options = get_option('amadex_payment_settings');
                $default_gateway = isset($options['default_card_gateway']) ? $options['default_card_gateway'] : 'nmi';
                $nmi_api_key = isset($options['nmi_api_key']) ? trim($options['nmi_api_key']) : '';
                $nmi_tokenization_key = isset($options['nmi_tokenization_key']) ? trim($options['nmi_tokenization_key']) : '';
                $is_configured = !empty($nmi_api_key) && !empty($nmi_tokenization_key);
                $is_default = ($default_gateway === 'nmi');
    ?>
        <div class="amadex-payment-gateway-header">
            <div class="amadex-gateway-status">
                <?php if ($is_configured): ?>
                    <span class="amadex-status-badge amadex-status-configured">✓ Configured</span>
                <?php else: ?>
                    <span class="amadex-status-badge amadex-status-not-configured">⚠ Not Configured</span>
                <?php endif; ?>
                <?php if ($is_default && $is_configured): ?>
                    <span class="amadex-status-badge amadex-status-default">Default Processor</span>
                <?php endif; ?>
            </div>
            <p class="description"><?php _e('Configure NMI payment gateway for credit/debit card processing using Collect.js tokenization.', 'amadex'); ?></p>
        </div>
    <?php
            }

            /**
             * Payment Stripe section callback
             */
            public function payment_stripe_section_callback()
            {
                $options = get_option('amadex_payment_settings');
                $default_gateway = isset($options['default_card_gateway']) ? $options['default_card_gateway'] : 'nmi';
                $stripe_publishable_key = isset($options['stripe_publishable_key']) ? trim($options['stripe_publishable_key']) : '';
                $stripe_secret_key = isset($options['stripe_secret_key']) ? trim($options['stripe_secret_key']) : '';
                $is_configured = !empty($stripe_publishable_key) && !empty($stripe_secret_key);
                $is_default = ($default_gateway === 'stripe');
                $stripe_library_path = defined('AMADEX_PATH') ? AMADEX_PATH . 'includes/vendor/stripe/stripe-php/' : '';
                $library_exists = $stripe_library_path && file_exists($stripe_library_path . 'init.php');
    ?>
        <div class="amadex-payment-gateway-header">
            <div class="amadex-gateway-status">
                <?php if ($is_configured): ?>
                    <span class="amadex-status-badge amadex-status-configured">✓ Configured</span>
                <?php else: ?>
                    <span class="amadex-status-badge amadex-status-not-configured">⚠ Not Configured</span>
                <?php endif; ?>
                <?php if ($is_default && $is_configured): ?>
                    <span class="amadex-status-badge amadex-status-default">Default Processor</span>
                <?php endif; ?>
                <?php if ($library_exists): ?>
                    <span class="amadex-status-badge amadex-status-configured" style="background: #00a32a;">✓ Stripe library found</span>
                <?php else: ?>
                    <span class="amadex-status-badge amadex-status-not-configured">⚠ Stripe library missing</span>
                <?php endif; ?>
            </div>
            <p class="description"><?php _e('Configure Stripe for credit/debit card payments. Use Test keys for testing; switch to Live when ready. Select "Default Card Processor" above as Stripe to use this gateway.', 'amadex'); ?></p>
            <p class="description">
                <strong><?php _e('Where to get keys:', 'amadex'); ?></strong>
                <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener"><?php _e('Stripe Dashboard → API keys', 'amadex'); ?></a>.
                <?php _e('Publishable key starts with pk_test_ or pk_live_; Secret key starts with sk_test_ or sk_live_.', 'amadex'); ?>
            </p>
            <?php if (!$library_exists): ?>
                <p class="description" style="color: #d63638;">
                    <strong><?php _e('Stripe PHP library not found.', 'amadex'); ?></strong>
                    <?php _e('Expected path: includes/vendor/stripe/stripe-php/ (with init.php and lib/ folder). Re-upload the plugin or restore the vendor folder.', 'amadex'); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php
            }

            /**
             * Payment PayPal section callback
             */
            public function payment_paypal_section_callback()
            {
                $options = get_option('amadex_payment_settings');
                $enable_paypal = isset($options['enable_paypal']) ? $options['enable_paypal'] : 0;
                $paypal_client_id = isset($options['paypal_client_id']) ? trim($options['paypal_client_id']) : '';
                $paypal_client_secret = isset($options['paypal_client_secret']) ? trim($options['paypal_client_secret']) : '';
                $is_configured = !empty($paypal_client_id) && !empty($paypal_client_secret);
    ?>
        <div class="amadex-payment-gateway-header">
            <div class="amadex-gateway-status">
                <?php if ($enable_paypal && $is_configured): ?>
                    <span class="amadex-status-badge amadex-status-enabled">✓ Enabled</span>
                <?php elseif ($is_configured): ?>
                    <span class="amadex-status-badge amadex-status-configured">Configured (Disabled)</span>
                <?php else: ?>
                    <span class="amadex-status-badge amadex-status-not-configured">⚠ Not Configured</span>
                <?php endif; ?>
            </div>
            <p class="description"><?php _e('Configure PayPal payment gateway for PayPal account payments.', 'amadex'); ?></p>
        </div>
    <?php
            }

            /**
             * Pay with Crypto (Cryptocurrency Transfer) section callback
             */
            public function payment_crypto_transfer_section_callback()
            {
                $options = get_option('amadex_payment_settings');
                $enable = isset($options['enable_crypto_transfer']) ? $options['enable_crypto_transfer'] : 0;
    ?>
        <div class="amadex-payment-gateway-header">
            <div class="amadex-gateway-status">
                <?php if ($enable): ?>
                    <span class="amadex-status-badge amadex-status-enabled">✓ Enabled</span>
                <?php else: ?>
                    <span class="amadex-status-badge amadex-status-not-configured">OFF</span>
                <?php endif; ?>
            </div>
            <p class="description"><?php _e('When enabled, customers can pay using cryptocurrency transfers.', 'amadex'); ?></p>
        </div>
    <?php
            }

            /**
             * Crypto.com Pay section callback
             */
            public function payment_crypto_com_section_callback()
            {
                $options = get_option('amadex_payment_settings');
                $enable = isset($options['enable_crypto_com']) ? $options['enable_crypto_com'] : 0;
    ?>
        <div class="amadex-payment-gateway-header">
            <div class="amadex-gateway-status">
                <?php if ($enable): ?>
                    <span class="amadex-status-badge amadex-status-enabled">✓ Enabled</span>
                <?php else: ?>
                    <span class="amadex-status-badge amadex-status-not-configured">OFF</span>
                <?php endif; ?>
            </div>
            <p class="description"><?php _e('When enabled, customers can pay using Crypto.com Pay.', 'amadex'); ?></p>
        </div>
    <?php
            }

            /**
             * PayPal Client ID callback
             */
            public function paypal_client_id_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['paypal_client_id']) ? $options['paypal_client_id'] : '';
    ?>
        <input type="text" id="paypal_client_id" name="amadex_payment_settings[paypal_client_id]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Your PayPal Client ID from the PayPal Developer Dashboard', 'amadex'); ?></p>
    <?php
            }

            /**
             * PayPal Client Secret callback
             */
            public function paypal_client_secret_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['paypal_client_secret']) ? $options['paypal_client_secret'] : '';
    ?>
        <input type="password" id="paypal_client_secret" name="amadex_payment_settings[paypal_client_secret]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Your PayPal Client Secret from the PayPal Developer Dashboard', 'amadex'); ?></p>
    <?php
            }

            /**
             * PayPal Mode callback
             */
            public function paypal_mode_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['paypal_mode']) ? $options['paypal_mode'] : 'sandbox';
    ?>
        <select id="paypal_mode" name="amadex_payment_settings[paypal_mode]">
            <option value="sandbox" <?php selected('sandbox', $value); ?>><?php _e('Sandbox', 'amadex'); ?></option>
            <option value="live" <?php selected('live', $value); ?>><?php _e('Live', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Choose between sandbox (test) and live (production) mode', 'amadex'); ?></p>
    <?php
            }

            /**
             * Stripe Publishable Key callback
             */
            public function stripe_publishable_key_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['stripe_publishable_key']) ? $options['stripe_publishable_key'] : '';
    ?>
        <input type="text" id="stripe_publishable_key" name="amadex_payment_settings[stripe_publishable_key]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Your Stripe Publishable Key from the Stripe Dashboard', 'amadex'); ?></p>
    <?php
            }

            /**
             * Stripe Secret Key callback
             */
            public function stripe_secret_key_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['stripe_secret_key']) ? $options['stripe_secret_key'] : '';
    ?>
        <input type="password" id="stripe_secret_key" name="amadex_payment_settings[stripe_secret_key]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Your Stripe Secret Key from the Stripe Dashboard', 'amadex'); ?></p>
    <?php
            }

            /**
             * Stripe Mode callback
             */
            public function stripe_mode_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['stripe_mode']) ? $options['stripe_mode'] : 'test';
    ?>
        <select id="stripe_mode" name="amadex_payment_settings[stripe_mode]">
            <option value="test" <?php selected('test', $value); ?>><?php _e('Test', 'amadex'); ?></option>
            <option value="live" <?php selected('live', $value); ?>><?php _e('Live', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Use Test for development (test cards); use Live for real charges. Keys must match the mode (test keys with Test, live keys with Live).', 'amadex'); ?></p>
    <?php
            }

            /**
             * Stripe Webhook Secret callback (optional – for webhook signature verification)
             */
            public function stripe_webhook_secret_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['stripe_webhook_secret']) ? $options['stripe_webhook_secret'] : '';
    ?>
        <input type="password" id="stripe_webhook_secret" name="amadex_payment_settings[stripe_webhook_secret]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <button type="button" class="button button-small" onclick="var e=document.getElementById('stripe_webhook_secret');e.type=e.type==='password'?'text':'password';" style="margin-left:5px;"><span class="dashicons dashicons-visibility"></span> Show/Hide</button>
        <p class="description"><?php _e('Optional. Only needed if you use Stripe webhooks. Get it from Stripe Dashboard → Developers → Webhooks → Add endpoint → Signing secret (whsec_...).', 'amadex'); ?></p>
    <?php
            }

            /**
             * NMI API Key callback
             */
            public function nmi_api_key_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['nmi_api_key']) ? $options['nmi_api_key'] : '';
                $tokenization_key = isset($options['nmi_tokenization_key']) ? $options['nmi_tokenization_key'] : '';

                // Detect which key set they're using
                $using_flytravelay = !empty($tokenization_key) && (strpos($tokenization_key, 'mY59yM') === 0 || strpos($tokenization_key, 'FLYTRAVELAY') !== false);
    ?>
        <input type="password" id="nmi_api_key" name="amadex_payment_settings[nmi_api_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" style="width: 100%; max-width: 500px;" placeholder="Enter your Private Security Key (API Key)">
        <button type="button" class="button button-small" onclick="document.getElementById('nmi_api_key').type = document.getElementById('nmi_api_key').type === 'password' ? 'text' : 'password';" style="margin-left: 5px;">
            <span class="dashicons dashicons-visibility"></span> Show/Hide
        </button>

        <!-- <p class="description"><strong>Step-by-Step Guide:</strong></p>
        <ol style="margin-left: 20px; margin-top: 10px;">
            <li>Go to your <a href="https://secure.nmi.com/merchants/options.php?Action=Keys" target="_blank">NMI Dashboard → Settings → Security Keys</a></li>
            <li>Look at the <strong>"Private Security Keys"</strong> section</li>
            <li>Check your <strong>Tokenization Key</strong> above - see which description it has</li>
            <?php if ($using_flytravelay): ?>
                <li style="color: #d63638; font-weight: 600;">✅ <strong>YOUR CASE:</strong> Your Tokenization Key is "FLYTRAVELAY", so use the <strong>"FLYTRAVELAY"</strong> Private Security Key (API Key)</li>
                <li style="color: #d63638; font-weight: 600;">⚠️ <strong>DO NOT USE:</strong> The "Default Cart Key" - it won't work!</li>
                <li>Copy the key that starts with: <code style="background: #f0f0f1; padding: 2px 6px;">aV5WZD69uVnXa8kUUjT3W2nm3aQ2522G...</code></li>
            <?php else: ?>
                <li>Find the Private Security Key with the <strong>same description</strong> as your Tokenization Key</li>
                <li>Example: If Tokenization Key is "FLYTRAVELAY", use "FLYTRAVELAY" API Key</li>
                <li>Make sure it says <strong>"API"</strong> in the Source column</li>
            <?php endif; ?>
            <li>Paste it in the field above</li>
        </ol> -->

        <!-- <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0;">
            <p style="margin: 0 0 8px 0; font-weight: 600;">📋 Quick Reference:</p>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px; border-bottom: 1px solid #ddd;"><strong>If your Tokenization Key is:</strong></td>
                    <td style="padding: 5px; border-bottom: 1px solid #ddd;"><strong>Use this API Key:</strong></td>
                </tr>
                <tr>
                    <td style="padding: 5px;">"FLYTRAVELAY" (starts with mY59yM...)</td>
                    <td style="padding: 5px;">"FLYTRAVELAY" API Key (starts with aV5WZD69...)</td>
                </tr>
                <tr>
                    <td style="padding: 5px;">"Default" or any other</td>
                    <td style="padding: 5px;">Same description API Key from Private Keys</td>
                </tr>
            </table>
        </div>
         -->
        <!-- <p class="description" style="color: #d63638; font-weight: 600; margin-top: 15px;">⚠️ <strong>CRITICAL RULES:</strong></p>
        <ul style="margin-left: 20px; color: #d63638;">
            <li>✅ Keys MUST be from the <strong>same NMI account</strong></li>
            <li>✅ Keys MUST have the <strong>same description/name</strong> (both "FLYTRAVELAY" or both "Default")</li>
            <li>✅ Keys MUST be from the <strong>same environment</strong> (both Test or both Production)</li>
            <li>❌ Do NOT mix "FLYTRAVELAY" Tokenization Key with "Default" API Key</li>
            <li>❌ Do NOT mix Test keys with Production keys</li>
        </ul>
         -->
        <!-- <p class="description" style="margin-top: 15px;"><strong>Current Tokenization Key:</strong> 
            <?php if (!empty($tokenization_key)): ?>
                <code style="background: #f0f0f1; padding: 2px 6px;"><?php echo esc_html(substr($tokenization_key, 0, 10)); ?>...</code>
                <?php if ($using_flytravelay): ?>
                    <span style="color: #46b450;">✓ Detected as "FLYTRAVELAY" - Use "FLYTRAVELAY" API Key below</span>
                <?php endif; ?>
            <?php else: ?>
                <span style="color: #d63638;">Not set yet - Set Tokenization Key first</span>
            <?php endif; ?>
        </p> -->
    <?php
            }

            /**
             * Sanitize general settings
             * Ensures all general settings are properly sanitized, especially agent notification emails
             */
            public function sanitize_general_settings($input)
            {
                // Ensure input is an array
                if (!is_array($input)) {
                    $input = array();
                }

                // Get existing settings safely
                $existing = get_option('amadex_general_settings', array());
                if (!is_array($existing)) {
                    $existing = array();
                }

                // Start with existing settings to preserve any fields not in input
                $sanitized = $existing;

                // Sanitize call_now_number
                if (isset($input['call_now_number'])) {
                    $sanitized['call_now_number'] = sanitize_text_field($input['call_now_number']);
                }

                // Sanitize agent_notification_emails - preserve textarea format with newlines
                if (isset($input['agent_notification_emails'])) {
                    // Keep the raw textarea value but clean it up
                    $agent_emails_raw = $input['agent_notification_emails'];
                    // Remove any dangerous characters but preserve commas and spaces for email list
                    $sanitized['agent_notification_emails'] = sanitize_textarea_field($agent_emails_raw);
                }

                // Sanitize admin notification email (form field name is notification_email)
                if (isset($input['notification_email'])) {
                    $email = sanitize_email($input['notification_email']);
                    if (is_email($email)) {
                        $sanitized['notification_email'] = $email;
                    }
                }
                // Backward compatibility: also accept admin_notification_email if used elsewhere
                if (isset($input['admin_notification_email'])) {
                    $email = sanitize_email($input['admin_notification_email']);
                    if (is_email($email)) {
                        $sanitized['notification_email'] = $email;
                    }
                }

                // Sanitize booking_confirmation_page
                if (isset($input['booking_confirmation_page'])) {
                    $sanitized['booking_confirmation_page'] = absint($input['booking_confirmation_page']);
                }

                // Sanitize cache_duration
                if (isset($input['cache_duration'])) {
                    $sanitized['cache_duration'] = absint($input['cache_duration']);
                }

                // Sanitize enable_plugin
                if (isset($input['enable_plugin'])) {
                    $sanitized['enable_plugin'] = 1;
                } else {
                    $sanitized['enable_plugin'] = 0;
                }

                // Sanitize google_places_api_key
                if (isset($input['google_places_api_key'])) {
                    $sanitized['google_places_api_key'] = trim(sanitize_text_field($input['google_places_api_key']));
                }

                return $sanitized;
            }



            /**
             * Sanitize API settings
             * Ensures API credentials are properly trimmed and token cache is cleared on change
             */
            public function sanitize_api_settings($input)
            {
                // Ensure input is an array
                if (!is_array($input)) {
                    $input = array();
                }

                // Get existing settings safely
                $existing = get_option('amadex_api_settings', array());
                if (!is_array($existing)) {
                    $existing = array();
                }

                // Start with existing settings to preserve any fields not in input
                $sanitized = $existing;

                // Sanitize and trim API key
                if (isset($input['api_key']) && is_string($input['api_key'])) {
                    $new_api_key = trim(sanitize_text_field($input['api_key']));
                    $old_api_key = isset($existing['api_key']) && is_string($existing['api_key']) ? trim($existing['api_key']) : '';

                    // If API key changed, clear token cache
                    if ($new_api_key !== $old_api_key && !empty($new_api_key)) {
                        delete_transient('amadex_amadeus_token');
                        delete_transient('amadex_amadeus_token_expiry');
                    }

                    $sanitized['api_key'] = $new_api_key;
                } elseif (isset($input['api_key'])) {
                    // Handle empty or invalid input
                    $sanitized['api_key'] = '';
                }

                // Sanitize and trim API secret
                if (isset($input['api_secret']) && is_string($input['api_secret'])) {
                    $new_api_secret = trim(sanitize_text_field($input['api_secret']));
                    $old_api_secret = isset($existing['api_secret']) && is_string($existing['api_secret']) ? trim($existing['api_secret']) : '';

                    // If API secret changed, clear token cache
                    if ($new_api_secret !== $old_api_secret && !empty($new_api_secret)) {
                        delete_transient('amadex_amadeus_token');
                        delete_transient('amadex_amadeus_token_expiry');
                    }

                    $sanitized['api_secret'] = $new_api_secret;
                } elseif (isset($input['api_secret'])) {
                    // Handle empty or invalid input
                    $sanitized['api_secret'] = '';
                }

                // Sanitize environment
                if (isset($input['environment']) && is_string($input['environment'])) {
                    $new_environment = sanitize_text_field($input['environment']);
                    $old_environment = isset($existing['environment']) ? $existing['environment'] : 'test';

                    // If environment changed, clear token cache
                    if ($new_environment !== $old_environment) {
                        delete_transient('amadex_amadeus_token');
                        delete_transient('amadex_amadeus_token_expiry');
                    }

                    $sanitized['environment'] = in_array($new_environment, array('test', 'production', 'live')) ? $new_environment : 'test';
                } elseif (!isset($sanitized['environment'])) {
                    // Set default if not set
                    $sanitized['environment'] = 'test';
                }

                // Ensure we always return a valid array
                if (!is_array($sanitized)) {
                    $sanitized = array();
                }

                return $sanitized;
            }

            /**
             * Sanitize pricing rules settings
             */
            public function sanitize_pricing_rules_settings($input)
            {
                // Start from existing settings to preserve any fields
                $existing = get_option('amadex_pricing_rules_settings', array());
                $sanitized = is_array($existing) ? $existing : array();

                $input = is_array($input) ? $input : array();

                // Ensure enable_pricing_rules_engine is saved properly
                // Checkboxes only send value when checked, so if not set, it's unchecked
                if (isset($input['enable_pricing_rules_engine'])) {
                    $value = $input['enable_pricing_rules_engine'];
                    // Accept '1', 1, or true
                    $sanitized['enable_pricing_rules_engine'] = ($value == '1' || $value == 1 || $value === true) ? 1 : 0;
                } else {
                    // Checkbox not checked - set to 0
                    $sanitized['enable_pricing_rules_engine'] = 0;
                }

                // Remove the hidden field we added
                if (isset($sanitized['_current_value'])) {
                    unset($sanitized['_current_value']);
                }

                return $sanitized;
            }

            /**
             * Sanitize payment settings
             * Ensures keys are properly trimmed and validated
             *
             * IMPORTANT:
             * - Always return ALL payment settings we want to persist.
             * - If a field is omitted here it will be dropped by WordPress when saving.
             */
            public function sanitize_payment_settings($input)
            {
                // Start from existing settings so we don't unintentionally wipe fields
                $existing   = get_option('amadex_payment_settings', array());
                $sanitized  = is_array($existing) ? $existing : array();

                $input = is_array($input) ? $input : array();

                // ========== NMI SETTINGS ==========
                // Save NMI API Key (allow empty to clear it)
                if (isset($input['nmi_api_key'])) {
                    $sanitized['nmi_api_key'] = trim(sanitize_text_field($input['nmi_api_key']));
                } elseif (!isset($sanitized['nmi_api_key'])) {
                    $sanitized['nmi_api_key'] = '';
                }

                // Save NMI Tokenization Key (allow empty to clear it). Remove non-printable chars to avoid 401 from copy-paste.
                if (isset($input['nmi_tokenization_key'])) {
                    $tk = trim(sanitize_text_field($input['nmi_tokenization_key']));
                    $tk = preg_replace('/[\x00-\x1F\x7F]/', '', $tk);
                    $sanitized['nmi_tokenization_key'] = trim($tk);
                } elseif (!isset($sanitized['nmi_tokenization_key'])) {
                    $sanitized['nmi_tokenization_key'] = '';
                }

                // Ensure nmi_sandbox defaults to 0 (disabled) when not checked
                $sanitized['nmi_sandbox'] = isset($input['nmi_sandbox']) ? 1 : 0;
                $sanitized['nmi_bypass_for_testing'] = isset($input['nmi_bypass_for_testing']) ? 1 : 0;
                $sanitized['enable_3ds']             = isset($input['enable_3ds']) ? 1 : 0;

                // ========== DEFAULT CARD GATEWAY ==========
                if (isset($input['default_card_gateway'])) {
                    $gateway = strtolower(trim(sanitize_text_field($input['default_card_gateway'])));
                    // Validate: must be 'nmi' or 'stripe' (store lowercase so NMI-enabled case always works)
                    if (in_array($gateway, array('nmi', 'stripe'))) {
                        $sanitized['default_card_gateway'] = $gateway;
                    } else {
                        $sanitized['default_card_gateway'] = 'nmi'; // Default to NMI if invalid
                    }
                } else {
                    // Default to NMI if not set (backward compatibility)
                    $sanitized['default_card_gateway'] = 'nmi';
                }

                // ========== CREDIT CARD / CRYPTO ==========
                $sanitized['enable_credit_card']        = isset($input['enable_credit_card']) ? 1 : 0;
                $sanitized['enable_crypto_transfer']    = isset($input['enable_crypto_transfer']) ? 1 : 0;
                $sanitized['crypto_transfer_redirect_url'] = isset($input['crypto_transfer_redirect_url'])
                    ? esc_url_raw($input['crypto_transfer_redirect_url'])
                    : '';
                $sanitized['enable_crypto_com']        = isset($input['enable_crypto_com']) ? 1 : 0;
                $sanitized['crypto_com_redirect_url']  = isset($input['crypto_com_redirect_url'])
                    ? esc_url_raw($input['crypto_com_redirect_url'])
                    : '';
                if (isset($input['crypto_com_publishable_key'])) {
                    $sanitized['crypto_com_publishable_key'] = trim(sanitize_text_field($input['crypto_com_publishable_key']));
                }
                if (isset($input['crypto_com_secret_key'])) {
                    $sanitized['crypto_com_secret_key'] = trim(sanitize_text_field($input['crypto_com_secret_key']));
                }

                // ========== MOONPAY COMMERCE SETTINGS (per Hel.io Getting Started) ==========
                $sanitized['enable_moonpay_commerce'] = isset($input['enable_moonpay_commerce']) ? 1 : 0;
                if (isset($input['moonpay_environment'])) {
                    $env = $input['moonpay_environment'] === 'live' ? 'live' : 'test';
                    $sanitized['moonpay_environment'] = $env;
                } else {
                    $sanitized['moonpay_environment'] = 'test';
                }
                if (isset($input['moonpay_public_key']) || isset($input['moonpay_secret_key'])) {
                    $env = isset($input['moonpay_environment']) && $input['moonpay_environment'] === 'live' ? 'live' : 'test';
                    if (isset($input['moonpay_public_key'])) {
                        $sanitized['moonpay_publishable_key_' . $env] = trim(sanitize_text_field($input['moonpay_public_key']));
                    }
                    if (isset($input['moonpay_secret_key'])) {
                        $secret = trim(sanitize_text_field($input['moonpay_secret_key']));
                        // Form POST can turn + into space; restore so Hel.io Bearer token is valid.
                        if (strpos($secret, ' ') !== false) {
                            $secret = str_replace(' ', '+', $secret);
                        }
                        $sanitized['moonpay_secret_key_' . $env] = $secret;
                    }
                }
                if (isset($input['moonpay_helio_wallet_id'])) {
                    $sanitized['moonpay_helio_wallet_id'] = trim(sanitize_text_field($input['moonpay_helio_wallet_id']));
                }
                if (isset($input['moonpay_settlement_currencies'])) {
                    $sanitized['moonpay_settlement_currencies'] = trim(sanitize_text_field($input['moonpay_settlement_currencies']));
                }
                if (isset($input['moonpay_helio_webhook_secret'])) {
                    $wh_secret = trim(sanitize_text_field($input['moonpay_helio_webhook_secret']));
                    if (strpos($wh_secret, ' ') !== false && strpos($wh_secret, '/') !== false) {
                        $wh_secret = str_replace(' ', '+', $wh_secret);
                    }
                    $sanitized['moonpay_helio_webhook_secret'] = $wh_secret;
                }
                $sanitized['enable_moonpay_onramp'] = isset($input['enable_moonpay_onramp']) ? 1 : 0;
                if (isset($input['moonpay_onramp_environment'])) {
                    $sanitized['moonpay_onramp_environment'] = $input['moonpay_onramp_environment'] === 'live' ? 'live' : 'test';
                } else {
                    $sanitized['moonpay_onramp_environment'] = 'test';
                }
                if (isset($input['moonpay_onramp_publishable_key_test'])) {
                    $sanitized['moonpay_onramp_publishable_key_test'] = trim(sanitize_text_field($input['moonpay_onramp_publishable_key_test']));
                }
                if (isset($input['moonpay_onramp_secret_key_test'])) {
                    $sanitized['moonpay_onramp_secret_key_test'] = trim(sanitize_text_field($input['moonpay_onramp_secret_key_test']));
                }
                if (isset($input['moonpay_onramp_publishable_key_live'])) {
                    $sanitized['moonpay_onramp_publishable_key_live'] = trim(sanitize_text_field($input['moonpay_onramp_publishable_key_live']));
                }
                if (isset($input['moonpay_onramp_secret_key_live'])) {
                    $sanitized['moonpay_onramp_secret_key_live'] = trim(sanitize_text_field($input['moonpay_onramp_secret_key_live']));
                }
                if (isset($input['moonpay_onramp_merchant_wallet_btc'])) {
                    $sanitized['moonpay_onramp_merchant_wallet_btc'] = trim(sanitize_text_field($input['moonpay_onramp_merchant_wallet_btc']));
                }
                if (isset($input['moonpay_onramp_merchant_wallet_btc_sandbox'])) {
                    $sanitized['moonpay_onramp_merchant_wallet_btc_sandbox'] = trim(sanitize_text_field($input['moonpay_onramp_merchant_wallet_btc_sandbox']));
                }

                // ========== PAYPAL SETTINGS ==========
                if (isset($input['paypal_client_id'])) {
                    $sanitized['paypal_client_id'] = trim(sanitize_text_field($input['paypal_client_id']));
                }
                if (isset($input['paypal_client_secret'])) {
                    // Use sanitize_text_field for secrets as well; WP will store as plain text
                    $sanitized['paypal_client_secret'] = trim(sanitize_text_field($input['paypal_client_secret']));
                }
                if (isset($input['paypal_mode'])) {
                    $mode = $input['paypal_mode'] === 'live' ? 'live' : 'sandbox';
                    $sanitized['paypal_mode'] = $mode;
                }
                $sanitized['enable_paypal'] = isset($input['enable_paypal']) ? 1 : 0;
                if (isset($input['paypal_redirect_url'])) {
                    $sanitized['paypal_redirect_url'] = esc_url_raw($input['paypal_redirect_url']);
                }

                // ========== STRIPE SETTINGS ==========
                if (isset($input['stripe_publishable_key'])) {
                    $sanitized['stripe_publishable_key'] = trim(sanitize_text_field($input['stripe_publishable_key']));
                }
                if (isset($input['stripe_secret_key'])) {
                    $sanitized['stripe_secret_key'] = trim(sanitize_text_field($input['stripe_secret_key']));
                }
                if (isset($input['stripe_mode'])) {
                    $mode = $input['stripe_mode'] === 'live' ? 'live' : 'test';
                    $sanitized['stripe_mode'] = $mode;
                }
                if (isset($input['stripe_webhook_secret'])) {
                    $sanitized['stripe_webhook_secret'] = trim(sanitize_text_field($input['stripe_webhook_secret']));
                }

                // Log key validation after sanitization (NMI only, partial for security)
                if (!empty($sanitized['nmi_api_key']) && !empty($sanitized['nmi_tokenization_key'])) {
                    error_log('Amadex Payment Settings Sanitized:');
                    error_log('  API Key Length: ' . strlen($sanitized['nmi_api_key']));
                    error_log('  Tokenization Key Length: ' . strlen($sanitized['nmi_tokenization_key']));
                    error_log('  API Key Preview: ' . substr($sanitized['nmi_api_key'], 0, 10) . '...');
                    error_log('  Tokenization Key Preview: ' . substr($sanitized['nmi_tokenization_key'], 0, 10) . '...');
                }

                return $sanitized;
            }

            /**
             * Sanitize Email Template settings (booking confirmation email). Static so AJAX live preview can use it.
             */
            public static function sanitize_email_template_settings_array($input)
            {
                if (!is_array($input)) {
                    return array();
                }
                $sanitized = array();
                $int_keys = array('container_max_width', 'outer_padding_desktop', 'outer_padding_mobile', 'mobile_breakpoint', 'font_size_body', 'h1_size', 'h1_size_mobile', 'logo_max_width_desktop', 'logo_max_width_mobile', 'border_radius', 'section_spacing');
                foreach ($int_keys as $key) {
                    if (isset($input[$key])) {
                        $v = absint($input[$key]);
                        if ($key === 'container_max_width') {
                            $sanitized[$key] = max(400, min(750, $v));
                        } elseif ($key === 'mobile_breakpoint') {
                            $sanitized[$key] = max(320, min(768, $v));
                        } elseif ($key === 'outer_padding_desktop' || $key === 'outer_padding_mobile') {
                            $sanitized[$key] = max(0, min(60, $v));
                        } elseif ($key === 'font_size_body') {
                            $sanitized[$key] = max(12, min(20, $v));
                        } elseif ($key === 'h1_size' || $key === 'h1_size_mobile') {
                            $sanitized[$key] = max(16, min(32, $v));
                        } elseif ($key === 'logo_max_width_desktop' || $key === 'logo_max_width_mobile') {
                            $sanitized[$key] = max(80, min(300, $v));
                        } elseif ($key === 'border_radius') {
                            $sanitized[$key] = max(0, min(24, $v));
                        } elseif ($key === 'section_spacing') {
                            $sanitized[$key] = max(0, min(48, $v));
                        } else {
                            $sanitized[$key] = $v;
                        }
                    }
                }
                $float_keys = array('line_height_body');
                foreach ($float_keys as $key) {
                    if (isset($input[$key])) {
                        $v = floatval($input[$key]);
                        $sanitized[$key] = max(1.2, min(2.0, $v));
                    }
                }
                $color_keys = array('body_bg', 'content_bg', 'primary_color', 'text_color', 'secondary_text', 'border_color', 'link_color');
                foreach ($color_keys as $key) {
                    if (isset($input[$key]) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $input[$key])) {
                        $sanitized[$key] = sanitize_text_field($input[$key]);
                    }
                }
                if (isset($input['font_family'])) {
                    $sanitized['font_family'] = sanitize_text_field($input['font_family']);
                }
                foreach (array('logo_primary_id', 'logo_secondary_id') as $key) {
                    if (isset($input[$key])) {
                        $sanitized[$key] = absint($input[$key]);
                    }
                }
                foreach (array('email_subject_customer', 'email_subject_admin', 'email_preheader_customer', 'email_preheader_admin') as $key) {
                    if (isset($input[$key])) {
                        $sanitized[$key] = sanitize_text_field($input[$key]);
                    }
                }
                if (isset($input['email_template_mode']) && in_array($input['email_template_mode'], array('default', 'custom'), true)) {
                    $sanitized['email_template_mode'] = $input['email_template_mode'];
                } else {
                    $sanitized['email_template_mode'] = 'custom';
                }
                $sanitized['custom_html'] = isset($input['custom_html']) ? wp_unslash($input['custom_html']) : '';
                $sanitized['custom_css']  = isset($input['custom_css'])  ? wp_unslash($input['custom_css'])  : '';
                return $sanitized;
            }

            /**
             * Sanitize Email Template settings (booking confirmation email) - instance wrapper for register_setting
             */
            public function sanitize_email_template_settings($input)
            {
                $sanitized = self::sanitize_email_template_settings_array($input);
                // Save custom_html and custom_css separately to avoid WordPress HTML stripping
                // WordPress strips HTML tags from option values for users without unfiltered_html capability
                if (isset($input['custom_html'])) {
                    update_option('amadex_email_custom_html', wp_unslash($input['custom_html']));
                }
                if (isset($input['custom_css'])) {
                    update_option('amadex_email_custom_css', wp_unslash($input['custom_css']));
                }
                return $sanitized;
            }

            /**
             * Default values for Brand (global) settings
             */
            public static function get_brand_defaults()
            {
                return array(
                    'company_name' => '',
                    'primary_color' => '#0E7D3F',
                    'body_bg' => '#EEF9F2',
                    'content_bg' => '#ffffff',
                    'text_color' => '#111827',
                    'secondary_text' => '#6B7280',
                    'border_color' => '#E5E7EB',
                    'link_color' => '#0E7D3F',
                    'font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
                    'logo_primary_id' => 0,
                    'logo_secondary_id' => 0,
                );
            }

            /**
             * Brand settings subset used as defaults for Email Template (same key names)
             */
            public static function get_brand_settings_for_email()
            {
                $brand = wp_parse_args(get_option('amadex_brand_settings', array()), self::get_brand_defaults());
                $keys = array('primary_color', 'body_bg', 'content_bg', 'text_color', 'secondary_text', 'border_color', 'link_color', 'font_family', 'logo_primary_id', 'logo_secondary_id');
                $out = array();
                foreach ($keys as $k) {
                    if (isset($brand[$k])) {
                        $out[$k] = $brand[$k];
                    }
                }
                return $out;
            }

            /**
             * Sanitize Brand settings
             */
            public function sanitize_brand_settings($input)
            {
                if (!is_array($input)) {
                    return array();
                }
                $sanitized = array();
                if (isset($input['company_name'])) {
                    $sanitized['company_name'] = sanitize_text_field($input['company_name']);
                }
                $color_keys = array('primary_color', 'body_bg', 'content_bg', 'text_color', 'secondary_text', 'border_color', 'link_color');
                foreach ($color_keys as $key) {
                    if (isset($input[$key]) && preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $input[$key])) {
                        $sanitized[$key] = sanitize_text_field($input[$key]);
                    }
                }
                if (isset($input['font_family'])) {
                    $sanitized['font_family'] = sanitize_text_field($input['font_family']);
                }
                foreach (array('logo_primary_id', 'logo_secondary_id') as $key) {
                    if (isset($input[$key])) {
                        $sanitized[$key] = absint($input[$key]);
                    }
                }
                return wp_parse_args($sanitized, wp_parse_args(get_option('amadex_brand_settings', array()), self::get_brand_defaults()));
            }

            /**
             * NMI Tokenization Key callback
             */
            public function nmi_tokenization_key_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['nmi_tokenization_key']) ? $options['nmi_tokenization_key'] : '';
    ?>
        <input type="text" id="nmi_tokenization_key" name="amadex_payment_settings[nmi_tokenization_key]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Your NMI Tokenization Key for Collect.js. This is a PUBLIC key used for client-side card tokenization. Find it in NMI Dashboard &gt; Settings &gt; Security Keys &gt; Public Key.', 'amadex'); ?></p>
        <p class="description" style="color: #d63638; font-weight: 600;">⚠️ <strong>IMPORTANT:</strong> This must be the PUBLIC tokenization key (not the private API key).</p>
    <?php
            }

            /**
             * NMI Sandbox Mode callback
             */
            public function nmi_sandbox_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['nmi_sandbox']) ? $options['nmi_sandbox'] : 0;
    ?>
        <label for="nmi_sandbox">
            <input type="checkbox" id="nmi_sandbox" name="amadex_payment_settings[nmi_sandbox]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable Sandbox Mode (Test Environment)', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, transactions will be processed in test mode. Disable for live transactions.', 'amadex'); ?></p>
    <?php
            }

            /**
             * NMI Test Connection callback
             */
            public function nmi_test_connection_callback()
            {
    ?>
        <button type="button" class="button" id="amadex-test-nmi-btn"><?php _e('Test Connection', 'amadex'); ?></button>
        <span class="spinner" style="float: none; margin: 0 10px;"></span>
        <p class="amadex-test-result"></p>
        <p class="description"><?php _e('Test your NMI API connection to verify credentials.', 'amadex'); ?></p>
        <script>
            jQuery(document).ready(function($) {
                $('#amadex-test-nmi-btn').on('click', function() {
                    var btn = $(this);
                    var spinner = btn.next('.spinner');
                    var result = $('.amadex-test-result');

                    btn.prop('disabled', true);
                    spinner.addClass('is-active');
                    result.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'amadex_test_nmi',
                            nonce: '<?php echo wp_create_nonce('amadex_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                result.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                            } else {
                                result.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                            }
                        },
                        error: function() {
                            result.html('<span style="color: red;">✗ Connection test failed</span>');
                        },
                        complete: function() {
                            btn.prop('disabled', false);
                            spinner.removeClass('is-active');
                        }
                    });
                });
            });
        </script>
    <?php
            }

            /**
             * NMI Bypass for Testing callback
             */
            public function nmi_bypass_for_testing_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['nmi_bypass_for_testing']) ? $options['nmi_bypass_for_testing'] : 0;
    ?>
        <label for="nmi_bypass_for_testing">
            <input type="checkbox" id="nmi_bypass_for_testing" name="amadex_payment_settings[nmi_bypass_for_testing]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Bypass Payment Authorization (Testing Only)', 'amadex'); ?>
        </label>
        <p class="description" style="color: #d63638; font-weight: 600;">⚠️ <strong>WARNING:</strong> When enabled, payment authorization will be skipped and bookings will be created without actual payment processing. <strong>Only use this for development/testing!</strong></p>
    <?php
            }

            /**
             * 3D Secure (3DS) toggle callback
             */
            public function enable_3ds_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value   = isset($options['enable_3ds']) ? (int) $options['enable_3ds'] : 1;
    ?>
        <label for="enable_3ds">
            <input type="checkbox" id="enable_3ds" name="amadex_payment_settings[enable_3ds]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable 3D Secure Authentication', 'amadex'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, NMI payments will go through the 3DS Two-Step Redirect challenge for extra cardholder verification. Disable only if your merchant account does not require 3DS or for troubleshooting — <strong>not recommended for live transactions.</strong>', 'amadex'); ?>
        </p>
        <?php if (!$value): ?>
            <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:10px 14px;margin-top:8px;">
                <p style="margin:0;color:#856404;font-weight:600;">⚠️ <strong>3DS is currently OFF.</strong> Card payments will be processed without 3D Secure authentication. Ensure your merchant account supports this.</p>
            </div>
        <?php endif; ?>
    <?php
            }

            /**
             * Default Card Processor Gateway callback
             */
            public function default_card_gateway_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['default_card_gateway']) ? $options['default_card_gateway'] : 'nmi';
                $nmi_api_key = isset($options['nmi_api_key']) ? trim($options['nmi_api_key']) : '';
                $nmi_tokenization_key = isset($options['nmi_tokenization_key']) ? trim($options['nmi_tokenization_key']) : '';
                $stripe_publishable_key = isset($options['stripe_publishable_key']) ? trim($options['stripe_publishable_key']) : '';
                $stripe_secret_key = isset($options['stripe_secret_key']) ? trim($options['stripe_secret_key']) : '';

                // Check if keys are configured
                $nmi_configured = !empty($nmi_api_key) && !empty($nmi_tokenization_key);
                $stripe_configured = !empty($stripe_publishable_key) && !empty($stripe_secret_key);
    ?>
        <select id="default_card_gateway" name="amadex_payment_settings[default_card_gateway]" style="width: 300px;">
            <option value="nmi" <?php selected('nmi', $value); ?>>NMI</option>
            <option value="stripe" <?php selected('stripe', $value); ?>>Stripe</option>
        </select>
        <p class="description">
            <?php _e('Select the default payment processor for credit/debit card payments. This only affects card payments; PayPal and other payment methods are independent.', 'amadex'); ?>
        </p>

        <?php if ($value === 'nmi' && !$nmi_configured): ?>
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 10px 0;">
                <p style="margin: 0; color: #856404; font-weight: 600;">⚠️ <strong>NMI Keys Not Configured:</strong> Please configure NMI API Key and Tokenization Key below for NMI to work.</p>
            </div>
        <?php endif; ?>

        <?php if ($value === 'stripe' && !$stripe_configured): ?>
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 10px 0;">
                <p style="margin: 0; color: #856404; font-weight: 600;">⚠️ <strong>Stripe Keys Not Configured:</strong> Please configure Stripe Publishable Key and Secret Key below for Stripe to work.</p>
            </div>
        <?php endif; ?>

        <p class="description" style="margin-top: 10px;">
            <strong>Note:</strong> The payment form visual appearance will remain the same regardless of which gateway is selected. Only the underlying payment processing changes.
        </p>
    <?php
            }

            /**
             * Enable Credit Card callback
             */
            public function enable_credit_card_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['enable_credit_card']) ? $options['enable_credit_card'] : 1;
    ?>
        <label for="enable_credit_card">
            <input type="checkbox" id="enable_credit_card" name="amadex_payment_settings[enable_credit_card]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable Credit/Debit Card payment method', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, customers can pay using credit or debit cards.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable Cryptocurrency Transfer callback
             */
            public function enable_crypto_transfer_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['enable_crypto_transfer']) ? $options['enable_crypto_transfer'] : 1;
    ?>
        <label for="enable_crypto_transfer">
            <input type="checkbox" id="enable_crypto_transfer" name="amadex_payment_settings[enable_crypto_transfer]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable Cryptocurrency Transfer payment method', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, customers can pay using cryptocurrency transfers.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Cryptocurrency Transfer Redirect URL callback
             */
            public function crypto_transfer_redirect_url_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['crypto_transfer_redirect_url']) ? $options['crypto_transfer_redirect_url'] : '';
    ?>
        <input type="url" id="crypto_transfer_redirect_url" name="amadex_payment_settings[crypto_transfer_redirect_url]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Redirect URL for cryptocurrency transfer payment. Leave empty to use default processing.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Enable Crypto.com callback
             */
            public function enable_crypto_com_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['enable_crypto_com']) ? $options['enable_crypto_com'] : 1;
    ?>
        <label for="enable_crypto_com">
            <input type="checkbox" id="enable_crypto_com" name="amadex_payment_settings[enable_crypto_com]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable Crypto.com Pay payment method', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, customers can pay using Crypto.com Pay.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Crypto.com Redirect URL callback
             */
            public function crypto_com_redirect_url_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['crypto_com_redirect_url']) ? $options['crypto_com_redirect_url'] : '';
    ?>
        <input type="url" id="crypto_com_redirect_url" name="amadex_payment_settings[crypto_com_redirect_url]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Redirect URL for Crypto.com Pay. Leave empty to use default processing.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Crypto.com Pay Publishable Key callback
             */
            public function crypto_com_publishable_key_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['crypto_com_publishable_key']) ? $options['crypto_com_publishable_key'] : '';
    ?>
        <input type="text" id="crypto_com_publishable_key" name="amadex_payment_settings[crypto_com_publishable_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="pk_test_... or pk_live_...">
        <p class="description"><?php _e('Publishable key from Crypto.com Pay Merchant Dashboard. Used in the frontend SDK.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Crypto.com Pay Secret Key callback
             */
            public function crypto_com_secret_key_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['crypto_com_secret_key']) ? $options['crypto_com_secret_key'] : '';
    ?>
        <input type="password" id="crypto_com_secret_key" name="amadex_payment_settings[crypto_com_secret_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="sk_test_... or sk_live_...">
        <p class="description"><?php _e('Secret key from Crypto.com Pay. Used only on the server; never expose to the frontend.', 'amadex'); ?></p>
    <?php
            }

            /**
             * MoonPay Commerce section callback (per Hel.io Getting Started)
             */
            public function payment_moonpay_section_callback()
            {
                echo '<p>' . __('Let customers pay with card or crypto; you receive settlement in your Hel.io wallet. Get <strong>Public</strong> and <strong>Secret</strong> API keys from Hel.io: <a href="https://app.hel.io" target="_blank" rel="noopener">app.hel.io</a> (Live) or <a href="https://app.dev.hel.io" target="_blank" rel="noopener">app.dev.hel.io</a> (Test) → Developer → API. See <a href="https://docs.hel.io/reference/getting-started-1" target="_blank" rel="noopener">Hel.io Getting Started</a>.', 'amadex') . '</p>';
            }

            public function enable_moonpay_commerce_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['enable_moonpay_commerce']) ? $options['enable_moonpay_commerce'] : 0;
    ?>
        <label for="enable_moonpay_commerce">
            <input type="checkbox" id="enable_moonpay_commerce" name="amadex_payment_settings[enable_moonpay_commerce]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable MoonPay Commerce payment method', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, customers can pay with card or crypto; you receive settlement in your chosen currency.', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_environment_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_environment']) ? $options['moonpay_environment'] : 'test';
    ?>
        <select id="moonpay_environment" name="amadex_payment_settings[moonpay_environment]">
            <option value="test" <?php selected('test', $value); ?>><?php _e('Test', 'amadex'); ?></option>
            <option value="live" <?php selected('live', $value); ?>><?php _e('Live', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Use Test for development; switch to Live for real payments. Keys below are for the selected environment.', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_public_key_callback()
            {
                $options = get_option('amadex_payment_settings');
                $env = isset($options['moonpay_environment']) && $options['moonpay_environment'] === 'live' ? 'live' : 'test';
                $key = $env === 'live' ? 'moonpay_publishable_key_live' : 'moonpay_publishable_key_test';
                $value = isset($options[$key]) ? $options[$key] : '';
    ?>
        <input type="text" id="moonpay_public_key" name="amadex_payment_settings[moonpay_public_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="<?php echo $env === 'live' ? 'Live public key' : 'pk_test_...'; ?>">
        <p class="description"><?php _e('Public API key for the selected Environment. From Hel.io Dashboard → Developer → API. Copy the full key (no trailing spaces or backslash).', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_secret_key_callback()
            {
                $options = get_option('amadex_payment_settings');
                $env = isset($options['moonpay_environment']) && $options['moonpay_environment'] === 'live' ? 'live' : 'test';
                $key = $env === 'live' ? 'moonpay_secret_key_live' : 'moonpay_secret_key_test';
                $value = isset($options[$key]) ? $options[$key] : '';
    ?>
        <input type="password" id="moonpay_secret_key" name="amadex_payment_settings[moonpay_secret_key]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="<?php echo $env === 'live' ? 'Live secret key' : 'sk_test_...'; ?>">
        <p class="description"><?php _e('Secret API key for the selected Environment. Keep confidential.', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_helio_wallet_id_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_helio_wallet_id']) ? $options['moonpay_helio_wallet_id'] : '';
    ?>
        <input type="text" id="moonpay_helio_wallet_id" name="amadex_payment_settings[moonpay_helio_wallet_id]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Dashboard → Settings → Manage Wallets → three dots next to wallet → Copy Helio ID. Required for pay links.', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_settlement_currencies_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_settlement_currencies']) ? $options['moonpay_settlement_currencies'] : 'USDC, USDT';
    ?>
        <input type="text" id="moonpay_settlement_currencies" name="amadex_payment_settings[moonpay_settlement_currencies]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="USDC, USDT">
        <p class="description"><?php _e('Comma-separated (e.g. USDC, USDT). First supported currency is used for the pay link.', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_helio_webhook_secret_callback()
            {
                $options = get_option('amadex_payment_settings', array());
                $value = isset($options['moonpay_helio_webhook_secret']) ? $options['moonpay_helio_webhook_secret'] : '';
                $url = rest_url('amadex/v1/helio-webhook');
    ?>
        <input type="password" id="moonpay_helio_webhook_secret" name="amadex_payment_settings[moonpay_helio_webhook_secret]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Optional. Set this to the shared token from your Hel.io Pay Link webhook. Webhook URL: ', 'amadex'); ?><code><?php echo esc_html($url); ?></code></p>
    <?php
            }

            public function payment_moonpay_onramp_section_callback()
            {
                echo '<p>' . __('Let customers pay with card in a MoonPay widget on your site. Crypto is sent to your BTC wallet. Uses keys from <a href="https://dashboard.moonpay.com" target="_blank" rel="noopener">dashboard.moonpay.com</a> (Onramp/Offramp), not Helio.', 'amadex') . '</p>';
            }

            public function enable_moonpay_onramp_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['enable_moonpay_onramp']) ? $options['enable_moonpay_onramp'] : 0;
    ?>
        <label for="enable_moonpay_onramp">
            <input type="checkbox" id="enable_moonpay_onramp" name="amadex_payment_settings[enable_moonpay_onramp]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable MoonPay Onramp (pay with card on site)', 'amadex'); ?>
        </label>
    <?php
            }

            public function moonpay_onramp_environment_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_onramp_environment']) ? $options['moonpay_onramp_environment'] : 'test';
    ?>
        <select id="moonpay_onramp_environment" name="amadex_payment_settings[moonpay_onramp_environment]">
            <option value="test" <?php selected('test', $value); ?>><?php _e('Test (Sandbox)', 'amadex'); ?></option>
            <option value="live" <?php selected('live', $value); ?>><?php _e('Live', 'amadex'); ?></option>
        </select>
        <p class="description"><?php _e('Use Test until you complete MoonPay KYB.', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_onramp_publishable_key_test_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_onramp_publishable_key_test']) ? $options['moonpay_onramp_publishable_key_test'] : '';
    ?>
        <input type="text" id="moonpay_onramp_publishable_key_test" name="amadex_payment_settings[moonpay_onramp_publishable_key_test]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="pk_test_...">
        <p class="description"><?php _e('From MoonPay Dashboard → Developers → API keys (Onramp).', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_onramp_secret_key_test_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_onramp_secret_key_test']) ? $options['moonpay_onramp_secret_key_test'] : '';
    ?>
        <input type="password" id="moonpay_onramp_secret_key_test" name="amadex_payment_settings[moonpay_onramp_secret_key_test]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="sk_test_...">
        <p class="description"><?php _e('Used to sign widget URL (required for wallet address). Keep confidential.', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_onramp_publishable_key_live_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_onramp_publishable_key_live']) ? $options['moonpay_onramp_publishable_key_live'] : '';
    ?>
        <input type="text" id="moonpay_onramp_publishable_key_live" name="amadex_payment_settings[moonpay_onramp_publishable_key_live]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="pk_live_...">
    <?php
            }

            public function moonpay_onramp_secret_key_live_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_onramp_secret_key_live']) ? $options['moonpay_onramp_secret_key_live'] : '';
    ?>
        <input type="password" id="moonpay_onramp_secret_key_live" name="amadex_payment_settings[moonpay_onramp_secret_key_live]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="sk_live_...">
    <?php
            }

            public function moonpay_onramp_merchant_wallet_btc_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_onramp_merchant_wallet_btc']) ? $options['moonpay_onramp_merchant_wallet_btc'] : '';
    ?>
        <input type="text" id="moonpay_onramp_merchant_wallet_btc" name="amadex_payment_settings[moonpay_onramp_merchant_wallet_btc]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="bc1q... or 3...">
        <p class="description"><?php _e('Mainnet BTC address for production (bc1q... or 3...). Must match the wallet in your MoonPay dashboard.', 'amadex'); ?></p>
    <?php
            }

            public function moonpay_onramp_merchant_wallet_btc_sandbox_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['moonpay_onramp_merchant_wallet_btc_sandbox']) ? $options['moonpay_onramp_merchant_wallet_btc_sandbox'] : '';
    ?>
        <input type="text" id="moonpay_onramp_merchant_wallet_btc_sandbox" name="amadex_payment_settings[moonpay_onramp_merchant_wallet_btc_sandbox]" value="<?php echo esc_attr($value); ?>" class="regular-text" placeholder="tb1q...">
        <p class="description"><?php _e('Bitcoin Testnet address (tb1q...) for sandbox testing. Required when Environment is set to Test. Generate one from a testnet wallet.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Call Banner section callback
             */
            public function call_banner_section_callback()
            {
                echo '<p>' . __('Configure the call banner HTML/CSS that will be displayed on flight cards in place of the call button. Leave empty to hide the banner area.', 'amadex') . '</p>';
            }

            /**
             * Get default call banner HTML
             */
            private function get_default_call_banner_html()
            {
                return '<style>
  :root{
    --travelay-green:#0E7D3F;
    --travelay-orange:#F6851F;
    --travelay-dark:#0b0f12;
    --panel-bg: rgba(0,0,0,0.10);
    --card-radius:16px;
    --safe-bottom: 12px;
    --outer-pad: 12px;
    --rotator-font: 15px;
  }

  .tgn-banner{
    width:100%;
    display:flex;
    justify-content:center;
    pointer-events:none;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  }

  .tgn-card{
    width:100%;
    max-width:620px;
    pointer-events:auto;
    position:relative;
    filter:drop-shadow(0 12px 28px rgba(0,0,0,0.28));
  }

  @keyframes tgnBlinkBG{
    0%,49%{ background:var(--travelay-green); }
    50%,100%{ background:var(--travelay-orange); }
  }
  @keyframes tgnStroke{
    0%,49%{ stroke:var(--travelay-green); }
    50%,100%{ stroke:var(--travelay-orange); }
  }
  @keyframes tgnShine{
    0%   { transform:translateX(-160%) skewX(-20deg); opacity:0; }
    10%  { opacity:0.35; }
    35%  { opacity:0; }
    100% { transform:translateX(260%) skewX(-20deg); opacity:0; }
  }

  .tgn-drawer{
    position:relative;
    overflow:hidden;
    border-radius:var(--card-radius);
    border:1px solid rgba(255,255,255,0.55);
    background:var(--travelay-green);
  }

  .tgn-collapsed{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    cursor:pointer;
    user-select:none;
    color:#fff;
    text-decoration:none;
    animation:tgnBlinkBG 1.05s steps(1,end) infinite;
    transform:skewX(-8deg);
    position:relative;
    overflow:hidden;
  }
  .tgn-collapsed::after{
    content:"";
    position:absolute;
    inset:0;
    width:60%;
    background:rgba(255,255,255,0.75);
    mix-blend-mode:overlay;
    transform:translateX(-160%) skewX(-20deg);
    animation:tgnShine 2.4s linear infinite;
    pointer-events:none;
  }

  .tgn-collapsed-inner{
    transform:skewX(8deg);
    display:flex;
    align-items:center;
    gap:12px;
    width:100%;
    position:relative;
    z-index:2;
  }

  .tgn-icon{
    width:46px;
    height:46px;
    border-radius:50%;
    background:#fff;
    display:grid;
    place-items:center;
    flex:0 0 46px;
  }
  .tgn-icon svg{
    width:20px;
    height:20px;
    fill:none;
    stroke:var(--travelay-green);
    stroke-width:2.3;
    animation:tgnStroke 1.05s steps(1,end) infinite;
  }

  .tgn-center{
    flex:1;
    min-width:0;
    text-align:center;
    line-height:1.15;
  }

  .tgn-rotator{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:8px 14px;
    border-radius:999px;
    background:rgba(255,255,255,0.96);
    color:#111;
    font-weight:900;
    letter-spacing:0.01em;
    font-size:var(--rotator-font);
    white-space:nowrap;
    max-width:100%;
  }
  .tgn-rotator span{
    overflow:hidden;
    text-overflow:ellipsis;
    max-width:100%;
    display:block;
  }

  .tgn-arrow{
    flex:0 0 auto;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:40px;
    height:40px;
    border-radius:12px;
    background:rgba(255,255,255,0.18);
    border:1px solid rgba(255,255,255,0.35);
    font-size:18px;
    line-height:1;
  }

  @media (max-width:420px){
    :root{ --rotator-font: 14px; }
    .tgn-rotator{ padding:7px 12px; }
  }

  .tgn-expanded{
    max-height:0;
    opacity:0;
    transform:translateY(6px);
    transition:max-height .28s ease, opacity .18s ease, transform .18s ease;
    background:var(--panel-bg);
    backdrop-filter:blur(2px);
  }

  .tgn-open .tgn-expanded{
    max-height: calc(100dvh - 130px);
    opacity:1;
    transform:translateY(0);
  }

  .tgn-panel{
    padding:12px 14px 14px 14px;
    max-height: calc(100dvh - 150px);
    overflow:auto;
    -webkit-overflow-scrolling: touch;
  }

  @supports not (height: 100dvh) {
    .tgn-open .tgn-expanded{ max-height: calc(100vh - 130px); }
    .tgn-panel{ max-height: calc(100vh - 150px); }
  }

  .tgn-panel-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
    color:#fff;
    margin-bottom:10px;
  }

  .tgn-title{
    font-weight:900;
    letter-spacing:.02em;
    font-size:14px;
    line-height:1.2;
  }
  .tgn-subtitle{
    font-weight:600;
    opacity:.9;
    font-size:12px;
    margin-top:2px;
  }

  .tgn-actions{
    display:flex;
    align-items:center;
    gap:8px;
    flex:0 0 auto;
  }

  .tgn-btn{
    border:none;
    cursor:pointer;
    color:#fff;
    background:rgba(255,255,255,0.18);
    border:1px solid rgba(255,255,255,0.35);
    border-radius:12px;
    width:40px;
    height:40px;
    display:grid;
    place-items:center;
    font-size:18px;
    line-height:1;
  }

  .tgn-close{
    width:22px;
    height:22px;
    border-radius:50%;
    border:none;
    background:#000;
    color:#fff;
    font-size:14px;
    line-height:1;
    cursor:pointer;
    margin-top:9px;
  }

  .tgn-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:10px;
  }
  @media (max-width:720px){
    .tgn-grid{ grid-template-columns:1fr; }
  }

  .tgn-item{
    --accent1:#111;
    --accent2:#111;
    --mark:url("");
    --tint: rgba(0,0,0,0.035);

    position:relative;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:12px 12px 12px 14px;
    border-radius:14px;
    text-decoration:none;
    color:#0b0f12;
    background:rgba(255,255,255,0.98);
    border:1px solid rgba(0,0,0,0.06);
    overflow:hidden;
    isolation:isolate;
    transform:translateZ(0);
    min-width:0;
  }

  .tgn-item::before{
    content:"";
    position:absolute;
    left:0;
    top:0;
    bottom:0;
    width:7px;
    background:linear-gradient(180deg, var(--accent1), var(--accent2));
    box-shadow: 2px 0 0 rgba(0,0,0,0.04);
    z-index:0;
  }

  .tgn-item::after{
    content:"";
    position:absolute;
    inset:0;
    background:
      linear-gradient(90deg, var(--tint), transparent 62%),
      var(--mark);
    background-repeat:no-repeat;
    background-position: right 12px center;
    background-size: 64px 64px;
    opacity:1;
    z-index:0;
  }

  .tgn-left{
    display:flex;
    align-items:center;
    gap:10px;
    min-width:0;
    position:relative;
    z-index:1;
    flex:1;
  }

  .tgn-badge{
    flex:0 0 auto;
    display:inline-flex;
    align-items:center;
    gap:7px;
    font-weight:900;
    font-size:12px;
    letter-spacing:.04em;
    padding:6px 10px;
    border-radius:999px;
    color:#fff;
    background:var(--travelay-dark);
    white-space:nowrap;
  }

  .tgn-flag{
    width:18px;
    height:18px;
    border-radius:50%;
    overflow:hidden;
    box-shadow:
      inset 0 0 0 1px rgba(255,255,255,0.28),
      0 1px 2px rgba(0,0,0,0.18);
    flex:0 0 18px;
    display:block;
  }
  .tgn-flag svg{ width:18px; height:18px; display:block; }

  .tgn-number{
    font-weight:900;
    font-size:14px;
    letter-spacing:.01em;
    position:relative;
    z-index:1;
    min-width:0;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  @media (min-width:721px){
    .tgn-number{
      white-space:normal;
      overflow:visible;
      text-overflow:clip;
      line-height:1.15;
    }
  }

  .tgn-call{
    flex:0 0 auto;
    font-weight:900;
    font-size:12px;
    padding:7px 10px;
    border-radius:999px;
    background:rgba(0,0,0,0.08);
    position:relative;
    z-index:1;
    white-space:nowrap;
  }

  @media (max-width:380px){
    .tgn-item{ padding:11px 10px 11px 12px; }
    .tgn-badge{ padding:6px 9px; }
    .tgn-call{ padding:7px 9px; }
    .tgn-number{ font-size:13px; }
  }

  .tgn-us{
    --accent1:#B22234; --accent2:#3C3B6E;
    --tint: rgba(178,34,52,0.06);
    --mark: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 64 64\'%3E%3Cg opacity=\'0.10\'%3E%3Cpath fill=\'%233C3B6E\' d=\'M40 16l2.4 4.9 5.4.8-3.9 3.8.9 5.4L40 28.7l-4.8 2.5.9-5.4-3.9-3.8 5.4-.8z\'/%3E%3Cpath fill=\'%23B22234\' d=\'M14 40h36v4H14zm0 8h36v4H14z\'/%3E%3C/g%3E%3C/svg%3E");
  }
  .tgn-au{
    --accent1:#012169; --accent2:#012169;
    --tint: rgba(1,33,105,0.06);
    --mark: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 64 64\'%3E%3Cg opacity=\'0.10\' fill=\'%23ffffff\'%3E%3Cpath d=\'M40 18l2.2 4.5 5 .7-3.6 3.5.9 5-4.5-2.3-4.5 2.3.9-5-3.6-3.5 5-.7z\'/%3E%3Cpath d=\'M48 34l1.6 3.3 3.6.5-2.6 2.6.6 3.6-3.2-1.7-3.2 1.7.6-3.6-2.6-2.6 3.6-.5z\'/%3E%3C/g%3E%3C/svg%3E");
  }
  .tgn-uk{
    --accent1:#012169; --accent2:#C8102E;
    --tint: rgba(1,33,105,0.06);
    --mark: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 64 64\'%3E%3Cg opacity=\'0.10\'%3E%3Cpath fill=\'%23012169\' d=\'M14 18l36 28v-6L22 18z\'/%3E%3Cpath fill=\'%23C8102E\' d=\'M14 46l36-28v6L22 46z\'/%3E%3Cpath fill=\'%23C8102E\' d=\'M30 14h4v36h-4z\'/%3E%3C/g%3E%3C/svg%3E");
  }
  .tgn-ca{
    --accent1:#D80621; --accent2:#D80621;
    --tint: rgba(216,6,33,0.06);
    --mark: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 64 64\'%3E%3Cg opacity=\'0.10\'%3E%3Cpath fill=\'%23D80621\' d=\'M32 12l3 9 9-2-6 7 6 7-9-2-3 9-3-9-9 2 6-7-6-7 9 2z\'/%3E%3C/g%3E%3C/svg%3E");
  }
  .tgn-in{
    --accent1:#FF9933; --accent2:#138808;
    --tint: rgba(255,153,51,0.06);
    --mark: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\' viewBox=\'0 0 64 64\'%3E%3Cg opacity=\'0.10\'%3E%3Ccircle cx=\'32\' cy=\'32\' r=\'14\' fill=\'none\' stroke=\'%23000080\' stroke-width=\'3\'/%3E%3Cpath d=\'M32 18v28M18 32h28M22.1 22.1l19.8 19.8M41.9 22.1L22.1 41.9\' stroke=\'%23000080\' stroke-width=\'2\'/%3E%3C/g%3E%3C/svg%3E");
  }

  .tgn-note{
    margin-top:10px;
    color:#fff;
    opacity:.92;
    font-size:12px;
    font-weight:700;
    text-align:center;
  }

  @media (min-width:769px){
    .tgn-collapsed, .tgn-collapsed-inner, .tgn-rotator, .tgn-title, .tgn-number, .tgn-call, .tgn-badge{
      transform:translateZ(0);
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      backface-visibility:hidden;
      text-rendering:optimizeLegibility;
    }
  }

  @media (prefers-reduced-motion: reduce){
    .tgn-collapsed{ animation:none !important; }
    .tgn-icon svg{ animation:none !important; }
    .tgn-collapsed::after{ display:none !important; }
    .tgn-expanded{ transition:none !important; }
  }
</style>

<div class="tgn-banner tgn-banner-instance">
  <div class="tgn-card">
    <div class="tgn-drawer">
      <div class="tgn-collapsed tgn-toggle" role="button" aria-label="Open global support numbers">
        <div class="tgn-collapsed-inner">
          <div class="tgn-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
          </div>
          <div class="tgn-center">
            <div class="tgn-rotator" aria-live="polite">
              <span class="tgn-rotating-line">📞 CLICK TO CALL YOUR LOCAL TFN</span>
            </div>
          </div>
          <div class="tgn-arrow">⬆️</div>
        </div>
      </div>
      <div class="tgn-expanded" aria-hidden="true">
        <div class="tgn-panel">
          <div class="tgn-panel-header">
            <div>
              <div class="tgn-title">🌍 FREE GLOBAL SUPPORT</div>
              <div class="tgn-subtitle">Select your country toll-free line to call</div>
            </div>
            <div class="tgn-actions">
              <button class="tgn-btn tgn-collapse-btn" type="button" aria-label="Collapse">⬇️</button>
              <button class="tgn-close tgn-hide-btn" type="button" aria-label="Hide banner">&times;</button>
            </div>
          </div>
          <div class="tgn-grid" role="list">
            <a class="tgn-item tgn-us" role="listitem" href="tel:+18777210410" aria-label="Call US support">
              <div class="tgn-left">
                <div class="tgn-badge">
                  <span class="tgn-flag" aria-hidden="true"></span>
                  US
                </div>
                <div class="tgn-number">+1 (877) 721-0410</div>
              </div>
              <div class="tgn-call">CALL</div>
            </a>
            <a class="tgn-item tgn-au" role="listitem" href="tel:+611800370349" aria-label="Call Australia support">
              <div class="tgn-left">
                <div class="tgn-badge">
                  <span class="tgn-flag" aria-hidden="true"></span>
                  AU
                </div>
                <div class="tgn-number">+61 1800 370 349</div>
              </div>
              <div class="tgn-call">CALL</div>
            </a>
            <a class="tgn-item tgn-uk" role="listitem" href="tel:+448002946339" aria-label="Call UK support">
              <div class="tgn-left">
                <div class="tgn-badge">
                  <span class="tgn-flag" aria-hidden="true"></span>
                  UK
                </div>
                <div class="tgn-number">+44 800 294 6339</div>
              </div>
              <div class="tgn-call">CALL</div>
            </a>
            <a class="tgn-item tgn-ca" role="listitem" href="tel:+18777210410" aria-label="Call Canada support">
              <div class="tgn-left">
                <div class="tgn-badge">
                  <span class="tgn-flag" aria-hidden="true"></span>
                  CA
                </div>
                <div class="tgn-number">+1 (877) 721-0410</div>
              </div>
              <div class="tgn-call">CALL</div>
            </a>
            <a class="tgn-item tgn-in" role="listitem" href="tel:+917888798732" aria-label="Call India support">
              <div class="tgn-left">
                <div class="tgn-badge">
                  <span class="tgn-flag" aria-hidden="true"></span>
                  IN
                </div>
                <div class="tgn-number">+91 78887 98732</div>
              </div>
              <div class="tgn-call">CALL</div>
            </a>
          </div>
          <div class="tgn-note">Toll-Free Numbers • No Call Charges • 24/7 Human Support</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  function initTgnBanner(banner){
    if(!banner) return;
    
    var drawer = banner.querySelector(\'.tgn-drawer\');
    var toggle = banner.querySelector(\'.tgn-toggle\');
    var expanded = banner.querySelector(\'.tgn-expanded\');
    var arrow = banner.querySelector(\'.tgn-arrow\');
    var collapseBtn = banner.querySelector(\'.tgn-collapse-btn\');
    var hideBtn = banner.querySelector(\'.tgn-hide-btn\');
    var rotatorEl = banner.querySelector(\'.tgn-rotating-line\');

    if(!drawer || !toggle || !expanded || !arrow || !rotatorEl) return;

    function setOpen(open){
      if(open){
        drawer.classList.add(\'tgn-open\');
        expanded.setAttribute(\'aria-hidden\',\'false\');
        arrow.textContent = \'⬇️\';
      }else{
        drawer.classList.remove(\'tgn-open\');
        expanded.setAttribute(\'aria-hidden\',\'true\');
        arrow.textContent = \'⬆️\';
      }
    }

    setOpen(false);

    function onToggle(){
      var open = drawer.classList.contains(\'tgn-open\');
      setOpen(!open);
      setTimeout(function(){ fitRotatorText(); }, 20);
    }

    toggle.addEventListener(\'click\', function(e){
      e.preventDefault();
      onToggle();
    });

    toggle.addEventListener(\'keydown\', function(e){
      if(e.key === \'Enter\' || e.key === \' \'){
        e.preventDefault();
        onToggle();
      }
    });

    if(collapseBtn){
      collapseBtn.addEventListener(\'click\', function(e){
        e.preventDefault();
        e.stopPropagation();
        setOpen(false);
      });
    }

    if(hideBtn){
      hideBtn.addEventListener(\'click\', function(e){
        e.preventDefault();
        e.stopPropagation();
        banner.style.display = \'none\';
      });
    }

    var CLAIMS = {
      govt: false,
      iata: false,
      atol: false
    };

    var baseLines = [
      "📞 TAP TO CALL YOUR LOCAL TFN",
      "💸 CALL FOR A BETTER PRICE",
      "☎️ TOLL-FREE • NO CHARGES",
      "⚡ LIVE FARES ON CALL",
      "🧑‍✈️ TALK TO A FLIGHT EXPERT",
      "✅ 24/7 HUMAN SUPPORT",
      "🔥 LIMITED SEATS • CALL NOW",
      "💳 SECURE PAYMENTS",
      "🧾 INSTANT CONFIRMATION"
    ];

    var claimLines = [];
    if (CLAIMS.govt) claimLines.push("🏛️ GOVT ENDORSED AGENCY");
    if (CLAIMS.iata) claimLines.push("🛡️ IATA AFFILIATED / ACCREDITED");
    if (CLAIMS.atol) claimLines.push("🛡️ ATOL CERTIFIED PROTECTION");

    var lines = baseLines.concat(claimLines);
    var i = Math.floor(Math.random() * lines.length);
    rotatorEl.textContent = lines[i];

    function getMaxRotatorWidth(){
      var center = banner.querySelector(\'.tgn-center\');
      if(!center) return null;
      var w = center.getBoundingClientRect().width;
      return Math.max(180, w);
    }

    function setRootVar(name, value){
      try{ document.documentElement.style.setProperty(name, value); }catch(e){}
    }

    function fitRotatorText(){
      var maxW = getMaxRotatorWidth();
      if(!maxW) return;
      var rot = rotatorEl;
      if(!rot) return;
      var min = 12;
      var safety = 20;
      var n = 15;
      setRootVar(\'--rotator-font\', n + \'px\');
      while(n > min && rot.scrollWidth > (rot.clientWidth + safety)){
        n -= 1;
        setRootVar(\'--rotator-font\', n + \'px\');
      }
    }

    fitRotatorText();
    window.addEventListener(\'resize\', function(){
      fitRotatorText();
    }, {passive:true});

    setInterval(function(){
      i = (i + 1) % lines.length;
      rotatorEl.textContent = lines[i];
      fitRotatorText();
    }, 4000);

  }

  window.initTgnAllBanners = function(){
    var banners = document.querySelectorAll(\'.tgn-banner-instance:not([data-tgn-initialized])\');
    for(var i = 0; i < banners.length; i++){
      banners[i].setAttribute(\'data-tgn-initialized\', \'true\');
      initTgnBanner(banners[i]);
    }
  };

  function initAllBanners(){
    window.initTgnAllBanners();
  }

  if(document.readyState === \'loading\'){
    document.addEventListener(\'DOMContentLoaded\', initAllBanners);
  }else{
    initAllBanners();
  }

  setTimeout(initAllBanners, 500);

})();
</script>';
            }

            /**
             * Call Banner HTML callback
             */
            public function call_banner_html_callback()
            {
                $options = get_option('amadex_call_banner_settings');

                // If option doesn't exist or is empty, set the default
                if (empty($options) || empty($options['call_banner_html'])) {
                    $default_options = array('call_banner_html' => $this->get_default_call_banner_html());
                    update_option('amadex_call_banner_settings', $default_options);
                    $value = $this->get_default_call_banner_html();
                } else {
                    $value = $options['call_banner_html'];
                }
    ?>
        <textarea id="call_banner_html" name="amadex_call_banner_settings[call_banner_html]" rows="15" cols="80" class="large-text code" style="font-family: monospace;"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php _e('Enter HTML/CSS code for the call banner. This will replace the default call button on flight cards. The banner will automatically maintain the same dimensions and responsive behavior as the original button.', 'amadex'); ?>
        </p>
        <p class="description" style="margin-top: 10px;">
            <strong><?php _e('Tips:', 'amadex'); ?></strong><br>
            <?php _e('- Use inline CSS for styling to ensure proper rendering<br>
            - The banner will be contained within the same space as the call button (width: 100%, padding: 12px 18px, border-radius: 999px)<br>
            - Responsive breakpoints are maintained automatically<br>
            - Leave empty to hide the banner area', 'amadex'); ?>
        </p>
    <?php
            }

            /**
             * Sanitize call banner settings
             */
            public function sanitize_call_banner_settings($input)
            {
                $sanitized = array();

                if (isset($input['call_banner_html'])) {
                    // For admin-only HTML/CSS/JS input, use less restrictive sanitization
                    // Allow script and style tags with their content preserved
                    // This is safe since only admins can edit this field
                    $html = stripslashes($input['call_banner_html']);

                    // Use wp_kses with custom allowed tags including script and style
                    $allowed_tags = wp_kses_allowed_html('post');

                    // Add script tags with all attributes
                    $allowed_tags['script'] = array(
                        'type' => true,
                        'src' => true,
                        'async' => true,
                        'defer' => true,
                        'charset' => true,
                        'language' => true,
                    );

                    // Add style tags
                    $allowed_tags['style'] = array(
                        'type' => true,
                        'media' => true,
                        'scoped' => true,
                    );

                    // Allow all standard HTML attributes on all elements
                    $global_attrs = array(
                        'id' => true,
                        'class' => true,
                        'style' => true,
                        'title' => true,
                        'lang' => true,
                        'dir' => true,
                        'role' => true,
                        'aria-*' => true,
                        'data-*' => true,
                        'onclick' => false, // Allow onclick for interactivity
                        'onload' => false,
                    );

                    // Apply global attributes to all tags
                    foreach ($allowed_tags as $tag => &$attrs) {
                        $attrs = array_merge($attrs, $global_attrs);
                    }

                    // First, let's preserve script and style content
                    // wp_kses will strip script tags by default, so we'll use a workaround
                    // For now, use stripslashes and minimal cleaning, since this is admin-only
                    $sanitized['call_banner_html'] = $html;
                }

                return $sanitized;
            }

            /**
             * Enable PayPal callback
             */
            public function enable_paypal_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['enable_paypal']) ? $options['enable_paypal'] : 1;
    ?>
        <label for="enable_paypal">
            <input type="checkbox" id="enable_paypal" name="amadex_payment_settings[enable_paypal]" value="1" <?php checked(1, $value); ?>>
            <?php _e('Enable PayPal payment method', 'amadex'); ?>
        </label>
        <p class="description"><?php _e('When enabled, customers can pay using PayPal.', 'amadex'); ?></p>
    <?php
            }

            /**
             * PayPal Redirect URL callback
             */
            public function paypal_redirect_url_callback()
            {
                $options = get_option('amadex_payment_settings');
                $value = isset($options['paypal_redirect_url']) ? $options['paypal_redirect_url'] : '';
    ?>
        <input type="url" id="paypal_redirect_url" name="amadex_payment_settings[paypal_redirect_url]" value="<?php echo esc_attr($value); ?>" class="regular-text">
        <p class="description"><?php _e('Redirect URL for PayPal payment. Leave empty to use default PayPal integration.', 'amadex'); ?></p>
    <?php
            }

            /**
             * Render settings page
             */
            public function render_settings_page()
            {
                // Get current tab
                $this->current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

                // Validate current tab
                if (!array_key_exists($this->current_tab, $this->tabs)) {
                    $this->current_tab = 'general';
                }

                // Get current tab settings page
                $current_tab_page = 'amadex_' . $this->current_tab . '_settings';
    ?>
        <div class="wrap amadex-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="amadex-admin-header">
                <div class="amadex-logo">
                    <img src="<?php echo AMADEX_URL; ?>assets/images/amadex-logo.png" alt="Amadex Logo">
                </div>
            </div>

            <div class="amadex-settings-tabs">
                <h2 class="nav-tab-wrapper">
                    <?php
                    foreach ($this->tabs as $tab_id => $tab_name) {
                        $tab_url = add_query_arg(array(
                            'page' => 'amadex-settings',
                            'tab' => $tab_id
                        ), admin_url('admin.php'));
                        $active = $this->current_tab === $tab_id ? 'nav-tab-active' : '';
                        echo '<a href="' . esc_url($tab_url) . '" class="nav-tab ' . $active . '">' . esc_html($tab_name) . '</a>';
                    }
                    ?>
                </h2>
            </div>

            <div class="amadex-settings-content">
                <?php
                // Display settings errors and success messages
                settings_errors('amadex_api_settings');

                // Check if settings were just saved
                if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
                    if ($this->current_tab === 'api') {
                        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Success!', 'amadex') . '</strong> ' . __('API settings have been saved successfully.', 'amadex') . '</p></div>';
                    } elseif ($this->current_tab === 'email_template') {
                        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Success!', 'amadex') . '</strong> ' . __('Email template settings saved. Refresh the preview to see changes.', 'amadex') . '</p></div>';
                    } elseif ($this->current_tab === 'brand') {
                        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Success!', 'amadex') . '</strong> ' . __('Brand settings saved. They are used as defaults for the Email Template.', 'amadex') . '</p></div>';
                    } else {
                        echo '<div class="notice notice-success is-dismissible"><p><strong>' . __('Success!', 'amadex') . '</strong> ' . __('Settings have been saved successfully.', 'amadex') . '</p></div>';
                    }
                }

                // For addons tab, don't use the standard settings form
                if ($this->current_tab === 'addons') {
                    // Render addons management directly (has its own form)
                    $this->render_addons_management();
                } elseif ($this->current_tab === 'promotional_containers') {
                    // Render promotional containers management directly (has its own form)
                    $this->render_promotional_containers_management();
                } elseif ($this->current_tab === 'email_template') {
                    $this->render_email_template_tab();
                } elseif ($this->current_tab === 'brand') {
                    $this->render_brand_tab();
                } else {
                ?>
                    <form method="post" action="options.php" id="amadex-settings-form">
                        <?php
                        // Output security fields for the registered setting
                        settings_fields($current_tab_page);

                        // For pricing tab, also output settings_fields for pricing_rules_settings
                        if ($this->current_tab === 'pricing') {
                            // This ensures amadex_pricing_rules_settings is processed
                            settings_fields('amadex_pricing_settings');
                        }

                        // Add redirect URL to return to current tab after save
                        $redirect_url = add_query_arg(array(
                            'page' => 'amadex-settings',
                            'tab' => $this->current_tab,
                            'settings-updated' => 'true'
                        ), admin_url('admin.php'));
                        ?>
                        <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr($redirect_url); ?>">
                        <?php
                        // For payment tab, render with accordion UI
                        if ($this->current_tab === 'payment') {
                            $this->render_payment_settings_with_accordions();
                        } else {
                            // Output setting sections and their fields (standard rendering for other tabs)
                            do_settings_sections($current_tab_page);
                        }

                        // For pricing tab, ensure pricing_rules_settings is processed
                        // The settings_fields('amadex_pricing_settings') call above should handle this

                        // Output save settings button
                        submit_button(__('Save Settings', 'amadex'));
                        ?>
                    </form>
                <?php
                } // End if not addons tab

                // Render modals outside the settings form (to avoid nested form issue)
                if ($this->current_tab === 'pricing') {
                    $this->render_pricing_rules_modal();
                }
                ?>

                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        // Ensure checkbox value is included in form submission
                        $('#amadex-settings-form').on('submit', function() {
                            var isChecked = $('#enable_pricing_rules_engine').is(':checked');
                            // The checkbox name already includes the setting group, so it should save automatically
                            console.log('Pricing Rules Engine checkbox state on submit:', isChecked);
                        });

                        <?php if ($this->current_tab === 'payment'): ?>
                            // Payment settings accordion functionality
                            $('.amadex-payment-accordion').each(function() {
                                var $accordion = $(this);
                                var $header = $accordion.find('.amadex-accordion-header');
                                var $content = $accordion.find('.amadex-accordion-content');
                                var isDefault = $accordion.hasClass('amadex-accordion-default-open');

                                // Set initial state
                                if (isDefault) {
                                    $accordion.addClass('amadex-accordion-open');
                                    $content.slideDown(200);
                                } else {
                                    $content.hide();
                                }

                                // Toggle on header click
                                $header.on('click', function(e) {
                                    e.preventDefault();
                                    var wasOpen = $accordion.hasClass('amadex-accordion-open');

                                    if (wasOpen) {
                                        $accordion.removeClass('amadex-accordion-open');
                                        $content.slideUp(200);
                                    } else {
                                        $accordion.addClass('amadex-accordion-open');
                                        $content.slideDown(200);
                                    }
                                });
                            });

                            // Update ON/OFF label when header switch is toggled
                            $(document).on('change', '.amadex-header-switch .amadex-toggle-input', function() {
                                var $label = $(this).siblings('.amadex-toggle-label');
                                $label.text($(this).is(':checked') ? '<?php echo esc_js(__('ON', 'amadex')); ?>' : '<?php echo esc_js(__('OFF', 'amadex')); ?>');
                            });
                        <?php endif; ?>
                    });
                </script>

                <?php if ($this->current_tab === 'payment'): ?>
                    <style type="text/css">
                        /* Payment Settings Accordion Styles */
                        .amadex-payment-accordion {
                            border: 1px solid #ccd0d4;
                            border-radius: 4px;
                            margin-bottom: 20px;
                            background: #fff;
                            box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                        }

                        .amadex-payment-accordion.amadex-accordion-open {
                            border-color: #2271b1;
                        }

                        .amadex-accordion-header {
                            padding: 15px 20px;
                            cursor: pointer;
                            user-select: none;
                            background: #f6f7f7;
                            border-bottom: 1px solid #ccd0d4;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            transition: background-color 0.2s;
                        }

                        .amadex-payment-accordion.amadex-accordion-open .amadex-accordion-header {
                            background: #f0f6fc;
                            border-bottom-color: #2271b1;
                        }

                        .amadex-accordion-header:hover {
                            background: #f0f0f1;
                        }

                        .amadex-payment-accordion.amadex-accordion-open .amadex-accordion-header:hover {
                            background: #e8f0f8;
                        }

                        .amadex-accordion-title {
                            font-size: 16px;
                            font-weight: 600;
                            color: #1d2327;
                            display: flex;
                            align-items: center;
                            gap: 10px;
                        }

                        .amadex-accordion-icon {
                            width: 0;
                            height: 0;
                            border-left: 5px solid transparent;
                            border-right: 5px solid transparent;
                            border-top: 6px solid #50575e;
                            transition: transform 0.2s;
                            margin-left: auto;
                        }

                        .amadex-payment-accordion.amadex-accordion-open .amadex-accordion-icon {
                            transform: rotate(180deg);
                        }

                        .amadex-accordion-content {
                            padding: 20px;
                            display: none;
                        }

                        .amadex-payment-accordion.amadex-accordion-open .amadex-accordion-content {
                            display: block;
                        }

                        .amadex-payment-gateway-header {
                            margin-bottom: 20px;
                            padding-bottom: 15px;
                            border-bottom: 1px solid #e5e5e5;
                        }

                        .amadex-gateway-status {
                            display: flex;
                            gap: 10px;
                            margin-bottom: 10px;
                            flex-wrap: wrap;
                        }

                        .amadex-status-badge {
                            display: inline-block;
                            padding: 4px 10px;
                            border-radius: 3px;
                            font-size: 12px;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                        }

                        .amadex-status-badge.amadex-status-configured {
                            background: #d1e7dd;
                            color: #0f5132;
                        }

                        .amadex-status-badge.amadex-status-not-configured {
                            background: #f8d7da;
                            color: #842029;
                        }

                        .amadex-status-badge.amadex-status-enabled {
                            background: #d1e7dd;
                            color: #0f5132;
                        }

                        .amadex-status-badge.amadex-status-default {
                            background: #cfe2ff;
                            color: #084298;
                        }

                        /* General section styling (always visible) */
                        .amadex-payment-general-section {
                            background: #fff;
                            border: 1px solid #ccd0d4;
                            border-radius: 4px;
                            padding: 20px;
                            margin-bottom: 20px;
                            box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                        }

                        .amadex-payment-general-section h2 {
                            margin-top: 0;
                            margin-bottom: 15px;
                            font-size: 18px;
                            font-weight: 600;
                        }

                        /* Form field spacing within accordions */
                        .amadex-accordion-content .form-table {
                            margin-top: 0;
                        }

                        .amadex-accordion-content .form-table th {
                            padding: 15px 10px 15px 0;
                            width: 200px;
                            vertical-align: top;
                        }

                        .amadex-accordion-content .form-table td {
                            padding: 15px 10px;
                        }

                        /* Symmetrical spacing */
                        .amadex-accordion-content .form-table tr:first-child th,
                        .amadex-accordion-content .form-table tr:first-child td {
                            padding-top: 0;
                        }

                        /* ON/OFF switch in accordion header (PayPal, Pay with Crypto, Crypto.com, MoonPay) */
                        .amadex-gateway-with-switch .amadex-accordion-header {
                            flex-wrap: wrap;
                            gap: 10px;
                        }

                        .amadex-header-switch {
                            display: inline-flex;
                            align-items: center;
                            gap: 8px;
                            cursor: pointer;
                            margin-left: auto;
                            margin-right: 8px;
                        }

                        .amadex-header-switch .amadex-toggle-input {
                            position: absolute;
                            opacity: 0;
                            width: 0;
                            height: 0;
                        }

                        .amadex-header-switch .amadex-toggle-slider {
                            display: inline-block;
                            width: 44px;
                            height: 24px;
                            background: #c3c4c7;
                            border-radius: 24px;
                            position: relative;
                            transition: background 0.2s;
                        }

                        .amadex-header-switch .amadex-toggle-slider::after {
                            content: "";
                            position: absolute;
                            width: 20px;
                            height: 20px;
                            left: 2px;
                            top: 2px;
                            background: #fff;
                            border-radius: 50%;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
                            transition: transform 0.2s;
                        }

                        .amadex-header-switch .amadex-toggle-input:checked+.amadex-toggle-slider {
                            background: #2271b1;
                        }

                        .amadex-header-switch .amadex-toggle-input:checked+.amadex-toggle-slider::after {
                            transform: translateX(20px);
                        }

                        .amadex-header-switch .amadex-toggle-label {
                            font-size: 12px;
                            font-weight: 600;
                            min-width: 28px;
                            color: #50575e;
                        }

                        .amadex-header-switch .amadex-toggle-input:checked~.amadex-toggle-label {
                            color: #0f5132;
                        }
                    </style>
                <?php endif; ?>
            </div>

            <?php if ($this->current_tab === 'api'): ?>
                <div class="amadex-card">
                    <div class="amadex-card-header">
                        <h2><?php _e('Test API Connection', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <p><?php _e('Click the button below to test your API connection.', 'amadex'); ?></p>
                        <button id="amadex-test-api" class="button button-secondary">
                            <?php _e('Test Connection', 'amadex'); ?>
                        </button>
                        <div id="amadex-test-result" class="amadex-test-result"></div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    <?php
            }

            /**
             * Render Add On Services Management Interface
             */
            private function render_addons_management()
            {
                // Get saved add-ons
                $addons = get_option('amadex_addon_services', array());

                // Handle form submissions
                if (isset($_POST['amadex_addon_action']) && check_admin_referer('amadex_addon_services', 'amadex_addon_nonce')) {
                    $action = sanitize_text_field($_POST['amadex_addon_action']);

                    if ($action === 'save') {
                        $addon_id = isset($_POST['addon_id']) ? sanitize_text_field($_POST['addon_id']) : '';
                        $addon_title = isset($_POST['addon_title']) ? sanitize_text_field($_POST['addon_title']) : '';
                        $addon_description = isset($_POST['addon_description']) ? sanitize_textarea_field($_POST['addon_description']) : '';
                        $addon_price = isset($_POST['addon_price']) ? floatval($_POST['addon_price']) : 0;
                        $addon_currency = isset($_POST['addon_currency']) ? sanitize_text_field($_POST['addon_currency']) : 'USD';
                        $addon_enabled = isset($_POST['addon_enabled']) ? 1 : 0;
                        $display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;

                        if (empty($addon_id)) {
                            // Generate new ID
                            $addon_id = 'addon_' . time() . '_' . rand(1000, 9999);
                        }

                        if (!empty($addon_title)) {
                            $addons[$addon_id] = array(
                                'id' => $addon_id,
                                'title' => $addon_title,
                                'description' => $addon_description,
                                'price' => $addon_price,
                                'currency' => $addon_currency,
                                'enabled' => $addon_enabled,
                                'display_order' => $display_order
                            );

                            update_option('amadex_addon_services', $addons);
                            echo '<div class="notice notice-success is-dismissible"><p>' . __('Add-on service saved successfully!', 'amadex') . '</p></div>';
                        } else {
                            echo '<div class="notice notice-error is-dismissible"><p>' . __('Please enter a title for the add-on service.', 'amadex') . '</p></div>';
                        }
                    } elseif ($action === 'delete') {
                        $addon_id = isset($_POST['addon_id']) ? sanitize_text_field($_POST['addon_id']) : '';
                        if (!empty($addon_id) && isset($addons[$addon_id])) {
                            unset($addons[$addon_id]);
                            update_option('amadex_addon_services', $addons);
                            echo '<div class="notice notice-success is-dismissible"><p>' . __('Add-on service deleted successfully!', 'amadex') . '</p></div>';
                        }
                    }
                }

                // Get add-on to edit (if any)
                $editing_addon = null;
                if (isset($_GET['edit_addon'])) {
                    $edit_id = sanitize_text_field($_GET['edit_addon']);
                    if (isset($addons[$edit_id])) {
                        $editing_addon = $addons[$edit_id];
                    }
                }

                // Get currencies list
                $currencies = array(
                    'USD' => 'USD - US Dollar',
                    'EUR' => 'EUR - Euro',
                    'GBP' => 'GBP - British Pound',
                    'INR' => 'INR - Indian Rupee',
                    'CAD' => 'CAD - Canadian Dollar',
                    'AUD' => 'AUD - Australian Dollar',
                    'JPY' => 'JPY - Japanese Yen',
                    'CNY' => 'CNY - Chinese Yuan',
                    'SGD' => 'SGD - Singapore Dollar',
                    'AED' => 'AED - UAE Dirham'
                );

    ?>
        <div class="amadex-addons-management">
            <!-- Add/Edit Form -->
            <div class="amadex-addon-form-wrapper">
                <h3><?php echo $editing_addon ? __('Edit Add-On Service', 'amadex') : __('Add New Add-On Service', 'amadex'); ?></h3>
                <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=amadex-settings&tab=addons')); ?>" class="amadex-addon-form">
                    <?php wp_nonce_field('amadex_addon_services', 'amadex_addon_nonce'); ?>
                    <input type="hidden" name="amadex_addon_action" value="save">
                    <?php if ($editing_addon): ?>
                        <input type="hidden" name="addon_id" value="<?php echo esc_attr($editing_addon['id']); ?>">
                    <?php endif; ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="addon_title"><?php _e('Title', 'amadex'); ?> <span class="required">*</span></label></th>
                            <td>
                                <input type="text" id="addon_title" name="addon_title" class="regular-text" value="<?php echo $editing_addon ? esc_attr($editing_addon['title']) : ''; ?>" required>
                                <p class="description"><?php _e('The name of the add-on service (e.g., Premium Services, Travel Insurance).', 'amadex'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="addon_description"><?php _e('Description', 'amadex'); ?></label></th>
                            <td>
                                <textarea id="addon_description" name="addon_description" rows="3" class="large-text"><?php echo $editing_addon ? esc_textarea($editing_addon['description']) : ''; ?></textarea>
                                <p class="description"><?php _e('A brief description of the add-on service. This will be displayed to customers.', 'amadex'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="addon_price"><?php _e('Price', 'amadex'); ?> <span class="required">*</span></label></th>
                            <td>
                                <input type="number" id="addon_price" name="addon_price" step="0.01" min="0" class="small-text" value="<?php echo $editing_addon ? esc_attr($editing_addon['price']) : '0.00'; ?>" required>
                                <select id="addon_currency" name="addon_currency" style="margin-left: 10px;">
                                    <?php foreach ($currencies as $code => $label): ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($editing_addon && isset($editing_addon['currency']) ? $editing_addon['currency'] : 'USD', $code); ?>>
                                            <?php echo esc_html($code); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('The price of the add-on service. Prices are NOT affected by price management markups.', 'amadex'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="display_order"><?php _e('Display Order', 'amadex'); ?></label></th>
                            <td>
                                <input type="number" id="display_order" name="display_order" min="0" class="small-text" value="<?php echo $editing_addon ? esc_attr($editing_addon['display_order']) : '0'; ?>">
                                <p class="description"><?php _e('Lower numbers appear first. Default is 0.', 'amadex'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="addon_enabled"><?php _e('Status', 'amadex'); ?></label></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="addon_enabled" name="addon_enabled" value="1" <?php checked($editing_addon && isset($editing_addon['enabled']) ? $editing_addon['enabled'] : 1, 1); ?>>
                                    <?php _e('Enabled', 'amadex'); ?>
                                </label>
                                <p class="description"><?php _e('Only enabled add-ons will be displayed to customers.', 'amadex'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php echo $editing_addon ? __('Update Add-On Service', 'amadex') : __('Add Add-On Service', 'amadex'); ?></button>
                        <?php if ($editing_addon): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=amadex-settings&tab=addons')); ?>" class="button"><?php _e('Cancel', 'amadex'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <!-- Existing Add-Ons List -->
            <div class="amadex-addons-list-wrapper">
                <h3><?php _e('Existing Add-On Services', 'amadex'); ?></h3>
                <?php if (empty($addons)): ?>
                    <p><?php _e('No add-on services have been created yet.', 'amadex'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 5%;"><?php _e('Order', 'amadex'); ?></th>
                                <th style="width: 25%;"><?php _e('Title', 'amadex'); ?></th>
                                <th style="width: 40%;"><?php _e('Description', 'amadex'); ?></th>
                                <th style="width: 15%;"><?php _e('Price', 'amadex'); ?></th>
                                <th style="width: 10%;"><?php _e('Status', 'amadex'); ?></th>
                                <th style="width: 5%;"><?php _e('Actions', 'amadex'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Sort by display_order
                            uasort($addons, function ($a, $b) {
                                $order_a = isset($a['display_order']) ? intval($a['display_order']) : 0;
                                $order_b = isset($b['display_order']) ? intval($b['display_order']) : 0;
                                return $order_a <=> $order_b;
                            });

                            foreach ($addons as $addon): ?>
                                <tr>
                                    <td><?php echo esc_html($addon['display_order'] ?? 0); ?></td>
                                    <td><strong><?php echo esc_html($addon['title']); ?></strong></td>
                                    <td><?php echo esc_html(wp_trim_words($addon['description'] ?? '', 20)); ?></td>
                                    <td><?php echo esc_html($addon['currency'] ?? 'USD'); ?> <?php echo number_format($addon['price'] ?? 0, 2); ?></td>
                                    <td>
                                        <?php if (isset($addon['enabled']) && $addon['enabled']): ?>
                                            <span style="color: green;"><?php _e('Enabled', 'amadex'); ?></span>
                                        <?php else: ?>
                                            <span style="color: red;"><?php _e('Disabled', 'amadex'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=amadex-settings&tab=addons&edit_addon=' . urlencode($addon['id']))); ?>" class="button button-small"><?php _e('Edit', 'amadex'); ?></a>
                                        <form method="post" action="" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this add-on service?', 'amadex'); ?>');">
                                            <?php wp_nonce_field('amadex_addon_services', 'amadex_addon_nonce'); ?>
                                            <input type="hidden" name="amadex_addon_action" value="delete">
                                            <input type="hidden" name="addon_id" value="<?php echo esc_attr($addon['id']); ?>">
                                            <button type="submit" class="button button-small" style="color: #b32d2e;"><?php _e('Delete', 'amadex'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php
            }

            /**
             * Render Promotional Containers Management Interface
             */
            private function render_promotional_containers_management()
            {
                // Get saved promotional containers
                $containers = get_option('amadex_promotional_containers', array());

                // Handle form submissions
                if (isset($_POST['amadex_promo_container_action']) && check_admin_referer('amadex_promo_containers', 'amadex_promo_container_nonce')) {
                    $action = sanitize_text_field($_POST['amadex_promo_container_action']);

                    if ($action === 'save') {
                        $container_id = isset($_POST['container_id']) ? sanitize_text_field($_POST['container_id']) : '';
                        $container_type = isset($_POST['container_type']) ? sanitize_text_field($_POST['container_type']) : 'price_alert';
                        $container_template_id = isset($_POST['container_template_id']) && !empty($_POST['container_template_id']) ? sanitize_text_field($_POST['container_template_id']) : null;
                        $container_type_id = isset($_POST['container_type_id']) && !empty($_POST['container_type_id']) ? sanitize_text_field($_POST['container_type_id']) : null;
                        $container_title = isset($_POST['container_title']) ? sanitize_text_field($_POST['container_title']) : '';
                        $container_description = isset($_POST['container_description']) ? sanitize_textarea_field($_POST['container_description']) : '';
                        $container_button_text = isset($_POST['container_button_text']) ? sanitize_text_field($_POST['container_button_text']) : '';
                        $container_enabled = isset($_POST['container_enabled']) ? 1 : 0;
                        $insertion_frequency = isset($_POST['insertion_frequency']) ? floatval($_POST['insertion_frequency']) : 0.25;
                        $insertion_interval = isset($_POST['insertion_interval']) ? intval($_POST['insertion_interval']) : 4;
                        $min_flights_viewed = isset($_POST['min_flights_viewed']) ? intval($_POST['min_flights_viewed']) : 2;
                        $max_appearances = isset($_POST['max_appearances']) ? intval($_POST['max_appearances']) : 0; // 0 = unlimited

                        // NEW: Custom width/height with units
                        $container_width_value = isset($_POST['container_width_value']) ? floatval($_POST['container_width_value']) : 100;
                        $container_width_unit = isset($_POST['container_width_unit']) ? sanitize_text_field($_POST['container_width_unit']) : '%';
                        $container_height_value = isset($_POST['container_height_value']) ? sanitize_text_field($_POST['container_height_value']) : 'auto';
                        $container_height_unit = isset($_POST['container_height_unit']) ? sanitize_text_field($_POST['container_height_unit']) : 'auto';

                        // NEW: Device-specific dimensions (Phase 1) with Min/Max constraints
                        $device_dimensions = array();
                        if (isset($_POST['dimensions']) && is_array($_POST['dimensions'])) {
                            foreach ($_POST['dimensions'] as $device => $dims) {
                                $device = sanitize_text_field($device);
                                if (in_array($device, array('desktop', 'tablet', 'mobile'))) {
                                    $device_dimensions[$device] = array(
                                        'width_value' => isset($dims['width_value']) && $dims['width_value'] !== '' ? floatval($dims['width_value']) : '',
                                        'width_unit' => isset($dims['width_unit']) ? sanitize_text_field($dims['width_unit']) : '%',
                                        'height_value' => isset($dims['height_value']) && $dims['height_value'] !== '' ? floatval($dims['height_value']) : '',
                                        'height_unit' => isset($dims['height_unit']) ? sanitize_text_field($dims['height_unit']) : 'auto',
                                        // Device-specific Min/Max constraints
                                        'min_width_value' => isset($dims['min_width_value']) && $dims['min_width_value'] !== '' ? floatval($dims['min_width_value']) : '',
                                        'min_width_unit' => isset($dims['min_width_unit']) ? sanitize_text_field($dims['min_width_unit']) : 'px',
                                        'max_width_value' => isset($dims['max_width_value']) && $dims['max_width_value'] !== '' ? floatval($dims['max_width_value']) : '',
                                        'max_width_unit' => isset($dims['max_width_unit']) ? sanitize_text_field($dims['max_width_unit']) : 'px',
                                        'min_height_value' => isset($dims['min_height_value']) && $dims['min_height_value'] !== '' ? floatval($dims['min_height_value']) : '',
                                        'min_height_unit' => isset($dims['min_height_unit']) ? sanitize_text_field($dims['min_height_unit']) : 'px',
                                        'max_height_value' => isset($dims['max_height_value']) && $dims['max_height_value'] !== '' ? floatval($dims['max_height_value']) : '',
                                        'max_height_unit' => isset($dims['max_height_unit']) ? sanitize_text_field($dims['max_height_unit']) : 'px'
                                    );

                                    // Use desktop dimensions as default for legacy fields if not set
                                    if ($device === 'desktop' && empty($container_width_value)) {
                                        $container_width_value = $device_dimensions[$device]['width_value'] ?: 100;
                                        $container_width_unit = $device_dimensions[$device]['width_unit'] ?: '%';
                                    }
                                    if ($device === 'desktop' && ($container_height_value === 'auto' || empty($container_height_value))) {
                                        $container_height_value = $device_dimensions[$device]['height_value'] ?: 'auto';
                                        $container_height_unit = $device_dimensions[$device]['height_unit'] ?: 'auto';
                                    }

                                    // Use desktop min/max as legacy fallback for backward compatibility
                                    if ($device === 'desktop') {
                                        $container_min_width_value = $device_dimensions[$device]['min_width_value'] ?: '';
                                        $container_min_width_unit = $device_dimensions[$device]['min_width_unit'] ?: 'px';
                                        $container_max_width_value = $device_dimensions[$device]['max_width_value'] ?: '';
                                        $container_max_width_unit = $device_dimensions[$device]['max_width_unit'] ?: 'px';
                                        $container_min_height_value = $device_dimensions[$device]['min_height_value'] ?: '';
                                        $container_min_height_unit = $device_dimensions[$device]['min_height_unit'] ?: 'px';
                                        $container_max_height_value = $device_dimensions[$device]['max_height_value'] ?: '';
                                        $container_max_height_unit = $device_dimensions[$device]['max_height_unit'] ?: 'px';
                                    }
                                }
                            }
                        }

                        // Legacy support: keep old container_width for backward compatibility
                        $container_width = isset($_POST['container_width']) ? sanitize_text_field($_POST['container_width']) : null;
                        if (!$container_width) {
                            // Convert new system to legacy if needed (fallback)
                            if ($container_width_value == 100 && $container_width_unit == '%') $container_width = 'full';
                            elseif ($container_width_value >= 60 && $container_width_value <= 70 && $container_width_unit == '%') $container_width = 'compact';
                            elseif ($container_width_value >= 40 && $container_width_value <= 50 && $container_width_unit == '%') $container_width = 'mini';
                            else $container_width = 'full'; // Default fallback
                        }

                        // Legacy Min/Max Dimensions (for backward compatibility - use desktop values)
                        if (empty($container_min_width_value) && isset($_POST['container_min_width_value']) && $_POST['container_min_width_value'] !== '') {
                            $container_min_width_value = floatval($_POST['container_min_width_value']);
                            $container_min_width_unit = isset($_POST['container_min_width_unit']) ? sanitize_text_field($_POST['container_min_width_unit']) : 'px';
                        } elseif (empty($container_min_width_value)) {
                            $container_min_width_value = '';
                            $container_min_width_unit = 'px';
                        }
                        if (empty($container_max_width_value) && isset($_POST['container_max_width_value']) && $_POST['container_max_width_value'] !== '') {
                            $container_max_width_value = floatval($_POST['container_max_width_value']);
                            $container_max_width_unit = isset($_POST['container_max_width_unit']) ? sanitize_text_field($_POST['container_max_width_unit']) : 'px';
                        } elseif (empty($container_max_width_value)) {
                            $container_max_width_value = '';
                            $container_max_width_unit = 'px';
                        }
                        if (empty($container_min_height_value) && isset($_POST['container_min_height_value']) && $_POST['container_min_height_value'] !== '') {
                            $container_min_height_value = floatval($_POST['container_min_height_value']);
                            $container_min_height_unit = isset($_POST['container_min_height_unit']) ? sanitize_text_field($_POST['container_min_height_unit']) : 'px';
                        } elseif (empty($container_min_height_value)) {
                            $container_min_height_value = '';
                            $container_min_height_unit = 'px';
                        }
                        if (empty($container_max_height_value) && isset($_POST['container_max_height_value']) && $_POST['container_max_height_value'] !== '') {
                            $container_max_height_value = floatval($_POST['container_max_height_value']);
                            $container_max_height_unit = isset($_POST['container_max_height_unit']) ? sanitize_text_field($_POST['container_max_height_unit']) : 'px';
                        } elseif (empty($container_max_height_value)) {
                            $container_max_height_value = '';
                            $container_max_height_unit = 'px';
                        }

                        // NEW: Padding Controls
                        $container_padding_mode = isset($_POST['container_padding_mode']) ? sanitize_text_field($_POST['container_padding_mode']) : 'uniform';
                        $container_padding_all = isset($_POST['container_padding_all']) && $_POST['container_padding_all'] !== '' ? floatval($_POST['container_padding_all']) : '';
                        $container_padding_all_unit = isset($_POST['container_padding_all_unit']) ? sanitize_text_field($_POST['container_padding_all_unit']) : 'px';
                        $container_padding_x = isset($_POST['container_padding_x']) && $_POST['container_padding_x'] !== '' ? floatval($_POST['container_padding_x']) : '';
                        $container_padding_x_unit = isset($_POST['container_padding_x_unit']) ? sanitize_text_field($_POST['container_padding_x_unit']) : 'px';
                        $container_padding_y = isset($_POST['container_padding_y']) && $_POST['container_padding_y'] !== '' ? floatval($_POST['container_padding_y']) : '';
                        $container_padding_y_unit = isset($_POST['container_padding_y_unit']) ? sanitize_text_field($_POST['container_padding_y_unit']) : 'px';
                        $container_padding_top = isset($_POST['container_padding_top']) && $_POST['container_padding_top'] !== '' ? floatval($_POST['container_padding_top']) : '';
                        $container_padding_right = isset($_POST['container_padding_right']) && $_POST['container_padding_right'] !== '' ? floatval($_POST['container_padding_right']) : '';
                        $container_padding_bottom = isset($_POST['container_padding_bottom']) && $_POST['container_padding_bottom'] !== '' ? floatval($_POST['container_padding_bottom']) : '';
                        $container_padding_left = isset($_POST['container_padding_left']) && $_POST['container_padding_left'] !== '' ? floatval($_POST['container_padding_left']) : '';

                        // NEW: Gap (Grid Spacing)
                        $container_gap_column = isset($_POST['container_gap_column']) && $_POST['container_gap_column'] !== '' ? floatval($_POST['container_gap_column']) : '';
                        $container_gap_column_unit = isset($_POST['container_gap_column_unit']) ? sanitize_text_field($_POST['container_gap_column_unit']) : 'px';
                        $container_gap_row = isset($_POST['container_gap_row']) && $_POST['container_gap_row'] !== '' ? floatval($_POST['container_gap_row']) : '';
                        $container_gap_row_unit = isset($_POST['container_gap_row_unit']) ? sanitize_text_field($_POST['container_gap_row_unit']) : 'px';

                        // NEW: Border Radius
                        $container_border_radius = isset($_POST['container_border_radius']) && $_POST['container_border_radius'] !== '' ? floatval($_POST['container_border_radius']) : '';
                        $container_border_radius_unit = isset($_POST['container_border_radius_unit']) ? sanitize_text_field($_POST['container_border_radius_unit']) : 'px';

                        // NEW: Compactness & Typography
                        $container_compactness = isset($_POST['container_compactness']) ? intval($_POST['container_compactness']) : 50; // 0-100
                        $container_typography_scale = isset($_POST['container_typography_scale']) ? floatval($_POST['container_typography_scale']) : 1.0; // 0.5-2.0

                        // NEW: Animations
                        $container_animations = isset($_POST['container_animations']) && is_array($_POST['container_animations']) ? array_map('sanitize_text_field', $_POST['container_animations']) : array();
                        $animation_duration = isset($_POST['animation_duration']) ? sanitize_text_field($_POST['animation_duration']) : '2s';
                        $animation_delay = isset($_POST['animation_delay']) ? sanitize_text_field($_POST['animation_delay']) : '0s';
                        $animation_mobile_disabled = isset($_POST['animation_mobile_disabled']) ? 1 : 0;
                        $animation_intensity = isset($_POST['animation_intensity']) ? intval($_POST['animation_intensity']) : 50; // 0-100

                        // NEW: Container Colors
                        $container_color_type = isset($_POST['container_color_type']) ? sanitize_text_field($_POST['container_color_type']) : 'default';
                        $color_picker_type = isset($_POST['color_picker_type']) ? sanitize_text_field($_POST['color_picker_type']) : 'html5';
                        $container_color_primary = isset($_POST['container_color_primary']) ? sanitize_hex_color($_POST['container_color_primary']) : '#0e7d3f';
                        $container_color_secondary = isset($_POST['container_color_secondary']) ? sanitize_hex_color($_POST['container_color_secondary']) : '#22af5c';
                        $container_color_tertiary = isset($_POST['container_color_tertiary']) ? sanitize_hex_color($_POST['container_color_tertiary']) : '#f97316';
                        $container_color_opacity = isset($_POST['container_color_opacity']) ? intval($_POST['container_color_opacity']) : 100; // 0-100
                        $container_gradient_direction = isset($_POST['container_gradient_direction']) ? sanitize_text_field($_POST['container_gradient_direction']) : 'to right';
                        $container_gradient_angle = isset($_POST['container_gradient_angle']) ? intval($_POST['container_gradient_angle']) : 135; // 0-360
                        $gradient_stops = array();
                        if (isset($_POST['gradient_stop_1']) && isset($_POST['gradient_stop_2']) && isset($_POST['gradient_stop_3'])) {
                            $gradient_stops = array(
                                intval($_POST['gradient_stop_1']),
                                intval($_POST['gradient_stop_2']),
                                intval($_POST['gradient_stop_3'])
                            );
                        }

                        // NEW: Text Color (separate heading and body)
                        $text_color_auto = isset($_POST['text_color_auto']) ? 1 : 0;
                        $text_colors_linked = isset($_POST['text_colors_linked']) ? 1 : 0;
                        $container_heading_color = isset($_POST['container_heading_color']) ? sanitize_hex_color($_POST['container_heading_color']) : '';
                        $container_body_color = isset($_POST['container_body_color']) ? sanitize_hex_color($_POST['container_body_color']) : '';
                        // Legacy support: keep old container_text_color if exists
                        $container_text_color = isset($_POST['container_text_color']) ? sanitize_hex_color($_POST['container_text_color']) : '';

                        $container_icon = isset($_POST['container_icon']) ? sanitize_text_field($_POST['container_icon']) : '';
                        $container_image_url = isset($_POST['container_image_url']) ? esc_url_raw($_POST['container_image_url']) : '';
                        $container_link_url = isset($_POST['container_link_url']) ? esc_url_raw($_POST['container_link_url']) : '';
                        $display_order = isset($_POST['display_order']) ? intval($_POST['display_order']) : 0;

                        // Additional fields based on container type
                        $additional_data = array();
                        if ($container_type === 'price_alert') {
                            $additional_data['email_placeholder'] = isset($_POST['email_placeholder']) ? sanitize_text_field($_POST['email_placeholder']) : 'Enter your email';
                        } elseif ($container_type === 'callback') {
                            $additional_data['phone_placeholder'] = isset($_POST['phone_placeholder']) ? sanitize_text_field($_POST['phone_placeholder']) : 'Enter your phone number';
                            $additional_data['callback_message'] = isset($_POST['callback_message']) ? sanitize_textarea_field($_POST['callback_message']) : '';
                        } elseif ($container_type === 'airline_ad') {
                            $additional_data['airline_logo_url'] = isset($_POST['airline_logo_url']) ? esc_url_raw($_POST['airline_logo_url']) : '';
                            $additional_data['offer_text'] = isset($_POST['offer_text']) ? sanitize_text_field($_POST['offer_text']) : '';
                        } elseif ($container_type === 'travelaygent_spotlight') {
                            // Phase 2: TravelayGent Spotlight
                            $additional_data['agent_name'] = isset($_POST['agent_name']) ? sanitize_text_field($_POST['agent_name']) : '';
                            $additional_data['agent_photo_url'] = isset($_POST['agent_photo_url']) ? esc_url_raw($_POST['agent_photo_url']) : '';
                            $additional_data['agent_specialties'] = isset($_POST['agent_specialties']) ? sanitize_text_field($_POST['agent_specialties']) : '';
                            $additional_data['agent_rating'] = isset($_POST['agent_rating']) ? floatval($_POST['agent_rating']) : 5.0;
                            $additional_data['agent_link_url'] = isset($_POST['agent_link_url']) ? esc_url_raw($_POST['agent_link_url']) : '';
                        } elseif ($container_type === 'trust_badge') {
                            // Phase 2: Trust Badge Banner
                            $additional_data['trust_badge_type'] = isset($_POST['trust_badge_type']) ? sanitize_text_field($_POST['trust_badge_type']) : 'rating';
                            $additional_data['trust_rating'] = isset($_POST['trust_rating']) ? floatval($_POST['trust_rating']) : 4.5;
                            $additional_data['trust_review_count'] = isset($_POST['trust_review_count']) ? intval($_POST['trust_review_count']) : 0;
                            $additional_data['trust_certification'] = isset($_POST['trust_certification']) ? sanitize_text_field($_POST['trust_certification']) : '';
                            $additional_data['trust_years'] = isset($_POST['trust_years']) ? intval($_POST['trust_years']) : 0;
                        } elseif ($container_type === 'urgency_scarcity') {
                            // Phase 2: Urgency/Scarcity Alert
                            $additional_data['urgency_type'] = isset($_POST['urgency_type']) ? sanitize_text_field($_POST['urgency_type']) : 'seats';
                            $additional_data['urgency_count'] = isset($_POST['urgency_count']) ? intval($_POST['urgency_count']) : 0;
                            $additional_data['urgency_countdown'] = isset($_POST['urgency_countdown']) ? sanitize_text_field($_POST['urgency_countdown']) : '';
                            $additional_data['urgency_price_change'] = isset($_POST['urgency_price_change']) ? sanitize_text_field($_POST['urgency_price_change']) : '';
                        } elseif ($container_type === 'deal_highlight') {
                            // Phase 2: Deal Highlight Card
                            $additional_data['deal_amount'] = isset($_POST['deal_amount']) ? sanitize_text_field($_POST['deal_amount']) : '';
                            $additional_data['deal_savings'] = isset($_POST['deal_savings']) ? sanitize_text_field($_POST['deal_savings']) : '';
                            $additional_data['deal_valid_dates'] = isset($_POST['deal_valid_dates']) ? sanitize_text_field($_POST['deal_valid_dates']) : '';
                            $additional_data['deal_route'] = isset($_POST['deal_route']) ? sanitize_text_field($_POST['deal_route']) : '';
                        } elseif ($container_type === 'comparison_table') {
                            // Phase 2: Comparison Table Banner
                            $additional_data['comparison_items'] = isset($_POST['comparison_items']) && is_array($_POST['comparison_items']) ? array_map('sanitize_text_field', $_POST['comparison_items']) : array();
                            $additional_data['comparison_highlight'] = isset($_POST['comparison_highlight']) ? sanitize_text_field($_POST['comparison_highlight']) : '';
                        } elseif ($container_type === 'social_proof') {
                            // Phase 2: Social Proof Banner
                            $additional_data['social_activity_text'] = isset($_POST['social_activity_text']) ? sanitize_text_field($_POST['social_activity_text']) : '';
                            $additional_data['social_booking_count'] = isset($_POST['social_booking_count']) ? intval($_POST['social_booking_count']) : 0;
                            $additional_data['social_time_period'] = isset($_POST['social_time_period']) ? sanitize_text_field($_POST['social_time_period']) : 'today';
                        }

                        if (empty($container_id)) {
                            // Generate new ID
                            $container_id = 'promo_' . time() . '_' . rand(1000, 9999);
                        }

                        if (!empty($container_title) || $container_type === 'ad') {
                            $containers[$container_id] = array(
                                'id' => $container_id,
                                'type' => $container_type,
                                'template_id' => $container_template_id,
                                'container_type_id' => $container_type_id,
                                'title' => $container_title,
                                'description' => $container_description,
                                'button_text' => $container_button_text,
                                'enabled' => $container_enabled,
                                'insertion_frequency' => $insertion_frequency,
                                'insertion_interval' => $insertion_interval,
                                'min_flights_viewed' => $min_flights_viewed,
                                'max_appearances' => $max_appearances,
                                'container_width' => $container_width, // Legacy support
                                'container_width_value' => $container_width_value,
                                'container_width_unit' => $container_width_unit,
                                'container_height_value' => $container_height_value,
                                'container_height_unit' => $container_height_unit,
                                'dimensions' => $device_dimensions, // Phase 1: Device-specific dimensions
                                'container_min_width_value' => $container_min_width_value,
                                'container_min_width_unit' => $container_min_width_unit,
                                'container_max_width_value' => $container_max_width_value,
                                'container_max_width_unit' => $container_max_width_unit,
                                'container_min_height_value' => $container_min_height_value,
                                'container_min_height_unit' => $container_min_height_unit,
                                'container_max_height_value' => $container_max_height_value,
                                'container_max_height_unit' => $container_max_height_unit,
                                'container_padding_mode' => $container_padding_mode,
                                'container_padding_all' => $container_padding_all,
                                'container_padding_all_unit' => $container_padding_all_unit,
                                'container_padding_x' => $container_padding_x,
                                'container_padding_x_unit' => $container_padding_x_unit,
                                'container_padding_y' => $container_padding_y,
                                'container_padding_y_unit' => $container_padding_y_unit,
                                'container_padding_top' => $container_padding_top,
                                'container_padding_right' => $container_padding_right,
                                'container_padding_bottom' => $container_padding_bottom,
                                'container_padding_left' => $container_padding_left,
                                'container_gap_column' => $container_gap_column,
                                'container_gap_column_unit' => $container_gap_column_unit,
                                'container_gap_row' => $container_gap_row,
                                'container_gap_row_unit' => $container_gap_row_unit,
                                'container_border_radius' => $container_border_radius,
                                'container_border_radius_unit' => $container_border_radius_unit,
                                'container_compactness' => $container_compactness,
                                'container_typography_scale' => $container_typography_scale,
                                'animations' => $container_animations,
                                'animation_duration' => $animation_duration,
                                'animation_delay' => $animation_delay,
                                'animation_mobile_disabled' => $animation_mobile_disabled,
                                'animation_intensity' => $animation_intensity,
                                'container_color_type' => $container_color_type,
                                'color_picker_type' => $color_picker_type,
                                'container_color_primary' => $container_color_primary,
                                'container_color_secondary' => $container_color_secondary,
                                'container_color_tertiary' => $container_color_tertiary,
                                'container_color_opacity' => $container_color_opacity,
                                'container_gradient_direction' => $container_gradient_direction,
                                'container_gradient_angle' => $container_gradient_angle,
                                'gradient_stops' => $gradient_stops,
                                'text_color_auto' => $text_color_auto,
                                'text_colors_linked' => $text_colors_linked,
                                'container_heading_color' => $container_heading_color,
                                'container_body_color' => $container_body_color,
                                'container_text_color' => $container_text_color, // Legacy support
                                'icon' => $container_icon,
                                'image_url' => $container_image_url,
                                'link_url' => $container_link_url,
                                'display_order' => $display_order,
                                'additional_data' => $additional_data,
                                // Phase 3: Template-specific data
                                'template_data' => isset($_POST['template_data']) && is_array($_POST['template_data']) ? array_map(function ($item) {
                                    if (is_array($item)) {
                                        return array_map('sanitize_text_field', $item);
                                    }
                                    return sanitize_text_field($item);
                                }, $_POST['template_data']) : array(),
                                'created_at' => isset($containers[$container_id]['created_at']) ? $containers[$container_id]['created_at'] : current_time('mysql'),
                                'updated_at' => current_time('mysql')
                            );

                            update_option('amadex_promotional_containers', $containers);
                            echo '<div class="notice notice-success is-dismissible"><p>' . __('Promotional container saved successfully!', 'amadex') . '</p></div>';
                        } else {
                            echo '<div class="notice notice-error is-dismissible"><p>' . __('Please enter a title for the promotional container.', 'amadex') . '</p></div>';
                        }
                    } elseif ($action === 'delete') {
                        $container_id = isset($_POST['container_id']) ? sanitize_text_field($_POST['container_id']) : '';
                        if (!empty($container_id) && isset($containers[$container_id])) {
                            unset($containers[$container_id]);
                            update_option('amadex_promotional_containers', $containers);
                            echo '<div class="notice notice-success is-dismissible"><p>' . __('Promotional container deleted successfully!', 'amadex') . '</p></div>';
                        }
                    }
                }

                // Get container to edit (if any)
                $editing_container = null;
                if (isset($_GET['edit_container'])) {
                    $edit_id = sanitize_text_field($_GET['edit_container']);
                    if (isset($containers[$edit_id])) {
                        $editing_container = $containers[$edit_id];

                        // Normalize container structure for backward compatibility
                        // Ensure 'id' field exists (old containers might not have it)
                        if (!isset($editing_container['id'])) {
                            $editing_container['id'] = $edit_id;
                        }

                        // Initialize 'dimensions' structure if missing (for old containers)
                        if (!isset($editing_container['dimensions']) || !is_array($editing_container['dimensions'])) {
                            $editing_container['dimensions'] = array();
                        }

                        // Initialize device-specific dimensions if missing
                        $devices = array('desktop', 'tablet', 'mobile');
                        foreach ($devices as $device) {
                            if (!isset($editing_container['dimensions'][$device]) || !is_array($editing_container['dimensions'][$device])) {
                                $editing_container['dimensions'][$device] = array();

                                // Migrate legacy dimensions to device-specific if available
                                if (isset($editing_container['container_width_value'])) {
                                    $editing_container['dimensions'][$device]['width_value'] = $editing_container['container_width_value'];
                                }
                                if (isset($editing_container['container_width_unit'])) {
                                    $editing_container['dimensions'][$device]['width_unit'] = $editing_container['container_width_unit'];
                                }
                                if (isset($editing_container['container_height_value']) && $editing_container['container_height_value'] !== 'auto') {
                                    $editing_container['dimensions'][$device]['height_value'] = $editing_container['container_height_value'];
                                }
                                if (isset($editing_container['container_height_unit'])) {
                                    $editing_container['dimensions'][$device]['height_unit'] = $editing_container['container_height_unit'];
                                }
                            }
                        }

                        // Initialize other required fields with defaults if missing
                        if (!isset($editing_container['type'])) {
                            $editing_container['type'] = 'price_alert';
                        }
                        if (!isset($editing_container['enabled'])) {
                            $editing_container['enabled'] = 1;
                        }
                        if (!isset($editing_container['template_data']) || !is_array($editing_container['template_data'])) {
                            $editing_container['template_data'] = array();
                        }
                    } else {
                        // Container not found - show error and redirect
                        echo '<div class="notice notice-error is-dismissible"><p>' . __('Promotional container not found.', 'amadex') . '</p></div>';
                        // Remove edit_container from URL to prevent infinite loop
                        $redirect_url = remove_query_arg('edit_container');
                        echo '<script>setTimeout(function(){ window.location.href = "' . esc_js($redirect_url) . '"; }, 2000);</script>';
                    }
                }

    ?>
        <style>
            /* Preview Section Layout */
            .amadex-promotional-containers-management {
                position: relative;
                width: 100%;
                max-width: 100%;
            }

            /* Promo Topics Grid - 4 cards in one line on desktop */
            .amadex-topics-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                width: 100%;
            }

            /* Responsive breakpoints for topics grid */
            @media screen and (max-width: 1400px) {
                .amadex-topics-grid {
                    grid-template-columns: repeat(3, 1fr);
                }
            }

            @media screen and (max-width: 1000px) {
                .amadex-topics-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media screen and (max-width: 600px) {
                .amadex-topics-grid {
                    grid-template-columns: 1fr;
                }
            }

            .amadex-preview-section {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 20px;
                margin: 30px 0;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .amadex-preview-section h3 {
                margin: 0 0 15px 0;
                font-size: 16px;
                font-weight: 600;
            }

            .amadex-device-selector {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
                flex-wrap: wrap;
                align-items: center;
            }

            .amadex-device-button {
                padding: 8px 16px;
                border: 2px solid #ddd;
                background: #fff;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
                font-weight: 500;
                transition: all 0.2s ease;
            }

            .amadex-device-button:hover {
                border-color: #0e7d3f;
                background: #f0f9f4;
            }

            .amadex-device-button.active {
                background: #0e7d3f;
                color: #fff;
                border-color: #0e7d3f;
            }

            .amadex-custom-device-inputs {
                display: flex;
                gap: 10px;
                align-items: center;
                margin-left: auto;
            }

            .amadex-custom-device-inputs input {
                width: 80px;
                padding: 6px 8px;
                border: 1px solid #ddd;
                border-radius: 3px;
                font-size: 13px;
            }

            .amadex-preview-iframe-container {
                border: 2px solid #ddd;
                border-radius: 4px;
                background: #f9f9f9;
                overflow: hidden;
                position: relative;
                min-height: 50px;
                max-height: 1200px;
                height: auto;
                transition: height 0.3s ease;
            }

            .amadex-preview-iframe-container iframe {
                width: 100%;
                height: auto;
                min-height: 50px;
                border: none;
                display: block;
            }

            .amadex-preview-loading {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                color: #666;
                font-size: 14px;
            }

            .amadex-settings-form-wrapper {
                margin-top: 20px;
            }

            /* Drawer Styles */
            .amadex-section-top {
                margin-bottom: 30px;
            }

            .amadex-drawer-header {
                border-bottom: 1px solid #ddd;
            }

            .amadex-drawer-header td {
                padding: 0 !important;
            }

            .amadex-drawer-toggle {
                width: 100%;
                text-align: left;
                background: #f7f7f7;
                border: none;
                border-bottom: 1px solid #ddd;
                padding: 15px 20px;
                cursor: pointer;
                font-size: 14px;
                color: #23282d;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                margin: 0;
            }

            .amadex-drawer-toggle:hover {
                background: #f0f0f1;
                color: #2271b1;
            }

            .amadex-drawer-toggle:focus {
                outline: none;
                box-shadow: inset 0 0 0 1px #2271b1;
            }

            .amadex-drawer-toggle .dashicons-arrow-right-alt2,
            .amadex-drawer-toggle .dashicons-arrow-down-alt2 {
                font-size: 16px;
                width: 16px;
                height: 16px;
                transition: transform 0.2s ease;
                margin-right: 5px;
            }

            .amadex-drawer-toggle.amadex-drawer-open .dashicons-arrow-right-alt2 {
                transform: rotate(90deg);
            }

            .amadex-drawer-toggle.amadex-drawer-open .dashicons-arrow-down-alt2 {
                transform: rotate(0deg);
            }

            /* Container Size Preset Buttons (Phase 1) */
            .preset-size-btn {
                transition: all 0.2s ease;
                border: 2px solid #ddd !important;
                background: #fff !important;
                color: #333 !important;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                cursor: pointer;
            }

            .preset-size-btn:hover {
                border-color: #0073aa !important;
                background: #f0f9ff !important;
                transform: translateY(-2px);
                box-shadow: 0 2px 6px rgba(0, 115, 170, 0.2);
            }

            .preset-size-btn.active,
            .preset-size-btn.button-primary {
                border-color: #0e7d3f !important;
                background: #0e7d3f !important;
                color: #fff !important;
                box-shadow: 0 2px 8px rgba(14, 125, 63, 0.3);
            }

            .preset-size-btn.active:hover {
                background: #0d6e36 !important;
                border-color: #0d6e36 !important;
            }

            .preset-size-btn strong {
                display: block;
                font-size: 14px;
                margin-bottom: 4px;
            }

            .preset-size-btn small {
                font-size: 11px;
                opacity: 0.8;
            }

            /* Device Tabs (Phase 1) */
            .amadex-device-tabs {
                margin-top: 15px;
            }

            .amadex-device-tab {
                transition: all 0.2s ease;
                position: relative;
            }

            .amadex-device-tab:hover {
                background: #f5f5f5 !important;
            }

            .amadex-device-tab.active {
                color: #0073aa !important;
                font-weight: 700;
            }

            .amadex-device-dimensions {
                padding: 15px;
                background: #fafafa;
                border-radius: 4px;
                border: 1px solid #e5e5e5;
            }

            .amadex-drawer-toggle:not(.amadex-drawer-open) .dashicons-arrow-down-alt2 {
                transform: rotate(-90deg);
            }

            .amadex-drawer-content {
                background: #fff;
                border-bottom: 1px solid #ddd;
            }

            .amadex-drawer-content td {
                padding: 20px !important;
                background: #fafafa;
            }

            .amadex-drawer-content table.form-table {
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                margin: 0;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }

            .amadex-drawer-content .form-table th {
                padding: 15px 20px 15px 0;
                width: 200px;
                min-width: 180px;
                vertical-align: top;
            }

            .amadex-drawer-content .form-table td {
                padding: 15px 0;
                background: transparent;
                width: auto;
            }

            /* Full-width form wrapper */
            .amadex-promo-container-form-wrapper .form-table {
                width: 100%;
                max-width: 100%;
            }

            .amadex-promo-container-form-wrapper .form-table th {
                width: 200px;
                min-width: 180px;
            }

            .amadex-promo-container-form-wrapper .form-table td {
                width: auto;
            }

            /* Preview section full width */
            .amadex-preview-section {
                width: 100%;
                box-sizing: border-box;
            }

            /* Device selector full width layout */
            .amadex-device-selector {
                width: 100%;
                max-width: 100%;
            }

            /* Angles list responsive grid */
            .amadex-angles-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
                width: 100%;
            }

            @media screen and (max-width: 1200px) {
                .amadex-angles-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media screen and (max-width: 600px) {
                .amadex-angles-grid {
                    grid-template-columns: 1fr;
                }
            }

            /* Main form table full width styling */
            .amadex-promo-form-table {
                width: 100% !important;
                max-width: 100% !important;
            }

            .amadex-promo-form-table th {
                width: 200px;
                min-width: 180px;
                padding: 15px 20px 15px 0;
            }

            .amadex-promo-form-table td {
                width: auto;
                padding: 15px 0;
            }

            /* Better spacing for form sections */
            .amadex-drawer-content {
                padding: 20px !important;
            }

            .amadex-drawer-content>td {
                padding: 20px !important;
            }

            /* Full-width for nested form tables inside drawers */
            .amadex-drawer-content .form-table,
            .amadex-drawer-content table.form-table {
                width: 100%;
                table-layout: auto;
            }

            /* Responsive adjustments for form fields */
            @media screen and (min-width: 1600px) {
                .amadex-promo-form-table th {
                    width: 220px;
                    min-width: 220px;
                }
            }

            @media screen and (max-width: 1400px) {
                .amadex-promo-form-table th {
                    width: 180px;
                    min-width: 180px;
                }
            }

            @media screen and (max-width: 1200px) {
                .amadex-promo-form-table th {
                    width: 160px;
                    min-width: 160px;
                }
            }

            @media screen and (max-width: 782px) {

                .amadex-promo-form-table th,
                .amadex-promo-form-table td {
                    display: block;
                    width: 100%;
                    padding: 10px 0;
                }

                .amadex-promo-form-table th {
                    width: 100%;
                    min-width: 100%;
                    padding-bottom: 5px;
                    font-weight: 600;
                }
            }
        </style>

        <div class="amadex-promotional-containers-management">
            <div class="amadex-admin-header-section">
                <h2><?php _e('Promotional Containers Management', 'amadex'); ?></h2>
                <p class="description"><?php _e('Create promotional containers that appear between flight results. Similar to Skyscanner\'s "Track prices" banners, these can be used for price alerts, airline ads, product promotions, and callbacks.', 'amadex'); ?></p>
            </div>

            <!-- Promo Topics Presets -->
            <div class="amadex-promo-topics-section" style="margin: 20px 0; padding: 20px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; width: 100%; box-sizing: border-box;">
                <h3 style="margin-top: 0;"><?php _e('🎯 Promo Topic Presets', 'amadex'); ?></h3>
                <p class="description" style="margin-bottom: 15px;"><?php _e('Start with a pre-designed banner concept. Each topic includes multiple angles with recommended container types and psychology insights.', 'amadex'); ?></p>

                <div id="amadex-promo-topics-container" class="amadex-topics-grid">
                    <!-- Topics will be populated by JavaScript -->
                </div>

                <div id="amadex-promo-topic-angles" style="display: none; margin-top: 20px; padding: 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <h4 id="amadex-topic-angles-title" style="margin-top: 0;"></h4>
                    <div id="amadex-topic-angles-list" class="amadex-angles-grid">
                        <!-- Angles will be populated by JavaScript -->
                    </div>
                    <button type="button" id="amadex-close-angles" style="margin-top: 15px; padding: 8px 16px; background: #6b7280; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                        <?php _e('Close', 'amadex'); ?>
                    </button>
                </div>
            </div>

            <!-- Add/Edit Container Form -->
            <div class="amadex-promo-container-form-wrapper amadex-settings-form-wrapper">
                <h3><?php echo $editing_container ? __('Edit Promotional Container', 'amadex') : __('Add New Promotional Container', 'amadex'); ?></h3>
                <form method="post" action="" class="amadex-promo-container-form">
                    <?php wp_nonce_field('amadex_promo_containers', 'amadex_promo_container_nonce'); ?>
                    <input type="hidden" name="amadex_promo_container_action" value="save">
                    <?php if ($editing_container): ?>
                        <input type="hidden" name="container_id" value="<?php echo esc_attr(isset($editing_container['id']) ? $editing_container['id'] : (isset($edit_id) ? $edit_id : '')); ?>">
                    <?php endif; ?>

                    <!-- Live Preview Section - Always Visible at Top -->
                    <div class="amadex-preview-section amadex-section-top" style="margin-bottom: 30px;">
                        <h3>
                            <span class="dashicons dashicons-visibility" style="font-size: 20px; vertical-align: middle; margin-right: 8px;"></span>
                            <?php _e('Live Preview', 'amadex'); ?>
                        </h3>

                        <!-- Device Selector -->
                        <div class="amadex-device-selector">
                            <button type="button" class="amadex-device-button active" data-device="desktop" data-width="1200" data-height="800">
                                <span style="margin-right: 5px;">🖥️</span> <?php _e('Desktop', 'amadex'); ?> (1200px)
                            </button>
                            <button type="button" class="amadex-device-button" data-device="tablet" data-width="768" data-height="1024">
                                <span style="margin-right: 5px;">📱</span> <?php _e('Tablet', 'amadex'); ?> (768px)
                            </button>
                            <button type="button" class="amadex-device-button" data-device="mobile" data-width="375" data-height="667">
                                <span style="margin-right: 5px;">📱</span> <?php _e('Mobile', 'amadex'); ?> (375px)
                            </button>

                            <!-- Custom Device Dimensions -->
                            <div class="amadex-custom-device-inputs">
                                <label style="font-size: 12px; color: #666;"><?php _e('Custom:', 'amadex'); ?></label>
                                <input type="number" id="amadex-custom-width" placeholder="Width" value="1200" min="200" max="2000" style="width: 80px;">
                                <span style="color: #666;">×</span>
                                <input type="number" id="amadex-custom-height" placeholder="Height" value="800" min="200" max="2000" style="width: 80px;">
                                <button type="button" class="amadex-device-button" id="amadex-apply-custom" style="padding: 6px 12px; font-size: 12px;">
                                    <?php _e('Apply', 'amadex'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Preview Iframe Container -->
                        <div class="amadex-preview-iframe-container" id="amadex-preview-iframe-container">
                            <div class="amadex-preview-loading" id="amadex-preview-loading">
                                <?php _e('Loading preview...', 'amadex'); ?>
                            </div>
                            <iframe id="amadex-preview-iframe" src="about:blank" style="display: none;"></iframe>
                        </div>
                    </div>

                    <table class="form-table amadex-promo-form-table" role="presentation" style="width: 100%; max-width: 100%;">
                        <!-- Container Content & Size Section -->
                        <tr class="amadex-drawer-header">
                            <td colspan="2">
                                <button type="button" class="amadex-drawer-toggle amadex-drawer-open" data-drawer="basic-content">
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    <span class="dashicons dashicons-edit" style="margin-left: 8px; font-size: 18px;"></span>
                                    <strong style="margin-left: 8px;"><?php _e('Container Content & Size', 'amadex'); ?></strong>
                                </button>
                            </td>
                        </tr>
                        <tr class="amadex-drawer-content" data-drawer="basic-content">
                            <td colspan="2">
                                <table class="form-table" style="margin: 0;">
                                    <!-- Container Size Section -->
                                    <tr>
                                        <th scope="row"><label><?php _e('Container Size', 'amadex'); ?> <span class="required">*</span></label></th>
                                        <td>
                                            <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9;">
                                                <legend style="padding: 0 10px; font-weight: 600;"><?php _e('Quick Preset Sizes', 'amadex'); ?></legend>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-top: 10px;">
                                                    <button type="button" class="button preset-size-btn" data-preset="320x50" data-width="320" data-height="50" data-unit="px" style="text-align: center; padding: 10px;">
                                                        <strong>320×50</strong><br>
                                                        <small style="font-size: 11px; color: #666;">Mobile Leaderboard</small>
                                                    </button>
                                                    <button type="button" class="button preset-size-btn" data-preset="320x100" data-width="320" data-height="100" data-unit="px" style="text-align: center; padding: 10px;">
                                                        <strong>320×100</strong><br>
                                                        <small style="font-size: 11px; color: #666;">Mobile Large</small>
                                                    </button>
                                                    <button type="button" class="button preset-size-btn" data-preset="300x250" data-width="300" data-height="250" data-unit="px" style="text-align: center; padding: 10px;">
                                                        <strong>300×250</strong><br>
                                                        <small style="font-size: 11px; color: #666;">Medium Rectangle</small>
                                                    </button>
                                                    <button type="button" class="button preset-size-btn" data-preset="728x90" data-width="728" data-height="90" data-unit="px" style="text-align: center; padding: 10px;">
                                                        <strong>728×90</strong><br>
                                                        <small style="font-size: 11px; color: #666;">Desktop Leaderboard</small>
                                                    </button>
                                                    <button type="button" class="button preset-size-btn" data-preset="300x600" data-width="300" data-height="600" data-unit="px" style="text-align: center; padding: 10px;">
                                                        <strong>300×600</strong><br>
                                                        <small style="font-size: 11px; color: #666;">Half Page</small>
                                                    </button>
                                                    <button type="button" class="button preset-size-btn" data-preset="fullwidth" data-width="100" data-height="auto" data-unit="%" style="text-align: center; padding: 10px;">
                                                        <strong>Full Width</strong><br>
                                                        <small style="font-size: 11px; color: #666;">100% Auto Height</small>
                                                    </button>
                                                    <button type="button" class="button preset-size-btn active" data-preset="custom" style="text-align: center; padding: 10px;">
                                                        <strong>Custom</strong><br>
                                                        <small style="font-size: 11px; color: #666;">Manual Dimensions</small>
                                                    </button>
                                                </div>
                                                <p class="description" style="margin-top: 10px;"><?php _e('Click a preset to quickly set dimensions, or use Custom for manual control.', 'amadex'); ?></p>
                                            </fieldset>

                                            <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9; margin-top: 20px;">
                                                <legend style="padding: 0 10px; font-weight: 600;"><?php _e('Device-Specific Dimensions', 'amadex'); ?></legend>

                                                <div style="margin-top: 15px;">
                                                    <label style="display: flex; align-items: center; margin-bottom: 15px; cursor: pointer;">
                                                        <input type="checkbox" id="link_dimensions" name="link_dimensions" value="1" checked style="margin-right: 8px;">
                                                        <span style="font-weight: 600;"><?php _e('Link Dimensions Across Devices', 'amadex'); ?></span>
                                                    </label>
                                                    <p class="description" style="margin-top: 5px; margin-bottom: 15px;"><?php _e('When enabled, changing dimensions on one device updates others. Uncheck for independent device sizing.', 'amadex'); ?></p>
                                                </div>

                                                <div class="amadex-device-tabs" style="margin-top: 15px;">
                                                    <div style="display: flex; gap: 5px; border-bottom: 2px solid #ddd; margin-bottom: 15px;">
                                                        <button type="button" class="amadex-device-tab active" data-device="desktop" style="padding: 8px 15px; border: none; background: transparent; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; font-weight: 600;">
                                                            <span style="margin-right: 5px;">🖥️</span> <?php _e('Desktop', 'amadex'); ?>
                                                        </button>
                                                        <button type="button" class="amadex-device-tab" data-device="tablet" style="padding: 8px 15px; border: none; background: transparent; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; font-weight: 600;">
                                                            <span style="margin-right: 5px;">📱</span> <?php _e('Tablet', 'amadex'); ?>
                                                        </button>
                                                        <button type="button" class="amadex-device-tab" data-device="mobile" style="padding: 8px 15px; border: none; background: transparent; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; font-weight: 600;">
                                                            <span style="margin-right: 5px;">📱</span> <?php _e('Mobile', 'amadex'); ?>
                                                        </button>
                                                    </div>

                                                    <?php
                                                    $devices = array('desktop', 'tablet', 'mobile');
                                                    foreach ($devices as $device):
                                                        $device_width_value = ($editing_container && isset($editing_container['dimensions'][$device]['width_value'])) ? $editing_container['dimensions'][$device]['width_value'] : '';
                                                        $device_width_unit = ($editing_container && isset($editing_container['dimensions'][$device]['width_unit'])) ? $editing_container['dimensions'][$device]['width_unit'] : (($device === 'desktop') ? '%' : '%');
                                                        $device_height_value = ($editing_container && isset($editing_container['dimensions'][$device]['height_value'])) ? $editing_container['dimensions'][$device]['height_value'] : '';
                                                        $device_height_unit = ($editing_container && isset($editing_container['dimensions'][$device]['height_unit'])) ? $editing_container['dimensions'][$device]['height_unit'] : 'auto';
                                                        // Fallback to global dimensions if device-specific not set
                                                        if (empty($device_width_value) && $editing_container) {
                                                            $device_width_value = isset($editing_container['container_width_value']) ? $editing_container['container_width_value'] : (($device === 'desktop') ? '100' : '100');
                                                            $device_width_unit = isset($editing_container['container_width_unit']) ? $editing_container['container_width_unit'] : '%';
                                                        }
                                                        if (empty($device_height_value) && $editing_container && isset($editing_container['container_height_value']) && $editing_container['container_height_value'] !== 'auto') {
                                                            $device_height_value = $editing_container['container_height_value'];
                                                            $device_height_unit = isset($editing_container['container_height_unit']) ? $editing_container['container_height_unit'] : 'auto';
                                                        }
                                                    ?>
                                                        <div class="amadex-device-dimensions" data-device="<?php echo esc_attr($device); ?>" style="display: <?php echo $device === 'desktop' ? 'block' : 'none'; ?>;">
                                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                                                <div>
                                                                    <label for="container_<?php echo esc_attr($device); ?>_width_value" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Width', 'amadex'); ?>:</label>
                                                                    <div style="display: flex; gap: 10px; align-items: center;">
                                                                        <input type="number" id="container_<?php echo esc_attr($device); ?>_width_value" name="dimensions[<?php echo esc_attr($device); ?>][width_value]" min="0" max="2000" step="0.1" class="small-text" value="<?php echo esc_attr($device_width_value); ?>" placeholder="<?php echo $device === 'desktop' ? '100' : '100'; ?>">
                                                                        <select id="container_<?php echo esc_attr($device); ?>_width_unit" name="dimensions[<?php echo esc_attr($device); ?>][width_unit]" style="width: 80px;">
                                                                            <option value="%" <?php selected($device_width_unit, '%'); ?>>%</option>
                                                                            <option value="px" <?php selected($device_width_unit, 'px'); ?>>px</option>
                                                                            <option value="vw" <?php selected($device_width_unit, 'vw'); ?>>vw</option>
                                                                            <option value="rem" <?php selected($device_width_unit, 'rem'); ?>>rem</option>
                                                                            <option value="em" <?php selected($device_width_unit, 'em'); ?>>em</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <label for="container_<?php echo esc_attr($device); ?>_height_value" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Height', 'amadex'); ?>:</label>
                                                                    <div style="display: flex; gap: 10px; align-items: center;">
                                                                        <input type="number" id="container_<?php echo esc_attr($device); ?>_height_value" name="dimensions[<?php echo esc_attr($device); ?>][height_value]" min="0" max="2000" step="0.1" class="small-text" value="<?php echo esc_attr($device_height_value); ?>" placeholder="auto">
                                                                        <select id="container_<?php echo esc_attr($device); ?>_height_unit" name="dimensions[<?php echo esc_attr($device); ?>][height_unit]" style="width: 100px;">
                                                                            <option value="auto" <?php selected($device_height_unit, 'auto'); ?>><?php _e('Auto', 'amadex'); ?></option>
                                                                            <option value="px" <?php selected($device_height_unit, 'px'); ?>>px</option>
                                                                            <option value="%" <?php selected($device_height_unit, '%'); ?>>%</option>
                                                                            <option value="rem" <?php selected($device_height_unit, 'rem'); ?>>rem</option>
                                                                            <option value="em" <?php selected($device_height_unit, 'em'); ?>>em</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <p class="description" style="margin-top: 10px;"><?php printf(__('Dimensions for %s devices. Use %% for responsive, px for fixed, or auto for content-based height.', 'amadex'), ucfirst($device)); ?></p>

                                                            <!-- Min/Max Constraints for Device -->
                                                            <?php
                                                            $device_min_width_value = ($editing_container && isset($editing_container['dimensions'][$device]['min_width_value'])) ? $editing_container['dimensions'][$device]['min_width_value'] : '';
                                                            $device_min_width_unit = ($editing_container && isset($editing_container['dimensions'][$device]['min_width_unit'])) ? $editing_container['dimensions'][$device]['min_width_unit'] : 'px';
                                                            $device_max_width_value = ($editing_container && isset($editing_container['dimensions'][$device]['max_width_value'])) ? $editing_container['dimensions'][$device]['max_width_value'] : '';
                                                            $device_max_width_unit = ($editing_container && isset($editing_container['dimensions'][$device]['max_width_unit'])) ? $editing_container['dimensions'][$device]['max_width_unit'] : 'px';
                                                            $device_min_height_value = ($editing_container && isset($editing_container['dimensions'][$device]['min_height_value'])) ? $editing_container['dimensions'][$device]['min_height_value'] : '';
                                                            $device_min_height_unit = ($editing_container && isset($editing_container['dimensions'][$device]['min_height_unit'])) ? $editing_container['dimensions'][$device]['min_height_unit'] : 'px';
                                                            $device_max_height_value = ($editing_container && isset($editing_container['dimensions'][$device]['max_height_value'])) ? $editing_container['dimensions'][$device]['max_height_value'] : '';
                                                            $device_max_height_unit = ($editing_container && isset($editing_container['dimensions'][$device]['max_height_unit'])) ? $editing_container['dimensions'][$device]['max_height_unit'] : 'px';

                                                            // Fallback to legacy min/max if device-specific not set
                                                            if (empty($device_min_width_value) && $editing_container && isset($editing_container['container_min_width_value'])) {
                                                                $device_min_width_value = $editing_container['container_min_width_value'];
                                                                $device_min_width_unit = isset($editing_container['container_min_width_unit']) ? $editing_container['container_min_width_unit'] : 'px';
                                                            }
                                                            if (empty($device_max_width_value) && $editing_container && isset($editing_container['container_max_width_value'])) {
                                                                $device_max_width_value = $editing_container['container_max_width_value'];
                                                                $device_max_width_unit = isset($editing_container['container_max_width_unit']) ? $editing_container['container_max_width_unit'] : 'px';
                                                            }
                                                            if (empty($device_min_height_value) && $editing_container && isset($editing_container['container_min_height_value'])) {
                                                                $device_min_height_value = $editing_container['container_min_height_value'];
                                                                $device_min_height_unit = isset($editing_container['container_min_height_unit']) ? $editing_container['container_min_height_unit'] : 'px';
                                                            }
                                                            if (empty($device_max_height_value) && $editing_container && isset($editing_container['container_max_height_value'])) {
                                                                $device_max_height_value = $editing_container['container_max_height_value'];
                                                                $device_max_height_unit = isset($editing_container['container_max_height_unit']) ? $editing_container['container_max_height_unit'] : 'px';
                                                            }
                                                            ?>

                                                            <fieldset style="border: 1px solid #e5e5e5; padding: 15px; border-radius: 4px; background: #fafafa; margin-top: 20px;">
                                                                <legend style="padding: 0 10px; font-weight: 600; font-size: 13px;"><?php _e('Min/Max Constraints', 'amadex'); ?></legend>
                                                                <p class="description" style="margin-bottom: 15px; font-size: 12px;"><?php _e('Optional constraints to limit container size. Leave empty to disable.', 'amadex'); ?></p>

                                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                                                                    <div>
                                                                        <label for="container_<?php echo esc_attr($device); ?>_min_width_value" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;"><?php _e('Min Width', 'amadex'); ?>:</label>
                                                                        <div style="display: flex; gap: 8px; align-items: center;">
                                                                            <input type="number" id="container_<?php echo esc_attr($device); ?>_min_width_value" name="dimensions[<?php echo esc_attr($device); ?>][min_width_value]" min="0" max="2000" step="0.1" class="small-text" value="<?php echo esc_attr($device_min_width_value); ?>" placeholder="0" style="width: 70px;">
                                                                            <select id="container_<?php echo esc_attr($device); ?>_min_width_unit" name="dimensions[<?php echo esc_attr($device); ?>][min_width_unit]" style="width: 60px;">
                                                                                <option value="px" <?php selected($device_min_width_unit, 'px'); ?>>px</option>
                                                                                <option value="%" <?php selected($device_min_width_unit, '%'); ?>>%</option>
                                                                                <option value="vw" <?php selected($device_min_width_unit, 'vw'); ?>>vw</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <label for="container_<?php echo esc_attr($device); ?>_max_width_value" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;"><?php _e('Max Width', 'amadex'); ?>:</label>
                                                                        <div style="display: flex; gap: 8px; align-items: center;">
                                                                            <input type="number" id="container_<?php echo esc_attr($device); ?>_max_width_value" name="dimensions[<?php echo esc_attr($device); ?>][max_width_value]" min="0" max="2000" step="0.1" class="small-text" value="<?php echo esc_attr($device_max_width_value); ?>" placeholder="none" style="width: 70px;">
                                                                            <select id="container_<?php echo esc_attr($device); ?>_max_width_unit" name="dimensions[<?php echo esc_attr($device); ?>][max_width_unit]" style="width: 60px;">
                                                                                <option value="px" <?php selected($device_max_width_unit, 'px'); ?>>px</option>
                                                                                <option value="%" <?php selected($device_max_width_unit, '%'); ?>>%</option>
                                                                                <option value="vw" <?php selected($device_max_width_unit, 'vw'); ?>>vw</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <label for="container_<?php echo esc_attr($device); ?>_min_height_value" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;"><?php _e('Min Height', 'amadex'); ?>:</label>
                                                                        <div style="display: flex; gap: 8px; align-items: center;">
                                                                            <input type="number" id="container_<?php echo esc_attr($device); ?>_min_height_value" name="dimensions[<?php echo esc_attr($device); ?>][min_height_value]" min="0" max="2000" step="0.1" class="small-text" value="<?php echo esc_attr($device_min_height_value); ?>" placeholder="0" style="width: 70px;">
                                                                            <select id="container_<?php echo esc_attr($device); ?>_min_height_unit" name="dimensions[<?php echo esc_attr($device); ?>][min_height_unit]" style="width: 60px;">
                                                                                <option value="px" <?php selected($device_min_height_unit, 'px'); ?>>px</option>
                                                                                <option value="%" <?php selected($device_min_height_unit, '%'); ?>>%</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <label for="container_<?php echo esc_attr($device); ?>_max_height_value" style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;"><?php _e('Max Height', 'amadex'); ?>:</label>
                                                                        <div style="display: flex; gap: 8px; align-items: center;">
                                                                            <input type="number" id="container_<?php echo esc_attr($device); ?>_max_height_value" name="dimensions[<?php echo esc_attr($device); ?>][max_height_value]" min="0" max="2000" step="0.1" class="small-text" value="<?php echo esc_attr($device_max_height_value); ?>" placeholder="none" style="width: 70px;">
                                                                            <select id="container_<?php echo esc_attr($device); ?>_max_height_unit" name="dimensions[<?php echo esc_attr($device); ?>][max_height_unit]" style="width: 60px;">
                                                                                <option value="px" <?php selected($device_max_height_unit, 'px'); ?>>px</option>
                                                                                <option value="%" <?php selected($device_max_height_unit, '%'); ?>>%</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </fieldset>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </fieldset>

                                            <script type="text/javascript">
                                                jQuery(document).ready(function($) {
                                                    // Container Size Handlers
                                                    var linkDimensions = true;

                                                    // Initialize link dimensions checkbox state
                                                    $('#link_dimensions').on('change', function() {
                                                        linkDimensions = $(this).is(':checked');
                                                    });

                                                    // Preset size buttons
                                                    $('.preset-size-btn').on('click', function(e) {
                                                        e.preventDefault();
                                                        var $btn = $(this);
                                                        var preset = $btn.data('preset');
                                                        var width = $btn.data('width');
                                                        var height = $btn.data('height');
                                                        var unit = $btn.data('unit');

                                                        // Remove active class from all buttons
                                                        $('.preset-size-btn').removeClass('active button-primary');
                                                        $btn.addClass('active button-primary');

                                                        // Apply preset to active device or all devices if linked
                                                        var activeDevice = $('.amadex-device-tab.active').data('device');

                                                        if (linkDimensions) {
                                                            // Apply to all devices
                                                            $('.amadex-device-dimensions').each(function() {
                                                                var device = $(this).data('device');
                                                                applyPresetToDevice(device, width, height, unit);
                                                            });
                                                        } else {
                                                            // Apply only to active device
                                                            applyPresetToDevice(activeDevice, width, height, unit);
                                                        }

                                                        amadexUpdatePromoPreview();
                                                    });

                                                    function applyPresetToDevice(device, width, height, unit) {
                                                        var $widthInput = $('#container_' + device + '_width_value');
                                                        var $widthUnit = $('#container_' + device + '_width_unit');
                                                        var $heightInput = $('#container_' + device + '_height_value');
                                                        var $heightUnit = $('#container_' + device + '_height_unit');

                                                        $widthInput.val(width);
                                                        $widthUnit.val(unit);

                                                        if (height === 'auto') {
                                                            $heightInput.val('');
                                                            $heightUnit.val('auto');
                                                        } else {
                                                            $heightInput.val(height);
                                                            $heightUnit.val(unit);
                                                        }
                                                    }

                                                    // Device tab switching
                                                    $('.amadex-device-tab').on('click', function() {
                                                        var device = $(this).data('device');

                                                        // Update tab states
                                                        $('.amadex-device-tab').removeClass('active');
                                                        $(this).addClass('active');
                                                        $('.amadex-device-tab').css({
                                                            'border-bottom': '2px solid transparent',
                                                            'color': '#555'
                                                        });
                                                        $(this).css({
                                                            'border-bottom': '2px solid #0073aa',
                                                            'color': '#0073aa'
                                                        });

                                                        // Show corresponding device dimensions
                                                        $('.amadex-device-dimensions').hide();
                                                        $('.amadex-device-dimensions[data-device="' + device + '"]').show();
                                                    });

                                                    // Link dimensions functionality
                                                    var deviceInputs = {
                                                        'desktop': {
                                                            width: $('#container_desktop_width_value, #container_desktop_width_unit'),
                                                            height: $('#container_desktop_height_value, #container_desktop_height_unit')
                                                        },
                                                        'tablet': {
                                                            width: $('#container_tablet_width_value, #container_tablet_width_unit'),
                                                            height: $('#container_tablet_height_value, #container_tablet_height_unit')
                                                        },
                                                        'mobile': {
                                                            width: $('#container_mobile_width_value, #container_mobile_width_unit'),
                                                            height: $('#container_mobile_height_value, #container_mobile_height_unit')
                                                        }
                                                    };

                                                    // Handle dimension changes with linking
                                                    $.each(deviceInputs, function(device, inputs) {
                                                        inputs.width.on('change input', function() {
                                                            var widthVal = $('#container_' + device + '_width_value').val();
                                                            var widthUnit = $('#container_' + device + '_width_unit').val();

                                                            if (linkDimensions) {
                                                                // Update all other devices
                                                                $.each(deviceInputs, function(otherDevice, otherInputs) {
                                                                    if (otherDevice !== device) {
                                                                        $('#container_' + otherDevice + '_width_value').val(widthVal);
                                                                        $('#container_' + otherDevice + '_width_unit').val(widthUnit);
                                                                    }
                                                                });
                                                            }
                                                            amadexUpdatePromoPreview();
                                                        });

                                                        inputs.height.on('change input', function() {
                                                            var heightVal = $('#container_' + device + '_height_value').val();
                                                            var heightUnit = $('#container_' + device + '_height_unit').val();

                                                            if (linkDimensions) {
                                                                // Update all other devices
                                                                $.each(deviceInputs, function(otherDevice, otherInputs) {
                                                                    if (otherDevice !== device) {
                                                                        $('#container_' + otherDevice + '_height_value').val(heightVal);
                                                                        $('#container_' + otherDevice + '_height_unit').val(heightUnit);
                                                                    }
                                                                });
                                                            }
                                                            amadexUpdatePromoPreview();
                                                        });
                                                    });

                                                    // Initialize active tab styling
                                                    $('.amadex-device-tab.active').css({
                                                        'border-bottom': '2px solid #0073aa',
                                                        'color': '#0073aa'
                                                    });

                                                    // Sync with legacy dimension fields (backward compatibility)
                                                    function syncDeviceDimensionsToLegacy() {
                                                        var activeDevice = $('.amadex-device-tab.active').data('device');
                                                        var widthVal = $('#container_' + activeDevice + '_width_value').val();
                                                        var widthUnit = $('#container_' + activeDevice + '_width_unit').val();
                                                        var heightVal = $('#container_' + activeDevice + '_height_value').val();
                                                        var heightUnit = $('#container_' + activeDevice + '_height_unit').val();

                                                        // Update legacy fields for preview
                                                        if ($('#container_width_value').length) {
                                                            $('#container_width_value').val(widthVal);
                                                            $('#container_width_unit').val(widthUnit);
                                                        }
                                                        if ($('#container_height_value').length && heightUnit !== 'auto') {
                                                            $('#container_height_value').val(heightVal);
                                                            $('#container_height_unit').val(heightUnit);
                                                        } else if ($('#container_height_unit').length) {
                                                            $('#container_height_unit').val('auto');
                                                            $('#container_height_value').val('');
                                                        }
                                                    }

                                                    // Sync on device tab change
                                                    $('.amadex-device-tab').on('click', function() {
                                                        setTimeout(syncDeviceDimensionsToLegacy, 100);
                                                    });

                                                    // Sync on dimension change
                                                    $('.amadex-device-dimensions input, .amadex-device-dimensions select').on('change', function() {
                                                        syncDeviceDimensionsToLegacy();
                                                    });
                                                });
                                            </script>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="container_type"><?php _e('Container Type', 'amadex'); ?> <span class="required">*</span></label></th>
                                        <td>
                                            <select id="container_type" name="container_type" required onchange="amadexUpdatePromoFields(this.value); amadexUpdatePromoPreview();">
                                                <option value="price_alert" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : 'price_alert', 'price_alert'); ?>>
                                                    <?php _e('Price Alert Banner', 'amadex'); ?>
                                                </option>
                                                <option value="airline_ad" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'airline_ad'); ?>>
                                                    <?php _e('Airline Promotion', 'amadex'); ?>
                                                </option>
                                                <option value="product_cross_sell" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'product_cross_sell'); ?>>
                                                    <?php _e('Product Cross-Sell', 'amadex'); ?>
                                                </option>
                                                <option value="callback" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'callback'); ?>>
                                                    <?php _e('Callback/Query Form', 'amadex'); ?>
                                                </option>
                                                <option value="ad" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'ad'); ?>>
                                                    <?php _e('Ad Slot', 'amadex'); ?>
                                                </option>
                                                <optgroup label="<?php _e('Travelay-Specific Types', 'amadex'); ?>">
                                                    <option value="travelaygent_spotlight" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'travelaygent_spotlight'); ?>>
                                                        <?php _e('TravelayGent Spotlight', 'amadex'); ?>
                                                    </option>
                                                    <option value="trust_badge" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'trust_badge'); ?>>
                                                        <?php _e('Trust Badge Banner', 'amadex'); ?>
                                                    </option>
                                                    <option value="urgency_scarcity" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'urgency_scarcity'); ?>>
                                                        <?php _e('Urgency/Scarcity Alert', 'amadex'); ?>
                                                    </option>
                                                    <option value="deal_highlight" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'deal_highlight'); ?>>
                                                        <?php _e('Deal Highlight Card', 'amadex'); ?>
                                                    </option>
                                                    <option value="comparison_table" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'comparison_table'); ?>>
                                                        <?php _e('Comparison Table Banner', 'amadex'); ?>
                                                    </option>
                                                    <option value="social_proof" <?php selected($editing_container && isset($editing_container['type']) ? $editing_container['type'] : '', 'social_proof'); ?>>
                                                        <?php _e('Social Proof Banner', 'amadex'); ?>
                                                    </option>
                                                </optgroup>
                                            </select>
                                            <p class="description"><?php _e('Select the type of promotional container you want to create.', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <!-- Phase 6: Template selector now always visible after Container Type -->
                                    <tr id="template_selector_row">
                                        <th scope="row"><label for="container_template_id"><?php _e('Template', 'amadex'); ?></label></th>
                                        <td>
                                            <select id="container_template_id" name="container_template_id" onchange="amadexUpdateTemplateFields(this.value); amadexUpdatePromoPreview();">
                                                <option value=""><?php _e('None (Use Legacy Layout)', 'amadex'); ?></option>
                                                <option value="native_inline_card" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'native_inline_card'); ?>>
                                                    <?php _e('Native Inline Card', 'amadex'); ?>
                                                </option>
                                                <option value="itinerary_promo" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'itinerary_promo'); ?>>
                                                    <?php _e('Itinerary Promo', 'amadex'); ?>
                                                </option>
                                                <option value="three_agent_cards" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'three_agent_cards'); ?>>
                                                    <?php _e('3 Agent Cards', 'amadex'); ?>
                                                </option>
                                                <option value="two_column_feature" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'two_column_feature'); ?>>
                                                    <?php _e('2 Column Feature', 'amadex'); ?>
                                                </option>
                                                <option value="hero_spotlight" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'hero_spotlight'); ?>>
                                                    <?php _e('Hero Spotlight', 'amadex'); ?>
                                                </option>
                                                <option value="promo_carousel" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'promo_carousel'); ?>>
                                                    <?php _e('Promo Carousel', 'amadex'); ?>
                                                </option>
                                                <option value="video_promo_tile" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'video_promo_tile'); ?>>
                                                    <?php _e('Video Promo Tile', 'amadex'); ?>
                                                </option>
                                                <optgroup label="<?php _e('Travelay-Specific Templates', 'amadex'); ?>">
                                                    <option value="travelaygent_profile_card" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'travelaygent_profile_card'); ?>>
                                                        <?php _e('TravelayGent Profile Card', 'amadex'); ?>
                                                    </option>
                                                    <option value="travel_pass_verification" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'travel_pass_verification'); ?>>
                                                        <?php _e('Travel Pass Verification Badge', 'amadex'); ?>
                                                    </option>
                                                    <option value="agent_comparison_grid" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'agent_comparison_grid'); ?>>
                                                        <?php _e('Agent Comparison Grid', 'amadex'); ?>
                                                    </option>
                                                    <option value="deal_countdown_timer" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'deal_countdown_timer'); ?>>
                                                        <?php _e('Deal Countdown Timer', 'amadex'); ?>
                                                    </option>
                                                    <option value="trust_metrics_banner" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'trust_metrics_banner'); ?>>
                                                        <?php _e('Trust Metrics Banner', 'amadex'); ?>
                                                    </option>
                                                    <option value="flight_status_alert" <?php selected($editing_container && isset($editing_container['template_id']) ? $editing_container['template_id'] : '', 'flight_status_alert'); ?>>
                                                        <?php _e('Flight Status Alert', 'amadex'); ?>
                                                    </option>
                                                </optgroup>
                                            </select>
                                            <p class="description"><?php _e('Select a prebuilt template for advanced layouts. Leave as "None" to use the legacy container type layout.', 'amadex'); ?></p>
                                            <div id="template_description" style="margin-top: 10px; padding: 10px; background: #f0f9f4; border-left: 3px solid #0e7d3f; border-radius: 3px; display: none;">
                                                <strong><?php _e('Template Description:', 'amadex'); ?></strong>
                                                <p id="template_description_text" style="margin: 5px 0 0 0; color: #555;"></p>
                                            </div>

                                            <!-- Phase 3: Template-Specific Fields -->
                                            <div id="template_specific_fields" style="margin-top: 20px; display: none;">
                                                <!-- 3 Agent Cards Template Fields -->
                                                <div id="template_fields_three_agent_cards" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Agent Cards Configuration', 'amadex'); ?></h4>
                                                    <?php
                                                    $agent_cards = ($editing_container && isset($editing_container['template_data']['agent_cards']) && is_array($editing_container['template_data']['agent_cards'])) ? $editing_container['template_data']['agent_cards'] : array(
                                                        array('name' => '', 'description' => '', 'image_url' => '', 'link_url' => '', 'phone' => '', 'rating' => '5.0'),
                                                        array('name' => '', 'description' => '', 'image_url' => '', 'link_url' => '', 'phone' => '', 'rating' => '5.0'),
                                                        array('name' => '', 'description' => '', 'image_url' => '', 'link_url' => '', 'phone' => '', 'rating' => '5.0')
                                                    );
                                                    for ($i = 0; $i < 3; $i++):
                                                        $card = isset($agent_cards[$i]) ? $agent_cards[$i] : array('name' => '', 'description' => '', 'image_url' => '', 'link_url' => '', 'phone' => '', 'rating' => '5.0');
                                                    ?>
                                                        <fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fafafa;">
                                                            <legend style="padding: 0 10px; font-weight: 600;"><?php printf(__('Agent Card %d', 'amadex'), $i + 1); ?></legend>
                                                            <table class="form-table" style="margin: 0;">
                                                                <tr>
                                                                    <th scope="row" style="width: 150px;"><label><?php _e('Agent Name', 'amadex'); ?></label></th>
                                                                    <td>
                                                                        <input type="text" name="template_data[agent_cards][<?php echo $i; ?>][name]" class="regular-text" value="<?php echo esc_attr($card['name']); ?>" placeholder="<?php printf(__('Agent %d Name', 'amadex'), $i + 1); ?>">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row"><label><?php _e('Description', 'amadex'); ?></label></th>
                                                                    <td>
                                                                        <textarea name="template_data[agent_cards][<?php echo $i; ?>][description]" rows="2" class="large-text" placeholder="<?php printf(__('Description for agent %d', 'amadex'), $i + 1); ?>"><?php echo esc_textarea($card['description']); ?></textarea>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row"><label><?php _e('Photo URL', 'amadex'); ?></label></th>
                                                                    <td>
                                                                        <input type="url" name="template_data[agent_cards][<?php echo $i; ?>][image_url]" class="regular-text" value="<?php echo esc_url($card['image_url']); ?>" placeholder="https://...">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row"><label><?php _e('Link URL', 'amadex'); ?></label></th>
                                                                    <td>
                                                                        <input type="url" name="template_data[agent_cards][<?php echo $i; ?>][link_url]" class="regular-text" value="<?php echo esc_url($card['link_url']); ?>" placeholder="https://...">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row"><label><?php _e('Phone/Contact', 'amadex'); ?></label></th>
                                                                    <td>
                                                                        <input type="text" name="template_data[agent_cards][<?php echo $i; ?>][phone]" class="regular-text" value="<?php echo esc_attr($card['phone']); ?>" placeholder="+1-877-XXX-XXXX">
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <th scope="row"><label><?php _e('Rating', 'amadex'); ?></label></th>
                                                                    <td>
                                                                        <input type="number" name="template_data[agent_cards][<?php echo $i; ?>][rating]" min="0" max="5" step="0.1" class="small-text" value="<?php echo esc_attr($card['rating']); ?>">
                                                                        <span class="description"><?php _e('(0-5 stars)', 'amadex'); ?></span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </fieldset>
                                                    <?php endfor; ?>
                                                </div>

                                                <!-- Itinerary Promo Template Fields -->
                                                <div id="template_fields_itinerary_promo" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Itinerary Details', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Departure Date', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="text" name="template_data[departure_date]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['departure_date']) ? $editing_container['template_data']['departure_date'] : ''); ?>" placeholder="Monday, Jan 26, 2024">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Return Date', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="text" name="template_data[return_date]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['return_date']) ? $editing_container['template_data']['return_date'] : ''); ?>" placeholder="Monday, Feb 2, 2024">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Destination', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="text" name="template_data[destination]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['destination']) ? $editing_container['template_data']['destination'] : ''); ?>" placeholder="New York to Los Angeles">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Price', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="text" name="template_data[price]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['price']) ? $editing_container['template_data']['price'] : ''); ?>" placeholder="$299">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <!-- Promo Carousel Template Fields -->
                                                <div id="template_fields_promo_carousel" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Carousel Slides', 'amadex'); ?></h4>
                                                    <div id="carousel_slides_container">
                                                        <?php
                                                        $slides = ($editing_container && isset($editing_container['template_data']['slides']) && is_array($editing_container['template_data']['slides'])) ? $editing_container['template_data']['slides'] : array(
                                                            array('title' => '', 'description' => '', 'image_url' => '', 'link_url' => '', 'button_text' => 'Learn More')
                                                        );
                                                        foreach ($slides as $index => $slide):
                                                        ?>
                                                            <fieldset class="carousel-slide-fieldset" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fafafa;">
                                                                <legend style="padding: 0 10px; font-weight: 600;"><?php printf(__('Slide %d', 'amadex'), $index + 1); ?> <button type="button" class="button button-small remove-slide" style="float: right; margin-top: -5px;"><?php _e('Remove', 'amadex'); ?></button></legend>
                                                                <table class="form-table" style="margin: 0;">
                                                                    <tr>
                                                                        <th scope="row" style="width: 150px;"><label><?php _e('Title', 'amadex'); ?></label></th>
                                                                        <td><input type="text" name="template_data[slides][<?php echo $index; ?>][title]" class="regular-text" value="<?php echo esc_attr($slide['title']); ?>"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row"><label><?php _e('Description', 'amadex'); ?></label></th>
                                                                        <td><textarea name="template_data[slides][<?php echo $index; ?>][description]" rows="2" class="large-text"><?php echo esc_textarea($slide['description']); ?></textarea></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row"><label><?php _e('Image URL', 'amadex'); ?></label></th>
                                                                        <td><input type="url" name="template_data[slides][<?php echo $index; ?>][image_url]" class="regular-text" value="<?php echo esc_url($slide['image_url']); ?>"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row"><label><?php _e('Link URL', 'amadex'); ?></label></th>
                                                                        <td><input type="url" name="template_data[slides][<?php echo $index; ?>][link_url]" class="regular-text" value="<?php echo esc_url($slide['link_url']); ?>"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row"><label><?php _e('Button Text', 'amadex'); ?></label></th>
                                                                        <td><input type="text" name="template_data[slides][<?php echo $index; ?>][button_text]" class="regular-text" value="<?php echo esc_attr($slide['button_text']); ?>" placeholder="Learn More"></td>
                                                                    </tr>
                                                                </table>
                                                            </fieldset>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <button type="button" class="button" id="add_carousel_slide"><?php _e('+ Add Slide', 'amadex'); ?></button>
                                                </div>

                                                <!-- Hero Spotlight Template Fields -->
                                                <div id="template_fields_hero_spotlight" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Hero Content', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Subtitle', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="text" name="template_data[subtitle]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['subtitle']) ? $editing_container['template_data']['subtitle'] : ''); ?>" placeholder="Optional subtitle above main title">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <!-- Two Column Feature Template Fields -->
                                                <div id="template_fields_two_column_feature" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Column Content', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Left Column Content', 'amadex'); ?></label></th>
                                                            <td>
                                                                <textarea name="template_data[left_content]" rows="3" class="large-text" placeholder="Left column text content"><?php echo esc_textarea($editing_container && isset($editing_container['template_data']['left_content']) ? $editing_container['template_data']['left_content'] : ''); ?></textarea>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Left Column Image URL', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="url" name="template_data[left_image]" class="regular-text" value="<?php echo esc_url($editing_container && isset($editing_container['template_data']['left_image']) ? $editing_container['template_data']['left_image'] : ''); ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Right Column Content', 'amadex'); ?></label></th>
                                                            <td>
                                                                <textarea name="template_data[right_content]" rows="3" class="large-text" placeholder="Right column text content"><?php echo esc_textarea($editing_container && isset($editing_container['template_data']['right_content']) ? $editing_container['template_data']['right_content'] : ''); ?></textarea>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Right Column Image URL', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="url" name="template_data[right_image]" class="regular-text" value="<?php echo esc_url($editing_container && isset($editing_container['template_data']['right_image']) ? $editing_container['template_data']['right_image'] : ''); ?>">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <!-- Video Promo Tile Template Fields -->
                                                <div id="template_fields_video_promo_tile" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Video Details', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Video URL', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="url" name="template_data[video_url]" class="regular-text" value="<?php echo esc_url($editing_container && isset($editing_container['template_data']['video_url']) ? $editing_container['template_data']['video_url'] : ''); ?>" placeholder="https://youtube.com/watch?v=...">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Video Thumbnail URL', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="url" name="template_data[video_thumbnail]" class="regular-text" value="<?php echo esc_url($editing_container && isset($editing_container['template_data']['video_thumbnail']) ? $editing_container['template_data']['video_thumbnail'] : ''); ?>">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Video Duration', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="text" name="template_data[video_duration]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['video_duration']) ? $editing_container['template_data']['video_duration'] : ''); ?>" placeholder="2:30">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <!-- Phase 4: New Travelay Templates -->
                                                <!-- TravelayGent Profile Card -->
                                                <div id="template_fields_travelaygent_profile_card" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('TravelayGent Profile', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Agent Name', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[agent_name]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['agent_name']) ? $editing_container['template_data']['agent_name'] : ''); ?>"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Agent Photo URL', 'amadex'); ?></label></th>
                                                            <td><input type="url" name="template_data[agent_photo]" class="regular-text" value="<?php echo esc_url($editing_container && isset($editing_container['template_data']['agent_photo']) ? $editing_container['template_data']['agent_photo'] : ''); ?>"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Specialties', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[agent_specialties]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['agent_specialties']) ? $editing_container['template_data']['agent_specialties'] : ''); ?>" placeholder="Business Travel, Luxury Vacations"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Rating', 'amadex'); ?></label></th>
                                                            <td><input type="number" name="template_data[agent_rating]" min="0" max="5" step="0.1" class="small-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['agent_rating']) ? $editing_container['template_data']['agent_rating'] : '5.0'); ?>"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Profile Link', 'amadex'); ?></label></th>
                                                            <td><input type="url" name="template_data[agent_profile_link]" class="regular-text" value="<?php echo esc_url($editing_container && isset($editing_container['template_data']['agent_profile_link']) ? $editing_container['template_data']['agent_profile_link'] : ''); ?>"></td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <!-- Travel Pass Verification Badge -->
                                                <div id="template_fields_travel_pass_verification" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Travel Pass Details', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Verification Status', 'amadex'); ?></label></th>
                                                            <td>
                                                                <select name="template_data[verification_status]">
                                                                    <option value="verified" <?php selected($editing_container && isset($editing_container['template_data']['verification_status']) ? $editing_container['template_data']['verification_status'] : 'verified', 'verified'); ?>><?php _e('Verified', 'amadex'); ?></option>
                                                                    <option value="pending" <?php selected($editing_container && isset($editing_container['template_data']['verification_status']) ? $editing_container['template_data']['verification_status'] : '', 'pending'); ?>><?php _e('Pending', 'amadex'); ?></option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Special Offer Text', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[offer_text]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['offer_text']) ? $editing_container['template_data']['offer_text'] : ''); ?>" placeholder="Inventory Suggest Better Price on Call"></td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <!-- Agent Comparison Grid -->
                                                <div id="template_fields_agent_comparison_grid" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Agent Comparison', 'amadex'); ?></h4>
                                                    <p class="description"><?php _e('Configure 2-4 agents for side-by-side comparison.', 'amadex'); ?></p>
                                                    <div id="agent_comparison_container">
                                                        <?php
                                                        $comparison_agents = ($editing_container && isset($editing_container['template_data']['comparison_agents']) && is_array($editing_container['template_data']['comparison_agents'])) ? $editing_container['template_data']['comparison_agents'] : array(
                                                            array('name' => '', 'photo' => '', 'rating' => '5.0', 'specialties' => '', 'link' => ''),
                                                            array('name' => '', 'photo' => '', 'rating' => '5.0', 'specialties' => '', 'link' => '')
                                                        );
                                                        foreach ($comparison_agents as $index => $agent):
                                                        ?>
                                                            <fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fafafa;">
                                                                <legend style="padding: 0 10px; font-weight: 600;"><?php printf(__('Agent %d', 'amadex'), $index + 1); ?></legend>
                                                                <table class="form-table" style="margin: 0;">
                                                                    <tr>
                                                                        <th scope="row" style="width: 150px;"><label><?php _e('Name', 'amadex'); ?></label></th>
                                                                        <td><input type="text" name="template_data[comparison_agents][<?php echo $index; ?>][name]" class="regular-text" value="<?php echo esc_attr($agent['name']); ?>"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row"><label><?php _e('Photo URL', 'amadex'); ?></label></th>
                                                                        <td><input type="url" name="template_data[comparison_agents][<?php echo $index; ?>][photo]" class="regular-text" value="<?php echo esc_url($agent['photo']); ?>"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row"><label><?php _e('Rating', 'amadex'); ?></label></th>
                                                                        <td><input type="number" name="template_data[comparison_agents][<?php echo $index; ?>][rating]" min="0" max="5" step="0.1" class="small-text" value="<?php echo esc_attr($agent['rating']); ?>"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row"><label><?php _e('Specialties', 'amadex'); ?></label></th>
                                                                        <td><input type="text" name="template_data[comparison_agents][<?php echo $index; ?>][specialties]" class="regular-text" value="<?php echo esc_attr($agent['specialties']); ?>"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <th scope="row"><label><?php _e('Link URL', 'amadex'); ?></label></th>
                                                                        <td><input type="url" name="template_data[comparison_agents][<?php echo $index; ?>][link]" class="regular-text" value="<?php echo esc_url($agent['link']); ?>"></td>
                                                                    </tr>
                                                                </table>
                                                            </fieldset>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <button type="button" class="button" id="add_comparison_agent"><?php _e('+ Add Agent', 'amadex'); ?></button>
                                                </div>

                                                <!-- Deal Countdown Timer -->
                                                <div id="template_fields_deal_countdown_timer" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Deal & Countdown', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Deal Headline', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[deal_headline]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['deal_headline']) ? $editing_container['template_data']['deal_headline'] : ''); ?>" placeholder="Flash Sale - Limited Time!"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Discount Percentage', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[discount_percent]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['discount_percent']) ? $editing_container['template_data']['discount_percent'] : ''); ?>" placeholder="25%"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Countdown End Time', 'amadex'); ?></label></th>
                                                            <td>
                                                                <input type="datetime-local" name="template_data[countdown_end]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['countdown_end']) ? $editing_container['template_data']['countdown_end'] : ''); ?>">
                                                                <p class="description"><?php _e('Date and time when the countdown should end.', 'amadex'); ?></p>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <!-- Trust Metrics Banner -->
                                                <div id="template_fields_trust_metrics_banner" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Trust Metrics', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Customer Count', 'amadex'); ?></label></th>
                                                            <td><input type="number" name="template_data[customer_count]" min="0" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['customer_count']) ? $editing_container['template_data']['customer_count'] : '0'); ?>" placeholder="10000"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Rating', 'amadex'); ?></label></th>
                                                            <td><input type="number" name="template_data[trust_rating]" min="0" max="5" step="0.1" class="small-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['trust_rating']) ? $editing_container['template_data']['trust_rating'] : '4.5'); ?>"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Certification Badge', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[certification]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['certification']) ? $editing_container['template_data']['certification'] : ''); ?>" placeholder="IATA Certified"></td>
                                                        </tr>
                                                    </table>
                                                </div>

                                                <!-- Flight Status Alert -->
                                                <div id="template_fields_flight_status_alert" class="template-specific-fields" style="display: none;">
                                                    <h4 style="margin-top: 0; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #ddd;"><?php _e('Flight Status', 'amadex'); ?></h4>
                                                    <table class="form-table" style="margin: 0;">
                                                        <tr>
                                                            <th scope="row" style="width: 150px;"><label><?php _e('Flight Number', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[flight_number]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['flight_number']) ? $editing_container['template_data']['flight_number'] : ''); ?>" placeholder="AA123"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Status', 'amadex'); ?></label></th>
                                                            <td>
                                                                <select name="template_data[flight_status]">
                                                                    <option value="on_time" <?php selected($editing_container && isset($editing_container['template_data']['flight_status']) ? $editing_container['template_data']['flight_status'] : 'on_time', 'on_time'); ?>><?php _e('On Time', 'amadex'); ?></option>
                                                                    <option value="delayed" <?php selected($editing_container && isset($editing_container['template_data']['flight_status']) ? $editing_container['template_data']['flight_status'] : '', 'delayed'); ?>><?php _e('Delayed', 'amadex'); ?></option>
                                                                    <option value="cancelled" <?php selected($editing_container && isset($editing_container['template_data']['flight_status']) ? $editing_container['template_data']['flight_status'] : '', 'cancelled'); ?>><?php _e('Cancelled', 'amadex'); ?></option>
                                                                </select>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Route', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[flight_route]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['flight_route']) ? $editing_container['template_data']['flight_route'] : ''); ?>" placeholder="NYC to LAX"></td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row"><label><?php _e('Action Button Text', 'amadex'); ?></label></th>
                                                            <td><input type="text" name="template_data[action_button]" class="regular-text" value="<?php echo esc_attr($editing_container && isset($editing_container['template_data']['action_button']) ? $editing_container['template_data']['action_button'] : 'View Details'); ?>" placeholder="View Details"></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Phase 6: Hidden legacy field - dimensions now handled by Container Size section -->
                                    <tr id="container_type_selector_row" style="display: none;">
                                        <th scope="row"><label for="container_type_id"><?php _e('Container Type (Skyscanner-inspired)', 'amadex'); ?></label></th>
                                        <td>
                                            <select id="container_type_id" name="container_type_id" onchange="amadexUpdateContainerTypeFields(this.value); amadexUpdatePromoPreview();">
                                                <option value=""><?php _e('None (Custom Dimensions)', 'amadex'); ?></option>
                                                <optgroup label="<?php _e('Standard Display Banners', 'amadex'); ?>">
                                                    <option value="standard_320x50" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'standard_320x50'); ?>>
                                                        <?php _e('320×50 - Mobile Leaderboard', 'amadex'); ?>
                                                    </option>
                                                    <option value="standard_320x100" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'standard_320x100'); ?>>
                                                        <?php _e('320×100 - Mobile Large Banner', 'amadex'); ?>
                                                    </option>
                                                    <option value="standard_300x250" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'standard_300x250'); ?>>
                                                        <?php _e('300×250 - Medium Rectangle', 'amadex'); ?>
                                                    </option>
                                                    <option value="standard_728x90" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'standard_728x90'); ?>>
                                                        <?php _e('728×90 - Leaderboard', 'amadex'); ?>
                                                    </option>
                                                    <option value="standard_300x600" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'standard_300x600'); ?>>
                                                        <?php _e('300×600 - Half Page', 'amadex'); ?>
                                                    </option>
                                                </optgroup>
                                                <optgroup label="<?php _e('Native & Custom Formats', 'amadex'); ?>">
                                                    <option value="native_inline_card" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'native_inline_card'); ?>>
                                                        <?php _e('Native Inline Card', 'amadex'); ?>
                                                    </option>
                                                    <option value="itinerary_style_native" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'itinerary_style_native'); ?>>
                                                        <?php _e('Itinerary Style Native', 'amadex'); ?>
                                                    </option>
                                                    <option value="brand_banner" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'brand_banner'); ?>>
                                                        <?php _e('Brand Banner', 'amadex'); ?>
                                                    </option>
                                                    <option value="carousel" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'carousel'); ?>>
                                                        <?php _e('Carousel', 'amadex'); ?>
                                                    </option>
                                                    <option value="hero_takeover" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'hero_takeover'); ?>>
                                                        <?php _e('Hero Takeover', 'amadex'); ?>
                                                    </option>
                                                    <option value="in_banner_video" <?php selected($editing_container && isset($editing_container['container_type_id']) ? $editing_container['container_type_id'] : '', 'in_banner_video'); ?>>
                                                        <?php _e('In-Banner Video', 'amadex'); ?>
                                                    </option>
                                                </optgroup>
                                            </select>
                                            <p class="description"><?php _e('Select a Skyscanner-inspired container type with predefined dimensions and constraints. This will automatically set optimal dimensions for the selected format.', 'amadex'); ?></p>
                                            <div id="container_type_info" style="margin-top: 10px; padding: 12px; background: #f0f7ff; border-left: 3px solid #0066cc; border-radius: 3px; display: none;">
                                                <div style="margin-bottom: 8px;">
                                                    <strong style="color: #0066cc;"><?php _e('Container Type Info:', 'amadex'); ?></strong>
                                                </div>
                                                <div style="margin-bottom: 5px;">
                                                    <strong><?php _e('Description:', 'amadex'); ?></strong>
                                                    <p id="container_type_description" style="margin: 3px 0 8px 0; color: #555; line-height: 1.5;"></p>
                                                </div>
                                                <div style="margin-bottom: 5px;">
                                                    <strong><?php _e('Use Case:', 'amadex'); ?></strong>
                                                    <p id="container_type_usecase" style="margin: 3px 0 8px 0; color: #555; line-height: 1.5;"></p>
                                                </div>
                                                <div>
                                                    <strong><?php _e('Uniqueness:', 'amadex'); ?></strong>
                                                    <p id="container_type_uniqueness" style="margin: 3px 0 0 0; color: #555; line-height: 1.5;"></p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="container_title"><?php _e('Title', 'amadex'); ?> <span class="required">*</span></label></th>
                                        <td>
                                            <input type="text" id="container_title" name="container_title" class="regular-text" value="<?php echo $editing_container ? esc_attr($editing_container['title']) : ''; ?>" required>
                                            <p class="description"><?php _e('Main title/headline for the container (e.g., "Like these flights?")', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="container_description"><?php _e('Description', 'amadex'); ?></label></th>
                                        <td>
                                            <textarea id="container_description" name="container_description" rows="3" class="large-text"><?php echo $editing_container ? esc_textarea($editing_container['description']) : ''; ?></textarea>
                                            <p class="description"><?php _e('Subtitle or description text (e.g., "We\'ll let you know when prices go up or down.")', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="container_button_text"><?php _e('Button Text', 'amadex'); ?></label></th>
                                        <td>
                                            <input type="text" id="container_button_text" name="container_button_text" class="regular-text" value="<?php echo $editing_container ? esc_attr($editing_container['button_text'] ?? 'Track prices') : 'Track prices'; ?>">
                                            <p class="description"><?php _e('Text for the call-to-action button.', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <!-- Container Type Specific Fields -->
                                    <tbody id="price_alert_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'price_alert') || !$editing_container ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="email_placeholder"><?php _e('Email Placeholder', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="email_placeholder" name="email_placeholder" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['email_placeholder']) ? esc_attr($editing_container['additional_data']['email_placeholder']) : 'Enter your email'; ?>">
                                                <p class="description"><?php _e('Placeholder text for email input field.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <tbody id="callback_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'callback') ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="phone_placeholder"><?php _e('Phone Placeholder', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="phone_placeholder" name="phone_placeholder" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['phone_placeholder']) ? esc_attr($editing_container['additional_data']['phone_placeholder']) : 'Enter your phone number'; ?>">
                                                <p class="description"><?php _e('Placeholder text for phone input field.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="callback_message"><?php _e('Callback Message', 'amadex'); ?></label></th>
                                            <td>
                                                <textarea id="callback_message" name="callback_message" rows="2" class="large-text"><?php echo $editing_container && isset($editing_container['additional_data']['callback_message']) ? esc_textarea($editing_container['additional_data']['callback_message']) : ''; ?></textarea>
                                                <p class="description"><?php _e('Message shown after callback request is submitted.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <tbody id="airline_ad_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'airline_ad') ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="airline_logo_url"><?php _e('Airline Logo URL', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="url" id="airline_logo_url" name="airline_logo_url" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['airline_logo_url']) ? esc_url($editing_container['additional_data']['airline_logo_url']) : ''; ?>">
                                                <p class="description"><?php _e('URL to airline logo image.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="offer_text"><?php _e('Offer Text', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="offer_text" name="offer_text" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['offer_text']) ? esc_attr($editing_container['additional_data']['offer_text']) : ''; ?>">
                                                <p class="description"><?php _e('Special offer or promotion text.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <!-- Phase 2: New Container Type Fields -->
                                    <tbody id="travelaygent_spotlight_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'travelaygent_spotlight') ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="agent_name"><?php _e('Agent Name', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="agent_name" name="agent_name" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['agent_name']) ? esc_attr($editing_container['additional_data']['agent_name']) : ''; ?>">
                                                <p class="description"><?php _e('Name of the TravelayGent to showcase.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="agent_photo_url"><?php _e('Agent Photo URL', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="url" id="agent_photo_url" name="agent_photo_url" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['agent_photo_url']) ? esc_url($editing_container['additional_data']['agent_photo_url']) : ''; ?>">
                                                <p class="description"><?php _e('URL to agent profile photo.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="agent_specialties"><?php _e('Agent Specialties', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="agent_specialties" name="agent_specialties" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['agent_specialties']) ? esc_attr($editing_container['additional_data']['agent_specialties']) : ''; ?>" placeholder="e.g., Business Travel, Luxury Vacations">
                                                <p class="description"><?php _e('Agent areas of expertise or specialties.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="agent_rating"><?php _e('Agent Rating', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="number" id="agent_rating" name="agent_rating" min="0" max="5" step="0.1" class="small-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['agent_rating']) ? esc_attr($editing_container['additional_data']['agent_rating']) : '5.0'; ?>">
                                                <p class="description"><?php _e('Agent rating (0-5 stars).', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="agent_link_url"><?php _e('Agent Profile Link', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="url" id="agent_link_url" name="agent_link_url" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['agent_link_url']) ? esc_url($editing_container['additional_data']['agent_link_url']) : ''; ?>">
                                                <p class="description"><?php _e('URL to agent profile or booking page.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <tbody id="trust_badge_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'trust_badge') ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="trust_badge_type"><?php _e('Badge Type', 'amadex'); ?></label></th>
                                            <td>
                                                <select id="trust_badge_type" name="trust_badge_type">
                                                    <option value="rating" <?php selected($editing_container && isset($editing_container['additional_data']['trust_badge_type']) ? $editing_container['additional_data']['trust_badge_type'] : 'rating', 'rating'); ?>><?php _e('Rating', 'amadex'); ?></option>
                                                    <option value="certification" <?php selected($editing_container && isset($editing_container['additional_data']['trust_badge_type']) ? $editing_container['additional_data']['trust_badge_type'] : '', 'certification'); ?>><?php _e('Certification', 'amadex'); ?></option>
                                                    <option value="years" <?php selected($editing_container && isset($editing_container['additional_data']['trust_badge_type']) ? $editing_container['additional_data']['trust_badge_type'] : '', 'years'); ?>><?php _e('Years in Business', 'amadex'); ?></option>
                                                </select>
                                                <p class="description"><?php _e('Type of trust badge to display.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="trust_rating"><?php _e('Rating', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="number" id="trust_rating" name="trust_rating" min="0" max="5" step="0.1" class="small-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['trust_rating']) ? esc_attr($editing_container['additional_data']['trust_rating']) : '4.5'; ?>">
                                                <p class="description"><?php _e('Trust rating (0-5 stars).', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="trust_review_count"><?php _e('Review Count', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="number" id="trust_review_count" name="trust_review_count" min="0" class="small-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['trust_review_count']) ? esc_attr($editing_container['additional_data']['trust_review_count']) : '0'; ?>">
                                                <p class="description"><?php _e('Number of customer reviews.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="trust_certification"><?php _e('Certification', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="trust_certification" name="trust_certification" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['trust_certification']) ? esc_attr($editing_container['additional_data']['trust_certification']) : ''; ?>" placeholder="e.g., IATA Certified">
                                                <p class="description"><?php _e('Certification or accreditation text.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="trust_years"><?php _e('Years in Business', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="number" id="trust_years" name="trust_years" min="0" class="small-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['trust_years']) ? esc_attr($editing_container['additional_data']['trust_years']) : '0'; ?>">
                                                <p class="description"><?php _e('Number of years in business.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <tbody id="urgency_scarcity_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'urgency_scarcity') ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="urgency_type"><?php _e('Urgency Type', 'amadex'); ?></label></th>
                                            <td>
                                                <select id="urgency_type" name="urgency_type">
                                                    <option value="seats" <?php selected($editing_container && isset($editing_container['additional_data']['urgency_type']) ? $editing_container['additional_data']['urgency_type'] : 'seats', 'seats'); ?>><?php _e('Limited Seats', 'amadex'); ?></option>
                                                    <option value="countdown" <?php selected($editing_container && isset($editing_container['additional_data']['urgency_type']) ? $editing_container['additional_data']['urgency_type'] : '', 'countdown'); ?>><?php _e('Countdown Timer', 'amadex'); ?></option>
                                                    <option value="price" <?php selected($editing_container && isset($editing_container['additional_data']['urgency_type']) ? $editing_container['additional_data']['urgency_type'] : '', 'price'); ?>><?php _e('Price Change', 'amadex'); ?></option>
                                                </select>
                                                <p class="description"><?php _e('Type of urgency indicator to display.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="urgency_count"><?php _e('Seats Left', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="number" id="urgency_count" name="urgency_count" min="0" class="small-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['urgency_count']) ? esc_attr($editing_container['additional_data']['urgency_count']) : '0'; ?>">
                                                <p class="description"><?php _e('Number of seats remaining (for Limited Seats type).', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="urgency_countdown"><?php _e('Countdown Time', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="urgency_countdown" name="urgency_countdown" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['urgency_countdown']) ? esc_attr($editing_container['additional_data']['urgency_countdown']) : ''; ?>" placeholder="e.g., 2h 30m">
                                                <p class="description"><?php _e('Countdown timer value (for Countdown Timer type).', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="urgency_price_change"><?php _e('Price Change', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="urgency_price_change" name="urgency_price_change" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['urgency_price_change']) ? esc_attr($editing_container['additional_data']['urgency_price_change']) : ''; ?>" placeholder="e.g., +$50 in 3 hours">
                                                <p class="description"><?php _e('Price change indicator (for Price Change type).', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <tbody id="deal_highlight_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'deal_highlight') ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="deal_amount"><?php _e('Deal Amount', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="deal_amount" name="deal_amount" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['deal_amount']) ? esc_attr($editing_container['additional_data']['deal_amount']) : ''; ?>" placeholder="e.g., $299">
                                                <p class="description"><?php _e('Deal price or amount.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="deal_savings"><?php _e('Savings', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="deal_savings" name="deal_savings" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['deal_savings']) ? esc_attr($editing_container['additional_data']['deal_savings']) : ''; ?>" placeholder="e.g., Save 25% or Save $150">
                                                <p class="description"><?php _e('Savings amount or percentage.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="deal_valid_dates"><?php _e('Valid Dates', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="deal_valid_dates" name="deal_valid_dates" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['deal_valid_dates']) ? esc_attr($editing_container['additional_data']['deal_valid_dates']) : ''; ?>" placeholder="e.g., Valid until Dec 31, 2024">
                                                <p class="description"><?php _e('Deal validity period.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="deal_route"><?php _e('Route', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="deal_route" name="deal_route" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['deal_route']) ? esc_attr($editing_container['additional_data']['deal_route']) : ''; ?>" placeholder="e.g., NYC to LAX">
                                                <p class="description"><?php _e('Flight route for this deal.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <tbody id="comparison_table_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'comparison_table') ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="comparison_highlight"><?php _e('Highlighted Option', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="comparison_highlight" name="comparison_highlight" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['comparison_highlight']) ? esc_attr($editing_container['additional_data']['comparison_highlight']) : ''; ?>" placeholder="e.g., Best Value">
                                                <p class="description"><?php _e('Text to highlight the recommended option.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label><?php _e('Comparison Items', 'amadex'); ?></label></th>
                                            <td>
                                                <div id="comparison_items_container">
                                                    <?php
                                                    $comparison_items = ($editing_container && isset($editing_container['additional_data']['comparison_items']) && is_array($editing_container['additional_data']['comparison_items'])) ? $editing_container['additional_data']['comparison_items'] : array('', '', '');
                                                    foreach ($comparison_items as $index => $item):
                                                    ?>
                                                        <div style="margin-bottom: 10px;">
                                                            <input type="text" name="comparison_items[]" class="regular-text" value="<?php echo esc_attr($item); ?>" placeholder="<?php printf(__('Item %d', 'amadex'), $index + 1); ?>">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button type="button" class="button" id="add_comparison_item" style="margin-top: 5px;"><?php _e('+ Add Item', 'amadex'); ?></button>
                                                <p class="description"><?php _e('Items to compare (e.g., prices, airlines, features).', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>

                                    <tbody id="social_proof_fields" class="promo-type-fields" style="<?php echo ($editing_container && isset($editing_container['type']) && $editing_container['type'] === 'social_proof') ? '' : 'display:none;'; ?>">
                                        <tr>
                                            <th scope="row"><label for="social_activity_text"><?php _e('Activity Text', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="text" id="social_activity_text" name="social_activity_text" class="regular-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['social_activity_text']) ? esc_attr($editing_container['additional_data']['social_activity_text']) : ''; ?>" placeholder="e.g., people booked this route">
                                                <p class="description"><?php _e('Activity description text.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="social_booking_count"><?php _e('Booking Count', 'amadex'); ?></label></th>
                                            <td>
                                                <input type="number" id="social_booking_count" name="social_booking_count" min="0" class="small-text" value="<?php echo $editing_container && isset($editing_container['additional_data']['social_booking_count']) ? esc_attr($editing_container['additional_data']['social_booking_count']) : '0'; ?>">
                                                <p class="description"><?php _e('Number of bookings or activity count.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><label for="social_time_period"><?php _e('Time Period', 'amadex'); ?></label></th>
                                            <td>
                                                <select id="social_time_period" name="social_time_period">
                                                    <option value="today" <?php selected($editing_container && isset($editing_container['additional_data']['social_time_period']) ? $editing_container['additional_data']['social_time_period'] : 'today', 'today'); ?>><?php _e('Today', 'amadex'); ?></option>
                                                    <option value="this_week" <?php selected($editing_container && isset($editing_container['additional_data']['social_time_period']) ? $editing_container['additional_data']['social_time_period'] : '', 'this_week'); ?>><?php _e('This Week', 'amadex'); ?></option>
                                                    <option value="this_month" <?php selected($editing_container && isset($editing_container['additional_data']['social_time_period']) ? $editing_container['additional_data']['social_time_period'] : '', 'this_month'); ?>><?php _e('This Month', 'amadex'); ?></option>
                                                </select>
                                                <p class="description"><?php _e('Time period for the activity count.', 'amadex'); ?></p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>

                        <!-- Styling & Layout Section -->
                        <tr class="amadex-drawer-header">
                            <td colspan="2">
                                <button type="button" class="amadex-drawer-toggle" data-drawer="dimensions-sizing">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    <span class="dashicons dashicons-admin-appearance" style="margin-left: 8px; font-size: 18px;"></span>
                                    <strong style="margin-left: 8px;"><?php _e('Styling & Layout', 'amadex'); ?></strong>
                                </button>
                            </td>
                        </tr>
                        <tr class="amadex-drawer-content" data-drawer="dimensions-sizing" style="display: none;">
                            <td colspan="2">
                                <table class="form-table" style="margin: 0;">
                                    <!-- Legacy Container Dimensions - Hidden for backward compatibility sync only -->
                                    <tr style="display: none;">
                                        <th scope="row"><label><?php _e('Container Dimensions (Legacy)', 'amadex'); ?></label></th>
                                        <td>
                                            <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9;">
                                                <legend style="padding: 0 10px; font-weight: 600;"><?php _e('Width', 'amadex'); ?></legend>
                                                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                                                    <input type="number" id="container_width_value" name="container_width_value" min="0" max="2000" step="0.1" class="small-text" value="<?php
                                                                                                                                                                                            if ($editing_container && isset($editing_container['container_width_value'])) {
                                                                                                                                                                                                echo esc_attr($editing_container['container_width_value']);
                                                                                                                                                                                            } elseif ($editing_container && isset($editing_container['container_width'])) {
                                                                                                                                                                                                // Convert old preset to new system
                                                                                                                                                                                                $old_width = $editing_container['container_width'];
                                                                                                                                                                                                if ($old_width === 'full') echo '100';
                                                                                                                                                                                                elseif ($old_width === 'compact') echo '65';
                                                                                                                                                                                                elseif ($old_width === 'mini') echo '45';
                                                                                                                                                                                                else echo '100';
                                                                                                                                                                                            } else {
                                                                                                                                                                                                echo '100';
                                                                                                                                                                                            }
                                                                                                                                                                                            ?>">
                                                    <select id="container_width_unit" name="container_width_unit" style="width: 60px;">
                                                        <option value="%" <?php selected(($editing_container && isset($editing_container['container_width_unit'])) ? $editing_container['container_width_unit'] : '%', '%'); ?>>%</option>
                                                        <option value="px" <?php selected(($editing_container && isset($editing_container['container_width_unit'])) ? $editing_container['container_width_unit'] : '', 'px'); ?>>px</option>
                                                    </select>
                                                </div>
                                                <p class="description" style="margin-top: 5px; margin-bottom: 15px;"><?php _e('Container width. Use % for responsive or px for fixed width.', 'amadex'); ?></p>

                                                <legend style="padding: 0 10px; font-weight: 600; margin-top: 15px;"><?php _e('Height', 'amadex'); ?></legend>
                                                <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
                                                    <input type="number" id="container_height_value" name="container_height_value" min="0" max="2000" step="0.1" class="small-text" value="<?php
                                                                                                                                                                                            if ($editing_container && isset($editing_container['container_height_value']) && $editing_container['container_height_value'] !== 'auto') {
                                                                                                                                                                                                echo esc_attr($editing_container['container_height_value']);
                                                                                                                                                                                            }
                                                                                                                                                                                            ?>" placeholder="auto">
                                                    <select id="container_height_unit" name="container_height_unit" style="width: 80px;">
                                                        <option value="auto" <?php selected(($editing_container && isset($editing_container['container_height_unit'])) ? $editing_container['container_height_unit'] : 'auto', 'auto'); ?>><?php _e('Auto', 'amadex'); ?></option>
                                                        <option value="px" <?php selected(($editing_container && isset($editing_container['container_height_unit'])) ? $editing_container['container_height_unit'] : '', 'px'); ?>>px</option>
                                                        <option value="%" <?php selected(($editing_container && isset($editing_container['container_height_unit'])) ? $editing_container['container_height_unit'] : '', '%'); ?>>%</option>
                                                    </select>
                                                </div>
                                                <script type="text/javascript">
                                                    jQuery(document).ready(function($) {
                                                        // Handle height unit change - clear value if "auto" selected
                                                        $('#container_height_unit').on('change', function() {
                                                            if ($(this).val() === 'auto') {
                                                                $('#container_height_value').val('');
                                                                amadexUpdatePromoPreview();
                                                            }
                                                        });

                                                        // Handle height value input - ensure unit is not "auto" when value is entered
                                                        $('#container_height_value').on('input', function() {
                                                            if ($(this).val() && $('#container_height_unit').val() === 'auto') {
                                                                $('#container_height_unit').val('px');
                                                            }
                                                            amadexUpdatePromoPreview();
                                                        });
                                                    });
                                                </script>
                                                <p class="description" style="margin-top: 5px;"><?php _e('Container height. Auto adjusts to content. Use px or % for fixed height.', 'amadex'); ?></p>
                                            </fieldset>
                                        </td>
                                    </tr>

                                    <!-- Padding Section -->
                                    <tr>
                                        <th scope="row"><label><?php _e('Padding', 'amadex'); ?></label></th>
                                        <td>
                                            <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9;">
                                                <legend style="padding: 0 10px; font-weight: 600;"><?php _e('Padding', 'amadex'); ?></legend>
                                                <div style="margin-top: 15px;">
                                                    <label style="display: flex; align-items: center; margin-bottom: 10px; cursor: pointer;">
                                                        <input type="radio" name="container_padding_mode" value="uniform" <?php checked(($editing_container && isset($editing_container['container_padding_mode'])) ? $editing_container['container_padding_mode'] : 'uniform', 'uniform'); ?> style="margin-right: 5px;">
                                                        <span style="font-weight: 600;"><?php _e('Uniform Padding', 'amadex'); ?></span>
                                                    </label>
                                                    <div id="padding_uniform_field" style="margin-left: 25px; margin-bottom: 15px; display: <?php echo (($editing_container && isset($editing_container['container_padding_mode']) && $editing_container['container_padding_mode'] === 'individual') ? 'none' : 'block'); ?>;">
                                                        <div style="display: flex; gap: 10px; align-items: center;">
                                                            <input type="number" id="container_padding_all" name="container_padding_all" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_padding_all'])) ? $editing_container['container_padding_all'] : ''); ?>" placeholder="0">
                                                            <select id="container_padding_all_unit" name="container_padding_all_unit" style="width: 60px;">
                                                                <option value="px" <?php selected(($editing_container && isset($editing_container['container_padding_all_unit'])) ? $editing_container['container_padding_all_unit'] : 'px', 'px'); ?>>px</option>
                                                                <option value="%" <?php selected(($editing_container && isset($editing_container['container_padding_all_unit'])) ? $editing_container['container_padding_all_unit'] : '', '%'); ?>>%</option>
                                                                <option value="em" <?php selected(($editing_container && isset($editing_container['container_padding_all_unit'])) ? $editing_container['container_padding_all_unit'] : '', 'em'); ?>>em</option>
                                                                <option value="rem" <?php selected(($editing_container && isset($editing_container['container_padding_all_unit'])) ? $editing_container['container_padding_all_unit'] : '', 'rem'); ?>>rem</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <label style="display: flex; align-items: center; margin-bottom: 10px; cursor: pointer;">
                                                        <input type="radio" name="container_padding_mode" value="xy" <?php checked(($editing_container && isset($editing_container['container_padding_mode'])) ? $editing_container['container_padding_mode'] : '', 'xy'); ?> style="margin-right: 5px;">
                                                        <span style="font-weight: 600;"><?php _e('X/Y Padding (Horizontal/Vertical)', 'amadex'); ?></span>
                                                    </label>
                                                    <div id="padding_xy_field" style="margin-left: 25px; margin-bottom: 15px; display: <?php echo (($editing_container && isset($editing_container['container_padding_mode']) && $editing_container['container_padding_mode'] === 'xy') ? 'block' : 'none'); ?>;">
                                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                                            <div>
                                                                <label for="container_padding_x" style="display: block; margin-bottom: 5px; font-size: 13px;"><?php _e('Horizontal (X)', 'amadex'); ?>:</label>
                                                                <div style="display: flex; gap: 10px; align-items: center;">
                                                                    <input type="number" id="container_padding_x" name="container_padding_x" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_padding_x'])) ? $editing_container['container_padding_x'] : ''); ?>" placeholder="0">
                                                                    <select id="container_padding_x_unit" name="container_padding_x_unit" style="width: 60px;">
                                                                        <option value="px" <?php selected(($editing_container && isset($editing_container['container_padding_x_unit'])) ? $editing_container['container_padding_x_unit'] : 'px', 'px'); ?>>px</option>
                                                                        <option value="%" <?php selected(($editing_container && isset($editing_container['container_padding_x_unit'])) ? $editing_container['container_padding_x_unit'] : '', '%'); ?>>%</option>
                                                                        <option value="em" <?php selected(($editing_container && isset($editing_container['container_padding_x_unit'])) ? $editing_container['container_padding_x_unit'] : '', 'em'); ?>>em</option>
                                                                        <option value="rem" <?php selected(($editing_container && isset($editing_container['container_padding_x_unit'])) ? $editing_container['container_padding_x_unit'] : '', 'rem'); ?>>rem</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label for="container_padding_y" style="display: block; margin-bottom: 5px; font-size: 13px;"><?php _e('Vertical (Y)', 'amadex'); ?>:</label>
                                                                <div style="display: flex; gap: 10px; align-items: center;">
                                                                    <input type="number" id="container_padding_y" name="container_padding_y" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_padding_y'])) ? $editing_container['container_padding_y'] : ''); ?>" placeholder="0">
                                                                    <select id="container_padding_y_unit" name="container_padding_y_unit" style="width: 60px;">
                                                                        <option value="px" <?php selected(($editing_container && isset($editing_container['container_padding_y_unit'])) ? $editing_container['container_padding_y_unit'] : 'px', 'px'); ?>>px</option>
                                                                        <option value="%" <?php selected(($editing_container && isset($editing_container['container_padding_y_unit'])) ? $editing_container['container_padding_y_unit'] : '', '%'); ?>>%</option>
                                                                        <option value="em" <?php selected(($editing_container && isset($editing_container['container_padding_y_unit'])) ? $editing_container['container_padding_y_unit'] : '', 'em'); ?>>em</option>
                                                                        <option value="rem" <?php selected(($editing_container && isset($editing_container['container_padding_y_unit'])) ? $editing_container['container_padding_y_unit'] : '', 'rem'); ?>>rem</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <label style="display: flex; align-items: center; margin-bottom: 10px; cursor: pointer;">
                                                        <input type="radio" name="container_padding_mode" value="individual" <?php checked(($editing_container && isset($editing_container['container_padding_mode'])) ? $editing_container['container_padding_mode'] : '', 'individual'); ?> style="margin-right: 5px;">
                                                        <span style="font-weight: 600;"><?php _e('Individual Sides', 'amadex'); ?></span>
                                                    </label>
                                                    <div id="padding_individual_field" style="margin-left: 25px; margin-bottom: 15px; display: <?php echo (($editing_container && isset($editing_container['container_padding_mode']) && $editing_container['container_padding_mode'] === 'individual') ? 'block' : 'none'); ?>;">
                                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                                            <div>
                                                                <label for="container_padding_top" style="display: block; margin-bottom: 5px; font-size: 13px;"><?php _e('Top', 'amadex'); ?>:</label>
                                                                <input type="number" id="container_padding_top" name="container_padding_top" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_padding_top'])) ? $editing_container['container_padding_top'] : ''); ?>" placeholder="0">
                                                            </div>
                                                            <div>
                                                                <label for="container_padding_right" style="display: block; margin-bottom: 5px; font-size: 13px;"><?php _e('Right', 'amadex'); ?>:</label>
                                                                <input type="number" id="container_padding_right" name="container_padding_right" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_padding_right'])) ? $editing_container['container_padding_right'] : ''); ?>" placeholder="0">
                                                            </div>
                                                            <div>
                                                                <label for="container_padding_bottom" style="display: block; margin-bottom: 5px; font-size: 13px;"><?php _e('Bottom', 'amadex'); ?>:</label>
                                                                <input type="number" id="container_padding_bottom" name="container_padding_bottom" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_padding_bottom'])) ? $editing_container['container_padding_bottom'] : ''); ?>" placeholder="0">
                                                            </div>
                                                            <div>
                                                                <label for="container_padding_left" style="display: block; margin-bottom: 5px; font-size: 13px;"><?php _e('Left', 'amadex'); ?>:</label>
                                                                <input type="number" id="container_padding_left" name="container_padding_left" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_padding_left'])) ? $editing_container['container_padding_left'] : ''); ?>" placeholder="0">
                                                            </div>
                                                        </div>
                                                        <p class="description" style="margin-top: 5px; font-size: 12px;"><?php _e('All individual padding values use px units.', 'amadex'); ?></p>
                                                    </div>
                                                </div>

                                                <legend style="padding: 0 10px; font-weight: 600; margin-top: 25px;"><?php _e('Gap (Grid Spacing)', 'amadex'); ?></legend>
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                                                    <div>
                                                        <label for="container_gap_column" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Column Gap', 'amadex'); ?>:</label>
                                                        <div style="display: flex; gap: 10px; align-items: center;">
                                                            <input type="number" id="container_gap_column" name="container_gap_column" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_gap_column'])) ? $editing_container['container_gap_column'] : ''); ?>" placeholder="0">
                                                            <select id="container_gap_column_unit" name="container_gap_column_unit" style="width: 60px;">
                                                                <option value="px" <?php selected(($editing_container && isset($editing_container['container_gap_column_unit'])) ? $editing_container['container_gap_column_unit'] : 'px', 'px'); ?>>px</option>
                                                                <option value="%" <?php selected(($editing_container && isset($editing_container['container_gap_column_unit'])) ? $editing_container['container_gap_column_unit'] : '', '%'); ?>>%</option>
                                                                <option value="em" <?php selected(($editing_container && isset($editing_container['container_gap_column_unit'])) ? $editing_container['container_gap_column_unit'] : '', 'em'); ?>>em</option>
                                                                <option value="rem" <?php selected(($editing_container && isset($editing_container['container_gap_column_unit'])) ? $editing_container['container_gap_column_unit'] : '', 'rem'); ?>>rem</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label for="container_gap_row" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Row Gap', 'amadex'); ?>:</label>
                                                        <div style="display: flex; gap: 10px; align-items: center;">
                                                            <input type="number" id="container_gap_row" name="container_gap_row" min="0" max="200" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_gap_row'])) ? $editing_container['container_gap_row'] : ''); ?>" placeholder="0">
                                                            <select id="container_gap_row_unit" name="container_gap_row_unit" style="width: 60px;">
                                                                <option value="px" <?php selected(($editing_container && isset($editing_container['container_gap_row_unit'])) ? $editing_container['container_gap_row_unit'] : 'px', 'px'); ?>>px</option>
                                                                <option value="%" <?php selected(($editing_container && isset($editing_container['container_gap_row_unit'])) ? $editing_container['container_gap_row_unit'] : '', '%'); ?>>%</option>
                                                                <option value="em" <?php selected(($editing_container && isset($editing_container['container_gap_row_unit'])) ? $editing_container['container_gap_row_unit'] : '', 'em'); ?>>em</option>
                                                                <option value="rem" <?php selected(($editing_container && isset($editing_container['container_gap_row_unit'])) ? $editing_container['container_gap_row_unit'] : '', 'rem'); ?>>rem</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="description" style="margin-top: 10px;"><?php _e('Spacing between grid columns and rows. Used for multi-column layouts.', 'amadex'); ?></p>

                                                <legend style="padding: 0 10px; font-weight: 600; margin-top: 25px;"><?php _e('Border Radius', 'amadex'); ?></legend>
                                                <div style="margin-top: 15px;">
                                                    <div style="display: flex; gap: 10px; align-items: center;">
                                                        <input type="number" id="container_border_radius" name="container_border_radius" min="0" max="100" step="0.1" class="small-text" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_border_radius'])) ? $editing_container['container_border_radius'] : ''); ?>" placeholder="0">
                                                        <select id="container_border_radius_unit" name="container_border_radius_unit" style="width: 60px;">
                                                            <option value="px" <?php selected(($editing_container && isset($editing_container['container_border_radius_unit'])) ? $editing_container['container_border_radius_unit'] : 'px', 'px'); ?>>px</option>
                                                            <option value="%" <?php selected(($editing_container && isset($editing_container['container_border_radius_unit'])) ? $editing_container['container_border_radius_unit'] : '', '%'); ?>>%</option>
                                                            <option value="em" <?php selected(($editing_container && isset($editing_container['container_border_radius_unit'])) ? $editing_container['container_border_radius_unit'] : '', 'em'); ?>>em</option>
                                                            <option value="rem" <?php selected(($editing_container && isset($editing_container['container_border_radius_unit'])) ? $editing_container['container_border_radius_unit'] : '', 'rem'); ?>>rem</option>
                                                        </select>
                                                    </div>
                                                    <p class="description" style="margin-top: 5px;"><?php _e('Rounded corners for the container. Higher values create more rounded corners.', 'amadex'); ?></p>
                                                </div>

                                                <legend style="padding: 0 10px; font-weight: 600; margin-top: 25px;"><?php _e('Compactness & Typography', 'amadex'); ?></legend>
                                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                                                    <div>
                                                        <label for="container_compactness" style="display: block; margin-bottom: 10px; font-weight: 600;"><?php _e('Compactness', 'amadex'); ?>: <span id="container_compactness_value"><?php echo esc_html(($editing_container && isset($editing_container['container_compactness'])) ? intval($editing_container['container_compactness']) : 50); ?>%</span></label>
                                                        <input type="range" id="container_compactness" name="container_compactness" min="0" max="100" step="1" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_compactness'])) ? intval($editing_container['container_compactness']) : 50); ?>" style="width: 100%;">
                                                        <div style="display: flex; justify-content: space-between; margin-top: 5px; font-size: 11px; color: #666;">
                                                            <span><?php _e('Spacious (0%)', 'amadex'); ?></span>
                                                            <span><?php _e('Balanced (50%)', 'amadex'); ?></span>
                                                            <span><?php _e('Compact (100%)', 'amadex'); ?></span>
                                                        </div>
                                                        <p class="description" style="margin-top: 5px; font-size: 12px;"><?php _e('Controls overall spacing density. Higher values reduce padding and gaps.', 'amadex'); ?></p>
                                                    </div>
                                                    <div>
                                                        <label for="container_typography_scale" style="display: block; margin-bottom: 10px; font-weight: 600;"><?php _e('Typography Scale', 'amadex'); ?>: <span id="container_typography_scale_value"><?php echo esc_html(($editing_container && isset($editing_container['container_typography_scale'])) ? number_format(floatval($editing_container['container_typography_scale']), 2) : '1.00'); ?>x</span></label>
                                                        <input type="range" id="container_typography_scale" name="container_typography_scale" min="0.5" max="2.0" step="0.01" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_typography_scale'])) ? floatval($editing_container['container_typography_scale']) : 1.0); ?>" style="width: 100%;">
                                                        <div style="display: flex; justify-content: space-between; margin-top: 5px; font-size: 11px; color: #666;">
                                                            <span><?php _e('Small (0.5x)', 'amadex'); ?></span>
                                                            <span><?php _e('Normal (1.0x)', 'amadex'); ?></span>
                                                            <span><?php _e('Large (2.0x)', 'amadex'); ?></span>
                                                        </div>
                                                        <p class="description" style="margin-top: 5px; font-size: 12px;"><?php _e('Scales all text sizes proportionally. Affects both title and body text.', 'amadex'); ?></p>
                                                    </div>
                                                </div>

                                                <script type="text/javascript">
                                                    jQuery(document).ready(function($) {
                                                        // Handle padding mode switching
                                                        $('input[name="container_padding_mode"]').on('change', function() {
                                                            var mode = $(this).val();
                                                            $('#padding_uniform_field, #padding_xy_field, #padding_individual_field').hide();
                                                            if (mode === 'uniform') {
                                                                $('#padding_uniform_field').show();
                                                            } else if (mode === 'xy') {
                                                                $('#padding_xy_field').show();
                                                            } else if (mode === 'individual') {
                                                                $('#padding_individual_field').show();
                                                            }
                                                            amadexUpdatePromoPreview();
                                                        });

                                                        // Update compactness value display
                                                        $('#container_compactness').on('input', function() {
                                                            $('#container_compactness_value').text($(this).val() + '%');
                                                            amadexUpdatePromoPreview();
                                                        });

                                                        // Update typography scale value display
                                                        $('#container_typography_scale').on('input', function() {
                                                            var val = parseFloat($(this).val()).toFixed(2);
                                                            $('#container_typography_scale_value').text(val + 'x');
                                                            amadexUpdatePromoPreview();
                                                        });
                                                    });
                                                </script>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Animations Section -->
                        <tr class="amadex-drawer-header">
                            <td colspan="2">
                                <button type="button" class="amadex-drawer-toggle" data-drawer="animations">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    <span class="dashicons dashicons-video-alt3" style="margin-left: 8px; font-size: 18px;"></span>
                                    <strong style="margin-left: 8px;"><?php _e('Animations', 'amadex'); ?></strong>
                                </button>
                            </td>
                        </tr>
                        <tr class="amadex-drawer-content" data-drawer="animations" style="display: none;">
                            <td colspan="2">
                                <table class="form-table" style="margin: 0;">
                                    <tr>
                                        <th scope="row"><label><?php _e('Animations', 'amadex'); ?></label></th>
                                        <td>
                                            <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9;">
                                                <legend style="padding: 0 10px; font-weight: 600;"><?php _e('Select Animations (Multiple allowed)', 'amadex'); ?></legend>
                                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px;">
                                                    <?php
                                                    $available_animations = array(
                                                        'shine' => __('Shine/Shimmer', 'amadex'),
                                                        'shimmer_sweep' => __('Shimmer Sweep', 'amadex'),
                                                        'pulse' => __('Pulse', 'amadex'),
                                                        'cta_pulse' => __('CTA Pulse', 'amadex'),
                                                        'fade_in' => __('Fade In', 'amadex'),
                                                        'slide_in_left' => __('Slide In Left', 'amadex'),
                                                        'slide_in_right' => __('Slide In Right', 'amadex'),
                                                        'slide_in_settle' => __('Slide In Settle', 'amadex'),
                                                        'bounce' => __('Bounce', 'amadex'),
                                                        'glow' => __('Glow', 'amadex'),
                                                        'neon_glow' => __('Neon Glow', 'amadex'),
                                                        'rotate' => __('Rotate', 'amadex'),
                                                        'blink' => __('Blink', 'amadex'),
                                                        'gradient_shift' => __('Gradient Shift', 'amadex'),
                                                        'float' => __('Float', 'amadex'),
                                                        'shake' => __('Shake', 'amadex'),
                                                        'zoom_in' => __('Zoom In', 'amadex'),
                                                        'glazing' => __('Glazing', 'amadex'),
                                                        'wave' => __('Wave', 'amadex'),
                                                        'number_counter' => __('Number Counter', 'amadex'),
                                                        'hover_microinteraction' => __('Hover Microinteraction', 'amadex'),
                                                        'seasonal_ornament_edges' => __('Seasonal Ornament Edges', 'amadex')
                                                    );
                                                    $selected_animations = ($editing_container && isset($editing_container['animations']) && is_array($editing_container['animations'])) ? $editing_container['animations'] : array();
                                                    foreach ($available_animations as $anim_key => $anim_label):
                                                    ?>
                                                        <label style="display: flex; align-items: center; cursor: pointer; position: relative;" data-animation-id="<?php echo esc_attr($anim_key); ?>" title="<?php echo esc_attr($anim_label); ?>">
                                                            <input type="checkbox" name="container_animations[]" value="<?php echo esc_attr($anim_key); ?>" <?php checked(in_array($anim_key, $selected_animations), true); ?> class="amadex-animation-checkbox" style="margin-right: 8px;">
                                                            <span><?php echo esc_html($anim_label); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                                                    <label for="animation_intensity" style="display: block; margin-bottom: 10px; font-weight: 600;"><?php _e('Animation Intensity', 'amadex'); ?>: <span id="animation_intensity_value"><?php echo esc_html(($editing_container && isset($editing_container['animation_intensity'])) ? intval($editing_container['animation_intensity']) : 50); ?>%</span></label>
                                                    <input type="range" id="animation_intensity" name="animation_intensity" min="0" max="100" step="1" value="<?php echo esc_attr(($editing_container && isset($editing_container['animation_intensity'])) ? intval($editing_container['animation_intensity']) : 50); ?>" style="width: 100%; max-width: 500px;">
                                                    <div style="display: flex; justify-content: space-between; width: 100%; max-width: 500px; margin-top: 5px; font-size: 11px; color: #666;">
                                                        <span><?php _e('Subtle (0%)', 'amadex'); ?></span>
                                                        <span><?php _e('Medium (50%)', 'amadex'); ?></span>
                                                        <span><?php _e('Maximum (100%)', 'amadex'); ?></span>
                                                    </div>
                                                    <p class="description" style="margin-top: 10px;"><?php _e('Control how prominent and noticeable animations are. Higher intensity makes animations more dramatic and eye-catching.', 'amadex'); ?></p>
                                                </div>

                                                <div style="margin-top: 20px; display: flex; gap: 20px; flex-wrap: wrap;">
                                                    <div>
                                                        <label for="animation_duration" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Duration', 'amadex'); ?>:</label>
                                                        <select id="animation_duration" name="animation_duration" style="width: 120px;">
                                                            <option value="0.5s" <?php selected(($editing_container && isset($editing_container['animation_duration'])) ? $editing_container['animation_duration'] : '2s', '0.5s'); ?>>0.5s</option>
                                                            <option value="1s" <?php selected(($editing_container && isset($editing_container['animation_duration'])) ? $editing_container['animation_duration'] : '', '1s'); ?>>1s</option>
                                                            <option value="2s" <?php selected(($editing_container && isset($editing_container['animation_duration'])) ? $editing_container['animation_duration'] : '', '2s'); ?>>2s (Default)</option>
                                                            <option value="3s" <?php selected(($editing_container && isset($editing_container['animation_duration'])) ? $editing_container['animation_duration'] : '', '3s'); ?>>3s</option>
                                                            <option value="infinite" <?php selected(($editing_container && isset($editing_container['animation_duration'])) ? $editing_container['animation_duration'] : '', 'infinite'); ?>>Infinite</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label for="animation_delay" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Delay', 'amadex'); ?>:</label>
                                                        <select id="animation_delay" name="animation_delay" style="width: 120px;">
                                                            <option value="0s" <?php selected(($editing_container && isset($editing_container['animation_delay'])) ? $editing_container['animation_delay'] : '0s', '0s'); ?>>0s (Default)</option>
                                                            <option value="0.5s" <?php selected(($editing_container && isset($editing_container['animation_delay'])) ? $editing_container['animation_delay'] : '', '0.5s'); ?>>0.5s</option>
                                                            <option value="1s" <?php selected(($editing_container && isset($editing_container['animation_delay'])) ? $editing_container['animation_delay'] : '', '1s'); ?>>1s</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label style="display: flex; align-items: center; cursor: pointer; margin-top: 25px;">
                                                            <input type="checkbox" id="animation_mobile_disabled" name="animation_mobile_disabled" value="1" <?php checked(($editing_container && isset($editing_container['animation_mobile_disabled'])) ? $editing_container['animation_mobile_disabled'] : 0, 1); ?> style="margin-right: 8px;">
                                                            <span style="font-weight: 600;"><?php _e('Disable on Mobile', 'amadex'); ?></span>
                                                        </label>
                                                        <p class="description" style="margin-top: 5px;"><?php _e('Uncheck animations on mobile devices for better performance.', 'amadex'); ?></p>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Colors & Styling Section -->
                        <tr class="amadex-drawer-header">
                            <td colspan="2">
                                <button type="button" class="amadex-drawer-toggle" data-drawer="colors-styling">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    <span class="dashicons dashicons-admin-appearance" style="margin-left: 8px; font-size: 18px;"></span>
                                    <strong style="margin-left: 8px;"><?php _e('Colors & Styling', 'amadex'); ?></strong>
                                </button>
                            </td>
                        </tr>
                        <tr class="amadex-drawer-content" data-drawer="colors-styling" style="display: none;">
                            <td colspan="2">
                                <table class="form-table" style="margin: 0;">
                                    <tr>
                                        <th scope="row"><label><?php _e('Container Background Color', 'amadex'); ?></label></th>
                                        <td>
                                            <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9;">
                                                <legend style="padding: 0 10px; font-weight: 600;"><?php _e('Color Type', 'amadex'); ?></legend>
                                                <div style="margin-top: 10px; display: flex; gap: 15px;">
                                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                                        <input type="radio" name="container_color_type" value="solid" <?php checked(($editing_container && isset($editing_container['container_color_type'])) ? $editing_container['container_color_type'] : 'default', 'solid'); ?> style="margin-right: 5px;">
                                                        <span><?php _e('Solid Color', 'amadex'); ?></span>
                                                    </label>
                                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                                        <input type="radio" name="container_color_type" value="gradient_2" <?php checked(($editing_container && isset($editing_container['container_color_type'])) ? $editing_container['container_color_type'] : '', 'gradient_2'); ?> style="margin-right: 5px;">
                                                        <span><?php _e('Gradient (2 Colors)', 'amadex'); ?></span>
                                                    </label>
                                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                                        <input type="radio" name="container_color_type" value="gradient_3" <?php checked(($editing_container && isset($editing_container['container_color_type'])) ? $editing_container['container_color_type'] : '', 'gradient_3'); ?> style="margin-right: 5px;">
                                                        <span><?php _e('Gradient (3 Colors)', 'amadex'); ?></span>
                                                    </label>
                                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                                        <input type="radio" name="container_color_type" value="default" <?php checked(($editing_container && isset($editing_container['container_color_type'])) ? $editing_container['container_color_type'] : 'default', 'default'); ?> style="margin-right: 5px;">
                                                        <span><?php _e('Default (Type-based)', 'amadex'); ?></span>
                                                    </label>
                                                </div>

                                                <div id="container_color_fields" style="margin-top: 20px; display: none;">
                                                    <div style="margin-bottom: 15px;">
                                                        <label for="color_picker_type" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Color Picker Type', 'amadex'); ?>:</label>
                                                        <select id="color_picker_type" name="color_picker_type" style="width: 200px;">
                                                            <option value="html5" <?php selected(($editing_container && isset($editing_container['color_picker_type'])) ? $editing_container['color_picker_type'] : 'html5', 'html5'); ?>><?php _e('HTML5 Color Picker', 'amadex'); ?></option>
                                                            <option value="wordpress" <?php selected(($editing_container && isset($editing_container['color_picker_type'])) ? $editing_container['color_picker_type'] : '', 'wordpress'); ?>><?php _e('WordPress Color Picker', 'amadex'); ?></option>
                                                        </select>
                                                    </div>

                                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                                                        <div id="color_primary_field">
                                                            <label for="container_color_primary" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Primary Color', 'amadex'); ?>:</label>
                                                            <input type="text" id="container_color_primary" name="container_color_primary" class="amadex-color-picker" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_color_primary'])) ? $editing_container['container_color_primary'] : '#0e7d3f'); ?>" data-default-color="#0e7d3f">
                                                        </div>
                                                        <div id="color_secondary_field" style="display: none;">
                                                            <label for="container_color_secondary" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Secondary Color', 'amadex'); ?>:</label>
                                                            <input type="text" id="container_color_secondary" name="container_color_secondary" class="amadex-color-picker" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_color_secondary'])) ? $editing_container['container_color_secondary'] : '#22af5c'); ?>" data-default-color="#22af5c">
                                                        </div>
                                                        <div id="color_tertiary_field" style="display: none;">
                                                            <label for="container_color_tertiary" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Tertiary Color', 'amadex'); ?>:</label>
                                                            <input type="text" id="container_color_tertiary" name="container_color_tertiary" class="amadex-color-picker" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_color_tertiary'])) ? $editing_container['container_color_tertiary'] : '#f97316'); ?>" data-default-color="#f97316">
                                                        </div>
                                                    </div>

                                                    <div style="margin-bottom: 15px;">
                                                        <label for="container_color_opacity" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Opacity', 'amadex'); ?>: <span id="color_opacity_value"><?php echo esc_html(($editing_container && isset($editing_container['container_color_opacity'])) ? intval($editing_container['container_color_opacity']) : 100); ?>%</span></label>
                                                        <input type="range" id="container_color_opacity" name="container_color_opacity" min="0" max="100" step="1" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_color_opacity'])) ? intval($editing_container['container_color_opacity']) : 100); ?>" style="width: 100%; max-width: 400px;">
                                                    </div>

                                                    <div id="gradient_controls" style="display: none;">
                                                        <div style="margin-bottom: 15px;">
                                                            <label for="container_gradient_direction" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Gradient Direction', 'amadex'); ?>:</label>
                                                            <select id="container_gradient_direction" name="container_gradient_direction" style="width: 200px; margin-bottom: 10px;">
                                                                <option value="to right" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : 'to right', 'to right'); ?>><?php _e('To Right →', 'amadex'); ?></option>
                                                                <option value="to bottom" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : '', 'to bottom'); ?>><?php _e('To Bottom ↓', 'amadex'); ?></option>
                                                                <option value="to left" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : '', 'to left'); ?>><?php _e('To Left ←', 'amadex'); ?></option>
                                                                <option value="to top" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : '', 'to top'); ?>><?php _e('To Top ↑', 'amadex'); ?></option>
                                                                <option value="to bottom right" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : '', 'to bottom right'); ?>><?php _e('Diagonal ↘', 'amadex'); ?></option>
                                                                <option value="to bottom left" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : '', 'to bottom left'); ?>><?php _e('Diagonal ↙', 'amadex'); ?></option>
                                                                <option value="to top right" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : '', 'to top right'); ?>><?php _e('Diagonal ↗', 'amadex'); ?></option>
                                                                <option value="to top left" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : '', 'to top left'); ?>><?php _e('Diagonal ↖', 'amadex'); ?></option>
                                                                <option value="custom" <?php selected(($editing_container && isset($editing_container['container_gradient_direction'])) ? $editing_container['container_gradient_direction'] : '', 'custom'); ?>><?php _e('Custom Angle', 'amadex'); ?></option>
                                                            </select>
                                                            <div id="gradient_angle_field" style="display: none; margin-top: 10px;">
                                                                <label for="container_gradient_angle" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Angle', 'amadex'); ?>: <span id="gradient_angle_value"><?php echo esc_html(($editing_container && isset($editing_container['container_gradient_angle'])) ? intval($editing_container['container_gradient_angle']) : 135); ?>°</span></label>
                                                                <input type="range" id="container_gradient_angle" name="container_gradient_angle" min="0" max="360" step="1" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_gradient_angle'])) ? intval($editing_container['container_gradient_angle']) : 135); ?>" style="width: 100%; max-width: 400px;">
                                                            </div>
                                                        </div>
                                                        <div id="gradient_stops_field" style="display: none;">
                                                            <label style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Color Stops (3-Color Gradient)', 'amadex'); ?>:</label>
                                                            <div style="display: flex; gap: 10px; align-items: center;">
                                                                <label style="display: flex; align-items: center; gap: 5px;">
                                                                    <span style="min-width: 60px;"><?php _e('Stop 1:', 'amadex'); ?></span>
                                                                    <input type="number" id="gradient_stop_1" name="gradient_stop_1" min="0" max="100" value="<?php echo esc_attr(($editing_container && isset($editing_container['gradient_stops'][0])) ? intval($editing_container['gradient_stops'][0]) : 0); ?>" style="width: 80px;">%
                                                                </label>
                                                                <label style="display: flex; align-items: center; gap: 5px;">
                                                                    <span style="min-width: 60px;"><?php _e('Stop 2:', 'amadex'); ?></span>
                                                                    <input type="number" id="gradient_stop_2" name="gradient_stop_2" min="0" max="100" value="<?php echo esc_attr(($editing_container && isset($editing_container['gradient_stops'][1])) ? intval($editing_container['gradient_stops'][1]) : 50); ?>" style="width: 80px;">%
                                                                </label>
                                                                <label style="display: flex; align-items: center; gap: 5px;">
                                                                    <span style="min-width: 60px;"><?php _e('Stop 3:', 'amadex'); ?></span>
                                                                    <input type="number" id="gradient_stop_3" name="gradient_stop_3" min="0" max="100" value="<?php echo esc_attr(($editing_container && isset($editing_container['gradient_stops'][2])) ? intval($editing_container['gradient_stops'][2]) : 100); ?>" style="width: 80px;">%
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php _e('Text Color', 'amadex'); ?></label></th>
                                        <td>
                                            <fieldset style="border: 1px solid #ddd; padding: 15px; border-radius: 4px; background: #f9f9f9;">
                                                <div style="margin-bottom: 15px;">
                                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                                        <input type="checkbox" id="text_color_auto" name="text_color_auto" value="1" <?php checked(($editing_container && isset($editing_container['text_color_auto'])) ? $editing_container['text_color_auto'] : 1, 1); ?> style="margin-right: 8px;">
                                                        <span style="font-weight: 600;"><?php _e('Auto-Adjust for Contrast', 'amadex'); ?></span>
                                                    </label>
                                                    <p class="description" style="margin-top: 5px;"><?php _e('Automatically adjust text color for optimal readability based on background.', 'amadex'); ?></p>
                                                </div>
                                                <div id="text_color_manual_field" style="display: none;">
                                                    <div style="margin-bottom: 15px;">
                                                        <label style="display: flex; align-items: center; cursor: pointer;">
                                                            <input type="checkbox" id="text_colors_linked" name="text_colors_linked" value="1" <?php checked(($editing_container && isset($editing_container['text_colors_linked'])) ? $editing_container['text_colors_linked'] : 0, 1); ?> style="margin-right: 8px;">
                                                            <span style="font-weight: 600;"><?php _e('Link Heading and Body Colors', 'amadex'); ?></span>
                                                        </label>
                                                        <p class="description" style="margin-top: 5px;"><?php _e('When enabled, changing one color will automatically update the other.', 'amadex'); ?></p>
                                                    </div>
                                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                                        <div>
                                                            <label for="container_heading_color" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Heading Color (Title)', 'amadex'); ?>:</label>
                                                            <input type="text" id="container_heading_color" name="container_heading_color" class="amadex-color-picker" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_heading_color'])) ? $editing_container['container_heading_color'] : '#111827'); ?>" data-default-color="#111827">
                                                            <p class="description" style="margin-top: 5px; font-size: 12px;"><?php _e('Color for h3 titles. If empty, auto-calculates from background.', 'amadex'); ?></p>
                                                        </div>
                                                        <div>
                                                            <label for="container_body_color" style="display: block; margin-bottom: 5px; font-weight: 600;"><?php _e('Body Text Color (Description)', 'amadex'); ?>:</label>
                                                            <input type="text" id="container_body_color" name="container_body_color" class="amadex-color-picker" value="<?php echo esc_attr(($editing_container && isset($editing_container['container_body_color'])) ? $editing_container['container_body_color'] : '#6b7280'); ?>" data-default-color="#6b7280">
                                                            <p class="description" style="margin-top: 5px; font-size: 12px;"><?php _e('Color for descriptions. If empty, auto-calculates from background.', 'amadex'); ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Media & Links Section -->
                        <tr class="amadex-drawer-header">
                            <td colspan="2">
                                <button type="button" class="amadex-drawer-toggle" data-drawer="media-links">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    <span class="dashicons dashicons-admin-media" style="margin-left: 8px; font-size: 18px;"></span>
                                    <strong style="margin-left: 8px;"><?php _e('Media & Links', 'amadex'); ?></strong>
                                </button>
                            </td>
                        </tr>
                        <tr class="amadex-drawer-content" data-drawer="media-links" style="display: none;">
                            <td colspan="2">
                                <table class="form-table" style="margin: 0;">
                                    <tr>
                                        <th scope="row"><label for="container_image_url"><?php _e('Image URL', 'amadex'); ?></label></th>
                                        <td>
                                            <input type="url" id="container_image_url" name="container_image_url" class="regular-text" value="<?php echo $editing_container ? esc_url($editing_container['image_url'] ?? '') : ''; ?>">
                                            <p class="description"><?php _e('Optional image URL for the container (for product cross-sell or ads).', 'amadex'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label for="container_link_url"><?php _e('Link URL', 'amadex'); ?></label></th>
                                        <td>
                                            <input type="url" id="container_link_url" name="container_link_url" class="regular-text" value="<?php echo $editing_container ? esc_url($editing_container['link_url'] ?? '') : ''; ?>">
                                            <p class="description"><?php _e('URL where the button/link should navigate (optional).', 'amadex'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <!-- Display Settings Section -->
                        <tr class="amadex-drawer-header">
                            <td colspan="2">
                                <button type="button" class="amadex-drawer-toggle" data-drawer="display-settings">
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                    <span class="dashicons dashicons-admin-settings" style="margin-left: 8px; font-size: 18px;"></span>
                                    <strong style="margin-left: 8px;"><?php _e('Display Settings', 'amadex'); ?></strong>
                                </button>
                            </td>
                        </tr>
                        <tr class="amadex-drawer-content" data-drawer="display-settings" style="display: none;">
                            <td colspan="2">
                                <table class="form-table" style="margin: 0;">
                                    <tr>
                                        <th scope="row"><label for="insertion_interval"><?php _e('Insertion Interval', 'amadex'); ?></label></th>
                                        <td>
                                            <input type="number" id="insertion_interval" name="insertion_interval" min="1" max="20" class="small-text" value="<?php echo $editing_container ? esc_attr($editing_container['insertion_interval'] ?? 4) : 4; ?>">
                                            <p class="description"><?php _e('Show this container after every N flight cards (e.g., 4 = after every 4th flight).', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="insertion_frequency"><?php _e('Insertion Frequency', 'amadex'); ?></label></th>
                                        <td>
                                            <input type="number" id="insertion_frequency" name="insertion_frequency" min="0" max="1" step="0.05" class="small-text" value="<?php echo $editing_container ? esc_attr($editing_container['insertion_frequency'] ?? 0.25) : 0.25; ?>">
                                            <p class="description"><?php _e('Probability of showing (0.0 to 1.0). 0.25 = 25% chance at each insertion point.', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="min_flights_viewed"><?php _e('Minimum Flights Viewed', 'amadex'); ?></label></th>
                                        <td>
                                            <input type="number" id="min_flights_viewed" name="min_flights_viewed" min="0" max="10" class="small-text" value="<?php echo $editing_container ? esc_attr($editing_container['min_flights_viewed'] ?? 2) : 2; ?>">
                                            <p class="description"><?php _e('Minimum number of flight cards user must view before showing this container.', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="max_appearances"><?php _e('Maximum Appearances', 'amadex'); ?></label></th>
                                        <td>
                                            <input type="number" id="max_appearances" name="max_appearances" min="0" max="100" class="small-text" value="<?php echo $editing_container ? esc_attr($editing_container['max_appearances'] ?? 0) : 0; ?>">
                                            <p class="description"><?php _e('Maximum number of times this container can appear in flight results. Set to 0 for unlimited appearances. This prevents containers from appearing too many times when multiple are enabled.', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="display_order"><?php _e('Display Order', 'amadex'); ?></label></th>
                                        <td>
                                            <input type="number" id="display_order" name="display_order" min="0" class="small-text" value="<?php echo $editing_container ? esc_attr($editing_container['display_order'] ?? 0) : 0; ?>">
                                            <p class="description"><?php _e('Lower numbers appear first when multiple containers compete for same position.', 'amadex'); ?></p>
                                        </td>
                                    </tr>

                                    <tr>
                                        <th scope="row"><label for="container_enabled"><?php _e('Status', 'amadex'); ?></label></th>
                                        <td>
                                            <label>
                                                <input type="checkbox" id="container_enabled" name="container_enabled" value="1" <?php checked($editing_container && isset($editing_container['enabled']) ? $editing_container['enabled'] : 1, 1); ?>>
                                                <?php _e('Enabled', 'amadex'); ?>
                                            </label>
                                            <p class="description"><?php _e('Only enabled containers will be displayed on flight results pages.', 'amadex'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php echo $editing_container ? __('Update Container', 'amadex') : __('Add Container', 'amadex'); ?></button>
                        <?php if ($editing_container): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=amadex-settings&tab=promotional_containers')); ?>" class="button"><?php _e('Cancel', 'amadex'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>
            </div>

            <!-- Existing Containers List -->
            <div class="amadex-promo-containers-list-wrapper">
                <h3><?php _e('Existing Promotional Containers', 'amadex'); ?></h3>
                <?php if (empty($containers)): ?>
                    <p><?php _e('No promotional containers have been created yet.', 'amadex'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 5%;"><?php _e('Order', 'amadex'); ?></th>
                                <th style="width: 10%;"><?php _e('Type', 'amadex'); ?></th>
                                <th style="width: 20%;"><?php _e('Title', 'amadex'); ?></th>
                                <th style="width: 15%;"><?php _e('Width', 'amadex'); ?></th>
                                <th style="width: 8%;"><?php _e('Interval', 'amadex'); ?></th>
                                <th style="width: 8%;"><?php _e('Frequency', 'amadex'); ?></th>
                                <th style="width: 8%;"><?php _e('Max Times', 'amadex'); ?></th>
                                <th style="width: 8%;"><?php _e('Status', 'amadex'); ?></th>
                                <th style="width: 20%;"><?php _e('Actions', 'amadex'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Sort by display_order
                            uasort($containers, function ($a, $b) {
                                $order_a = isset($a['display_order']) ? intval($a['display_order']) : 0;
                                $order_b = isset($b['display_order']) ? intval($b['display_order']) : 0;
                                return $order_a <=> $order_b;
                            });

                            foreach ($containers as $container): ?>
                                <tr>
                                    <td><?php echo esc_html($container['display_order'] ?? 0); ?></td>
                                    <td>
                                        <?php
                                        $type_labels = array(
                                            'price_alert' => 'Price Alert',
                                            'airline_ad' => 'Airline Ad',
                                            'product_cross_sell' => 'Product',
                                            'callback' => 'Callback',
                                            'ad' => 'Ad Slot'
                                        );
                                        echo esc_html($type_labels[$container['type']] ?? $container['type']);
                                        ?>
                                    </td>
                                    <td><strong><?php echo esc_html($container['title']); ?></strong></td>
                                    <td>
                                        <?php
                                        if (isset($container['container_width_value']) && isset($container['container_width_unit'])) {
                                            $widthValue = $container['container_width_value'];
                                            $widthUnit = $container['container_width_unit'];
                                            $heightValue = $container['container_height_value'] ?? 'auto';
                                            $heightUnit = $container['container_height_unit'] ?? 'auto';
                                            echo esc_html($widthValue . $widthUnit . ' × ' . ($heightValue !== 'auto' ? $heightValue . ($heightUnit !== 'auto' ? $heightUnit : '') : 'auto'));
                                        } else {
                                            // Legacy support
                                            echo esc_html(ucfirst($container['container_width'] ?? 'full'));
                                        }
                                        ?>
                                        <?php if (isset($container['animations']) && is_array($container['animations']) && count($container['animations']) > 0): ?>
                                            <br><small style="color: #666;"><?php echo esc_html(count($container['animations']) . ' animation' . (count($container['animations']) > 1 ? 's' : '')); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($container['insertion_interval'] ?? 4); ?></td>
                                    <td><?php echo esc_html(($container['insertion_frequency'] ?? 0.25) * 100); ?>%</td>
                                    <td><?php
                                        $maxAppearances = isset($container['max_appearances']) ? intval($container['max_appearances']) : 0;
                                        echo $maxAppearances > 0 ? esc_html($maxAppearances) : __('Unlimited', 'amadex');
                                        ?></td>
                                    <td>
                                        <?php if (isset($container['enabled']) && $container['enabled']): ?>
                                            <span style="color: green;"><?php _e('Enabled', 'amadex'); ?></span>
                                        <?php else: ?>
                                            <span style="color: red;"><?php _e('Disabled', 'amadex'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=amadex-settings&tab=promotional_containers&edit_container=' . urlencode($container['id']))); ?>" class="button button-small"><?php _e('Edit', 'amadex'); ?></a>
                                        <form method="post" action="" style="display: inline;" onsubmit="return confirm('<?php _e('Are you sure you want to delete this promotional container?', 'amadex'); ?>');">
                                            <?php wp_nonce_field('amadex_promo_containers', 'amadex_promo_container_nonce'); ?>
                                            <input type="hidden" name="amadex_promo_container_action" value="delete">
                                            <input type="hidden" name="container_id" value="<?php echo esc_attr($container['id']); ?>">
                                            <button type="submit" class="button button-small" style="color: #b32d2e;"><?php _e('Delete', 'amadex'); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <script type="text/javascript">
            // Helper function to escape HTML (for XSS prevention)
            function escapeHtml(text) {
                if (typeof text !== 'string') return '';
                var map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) {
                    return map[m];
                });
            }

            // Helper function to convert hex to rgba (MUST BE DEFINED FIRST)
            function hexToRgba(hex, alpha) {
                if (!hex || hex.length < 7) return 'rgba(0, 0, 0, ' + alpha + ')';
                var r = parseInt(hex.slice(1, 3), 16);
                var g = parseInt(hex.slice(3, 5), 16);
                var b = parseInt(hex.slice(5, 7), 16);
                return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
            }

            // Helper function to calculate contrast color (light or dark) (MUST BE DEFINED FIRST)
            function getContrastColor(hexColor) {
                if (!hexColor || hexColor.length < 7) return '#111827';
                var r = parseInt(hexColor.slice(1, 3), 16);
                var g = parseInt(hexColor.slice(3, 5), 16);
                var b = parseInt(hexColor.slice(5, 7), 16);
                // Calculate relative luminance
                var luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                // Return dark color for light backgrounds, light color for dark backgrounds
                return luminance > 0.5 ? '#111827' : '#ffffff';
            }

            // Helper function to get color value from picker (handles WordPress and HTML5)
            function getColorPickerValue(selector) {
                var $picker = jQuery(selector);
                if ($picker.length === 0) return null;

                // Check if WordPress color picker is available and initialized
                if (typeof wp !== 'undefined' && wp.colorPicker) {
                    // Check if it's a WordPress picker (has wp-picker-container wrapper)
                    var $wpContainer = $picker.closest('.wp-picker-container');
                    if ($wpContainer.length > 0) {
                        var $wpInput = $wpContainer.find('.wp-color-picker');
                        if ($wpInput.length > 0) {
                            try {
                                // Try to get color from WordPress picker API
                                var wpColor = $wpInput.wpColorPicker('color');
                                if (wpColor) return wpColor;
                            } catch (e) {
                                // If API fails, fall back to value
                                console.warn('wpColorPicker API error, using value:', e);
                            }
                            // Fallback to input value
                            var inputValue = $wpInput.val();
                            if (inputValue) return inputValue;
                        }
                    }
                }

                // Check if it's a direct WordPress color picker input
                if ($picker.hasClass('wp-color-picker')) {
                    try {
                        if (typeof wp !== 'undefined' && wp.colorPicker) {
                            var directColor = $picker.wpColorPicker('color');
                            if (directColor) return directColor;
                        }
                    } catch (e) {
                        // Fall through to value
                    }
                }

                // HTML5 color picker or regular input - get value directly
                var value = $picker.val();
                return value || null;
            }

            /**
             * Template Compatibility Mapping
             * Defines which templates are compatible with which container types
             */
            var CONTAINER_TYPE_TEMPLATE_COMPATIBILITY = {
                'price_alert': ['native_inline_card', 'deal_countdown_timer', 'travel_pass_verification', 'flight_status_alert'],
                'airline_ad': ['native_inline_card', 'hero_spotlight', 'video_promo_tile', 'two_column_feature', 'promo_carousel'],
                'product_cross_sell': ['native_inline_card', 'two_column_feature', 'video_promo_tile', 'promo_carousel', 'hero_spotlight'],
                'callback': ['native_inline_card', 'hero_spotlight'],
                'ad': ['native_inline_card', 'video_promo_tile', 'travel_pass_verification'],
                'travelaygent_spotlight': ['travelaygent_profile_card', 'agent_comparison_grid', 'three_agent_cards', 'native_inline_card', 'hero_spotlight'],
                'trust_badge': ['native_inline_card', 'trust_metrics_banner', 'travel_pass_verification'],
                'urgency_scarcity': ['native_inline_card', 'deal_countdown_timer', 'travel_pass_verification', 'flight_status_alert'],
                'deal_highlight': ['deal_countdown_timer', 'native_inline_card', 'hero_spotlight', 'two_column_feature', 'promo_carousel'],
                'comparison_table': ['two_column_feature', 'native_inline_card', 'agent_comparison_grid', 'trust_metrics_banner'],
                'social_proof': ['trust_metrics_banner', 'native_inline_card', 'three_agent_cards', 'two_column_feature']
            };

            /**
             * Filter templates based on container type compatibility
             */
            function amadexFilterTemplatesByContainerType(containerType) {
                var $templateSelect = jQuery('#container_template_id');
                var currentValue = $templateSelect.val();
                var compatibleTemplates = containerType && CONTAINER_TYPE_TEMPLATE_COMPATIBILITY[containerType] ?
                    CONTAINER_TYPE_TEMPLATE_COMPATIBILITY[containerType] : [];

                // Always include "None (Use Legacy Layout)"
                compatibleTemplates.push('');

                // Hide/show template options
                $templateSelect.find('option').each(function() {
                    var $option = jQuery(this);
                    var optionValue = $option.val();

                    // Always show "None (Use Legacy Layout)" option
                    if (optionValue === '') {
                        $option.show();
                        return;
                    }

                    // Show optgroup labels
                    if ($option.is('optgroup')) {
                        return;
                    }

                    // Show if compatible or if it's an optgroup (which contains options)
                    if (compatibleTemplates.indexOf(optionValue) !== -1) {
                        $option.show();
                    } else {
                        $option.hide();
                    }
                });

                // If current template is not compatible, reset to "None" and show notice
                if (currentValue && compatibleTemplates.indexOf(currentValue) === -1) {
                    $templateSelect.val('');
                    amadexUpdateTemplateFields('');

                    // Show notice
                    var $notice = jQuery('<div class="notice notice-warning inline" style="margin: 10px 0; padding: 10px; background: #fff8e5; border-left: 4px solid #f0b849;"><p><strong>' +
                        'Notice:</strong> The previously selected template is not compatible with the selected container type. Template has been reset to "None (Use Legacy Layout)".</p></div>');

                    // Remove any existing notice
                    jQuery('#template_compatibility_notice').remove();

                    // Insert notice after template description
                    $notice.attr('id', 'template_compatibility_notice');
                    var $templateDescription = jQuery('#template_description');
                    if ($templateDescription.length) {
                        $notice.insertAfter($templateDescription);
                    } else {
                        $notice.insertAfter($templateSelect);
                    }

                    // Auto-remove notice after 5 seconds
                    setTimeout(function() {
                        $notice.fadeOut(300, function() {
                            jQuery(this).remove();
                        });
                    }, 5000);
                }
            }

            function amadexUpdatePromoFields(selectedType) {
                // Hide all type-specific fields
                jQuery('.promo-type-fields').hide();

                // Show fields for selected type
                if (selectedType === 'price_alert') {
                    jQuery('#price_alert_fields').show();
                } else if (selectedType === 'callback') {
                    jQuery('#callback_fields').show();
                } else if (selectedType === 'airline_ad') {
                    jQuery('#airline_ad_fields').show();
                } else if (selectedType === 'travelaygent_spotlight') {
                    // Phase 2: TravelayGent Spotlight
                    jQuery('#travelaygent_spotlight_fields').show();
                } else if (selectedType === 'trust_badge') {
                    // Phase 2: Trust Badge Banner
                    jQuery('#trust_badge_fields').show();
                } else if (selectedType === 'urgency_scarcity') {
                    // Phase 2: Urgency/Scarcity Alert
                    jQuery('#urgency_scarcity_fields').show();
                } else if (selectedType === 'deal_highlight') {
                    // Phase 2: Deal Highlight Card
                    jQuery('#deal_highlight_fields').show();
                } else if (selectedType === 'comparison_table') {
                    // Phase 2: Comparison Table Banner
                    jQuery('#comparison_table_fields').show();
                } else if (selectedType === 'social_proof') {
                    // Phase 2: Social Proof Banner
                    jQuery('#social_proof_fields').show();
                }

                // Filter templates based on container type compatibility
                amadexFilterTemplatesByContainerType(selectedType);

                // Update preview when type changes
                amadexUpdatePromoPreview();
            }

            // Initialize template filtering on page load
            jQuery(document).ready(function($) {
                var $containerType = $('#container_type');
                if ($containerType.length && $containerType.val()) {
                    amadexFilterTemplatesByContainerType($containerType.val());
                }
            });

            // Phase 2: Add comparison item handler
            jQuery(document).ready(function($) {
                $('#add_comparison_item').on('click', function() {
                    var $container = $('#comparison_items_container');
                    var $newItem = $('<div style="margin-bottom: 10px;"><input type="text" name="comparison_items[]" class="regular-text" placeholder="' + ($container.children().length + 1) + '"></div>');
                    $container.append($newItem);
                });
            });

            /**
             * Current device settings for preview
             */
            var amadexCurrentDevice = {
                type: 'desktop',
                width: 1200,
                height: 800
            };

            /**
             * Update promotional container preview using shared renderer in iframe
             * This ensures 1:1 parity with frontend rendering
             */
            function amadexUpdatePromoPreview() {
                try {
                    var $iframe = jQuery('#amadex-preview-iframe');
                    var $loading = jQuery('#amadex-preview-loading');
                    var $container = jQuery('#amadex-preview-iframe-container');

                    if ($iframe.length === 0) {
                        console.warn('Amadex Preview: Iframe not found');
                        return;
                    }

                    // Check if shared renderer is available
                    if (typeof AmadexPromoRenderer === 'undefined' || typeof AmadexPromoRenderer.renderPromotionalContainer !== 'function') {
                        console.warn('Amadex Preview: Shared renderer not available, using fallback');
                        amadexUpdatePromoPreviewFallback();
                        return;
                    }

                    // Show loading
                    $loading.show();
                    $iframe.hide();

                    // Build container config object from form fields (matches frontend structure)
                    var containerConfig = {
                        type: jQuery('#container_type').val() || 'price_alert',
                        template_id: jQuery('#container_template_id').val() || null,
                        container_type_id: jQuery('#container_type_id').val() || null,
                        title: jQuery('#container_title').val() || 'Preview Container',
                        description: jQuery('#container_description').val() || 'This is a preview of your promotional container with selected animations and dimensions.',
                        button_text: jQuery('#container_button_text').val() || 'Track prices',
                        image_url: jQuery('#container_image_url').val() || '',
                        link_url: jQuery('#container_link_url').val() || '',
                        additional_data: {},

                        // Dimensions
                        container_width_value: jQuery('#container_width_value').val(),
                        container_width_unit: jQuery('#container_width_unit').val() || '%',
                        container_height_value: jQuery('#container_height_value').val(),
                        container_height_unit: jQuery('#container_height_unit').val() || 'auto',

                        // Min/Max Dimensions
                        container_min_width_value: jQuery('#container_min_width_value').val() || '',
                        container_min_width_unit: jQuery('#container_min_width_unit').val() || 'px',
                        container_max_width_value: jQuery('#container_max_width_value').val() || '',
                        container_max_width_unit: jQuery('#container_max_width_unit').val() || 'px',
                        container_min_height_value: jQuery('#container_min_height_value').val() || '',
                        container_min_height_unit: jQuery('#container_min_height_unit').val() || 'px',
                        container_max_height_value: jQuery('#container_max_height_value').val() || '',
                        container_max_height_unit: jQuery('#container_max_height_unit').val() || 'px',

                        // Padding
                        container_padding_mode: jQuery('input[name="container_padding_mode"]:checked').val() || 'uniform',
                        container_padding_all: jQuery('#container_padding_all').val() || '',
                        container_padding_all_unit: jQuery('#container_padding_all_unit').val() || 'px',
                        container_padding_x: jQuery('#container_padding_x').val() || '',
                        container_padding_x_unit: jQuery('#container_padding_x_unit').val() || 'px',
                        container_padding_y: jQuery('#container_padding_y').val() || '',
                        container_padding_y_unit: jQuery('#container_padding_y_unit').val() || 'px',
                        container_padding_top: jQuery('#container_padding_top').val() || '',
                        container_padding_right: jQuery('#container_padding_right').val() || '',
                        container_padding_bottom: jQuery('#container_padding_bottom').val() || '',
                        container_padding_left: jQuery('#container_padding_left').val() || '',

                        // Gap (Grid Spacing)
                        container_gap_column: jQuery('#container_gap_column').val() || '',
                        container_gap_column_unit: jQuery('#container_gap_column_unit').val() || 'px',
                        container_gap_row: jQuery('#container_gap_row').val() || '',
                        container_gap_row_unit: jQuery('#container_gap_row_unit').val() || 'px',

                        // Border Radius
                        container_border_radius: jQuery('#container_border_radius').val() || '',
                        container_border_radius_unit: jQuery('#container_border_radius_unit').val() || 'px',

                        // Compactness & Typography
                        container_compactness: parseFloat(jQuery('#container_compactness').val()) || 50,
                        container_typography_scale: parseFloat(jQuery('#container_typography_scale').val()) || 1.0,

                        // Animations
                        animations: [],
                        animation_duration: jQuery('#animation_duration').val() || '2s',
                        animation_delay: jQuery('#animation_delay').val() || '0s',
                        animation_mobile_disabled: jQuery('#animation_mobile_disabled').is(':checked'),
                        animation_intensity: parseFloat(jQuery('#animation_intensity').val()) || 50,

                        // Colors
                        container_color_type: jQuery('input[name="container_color_type"]:checked').val() || 'default',
                        container_color_primary: getColorPickerValue('#container_color_primary') || '#0e7d3f',
                        container_color_secondary: getColorPickerValue('#container_color_secondary') || '#22af5c',
                        container_color_tertiary: getColorPickerValue('#container_color_tertiary') || '#f97316',
                        container_color_opacity: parseFloat(jQuery('#container_color_opacity').val()) || 100,
                        container_gradient_direction: jQuery('#container_gradient_direction').val() || 'to right',
                        container_gradient_angle: parseInt(jQuery('#container_gradient_angle').val()) || 135,
                        gradient_stops: [
                            parseInt(jQuery('#gradient_stop_1').val()) || 0,
                            parseInt(jQuery('#gradient_stop_2').val()) || 50,
                            parseInt(jQuery('#gradient_stop_3').val()) || 100
                        ],

                        // Text colors
                        text_color_auto: jQuery('#text_color_auto').is(':checked'),
                        container_heading_color: getColorPickerValue('#container_heading_color') || '',
                        container_body_color: getColorPickerValue('#container_body_color') || '',
                        text_colors_linked: jQuery('#text_colors_linked').is(':checked')
                    };

                    // Get selected animations
                    jQuery('input[name="container_animations[]"]:checked').each(function() {
                        containerConfig.animations.push(jQuery(this).val());
                    });

                    // Get current device type from active device button
                    var currentDevice = 'desktop';
                    var $activeDeviceTab = jQuery('.amadex-device-tab.active');
                    if ($activeDeviceTab.length) {
                        currentDevice = $activeDeviceTab.data('device') || 'desktop';
                    } else {
                        // Fallback: determine from amadexCurrentDevice
                        var deviceWidth = amadexCurrentDevice.width || 1200;
                        if (deviceWidth <= 768) {
                            currentDevice = 'mobile';
                        } else if (deviceWidth <= 1024) {
                            currentDevice = 'tablet';
                        } else {
                            currentDevice = 'desktop';
                        }
                    }

                    // Get device-specific dimensions and min/max constraints
                    containerConfig.dimensions = {};
                    var devices = ['desktop', 'tablet', 'mobile'];
                    devices.forEach(function(device) {
                        containerConfig.dimensions[device] = {
                            width_value: jQuery('#container_' + device + '_width_value').val() || '',
                            width_unit: jQuery('#container_' + device + '_width_unit').val() || '%',
                            height_value: jQuery('#container_' + device + '_height_value').val() || '',
                            height_unit: jQuery('#container_' + device + '_height_unit').val() || 'auto',
                            min_width_value: jQuery('#container_' + device + '_min_width_value').val() || '',
                            min_width_unit: jQuery('#container_' + device + '_min_width_unit').val() || 'px',
                            max_width_value: jQuery('#container_' + device + '_max_width_value').val() || '',
                            max_width_unit: jQuery('#container_' + device + '_max_width_unit').val() || 'px',
                            min_height_value: jQuery('#container_' + device + '_min_height_value').val() || '',
                            min_height_unit: jQuery('#container_' + device + '_min_height_unit').val() || 'px',
                            max_height_value: jQuery('#container_' + device + '_max_height_value').val() || '',
                            max_height_unit: jQuery('#container_' + device + '_max_height_unit').val() || 'px'
                        };
                    });

                    // Set current device for renderer
                    containerConfig.current_device = currentDevice;

                    // Get type-specific additional data
                    var containerType = containerConfig.type;
                    if (containerType === 'price_alert') {
                        containerConfig.additional_data.email_placeholder = jQuery('#email_placeholder').val() || 'Enter your email';
                    } else if (containerType === 'callback') {
                        containerConfig.additional_data.phone_placeholder = jQuery('#phone_placeholder').val() || 'Enter your phone number';
                    } else if (containerType === 'airline_ad') {
                        containerConfig.additional_data.airline_logo_url = jQuery('#airline_logo_url').val() || '';
                        containerConfig.additional_data.offer_text = jQuery('#offer_text').val() || '';
                    }

                    // Use shared renderer to generate HTML (SAME as frontend)
                    // Pass device parameter for device-specific rendering
                    var previewHtml = AmadexPromoRenderer.renderPromotionalContainer(containerConfig, 'preview_' + Date.now(), currentDevice);

                    // Get CSS file URL (frontend CSS for accurate preview)
                    var cssUrl = '<?php echo esc_js(AMADEX_URL . 'assets/css/amadex.css'); ?>';

                    // Create complete HTML document for iframe with media query simulation
                    var deviceWidth = amadexCurrentDevice.width;
                    var deviceHeight = amadexCurrentDevice.height;

                    // Determine device type for media query simulation
                    var isMobile = deviceWidth <= 768;
                    var isTablet = deviceWidth > 768 && deviceWidth <= 1024;
                    var isDesktop = deviceWidth > 1024;

                    var iframeHtml = '<!DOCTYPE html><html><head>' +
                        '<meta charset="UTF-8">' +
                        '<meta name="viewport" content="width=' + deviceWidth + ', initial-scale=1.0, maximum-scale=1.0, user-scalable=no">' +
                        '<title>Preview</title>' +
                        '<link rel="stylesheet" href="' + cssUrl + '?v=<?php echo esc_js(AMADEX_VERSION); ?>">' +
                        '<style>' +
                        'body { margin: 0; padding: 20px; background: #f5f5f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; overflow-x: hidden; }' +
                        '#amadex-preview-wrapper { width: 100%; max-width: 100%; box-sizing: border-box; }' +
                        // Media query simulation - matches frontend CSS breakpoints
                        '@media (max-width: 768px) { ' +
                        '  .amadex-promotional-container { width: 100% !important; margin-left: 0 !important; margin-right: 0 !important; }' +
                        '  .amadex-promotional-container.amadex-promo-width-compact,' +
                        '  .amadex-promotional-container.amadex-promo-width-mini { width: 100% !important; }' +
                        '  .amadex-promo-content { flex-direction: column !important; align-items: stretch !important; gap: 15px !important; }' +
                        '  .amadex-promo-type-price_alert .amadex-promo-form { margin-left: 0 !important; flex-direction: column !important; }' +
                        '  .amadex-promo-email-input,' +
                        '  .amadex-promo-phone-input { min-width: 100% !important; width: 100% !important; }' +
                        '  .amadex-promo-button { width: 100% !important; }' +
                        '  .amadex-promo-image,' +
                        '  .amadex-promo-airline-logo { align-self: center !important; }' +
                        '}' +
                        // Additional responsive styles for very small screens
                        '@media (max-width: 480px) { ' +
                        '  body { padding: 10px !important; }' +
                        '  .amadex-promo-content { padding: 15px !important; }' +
                        '}' +
                        '</style>' +
                        '</head><body>' +
                        '<div id="amadex-preview-wrapper">' + previewHtml + '</div>' +
                        '</body></html>';

                    // Update iframe container width (height will be set dynamically after content loads)
                    $container.css({
                        'width': deviceWidth + 'px',
                        'max-width': '100%',
                        'height': 'auto'
                    });

                    // Write HTML to iframe
                    var iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
                    iframeDoc.open();
                    iframeDoc.write(iframeHtml);
                    iframeDoc.close();

                    // Function to measure and adjust container height based on content
                    function adjustPreviewHeight() {
                        try {
                            var iframeDoc = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
                            if (!iframeDoc || !iframeDoc.body) {
                                return;
                            }

                            // Find the promotional container element inside the iframe
                            var promoContainer = iframeDoc.querySelector('.amadex-promotional-container');
                            var previewWrapper = iframeDoc.getElementById('amadex-preview-wrapper');

                            var contentHeight = 0;

                            if (promoContainer) {
                                // Get the actual rendered height of the promotional container
                                // Include margins, padding, and borders
                                var rect = promoContainer.getBoundingClientRect();
                                var computedStyle = iframeDoc.defaultView.getComputedStyle(promoContainer);
                                var marginTop = parseFloat(computedStyle.marginTop) || 0;
                                var marginBottom = parseFloat(computedStyle.marginBottom) || 0;

                                // Use the larger of offsetHeight (includes padding/border) or getBoundingClientRect height
                                contentHeight = Math.max(
                                    promoContainer.offsetHeight,
                                    rect.height
                                ) + marginTop + marginBottom;
                            } else if (previewWrapper) {
                                // Fallback: measure the wrapper
                                contentHeight = previewWrapper.offsetHeight || previewWrapper.scrollHeight;
                            } else {
                                // Last resort: measure body
                                contentHeight = iframeDoc.body.scrollHeight || iframeDoc.body.offsetHeight;
                            }

                            // Add padding for the iframe body (20px top/bottom = 40px total)
                            var bodyPadding = 40;
                            var totalHeight = contentHeight + bodyPadding;

                            // Respect manual height settings if set
                            var manualHeightValue = jQuery('#container_height_value').val();
                            var manualHeightUnit = jQuery('#container_height_unit').val() || 'auto';
                            var manualMinHeightValue = jQuery('#container_min_height_value').val();
                            var manualMinHeightUnit = jQuery('#container_min_height_unit').val() || 'px';
                            var manualMaxHeightValue = jQuery('#container_max_height_value').val();
                            var manualMaxHeightUnit = jQuery('#container_max_height_unit').val() || 'px';

                            // If manual height is set and not 'auto', use it (convert to px if needed)
                            if (manualHeightValue && manualHeightUnit !== 'auto') {
                                var manualHeightPx = manualHeightValue;
                                if (manualHeightUnit === 'rem') {
                                    manualHeightPx = manualHeightValue * 16; // Approximate rem to px
                                } else if (manualHeightUnit === 'em') {
                                    manualHeightPx = manualHeightValue * 16; // Approximate em to px
                                } else if (manualHeightUnit === '%') {
                                    // For percentage, calculate based on device height
                                    manualHeightPx = (manualHeightValue / 100) * deviceHeight;
                                }
                                totalHeight = Math.max(totalHeight, manualHeightPx);
                            }

                            // Apply min-height constraint if set
                            if (manualMinHeightValue) {
                                var minHeightPx = parseFloat(manualMinHeightValue);
                                if (manualMinHeightUnit === 'rem') {
                                    minHeightPx = minHeightPx * 16;
                                } else if (manualMinHeightUnit === 'em') {
                                    minHeightPx = minHeightPx * 16;
                                } else if (manualMinHeightUnit === '%') {
                                    minHeightPx = (minHeightPx / 100) * deviceHeight;
                                }
                                totalHeight = Math.max(totalHeight, minHeightPx);
                            }

                            // Apply max-height constraint if set
                            if (manualMaxHeightValue) {
                                var maxHeightPx = parseFloat(manualMaxHeightValue);
                                if (manualMaxHeightUnit === 'rem') {
                                    maxHeightPx = maxHeightPx * 16;
                                } else if (manualMaxHeightUnit === 'em') {
                                    maxHeightPx = maxHeightPx * 16;
                                } else if (manualMaxHeightUnit === '%') {
                                    maxHeightPx = (maxHeightPx / 100) * deviceHeight;
                                }
                                totalHeight = Math.min(totalHeight, maxHeightPx);
                            }

                            // Ensure minimum height of 50px and maximum of 1200px for preview container
                            totalHeight = Math.max(50, Math.min(1200, totalHeight));

                            // Set the container and iframe heights
                            $container.css('height', totalHeight + 'px');
                            $iframe.css('height', totalHeight + 'px');

                        } catch (e) {
                            console.warn('Amadex Preview: Could not measure content height:', e);
                            // Fallback to a reasonable default
                            $container.css('height', '300px');
                            $iframe.css('height', '300px');
                        }
                    }

                    // Wait for iframe to load, then measure and adjust height
                    // Unbind previous handlers to prevent stacking
                    $iframe.off('load.amadexPreview');
                    $iframe.on('load.amadexPreview', function() {
                        // Wait a bit for content to fully render (especially for animations/async content)
                        setTimeout(function() {
                            adjustPreviewHeight();
                            $loading.hide();
                            $iframe.show();

                            // Re-measure after a short delay to catch any delayed rendering
                            setTimeout(function() {
                                adjustPreviewHeight();
                            }, 200);
                        }, 100);
                    });

                    // Trigger load event (in case it's already loaded)
                    setTimeout(function() {
                        adjustPreviewHeight();
                        $loading.hide();
                        $iframe.show();

                        // Re-measure after content settles
                        setTimeout(function() {
                            adjustPreviewHeight();
                        }, 300);
                    }, 100);

                } catch (e) {
                    console.error('Amadex Preview Update Error:', e);
                    console.error('Stack:', e.stack);
                    jQuery('#amadex-preview-loading').hide();
                    amadexUpdatePromoPreviewFallback();
                }
            }

            /**
             * Switch device view for preview
             */
            function amadexSwitchDevice(deviceType, width, height) {
                amadexCurrentDevice.type = deviceType;
                amadexCurrentDevice.width = width;
                amadexCurrentDevice.height = height;

                // Update active button
                jQuery('.amadex-device-button').removeClass('active');
                jQuery('.amadex-device-button[data-device="' + deviceType + '"]').addClass('active');

                // Update custom inputs
                jQuery('#amadex-custom-width').val(width);
                jQuery('#amadex-custom-height').val(height);

                // Refresh preview with new device dimensions
                amadexUpdatePromoPreview();
            }

            /**
             * Fallback preview method (old implementation)
             * Used if shared renderer is not available
             */
            function amadexUpdatePromoPreviewFallback() {
                try {
                    var $preview = jQuery('#amadex-promo-preview-container');
                    if ($preview.length === 0) {
                        return;
                    }

                    // Simple fallback preview
                    var title = jQuery('#container_title').val() || 'Preview Container';
                    var description = jQuery('#container_description').val() || 'This is a preview of your promotional container.';
                    var previewHtml = '<div class="amadex-promotional-container" style="width: 100%; padding: 20px; background: #f9f9f9; border: 2px dashed #ddd; border-radius: 8px;">';
                    previewHtml += '<h3 style="margin: 0 0 10px 0; color: #333;">' + escapeHtml(title) + '</h3>';
                    previewHtml += '<p style="margin: 0; color: #666;">' + escapeHtml(description) + '</p>';
                    previewHtml += '<p style="margin: 10px 0 0 0; font-size: 12px; color: #999;">Note: Full preview requires shared renderer to be loaded.</p>';
                    previewHtml += '</div>';

                    $preview.html(previewHtml);
                } catch (e) {
                    console.error('Amadex Preview Fallback Error:', e);
                }
            }

            // Handle color type changes
            function amadexUpdateColorFields() {
                var colorType = jQuery('input[name="container_color_type"]:checked').val();
                var $colorFields = jQuery('#container_color_fields');
                var $secondaryField = jQuery('#color_secondary_field');
                var $tertiaryField = jQuery('#color_tertiary_field');
                var $gradientControls = jQuery('#gradient_controls');
                var $gradientStops = jQuery('#gradient_stops_field');

                if (colorType === 'default') {
                    $colorFields.hide();
                } else {
                    $colorFields.show();

                    if (colorType === 'solid') {
                        $secondaryField.hide();
                        $tertiaryField.hide();
                        $gradientControls.hide();
                    } else if (colorType === 'gradient_2') {
                        $secondaryField.show();
                        $tertiaryField.hide();
                        $gradientControls.show();
                        $gradientStops.hide();
                    } else if (colorType === 'gradient_3') {
                        $secondaryField.show();
                        $tertiaryField.show();
                        $gradientControls.show();
                        $gradientStops.show();
                    }
                }

                amadexUpdatePromoPreview();
            }

            // Handle gradient direction changes
            function amadexUpdateGradientAngle() {
                var direction = jQuery('#container_gradient_direction').val();
                if (direction === 'custom') {
                    jQuery('#gradient_angle_field').show();
                } else {
                    jQuery('#gradient_angle_field').hide();
                }
                amadexUpdatePromoPreview();
            }

            // Handle text color auto toggle
            function amadexUpdateTextColorField() {
                if (jQuery('#text_color_auto').is(':checked')) {
                    jQuery('#text_color_manual_field').hide();
                } else {
                    jQuery('#text_color_manual_field').show();
                }
                amadexUpdatePromoPreview();
            }

            // Handle linked colors toggle
            function amadexHandleLinkedColors() {
                var isLinked = jQuery('#text_colors_linked').is(':checked');
                // Remove existing handlers first
                jQuery('#container_heading_color, #container_body_color').off('change.amadex-link input.amadex-link');

                if (isLinked) {
                    // When linked, sync heading color to body color on change
                    jQuery('#container_heading_color').on('change.amadex-link input.amadex-link', function() {
                        var headingColor = getColorPickerValue('#container_heading_color');
                        if (headingColor) {
                            // Update body color picker
                            var $bodyPicker = jQuery('#container_body_color');
                            var $bodyInput = $bodyPicker.closest('.wp-picker-container').find('.wp-color-picker').length > 0 ?
                                $bodyPicker.closest('.wp-picker-container').find('.wp-color-picker') :
                                $bodyPicker;

                            if ($bodyInput.length > 0 && typeof $bodyInput.wpColorPicker === 'function') {
                                try {
                                    $bodyInput.wpColorPicker('color', headingColor);
                                } catch (e) {
                                    $bodyInput.val(headingColor).trigger('change');
                                }
                            } else {
                                $bodyPicker.val(headingColor).trigger('input');
                            }
                        }
                        amadexUpdatePromoPreview();
                    });

                    // Also sync body to heading
                    jQuery('#container_body_color').on('change.amadex-link input.amadex-link', function() {
                        var bodyColor = getColorPickerValue('#container_body_color');
                        if (bodyColor) {
                            // Update heading color picker
                            var $headingPicker = jQuery('#container_heading_color');
                            var $headingInput = $headingPicker.closest('.wp-picker-container').find('.wp-color-picker').length > 0 ?
                                $headingPicker.closest('.wp-picker-container').find('.wp-color-picker') :
                                $headingPicker;

                            if ($headingInput.length > 0 && typeof $headingInput.wpColorPicker === 'function') {
                                try {
                                    $headingInput.wpColorPicker('color', bodyColor);
                                } catch (e) {
                                    $headingInput.val(bodyColor).trigger('change');
                                }
                            } else {
                                $headingPicker.val(bodyColor).trigger('input');
                            }
                        }
                        amadexUpdatePromoPreview();
                    });
                }
            }

            // Initialize color pickers
            function amadexInitColorPickers() {
                try {
                    var pickerType = jQuery('#color_picker_type').val() || 'html5';
                    jQuery('.amadex-color-picker').each(function() {
                        var $picker = jQuery(this);
                        var pickerName = $picker.attr('name');

                        if (!pickerName) {
                            console.warn('Amadex Color Picker: Missing name attribute');
                            return;
                        }

                        // Skip if already initialized
                        if ($picker.closest('.wp-picker-container').length > 0) {
                            return; // Already a WordPress picker
                        }
                        if ($picker.attr('type') === 'color') {
                            return; // Already an HTML5 picker
                        }

                        var currentColor = $picker.val() || $picker.data('default-color') || '#000000';

                        if (pickerType === 'wordpress') {
                            // Check if WordPress color picker is available
                            if (typeof wp !== 'undefined' && wp.colorPicker) {
                                try {
                                    // WordPress color picker
                                    $picker.wpColorPicker({
                                        defaultColor: currentColor,
                                        change: function(event, ui) {
                                            amadexUpdatePromoPreview();
                                        },
                                        clear: function() {
                                            amadexUpdatePromoPreview();
                                        }
                                    });
                                } catch (e) {
                                    console.error('Amadex Color Picker: WordPress initialization failed:', e);
                                    // Fallback to HTML5
                                    var $newPicker = jQuery('<input type="color" class="amadex-color-picker" name="' + pickerName + '" value="' + currentColor + '" style="width: 60px; height: 35px; border: 1px solid #ddd; border-radius: 3px; cursor: pointer;">');
                                    $picker.replaceWith($newPicker);
                                }
                            } else {
                                console.warn('Amadex Color Picker: WordPress color picker not available, using HTML5');
                                // Fallback to HTML5
                                var $newPicker = jQuery('<input type="color" class="amadex-color-picker" name="' + pickerName + '" value="' + currentColor + '" style="width: 60px; height: 35px; border: 1px solid #ddd; border-radius: 3px; cursor: pointer;">');
                                $picker.replaceWith($newPicker);
                            }
                        } else {
                            // HTML5 color picker
                            var $newPicker = jQuery('<input type="color" class="amadex-color-picker" name="' + pickerName + '" value="' + currentColor + '" style="width: 60px; height: 35px; border: 1px solid #ddd; border-radius: 3px; cursor: pointer;">');
                            $picker.replaceWith($newPicker);
                        }
                    });
                } catch (e) {
                    console.error('Amadex Color Picker: Initialization error:', e);
                }
            }

            /**
             * Update template fields visibility and description
             * Phase 3: Enhanced with template-specific fields
             */
            function amadexUpdateTemplateFields(templateId) {
                var $templateRow = jQuery('#template_selector_row');
                var $templateDesc = jQuery('#template_description');
                var $templateDescText = jQuery('#template_description_text');
                var $templateFields = jQuery('#template_specific_fields');
                var $allTemplateFields = jQuery('.template-specific-fields');

                // Phase 6: Template selector is now always visible (no need to show/hide)

                // Hide all template-specific fields first
                $allTemplateFields.hide();
                $templateFields.hide();

                if (templateId && typeof AmadexPromoTemplates !== 'undefined') {
                    var template = AmadexPromoTemplates.getTemplate(templateId);
                    if (template) {
                        $templateDesc.show();
                        $templateDescText.text(template.description || 'No description available.');

                        // Phase 3: Show template-specific fields
                        var $specificFields = jQuery('#template_fields_' + templateId);
                        if ($specificFields.length > 0) {
                            $templateFields.show();
                            $specificFields.show();
                        }
                    } else {
                        $templateDesc.hide();
                    }
                } else {
                    $templateDesc.hide();
                }
            }

            // Phase 3: Carousel slide management
            jQuery(document).ready(function($) {
                var slideIndex = jQuery('.carousel-slide-fieldset').length;

                $('#add_carousel_slide').on('click', function() {
                    var $container = $('#carousel_slides_container');
                    var $newSlide = $('<fieldset class="carousel-slide-fieldset" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fafafa;">' +
                        '<legend style="padding: 0 10px; font-weight: 600;">Slide ' + (slideIndex + 1) + ' <button type="button" class="button button-small remove-slide" style="float: right; margin-top: -5px;">Remove</button></legend>' +
                        '<table class="form-table" style="margin: 0;">' +
                        '<tr><th scope="row" style="width: 150px;"><label>Title</label></th><td><input type="text" name="template_data[slides][' + slideIndex + '][title]" class="regular-text"></td></tr>' +
                        '<tr><th scope="row"><label>Description</label></th><td><textarea name="template_data[slides][' + slideIndex + '][description]" rows="2" class="large-text"></textarea></td></tr>' +
                        '<tr><th scope="row"><label>Image URL</label></th><td><input type="url" name="template_data[slides][' + slideIndex + '][image_url]" class="regular-text"></td></tr>' +
                        '<tr><th scope="row"><label>Link URL</label></th><td><input type="url" name="template_data[slides][' + slideIndex + '][link_url]" class="regular-text"></td></tr>' +
                        '<tr><th scope="row"><label>Button Text</label></th><td><input type="text" name="template_data[slides][' + slideIndex + '][button_text]" class="regular-text" placeholder="Learn More"></td></tr>' +
                        '</table></fieldset>');
                    $container.append($newSlide);
                    slideIndex++;

                    // Re-bind remove handler
                    $newSlide.find('.remove-slide').on('click', function() {
                        $(this).closest('.carousel-slide-fieldset').remove();
                    });
                });

                // Remove slide handler
                $(document).on('click', '.remove-slide', function() {
                    $(this).closest('.carousel-slide-fieldset').remove();
                });

                // Phase 4: Agent comparison add handler
                var agentComparisonIndex = jQuery('#agent_comparison_container .fieldset').length || 2;
                $('#add_comparison_agent').on('click', function() {
                    if (agentComparisonIndex >= 4) {
                        alert('Maximum 4 agents allowed for comparison.');
                        return;
                    }
                    var $container = $('#agent_comparison_container');
                    var $newAgent = $('<fieldset style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; background: #fafafa;">' +
                        '<legend style="padding: 0 10px; font-weight: 600;">Agent ' + (agentComparisonIndex + 1) + '</legend>' +
                        '<table class="form-table" style="margin: 0;">' +
                        '<tr><th scope="row" style="width: 150px;"><label>Name</label></th><td><input type="text" name="template_data[comparison_agents][' + agentComparisonIndex + '][name]" class="regular-text"></td></tr>' +
                        '<tr><th scope="row"><label>Photo URL</label></th><td><input type="url" name="template_data[comparison_agents][' + agentComparisonIndex + '][photo]" class="regular-text"></td></tr>' +
                        '<tr><th scope="row"><label>Rating</label></th><td><input type="number" name="template_data[comparison_agents][' + agentComparisonIndex + '][rating]" min="0" max="5" step="0.1" class="small-text" value="5.0"></td></tr>' +
                        '<tr><th scope="row"><label>Specialties</label></th><td><input type="text" name="template_data[comparison_agents][' + agentComparisonIndex + '][specialties]" class="regular-text"></td></tr>' +
                        '<tr><th scope="row"><label>Link URL</label></th><td><input type="url" name="template_data[comparison_agents][' + agentComparisonIndex + '][link]" class="regular-text"></td></tr>' +
                        '</table></fieldset>');
                    $container.append($newAgent);
                    agentComparisonIndex++;
                });
            });

            /**
             * Update container type fields and apply constraints
             */
            function amadexUpdateContainerTypeFields(containerTypeId) {
                var $containerTypeInfo = jQuery('#container_type_info');
                var $containerTypeDesc = jQuery('#container_type_description');
                var $containerTypeUseCase = jQuery('#container_type_usecase');
                var $containerTypeUniqueness = jQuery('#container_type_uniqueness');

                if (containerTypeId && typeof AmadexContainerTypes !== 'undefined') {
                    var containerType = AmadexContainerTypes.getContainerType(containerTypeId);
                    if (containerType) {
                        // Show info
                        $containerTypeInfo.show();
                        $containerTypeDesc.text(containerType.description || 'No description available.');
                        $containerTypeUseCase.text(containerType.useCase || 'No use case specified.');
                        $containerTypeUniqueness.text(containerType.uniqueness || 'No uniqueness specified.');

                        // Apply dimension constraints
                        if (containerType.dimensions) {
                            var dims = containerType.dimensions;
                            if (dims.width !== '100%' && typeof dims.width === 'number') {
                                jQuery('#container_width_value').val(dims.width);
                                jQuery('#container_width_unit').val('px');
                            }
                            if (dims.height !== 'auto' && typeof dims.height === 'number') {
                                jQuery('#container_height_value').val(dims.height);
                                jQuery('#container_height_unit').val('px');
                            }

                            // Apply constraints to min/max fields
                            if (containerType.constraints) {
                                var constraints = containerType.constraints;
                                if (constraints.minWidth) {
                                    jQuery('#container_min_width_value').val(constraints.minWidth);
                                    jQuery('#container_min_width_unit').val('px');
                                }
                                if (constraints.maxWidth && constraints.maxWidth !== '100%') {
                                    jQuery('#container_max_width_value').val(constraints.maxWidth);
                                    jQuery('#container_max_width_unit').val('px');
                                }
                                if (constraints.minHeight) {
                                    jQuery('#container_min_height_value').val(constraints.minHeight);
                                    jQuery('#container_min_height_unit').val('px');
                                }
                                if (constraints.maxHeight && constraints.maxHeight !== 'none') {
                                    jQuery('#container_max_height_value').val(constraints.maxHeight);
                                    jQuery('#container_max_height_unit').val('px');
                                }
                            }
                        }
                    } else {
                        $containerTypeInfo.hide();
                    }
                } else {
                    $containerTypeInfo.hide();
                }
            }

            /**
             * Initialize Promo Topics UI
             */
            function amadexInitPromoTopics() {
                if (typeof AmadexPromoTopics === 'undefined') {
                    console.warn('Amadex Promo Topics: Library not loaded');
                    return;
                }

                var $topicsContainer = jQuery('#amadex-promo-topics-container');
                var $anglesSection = jQuery('#amadex-promo-topic-angles');
                var $anglesList = jQuery('#amadex-topic-angles-list');
                var $anglesTitle = jQuery('#amadex-topic-angles-title');

                var allTopics = AmadexPromoTopics.getAllTopics();
                $topicsContainer.empty();

                // Render each topic
                Object.keys(allTopics).forEach(function(topicId) {
                    var topic = allTopics[topicId];
                    var $topicCard = jQuery('<div>', {
                        class: 'amadex-topic-card',
                        style: 'padding: 15px; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s;'
                    });

                    $topicCard.on('mouseenter', function() {
                        jQuery(this).css({
                            'border-color': '#0e7d3f',
                            'box-shadow': '0 2px 8px rgba(14, 125, 63, 0.1)'
                        });
                    }).on('mouseleave', function() {
                        jQuery(this).css({
                            'border-color': '#e5e7eb',
                            'box-shadow': 'none'
                        });
                    });

                    $topicCard.html(
                        '<h4 style="margin: 0 0 8px 0; color: #111827; font-size: 16px;">' + escapeHtml(topic.name) + '</h4>' +
                        '<p style="margin: 0 0 10px 0; color: #6b7280; font-size: 13px; line-height: 1.5;">' + escapeHtml(topic.description) + '</p>' +
                        '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px;">' +
                        '<span style="color: #6b7280; font-size: 12px;">' + topic.angles.length + ' banner angles</span>' +
                        '<button type="button" class="amadex-view-angles-btn" data-topic-id="' + escapeHtml(topicId) + '" style="padding: 6px 12px; background: #0e7d3f; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">View Angles</button>' +
                        '</div>'
                    );

                    $topicsContainer.append($topicCard);
                });

                // Handle "View Angles" button clicks
                jQuery(document).on('click', '.amadex-view-angles-btn', function() {
                    var topicId = jQuery(this).data('topic-id');
                    var topic = AmadexPromoTopics.getTopic(topicId);

                    if (!topic || !topic.angles) {
                        return;
                    }

                    $anglesTitle.text(topic.name + ' - Banner Angles');
                    $anglesList.empty();

                    topic.angles.forEach(function(angle) {
                        var $angleCard = jQuery('<div>', {
                            class: 'amadex-angle-card',
                            style: 'padding: 15px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;'
                        });

                        var riskColor = '#10b981'; // Default green
                        var containerTypeName = 'Custom';
                        if (angle.recommendedContainerType && typeof AmadexContainerTypes !== 'undefined') {
                            var containerType = AmadexContainerTypes.getContainerType(angle.recommendedContainerType);
                            if (containerType) {
                                containerTypeName = containerType.name;
                            }
                        }

                        $angleCard.html(
                            '<h5 style="margin: 0 0 8px 0; color: #111827; font-size: 14px; font-weight: 600;">' + escapeHtml(angle.name) + '</h5>' +
                            '<p style="margin: 0 0 8px 0; color: #374151; font-size: 13px; font-weight: 500;">' + escapeHtml(angle.headline) + '</p>' +
                            '<p style="margin: 0 0 10px 0; color: #6b7280; font-size: 12px; line-height: 1.5;">' + escapeHtml(angle.description) + '</p>' +
                            '<div style="margin: 10px 0; padding: 10px; background: #eff6ff; border-left: 3px solid #0066cc; border-radius: 3px;">' +
                            '<div style="font-size: 11px; color: #1e40af; margin-bottom: 5px;"><strong>Psychology:</strong></div>' +
                            '<div style="font-size: 12px; color: #1e3a8a; line-height: 1.5;">' + escapeHtml(angle.psychology) + '</div>' +
                            '</div>' +
                            '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">' +
                            '<div style="font-size: 11px; color: #6b7280;">' +
                            '<strong>Container:</strong> ' + escapeHtml(containerTypeName) +
                            '</div>' +
                            '<button type="button" class="amadex-apply-angle-btn" data-topic-id="' + escapeHtml(topicId) + '" data-angle-id="' + escapeHtml(angle.id) + '" style="padding: 6px 12px; background: #0e7d3f; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">Apply Preset</button>' +
                            '</div>'
                        );

                        $anglesList.append($angleCard);
                    });

                    $anglesSection.slideDown(300);
                    jQuery('html, body').animate({
                        scrollTop: $anglesSection.offset().top - 100
                    }, 500);
                });

                // Handle "Close" button
                jQuery('#amadex-close-angles').on('click', function() {
                    $anglesSection.slideUp(300);
                });

                // Handle "Apply Preset" button clicks
                jQuery(document).on('click', '.amadex-apply-angle-btn', function() {
                    var topicId = jQuery(this).data('topic-id');
                    var angleId = jQuery(this).data('angle-id');

                    var exampleConfig = AmadexPromoTopics.getExampleConfig(topicId, angleId);
                    if (!exampleConfig) {
                        alert('Preset configuration not found.');
                        return;
                    }

                    // Confirm before applying
                    if (!confirm('This will replace your current container settings with the preset. Continue?')) {
                        return;
                    }

                    // Apply the preset configuration
                    amadexApplyPresetConfig(exampleConfig);

                    // Close angles section
                    $anglesSection.slideUp(300);

                    // Show success message
                    alert('Preset applied! Review the settings below and adjust as needed.');
                });
            }

            /**
             * Apply a preset configuration to the form
             */
            function amadexApplyPresetConfig(config) {
                // Basic fields
                if (config.type) {
                    jQuery('#container_type').val(config.type).trigger('change');
                }
                if (config.title) {
                    jQuery('#container_title').val(config.title);
                }
                if (config.description) {
                    jQuery('#container_description').val(config.description);
                }
                if (config.button_text) {
                    jQuery('#container_button_text').val(config.button_text);
                }
                if (config.link_url) {
                    jQuery('#container_link_url').val(config.link_url);
                }
                if (config.image_url) {
                    jQuery('#container_image_url').val(config.image_url);
                }

                // Container type
                if (config.container_type_id) {
                    jQuery('#container_type_id').val(config.container_type_id).trigger('change');
                }

                // Template
                if (config.template_id) {
                    jQuery('#container_template_id').val(config.template_id).trigger('change');
                }

                // Colors
                if (config.container_color_type) {
                    jQuery('#container_color_type').val(config.container_color_type).trigger('change');
                }
                if (config.container_color_primary) {
                    jQuery('#container_color_primary').val(config.container_color_primary);
                    if (typeof jQuery.fn.wpColorPicker === 'function') {
                        jQuery('#container_color_primary').wpColorPicker('color', config.container_color_primary);
                    }
                }
                if (config.container_color_secondary) {
                    jQuery('#container_color_secondary').val(config.container_color_secondary);
                    if (typeof jQuery.fn.wpColorPicker === 'function') {
                        jQuery('#container_color_secondary').wpColorPicker('color', config.container_color_secondary);
                    }
                }
                if (config.container_color_tertiary) {
                    jQuery('#container_color_tertiary').val(config.container_color_tertiary);
                    if (typeof jQuery.fn.wpColorPicker === 'function') {
                        jQuery('#container_color_tertiary').wpColorPicker('color', config.container_color_tertiary);
                    }
                }
                if (config.container_color_opacity !== undefined) {
                    jQuery('#container_color_opacity').val(config.container_color_opacity);
                }
                if (config.container_gradient_direction) {
                    jQuery('#container_gradient_direction').val(config.container_gradient_direction).trigger('change');
                }

                // Animations
                if (config.animations && Array.isArray(config.animations)) {
                    // Uncheck all first
                    jQuery('input[name="container_animations[]"]').prop('checked', false);
                    // Check selected animations
                    config.animations.forEach(function(animId) {
                        jQuery('input[name="container_animations[]"][value="' + escapeHtml(animId) + '"]').prop('checked', true);
                    });
                }
                if (config.animation_intensity !== undefined) {
                    jQuery('#animation_intensity').val(config.animation_intensity);
                    jQuery('#animation_intensity_value').text(config.animation_intensity + '%');
                }
                if (config.animation_duration !== undefined) {
                    jQuery('#animation_duration').val(config.animation_duration);
                }
                if (config.animation_delay !== undefined) {
                    jQuery('#animation_delay').val(config.animation_delay);
                }
                // Trigger updates
                amadexUpdateColorFields();
                amadexUpdatePromoPreview();
            }

            // Initialize on page load
            jQuery(document).ready(function($) {
                var currentType = $('#container_type').val();
                if (currentType) {
                    amadexUpdatePromoFields(currentType);
                }

                // Initialize template selector
                var currentTemplateId = $('#container_template_id').val();
                if (currentTemplateId) {
                    amadexUpdateTemplateFields(currentTemplateId);
                } else {
                    // Show template selector row by default
                    $('#template_selector_row').show();
                }

                // Initialize container type selector
                var currentContainerTypeId = $('#container_type_id').val();
                if (currentContainerTypeId) {
                    amadexUpdateContainerTypeFields(currentContainerTypeId);
                }

                // Initialize promo topics (wait a bit for libraries to load)
                setTimeout(function() {
                    amadexInitPromoTopics();
                }, 200);

                // Initialize color fields
                amadexUpdateColorFields();
                amadexUpdateGradientAngle();
                amadexUpdateTextColorField();
                amadexHandleLinkedColors();

                // Device selector event handlers
                $('.amadex-device-button[data-device]').on('click', function() {
                    var $btn = $(this);
                    var deviceType = $btn.data('device');
                    var width = parseInt($btn.data('width'));
                    var height = parseInt($btn.data('height'));
                    amadexSwitchDevice(deviceType, width, height);
                });

                // Custom device dimensions
                $('#amadex-apply-custom').on('click', function() {
                    var customWidth = parseInt($('#amadex-custom-width').val());
                    var customHeight = parseInt($('#amadex-custom-height').val());

                    if (customWidth >= 200 && customWidth <= 2000 && customHeight >= 200 && customHeight <= 2000) {
                        amadexCurrentDevice.type = 'custom';
                        amadexCurrentDevice.width = customWidth;
                        amadexCurrentDevice.height = customHeight;

                        // Update active button (remove active from all, none active for custom)
                        $('.amadex-device-button').removeClass('active');

                        // Refresh preview
                        amadexUpdatePromoPreview();
                    } else {
                        alert('Please enter valid dimensions (200-2000px)');
                    }
                });

                // Wait a bit for WordPress scripts to load, then initialize pickers
                setTimeout(function() {
                    amadexInitColorPickers();
                    // Initial preview update after pickers are ready
                    setTimeout(amadexUpdatePromoPreview, 50);
                }, 150);

                // Update intensity value display
                $('#animation_intensity').on('input', function() {
                    $('#animation_intensity_value').text($(this).val() + '%');
                    amadexUpdatePromoPreview();
                });

                // Update color opacity value display
                $('#container_color_opacity').on('input', function() {
                    $('#color_opacity_value').text($(this).val() + '%');
                    amadexUpdatePromoPreview();
                });

                // Update gradient angle value display
                $('#container_gradient_angle').on('input', function() {
                    $('#gradient_angle_value').text($(this).val() + '°');
                    amadexUpdatePromoPreview();
                });

                // Color type change handlers
                $('input[name="container_color_type"]').on('change', function() {
                    amadexUpdateColorFields();
                });

                $('#color_picker_type').on('change', function() {
                    amadexInitColorPickers();
                    amadexUpdatePromoPreview();
                });

                $('#container_gradient_direction').on('change', amadexUpdateGradientAngle);
                $('#text_color_auto').on('change', amadexUpdateTextColorField);
                $('#text_colors_linked').on('change', function() {
                    amadexHandleLinkedColors();
                    amadexUpdatePromoPreview();
                });

                // Update preview on dimension/animation/color changes
                // Dimensions
                $('#container_width_value, #container_width_unit, #container_height_value, #container_height_unit').on('input change', amadexUpdatePromoPreview);

                // Min/Max Dimensions
                $('#container_min_width_value, #container_min_width_unit, #container_max_width_value, #container_max_width_unit').on('input change', amadexUpdatePromoPreview);
                $('#container_min_height_value, #container_min_height_unit, #container_max_height_value, #container_max_height_unit').on('input change', amadexUpdatePromoPreview);

                // Padding Controls
                $('input[name="container_padding_mode"]').on('change', amadexUpdatePromoPreview);
                $('#container_padding_all, #container_padding_all_unit').on('input change', amadexUpdatePromoPreview);
                $('#container_padding_x, #container_padding_x_unit, #container_padding_y, #container_padding_y_unit').on('input change', amadexUpdatePromoPreview);
                $('#container_padding_top, #container_padding_right, #container_padding_bottom, #container_padding_left').on('input change', amadexUpdatePromoPreview);

                // Gap (Grid Spacing)
                $('#container_gap_column, #container_gap_column_unit, #container_gap_row, #container_gap_row_unit').on('input change', amadexUpdatePromoPreview);

                // Border Radius
                $('#container_border_radius, #container_border_radius_unit').on('input change', amadexUpdatePromoPreview);

                // Compactness & Typography
                $('#container_compactness, #container_typography_scale').on('input change', amadexUpdatePromoPreview);

                // Animations
                $('input[name="container_animations[]"]').on('change', function() {
                    amadexUpdatePromoPreview();
                });
                $('#animation_duration, #animation_delay, #animation_mobile_disabled').on('change', amadexUpdatePromoPreview);

                // Animation intensity
                $('#animation_intensity').on('input', function() {
                    $('#animation_intensity_value').text($(this).val() + '%');
                    amadexUpdatePromoPreview();
                });

                // Content
                $('#container_title, #container_description').on('input', amadexUpdatePromoPreview);

                // Handle color picker changes (both WordPress and HTML5) - use delegated events
                jQuery(document).on('input change', '.amadex-color-picker', function() {
                    amadexUpdatePromoPreview();
                });

                // WordPress color picker events - use multiple event types
                jQuery(document).on('colorchange change input', '.wp-color-picker', function() {
                    amadexUpdatePromoPreview();
                });

                // Also listen for changes on the color picker container
                jQuery(document).on('change', '.wp-picker-container', function() {
                    amadexUpdatePromoPreview();
                });

                // Other color-related inputs
                $('#container_color_opacity, #container_gradient_direction, #container_gradient_angle, #gradient_stop_1, #gradient_stop_2, #gradient_stop_3').on('input change', amadexUpdatePromoPreview);

                // Initial preview update - wait for everything to be ready
                setTimeout(function() {
                    amadexUpdatePromoPreview();
                }, 300);

                // Drawer Toggle Functionality
                $('.amadex-drawer-toggle').on('click', function(e) {
                    e.preventDefault();
                    var $toggle = $(this);
                    var drawerId = $toggle.data('drawer');
                    var $content = $('.amadex-drawer-content[data-drawer="' + drawerId + '"]');
                    var isOpen = $toggle.hasClass('amadex-drawer-open');
                    var $arrowIcon = $toggle.find('.dashicons-arrow-right-alt2, .dashicons-arrow-down-alt2').first();

                    if (isOpen) {
                        // Close drawer
                        $toggle.removeClass('amadex-drawer-open');
                        $content.slideUp(300);
                        if ($arrowIcon.hasClass('dashicons-arrow-down-alt2')) {
                            $arrowIcon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-right-alt2');
                        }
                    } else {
                        // Open drawer
                        $toggle.addClass('amadex-drawer-open');
                        $content.slideDown(300);
                        if ($arrowIcon.hasClass('dashicons-arrow-right-alt2')) {
                            $arrowIcon.removeClass('dashicons-arrow-right-alt2').addClass('dashicons-arrow-down-alt2');
                        }
                    }
                });
            });
        </script>

        <style type="text/css">
            /* Full-width layout for Promotional Containers Management */
            .amadex-promotional-containers-management {
                max-width: 100%;
                width: 100%;
                padding: 0;
                margin: 0;
            }

            .amadex-admin-header-section {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #ccd0d4;
                width: 100%;
            }

            .amadex-admin-header-section h2 {
                margin-top: 0;
            }

            .amadex-promo-container-form-wrapper {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 30px;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                width: 100%;
                box-sizing: border-box;
            }

            .amadex-promo-container-form-wrapper h3 {
                margin-top: 0;
            }

            .amadex-promo-containers-list-wrapper {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                width: 100%;
                box-sizing: border-box;
            }

            /* Full-width form tables */
            .amadex-promotional-containers-management .form-table {
                width: 100%;
                max-width: 100%;
            }

            .amadex-promotional-containers-management .form-table th,
            .amadex-promotional-containers-management .form-table td {
                padding: 15px 20px 15px 0;
            }

            /* Ensure form elements use full width where appropriate */
            .amadex-promotional-containers-management input[type="text"],
            .amadex-promotional-containers-management input[type="url"],
            .amadex-promotional-containers-management input[type="email"],
            .amadex-promotional-containers-management input[type="number"],
            .amadex-promotional-containers-management textarea,
            .amadex-promotional-containers-management select {
                max-width: 100%;
                box-sizing: border-box;
            }

            /* Full-width for regular-text and large-text inputs */
            .amadex-promotional-containers-management .regular-text,
            .amadex-promotional-containers-management .large-text {
                width: 100%;
                max-width: 100%;
            }

            /* Responsive layout adjustments */
            @media screen and (min-width: 1600px) {
                .amadex-promotional-containers-management {
                    padding-right: 20px;
                }
            }

            @media screen and (max-width: 1200px) {
                .amadex-promotional-containers-management .form-table th {
                    width: 180px;
                }
            }

            @media screen and (max-width: 782px) {

                .amadex-promotional-containers-management .form-table th,
                .amadex-promotional-containers-management .form-table td {
                    display: block;
                    width: 100%;
                    padding: 10px 0;
                }
            }

            .promo-type-fields {
                display: none;
            }
        </style>
    <?php
            }

            /**
             * Static default values for Email Template (no brand merge)
             */
            public static function get_email_template_static_defaults()
            {
                return array(
                    'container_max_width' => 600,
                    'outer_padding_desktop' => 32,
                    'outer_padding_mobile' => 15,
                    'mobile_breakpoint' => 600,
                    'body_bg' => '#EEF9F2',
                    'content_bg' => '#ffffff',
                    'border_radius' => 20,
                    'section_spacing' => 24,
                    'primary_color' => '#0E7D3F',
                    'text_color' => '#111827',
                    'secondary_text' => '#6B7280',
                    'border_color' => '#E5E7EB',
                    'link_color' => '#0E7D3F',
                    'font_family' => "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
                    'font_size_body' => 14,
                    'line_height_body' => 1.5,
                    'h1_size' => 24,
                    'h1_size_mobile' => 20,
                    'logo_max_width_desktop' => 200,
                    'logo_max_width_mobile' => 150,
                    'logo_primary_id' => 0,
                    'logo_secondary_id' => 0,
                    'email_subject_customer' => __('We received your booking request ({reference})', 'amadex'),
                    'email_subject_admin' => __('New verified lead received ({reference})', 'amadex'),
                    'email_preheader_customer' => '',
                    'email_preheader_admin' => '',
                    'email_template_mode' => 'custom',
                );
            }

            /**
             * Default values for Email Template settings (merged with Brand so one change applies everywhere)
             */
            public static function get_email_template_defaults()
            {
                return wp_parse_args(self::get_brand_settings_for_email(), self::get_email_template_static_defaults());
            }

            /**
             * Render Brand tab: global company name, colors, font, logos (used as defaults for Email Template)
             */
            private function render_brand_tab()
            {
                $opts = wp_parse_args(get_option('amadex_brand_settings', array()), self::get_brand_defaults());
                $redirect_url = add_query_arg(array('page' => 'amadex-settings', 'tab' => 'brand', 'settings-updated' => 'true'), admin_url('admin.php'));
    ?>
        <form method="post" action="options.php" id="amadex-brand-form">
            <?php settings_fields('amadex_brand_settings'); ?>
            <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr($redirect_url); ?>">
            <div class="amadex-brand-wrap" style="max-width:640px;">
                <p class="description" style="margin-bottom:16px;"><?php _e('Global brand settings. These values are used as defaults for the Email Template (booking confirmation emails).', 'amadex'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="amadex-brand-company_name"><?php _e('Company / site name', 'amadex'); ?></label></th>
                        <td>
                            <input type="text" id="amadex-brand-company_name" name="amadex_brand_settings[company_name]" value="<?php echo esc_attr($opts['company_name']); ?>" class="large-text">
                            <p class="description"><?php _e('Used in emails when set. Leave empty to use the site title.', 'amadex'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Colors', 'amadex'); ?></th>
                        <td>
                            <p class="amadex-color-row"><label><?php _e('Primary', 'amadex'); ?></label><br>
                                <input type="text" name="amadex_brand_settings[primary_color]" value="<?php echo esc_attr($opts['primary_color']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['primary_color']); ?>">
                            </p>
                            <p class="amadex-color-row"><label><?php _e('Body background', 'amadex'); ?></label><br>
                                <input type="text" name="amadex_brand_settings[body_bg]" value="<?php echo esc_attr($opts['body_bg']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['body_bg']); ?>">
                            </p>
                            <p class="amadex-color-row"><label><?php _e('Content background', 'amadex'); ?></label><br>
                                <input type="text" name="amadex_brand_settings[content_bg]" value="<?php echo esc_attr($opts['content_bg']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['content_bg']); ?>">
                            </p>
                            <p class="amadex-color-row"><label><?php _e('Text', 'amadex'); ?></label><br>
                                <input type="text" name="amadex_brand_settings[text_color]" value="<?php echo esc_attr($opts['text_color']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['text_color']); ?>">
                            </p>
                            <p class="amadex-color-row"><label><?php _e('Secondary text', 'amadex'); ?></label><br>
                                <input type="text" name="amadex_brand_settings[secondary_text]" value="<?php echo esc_attr($opts['secondary_text']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['secondary_text']); ?>">
                            </p>
                            <p class="amadex-color-row"><label><?php _e('Border', 'amadex'); ?></label><br>
                                <input type="text" name="amadex_brand_settings[border_color]" value="<?php echo esc_attr($opts['border_color']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['border_color']); ?>">
                            </p>
                            <p class="amadex-color-row"><label><?php _e('Links', 'amadex'); ?></label><br>
                                <input type="text" name="amadex_brand_settings[link_color]" value="<?php echo esc_attr($opts['link_color']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['link_color']); ?>">
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="amadex-brand-font_family"><?php _e('Font family', 'amadex'); ?></label></th>
                        <td>
                            <input type="text" id="amadex-brand-font_family" name="amadex_brand_settings[font_family]" value="<?php echo esc_attr($opts['font_family']); ?>" class="large-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Logos', 'amadex'); ?></th>
                        <td>
                            <input type="hidden" name="amadex_brand_settings[logo_primary_id]" id="amadex-brand-logo_primary_id" value="<?php echo esc_attr($opts['logo_primary_id']); ?>">
                            <input type="hidden" name="amadex_brand_settings[logo_secondary_id]" id="amadex-brand-logo_secondary_id" value="<?php echo esc_attr($opts['logo_secondary_id']); ?>">
                            <p class="amadex-logo-row"><strong><?php _e('Primary logo', 'amadex'); ?></strong><br>
                                <span class="amadex-logo-preview-wrap"><?php
                                                                        if (!empty($opts['logo_primary_id'])) {
                                                                            $url = wp_get_attachment_image_url($opts['logo_primary_id'], 'thumbnail');
                                                                            if ($url) echo '<img id="amadex-brand-logo-primary-preview" src="' . esc_url($url) . '" alt="" style="max-height:60px; display:block; margin:6px 0;">';
                                                                        }
                                                                        ?></span>
                                <button type="button" class="button amadex-logo-select" data-target="primary"><?php _e('Select', 'amadex'); ?></button>
                                <button type="button" class="button amadex-logo-remove" data-target="primary"><?php _e('Remove', 'amadex'); ?></button>
                            </p>
                            <p class="amadex-logo-row"><strong><?php _e('Secondary logo', 'amadex'); ?></strong><br>
                                <span class="amadex-logo-preview-wrap"><?php
                                                                        if (!empty($opts['logo_secondary_id'])) {
                                                                            $url = wp_get_attachment_image_url($opts['logo_secondary_id'], 'thumbnail');
                                                                            if ($url) echo '<img id="amadex-brand-logo-secondary-preview" src="' . esc_url($url) . '" alt="" style="max-height:60px; display:block; margin:6px 0;">';
                                                                        }
                                                                        ?></span>
                                <button type="button" class="button amadex-logo-select" data-target="secondary"><?php _e('Select', 'amadex'); ?></button>
                                <button type="button" class="button amadex-logo-remove" data-target="secondary"><?php _e('Remove', 'amadex'); ?></button>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Save Brand Settings', 'amadex')); ?>
            </div>
        </form>
        <script>
            jQuery(document).ready(function($) {
                var $form = $('#amadex-brand-form');
                try {
                    if (typeof $.fn.wpColorPicker !== 'undefined') {
                        $form.find('.amadex-email-template-color').wpColorPicker();
                    }
                } catch (e) {
                    console.warn('Amadex brand: wpColorPicker init skipped', e);
                }
                var amadexBrandFrame = null,
                    amadexBrandTarget = null;
                $form.on('click', '.amadex-logo-select', function(e) {
                    e.preventDefault();
                    amadexBrandTarget = $(this).data('target');
                    if (!amadexBrandFrame) {
                        amadexBrandFrame = wp.media({
                            title: '<?php echo esc_js(__('Select logo', 'amadex')); ?>',
                            button: {
                                text: '<?php echo esc_js(__('Use this image', 'amadex')); ?>'
                            },
                            library: {
                                type: 'image'
                            },
                            multiple: false
                        });
                        amadexBrandFrame.on('select', function() {
                            var t = amadexBrandTarget;
                            var idInp = t === 'primary' ? 'amadex-brand-logo_primary_id' : 'amadex-brand-logo_secondary_id';
                            var prevId = t === 'primary' ? 'amadex-brand-logo-primary-preview' : 'amadex-brand-logo-secondary-preview';
                            var att = amadexBrandFrame.state().get('selection').first().toJSON();
                            $('#' + idInp).val(att.id);
                            var $wrap = $('#' + idInp).closest('.amadex-logo-row').find('.amadex-logo-preview-wrap');
                            $wrap.html('<img id="' + prevId + '" src="' + (att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url) + '" alt="" style="max-height:60px; display:block; margin:6px 0;">');
                        });
                    }
                    amadexBrandFrame.open();
                });
                $form.on('click', '.amadex-logo-remove', function(e) {
                    e.preventDefault();
                    var t = $(this).data('target');
                    var idInp = t === 'primary' ? 'amadex-brand-logo_primary_id' : 'amadex-brand-logo_secondary_id';
                    $('#' + idInp).val('');
                    $(this).closest('.amadex-logo-row').find('.amadex-logo-preview-wrap').empty();
                });
            });
        </script>
    <?php
            }

            /**
             * Render Email Template tab: left settings, right preview
             */
            private function render_email_template_tab()
            {
                $opts = wp_parse_args(get_option('amadex_email_template_settings', array()), self::get_email_template_defaults());
                // Read custom_html/css from separate options
                $opts['custom_html'] = get_option('amadex_email_custom_html', $opts['custom_html'] ?? '');
                $opts['custom_css']  = get_option('amadex_email_custom_css',  $opts['custom_css']  ?? '');
                $preview_url_customer = add_query_arg(array('amadex_email_preview' => '1', 'type' => 'customer'), home_url('/'));
                $preview_url_admin = add_query_arg(array('amadex_email_preview' => '1', 'type' => 'admin'), home_url('/'));
                $live_preview_nonce = wp_create_nonce('amadex_email_live_preview');
                $builder_design = get_option('amadex_email_builder_design', '');
                $builder_save_nonce = wp_create_nonce('amadex_save_email_builder');
                $saved_blocks = get_option('amadex_email_builder_blocks', array());
                $saved_blocks = is_array($saved_blocks) ? array_values($saved_blocks) : array();
                $template_mode = isset($opts['email_template_mode']) && $opts['email_template_mode'] === 'default' ? 'default' : 'custom';
    ?>
        <div class="amadex-email-template-mode-nav" style="margin-bottom:16px;">
            <button type="button" class="button amadex-email-mode-btn active" data-mode="simple"><?php _e('Simple style', 'amadex'); ?></button>
            <button type="button" class="button amadex-email-mode-btn" data-mode="builder"><?php _e('Drag & drop builder', 'amadex'); ?></button>
        </div>
        <div id="amadex-email-template-simple-wrap" class="amadex-email-mode-panel" style="display:block !important;">
            <form method="post" action="options.php" id="amadex-email-template-form">
                <?php settings_fields('amadex_email_template_settings'); ?>
                <?php
                $redirect_url = add_query_arg(array('page' => 'amadex-settings', 'tab' => 'email_template', 'settings-updated' => 'true'), admin_url('admin.php'));
                ?>
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr($redirect_url); ?>">
                <div class="amadex-email-template-global-mode" style="margin-bottom:20px; padding:14px 16px; background:#f0f6fc; border:1px solid #c3c4c7; border-radius:4px;">
                    <h3 style="margin:0 0 10px 0; font-size:14px;"><?php _e('Template mode', 'amadex'); ?></h3>
                    <p style="margin:0 0 12px 0; font-size:13px; color:#1d2327;">
                        <label style="display:inline-block; margin-right:20px;"><input type="radio" name="amadex_email_template_settings[email_template_mode]" value="default" <?php checked($template_mode, 'default'); ?> class="amadex-template-mode-radio"> <strong><?php _e('Default', 'amadex'); ?></strong> &mdash; <?php _e('Use standard Travelay confirmation style for all emails (customer, admin, and agents).', 'amadex'); ?></label>
                        <label style="display:inline-block;"><input type="radio" name="amadex_email_template_settings[email_template_mode]" value="custom" <?php checked($template_mode, 'custom'); ?> class="amadex-template-mode-radio"> <strong><?php _e('Custom', 'amadex'); ?></strong> &mdash; <?php _e('Use the settings and optional builder below.', 'amadex'); ?></label>
                    </p>
                </div>
                <div class="amadex-email-template-layout">
                    <div class="amadex-email-template-sidebar">
                        <div class="amadex-email-template-actions" style="margin-bottom:16px;">
                            <?php submit_button(__('Save Changes', 'amadex'), 'primary', 'submit', false); ?>
                            <a href="<?php echo esc_url($preview_url_customer); ?>" target="_blank" class="button" style="margin-left:8px;" id="amadex-open-preview-tab"><?php _e('Open preview in new tab', 'amadex'); ?></a>
                            <span id="amadex-unsaved-notice" style="display:none;color:#b32d2e;font-size:12px;margin-left:8px;">⚠️ Save Changes first to see your edits in the new tab.</span>
                        </div>
                        <div class="amadex-email-template-sections" style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px;">
                            <h3 style="margin-top:0;"><?php _e('Preview', 'amadex'); ?></h3>
                            <p><label><?php _e('Recipient type:', 'amadex'); ?></label>
                                <select id="amadex-email-preview-type" style="width:100%; margin-top:4px;">
                                    <option value="customer"><?php _e('Customer', 'amadex'); ?></option>
                                    <option value="admin"><?php _e('Admin / Agent', 'amadex'); ?></option>
                                </select>
                            </p>
                            <p><label><?php _e('Device width:', 'amadex'); ?></label></p>
                            <p style="margin-top:4px;">
                                <button type="button" class="button amadex-device-width" data-width="320">320px</button>
                                <button type="button" class="button amadex-device-width" data-width="375">375px</button>
                                <button type="button" class="button amadex-device-width" data-width="480">480px</button>
                                <button type="button" class="button amadex-device-width" data-width="600">600px</button>
                                <button type="button" class="button amadex-device-width" data-width="700">700px</button>
                            </p>

                            <h3 style="margin-top:24px;"><?php _e('Subject &amp; preheader', 'amadex'); ?></h3>
                            <p><label for="amadex-email_subject_customer"><?php _e('Customer email subject', 'amadex'); ?></label><br>
                                <input type="text" id="amadex-email_subject_customer" name="amadex_email_template_settings[email_subject_customer]" value="<?php echo esc_attr($opts['email_subject_customer']); ?>" class="large-text">
                                <span class="description"><?php _e('Use {reference} for booking reference.', 'amadex'); ?></span>
                            </p>
                            <p><label for="amadex-email_preheader_customer"><?php _e('Customer preheader (inbox preview)', 'amadex'); ?></label><br>
                                <input type="text" id="amadex-email_preheader_customer" name="amadex_email_template_settings[email_preheader_customer]" value="<?php echo esc_attr($opts['email_preheader_customer']); ?>" class="large-text">
                            </p>
                            <p><label for="amadex-email_subject_admin"><?php _e('Admin / agent email subject', 'amadex'); ?></label><br>
                                <input type="text" id="amadex-email_subject_admin" name="amadex_email_template_settings[email_subject_admin]" value="<?php echo esc_attr($opts['email_subject_admin']); ?>" class="large-text">
                            </p>
                            <p><label for="amadex-email_preheader_admin"><?php _e('Admin preheader (optional)', 'amadex'); ?></label><br>
                                <input type="text" id="amadex-email_preheader_admin" name="amadex_email_template_settings[email_preheader_admin]" value="<?php echo esc_attr($opts['email_preheader_admin']); ?>" class="large-text">
                            </p>

                            <h3 style="margin-top:24px;"><?php _e('Container &amp; layout', 'amadex'); ?></h3>
                            <p class="amadex-range-row"><label for="amadex-container_max_width"><?php _e('Container max width (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-container_max_width" name="amadex_email_template_settings[container_max_width]" value="<?php echo esc_attr($opts['container_max_width']); ?>" min="400" max="750" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['container_max_width']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-outer_padding_desktop"><?php _e('Outer padding desktop (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-outer_padding_desktop" name="amadex_email_template_settings[outer_padding_desktop]" value="<?php echo esc_attr($opts['outer_padding_desktop']); ?>" min="0" max="60" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['outer_padding_desktop']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-outer_padding_mobile"><?php _e('Outer padding mobile (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-outer_padding_mobile" name="amadex_email_template_settings[outer_padding_mobile]" value="<?php echo esc_attr($opts['outer_padding_mobile']); ?>" min="0" max="60" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['outer_padding_mobile']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-mobile_breakpoint"><?php _e('Mobile breakpoint (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-mobile_breakpoint" name="amadex_email_template_settings[mobile_breakpoint]" value="<?php echo esc_attr($opts['mobile_breakpoint']); ?>" min="320" max="768" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['mobile_breakpoint']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-border_radius"><?php _e('Border radius (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-border_radius" name="amadex_email_template_settings[border_radius]" value="<?php echo esc_attr($opts['border_radius']); ?>" min="0" max="24" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['border_radius']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-section_spacing"><?php _e('Section spacing (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-section_spacing" name="amadex_email_template_settings[section_spacing]" value="<?php echo esc_attr($opts['section_spacing']); ?>" min="0" max="48" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['section_spacing']); ?></output></span>
                            </p>

                            <h3 style="margin-top:24px;"><?php _e('Colors', 'amadex'); ?></h3>
                            <p class="amadex-color-row"><label for="amadex-body_bg"><?php _e('Body background', 'amadex'); ?></label>
                                <input type="text" id="amadex-body_bg" name="amadex_email_template_settings[body_bg]" value="<?php echo esc_attr($opts['body_bg']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['body_bg']); ?>">
                            </p>
                            <p class="amadex-color-row"><label for="amadex-content_bg"><?php _e('Content background', 'amadex'); ?></label>
                                <input type="text" id="amadex-content_bg" name="amadex_email_template_settings[content_bg]" value="<?php echo esc_attr($opts['content_bg']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['content_bg']); ?>">
                            </p>
                            <p class="amadex-color-row"><label for="amadex-primary_color"><?php _e('Primary / accent', 'amadex'); ?></label>
                                <input type="text" id="amadex-primary_color" name="amadex_email_template_settings[primary_color]" value="<?php echo esc_attr($opts['primary_color']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['primary_color']); ?>">
                            </p>
                            <p class="amadex-color-row"><label for="amadex-text_color"><?php _e('Text color', 'amadex'); ?></label>
                                <input type="text" id="amadex-text_color" name="amadex_email_template_settings[text_color]" value="<?php echo esc_attr($opts['text_color']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['text_color']); ?>">
                            </p>
                            <p class="amadex-color-row"><label for="amadex-secondary_text"><?php _e('Secondary text', 'amadex'); ?></label>
                                <input type="text" id="amadex-secondary_text" name="amadex_email_template_settings[secondary_text]" value="<?php echo esc_attr($opts['secondary_text']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['secondary_text']); ?>">
                            </p>
                            <p class="amadex-color-row"><label for="amadex-border_color"><?php _e('Border color', 'amadex'); ?></label>
                                <input type="text" id="amadex-border_color" name="amadex_email_template_settings[border_color]" value="<?php echo esc_attr($opts['border_color']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['border_color']); ?>">
                            </p>
                            <p class="amadex-color-row"><label for="amadex-link_color"><?php _e('Link color', 'amadex'); ?></label>
                                <input type="text" id="amadex-link_color" name="amadex_email_template_settings[link_color]" value="<?php echo esc_attr($opts['link_color']); ?>" class="amadex-email-template-color" data-default-color="<?php echo esc_attr($opts['link_color']); ?>">
                            </p>

                            <h3 style="margin-top:24px;"><?php _e('Typography', 'amadex'); ?></h3>
                            <p><label for="amadex-font_family"><?php _e('Font family', 'amadex'); ?></label>
                                <input type="text" id="amadex-font_family" name="amadex_email_template_settings[font_family]" value="<?php echo esc_attr($opts['font_family']); ?>" class="large-text" style="width:100%;">
                            </p>
                            <p class="amadex-range-row"><label for="amadex-font_size_body"><?php _e('Body font size (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-font_size_body" name="amadex_email_template_settings[font_size_body]" value="<?php echo esc_attr($opts['font_size_body']); ?>" min="12" max="20" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['font_size_body']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-line_height_body"><?php _e('Body line height', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="hidden" name="amadex_email_template_settings[line_height_body]" id="amadex-line_height_body_val" value="<?php echo esc_attr($opts['line_height_body']); ?>"><input type="range" id="amadex-line_height_body" min="12" max="20" step="1" value="<?php echo esc_attr((string) round((float) $opts['line_height_body'] * 10)); ?>" class="amadex-range amadex-range-decimal" data-scale="0.1"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['line_height_body']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-h1_size"><?php _e('Heading 1 size (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-h1_size" name="amadex_email_template_settings[h1_size]" value="<?php echo esc_attr($opts['h1_size']); ?>" min="16" max="32" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['h1_size']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-h1_size_mobile"><?php _e('Heading 1 mobile (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-h1_size_mobile" name="amadex_email_template_settings[h1_size_mobile]" value="<?php echo esc_attr($opts['h1_size_mobile']); ?>" min="16" max="32" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['h1_size_mobile']); ?></output></span>
                            </p>

                            <?php /* Custom HTML/CSS moved to right panel HTML Editor tab */ ?>
                            <h3 style="margin-top:24px; display:none;"><?php _e('Custom HTML / CSS', 'amadex'); ?></h3>
                            <p class="description" style="margin-bottom:8px;"><?php _e('Override the email template with your own HTML and CSS. Leave blank to use the settings above. Use <code>{booking_content}</code> where flight/passenger details should appear.', 'amadex'); ?></p>
                            <h3 style="margin-top:24px;"><?php _e('Logos', 'amadex'); ?></h3>
                            <p><label><?php _e('Primary logo (left)', 'amadex'); ?></label></p>
                            <p class="amadex-logo-row">
                                <?php
                                $primary_logo_src = !empty($opts['logo_primary_id']) ? wp_get_attachment_image_url((int) $opts['logo_primary_id'], 'thumbnail') : '';
                                $placeholder_src = 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="40" viewBox="0 0 100 40"><rect fill="#f0f0f1" width="100" height="40"/><text x="50" y="24" font-size="11" fill="#888" text-anchor="middle">' . esc_attr__('No logo', 'amadex') . '</text></svg>');
                                ?>
                                <span class="amadex-logo-preview-wrap">
                                    <img id="amadex-logo-primary-preview" src="<?php echo $primary_logo_src ? esc_url($primary_logo_src) : $placeholder_src; ?>" alt="" style="max-width:120px;height:auto;display:block;border:1px solid #ddd;border-radius:4px;" data-placeholder="<?php echo esc_attr($placeholder_src); ?>">
                                </span>
                                <input type="hidden" name="amadex_email_template_settings[logo_primary_id]" id="amadex-logo_primary_id" value="<?php echo esc_attr($opts['logo_primary_id']); ?>">
                                <button type="button" class="button amadex-logo-select" data-target="primary"><?php _e('Select logo', 'amadex'); ?></button>
                                <button type="button" class="button amadex-logo-remove" data-target="primary"><?php _e('Remove', 'amadex'); ?></button>
                            </p>
                            <p><label><?php _e('Secondary logo (right)', 'amadex'); ?></label></p>
                            <p class="amadex-logo-row">
                                <?php
                                $secondary_logo_src = !empty($opts['logo_secondary_id']) ? wp_get_attachment_image_url((int) $opts['logo_secondary_id'], 'thumbnail') : '';
                                ?>
                                <span class="amadex-logo-preview-wrap">
                                    <img id="amadex-logo-secondary-preview" src="<?php echo $secondary_logo_src ? esc_url($secondary_logo_src) : $placeholder_src; ?>" alt="" style="max-width:120px;height:auto;display:block;border:1px solid #ddd;border-radius:4px;" data-placeholder="<?php echo esc_attr($placeholder_src); ?>">
                                </span>
                                <input type="hidden" name="amadex_email_template_settings[logo_secondary_id]" id="amadex-logo_secondary_id" value="<?php echo esc_attr($opts['logo_secondary_id']); ?>">
                                <button type="button" class="button amadex-logo-select" data-target="secondary"><?php _e('Select logo', 'amadex'); ?></button>
                                <button type="button" class="button amadex-logo-remove" data-target="secondary"><?php _e('Remove', 'amadex'); ?></button>
                            </p>
                            <p class="description"><?php _e('PNG, JPG or GIF recommended. Size is controlled by the sliders below.', 'amadex'); ?></p>
                            <p class="amadex-range-row"><label for="amadex-logo_max_width_desktop"><?php _e('Logo max width desktop (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-logo_max_width_desktop" name="amadex_email_template_settings[logo_max_width_desktop]" value="<?php echo esc_attr($opts['logo_max_width_desktop']); ?>" min="80" max="300" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['logo_max_width_desktop']); ?></output></span>
                            </p>
                            <p class="amadex-range-row"><label for="amadex-logo_max_width_mobile"><?php _e('Logo max width mobile (px)', 'amadex'); ?></label>
                                <span class="amadex-range-wrap"><input type="range" id="amadex-logo_max_width_mobile" name="amadex_email_template_settings[logo_max_width_mobile]" value="<?php echo esc_attr($opts['logo_max_width_mobile']); ?>" min="80" max="300" step="1" class="amadex-range"><output class="amadex-slider-value" aria-live="polite"><?php echo esc_html($opts['logo_max_width_mobile']); ?></output></span>
                            </p>
                        </div>
                    </div>
                    <div class="amadex-email-template-preview">
                        <div style="display:flex;gap:0;margin-bottom:8px;border-bottom:2px solid #ddd;">
                            <button type="button" id="amadex-right-tab-preview" class="amadex-right-tab amadex-right-tab-active" style="padding:8px 18px;border:none;background:none;cursor:pointer;font-weight:600;font-size:13px;color:#0E7D3F;border-bottom:2px solid #0E7D3F;margin-bottom:-2px;">📧 <?php _e('Preview', 'amadex'); ?></button>
                            <button type="button" id="amadex-right-tab-code" class="amadex-right-tab" style="padding:8px 18px;border:none;background:none;cursor:pointer;font-weight:600;font-size:13px;color:#666;border-bottom:2px solid transparent;margin-bottom:-2px;">💻 <?php _e('HTML / CSS Editor', 'amadex'); ?></button>
                        </div>

                        <!-- Preview panel -->
                        <div id="amadex-right-panel-preview" class="amadex-email-template-preview-inner">
                            <p style="margin:0 0 8px 0;"><strong><?php _e('Live preview', 'amadex'); ?></strong> <?php _e('Changes update instantly.', 'amadex'); ?></p>
                            <iframe id="amadex-email-preview-iframe" src="about:blank" style="width:375px; max-width:100%; height:600px; border:1px solid #ccc; background:#fff; display:block;"></iframe>
                        </div>

                        <!-- HTML/CSS Editor panel -->
                        <div id="amadex-right-panel-code" style="display:none; flex-direction:column; gap:12px; background:#1e1e1e; border:1px solid #333; border-radius:4px; padding:16px; height:calc(100vh - 320px); min-height:500px; overflow:auto;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                <span style="color:#fff;font-weight:600;font-size:13px;">📄 <?php _e('Email HTML Source', 'amadex'); ?></span>
                                <div style="display:flex;gap:8px;">
                                    <button type="button" id="amadex-code-load" class="button" style="font-size:12px;background:#0E7D3F;color:#fff;border-color:#0E7D3F;">↓ <?php _e('Load Live HTML', 'amadex'); ?></button>
                                    <button type="button" id="amadex-code-clear" class="button" style="font-size:12px;color:#ff6b6b;border-color:#ff6b6b;background:transparent;"><?php _e('Clear', 'amadex'); ?></button>
                                    <button type="button" id="amadex-code-copy" class="button" style="font-size:12px;background:#333;color:#fff;border-color:#555;"><?php _e('Copy', 'amadex'); ?></button>
                                </div>
                            </div>
                            <p style="color:#aaa;font-size:11px;margin:0 0 8px;">
                                <?php _e('Edit the HTML directly. Changes update the preview instantly. Click "Load Live HTML" to populate with the current generated template.', 'amadex'); ?>
                            </p>
                            <textarea id="amadex-code-html" name="amadex_email_template_settings[custom_html]" style="flex:1;width:100%;min-height:340px;background:#1e1e1e;color:#d4d4d4;font-family:monospace;font-size:13px;line-height:1.6;border:1px solid #444;border-radius:4px;padding:12px;resize:vertical;tab-size:2;" placeholder="<!-- Click 'Load Live HTML' to populate with the current template -->"><?php echo esc_textarea($opts['custom_html'] ?? ''); ?></textarea>
                            <div style="margin-top:8px;">
                                <label style="color:#aaa;font-size:12px;font-weight:600;">🎨 <?php _e('Custom CSS', 'amadex'); ?></label>
                                <textarea id="amadex-code-css" name="amadex_email_template_settings[custom_css]" style="width:100%;min-height:120px;background:#1e1e1e;color:#ce9178;font-family:monospace;font-size:13px;line-height:1.6;border:1px solid #444;border-radius:4px;padding:12px;resize:vertical;margin-top:6px;tab-size:2;" placeholder="/* Custom CSS - injected into the email <head> */"><?php echo esc_textarea($opts['custom_css'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div id="amadex-grapesjs-wrap" class="amadex-email-mode-panel" style="display:none;">
            <div class="amadex-builder-toolbar" style="margin-bottom:12px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <button type="button" class="button button-primary" id="amadex-builder-save"><?php _e('Save design', 'amadex'); ?></button>
                <button type="button" class="button" id="amadex-builder-save-block"><?php _e('Save selection as block', 'amadex'); ?></button>
                <button type="button" class="button" id="amadex-builder-import-html"><?php _e('Import HTML', 'amadex'); ?></button>
                <button type="button" class="button" id="amadex-builder-version-history"><?php _e('Version history', 'amadex'); ?></button>
                <span id="amadex-builder-save-status" style="color:#666;"></span>
            </div>
            <div id="amadex-version-history-modal" class="amadex-modal-wrap" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100000; align-items:center; justify-content:center;">
                <div class="amadex-modal-inner" style="background:#fff; max-width:400px; width:90%; padding:20px; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.2);">
                    <h3 style="margin-top:0;"><?php _e('Version history', 'amadex'); ?></h3>
                    <p class="description"><?php _e('Restore a previous design. You can then save it to make it current.', 'amadex'); ?></p>
                    <ul id="amadex-version-history-list" style="list-style:none; padding:0; margin:0 0 16px 0; max-height:240px; overflow:auto;"></ul>
                    <p><button type="button" class="button" id="amadex-version-history-close"><?php _e('Close', 'amadex'); ?></button></p>
                </div>
            </div>
            <div id="amadex-import-html-modal" class="amadex-modal-wrap" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100000; align-items:center; justify-content:center;">
                <div class="amadex-modal-inner" style="background:#fff; max-width:640px; width:90%; max-height:85vh; overflow:auto; padding:20px; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.2);">
                    <h3 style="margin-top:0;"><?php _e('Import custom HTML template', 'amadex'); ?></h3>
                    <p class="description"><?php _e('Paste your email HTML below or upload an HTML file. Table-based layouts work best. Full HTML documents: only the body content will be used.', 'amadex'); ?></p>
                    <p class="description" style="margin-top:8px; padding:10px; background:#f0f6fc; border-left:4px solid #2271b1;"><strong><?php _e('Dynamic booking data', 'amadex'); ?>:</strong> <?php _e('To show flight and passenger details in your template, include this exact comment where the content should appear:', 'amadex'); ?> <code style="background:#fff;padding:2px 6px;">&lt;!-- AMADEX_BOOKING_CONTENT --&gt;</code> <?php _e('When a customer books, it will be replaced automatically with their itinerary, passengers, and booking details.', 'amadex'); ?></p>
                    <p><strong><?php _e('Upload template file', 'amadex'); ?></strong><br>
                        <input type="file" id="amadex-import-html-file" accept=".html,.htm,text/html" style="margin-top:4px;">
                        <span class="description"><?php _e('Choose an .html file to load into the editor.', 'amadex'); ?></span>
                    </p>
                    <p><label for="amadex-import-html-textarea"><strong><?php _e('HTML', 'amadex'); ?></strong></label></p>
                    <textarea id="amadex-import-html-textarea" rows="12" class="large-text code" style="width:100%; font-family:monospace; font-size:12px;" placeholder="&lt;table&gt;&lt;tr&gt;&lt;td&gt;..."></textarea>
                    <p style="margin-top:12px;"><label for="amadex-import-html-css"><strong><?php _e('CSS (optional)', 'amadex'); ?></strong></label></p>
                    <textarea id="amadex-import-html-css" rows="4" class="large-text code" style="width:100%; font-family:monospace; font-size:12px;" placeholder="/* Optional styles */"></textarea>
                    <p style="margin-top:16px;">
                        <button type="button" class="button button-primary" id="amadex-import-html-apply"><?php _e('Replace content with this HTML', 'amadex'); ?></button>
                        <button type="button" class="button" id="amadex-import-html-cancel"><?php _e('Cancel', 'amadex'); ?></button>
                    </p>
                </div>
            </div>
            <div id="amadex-builder-saved-blocks-wrap" style="margin-bottom:12px; font-size:12px;">
                <strong><?php _e('Saved blocks', 'amadex'); ?>:</strong>
                <span id="amadex-builder-saved-blocks-list"><?php
                                                            foreach ($saved_blocks as $sb) {
                                                                echo '<span class="amadex-saved-block-tag" data-id="' . esc_attr($sb['id']) . '">' . esc_html($sb['label']) . ' <button type="button" class="button-link amadex-delete-block" data-id="' . esc_attr($sb['id']) . '">' . esc_html__('Delete', 'amadex') . '</button></span> ';
                                                            }
                                                            ?></span>
                <span id="amadex-builder-saved-blocks-none" style="color:#666;<?php echo empty($saved_blocks) ? '' : ' display:none;'; ?>"><?php echo esc_html__('None yet. Select a component and use "Save selection as block".', 'amadex'); ?></span>
            </div>
            <div id="amadex-grapesjs-editor" style="min-height:70vh; border:1px solid #ccd0d4; border-radius:4px;"></div>
        </div>
        <style>
            .amadex-email-template-layout {
                display: flex;
                gap: 24px;
                margin-top: 20px;
                max-height: calc(100vh - 220px);
                align-items: stretch;
                flex-wrap: nowrap;
            }

            .amadex-email-template-sidebar {
                flex: 0 0 320px;
                max-width: 320px;
                min-height: 0;
                overflow-y: auto;
                overflow-x: hidden;
            }

            .amadex-email-template-preview {
                flex: 1;
                min-width: 320px;
                min-height: 0;
                display: flex;
                flex-direction: column;
            }

            .amadex-email-template-preview-inner {
                background: #f0f0f1;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 16px;
                overflow: auto;
                flex: 1;
                min-height: 0;
            }

            .amadex-email-template-sidebar .amadex-range-wrap {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-top: 4px;
            }

            .amadex-email-template-sidebar .amadex-range {
                flex: 1;
                min-width: 0;
                max-width: 180px;
                height: 6px;
                accent-color: #0E7D3F;
            }

            .amadex-email-template-sidebar .amadex-slider-value {
                font-weight: 600;
                min-width: 2.5em;
                text-align: right;
                font-variant-numeric: tabular-nums;
            }

            .amadex-email-template-sidebar .amadex-color-row .wp-picker-container {
                margin-top: 4px;
            }

            .amadex-email-template-sidebar .amadex-email-template-color {
                max-width: 100px;
            }

            .amadex-email-template-sidebar .amadex-logo-row {
                margin-top: 6px;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
            }

            .amadex-email-template-sidebar .amadex-logo-row .amadex-logo-preview-wrap {
                flex-shrink: 0;
            }

            .amadex-email-mode-btn.active {
                background: #0E7D3F;
                color: #fff;
                border-color: #0E7D3F;
            }

            #amadex-email-template-simple-wrap {
                min-height: 400px;
            }

            @media (max-width: 782px) {
                .amadex-email-template-layout {
                    flex-wrap: wrap;
                    max-height: none;
                }

                .amadex-email-template-sidebar {
                    max-width: 100%;
                }
            }
        </style>
        <script>
            jQuery(document).ready(function($) {
                var $simpleWrap = $('#amadex-email-template-simple-wrap');
                var $builderWrap = $('#amadex-grapesjs-wrap');
                $simpleWrap.show().css('display', 'block');
                $builderWrap.hide();

                var $form = $('#amadex-email-template-form');
                var $iframe = $('#amadex-email-preview-iframe');
                var livePreviewNonce = '<?php echo esc_js($live_preview_nonce); ?>';
                var livePreviewTimer = null;
                var livePreviewDebounce = 400;

                try {
                    if (typeof $.fn.wpColorPicker !== 'undefined') {
                        $form.find('.amadex-email-template-color').wpColorPicker({
                            change: scheduleLivePreview
                        });
                    }
                } catch (e) {
                    console.warn('Amadex: wpColorPicker init skipped', e);
                }

                $form.find('.amadex-range').on('input change', function() {
                    var $r = $(this),
                        $wrap = $r.closest('.amadex-range-wrap'),
                        scale = parseFloat($r.data('scale')) || 1;
                    var val = scale !== 1 ? (parseFloat($r.val()) * scale).toFixed(1) : $r.val();
                    $wrap.find('.amadex-slider-value').text(val);
                    var $hidden = $wrap.find('input[type="hidden"][name^="amadex_email_template_settings"]');
                    if ($hidden.length) $hidden.val(val);
                    scheduleLivePreview();
                });

                var amadexLogoFrame = null;
                var amadexLogoCurrentTarget = null;
                $form.on('click', '.amadex-logo-select', function(e) {
                    e.preventDefault();
                    amadexLogoCurrentTarget = $(this).data('target');
                    if (!amadexLogoFrame) {
                        amadexLogoFrame = wp.media({
                            title: '<?php echo esc_js(__('Select logo', 'amadex')); ?>',
                            button: {
                                text: '<?php echo esc_js(__('Use this image', 'amadex')); ?>'
                            },
                            library: {
                                type: 'image'
                            },
                            multiple: false
                        });
                        amadexLogoFrame.on('select', function() {
                            var target = amadexLogoCurrentTarget;
                            var inputId = target === 'primary' ? 'amadex-logo_primary_id' : 'amadex-logo_secondary_id';
                            var previewId = target === 'primary' ? 'amadex-logo-primary-preview' : 'amadex-logo-secondary-preview';
                            var att = amadexLogoFrame.state().get('selection').first().toJSON();
                            $('#' + inputId).val(att.id);
                            $('#' + previewId).attr('src', att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url);
                            scheduleLivePreview();
                        });
                    }
                    amadexLogoFrame.open();
                });
                $form.on('click', '.amadex-logo-remove', function(e) {
                    e.preventDefault();
                    var target = $(this).data('target');
                    var inputId = target === 'primary' ? 'amadex-logo_primary_id' : 'amadex-logo_secondary_id';
                    var $preview = target === 'primary' ? $('#amadex-logo-primary-preview') : $('#amadex-logo-secondary-preview');
                    $('#' + inputId).val('0');
                    var ph = $preview.attr('data-placeholder');
                    if (ph) $preview.attr('src', ph);
                    scheduleLivePreview();
                });

                function collectTemplateSettings() {
                    var data = {
                        type: $('#amadex-email-preview-type').val()
                    };
                    data.amadex_email_template_settings = {};
                    $form.find('[name^="amadex_email_template_settings["]').each(function() {
                        var name = $(this).attr('name');
                        var m = name.match(/amadex_email_template_settings\[([^\]]+)\]/);
                        if (m && m[1]) data.amadex_email_template_settings[m[1]] = $(this).val();
                    });
                    return data;
                }

                function runLivePreview() {
                    var payload = collectTemplateSettings();
                    payload.action = 'amadex_email_template_live_preview';
                    payload.nonce = livePreviewNonce;
                    $.post(ajaxurl, payload).done(function(html) {
                        var doc = $iframe[0].contentDocument;
                        if (doc) {
                            doc.open();
                            doc.write(html);
                            doc.close();
                        }
                    }).fail(function() {
                        var doc = $iframe[0].contentDocument;
                        if (doc) {
                            doc.open();
                            doc.write('<p style="padding:1em; color:#b32d2e;">Preview could not be loaded.</p>');
                            doc.close();
                        }
                    });
                }

                function scheduleLivePreview() {
                    if (livePreviewTimer) clearTimeout(livePreviewTimer);
                    livePreviewTimer = setTimeout(runLivePreview, livePreviewDebounce);
                }

                $form.on('input change', '[name^="amadex_email_template_settings["], #amadex-email-preview-type', scheduleLivePreview);
                // Load preview immediately on page load
                runLivePreview();

                // If custom_html is already saved, auto-load it into editor and show notice
                var savedCustomHtml = <?php echo json_encode($opts['custom_html'] ?? ''); ?>;
                if (savedCustomHtml && savedCustomHtml.trim() !== '') {
                    // Show a notice that custom HTML is active
                    $('#amadex-right-panel-preview').before(
                        '<div id="amadex-custom-html-notice" style="background:#d4edda;border:1px solid #28a745;border-radius:4px;padding:8px 12px;margin-bottom:8px;font-size:13px;color:#155724;">' +
                        '✅ <strong>Custom HTML is active</strong> — emails are using your saved HTML. ' +
                        '<a href="#" id="amadex-edit-custom-html-link">Click to edit</a> | ' +
                        '<a href="#" id="amadex-disable-custom-html-link" style="color:#b32d2e;">Disable (clear)</a>' +
                        '</div>'
                    );
                    $('#amadex-edit-custom-html-link').on('click', function(e) {
                        e.preventDefault();
                        $('#amadex-right-tab-code').trigger('click');
                    });
                    $('#amadex-disable-custom-html-link').on('click', function(e) {
                        e.preventDefault();
                        if (confirm('This will clear your custom HTML and revert to the default template settings. Continue?')) {
                            $('#amadex-code-html').val('');
                            $('#amadex-code-css').val('');
                            scheduleLivePreview();
                            $('#amadex-custom-html-notice').remove();
                            // Auto-save via AJAX
                            $.post(ajaxurl, {
                                action: 'amadex_clear_custom_email_html',
                                nonce: livePreviewNonce
                            });
                        }
                    });
                }

                // ── Right panel tab switching ─────────────────────────────
                $('#amadex-right-tab-preview').on('click', function() {
                    $(this).css({
                        'color': '#0E7D3F',
                        'border-bottom-color': '#0E7D3F'
                    });
                    $('#amadex-right-tab-code').css({
                        'color': '#666',
                        'border-bottom-color': 'transparent'
                    });
                    $('#amadex-right-panel-preview').show();
                    $('#amadex-right-panel-code').hide();
                });
                $('#amadex-right-tab-code').on('click', function() {
                    $(this).css({
                        'color': '#0E7D3F',
                        'border-bottom-color': '#0E7D3F'
                    });
                    $('#amadex-right-tab-preview').css({
                        'color': '#666',
                        'border-bottom-color': 'transparent'
                    });
                    $('#amadex-right-panel-preview').hide();
                    $('#amadex-right-panel-code').css('display', 'flex');
                });

                // ── Load live HTML into code editor ───────────────────────
                $('#amadex-code-load').on('click', function() {
                    var $btn = $(this);
                    $btn.text('Loading...').prop('disabled', true);
                    var payload = collectTemplateSettings();
                    payload.action = 'amadex_email_template_live_preview';
                    payload.nonce = livePreviewNonce;
                    $.post(ajaxurl, payload).done(function(html) {
                        // Format HTML with basic indentation
                        $('#amadex-code-html').val(html);
                        $('#amadex-code-html').trigger('input');
                        $btn.text('↓ Load Live HTML').prop('disabled', false);
                    }).fail(function() {
                        alert('Could not load template HTML. Please try again.');
                        $btn.text('↓ Load Live HTML').prop('disabled', false);
                    });
                });

                // ── Clear custom HTML ──────────────────────────────────────
                $('#amadex-code-clear').on('click', function() {
                    if (confirm('Clear the custom HTML? The template will revert to the settings on the left.')) {
                        $('#amadex-code-html').val('').trigger('input');
                        scheduleLivePreview();
                    }
                });

                // ── Copy HTML to clipboard ─────────────────────────────────
                $('#amadex-code-copy').on('click', function() {
                    var $btn = $(this);
                    var text = $('#amadex-code-html').val();
                    if (!text) {
                        alert('Nothing to copy. Load the HTML first.');
                        return;
                    }
                    navigator.clipboard.writeText(text).then(function() {
                        $btn.text('Copied!');
                        setTimeout(function() {
                            $btn.text('Copy');
                        }, 2000);
                    }).catch(function() {
                        $('#amadex-code-html').select();
                        document.execCommand('copy');
                        $btn.text('Copied!');
                        setTimeout(function() {
                            $btn.text('Copy');
                        }, 2000);
                    });
                });

                $('#amadex-code-html, #amadex-code-css').on('input', function() {
                    scheduleLivePreview();
                    $('#amadex-unsaved-notice').show();
                });

                // Hide notice when form is saved
                $('#amadex-email-template-form').on('submit', function() {
                    $('#amadex-unsaved-notice').hide();
                });

                // ── Tab key support in textareas ───────────────────────────
                $('#amadex-code-html, #amadex-code-css').on('keydown', function(e) {
                    if (e.key === 'Tab') {
                        e.preventDefault();
                        var el = this,
                            start = el.selectionStart,
                            end = el.selectionEnd;
                        el.value = el.value.substring(0, start) + '  ' + el.value.substring(end);
                        el.selectionStart = el.selectionEnd = start + 2;
                    }
                });


                $('.amadex-device-width').on('click', function() {
                    var w = $(this).data('width');
                    $('.amadex-device-width').removeClass('button-primary');
                    $(this).addClass('button-primary');
                    $iframe.css('width', w + 'px');
                });
                $('.amadex-device-width[data-width="375"]').addClass('button-primary');

                var amadexBuilderEditor = null;
                var amadexBuilderDesign = <?php echo $builder_design !== '' ? wp_json_encode($builder_design) : '""'; ?>;
                var amadexBuilderSaveNonce = '<?php echo esc_js($builder_save_nonce); ?>';
                var amadexSavedBlocks = <?php echo wp_json_encode($saved_blocks); ?>;

                $('#amadex-builder-import-html').on('click', function() {
                    $('#amadex-import-html-modal').css('display', 'flex').show();
                    $('#amadex-import-html-file').val('');
                });
                $('#amadex-import-html-file').on('change', function() {
                    var input = this;
                    var file = input.files && input.files[0];
                    if (!file) return;
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var html = e.target && e.target.result ? String(e.target.result) : '';
                        $('#amadex-import-html-textarea').val(html);
                    };
                    reader.onerror = function() {
                        alert('<?php echo esc_js(__('Could not read the file.', 'amadex')); ?>');
                    };
                    reader.readAsText(file, 'UTF-8');
                });
                $(document).on('click', '#amadex-import-html-cancel', function() {
                    $('#amadex-import-html-modal').hide();
                });
                $(document).on('click', '#amadex-import-html-modal', function(e) {
                    if (e.target === this) $('#amadex-import-html-modal').hide();
                });
                $(document).on('click', '#amadex-import-html-modal .amadex-modal-inner', function(e) {
                    e.stopPropagation();
                });
                $(document).on('click', '#amadex-import-html-apply', function() {
                    if (!amadexBuilderEditor) {
                        alert('<?php echo esc_js(__('The drag-and-drop editor could not load, so Import HTML is not available. Use \'Simple style\' above to edit your template, or allow scripts from cdnjs.cloudflare.com and unpkg.com then switch to \'Drag & drop builder\' again.', 'amadex')); ?>');
                        return;
                    }
                    var htmlRaw = $('#amadex-import-html-textarea').val() || '';
                    var cssRaw = ($('#amadex-import-html-css').val() || '').trim();
                    if (!htmlRaw.replace(/^\s+|\s+$/g, '')) {
                        alert('<?php echo esc_js(__('Please paste some HTML first.', 'amadex')); ?>');
                        return;
                    }
                    var html = htmlRaw.replace(/^\s+|\s+$/g, '');
                    try {
                        var parser = new DOMParser();
                        var doc = parser.parseFromString(html, 'text/html');
                        if (doc.body && doc.body.innerHTML) html = doc.body.innerHTML;
                    } catch (e) {}
                    try {
                        amadexBuilderEditor.setComponents(html);
                        if (cssRaw) amadexBuilderEditor.setStyle(cssRaw);
                        $('#amadex-import-html-modal').hide();
                        $('#amadex-builder-save-status').text('<?php echo esc_js(__('Content imported. Click Save design to keep it.', 'amadex')); ?>');
                        setTimeout(function() {
                            $('#amadex-builder-save-status').text('');
                        }, 4000);
                    } catch (err) {
                        alert('<?php echo esc_js(__('Could not load HTML into the editor.', 'amadex')); ?>');
                    }
                });
                $('#amadex-builder-version-history').on('click', function() {
                    $('#amadex-version-history-modal').css('display', 'flex').show();
                    $('#amadex-version-history-list').html('<li style="padding:8px;color:#666;"><?php echo esc_js(__('Loading...', 'amadex')); ?></li>');
                    $.post(ajaxurl, {
                        action: 'amadex_get_email_builder_history'
                    }).done(function(r) {
                        var $list = $('#amadex-version-history-list');
                        $list.empty();
                        if (r.success && r.data.versions && r.data.versions.length) {
                            r.data.versions.forEach(function(v) {
                                $list.append('<li style="padding:8px 0; border-bottom:1px solid #eee;"><span style="margin-right:10px;">' + (v.label || v.time) + '</span><button type="button" class="button button-small amadex-restore-version" data-time="' + v.time + '"><?php echo esc_js(__('Restore', 'amadex')); ?></button></li>');
                            });
                        } else {
                            $list.append('<li style="padding:8px;color:#666;"><?php echo esc_js(__('No previous versions yet. Save the design to create history.', 'amadex')); ?></li>');
                        }
                    }).fail(function() {
                        $('#amadex-version-history-list').html('<li style="padding:8px;color:#b32d2e;"><?php echo esc_js(__('Could not load history.', 'amadex')); ?></li>');
                    });
                });
                $(document).on('click', '#amadex-version-history-close, #amadex-version-history-modal', function(e) {
                    if (e.target === this || $(this).attr('id') === 'amadex-version-history-close') $('#amadex-version-history-modal').hide();
                });
                $(document).on('click', '#amadex-version-history-modal .amadex-modal-inner', function(e) {
                    e.stopPropagation();
                });
                $(document).on('click', '.amadex-restore-version', function() {
                    var time = $(this).data('time');
                    if (!time || !amadexBuilderEditor) return;
                    var $btn = $(this);
                    $btn.prop('disabled', true).text('<?php echo esc_js(__('Restoring...', 'amadex')); ?>');
                    $.post(ajaxurl, {
                        action: 'amadex_restore_email_builder_version',
                        nonce: amadexBuilderSaveNonce,
                        time: time
                    }).done(function(r) {
                        if (r.success && r.data && r.data.design !== undefined) {
                            try {
                                var data = typeof r.data.design === 'string' ? JSON.parse(r.data.design) : r.data.design;
                                if (data && (data.components || data.html)) amadexBuilderEditor.loadProjectData(data).catch(function() {});
                                $('#amadex-version-history-modal').hide();
                                $('#amadex-builder-save-status').text('<?php echo esc_js(__('Version restored. Click Save design to keep it.', 'amadex')); ?>');
                                setTimeout(function() {
                                    $('#amadex-builder-save-status').text('');
                                }, 4000);
                            } catch (err) {}
                        }
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Restore', 'amadex')); ?>');
                    }).fail(function() {
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Restore', 'amadex')); ?>');
                    });
                });
                $(document).on('click', '#amadex-builder-saved-blocks-wrap .amadex-delete-block', function(e) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    if (!id) return;
                    var $tag = $(this).closest('.amadex-saved-block-tag');
                    $.post(ajaxurl, {
                        action: 'amadex_delete_email_builder_block',
                        nonce: amadexBuilderSaveNonce,
                        id: id
                    }).done(function(r) {
                        if (r.success) {
                            $tag.remove();
                            try {
                                if (amadexBuilderEditor && amadexBuilderEditor.BlockManager && amadexBuilderEditor.BlockManager.get(id)) amadexBuilderEditor.BlockManager.remove(id);
                            } catch (err) {}
                            if ($('#amadex-builder-saved-blocks-list .amadex-saved-block-tag').length === 0) $('#amadex-builder-saved-blocks-none').show();
                        }
                    });
                });
                $('.amadex-email-mode-btn').on('click', function() {
                    var mode = $(this).data('mode');
                    $('.amadex-email-mode-btn').removeClass('active');
                    $(this).addClass('active');
                    if (mode === 'simple') {
                        $simpleWrap.show().css('display', 'block');
                        $builderWrap.hide();
                    } else {
                        $simpleWrap.hide();
                        $builderWrap.show();
                        if (!amadexBuilderEditor) {
                            if (typeof grapesjs !== 'undefined') {
                                try {
                                    initAmadexGrapesJS();
                                } catch (err) {
                                    $('#amadex-grapesjs-editor').html('<div style="padding:24px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;"><p><strong><?php echo esc_js(__('Editor could not load.', 'amadex')); ?></strong></p><p><?php echo esc_js(__('Use \'Simple style\' above to edit colors and layout, or allow scripts from cdnjs.cloudflare.com and unpkg.com in your firewall or browser.', 'amadex')); ?></p><button type="button" class="button" onclick="jQuery(\'.amadex-email-mode-btn[data-mode=simple]\').click();"><?php echo esc_js(__('Switch to Simple style', 'amadex')); ?></button></div>');
                                }
                            } else {
                                $('#amadex-grapesjs-editor').html('<div style="padding:24px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;"><p><strong><?php echo esc_js(__('Editor could not load.', 'amadex')); ?></strong></p><p><?php echo esc_js(__('Use \'Simple style\' above to edit your email template.', 'amadex')); ?></p><button type="button" class="button" onclick="jQuery(\'.amadex-email-mode-btn[data-mode=simple]\').click();"><?php echo esc_js(__('Switch to Simple style', 'amadex')); ?></button></div>');
                            }
                        }
                    }
                });

                function initAmadexGrapesJS() {
                    var editorOpts = {
                        container: '#amadex-grapesjs-editor',
                        height: '70vh',
                        storageManager: false
                    };
                    try {
                        editorOpts.plugins = ['grapesjs-preset-newsletter'];
                        editorOpts.pluginOpts = {
                            'grapesjs-preset-newsletter': {}
                        };
                        amadexBuilderEditor = grapesjs.init(editorOpts);
                    } catch (e1) {
                        editorOpts.plugins = [];
                        editorOpts.pluginOpts = {};
                        amadexBuilderEditor = grapesjs.init(editorOpts);
                        addAmadexEmailBlocks(amadexBuilderEditor);
                    }
                    amadexBuilderEditor.BlockManager.add('amadex-booking-content', {
                        label: '<?php echo esc_js(__('Booking content', 'amadex')); ?>',
                        category: '<?php echo esc_js(__('Amadex', 'amadex')); ?>',
                        content: '<div id="amadex-booking-content" style="min-height:60px;padding:16px;background:#f9fafb;border:2px dashed #d1d5db;color:#6b7280;font-size:13px;"><!-- AMADEX_BOOKING_CONTENT --></div>'
                    });
                    var savedBlocksList = typeof amadexSavedBlocks !== 'undefined' && Array.isArray(amadexSavedBlocks) ? amadexSavedBlocks : [];
                    savedBlocksList.forEach(function(b) {
                        if (b.id && b.label) {
                            amadexBuilderEditor.BlockManager.add(b.id, {
                                label: b.label,
                                category: '<?php echo esc_js(__('My blocks', 'amadex')); ?>',
                                content: b.content || ''
                            });
                        }
                    });
                    var savedDesign = typeof amadexBuilderDesign === 'string' && amadexBuilderDesign ? amadexBuilderDesign : null;
                    if (savedDesign) {
                        try {
                            var data = typeof savedDesign === 'string' ? JSON.parse(savedDesign) : savedDesign;
                            if (data && (data.components || data.html)) {
                                amadexBuilderEditor.loadProjectData(data).catch(function() {});
                            }
                        } catch (e) {}
                    }
                    $('#amadex-builder-save').on('click', function() {
                        var $btn = $(this);
                        var $status = $('#amadex-builder-save-status');
                        $status.text('<?php echo esc_js(__('Saving...', 'amadex')); ?>');
                        var projectData = amadexBuilderEditor.getProjectData();
                        var html = '';
                        try {
                            html = amadexBuilderEditor.runCommand('gjs-get-inlined-html') || amadexBuilderEditor.getHtml() + '<style>' + amadexBuilderEditor.getCss() + '</style>';
                        } catch (err) {
                            html = amadexBuilderEditor.getHtml() + '<style>' + amadexBuilderEditor.getCss() + '</style>';
                        }
                        $.post(ajaxurl, {
                            action: 'amadex_save_email_builder',
                            nonce: amadexBuilderSaveNonce,
                            design: JSON.stringify(projectData),
                            html: html
                        }).done(function(r) {
                            if (r.success) {
                                $status.text('<?php echo esc_js(__('Saved.', 'amadex')); ?>');
                                setTimeout(function() {
                                    $status.text('');
                                }, 2000);
                            } else {
                                $status.text(r.data && r.data.message ? r.data.message : 'Error');
                            }
                        }).fail(function() {
                            $status.text('<?php echo esc_js(__('Save failed.', 'amadex')); ?>');
                        });
                    });
                    $('#amadex-builder-save-block').on('click', function() {
                        var sel = amadexBuilderEditor.getSelected();
                        if (!sel) {
                            alert('<?php echo esc_js(__('Select a component in the canvas first (e.g. a section or block), then click again.', 'amadex')); ?>');
                            return;
                        }
                        var html = '';
                        try {
                            var el = sel.getEl ? sel.getEl() : null;
                            if (el && el.outerHTML) html = el.outerHTML;
                            else if (sel.view && sel.view.$el && sel.view.$el[0]) html = sel.view.$el[0].outerHTML;
                        } catch (e) {}
                        if (!html) {
                            alert('<?php echo esc_js(__('Could not get content for this selection.', 'amadex')); ?>');
                            return;
                        }
                        var label = prompt('<?php echo esc_js(__('Block name (e.g. Header, Footer):', 'amadex')); ?>', '');
                        if (label === null || label.replace(/^\s+|\s+$/g, '') === '') return;
                        label = label.replace(/^\s+|\s+$/g, '');
                        var $status = $('#amadex-builder-save-status');
                        $status.text('<?php echo esc_js(__('Saving block...', 'amadex')); ?>');
                        $.post(ajaxurl, {
                            action: 'amadex_save_email_builder_block',
                            nonce: amadexBuilderSaveNonce,
                            label: label,
                            content: html
                        }).done(function(r) {
                            if (r.success && r.data && r.data.block) {
                                var b = r.data.block;
                                amadexBuilderEditor.BlockManager.add(b.id, {
                                    label: b.label,
                                    category: '<?php echo esc_js(__('My blocks', 'amadex')); ?>',
                                    content: b.content || ''
                                });
                                var safeLabel = (b.label || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                                $('#amadex-builder-saved-blocks-list').append('<span class="amadex-saved-block-tag" data-id="' + b.id + '">' + safeLabel + ' <button type="button" class="button-link amadex-delete-block" data-id="' + b.id + '"><?php echo esc_js(__('Delete', 'amadex')); ?></button></span> ');
                                $('#amadex-builder-saved-blocks-none').hide();
                                $status.text('<?php echo esc_js(__('Block saved.', 'amadex')); ?>');
                                setTimeout(function() {
                                    $status.text('');
                                }, 2000);
                            } else {
                                $status.text(r.data && r.data.message ? r.data.message : 'Error');
                            }
                        }).fail(function() {
                            $status.text('<?php echo esc_js(__('Save failed.', 'amadex')); ?>');
                        });
                    });
                    var amadexAutosaveTimer = null;
                    var amadexAutosaveDelay = 45000;

                    function amadexDoAutosave() {
                        if (!amadexBuilderEditor) return;
                        var projectData = amadexBuilderEditor.getProjectData();
                        var html = '';
                        try {
                            html = amadexBuilderEditor.runCommand('gjs-get-inlined-html') || amadexBuilderEditor.getHtml() + '<style>' + amadexBuilderEditor.getCss() + '</style>';
                        } catch (err) {
                            html = amadexBuilderEditor.getHtml() + '<style>' + amadexBuilderEditor.getCss() + '</style>';
                        }
                        $.post(ajaxurl, {
                            action: 'amadex_save_email_builder',
                            nonce: amadexBuilderSaveNonce,
                            design: JSON.stringify(projectData),
                            html: html,
                            autosave: 1
                        }).done(function(r) {
                            if (r.success) {
                                var $status = $('#amadex-builder-save-status');
                                $status.text('<?php echo esc_js(__('Autosaved', 'amadex')); ?>');
                                setTimeout(function() {
                                    if ($status.text() === '<?php echo esc_js(__('Autosaved', 'amadex')); ?>') $status.text('');
                                }, 2000);
                            }
                        });
                    }
                    try {
                        amadexBuilderEditor.on('update', function() {
                            if (amadexAutosaveTimer) clearTimeout(amadexAutosaveTimer);
                            amadexAutosaveTimer = setTimeout(amadexDoAutosave, amadexAutosaveDelay);
                        });
                    } catch (e) {}
                }

                function addAmadexEmailBlocks(editor) {
                    var bm = editor.BlockManager;
                    bm.add('amadex-section-1', {
                        label: '1 Column',
                        category: 'Layout',
                        content: '<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;"><tr><td style="padding:20px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;"><p style="margin:0;font-size:14px;line-height:1.6;">Content here</p></td></tr></table>'
                    });
                    bm.add('amadex-section-2', {
                        label: '2 Columns',
                        category: 'Layout',
                        content: '<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;"><tr><td width="50%" style="padding:20px;background:#fff;border:1px solid #e5e7eb;vertical-align:top;">Left</td><td width="50%" style="padding:20px;background:#fff;border:1px solid #e5e7eb;vertical-align:top;">Right</td></tr></table>'
                    });
                    bm.add('amadex-text', {
                        label: 'Text',
                        category: 'Basic',
                        content: '<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="padding:12px 0;"><p style="margin:0;font-size:14px;line-height:1.6;">Your text here</p></td></tr></table>'
                    });
                    bm.add('amadex-heading', {
                        label: 'Heading',
                        category: 'Basic',
                        content: '<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="padding:8px 0;"><h2 style="margin:0;font-size:22px;font-weight:700;color:#111827;">Heading</h2></td></tr></table>'
                    });
                    bm.add('amadex-image', {
                        label: 'Image',
                        category: 'Basic',
                        content: '<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="padding:12px 0;"><img src="https://via.placeholder.com/600x200" alt="" style="max-width:100%;height:auto;display:block;border:0;" width="600"></td></tr></table>'
                    });
                    bm.add('amadex-button', {
                        label: 'Button',
                        category: 'Basic',
                        content: '<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="padding:16px 0;"><a href="#" style="display:inline-block;padding:12px 24px;background:#0E7D3F;color:#fff !important;text-decoration:none;font-weight:600;border-radius:8px;font-size:14px;">Button</a></td></tr></table>'
                    });
                    bm.add('amadex-divider', {
                        label: 'Divider',
                        category: 'Basic',
                        content: '<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="padding:16px 0;border-bottom:1px solid #e5e7eb;"></td></tr></table>'
                    });
                }
            });
        </script>
<?php
            }

            /**
             * Render payment settings with accordion UI
             * Groups each merchant gateway into collapsible accordions for better organization
             */
            private function render_payment_settings_with_accordions()
            {
                global $wp_settings_sections, $wp_settings_fields;

                $page = 'amadex_payment_settings';
                $options = get_option('amadex_payment_settings', array());
                $default_gateway = isset($options['default_card_gateway']) ? $options['default_card_gateway'] : 'nmi';

                // Render General Settings (always visible, not in accordion)
                if (isset($wp_settings_sections[$page]['amadex_payment_general_section'])) {
                    echo '<div class="amadex-payment-general-section">';
                    echo '<h2>' . __('General Payment Settings', 'amadex') . '</h2>';
                    echo '<p class="description">' . __('Configure general payment settings that apply to all payment methods.', 'amadex') . '</p>';

                    if (isset($wp_settings_fields[$page]['amadex_payment_general_section'])) {
                        echo '<table class="form-table" role="presentation">';
                        do_settings_fields($page, 'amadex_payment_general_section');
                        echo '</table>';
                    }
                    echo '</div>';
                }

                // Render NMI Gateway (in accordion)
                if (isset($wp_settings_sections[$page]['amadex_payment_nmi_section'])) {
                    $is_default = ($default_gateway === 'nmi');
                    $accordion_class = $is_default ? 'amadex-payment-accordion amadex-accordion-default-open' : 'amadex-payment-accordion';

                    echo '<div class="' . esc_attr($accordion_class) . '">';
                    echo '<div class="amadex-accordion-header">';
                    echo '<div class="amadex-accordion-title">';
                    echo '<span>💳 ' . __('NMI Payment Gateway', 'amadex') . '</span>';
                    echo '</div>';
                    echo '<span class="amadex-accordion-icon"></span>';
                    echo '</div>';

                    echo '<div class="amadex-accordion-content">';
                    if (isset($wp_settings_fields[$page]['amadex_payment_nmi_section'])) {
                        echo '<table class="form-table" role="presentation">';
                        do_settings_fields($page, 'amadex_payment_nmi_section');
                        echo '</table>';
                    }
                    echo '</div>';
                    echo '</div>';
                }

                // Render Stripe Gateway (in accordion)
                if (isset($wp_settings_sections[$page]['amadex_payment_stripe_section'])) {
                    $is_default = ($default_gateway === 'stripe');
                    $accordion_class = $is_default ? 'amadex-payment-accordion amadex-accordion-default-open' : 'amadex-payment-accordion';

                    echo '<div class="' . esc_attr($accordion_class) . '">';
                    echo '<div class="amadex-accordion-header">';
                    echo '<div class="amadex-accordion-title">';
                    echo '<span>💳 ' . __('Stripe Payment Gateway', 'amadex') . '</span>';
                    echo '</div>';
                    echo '<span class="amadex-accordion-icon"></span>';
                    echo '</div>';

                    echo '<div class="amadex-accordion-content">';
                    if (isset($wp_settings_fields[$page]['amadex_payment_stripe_section'])) {
                        echo '<table class="form-table" role="presentation">';
                        do_settings_fields($page, 'amadex_payment_stripe_section');
                        echo '</table>';
                    }
                    echo '</div>';
                    echo '</div>';
                }

                // Render PayPal Gateway (in accordion)
                if (isset($wp_settings_sections[$page]['amadex_payment_paypal_section'])) {
                    $options = get_option('amadex_payment_settings', array());
                    $enable = isset($options['enable_paypal']) ? (int) $options['enable_paypal'] : 0;
                    echo '<div class="amadex-payment-accordion amadex-gateway-with-switch">';
                    echo '<div class="amadex-accordion-header">';
                    echo '<div class="amadex-accordion-title">';
                    echo '<span>💼 ' . __('PayPal Payment Gateway', 'amadex') . '</span>';
                    echo '</div>';
                    echo '<label class="amadex-header-switch" onclick="event.stopPropagation();">';
                    echo '<input type="checkbox" name="amadex_payment_settings[enable_paypal]" value="1" ' . checked(1, $enable, false) . ' class="amadex-toggle-input">';
                    echo '<span class="amadex-toggle-slider"></span>';
                    echo '<span class="amadex-toggle-label">' . ($enable ? __('ON', 'amadex') : __('OFF', 'amadex')) . '</span>';
                    echo '</label>';
                    echo '<span class="amadex-accordion-icon"></span>';
                    echo '</div>';
                    echo '<div class="amadex-accordion-content">';
                    if (isset($wp_settings_fields[$page]['amadex_payment_paypal_section'])) {
                        echo '<table class="form-table" role="presentation">';
                        do_settings_fields($page, 'amadex_payment_paypal_section');
                        echo '</table>';
                    }
                    echo '</div>';
                    echo '</div>';
                }

                // Render Pay with Crypto (in accordion)
                if (isset($wp_settings_sections[$page]['amadex_payment_crypto_transfer_section'])) {
                    $options = get_option('amadex_payment_settings', array());
                    $enable = isset($options['enable_crypto_transfer']) ? (int) $options['enable_crypto_transfer'] : 0;
                    echo '<div class="amadex-payment-accordion amadex-gateway-with-switch">';
                    echo '<div class="amadex-accordion-header">';
                    echo '<div class="amadex-accordion-title">';
                    echo '<span>🔗 ' . __('Pay with Crypto', 'amadex') . '</span>';
                    echo '</div>';
                    echo '<label class="amadex-header-switch" onclick="event.stopPropagation();">';
                    echo '<input type="checkbox" name="amadex_payment_settings[enable_crypto_transfer]" value="1" ' . checked(1, $enable, false) . ' class="amadex-toggle-input">';
                    echo '<span class="amadex-toggle-slider"></span>';
                    echo '<span class="amadex-toggle-label">' . ($enable ? __('ON', 'amadex') : __('OFF', 'amadex')) . '</span>';
                    echo '</label>';
                    echo '<span class="amadex-accordion-icon"></span>';
                    echo '</div>';
                    echo '<div class="amadex-accordion-content">';
                    if (isset($wp_settings_fields[$page]['amadex_payment_crypto_transfer_section'])) {
                        echo '<table class="form-table" role="presentation">';
                        do_settings_fields($page, 'amadex_payment_crypto_transfer_section');
                        echo '</table>';
                    }
                    echo '</div>';
                    echo '</div>';
                }

                // Render Crypto.com Pay (in accordion)
                if (isset($wp_settings_sections[$page]['amadex_payment_crypto_com_section'])) {
                    $options = get_option('amadex_payment_settings', array());
                    $enable = isset($options['enable_crypto_com']) ? (int) $options['enable_crypto_com'] : 0;
                    echo '<div class="amadex-payment-accordion amadex-gateway-with-switch">';
                    echo '<div class="amadex-accordion-header">';
                    echo '<div class="amadex-accordion-title">';
                    echo '<span>💎 ' . __('Crypto.com Pay', 'amadex') . '</span>';
                    echo '</div>';
                    echo '<label class="amadex-header-switch" onclick="event.stopPropagation();">';
                    echo '<input type="checkbox" name="amadex_payment_settings[enable_crypto_com]" value="1" ' . checked(1, $enable, false) . ' class="amadex-toggle-input">';
                    echo '<span class="amadex-toggle-slider"></span>';
                    echo '<span class="amadex-toggle-label">' . ($enable ? __('ON', 'amadex') : __('OFF', 'amadex')) . '</span>';
                    echo '</label>';
                    echo '<span class="amadex-accordion-icon"></span>';
                    echo '</div>';
                    echo '<div class="amadex-accordion-content">';
                    if (isset($wp_settings_fields[$page]['amadex_payment_crypto_com_section'])) {
                        echo '<table class="form-table" role="presentation">';
                        do_settings_fields($page, 'amadex_payment_crypto_com_section');
                        echo '</table>';
                    }
                    echo '</div>';
                    echo '</div>';
                }

                // Render MoonPay Onramp (in accordion)
                if (isset($wp_settings_sections[$page]['amadex_payment_moonpay_onramp_section'])) {
                    $options = get_option('amadex_payment_settings', array());
                    $enable = isset($options['enable_moonpay_onramp']) ? (int) $options['enable_moonpay_onramp'] : 0;
                    echo '<div class="amadex-payment-accordion amadex-gateway-with-switch">';
                    echo '<div class="amadex-accordion-header">';
                    echo '<div class="amadex-accordion-title">';
                    echo '<span>🌙 ' . __('MoonPay Onramp (Pay with card on site)', 'amadex') . '</span>';
                    echo '</div>';
                    echo '<label class="amadex-header-switch" onclick="event.stopPropagation();">';
                    echo '<input type="checkbox" name="amadex_payment_settings[enable_moonpay_onramp]" value="1" ' . checked(1, $enable, false) . ' class="amadex-toggle-input">';
                    echo '<span class="amadex-toggle-slider"></span>';
                    echo '<span class="amadex-toggle-label">' . ($enable ? __('ON', 'amadex') : __('OFF', 'amadex')) . '</span>';
                    echo '</label>';
                    echo '<span class="amadex-accordion-icon"></span>';
                    echo '</div>';
                    echo '<div class="amadex-accordion-content">';
                    if (isset($wp_settings_fields[$page]['amadex_payment_moonpay_onramp_section'])) {
                        echo '<table class="form-table" role="presentation">';
                        do_settings_fields($page, 'amadex_payment_moonpay_onramp_section');
                        echo '</table>';
                    }
                    echo '</div>';
                    echo '</div>';
                }

                // Render MoonPay Commerce (in accordion)
                if (isset($wp_settings_sections[$page]['amadex_payment_moonpay_section'])) {
                    $options = get_option('amadex_payment_settings', array());
                    $enable = isset($options['enable_moonpay_commerce']) ? (int) $options['enable_moonpay_commerce'] : 0;
                    echo '<div class="amadex-payment-accordion amadex-gateway-with-switch">';
                    echo '<div class="amadex-accordion-header">';
                    echo '<div class="amadex-accordion-title">';
                    echo '<span>🌙 ' . __('MoonPay Commerce (Pay with Card → Crypto)', 'amadex') . '</span>';
                    echo '</div>';
                    echo '<label class="amadex-header-switch" onclick="event.stopPropagation();">';
                    echo '<input type="checkbox" name="amadex_payment_settings[enable_moonpay_commerce]" value="1" ' . checked(1, $enable, false) . ' class="amadex-toggle-input">';
                    echo '<span class="amadex-toggle-slider"></span>';
                    echo '<span class="amadex-toggle-label">' . ($enable ? __('ON', 'amadex') : __('OFF', 'amadex')) . '</span>';
                    echo '</label>';
                    echo '<span class="amadex-accordion-icon"></span>';
                    echo '</div>';
                    echo '<div class="amadex-accordion-content">';
                    if (isset($wp_settings_fields[$page]['amadex_payment_moonpay_section'])) {
                        echo '<table class="form-table" role="presentation">';
                        do_settings_fields($page, 'amadex_payment_moonpay_section');
                        echo '</table>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            }
        }
