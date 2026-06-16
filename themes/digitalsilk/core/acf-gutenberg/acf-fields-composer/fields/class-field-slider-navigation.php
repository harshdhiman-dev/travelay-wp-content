<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderNavigation extends DS_Field {

	/**
	 * Get
	 *
	 * @param array $args arguments.
	 * @param bool  $is_super_admin super admin check.
	 */
	public static function get( array $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'         => 'Navigation',
			'choices'       => array(
				''       => 'none',
				'tabbed' => 'tabbed',
				'thumbs' => 'thumbs',
			),
			'default_value' => 'tabbed',
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'button_group', 'data_thumbs', $args, $is_super_admin );
	}
}
