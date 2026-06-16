<?php
/**
 * Class generates module template classes and styles on render_callback level
 */

// phpcs:ignoreFile
#[AllowDynamicProperties]
class DS_ModuleDefaultSettings {
	/**
	 * Used for printing additional style properties in 'style' attribute
	 */
	private string $additionalStyles = '';

	public string $classNames = '';

	public string $data_attributes = '';

	public string $backgroundStyles = '';

	public string $backgroundMediaHTML = '';

	public string $container = '';

	public string $container_width = '';

	public function __construct() {
		$backgroundSettings = get_field( 'background' ) ?: array();
		$gapSettings        = get_field( 'layout_settings_module_gap' ) ?: array();
		$effectsSettings    = get_field( 'module_effects' ) ?: array();
		// pass only gap because if background type is not image, but it has values for image classes it will print them even if image is not set as type
		$classSettings           = empty( $backgroundSettings ) || ( empty( $backgroundSettings['bg_color_type'] ) && $backgroundSettings['bg_color_type'] !== 'image' )
			? $gapSettings
			: array_merge( $gapSettings, $backgroundSettings );
		$backgroundMediaSettings = get_field( 'media_background' ) ?: array();
		if ( ! empty( $backgroundMediaSettings ) ) {
			$classSettings = array_merge( $classSettings, $backgroundMediaSettings );
		}

		$this->classNames          = $this->build_classes( $classSettings );
		$this->data_attributes     = $this->build_effects_data_attributes( $effectsSettings );
		$this->backgroundStyles    = $this->build_background_styles( $backgroundSettings );
		$this->backgroundMediaHTML = $this->build_acf_background_html( $backgroundMediaSettings );

		$this->container_settings();
	}

	public function __get( $key ) {
		return $this->{$key} = get_field( $key ) ?? false;
	}

	public function get_styles(): string {
		return ! empty( $this->backgroundStyles ) || ! empty( $this->additionalStyles ) ? "style='{$this->additionalStyles}{$this->backgroundStyles}'" : '';
	}

	public function set_style( string $key, string $value ) {
		if ( empty( $key ) && empty( $value ) ) {
			return;
		}

		$this->additionalStyles .= "$key: $value;";
	}

	public function container_settings(): void {
		$this->container = get_field( 'layout_settings_container' ) ?: 'container';
		if ( $this->container === 'container-custom' ) {
			$containerMaxWidth = get_field( 'layout_settings_container_width' );

			if ( ! empty( $containerMaxWidth ) ) {
				$containerVarKey = '--l-container-width';
				$this->set_style( $containerVarKey, "{$containerMaxWidth}" );
				$this->container_width = ! empty( $containerMaxWidth ) ? "{$containerVarKey}: {$containerMaxWidth}" : '';
			}
		}
	}

	// TODO Eugene, Alex

	/**
	 * Build all data attributes based on chosen settings.
	 * Note: $key in foreach which is coming from ACF should match AOS attribute key.
	 *       - ex: If AOS key is data-aos-duration the $key should be 'duration'
	 */
	private function build_effects_data_attributes( $settings ): string {

		if ( empty( $settings['enabled'] ) ) {
			return '';
		}

		$data_attributes = '';
		foreach ( $settings as $key => $item ) {
			if ( empty( $item ) || $key === 'viewport-effect-custom' ) {
				continue;
			}

			switch ( $key ) {
				case 'enabled':
					$data_attributes .= " data-viewport='true'";
					break;
				case 'viewport-effect':
					$data_attributes .= " data-{$key}='" . ( $item === 'custom' && ! empty( $settings['viewport-effect-custom'] ) ? esc_attr( $settings['viewport-effect-custom'] ) : esc_attr( $item ) ) . "'";
					break;
				default:
					$data_attributes .= " data-{$key}='" . ( $item === true ? 'true' : esc_attr( $item ) ) . "'";
					break;
			}
		}

		return trim( $data_attributes );
	}

	private function build_classes( $settings ): string {
		if ( empty( $settings ) ) {
			return '';
		}
		$classes = '';
		foreach ( $settings as $key => $item ) {
			if ( $key === 'overlay_opacity' && intval( $item ) !== 0 ) {
				$classes .= ' has-overlay';
			}

			if ( $key === 'overlay_opacity_color' && $item !== 0 ) {
				$classes .= ' has-overlay-color';
			}

			if ( $key === 'bg_color_type' && $item === 'multiple' ) {
				$classes .= ' bg-multi-images';
			}

			if ( $key === 'invert_colors' && $item === true ) {
				$classes .= ' is-style-colors-inverted';
			}

			if ( $key === 'class_margin_top' && $item === 'mt-custom' && ! empty( $settings['margin_top_custom'] ) ) {
				$this->set_style( '--margin-top', $settings['margin_top_custom'] );
			}

			if ( $key === 'class_margin_bottom' && $item === 'mb-custom' && ! empty( $settings['margin_bottom_custom'] ) ) {
				$this->set_style( '--margin-bottom', $settings['margin_bottom_custom'] );
			}

			if ( empty( $item ) || strpos( $key, 'class_' ) === false ) {
				continue;
			}

			$classes .= " {$item}";
		}

		$customClasses   = get_field( 'ds_custom_settings_ds_classes' );
		$additionalClass = '';
		if ( ! empty( $customClasses ) ) {
			$additionalClass = ' ' . implode( ' ', $customClasses );
		}

		return ' ' . $classes . $additionalClass;
	}

