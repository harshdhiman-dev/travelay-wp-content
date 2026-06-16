<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ButtonStyle extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'button_group',
			'style',
			array(
				'label'   => 'Style',
				'choices' => array(
					'-primary' => 'primary',
					// '-inverted'  => 'inverted',
												'-secondary' => 'secondary',
					'-link'    => 'link',
				),
			),
			$is_super_admin
		);
	}
}
