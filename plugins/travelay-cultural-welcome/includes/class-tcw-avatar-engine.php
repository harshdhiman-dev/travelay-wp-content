<?php
/**
 * Avatar asset resolution: Rive → Lottie → Premium SVG.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Avatar_Engine
 */
class TCW_Avatar_Engine {

	/**
	 * Register REST route for dynamic Lottie JSON.
	 */
	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'rest_pre_serve_request', array( __CLASS__, 'serve_clean_lottie_json' ), 10, 4 );
	}

	/**
	 * Serve Lottie JSON without stray theme/plugin output corrupting the body.
	 *
	 * @param bool             $served  Whether the request was served.
	 * @param WP_HTTP_Response $result  Response object.
	 * @param WP_REST_Request  $request Request.
	 * @param WP_REST_Server   $server  Server.
	 * @return bool
	 */
	public static function serve_clean_lottie_json( $served, $result, $request, $server ) {
		$route = $request instanceof WP_REST_Request ? $request->get_route() : '';
		if ( ! is_string( $route ) || 0 !== strpos( $route, '/tcw/v1/lottie/' ) ) {
			return $served;
		}

		if ( ! $result instanceof WP_HTTP_Response ) {
			return $served;
		}

		$data = $result->get_data();
		if ( ! is_array( $data ) ) {
			return $served;
		}

		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		status_header( $result->get_status() );
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset', 'UTF-8' ) );
		header( 'Cache-Control: public, max-age=86400' );
		echo wp_json_encode( $data );
		return true;
	}

	/**
	 * REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'tcw/v1',
			'/lottie/(?P<slug>[a-z0-9\-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_lottie' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Serve Lottie JSON.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_lottie( $request ) {
		$slug = sanitize_title( $request['slug'] );
		$data = TCW_Lottie::get_animation_data( $slug );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'tcw_no_lottie', 'Lottie not found', array( 'status' => 404 ) );
		}

		$response = new WP_REST_Response( $data, 200 );
		$response->header( 'Content-Type', 'application/json' );
		$response->header( 'Cache-Control', 'public, max-age=86400' );
		return $response;
	}

	/**
	 * Build frontend avatar manifest.
	 *
	 * @param array<string, mixed> $profile Profile.
	 * @return array<string, mixed>
	 */
	public static function get_manifest( $profile ) {
		$slug    = isset( $profile['location_slug'] ) ? sanitize_title( $profile['location_slug'] ) : '';
		$gesture = isset( $profile['gesture'] ) ? TCW_Gestures::sanitize( $profile['gesture'] ) : 'wave';

		if ( 'all-country-test' === $slug ) {
			$slug = 'india';
		}

		$settings    = TCW_Settings::get();
		$rive_url    = TCW_Rive::get_file_url( $slug );
		$lottie_url = TCW_Lottie::get_url( $slug );

		$states       = self::gesture_states( $gesture );
		$tap_gestures = self::build_tap_gestures( $gesture, $slug, $profile );
		$rive_config  = TCW_Rive::get_frontend_config( $profile );

		$manifest = array(
			'slug'         => $slug,
			'gesture'      => $gesture,
			'riveUrl'      => $rive_url,
			'lottieUrl'    => $lottie_url,
			'hasRive'      => (bool) $rive_url,
			'hasLottie'    => true,
			'states'       => $states,
			'tapGestures'  => $tap_gestures,
			'renderer'     => $settings['avatar_renderer'],
			'enableLottie' => (bool) $settings['enable_lottie'],
			'enableRive'   => (bool) $settings['enable_rive'],
			'soundMotif'   => TCW_Confetti::get_profile_for_slug( $slug )['motif'] ?? 'default',
		);

		if ( $rive_config ) {
			$manifest['riveConfig'] = $rive_config;
		}

		return $manifest;
	}

	/**
	 * Ordered gesture keys cycled when visitors tap "Tap to greet again".
	 *
	 * @param string $gesture Primary profile gesture.
	 * @param string $slug    Location slug.
	 * @return string[]
	 */
	public static function tap_gesture_keys( $gesture, $slug ) {
		$sequences = array(
			'namaste'      => array( 'namaste', 'wave', 'bow', 'nod' ),
			'bow'          => array( 'bow', 'nod', 'namaste', 'wave' ),
			'wave'         => array( 'wave', 'open_welcome', 'nod', 'hand_heart' ),
			'hand_heart'   => array( 'hand_heart', 'wave', 'nod', 'open_welcome' ),
			'open_welcome' => array( 'open_welcome', 'wave', 'hand_heart', 'nod' ),
			'nod'          => array( 'nod', 'wave', 'bow', 'namaste' ),
		);

		$keys = isset( $sequences[ $gesture ] ) ? $sequences[ $gesture ] : array( 'wave', 'nod', 'bow' );

		/**
		 * Filter the tap gesture rotation for a profile.
		 *
		 * @param string[] $keys    Gesture keys in play order.
		 * @param string   $gesture Primary gesture.
		 * @param string   $slug    Location slug.
		 */
		return apply_filters( 'tcw_avatar_tap_gesture_keys', $keys, $gesture, $slug );
	}

	/**
	 * Frontend tap gesture definitions (Rive triggers + SVG states + labels).
	 *
	 * @param string $gesture Primary gesture.
	 * @param string $slug    Location slug.
	 * @return array<int, array<string, string>>
	 */
	public static function build_tap_gestures( $gesture, $slug, $profile = null ) {
		if ( is_array( $profile ) && TCW_Rive::file_exists( $slug ) ) {
			$config = TCW_Rive::get_frontend_config( $profile );
			if ( $config && ! empty( $config['tapTriggers'] ) ) {
				$items = array();
				foreach ( $config['tapTriggers'] as $trigger ) {
					$key       = sanitize_title( $trigger );
					$items[]   = array(
						'key'         => $key ? $key : 'tap',
						'label'       => $trigger,
						'riveTrigger' => $trigger,
						'svgState'    => self::gesture_key_to_svg_state( $gesture ),
					);
				}

				return apply_filters( 'tcw_avatar_tap_gestures', $items, $gesture, $slug );
			}
		}

		$labels = TCW_Gestures::all();
		$items  = array();

		foreach ( self::tap_gesture_keys( $gesture, $slug ) as $key ) {
			$svg_state = self::gesture_key_to_svg_state( $key );
			$items[]   = array(
				'key'         => $key,
				'label'       => isset( $labels[ $key ]['label'] ) ? (string) $labels[ $key ]['label'] : ucfirst( str_replace( '_', ' ', $key ) ),
				'riveTrigger' => self::gesture_key_to_rive_trigger( $key ),
				'svgState'    => $svg_state,
			);
		}

		/**
		 * Filter tap gesture payloads passed to the avatar engine.
		 *
		 * @param array<int, array<string, string>> $items   Gesture definitions.
		 * @param string                            $gesture Primary gesture.
		 * @param string                            $slug    Location slug.
		 */
		return apply_filters( 'tcw_avatar_tap_gestures', $items, $gesture, $slug );
	}

	/**
	 * Map gesture key to premium SVG state class suffix.
	 *
	 * @param string $key Gesture key.
	 * @return string
	 */
	public static function gesture_key_to_svg_state( $key ) {
		$map = array(
			'hand_heart'   => 'heart',
			'open_welcome' => 'welcome',
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : $key;
	}

	/**
	 * Map gesture key to Rive state-machine trigger name.
	 *
	 * @param string $key Gesture key.
	 * @return string
	 */
	public static function gesture_key_to_rive_trigger( $key ) {
		$map = array(
			'hand_heart'   => 'heart',
			'open_welcome' => 'welcome',
		);

		return isset( $map[ $key ] ) ? $map[ $key ] : $key;
	}

	/**
	 * Legacy SVG state machine sequence.
	 *
	 * @param string $gesture Primary gesture.
	 * @return string[]
	 */
	private static function gesture_states( $gesture ) {
		$map = array(
			'namaste'      => array( 'idle', 'namaste', 'smile', 'idle' ),
			'bow'          => array( 'idle', 'bow', 'smile', 'idle' ),
			'wave'         => array( 'idle', 'wave', 'smile', 'idle' ),
			'hand_heart'   => array( 'idle', 'heart', 'smile', 'idle' ),
			'open_welcome' => array( 'idle', 'welcome', 'smile', 'idle' ),
			'nod'          => array( 'idle', 'nod', 'smile', 'idle' ),
		);

		return isset( $map[ $gesture ] ) ? $map[ $gesture ] : array( 'idle', 'wave', 'smile', 'idle' );
	}
}
