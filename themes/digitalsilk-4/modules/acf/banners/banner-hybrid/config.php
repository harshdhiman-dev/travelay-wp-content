<?php

return array(
	'fields'                   => array(
		DS_Field::accordion( 'media_accordion', array( 'label' => 'Additional Content' ) ),
		DS_Field::button_group(
			'content_type',
			array(
				'label'         => 'Content Type',
				'choices'       => array(
					'none'  => 'None',
					'text'  => 'Text/Links',
					'image' => 'Image',
					'video' => 'Video',
				),
				'default_value' => 'none',
			)
		),
		DS_Field::group(
			'content_text',
			array(
				'label'             => 'Text/Links',
				'layout'            => 'block',
				'sub_fields'        => array(
					DS_Field::text( 'pretitle', array( 'label' => 'Pretitle' ) ),
					DS_Field::text(
						'title',
						array(
							'label'            => 'Title',
							'ds_default_value' => 1,
						)
					),
					DS_Field::text( 'subtitle', array( 'label' => 'Subtitle' ) ),
					DS_Field::wysiwyg(
						'description',
						array(
							'label'            => 'Description',
							'ds_default_value' => 1,
						)
					),
					DS_Field_CTAList::get(),
				),
				'conditional_logic' => array(
					array(
						array(
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'text',
						),
					),
				),
			)
		),
		DS_Field::image(
			'content_image',
			array(
				'label'             => 'Image',
				'conditional_logic' => array(
					array(
						array(
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'image',
						),
					),
				),
			)
		),
		DS_Field::select(
			'image_size',
			array(
				'label'             => 'Image size',
				'choices'           => array(
					'full'         => 'full',
					'large'        => 'large - 1024px',
					'medium_large' => 'medium_large - 768px',
					'ds_medium'    => 'medium - 400px',
					'ds_small'     => 'small (logo, icon)',
				),
				'default_value'     => 'ds_medium',
				'conditional_logic' => array(
					array(
						array(
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'image',
						),
					),
				),
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
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'video',
						),
					),
				),
			)
		),
		DS_Field_ScrollDown::get(),

		DS_Field::accordion( 'background_accordion', array( 'label' => 'Background' ) ),
		DS_Field::group(
			'media_background',
			array(
				'label'      => 'Background',
				'layout'     => 'block',
				'sub_fields' => array(
					DS_Field_MediaBackground::get(),
				),
			)
		),
		DS_Field::range(
			'overlay_opacity',
			array(
				'label'  => 'Overlay Opacity',
				'append' => '%',
			)
		),
		DS_Field::color_picker( 'overlay_opacity_color', array( 'label' => 'Overlay Color' ) ),

		DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
		DS_Field::tab(
			'background_styles_tab',
			array(
				'label'     => 'Background Styles',
				'placement' => 'left',
			)
		),
		DS_Field_BackgroundSimple::get(),
		DS_Field_ModuleEffects::get(),
		DS_Field::tab(
			'content_settings_tab',
			array(
				'label'     => 'Content Settings',
				'placement' => 'left',
			)
		),
		DS_Field_ContentPosition::get(),
		DS_Field_ContentStyle::get(),
		DS_Field::true_false(
			'content_has_border',
			array(
				'label' => 'Content has border?',
				'ui'    => 1,
			)
		),
		DS_Field::tab(
			'layout_settings_tab',
			array(
				'label'     => 'Layout Settings',
				'placement' => 'left',
			),
			true
		),
		DS_Field::group(
			'layout_settings',
			array(
				'label'      => 'Layout Settings',
				'layout'     => 'block',
				'sub_fields' => array(
					DS_Field_Container::get( 'container-fluid' ),
					DS_Field_ScreenHeight::get(),
					DS_Field_ModuleGap::get(),
					DS_Field_LayoutGap::get(),
					DS_Field::true_false(
						'header_height',
						array(
							'label'         => 'Consider Header Height',
							'default_value' => 0,
							'ui'            => 1,
						)
					),
					DS_Field::range(
						'columns_ratio',
						array(
							'label'         => 'Columns Ratio',
							'append'        => '%',
							'default_value' => 100,
						)
					),
					DS_Field_ColumnsOrder::get(),
					DS_Field::true_false(
						'vertical_columns',
						array(
							'label'         => 'Vertical Columns',
							'default_value' => 0,
							'ui'            => 1,
						)
					),
				),
			),
			true
		),
	),
	'register_assets_callback' => function () {
	},
);
