<?php
/**
 * Gutenberg Functions.
 *
 * @package DST
 */

if ( ! function_exists( 'ds_theme_generate_extra_atts' ) ) {
	/**
	 * Generates extra attributes to pass to the block
	 * This should be used to pass an array to get_block_wrapper_attributes
	 *
	 * @param array    $attributes Block attributes.
	 * @param WP_Block $block      Block object.
	 *
	 * @return array
	 */
	function ds_theme_generate_extra_atts( array $attributes = [], ?WP_Block $block = null ): array {
		$extra_atts    = [];
		$css_classes   = [];
		$inline_styles = [];

		// Check for block supports.
		$supports_containers = false;
		$supports_gaps       = false;
		if ( $block && $block instanceof WP_Block ) {
			$registered          = WP_Block_Type_Registry::get_instance()->get_registered( $block->name );
			$supports_containers = $registered->supports['dsContainers'] ?? false;
			$supports_gaps       = $registered->supports['dsGapControl'] ?? false;
		}

		// Convert classes into an array.
		if ( isset( $attributes['class'] ) && ! empty( $attributes['class'] ) ) {
			// Split the class string into an array and filter out container classes.
			$class_array = explode( ' ', $attributes['class'] );
			foreach ( $class_array as $class ) {
				$css_classes[] = trim( $class );
			}
		}

		// Preserve existing styles if already set.
		if ( isset( $attributes['style'] ) && is_string( $attributes['style'] ) ) {
			$inline_styles[] = trim( $attributes['style'], ';' );
		}

		// Add container class based on our High Order Component.
		if ( $supports_containers ) {
			$container_class       = ( isset( $attributes['dsContainer'] ) ) ? (string) trim( $attributes['dsContainer'] ) : '';
			// If container is explicitly set to empty string (Full Width), map it to 'container-fluid' to mirror editor behaviour.
			if ( '' === $container_class ) {
				$container_class = 'container-fluid';
			}
			$container_custom_size = ( isset( $attributes['dsContainerCustom'] ) ) ? (string) trim( $attributes['dsContainerCustom'] ) : '';
			$container_gaps        = ( isset( $attributes['dsContainerSideGap'] ) ) ? (bool) $attributes['dsContainerSideGap'] : true;
			$container_align       = ( isset( $attributes['dsContainerAlign'] ) ) ? (string) trim( $attributes['dsContainerAlign'] ) : 'center';

			if ( isset( $attributes['dsContainer'] ) ) {
				$css_classes[] = $container_class;
				if ( 'container-custom' === $container_class ) {
					// Add custom container styles if the class is 'container-custom'.
					if ( $container_custom_size ) {
						$inline_styles[] = '--l-container-width:' . esc_attr( $container_custom_size );
					}
					// Add alignment class if the class is 'container-custom' and alignment is set.
					if ( $container_align ) {
						$css_classes[] = 'container-' . $container_align;
					}
				}
			} else {
				$css_classes[] = 'container';
			}
			// Add a class to remove container gaps.
			if ( ! $container_gaps ) {
				$css_classes[] = 'no-side-padding';
			}
		}

		// Add gaps based on our High Order Component.
		if ( $supports_gaps ) {
			$gap_output = ds_theme_generate_gap_styles( $attributes['dsPadding'] ?? [] );
			if ( ! empty( $gap_output['classes'] ) ) {
				$css_classes = array_merge( $css_classes, $gap_output['classes'] );
			}
			if ( ! empty( $gap_output['styles'] ) ) {
				$inline_styles = array_merge( $inline_styles, $gap_output['styles'] );
			}
		}

		// ---------------
		// Wrap them all together.
		// ---------------

		// Add classes to extra attributes if we have any.
		if ( ! empty( $css_classes ) ) {
			$extra_atts['class'] = implode( ' ', array_unique( array_filter( $css_classes ) ) );
		}

		// Add styles to extra attributes if we have any.
		if ( ! empty( $inline_styles ) ) {
			$extra_atts['style'] = implode( '; ', $inline_styles ) . ';';
		}

		return $extra_atts;
	}
}

