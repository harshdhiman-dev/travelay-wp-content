<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_CaseStudy_V2_ComponentSettings extends DS_Field {
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
						DS_Field_ComponentGap::get(
							'inner_gap_top',
							array(
								'label'         => 'Padding Top (px)',
								'default_value' => 20,
							)
						),
						DS_Field_ComponentGap::get(
							'inner_gap_left',
							array(
								'label'         => 'Padding Left (px)',
								'default_value' => 20,
							)
						),
						DS_Field_ComponentGap::get(
							'inner_gap_right',
							array(
								'label'         => 'Padding Right (px)',
								'default_value' => 20,
							)
						),
						DS_Field_ComponentGap::get(
							'inner_gap_bottom',
							array(
								'label'         => 'Padding Bottom (px)',
								'default_value' => 20,
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
						DS_Field_ComponentGap::get(
							'inner_gap_top',
							array(
								'label'         => 'Padding Top (px)',
								'default_value' => 0,
							)
						),
						DS_Field_ComponentGap::get(
							'inner_gap_left',
							array(
								'label'         => 'Padding Left (px)',
								'default_value' => 20,
							)
						),
						DS_Field_ComponentGap::get(
							'inner_gap_right',
							array(
								'label'         => 'Padding Right (px)',
								'default_value' => 20,
							)
						),
						DS_Field_ComponentGap::get(
							'inner_gap_bottom',
							array(
								'label'         => 'Padding Bottom (px)',
								'default_value' => 0,
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
		);
	}
}
