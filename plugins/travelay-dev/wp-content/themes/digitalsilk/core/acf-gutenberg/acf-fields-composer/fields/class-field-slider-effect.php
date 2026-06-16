<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderEffect extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'group',
			'data_effect',
			array(
				'label'      => 'Effect',
				'layout'     => 'block',
				'sub_fields' => array(
					self::add_field(
						'true_false',
						'enabled',
						array(
							'label' => 'Enabled',
							'ui'    => 1,
						)
					),

					self::add_field(
						'select',
						'data_transition',
						array(
							'label'             => 'Transition',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
							'choices'           => array(
								''          => '----',
								'fade'      => 'Fade',
								'cube'      => 'Cube',
								'coverflow' => 'Coverflow',
								'flip'      => 'Flip',
							),
						)
					),
				),
			),
			$is_super_admin
		);
	}
}
