<?php
// phpcs:ignoreFile

class DS_Module_counter extends DS_AbstractModule {

	public $name = 'counter';

	public $title = 'Counter';

	protected $description = 'Counter Widget';

	protected $category = 'ds-counters';

	protected $icon = 'clock';

	protected $keywords = array( 'counter' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-counter.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-counters.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'counter_content_ac', array( 'label' => 'Counter Content' ) ),
			DS_Field::repeater(
				'counter_widget',
				array(
					'label'            => 'Counter',
					'layout'           => 'block',
					'button_label'     => 'Add Item',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field_Counter_V1_Content::get(),
					),
				)
			),
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
				'component_settings_tab',
				array(
					'label'     => 'Component Settings',
					'placement' => 'left',
				),
				true
			),
			DS_Field_Counter_V1_ComponentSettings::get(),
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
						DS_Field_LayoutType::get( 3 ),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
