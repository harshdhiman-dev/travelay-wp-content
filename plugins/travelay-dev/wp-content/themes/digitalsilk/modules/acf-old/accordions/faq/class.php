<?php
// phpcs:ignoreFile

class DS_Module_faq extends DS_AbstractModule {

	protected $feature = 'faq_feature';

	public $name = 'faq';

	public $title = 'FAQ';

	protected $description = 'FAQ Accordion';

	protected $category = 'ds-accordions';

	protected $icon = 'editor-help';

	protected $keywords = array( 'faq', 'accordion' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-accordions.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-accordions.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'faq_accordion', array( 'label' => 'FAQ Content' ) ),
			DS_Field::post_object(
				'faq_list',
				array(
					'label'     => 'FAQ list',
					'post_type' => 'faq',
					'multiple'  => 1,
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
			DS_Field_ComponentTitle::get( array( 'label' => 'Accordion Item Title Styles' ) ),
			DS_Field::tab(
				'background_tab',
				array(
					'label'     => 'Background',
					'placement' => 'left',
				)
			),
			DS_Field_Background::get(),
			DS_Field_ModuleDecorations::get(),
			DS_Field::tab(
				'component_settings_tab',
				array(
					'label'     => 'Component Settings',
					'placement' => 'left',
				),
				true
			),
			DS_Field_Accordion_ComponentSettings::get(),
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
