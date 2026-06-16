<?php
// phpcs:ignoreFile

class DS_Module_panel_slider extends DS_AbstractModule {

	public $name = 'panel-slider';

	public $title = 'Panel Slider';

	protected $description = 'Listing of content items';

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
		return array(
			DS_Field::accordion( 'content_ac', array( 'label' => 'Main Content' ) ),
			DS_Field::repeater(
				'content_list',
				array(
					'label'            => 'Content List',
					'button_label'     => 'Add Item',
					'layout'           => 'block',
					'ds_default_value' => 1,
					'sub_fields'       => array(
						DS_Field::accordion( 'main_accordion', array( 'label' => 'Content' ) ),
						DS_Field_Block_V2_Content::get(),
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
						DS_Field_SliderColumns::get(),
						DS_Field_SliderColumnsGap::get(),
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
			DS_Field_Block_V2_ComponentSettings::get(),
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