	private function build_background_styles( $settings ): string {
		if ( empty( $settings ) ) {
			return '';
		}

		$styles = '';
		if ( in_array(
			     $settings['bg_color_type'],
			     array(
				     'image',
				     'color',
				     'multiple',
			     )
		     ) && ! empty( $settings['color'] ) ) {
			$styles .= "background-color: {$settings['color']};";
		}

		if ( ! empty( $settings['text_color'] ) ) {
			$styles .= "color: {$settings['text_color']};";
		}

		if ( $settings['bg_color_type'] === 'gradient' ) {
			$g_c_1  = empty( $settings['gradient_color_1'] ) ? 'transparent' : $settings['gradient_color_1'];
			$g_c_2  = empty( $settings['gradient_color_2'] ) ? 'transparent' : $settings['gradient_color_2'];
			$styles .= "background-image: linear-gradient(to {$settings['gradient_direction']}, {$g_c_1}, {$g_c_2});";
		} elseif ( $settings['bg_color_type'] === 'image' && ! empty( $settings['image'] ) ) {
			$styles .= "background-image: url({$settings['image']});";
		} elseif ( $settings['bg_color_type'] === 'multiple' && ! empty( $settings['multi_images'] ) && is_array( $settings['multi_images'] ) ) {
			$bg_image_val    = '';
			$bg_size_val     = '';
			$bg_position_val = '';
			$bg_repeat_val   = '';
			$items_num       = count( $settings['multi_images'] );
			foreach ( $settings['multi_images'] as $key => $image ) {
				$bg_image_val    .= "url({$image['image']})";
				$bg_size_val     .= ! empty( $image['background_size'] ) ? $image['background_size'] : 'auto';
				$bg_position_val .= ! empty( $image['background_position'] ) ? str_replace( '-', ' ', $image['background_position'] ) : '0 0';
				$bg_repeat_val   .= ! empty( $image['background_repeat'] ) ? str_replace( '_', ' ', $image['background_repeat'] ) : 'no-repeat';
				if ( ( $key + 1 ) !== $items_num ) {
					$bg_image_val    .= ',';
					$bg_size_val     .= ',';
					$bg_position_val .= ',';
					$bg_repeat_val   .= ',';
				}
			}

			$styles .= "background-image:{$bg_image_val};background-size:{$bg_size_val};background-position:{$bg_position_val};background-repeat:{$bg_repeat_val};";
		}

		return $styles;
	}

	public function build_acf_background_html( $settings ): string {
		if ( empty( $settings['media_background_type'] ) || ! in_array(
				$settings['media_background_type'],
				array(
					'image',
					'video',
				)
			) ) {
			return '';
		}

		if ( $settings['media_background_type'] === 'image' ) {
			ob_start();
			get_template_part(
				'templates/components/pictures/picture-banner',
				null,
				array(
					'image'          => $settings['media_image'],
					'mobile_image'   => $settings['media_mobile_image'],
					'image_fit'      => $settings['media_image_fit'],
					'image_position' => $settings['media_image_position'],
					'disable_lazy'   => $settings['disable_lazy'] ?? false,
				)
			);

			return ob_get_clean();
		} else {
			if ( $settings['media_video']['video_source'] === 'internal' && ! empty( $settings['media_video']['video'] ) ) {
				ob_start();
				echo '<div class="m-banner__media">';
				get_template_part(
					'templates/components/videos/video-box',
					null,
					array(
						'video'            => $settings['media_video']['video'],
						'poster_image'     => $settings['media_video']['poster_image'],
						'show_js_controls' => true,
						'hide_controls'    => $settings['media_video']['hide_controls'] ?? false,
						'autoplay'         => $settings['media_video']['autoplay'] ?? false,
						'disable_lazy'     => $settings['media_video']['disable_lazy'] ?? false,
					)
				);
				echo '</div>';

				return ob_get_clean();
			}

			if ( $settings['media_video']['video_source'] === 'external' && ! empty( $settings['media_video']['video_embed'] ) ) {
				ob_start();
				get_template_part(
					'templates/components/videos/video-embed',
					null,
					array(
						'iframe'       => $settings['media_video']['video_embed'],
						'class'        => 'm-banner__media',
						'disable_lazy' => $settings['media_video']['disable_lazy'] ?? false,
					)
				);

				return ob_get_clean();
			}
		}

		return '';
	}
}
