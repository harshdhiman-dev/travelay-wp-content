<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_Container extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $default_value default value.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $default_value = 'container', $is_super_admin = false ): array {
		return array(
			self::add_field(
				'button_group',
				'container',
				array(
					'label'         => 'Width',
					'choices'       => array(
						'container'        => 'default',
						'container-wide'   => 'wide',
						'container-fluid'  => 'full',
						'container-custom' => 'custom',
					),
					'default_value' => $default_value,
				),
				$is_super_admin
			),
			self::add_field(
				'text',
				'container_width',
				array(
					'label'             => 'Container Max Width',
					'default_value'     => 0,
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'container',
								'operator'  => '==',
								'value'     => 'container-custom',
							),
						),
					),
				),
				$is_super_admin
			),
		);
	}
}
