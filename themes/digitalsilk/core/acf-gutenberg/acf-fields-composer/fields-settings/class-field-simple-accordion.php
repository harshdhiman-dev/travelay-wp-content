<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_SimpleAccordion_ComponentSettings extends DS_Field {
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
								'label'   => 'Icons Type',
								'choices' => array(
									''       => 'None',
									'arrows' => 'Arrows',
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
