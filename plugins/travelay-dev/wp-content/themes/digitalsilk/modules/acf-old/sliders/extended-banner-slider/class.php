<?php
// phpcs:ignoreFile

class DS_Module_extended_banner_slider extends DS_AbstractModule {

	public $name = 'extended-banner-slider';

	public $title = 'Banner Extended Slider';

	protected $description = 'Banner slider with split sections';

	protected $category = 'ds-sliders';

	protected $icon = 'button';

	protected $keywords = array( 'banner', 'slider', 'tabbed navigation', 'hover' );

	public function enqueue_assets(): void {
		wp_enqueue_script( 'swiper-js', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.js', true, true, true );
		wp_enqueue_style( 'swiper-css', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.css', array(), '1.8' );

		DS_ViteAssets::enqueue_script( 'js/dst-sliders.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-sliders.scss' );
		DS_ViteAssets::enqueue_style( 'sass/modules/sliders/slider-type/slider-banner-tabs.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'content_slider_ac', array( 'label' => 'Main Content' ) ),
			DS_Field::repeater(
				'content_slider',
				array(
					'label'            => 'Content Slider',
					'layout'           => 'block',
					'button_label'     => 'Add Item',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field::accordion(
							'navigation_accordion_tabbed',
							array(
								'label' => 'Navigation',
							)
						),
						DS_Field::text( 'slider_navigation_text', array( 'label' => 'Slider Navigation Text' ) ),

						DS_Field::accordion( 'text_content_accordion', array( 'label' => 'Text' ) ),
						DS_Field::text( 'pretitle', array( 'label' => 'Pretitle' ) ),
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

						DS_Field::accordion( 'background_accordion', array( 'label' => 'Background' ) ),
						DS_Field_MediaBackground::get(),
						DS_Field::range(
							'overlay_opacity',
							array(
								'label'  => 'Overlay Opacity',
								'append' => '%',
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
			DS_Field_TitleStyles::get(),
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
						DS_Field::accordion( 'settings', array( 'label' => 'Slider Settings' ) ),
						DS_Field_SliderNavigation::get(),
						DS_Field::button_group(
							'data_trigger',
							array(
								'label'             => 'Trigger',
								'choices'           => array(
									'click'     => 'click',
									'mouseover' => 'hover',
								),
								'default_value'     => 'click',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'data_thumbs',
											'operator'  => '==',
											'value'     => 'tabbed',
										),
									),
								),
							)
						),
						DS_Field_SliderArrowsNavigation::get(),
						DS_Field_SliderPagination::get(),
						DS_Field::true_false(
							'data_leading-zero',
							array(
								'label'             => 'Leading Zero',
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
			DS_Field::group(
				'slider_navigation_component_settings',
				array(
					'label'      => '',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field::accordion( 'settings', array( 'label' => 'Slider Navigation Settings' ) ),
						DS_Field_LayoutType::get( 5, 'nav_layout_type', array( 'label' => 'Slider Navigation Layout Type' ) ),
						DS_Field::true_false(
							'has_counter',
							array(
								'label' => 'Has Counter?',
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
						DS_Field_Container::get( 'container-fluid' ),
						DS_Field_LayoutType::get(),
						DS_Field_ScreenHeight::get(),
						DS_Field_LayoutGap::get(),
					),
				),
				true
			),
		);
	}
}
