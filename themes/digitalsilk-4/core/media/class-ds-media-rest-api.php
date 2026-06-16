<?php
/**
 * Media REST API class
 *
 * @package ds_theme
 */

if ( ! class_exists( 'Ds_Media_Rest_Api' ) ) {
	/**
	 * Media REST API class
	 */
	class Ds_Media_Rest_Api {

		/**
		 * Constructor
		 */
		public function __construct() {
			// Add custom image sizes to REST API.
			// This is needed to expose all of the custom image sizes to the block editor.
			add_filter( 'block_editor_settings_all', [ $this, 'add_custom_image_sizes_to_rest_api' ], 10, 1 );
		}

		/**
		 * Format image size name
		 *
		 * @param string $size Image size.
		 */
		public function format_image_size_name( $size ) {
			// Bail early if no size is provided.
			if ( ! $size ) {
				return '';
			}
			$name = str_replace( '-', ' ', $size );
			$name = str_replace( '_', ' ', $name );
			return ucwords( $name );
		}

		/**
		 * Add custom image sizes to REST API
		 *
		 * @param array $editor_settings Editor settings.
		 */
		public function add_custom_image_sizes_to_rest_api( $editor_settings ) {
			if ( empty( $editor_settings['imageSizes'] ) ) {
				$editor_settings['imageSizes'] = [];
			}

			global $_wp_additional_image_sizes;
			if ( ! empty( $_wp_additional_image_sizes ) && is_array( $_wp_additional_image_sizes ) ) {
				foreach ( $_wp_additional_image_sizes as $size => $attributes ) {
					// Skip Gravity Forms image sizes that start with "gform".
					if ( strpos( $size, 'gform' ) === 0 ) {
						continue;
					}

					$editor_settings['imageSizes'][] = [
						'slug'   => $size,
						'name'   => $this->format_image_size_name( $size ),
						'width'  => (int) $attributes['width'],
						'height' => (int) $attributes['height'],
					];
				}
			}

			return $editor_settings;
		}
	}
	new Ds_Media_Rest_Api();
}
