<?php
//phpcs:ignoreFile

/**
 * Fetches global button settings and defines icon image, icon position, and button style to use globally
 */
class DS_Buttons extends DS_Settings {

	/**
	 * List of available icons (by category)
	 *
	 * @var array|string[][]
	 */
	public static array $icon_library = [
		'arrows' => [
			'lib-icon-arrow1',
			'lib-icon-arrow2',
			'lib-icon-arrow3',
			'lib-icon-arrow4',
		],
		'other'  => [
			'lib-icon-envelop',
			'lib-icon-plus',
			'lib-icon-minus',
			'lib-icon-phone',
			'lib-icon-pin',
			'lib-icon-search',
			'lib-icon-x',
		],
	];

	public function __construct() {
		$this->set_global( 'buttons_icon', self::get_icon() );
		$this->set_global(
			'buttons_link_icon',
			self::get_icon(
				null,
				array(
					'is_icon'              => 'is_link_btn_icon',
					'icon_type'            => 'link-btn-icon-type',
					'icon'                 => 'link-btn-icon',
					'icon_library'         => 'link-btn-icon-library',
					'icon_project_library' => 'link-btn-project-icon-library',
				)
			)
		);
		$this->set_global( 'buttons_icon_reverse', $this->get_setting( 'buttons_styles_button_icon_reversed' ) );
		$this->set_global( 'buttons_icon_position', self::get_icon_position() );
		$this->set_global( 'buttons_type', $this->get_setting( 'buttons_template_buttons_type' ) );
		$this->set_global( 'buttons_style_type', self::get_btn_style_type() );
	}

	/**
	 * Gets icon selected on option page to use globally
	 *
	 * @param array|null $link
	 * @param array      $options_names
	 *
	 * @return string
	 */
	public static function get_icon( array $link = null, $options_names = [] ): string {
		$icon = '';

		$options_names = wp_parse_args(
			$options_names,
			array(
				'is_icon'              => 'is_button_icon',
				'icon_type'            => 'button-icon-type',
				'icon'                 => 'button-icon',
				'icon_library'         => 'button-icon-library',
				'icon_project_library' => 'button-project-icon-library',
			)
		);
		$is_icon       = $link['is_custom_icon'] ?? get_option( 'options_buttons_styles_' . $options_names['is_icon'] );

		if ( ! empty( $is_icon ) ) {
			$icon_type = $link['icon_type'] ?? get_option( 'options_buttons_styles_' . $options_names['icon_type'] );

			if ( $icon_type === 'custom' ) {
				$icon_id  = $link['icon'] ?? get_option( 'options_buttons_styles_' . $options_names['icon'] );
				$icon_url = ( ! empty( $icon_id ) ) ? wp_get_attachment_image_url( $icon_id, 'full' ) : '';

				$icon = ds_get_embedded_image( $icon_id, $icon_url );
			} elseif ( $icon_type === 'library' ) {
				$icon_library  = $link['icon-library'] ?? get_option( 'options_buttons_styles_' . $options_names['icon_library'] );
				$icon_selected = ! empty( $link['icon-library'] ) ? $link[ $icon_library ] : get_option( 'options_buttons_styles_' . $icon_library );
				$icon          = get_svg(
					array(
						'icon'  => $icon_selected,
						'class' => '',
					)
				);
			} elseif ( 'project-library' === $icon_type ) {
				$icon_id  = $link['icon'] ?? get_option( 'options_buttons_styles_' . $options_names['icon_project_library'] );
				$icon_url = ( ! empty( $icon_id ) ) ? wp_get_attachment_image_url( $icon_id, 'full' ) : '';

				$icon = ds_get_embedded_image( $icon_id, $icon_url );
			}
		}

		return $icon;
	}

	/**
	 * Gets icon position selected on option page to use globally
	 *
	 * @param string|null $link_direction
	 *
	 * @return string
	 */
	public static function get_icon_position( string $link_direction = null ): string {
		$icon_position = $link_direction ?? get_option( 'options_buttons_styles_flex-direction' );

		return $icon_position === 'row' ? 'right' : 'left';
	}

	/**
	 * Gets button-style-type
	 *
	 * @return string
	 */
	public static function get_btn_style_type() {
		$btn_style_type = get_option( 'options_buttons_styles_button-style-type' );

		return $btn_style_type === 'oblique' ? 'is-oblique' : '';
	}
}

add_action(
	'after_setup_theme',
	function () {
		new DS_Buttons();
	}
);
