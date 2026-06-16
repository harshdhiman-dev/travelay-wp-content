<?php

/**
 * Plugin Name: Digital Silk: GravityForms Addon to pass URL Parameters
 * Description: A plugin to add hidden fields to Gravity Forms based on URL parameters.
 * Version: 1.0.2
 * Author: DigitalSilk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Checks if Gravity Forms is active and then initializes the plugin.
 */
function ds_gf_url_add_parameters_add_on_init() {
	DS_GF_URL_Parameters_AddOn::get_instance();
}

add_action( 'plugins_loaded', 'ds_gf_url_add_parameters_add_on_init' );

class DS_GF_URL_Parameters_AddOn {

	private static $instance = null;

	// Singleton pattern to ensure only one instance runs
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 */
	public function __construct() {
		// Ensure Gravity Forms fields are added at appropriate stages
		add_filter( 'gform_pre_render', array( $this, 'ds_add_hidden_fields_from_url_params' ) );
		add_filter( 'gform_pre_validation', array( $this, 'ds_add_hidden_fields_from_url_params' ) );
		add_filter( 'gform_pre_submission_filter', array( $this, 'ds_add_hidden_fields_from_url_params' ) );
		add_filter( 'gform_admin_pre_render', array( $this, 'ds_add_hidden_fields_from_url_params' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'plugin_assets' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_custom_css' ) );
		add_filter( 'gform_entry_field_visibility', array( $this, 'ds_hide_fields_in_entries_view' ), 10, 4 );
	}

	/**
	 * Add hidden fields from URL parameters to the form.
	 */
	public function ds_add_hidden_fields_from_url_params( $form ) {
		global $wp;

		// Get referrer and current page URL
		$referer_url  = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : '';
		$current_page = esc_url( home_url( add_query_arg( array(), $wp->request ) ) );

		// Define hidden fields with unique input names and default values
		$hidden_fields = [
			'input_referrer'     => [
				'label'   => 'Referrer',
				'default' => $referer_url,
				'id'      => 9990,
			],
			'input_page'         => [
				'label'   => 'Current Page',
				'default' => $current_page,
				'id'      => 9991,
			],
			'input_utm_campaign' => [
				'label'   => 'UTM Campaign',
				'default' => '',
				'id'      => 9992,
			],
			'input_utm_medium'   => [
				'label'   => 'UTM Medium',
				'default' => '',
				'id'      => 9993,
			],
			'input_utm_source'   => [
				'label'   => 'UTM Source',
				'default' => '',
				'id'      => 9994,
			],
			'input_utm_term'     => [
				'label'   => 'UTM Term',
				'default' => '',
				'id'      => 9995,
			],
			'input_utm_content'  => [
				'label'   => 'UTM Content',
				'default' => '',
				'id'      => 9996,
			],
			'input_utm_id'       => [
				'label'   => 'UTM ID',
				'default' => '',
				'id'      => 9997,
			],
		];

		// Get existing field names to prevent duplication
		$existing_field_names = wp_list_pluck( $form['fields'], 'inputName' );

		foreach ( $hidden_fields as $input_name => $field_data ) {
			if ( ! in_array( $input_name, $existing_field_names, true ) ) {
				$form['fields'][] = GF_Fields::create( [
					'type'         => 'hidden',
					'id'           => $field_data['id'],
					'formId'       => $form['id'],
					'label'        => $field_data['label'],
					'inputName'    => $input_name, // Match JavaScript
					'defaultValue' => $field_data['default'],
				] );
			}
		}

		if ( ! isset( $_COOKIE['ds_gf_data'] ) ) {
			return $form;
		}

		// Decode Base64 UTM Data
		$cookie_data = json_decode( base64_decode( $_COOKIE['ds_gf_data'] ), true );
		if ( ! $cookie_data ) {
			return $form;
		}

		foreach ( $form['fields'] as &$field ) {
			if ( ! isset( $field->inputName ) ) {
				continue;
			}

			switch ( $field->inputName ) {
				case 'input_utm_campaign':
					$_POST['input_9992'] = $cookie_data['utm_data']['utm_campaign'] ?? '';
					break;
				case 'input_utm_medium':
					$_POST['input_9993'] = $cookie_data['utm_data']['utm_medium'] ?? '';
					break;
				case 'input_utm_source':
					$_POST['input_9994'] = $cookie_data['utm_data']['utm_source'] ?? '';
					break;
				case 'input_utm_term':
					$_POST['input_9995'] = $cookie_data['utm_data']['utm_term'] ?? '';
					break;
				case 'input_utm_content':
					$_POST['input_9996'] = $cookie_data['utm_data']['utm_content'] ?? '';
					break;
				case 'input_utm_id':
					$_POST['input_9997'] = $cookie_data['utm_data']['utm_id'] ?? '';
					break;
			}
		}


		return $form;
	}

	/**
	 * Enqueue the plugin's JavaScript file.
	 */
	public function plugin_assets() {
		// Add debugging

		$script_path = plugin_dir_url( __FILE__ ) . 'assets/js/ds-gf-url-params.js';

		wp_enqueue_script(
			'ds-gf-url-params',
			$script_path,
			array( 'jquery' ),
			'1.0.0',
			true
		);
//		wp_enqueue_script(
//			'ds-gf-url-params',
//			plugin_dir_url(__FILE__) . 'assets/js/ds-gf-url-params.js',
//			array('jquery'), // Make sure Gravity Forms is a dependency
//			'1.0',
//			true // Load in footer
//		);
//		wp_enqueue_script(
//			'ds-gf-url-params',
//			plugin_dir_url(__FILE__) . 'assets/js/ds-gf-url-params.js',
//			array('jquery'), // Correct dependency
//			'1.0.1',
//			true // Load in footer
//		);
//		echo '<script src="' . plugin_dir_url(__FILE__) . 'assets/js/ds-gf-url-params.js"></script>';
	}

	/**
	 * Enqueue custom CSS for hiding specific columns in the Gravity Forms entries list view.
	 */
	public function enqueue_admin_custom_css( $hook ) {
		// Check if we're on the Gravity Forms entries list page
		if ( $hook !== 'forms_page_gf_entries' ) {
			return;
		}

		// Add inline CSS to hide specific columns
		wp_add_inline_style( 'wp-admin', $this->get_entries_list_custom_css() );
	}

	private function get_entries_list_custom_css() {
		return "
        /* Hide specific Gravity Forms columns in the entries list view */
        .column-ds_utm_source, .column-ds_utm_medium, .column-ds_utm_campaign, .column-ds_utm_term, .column-ds_utm_content, .column-ds_utm_id, .column-ds_utm_referrer {
            /*display: none;*/
        }
    ";
	}

	/**
	 * Hide specific fields in the Gravity Forms entries view.
	 */
	public function ds_hide_fields_in_entries_view( $visibility, $field, $entry, $form ) {
		// Define field IDs to hide
		$hidden_fields = array( 9990, 9991, 9992, 9993, 9994, 9995, 9996, 9997 );

		// If the field ID is in the hidden fields array, set visibility to 'hidden'
		if ( in_array( $field->id, $hidden_fields ) ) {
			return 'hidden';
		}

		return $visibility;
	}
}
