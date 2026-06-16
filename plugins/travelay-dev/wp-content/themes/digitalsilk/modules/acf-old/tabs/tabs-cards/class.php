<?php
// phpcs:ignoreFile

class DS_Module_tabs_cards extends DS_AbstractModule {

	public $name = 'tabs-cards';

	public $title = 'Tabbed Cards';

	protected $description = 'Tabbed cards with image and title';

	protected $category = 'ds-tabs';

	protected $icon = 'grid-view';

	protected $keywords = array( 'tabs', 'cards' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-tabs.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dsmp-tabs.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'cards_content_accordion', array( 'label' => 'Content' ) ),
			DS_Field::repeater(
				'tabs',
				array(
					'label'        => 'Tabs Content',
					'layout'       => 'block',
					'button_label' => 'Add Tab',
					'sub_fields'   => array(
						DS_Field::text( 'title_nav', array( 'label' => 'Tab title' ) ),
						DS_Field::text(
							'title',
							array(
								'label'            => 'Content Title',
								'ds_default_value' => 1,
							)
						),
						DS_Field::text( 'subtitle', array( 'label' => 'Subtitle' ) ),
						DS_Field_CTAList::get(),
						DS_Field_Block_V1_Content::get(),
					),
				)
			),
			DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
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
				'settings_tab',
				array(
					'label'     => 'Tabs Settings',
					'placement' => 'left',
				),
				true
			),
			DS_Field::group(
				'tabs_component_settings',
				array(
					'label'      => 'Tabs settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::button_group(
							'transform_on_mobile',
							array(
								'label'         => 'Transform on Mobile to?',
								'choices'       => array(
									'none'     => 'tabs',
									'dropdown' => 'dropdown',
								),
								'default_value' => 'none',
							)
						),
					),
				),
				true
			),
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
						DS_Field_ComponentGap::get( 'card_gap_vertical', array( 'label' => 'Y-Gap (px)' ) ),
						DS_Field_ComponentGap::get( 'card_gap_horizontal', array( 'label' => 'X-Gap (px)' ) ),
						DS_Field_Columns::get(),
						DS_Field_LayoutType::get(),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
