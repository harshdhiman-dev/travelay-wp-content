<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderAutoplay extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'group',
			'data_autoplay',
			array(
				'label'      => 'Autoplay',
				'layout'     => 'block',
				'sub_fields' => array(
					self::add_field(
						'true_false',
						'data_enabled',
						array(
							'label' => 'Enabled',
							'ui'    => 1,
						)
					),

					self::add_field(
						'number',
						'data_delay',
						array(
							'label'             => 'Delay',
							'instructions'      => 'Leave empty for default value',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'data_enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
						)
					),
					self::add_field(
						'true_false',
						'data_observer',
						array(
							'label'             => 'Play only in viewport',
							'ui'                => 1,
							'default_value'     => 1,
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'data_enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
						)
					),
					self::add_field(
						'number',
						'data_speed',
						array(
							'label'             => 'Speed',
							'instructions'      => 'Leave empty for default value',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'data_enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
						)
					),
					self::add_field(
						'true_false',
						'data_continuously',
						array(
							'label'             => 'Marquee',
							'ui'                => 1,
							'default_value'     => 0,
							'instructions'      => '(linear continuously transition)',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'data_enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
						)
					),
				),
			),
			$is_super_admin
		);
	}
}
