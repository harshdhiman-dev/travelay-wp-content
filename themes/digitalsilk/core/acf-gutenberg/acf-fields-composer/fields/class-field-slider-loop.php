<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderLoop extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $default_value default value.
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $default_value = 0, $is_super_admin = false ): string {
		return self::add_field(
			'true_false',
			'data_loop',
			array(
				'label'         => 'Infinite Loop?',
				'default_value' => $default_value,
				'ui'            => 1,
			),
			$is_super_admin
		);
	}
}
