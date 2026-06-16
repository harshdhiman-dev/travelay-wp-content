<?php

/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_MediaBackground extends DS_Field {

	/**
	 * Get
	 */
	public static function get(): array {
		return array(
			self::add_field(
				'button_group',
				'media_background_type',
				array(
					'label'   => 'Background Type',
					'choices' => array(
						'image' => 'image',
						'video' => 'video',
					),
				)
			),
			self::add_field(
				'image',
				'media_image',
				array(
					'label'             => 'Image',
					'preview_size'      => 'medium',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'media_background_type',
								'operator'  => '==',
								'value'     => 'image',
							),
						),
					),
				)
			),
			self::add_field(
				'image',
				'media_mobile_image',
				array(
					'label'             => 'Mobile Image',
					'preview_size'      => 'medium',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'media_background_type',
								'operator'  => '==',
								'value'     => 'image',
							),
						),
					),
				)
			),
			self::add_field(
				'select',
				'media_image_fit',
				array(
					'label'             => 'Media Fit',
					'choices'           => array(
						''              => 'none',
						'media-cover'   => 'Media Cover',
						'media-contain' => 'Media Contain',
					),
					'default_value'     => 'media-cover',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'media_background_type',
								'operator'  => '==',
								'value'     => 'image',
							),
						),
					),
				)
			),
			self::add_field(
				'select',
				'media_image_position',
				array(
					'label'             => 'Media Position',
					'choices'           => array(
						'focal-top-center'    => 'Top Center',
						'focal-top-left'      => 'Top Left',
						'focal-top-right'     => 'Top Right',
						'focal-center-left'   => 'Center Left',
						'focal-center-center' => 'Center ',
						'focal-center-right'  => 'Center Right',
						'focal-bottom-left'   => 'Bottom Left',
						'focal-bottom-center' => 'Bottom Center',
						'focal-bottom-right'  => 'Bottom Right',
					),
					'default_value'     => 'focal-top-center',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'media_background_type',
								'operator'  => '==',
								'value'     => 'image',
							),
						),
					),
				)
			),
			self::add_field(
				'true_false',
				'disable_lazy',
				array(
					'label'             => 'Disable Lazy Load?',
					'ui'                => 1,
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'media_background_type',
								'operator'  => '==',
								'value'     => 'image',
							),
						),
					),
				)
			),
			self::add_field(
				'group',
				'media_video',
				array(
					'label'             => 'Video',
					'sub_fields'        => array(
						...DS_Field_Video::get(),
					),
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'media_background_type',
								'operator'  => '==',
								'value'     => 'video',
							),
						),
					),
				)
			),
		);
	}
}
