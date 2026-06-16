<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ContentStyle extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $default_value default value.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $default_value = 'is-style-colors-inverted', $is_super_admin = false ): string {
		return self::add_field(
			'button_group',
			'content_style',
			array(
				'label'         => 'Content style',
				'choices'       => array(
					'is-style-colors-inverted' => 'Inverted Colors',
					'' 				  		   => 'Default',
				),
				'default_value' => $default_value,
			),
			$is_super_admin
		);
	}
}
