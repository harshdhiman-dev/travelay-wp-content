<?php
/**
 * Adds custom ACF fields for demo live preview (button settings, form settings)
 */

if ( ! function_exists( 'ds_include_custom_acf_fields' ) ) {
	/**
	 * This function will include the field type class
	 */
	function ds_include_custom_acf_fields() {
		foreach ( glob( get_template_directory() . '/core/theme-settings/acf-custom-fields/fields/*.php' ) as $filename ) {
			include_once $filename;
		}
	}

	add_action( 'acf/include_field_types', 'ds_include_custom_acf_fields' );
}
