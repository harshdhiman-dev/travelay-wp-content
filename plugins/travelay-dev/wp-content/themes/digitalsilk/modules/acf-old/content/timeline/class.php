<?php
// phpcs:ignoreFile

class DS_Module_Timeline extends DS_AbstractModule {

	public $name = 'timeline';

	public $title = 'Timeline';

	protected $description = 'Timeline with photos';

	protected $category = 'ds-content';

	protected $icon = 'editor-help';

	protected $keywords = array( 'timeline', 'images' );

	protected bool $new = true;

	/**
	 * Add Composer Fields
	 */
	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'timeline_content_accordion', array( 'label' => 'Timeline Content' ) ),
			DS_Field::repeater(
				'timeline_listing',
				array(
					'label'            => 'Timeline Content',
					'button_label'     => 'Add Item',
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
						DS_Field::textarea(
							'description',
							array(
								'label'            => 'Description',
								'rows'             => 3,
								'new_lines'        => 'wpautop',
								'ds_default_value' => 1,
							)
						),
					),
				)
			),
			DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
			DS_Field::tab(
				'title_tab',
				array(
					'label'     => 'Title Styles',
					'placement' => 'left',
				)
			),
			DS_Field_ComponentTitle::get(),
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
						DS_Field::button_group(
							'horizontal_alignment',
							array(
								'label'         => 'X-Align',
								'choices'       => array(
									'left'   => 'Left',
									'center' => 'Center',
									'right'  => 'Right',
								),
								'default_value' => 'center',
							)
						),
						DS_Field::button_group(
							'vertical_alignment',
							array(
								'label'         => 'Y-Align',
								'choices'       => array(
									'top'    => 'Top',
									'center' => 'Center',
									'bottom' => 'Bottom',
								),
								'default_value' => 'top',
							)
						),
						DS_Field::color_picker( 'title_color', array( 'label' => 'Title Color' ) ),
						DS_Field::color_picker( 'content_color', array( 'label' => 'Content Color' ) ),
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
						DS_Field_LayoutType::get(),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
