<?php
class DS_Module_Content_Simple extends DS_AbstractModule {

	public $name = 'content-simple';

	public $title = 'Titles/Links';

	protected $description = 'Headings, description and links';

	protected $category = 'ds-content';

	protected $icon = 'heading';

	protected $keywords = array( 'headline', 'title', 'links', 'description' );

	protected bool $new = true;

	/**
	 * Adds ACF composer fields to the module.
	 *
	 * @return array An array of composer fields.
	 */
	public function add_composer_fields(): array {
		return array(
			DS_Field_Section_Header::get(),
			DS_Field::accordion( 'advanced_settings_ac', array( 'label' => 'Advanced Settings' ) ),
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
