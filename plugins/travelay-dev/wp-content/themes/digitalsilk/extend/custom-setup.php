<?php
/**
 * Add custom libraries and functionalities.
 *
 * @package Digitalsilk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$extra_fields_path = get_template_directory() . '/class-module-extra-fields.php';

if ( file_exists( $extra_fields_path ) ) {
	require_once $extra_fields_path;
}

/**
 * Enqueue custom pattern styles.
 *
 * @return void
 */
function digitalsilk_pattern_styles() {

	$style_path = get_template_directory() . '/patterns/patterns-style.css';

	if ( ! file_exists( $style_path ) ) {
		return;
	}

	wp_enqueue_style(
		'digitalsilk-pattern-styles',
		get_template_directory_uri() . '/patterns/patterns-style.css',
		array(),
		filemtime( $style_path )
	);
}

add_action( 'wp_enqueue_scripts', 'digitalsilk_pattern_styles' );
add_action( 'enqueue_block_editor_assets', 'digitalsilk_pattern_styles' );
