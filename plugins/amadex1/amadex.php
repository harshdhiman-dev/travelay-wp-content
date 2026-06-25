<?php
/**
 * Plugin Name:     Amadex
 * Plugin URI:      https://wphacks4u.com
 * Description:     Advanced flight search and booking solution powered by Amadeus API. Easily integrate flight search functionality into your WordPress website.
 * Version:         1.1.0
 * Requires PHP:    7.4
 * Author:          FlyTravelay
 * Author URI:      https://www.flytravelay.com/
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.htm2
 * Text Domain:     amadex
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('AMADEX_VERSION', '43.0.5');
define('AMADEX_PATH', plugin_dir_path(__FILE__));
define('AMADEX_URL', plugin_dir_url(__FILE__));
define('AMADEX_BASENAME', plugin_basename(__FILE__));

$autoload_paths = array(
    AMADEX_PATH . 'vendor/autoload.php',
    AMADEX_PATH . 'includes/vendor/autoload.php',
); 
foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        break;
    }
}

function amadex_enqueue_custom_css() {
    if ( isset($_GET['postId']) || isset($_GET['postType']) ) return;
    if ( isset($_GET['canvas']) ) return;
    wp_enqueue_style(
        'amadex-custom-style',
        AMADEX_URL . 'assets/css/amadex-custom.css',
        array(),
        filemtime(AMADEX_PATH . 'assets/css/amadex-custom.css')
    );
}

add_action('enqueue_block_editor_assets', function() {
    define('AMADEX_BLOCK_EDITOR_ACTIVE', true);
}, 1);

add_action('wp_enqueue_scripts', 'amadex_enqueue_custom_css');

require_once(AMADEX_PATH . 'includes/class-amadex-database.php');
require_once(AMADEX_PATH . 'includes/class-amadex-payment.php');
require_once(AMADEX_PATH . 'includes/class-amadex-pricing.php');
require_once(AMADEX_PATH . 'includes/class-amadex-pricing-rules.php');
require_once(AMADEX_PATH . 'includes/class-amadex-lastminute-deals.php');
require_once(AMADEX_PATH . 'includes/class-amadex-tabbed-search.php');
require_once(AMADEX_PATH . 'includes/class-amadex-hotel-results.php');
require_once(AMADEX_PATH . 'includes/class-amadex-hotel-detail.php');
// Register location search globally for all pages
add_action('wp_ajax_amadex_search_locations',        'amadex_search_locations_global');
add_action('wp_ajax_nopriv_amadex_search_locations', 'amadex_search_locations_global');

function amadex_search_locations_global() {
    check_ajax_referer('amadex_nonce', 'nonce');
    $keyword    = sanitize_text_field($_POST['keyword'] ?? '');
    $google_key = 'AIzaSyDf1tj8wdsAL1oPK8O9M0YFnfVPgSTMfYY';

    if (strlen($keyword) < 2) { wp_send_json_success(array()); return; }

    // Use Google Places Autocomplete API
    $resp = wp_remote_get(
        'https://maps.googleapis.com/maps/api/place/autocomplete/json?' . http_build_query(array(
            'input'    => $keyword,
            'types'    => '(cities)',
            'key'      => $google_key,
        )),
        array('timeout' => 10)
    );

    if (is_wp_error($resp)) { wp_send_json_success(array()); return; }

    $data = json_decode(wp_remote_retrieve_body($resp));
    if (empty($data->predictions)) { wp_send_json_success(array()); return; }

    $results = array();
    foreach ($data->predictions as $place) {
        $main    = $place->structured_formatting->main_text ?? '';
        $second  = $place->structured_formatting->secondary_text ?? '';
        $place_id= $place->place_id ?? '';
        if (!$main) continue;
        $results[] = array(
            'code'     => $place_id,   // use place_id as code
            'city'     => $main,
            'name'     => $second,
            'country'  => $second,
            'place_id' => $place_id,
            'type'     => 'CITY',
        );
    }
    wp_send_json_success($results);
}
$currency_file = AMADEX_PATH . 'includes/class-amadex-currency.php';
if (file_exists($currency_file)) {
    require_once($currency_file);
} else {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Amadex Warning: Currency class file not found at: ' . $currency_file);
    }
}

require_once(AMADEX_PATH . 'includes/api/class-amadex-api.php');
require_once(AMADEX_PATH . 'includes/class-amadex-performance.php');
require_once(AMADEX_PATH . 'includes/amadex-helpers.php');
require_once(AMADEX_PATH . 'includes/class-amadex-redis-cache.php');
require_once(AMADEX_PATH . 'includes/class-amadex-streaming.php');
$token_prefetch_file = AMADEX_PATH . 'includes/class-amadex-token-prefetch.php';
if (file_exists($token_prefetch_file)) {
    require_once($token_prefetch_file);
}
require_once(AMADEX_PATH . 'includes/class-amadex-pdf-generator.php');
require_once(AMADEX_PATH . 'includes/class-amadex-data-exporter.php');
require_once(AMADEX_PATH . 'includes/class-amadex-assignment-engine.php');
require_once(AMADEX_PATH . 'includes/class-amadex-analytics.php');

require_once plugin_dir_path(__FILE__) . 'amadex-dashboard-api.php';

register_activation_hook(__FILE__, 'amadex_install');
function amadex_install() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-amadex-database.php';
    $db = new Amadex_Database();
    $db->create_tables();
}

require_once(AMADEX_PATH . 'includes/admin/class-amadex-admin.php');
require_once(AMADEX_PATH . 'includes/admin/class-amadex-settings.php');
require_once(AMADEX_PATH . 'includes/admin/class-amadex-creative-experience-admin.php');
require_once(AMADEX_PATH . 'includes/admin/class-amadex-leads.php');

require_once(AMADEX_PATH . 'includes/frontend/class-amadex-shortcodes.php');
require_once(AMADEX_PATH . 'includes/class-amadex-deals.php');
require_once(AMADEX_PATH . 'includes/amadex-ajax.php');
require_once(AMADEX_PATH . 'includes/class-amadex-hotel-booking.php');


/**
 * Main Amadex Plugin Class
 */
