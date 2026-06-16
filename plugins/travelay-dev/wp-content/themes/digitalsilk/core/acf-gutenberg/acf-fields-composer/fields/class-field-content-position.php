<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ContentPosition extends DS_Field {

	/**
	 * Get
	 *
	 * @param string $default_value default value.
	 * @param bool   $is_super_admin super admin check.
	 */
	public static function get( $default_value = 'left', $is_super_admin = false ): string {
		return self::add_field(
			'button_group',
			'content_position',
			array(
				'label'         => 'Content position',
				'choices'       => array(
					'left'   => 'left',
					'center' => 'center',
					'right'  => 'right',
				),
				'default_value' => $default_value,
			),
			$is_super_admin
		);
	}
}
