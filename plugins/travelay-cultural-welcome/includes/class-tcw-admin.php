<?php
/**
 * Admin settings and profile management UI.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Admin
 */
class TCW_Admin {

	/**
	 * Register admin hooks.
	 */
	public static function register() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_tcw_save_profile', array( __CLASS__, 'handle_save_profile' ) );
		add_action( 'admin_post_tcw_delete_profile', array( __CLASS__, 'handle_delete_profile' ) );
		add_action( 'admin_post_tcw_bulk_delete_profiles', array( __CLASS__, 'handle_bulk_delete_profiles' ) );
		add_action( 'admin_post_tcw_seed_profiles', array( __CLASS__, 'handle_seed_profiles' ) );
		add_action( 'admin_post_tcw_refresh_voice_catalog', array( __CLASS__, 'handle_refresh_voice_catalog' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register top-level menu.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Cultural Welcome', 'travelay-cultural-welcome' ),
			__( 'Cultural Welcome', 'travelay-cultural-welcome' ),
			'manage_options',
			'tcw-dashboard',
			array( __CLASS__, 'render_dashboard_page' ),
			'dashicons-id-alt',
			58
		);

		add_submenu_page(
			'tcw-dashboard',
			__( 'Welcome Profiles', 'travelay-cultural-welcome' ),
			__( 'Profiles', 'travelay-cultural-welcome' ),
			'manage_options',
			'tcw-dashboard',
			array( __CLASS__, 'render_dashboard_page' )
		);

		add_submenu_page(
			'tcw-dashboard',
			__( 'Add Profile', 'travelay-cultural-welcome' ),
			__( 'Add Profile', 'travelay-cultural-welcome' ),
			'manage_options',
			'tcw-edit-profile',
			array( __CLASS__, 'render_edit_profile_page' )
		);

		add_submenu_page(
			'tcw-dashboard',
			__( 'Settings', 'travelay-cultural-welcome' ),
			__( 'Settings', 'travelay-cultural-welcome' ),
			'manage_options',
			'tcw-settings',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Render shared admin hero header.
	 *
	 * @param array<string, mixed> $args Hero arguments.
	 */
	public static function render_hero( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'   => '',
				'tagline' => '',
				'icon'    => 'dashicons-welcome-view-site',
				'chips'   => array(),
				'actions' => '',
			)
		);

		$tcw_hero_title    = (string) $args['title'];
		$tcw_hero_tagline  = (string) $args['tagline'];
		$tcw_hero_icon     = (string) $args['icon'];
		$tcw_hero_chips    = (array) $args['chips'];
		$tcw_hero_actions  = (string) $args['actions'];

		include TCW_PLUGIN_DIR . 'templates/partials/admin-hero.php';
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		register_setting( 'tcw_settings_group', TCW_Settings::OPTION_KEY, array(
			'type'              => 'array',
			'sanitize_callback' => array( 'TCW_Settings', 'sanitize' ),
			'default'           => TCW_Settings::defaults(),
		) );
	}

	/**
	 * i18n strings for the Rive admin scanner.
	 *
	 * @return array<string, string>
	 */
	private static function rive_admin_i18n() {
		return array(
			'scan'        => __( 'Scan Rive inputs', 'travelay-cultural-welcome' ),
			'scanning'    => __( 'Scanning…', 'travelay-cultural-welcome' ),
			'scanDone'    => __( 'Scan complete. Inputs loaded below.', 'travelay-cultural-welcome' ),
			'scanError'   => __( 'Could not scan Rive file. Check the slug matches your .riv filename.', 'travelay-cultural-welcome' ),
			'noFile'      => __( 'No .riv file found for this slug. Upload assets/avatars/rive/{slug}.riv first.', 'travelay-cultural-welcome' ),
			'saveFirst'   => __( 'Save the profile first to scan Rive inputs.', 'travelay-cultural-welcome' ),
			'none'        => __( '— None —', 'travelay-cultural-welcome' ),
			'tapHelp'     => __( 'Hold Ctrl/Cmd to select multiple triggers. Order follows selection order.', 'travelay-cultural-welcome' ),
			'lastScanned' => __( 'Last scanned:', 'travelay-cultural-welcome' ),
		);
	}

