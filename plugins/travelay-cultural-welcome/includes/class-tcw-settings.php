<?php
/**
 * Global plugin settings.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Settings
 */
class TCW_Settings {

	public const OPTION_KEY = 'tcw_settings';

	/**
	 * Setting keys controlled by experience presets.
	 *
	 * @return string[]
	 */
	public static function preset_flag_keys() {
		return array(
			'enable_ip_welcome',
			'enable_voice_welcome',
			'enable_confetti',
			'enable_sound',
			'typewriter_enabled',
			'enable_lottie',
			'enable_rive',
		);
	}

	/**
	 * Named experience presets.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function presets() {
		return array(
			'classic'       => array(
				'label'       => __( 'Classic', 'travelay-cultural-welcome' ),
				'description' => __( 'Visual welcome only — avatars, confetti, typewriter. No IP detection or spoken voice.', 'travelay-cultural-welcome' ),
				'enable_ip_welcome'    => false,
				'enable_voice_welcome' => false,
				'enable_confetti'      => true,
				'enable_sound'         => true,
				'typewriter_enabled'   => true,
				'enable_lottie'        => true,
				'enable_rive'          => true,
			),
			'ip_welcome'    => array(
				'label'       => __( 'IP Welcome', 'travelay-cultural-welcome' ),
				'description' => __( 'Classic visual welcome plus geo-based greetings from visitor IP on the homepage and non-country pages.', 'travelay-cultural-welcome' ),
				'enable_ip_welcome'    => true,
				'enable_voice_welcome' => false,
				'enable_confetti'      => true,
				'enable_sound'         => true,
				'typewriter_enabled'   => true,
				'enable_lottie'        => true,
				'enable_rive'          => true,
			),
			'voice_welcome' => array(
				'label'       => __( 'Voice Welcome', 'travelay-cultural-welcome' ),
				'description' => __( 'Visual welcome plus Google Text-to-Speech on country pages. No IP-based welcome.', 'travelay-cultural-welcome' ),
				'enable_ip_welcome'    => false,
				'enable_voice_welcome' => true,
				'enable_confetti'      => true,
				'enable_sound'         => true,
				'typewriter_enabled'   => true,
				'enable_lottie'        => true,
				'enable_rive'          => true,
			),
			'full'          => array(
				'label'       => __( 'Full Experience', 'travelay-cultural-welcome' ),
				'description' => __( 'Everything enabled — IP welcome, spoken voice, confetti, premium avatars, and typewriter message.', 'travelay-cultural-welcome' ),
				'enable_ip_welcome'    => true,
				'enable_voice_welcome' => true,
				'enable_confetti'      => true,
				'enable_sound'         => true,
				'typewriter_enabled'   => true,
				'enable_lottie'        => true,
				'enable_rive'          => true,
			),
			'custom'        => array(
				'label'       => __( 'Custom', 'travelay-cultural-welcome' ),
				'description' => __( 'Manually control each feature below. Use this when you need a mix outside the named presets.', 'travelay-cultural-welcome' ),
			),
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'enabled'                => true,
			'experience_preset'      => 'full',
			'default_tone'           => 'elegant',
			'default_trigger'        => 'both',
			'auto_delay_ms'          => 1200,
			'auto_duration_ms'       => 5000,
			'frequency'              => 'session',
			'show_replay_button'     => true,
			'respect_reduced_motion' => true,
			'enable_confetti'        => true,
			'confetti_intensity'     => 'high',
			'typewriter_enabled'     => true,
			'avatar_renderer'        => 'auto',
			'enable_lottie'          => true,
			'enable_rive'            => true,
			'enable_sound'           => false,
			'sound_volume'           => 0.35,
			'enable_ip_welcome'      => true,
			'google_api_key'         => '',
			'enable_voice_welcome'   => true,
			'voice_volume'           => 0.9,
			'voice_speaking_rate'    => 1.0,
			'z_index'                => 2147483000,
			'sync_exclude_slugs'     => 'privacy-policy,cart,checkout,my-account',
		);
	}

	/**
	 * Slugs excluded from bulk page sync.
	 *
	 * @return string[]
	 */
	public static function sync_exclude_slugs() {
		$settings = self::get_stored();
		$raw      = isset( $settings['sync_exclude_slugs'] ) ? (string) $settings['sync_exclude_slugs'] : '';
		$parts    = array_map( 'sanitize_title', array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );

		/**
		 * Filter slugs excluded from Cultural Welcome page sync.
		 *
		 * @param string[] $parts Slug list.
		 */
		return apply_filters( 'tcw_sync_exclude_slugs', $parts );
	}

