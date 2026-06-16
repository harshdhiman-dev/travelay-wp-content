<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_BackgroundRepeat extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $field_name field name.
	 * @param array  $args arguments.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $field_name = 'class_background_repeat', $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'   => 'Background repeat',
			'choices' => array(
				''                => '----',
				'bg-no-repeat'    => 'no-repeat',
				'bg-repeat'       => 'repeat',
				'bg-repeat-x'     => 'repeat-x',
				'bg-repeat-y'     => 'repeat-y',
				'bg-repeat-round' => 'round',
				'bg-repeat-space' => 'space',
			),
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'select', $field_name, $args, $is_super_admin );
	}
}