if ( ! function_exists( 'ds_theme_generate_anchor' ) ) {
	/**
	 * Generates an id HTML tag based on the block attributes.
	 *
	 * @param array $attributes Attributes for the anchor tag.
	 *
	 * @return string The generated anchor tag.
	 */
	function ds_theme_generate_anchor( array $attributes = array() ) {
		$anchor = ( isset( $attributes['anchor'] ) && ! empty( $attributes['anchor'] ) ) ? (string) sanitize_title( $attributes['anchor'] ) : '';
		if ( ! $anchor ) {
			return '';
		}
		echo 'id="' . esc_attr( $anchor ) . '"';
	}
}

if ( ! function_exists( 'get_modules_asset_info' ) ) {
	/**
	 * Get asset info from extracted asset files.
	 * Used for getting version and dependencies and urls from wp-scipts compiled files.
	 *
	 * @param string $location Location of the asset, inside of the build folder.
	 * @param string $attribute Optional attribute to get. Can be version, dependency or url. Pass an empty value to get the full asset array.
	 *
	 * @return string|array
	 */
	function get_modules_asset_info( $location, $attribute = null ) {
		// Bail early if type is not provided.
		if ( empty( $location ) ) {
			return [];
		}

		// Check if asset file exists.
		$modules_folder_url  = DS_THEME_REACT_DIST_URL . $location . '/';
		$modules_folder_path = DS_THEME_REACT_DIST_DIR . $location . '/';
		$asset_path          = $modules_folder_path . '/index.asset.php';
		$asset_url           = '';
		$asset_style         = '';
		$asset_array         = [];
		if ( file_exists( $asset_path ) ) {
			$asset_array = require $asset_path;
		} else {
			return [];
		}

		// Return an asset url if requested.
		$asset_url = esc_url_raw( $modules_folder_url . '/index.js' );
		if ( 'url' === $attribute ) {
			return $asset_url;
		}

		// If we are looking for a style, we need to return the css file.
		$asset_style = esc_url_raw( $modules_folder_url . '/style-index.css' );
		if ( 'style' === $attribute ) {
			return $asset_style;
		}

		// Return version or dependencies.
		if ( $attribute && ! empty( $attribute ) && isset( $asset_array[ $attribute ] ) ) {
			return $asset_array[ $attribute ];
		}
		return array_merge(
			[
				'url'   => $asset_url,
				'style' => $asset_style,
				'path'  => $asset_path,
			],
			$asset_array
		);
	}
}

if ( ! function_exists( 'ds_generate_tab_styles' ) ) {
	/**
	 * Generate tab inline styles for our DS tabs gutenberg block.
	 *
	 * @param array $tab_styles Array containig all of the tab styles from our widget.
	 *
	 * @return string containing all of the inline styles.
	 */
	function ds_generate_tab_styles( $tab_styles = array() ) {
		// Bail early.
		if ( ! $tab_styles || ! is_array( $tab_styles ) || empty( array_filter( $tab_styles ) ) ) {
			return array();
		}

		$styles_array = array();
		foreach ( $tab_styles as $property => $value ) {
			$variable_name = '';
			if ( ! $value || ( ( is_array( $value ) && empty( array_filter( $value ) ) ) ) ) {
				continue;
			}
			// If our value is a string/bool/int, then we just need to convert it to proper css variable.
			if ( ! is_array( $value ) ) {
				$variable_name                  = '--' . ds_convert_camel_case_to_kebab_case( $property );
				$styles_array[ $variable_name ] = $value;
			} else {
				// If our value is an array, we need to extract the values from it.
				foreach ( $value as $value_propery => $sub_value ) {
					// Skip if there is no value.
					if ( ! $sub_value ) {
						continue;
					}
					if ( ! is_array( $sub_value ) ) {
						$variable_name                  = '--' . ds_convert_camel_case_to_kebab_case( $property ) . '-' . $value_propery;
						$styles_array[ $variable_name ] = $sub_value;
					} else {
						// If our sub-value is an array, we need to extract the actual values from it.
						foreach ( $sub_value as $sub_value_property => $sub_value_val ) {
							// Skip if there is no value.
							if ( ! $sub_value_val ) {
								continue;
							}
							$variable_name                  = '--' . ds_convert_camel_case_to_kebab_case( $property ) . '-' . $value_propery . '-' . $sub_value_property;
							$styles_array[ $variable_name ] = $sub_value_val;
						}
					}
				}
			}
		}

		if ( $styles_array ) {
			// Convert styles array to a styles string.
			$formatted_styles = array_map(
				function ( $key, $value ) {
					return "$key: $value";
				},
				array_keys( $styles_array ),
				$styles_array
			);

			return implode( '; ', $formatted_styles ) . ';';
		}

		return '';
	}
}

