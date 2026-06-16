<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ComponentGap extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $field_name field name.
	 * @param array  $args arguments.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $field_name = 'card_gap', $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'         => 'Card Gap (px)',
			'choices'       => array(
				0   => '0',
				15  => '15',
				20  => '20',
				25  => '25',
				30  => '30',
				40  => '40',
				50  => '50',
				80  => '80',
				100 => '100',
				120 => '120',
			),
			'default_value' => 20,
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'select', $field_name, $args, $is_super_admin );
	}
}
