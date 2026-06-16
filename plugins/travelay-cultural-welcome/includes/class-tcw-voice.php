<?php
/**
 * Google Cloud Text-to-Speech for avatar voice welcomes.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Voice
 */
class TCW_Voice {

	/**
	 * Register hooks.
	 */
	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * REST routes.
	 */
	public static function register_routes() {
		register_rest_route(
			'tcw/v1',
			'/voice/(?P<profile_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'rest_voice' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'profile_id' => array(
						'validate_callback' => function ( $value ) {
							return absint( $value ) > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * Serve or redirect to cached voice audio.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_voice( $request ) {
		$profile_id = absint( $request['profile_id'] );
		$post       = get_post( $profile_id );

		if ( ! $post || TCW_Profile::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'tcw_voice_not_found', 'Profile not found', array( 'status' => 404 ) );
		}

		$profile = TCW_Profile::format_post( $post );
		$result  = self::resolve_audio( $profile );

		if ( empty( $result['url'] ) ) {
			return new WP_Error( 'tcw_voice_unavailable', $result['error'] ?? 'Voice unavailable', array( 'status' => 503 ) );
		}

		$response = new WP_REST_Response(
			array(
				'url'  => $result['url'],
				'text' => $result['text'],
			),
			200
		);
		$response->header( 'Cache-Control', 'public, max-age=86400' );
		return $response;
	}

	/**
	 * Build voice payload for the frontend.
	 *
	 * @param array<string, mixed> $profile Profile.
	 * @return array<string, mixed>
	 */
	public static function get_frontend_payload( $profile ) {
		$settings = TCW_Settings::get();

		if ( empty( $settings['enable_voice_welcome'] ) || ! self::profile_voice_enabled( $profile ) ) {
			return array(
				'enabled' => false,
				'url'     => '',
				'text'    => '',
			);
		}

		if ( ! TCW_Google_API::is_configured() ) {
			return array(
				'enabled' => false,
				'url'     => '',
				'text'    => self::get_speech_text( $profile ),
				'error'   => 'missing_api_key',
			);
		}

		$result = self::resolve_audio( $profile );

		return array(
			'enabled' => ! empty( $result['url'] ),
			'url'     => $result['url'] ?? '',
			'text'    => $result['text'] ?? '',
			'restUrl' => rest_url( 'tcw/v1/voice/' . (int) $profile['id'] ),
		);
	}

	/**
	 * @param array<string, mixed> $profile Profile.
	 * @return bool
	 */
	public static function profile_voice_enabled( $profile ) {
		if ( isset( $profile['voice_enabled'] ) && ! $profile['voice_enabled'] ) {
			return false;
		}
		return '' !== trim( self::get_speech_text( $profile ) );
	}

	/**
	 * Speech text: dedicated script, else welcome message.
	 *
	 * @param array<string, mixed> $profile Profile.
	 * @return string
	 */
	public static function get_speech_text( $profile ) {
		$script = isset( $profile['voice_script'] ) ? trim( (string) $profile['voice_script'] ) : '';
		if ( '' !== $script ) {
			return $script;
		}

		return isset( $profile['welcome_message_en'] ) ? trim( (string) $profile['welcome_message_en'] ) : '';
	}

	/**
	 * Resolve cached or freshly synthesized audio.
	 *
	 * @param array<string, mixed> $profile Profile.
	 * @return array{url?:string,text?:string,error?:string}
	 */
	public static function resolve_audio( $profile ) {
		$text = self::get_speech_text( $profile );
		if ( '' === $text ) {
			return array( 'error' => 'empty_text' );
		}

		$voice_config = self::resolve_voice_config( $profile );
		$cache_file   = self::cache_file_path( $profile, $text, $voice_config );
		$cache_url    = self::cache_file_url( $profile, $text, $voice_config );

		if ( file_exists( $cache_file ) && filesize( $cache_file ) > 0 ) {
			return array(
				'url'  => $cache_url,
				'text' => $text,
			);
		}

		$audio = self::synthesize( $text, $voice_config );
		if ( is_wp_error( $audio ) ) {
			return array(
				'text'  => $text,
				'error' => $audio->get_error_message(),
			);
		}

		self::ensure_cache_dir();
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( false === file_put_contents( $cache_file, $audio ) ) {
			return array(
				'text'  => $text,
				'error' => 'cache_write_failed',
			);
		}

		return array(
			'url'  => $cache_url,
			'text' => $text,
		);
	}

	/**
	 * @param array<string, mixed> $profile Profile.
	 * @return array{languageCode:string,name:string,speakingRate:float,pitch:float}
	 */
	public static function resolve_voice_config( $profile ) {
		$slug    = isset( $profile['location_slug'] ) ? sanitize_title( $profile['location_slug'] ) : '';
		$default = self::default_voice_for_slug( $slug );

		$language = ! empty( $profile['voice_language'] )
			? sanitize_text_field( $profile['voice_language'] )
			: $default['languageCode'];

		$name = ! empty( $profile['voice_name'] )
			? sanitize_text_field( $profile['voice_name'] )
			: '';

		if ( '' === $name && ! empty( $language ) ) {
			$name = self::best_voice_name_for_language( $language );
		}

		if ( '' === $name ) {
			$name = $default['name'];
		}

		if ( ! empty( $name ) && empty( $profile['voice_language'] ) ) {
			$detected_language = TCW_Voice_Catalog::language_for_voice_name( $name );
			if ( $detected_language ) {
				$language = $detected_language;
			}
		}

		$rate = isset( $profile['voice_speaking_rate'] ) ? (float) $profile['voice_speaking_rate'] : 0;
		if ( $rate <= 0 ) {
			$rate = (float) TCW_Settings::get()['voice_speaking_rate'];
		}

		return array(
			'languageCode' => $language,
			'name'         => $name,
			'speakingRate' => max( 0.5, min( 1.5, $rate ) ),
			'pitch'        => 0.0,
		);
	}

	/**
	 * Pick the highest-quality voice for a language from the synced catalog.
	 *
	 * @param string $language_code Language code.
	 * @return string
	 */
	public static function best_voice_name_for_language( $language_code ) {
		$voices = TCW_Voice_Catalog::get_voices_for_language( $language_code );
		if ( empty( $voices ) ) {
			return '';
		}

		return $voices[0]['name'];
	}

	/**
	 * Default Neural2 voices per country slug.
	 *
	 * @param string $slug Location slug.
	 * @return array{languageCode:string,name:string}
	 */
	public static function default_voice_for_slug( $slug ) {
		$map = array(
			'india'          => array( 'languageCode' => 'en-IN', 'name' => 'en-IN-Neural2-A' ),
			'japan'          => array( 'languageCode' => 'en-US', 'name' => 'en-US-Neural2-J' ),
			'italy'          => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-F' ),
			'brazil'         => array( 'languageCode' => 'en-US', 'name' => 'en-US-Neural2-F' ),
			'australia'      => array( 'languageCode' => 'en-AU', 'name' => 'en-AU-Neural2-A' ),
			'saudi-arabia'   => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-D' ),
			'france'         => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-F' ),
			'spain'          => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-A' ),
			'united-kingdom' => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-A' ),
			'mexico'         => array( 'languageCode' => 'en-US', 'name' => 'en-US-Neural2-H' ),
			'canada'         => array( 'languageCode' => 'en-US', 'name' => 'en-US-Neural2-F' ),
			'usa'            => array( 'languageCode' => 'en-US', 'name' => 'en-US-Neural2-J' ),
			'netherlands'    => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-B' ),
			'greece'         => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-F' ),
			'russia'         => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-D' ),
			'switzerland'    => array( 'languageCode' => 'en-GB', 'name' => 'en-GB-Neural2-B' ),
		);

		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ];
		}

		return array(
			'languageCode' => 'en-US',
			'name'         => 'en-US-Neural2-F',
		);
	}

