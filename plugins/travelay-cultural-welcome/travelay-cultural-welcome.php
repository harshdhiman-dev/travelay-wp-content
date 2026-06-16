<?php
/**
 * Plugin Name:       Travelay Cultural Welcome
 * Plugin URI:        https://www.flytravelay.com/
 * Description:       Interactive welcome avatars for any WordPress site — sync pages, country templates, voice, Rive & Lottie.
 * Version:           3.11.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            FlyTravelay
 * Author URI:        https://www.flytravelay.com/
 * Text Domain:       travelay-cultural-welcome
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TCW_VERSION', '3.11.0' );
define( 'TCW_PLUGIN_FILE', __FILE__ );
define( 'TCW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TCW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TCW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once TCW_PLUGIN_DIR . 'includes/class-tcw-loader.php';
TCW_Loader::init();

/**
 * Returns the main plugin instance.
 *
 * @return TCW_Plugin
 */
function tcw_plugin() {
	return TCW_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'TCW_Seeder', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TCW_Seeder', 'deactivate' ) );

tcw_plugin();
