<?php

/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ModuleDecorations extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = true ): array {

		return array(
			DS_Field::tab(
				'ds_background_decorations_tab',
				array(
					'label'     => 'Background Decorations',
					'placement' => 'left',
				),
				$is_super_admin
			),
			DS_Field::repeater(
				'decor_settings',
				array(
					'label'        => '',
					'layout'       => 'block',
					'button_label' => 'Add Decor',
					'sub_fields'   => array(
						DS_Field::accordion(
							'decor_acc',
							array(
								'label'        => 'Decoration',
								'open'         => 1,
								'multi_expand' => 1,
							)
						),
						DS_Field::button_group(
							'decor_type',
							array(
								'label'         => 'Decor Type',
								'choices'       => array(
									'none'  => 'none',
									'class' => 'class',
									'image' => 'image',
								),
								'default_value' => 'none',
							)
						),
						DS_Field::image(
							'decor_image',
							array(
								'label'             => 'Decor Image',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '==',
											'value'     => 'image',
										),
									),
								),
							)
						),
						DS_Field::select(
							'decor_class',
							array(
								'label'             => 'Decor Class',
								'choices'           => array( '' => 'none' ),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '!=',
											'value'     => 'none',
										),
									),
								),
							)
						),
						DS_Field::true_false(
							'embed_image',
							array(
								'label'             => 'Embed Image?',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '==',
											'value'     => 'image',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'horizontal_pos',
							array(
								'label'             => 'Horizontal Position',
								'choices'           => array(
									'left'     => 'left',
									'center-x' => 'center',
									'right'    => 'right',
								),
								'default_value'     => 'left',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '!=',
											'value'     => 'none',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'vertical_pos',
							array(
								'label'             => 'Vertical Position',
								'choices'           => array(
									'top'      => 'top',
									'center-y' => 'center',
									'bottom'   => 'bottom',
								),
								'default_value'     => 'top',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'decor_type',
											'operator'  => '!=',
											'value'     => 'none',
										),
									),
								),
							)
						),
					),
				),
				$is_super_admin
			),
		);
	}

	/**
	 * Checks if a module has decorations configured.
	 *
	 * @param array $field The field array containing the module's parent information.
	 *
	 * @return array Returns an array where the first element is a boolean indicating
	 *               whether the module has decorations, and the second element is an
	 *               array of decoration classes if available.
	 */
	public static function has_module_decors( $field ): array {
		if ( ! class_exists( 'DS_ModuleExtraFields' ) ) {
			return array( false, array() );
		}

		$block_name     = str_replace( 'group_group_', '', $field['parent'] );
		$module_classes = DS_ModuleExtraFields::$module_decoration_classes;

		if ( ! isset( $module_classes[ $block_name ] ) ) {
			return array( false, array() );
		}

		if ( empty( $module_classes[ $block_name ] ) ) {
			return array( false, array() );
		}

		return array( true, $module_classes[ $block_name ] );
	}

	/**
	 * Prepare fields.
	 *
	 * @param array $field The field configuration array.
	 *
	 * @return array|bool Returns the updated field configuration array or false if conditions are not met.
	 */
	public static function prepare_fields( $field ) {

		list( $has_module_decors, $module_classes ) = self::has_module_decors( $field );

		if ( isset( $field['ds_block_name'] ) ) {
			return $field;
		}

		if ( ! $has_module_decors ) {
			return false;
		}

		if ( 'accordion' === $field['type'] ) {
			return $field;
		}

		// decor_class acf field.
		$field['sub_fields'][3]['choices'] = $module_classes;

		return $field;
	}

	/**
	 * Load group value.
	 *
	 * @param mixed $value The value to load.
	 * @param int   $post_id The ID of the post being loaded.
	 * @param array $field The field data related to the group.
	 *
	 * @return mixed Returns the loaded value, or false if specific conditions are not met.
	 */
	public static function load_group_value( $value, $post_id, $field ) {

		if ( isset( $field['ds_block_name'] ) ) {
			return $value;
		}

		list( $has_module_decors, $module_classes ) = self::has_module_decors( $field );

		if ( ! $has_module_decors ) {
			return false;
		}

		return $value;
	}
}

add_filter(
	'acf/prepare_field/name=decor_settings',
	array(
		DS_Field_ModuleDecorations::class,
		'prepare_fields',
	),
	99,
	1
);
add_filter(
	'acf/load_value/name=decor_settings',
	array(
		DS_Field_ModuleDecorations::class,
		'load_group_value',
	),
	10,
	3
);

add_filter(
	'acf/prepare_field/name=ds_background_decorations_tab',
	array(
		DS_Field_ModuleDecorations::class,
		'prepare_fields',
	)
);
