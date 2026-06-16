<?php
/**
 * Set default values on ACF fields with custom field attribute 'ds_default_value'
 * based on predefined field type default value.
 *
 * For Repeaters use additional field attribute 'ds_default_value_items' for setting amount of rows added - Defaults to 3
 */
// phpcs:ignoreFile
class DS_DefaultFieldValues {

	private array $default_values = array();

	public function __construct() {
		$this->default_values = array(
			'image'    => get_option( 'options_default_image_field_type' ),
			'text'     => get_option( 'options_default_text_field_type' ),
			'textarea' => get_option( 'options_default_textarea_field_type' ),
			'wysiwyg'  => get_option( 'options_default_wysiwyg_field_type' ),
			'number'   => 10,
		);

		add_action( 'acf/render_field_settings', array( $this, 'add_default_value_setting_to_all_fields' ) );
		add_filter( 'acf/load_value/type=repeater', array( $this, 'acf_load_repeater_default_value' ), 20, 3 );
		add_filter( 'acf/load_value', array( $this, 'acf_load_simple_fields_default_value' ), 20, 3 );
	}

	public function add_default_value_setting_to_all_fields( $field ): void {
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Load Default Value?', 'dstheme' ),
				'instructions' => __( 'Loads value from predefined list of default values for some field types', 'dstheme' ),
				'name'         => 'ds_default_value',
				'type'         => 'true_false',
				'ui'           => 1,
			)
		);
	}

	private function is_default_value_enabled( array $field ): bool {
		return isset( $field['ds_default_value'] ) && $field['ds_default_value'];
	}

	private function field_type_has_default_value( array $field ): bool {
		return isset( $this->default_values[ $field['type'] ] ) && ! empty( $this->default_values[ $field['type'] ] );
	}

	/**
	 * Load simple field types predefined default values only one time when content has never been added
	 * and the custom option to load default values is enabled.
	 */
	public function acf_load_simple_fields_default_value( $value, $post_id, $field ) {
		if ( ! in_array( $field['type'], array_keys( $this->default_values ) ) || ! empty( $value ) || ! $this->is_default_value_enabled( $field ) || ! $this->field_type_has_default_value( $field ) || acf_get_metadata( $post_id, $field['name'] ) !== null ) {
			return $value;
		}

		return $this->default_values[ $field['type'] ];
	}

	/**
	 * Load repeater field type predefined default values only one time when content has never been added
	 * and the custom option to load default values is enabled.
	 */
	public function acf_load_repeater_default_value( $value, $post_id, $field ) {
		if ( ! empty( $value ) || empty( $field['sub_fields'] ) || ! $this->is_default_value_enabled( $field ) || acf_get_metadata( $post_id, $field['name'] ) !== null ) {
			return $value;
		}

		$value     = array();
		$max_items = $field['ds_default_value_items'] ?? 3;
		foreach ( $field['sub_fields'] as $sub_field ) {
			for ( $i = 0; $i < $max_items; $i++ ) {
				if ( ! $this->is_default_value_enabled( $sub_field ) || ! $this->field_type_has_default_value( $sub_field ) ) {
					continue;
				}
				$value[ $i ][ $sub_field['key'] ] = $this->default_values[ $sub_field['type'] ];
			}
		}

		return $value;
	}
}

new DS_DefaultFieldValues();
