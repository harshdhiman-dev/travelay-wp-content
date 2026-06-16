<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ButtonIcon extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): array {
		return array(
			self::add_field(
				'button_group',
				'is_custom_icon',
				array(
					'label'         => 'Button Icon',
					'choices'       => array(
						'global' => 'Default',
						'yes'    => 'Custom',
						'no'     => 'No Icon',
					),
					'default_value' => 'global',
				)
			),
			self::add_field(
				'button_group',
				'icon_type',
				array(
					'label'             => 'Icon Type',
					'choices'           => array(
						'library' => 'library',
						'custom'  => 'custom',
					),
					'default_value'     => 'library',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'is_custom_icon',
								'operator'  => '==',
								'value'     => 'yes',
							),
						),
					),
				)
			),

			self::add_field(
				'image',
				'icon',
				array(
					'label'             => 'Icon',
					'return_format'     => 'id',
					'preview_size'      => 'medium',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'is_custom_icon',
								'operator'  => '==',
								'value'     => 'yes',
							),
							array(

								'fieldPath' => 'icon_type',
								'operator'  => '==',
								'value'     => 'custom',
							),
						),
					),
				)
			),
			...self::get_icon_library_fields(),
			self::add_field(
				'true_false',
				'icon_reversed',
				array(
					'label'             => 'Icon Reversed',
					'default_value'     => 0,
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'is_custom_icon',
								'operator'  => '==',
								'value'     => 'yes',
							),
						),
					),
				)
			),
			self::add_field(
				'button_group',
				'icon_direction',
				array(
					'label'             => 'Icon position',
					'choices'           => array(
						'row-reverse' => 'left',
						'row'         => 'right',
					),
					'default_value'     => 'right',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'is_custom_icon',
								'operator'  => '==',
								'value'     => 'yes',
							),
						),
					),
				)
			),
		);
	}

	/**
	 * Get icon library fields
	 */
	private static function get_icon_library_fields(): array {
		$lib_key             = 'icon-library';
		$icon_library        = DS_Buttons::$icon_library;
		$fields              = array();
		$library_set_choices = array();

		if ( ! empty( $icon_library ) ) {
			foreach ( $icon_library as $set_name => $icon_set ) {
				$library_set_choices[ $lib_key . '_' . $set_name ] = ucwords( $set_name );

				$choices = array_combine( $icon_set, $icon_set );

				$fields[] = self::add_field(
					'radio',
					$lib_key . '_' . $set_name,
					array(
						'label'             => ucwords( $set_name ) . ' Icons',
						'choices'           => $choices,
						'conditional_logic' => array(
							array(
								array(
									'fieldPath' => 'is_custom_icon',
									'operator'  => '==',
									'value'     => 'yes',
								),
								array(

									'fieldPath' => 'icon_type',
									'operator'  => '==',
									'value'     => 'library',
								),
								array(

									'fieldPath' => $lib_key,
									'operator'  => '==',
									'value'     => $lib_key . '_' . $set_name,
								),
							),
						),
						'layout'            => 'horizontal',
						'wrapper'           => array(
							'width' => '60',
							'class' => 'button-icon-library',
						),
					)
				);
			}

			$lib_btn_group = self::add_field(
				'button_group',
				$lib_key,
				array(
					'label'             => 'Icon Library',
					'choices'           => $library_set_choices,
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'is_custom_icon',
								'operator'  => '==',
								'value'     => 'yes',
							),
							array(

								'fieldPath' => 'icon_type',
								'operator'  => '==',
								'value'     => 'library',
							),
						),
					),
					'wrapper'           => array(
						'width' => '20',
					),
				)
			);

			array_unshift( $fields, $lib_btn_group );
		}

		return $fields;
	}
}
