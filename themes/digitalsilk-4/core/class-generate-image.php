<?php
/**
 * Generate Image Class
 *
 * @package DS_Theme
 */

if ( ! class_exists( 'DS_GenerateImage' ) ) {

	/**
	 * Class responsible for image generation
	 */
	class DS_GenerateImage {

		/**
		 * Generate image based on attachment ID
		 *
		 * @param int    $image_id attachment id.
		 * @param string $size desired image size (register new if needed).
		 * @param string $class add class or multiple classes to <img>, class1 class2 class3.
		 * @param string $fallback_size placeholder fallback if $image_id is null, empty, false. example '300x300'.
		 * @param bool   $lazy should the image be lazy loaded.
		 * @param bool   $html_output output <img> (true) or just the image URL (false), use false for background images.
		 *
		 * @return string
		 */
		public function get_image( $image_id, $size = 'full', $class = '', $fallback_size = '', $lazy = true, $html_output = true ): string {
			$img_src         = '';
			$available_sizes = self::get_image_sizes();

			if ( empty( $size ) ) {
				$size = 'full';
			}

			if ( 'full' === $size && empty( $fallback_size ) ) {
				$fallback_size = '1600x900';
			} else {
				$fallback_size = $available_sizes[ $size ][0] . 'x' . $available_sizes[ $size ][1];
			}

			if ( $image_id ) {
				// get requested image source and dimensions.
				$img_src_attributes = wp_get_attachment_image_src( $image_id, $size );

				if ( $img_src_attributes ) {
					$img_src = $img_src_attributes[0];

					// set 30x30 default SVG size, if dimensions not set in SVG file.
					$img_width  = ( 1 === $img_src_attributes[1] ) ? '30' : $img_src_attributes[1];
					$img_height = ( 1 === $img_src_attributes[2] ) ? '30' : $img_src_attributes[2];
				}
			}

			// start to generate actual <img> tag.
			$html        = '';
			$lazy_attr   = '';
			$srcset_attr = '';
			$class_attr  = '';
			$css_classes = array();

			if ( '' !== $img_src || '' !== $fallback_size ) {
				$src_attr = 'src="' . $img_src . '"';

				if ( $class ) {
					$css_classes[] = $class;
				}

				if ( $lazy ) {
					$lazy_attr     = 'loading="lazy"';
					$css_classes[] = 'lazy';
				}

				if ( ! empty( $css_classes ) ) {
					$class_attr = 'class="' . implode( ' ', $css_classes ) . '"';
				}

				if ( $img_src ) {
					// get image alt or image title.
					$alt_attr_meta = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
					$alt           = $alt_attr_meta ? $alt_attr_meta : get_the_title( $image_id );

					$html .= '<img ' . $class_attr . ' ' . $lazy_attr . ' ' . $src_attr . ' ' . $srcset_attr . ' alt="' . $alt . '" width="' . $img_width . '" height="' . $img_height . '"/>';
				} elseif ( $fallback_size ) {
					$placeholder_img    = get_field( 'default_image_field_type', 'options' );
					$img_width          = '';
					$img_height         = '';
					$fallback_size_attr = explode( 'x', $fallback_size );

					// phpcs:ignore
					if ( in_array( $fallback_size_attr, $available_sizes ) && ! empty( $placeholder_img ) ) {
						$placeholder_img_attributes = wp_get_attachment_image_src( $placeholder_img['ID'], $size );

						if ( $placeholder_img_attributes ) {
							$img_src    = $placeholder_img_attributes[0];
							$img_width  = $placeholder_img_attributes[1];
							$img_height = $placeholder_img_attributes[2];
						}
					} elseif ( filter_var( $fallback_size, FILTER_VALIDATE_URL ) === false ) {
							$fallback_sizes = explode( 'x', $fallback_size );
						if ( $fallback_sizes ) {
							$img_width  = $fallback_sizes[0];
							$img_height = $fallback_sizes[1];
						}
							$img_src = 'https://via.placeholder.com/' . $fallback_size;
					} else {
						$img_src = $fallback_size;
					}

					$html .= '<img ' . $class_attr . ' src="' . $img_src . '" alt="placeholder" width="' . $img_width . '" height="' . $img_height . '" />';
				}
			}

			// return image source if no full <img> tag needed.
			if ( ! $html_output ) {
				return $img_src;
			}

			return $html;
		}

		/**
		 * Generate image url based on attachment ID
		 *
		 * @param int    $image_id attachment id.
		 * @param string $size desired image size (register new if needed).
		 *
		 * @return string
		 */
		public function get_image_url( $image_id, $size = 'full' ): string {
			return $this->get_image( $image_id, $size, '', '', false, false );
		}

		/**
		 * Get information about available image sizes
		 *
		 * @param string $size image size.
		 *
		 * @return array
		 */
		public static function get_image_sizes( string $size = '' ) {
			$wp_additional_image_sizes = wp_get_additional_image_sizes();

			$sizes                        = array();
			$get_intermediate_image_sizes = get_intermediate_image_sizes();

			// Create the full array with sizes and crop info.
			foreach ( $get_intermediate_image_sizes as $_size ) {
				if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
					$size_array      = array(
						get_option( $_size . '_size_w' ),
						get_option( $_size . '_size_h' ),
					);
					$sizes[ $_size ] = $size_array;
				} elseif ( isset( $wp_additional_image_sizes[ $_size ] ) ) {
					$sizes[ $_size ] = array(
						$wp_additional_image_sizes[ $_size ]['width'],
						$wp_additional_image_sizes[ $_size ]['height'],
					);
				}
			}

			// Get only 1 size if found.
			if ( $size ) {
				if ( isset( $sizes[ $size ] ) ) {
					return $sizes[ $size ];
				} else {
					return false;
				}
			}

			return $sizes;
		}


		/**
		 * Remove unused default image sizes
		 *
		 * WP default sizes:
		 * thumbnail, medium, medium_large, large, 1536x1536, 2048x2048
		 */
		public static function remove_sizes(): void {
			add_filter( 'fallback_intermediate_image_sizes', '__return_empty_array' );
			add_filter(
				'intermediate_image_sizes',
				function ( $sizes ) {
					return array_diff( $sizes, array( '1536x1536', '2048x2048' ) );
				}
			);
		}
	}

	DS_GenerateImage::remove_sizes();
}
