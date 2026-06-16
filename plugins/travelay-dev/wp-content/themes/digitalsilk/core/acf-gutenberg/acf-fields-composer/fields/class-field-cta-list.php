<?php
/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_CTAList extends DS_Field {

	/**
	 * Get
	 *
	 * @param array $args arguments.
	 */
	public static function get( array $args = array() ): string {
		$sub_fields = array(
			self::add_field(
				'link',
				'link',
				array(
					'label' => 'Link',
				)
			),
			DS_Field_ButtonSize::get(),
			DS_Field_ButtonStyle::get(),
			DS_Field_ButtonIcon::get(),
			self::add_field(
				'group',
				'link_popup',
				array(
					'label'      => 'Popup',
					'layout'     => 'block',
					'sub_fields' => array(
						self::add_field(
							'button_group',
							'open_popup',
							array(
								'label'         => 'Open Popup',
								'choices'       => array(
									'disable' => 'disable',
									'content' => 'content',
									'video'   => 'video',
								),
								'default_value' => 'disable',
							)
						),
						self::add_field(
							'wysiwyg',
							'popup_content',
							array(
								'label'             => 'Popup Content',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'open_popup',
											'operator'  => '==',
											'value'     => 'content',
										),
									),
								),
							)
						),
						self::add_field(
							'button_group',
							'popup_video_type',
							array(
								'label'             => 'Popup video type',
								'choices'           => array(
									'url'  => 'url',
									'file' => 'file',
								),
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'open_popup',
											'operator'  => '==',
											'value'     => 'video',
										),
									),
								),
								'default_value'     => 'url',
							)
						),
						self::add_field(
							'url',
							'popup_video_url',
							array(
								'label'             => 'Video Link',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'popup_video_type',
											'operator'  => '==',
											'value'     => 'url',
										),
									),
								),
							)
						),
						self::add_field(
							'file',
							'popup_video_file',
							array(
								'label'             => 'Video File',
								'conditional_logic' => array(
									array(
										array(
											'fieldPath' => 'popup_video_type',
											'operator'  => '==',
											'value'     => 'file',
										),
									),
								),
							)
						),
					),
				)
			),
		);

		$default_fields = array(
			'label'        => '',
			'sub_fields'   => $sub_fields,
			'button_label' => 'Add link',
			'layout'       => 'block',
		);

		$fields = wp_parse_args( $args, $default_fields );

		return self::add_field( 'repeater', 'cta_list', $fields );
	}
}
