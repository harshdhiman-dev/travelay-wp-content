<?php
/**
 * Default theme shortcodes.
 * Can be used for "copyright" field
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_ThemeShortcodes' ) ) {
	/**
	 * Class responsible for custom shortcodes
	 */
	class DS_ThemeShortcodes {
		/**
		 * Construct
		 */
		public function __construct() {
			add_shortcode( 'day', array( $this, 'day' ) );
			add_shortcode( 'month', array( $this, 'month' ) );
			add_shortcode( 'year', array( $this, 'year' ) );
			add_shortcode( 'copyright', array( $this, 'copyright' ) );
			add_shortcode( 'copyright_date', array( $this, 'copyright_date' ) );
		}

		/**
		 * Display current month
		 */
		public function month() {
			// phpcs:ignore
			return date( 'm' );
		}

		/**
		 * Display current day
		 */
		public function day() {
			// phpcs:ignore
			return date( 'd' );
		}

		/**
		 * Display current year
		 */
		public function year() {
			// phpcs:ignore
			return date( 'Y' );
		}

		/**
		 * Display copyright symbol
		 */
		public function copyright() {
			return '&copy;';
		}

		/**
		 * Display date with copyright symbol
		 */
		public function copyright_date() {
			// phpcs:ignore
			return date( 'Y' ) . ' &copy;';
		}
	}

	new DS_ThemeShortcodes();
}
