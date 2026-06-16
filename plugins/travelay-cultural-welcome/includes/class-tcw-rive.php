<?php
/**
 * Rive avatar discovery, profile mapping, and frontend config.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Rive
 */
class TCW_Rive {

	public const META_SCAN           = '_tcw_rive_scan';
	public const META_STATE_MACHINE  = '_tcw_rive_state_machine';
	public const META_HOVER_INPUT    = '_tcw_rive_hover_input';
	public const META_ENTRY_TRIGGER  = '_tcw_rive_entry_trigger';
	public const META_TAP_TRIGGERS   = '_tcw_rive_tap_triggers';

	/**
	 * Register hooks.
	 */
	public static function register() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_ajax_tcw_save_rive_scan', array( __CLASS__, 'ajax_save_scan' ) );
	}

	/**
	 * REST routes (admin scan only).
	 */
	public static function register_routes() {
		register_rest_route(
			'tcw/v1',
			'/rive/scan',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'rest_save_scan' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
				'args'                => array(
					'profile_id' => array(
						'required'          => true,
						'validate_callback' => function ( $value ) {
							return absint( $value ) > 0;
						},
					),
				),
			)
		);
	}

	/**
	 * @return bool
	 */
	public static function can_manage() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Absolute path to a slug's .riv file.
	 *
	 * @param string $slug Location slug.
	 * @return string
	 */
	public static function get_file_path( $slug ) {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return '';
		}

		return TCW_PLUGIN_DIR . 'assets/avatars/rive/' . $slug . '.riv';
	}

	/**
	 * Public URL for a slug's .riv file.
	 *
	 * @param string $slug Location slug.
	 * @return string
	 */
	public static function get_file_url( $slug ) {
		$path = self::get_file_path( $slug );
		if ( ! $path || ! is_readable( $path ) ) {
			return '';
		}

		return TCW_PLUGIN_URL . 'assets/avatars/rive/' . sanitize_title( $slug ) . '.riv';
	}

	/**
	 * Whether a .riv file exists for the slug.
	 *
	 * @param string $slug Location slug.
	 * @return bool
	 */
	public static function file_exists( $slug ) {
		$path = self::get_file_path( $slug );
		return '' !== $path && is_readable( $path );
	}

	/**
	 * Sanitize a Rive input or state machine name.
	 *
	 * @param string $name Raw name.
	 * @return string
	 */
	public static function sanitize_input_name( $name ) {
		$name = sanitize_text_field( (string) $name );
		$name = preg_replace( '/[^a-zA-Z0-9_\- ]/', '', $name );
		return trim( $name );
	}

	/**
	 * Sanitize input type from Rive runtime.
	 *
	 * @param string $type Raw type.
	 * @return string
	 */
	public static function sanitize_input_type( $type ) {
		$type = strtolower( sanitize_key( (string) $type ) );
		if ( in_array( $type, array( 'boolean', 'trigger', 'number' ), true ) ) {
			return $type;
		}
		return 'unknown';
	}

	/**
	 * Sanitize scan payload from admin browser.
	 *
	 * @param mixed $scan Raw scan data.
	 * @return array<string, mixed>
	 */
	public static function sanitize_scan( $scan ) {
		if ( ! is_array( $scan ) ) {
			return array();
		}

		$clean = array(
			'scanned_at'     => time(),
			'state_machines' => array(),
		);

		$machines_raw = array();
		if ( ! empty( $scan['stateMachines'] ) && is_array( $scan['stateMachines'] ) ) {
			$machines_raw = $scan['stateMachines'];
		} elseif ( ! empty( $scan['state_machines'] ) && is_array( $scan['state_machines'] ) ) {
			$machines_raw = $scan['state_machines'];
		}

		if ( ! empty( $machines_raw ) ) {
			foreach ( $machines_raw as $machine ) {
				if ( ! is_array( $machine ) || empty( $machine['name'] ) ) {
					continue;
				}

				$machine_name = self::sanitize_input_name( $machine['name'] );
				if ( '' === $machine_name ) {
					continue;
				}

				$inputs = array();
				if ( ! empty( $machine['inputs'] ) && is_array( $machine['inputs'] ) ) {
					foreach ( $machine['inputs'] as $input ) {
						if ( ! is_array( $input ) || empty( $input['name'] ) ) {
							continue;
						}
						$input_name = self::sanitize_input_name( $input['name'] );
						if ( '' === $input_name ) {
							continue;
						}
						$inputs[] = array(
							'name' => $input_name,
							'type' => self::sanitize_input_type( $input['type'] ?? '' ),
						);
					}
				}

				$clean['state_machines'][] = array(
					'name'   => $machine_name,
					'inputs' => $inputs,
				);
			}
		}

		return $clean;
	}

	/**
	 * Get stored scan for a profile.
	 *
	 * @param int $profile_id Profile post ID.
	 * @return array<string, mixed>
	 */
	public static function get_scan( $profile_id ) {
		$raw = get_post_meta( absint( $profile_id ), self::META_SCAN, true );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Inputs for a state machine from cached scan.
	 *
	 * @param array<string, mixed> $scan           Scan data.
	 * @param string               $state_machine State machine name.
	 * @return array<int, array<string, string>>
	 */
	public static function inputs_for_machine( $scan, $state_machine ) {
		if ( empty( $scan['state_machines'] ) || ! is_array( $scan['state_machines'] ) ) {
			return array();
		}

		foreach ( $scan['state_machines'] as $machine ) {
			if ( ! is_array( $machine ) ) {
				continue;
			}
			if ( $machine['name'] === $state_machine && ! empty( $machine['inputs'] ) ) {
				return $machine['inputs'];
			}
		}

		return array();
	}

	/**
	 * Default state machine name from scan.
	 *
	 * @param array<string, mixed> $scan Scan data.
	 * @return string
	 */
	public static function default_state_machine( $scan ) {
		if ( empty( $scan['state_machines'][0]['name'] ) ) {
			return 'State Machine 1';
		}
		return (string) $scan['state_machines'][0]['name'];
	}

	/**
	 * Guess hover boolean input name.
	 *
	 * @param array<int, array<string, string>> $inputs Inputs.
	 * @return string
	 */
	public static function guess_hover_input( $inputs ) {
		$candidates = array( 'hover', 'isHover', 'is_hover', 'Hover', 'mouseHover', 'pointer' );
		foreach ( $inputs as $input ) {
			if ( 'boolean' !== ( $input['type'] ?? '' ) ) {
				continue;
			}
			foreach ( $candidates as $needle ) {
				if ( strcasecmp( $input['name'], $needle ) === 0 ) {
					return $input['name'];
				}
			}
		}
		foreach ( $inputs as $input ) {
			if ( 'boolean' === ( $input['type'] ?? '' ) ) {
				return $input['name'];
			}
		}
		return '';
	}

	/**
	 * Guess tap trigger names from scan.
	 *
	 * @param array<int, array<string, string>> $inputs Inputs.
	 * @return string[]
	 */
	public static function guess_tap_triggers( $inputs ) {
		$triggers = array();
		$priority = array( 'tap', 'react', 'wave', 'namaste', 'bow', 'nod', 'smile', 'welcome', 'heart' );

		foreach ( $priority as $needle ) {
			foreach ( $inputs as $input ) {
				if ( 'trigger' !== ( $input['type'] ?? '' ) ) {
					continue;
				}
				if ( strcasecmp( $input['name'], $needle ) === 0 ) {
					$triggers[] = $input['name'];
				}
			}
		}

		if ( empty( $triggers ) ) {
			foreach ( $inputs as $input ) {
				if ( 'trigger' === ( $input['type'] ?? '' ) ) {
					$triggers[] = $input['name'];
				}
			}
		}

		return array_values( array_unique( $triggers ) );
	}

	/**
	 * Apply scan defaults to profile meta when empty.
	 *
	 * @param int                  $profile_id Profile ID.
	 * @param array<string, mixed> $scan       Scan data.
	 */
	public static function apply_scan_defaults( $profile_id, $scan ) {
		$profile_id = absint( $profile_id );
		if ( ! $profile_id || empty( $scan['state_machines'] ) ) {
			return;
		}

		$machine = get_post_meta( $profile_id, self::META_STATE_MACHINE, true );
		if ( ! $machine ) {
			update_post_meta( $profile_id, self::META_STATE_MACHINE, self::default_state_machine( $scan ) );
			$machine = self::default_state_machine( $scan );
		}

		$inputs = self::inputs_for_machine( $scan, (string) $machine );

		if ( ! get_post_meta( $profile_id, self::META_HOVER_INPUT, true ) ) {
			$hover = self::guess_hover_input( $inputs );
			if ( $hover ) {
				update_post_meta( $profile_id, self::META_HOVER_INPUT, $hover );
			}
		}

		$tap_raw = get_post_meta( $profile_id, self::META_TAP_TRIGGERS, true );
		if ( empty( $tap_raw ) ) {
			$triggers = self::guess_tap_triggers( $inputs );
			if ( ! empty( $triggers ) ) {
				update_post_meta( $profile_id, self::META_TAP_TRIGGERS, wp_json_encode( $triggers ) );
			}
		}
	}

	/**
	 * Persist a sanitized scan for a profile.
	 *
	 * @param int                  $profile_id Profile post ID.
	 * @param array<string, mixed> $scan_raw   Raw scan payload.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function persist_scan( $profile_id, $scan_raw ) {
		$profile_id = absint( $profile_id );
		$post       = get_post( $profile_id );

		if ( ! $post || TCW_Profile::POST_TYPE !== $post->post_type ) {
			return new WP_Error( 'tcw_rive_profile', 'Profile not found', array( 'status' => 404 ) );
		}

		$profile = TCW_Profile::format_post( $post );
		$slug    = $profile['location_slug'] ?? '';

		if ( ! self::file_exists( $slug ) ) {
			return new WP_Error( 'tcw_rive_missing', 'Rive file not found for this slug', array( 'status' => 404 ) );
		}

		$scan = self::sanitize_scan( $scan_raw );

		if ( empty( $scan['state_machines'] ) ) {
			return new WP_Error( 'tcw_rive_scan_empty', 'No state machines found in scan', array( 'status' => 400 ) );
		}

		update_post_meta( $profile_id, self::META_SCAN, $scan );
		self::apply_scan_defaults( $profile_id, $scan );

		$profile = TCW_Profile::format_post( get_post( $profile_id ) );

		return array(
			'scan'    => $scan,
			'profile' => self::get_admin_payload( $profile ),
		);
	}

	/**
	 * REST: save scan results.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function rest_save_scan( $request ) {
		$profile_id = absint( $request->get_param( 'profile_id' ) );
		$body       = $request->get_json_params();
		$result     = self::persist_scan( $profile_id, $body['scan'] ?? array() );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Admin AJAX: save scan results (preferred over REST on restricted hosts).
	 */
	public static function ajax_save_scan() {
		if ( ! self::can_manage() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		check_ajax_referer( 'tcw_save_rive_scan' );

		$profile_id = isset( $_POST['profile_id'] ) ? absint( $_POST['profile_id'] ) : 0;
		$scan_raw   = array();

		if ( isset( $_POST['scan'] ) ) {
			$decoded = json_decode( wp_unslash( (string) $_POST['scan'] ), true );
			if ( is_array( $decoded ) ) {
				$scan_raw = $decoded;
			}
		}

		$result = self::persist_scan( $profile_id, $scan_raw );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
					'code'    => $result->get_error_code(),
				),
				(int) ( $result->get_error_data()['status'] ?? 400 )
			);
		}

		wp_send_json_success( $result );
	}

	/**
	 * Parse tap triggers meta to string array.
	 *
	 * @param mixed $raw Raw meta.
	 * @return string[]
	 */
	public static function parse_tap_triggers( $raw ) {
		if ( is_array( $raw ) ) {
			$list = $raw;
		} else {
			$list = json_decode( (string) $raw, true );
		}

		if ( ! is_array( $list ) ) {
			return array();
		}

		$clean = array();
		foreach ( $list as $item ) {
			$name = self::sanitize_input_name( $item );
			if ( $name ) {
				$clean[] = $name;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	/**
	 * Sanitize profile Rive settings from POST.
	 *
	 * @param array<string, mixed> $data POST data.
	 * @return array<string, mixed>
	 */
	public static function sanitize_profile_settings( $data ) {
		$clean = array(
			'rive_state_machine' => '',
			'rive_hover_input'   => '',
			'rive_entry_trigger' => '',
			'rive_tap_triggers'  => array(),
		);

		if ( isset( $data['rive_state_machine'] ) ) {
			$clean['rive_state_machine'] = self::sanitize_input_name( $data['rive_state_machine'] );
		}

		if ( isset( $data['rive_hover_input'] ) ) {
			$clean['rive_hover_input'] = self::sanitize_input_name( $data['rive_hover_input'] );
		}

		if ( isset( $data['rive_entry_trigger'] ) ) {
			$clean['rive_entry_trigger'] = self::sanitize_input_name( $data['rive_entry_trigger'] );
		}

		if ( isset( $data['rive_tap_triggers'] ) ) {
			if ( is_array( $data['rive_tap_triggers'] ) ) {
				$clean['rive_tap_triggers'] = self::parse_tap_triggers( $data['rive_tap_triggers'] );
			} else {
				$parts = explode( ',', (string) $data['rive_tap_triggers'] );
				$clean['rive_tap_triggers'] = self::parse_tap_triggers( $parts );
			}
		}

		return $clean;
	}

	/**
	 * Validate settings against scan — drop unknown input names.
	 *
	 * @param array<string, mixed> $settings Profile rive settings.
	 * @param array<string, mixed> $scan     Scan cache.
	 * @return array<string, mixed>
	 */
	public static function validate_settings_against_scan( $settings, $scan ) {
		if ( empty( $scan['state_machines'] ) ) {
			return $settings;
		}

		$machine = $settings['rive_state_machine'] ?: self::default_state_machine( $scan );
		$inputs  = self::inputs_for_machine( $scan, $machine );

		$names = array();
		$types = array();
		foreach ( $inputs as $input ) {
			$names[] = $input['name'];
			$types[ $input['name'] ] = $input['type'];
		}

		if ( $settings['rive_hover_input'] && ! in_array( $settings['rive_hover_input'], $names, true ) ) {
			$settings['rive_hover_input'] = '';
		}
		if ( $settings['rive_hover_input'] && 'boolean' !== ( $types[ $settings['rive_hover_input'] ] ?? '' ) ) {
			$settings['rive_hover_input'] = '';
		}

		if ( $settings['rive_entry_trigger'] && ! in_array( $settings['rive_entry_trigger'], $names, true ) ) {
			$settings['rive_entry_trigger'] = '';
		}

		$valid_taps = array();
		foreach ( $settings['rive_tap_triggers'] as $trigger ) {
			if ( in_array( $trigger, $names, true ) && 'trigger' === ( $types[ $trigger ] ?? '' ) ) {
				$valid_taps[] = $trigger;
			}
		}
		$settings['rive_tap_triggers'] = $valid_taps;

		$settings['rive_state_machine'] = in_array( $machine, wp_list_pluck( $scan['state_machines'], 'name' ), true )
			? $machine
			: self::default_state_machine( $scan );

		return $settings;
	}

	/**
	 * Admin editor payload.
	 *
	 * @param array<string, mixed> $profile Profile.
	 * @return array<string, mixed>
	 */
	public static function get_admin_payload( $profile ) {
		$slug    = $profile['location_slug'] ?? '';
		$scan    = self::get_scan( (int) ( $profile['id'] ?? 0 ) );
		$settings = array(
			'rive_state_machine' => (string) ( $profile['rive_state_machine'] ?? '' ),
			'rive_hover_input'   => (string) ( $profile['rive_hover_input'] ?? '' ),
			'rive_entry_trigger' => (string) ( $profile['rive_entry_trigger'] ?? '' ),
			'rive_tap_triggers'  => $profile['rive_tap_triggers'] ?? array(),
		);

		return array(
			'hasFile'    => self::file_exists( $slug ),
			'fileUrl'    => self::get_file_url( $slug ),
			'slug'       => $slug,
			'scan'       => $scan,
			'settings'   => $settings,
			'scanned_at' => ! empty( $scan['scanned_at'] ) ? (int) $scan['scanned_at'] : 0,
		);
	}

	/**
	 * Compact frontend config (only when Rive file + mapping exists).
	 *
	 * @param array<string, mixed> $profile Profile.
	 * @return array<string, mixed>|null
	 */
	public static function get_frontend_config( $profile ) {
		$slug = $profile['location_slug'] ?? '';
		if ( ! self::file_exists( $slug ) ) {
			return null;
		}

		$scan = self::get_scan( (int) ( $profile['id'] ?? 0 ) );
		$settings = array(
			'rive_state_machine' => (string) ( $profile['rive_state_machine'] ?? '' ),
			'rive_hover_input'   => (string) ( $profile['rive_hover_input'] ?? '' ),
			'rive_entry_trigger' => (string) ( $profile['rive_entry_trigger'] ?? '' ),
			'rive_tap_triggers'  => is_array( $profile['rive_tap_triggers'] ?? null )
				? $profile['rive_tap_triggers']
				: self::parse_tap_triggers( $profile['rive_tap_triggers'] ?? '' ),
		);

		if ( ! empty( $scan['state_machines'] ) ) {
			$settings = self::validate_settings_against_scan( $settings, $scan );
		}

		$state_machine = $settings['rive_state_machine'] ?: self::default_state_machine( $scan );
		$hover           = $settings['rive_hover_input'];
		$entry           = $settings['rive_entry_trigger'];
		$tap_triggers    = $settings['rive_tap_triggers'];

		if ( ! $hover && ! $entry && empty( $tap_triggers ) && empty( $scan ) ) {
			return null;
		}

		return array(
			'stateMachine' => $state_machine,
			'hoverInput'   => $hover,
			'entryTrigger' => $entry,
			'tapTriggers'  => array_values( $tap_triggers ),
		);
	}
}
