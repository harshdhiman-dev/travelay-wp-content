<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderRotateToActive extends DS_Field {

	/**
	 * Get
	 *
	 * @param array $args arguments.
	 * @param bool  $is_super_admin super admin check.
	 */
	public static function get( array $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'         => 'Rotate to Active?',
			'ui'            => 1,
			'default_value' => 0,
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'true_false', 'data_circular-rotate-to-active', $args, $is_super_admin );
	}
}
