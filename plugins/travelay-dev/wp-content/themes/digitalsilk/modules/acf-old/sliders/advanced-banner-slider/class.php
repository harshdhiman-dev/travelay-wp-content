<?php
// phpcs:ignoreFile

class DS_Module_advanced_banner_slider extends DS_AbstractModule {

	public $name = 'advanced-banner-slider';

	public $title = 'Banner Advanced Slider';

	protected $description = 'Banner slider with tabbed navigation';

	protected $category = 'ds-sliders';

	protected $icon = 'button';

	protected $keywords = array( 'banner', 'slider', 'tabbed navigation' );

	public function enqueue_assets(): void {
		wp_enqueue_script( 'swiper-js', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.js', true, true, true );
		wp_enqueue_style( 'swiper-css', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.css', array(), '1.8' );

		DS_ViteAssets::enqueue_script( 'js/dst-sliders.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-sliders.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'content_slider_ac', array( 'label' => 'Main Content' ) ),
			DS_Field::true_false(
				'has_video_in_navigation',
				array(
					'label'             => 'Has Video in Navigation?',
					'ui'                => 1,
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'module_slider_settings_data_thumbs',
								'operator'  => '!=',
								'value'     => '',
							),
						),
					),
				)
			),
			DS_Field::repeater(
				'content_slider',
				array(
					'label'            => 'Content Slider',
					'layout'           => 'block',
					'button_label'     => 'Add Item',
					'ds_default_value' => 1,
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
											'fieldPath' => '../module_slider_settings_data_thumbs',
											'operator'  => '!=',
											'value'     => '',
										),
									),
								),
							)
						),
						DS_Field::image(
							'slider_navigation_icon',
							array(
								'label' => 'Slider Navigation Icon',
							)
						),
						DS_Field::text( 'slider_navigation_text', array( 'label' => 'Slider Navigation Text' ) ),
						DS_Field::group(
							'slider_navigation_video',
							array(
								'label'             => 'Navigation Video',
								'sub_fields'        => array(
									DS_Field_Video::get( false, false ),
								),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => '../has_video_in_navigation',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),

						DS_Field::accordion( 'media_accordion', array( 'label' => 'Additional Content' ) ),
						DS_Field::button_group(
							'content_type',
							array(
								'label'         => 'Content Type',
								'choices'       => array(
									'none'  => 'None',
									'text'  => 'Text/Links',
									'image' => 'Image',
									'video' => 'Video',
								),
								'default_value' => 'none',
							)
						),
						DS_Field::group(
							'content_text',
							array(
								'label'             => 'Text/Links',
								'layout'            => 'block',
								'sub_fields'        => array(
									DS_Field::text( 'pretitle', array( 'label' => 'Pretitle' ) ),
									DS_Field::text(
										'title',
										array(
											'label' => 'Title',
											'ds_default_value' => 1,
										)
									),
									DS_Field::text( 'subtitle', array( 'label' => 'Subtitle' ) ),
									DS_Field::wysiwyg(
										'description',
										array(
											'label' => 'Description',
											'ds_default_value' => 1,
										)
									),
									DS_Field_CTAList::get(),
								),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'content_type',
											'operator'  => '==',
											'value'     => 'text',
										),
									),
								),
							)
						),
						DS_Field::image(
							'content_image',
							array(
								'label'             => 'Image',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'content_type',
											'operator'  => '==',
											'value'     => 'image',
										),
									),
								),
							)
						),
						DS_Field::group(
							'main_video',
							array(
								'label'             => 'Main Video',
								'sub_fields'        => array(
									DS_Field_Video::get(),
								),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'content_type',
											'operator'  => '==',
											'value'     => 'video',
										),
									),
								),
							)
						),

						DS_Field::accordion( 'background_accordion', array( 'label' => 'Background' ) ),
						DS_Field_MediaBackground::get(),
						DS_Field::range(
							'overlay_opacity',
							array(
								'label'  => 'Overlay Opacity',
								'append' => '%',
							)
						),
						DS_Field::color_picker( 'overlay_opacity_color', array( 'label' => 'Overlay Color' ) ),
						DS_Field::accordion( 'settings_accordion', array( 'label' => 'Settings' ) ),
						DS_Field::range(
							'columns_ratio',
							array(
								'label'         => 'Columns Ratio',
								'append'        => '%',
								'default_value' => 75,
							)
						),
						DS_Field_ColumnsOrder::get(),
						DS_Field::true_false(
							'vertical_columns',
							array(
								'label'         => 'Vertical Columns',
								'default_value' => 0,
								'ui'            => 1,
							)
						),
					),
				)
			),
			DS_Field_ScrollDown::get(),

			DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
			DS_Field::tab(
				'title_tab',
				array(
					'label'     => 'Text Styles',
					'placement' => 'left',
				)
			),
			DS_Field_TitleStyles::get(),
			DS_Field::true_false(
				'title_seo',
				array(
					'label'         => 'Enable Seo Primary Tag?',
					'instructions'  => 'If enabled first title will appear as H1 and others will be H2.',
					'default_value' => 0,
					'ui'            => 1,

				)
			),
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
						DS_Field::true_false(
							'is_icon_rounded',
							array(
								'label' => 'Is Icon Rounded?',
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
						DS_Field_ScreenHeight::get(),
						DS_Field_LayoutGap::get(),
						DS_Field::true_false(
							'header_height',
							array(
								'label'         => 'Consider Header Height',
								'default_value' => 0,
								'ui'            => 1,
							)
						),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
