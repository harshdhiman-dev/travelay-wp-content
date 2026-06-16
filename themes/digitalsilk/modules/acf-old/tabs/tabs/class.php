<?php
// phpcs:ignoreFile

class DS_Module_tabs extends DS_AbstractModule {

	public $name = 'tabs';

	public $title = 'Tabs';

	protected $description = 'Tabs with images and text';

	protected $category = 'ds-tabs';

	protected $icon = 'table-row-after';

	protected $keywords = array( 'tabs', 'image', 'navigation' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-tabs.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dsmp-tabs.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'content_tabs_accordion', array( 'label' => 'Content Tabs' ) ),
			DS_Field::repeater(
				'content_tabs',
				array(
					'label'            => 'Content Tabs',
					'layout'           => 'block',
					'button_label'     => 'Add Tab',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field::accordion( 'tab_nav_accordion', array( 'label' => 'Navigation' ) ),
						DS_Field::image(
							'icon',
							array(
								'label'            => 'Icon',
								'ds_default_value' => 1,
							)
						),
						DS_Field::text(
							'title_nav',
							array(
								'label'            => 'Title',
								'ds_default_value' => 1,
							)
						),
						DS_Field::accordion( 'tab_main_accordion', array( 'label' => 'Content' ) ),
						DS_Field_Block_V2_Content::get(),
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
			DS_Field_TitleStyles::get(),
			DS_Field::tab(
				'background_styles_tab',
				array(
					'label'     => 'Background Styles',
					'placement' => 'left',
				)
			),
			DS_Field_Background::get(),
			DS_Field_ModuleDecorations::get(),
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
			DS_Field::group(
				'tabs_component_settings',
				array(
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Tabs Main Settings',
								'open'  => 1,
							)
						),
						DS_Field::button_group(
							'transform_on_mobile',
							array(
								'label'         => 'Transform on Mobile to?',
								'choices'       => array(
									'none'      => 'tabs',
									'accordion' => 'accordion',
									'dropdown'  => 'dropdown',
								),
								'default_value' => 'none',
							)
						),
						DS_Field::true_false(
							'data_scroll_to_view',
							array(
								'label'             => 'Scroll into view mobile?',
								'ui'                => 1,
								'default_value'     => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'transform_on_mobile',
											'operator'  => '==',
											'value'     => 'accordion',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'data_display',
							array(
								'label'             => 'Display',
								'choices'           => array(
									'block' => 'Block',
									'flex'  => 'Flex',
								),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'transform_on_mobile',
											'operator'  => '==',
											'value'     => 'accordion',
										),
									),
								),
								'default_value'     => 'flex',
							)
						),
					),
				),
				true
			),
			DS_Field::group(
				'navigation_component_settings',
				array(
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Navigation Component Settings',
								'open'  => 1,
							)
						),
						DS_Field::button_group(
							'navigation_type',
							array(
								'label'         => 'Navigation Component Type',
								'choices'       => DS_Field::get_choices( array( '1', '6', '9' ), 'v' ),
								'default_value' => 'v1',
							)
						),
					),
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
						DS_Field_LayoutType::get(
							array(
								'1',
								'6',
								'9',
							),
							'wrapper_layout_type',
							array( 'label' => 'Wrapper Layout Type' )
						),
						DS_Field_LayoutType::get( array( '1', '6', '9' ) ),
						DS_Field_LayoutType::get(
							array(
								'1',
								'6',
								'9',
								'Timeline',
							),
							'nav_layout_type',
							array( 'label' => 'Navigation Layout Type' )
						),
						DS_Field::button_group(
							'timeline_type',
							array(
								'label'             => 'Timeline Type',
								'choices'           => array(
									'circles'    => 'circles',
									'text-above' => 'text-above',
								),
								'default_value'     => 'circles',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'nav_layout_type',
											'operator'  => '==',
											'value'     => 'vTimeline',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'timeline_circle_type',
							array(
								'label'             => 'Circle Type',
								'choices'           => array(
									'media-above'  => 'above',
									'media-inside' => 'inside',
									'no-media'     => 'none',
								),
								'default_value'     => 'above',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'timeline_type',
											'operator'  => '==',
											'value'     => 'circles',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'timeline_width',
							array(
								'label'             => 'Timeline Width',
								'choices'           => array(
									'full'    => 'full',
									'content' => 'content',
								),
								'default_value'     => 'full',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'nav_layout_type',
											'operator'  => '==',
											'value'     => 'vTimeline',
										),
									),
								),
							)
						),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