	/**
	 * Raw stored settings without preset resolution.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_stored() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$settings = wp_parse_args( $stored, self::defaults() );

		if ( empty( $settings['experience_preset'] ) ) {
			$settings['experience_preset'] = self::infer_preset( $settings );
		}

		if ( (int) $settings['z_index'] < 1000000 ) {
			$settings['z_index'] = self::defaults()['z_index'];
		}

		return $settings;
	}

	/**
	 * Get merged settings with the active preset applied.
	 *
	 * @return array<string, mixed>
	 */
	public static function get() {
		return self::apply_preset( self::get_stored() );
	}

	/**
	 * Guess the closest preset from stored feature flags.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return string
	 */
	public static function infer_preset( $settings ) {
		foreach ( array( 'full', 'voice_welcome', 'ip_welcome', 'classic' ) as $preset_key ) {
			if ( self::settings_match_preset( $settings, $preset_key ) ) {
				return $preset_key;
			}
		}

		return 'custom';
	}

	/**
	 * Whether stored flags exactly match a preset bundle.
	 *
	 * @param array<string, mixed> $settings   Settings.
	 * @param string               $preset_key Preset key.
	 * @return bool
	 */
	public static function settings_match_preset( $settings, $preset_key ) {
		$presets = self::presets();
		if ( empty( $presets[ $preset_key ] ) || 'custom' === $preset_key ) {
			return false;
		}

		foreach ( self::preset_flag_keys() as $flag_key ) {
			$expected = ! empty( $presets[ $preset_key ][ $flag_key ] );
			$actual   = ! empty( $settings[ $flag_key ] );
			if ( $expected !== $actual ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Apply the selected preset flags onto settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	public static function apply_preset( $settings ) {
		$preset_key = isset( $settings['experience_preset'] ) ? sanitize_key( $settings['experience_preset'] ) : 'full';
		$presets    = self::presets();

		if ( 'custom' === $preset_key || empty( $presets[ $preset_key ] ) ) {
			return $settings;
		}

		foreach ( self::preset_flag_keys() as $flag_key ) {
			if ( array_key_exists( $flag_key, $presets[ $preset_key ] ) ) {
				$settings[ $flag_key ] = (bool) $presets[ $preset_key ][ $flag_key ];
			}
		}

		return $settings;
	}

	/**
	 * Persist settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return bool
	 */
	public static function update( $settings ) {
		$clean = self::sanitize( $settings );
		return update_option( self::OPTION_KEY, $clean );
	}

	/**
	 * Sanitize settings payload.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $settings ) {
		$defaults = self::defaults();
		$clean    = array();
		$presets  = self::presets();

		$clean['enabled'] = ! empty( $settings['enabled'] );

		$preset = isset( $settings['experience_preset'] ) ? sanitize_key( $settings['experience_preset'] ) : $defaults['experience_preset'];
		if ( ! array_key_exists( $preset, $presets ) ) {
			$preset = $defaults['experience_preset'];
		}
		$clean['experience_preset'] = $preset;

		$tone = isset( $settings['default_tone'] ) ? sanitize_key( $settings['default_tone'] ) : $defaults['default_tone'];
		$clean['default_tone'] = in_array( $tone, array( 'elegant', 'playful' ), true ) ? $tone : 'elegant';

		$trigger = isset( $settings['default_trigger'] ) ? sanitize_key( $settings['default_trigger'] ) : $defaults['default_trigger'];
		$clean['default_trigger'] = in_array( $trigger, array( 'auto', 'manual', 'both' ), true ) ? $trigger : 'both';

		$clean['auto_delay_ms']    = max( 0, min( 10000, absint( $settings['auto_delay_ms'] ?? $defaults['auto_delay_ms'] ) ) );
		$clean['auto_duration_ms'] = max( 2000, min( 15000, absint( $settings['auto_duration_ms'] ?? $defaults['auto_duration_ms'] ) ) );

		$frequency = isset( $settings['frequency'] ) ? sanitize_key( $settings['frequency'] ) : $defaults['frequency'];
		$clean['frequency'] = in_array( $frequency, array( 'session', 'day', 'week', 'always' ), true ) ? $frequency : 'session';

		$clean['show_replay_button']     = ! empty( $settings['show_replay_button'] );
		$clean['respect_reduced_motion'] = ! empty( $settings['respect_reduced_motion'] );
		$clean['enable_confetti']        = ! empty( $settings['enable_confetti'] );
		$intensity = isset( $settings['confetti_intensity'] ) ? sanitize_key( $settings['confetti_intensity'] ) : $defaults['confetti_intensity'];
		$clean['confetti_intensity']     = in_array( $intensity, array( 'low', 'medium', 'high' ), true ) ? $intensity : 'high';
		$clean['typewriter_enabled']     = ! empty( $settings['typewriter_enabled'] );
		$renderer = isset( $settings['avatar_renderer'] ) ? sanitize_key( $settings['avatar_renderer'] ) : $defaults['avatar_renderer'];
		$clean['avatar_renderer']        = in_array( $renderer, array( 'auto', 'rive', 'lottie', 'svg' ), true ) ? $renderer : 'auto';
		$clean['enable_lottie']          = ! empty( $settings['enable_lottie'] );
		$clean['enable_rive']            = ! empty( $settings['enable_rive'] );
		$clean['enable_sound']           = ! empty( $settings['enable_sound'] );
		$clean['sound_volume']           = max( 0, min( 1, (float) ( $settings['sound_volume'] ?? $defaults['sound_volume'] ) ) );
		$clean['enable_ip_welcome']      = ! empty( $settings['enable_ip_welcome'] );
		$clean['enable_voice_welcome']   = ! empty( $settings['enable_voice_welcome'] );
		$clean['voice_volume']           = max( 0, min( 1, (float) ( $settings['voice_volume'] ?? $defaults['voice_volume'] ) ) );
		$clean['voice_speaking_rate']    = max( 0.5, min( 1.5, (float) ( $settings['voice_speaking_rate'] ?? $defaults['voice_speaking_rate'] ) ) );

		$existing = self::get_stored();
		if ( ! empty( $settings['google_api_key'] ) ) {
			$clean['google_api_key'] = sanitize_text_field( $settings['google_api_key'] );
		} else {
			$clean['google_api_key'] = isset( $existing['google_api_key'] ) ? (string) $existing['google_api_key'] : '';
		}

		$clean['z_index'] = max( 1000000, min( 2147483647, absint( $settings['z_index'] ?? $defaults['z_index'] ) ) );

		$clean['sync_exclude_slugs'] = isset( $settings['sync_exclude_slugs'] )
			? sanitize_text_field( $settings['sync_exclude_slugs'] )
			: $defaults['sync_exclude_slugs'];

		if ( 'custom' !== $preset ) {
			foreach ( self::preset_flag_keys() as $flag_key ) {
				if ( array_key_exists( $flag_key, $presets[ $preset ] ) ) {
					$clean[ $flag_key ] = (bool) $presets[ $preset ][ $flag_key ];
				}
			}
		}

		return $clean;
	}

	/**
	 * Resolve tone for a profile.
	 *
	 * @param array<string, mixed> $profile Profile data.
	 * @return string
	 */
	public static function resolve_tone( $profile ) {
		$override = isset( $profile['tone_override'] ) ? $profile['tone_override'] : 'inherit';
		if ( in_array( $override, array( 'elegant', 'playful' ), true ) ) {
			return $override;
		}

		$settings = self::get();
		return $settings['default_tone'];
	}

	/**
	 * Resolve trigger mode for a profile.
	 *
	 * @param array<string, mixed> $profile Profile data.
	 * @return string
	 */
	public static function resolve_trigger( $profile ) {
		$override = isset( $profile['trigger_override'] ) ? $profile['trigger_override'] : 'inherit';
		if ( in_array( $override, array( 'auto', 'manual', 'both' ), true ) ) {
			return $override;
		}

		$settings = self::get();
		return $settings['default_trigger'];
	}
}
