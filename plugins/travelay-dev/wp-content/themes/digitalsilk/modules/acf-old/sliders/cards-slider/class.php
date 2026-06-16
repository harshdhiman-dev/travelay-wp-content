<?php
// phpcs:ignoreFile

class DS_Module_cards_slider extends DS_AbstractModule {

	public $name = 'cards-slider';

	public $title = 'Cards Slider';

	protected $description = 'Listing of content cards';

	protected $category = 'ds-sliders';

	protected $icon = 'format-gallery';

	protected $keywords = array( 'cards', 'slider', 'image' );

	public function enqueue_assets(): void {
		wp_enqueue_script( 'swiper-js', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.js', true, true, true );
		wp_enqueue_style( 'swiper-css', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.css', array(), '1.8' );

		DS_ViteAssets::enqueue_script( 'js/dst-sliders.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-sliders.scss' );
	}

	public function add_composer_fields(): array {
		global $dsmp_settings;
		$post_types = is_array( $dsmp_settings->post_types ) ? $dsmp_settings->post_types : array( $dsmp_settings->post_types );

		return array(
			DS_Field_Section_Header::get(),

			DS_Field::accordion( 'cards_content_accordion', array( 'label' => 'Cards Content' ) ),
			DS_Field::button_group(
				'content_type',
				array(
					'label'         => 'Content type',
					'choices'       => array(
						'static'    => 'static',
						'post_type' => 'post type',
					),
					'default_value' => 'static',
				)
			),
			DS_Field::group(
				'post_type_data',
				array(
					'label'             => 'Post Type Data',
					'sub_fields'        => array(
						DS_Field::button_group(
							'query_type',
							array(
								'label'         => '',
								'choices'       => array(
									'latest_posts' => 'Latest Posts',
									'select_posts' => 'Select Posts',
								),
								'default_value' => 'latest_posts',
							)
						),
						DS_Field::select(
							'post_type',
							array(
								'label'             => 'Choose post type',
								'choices'           => array_combine( $post_types, $post_types ),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'query_type',
											'operator'  => '==',
											'value'     => 'latest_posts',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'filter_tabs',
							array(
								'label'             => 'Filter Tabs',
								'choices'           => array(
									'disable' => 'Disable',
									'enable'  => 'Enable',
								),
								'default_value'     => 'disable',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'post_type',
											'operator'  => '==',
											'value'     => 'post',
										),
									),
								),
							)
						),
						DS_Field::button_group(
							'filter_type',
							array(
								'label'             => 'Filter Type',
								'choices'           => array(
									'list'     => 'list',
									'dropdown' => 'dropdown',
								),
								'default_value'     => 'list',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'filter_tabs',
											'operator'  => '==',
											'value'     => 'enable',
										),
									),
								),
							)
						),
						DS_Field::taxonomy(
							'filter_categories',
							array(
								'label'             => 'Filter Categories',
								'taxonomy'          => 'category',
								'field_type'        => 'multi_select',
								'return_format'     => 'id',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'filter_tabs',
											'operator'  => '==',
											'value'     => 'enable',
										),
									),
								),
							)
						),
						DS_Field::range(
							'posts_per_page',
							array(
								'label'             => 'Number of Items?',
								'instructions'      => 'per category with enabled tabs filter',
								'min'               => 1,
								'max'               => 12,
								'default_value'     => 6,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'query_type',
											'operator'  => '==',
											'value'     => 'latest_posts',
										),
									),
								),
							)
						),
						DS_Field::relationship(
							'select_posts',
							array(
								'label'             => '',
								'post_type'         => $post_types,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'query_type',
											'operator'  => '==',
											'value'     => 'select_posts',
										),
									),
								),
								'return_format'     => 'id',
							)
						),
						DS_Field::true_false(
							'is_clickable',
							array(
								'label' => 'Clickable?',
								'ui'    => 1,
							)
						),
						DS_Field::text(
							'button_text',
							array(
								'label'             => 'Button Text',
								'default_value'     => 'Read Full Article',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'is_clickable',
											'operator'  => '!=',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field::group(
							'cta_button_styles',
							array(
								'label'             => 'Story Button',
								'layout'            => 'block',
								'sub_fields'        => array(
									DS_Field_CtaBasicFeatures::get(),
								),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'is_clickable',
											'operator'  => '!=',
											'value'     => 1,
										),
									),
								),
							)
						),
					),
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'content_type',
								'operator'  => '==',
								'value'     => 'post_type',
							),
						),
					),
				)
			),
			DS_Field_Block_V1_Content::get(
				array(
					array(
						array(
							'fieldPath' => 'content_type',
							'operator'  => '==',
							'value'     => 'static',
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
						DS_Field_SliderColumns::get( 6, 4 ),
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
			DS_Field_Block_V1_ComponentSettings::get(),
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
						DS_Field_LayoutType::get( 3, 'filter_layout_type', array( 'label' => 'Filter Layout Type' ) ),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
