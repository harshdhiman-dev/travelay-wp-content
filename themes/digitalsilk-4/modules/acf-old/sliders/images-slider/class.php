<?php
// phpcs:ignoreFile

class DS_Module_images_slider extends DS_AbstractModule {

	public $name = 'images-slider';

	public $title = 'Logo Slider';

	protected $description = 'Slider listing of logos';

	protected $category = 'ds-sliders';

	protected $icon = 'images-alt2';

	protected $keywords = array( 'logos', 'image', 'slider' );

	public function enqueue_assets(): void {
		wp_enqueue_script( 'swiper-js', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.js', true, true, true );
		wp_enqueue_style( 'swiper-css', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.css', array(), '1.8' );

		DS_ViteAssets::enqueue_script( 'js/dst-sliders.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-sliders.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'images_slider_ac', array( 'label' => 'Images' ) ),
			DS_Field::repeater(
				'content_images',
				array(
					'label'                  => 'Images',
					'layout'                 => 'block',
					'button_label'           => 'Add Image',
					'ds_default_value'       => 1,
					'ds_default_value_items' => 6,
					'sub_fields'             => array(
						DS_Field::image(
							'image',
							array(
								'label'            => 'Image',
								'ds_default_value' => 1,
							)
						),
						DS_Field::true_false(
							'has_link_or_popup',
							array(
								'label' => 'Has Link/Popup?',
								'ui'    => 1,
							)
						),
						DS_Field::button_group(
							'type',
							array(
								'label'             => 'Type',
								'choices'           => array(
									'link'  => 'link',
									'popup' => 'popup',
								),
								'default_value'     => 'link',
								'instructions'      => 'If popup is chosen the image will expand on screen.',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'has_link_or_popup',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::link(
							'link',
							array(
								'label'             => 'Link',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'has_link_or_popup',
											'operator'  => '==',
											'value'     => 1,
										),
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'link',
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
				'slider_settings_tab',
				array(
					'label'     => 'Slider Settings',
					'placement' => 'left',
				)
			),
			DS_Field::group(
				'module_slider_settings',
				array(
					'label'      => 'Slider Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field_SliderColumns::get( 6, 6 ),
						DS_Field_SliderColumnsGap::get(),
						DS_Field_SliderColumnsMobile::get(),
						DS_Field_SliderColumnsMobileGap::get(),
						DS_Field_SliderArrowsNavigation::get(),
						DS_Field_SliderPagination::get(),
						DS_Field::true_false(
							'data_leading-zero',
							array(
								'label'             => 'Leading Zero',
								'ui'                => 1,
								'default_value'     => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'data_pagination',
											'operator'  => '==',
											'value'     => 'combo',
										),
									),
									array(
										array(
											'fieldPath' => 'data_pagination',
											'operator'  => '==',
											'value'     => 'fraction',
										),
									),
								),
							)
						),
						DS_Field_SliderLoop::get(),
						DS_Field_SliderAutoplay::get(),
						DS_Field_SliderEffect::get(),
					),
				)
			),
			DS_Field_SliderArrows_ComponentSettings::get(),
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
					'label'      => 'Layout Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Component Settings',
								'open'  => 1,
							)
						),
						DS_Field::true_false(
							'has_greyscale',
							array(
								'label' => 'Has Greyscale?',
								'ui'    => 1,
							)
						),
					),
				),
				true
			),
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
