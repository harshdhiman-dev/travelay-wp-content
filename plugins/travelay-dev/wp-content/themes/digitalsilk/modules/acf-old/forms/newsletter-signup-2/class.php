<?php
// phpcs:ignoreFile

class DS_Module_newsletter_signup_2 extends DS_AbstractModule {

	public $name = 'newsletter-signup-2';

	public $title = 'Newsletter Signup 2';

	protected $description = 'Newsletter Signup Form with info box';

	protected $category = 'ds-forms';

	protected $icon = 'email';

	protected $keywords = array( 'form', 'info box', 'cf7', 'mailchimp' );

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'info_box_content_ac', array( 'label' => 'Info Box Content' ) ),
			DS_Field::text(
				'info_box_title',
				array(
					'label'            => 'Title',
					'ds_default_value' => 1,
				)
			),
			DS_Field::accordion( 'form_content_ac', array( 'label' => 'Form Content' ) ),
			DS_Field::text( 'form_shortcode', array( 'label' => 'Form Shortcode' ) ),
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
			DS_Field::group(
				'component_settings',
				array(
					'label'      => 'Component Settings',
					'layout'     => 'block',
					'sub_fields' => array(
						DS_Field_ComponentGap::get(
							'inner_gap',
							array(
								'label'         => 'Inner Gap (px)',
								'default_value' => 0,
							)
						),
						DS_Field::select(
							'input_margin',
							array(
								'label'         => 'Input Margin (px)',
								'choices'       => array(
									0  => '0',
									5  => '5',
									10 => '10',
									15 => '15',
									20 => '20',
									25 => '25',
									30 => '30',
									35 => '35',
									40 => '40',
									45 => '45',
									50 => '50',
								),
								'default_value' => 0,
							)
						),
						DS_Field::color_picker( 'bg_color', array( 'label' => 'Background Color' ) ),
					),
				),
				true
			),
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
						DS_Field_ColumnsOrder::get(),
						DS_Field::true_false(
							'vertical_columns',
							array(
								'label'         => 'Vertical Columns',
								'default_value' => 0,
								'ui'            => 1,
							)
						),
						DS_Field::range(
							'columns_ratio',
							array(
								'label'         => 'Columns Ratio',
								'append'        => '%',
								'default_value' => 40,
							)
						),
						DS_Field_LayoutType::get( 1 ),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
