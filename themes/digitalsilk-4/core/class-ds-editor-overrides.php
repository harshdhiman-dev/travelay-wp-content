<?php
/**
 * Editor Overrides class.
 *
 * Overrides the default gutenberg settings for the editor.
 * You can list the settings in the console, like this:
 * wp.data.select( 'core/block-editor' ).getSettings()
 *
 * @package dstheme
 */

if ( ! class_exists( 'Ds_Editor_Overrides' ) ) {
	/**
	 * Editor Overrides class.
	 */
	class Ds_Editor_Overrides {
		/**
		 * Constructor.
		 */
		public function __construct() {
			// Remove the default settings and add custom colors.
			add_filter( 'block_editor_settings_all', [ $this, 'remove_default_settings' ], 10, 1 );
			// Add button default icon settings.
			add_filter( 'block_editor_settings_all', [ $this, 'add_button_icon_defaults' ], 11, 1 );
		}

		/**
		 * Remove all of the default settings from the block editor, only leaving our defined settings.
		 *
		 * @param array $editor_settings Editor settings.
		 */
		public function remove_default_settings( $editor_settings ) {

			// Check if we have features key.
			if ( ! isset( $editor_settings['__experimentalFeatures'] ) ) {
				$editor_settings['__experimentalFeatures'] = [];
			}

			// Clear the default font sizes.
			$editor_settings['__experimentalFeatures']['typography']['fontSizes']['default'] = [];

			// Remove default colors, duotones and gradients.
			$editor_settings['__experimentalFeatures']['color']['duotone']['default']   = [];
			$editor_settings['__experimentalFeatures']['color']['gradients']['default'] = [];
			$editor_settings['__experimentalFeatures']['color']['palette']['default']   = [];

			// Add secondary colors repeater to theme colors.
			$theme_alt_colors = get_field( 'alternative_colors', 'options' );
			if ( $theme_alt_colors && is_array( $theme_alt_colors ) ) {
				foreach ( $theme_alt_colors as $color_key => $color ) {
					// We already have secondary color 1 to 3, so we start from 4.
					$secondary_color_number = $color_key + 4;
					$editor_settings['__experimentalFeatures']['color']['palette']['theme'][] = [
						'name'  => __( 'Secondary', 'dstheme' ) . ' ' . $secondary_color_number,
						'slug'  => 'secondary-color-' . $secondary_color_number,
						'color' => 'var(--dst--secondary-color' . $secondary_color_number . ')',
					];
				}
			}

			// Remove default gradients.
			$editor_settings['gradients'] = [];

			return $editor_settings;
		}

		/**
		 * Add button icon defaults.
		 *
		 * @param array $editor_settings Editor settings.
		 */
		public function add_button_icon_defaults( $editor_settings ) {

			// Set the defautls.
			if ( ! isset( $editor_settings['__experimentalFeatures'] ) ) {
				$editor_settings['__experimentalFeatures'] = [];
			}

			$button_styles = get_field( 'buttons_styles', 'options' ) ?? [];
			$has_icon      = (bool) isset( $button_styles['is_button_icon'] ) ? $button_styles['is_button_icon'] : false;
			$icon_reversed = (bool) isset( $button_styles['button_icon_reversed'] ) ? $button_styles['button_icon_reversed'] : false;
			$icon_position = (string) isset( $button_styles['flex-direction'] ) ? $button_styles['flex-direction'] : 'row';
			$icon_source   = (string) isset( $button_styles['button-icon-type'] ) ? $button_styles['button-icon-type'] : 'library';
			$library_used  = (string) isset( $button_styles['button-icon-library'] ) ? $button_styles['button-icon-library'] : 'button-icon-library_arrows';
			$icon_value    = '';

			// Different values per sources.
			$source_library_value      = (string) isset( $button_styles['button-icon-library_arrows'] ) ? $button_styles['button-icon-library_arrows'] : 'lib-icon-arrow1';
			$source_other_value        = (string) isset( $button_styles['button-icon-library_other'] ) ? $button_styles['button-icon-library_other'] : 'lib-icon-envelope';
			$source_icon_library_value = (string) isset( $button_styles['button-project-icon-library'] ) ? $button_styles['button-project-icon-library'] : '';
			$source_custom_url         = (string) isset( $button_styles['button-icon'] ) ? $button_styles['button-icon'] : '';
			$source_custom_pid         = ( $source_custom_url ) ? attachment_url_to_postid( $source_custom_url ) : 0;
			$source_custom_value       = (string) ( $source_custom_url && $source_custom_pid ) ? $source_custom_pid : '';

			// Define different icon value based on icon source and library used.
			switch ( $icon_source ) {
				case 'library':
					$icon_value = ( 'button-icon-library_arrows' === $library_used ) ? $source_library_value : $source_other_value;
					break;
				case 'custom':
					$icon_value = $source_custom_value;
					break;
				case 'project-library':
					$icon_value = $source_icon_library_value;
					break;
				default:
					$icon_value = '';
					break;
			}

			// Create icon defaults.
			$icon_defaults = [
				'hasIcon'    => $has_icon,
				'isReversed' => $icon_reversed,
				'position'   => $icon_position,
				'value'      => $icon_value,
				'link'       => [],
			];

			// Extract different values for link icons.
			$link_has_custom_icon = (bool) isset( $button_styles['is_link_btn_icon'] ) ? $button_styles['is_link_btn_icon'] : false;
			if ( $link_has_custom_icon ) {
				$link_ico_source   = (string) ( isset( $button_styles['link-btn-icon-type'] ) ) ? $button_styles['link-btn-icon-type'] : 'library';
				$link_icon_library = (string) ( isset( $button_styles['link-btn-icon-library'] ) ) ? $button_styles['link-btn-icon-library'] : 'link-btn-icon-library_arrows';
				$link_icon_value   = '';

				// Different values per sources.
				$link_source_library_value = (string) ( isset( $button_styles['link-btn-icon-library_arrows'] ) ) ? $button_styles['link-btn-icon-library_arrows'] : 'lib-icon-arrow1';
				$link_source_other_value   = (string) ( isset( $button_styles['link-btn-icon-library_other'] ) ) ? $button_styles['link-btn-icon-library_other'] : 'lib-icon-envelope';
				$link_source_custom_url    = (string) ( isset( $button_styles['link-btn-icon'] ) ) ? $button_styles['link-btn-icon'] : '';
				$link_source_custom_pid    = ( $link_source_custom_url ) ? attachment_url_to_postid( $link_source_custom_url ) : 0;
				$link_source_custom_value  = (string) ( $link_source_custom_url && $link_source_custom_pid ) ? $link_source_custom_pid : '';

				// Define different icon value based on icon source and library used.
				switch ( $link_ico_source ) {
					case 'library':
						$link_icon_value = ( 'link-btn-icon-library_arrows' === $link_icon_library ) ? $link_source_library_value : $link_source_other_value;
						break;
					case 'custom':
						$link_icon_value = $link_source_custom_value;
						break;
					default:
						$link_icon_value = '';
						break;
				}

				// Add to icon defaults.
				$icon_defaults['link'] = [
					'hasIcon' => $link_has_custom_icon,
					'value'   => $link_icon_value,
				];
			}

			// Add to editor settings.
			// NOTE: We must add it to the exsitng settings key, we can't add a custom one.
			$editor_settings['__experimentalFeatures']['btnDefaults'] = $icon_defaults;

			return $editor_settings;
		}
	}
	new Ds_Editor_Overrides();
}
