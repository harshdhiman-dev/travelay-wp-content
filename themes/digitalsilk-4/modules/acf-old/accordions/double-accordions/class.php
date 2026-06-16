<?php
// phpcs:ignoreFile

class DS_Module_double_accordions extends DS_AbstractModule {

	public $name = 'double-accordions';

	public $title = 'Accordions Left/Right with Centered Image';

	protected $description = 'Two accordions left/right with image in center';

	protected $category = 'ds-accordions';

	protected $icon = 'list-view';

	protected $keywords = array( 'accordion', 'multiple accordions', 'image' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-accordions.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-accordions.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'accordion_content_ac', array( 'label' => 'Accordion Content' ) ),
			DS_Field::image( 'main_image', array( 'label' => 'Main Image' ) ),
			DS_Field::text( 'left_accordion_title', array( 'label' => 'Left Title' ) ),
			DS_Field::repeater(
				'left_accordion_content',
				array(
					'label'            => 'Left Accordion',
					'button_label'     => 'Add Left Accordion',
					'layout'           => 'block',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field::accordion( 'm_content_ac', array( 'label' => 'Media' ) ),
						DS_Field::image( 'image', array( 'label' => 'Main Image' ) ),
						DS_Field::accordion( 'c_content_ac', array( 'label' => 'Content' ) ),
						DS_Field::image( 'icon', array( 'label' => 'Title Icon' ) ),
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
					),
				)
			),
			DS_Field::text( 'right_accordion_title', array( 'label' => 'Right Title' ) ),
			DS_Field::repeater(
				'right_accordion_content',
				array(
					'label'            => 'Right Accordion',
					'button_label'     => 'Add Right Accordion',
					'layout'           => 'block',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field::accordion( 'm_content_ac', array( 'label' => 'Media' ) ),
						DS_Field::image( 'image', array( 'label' => 'Main Image' ) ),
						DS_Field::accordion( 'c_content_ac', array( 'label' => 'Content' ) ),
						DS_Field::image( 'icon', array( 'label' => 'Title Icon' ) ),
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
					),
				)
			),
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
			DS_Field::group(
				'component_settings',
				array(
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Main Component Settings',
								'open'  => 1,
							)
						),
						DS_Field_ComponentType::get(
							2,
							'type',
							array(
								'label' => 'Accordion Component Type',
							)
						),
					),
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
