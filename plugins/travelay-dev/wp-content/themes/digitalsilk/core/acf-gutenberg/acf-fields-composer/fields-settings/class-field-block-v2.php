<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_Block_V2_ComponentSettings extends DS_Field {
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
								'label' => 'Module Settings',
								'open'  => 1,
							)
						),
						DS_Field_ColumnsOrder::get(),
						DS_Field::range(
							'columns_ratio',
							array(
								'label'         => 'Content Ratio',
								'append'        => '%',
								'default_value' => 50,
							)
						),
						DS_Field::select(
							'columns_gap',
							array(
								'label'         => 'Column Gap',
								'choices'       => array(
									'0'  => '0',
									'10' => '10px',
									'20' => '20px',
									'30' => '30px',
									'40' => '40px',
									'50' => '50px',
								),
								'default_value' => '20',
							)
						),
						DS_Field::true_false(
							'is_vertical',
							array(
								'label' => 'Vertical Layout (No Columns)',
								'ui'    => 1,
							)
						),
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
						DS_Field::group(
							'gap_top',
							array(
								'label'      => 'Padding Top',
								'layout'     => 'block',
								'sub_fields' => array(
									DS_Field::range(
										'padding_desktop',
										array(
											'label'  => 'Desktop',
											'max'    => 50,
											'append' => '%',
										)
									),
									DS_Field::select(
										'padding_mobile',
										array(
											'label'   => 'Mobile',
											'choices' => array(
												'0'  => '0',
												'10' => '10px',
												'15' => '15px',
												'20' => '20px',
												'30' => '30px',
											),
											'default_value' => '20',
										)
									),
								),
							),
						),
						DS_Field::group(
							'gap_left',
							array(
								'label'      => 'Padding Left',
								'layout'     => 'block',
								'sub_fields' => array(
									DS_Field::range(
										'padding_desktop',
										array(
											'label'  => 'Desktop',
											'max'    => 50,
											'append' => '%',
										)
									),
									DS_Field::select(
										'padding_mobile',
										array(
											'label'   => 'Mobile',
											'choices' => array(
												'0'  => '0',
												'10' => '10px',
												'15' => '15px',
												'20' => '20px',
												'30' => '30px',
											),
											'default_value' => '20',
										)
									),
								),
							),
						),
						DS_Field::group(
							'gap_right',
							array(
								'label'      => 'Padding Right',
								'layout'     => 'block',
								'sub_fields' => array(
									DS_Field::range(
										'padding_desktop',
										array(
											'label'  => 'Desktop',
											'max'    => 50,
											'append' => '%',
										)
									),
									DS_Field::select(
										'padding_mobile',
										array(
											'label'   => 'Mobile',
											'choices' => array(
												'0'  => '0',
												'10' => '10px',
												'15' => '15px',
												'20' => '20px',
												'30' => '30px',
											),
											'default_value' => '20',
										)
									),
								),
							),
						),
						DS_Field::group(
							'gap_bottom',
							array(
								'label'      => 'Padding Bottom',
								'layout'     => 'block',
								'sub_fields' => array(
									DS_Field::range(
										'padding_desktop',
										array(
											'label'  => 'Desktop',
											'max'    => 50,
											'append' => '%',
										)
									),
									DS_Field::select(
										'padding_mobile',
										array(
											'label'   => 'Mobile',
											'choices' => array(
												'0'  => '0',
												'10' => '10px',
												'15' => '15px',
												'20' => '20px',
												'30' => '30px',
											),
											'default_value' => '20',
										)
									),
								),
							),
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
			DS_Field_MixedGallery_V1_ComponentSettings::get( $is_super_admin ),
		);
	}
}
