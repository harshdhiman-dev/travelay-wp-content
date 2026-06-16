<?php
/**
 * Custom DS_Field
 *
 * @package DS_Theme
 */
class DS_Field_Block_V2_5_Content extends DS_Field {

	/**
	 * Get
	 */
	public static function get(): array {
		return array(
			DS_Field::accordion( 'media_content_ac', array( 'label' => 'Media Content' ) ),
			DS_Field_MixedGallery_V1_Content::get(),
			DS_Field::true_false(
				'has_image_description',
				array(
					'label' => 'Add Image Description?',
					'ui'    => 1,
				)
			),
			DS_Field::textarea(
				'image_description',
				array(
					'label'             => 'Image Description',
					'rows'              => 2,
					'new_lines'         => 'wpautop',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'has_image_description',
								'operator'  => '==',
								'value'     => 1,
							),
						),
					),
				)
			),
		);
	}
}
