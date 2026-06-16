<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_TitleStyles extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'group',
			'title_styles',
			array(
				'label'      => 'Title styles',
				'sub_fields' => array(
					self::add_field(
						'select',
						'tag',
						array(
							'label'         => 'Heading Tag',
							'choices'       => array(
								'h1' => 'h1',
								'h2' => 'h2',
								'h3' => 'h3',
								'h4' => 'h4',
							),
							'default_value' => 'h2',
						)
					),
					self::add_field(
						'select',
						'tag_style',
						array(
							'label'         => 'Heading Style',
							'choices'       => array(
								'h1' => 'h1',
								'h2' => 'h2',
								'h3' => 'h3',
								'h4' => 'h4',
							),
							'default_value' => 'h2',
						)
					),
					self::add_field(
						'button_group',
						'horizontal_alignment',
						array(
							'label'         => 'X-Align',
							'choices'       => array(
								'left'   => 'Left',
								'center' => 'Center',
								'right'  => 'Right',
							),
							'default_value' => 'left',
						)
					),
					self::add_field(
						'button_group',
						'horizontal_alignment_mobile',
						array(
							'label'         => 'X-Align Mobile',
							'choices'       => array(
								'left'   => 'Left',
								'center' => 'Center',
								'right'  => 'Right',
							),
							'default_value' => 'left',
						)
					),
					self::add_field(
						'button_group',
						'layout',
						array(
							'label'         => 'Layout Type',
							'choices'       => array(
								'v1' => 'v1',
								'v2' => 'v2',
								'v3' => 'v3',
							),
							'default_value' => 'v1',
						)
					),
				),
				'layout'     => 'block',
			),
			$is_super_admin
		);
	}
}
