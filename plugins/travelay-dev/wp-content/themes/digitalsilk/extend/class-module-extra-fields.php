<?php
/**
 * Allows to add custom CSS classes to a Gutenberg modules
 * for "DSMP professional" OR "custom" projects.
 *
 * It prevents to use "Advanced -> Additional CSS class(es) field"
 */

// phpcs:ignoreFile

class DS_ModuleExtraFields {

	/**
	 * @key - module name
	 * @subarray - CSS classes list
	 * $module_classes = [
	 * 'content-1' => [
	 * 'custom-cssclass'   => 'Admin panel value',
	 * ]
	 * ]
	 */
	private array $module_classes = array(
		'wrapper'         => array(
			'wrapper-custom-v1' => 'wrapper-custom-v1',
			'has-overflow'      => 'has-overflow',
		),
		'tabs'            => array(
			'has-overflow' => 'has-overflow',
		),
		'accordion'            => array(
			'l-accordion-v1' => 'accordion-v1',
			'l-accordion-v2' => 'accordion-v2',
		),
		'content-1'       => array(
			'rcbl-custom-v1' => 'rcbl-custom-v1',
			'rcbl-custom-v2' => 'rcbl-custom-v2',
			'slider-mobile'  => 'slider-mobile',
			'content1 --v1' => 'Version 1', // Location cards
		),
		'circular-slider' => array(
			''          => 'none',
			'edge-cs'   => 'Lykan style',
			'center-cs' => 'Centered',
		),
		'cards-slider'    => array(
			'overflow-right' => 'overflow-right',
			'--v1' => 'Version 1', // Simple cards slider
			'--v2' => 'Version 2', // Testimonials cards slider
		),
		'contact'    => array(
			'--v2' => 'Version 2', // Contact module
		),
		'shortcode'    => array(
			'--v1' => 'Version 1', // Subscribe from
		),
	);

	/**
	 * @key - module name
	 * @subarray - CSS classes list, 1st value required as empty
	 * $module_classes = [
	 * 'content-1' => [
	 * ''   => 'none',
	 * 'custom-decor-class'   => 'Admin panel value',
	 * ]
	 * ]
	 */
	public static array $module_decoration_classes = array(
		'wrapper' => array(
			''           => 'none',
			'has-decor1' => 'has-decor1',
			'has-decor2' => 'has-decor2',
		),
	);

	public function __construct() {
		if ( empty( $this->module_classes ) ) {
			return;
		}

		add_action(
			'acf/init',
			function () {
				$this->add_fields();
			}
		);
	}

	public function add_fields() {
		if ( ! class_exists( 'ACFComposer\ACFComposer' ) ) {
			return;
		}

		foreach ( $this->module_classes as $module_name => $choices ) {
			$config = array(
				'name'       => "group_ds_extra_fields_{$module_name}",
				'title'      => 'Extra Fields',
				'fields'     => array(
					DS_Field::accordion(
						'ds_custom_project_acc',
						array(
							'label' => 'Project Custom Settings',
						),
						true
					),
					DS_Field::group(
						'ds_custom_settings',
						array(
							'label'      => '',
							'layout'     => 'block',
							'sub_fields' => array(
								DS_Field::checkbox(
									'ds_classes',
									array(
										'label'         => 'Class',
										'choices'       => $choices,
										'default_value' => '',
										'multiple'      => 1,
										'ui'            => 1,
										'layout'        => 'horizontal',
									)
								),
							),
						),
						true
					),
				),
				'menu_order' => 4,
				'location'   => array(
					array(
						array(
							'param'    => 'block',
							'operator' => '==',
							'value'    => "acf/{$module_name}",
						),
					),
				),
			);

			ACFComposer\ACFComposer::registerFieldGroup( $config );
		}
	}
}

new DS_ModuleExtraFields();