if ( ! function_exists( 'ds_theme_generate_gap_styles' ) ) {
	/**
	 * Generates gap-related classes and inline styles based on dsPadding High Order Component.
	 *
	 * @param array $ds_padding Block's dsPadding attribute value (can be partial or empty).
	 * @return array {
	 *     @type array $classes CSS classes to apply.
	 *     @type array $styles  Inline styles to apply.
	 * }
	 */
	function ds_theme_generate_gap_styles( array $ds_padding = [] ): array {
		$classes = [];
		$styles  = [];

		// Define fallback default for each direction.
		$default_padding = [
			'type'    => 'none',
			'desktop' => '',
			'mobile'  => '',
		];

		// Define default gaps for each side.
		$default_gaps = [
			'top'    => 'gt',
			'bottom' => 'gb',
		];

		foreach ( $default_gaps as $side => $prefix ) {
			$padding = $ds_padding[ $side ] ?? $default_padding;
			$type    = $padding['type'] ?? 'default';
			$desktop = $padding['desktop'] ?? '';
			$mobile  = $padding['mobile'] ?? '';

			switch ( $type ) {
				case 'small':
					$classes[] = "{$prefix}-s";
					break;
				case 'default':
					$classes[] = $prefix;
					break;
				case 'large':
					$classes[] = "{$prefix}-l";
					break;
				case 'custom':
					$classes[] = "{$prefix}-custom";

					if ( ! empty( $desktop ) ) {
						$styles[] = "--{$prefix}-custom: " . esc_attr( $desktop );
					}
					if ( ! empty( $mobile ) ) {
						$styles[] = "--{$prefix}-custom-mobile: " . esc_attr( $mobile );
					}
					break;
			}
		}

		return [
			'classes' => $classes,
			'styles'  => $styles,
		];
	}
}

/**
 * Inject dsEffects data attributes into the first wrapper element of a block.
 * Data attributes need to be added like this, because get_block_wrapper_attributes() does not support data attributes.
 *
 * @param string $block_content The rendered block HTML.
 * @param array  $block         The full block data (name, attrs, etc.).
 * @return string Modified block HTML with data-viewport* attributes.
 */
function ds_effects_render_block_data_attributes( $block_content, $block ) {
	$ds_effects  = isset( $block['attrs']['dsEffects'] ) ? $block['attrs']['dsEffects'] : [];
	$effect_type = isset( $ds_effects['type'] ) ? $ds_effects['type'] : '';

	// Only proceed if the block has a chosen effect.
	if ( ! $effect_type || ! is_array( $ds_effects ) || empty( array_filter( $ds_effects ) ) ) {
		return $block_content;
	}

	$effect_repeat     = isset( $ds_effects['repeat'] ) && ! empty( $ds_effects['repeat'] ) ? 'true' : 'false';
	$effect_margin     = isset( $ds_effects['margin'] ) ? sanitize_text_field( $ds_effects['margin'] ) : '';
	$effect_threashold = isset( $ds_effects['threashold'] ) ? $ds_effects['threashold'] : '';
	$effect_custom     = isset( $ds_effects['custom'] ) ? sanitize_text_field( $ds_effects['custom'] ) : '';

	// Build our data-viewport attributes.
	$data = [
		'data-viewport'        => 'true',
		'data-viewport-repeat' => $effect_repeat,
	];
	if ( $effect_type ) {
		$data['data-viewport-effect'] = $effect_type;
	}
	if ( $effect_margin ) {
		$data['data-viewport-margin'] = $effect_margin;
	}
	if ( $effect_threashold ) {
		$data['data-viewport-threshold'] = $effect_threashold;
	}
	if ( $effect_custom ) {
		$data['data-viewport-effect'] = $effect_custom;
	}

	// Create a processor for the block's HTML.
	$processor = new \WP_HTML_Tag_Processor( $block_content );

	// Advance to the first tag (any tag name) and inject attrs.
	if ( $processor->next_tag() ) {
		foreach ( $data as $attr => $value ) {
			$processor->set_attribute( $attr, $value );
		}
	}

	// Casting to string returns the updated HTML.
	return (string) $processor;
}

