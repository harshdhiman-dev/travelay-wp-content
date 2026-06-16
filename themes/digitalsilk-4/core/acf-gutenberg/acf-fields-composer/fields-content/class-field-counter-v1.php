<?php
/**
 * Custom DS_Field
 *
 * @package DS_Theme
 */
class DS_Field_Counter_V1_Content extends DS_Field {

	/**
	 * Get
	 */
	public static function get(): array {
		return array(
			DS_Field::image(
				'image',
				array(
					'label'            => 'Image',
					'ds_default_value' => 1,
				)
			),
			DS_Field::text( 'pre_number_symbol', array( 'label' => 'Pre-Number symbol' ) ),
			DS_Field::number(
				'number',
				array(
					'label'            => 'Count Number',
					'ds_default_value' => 1,
				)
			),
			DS_Field::text( 'after_number_symbol', array( 'label' => 'After-Number symbol' ) ),
			DS_Field::text( 'title', array( 'label' => 'Title' ) ),
			DS_Field::textarea(
				'description',
				array(
					'label'     => 'Description',
					'rows'      => 3,
					'new_lines' => 'wpautop',
				)
			),
		);
	}
}
