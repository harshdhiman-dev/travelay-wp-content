<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ScreenHeight extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $default_value default value.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $default_value = 'small', $is_super_admin = false ): array {
		return array(
			self::add_field(
				'button_group',
				'screen_height',
				array(
					'label'         => 'Height Desktop',
					'choices'       => array(
						'small'  => 'small',
						'medium' => 'mid',
						'full'   => 'full',
						'custom' => 'custom',
					),
					'default_value' => $default_value,
				),
				$is_super_admin
			),
			self::add_field(
				'group',
				'custom_height',
				array(
					'label'             => '',
					'sub_fields'        => array(
						self::add_field(
							'text',
							'height',
							array(
								'label' => 'Desktop Height Value',
							),
							'screen_height_custom_height'
						),
					),
					'layout'            => 'block',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'screen_height',
								'operator'  => '==',
								'value'     => 'custom',
							),
						),
					),
				),
				$is_super_admin
			),
			self::add_field(
				'button_group',
				'screen_height_mobile',
				array(
					'label'         => 'Height Mobile',
					'choices'       => array(
						'auto'   => 'auto',
						'custom' => 'custom',
					),
					'default_value' => 'auto',
				),
				$is_super_admin
			),
			self::add_field(
				'group',
				'custom_height_mobile',
				array(
					'label'             => '',
					'sub_fields'        => array(
						self::add_field(
							'text',
							'height',
							array(
								'label' => 'Mobile Height Value',
							),
							'screen_height_custom_height_mobile'
						),
					),
					'layout'            => 'block',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'screen_height_mobile',
								'operator'  => '==',
								'value'     => 'custom',
							),
						),
					),
				),
				$is_super_admin
			),
		);
	}
}
