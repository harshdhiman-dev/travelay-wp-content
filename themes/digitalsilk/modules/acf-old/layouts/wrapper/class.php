<?php

// phpcs:ignoreFile
class DS_Module_wrapper extends DS_AbstractModule {

	public $name = 'wrapper';

	public $title = 'Wrapper';

	protected $description = 'Common container wrapper with columns feature';

	protected $category = 'ds-layouts';

	protected $icon = 'align-wide';

	protected $keywords = [ 'content', 'wrapper', 'columns' ];
	protected bool $supportInnerBlocks = true;

	public function add_composer_fields(): array {
		return [
			DS_Field::accordion( 'advanced_settings', [ 'label' => 'Advanced Settings' ] ),
			DS_Field::tab(
				'background_styles_tab',
				[
					'label'     => 'Background Styles',
					'placement' => 'left',
				]
			),
			DS_Field::group(
				'media_background',
				array(
					'label'      => 'Background Media',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field_MediaBackground::get(),
					),
				)
			),
			DS_Field_BackgroundSimple::get(),
			DS_Field_ModuleDecorations::get(),
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
						DS_Field_Container::get( 'container-fluid' ),
						DS_Field::true_false( 'module_columns', [ 'label' => 'Module columns' ] ),
						DS_Field::range(
							'columns_ratio',
							[
								'label'             => 'Columns ratio',
								'conditional_logic' => [
									[
										[
											'fieldPath' => 'module_columns',
											'operator'  => '==',
											'value'     => 1,
										],
									],
								],
								'default_value'     => 50,
								'min'               => 1,
								'max'               => 99,
							]
						),
						DS_Field_ComponentGap::get(
							'columns_gap',
							[
								'label'             => 'Columns gap',
								'conditional_logic' => [
									[
										[
											'fieldPath' => 'module_columns',
											'operator'  => '==',
											'value'     => 1,
										],
									],
								],
							]
						),
						DS_Field::button_group(
							'vertical_alignment',
							[
								'label'             => 'Y-Align',
								'choices'           => [
									'top'    => 'Top',
									'center' => 'Center',
									'bottom' => 'Bottom',
								],
								'default_value'     => 'top',
								'conditional_logic' => [
									[
										[
											'fieldPath' => 'module_columns',
											'operator'  => '==',
											'value'     => 1,
										],
									],
								],
							]
						),
						DS_Field_ModuleGap::get(),
					],
				],
				true
			),
		];
	}
}
