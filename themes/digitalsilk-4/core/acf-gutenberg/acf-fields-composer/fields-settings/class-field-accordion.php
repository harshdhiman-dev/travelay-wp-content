<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_Accordion_ComponentSettings extends DS_Field {
	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = true ): array {
		return array(
			DS_Field::group(
				'accordion_component_settings',
				array(
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Accordion Component Settings',
								'open'  => 1,
							)
						),
						DS_Field::range(
							'inner_gap_top',
							array(
								'label'  => 'Padding Top',
								'max'    => 50,
								'append' => 'px',
							)
						),
						DS_Field::range(
							'inner_gap_left',
							array(
								'label'  => 'Padding Left',
								'max'    => 50,
								'append' => 'px',
							)
						),
						DS_Field::range(
							'inner_gap_right',
							array(
								'label'  => 'Padding Right',
								'max'    => 50,
								'append' => 'px',
							)
						),
						DS_Field::range(
							'inner_gap_bottom',
							array(
								'label'  => 'Padding Bottom',
								'max'    => 50,
								'append' => 'px',
							)
						),
						DS_Field::true_false(
							'has_border',
							array(
								'label' => 'Has Border?',
								'ui'    => 1,
							)
						),
						DS_Field::color_picker(
							'border_color',
							array(
								'label'             => 'Border Color',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'has_border',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::color_picker( 'title_text_color', array( 'label' => 'Title Text Color' ) ),
						DS_Field::color_picker( 'title_bg_color', array( 'label' => 'Title Background Color' ) ),
						DS_Field::color_picker( 'area_text_color', array( 'label' => 'Description Text Color' ) ),
						DS_Field::color_picker( 'area_bg_color', array( 'label' => 'Description Background Color' ) ),
						DS_Field::button_group(
							'icon_styles',
							array(
								'label'         => 'Expand/Collapse Icons',
								'choices'       => array(
									''          => 'Disable',
									'has-icons' => 'Enable',
								),
								'default_value' => 'has-icons',
							)
						),
						DS_Field::button_group(
							'data_animation',
							array(
								'label'         => 'Animation Type',
								'choices'       => array(
									'css' => 'CSS',
									'js'  => 'JS',
								),
								'default_value' => 'js',
							)
						),
						DS_Field::button_group(
							'data_gallery_animation',
							array(
								'label'         => 'Gallery Animation Type',
								'choices'       => array(
									'css' => 'CSS',
									'js'  => 'JS',
								),
								'default_value' => 'js',
							)
						),
						DS_Field::button_group(
							'data_expanded',
							array(
								'label'             => 'Expanded Type',
								'choices'           => array(
									'single' => 'Expand current only',
									'all'    => 'Expand all',
								),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../accordion_gallery',
											'operator'  => '!=',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::true_false(
							'data_close',
							array(
								'label'             => 'Self Close?',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'data_expanded',
											'operator'  => '==',
											'value'     => 'single',
										),
										array(
											'fieldPath' => 'data_closed_at_start',
											'operator'  => '!=',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::true_false(
							'data_closed_at_start',
							array(
								'label'             => 'All Closed at Start?',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'data_expanded',
											'operator'  => '==',
											'value'     => 'single',
										),
									),
								),
							)
						),
						DS_Field::true_false(
							'data_scroll_to_view',
							array(
								'label'             => 'Scroll into view mobile?',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'transform_on_mobile',
											'operator'  => '!=',
											'value'     => 'accordion',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'data_display',
							array(
								'label'         => 'Display',
								'choices'       => array(
									'block' => 'Block',
									'flex'  => 'Flex',
								),
								'default_value' => 'block',
							)
						),
					),
				),
				$is_super_admin
			),
		);
	}
}
