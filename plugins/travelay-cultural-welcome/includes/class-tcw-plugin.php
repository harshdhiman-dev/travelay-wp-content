<?php
/**
 * Main plugin bootstrap.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Plugin
 */
class TCW_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var TCW_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return TCW_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Initialize plugin modules.
	 */
	public function init() {
		TCW_Profile::register();
		TCW_Rive::register();
		TCW_Avatar_Engine::register();
		TCW_Voice::register();
		TCW_Voice_Catalog::register();
		TCW_Admin::register();
		TCW_Page_Sync::register();
		TCW_Compatibility::register();
		TCW_Frontend::register();
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'travelay-cultural-welcome',
			false,
			dirname( TCW_PLUGIN_BASENAME ) . '/languages'
		);
	}
}
