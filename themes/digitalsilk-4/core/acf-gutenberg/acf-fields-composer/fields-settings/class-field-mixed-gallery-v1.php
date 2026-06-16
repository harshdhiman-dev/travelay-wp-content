<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_MixedGallery_V1_ComponentSettings extends DS_Field {
	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = true ): array {
		return array(
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
						DS_Field::button_group(
							'media_ratio',
							array(
								'label'         => 'Media Ratio',
								'choices'       => array(
									'1x1'  => '1x1',
									'4x3'  => '4x3',
									'3x4'  => '3x4',
									'16x9' => '16x9',
									'none' => 'none',
								),
								'default_value' => '16x9',
							)
						),
						DS_Field::button_group(
							'media_fit',
							array(
								'label'         => 'Media fit',
								'choices'       => array(
									'contain' => 'contain',
									'cover'	  => 'cover',
								),
								'default_value' => 'cover',
							)
						),
						DS_Field::button_group(
							'focal_point',
							array(
								'label'             => 'Focal Point',
								'choices'           => array(
									'top-left'      => '↖️',
									'top-center'    => '⬆️',
									'top-right'     => '↗️',
									'center-left'   => '⬅️',
									'center-center' => '⏺️',
									'center-right'  => '➡️',
									'bottom-left'   => '↙️',
									'bottom-center' => '⬇️',
									'bottom-right'  => '↘️',
								),
								'default_value'     => 'center-center',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'media_fit',
											'operator'  => '==',
											'value'     => 'cover',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'main_media_position',
							array(
								'label'         => 'Media X-Align',
								'choices'       => array(
									'left'   => 'left',
									'center' => 'center',
									'right'  => 'right',
								),
								'default_value' => 'center',
							)
						),
						DS_Field::button_group(
							'main_media_vertical_position',
							array(
								'label'         => 'Media Y-Align',
								'choices'       => array(
									'top'    => 'top',
									'center' => 'center',
									'bottom' => 'bottom',
								),
								'default_value' => 'center',
							)
						),
						DS_Field::group(
							'mobile',
							array(
								'label'      => 'Mobile',
								'layout'     => 'block',
								'sub_fields' => array(
									DS_Field::button_group(
										'media_ratio_mobile',
										array(
											'label'   => 'Media Ratio',
											'choices' => array(
												'1x1'  => '1x1',
												'4x3'  => '4x3',
												'3x4'  => '3x4',
												'16x9' => '16x9',
												'none' => 'none',
											),
											'default_value' => '16x9',
										)
									),
									DS_Field::button_group(
										'media_fit_mobile',
										array(
											'label'   => 'Media fit',
											'choices' => array(
												'contain' => 'contain',
												'cover'	  => 'cover',
											),
											'default_value' => 'cover',
										)
									),
								),
							),
						),
					),
				),
				$is_super_admin
			),
		);
	}
}
