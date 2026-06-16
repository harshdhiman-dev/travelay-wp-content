<?php
/**
 * Autoload plugin class files in dependency order.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Loader
 */
class TCW_Loader {

	/**
	 * Require all plugin includes once.
	 */
	public static function init() {
		$files = array(
			// Core data & assets.
			'class-tcw-gestures.php',
			'class-tcw-avatar-library.php',
			'class-tcw-confetti.php',
			'class-tcw-premium-avatars.php',
			'class-tcw-lottie-generator.php',
			'class-tcw-lottie.php',
			'class-tcw-rive.php',
			'class-tcw-avatar-engine.php',
			// Settings & integrations.
			'class-tcw-settings.php',
			'class-tcw-google-api.php',
			'class-tcw-voice.php',
			'class-tcw-voice-catalog.php',
			// Profiles & matching.
			'class-tcw-profile.php',
			'class-tcw-page-sync.php',
			'class-tcw-geo.php',
			'class-tcw-booking-guard.php',
			'class-tcw-compatibility.php',
			'class-tcw-matcher.php',
			// Runtime.
			'class-tcw-frontend.php',
			'class-tcw-admin.php',
			'class-tcw-seeder.php',
			'class-tcw-plugin.php',
		);

		foreach ( $files as $file ) {
			$path = TCW_PLUGIN_DIR . 'includes/' . $file;
			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
	}
}
