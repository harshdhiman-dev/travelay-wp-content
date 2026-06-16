<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderCircularDirection extends DS_Field {

	/**
	 * Get
	 *
	 * @param array $args arguments.
	 * @param bool  $is_super_admin super admin check.
	 */
	public static function get( $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'         => 'Direction',
			'choices'       => array(
				''     => 'clockwise',
				'true' => 'anti-clockwise',
			),
			'default_value' => '',
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'button_group', 'data_circular-item-direction', $args, $is_super_admin );
	}
}
