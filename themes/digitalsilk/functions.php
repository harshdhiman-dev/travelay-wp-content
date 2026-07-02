<?php
/**
 * Main Theme Functions
 *
 * @package DS_Theme
 */

if ( ! defined( 'DS_THEME_REACT_DIST_URL' ) ) {
	define( 'DS_THEME_REACT_DIST_URL', get_template_directory_uri() . '/build/react/' );
}
if ( ! defined( 'DS_THEME_REACT_DIST_DIR' ) ) {
	define( 'DS_THEME_REACT_DIST_DIR', get_template_directory() . '/build/react/' );
}
if ( ! defined( 'DS_THEME_BLOCK_DIST_URL' ) ) {
	define( 'DS_THEME_BLOCK_DIST_URL', DS_THEME_REACT_DIST_URL . 'blocks/' );
}
if ( ! defined( 'DS_THEME_BLOCK_DIST_DIR' ) ) {
	define( 'DS_THEME_BLOCK_DIST_DIR', DS_THEME_REACT_DIST_DIR . 'blocks/' );
}

/**
 * Main theme functionality
 */
require_once 'core/theme-setup.php';
require_once get_template_directory() . '/core/travelay-location-cpt.php';
/**
 * Project Customizations
 */
require_once 'extend/custom-setup.php';

/**
 * Woocommerce Files and Classes
 * Enable for Woocommerce projects
 */
if ( class_exists( 'woocommerce' ) ) {
	include_once 'core/woocommerce/woocommerce-setup.php';
}

add_action( 'wp_footer', function() {
    if ( has_block( 'ds-blocks/locations' ) ) {
        wp_enqueue_script( 'maplibre-gl', 'https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js', [], '4.7.1', true );
        wp_enqueue_script( 'dst-locations-view', get_template_directory_uri() . '/build/react/blocks/custom/dst-locations/view.js', ['maplibre-gl'], '1.0', true );
    }
});