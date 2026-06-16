<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_BackgroundPosition extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $field_name field name.
	 * @param array  $args arguments.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $field_name = 'class_background_position', $args = array(), $is_super_admin = false ): string {
		$default_args = array(
			'label'   => 'Background position',
			'choices' => array(
				''                => '----',
				'bg-center'       => 'center',
				'bg-bottom'       => 'bottom',
				'bg-left'         => 'left',
				'bg-left-bottom'  => 'left-bottom',
				'bg-left-top'     => 'left-top',
				'bg-right'        => 'right',
				'bg-right-bottom' => 'right-bottom',
				'bg-right-top'    => 'right-top',
				'bg-top'          => 'top',
			),
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'select', $field_name, $args, $is_super_admin );
	}
}
