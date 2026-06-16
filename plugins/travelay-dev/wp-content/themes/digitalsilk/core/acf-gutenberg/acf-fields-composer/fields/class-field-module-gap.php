<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ModuleGap extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'group',
			'module_gap',
			array(
				'label'      => 'Module Outer Gap',
				'sub_fields' => array(
					self::add_field(
						'button_group',
						'class_gap_top',
						array(
							'label'         => 'Padding Top',
							'choices'       => array(
								'gt'   => 'default',
								'gt-s' => 'small',
								'gt-l' => 'big',
								''     => 'none',
							),
							'default_value' => '',
						)
					),
					self::add_field(
						'button_group',
						'class_gap_bottom',
						array(
							'label'         => 'Padding Bottom',
							'choices'       => array(
								'gb'   => 'default',
								'gb-s' => 'small',
								'gb-l' => 'big',
								''     => 'none',
							),
							'default_value' => '',
						)
					),
					self::add_field(
						'button_group',
						'class_gap_gutters',
						array(
							'label'         => 'Side Padding',
							'choices'       => array(
								''     		 	  => 'Enabled',
								'no-side-padding' => 'Disabled',
							),
							'default_value' => '',
						)
					),
					self::add_field(
						'button_group',
						'class_margin_top',
						array(
							'label'         => 'Margin Top',
							'choices'       => array(
								''          => 'none',
								'mt'        => 'default',
								'mt-custom' => 'custom',
							),
							'default_value' => '',
						)
					),
					self::add_field(
						'text',
						'margin_top_custom',
						array(
							'label'             => 'Custom Margin Top',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'class_margin_top',
										'operator'  => '==',
										'value'     => 'mt-custom',
									),
								),
							),
						),
						$is_super_admin
					),
					self::add_field(
						'button_group',
						'class_margin_bottom',
						array(
							'label'         => 'Margin Bottom',
							'choices'       => array(
								''          => 'none',
								'mb'        => 'default',
								'mb-custom' => 'custom',
							),
							'default_value' => '',
						)
					),
					self::add_field(
						'text',
						'margin_bottom_custom',
						array(
							'label'             => 'Custom Margin Bottom',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'class_margin_bottom',
										'operator'  => '==',
										'value'     => 'mb-custom',
									),
								),
							),
						),
						$is_super_admin
					),
				),
				'layout'     => 'block',
			),
			$is_super_admin
		);
	}
}
