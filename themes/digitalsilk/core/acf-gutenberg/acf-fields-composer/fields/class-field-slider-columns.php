<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderColumns extends DS_Field {

	/**
	 * Get
	 *
	 * @param int  $columns columns.
	 * @param bool $default_value default value.
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $columns = 5, $default_value = 1, $is_super_admin = false ): string {
		$choices = self::get_choices( $columns );

		return self::add_field(
			'radio',
			'data_columns',
			array(
				'label'         => 'Columns',
				'layout'        => 'horizontal',
				'choices'       => $choices,
				'default_value' => $default_value,
			),
			$is_super_admin
		);
	}
}
