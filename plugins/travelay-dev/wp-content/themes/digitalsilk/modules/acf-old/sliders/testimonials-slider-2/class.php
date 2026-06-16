<?php
// phpcs:ignoreFile

class DS_Module_testimonials_slider_2 extends DS_AbstractModule {

	protected $feature = 'testimonials_feature';

	public $name = 'testimonials-slider-2';

	public $title = 'Testimonials Slider 2';

	protected $description = 'Testimonials slider';

	protected $category = 'ds-sliders';

	protected $icon = 'id';

	protected $keywords = array( 'testimonials', 'slider' );

	public function enqueue_assets(): void {
		wp_enqueue_script( 'swiper-js', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.js', true, true, true );
		wp_enqueue_style( 'swiper-css', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.css', array(), '1.8' );

		DS_ViteAssets::enqueue_script( 'js/dst-sliders.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-sliders.scss' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-testimonial.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'testimonials_content_ac', array( 'label' => 'Testimonials Content' ) ),
			DS_Field::relationship(
				'testimonials',
				array(
					'label'         => 'Testimonials',
					'post_type'     => 'testimonials',
					'filters'       => array( 'search' ),
					'elements'      => array( 'featured_image' ),
					'return_format' => 'id',
				)
			),
			DS_Field::text( 'testimonial_intro_title', array( 'label' => 'Intro Title' ) ),
			DS_Field::group(
				'cta_button',
				array(
					'label'             => 'Story Button',
					'layout'            => 'block',
					'sub_fields'        => array(
						DS_Field_CtaBasicFeatures::get(),
					),
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'text_component_settings_has_read_full_story',
								'operator'  => '==',
								'value'     => 1,
							),
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
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion(
							'settings',
							array(
								'label' => 'Slider Settings',
								'open'  => 1,
							)
						),
						DS_Field_SliderThumbsNavigation::get(),
						DS_Field::true_false(
							'data_vertical',
							array(
								'label'             => 'Vertical Direction',
								'ui'                => 1,
								'default_value'     => 0,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'data_thumbs',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field_SliderArrowsNavigation::get(),
						DS_Field_SliderPagination::get(),
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
						DS_Field_SliderAutoplay::get(),
						DS_Field_SliderEffect::get(),
					),
				)
			),
			DS_Field_SliderArrows_ComponentSettings::get(),
			DS_Field::group(
				'tabbed_navigation_component_settings',
				array(
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion( 'settings', array( 'label' => 'Avatar Settings' ) ),
						DS_Field::true_false(
							'show_avatar',
							array(
								'label'         => 'Show Avatar?',
								'ui'            => 1,
								'default_value' => 1,
							)
						),
						DS_Field::true_false(
							'is_image_rounded',
							array(
								'label' => 'Is Image Rounded?',
								'ui'    => 1,
							)
						),
						DS_Field::true_false(
							'show_name_position',
							array(
								'label' => 'Show Name and Position?',
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
				'component_settings_tab',
				array(
					'label'     => 'Component Settings',
					'placement' => 'left',
				),
				true
			),
			DS_Field_Testimonial_V2_ComponentSettings::get(),
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
						DS_Field_LayoutType::get( 5, 'nav_layout_type', array( 'label' => 'Navigation Layout Type' ) ),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
