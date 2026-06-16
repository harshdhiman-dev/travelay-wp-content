<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_BackgroundSimple extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'group',
			'background',
			array(
				'label'      => 'Background',
				'layout'     => 'block',
				'sub_fields' => array(
					self::add_field(
						'button_group',
						'bg_color_type',
						array(
							'label'   => 'Background type',
							'choices' => array(
								'color'    => 'color',
								'gradient' => 'gradient',
							),
						)
					),
					self::add_field(
						'color_picker',
						'color',
						array(
							'label'             => 'Background Color',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'color',
									),
								),
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'image',
									),
								),
							),
						)
					),
					self::add_field(
						'color_picker',
						'gradient_color_1',
						array(
							'label'             => 'Gradient color 1',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'gradient',
									),
								),
							),
						)
					),
					self::add_field(
						'color_picker',
						'gradient_color_2',
						array(
							'label'             => 'Gradient color 2',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'gradient',
									),
								),
							),
						)
					),
					DS_Field_BackgroundGradientDirection::get(
						'gradient_direction',
						array(
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'gradient',
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
