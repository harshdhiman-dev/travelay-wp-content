<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ColumnsOrder extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $field_name field name.
	 * @param array  $args arguments.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $field_name = 'columns_order', $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'   => 'Content Order',
			'choices' => array(
				'default' => 'Text - Media',
				'reverse' => 'Media - Text',
			),
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'button_group', $field_name, $args, $is_super_admin );
	}
}
