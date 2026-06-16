<?php
// phpcs:ignoreFile

class DS_Module_circular_slider extends DS_AbstractModule {

	public $name = 'circular-slider';

	public $title = 'Circular Slider';

	protected $description = 'Circular slider';

	protected $category = 'ds-sliders';

	protected $icon = 'button';

	protected $keywords = array( 'circular', 'slider', 'navigation' );

	public function enqueue_assets(): void {
		wp_enqueue_script( 'swiper-js', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.js', true, true, true );
		wp_enqueue_style( 'swiper-css', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.css', array(), '1.8' );

		DS_ViteAssets::enqueue_script( 'js/dst-sliders.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-sliders.scss' );
		DS_ViteAssets::enqueue_style( 'sass/modules/sliders/slider-type/slider-circular.scss' );
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
					'max'              => 8,
					'sub_fields'       => array(
						DS_Field::accordion( 'text_content_accordion', array( 'label' => 'Text' ) ),
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
						DS_Field::accordion(
							'navigation_accordion_tabbed',
							array(
								'label'             => 'Navigation',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../module_slider_settings_tabbed_navigation',
											'operator'  => '!=',
											'value'     => '',
										),
									),
								),
							)
						),
						DS_Field::image( 'slider_navigation_icon', array( 'label' => 'Slider Navigation Icon' ) ),
						DS_Field::text( 'slider_navigation_text', array( 'label' => 'Slider Navigation Text' ) ),
						DS_Field::accordion( 'background_accordion', array( 'label' => 'Background' ) ),
						DS_Field_MediaBackground::get(),
					),
				)
			),
			DS_Field::image(
				'navigation_inner_circle_image',
				array(
					'label'             => 'Inner Circle Image',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'module_slider_settings_tabbed_navigation',
								'operator'  => '==',
								'value'     => 1,
							),
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
						DS_Field_SliderTabbedNavigation::get(),
						DS_Field_SliderArrowsNavigation::get(),
						DS_Field_SliderPagination::get(),
						DS_Field_SliderAutoplay::get(),
						DS_Field_SliderEffect::get(),
					),
				)
			),
			DS_Field::group(
				'module_slider_nav_settings',
				array(
					'label'             => '',
					'layout'            => 'block',
					'sub_fields'        => array(
						DS_Field::accordion( 'settings', array( 'label' => 'Slider Tabbed Settings' ) ),
						DS_Field::button_group(
							'data_circular-trigger',
							array(
								'label'         => 'Trigger',
								'choices'       => array(
									'click'     => 'click',
									'mouseover' => 'hover',
								),
								'default_value' => 'click',
							)
						),
						DS_Field_SliderCircularDirection::get(),
						DS_Field_SliderCircularPosition::get(),
						DS_Field_SliderCircularArrangeItems::get(),
						DS_Field::true_false(
							'data_circular-centered',
							array(
								'label'             => 'Centered even if uneven no of items',
								'default_value'     => 1,
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'data_circular-arrange',
											'operator'  => '==',
											'value'     => 'center',
										),
									),
								),
							)
						),
						DS_Field::true_false(
							'data_circular-symmetric',
							array(
								'label'             => 'Symmetric',
								'default_value'     => 0,
								'ui'                => 1,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'data_circular-arrange',
											'operator'  => '==',
											'value'     => 'center',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'data_circular-order',
							array(
								'label'             => 'Items Order',
								'choices'           => array(
									'columns' => 'columns',
									'rows'    => 'rows',
								),
								'default_value'     => 'columns',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'data_circular-symmetric',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field_SliderAlignItems::get(),
						DS_Field_SliderCircularAngle::get(),
						DS_Field_SliderCircularOffset::get(),
						DS_Field_SliderRotateToActive::get(),
					),
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'module_slider_settings_tabbed_navigation',
								'operator'  => '==',
								'value'     => 1,
							),
						),
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
						DS_Field_LayoutType::get(
							3,
							'nav_layout_type',
							array(
								'label'         => 'Slider Navigation Layout Type',
								'default_value' => 'v2',
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
				'content_settings_tab',
				array(
					'label'     => 'Content Settings',
					'placement' => 'left',
				)
			),
			DS_Field_TextPosition::get(),
			DS_Field_ContentPosition::get(),
			DS_Field_ContentStyle::get(),
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
						DS_Field_ScreenHeight::get( 'full' ),
					),
				),
				true
			),
		);
	}
}