add_filter( 'render_block', 'ds_effects_render_block_data_attributes', 10, 2 );

if ( ! function_exists( 'ds_get_decoration_styles' ) ) {
	/**
	 * Generates an array of CSS variable declarations for a decoration item.
	 *
	 * @param array $decoration The decoration data array (with position, display, size).
	 * @return array List of individual CSS variable declarations as strings.
	 */
	function ds_get_decoration_styles( $decoration ) {
		$styles  = [];
		$devices = [ 'desktop', 'tablet', 'mobile' ];

		foreach ( $devices as $device ) {
			// Position (x/y as left/top).
			if (
				isset( $decoration['position'][ $device ]['x'] ) &&
				isset( $decoration['position'][ $device ]['y'] )
			) {
				$x = round( $decoration['position'][ $device ]['x'] * 100, 2 );
				$y = round( $decoration['position'][ $device ]['y'] * 100, 2 );

				$styles[] = "--c-decoration-position-{$device}-left: {$x}%";
				$styles[] = "--c-decoration-position-{$device}-top: {$y}%";
			}

			// Display.
			$visible  = $decoration['display'][ $device ] ?? true;
			$styles[] = "--c-decoration-display-{$device}: " . ( $visible ? 'block' : 'none' );

			// Size (width and height).
			if ( isset( $decoration['size'][ $device ]['width'] ) && '' !== $decoration['size'][ $device ]['width'] ) {
				$styles[] = "--c-decoration-width-{$device}: {$decoration['size'][ $device ]['width']}";
			}
			if ( isset( $decoration['size'][ $device ]['height'] ) && '' !== $decoration['size'][ $device ]['height'] ) {
				$styles[] = "--c-decoration-height-{$device}: {$decoration['size'][ $device ]['height']}";
			}
			if ( isset( $decoration['rotate'] ) && '' !== $decoration['rotate'] ) {
				$styles[] = "--c-decoration-rotate: {$decoration['rotate']}";
			}
		}

		return $styles;
	}
}

if ( ! function_exists( 'ds_render_decorations' ) ) {
	/**
	 * Renders decorative images or custom elements inside wrapper divs with inline styles.
	 *
	 * @param array $decorations Array of decoration items.
	 */
	function ds_render_decorations( $decorations = [] ) {
		if ( empty( $decorations ) || ! is_array( $decorations ) ) {
			return;
		}

		echo '<div class="c-decoration">';

		foreach ( $decorations as $decoration ) {
			$type        = $decoration['type'] ?? 'image';
			$style_array = ds_get_decoration_styles( $decoration );
			$style_attr  = implode( '; ', array_map( 'esc_attr', $style_array ) );

			echo '<div class="c-decoration__item" style="' . esc_attr( $style_attr ) . '">';

			if ( 'custom' === $type && ! empty( $decoration['className'] ) ) {
				echo '<div class="' . esc_attr( $decoration['className'] ) . '"></div>';
			} elseif ( ! empty( $decoration['media']['id'] ) ) {
				echo ds_generate_image( $decoration['media']['id'], 'full', '', '', true, true ); // phpcs:ignore
			}

			echo '</div>';
		}

		echo '</div>';
	}
}

