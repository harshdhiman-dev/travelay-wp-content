<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_Testimonial_V2_ComponentSettings extends DS_Field {
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
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Main Component Settings',
								'open'  => 1,
							)
						),
						DS_Field_ColumnsOrder::get(),
						DS_Field::range(
							'columns_ratio',
							array(
								'label'         => 'Columns Ratio',
								'append'        => '%',
								'default_value' => 50,
							)
						),
						DS_Field::range(
							'inner_gap_top',
							array(
								'label'  => 'Padding Top',
								'max'    => 100,
								'append' => '%',
							)
						),
						DS_Field::range(
							'inner_gap_left',
							array(
								'label'  => 'Padding Left',
								'max'    => 100,
								'append' => '%',
							)
						),
						DS_Field::range(
							'inner_gap_right',
							array(
								'label'  => 'Padding Right',
								'max'    => 100,
								'append' => '%',
							)
						),
						DS_Field::range(
							'inner_gap_bottom',
							array(
								'label'  => 'Padding Bottom',
								'max'    => 100,
								'append' => '%',
							)
						),
						DS_Field::color_picker( 'bg_color', array( 'label' => 'Background Color' ) ),
					),
				),
				$is_super_admin
			),
			DS_Field::group(
				'text_component_settings',
				array(
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Text Component Settings',
								'open'  => 1,
							)
						),
						DS_Field_ColumnsOrder::get( 'quote_order', array( 'label' => 'Quote Order' ) ),
						DS_Field::true_false(
							'has_read_full_story',
							array(
								'label' => 'Has Read Full Story?',
								'ui'    => 1,
							)
						),
						DS_Field::range(
							'inner_gap_top',
							array(
								'label'  => 'Padding Top',
								'max'    => 100,
								'append' => '%',
							)
						),
						DS_Field::range(
							'inner_gap_left',
							array(
								'label'  => 'Padding Left',
								'max'    => 100,
								'append' => '%',
							)
						),
						DS_Field::range(
							'inner_gap_right',
							array(
								'label'  => 'Padding Right',
								'max'    => 100,
								'append' => '%',
							)
						),
						DS_Field::range(
							'inner_gap_bottom',
							array(
								'label'  => 'Padding Bottom',
								'max'    => 100,
								'append' => '%',
							)
						),
						DS_Field::button_group(
							'horizontal_alignment',
							array(
								'label'         => 'X-Align',
								'choices'       => array(
									'left'   => 'left',
									'center' => 'center',
									'right'  => 'right',
								),
								'default_value' => 'left',
							)
						),
						DS_Field::button_group(
							'vertical_alignment',
							array(
								'label'         => 'Y-Align',
								'choices'       => array(
									'top'    => 'top',
									'center' => 'center',
									'bottom' => 'bottom',
								),
								'default_value' => 'center',
							)
						),
					),
				),
				$is_super_admin
			),
			DS_Field::group(
				'media_component_settings',
				array(
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Media Component Settings',
								'open'  => 1,
							)
						),
						DS_Field::true_false(
							'has_side_image',
							array(
								'label' => 'Has Side Image?',
								'ui'    => 1,
							)
						),
						DS_Field::true_false(
							'has_avatar',
							array(
								'label' => 'Has Avatar?',
								'ui'    => 1,
							)
						),
						DS_Field::true_false(
							'is_rounded',
							array(
								'label'             => 'Is Avatar Rounded?',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'has_avatar',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
					),
				),
				$is_super_admin
			),
		);
	}
}
