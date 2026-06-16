<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_ScrollDown extends DS_Field {

	/**
	 * Get
	 */
	public static function get(): array {
		if ( ! get_field( 'scroll_down_enable', 'option' ) ) {
			return array();
		}

		$choices = array(
			'0'      => 'disable',
			'global' => 'global',
		);

		return array(
			DS_Field::button_group(
				'scroll_down',
				array(
					'label'   => 'Scroll down',
					'choices' => $choices,
				)
			),
			DS_Field::button_group(
				'scroll_down_position',
				array(
					'label'             => 'Scroll down position',
					'choices'           => array(
						'default'   => 'default',
						'sd-left'   => 'left',
						'sd-center' => 'center',
						'sd-right'  => 'right',
					),
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'scroll_down',
								'operator'  => '==',
								'value'     => 'global',
							),
						),
					),
				)
			),
		);
	}
}
