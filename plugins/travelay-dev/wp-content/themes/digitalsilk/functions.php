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
