<?php
// phpcs:ignoreFile

class DS_Module_content_4 extends DS_AbstractModule {

	public $name = 'content-4';

	public $title = 'Content 4';

	protected $description = 'Content block with image';

	protected $category = 'ds-content';

	protected $icon = 'slides';

	protected $keywords = array( 'content', 'image' );

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'cards_content_accordion', array( 'label' => 'Cards Content' ) ),
			DS_Field::image( 'image', array( 'label' => 'Main Image' ) ),
			DS_Field::repeater(
				'cards_widget',
				array(
					'label'            => 'Cards Content',
					'button_label'     => 'Add Card',
					'layout'           => 'block',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field::image(
							'image',
							array(
								'label'            => 'Image',
								'ds_default_value' => 1,
							)
						),
						DS_Field::select(
							'image_size',
							array(
								'label'         => 'Image size',
								'choices'       => array(
									'full'         => 'full',
									'large'        => 'large - 1024px',
									'medium_large' => 'medium_large - 768px',
									'ds_medium'    => 'medium - 400px',
									'ds_small'     => 'small (logo, icon)',
								),
								'default_value' => 'ds_medium',
							)
						),
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
						DS_Field::true_false(
							'is_clickable',
							array(
								'label' => 'Clickable?',
								'ui'    => 1,
							)
						),
						DS_Field_CTAList::get(
							array(
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'is_clickable',
											'operator'  => '!=',
											'value'     => 1,
										),
									),
								),
							),
						),
						DS_Field::link(
							'component_link',
							array(
								'label'             => 'Component Link',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'is_clickable',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
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
						DS_Field::button_group(
							'orientation',
							array(
								'label'         => 'Orientation',
								'choices'       => array(
									'vertical'   => 'vertical',
									'horizontal' => 'horizontal',
								),
								'default_value' => 'vertical',
							)
						),
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
						DS_Field_LayoutType::get( 3 ),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
