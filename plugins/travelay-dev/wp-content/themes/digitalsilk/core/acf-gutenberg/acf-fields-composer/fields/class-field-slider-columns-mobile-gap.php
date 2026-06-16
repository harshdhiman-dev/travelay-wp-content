<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SliderColumnsMobileGap extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'select',
			'data_columns-mobile-gap',
			array(
				'label'         => 'Columns Mobile Gap (px)',
				'choices'       => array(
					0   => '0',
					15  => '15',
					20  => '20',
					25  => '25',
					30  => '30',
					40  => '40',
					50  => '50',
					80  => '80',
					100 => '100',
					120 => '120',
				),
				'default_value' => 15,
			),
			$is_super_admin
		);
	}
}
