<?php
/**
 * Travelay Location CPT — register post type and meta fields.
 *
 * Drop this file into your theme's core/ folder and require it from functions.php:
 *   require_once get_template_directory() . '/core/travelay-location-cpt.php';
 *
 * Or add it as a plugin file.
 *
 * @package DST
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Register Post Type ───────────────────────────────────────────────────────
add_action( 'init', function () {

	register_post_type( 'travelay_location', array(
		'labels' => array(
			'name'               => __( 'Locations', 'dstheme' ),
			'singular_name'      => __( 'Location', 'dstheme' ),
			'add_new'            => __( 'Add New', 'dstheme' ),
			'add_new_item'       => __( 'Add New Location', 'dstheme' ),
			'edit_item'          => __( 'Edit Location', 'dstheme' ),
			'new_item'           => __( 'New Location', 'dstheme' ),
			'view_item'          => __( 'View Location', 'dstheme' ),
			'search_items'       => __( 'Search Locations', 'dstheme' ),
			'not_found'          => __( 'No locations found.', 'dstheme' ),
			'not_found_in_trash' => __( 'No locations found in Trash.', 'dstheme' ),
		),
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => true,
		'menu_icon'           => 'dashicons-location-alt',
		'menu_position'       => 25,
		'supports'            => array( 'title', 'thumbnail', 'editor' ),
		'has_archive'         => false,
		'rewrite'             => array( 'slug' => 'location' ),
	) );

} );

// ── Register Meta Fields ─────────────────────────────────────────────────────
add_action( 'init', function () {

	$fields = array(
		'tl_address'      => 'string',
		'tl_city'         => 'string',
		'tl_country'      => 'string',
		'tl_country_code' => 'string',
		'tl_airport_code' => 'string',
		'tl_phone'        => 'string',
		'tl_email'        => 'string',
		'tl_hours'        => 'string',
		'tl_terminal'     => 'string',
		'tl_lat'          => 'number',
		'tl_lng'          => 'number',
		'tl_open_label'   => 'string',
		'tl_open_status'  => 'string',
		'tl_walk_in'      => 'boolean',
		'tl_airport'      => 'boolean',
		'tl_phone_247'    => 'boolean',
	);

	foreach ( $fields as $key => $type ) {
		register_post_meta( 'travelay_location', $key, array(
			'type'         => $type,
			'single'       => true,
			'show_in_rest' => true,
		) );
	}

} );

// ── Meta Box (admin form) ─────────────────────────────────────────────────────
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'travelay_location_details',
		__( 'Location Details', 'dstheme' ),
		'dst_travelay_location_meta_box',
		'travelay_location',
		'normal',
		'high'
	);
} );

function dst_travelay_location_meta_box( $post ) {
	wp_nonce_field( 'dst_travelay_location_save', 'dst_travelay_location_nonce' );

	$get = function( $key ) use ( $post ) {
		return get_post_meta( $post->ID, $key, true );
	};

	$fields = array(
		array( 'key' => 'tl_address',      'label' => 'Full Address',       'type' => 'text' ),
		array( 'key' => 'tl_city',         'label' => 'City',               'type' => 'text' ),
		array( 'key' => 'tl_country',      'label' => 'Country Name',       'type' => 'text' ),
		array( 'key' => 'tl_country_code', 'label' => 'Country Code (e.g. US, IN)', 'type' => 'text' ),
		array( 'key' => 'tl_airport_code', 'label' => 'Airport IATA Code (e.g. JFK)', 'type' => 'text' ),
		array( 'key' => 'tl_phone',        'label' => 'Phone',              'type' => 'text' ),
		array( 'key' => 'tl_email',        'label' => 'Email',              'type' => 'email' ),
		array( 'key' => 'tl_hours',        'label' => 'Hours (e.g. Mon–Fri 9am–6pm)', 'type' => 'text' ),
		array( 'key' => 'tl_terminal',     'label' => 'Terminal',           'type' => 'text' ),
		array( 'key' => 'tl_lat',          'label' => 'Latitude',           'type' => 'text' ),
		array( 'key' => 'tl_lng',          'label' => 'Longitude',          'type' => 'text' ),
		array( 'key' => 'tl_open_label',   'label' => 'Open Status Label (e.g. Open until 6pm)', 'type' => 'text' ),
		array( 'key' => 'tl_open_status',  'label' => 'Open Status (open / closed / unknown)', 'type' => 'text' ),
	);

	$checkboxes = array(
		array( 'key' => 'tl_walk_in',   'label' => 'Walk-in counter' ),
		array( 'key' => 'tl_airport',   'label' => 'Airport counter' ),
		array( 'key' => 'tl_phone_247', 'label' => '24/7 phone support' ),
	);

	echo '<style>.dst-meta-table { width:100%; border-collapse:collapse; } .dst-meta-table td { padding:8px 4px; vertical-align:middle; } .dst-meta-table input[type=text], .dst-meta-table input[type=email] { width:100%; } .dst-meta-section { margin:12px 0 4px; font-weight:600; color:#1f7a4d; }</style>';
	echo '<table class="dst-meta-table">';
	foreach ( $fields as $field ) {
		$value = esc_attr( (string) $get( $field['key'] ) );
		echo '<tr><td style="width:220px;font-weight:500;">' . esc_html( $field['label'] ) . '</td>';
		echo '<td><input type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $field['key'] ) . '" value="' . $value . '" /></td></tr>';
	}
	echo '</table>';
	echo '<p class="dst-meta-section">' . esc_html__( 'Services', 'dstheme' ) . '</p>';
	foreach ( $checkboxes as $cb ) {
		$checked = checked( $get( $cb['key'] ), '1', false );
		echo '<label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">';
		echo '<input type="checkbox" name="' . esc_attr( $cb['key'] ) . '" value="1" ' . $checked . ' />';
		echo esc_html( $cb['label'] );
		echo '</label>';
	}
	echo '<p style="font-size:12px;color:#888;margin-top:16px;">💡 Get coordinates: <a href="https://www.latlong.net/" target="_blank">latlong.net</a></p>';
}

add_action( 'save_post_travelay_location', function ( $post_id ) {
	if ( ! isset( $_POST['dst_travelay_location_nonce'] ) ) return;
	if ( ! wp_verify_nonce( $_POST['dst_travelay_location_nonce'], 'dst_travelay_location_save' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$text_fields = array( 'tl_address', 'tl_city', 'tl_country', 'tl_country_code', 'tl_airport_code', 'tl_phone', 'tl_email', 'tl_hours', 'tl_terminal', 'tl_lat', 'tl_lng', 'tl_open_label', 'tl_open_status' );
	$bool_fields = array( 'tl_walk_in', 'tl_airport', 'tl_phone_247' );

	foreach ( $text_fields as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			update_post_meta( $post_id, $key, sanitize_text_field( $_POST[ $key ] ) );
		}
	}
	foreach ( $bool_fields as $key ) {
		update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? '1' : '' );
	}
} );
