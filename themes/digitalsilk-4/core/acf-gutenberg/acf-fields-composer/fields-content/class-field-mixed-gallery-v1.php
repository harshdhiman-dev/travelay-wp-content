<?php

/**
 * Custom DS_Field
 *
 * @package DS_Theme
 */
class DS_Field_MixedGallery_V1_Content extends DS_Field {

	/**
	 * Get
	 *
	 * @param array $conditional_logic contains conditional logic.
	 */
	public static function get( $conditional_logic = array() ): string {
		return DS_Field::group(
			'mixed_gallery',
			array(
				'label'             => '',
				'conditional_logic' => $conditional_logic,
				'sub_fields'        => array(
					DS_Field::button_group(
						'main_content_type',
						array(
							'label'         => 'Main Content Type',
							'choices'       => array(
								'image' => 'image',
								'video' => 'video',
							),
							'default_value' => 'image',
						)
					),
					DS_Field::image(
						'main_image',
						array(
							'label'             => 'Main Image',
							'ds_default_value'  => 1,
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'main_content_type',
										'operator'  => '==',
										'value'     => 'image',
									),
								),
							),
						)
					),
					DS_Field::select(
						'main_image_size',
						array(
							'label'         => 'Main Image size',
							'choices'       => array(
								'full'         => 'full',
								'large'        => 'large - 1024px',
								'medium_large' => 'medium_large - 768px',
								'ds_medium'    => 'medium - 400px',
								'ds_small'     => 'small (logo, icon)',
							),
							'default_value' => 'medium_large',
						)
					),
					DS_Field::group(
						'main_video',
						array(
							'label'             => 'Main Video',
							'sub_fields'        => array(
								DS_Field_Video::get(),
							),
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'main_content_type',
										'operator'  => '==',
										'value'     => 'video',
									),
								),
							),
						)
					),
					DS_Field::image( 'secondary_image', array( 'label' => 'Secondary Image' ) ),
					DS_Field::select(
						'secondary_image_size',
						array(
							'label'         => 'Secondary Image size',
							'choices'       => array(
								'full'         => 'full',
								'large'        => 'large - 1024px',
								'medium_large' => 'medium_large - 768px',
								'ds_medium'    => 'medium - 400px',
								'ds_small'     => 'small (logo, icon)',
							),
							'default_value' => 'medium_large',
						)
					),
					DS_Field::true_false(
                        'disable_lazy',
                        [
							'label' => 'Disable Lazy Loading?',
							'ui'    => 1,
                        ]
                    ),
				),
			)
		);
	}
}
