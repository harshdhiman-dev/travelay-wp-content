<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_Background extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $is_super_admin super admin check.
	 */
	public static function get( $is_super_admin = false ): string {
		return self::add_field(
			'group',
			'background',
			array(
				'label'      => 'Background',
				'layout'     => 'block',
				'sub_fields' => array(
					self::color_picker( 'text_color', array( 'label' => 'Text Color' ) ),
					self::true_false(
						'invert_colors',
						array(
							'label' => 'Invert Colors?',
							'ui'    => 1,
						)
					),
					self::add_field(
						'button_group',
						'bg_color_type',
						array(
							'label'   => 'Background type',
							'choices' => array(
								'color'    => 'color',
								'gradient' => 'gradient',
								'image'    => 'image',
								'multiple' => 'multi images',
							),
						)
					),
					self::add_field(
						'color_picker',
						'color',
						array(
							'label'             => 'Background Color',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'color',
									),
								),
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'image',
									),
								),
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'multiple',
									),
								),
							),
						)
					),
					self::add_field(
						'color_picker',
						'gradient_color_1',
						array(
							'label'             => 'Gradient color 1',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'gradient',
									),
								),
							),
						)
					),
					self::add_field(
						'color_picker',
						'gradient_color_2',
						array(
							'label'             => 'Gradient color 2',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'gradient',
									),
								),
							),
						)
					),
					DS_Field_BackgroundGradientDirection::get(
						'gradient_direction',
						array(
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'gradient',
									),
								),
							),
						)
					),
					self::add_field(
						'image',
						'image',
						array(
							'label'             => 'Image',
							'return_format'     => 'url',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'image',
									),
								),
							),
						)
					),
					DS_Field_BackgroundSize::get(
						'class_background_size',
						array(
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'image',
									),
								),
							),
						)
					),
					DS_Field_BackgroundPosition::get(
						'class_background_position',
						array(
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'image',
									),
								),
							),
						)
					),
					DS_Field_BackgroundRepeat::get(
						'class_background_repeat',
						array(
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'image',
									),
								),
							),
						)
					),
					DS_Field_BackgroundFixed::get(
						'class_background_fixed',
						array(
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'image',
									),
								),
							),
						)
					),
					self::add_field(
						'repeater',
						'multi_images',
						array(
							'label'             => 'Multiple Background',
							'sub_fields'        => array(
								self::add_field(
									'image',
									'image',
									array(
										'label'         => 'Image',
										'return_format' => 'url',
									)
								),
								self::add_field(
									'button_group',
									'background_size',
									array(
										'label'   => 'Background size',
										'choices' => array(
											''        => 'full',
											'cover'   => 'cover',
											'contain' => 'contain',
										),
									)
								),
								self::add_field(
									'select',
									'background_position',
									array(
										'label'   => 'Background position',
										'choices' => array(
											''             => '----',
											'center'       => 'center',
											'bottom'       => 'bottom',
											'left'         => 'left',
											'left-bottom'  => 'left-bottom',
											'left-top'     => 'left-top',
											'right'        => 'right',
											'right-bottom' => 'right-bottom',
											'right-top'    => 'right-top',
											'top'          => 'top',
										),
									)
								),
								self::add_field(
									'select',
									'background_repeat',
									array(
										'label'   => 'Background repeat',
										'choices' => array(
											'no-repeat'    => 'no-repeat',
											'repeat'       => 'repeat',
											'repeat-x'     => 'repeat-x',
											'repeat-y'     => 'repeat-y',
											'repeat_round' => 'round',
											'repeat_space' => 'space',
										),
									)
								),

							),
							'button_label'      => 'Add Image',
							'layout'            => 'block',
							'conditional_logic' => array(
								array(
									array(
										'fieldPath' => 'bg_color_type',
										'operator'  => '==',
										'value'     => 'multiple',
									),
								),
							),
						)
					),
				),
			),
			$is_super_admin
		);
	}
}
