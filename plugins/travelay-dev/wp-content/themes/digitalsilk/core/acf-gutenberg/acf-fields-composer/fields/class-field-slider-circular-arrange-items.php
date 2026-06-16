<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderCircularArrangeItems extends DS_Field {

	/**
	 * Get
	 *
	 * @param array $args arguments.
	 * @param bool  $is_super_admin super admin check.
	 */
	public static function get( array $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'         => 'Arrange Items',
			'choices'       => array(
				''       => 'none',
				'center' => 'center',
			),
			'default_value' => '',
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'button_group', 'data_circular-arrange', $args, $is_super_admin );
	}
}