class Amadex {
    /**
     * Instance variable
     *
     * @var Amadex
     */
    private static $instance = null;

    /**
     * Admin class instance
     *
     * @var Amadex_Admin
     */
    public $admin;

    /**
     * Settings class instance
     *
     * @var Amadex_Settings
     */
    public $settings;

    /**
     * API class instance
     *
     * @var Amadex_API
     */
    public $api;

    /**
     * Get singleton instance
     *
     * @return Amadex
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_classes();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_filter('plugin_action_links_' . AMADEX_BASENAME, array($this, 'add_plugin_action_links'));

        add_action('init', array($this, 'ensure_database_tables'), 1);
        
        add_filter('wp_get_attachment_metadata', array($this, 'sanitize_attachment_metadata_numeric'), 0, 2);
        add_filter('editor_max_image_size', array($this, 'sanitize_editor_max_image_size'), 0, 3);
    }
    
    /**
     * Sanitize attachment metadata width/height to prevent non-numeric warnings in wp-includes/media.php
     *
     * @param array|false $data    Attachment metadata.
     * @param int         $post_id Attachment ID.
     * @return array|false
     */
    public function sanitize_attachment_metadata_numeric($data, $post_id) {
        if (!is_array($data)) {
            return $data;
        }
        if (isset($data['width']) && !is_numeric($data['width'])) {
            $data['width'] = absint($data['width']);
        }
        if (isset($data['height']) && !is_numeric($data['height'])) {
            $data['height'] = absint($data['height']);
        }
        if (!empty($data['sizes']) && is_array($data['sizes'])) {
            foreach ($data['sizes'] as $k => $size) {
                if (isset($size['width']) && !is_numeric($size['width'])) {
                    $data['sizes'][$k]['width'] = absint($size['width']);
                }
                if (isset($size['height']) && !is_numeric($size['height'])) {
                    $data['sizes'][$k]['height'] = absint($size['height']);
                }
            }
        }
        return $data;
    }

    /**
     * Sanitize editor_max_image_size to ensure numeric values (prevents media.php wp_constrain_dimensions warnings)
     *
     * @param array        $max_image_size [max_width, max_height]
     * @param string|array $size           Requested size.
     * @param string       $context        'display' or 'edit'.
     * @return array
     */
    public function sanitize_editor_max_image_size($max_image_size, $size, $context) {
        if (is_array($max_image_size) && count($max_image_size) >= 2) {
            $max_image_size[0] = is_numeric($max_image_size[0]) ? (int) $max_image_size[0] : 0;
            $max_image_size[1] = is_numeric($max_image_size[1]) ? (int) $max_image_size[1] : 0;
        }
        return $max_image_size;
    }

