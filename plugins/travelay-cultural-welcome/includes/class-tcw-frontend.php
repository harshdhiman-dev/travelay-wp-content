<?php
/**
 * Frontend rendering and assets.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Frontend
 */
class TCW_Frontend {

	/**
	 * Cached profile for current request.
	 *
	 * @var array<string, mixed>|null
	 */
	private static $profile = null;

	/**
	 * Register hooks.
	 */
	public static function register() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'maybe_enqueue_assets' ), 20 );
		add_action( 'wp_footer', array( __CLASS__, 'render_mount_point' ), 50 );
	}

	/**
	 * Get profile once per request.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function get_profile() {
		if ( null === self::$profile ) {
			self::$profile = TCW_Matcher::resolve_current();
		}

		return self::$profile;
	}

	/**
	 * Enqueue assets only when a profile matches.
	 */
	public static function maybe_enqueue_assets() {
		$profile = self::get_profile();
		if ( ! $profile ) {
			return;
		}

		$settings = TCW_Settings::get();

		wp_enqueue_style(
			'tcw-welcome',
			TCW_PLUGIN_URL . 'assets/css/tcw-welcome.css',
			array(),
			TCW_VERSION
		);

		wp_enqueue_script(
			'tcw-confetti',
			TCW_PLUGIN_URL . 'assets/js/tcw-confetti.js',
			array(),
			TCW_VERSION,
			true
		);

		$welcome_deps = array( 'tcw-confetti', 'tcw-avatar-engine' );

		if ( ! empty( $settings['enable_sound'] ) ) {
			wp_enqueue_script(
				'tcw-sound',
				TCW_PLUGIN_URL . 'assets/js/tcw-sound.js',
				array(),
				TCW_VERSION,
				true
			);
			$welcome_deps[] = 'tcw-sound';
		}

		if ( ! empty( $settings['enable_voice_welcome'] ) ) {
			wp_enqueue_script(
				'tcw-voice',
				TCW_PLUGIN_URL . 'assets/js/tcw-voice.js',
				array(),
				TCW_VERSION,
				true
			);
			$welcome_deps[] = 'tcw-voice';
		}

		wp_enqueue_script(
			'tcw-avatar-engine',
			TCW_PLUGIN_URL . 'assets/js/tcw-avatar-engine.js',
			array(),
			TCW_VERSION,
			true
		);

		wp_enqueue_script(
			'tcw-welcome',
			TCW_PLUGIN_URL . 'assets/js/tcw-welcome.js',
			$welcome_deps,
			TCW_VERSION,
			true
		);

		$confetti = TCW_Confetti::get_profile_for_slug( $profile['location_slug'] );
		$avatar   = TCW_Avatar_Engine::get_manifest( $profile );
		$voice    = TCW_Voice::get_frontend_payload( $profile );

		$payload = array(
			'profile' => array(
				'id'                   => (int) $profile['id'],
				'entityType'           => $profile['entity_type'],
				'locationSlug'         => $profile['location_slug'],
				'displayName'          => $profile['display_name'],
				'gesture'              => $profile['gesture'],
				'message'              => $profile['welcome_message_en'],
				'tone'                 => $profile['tone'],
				'trigger'              => $profile['trigger'],
				'palette'              => $profile['palette'],
				'autoDelayMs'          => (int) $profile['auto_delay_ms'],
				'autoDurationMs'       => (int) $profile['auto_duration_ms'],
				'frequency'            => $profile['frequency'],
				'showReplayButton'     => (bool) $profile['show_replay_button'],
				'respectReducedMotion' => (bool) $profile['respect_reduced_motion'],
				'zIndex'               => (int) $profile['z_index'],
				'matchSource'          => isset( $profile['match_source'] ) ? (string) $profile['match_source'] : 'page',
				'countryCode'          => isset( $profile['country_code'] ) ? (string) $profile['country_code'] : '',
			),
			'avatar'     => $avatar,
			'confetti'   => $confetti,
			'voice'      => $voice,
			'experience' => array(
				'preset'            => isset( $settings['experience_preset'] ) ? (string) $settings['experience_preset'] : 'full',
				'enableConfetti'    => (bool) $settings['enable_confetti'],
				'confettiIntensity' => $settings['confetti_intensity'],
				'typewriterEnabled' => (bool) $settings['typewriter_enabled'],
				'enableSound'       => (bool) $settings['enable_sound'],
				'soundVolume'       => (float) $settings['sound_volume'],
				'enableVoice'       => (bool) $settings['enable_voice_welcome'],
				'voiceVolume'       => (float) $settings['voice_volume'],
			),
			'i18n' => array(
				'welcome'      => __( 'Welcome', 'travelay-cultural-welcome' ),
				'dismiss'      => __( 'Continue your journey', 'travelay-cultural-welcome' ),
				'replay'       => __( 'Celebrate again', 'travelay-cultural-welcome' ),
				'openWelcome'  => __( 'Open country welcome', 'travelay-cultural-welcome' ),
				'travelay'     => __( 'Travelay', 'travelay-cultural-welcome' ),
				'tapAvatar'        => __( 'Tap to greet again', 'travelay-cultural-welcome' ),
				'nowPlayingGesture'=> __( 'Tap to greet again', 'travelay-cultural-welcome' ),
				'nextGestureHint'  => __( 'Tap to greet again — next: %s', 'travelay-cultural-welcome' ),
				'celebrating'  => __( 'Celebrating your visit', 'travelay-cultural-welcome' ),
			),
		);

		wp_add_inline_script(
			'tcw-welcome',
			'window.TCWWelcome = ' . wp_json_encode( $payload ) . ';',
			'before'
		);
	}

	/**
	 * Output mount point and server-rendered avatar template.
	 */
	public static function render_mount_point() {
		$profile = self::get_profile();
		if ( ! $profile ) {
			return;
		}

		$avatar_markup = TCW_Avatar_Library::render( $profile );
		$z_index       = (int) $profile['z_index'];

		printf(
			'<div id="tcw-welcome-root" class="tcw-welcome-root" aria-live="polite" style="--tcw-z:%d;">',
			$z_index
		);
		echo '<template id="tcw-avatar-template">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG from trusted plugin templates.
		echo $avatar_markup;
		echo '</template>';
		echo '</div>';
	}
}
