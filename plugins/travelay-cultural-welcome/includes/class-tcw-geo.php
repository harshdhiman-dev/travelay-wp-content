<?php
/**
 * Visitor geolocation for IP-based welcome matching.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Geo
 */
class TCW_Geo {

	/**
	 * Resolve ISO 3166-1 alpha-2 country code for the current visitor.
	 *
	 * @return string Two-letter country code or empty string.
	 */
	public static function get_visitor_country_code() {
		/**
		 * Short-circuit country detection (e.g. for testing).
		 *
		 * @param string $country_code Country code or empty to auto-detect.
		 */
		$forced = apply_filters( 'tcw_ip_country_code', '' );
		if ( is_string( $forced ) && 2 === strlen( $forced ) ) {
			return strtoupper( $forced );
		}

		$from_header = self::country_from_proxy_headers();
		if ( $from_header ) {
			return $from_header;
		}

		$ip = self::get_client_ip();
		if ( ! $ip || ! self::is_public_ip( $ip ) ) {
			return '';
		}

		$cached = get_transient( self::cache_key( $ip ) );
		if ( is_string( $cached ) && 2 === strlen( $cached ) ) {
			return strtoupper( $cached );
		}

		$detected = self::detect_country_from_ip( $ip );
		if ( $detected ) {
			set_transient( self::cache_key( $ip ), $detected, DAY_IN_SECONDS );
			return $detected;
		}

		return '';
	}

	/**
	 * Read country from CDN / reverse-proxy headers (no external API).
	 *
	 * @return string
	 */
	private static function country_from_proxy_headers() {
		$headers = array(
			'HTTP_CF_IPCOUNTRY',
			'HTTP_X_COUNTRY_CODE',
			'HTTP_GEOIP_COUNTRY_CODE',
			'HTTP_X_GEOIP_COUNTRY',
		);

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
			if ( self::is_valid_country_code( $code ) ) {
				return $code;
			}
		}

		return '';
	}

	/**
	 * @param string $ip IP address.
	 * @return string
	 */
	private static function cache_key( $ip ) {
		return 'tcw_geo_' . md5( $ip );
	}

	/**
	 * @return string
	 */
	public static function get_client_ip() {
		$keys = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}

			$raw = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			if ( strpos( $raw, ',' ) !== false ) {
				$raw = trim( explode( ',', $raw )[0] );
			}

			if ( filter_var( $raw, FILTER_VALIDATE_IP ) ) {
				return $raw;
			}
		}

		return '';
	}

	/**
	 * @param string $ip IP address.
	 * @return bool
	 */
	private static function is_public_ip( $ip ) {
		return false !== filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	/**
	 * @param string $code Country code.
	 * @return bool
	 */
	private static function is_valid_country_code( $code ) {
		if ( 2 !== strlen( $code ) ) {
			return false;
		}

		// Cloudflare uses XX/T1 for unknown / Tor.
		if ( in_array( $code, array( 'XX', 'T1' ), true ) ) {
			return false;
		}

		return (bool) preg_match( '/^[A-Z]{2}$/', $code );
	}

	/**
	 * @param string $ip IP address.
	 * @return string
	 */
	private static function detect_country_from_ip( $ip ) {
		if ( ! function_exists( 'wp_remote_get' ) ) {
			return '';
		}

		$providers = array(
			array( self::class, 'fetch_ipapi_co' ),
			array( self::class, 'fetch_ip_api_com' ),
		);

		foreach ( $providers as $provider ) {
			$code = call_user_func( $provider, $ip );
			if ( $code ) {
				return $code;
			}
		}

		return '';
	}

	/**
	 * @param string $ip IP address.
	 * @return string
	 */
	private static function fetch_ipapi_co( $ip ) {
		$response = wp_remote_get(
			'https://ipapi.co/' . rawurlencode( $ip ) . '/country_code/',
			array(
				'timeout'    => 3,
				'user-agent' => 'TravelayCulturalWelcome/' . TCW_VERSION,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$code = strtoupper( trim( (string) wp_remote_retrieve_body( $response ) ) );
		return self::is_valid_country_code( $code ) ? $code : '';
	}

	/**
	 * @param string $ip IP address.
	 * @return string
	 */
	private static function fetch_ip_api_com( $ip ) {
		$response = wp_remote_get(
			'http://ip-api.com/json/' . rawurlencode( $ip ) . '?fields=status,countryCode',
			array(
				'timeout'    => 3,
				'user-agent' => 'TravelayCulturalWelcome/' . TCW_VERSION,
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || 'success' !== ( $data['status'] ?? '' ) ) {
			return '';
		}

		$code = strtoupper( (string) ( $data['countryCode'] ?? '' ) );
		return self::is_valid_country_code( $code ) ? $code : '';
	}
}
