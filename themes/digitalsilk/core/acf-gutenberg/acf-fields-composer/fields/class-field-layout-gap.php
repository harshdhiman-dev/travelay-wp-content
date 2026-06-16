<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_LayoutGap extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): array {
		return array(
			self::add_field(
				'button_group',
				'top_layout_gap',
				array(
					'label'         => 'Inner Top',
					'choices'       => array(
						'space-top'   	   => 'default',
						''                 => 'none',
						'space-top-custom' => 'custom',
					),
					'default_value' => 'space-top',
				),
				$is_super_admin
			),
			self::add_field(
				'group',
				'top_layout_custom',
				array(
					'label'             => 'Your Value',
					'sub_fields'        => array(
						self::add_field(
							'text',
							'gap',
							array(
								'label' => '',
							),
							$is_super_admin
						),
					),
					'layout'            => 'block',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'top_layout_gap',
								'operator'  => '==',
								'value'     => 'space-top-custom',
							),
						),
					),
				),
				$is_super_admin
			),
			self::add_field(
				'button_group',
				'bottom_layout_gap',
				array(
					'label'         => 'Inner Bottom',
					'choices'       => array(
						'space-bottom'        => 'default',
						''            		  => 'none',
						'space-bottom-custom' => 'custom',
					),
					'default_value' => 'space-bottom',
				),
				$is_super_admin
			),
			self::add_field(
				'group',
				'bottom_layout_custom',
				array(
					'label'             => 'Your Value',
					'sub_fields'        => array(
						self::add_field(
							'text',
							'gap',
							array(
								'label' => '',
							),
							$is_super_admin
						),
					),
					'layout'            => 'block',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'bottom_layout_gap',
								'operator'  => '==',
								'value'     => 'space-bottom-custom',
							),
						),
					),
				),
				$is_super_admin
			),
		);
	}
}
