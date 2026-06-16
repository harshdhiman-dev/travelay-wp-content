<?php
// phpcs:ignoreFile

class DS_Module_image_spinner extends DS_AbstractModule {

	public $name = 'image-spinner';

	public $title = '3D Image Spinner';

	protected $description = 'Image rotation with controls';

	protected $category = 'ds-sliders';

	protected $icon = 'image-rotate';

	protected $keywords = [ '3d', 'rotation', 'spinner', 'image' ];

	protected string $template = 'spinner';

	public function enqueue_assets(): void {
        wp_enqueue_script( 'image-spritespin-js', get_template_directory_uri() . '/assets/vendors/spritespin/spritespin.js', array( 'jquery' ), '1.0', false );
		DS_ViteAssets::enqueue_script( 'js/dst-3dSpinner.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-3d-media.scss' );
	}

	public function add_composer_fields(): array {
		return [
			DS_Field::accordion( 'content_ac', [ 'label' => 'Main Content' ] ),

			DS_Field::text( 'pretitle', [ 'label' => 'Pretitle' ] ),
			DS_Field::text(
                'title',
                [
					'label'            => 'Title',
					'ds_default_value' => 1,
                ]
            ),
			DS_Field::text( 'subtitle', [ 'label' => 'Subtitle' ] ),
			DS_Field::wysiwyg(
                'description',
                [
					'label'            => 'Description',
					'ds_default_value' => 1,
                ]
            ),

			DS_Field_CTAList::get(),

			DS_Field::accordion(
                'hotspots_acc',
                [
					'label'             => 'Hotspots',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'module_image_spinner_settings_data_has-hotspots',
								'operator'  => '==',
								'value'     => '1',
							),
						),
					),
                ]
            ),

			DS_Field::repeater(
                'module_image_spinner_hotspots',
                [
					'label'             => '',
					'layout'            => 'block',
					'button_label'      => 'Add Hotspot',
					'sub_fields'        => [
						DS_Field::accordion(
                            'hotspot_acc',
							[
								'label'        => 'Hotspot',
								'open'         => 1,
								'multi_expand' => 1,
                            ]
                        ),
						DS_Field::number(
                            'hotspot_frame',
							[
								'label'         => 'Frame',
								'default_value' => 1,
								'min'           => 1,
                            ]
                        ),

						DS_Field::group(
                            'hotspot_position',
							[
								'label'      => 'Position',
								'layout'     => 'table',
								'sub_fields' => [
									DS_Field::range(
                                        'top',
										[
											'label'  => 'Vertical',
											'append' => '%',
											'default_value' => 50,
                                        ]
                                    ),
									DS_Field::range(
                                        'left',
										[
											'label'  => 'Horizontal',
											'append' => '%',
											'default_value' => 50,
                                        ]
                                    ),
								],
                            ]
                        ),

						DS_Field::group(
                            'hotspot_tooltip',
							[
								'label'             => 'Tooltip',
								'layout'            => 'block',
								'sub_fields'        => [
									DS_Field::text( 'title', [ 'label' => 'Title' ] ),
									DS_Field::wysiwyg( 'description', [ 'label' => 'Description' ] ),
								],
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../hotspots_content_type_tooltip',
											'operator'  => '==',
											'value'     => '1',
										),
									),
								),
                            ]
                        ),

						DS_Field::text(
                            'hotspot_label',
							[
								'label'             => 'Label',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../hotspots_content_type_label',
											'operator'  => '==',
											'value'     => '1',
										),
									),
								),
                            ]
                        ),
					],
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'module_image_spinner_settings_data_has-hotspots',
								'operator'  => '==',
								'value'     => '1',
							),
						),
					),
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
			DS_Field_TitleStyles::get(),

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
                'image_spinner_settings_tab',
                [
					'label'     => 'Image Spinner Settings',
					'placement' => 'left',
                ]
            ),
			DS_Field::group(
                'module_image_spinner_settings',
                [
					'label'      => 'Image Spinner Settings',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field::text( 'data_folder', [ 'label' => 'Image Folder' ] ),
						DS_Field::text( 'data_prefix', [ 'label' => 'Image Frame Prefix' ] ),
						DS_Field::text( 'data_ext', [ 'label' => 'Image Extension' ] ),
						DS_Field::number(
							'data_digits',
							[
								'label'         => 'Image Frame Digits',
								'default_value' => 4,
							]
						),
						DS_Field::number(
							'data_count',
							[
								'label'         => 'Number of Images',
								'default_value' => 2,
							]
						),
						DS_Field::true_false(
                            'data_autoanimate',
                            [
								'label' => 'Auto Animate',
								'ui'    => 1,
                            ]
                        ),
						DS_Field::true_false(
                            'data_has-hotspots',
                            [
								'label' => 'Has Hotspots',
								'ui'    => 1,
                            ],
                            true
                        ),
						DS_Field::true_false(
                            'data_drag',
                            [
								'label'       => 'Enable Drag',
								'description' => 'Disable if hotspots are included.',
								'ui'          => 1,
                            ],
                            true
                        ),
					],
                ]
            ),

			DS_Field::group(
                'hotspots_content_type',
                [
					'label'             => 'Hotspots Content Type',
					'layout'            => 'block',
					'sub_fields'        => [
						DS_Field::true_false(
                            'tooltip',
                            [
								'label' => 'Tooltip',
								'ui'    => 1,
                            ]
                        ),
						DS_Field::true_false(
                            'label',
                            [
								'label' => 'Label',
								'ui'    => 1,
                            ]
                        ),
					],
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'module_image_spinner_settings_data_has-hotspots',
								'operator'  => '==',
								'value'     => '1',
							),
						),
					),
                ],
                true
            ),

			DS_Field::group(
                'module_image_spinner_controls',
                [
					'label'      => 'Image Spinner Controls',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field::true_false(
                            'data_progress-fraction',
                            [
								'label' => 'Show Progress Fraction',
								'ui'    => 1,
                            ]
                        ),
						DS_Field::true_false(
                            'data_playback',
                            [
								'label' => 'Play/Pause',
								'ui'    => 1,
                            ]
                        ),
						DS_Field::true_false(
                            'data_frames-nav',
                            [
								'label' => 'Prev/Next Frame',
								'ui'    => 1,
                            ]
                        ),
						DS_Field::true_false(
							'data_hotspots-nav',
							[
								'label'             => 'Prev/Next Hotspot',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../module_image_spinner_settings_data_has-hotspots',
											'operator'  => '==',
											'value'     => '1',
										),
									),
								),
							]
						),
						DS_Field::true_false(
							'data_zoom',
							[
								'label'             => 'Zoom',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../module_image_spinner_settings_data_has-hotspots',
											'operator'  => '!=',
											'value'     => '1',
										),
									),
								),
							]
						),
						DS_Field::true_false(
							'data_fullscr',
							[
								'label'             => 'Full Screen',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../module_image_spinner_settings_data_has-hotspots',
											'operator'  => '!=',
											'value'     => '1',
										),
									),
								),
							]
						),
					],
                ],
                true
            ),
			DS_Field::group(
                'module_image_spinner_layout',
                [
					'label'      => 'Image Spinner Layout',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field::text(
                            'image_max_width',
                            [
								'label'         => 'Image Max Width',
								'default_value' => '100%',
                            ]
                        ),
						DS_Field::number(
							'image_aspect_ratio',
							[
								'label'         => 'Image Aspect Ratio',
								'default_value' => 1.27,
							]
						),
					],
                ],
                true
            ),

			DS_Field::tab(
                'component_settings_tab',
                [
					'label'     => 'Component Settings',
					'placement' => 'left',
                ],
                true
            ),
			DS_Field::group(
                'component_settings',
                [
					'label'      => 'Component Settings',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field::button_group(
                            'horizontal_alignment',
							[
								'label'         => 'X-Align',
								'choices'       => [
									'left'   => 'Left',
									'center' => 'Center',
									'right'  => 'Right',
								],
								'default_value' => 'center',
                            ]
                        ),
					],
                ],
                true
            ),

			DS_Field::tab(
                'layout_settings_tab',
                [
					'label'     => 'Layout Settings',
					'placement' => 'left',
                ]
            ),
			DS_Field::group(
                'layout_settings',
                [
					'label'      => 'Layout Settings',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field_Container::get( 'container', true ),
						DS_Field_ModuleGap::get(),
					],
                ]
            ),
		];
	}
}
