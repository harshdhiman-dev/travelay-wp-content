<?php
// phpcs:ignoreFile

class DS_Module_accordion extends DS_AbstractModule {

	public $name = 'accordion';

	public $title = 'Accordion with image';

	protected $description = 'Accordion with side images';

	protected $category = 'ds-accordions';

	protected $icon = 'list-view';

	protected $keywords = array( 'accordion', 'text', 'image' );

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_script( 'js/dst-accordions.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-accordions.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'content_ac', array( 'label' => 'Main Content' ) ),
			DS_Field::text( 'pretitle', array( 'label' => 'Pretitle' ) ),
			DS_Field::text(
				'title',
				array(
					'label'            => 'Title',
					'ds_default_value' => 1,
				)
			),
			DS_Field::text( 'subtitle', array( 'label' => 'Subtitle' ) ),
			DS_Field::wysiwyg(
				'description',
				array(
					'label'            => 'Description',
					'ds_default_value' => 1,
				)
			),
			DS_Field_CTAList::get(),
			DS_Field::accordion( 'accordion_content_ac', array( 'label' => 'Accordion Content' ) ),
			DS_Field::true_false(
				'accordion_gallery',
				array(
					'label' => 'Is Accordion Gallery?',
					'ui'    => 1,
				)
			),
			DS_Field_MixedGallery_V1_Content::get(
				array(
					array(
						array(
							'fieldPath' => 'accordion_gallery',
							'operator'  => '!=',
							'value'     => '1',
						),
					),
				)
			),
			DS_Field::true_false(
				'has_image_description',
				array(
					'label'             => 'Add Image Description?',
					'ui'                => 1,
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'accordion_gallery',
								'operator'  => '!=',
								'value'     => '1',
							),
						),
					),
				)
			),
			DS_Field::textarea(
				'image_description',
				array(
					'label'             => 'Image Description',
					'rows'              => 2,
					'new_lines'         => 'wpautop',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'has_image_description',
								'operator'  => '==',
								'value'     => 1,
							),
						),
					),
				)
			),
			DS_Field::repeater(
				'accordion_content',
				array(
					'label'            => 'Accordion Content',
					'button_label'     => 'Add Accordion',
					'layout'           => 'block',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field::accordion(
							'm_content_ac',
							array(
								'label'             => 'Media',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../accordion_gallery',
											'operator'  => '==',
											'value'     => '1',
										),
									),
								),
							)
						),
						DS_Field_MixedGallery_V1_Content::get(
							array(
								array(
									array(
										'fieldPath' => 'accordion_gallery',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							)
						),
						DS_Field::true_false(
							'has_image_description',
							array(
								'label'             => 'Add Image Description?',
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../accordion_gallery',
											'operator'  => '==',
											'value'     => '1',
										),
									),
								),
							)
						),
						DS_Field::textarea(
							'image_description',
							array(
								'label'             => 'Image Description',
								'rows'              => 2,
								'new_lines'         => 'wpautop',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'has_image_description',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::accordion( 'c_content_ac', array( 'label' => 'Content' ) ),
						DS_Field::image( 'icon', array( 'label' => 'Icon' ) ),
						DS_Field::text(
							'title',
							array(
								'label'            => 'Title',
								'ds_default_value' => 1,
							)
						),
						DS_Field::wysiwyg(
							'description',
							array(
								'label'            => 'Description',
								'ds_default_value' => 1,
							)
						),
						DS_Field_CTAList::get(),
					),
				)
			),
			DS_Field::accordion( 'advanced_settings_ac', array( 'label' => 'Advanced Settings' ) ),
			DS_Field::tab(
				'title_tab',
				array(
					'label'     => 'Title Styles',
					'placement' => 'left',
				)
			),
			DS_Field_TitleStyles::get(),
			DS_Field_ComponentTitle::get( array( 'label' => 'Accordion Item Title Styles' ) ),
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
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Main Component Settings',
								'open'  => 1,
							)
						),
						DS_Field_ComponentType::get(
							2,
							'type',
							array(
								'label' => 'Accordion Component Type',
							)
						),
						DS_Field_ComponentType::get(
							2,
							'gallery_component_type',
							array(
								'label' => 'Gallery Component Type',
							)
						),
						DS_Field_ColumnsOrder::get(),
						DS_Field::range(
							'columns_ratio',
							array(
								'label'         => 'Columns Ratio',
								'append'        => '%',
								'default_value' => 50,
							)
						),
					),
				),
				true
			),
			DS_Field_Accordion_ComponentSettings::get(),
			DS_Field_MixedGallery_V1_ComponentSettings::get(),
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
