<?php
// phpcs:ignoreFile
class DS_Module_teams_grid extends DS_AbstractModule {

	protected $feature = 'team_feature';

	public $name = 'teams-grid';

	public $title = 'Teams Grid';

	protected $description = 'Listing of teams with grid feature';

	protected $category = 'ds-team';

	protected $icon = 'groups';

	protected $keywords = array( 'teams', 'grid' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-gridder.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-teams.scss' );

		wp_enqueue_script( 'gridder', get_template_directory_uri() . '/assets/vendors/gridder/gridder-js-min.js', true, true );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'teams_content_ac', array( 'label' => 'Teams Content' ) ),
			DS_Field::relationship(
				'teams_content',
				array(
					'label'     => 'Teams Content',
					'post_type' => 'team',
					'filters'   => array( 'search' ),
					'elements'  => array( 'featured_image' ),
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
				'gridder_settings_tab',
				array(
					'label'     => 'Gridder Settings',
					'placement' => 'left',
				)
			),
			DS_Field::group(
				'gridder_settings',
				array(
					'label'      => 'Gridder Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::number(
							'scroll_offset',
							array(
								'label'         => 'Scroll Offset',
								'default_value' => 30,
							)
						),
						DS_Field::select(
							'animation_effect',
							array(
								'label'   => 'Animation Effect',
								'choices' => array(
									'linear'        => 'linear',
									'swing'         => 'swing',
									'easeInOutExpo' => 'easeInOutExpo',
								),
							)
						),
						DS_Field::number(
							'animation_speed',
							array(
								'label'         => 'Animation Speed',
								'default_value' => 400,
							)
						),
					),
				)
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
						DS_Field_ComponentGap::get( 'teams_gap', array( 'label' => 'Gap (px)' ) ),
						DS_Field_Columns::get( 6, 'columns', array( 'label' => 'Columns' ) ),
						DS_Field_LayoutType::get(),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
