<?php
// phpcs:ignoreFile

class DS_Module_content_2 extends DS_AbstractModule {

	public $name = 'content-2';

	public $title = 'Content 2';

	protected $description = 'Content block with image';

	protected $category = 'ds-content';

	protected $icon = 'align-pull-left';

	protected $keywords = array( 'content', 'image' );

	public function add_composer_fields(): array {
		return array(
			DS_Field_Block_V2_Content::get(),

			DS_Field_ScrollDown::get(),

			DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
			DS_Field::tab(
				'title_tab',
				array(
					'label'     => 'Content Styles',
					'placement' => 'left',
				)
			),
			DS_Field_TitleStyles::get(),
			DS_Field::group(
				'content_styles',
				array(
					'label'      => 'Content Styles',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::color_picker( 'pretitle_color', array( 'label' => 'Pretitle Color' ) ),
						DS_Field::color_picker( 'subtitle_color', array( 'label' => 'Subtitle Color' ) ),
					),
				),
				true
			),
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
		);
	}
}
