<?php
/*
 * Plugin Name: Digital Silk Disable Feed
 * Description: Disables RSS feeds on the website.
 * Version: 1.0.1
 * Author: Digital Silk
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /languages/
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_Deactivate_Feeds' ) ) {
    class DS_Deactivate_Feeds {

        public function __construct() {
            define('DS_DISABLE_FEED_VERSION', '1.0.0');

            register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );
            register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );
            
            add_filter('plugins_loaded', array( __CLASS__, 'ds_plugins_loaded' ) );

                        
            add_action('do_feed', array( __CLASS__, 'ds_disable_feed'), 1);
            add_action('do_feed_rdf', array( __CLASS__, 'ds_disable_feed'), 1);
            add_action('do_feed_rss', array( __CLASS__, 'ds_disable_feed'), 1);
            add_action('do_feed_rss2', array( __CLASS__, 'ds_disable_feed'), 1);
            add_action('do_feed_atom', array( __CLASS__, 'ds_disable_feed'), 1);
            add_action('do_feed_rss2_comments', array( __CLASS__, 'ds_disable_feed'), 1);
            add_action('do_feed_atom_comments', array( __CLASS__, 'ds_disable_feed'), 1);

            remove_action( 'wp_head', 'feed_links_extra', 3 );
            remove_action( 'wp_head', 'feed_links', 2 );    
        }
        
        public static function activate() {
            update_option( 'ds_disable_feed_version', DS_DISABLE_FEED_VERSION );
        }
        
        public static function deactivate() {
            delete_option( 'ds_disable_feed_version' );
        }

        public static function ds_plugins_loaded() {
            load_plugin_textdomain( 'ds-disable-feed', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
        }

        public static function ds_disable_feed() {
            wp_die( __( 'No feed available', 'ds-disable-feed' ), 200 );
        }    
    }
    
    new DS_Deactivate_Feeds();
}
