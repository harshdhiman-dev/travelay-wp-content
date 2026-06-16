<?php
// phpcs:ignoreFile

class DS_Module_shortcode extends DS_AbstractModule {

	public $name = 'shortcode';

	public $title = 'Shortcode';

	protected $description = 'Shortcode module';

	protected $category = 'ds-functional';

	protected $icon = 'shortcode';

	protected $keywords = array( 'shortcode', 'table', 'form', 'functional' );

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'shortcode_content', array( 'label' => 'Content' ) ),
			DS_Field::text( 'shortcode', array( 'label' => 'shortcode' ) ),
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
						DS_Field_ComponentGap::get( 'teams_gap', array( 'label' => 'Gap (px)' ) ),
						DS_Field_Columns::get( 5, 'columns', array( 'label' => 'Columns' ) ),
						DS_Field_LayoutType::get(),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
