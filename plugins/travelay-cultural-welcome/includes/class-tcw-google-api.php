<?php
/**
 * Shared Google Cloud API credentials (server-side only).
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Google_API
 */
class TCW_Google_API {

	/**
	 * Get Google API key — never expose to the frontend.
	 *
	 * Priority: wp-config constant → plugin settings.
	 *
	 * @return string
	 */
	public static function get_api_key() {
		if ( defined( 'TCW_GOOGLE_API_KEY' ) && TCW_GOOGLE_API_KEY ) {
			return (string) TCW_GOOGLE_API_KEY;
		}

		$settings = TCW_Settings::get();
		return isset( $settings['google_api_key'] ) ? (string) $settings['google_api_key'] : '';
	}

	/**
	 * Whether a Google API key is configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== self::get_api_key();
	}
}
