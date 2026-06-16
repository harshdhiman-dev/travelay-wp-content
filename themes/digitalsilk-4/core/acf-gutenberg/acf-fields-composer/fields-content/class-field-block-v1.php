<?php
/**
 * Custom DS_Field
 *
 * @package DS_Theme
 */
class DS_Field_Block_V1_Content extends DS_Field {

	/**
	 * Get
	 *
	 * @param array $conditional_logic contains conditional logic.
	 */
	public static function get( $conditional_logic = array() ): array {
		return array(
			DS_Field::repeater(
				'cards_widget',
				array(
					'conditional_logic' => $conditional_logic,
					'label'             => 'Cards Content',
					'button_label'      => 'Add Card',
					'layout'            => 'block',
					'ds_default_value'  => 1,
					'sub_fields'        => array(
						DS_Field::accordion(
							'card_item_ac',
							array(
								'label' => 'Card item',
							)
						),
						DS_Field::image(
							'image',
							array(
								'label'            => 'Image',
								'ds_default_value' => 1,
							)
						),
						DS_Field::select(
							'image_size',
							array(
								'label'         => 'Image size',
								'choices'       => array(
									'full'         => 'full',
									'large'        => 'large - 1024px',
									'medium_large' => 'medium_large - 768px',
									'ds_medium'    => 'medium - 400px',
									'ds_small'     => 'small (logo, icon)',
								),
								'default_value' => 'ds_medium',
							)
						),
						DS_Field::true_false(
							'with_stars',
							array(
								'label' => 'With Stars?',
								'ui'    => 1,
							)
						),
						DS_Field::range(
							'stars',
							array(
								'label'             => 'Stars',
								'step'              => .5,
								'min'               => .5,
								'max'               => 5,
								'default_value'     => 4.5,
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'with_stars',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
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
						DS_Field::true_false(
							'is_clickable',
							array(
								'label' => 'Clickable?',
								'ui'    => 1,
							)
						),
						DS_Field_CTAList::get(
							array(
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'is_clickable',
											'operator'  => '!=',
											'value'     => 1,
										),
									),
								),
							),
						),
						DS_Field::link(
							'component_link',
							array(
								'label'             => 'Component Link',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'is_clickable',
											'operator'  => '==',
											'value'     => 1,
										),
									),
								),
							)
						),
					),
				)
			),
		);
	}
}
