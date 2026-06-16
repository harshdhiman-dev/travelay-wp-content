<?php
/**
 * Plugin Name:     DS Gravity Forms Tracking Addon
 * Plugin URI:      https://www.digitalsilk.com/
 * Description:     Capture UTM parameters and pass them with Gravity Forms submissions.
 * Author:          Digital Silk
 * Author URI:      https://www.digitalsilk.com/
 * Text Domain:     ds-gf-tracking
 * Domain Path:     /languages
 * Version:         1.1.2
 *
 * @package         Ds_Gf_Tracking
 * @since    1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-ds-gf-tracking.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */

DS_GF_Tracking::get_instance()->run();
