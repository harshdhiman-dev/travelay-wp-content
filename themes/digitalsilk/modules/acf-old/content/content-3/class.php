<?php
// phpcs:ignoreFile

class DS_Module_content_3 extends DS_AbstractModule {

	public $name = 'content-3';

	public $title = 'Content 3';

	protected $description = 'Content block with image';

	protected $category = 'ds-content';

	protected $icon = 'editor-ul';

	protected $keywords = array( 'content', 'image' );

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'content_ac', array( 'label' => 'Main Content' ) ),
			DS_Field::text( 'pretitle', array( 'label' => 'Pretitle' ) ),
			DS_Field::text(
				'title',
				array(
					'label'            => 'Title',
					'ds_default_value' => 1,
				)
			),
			DS_Field::text( 'subtitle', array( 'label' => 'Subtitle' ) ),
			DS_Field::wysiwyg( 'description', array( 'label' => 'Description' ) ),
			DS_Field_CTAList::get(),
			DS_Field::accordion( 'cards_content_accordion', array( 'label' => 'Cards Content' ) ),
			DS_Field::repeater(
				'cards_widget',
				array(
					'label'            => 'Cards Content',
					'button_label'     => 'Add Card',
					'layout'           => 'block',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field::image( 'image', array( 'label' => 'Image' ) ),
						DS_Field::text( 'pretitle', array( 'label' => 'Pretitle' ) ),
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
						DS_Field_ComponentType::get( 2, 'v1' ),
						DS_Field_ComponentTitle::get(),
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
						DS_Field_LayoutType::get( 2 ),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
