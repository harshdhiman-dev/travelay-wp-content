<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Amadex Admin Class
 */
class Amadex_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers for database setup
        add_action('wp_ajax_amadex_create_database_tables', array($this, 'ajax_create_database_tables'));
        add_action('wp_ajax_amadex_check_database_status', array($this, 'ajax_check_database_status'));
        
        // AJAX handler for applying performance settings from snapshot
        add_action('wp_ajax_amadex_apply_performance_settings', array($this, 'ajax_apply_performance_settings'));
        add_action('wp_ajax_amadex_delete_performance_metrics', array($this, 'ajax_delete_performance_metrics'));
    }
    
    /**
     * Add admin menu and submenus
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Amadex', 'amadex'),
            __('Amadex', 'amadex'),
            'manage_options',
            'amadex-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-airplane',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'amadex-dashboard',
            __('Dashboard', 'amadex'),
            __('Dashboard', 'amadex'),
            'manage_options',
            'amadex-dashboard',
            array($this, 'dashboard_page')
        );
        
        // API Settings submenu
        add_submenu_page(
            'amadex-dashboard',
            __('API Settings', 'amadex'),
            __('API Settings', 'amadex'),
            'manage_options',
            'amadex-settings',
            array($this, 'settings_page')
        );
        
        // Documentation submenu
        add_submenu_page(
            'amadex-dashboard',
            __('Documentation', 'amadex'),
            __('Documentation', 'amadex'),
            'manage_options',
            'amadex-documentation',
            array($this, 'documentation_page')
        );
        
        // Contact Us submenu
        // add_submenu_page(
        //     'amadex-dashboard',
        //     __('Contact Us', 'amadex'),
        //     __('Contact Us', 'amadex'),
        //     'manage_options',
        //     'amadex-contact',
        //     array($this, 'contact_page')
        // );
        
        // Airports submenu
        add_submenu_page(
            'amadex-dashboard',
            __('Airports', 'amadex'),
            __('Airports', 'amadex'),
            'manage_options',
            'amadex-airports',
            array($this, 'airports_page')
        );
        
        // Database Setup submenu
        add_submenu_page(
            'amadex-dashboard',
            __('Database Setup', 'amadex'),
            __('Database Setup', 'amadex'),
            'manage_options',
            'amadex-database-setup',
            array($this, 'database_setup_page')
        );
        
        // Performance Metrics submenu
        add_submenu_page(
            'amadex-dashboard',
            __('Performance Metrics', 'amadex'),
            __('Performance Metrics', 'amadex'),
            'manage_options',
            'amadex-performance',
            array($this, 'performance_metrics_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'amadex') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'amadex-admin-style',
            AMADEX_URL . 'assets/css/admin.css',
            array(),
            AMADEX_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'amadex-admin-script',
            AMADEX_URL . 'assets/js/admin.js',
            array('jquery'),
            AMADEX_VERSION,
            true
        );
        
        // Add localized script with nonce
        wp_localize_script(
            'amadex-admin-script',
            'amadex_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('amadex_admin_nonce')
            )
        );
    }
    
    /**
     * Dashboard page callback
     */
    public function dashboard_page() {
        ?>
        <div class="wrap amadex-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amadex-admin-header">
                <div class="amadex-logo">
                    <img src="<?php echo AMADEX_URL; ?>assets/images/travelay-logo.png" alt="Travelay Logo">
                </div>
                <div class="amadex-version">
                    <span><?php printf(__('Version %s', 'amadex'), AMADEX_VERSION); ?></span>
                </div>
            </div>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card">
                    <div class="amadex-card-header">
                        <h2><?php _e('API Connection Status', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <?php
                        $settings = get_option('amadex_api_settings');
                        if (empty($settings['api_key']) || empty($settings['api_secret'])) {
                            echo '<div class="amadex-status amadex-status-error">';
                            echo '<span class="dashicons dashicons-warning"></span>';
                            echo '<p>' . __('API credentials not configured', 'amadex') . '</p>';
                            echo '</div>';
                            echo '<p>' . __('Please configure your Amadeus API credentials in the API Settings section.', 'amadex') . '</p>';
                            echo '<a href="' . admin_url('admin.php?page=amadex-settings') . '" class="button button-primary">' . __('Configure API Settings', 'amadex') . '</a>';
                        } else {
                            echo '<div class="amadex-status amadex-status-success">';
                            echo '<span class="dashicons dashicons-yes-alt"></span>';
                            echo '<p>' . __('API credentials configured', 'amadex') . '</p>';
                            echo '</div>';
                            echo '<p>' . __('Your Amadeus API credentials are configured and ready to use.', 'amadex') . '</p>';
                            echo '<a href="' . admin_url('admin.php?page=amadex-settings') . '" class="button button-secondary">' . __('Update API Settings', 'amadex') . '</a>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="amadex-card">
                    <div class="amadex-card-header">
                        <h2><?php _e('Quick Start', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <p><?php _e('Use the shortcode below to display the flight search form on any page or post:', 'amadex'); ?></p>
                        <div class="amadex-shortcode-wrap">
                            <code>[amadex_search_modern]</code>
                            <button class="amadex-copy-shortcode button button-secondary" data-shortcode="[amadex_search_modern]">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                        <p><?php _e('Need help?', 'amadex'); ?> <a href="<?php echo admin_url('admin.php?page=amadex-documentation'); ?>"><?php _e('Check our documentation', 'amadex'); ?></a></p>
                    </div>
                </div>
            </div>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card amadex-card-full">
                    <div class="amadex-card-header">
                        <h2><?php _e('Recent Searches', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <p><?php _e('No recent searches found.', 'amadex'); ?></p>
                        <p><?php _e('Search statistics will appear here after users start using the flight search form on your website.', 'amadex'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page callback
     */
    public function settings_page() {
        // Settings page is handled by the Amadex_Settings class
        amadex()->settings->render_settings_page();
    }
    
    /**
     * Documentation page callback
     */
    public function documentation_page() {
        ?>
        <div class="wrap amadex-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amadex-admin-header">
                <div class="amadex-logo">
                    <img src="<?php echo AMADEX_URL; ?>assets/images/travelay-logo.png" alt="Travelay Logo">
                </div>
            </div>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card amadex-card-full">
                    <div class="amadex-card-header">
                        <h2><?php _e('Documentation', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <div class="amadex-documentation-tabs">
                            <div class="amadex-tabs-nav">
                                <button class="amadex-tab-button active" data-tab="getting-started"><?php _e('Getting Started', 'amadex'); ?></button>
                                <button class="amadex-tab-button" data-tab="shortcodes"><?php _e('Shortcodes', 'amadex'); ?></button>
                                <button class="amadex-tab-button" data-tab="api-info"><?php _e('API Information', 'amadex'); ?></button>
                                <button class="amadex-tab-button" data-tab="faq"><?php _e('FAQ', 'amadex'); ?></button>
                            </div>
                            
                            <div class="amadex-tab-content active" id="getting-started">
                                <h3><?php _e('Getting Started with Amadex', 'amadex'); ?></h3>
                                <p><?php _e('Follow these steps to get started with Amadex:', 'amadex'); ?></p>
                                <ol>
                                    <li>
                                        <strong><?php _e('Sign up for an Amadeus Developer Account', 'amadex'); ?></strong><br>
                                        <?php _e('Visit <a href="https://developers.amadeus.com" target="_blank">Amadeus for Developers</a> to create an account and register your application.', 'amadex'); ?>
                                    </li>
                                    <li>
                                        <strong><?php _e('Get API Key and Secret', 'amadex'); ?></strong><br>
                                        <?php _e('After registering your application, you will receive an API Key and Secret.', 'amadex'); ?>
                                    </li>
                                    <li>
                                        <strong><?php _e('Configure Amadex Settings', 'amadex'); ?></strong><br>
                                        <?php _e('Enter your API Key and Secret in the API Settings page.', 'amadex'); ?>
                                    </li>
                                    <li>
                                        <strong><?php _e('Add the Flight Search Form to Your Website', 'amadex'); ?></strong><br>
                                        <?php _e('Use the shortcode [amadex_search_modern] to display the flight search form on any page or post.', 'amadex'); ?>
                                    </li>
                                </ol>
                            </div>
                            
                            <div class="amadex-tab-content" id="shortcodes">
                                <h3><?php _e('Available Shortcodes', 'amadex'); ?></h3>
                                <div class="amadex-shortcode-doc">
                                    <h4>[amadex_search_modern]</h4>
                                    <p><?php _e('Displays the flight search form.', 'amadex'); ?></p>
                                    <h5><?php _e('Parameters:', 'amadex'); ?></h5>
                                    <ul>
                                        <li><code>title</code> - <?php _e('Custom title for the form (default: "Flight Search")', 'amadex'); ?></li>
                                        <li><code>button_text</code> - <?php _e('Custom text for the search button (default: "Search Flights")', 'amadex'); ?></li>
                                        <li><code>theme</code> - <?php _e('Form theme: "light" or "dark" (default: "light")', 'amadex'); ?></li>
                                    </ul>
                                    <h5><?php _e('Example:', 'amadex'); ?></h5>
                                    <code>[amadex-flight-search title="Find Your Flight" button_text="Find Flights" theme="dark"]</code>
                                </div>
                            </div>
                            
                            <div class="amadex-tab-content" id="api-info">
                                <h3><?php _e('Amadeus API Information', 'amadex'); ?></h3>
                                <p><?php _e('The Amadex plugin uses the Amadeus Flight Offers Search API to retrieve flight information.', 'amadex'); ?></p>
                                <p><?php _e('The Flight Offers Search API provides a list of flight offers based on your search parameters, including:', 'amadex'); ?></p>
                                <ul>
                                    <li><?php _e('Origin and Destination', 'amadex'); ?></li>
                                    <li><?php _e('Departure and Return Dates', 'amadex'); ?></li>
                                    <li><?php _e('Number of Passengers', 'amadex'); ?></li>
                                    <li><?php _e('Travel Class', 'amadex'); ?></li>
                                    <li><?php _e('and more...', 'amadex'); ?></li>
                                </ul>
                                <p><?php _e('For more information about the Amadeus API, visit:', 'amadex'); ?></p>
                                <a href="https://developers.amadeus.com/self-service/category/air" target="_blank" class="button button-secondary"><?php _e('Amadeus API Documentation', 'amadex'); ?></a>
                            </div>
                            
                            <div class="amadex-tab-content" id="faq">
                                <h3><?php _e('Frequently Asked Questions', 'amadex'); ?></h3>
                                
                                <div class="amadex-faq-item">
                                    <div class="amadex-faq-question"><?php _e('How do I get an Amadeus API key?', 'amadex'); ?></div>
                                    <div class="amadex-faq-answer">
                                        <p><?php _e('You need to create an account on the <a href="https://developers.amadeus.com" target="_blank">Amadeus for Developers</a> portal, then create a new application to get your API key and secret.', 'amadex'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="amadex-faq-item">
                                    <div class="amadex-faq-question"><?php _e('Does the plugin support booking functionality?', 'amadex'); ?></div>
                                    <div class="amadex-faq-answer">
                                        <p><?php _e('Currently, the plugin only supports searching for flights. Booking functionality may be added in future versions.', 'amadex'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="amadex-faq-item">
                                    <div class="amadex-faq-question"><?php _e('How do I style the flight search form?', 'amadex'); ?></div>
                                    <div class="amadex-faq-answer">
                                        <p><?php _e('You can use the "theme" parameter in the shortcode to choose between light and dark themes. For custom styling, you can add your own CSS to your theme.', 'amadex'); ?></p>
                                    </div>
                                </div>
                                
                                <div class="amadex-faq-item">
                                    <div class="amadex-faq-question"><?php _e('Are there any API usage limits?', 'amadex'); ?></div>
                                    <div class="amadex-faq-answer">
                                        <p><?php _e('Yes, Amadeus API has usage limits depending on your subscription plan. Please check your Amadeus Developer account for your specific limits.', 'amadex'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Contact Us page callback
     */
    public function contact_page() {
        ?>
        <div class="wrap amadex-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amadex-admin-header">
                <div class="amadex-logo">
                    <img src="<?php echo AMADEX_URL; ?>assets/images/travelay-logo.png" alt="Travelay Logo">
                </div>
            </div>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card">
                    <div class="amadex-card-header">
                        <h2><?php _e('Contact Information', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <div class="amadex-contact-info">
                            <div class="amadex-contact-item">
                                <span class="dashicons dashicons-admin-site-alt3"></span>
                                <div class="amadex-contact-detail">
                                    <h3><?php _e('Website', 'amadex'); ?></h3>
                                    <p><a href="https://flytravelay.com" target="_blank">Flytravelay.com</a></p>
                                </div>
                            </div>
                            <div class="amadex-contact-item">
                                <span class="dashicons dashicons-email-alt"></span>
                                <div class="amadex-contact-detail">
                                    <h3><?php _e('Email', 'amadex'); ?></h3>
                                    <p><a href="mailto:support@flytravelay.com">support@flytravelay.com</a></p>
                                </div>
                            </div>
                            <!-- <div class="amadex-contact-item">
                                <span class="dashicons dashicons-twitter"></span>
                                <div class="amadex-contact-detail">
                                    <h3><?php _e('Twitter', 'amadex'); ?></h3>
                                    <p><a href="https://twitter.com/wphacks4u" target="_blank">@wphacks4u</a></p>
                                </div>
                            </div> -->
                        </div>
                    </div>
                </div>
                
                <div class="amadex-card">
                    <div class="amadex-card-header">
                        <h2><?php _e('Support', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <p><?php _e('For plugin support, please use one of the following options:', 'amadex'); ?></p>
                        <!-- <ul class="amadex-support-options">
                            <li>
                                <a href="https://wphacks4u.com/support" target="_blank" class="button button-primary">
                                    <span class="dashicons dashicons-admin-users"></span>
                                    <?php _e('Support Portal', 'amadex'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="https://wphacks4u.com/documentation" target="_blank" class="button button-secondary">
                                    <span class="dashicons dashicons-book"></span>
                                    <?php _e('Documentation', 'amadex'); ?>
                                </a>
                            </li>
                            <li>
                                <a href="https://github.com/wphacks4u/amadex" target="_blank" class="button button-secondary">
                                    <span class="dashicons dashicons-code-standards"></span>
                                    <?php _e('GitHub Repository', 'amadex'); ?>
                                </a>
                            </li>
                        </ul> -->
                    </div>
                </div>
            </div>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card amadex-card-full">
                    <div class="amadex-card-header">
                        <h2><?php _e('Send us a Message', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <form id="amadex-contact-form" class="amadex-contact-form">
                            <div class="amadex-form-row">
                                <div class="amadex-form-group">
                                    <label for="amadex-name"><?php _e('Name', 'amadex'); ?></label>
                                    <input type="text" id="amadex-name" name="name" required>
                                </div>
                                <div class="amadex-form-group">
                                    <label for="amadex-email"><?php _e('Email', 'amadex'); ?></label>
                                    <input type="email" id="amadex-email" name="email" required>
                                </div>
                            </div>
                            <div class="amadex-form-group">
                                <label for="amadex-subject"><?php _e('Subject', 'amadex'); ?></label>
                                <input type="text" id="amadex-subject" name="subject" required>
                            </div>
                            <div class="amadex-form-group">
                                <label for="amadex-message"><?php _e('Message', 'amadex'); ?></label>
                                <textarea id="amadex-message" name="message" rows="5" required></textarea>
                            </div>
                            <div class="amadex-form-submit">
                                <button type="submit" class="button button-primary"><?php _e('Send Message', 'amadex'); ?></button>
                                <div id="amadex-contact-response"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Airports page callback
     */
    public function airports_page() {
        // Handle airport operations
        if (isset($_POST['action']) && check_admin_referer('amadex_airports_nonce')) {
            $action = sanitize_text_field($_POST['action']);
            
            if ($action === 'add_airport' && current_user_can('manage_options')) {
                $this->add_airport();
            } elseif ($action === 'delete_airport' && current_user_can('manage_options')) {
                $this->delete_airport();
            }
        }
        
        // Get airports
        global $wpdb;
        $table_name = $wpdb->prefix . 'amadex_airports';
        $airports = $wpdb->get_results("SELECT * FROM $table_name ORDER BY code", ARRAY_A);
        ?>
        <div class="wrap amadex-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amadex-admin-header">
                <div class="amadex-logo">
                    <img src="<?php echo AMADEX_URL; ?>assets/images/travelay-logo.png" alt="Travelay Logo">
                </div>
            </div>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card">
                    <div class="amadex-card-header">
                        <h2><?php _e('Add New Airport', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <form method="post" action="">
                            <?php wp_nonce_field('amadex_airports_nonce'); ?>
                            <input type="hidden" name="action" value="add_airport">
                            
                            <div class="amadex-form-row">
                                <div class="amadex-form-group">
                                    <label for="airport-code"><?php _e('Airport Code', 'amadex'); ?></label>
                                    <input type="text" id="airport-code" name="code" required maxlength="3" pattern="[A-Z]{3}">
                                    <small class="description"><?php _e('3-letter IATA code (uppercase)', 'amadex'); ?></small>
                                </div>
                                
                                <div class="amadex-form-group">
                                    <label for="airport-name"><?php _e('Airport Name', 'amadex'); ?></label>
                                    <input type="text" id="airport-name" name="name" required>
                                </div>
                            </div>
                            
                            <div class="amadex-form-row">
                                <div class="amadex-form-group">
                                    <label for="airport-city"><?php _e('City', 'amadex'); ?></label>
                                    <input type="text" id="airport-city" name="city" required>
                                </div>
                                
                                <div class="amadex-form-group">
                                    <label for="airport-country"><?php _e('Country', 'amadex'); ?></label>
                                    <input type="text" id="airport-country" name="country" required>
                                </div>
                            </div>
                            
                            <div class="amadex-form-submit">
                                <button type="submit" class="button button-primary"><?php _e('Add Airport', 'amadex'); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="amadex-card amadex-card-full">
                    <div class="amadex-card-header">
                        <h2><?php _e('Airports', 'amadex'); ?></h2>
                    </div>
                    <div class="amadex-card-body">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Code', 'amadex'); ?></th>
                                    <th><?php _e('Name', 'amadex'); ?></th>
                                    <th><?php _e('City', 'amadex'); ?></th>
                                    <th><?php _e('Country', 'amadex'); ?></th>
                                    <th><?php _e('Actions', 'amadex'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($airports)): ?>
                                    <tr>
                                        <td colspan="5"><?php _e('No airports found.', 'amadex'); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($airports as $airport): ?>
                                        <tr>
                                            <td><?php echo esc_html($airport['code']); ?></td>
                                            <td><?php echo esc_html($airport['name']); ?></td>
                                            <td><?php echo esc_html($airport['city']); ?></td>
                                            <td><?php echo esc_html($airport['country']); ?></td>
                                            <td>
                                                <form method="post" style="display:inline;">
                                                    <?php wp_nonce_field('amadex_airports_nonce'); ?>
                                                    <input type="hidden" name="action" value="delete_airport">
                                                    <input type="hidden" name="airport_id" value="<?php echo esc_attr($airport['id']); ?>">
                                                    <button type="submit" class="button button-small" onclick="return confirm('<?php _e('Are you sure you want to delete this airport?', 'amadex'); ?>')">
                                                        <?php _e('Delete', 'amadex'); ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Add airport to database
     */
    private function add_airport() {
        global $wpdb;
        
        $code = strtoupper(sanitize_text_field($_POST['code']));
        $name = sanitize_text_field($_POST['name']);
        $city = sanitize_text_field($_POST['city']);
        $country = sanitize_text_field($_POST['country']);
        
        // Validate airport code
        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            add_settings_error('amadex_airports', 'invalid_code', __('Airport code must be 3 uppercase letters.', 'amadex'), 'error');
            return;
        }
        
        $table_name = $wpdb->prefix . 'amadex_airports';
        
        // Check if airport code already exists
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE code = %s", $code));
        
        if ($exists) {
            add_settings_error('amadex_airports', 'duplicate_code', __('Airport code already exists.', 'amadex'), 'error');
            return;
        }

       
        // Insert airport
        $result = $wpdb->insert(
            $table_name,
            array(
                'code' => $code,
                'name' => $name,
                'city' => $city,
                'country' => $country
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            add_settings_error('amadex_airports', 'airport_added', __('Airport added successfully.', 'amadex'), 'success');
        } else {
            add_settings_error('amadex_airports', 'airport_error', __('Error adding airport.', 'amadex'), 'error');
        }
    }
    
    
    /**
     * Delete airport from database
     */
    private function delete_airport() {
        global $wpdb;
        
        $airport_id = intval($_POST['airport_id']);
        
        $table_name = $wpdb->prefix . 'amadex_airports';
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $airport_id),
            array('%d')
        );
        
        if ($result) {
            add_settings_error('amadex_airports', 'airport_deleted', __('Airport deleted successfully.', 'amadex'), 'success');
        } else {
            add_settings_error('amadex_airports', 'airport_error', __('Error deleting airport.', 'amadex'), 'error');
        }
         // add_settings_error('display alert message')
    }

    /**
     * Database Setup page callback
     */
    public function database_setup_page() {
        global $wpdb;
        $database = new Amadex_Database();
        
        // Check table status
        $required_tables = array(
            'leads' => $wpdb->prefix . 'amadex_leads',
            'bookings' => $wpdb->prefix . 'amadex_bookings',
            'passengers' => $wpdb->prefix . 'amadex_passengers',
            'payments' => $wpdb->prefix . 'amadex_payments'
        );
        
        $table_status = array();
        foreach ($required_tables as $key => $table_name) {
            $table_status[$key] = array(
                'name' => $table_name,
                'exists' => $database->table_exists_public($table_name),
                'row_count' => 0
            );
            
            if ($table_status[$key]['exists']) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
                $table_status[$key]['row_count'] = intval($count);
            }
        }
        
        $all_tables_exist = true;
        foreach ($table_status as $status) {
            if (!$status['exists']) {
                $all_tables_exist = false;
                break;
            }
        }
        
        ?>
        <div class="wrap amadex-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amadex-admin-header">
                <div class="amadex-logo">
                    <img src="<?php echo AMADEX_URL; ?>assets/images/travelay-logo.png" alt="Travelay Logo">
                </div>
            </div>
            
            <?php if (!$all_tables_exist): ?>
                <div class="notice notice-error inline">
                    <h3>⚠️ Database Tables Missing</h3>
                    <p><strong>The required database tables are missing. This is preventing bookings from being created.</strong></p>
                    <p>Click the button below to create all required tables automatically. This will use your WordPress database connection and doesn't require phpMyAdmin access.</p>
                </div>
            <?php else: ?>
                <div class="notice notice-success inline">
                    <h3>✅ All Database Tables Exist</h3>
                    <p>All required database tables are present and ready to use.</p>
                </div>
            <?php endif; ?>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card amadex-card-full">
                    <div class="amadex-card-header">
                        <h2>Database Tables Status</h2>
                    </div>
                    <div class="amadex-card-body">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 200px;">Table Name</th>
                                    <th style="width: 150px;">Status</th>
                                    <th style="width: 150px;">Records</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code><?php echo esc_html($table_status['leads']['name']); ?></code></td>
                                    <td>
                                        <?php if ($table_status['leads']['exists']): ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <strong>Exists</strong>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> <strong>Missing</strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(number_format($table_status['leads']['row_count'])); ?></td>
                                    <td>Stores flight search leads and inquiries</td>
                                </tr>
                                <tr>
                                    <td><code><?php echo esc_html($table_status['bookings']['name']); ?></code></td>
                                    <td>
                                        <?php if ($table_status['bookings']['exists']): ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <strong>Exists</strong>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> <strong>Missing</strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(number_format($table_status['bookings']['row_count'])); ?></td>
                                    <td>Stores confirmed flight bookings</td>
                                </tr>
                                <tr>
                                    <td><code><?php echo esc_html($table_status['passengers']['name']); ?></code></td>
                                    <td>
                                        <?php if ($table_status['passengers']['exists']): ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <strong>Exists</strong>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> <strong>Missing</strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(number_format($table_status['passengers']['row_count'])); ?></td>
                                    <td>Stores passenger information for each booking</td>
                                </tr>
                                <tr>
                                    <td><code><?php echo esc_html($table_status['payments']['name']); ?></code></td>
                                    <td>
                                        <?php if ($table_status['payments']['exists']): ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <strong>Exists</strong>
                                        <?php else: ?>
                                            <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span> <strong>Missing</strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(number_format($table_status['payments']['row_count'])); ?></td>
                                    <td>Stores payment transaction information</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div style="margin-top: 30px; padding: 20px; background: #f0f0f1; border-radius: 4px;">
                            <h3 style="margin-top: 0;">🔧 Setup Instructions</h3>
                            
                            <?php if (!$all_tables_exist): ?>
                                <p><strong>Option 1: One-Click Setup (Recommended)</strong></p>
                                <p>Click the button below to create all missing tables automatically. This uses your WordPress database connection and doesn't require external access.</p>
                                <p>
                                    <button type="button" id="amadex-create-tables-btn" class="button button-primary button-large">
                                        <span class="dashicons dashicons-database-add"></span> Create All Database Tables
                                    </button>
                                    <span id="amadex-create-tables-spinner" class="spinner" style="float: none; margin-left: 10px; visibility: hidden;"></span>
                                </p>
                                <div id="amadex-create-tables-result" style="margin-top: 15px;"></div>
                                
                                <hr style="margin: 20px 0;">
                                
                                <p><strong>Option 2: Manual SQL Import</strong></p>
                                <p>If automatic creation fails, you can manually import the SQL file:</p>
                                <ol>
                                    <li>Download the SQL file: <a href="<?php echo esc_url(AMADEX_URL . 'install/database-schema.sql'); ?>" target="_blank" download>database-schema.sql</a></li>
                                    <li>Or use the SQL generator: <a href="<?php echo esc_url(AMADEX_URL . 'install/generate-sql.php'); ?>" target="_blank">Generate SQL with your prefix</a></li>
                                    <li>Go to phpMyAdmin → Select your database → SQL tab → Import or paste the SQL</li>
                                </ol>
                            <?php else: ?>
                                <p>✅ All tables are set up correctly. Your booking system is ready to use!</p>
                                <p>
                                    <button type="button" id="amadex-check-status-btn" class="button button-secondary">
                                        <span class="dashicons dashicons-update"></span> Refresh Status
                                    </button>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card">
                    <div class="amadex-card-header">
                        <h2>Database Information</h2>
                    </div>
                    <div class="amadex-card-body">
                        <table class="form-table">
                            <tr>
                                <th>Database Name:</th>
                                <td><code><?php echo esc_html(DB_NAME); ?></code></td>
                            </tr>
                            <tr>
                                <th>Table Prefix:</th>
                                <td><code><?php echo esc_html($wpdb->prefix); ?></code></td>
                            </tr>
                            <tr>
                                <th>Database Host:</th>
                                <td><code><?php echo esc_html(DB_HOST); ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var ajaxUrl = '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
            
            $('#amadex-create-tables-btn').on('click', function() {
                var $btn = $(this);
                var $spinner = $('#amadex-create-tables-spinner');
                var $result = $('#amadex-create-tables-result');
                
                if (!confirm('Are you sure you want to create the database tables? This action cannot be undone, but existing tables will not be modified.')) {
                    return;
                }
                
                $btn.prop('disabled', true);
                $spinner.css('visibility', 'visible');
                $result.html('<div class="notice notice-info inline"><p>Creating tables... Please wait.</p></div>');
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'amadex_create_database_tables',
                        nonce: '<?php echo wp_create_nonce("amadex_admin_nonce"); ?>'
                    },
                    success: function(response) {
                        $spinner.css('visibility', 'hidden');
                        $btn.prop('disabled', false);
                        
                        if (response.success) {
                            $result.html('<div class="notice notice-success inline"><p><strong>✅ Success!</strong> ' + response.data.message + '</p></div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $result.html('<div class="notice notice-error inline"><p><strong>❌ Error:</strong> ' + (response.data.message || 'Unknown error') + '</p></div>');
                        }
                    },
                    error: function() {
                        $spinner.css('visibility', 'hidden');
                        $btn.prop('disabled', false);
                        $result.html('<div class="notice notice-error inline"><p><strong>❌ Error:</strong> Failed to communicate with server. Please try again.</p></div>');
                    }
                });
            });
            
            $('#amadex-check-status-btn').on('click', function() {
                location.reload();
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler: Create database tables
     */
    public function ajax_create_database_tables() {
        check_ajax_referer('amadex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        global $wpdb;
        $database = new Amadex_Database();
        
        // Check if bookings table exists but has issues
        $bookings_table = $wpdb->prefix . 'amadex_bookings';
        $table_exists = $database->table_exists_public($bookings_table);
        
        if ($table_exists) {
            // Table exists - check for duplicate key issues
            $indexes = $wpdb->get_results("SHOW INDEX FROM {$bookings_table} WHERE Key_name = 'booking_reference'");
            if (count($indexes) > 1 || !empty($wpdb->last_error)) {
                // Table has issues - drop and recreate
                error_log('Amadex: Bookings table has structure issues. Dropping and recreating...');
                $wpdb->query("DROP TABLE IF EXISTS {$bookings_table}");
                
                // Clear query cache
                $wpdb->flush();
            }
        }
        
        // Now create all tables
        $result = $database->create_tables();
        
        // Double-check bookings table was created
        $final_check = $database->table_exists_public($bookings_table);
        if (!$final_check && empty($result['errors']['bookings'])) {
            // Try direct SQL creation as last resort
            error_log('Amadex: Attempting direct SQL creation for bookings table');
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            
            $charset_collate = $wpdb->get_charset_collate();
            $sql_bookings = "CREATE TABLE {$bookings_table} (
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
            
            $wpdb->query($sql_bookings);
            
            if (!empty($wpdb->last_error)) {
                $result['errors']['bookings'] = $wpdb->last_error;
                $result['success'] = false;
            } else {
                // Success - verify it was created
                $final_check = $database->table_exists_public($bookings_table);
                if ($final_check) {
                    unset($result['errors']['bookings']);
                    if (empty($result['errors'])) {
                        $result['success'] = true;
                    }
                }
            }
        }
        
        if ($result['success'] && $database->table_exists_public($bookings_table)) {
            // Clear any missing table flags
            delete_option('amadex_tables_missing');
            delete_option('amadex_table_creation_errors');
            
            wp_send_json_success(array(
                'message' => 'All database tables created successfully! The page will refresh in a moment.',
                'details' => $result
            ));
        } else {
            // Update error messages to be more helpful
            $error_messages = array();
            foreach ($result['errors'] as $table => $error) {
                if (strpos($error, 'Duplicate key') !== false) {
                    $error_messages[] = "{$table}: Duplicate index conflict - this will be automatically fixed";
                } else {
                    $error_messages[] = "{$table}: {$error}";
                }
            }
            
            wp_send_json_error(array(
                'message' => 'Failed to create some tables: ' . implode('; ', $error_messages),
                'details' => $result
            ));
        }
    }
    
    /**
     * AJAX handler: Check database status
     */
    public function ajax_check_database_status() {
        check_ajax_referer('amadex_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        global $wpdb;
        $database = new Amadex_Database();
        
        $required_tables = array(
            'leads' => $wpdb->prefix . 'amadex_leads',
            'bookings' => $wpdb->prefix . 'amadex_bookings',
            'passengers' => $wpdb->prefix . 'amadex_passengers',
            'payments' => $wpdb->prefix . 'amadex_payments'
        );
        
        $status = array();
        foreach ($required_tables as $key => $table_name) {
            $status[$key] = array(
                'exists' => $database->table_exists_public($table_name)
            );
        }
        
        wp_send_json_success($status);
    }
    
    /**
     * AJAX handler to apply performance settings from a snapshot
     */
    public function ajax_apply_performance_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_apply_settings')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Get settings from POST
        $snapshot = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        if (empty($snapshot)) {
            wp_send_json_error('No settings provided');
            return;
        }
        
        // Get current settings
        $advanced_settings = get_option('amadex_advanced_settings', array());
        $performance_settings = get_option('amadex_performance_settings', array());
        
        // Apply API & Debug settings
        if (isset($snapshot['api_timeout'])) {
            $advanced_settings['timeout'] = max(1, min(60, intval($snapshot['api_timeout'])));
        }
        if (isset($snapshot['error_logging'])) {
            $advanced_settings['error_logging'] = $snapshot['error_logging'] ? '1' : '0';
        }
        if (isset($snapshot['debug_mode'])) {
            $advanced_settings['debug_mode'] = $snapshot['debug_mode'] ? '1' : '0';
        }
        
        // Apply Performance settings
        if (isset($snapshot['debug_logging'])) {
            $performance_settings['enable_debug_logging'] = $snapshot['debug_logging'] ? '1' : '0';
        }
        if (isset($snapshot['initial_results_count'])) {
            $performance_settings['initial_results_count'] = max(10, min(250, intval($snapshot['initial_results_count'])));
        }
        if (isset($snapshot['redis_enabled'])) {
            $performance_settings['enable_redis_cache'] = $snapshot['redis_enabled'] ? '1' : '0';
        }
        if (isset($snapshot['progressive_loading'])) {
            $performance_settings['enable_progressive_loading'] = $snapshot['progressive_loading'] ? '1' : '0';
        }
        if (isset($snapshot['progressive_count'])) {
            $performance_settings['progressive_initial_count'] = max(10, min(100, intval($snapshot['progressive_count'])));
        }
        if (isset($snapshot['streaming_response'])) {
            $performance_settings['enable_streaming_response'] = $snapshot['streaming_response'] ? '1' : '0';
        }
        if (isset($snapshot['streaming_count'])) {
            $performance_settings['streaming_initial_count'] = max(1, min(50, intval($snapshot['streaming_count'])));
        }
        if (isset($snapshot['virtual_scrolling'])) {
            $performance_settings['enable_virtual_scrolling'] = $snapshot['virtual_scrolling'] ? '1' : '0';
        }
        if (isset($snapshot['skeleton_ui'])) {
            $performance_settings['enable_skeleton_ui'] = $snapshot['skeleton_ui'] ? '1' : '0';
        }
        if (isset($snapshot['loading_animation'])) {
            $performance_settings['enable_loading_animation'] = $snapshot['loading_animation'] ? '1' : '0';
        }
        
        // Save settings
        update_option('amadex_advanced_settings', $advanced_settings);
        update_option('amadex_performance_settings', $performance_settings);
        
        wp_send_json_success(array(
            'message' => 'Settings applied successfully',
            'advanced' => $advanced_settings,
            'performance' => $performance_settings
        ));
    }
    
    /**
     * AJAX handler to delete selected performance metrics
     */
    public function ajax_delete_performance_metrics() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'amadex_delete_metrics')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        $search_ids = isset($_POST['search_ids']) ? $_POST['search_ids'] : array();
        if (empty($search_ids)) {
            wp_send_json_error('No items selected');
            return;
        }
        
        if (!is_array($search_ids)) {
            $search_ids = array($search_ids);
        }
        
        $deleted = Amadex_Performance::delete_metrics_by_ids($search_ids);
        wp_send_json_success(array('deleted' => $deleted, 'message' => sprintf(__('%d item(s) deleted.', 'amadex'), $deleted)));
    }
    
    /**
     * Performance Metrics page callback
     */
    public function performance_metrics_page() {
        if (!class_exists('Amadex_Performance')) {
            echo '<div class="wrap"><h1>Performance Metrics</h1><p>Performance class not available.</p></div>';
            return;
        }
        
        // Get recent metrics
        $recent_metrics = Amadex_Performance::get_recent_metrics(20);
        $average_metrics = Amadex_Performance::get_average_metrics(20);
        
        // Check if performance logging is enabled
        $settings = get_option('amadex_performance_settings', array());
        $is_enabled = isset($settings['enable_performance_logging']) && $settings['enable_performance_logging'] === '1';
        
        ?>
        <div class="wrap amadex-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="amadex-admin-header">
                <div class="amadex-logo">
                    <img src="<?php echo AMADEX_URL; ?>assets/images/travelay-logo.png" alt="Travelay Logo">
                </div>
            </div>
            
            <?php if (!$is_enabled): ?>
                <div class="notice notice-warning inline">
                    <p><strong>Performance logging is currently disabled.</strong> Enable it in <a href="<?php echo admin_url('admin.php?page=amadex-settings&tab=advanced'); ?>">Advanced Settings</a> to start collecting metrics.</p>
                </div>
            <?php endif; ?>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card amadex-card-full">
                    <div class="amadex-card-header">
                        <h2>Average Performance Metrics (Last 20 Searches)</h2>
                    </div>
                    <div class="amadex-card-body">
                        <?php if (empty($average_metrics)): ?>
                            <p>No performance metrics available yet. Metrics will appear after flight searches are performed.</p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th style="width: 300px;">Metric</th>
                                        <th style="width: 150px;">Average Time</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($average_metrics as $label => $metric): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($label); ?></strong></td>
                                            <td>
                                                <span style="font-size: 18px; font-weight: bold; color: <?php echo $metric['time'] > 2 ? '#dc3232' : ($metric['time'] > 1 ? '#f56e28' : '#46b450'); ?>">
                                                    <?php echo number_format($metric['time'], 3); ?>s
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $descriptions = array(
                                                    'ttfb' => 'Time to First Byte - Initial response time',
                                                    'ajax_search_flights' => 'Total AJAX search handler time',
                                                    'api_search_flights' => 'Total API search method time',
                                                    'amadeus_api_call' => 'Amadeus API network request time',
                                                    'json_parse' => 'JSON response parsing time',
                                                    'format_results' => 'Flight results formatting time'
                                                );
                                                echo esc_html($descriptions[$label] ?? 'Performance metric');
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="amadex-card amadex-card-full">
                    <div class="amadex-card-header">
                        <h2>Recent Search Performance (Last 20 Searches)</h2>
                    </div>
                    <div class="amadex-card-body">
                        <?php if (empty($recent_metrics)): ?>
                            <p>No recent search metrics available.</p>
                        <?php else: ?>
                            <p style="margin-bottom: 12px;">
                                <button type="button" class="button amadex-delete-selected" disabled>
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Delete Selected
                                </button>
                                <span class="amadex-delete-status" style="margin-left: 10px;"></span>
                            </p>
                            <table class="wp-list-table widefat fixed striped" id="amadex-perf-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="amadex-select-all" title="<?php esc_attr_e('Select All', 'amadex'); ?>">
                                        </th>
                                        <th style="width: 200px;">Search</th>
                                        <th style="width: 100px;">Total Time</th>
                                        <th style="width: 130px;">Timestamp</th>
                                        <th style="width: 180px;">Key Metrics</th>
                                        <th style="width: 100px;">Settings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_metrics as $index => $search): ?>
                                        <tr data-search-id="<?php echo esc_attr($search['search_id']); ?>">
                                            <td>
                                                <input type="checkbox" class="amadex-metric-checkbox" value="<?php echo esc_attr($search['search_id']); ?>" title="<?php esc_attr_e('Select to delete', 'amadex'); ?>">
                                            </td>
                                            <td>
                                                <?php if (isset($search['search_params']) && !empty($search['search_params']['origin'])): ?>
                                                    <strong style="font-size: 14px;">
                                                        <?php echo esc_html($search['search_params']['origin']); ?> → <?php echo esc_html($search['search_params']['destination']); ?>
                                                    </strong><br>
                                                    <span style="color: #666; font-size: 12px;">
                                                        <?php echo esc_html($search['search_params']['departure_date']); ?>
                                                        <?php if (!empty($search['search_params']['return_date'])): ?>
                                                            · RT
                                                        <?php else: ?>
                                                            · OW
                                                        <?php endif; ?>
                                                        · <?php echo intval($search['search_params']['passengers']); ?> pax
                                                        · <?php echo intval($search['search_params']['flights_found']); ?> results
                                                    </span>
                                                <?php else: ?>
                                                    <code title="<?php echo esc_attr($search['search_id']); ?>"><?php echo esc_html(substr($search['search_id'], 0, 18)); ?>...</code>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-size: 16px; font-weight: bold; color: <?php echo $search['total_time'] > 5 ? '#dc3232' : ($search['total_time'] > 3 ? '#f56e28' : '#46b450'); ?>">
                                                    <?php echo number_format($search['total_time'], 2); ?>s
                                                </span>
                                            </td>
                                            <td><?php echo esc_html(date('M j, H:i:s', strtotime($search['timestamp']))); ?></td>
                                            <td>
                                                <?php
                                                if (isset($search['metrics'])) {
                                                    $key_metrics = array();
                                                    if (isset($search['metrics']['ttfb'])) {
                                                        $key_metrics[] = 'TTFB: ' . number_format($search['metrics']['ttfb']['time'], 2) . 's';
                                                    }
                                                    if (isset($search['metrics']['amadeus_api_call'])) {
                                                        $key_metrics[] = 'API: ' . number_format($search['metrics']['amadeus_api_call']['time'], 2) . 's';
                                                    }
                                                    if (isset($search['metrics']['format_results'])) {
                                                        $key_metrics[] = 'Format: ' . number_format($search['metrics']['format_results']['time'], 2) . 's';
                                                    }
                                                    echo esc_html(implode(' | ', $key_metrics));
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if (isset($search['settings_snapshot'])): ?>
                                                    <button type="button" class="button button-small amadex-view-settings" data-index="<?php echo $index; ?>">
                                                        <span class="dashicons dashicons-admin-generic" style="vertical-align: middle;"></span> View
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: #999;">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php if (isset($search['settings_snapshot'])): ?>
                                        <tr class="amadex-settings-row" id="settings-row-<?php echo $index; ?>" style="display: none;">
                                            <td colspan="6" style="background: #f9f9f9; padding: 15px 20px;">
                                                <div class="amadex-settings-snapshot">
                                                    <h4 style="margin: 0 0 10px 0; display: flex; align-items: center; gap: 10px;">
                                                        <span class="dashicons dashicons-admin-settings"></span>
                                                        Settings Snapshot for Search: <code><?php echo esc_html(substr($search['search_id'], 0, 25)); ?></code>
                                                        <span style="margin-left: auto; font-weight: normal; font-size: 12px; color: #666;">
                                                            <?php echo esc_html(date('F j, Y \a\t H:i:s', strtotime($search['timestamp']))); ?>
                                                        </span>
                                                    </h4>
                                                    
                                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 15px;">
                                                        <!-- API & Debug Settings -->
                                                        <div style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 12px;">
                                                            <h5 style="margin: 0 0 10px 0; color: #23282d; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                                                                <span class="dashicons dashicons-admin-tools" style="color: #0073aa;"></span> API & Debug
                                                            </h5>
                                                            <?php $ss = $search['settings_snapshot']; ?>
                                                            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                                                                <li>API Timeout: <strong><?php echo intval($ss['api_timeout'] ?? 10); ?>s</strong></li>
                                                                <li>Error Logging: <?php echo ($ss['error_logging'] ?? false) ? '<span style="color:#dc3232;">ON</span>' : '<span style="color:#46b450;">OFF</span>'; ?></li>
                                                                <li>Debug Mode: <?php echo ($ss['debug_mode'] ?? false) ? '<span style="color:#dc3232;">ON</span>' : '<span style="color:#46b450;">OFF</span>'; ?></li>
                                                                <li>Debug Logging: <?php echo ($ss['debug_logging'] ?? false) ? '<span style="color:#dc3232;">ON</span>' : '<span style="color:#46b450;">OFF</span>'; ?></li>
                                                            </ul>
                                                        </div>
                                                        
                                                        <!-- Caching & Loading -->
                                                        <div style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 12px;">
                                                            <h5 style="margin: 0 0 10px 0; color: #23282d; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                                                                <span class="dashicons dashicons-performance" style="color: #0073aa;"></span> Caching & Loading
                                                            </h5>
                                                            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                                                                <li>Redis Enabled: <?php echo ($ss['redis_enabled'] ?? false) ? '<span style="color:#46b450;">ON</span>' : '<span style="color:#999;">OFF</span>'; ?></li>
                                                                <li>Redis Connected: <?php echo ($ss['redis_connected'] ?? false) ? '<span style="color:#46b450;">YES</span>' : '<span style="color:#dc3232;">NO</span>'; ?></li>
                                                                <li>Progressive: <?php echo ($ss['progressive_loading'] ?? false) ? 'ON (' . intval($ss['progressive_count'] ?? 30) . ')' : '<span style="color:#999;">OFF</span>'; ?></li>
                                                                <li>Streaming: <?php echo ($ss['streaming_response'] ?? false) ? '<span style="color:#46b450;">ON</span> (' . intval($ss['streaming_count'] ?? 5) . ')' : '<span style="color:#999;">OFF</span>'; ?></li>
                                                            </ul>
                                                        </div>
                                                        
                                                        <!-- UI Performance -->
                                                        <div style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 12px;">
                                                            <h5 style="margin: 0 0 10px 0; color: #23282d; border-bottom: 1px solid #eee; padding-bottom: 8px;">
                                                                <span class="dashicons dashicons-visibility" style="color: #0073aa;"></span> UI Performance
                                                            </h5>
                                                            <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
                                                                <li>Initial Results: <strong><?php echo intval($ss['initial_results_count'] ?? 50); ?></strong></li>
                                                                <li>Virtual Scrolling: <?php echo ($ss['virtual_scrolling'] ?? false) ? '<span style="color:#46b450;">ON</span>' : '<span style="color:#999;">OFF</span>'; ?></li>
                                                                <li>Skeleton UI: <?php echo ($ss['skeleton_ui'] ?? false) ? '<span style="color:#46b450;">ON</span>' : '<span style="color:#999;">OFF</span>'; ?></li>
                                                                <li>Loading Animation: <?php echo ($ss['loading_animation'] ?? false) ? '<span style="color:#46b450;">ON</span>' : '<span style="color:#999;">OFF</span>'; ?></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                    
                                                    <div style="margin-top: 15px; padding-top: 12px; border-top: 1px solid #ddd;">
                                                        <button type="button" class="button button-primary amadex-apply-settings" data-settings='<?php echo esc_attr(json_encode($ss)); ?>'>
                                                            <span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Apply These Settings
                                                        </button>
                                                        <span style="margin-left: 10px; color: #666; font-size: 12px;">
                                                            Click to copy these exact settings to Advanced Settings page
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <script>
                            jQuery(document).ready(function($) {
                                var deleteNonce = '<?php echo wp_create_nonce('amadex_delete_metrics'); ?>';
                                
                                // Select All
                                $('#amadex-select-all').on('change', function() {
                                    var checked = $(this).prop('checked');
                                    $('.amadex-metric-checkbox').prop('checked', checked);
                                    $('.amadex-delete-selected').prop('disabled', !checked);
                                });
                                
                                // Individual checkbox - enable Delete when any selected
                                $(document).on('change', '.amadex-metric-checkbox', function() {
                                    var anyChecked = $('.amadex-metric-checkbox:checked').length > 0;
                                    $('.amadex-delete-selected').prop('disabled', !anyChecked);
                                    $('#amadex-select-all').prop('checked', anyChecked && $('.amadex-metric-checkbox:checked').length === $('.amadex-metric-checkbox').length);
                                });
                                
                                // Delete Selected
                                $('.amadex-delete-selected').on('click', function() {
                                    var $btn = $(this);
                                    var ids = [];
                                    $('.amadex-metric-checkbox:checked').each(function() {
                                        ids.push($(this).val());
                                    });
                                    if (ids.length === 0) return;
                                    
                                    if (!confirm('Delete ' + ids.length + ' selected item(s)?')) return;
                                    
                                    $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="vertical-align: middle;"></span> Deleting...');
                                    $('.amadex-delete-status').text('');
                                    
                                    $.post(ajaxurl, {
                                        action: 'amadex_delete_performance_metrics',
                                        search_ids: ids,
                                        nonce: deleteNonce
                                    }, function(response) {
                                        if (response.success) {
                                            $('.amadex-delete-status').css('color', '#46b450').text(response.data.message);
                                            var $rowsToRemove = $();
                                            ids.forEach(function(id) {
                                                var $tr = $('tr[data-search-id="' + id + '"]');
                                                $tr.next('.amadex-settings-row').remove();
                                                $tr.remove();
                                            });
                                            $btn.prop('disabled', true).html('<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Deleted');
                                            setTimeout(function() { location.reload(); }, 800);
                                        } else {
                                            $('.amadex-delete-status').css('color', '#dc3232').text(response.data || 'Error');
                                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Delete Selected');
                                        }
                                    }).fail(function() {
                                        $('.amadex-delete-status').css('color', '#dc3232').text('Request failed');
                                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> Delete Selected');
                                    });
                                });
                                
                                // Toggle settings row visibility
                                $('.amadex-view-settings').on('click', function() {
                                    var index = $(this).data('index');
                                    var $row = $('#settings-row-' + index);
                                    var $btn = $(this);
                                    
                                    // Hide all other settings rows
                                    $('.amadex-settings-row').not($row).slideUp(200);
                                    $('.amadex-view-settings').not($btn).find('.dashicons').removeClass('dashicons-dismiss').addClass('dashicons-admin-generic');
                                    
                                    // Toggle this row
                                    $row.slideToggle(200);
                                    $btn.find('.dashicons').toggleClass('dashicons-admin-generic dashicons-dismiss');
                                });
                                
                                // Apply settings button
                                $('.amadex-apply-settings').on('click', function() {
                                    var settings = $(this).data('settings');
                                    var confirmMsg = 'This will save the following settings to your Advanced Settings:\n\n';
                                    confirmMsg += '• Error Logging: ' + (settings.error_logging ? 'ON' : 'OFF') + '\n';
                                    confirmMsg += '• Debug Mode: ' + (settings.debug_mode ? 'ON' : 'OFF') + '\n';
                                    confirmMsg += '• Debug Logging: ' + (settings.debug_logging ? 'ON' : 'OFF') + '\n';
                                    confirmMsg += '• Redis: ' + (settings.redis_enabled ? 'ON' : 'OFF') + '\n';
                                    confirmMsg += '• Progressive Loading: ' + (settings.progressive_loading ? 'ON' : 'OFF') + '\n';
                                    confirmMsg += '• Streaming Response: ' + (settings.streaming_response ? 'ON' : 'OFF') + '\n';
                                    confirmMsg += '• Virtual Scrolling: ' + (settings.virtual_scrolling ? 'ON' : 'OFF') + '\n';
                                    confirmMsg += '• Skeleton UI: ' + (settings.skeleton_ui ? 'ON' : 'OFF') + '\n';
                                    confirmMsg += '• Loading Animation: ' + (settings.loading_animation ? 'ON' : 'OFF') + '\n\n';
                                    confirmMsg += 'Continue?';
                                    
                                    if (confirm(confirmMsg)) {
                                        var $btn = $(this);
                                        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="vertical-align: middle;"></span> Applying...');
                                        
                                        $.post(ajaxurl, {
                                            action: 'amadex_apply_performance_settings',
                                            settings: settings,
                                            nonce: '<?php echo wp_create_nonce('amadex_apply_settings'); ?>'
                                        }, function(response) {
                                            if (response.success) {
                                                $btn.html('<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Applied!');
                                                setTimeout(function() {
                                                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Apply These Settings');
                                                }, 2000);
                                            } else {
                                                alert('Error: ' + (response.data || 'Unknown error'));
                                                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Apply These Settings');
                                            }
                                        }).fail(function() {
                                            alert('AJAX request failed');
                                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="vertical-align: middle;"></span> Apply These Settings');
                                        });
                                    }
                                });
                            });
                            </script>
                            
                            <style>
                            .amadex-settings-row td {
                                border-top: none !important;
                            }
                            .amadex-view-settings .dashicons {
                                font-size: 16px;
                                width: 16px;
                                height: 16px;
                            }
                            .dashicons.spin {
                                animation: spin 1s linear infinite;
                            }
                            @keyframes spin {
                                100% { transform: rotate(360deg); }
                            }
                            </style>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="amadex-admin-cards">
                <div class="amadex-card">
                    <div class="amadex-card-header">
                        <h2>Performance Settings</h2>
                    </div>
                    <div class="amadex-card-body">
                        <p>Configure performance optimization settings:</p>
                        <a href="<?php echo admin_url('admin.php?page=amadex-settings&tab=advanced'); ?>" class="button button-primary">
                            Go to Performance Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

}
