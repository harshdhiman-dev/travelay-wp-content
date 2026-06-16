<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ButtonSize extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'button_group',
			'size',
			array(
				'label'         => 'Size',
				'choices'       => array(
					'-small'  => 'small',
					'-normal' => 'normal',
					'-large'  => 'large',
				),
				'default_value' => '-normal',
			),
			$is_super_admin
		);
	}
}