if ( ! function_exists( 'ds_get_background_media_styles' ) ) {
	/**
	 * Generates CSS custom properties for background media styles.
	 * Includes desktop and mobile values, but omits mobile if same as desktop.
	 *
	 * @param array $item Background media item.
	 * @return array Array with 'styles' and 'classes'.
	 */
	function ds_get_background_media_styles( $item ) {
		$desktop = $item['desktop'] ?? [];
		$mobile  = $item['mobile'] ?? [];

		$desktop_styles = [
			'focal' => ds_focal_to_percent( $desktop['focal']['x'] ?? 0.5 ) . ' ' . ds_focal_to_percent( $desktop['focal']['y'] ?? 0.5 ),
			'fixed' => ! empty( $desktop['fixed'] ) ? 'fixed' : 'scroll',
			'size'  => $desktop['size'] ?? 'auto',
			'width' => $desktop['width'] ?? 'auto',
		];

		$mobile_styles = [
			'focal' => ds_focal_to_percent( $mobile['focal']['x'] ?? 0.5 ) . ' ' . ds_focal_to_percent( $mobile['focal']['y'] ?? 0.5 ),
			'fixed' => ! empty( $mobile['fixed'] ) ? 'fixed' : 'scroll',
			'size'  => $mobile['size'] ?? 'auto',
			'width' => $mobile['width'] ?? 'auto',
		];

		$styles = [
			'--dst--bg-desktop-focal' => $desktop_styles['focal'],
			'--dst--bg-desktop-fixed' => $desktop_styles['fixed'],
			'--dst--bg-desktop-size'  => $desktop_styles['size'],
			'--dst--bg-desktop-width' => $desktop_styles['width'],
		];

		// Only print mobile styles if different from desktop.
		foreach ( $mobile_styles as $key => $value ) {
			if ( $value !== $desktop_styles[ $key ] ) {
				$styles[ '--dst--bg-mobile-' . $key ] = $value;
			}
		}

		$inline = [];
		foreach ( $styles as $key => $value ) {
			$inline[] = esc_attr( $key ) . ': ' . esc_attr( $value );
		}

		// Generate classes based on properties.
		$classes = [];
		if ( ! empty( $desktop['fixed'] ) ) {
			$classes[] = 'is-fixed';
		}

		return [
			'styles'  => implode( '; ', $inline ),
			'classes' => $classes,
		];
	}
}

