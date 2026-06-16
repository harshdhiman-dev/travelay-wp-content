<?php
// phpcs:ignoreFile

class DS_Module_tabs_posts extends DS_AbstractModule {

	public $name = 'tabs-posts';

	public $title = 'Posts';

	protected $description = 'Posts Grid with tabbed navigation';

	protected $category = 'ds-tabs';

	protected $icon = 'editor-help';

	protected $keywords = array( 'posts', 'grid', 'tabs', 'categories' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-tabs.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dsmp-tabs.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'posts_content_ac', array( 'label' => 'Posts Content' ) ),
			DS_Field::taxonomy(
				'post_categories',
				array(
					'label'         => 'Show Posts from Categories:',
					'taxonomy'      => 'category',
					'field_type'    => 'multi_select',
					'return_format' => 'object',
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
				'component_settings',
				array(
					'label'      => 'Component Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::button_group(
							'navigation_type',
							array(
								'label'         => 'Navigation Component Type',
								'choices'       => DS_Field::get_choices( 2, 'v' ),
								'default_value' => 'v1',
							)
						),
						DS_Field_ComponentType::get(),
					),
				),
				true
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
						DS_Field_Container::get(),
						DS_Field_ComponentGap::get(),
						DS_Field_Columns::get(),
						DS_Field_LayoutType::get( 2, 'wrapper_layout_type', array( 'label' => 'Wrapper Layout Type' ) ),
						DS_Field_LayoutType::get(),
						DS_Field_LayoutType::get( 2, 'nav_layout_type', array( 'label' => 'Navigation Layout Type' ) ),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
