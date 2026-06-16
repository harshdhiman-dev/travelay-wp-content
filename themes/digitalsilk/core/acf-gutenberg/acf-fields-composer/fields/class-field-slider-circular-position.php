<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderCircularPosition extends DS_Field {

	/**
	 * Get
	 *
	 * @param array $args arguments.
	 * @param bool  $is_super_admin super admin check.
	 */
	public static function get( array $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'         => 'Position',
			'choices'       => array(
				'1' => 'top',
				'2' => 'right',
				'3' => 'bottom',
				'4' => 'left',
			),
			'default_value' => '2',
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'button_group', 'data_circular-position', $args, $is_super_admin );
	}
}
