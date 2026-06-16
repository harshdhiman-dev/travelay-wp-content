<?php
/**
 * Gutenberg Helpers class.
 *
 * @package ds_theme
 */

if ( ! class_exists( 'Ds_Media_Helpers' ) ) {
	/**
	 * Our helper class
	 */
	class Ds_Media_Helpers {

		/**
		 * Constructor
		 */
		public function __construct() {}

		/**
		 * Convert image sizes to format ACF expects.
		 *
		 * @param array $sizes Array of image sizes.
		 *
		 * @return array
		 */
		public static function convert_sizes_to_acf_format( $sizes ) {
			$converted_sizes = [];

			if ( is_array( $sizes ) && ! empty( $sizes ) ) {
				foreach ( $sizes as $size_key => $size_data ) {
					if ( is_array( $size_data ) && isset( $size_data['url'] ) ) {
						$converted_sizes[ $size_key ] = $size_data['url'];
					}
				}
			}

			return $converted_sizes;
		}

		/**
		 * Convert dst-media image data to format our picture.php template expects.
		 *
		 * @param array $image_data_array Array of media data.
		 */
		public static function convert_image_data( $image_data_array ) {
			if ( ! $image_data_array || empty( $image_data_array ) || ! is_array( $image_data_array ) || ! isset( $image_data_array['imagePrimary']['id'] ) ) {
				return [];
			}
			// Check if lazy load is enabled or not.
			$lazy_load = (bool) $image_data_array['lazyLoad'];

			// Picture arguments.
			$picture_args = [
				'image'                   => [
					'ID'    => (int) $image_data_array['imagePrimary']['id'] ?? '',
					'id'    => (int) $image_data_array['imagePrimary']['id'] ?? '',
					'url'   => (string) $image_data_array['imagePrimary']['url'] ?? '',
					'alt'   => (string) $image_data_array['imagePrimary']['alt'] ?? '',
					'sizes' => self::convert_sizes_to_acf_format( $image_data_array['imagePrimary']['sizes'] ),
				],
				'image_size'              => ! empty( $image_data_array['imagePrimary']['size'] ) ? $image_data_array['imagePrimary']['size'] : 'full',
				'class_image'             => 'dst-media__src',
				'class_default_container' => '',
				'disable_lazy'            => ! $lazy_load,
			];

			// Add mobile image if available.
			if ( isset( $image_data_array['imagePrimaryMobile']['id'] ) && ! empty( $image_data_array['imagePrimaryMobile']['id'] && $image_data_array['imagePrimaryMobile']['sizes'] ) ) {
				$picture_args['mobile_image'] = [
					'ID'    => (int) $image_data_array['imagePrimaryMobile']['id'],
					'id'    => (int) $image_data_array['imagePrimaryMobile']['id'],
					'url'   => (string) $image_data_array['imagePrimaryMobile']['url'],
					'alt'   => (string) $image_data_array['imagePrimaryMobile']['alt'],
					'sizes' => self::convert_sizes_to_acf_format( $image_data_array['imagePrimaryMobile']['sizes'] ),
				];
			}

			return $picture_args;
		}

		/**
		 * Convert video data to format our video.php template expects.
		 *
		 * @param array $video_data_array Array of video data.
		 *
		 * @return array
		 */
		public static function convert_local_video_data( $video_data_array ) {
			if ( empty( $video_data_array ) || ! is_array( $video_data_array ) || empty( $video_data_array['videoLocal']['id'] ) ) {
				return [];
			}

			$video_local     = $video_data_array['videoLocal'];
			$video_mime_type = get_post_mime_type( $video_local['id'] );

			// Extract poster image if available.
			$poster_image = [];
			if ( ! empty( $video_local['poster']['id'] ) && ! empty( $video_local['poster']['url'] ) ) {
				$poster_image = [
					'id'  => (int) $video_local['poster']['id'],
					'url' => esc_url( $video_local['poster']['url'] ),
				];
			}

			// Construct the video args.
			return [
				'video'            => [
					'id'        => (int) $video_local['id'],
					'url'       => esc_url( $video_local['url'] ),
					'mime_type' => $video_mime_type ?? 'video/mp4', // Fallback to MP4 if not available.
				],
				'poster_image'     => $poster_image,
				'autoplay'         => ! empty( $video_local['autoplay'] ) ? (bool) $video_local['autoplay'] : false,
				'hide_controls'    => isset( $video_local['controls'] ) ? ! (bool) $video_local['controls'] : false, // Invert to match `hide_controls` behavior.
				'show_js_controls' => isset( $video_local['controls'] ) ? (bool) $video_local['controls'] : false,
				'disable_lazy'     => ! empty( $video_data_array['lazyLoad'] ) ? false : true, // Match lazy loading flag.
			];
		}

		/**
		 * Convert external video data to the format expected by the video.php template.
		 *
		 * @param array $video_data_array Array of video data.
		 * @return array Converted video data array.
		 */
		public static function convert_external_video_data( $video_data_array ) {
			if ( empty( $video_data_array ) || ! is_array( $video_data_array ) || empty( $video_data_array['videoExternal']['url'] ) ) {
				return [];
			}

			$video_external = $video_data_array['videoExternal'];

			// Extract embed HTML and URL.
			$embed_html = ! empty( $video_external['html'] ) ? $video_external['html'] : '';

			return [
				'embed_html'   => $embed_html,
				'disable_lazy' => ! empty( $video_data_array['lazyLoad'] ) ? false : true, // Match lazy loading flag.
			];
		}

		/**
		 * Get the video URL from the provided arguments.
		 *
		 * This function first checks if a local video URL is available. If not, it looks for
		 * an external video iframe HTML and extracts the src attribute.
		 *
		 * @param array $args The full arguments array, which may include 'videoLocal' or 'videoExternal'.
		 * @return string The video URL (local or extracted external URL) or an empty string if not found.
		 */
		public static function get_video_url( $args ) {
			// Check for a local video URL first.
			if ( ! empty( $args['videoLocal']['url'] ) ) {
				return esc_url( $args['videoLocal']['url'] );
			}

			// Check if an external video HTML exists.
			if ( ! empty( $args['videoExternal']['html'] ) ) {
				$iframe_html = $args['videoExternal']['html'];

				// Use regex to extract the src attribute from the iframe.
				if ( preg_match( '/<iframe[^>]+src=["\']([^"\']+)["\']/i', $iframe_html, $matches ) ) {
					return esc_url( $matches[1] );
				}
			}

			// Return empty string if no video source is found.
			return '';
		}

		/**
		 * Generate class names based on the media styles in the provided $args array.
		 *
		 * @param array $args The full arguments array, which includes the 'style' key.
		 * @return string Space-separated string of class names.
		 */
		public static function get_styles_classes( $args ) {
			if ( empty( $args['style'] ) || ! is_array( $args['style'] ) ) {
				return '';
			}

			$style_data = $args['style'];

			// Extract styles for desktop and mobile.
			$desktop = isset( $style_data['desktop'] ) ? $style_data['desktop'] : [];
			$mobile  = isset( $style_data['mobile'] ) ? $style_data['mobile'] : [];

			// Extract media fit and ratio values, falling back to default if not set.
			$desktop_media_fit   = isset( $desktop['mediaFit'] ) ? $desktop['mediaFit'] : 'cover';
			$mobile_media_fit    = isset( $mobile['mediaFit'] ) ? $mobile['mediaFit'] : 'cover';
			$desktop_media_ratio = isset( $desktop['mediaRatio'] ) ? $desktop['mediaRatio'] : 'default';
			$mobile_media_ratio  = isset( $mobile['mediaRatio'] ) ? $mobile['mediaRatio'] : 'default';

			// Generate class names.
			$classes = [
				'media-' . sanitize_html_class( $desktop_media_fit ),
				'media-' . sanitize_html_class( $mobile_media_fit ) . '-mobile',
				'r-' . sanitize_html_class( $desktop_media_ratio ),
				'r-' . sanitize_html_class( $mobile_media_ratio ) . '-mobile',
			];

			return implode( ' ', array_filter( $classes ) );
		}

		/**
		 * Generate inline styles for focal point positioning based on media styles.
		 *
		 * @param array $args The full arguments array, which includes the 'style' key.
		 * @return string Inline styles formatted for use in a style attribute.
		 */
		public static function get_styles_inline( $args ) {
			if ( empty( $args['style'] ) || ! is_array( $args['style'] ) ) {
				return '';
			}

			$style_data = $args['style'];

			// Extract focal points for desktop and mobile.
			$desktop_focal = isset( $style_data['desktop']['focalPoint'] ) ? $style_data['desktop']['focalPoint'] : [ 'x' => 0.5, 'y' => 0.5 ]; // phpcs:ignore
			$mobile_focal  = isset( $style_data['mobile']['focalPoint'] ) ? $style_data['mobile']['focalPoint'] : [ 'x' => 0.5, 'y' => 0.5 ]; // phpcs:ignore

			// Convert focal point values to percentages.
			$desktop_focal_str = ( $desktop_focal['x'] * 100 ) . '% ' . ( $desktop_focal['y'] * 100 ) . '%';
			$mobile_focal_str  = ( $mobile_focal['x'] * 100 ) . '% ' . ( $mobile_focal['y'] * 100 ) . '%';

			// Generate inline styles.
			$styles = [
				'--c-media__position'        => $desktop_focal_str,
				'--c-media__position-mobile' => $mobile_focal_str,
			];

			// Convert array to inline CSS string.
			$inline_styles = '';
			foreach ( $styles as $key => $value ) {
				$inline_styles .= $key . ': ' . esc_attr( $value ) . '; ';
			}

			return trim( $inline_styles );
		}
	}
	new Ds_Media_Helpers();
}
