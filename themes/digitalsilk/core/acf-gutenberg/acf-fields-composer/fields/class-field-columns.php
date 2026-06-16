<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_Columns extends DS_Field {

	/**
	 * Get
	 *
	 * @param int    $columns column count.
	 * @param string $field_name field name.
	 * @param array  $args arguments.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $columns = 5, $field_name = 'card_columns', $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'         => 'Card Columns',
			'choices'       => self::get_choices( $columns ),
			'default_value' => 3,
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'button_group', $field_name, $args, $is_super_admin );
	}
}
