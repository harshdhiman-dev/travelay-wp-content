<?php

// phpcs:ignoreFile
class DS_Module_Marquee_Slider extends DS_AbstractModule {

	public $name = 'marquee-slider';

	public $title = 'Marquee Slider';

	protected $description = 'Marquee slider of images';

	protected $category = 'ds-sliders';

	protected $icon = 'images-alt2';

	protected $keywords = [ 'logos', 'image', 'slider' ];

	public function enqueue_assets(): void {
		DS_ViteAssets::enqueue_style( 'sass/modules/dst-sliders.scss' );
	}

	public function add_composer_fields(): array {
		return [
			DS_Field::accordion( 'images_slider_ac', [ 'label' => 'Images' ] ),
			DS_Field::repeater(
				'content_images',
				[
					'label'                  => 'Images',
					'layout'                 => 'block',
					'button_label'           => 'Add Image',
					'ds_default_value'       => 1,
					'ds_default_value_items' => 6,
					'sub_fields'             => [
						DS_Field::image(
							'image',
							[
								'label'            => 'Image',
								'ds_default_value' => 1,
							]
						),
					],
				]
			),
			DS_Field::accordion( 'advanced_settings', [ 'label' => 'Advanced Settings' ] ),
			DS_Field_ModuleDecorations::get(),
			DS_Field::tab(
				'slider_settings_tab',
				[
					'label'     => 'Marquee Settings',
					'placement' => 'left',
				],
				true
			),
			DS_Field::group(
				'module_slider_settings',
				[
					'label'      => 'Marquee Settings',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field::true_false(
							'data_autoplay',
							[
								'label' => 'Autoplay',
								'ui'    => 1,
							]
						),
						DS_Field::number( 'interval', [ 'label' => 'Autoplay Interval (s)' ] ),
						DS_Field::number( 'gap', [ 'label' => 'Slide Gap (px)' ] ),
						DS_Field::number( 'max_height', [ 'label' => 'Slide Max Height (px)' ] ),
						DS_Field::number( 'max_width', [ 'label' => 'Slide Max Width (px)' ] ),
						DS_Field::true_false(
							'data_pause',
							[
								'label' => 'Pause on Hover',
								'ui'    => 1,
							]
						),
						DS_Field::true_false(
							'data_reversed',
							[
								'label' => 'Reversed Direction',
								'ui'    => 1,
							]
						),
					],
				],
				true
			),
			DS_Field::tab(
				'component_settings_tab',
				[
					'label'     => 'Component Settings',
					'placement' => 'left',
				],
				true
			),
			DS_Field::group(
				'component_settings',
				[
					'label'      => 'Layout Settings',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field::accordion(
							'settings',
							[
								'label' => 'Component Settings',
								'open'  => 1,
							]
						),
						DS_Field::true_false(
							'is_vertical',
							[
								'label' => 'Vertical Scrolling',
								'ui'    => 1,
							]
						),
						DS_Field::true_false(
							'is_greyscale',
							[
								'label' => 'Has Greyscale',
								'ui'    => 1,
							]
						),
						DS_Field::true_false(
							'is_fit',
							[
								'label' => 'Fit Content',
								'ui'    => 1,
							]
						),
						DS_Field::true_false(
							'is_absolute',
							[
								'label'             => 'Is Absolute',
								'ui'                => 1,
								'conditional_logic' => [
									[
										[
											'fieldPath'    => 'is_fit',
											'operator' => '==',
											'value'    => 1,
										]
									]
								],
							]
						),
					],
				],
				true
			),
			DS_Field::tab(
				'effects_tab',
				[
					'label'     => 'Effects',
					'placement' => 'left',
				],
				true
			),
			DS_Field_ModuleEffects::get(),
			DS_Field::tab(
				'layout_settings_tab',
				[
					'label'     => 'Layout Settings',
					'placement' => 'left',
				],
				true
			),
			DS_Field::group(
				'layout_settings',
				[
					'label'      => 'Layout Settings',
					'layout'     => 'block',
					'sub_fields' => [
						DS_Field_Container::get(),
						DS_Field_ModuleGap::get(),
					],
				],
				true
			),
		];
	}
}
