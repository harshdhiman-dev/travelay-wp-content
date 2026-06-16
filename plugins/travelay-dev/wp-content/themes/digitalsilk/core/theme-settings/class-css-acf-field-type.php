<?php
//phpcs:ignoreFile
/**
 * Render additional setting field for ACF fields while adding new filed group.
 * Required for "theme settings" and "theme content" option pages.
 */
class DS_FieldSettings {

	public $types = array(
		'def_value'            => 'Value',
		'size_em'              => 'em',
		'size_rem'             => 'rem',
		'size_px'              => 'px',
		'var'                  => 'var',
		'img_url'              => 'Image',
		'img_url_base64'       => 'Image Base64',
		'color'                => 'Color',
		'color_switcher'       => 'Color Selector',
		'color_switcher_color' => 'Color Selector RGBA',
		'gradient'             => 'Gradient',
		'gradient_color'       => 'Gradient Color',
		'gradient_direction'   => 'Gradient Direction',
		'font_family'          => 'Font Family',
		'font_weight'          => 'Font Weight',
	);

	public function __construct() {
		add_action( 'acf/render_field_settings', [ $this, 'add_type_field_settings' ], 10, 1 );
		add_action( 'acf/render_field_settings', [ $this, 'add_css_key_name_field_settings' ], 10, 1 );
		add_filter( 'acf/update_field', [ $this, 'color_switcher_settings' ], 50, 1 );
		add_filter( 'acf/prepare_field', [ $this, 'color_switcher_settings' ], 50, 1 );
	}

	/**
	 * Adds additional select field  'ds_asset_type' to the Field
	 *
	 * @param $field
	 */
	public function add_type_field_settings( $field ) {
		acf_render_field_setting(
            $field,
            array(
				'label'        => __( 'Field Asset Type', 'dstheme' ),
				'instructions' => 'Choose type if this field will be used for theme.css generation.',
				'name'         => 'ds_asset_type',
				'type'         => 'select',
				'choices'      => $this->types,
				'multiple'     => 0,
				'ui'           => 1,
				'allow_null'   => 1,
				'placeholder'  => __( 'Select Type', 'dstheme' ),
            ),
            true
        );
	}

	/**
	 * Adds additional input field 'ds_css_key_name' to the Field
	 *
	 * @param $field
	 */
	public function add_css_key_name_field_settings( $field ) {
		acf_render_field_setting(
            $field,
            array(
				'label'        => __( 'CSS Key Name', 'dstheme' ),
				'instructions' => 'If you want to use custom CSS key. Note: You need to select Field Asset Type first.',
				'name'         => 'ds_css_key_name',
				'type'         => 'text',
				'conditions'   => array(
					array(
						array(
							'field'    => 'ds_asset_type',
							'operator' => '!=',
							'value'    => null,
						),
						array(
							'field'    => 'ds_asset_type',
							'operator' => '!=',
							'value'    => 'color_switcher_color',
						),
					),
				),
            ),
            true
        );
	}

	/**
	 * Filter settings for the fields before save and before render
	 *
	 * @param $field
	 *
	 * @return mixed
	 */
	public function color_switcher_settings( $field ) {
		if ( ! empty( $field['ds_asset_type'] ) ) {
			if ( $field['ds_asset_type'] === 'color_switcher' ) {
				$field['type']             = 'radio';
				$field['wrapper']['class'] = empty( $field['wrapper']['class'] ) ? 'ds-color-selector' : $field['wrapper']['class'];
				if ( empty( $field['choices'] ) ) {
					$field['choices'] = array(
						'--primary-color1'   => 'Main Color 1',
						'--primary-color2'   => 'Main Color 2',
						'--primary-color3'   => 'Main Color 3',
						'--secondary-color1' => 'Secondary Color 1',
						'--secondary-color2' => 'Secondary Color 2',
						'custom'             => 'Custom',
					);
				}
			}

			if ( $field['ds_asset_type'] === 'color_switcher_color' ) {
				$field['type']           = 'color_picker';
				$field['enable_opacity'] = 1;
				if ( empty( $field['conditional_logic'] ) ) {
					$parent_field        = acf_get_field( $field['parent'] );
					$color_switcher_key  = '';
					$color_switcher_name = str_replace( '_custom', '', $field['_name'] );
					foreach ( $parent_field['sub_fields'] as $sub_field ) {
						if ( ! empty( $sub_field['ds_asset_type'] ) && $sub_field['ds_asset_type'] === 'color_switcher' && $sub_field['_name'] === $color_switcher_name ) {
							$color_switcher_key = $sub_field['key'];
						}
					}
					if ( ! empty( $color_switcher_key ) ) {
						$field['conditional_logic'] = array(
							array(
								array(
									'field'    => $color_switcher_key,
									'operator' => '==',
									'value'    => 'custom',
								),
							),
						);
					}
				}
			}
		}

		return $field;
	}
}

new DS_FieldSettings();