	/**
	 * Call Google Cloud Text-to-Speech API.
	 *
	 * @param string               $text   Text to speak.
	 * @param array<string, mixed> $config Voice config.
	 * @return string|WP_Error Raw MP3 bytes.
	 */
	private static function synthesize( $text, $config ) {
		$api_key = TCW_Google_API::get_api_key();
		if ( '' === $api_key ) {
			return new WP_Error( 'tcw_missing_api_key', 'Google API key is not configured.' );
		}

		$body = array(
			'input'       => array( 'text' => $text ),
			'voice'       => array(
				'languageCode' => $config['languageCode'],
				'name'         => $config['name'],
			),
			'audioConfig' => array(
				'audioEncoding' => 'MP3',
				'speakingRate'  => $config['speakingRate'],
				'pitch'         => $config['pitch'],
			),
		);

		$url = add_query_arg(
			'key',
			$api_key,
			'https://texttospeech.googleapis.com/v1/text:synthesize'
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
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
				: 'Google TTS request failed';
			return new WP_Error( 'tcw_tts_failed', $message );
		}

		if ( empty( $raw['audioContent'] ) ) {
			return new WP_Error( 'tcw_tts_empty', 'Google TTS returned no audio.' );
		}

		$decoded = base64_decode( (string) $raw['audioContent'], true );
		if ( false === $decoded || '' === $decoded ) {
			return new WP_Error( 'tcw_tts_decode', 'Failed to decode Google TTS audio.' );
		}

		return $decoded;
	}

	/**
	 * @return string
	 */
	private static function cache_dir() {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['basedir'] ) . 'travelay-cultural-welcome/voice';
	}

	/**
	 * @return string
	 */
	private static function cache_url_base() {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['baseurl'] ) . 'travelay-cultural-welcome/voice';
	}

	/**
	 * Ensure cache directory exists.
	 */
	private static function ensure_cache_dir() {
		$dir = self::cache_dir();
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * @param array<string, mixed> $profile Profile.
	 * @param string               $text    Speech text.
	 * @param array<string, mixed> $config  Voice config.
	 * @return string
	 */
	private static function cache_file_path( $profile, $text, $config ) {
		return self::cache_dir() . '/' . self::cache_filename( $profile, $text, $config );
	}

	/**
	 * @param array<string, mixed> $profile Profile.
	 * @param string               $text    Speech text.
	 * @param array<string, mixed> $config  Voice config.
	 * @return string
	 */
	private static function cache_file_url( $profile, $text, $config ) {
		return self::cache_url_base() . '/' . self::cache_filename( $profile, $text, $config );
	}

	/**
	 * @param array<string, mixed> $profile Profile.
	 * @param string               $text    Speech text.
	 * @param array<string, mixed> $config  Voice config.
	 * @return string
	 */
	private static function cache_filename( $profile, $text, $config ) {
		$slug = isset( $profile['location_slug'] ) ? sanitize_title( $profile['location_slug'] ) : 'profile';
		$hash = md5( $text . '|' . $config['name'] . '|' . $config['speakingRate'] );
		return sanitize_file_name( $slug . '-' . $hash . '.mp3' );
	}
}
