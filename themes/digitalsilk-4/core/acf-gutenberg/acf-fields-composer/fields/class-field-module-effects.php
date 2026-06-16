<?php

/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ModuleEffects extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'group',
			'module_effects',
			array(
				'label'        => 'Effects',
				'sub_fields'   => array(
					self::add_field(
						'true_false',
						'enabled',
						array(
							'label'         => 'Enabled',
							'instructions'  => '<a href="https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver" target="_blank">Read More</a>',
							'default_value' => 0,
							'ui'            => 1,
							'width'         => '40%',
						)
					),
					self::add_field(
						'true_false',
						'viewport-repeat',
						array(
							'label'             => 'Repeatable',
							'ui'                => 1,
							'instructions'      => 'Check if animation is repeatable.',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
						)
					),
					self::add_field(
						'select',
						'viewport-effect',
						array(
							'label'             => 'Effect',
							'instructions'      => 'Choose predefined effect or you can create custom CSS effect by adding custom class.',
							'choices'           => array(
								'none'             => '--',
								'custom'           => 'Custom',
								'fade'             => 'Fade',
								'fade-up'          => 'Fade Up',
								'fade-down'        => 'Fade Down',
								'fade-right'       => 'Fade Right',
								'fade-left'        => 'Fade Left',
								'zoom-in'          => 'Zoom In',
								'slide-up'         => 'Slide Up',
								'slide-down'       => 'Slide Down',
								'slide-right'      => 'Slide Right',
								'slide-left'       => 'Slide Left',
								'fade-in-seq'      => 'Fade In Sequence',
								'fade-in-slides'   => 'Fade In Slides',
								'animate-headings' => 'Animate Headings',
							),
							'default_value'     => 'none',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
						)
					),
					self::add_field(
						'text',
						'viewport-effect-custom',
						array(
							'label'             => 'Custom Effect',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'viewport-effect',
										'operator'  => '==',
										'value'     => 'custom',
									),
								),
							),
						)
					),
					self::add_field(
						'range',
						'viewport-threshold',
						array(
							'label'             => 'Threshold',
							'max'               => 1,
							'min'               => 0,
							'default_value'     => 0,
							'instructions'      => '<a href="https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/thresholds" target="_blank">Read More</a>',
							'step'              => 0.1,
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
						)
					),
					self::add_field(
						'text',
						'viewport-margin',
						array(
							'label'             => 'Root Margin',
							'default_value'     => '',
							'placeholder'       => '0px 0px 0px 0px',
							'instructions'      => '<a href="https://developer.mozilla.org/en-US/docs/Web/API/IntersectionObserver/rootMargin" target="_blank">Read More</a>',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'enabled',
										'operator'  => '==',
										'value'     => '1',
									),
								),
							),
						)
					),
				),
				'layout'       => 'block',
				'instructions' => '',
			),
			$is_super_admin
		);
	}
}