if ( ! function_exists( 'ds_render_background_media' ) ) {
	/**
	 * Renders background media (images/videos) using <picture> or <video> elements.
	 * Applies responsive logic for mobile vs. desktop.
	 *
	 * @param array $items Background media array.
	 */
	function ds_render_background_media( $items = [] ) {
		if ( empty( $items ) || ! is_array( $items ) ) {
			return;
		}

		echo '<div class="c-bg">';

		foreach ( $items as $index => $item ) {
			$desktop      = $item['desktop'] ?? [];
			$mobile       = $item['mobile'] ?? [];
			$desktop_type = $desktop['media']['type'] ?? '';
			$mobile_type  = $mobile['media']['type'] ?? '';
			$same_media   = ( $desktop['media']['id'] ?? null ) && ( ( $mobile['media']['id'] ?? null ) === $desktop['media']['id'] );
			$lazy_attr    = ! empty( $item['lazy'] ) ? ' loading="lazy" decoding="async"' : '';

			// Get styles and classes from the background media.
			$bg_attributes      = ds_get_background_media_styles( $item );
			$style_attr         = $bg_attributes['styles'];
			$additional_classes = ! empty( $bg_attributes['classes'] ) ? ' ' . implode( ' ', $bg_attributes['classes'] ) : '';
			$wrapper_class      = 'c-bg__item -item' . ( $index + 1 ) . $additional_classes;

			?>
			<div class="<?php echo esc_attr( $wrapper_class ); ?>" style="<?php echo esc_attr( $style_attr ); ?>">
				<?php if ( 'image' === $desktop_type && 'image' === $mobile_type ) : ?>
					<?php if ( $same_media ) : ?>
						<picture>
							<?php
							$media   = $desktop['media'];
							$sources = [];
							if ( isset( $media['sizes'] ) && is_array( $media['sizes'] ) ) {
								$sources = ds_is_assoc( $media['sizes'] ) ? array_values( $media['sizes'] ) : $media['sizes'];
								$sources = array_filter( $sources, static fn( $s ) => ! empty( $s['url'] ) && ! empty( $s['width'] ) );
								usort( $sources, static fn( $a, $b ) => $b['width'] <=> $a['width'] );
							}
							foreach ( $sources as $size ) {
								?>
								<source media="(min-width: <?php echo (int) $size['width']; ?>px)" srcset="<?php echo esc_url( $size['url'] ); ?>" type="<?php echo esc_attr( $media['mime'] ?? 'image/webp' ); ?>" />
								<?php
							}
							?>
							<img class="c-bg__media" src="<?php echo esc_url( $media['url'] ); ?>" alt="<?php echo esc_attr( $media['alt'] ?? '' ); ?>"<?php echo $lazy_attr; // phpcs:ignore ?> />
						</picture>
					<?php else : ?>
						<picture>
							<source media="(min-width: 769px)" srcset="<?php echo esc_url( $desktop['media']['url'] ); ?>" />
							<source media="(max-width: 768px)" srcset="<?php echo esc_url( $mobile['media']['url'] ); ?>" />
							<img class="c-bg__media" src="<?php echo esc_url( $desktop['media']['url'] ); ?>" alt="<?php echo esc_attr( $desktop['media']['alt'] ?? '' ); ?>"<?php echo $lazy_attr; // phpcs:ignore ?> />
						</picture>
					<?php endif; ?>

				<?php elseif ( 'video' === $desktop_type && $same_media ) : ?>
					<video class="c-bg__media" autoplay muted loop<?php echo $lazy_attr; // phpcs:ignore ?>>
						<source src="<?php echo esc_url( $desktop['media']['url'] ); ?>" type="<?php echo esc_attr( $desktop['media']['mime'] ?? 'video/mp4' ); ?>" />
					</video>

				<?php elseif ( 'video' === $desktop_type && 'image' === $mobile_type ) : ?>
					<video class="c-bg__media -visible-desktop -hidden-mobile" autoplay muted loop<?php echo $lazy_attr; // phpcs:ignore  ?>>
						<source src="<?php echo esc_url( $desktop['media']['url'] ); ?>" type="<?php echo esc_attr( $desktop['media']['mime'] ?? 'video/mp4' ); ?>" />
					</video>
					<picture class="-visible-mobile -hidden-desktop">
						<source media="(max-width: 768px)" srcset="<?php echo esc_url( $mobile['media']['url'] ); ?>" />
						<img class="c-bg__media" src="<?php echo esc_url( $mobile['media']['url'] ); ?>" alt="<?php echo esc_attr( $mobile['media']['alt'] ?? '' ); ?>"<?php echo $lazy_attr; // phpcs:ignore  ?> />
					</picture>

				<?php elseif ( 'image' === $desktop_type && 'video' === $mobile_type ) : ?>
					<picture class="-visible-desktop -hidden-mobile">
						<source media="(min-width: 769px)" srcset="<?php echo esc_url( $desktop['media']['url'] ); ?>" />
						<img class="c-bg__media" src="<?php echo esc_url( $desktop['media']['url'] ); ?>" alt="<?php echo esc_attr( $desktop['media']['alt'] ?? '' ); ?>"<?php echo $lazy_attr; // phpcs:ignore  ?> />
					</picture>
					<video class="c-bg__media -visible-mobile -hidden-desktop" autoplay muted loop<?php echo $lazy_attr; // phpcs:ignore  ?>>
						<source src="<?php echo esc_url( $mobile['media']['url'] ); ?>" type="<?php echo esc_attr( $mobile['media']['mime'] ?? 'video/mp4' ); ?>" />
					</video>
				<?php endif; ?>

			</div>
			<?php
		}

		echo '</div>';
	}
}
