<?php
// phpcs:ignoreFile

class DS_Module_double_cards extends DS_AbstractModule {

	public $name = 'double-cards';

	public $title = 'Cards Double Slider';

	protected $description = 'Slider listing of content cards with separated mobile slider';

	protected $category = 'ds-sliders';

	protected $icon = 'images-alt';

	protected $keywords = array( 'cards', 'image', 'slider' );

	public function enqueue_assets(): void {
		wp_enqueue_script( 'swiper-js', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.js', true, true, true );
		wp_enqueue_style( 'swiper-css', get_template_directory_uri() . '/assets/vendors/swiper/swiper-bundle.min.css', array(), '1.8' );

		DS_ViteAssets::enqueue_script( 'js/dst-sliders.js' );
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-sliders.scss' );
		DS_ViteAssets::enqueue_style( 'sass/modules/sliders/slider-type/slider-dsbls.scss' );
	}

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'cards_content_accordion', array( 'label' => 'Cards Content' ) ),
			DS_Field::repeater(
				'cards_widget',
				array(
					'label'                  => 'Cards Content',
					'button_label'           => 'Add Card',
					'layout'                 => 'block',
					'ds_default_value'       => 1,
					'ds_default_value_items' => 4,
					'sub_fields'             => array(
						DS_Field::image(
							'image',
							array(
								'label'            => 'Image',
								'ds_default_value' => 1,
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
						DS_Field_CTAList::get(),
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
						DS_Field_SliderTabbedNavigation::get(),
						DS_Field::button_group(
							'data_trigger',
							array(
								'label'             => 'Trigger',
								'choices'           => array(
									'mouseover' => 'hover',
									'click'     => 'click',
								),
								'default_value'     => 'hover',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'tabbed_navigation',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
						DS_Field_SliderIsMobile::get(),
						DS_Field_SliderArrowsNavigation::get(),
						DS_Field_SliderPagination::get(),
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
						DS_Field_ComponentGap::get(),
						DS_Field_LayoutType::get(),
						DS_Field_ComponentType::get(),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
