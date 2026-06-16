<?php
/**
 * Custom DS_Field
 *
 * @package DS_Theme
 */
class DS_Field_Block_V2_Content extends DS_Field {

	/**
	 * Get
	 */
	public static function get(): array {
		return array(
			DS_Field::accordion( 'main_content_ac', array( 'label' => 'Main Content' ) ),
			DS_Field::image( 'main_icon', array( 'label' => 'Icon' ) ),
			DS_Field::text( 'pretitle', array( 'label' => 'Pretitle' ) ),
			DS_Field::text(
				'title',
				array(
					'label'            => 'Title',
					'ds_default_value' => 1,
				)
			),
			DS_Field::text( 'subtitle', array( 'label' => 'Subtitle' ) ),
			DS_Field::wysiwyg(
				'description',
				array(
					'label'            => 'Description',
					'ds_default_value' => 1,
				)
			),
			DS_Field_CTAList::get(),

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

			DS_Field::accordion( 'additional_content_ac', array( 'label' => 'Additional Content' ) ),
			DS_Field::repeater(
				'list',
				array(
					'label'        => 'List Items',
					'layout'       => 'block',
					'button_label' => 'Add List Item',
					'sub_fields'   => array(
						DS_Field::radio(
							'type',
							array(
								'label'         => 'Type',
								'choices'       => array(
									'label'         => 'Label',
									'link'          => 'Link',
									'phone'         => 'Phone',
									'address'       => 'Address',
									'working_hours' => 'Working Hours',
								),
								'default_value' => 'label',
								'layout'        => 'horizontal',
							)
						),
						DS_Field::image(
							'icon',
							array(
								'label'             => 'Icon',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'label',
										),
									),
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'link',
										),
									),
								),
							)
						),
						DS_Field::text(
							'label',
							array(
								'label' => 'Label',
							)
						),
						DS_Field::link(
							'link',
							array(
								'label'             => 'Link',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'link',
										),
									),
								),
							)
						),
						DS_Field::text(
							'phone',
							array(
								'label'             => 'Phone',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'phone',
										),
									),
								),
							)
						),
						DS_Field::text(
							'address',
							array(
								'label'             => 'Address',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'address',
										),
									),
								),
							)
						),
						DS_Field::url(
							'address_link',
							array(
								'label'             => 'Link',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'address',
										),
									),
								),
							)
						),
						DS_Field::repeater(
							'working_hours',
							array(
								'label'             => 'Working Hours',
								'sub_fields'        => array(
									DS_Field::text(
										'day',
										array(
											'label' => 'Day(s)',
										)
									),
									DS_Field::text(
										'status',
										array(
											'label' => 'Status',
										)
									),
								),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'type',
											'operator'  => '==',
											'value'     => 'working_hours',
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