    /**
     * Ensure database tables exist - auto-create if missing
     * This runs on every page load to automatically fix missing tables
     */
    public function ensure_database_tables() {
        global $wpdb;
        
        // Check if bookings table exists
        $bookings_table = $wpdb->prefix . 'amadex_bookings';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$bookings_table}'") === $bookings_table;
        
        if (!$table_exists) {
            // Tables are missing - create them automatically
            $database = new Amadex_Database();
            $database->create_tables();
        }
    }

    /**
     * Initialize classes
     */
    private function init_classes() {
        $this->admin = new Amadex_Admin();
        $this->settings = new Amadex_Settings();
        $this->api = new Amadex_API();
        
        if (class_exists('Amadex_Token_Prefetch')) {
            new Amadex_Token_Prefetch();
        }
        new Amadex_Shortcodes();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $default_settings = array(
            'api_key' => '',
            'api_secret' => '',
            'environment' => 'test'
        );
        
        if (!get_option('amadex_api_settings')) {
            update_option('amadex_api_settings', $default_settings);
        }
        
        $this->create_airports_table();
        
        $database = new Amadex_Database();
        $database->create_tables();
        
        flush_rewrite_rules();
    }

    private function create_airports_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amadex_airports';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(3) NOT NULL,
            name varchar(255) NOT NULL,
            city varchar(100) NOT NULL,
            country varchar(100) NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            KEY city (city)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($count == 0) {
            $this->import_default_airports();
        }
    }


    /**
     * Import default airports data
     */
    private function import_default_airports() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amadex_airports';
        $airports = array(
            array('JFK', 'John F. Kennedy International Airport', 'New York', 'United States'),
            array('LAX', 'Los Angeles International Airport', 'Los Angeles', 'United States'),
            array('LHR', 'Heathrow Airport', 'London', 'United Kingdom'),
            array('CDG', 'Charles de Gaulle Airport', 'Paris', 'France'),
            array('DXB', 'Dubai International Airport', 'Dubai', 'United Arab Emirates'),
            array('DEL', 'Indira Gandhi International Airport', 'Delhi', 'India'),
            array('ATL', 'Hartsfield-Jackson Atlanta International Airport', 'Atlanta', 'United States'),
            array('PEK', 'Beijing Capital International Airport', 'Beijing', 'China'),
            array('HND', 'Tokyo Haneda Airport', 'Tokyo', 'Japan'),
            array('ORD', 'O\'Hare International Airport', 'Chicago', 'United States'),
            array('LGW', 'Gatwick Airport', 'London', 'United Kingdom'),
            array('FRA', 'Frankfurt Airport', 'Frankfurt', 'Germany'),
            array('IST', 'Istanbul Airport', 'Istanbul', 'Turkey'),
            array('AMS', 'Amsterdam Airport Schiphol', 'Amsterdam', 'Netherlands'),
            array('SIN', 'Singapore Changi Airport', 'Singapore', 'Singapore'),
            array('ICN', 'Incheon International Airport', 'Seoul', 'South Korea'),
            array('BOM', 'Chhatrapati Shivaji Maharaj International Airport', 'Mumbai', 'India'),
            array('SYD', 'Sydney Airport', 'Sydney', 'Australia'),
            array('MEX', 'Mexico City International Airport', 'Mexico City', 'Mexico'),
            array('YYZ', 'Toronto Pearson International Airport', 'Toronto', 'Canada')
        );
        
        foreach ($airports as $airport) {
            $wpdb->insert(
                $table_name,
                array(
                    'code' => $airport[0],
                    'name' => $airport[1],
                    'city' => $airport[2],
                    'country' => $airport[3]
                )
            );
        }
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('amadex', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Add settings link to plugin action links
     *
     * @param array $links
     * @return array
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=amadex-settings') . '">' . __('Settings', 'amadex') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

function amadex() {
    return Amadex::get_instance();
}

add_action('init', function() {
    load_plugin_textdomain('amadex', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 0);
add_action('init', function() {
    amadex();
}, 1);

add_action('send_headers', function() {
    if (headers_sent()) {
        return;
    }
    $policy = 'unload=(self "https://www.paypal.com" "https://www.sandbox.paypal.com" "https://t.paypal.com")';
    header('Permissions-Policy: ' . $policy, false);
});
// Action
add_action('plugins_loaded', 'amadex_force_db_check');
function amadex_force_db_check() {
    if (class_exists('Amadex_Database')) {
        $db = new Amadex_Database();
        $db->ensure_tables_ready();
    }
}