	/**
	 * Build admin payload for the Rive scanner script.
	 *
	 * @param int $profile_id Profile post ID.
	 * @return array<string, mixed>
	 */
	private static function rive_admin_payload( $profile_id ) {
		$profile_id = absint( $profile_id );

		$payload = array(
			'profileId' => $profile_id,
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'ajaxNonce' => wp_create_nonce( 'tcw_save_rive_scan' ),
			'restUrl'   => rest_url( 'tcw/v1/rive/scan' ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'hasFile'   => false,
			'fileUrl'   => '',
			'slug'      => '',
			'scan'      => array(),
			'settings'  => array(
				'rive_state_machine' => '',
				'rive_hover_input'   => '',
				'rive_entry_trigger' => '',
				'rive_tap_triggers'  => array(),
			),
			'scannedAt' => 0,
			'i18n'      => self::rive_admin_i18n(),
		);

		if ( ! $profile_id ) {
			return $payload;
		}

		$post = get_post( $profile_id );
		if ( ! $post || TCW_Profile::POST_TYPE !== $post->post_type ) {
			return $payload;
		}

		$profile = TCW_Profile::format_post( $post );

		return array_merge( $payload, TCW_Rive::get_admin_payload( $profile ) );
	}

	/**
	 * Enqueue admin styles on plugin pages only.
	 *
	 * @param string $hook Hook suffix.
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( false === strpos( $hook, 'tcw-' ) ) {
			return;
		}

		wp_enqueue_style( 'dashicons' );

		if ( 'toplevel_page_tcw-dashboard' === $hook ) {

			wp_enqueue_script(
				'tcw-admin-sync',
				TCW_PLUGIN_URL . 'assets/js/tcw-admin-sync.js',
				array( 'jquery' ),
				TCW_VERSION,
				true
			);

			wp_add_inline_script(
				'tcw-admin-sync',
				'window.tcwAdminSync = ' . wp_json_encode(
					array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'tcw_sync_pages' ),
						'redirectUrl' => admin_url( 'admin.php?page=tcw-dashboard&tcw_notice=synced' ),
						'i18n'        => array(
							'starting' => __( 'Starting sync…', 'travelay-cultural-welcome' ),
							'empty'    => __( 'No published content found for this scope.', 'travelay-cultural-welcome' ),
							'error'    => __( 'Sync failed. Please try again.', 'travelay-cultural-welcome' ),
						),
					)
				) . ';',
				'before'
			);

			wp_enqueue_script(
				'tcw-admin-profiles',
				TCW_PLUGIN_URL . 'assets/js/tcw-admin-profiles.js',
				array( 'jquery' ),
				TCW_VERSION,
				true
			);

			wp_add_inline_script(
				'tcw-admin-profiles',
				'window.tcwAdminProfiles = ' . wp_json_encode(
					array(
						'i18n' => array(
							'showing'      => __( 'Showing %1$d of %2$d profiles', 'travelay-cultural-welcome' ),
							'noneSelected' => __( 'None selected', 'travelay-cultural-welcome' ),
							'selected'     => __( '%d selected', 'travelay-cultural-welcome' ),
							'confirmBulk'  => __( 'Delete %d selected profile(s)? This cannot be undone.', 'travelay-cultural-welcome' ),
						),
					)
				) . ';',
				'before'
			);
		}

		wp_enqueue_style(
			'tcw-admin',
			TCW_PLUGIN_URL . 'assets/css/tcw-admin.css',
			array(),
			TCW_VERSION
		);

		if ( 'cultural-welcome_page_tcw-settings' === $hook ) {
			wp_enqueue_script(
				'tcw-admin-compatibility',
				TCW_PLUGIN_URL . 'assets/js/tcw-admin-compatibility.js',
				array( 'jquery' ),
				TCW_VERSION,
				true
			);

			wp_add_inline_script(
				'tcw-admin-compatibility',
				'window.tcwAdminCompat = ' . wp_json_encode(
					array(
						'ajaxUrl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'tcw_run_compatibility' ),
						'cached'  => TCW_Compatibility::get_cached_report(),
						'i18n'    => array(
							'passed'   => __( 'passed', 'travelay-cultural-welcome' ),
							'warnings' => __( 'warnings', 'travelay-cultural-welcome' ),
							'critical' => __( 'critical', 'travelay-cultural-welcome' ),
							'fix'      => __( 'Suggested fix', 'travelay-cultural-welcome' ),
							'view'     => __( 'Open related screen', 'travelay-cultural-welcome' ),
							'ranAt'    => __( 'Last run: %s', 'travelay-cultural-welcome' ),
							'copy'     => __( 'Copy report', 'travelay-cultural-welcome' ),
							'copied'   => __( 'Copied!', 'travelay-cultural-welcome' ),
							'error'    => __( 'Compatibility check failed. Please try again.', 'travelay-cultural-welcome' ),
						),
					)
				) . ';',
				'before'
			);

			$presets        = TCW_Settings::presets();
			$preset_payload = array();

			foreach ( $presets as $preset_key => $preset_data ) {
				$preset_payload[ $preset_key ] = array(
					'label'       => $preset_data['label'],
					'description' => $preset_data['description'],
				);
				foreach ( TCW_Settings::preset_flag_keys() as $flag_key ) {
					if ( array_key_exists( $flag_key, $preset_data ) ) {
						$preset_payload[ $preset_key ][ $flag_key ] = (bool) $preset_data[ $flag_key ];
					}
				}
			}

			wp_enqueue_script(
				'tcw-admin-presets',
				TCW_PLUGIN_URL . 'assets/js/tcw-admin-presets.js',
				array( 'jquery' ),
				TCW_VERSION,
				true
			);

			wp_add_inline_script(
				'tcw-admin-presets',
				'window.tcwAdminPresets = ' . wp_json_encode(
					array(
						'optionKey' => TCW_Settings::OPTION_KEY,
						'presets'   => $preset_payload,
					)
				) . ';',
				'before'
			);
		}

		if ( 'cultural-welcome_page_tcw-edit-profile' === $hook ) {
			$catalog = TCW_Voice_Catalog::get_catalog();

			wp_enqueue_script(
				'tcw-admin-voice',
				TCW_PLUGIN_URL . 'assets/js/tcw-admin-voice.js',
				array( 'jquery' ),
				TCW_VERSION,
				true
			);

			wp_add_inline_script(
				'tcw-admin-voice',
				'window.tcwVoiceCatalog = ' . wp_json_encode(
					array(
						'languages' => $catalog['languages'] ?? array(),
						'voices'    => $catalog['voices'] ?? array(),
						'features'  => $catalog['features'] ?? array(),
						'synced_at' => $catalog['synced_at'] ?? 0,
						'error'     => $catalog['error'] ?? '',
						'i18n'      => array(
							'autoLanguage'  => __( 'Auto (country default)', 'travelay-cultural-welcome' ),
							'autoVoice'     => __( 'Auto (best match for language)', 'travelay-cultural-welcome' ),
							'allFeatures'   => __( 'All voice technologies', 'travelay-cultural-welcome' ),
							'autoVoiceHint' => __( 'Uses the country default Neural2 voice when auto is selected.', 'travelay-cultural-welcome' ),
						),
					)
				) . ';',
				'before'
			);

			$profile_id = isset( $_GET['profile_id'] ) ? absint( $_GET['profile_id'] ) : 0;
			$rive_data  = self::rive_admin_payload( $profile_id );

			wp_enqueue_script(
				'tcw-admin-rive',
				TCW_PLUGIN_URL . 'assets/js/tcw-admin-rive.js',
				array( 'jquery' ),
				TCW_VERSION,
				true
			);

			wp_add_inline_script(
				'tcw-admin-rive',
				'window.tcwAdminRive = ' . wp_json_encode( $rive_data ) . ';',
				'before'
			);
		}
	}

	/**
	 * Render profiles dashboard.
	 */
	public static function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$profiles = get_posts(
			array(
				'post_type'      => TCW_Profile::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1000,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$settings = TCW_Settings::get();
		$notice   = isset( $_GET['tcw_notice'] ) ? sanitize_key( wp_unslash( $_GET['tcw_notice'] ) ) : '';

		include TCW_PLUGIN_DIR . 'templates/admin-dashboard.php';
	}

	/**
	 * Render profile editor.
	 */
	public static function render_edit_profile_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$profile_id = isset( $_GET['profile_id'] ) ? absint( $_GET['profile_id'] ) : 0;
		$profile    = array(
			'id'                  => 0,
			'entity_type'         => 'country',
			'location_slug'       => '',
			'country_code'        => '',
			'display_name'        => '',
			'gesture'             => 'wave',
			'welcome_message_en'  => '',
			'tone_override'       => 'inherit',
			'trigger_override'    => 'inherit',
			'status'              => 'draft',
			'cultural_notes'      => '',
			'is_enabled'          => false,
			'parent_country_slug' => '',
			'page_id'             => 0,
			'voice_script'        => '',
			'voice_enabled'       => true,
			'voice_language'      => '',
			'voice_name'          => '',
			'voice_speaking_rate' => 0,
			'rive_state_machine'  => '',
			'rive_hover_input'    => '',
			'rive_entry_trigger'  => '',
			'rive_tap_triggers'   => array(),
		);

		if ( $profile_id ) {
			$post = get_post( $profile_id );
			if ( $post && TCW_Profile::POST_TYPE === $post->post_type ) {
				$profile = TCW_Profile::format_post( $post );
				$profile['is_enabled'] = (bool) $profile['is_enabled'];
			}
		}

		$gestures       = TCW_Gestures::all();
		$voice_catalog  = TCW_Voice_Catalog::get_catalog();
		$voice_meta     = TCW_Voice_Catalog::get_meta();

		if ( empty( $profile['voice_language'] ) && ! empty( $profile['voice_name'] ) ) {
			$profile['voice_language'] = TCW_Voice_Catalog::language_for_voice_name( $profile['voice_name'] );
		}

		include TCW_PLUGIN_DIR . 'templates/admin-edit-profile.php';
	}

	/**
	 * Render global settings page.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings       = TCW_Settings::get();
		$voice_catalog  = TCW_Voice_Catalog::get_catalog();
		$voice_meta     = TCW_Voice_Catalog::get_meta();
		$compat_report  = TCW_Compatibility::get_cached_report();
		include TCW_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	/**
	 * Save profile handler.
	 */
	public static function handle_save_profile() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'travelay-cultural-welcome' ) );
		}

		check_admin_referer( 'tcw_save_profile' );

		$profile_id = isset( $_POST['profile_id'] ) ? absint( $_POST['profile_id'] ) : 0;
		$title      = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';

		$postarr = array(
			'post_type'   => TCW_Profile::POST_TYPE,
			'post_title'  => $title,
			'post_status' => 'publish',
		);

		if ( $profile_id ) {
			$existing = get_post( $profile_id );
			if ( ! $existing || TCW_Profile::POST_TYPE !== $existing->post_type ) {
				wp_die( esc_html__( 'Invalid profile.', 'travelay-cultural-welcome' ) );
			}

			$postarr['ID'] = $profile_id;
			$result        = wp_update_post( $postarr, true );
		} else {
			$result = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=tcw-edit-profile&tcw_notice=error' ) );
			exit;
		}

		$data = array(
			'entity_type'         => TCW_Profile::sanitize_entity_type( isset( $_POST['entity_type'] ) ? wp_unslash( $_POST['entity_type'] ) : 'country' ),
			'location_slug'       => isset( $_POST['location_slug'] ) ? sanitize_title( wp_unslash( $_POST['location_slug'] ) ) : '',
			'country_code'        => isset( $_POST['country_code'] ) ? sanitize_text_field( wp_unslash( $_POST['country_code'] ) ) : '',
			'display_name'        => $title,
			'gesture'             => isset( $_POST['gesture'] ) ? sanitize_key( wp_unslash( $_POST['gesture'] ) ) : 'wave',
			'welcome_message_en'  => isset( $_POST['welcome_message_en'] ) ? sanitize_text_field( wp_unslash( $_POST['welcome_message_en'] ) ) : '',
			'tone_override'       => isset( $_POST['tone_override'] ) ? sanitize_key( wp_unslash( $_POST['tone_override'] ) ) : 'inherit',
			'trigger_override'    => isset( $_POST['trigger_override'] ) ? sanitize_key( wp_unslash( $_POST['trigger_override'] ) ) : 'inherit',
			'status'              => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'draft',
			'cultural_notes'      => isset( $_POST['cultural_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cultural_notes'] ) ) : '',
			'is_enabled'          => ! empty( $_POST['is_enabled'] ),
			'parent_country_slug' => isset( $_POST['parent_country_slug'] ) ? sanitize_title( wp_unslash( $_POST['parent_country_slug'] ) ) : '',
			'page_id'             => isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0,
			'voice_script'        => isset( $_POST['voice_script'] ) ? sanitize_textarea_field( wp_unslash( $_POST['voice_script'] ) ) : '',
			'voice_enabled'       => ! empty( $_POST['voice_enabled'] ),
			'voice_language'      => isset( $_POST['voice_language'] ) ? sanitize_text_field( wp_unslash( $_POST['voice_language'] ) ) : '',
			'voice_name'          => isset( $_POST['voice_name'] ) ? sanitize_text_field( wp_unslash( $_POST['voice_name'] ) ) : '',
			'voice_speaking_rate' => isset( $_POST['voice_speaking_rate'] ) ? (float) $_POST['voice_speaking_rate'] : 0,
		);

		TCW_Profile::save_meta( (int) $result, $data );

		$rive_settings = TCW_Rive::sanitize_profile_settings(
			array(
				'rive_state_machine' => isset( $_POST['rive_state_machine'] ) ? wp_unslash( $_POST['rive_state_machine'] ) : '',
				'rive_hover_input'   => isset( $_POST['rive_hover_input'] ) ? wp_unslash( $_POST['rive_hover_input'] ) : '',
				'rive_entry_trigger' => isset( $_POST['rive_entry_trigger'] ) ? wp_unslash( $_POST['rive_entry_trigger'] ) : '',
				'rive_tap_triggers'  => isset( $_POST['rive_tap_triggers'] ) ? wp_unslash( $_POST['rive_tap_triggers'] ) : '',
			)
		);

		$scan = TCW_Rive::get_scan( (int) $result );
		if ( ! empty( $scan['state_machines'] ) ) {
			$rive_settings = TCW_Rive::validate_settings_against_scan( $rive_settings, $scan );
		}

		update_post_meta( (int) $result, TCW_Rive::META_STATE_MACHINE, $rive_settings['rive_state_machine'] );
		update_post_meta( (int) $result, TCW_Rive::META_HOVER_INPUT, $rive_settings['rive_hover_input'] );
		update_post_meta( (int) $result, TCW_Rive::META_ENTRY_TRIGGER, $rive_settings['rive_entry_trigger'] );
		update_post_meta( (int) $result, TCW_Rive::META_TAP_TRIGGERS, wp_json_encode( $rive_settings['rive_tap_triggers'] ) );

		if ( ! empty( $_POST['rive_scan_cache'] ) ) {
			$scan_decoded = json_decode( wp_unslash( (string) $_POST['rive_scan_cache'] ), true );
			if ( is_array( $scan_decoded ) && ! empty( $scan_decoded['state_machines'] ) ) {
				$scan_clean = TCW_Rive::sanitize_scan( $scan_decoded );
				if ( ! empty( $scan_clean['state_machines'] ) ) {
					update_post_meta( (int) $result, TCW_Rive::META_SCAN, $scan_clean );
				}
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=tcw-edit-profile&profile_id=' . (int) $result . '&tcw_notice=saved' ) );
		exit;
	}

	/**
	 * Bulk delete selected profiles.
	 */
	public static function handle_bulk_delete_profiles() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'travelay-cultural-welcome' ) );
		}

		check_admin_referer( 'tcw_bulk_delete_profiles' );

		$ids     = isset( $_POST['profile_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['profile_ids'] ) ) : array();
		$deleted = 0;

		foreach ( array_unique( array_filter( $ids ) ) as $profile_id ) {
			$post = get_post( $profile_id );
			if ( ! $post || TCW_Profile::POST_TYPE !== $post->post_type ) {
				continue;
			}

			if ( wp_delete_post( $profile_id, true ) ) {
				++$deleted;
			}
		}

		wp_safe_redirect(
			admin_url(
				'admin.php?page=tcw-dashboard&tcw_notice=bulk_deleted&count=' . $deleted
			)
		);
		exit;
	}

	/**
	 * Delete profile handler.
	 */
	public static function handle_delete_profile() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'travelay-cultural-welcome' ) );
		}

		$profile_id = isset( $_GET['profile_id'] ) ? absint( $_GET['profile_id'] ) : 0;
		check_admin_referer( 'tcw_delete_profile_' . $profile_id );

		if ( $profile_id ) {
			wp_delete_post( $profile_id, true );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=tcw-dashboard&tcw_notice=deleted' ) );
		exit;
	}

	/**
	 * Refresh Google TTS voice catalog.
	 */
	public static function handle_refresh_voice_catalog() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'travelay-cultural-welcome' ) );
		}

		check_admin_referer( 'tcw_refresh_voice_catalog' );
		TCW_Voice_Catalog::refresh_catalog();

		wp_safe_redirect( admin_url( 'admin.php?page=tcw-settings&tcw_notice=voices_refreshed' ) );
		exit;
	}

	/**
	 * Re-seed profiles handler.
	 */
	public static function handle_seed_profiles() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'travelay-cultural-welcome' ) );
		}

		check_admin_referer( 'tcw_seed_profiles' );
		TCW_Seeder::seed_profiles();

		wp_safe_redirect( admin_url( 'admin.php?page=tcw-dashboard&tcw_notice=seeded' ) );
		exit;
	}
}
