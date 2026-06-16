<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_Block_V1_ComponentSettings extends DS_Field {
	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = true ): array {
		return array(
			DS_Field::group(
				'component_settings',
				array(
					'label'      => 'Component Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field_ComponentGap::get(
							'inner_gap_vertical',
							array(
								'label'         => 'Padding-Y',
								'default_value' => 0,
							)
						),
						DS_Field_ComponentGap::get(
							'inner_gap_horizontal',
							array(
								'label'         => 'Padding-X',
								'default_value' => 0,
							)
						),
						DS_Field::button_group(
							'media_fit',
							array(
								'label'         => 'Media fit',
								'choices'       => array(
									'contain' => 'contain',
									'cover'	  => 'cover',
									'bg'	  => 'bg',
								),
								'default_value' => 'contain',
							)
						),
						DS_Field::button_group(
							'media_ratio',
							array(
								'label'         => 'Image Ratio',
								'choices'       => array(
									'r-1x1'    => '1x1',
									'r-4x3'	   => '4x3',
									'r-3x4'	   => '3x4',
									'r-16x9'   => '16x9',
									'r-custom' => 'none',
								),
								'default_value' => 'r-16x9',
							)
						),
						DS_Field::range(
							'text_clamp',
							array(
								'label'         => 'Text Line Limit',
								'min'           => 0,
								'max'           => 8,
								'default_value' => 4,
							)
						),
						DS_Field::button_group(
							'orientation',
							array(
								'label'         => 'Orientation',
								'choices'       => array(
									'vertical'   => 'vertical',
									'horizontal' => 'horizontal',
								),
								'default_value' => 'vertical',
							)
						),
						DS_Field::button_group(
							'horizontal_alignment',
							array(
								'label'         => 'X-Align',
								'choices'       => array(
									'left'   => 'Left',
									'center' => 'Center',
									'right'  => 'Right',
								),
								'default_value' => 'center',
							)
						),
						DS_Field::button_group(
							'vertical_alignment',
							array(
								'label'         => 'Y-Align',
								'choices'       => array(
									'top'    => 'Top',
									'center' => 'Center',
									'bottom' => 'Bottom',
								),
								'default_value' => 'top',
							)
						),
						DS_Field::true_false(
							'has_background',
							array(
								'label' => 'Custom Background?',
								'ui'    => 1,
							)
						),
						DS_Field::color_picker(
							'component_background',
							array(
								'label'             => 'Background',
								'default_value'     => '#ffffff',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'has_background',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::color_picker( 'title_color', array( 'label' => 'Title Color' ) ),
						DS_Field::color_picker( 'content_color', array( 'label' => 'Text Color' ) ),
						DS_Field::true_false(
							'has_hover',
							array(
								'label' => 'Has Hover?',
								'ui'    => 1,
							)
						),
						DS_Field_ComponentType::get(),
					),
				),
				$is_super_admin
			),
		);
	}
}
