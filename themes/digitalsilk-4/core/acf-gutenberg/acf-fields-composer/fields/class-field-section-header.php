<?php
/**
 * Custom DS_Field
 *
 * @package DS_Theme
 */
class DS_Field_Section_Header extends DS_Field {

	/**
	 * Get
	 */
	public static function get(): array {
		return array(
			DS_Field::accordion( 'module_header_accordion', array( 'label' => 'Section Header' ) ),
			DS_Field::text( 'pretitle', array( 'label' => 'Pretitle' ) ),
			DS_Field::text(
				'title',
				array(
					'label'            => 'Title',
					'ds_default_value' => 1,
				)
			),
			DS_Field::text( 'subtitle', array( 'label' => 'Subtitle' ) ),
			DS_Field::wysiwyg( 'description', array( 'label' => 'Description' ) ),
			DS_Field_CTAList::get(),
			DS_Field_TitleStyles::get(),
		);
	}
}
