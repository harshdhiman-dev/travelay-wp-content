<?php

return array(
	'fields'                   => array(
		DS_Field::accordion( 'cards_content_ac', array( 'label' => 'Cards Content' ) ),
		DS_Field::button_group(
			'content_type',
			array(
				'label'         => 'Content type',
				'choices'       => array(
					'static' => 'static',
					'posts'  => 'select posts',
					'query'  => 'query posts',
				),
				'default_value' => 'static',
			)
		),
		DS_Field::text(
			'read_more_text',
			array(
				'label'             => 'Read more text',
				'default_value'     => 'Read Article',
				'conditional_logic' => array(
					array(
						array(
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'posts',
						),
					),
					array(
						array(
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'query',
						),
					),
				),
			)
		),
		DS_Field_Block_V1_Content::get(
			array(
				array(
					array(
						'fieldPath' => 'content_type',
						'operator'  => '==',
						'value'     => 'static',
					),
				),
			)
		),
		DS_Field::group(
			'post_type_data',
			array(
				'label'             => 'Post Type Data',
				'sub_fields'        => array(
					DS_Field::select(
						'post_type',
						array(
							'label'   => 'Choose post type',
							'choices' => array( 'post' ),
						)
					),
					DS_Field::range(
						'posts_per_page',
						array(
							'label'         => 'Per page',
							'min'           => 1,
							'max'           => 12,
							'default_value' => 3,
						)
					),
				),
				'conditional_logic' => array(
					array(
						array(
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'query',
						),
					),
				),
			)
		),
		DS_Field::post_object(
			'posts',
			array(
				'label'             => 'Posts',
				'post_type'         => array( 'post' ),
				'multiple'          => 1,
				'return_format'     => 'ID',
				'conditional_logic' => array(
					array(
						array(
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'posts',
						),
					),
				),
			)
		),
		DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
		DS_Field::tab(
			'title_tab',
			array(
				'label'     => 'Title Styles',
				'placement' => 'left',
			)
		),
		DS_Field_ComponentTitle::get(),
		DS_Field::tab(
			'background_styles_tab',
			array(
				'label'     => 'Background Styles',
				'placement' => 'left',
			)
		),
		DS_Field_Background::get(),
		DS_Field::tab(
			'effects_tab',
			array(
				'label'     => 'Effects',
				'placement' => 'left',
			)
		),
		DS_Field_ModuleEffects::get(),
		DS_Field::tab(
			'component_settings_tab',
			array(
				'label'     => 'Component Settings',
				'placement' => 'left',
			),
			true
		),
		DS_Field_Block_V1_ComponentSettings::get(),
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
					DS_Field_Container::get(),
					DS_Field_ComponentGap::get( 'card_gap_vertical', array( 'label' => 'Grid Y-Gap' ) ),
					DS_Field_ComponentGap::get( 'card_gap_horizontal', array( 'label' => 'Grid X-Gap)' ) ),
					DS_Field_Columns::get(),
					DS_Field_ModuleGap::get(),
				),
			),
			true
		),
	),
	'register_assets_callback' => function () {
	},
);
