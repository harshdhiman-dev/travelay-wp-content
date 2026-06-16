<?php

/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderColumnsMobile extends DS_Field {

	/**
	 * Get
	 *
	 * @param int  $max columns.
	 * @param bool $default_value default value.
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $max = 4, $default_value = 1, $is_super_admin = false ): string {
		return self::add_field(
			'range',
			'data_columns-mobile',
			array(
				'label'         => 'Columns Mobile',
				'min'           => 1,
				'max'           => $max,
				'step'          => 0.1,
				'default_value' => $default_value,
			),
			$is_super_admin
		);
	}
}
