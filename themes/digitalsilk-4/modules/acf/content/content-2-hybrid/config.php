<?php

return array(
	'fields'                   => array(
		DS_Field_Block_V2_5_Content::get(),

		DS_Field_ScrollDown::get(),

		DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
		DS_Field::tab(
			'background_styles_tab',
			array(
				'label'     => 'Background Styles',
				'placement' => 'left',
			)
		),
		DS_Field_Background::get(),
		DS_Field_ModuleEffects::get(),
		DS_Field::tab(
			'component_settings_tab',
			array(
				'label'     => 'Component Settings',
				'placement' => 'left',
			),
			true
		),
		DS_Field_Block_V2_ComponentSettings::get(),
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
					DS_Field_ModuleGap::get(),
				),
			),
			true
		),
	),
	'register_assets_callback' => function () {
	},
);
