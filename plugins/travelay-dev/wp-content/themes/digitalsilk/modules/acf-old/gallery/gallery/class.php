<?php
// phpcs:ignoreFile

class DS_Module_gallery extends DS_AbstractModule {

	public $name = 'gallery';

	public $title = 'Gallery';

	protected $description = 'Gallery Grid with popup';

	protected $category = 'ds-gallery';

	protected $icon = 'format-gallery';

	protected $keywords = array( 'gallery', 'slider', 'image', 'images', 'grid' );

	protected string $core = '';

	public function enqueue_assets(): void {
		// wp_enqueue_style( 'floatbox', get_template_directory_uri() . '/assets/vendors/floatbox/floatbox.css' );
		// wp_enqueue_script( 'floatbox', get_template_directory_uri() . '/assets/vendors/floatbox/floatbox.js', array(), '8.5.0', true );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'content_ac', array( 'label' => 'Main Content' ) ),
			DS_Field::gallery(
				'gallery',
				array(
					'label' => 'Gallery',
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
			DS_Field_BackgroundSimple::get(),
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
				'gallery_tab',
				array(
					'label'     => 'Gallery Settings',
					'placement' => 'left',
				)
			),
			DS_Field::group(
				'gallery_settings',
				array(
					'label'      => 'Gallery Settings',
					'sub_fields' => array(
						DS_Field::range(
							'overlay_opacity',
							array(
								'label'         => 'Overlay Opacity',
								'default_value' => 55,
								'max'           => 100,
								'append'        => '%',
							)
						),
						DS_Field::color_picker(
							'overlay_color',
							array(
								'label'         => 'Overlay Color',
								'default_value' => '#000000',
							)
						),
					),
					'layout'     => 'block',
				)
			),
			DS_Field::tab(
				'component_settings_tab',
				array(
					'label'     => 'Component Settings',
					'placement' => 'left',
				)
			),
			DS_Field::group(
				'component_settings',
				array(
					'label'      => 'Component Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field_ComponentGap::get(
							'inner_gap_vertical',
							array(
								'label'         => 'Inner Vertical Gap (px)',
								'default_value' => 0,
							)
						),
						DS_Field_ComponentGap::get(
							'inner_gap_horizontal',
							array(
								'label'         => 'Inner Horizontal Gap (px)',
								'default_value' => 0,
							)
						),
						DS_Field::true_false(
							'has_background',
							array(
								'label' => 'Custom Background?',
								'ui'    => 1,
							)
						),
						DS_Field::color_picker(
							'component_background',
							array(
								'label'             => 'Component Background',
								'default_value'     => '#ffffff',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'has_background',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::true_false(
							'has_hover',
							array(
								'label' => 'Has Hover?',
								'ui'    => 1,
							)
						),
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
						DS_Field_ComponentGap::get( 'card_gap_vertical', array( 'label' => 'Y-Gap (px)' ) ),
						DS_Field_ComponentGap::get( 'card_gap_horizontal', array( 'label' => 'X-Gap (px)' ) ),
						DS_Field_Columns::get(),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
