<?php
/**
 * Google Cloud TTS voice catalog (languages, accents, features).
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Voice_Catalog
 */
class TCW_Voice_Catalog {

	public const TRANSIENT_KEY = 'tcw_tts_voice_catalog';
	public const META_OPTION   = 'tcw_voice_catalog_meta';

	/**
	 * Register hooks.
	 */
	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * REST routes (admin-only refresh + read).
	 */
	public static function register_routes() {
		register_rest_route(
			'tcw/v1',
			'/voices',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_voices' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_voices( $request ) {
		$refresh = (bool) $request->get_param( 'refresh' );
		$catalog = $refresh ? self::refresh_catalog() : self::get_catalog();

		if ( ! empty( $catalog['error'] ) && empty( $catalog['voices'] ) ) {
			return new WP_Error( 'tcw_voice_catalog_error', $catalog['error'], array( 'status' => 503 ) );
		}

		return new WP_REST_Response( $catalog, 200 );
	}

	/**
	 * Get cached catalog or fetch from Google.
	 *
	 * @param bool $force_refresh Force API refresh.
	 * @return array<string, mixed>
	 */
	public static function get_catalog( $force_refresh = false ) {
		if ( ! $force_refresh ) {
			$cached = get_transient( self::TRANSIENT_KEY );
			if ( is_array( $cached ) && ! empty( $cached['voices'] ) ) {
				return $cached;
			}
		}

		return self::refresh_catalog();
	}

	/**
	 * Fetch voices from Google and cache.
	 *
	 * @return array<string, mixed>
	 */
	public static function refresh_catalog() {
		$fetched = self::fetch_from_google();

		if ( is_wp_error( $fetched ) ) {
			$stale = get_transient( self::TRANSIENT_KEY );
			if ( is_array( $stale ) && ! empty( $stale['voices'] ) ) {
				$stale['error'] = $fetched->get_error_message();
				return $stale;
			}

			return array(
				'synced_at' => 0,
				'error'     => $fetched->get_error_message(),
				'languages' => array(),
				'voices'    => array(),
				'features'  => array(),
			);
		}

		set_transient( self::TRANSIENT_KEY, $fetched, WEEK_IN_SECONDS );
		update_option(
			self::META_OPTION,
			array(
				'synced_at'     => $fetched['synced_at'],
				'language_count'=> count( $fetched['languages'] ),
				'voice_count'   => count( $fetched['voices'] ),
			),
			false
		);

		return $fetched;
	}

	/**
	 * @return array<string, mixed>|WP_Error
	 */
	private static function fetch_from_google() {
		$api_key = TCW_Google_API::get_api_key();
		if ( '' === $api_key ) {
			return new WP_Error( 'tcw_missing_api_key', __( 'Google API key is not configured.', 'travelay-cultural-welcome' ) );
		}

		$url = add_query_arg(
			'key',
			$api_key,
			'https://texttospeech.googleapis.com/v1/voices'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 25,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$message = is_array( $raw ) && isset( $raw['error']['message'] )
				? $raw['error']['message']
				: __( 'Failed to fetch Google TTS voices.', 'travelay-cultural-welcome' );
			return new WP_Error( 'tcw_voice_catalog_fetch', $message );
		}

		if ( empty( $raw['voices'] ) || ! is_array( $raw['voices'] ) ) {
			return new WP_Error( 'tcw_voice_catalog_empty', __( 'Google TTS returned no voices.', 'travelay-cultural-welcome' ) );
		}

		return self::normalize_voices( $raw['voices'] );
	}

	/**
	 * @param array<int, array<string, mixed>> $voices Raw Google voices.
	 * @return array<string, mixed>
	 */
	private static function normalize_voices( $voices ) {
		$normalized = array();
		$languages  = array();
		$features   = array();

		foreach ( $voices as $voice ) {
			if ( empty( $voice['name'] ) || empty( $voice['languageCodes'] ) ) {
				continue;
			}

			$name           = (string) $voice['name'];
			$gender         = isset( $voice['ssmlGender'] ) ? (string) $voice['ssmlGender'] : 'NEUTRAL';
			$feature_tags   = self::detect_features( $name );
			$sample_rate    = isset( $voice['naturalSampleRateHertz'] ) ? (int) $voice['naturalSampleRateHertz'] : 0;
			$primary_lang   = (string) $voice['languageCodes'][0];

			foreach ( $voice['languageCodes'] as $language_code ) {
				$language_code = (string) $language_code;
				$entry         = array(
					'name'        => $name,
					'language'    => $language_code,
					'gender'      => $gender,
					'features'    => $feature_tags,
					'sample_rate' => $sample_rate,
					'label'       => self::format_voice_label( $name, $gender, $feature_tags, $language_code ),
				);

				$normalized[] = $entry;

				if ( ! isset( $languages[ $language_code ] ) ) {
					$languages[ $language_code ] = array(
						'code'        => $language_code,
						'label'       => self::format_language_label( $language_code ),
						'voice_count' => 0,
					);
				}
				$languages[ $language_code ]['voice_count']++;

				foreach ( $feature_tags as $tag ) {
					$features[ $tag ] = $tag;
				}
			}
		}

		usort(
			$normalized,
			function ( $a, $b ) {
				$rank_a = self::feature_rank( $a['features'] );
				$rank_b = self::feature_rank( $b['features'] );
				if ( $rank_a !== $rank_b ) {
					return $rank_a <=> $rank_b;
				}
				return strcmp( $a['label'], $b['label'] );
			}
		);

		$lang_list = array_values( $languages );
		usort(
			$lang_list,
			function ( $a, $b ) {
				return strcmp( $a['label'], $b['label'] );
			}
		);

		$feature_list = array_values( $features );
		usort( $feature_list, array( __CLASS__, 'sort_features' ) );

		return array(
			'synced_at' => time(),
			'error'     => '',
			'languages' => $lang_list,
			'voices'    => $normalized,
			'features'  => $feature_list,
		);
	}

	/**
	 * Detect Google voice technology from voice name.
	 *
	 * @param string $name Voice name.
	 * @return string[]
	 */
	public static function detect_features( $name ) {
		$patterns = array(
			'Chirp 3 HD' => '/Chirp3-HD/i',
			'Chirp HD'   => '/Chirp-HD/i',
			'Chirp'      => '/Chirp/i',
			'Studio'     => '/Studio/i',
			'Neural2'    => '/Neural2/i',
			'Journey'    => '/Journey/i',
			'Polyglot'   => '/Polyglot/i',
			'Neural'     => '/Neural/i',
			'News'       => '/News/i',
			'Casual'     => '/Casual/i',
			'WaveNet'    => '/Wavenet|WaveNet/i',
			'Standard'   => '/Standard/i',
		);

		foreach ( $patterns as $label => $pattern ) {
			if ( preg_match( $pattern, $name ) ) {
				return array( $label );
			}
		}

		return array( 'Other' );
	}

	/**
	 * @param string[] $features Feature tags.
	 * @return int
	 */
	private static function feature_rank( $features ) {
		$order = array( 'Chirp 3 HD', 'Chirp HD', 'Chirp', 'Studio', 'Neural2', 'Journey', 'Polyglot', 'Neural', 'WaveNet', 'News', 'Casual', 'Standard', 'Other' );
		foreach ( $order as $index => $tag ) {
			if ( in_array( $tag, $features, true ) ) {
				return $index;
			}
		}
		return 99;
	}

	/**
	 * @param string $a Feature A.
	 * @param string $b Feature B.
	 * @return int
	 */
	public static function sort_features( $a, $b ) {
		return self::feature_rank( array( $a ) ) <=> self::feature_rank( array( $b ) );
	}

	/**
	 * @param string $code Language code.
	 * @return string
	 */
	public static function format_language_label( $code ) {
		if ( class_exists( 'Locale' ) ) {
			$display = Locale::getDisplayName( $code, 'en' );
			if ( is_string( $display ) && '' !== $display && $display !== $code ) {
				return sprintf( '%s (%s)', $display, $code );
			}
		}

		return $code;
	}

	/**
	 * @param string   $name     Voice name.
	 * @param string   $gender   Gender.
	 * @param string[] $features Features.
	 * @param string   $language Language code.
	 * @return string
	 */
	public static function format_voice_label( $name, $gender, $features, $language ) {
		$gender_label = ucfirst( strtolower( $gender ) );
		$feature      = ! empty( $features ) ? $features[0] : 'Voice';
		$variant      = self::voice_variant_letter( $name );

		return sprintf(
			'%s · %s · %s%s',
			$feature,
			$gender_label,
			$language,
			$variant ? ' · Voice ' . $variant : ''
		);
	}

	/**
	 * @param string $name Voice name.
	 * @return string
	 */
	private static function voice_variant_letter( $name ) {
		if ( preg_match( '/-([A-Z])$/', $name, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	/**
	 * Catalog meta for admin display.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_meta() {
		$meta = get_option( self::META_OPTION, array() );
		return is_array( $meta ) ? $meta : array();
	}

	/**
	 * Voices for a language, optionally filtered by feature.
	 *
	 * @param string $language_code Language code.
	 * @param string $feature       Feature filter or empty.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_voices_for_language( $language_code, $feature = '' ) {
		$catalog = self::get_catalog();
		$voices  = array();

		foreach ( $catalog['voices'] as $voice ) {
			if ( $voice['language'] !== $language_code ) {
				continue;
			}
			if ( $feature && ! in_array( $feature, $voice['features'], true ) ) {
				continue;
			}
			$voices[] = $voice;
		}

		return $voices;
	}

	/**
	 * Resolve language for a stored voice name.
	 *
	 * @param string $voice_name Voice name.
	 * @return string
	 */
	public static function language_for_voice_name( $voice_name ) {
		if ( '' === $voice_name ) {
			return '';
		}

		$catalog = self::get_catalog();
		foreach ( $catalog['voices'] as $voice ) {
			if ( $voice['name'] === $voice_name ) {
				return $voice['language'];
			}
		}

		if ( preg_match( '/^([a-z]{2,3}-[A-Z]{2})/', $voice_name, $matches ) ) {
			return $matches[1];
		}

		return '';
	}
}
