<?php
// phpcs:ignoreFile

class DS_Module_cta extends DS_AbstractModule {

	public $name = 'cta';

	public $title = 'CTA';

	protected $description = 'CTA';

	protected $category = 'ds-separators';

	protected $icon = 'align-center';

	protected $keywords = array( 'cta', 'separator' );

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'cta_content_ac', array( 'label' => 'CTA Content' ) ),
			DS_Field::text( 'title', array( 'label' => 'Title' ) ),
			DS_Field::link( 'cta_link', array( 'label' => 'Link' ) ),
			DS_Field::text(
				'link_rel',
				array(
					'label'        => 'Link rel',
					'instructions' => 'noreferrer noopener',
				)
			),
			DS_Field::accordion( 'cta_styles_ac', array( 'label' => 'CTA Styles' ) ),
			DS_Field::color_picker( 'title_color', array( 'label' => 'Title Color' ) ),
			DS_Field::color_picker( 'cta_text_color', array( 'label' => 'CTA Text Color' ) ),
			DS_Field::button_group(
				'cta_type',
				array(
					'label'   => 'CTA Background Type',
					'choices' => array(
						'color'    => 'Color',
						'gradient' => 'Gradient',
					),
				)
			),
			DS_Field::color_picker(
				'cta_gradient_color_1',
				array(
					'label'             => 'Gradient color 1',
					'enable_opacity'    => 1,
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'cta_type',
								'operator'  => '==',
								'value'     => 'gradient',
							),
						),
					),
				)
			),
			DS_Field::color_picker(
				'cta_gradient_color_2',
				array(
					'label'             => 'Gradient color 2',
					'enable_opacity'    => 1,
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'cta_type',
								'operator'  => '==',
								'value'     => 'gradient',
							),
						),
					),
				)
			),
			DS_Field::button_group(
				'cta_gradient_direction',
				array(
					'label'             => 'Gradient direction',
					'choices'           => array(
						'top'    => 'Top',
						'right'  => 'Right',
						'bottom' => 'Bottom',
						'left'   => 'Left',
					),
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'cta_type',
								'operator'  => '==',
								'value'     => 'gradient',
							),
						),
					),
				)
			),
			DS_Field::color_picker( 'cta_color', array( 'label' => 'Background Color' ) ),
			DS_Field::accordion( 'advanced_settings', array( 'label' => 'Advanced Settings' ) ),
			DS_Field::tab(
				'background_styles_tab',
				array(
					'label'     => 'Background Styles',
					'placement' => 'left',
				)
			),
			DS_Field_Background::get(),
			DS_Field::tab(
				'effects_tab',
				array(
					'label'     => 'Effects',
					'placement' => 'left',
				)
			),
			DS_Field_ModuleEffects::get(),
		);
	}
}
