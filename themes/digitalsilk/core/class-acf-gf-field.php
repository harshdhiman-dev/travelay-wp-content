<?php
/**
 * Register ACF field to display and use GravityForms plugin forms
 *
 * @package DS_Theme
 */
// phpcs:ignoreFile
if ( ! class_exists( 'acf' ) ) {
	return;
}

class DS_ACF_GF_Field extends acf_field {

	/**
	 * Get forms
	 *
	 * @var array
	 */
	public $forms;

	public function __construct() {
		$this->name     = 'forms';
		$this->label    = __( 'Gravity Form', 'gravityforms' );
		$this->category = __( 'Relational', 'acf' );
		$this->defaults = array(
			'return_format' => 'form_object',
			'multiple'      => 0,
			'allow_null'    => 0,
		);

		parent::__construct();
	}

	/**
	 * Settings for gravityforms field
	 *
	 * @param $field
	 */
	public function render_field_settings( $field ) {
		// Render a field settings that will tell us if an empty field is allowed or not
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Return Value', 'acf' ),
				'instructions' => __( 'Specify the returned value on front end', 'acf' ),
				'type'         => 'radio',
				'name'         => 'return_format',
				'layout'       => 'horizontal',
				'choices'      => array(
					'post_object' => __( 'Form Object', 'dstheme-admin' ),
					'id'          => __( 'Form ID', 'dstheme-admin' ),
				),
			)
		);

		acf_render_field_setting(
			$field,
			array(
				'label'   => __( 'Allow Null?', 'acf' ),
				'type'    => 'radio',
				'name'    => 'allow_null',
				'choices' => array(
					1 => __( 'Yes', 'acf' ),
					0 => __( 'No', 'acf' ),
				),
				'layout'  => 'horizontal',
			)
		);

		acf_render_field_setting(
			$field,
			array(
				'label'   => __( 'Select multiple values?', 'acf' ),
				'type'    => 'radio',
				'name'    => 'multiple',
				'choices' => array(
					1 => __( 'Yes', 'acf' ),
					0 => __( 'No', 'acf' ),
				),
				'layout'  => 'horizontal',
			)
		);
	}

	/**
	 * Render Gravity Form field with all the forms as options
	 *
	 * @param $field
	 *
	 * @return bool
	 */
	public function render_field( $field ) {

		if ( class_exists( 'GFAPI' ) ) {
			$this->forms = GFAPI::get_forms();
		}

		// Set our defaults
		$field   = array_merge( $this->defaults, $field );
		$choices = array();

		if ( empty( $this->forms ) ) {
			return;
		}

		foreach ( $this->forms as $form ) {
			$choices[ $form['id'] ] = $form['title'];
		}

		// Override field settings and start rendering
		$field['choices'] = $choices;
		$field['type']    = 'select';
		// Create a css id for our field
		$fieldId = str_replace( array( '[', ']' ), array( '-', '' ), $field['name'] );

		// Check if we're allowing multiple selections.
		$hiddenField  = '';
		$multiple     = '';
		$fieldOptions = '';

		if ( $field['multiple'] ) {
			$hiddenField = '<input type="hidden" name="{$field[\'name\']}">';
			$multiple    = '[]" multiple="multiple" data-multiple="1';
		}

		// Check if we're allowing an empty form. If so, create a default option
		if ( $field['allow_null'] ) {
			$fieldOptions .= '<option value="">' . __( '- Select a form -', 'dstheme-admin' ) . '</option>';
		}

		// Loop trough all our choices
		foreach ( $field['choices'] as $formId => $formTitle ) {
			$selected = '';

			if ( ( is_array( $field['value'] ) && in_array( $formId, $field['value'], false ) )
				|| (int) $field['value'] === (int) $formId
			) {
				$selected = ' selected';
			}

			$fieldOptions .= '<option value="' . $formId . '"' . $selected . '>' . $formTitle . '</option>';
		}

		// Start building the html for our field
		$fieldHhtml  = $hiddenField;
		$fieldHhtml .= '<select id="' . $fieldId . '" name="' . $field['name'] . $multiple . '">';
		$fieldHhtml .= $fieldOptions;
		$fieldHhtml .= '</select>';

		echo $fieldHhtml;
	}

	/**
	 * Return a form object when not empty
	 *
	 * @param $value
	 * @param $postId
	 * @param $field
	 *
	 * @return array|bool
	 */
	public function format_value( $value, $postId, $field ) {
		return $this->processValue( $value, $field );
	}

	/**
	 *
	 *  This filter is applied to the $value before it is updated in the db
	 *
	 * @param  $value - the value which will be saved in the database
	 * @param  post_id - the                                         $post_id of which the value will be saved
	 * @param  $field - the field array holding all the field options
	 *
	 * @return $value - the modified value
	 */
	public function update_value( $value ) {
		// Strip empty array values
		if ( is_array( $value ) ) {
			$value = array_values( array_filter( $value ) );
		}

		return $value;
	}

	/**
	 * Check what to return on basis of return format
	 *
	 * @param $value
	 * @param $field
	 *
	 * @return array|bool|int
	 */
	public function processValue( $value, $field ) {
		if ( is_array( $value ) ) {
			$formObjects = array();

			foreach ( $value as $key => $formId ) {
				$form = $this->processValue( $formId, $field );
				// Add it if it's not an error object
				if ( $form ) {
					$formObjects[ $key ] = $form;
				}
			}

			// Return the form object
			if ( ! empty( $formObjects ) ) {
				return $formObjects;
			}

			// Else return false
			return false;
		}

		// Make sure field is an array
		$field = (array) $field;

		if ( ! empty( $field['return_format'] ) && $field['return_format'] === 'id' ) {
			return (int) $value;
		}
		$form = GFAPI::get_form( $value );

		// Return the form object if it's not an error object. Otherwise return false.
		if ( ! is_wp_error( $form ) ) {
			return $form;
		}

		return false;
	}
}

add_action(
	'acf/include_field_types',
	function () {
		if ( class_exists( 'GFAPI' ) ) {
			new DS_ACF_GF_Field();
		}
	}
);
