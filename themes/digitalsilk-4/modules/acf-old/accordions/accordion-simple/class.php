<?php
// phpcs:ignoreFile

class DS_Module_accordion_simple extends DS_AbstractModule {

	public $name = 'accordion-simple';

	public $title = 'Collapsible Text';

	protected $description = 'Read more collapsible content';

	protected $category = 'ds-accordions';

	protected $icon = 'list-view';

	protected $keywords = array( 'accordion', 'simple', 'text' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-accordions.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-accordions.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'accordion_content_ac', array( 'label' => 'Accordion Content' ) ),
			DS_Field::text(
				'title',
				array(
					'label'            => 'Title',
					'ds_default_value' => 1,
				)
			),
			DS_Field::wysiwyg(
				'description',
				array(
					'label'            => 'Description',
					'ds_default_value' => 1,
				)
			),
			DS_Field_CTAList::get(),
			DS_Field::accordion( 'advanced_settings_ac', array( 'label' => 'Advanced Settings' ) ),
			DS_Field::tab(
				'title_tab',
				array(
					'label'     => 'Title Styles',
					'placement' => 'left',
				)
			),
			DS_Field_ComponentTitle::get( array( 'label' => 'Accordion Item Title Styles' ) ),
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
			DS_Field_SimpleAccordion_ComponentSettings::get(),
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
						DS_Field_LayoutType::get(),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
