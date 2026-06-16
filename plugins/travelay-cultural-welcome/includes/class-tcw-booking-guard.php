<?php
/**
 * Suppress Cultural Welcome during Amadex flight search / booking flow.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Booking_Guard
 */
class TCW_Booking_Guard {

	/**
	 * Amadex shortcodes that indicate a transactional page.
	 *
	 * @var string[]
	 */
	private static $booking_shortcodes = array(
		'amadex_flight_results',
		'amadex_flight_booking',
		'amadex_payment',
		'amadex_booking_confirmation',
	);

	/**
	 * Default booking-flow page slugs on Travelay.
	 *
	 * @return string[]
	 */
	public static function default_slugs() {
		return array(
			'flight-results',
			'flight-results-new',
			'flight-booking',
			'payment-page',
			'complete-payment',
			'booking-confirmation',
		);
	}

	/**
	 * URI path fragments that indicate booking flow.
	 *
	 * @return string[]
	 */
	public static function default_path_fragments() {
		return array(
			'/flight-results',
			'/flight-booking',
			'/booking-confirmation',
			'/payment-page',
			'/complete-payment',
		);
	}

	/**
	 * Whether the current request is part of the flight booking funnel.
	 *
	 * @return bool
	 */
	public static function is_booking_flow_request() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		/**
		 * Short-circuit booking-flow detection.
		 *
		 * @param bool|null $is_booking_flow Null to use built-in detection.
		 */
		$filtered = apply_filters( 'tcw_is_booking_flow', null );
		if ( null !== $filtered ) {
			return (bool) $filtered;
		}

		if ( ! self::amadex_is_active() && ! apply_filters( 'tcw_booking_guard_force', false ) ) {
			return false;
		}

		if ( self::matches_path() ) {
			return true;
		}

		if ( self::matches_queried_page() ) {
			return true;
		}

		if ( self::page_contains_booking_shortcode() ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether Amadex booking integration appears active on this site.
	 *
	 * @return bool
	 */
	public static function amadex_is_active() {
		if ( defined( 'AMADEX_VERSION' ) ) {
			return true;
		}

		$general = get_option( 'amadex_general_settings', array() );
		return is_array( $general ) && ! empty( $general );
	}

	/**
	 * Whether a post should be excluded from sync (booking funnel pages).
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	public static function is_excluded_booking_post( $post ) {
		if ( ! $post instanceof WP_Post || ! self::amadex_is_active() ) {
			return false;
		}

		$slugs = apply_filters( 'tcw_booking_flow_slugs', self::default_slugs() );
		$slugs = array_map( 'sanitize_title', array_filter( (array) $slugs ) );

		if ( in_array( $post->post_name, $slugs, true ) ) {
			return true;
		}

		$page_ids = array_filter( array_map( 'absint', self::booking_page_ids() ) );
		return in_array( (int) $post->ID, $page_ids, true );
	}

	/**
	 * Match request URI against known booking paths.
	 *
	 * @return bool
	 */
	private static function matches_path() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$uri = strtolower( $uri );
		if ( '' === $uri ) {
			return false;
		}

		$fragments = apply_filters( 'tcw_booking_flow_path_fragments', self::default_path_fragments() );
		foreach ( $fragments as $fragment ) {
			$fragment = strtolower( (string) $fragment );
			if ( '' !== $fragment && false !== strpos( $uri, $fragment ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Match current queried page slug or configured Amadex page IDs.
	 *
	 * @return bool
	 */
	private static function matches_queried_page() {
		$slugs = apply_filters( 'tcw_booking_flow_slugs', self::default_slugs() );
		$slugs = array_map( 'sanitize_title', array_filter( (array) $slugs ) );

		$page_ids = self::booking_page_ids();
		$page_ids = array_filter( array_map( 'absint', $page_ids ) );

		if ( is_singular( 'page' ) ) {
			$page_id = get_queried_object_id();
			if ( $page_id && in_array( $page_id, $page_ids, true ) ) {
				return true;
			}

			$post = get_post( $page_id );
			if ( $post && in_array( $post->post_name, $slugs, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Page IDs used by Amadex booking flow.
	 *
	 * @return int[]
	 */
	private static function booking_page_ids() {
		$ids = array();

		$general = get_option( 'amadex_general_settings', array() );
		if ( ! empty( $general['booking_confirmation_page'] ) ) {
			$ids[] = absint( $general['booking_confirmation_page'] );
		}

		/**
		 * Filter Amadex booking page IDs that should suppress Cultural Welcome.
		 *
		 * @param int[] $ids Page IDs.
		 */
		return apply_filters( 'tcw_booking_flow_page_ids', $ids );
	}

	/**
	 * Detect booking shortcodes in the current page content.
	 *
	 * @return bool
	 */
	private static function page_contains_booking_shortcode() {
		if ( ! is_singular() ) {
			return false;
		}

		$post = get_post( get_queried_object_id() );
		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}

		$content = (string) $post->post_content;
		foreach ( self::$booking_shortcodes as $shortcode ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				return true;
			}
		}

		return false;
	}
}
