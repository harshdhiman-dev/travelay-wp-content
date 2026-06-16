<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ComponentTitle extends DS_Field {

	/**
	 * Get
	 *
	 * @param array  $args arguments.
	 * @param string $default_value field name.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $args = array(), $default_value = 'h3', $is_super_admin = false ): string {
		$default_args = array(
			'label'      => 'Content Cards Title Styles',
			'layout'     => 'block',
			'sub_fields' => array(
				self::add_field(
					'select',
					'tag',
					array(
						'label'         => 'Tag',
						'choices'       => array(
							'h2' => 'h2',
							'h3' => 'h3',
							'h4' => 'h4',
						),
						'default_value' => $default_value,
					)
				),
			),
		);
		$args         = wp_parse_args( $args, $default_args );

		return self::add_field( 'group', 'component_title_styles', $args, $is_super_admin );
	}
}
