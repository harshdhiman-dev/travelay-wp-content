<?php
/**
 * Custom Blocks Initialization
 *
 * Load custom block initialization files
 *
 * @package DST
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load Flight Deals block initialization
 * This loads the admin settings page for Amadeus API configuration
 */
$flight_deals_init = get_template_directory() . '/modules/gutenberg/blocks/custom/dst-flight-deals/init.php';
if ( file_exists( $flight_deals_init ) ) {
    require_once $flight_deals_init;
}
