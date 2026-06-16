<?php
// phpcs:ignoreFile
class DS_Module_teams_popup extends DS_AbstractModule {

	protected $feature = 'team_feature';

	public $name = 'teams-popup';

	public $title = 'Teams with popup';

	protected $description = 'Listing of teams with popup feature';

	protected $category = 'ds-team';

	protected $icon = 'groups';

	protected $keywords = [ 'teams', 'popup' ];

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-teams.scss' );
	}

	public function add_composer_fields(): array {
		return [
			DS_Field::accordion( 'teams_content_ac', [ 'label' => 'Teams Content' ] ),
			DS_Field::relationship(
                'teams_content',
                [
					'label'     => 'Teams Content',
					'post_type' => 'team',
					'filters'   => [ 'search' ],
					'elements'  => [ 'featured_image' ],
                ]
            ),
			DS_Field::true_false(
				'show_bio_popup',
				[
					'label' => 'Show Bio in a Popup',
					'ui' => true,
					'ui_on_text' => 'Yes',
					'ui_off_text' => 'No',
				]
			),
			DS_Field::button_group(
				'popup_trigger_type',
				[
					'label' => 'Popup Trigger Type',
					'choices' => [
						'button' => 'Button',
						'full_item' => 'Make Full Item Clickable',
					],
					'default_value' => 'button',
					'layout' => 'horizontal',
					'conditional_logic' => [
						[
							[
								'fieldPath' => 'show_bio_popup',
								'operator'  => '==',
								'value'     => 1,
							],
						],
					],
				]
			),
			DS_Field::group(
                'cta_button',
                [
					'label'             => 'Bio Button',
					'layout'            => 'block',
					'sub_fields'        => [
						DS_Field::text(
                            'title',
							[
								'label'         => 'Button Label',
								'default_value' => 'Read Full Bio',
                            ]
                        ),
						DS_Field_CtaBasicFeatures::get(),
					],
					'conditional_logic' => [
						[
							[
								'fieldPath' => 'show_bio_popup',
								'operator'  => '==',
								'value'     => 1,
							],
							[
								'fieldPath' => 'popup_trigger_type',
								'operator'  => '==',
								'value'     => 'button',
							],
						],
					],
                ]
            ),
			DS_Field::accordion( 'advanced_settings', [ 'label' => 'Advanced Settings' ] ),
			DS_Field::tab(
                'title_tab',
                [
					'label'     => 'Title Styles',
					'placement' => 'left',
                ]
            ),
			DS_Field_ComponentTitle::get( [ 'label' => 'Team Member Title Styles' ] ),
			DS_Field::tab(
                'background_styles_tab',
                [
					'label'     => 'Background Styles',
					'placement' => 'left',
                ]
            ),
			DS_Field_Background::get(),
			DS_Field_ModuleDecorations::get(),
			DS_Field::tab(
                'effects_tab',
                [
					'label'     => 'Effects',
					'placement' => 'left',
                ]
            ),
			DS_Field_ModuleEffects::get(),
			DS_Field::tab(
                'component_settings_tab',
                [
					'label'     => 'Component Settings',
					'placement' => 'left',
                ],
                true
            ),
			DS_Field_TeamCard_V1_ComponentSettings::get(),
			DS_Field::tab(
                'layout_settings_tab',
                [
					'label'     => 'Layout Settings',
					'placement' => 'left',
                ],
                true
            ),
			DS_Field::group(
                'layout_settings',
                [
					'label'      => 'Layout Settings',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field_Container::get(),
						DS_Field_ComponentGap::get( 'teams_gap', [ 'label' => 'Gap (px)' ] ),
						DS_Field_Columns::get( 6, 'columns', [ 'label' => 'Columns' ] ),
						DS_Field_LayoutType::get(),
						DS_Field_ModuleGap::get(),
					],
                ],
                true
            ),
		];
	}
}
