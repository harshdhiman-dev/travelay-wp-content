<?php
// phpcs:ignoreFile

class DS_Module_newsletter_signup_1 extends DS_AbstractModule {

	public $name = 'newsletter-signup-1';

	public $title = 'Newsletter Signup 1';

	protected $description = 'Newsletter Signup Form with image';

	protected $category = 'ds-forms';

	protected $icon = 'email';

	protected $keywords = array( 'form', 'info box', 'cf7', 'mailchimp' );

	public function add_composer_fields(): array {
		return array(
			DS_Field::accordion( 'content_image_ac', array( 'label' => 'Content Image' ) ),
			DS_Field::image(
				'image',
				array(
					'label'        => 'Image',
					'preview_size' => 'medium',
				)
			),
			DS_Field::accordion( 'form_content_ac', array( 'label' => 'Form Content' ) ),
			DS_Field::text( 'form_pretitle', array( 'label' => 'Pretitle' ) ),
			DS_Field::text(
				'form_title',
				array(
					'label'            => 'Title',
					'ds_default_value' => 1,
				)
			),
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
						DS_Field_LayoutType::get( 1 ),
						DS_Field_ModuleGap::get(),
					),
				),
				true
			),
		);
	}
}
