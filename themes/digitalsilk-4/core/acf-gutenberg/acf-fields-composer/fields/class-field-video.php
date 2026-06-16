<?php

/**
 * Custom DS Field
 *
 * @package DS_Theme
 */
class DS_Field_Video extends DS_Field {

	/**
	 * Get
	 *
	 * @param bool $show_video_controls show video controls.
	 * @param bool $hide_external_video_poster_image hide external poster.
	 */
	public static function get( $show_video_controls = true, $hide_external_video_poster_image = true ): array {
		$poster_image_args = array(
			'label'        => 'Poster Image',
			'preview_size' => 'medium',
		);

		if ( $hide_external_video_poster_image ) {
			$poster_image_args['conditional_logic'] = array(
				array(
					array(
						'fieldPath' => 'video_source',
						'operator'  => '==',
						'value'     => 'internal',
					),
				),
			);
		}

		$video_fields = array(
			self::add_field(
				'button_group',
				'video_source',
				array(
					'label'   => 'Video Source',
					'choices' => array(
						'internal' => 'internal',
						'external' => 'external',
					),
				)
			),
			self::add_field(
				'oembed',
				'video_embed',
				array(
					'label'             => 'Video Embed',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'video_source',
								'operator'  => '==',
								'value'     => 'external',
							),
						),
					),
				)
			),
			self::add_field(
				'file',
				'video',
				array(
					'label'             => 'Video',
					'conditional_logic' => array(
						array(
							array(
								'fieldPath' => 'video_source',
								'operator'  => '==',
								'value'     => 'internal',
							),
						),
					),
				)
			),
			self::add_field( 'image', 'poster_image', $poster_image_args ),
			self::add_field(
				'true_false',
				'disable_lazy',
				array(
					'label' => 'Disable Lazy Load?',
					'ui'    => 1,
				)
			),
		);

		if ( $show_video_controls ) {
			$video_fields = array(
				...$video_fields,
				self::add_field(
					'true_false',
					'autoplay',
					array(
						'label'             => 'Autoplay Video?',
						'ui'                => 1,
						'conditional_logic' => array(
							array(
								array(
									'fieldPath' => 'video_source',
									'operator'  => '==',
									'value'     => 'internal',
								),
							),
						),
					)
				),
				self::add_field(
					'true_false',
					'hide_controls',
					array(
						'label'             => 'Hide Video Controls?',
						'ui'                => 1,
						'conditional_logic' => array(
							array(
								array(
									'fieldPath' => 'video_source',
									'operator'  => '==',
									'value'     => 'internal',
								),
								array(
									'fieldPath' => 'autoplay',
									'operator'  => '==',
									'value'     => 1,
								),
							),
						),
					)
				),
			);
		}

		return $video_fields;
	}
}
