<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_LayoutType extends DS_Field {

	/**
	 * Get
	 *
	 * @param int    $types column types.
	 * @param string $field_name field name.
	 * @param array  $args arguments.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $types = 5, $field_name = 'layout_type', $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'         => 'Layout Type',
			'choices'       => self::get_choices( $types, 'v' ),
			'default_value' => 'v1',
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'button_group', $field_name, $args, $is_super_admin );
	}
}
