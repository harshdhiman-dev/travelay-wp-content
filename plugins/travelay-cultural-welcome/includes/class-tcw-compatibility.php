<?php
/**
 * Site compatibility diagnostics for fresh installs and troubleshooting.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Compatibility
 */
class TCW_Compatibility {

	public const TRANSIENT_KEY = 'tcw_compat_last_report';

	/**
	 * Register AJAX handler.
	 */
	public static function register() {
		add_action( 'wp_ajax_tcw_run_compatibility', array( __CLASS__, 'ajax_run' ) );
	}

	/**
	 * AJAX: run full compatibility report.
	 */
	public static function ajax_run() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'travelay-cultural-welcome' ) ), 403 );
		}

		check_ajax_referer( 'tcw_run_compatibility', 'nonce' );

		$report = self::run();
		set_transient( self::TRANSIENT_KEY, $report, HOUR_IN_SECONDS );

		wp_send_json_success( $report );
	}

	/**
	 * Get last cached report if any.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_cached_report() {
		$cached = get_transient( self::TRANSIENT_KEY );
		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * Run all compatibility checks.
	 *
	 * @return array<string, mixed>
	 */
	public static function run() {
		$groups = array(
			self::group_environment(),
			self::group_configuration(),
			self::group_profiles(),
			self::group_integrations(),
			self::group_technical(),
		);

		$summary = array(
			'pass' => 0,
			'warn' => 0,
			'fail' => 0,
			'info' => 0,
		);

		foreach ( $groups as $group ) {
			foreach ( $group['checks'] as $check ) {
				$status = $check['status'] ?? 'info';
				if ( isset( $summary[ $status ] ) ) {
					++$summary[ $status ];
				}
			}
		}

		return array(
			'run_at'  => time(),
			'summary' => $summary,
			'groups'  => $groups,
			'verdict' => self::verdict_message( $summary ),
		);
	}

	/**
	 * Human verdict from summary counts.
	 *
	 * @param array<string, int> $summary Summary counts.
	 * @return string
	 */
	private static function verdict_message( $summary ) {
		if ( $summary['fail'] > 0 ) {
			return sprintf(
				/* translators: %d: number of critical issues */
				__( '%d critical issue(s) need attention before welcomes will work reliably.', 'travelay-cultural-welcome' ),
				(int) $summary['fail']
			);
		}

		if ( $summary['warn'] > 0 ) {
			return sprintf(
				/* translators: %d: number of warnings */
				__( 'Site is mostly ready — review %d warning(s) below for the best experience.', 'travelay-cultural-welcome' ),
				(int) $summary['warn']
			);
		}

		return __( 'All checks passed — your site looks ready for Cultural Welcome.', 'travelay-cultural-welcome' );
	}

	/**
	 * Build a single check row.
	 *
	 * @param string $id      Check ID.
	 * @param string $label   Label.
	 * @param string $status  pass|warn|fail|info.
	 * @param string $message Message.
	 * @param string $fix     Optional fix hint.
	 * @param string $url     Optional admin URL.
	 * @return array<string, string>
	 */
	private static function check( $id, $label, $status, $message, $fix = '', $url = '' ) {
		return array(
			'id'      => $id,
			'label'   => $label,
			'status'  => $status,
			'message' => $message,
			'fix'     => $fix,
			'url'     => $url,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function group_environment() {
		$checks   = array();
		$php_ok   = version_compare( PHP_VERSION, '7.4', '>=' );
		$checks[] = self::check(
			'php_version',
			__( 'PHP version', 'travelay-cultural-welcome' ),
			$php_ok ? 'pass' : 'fail',
			$php_ok
				? sprintf( __( 'PHP %s meets the minimum (7.4+).', 'travelay-cultural-welcome' ), PHP_VERSION )
				: sprintf( __( 'PHP %s is below 7.4.', 'travelay-cultural-welcome' ), PHP_VERSION ),
			$php_ok ? '' : __( 'Upgrade PHP on your hosting account.', 'travelay-cultural-welcome' )
		);

		global $wp_version;
		$wp_ok    = version_compare( $wp_version, '5.8', '>=' );
		$checks[] = self::check(
			'wp_version',
			__( 'WordPress version', 'travelay-cultural-welcome' ),
			$wp_ok ? 'pass' : 'fail',
			$wp_ok
				? sprintf( __( 'WordPress %s meets the minimum (5.8+).', 'travelay-cultural-welcome' ), $wp_version )
				: sprintf( __( 'WordPress %s is below 5.8.', 'travelay-cultural-welcome' ), $wp_version ),
			$wp_ok ? '' : __( 'Update WordPress to a supported version.', 'travelay-cultural-welcome' )
		);

		$zip_ok   = class_exists( 'ZipArchive' );
		$checks[] = self::check(
			'zip_extension',
			__( 'ZipArchive (dotLottie)', 'travelay-cultural-welcome' ),
			$zip_ok ? 'pass' : 'warn',
			$zip_ok
				? __( 'ZipArchive is available for .lottie uploads.', 'travelay-cultural-welcome' )
				: __( 'ZipArchive is missing — .lottie extraction will not work.', 'travelay-cultural-welcome' ),
			$zip_ok ? '' : __( 'Ask your host to enable the PHP zip extension.', 'travelay-cultural-welcome' )
		);

		$lottie_dir = TCW_PLUGIN_DIR . 'assets/avatars/lottie/';
		$writable   = is_dir( $lottie_dir ) && is_writable( $lottie_dir );
		$checks[]   = self::check(
			'lottie_dir_writable',
			__( 'Lottie folder writable', 'travelay-cultural-welcome' ),
			$writable ? 'pass' : 'warn',
			$writable
				? __( 'Lottie cache folder is writable.', 'travelay-cultural-welcome' )
				: __( 'Cannot write extracted .json beside .lottie files.', 'travelay-cultural-welcome' ),
			$writable ? '' : __( 'Set wp-content/plugins/travelay-cultural-welcome/assets/avatars/lottie/ to writable.', 'travelay-cultural-welcome' )
		);

		return array(
			'id'     => 'environment',
			'label'  => __( 'Environment', 'travelay-cultural-welcome' ),
			'checks' => $checks,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function group_configuration() {
		$settings = TCW_Settings::get();
		$checks   = array();

		$enabled = ! empty( $settings['enabled'] );
		$checks[] = self::check(
			'plugin_enabled',
			__( 'Plugin enabled', 'travelay-cultural-welcome' ),
			$enabled ? 'pass' : 'fail',
			$enabled
				? __( 'Cultural Welcome is enabled globally.', 'travelay-cultural-welcome' )
				: __( 'Plugin is disabled — no welcomes will appear.', 'travelay-cultural-welcome' ),
			__( 'Enable “Show cultural welcomes” in Settings below.', 'travelay-cultural-welcome' ),
			admin_url( 'admin.php?page=tcw-settings' )
		);

		$z_index = (int) ( $settings['z_index'] ?? 0 );
		$z_ok    = $z_index >= 1000000;
		$checks[] = self::check(
			'z_index',
			__( 'Overlay z-index', 'travelay-cultural-welcome' ),
			$z_ok ? 'pass' : 'warn',
			$z_ok
				? sprintf( __( 'Z-index is %d (above most overlays).', 'travelay-cultural-welcome' ), $z_index )
				: sprintf( __( 'Z-index %d may sit behind theme or booking overlays.', 'travelay-cultural-welcome' ), $z_index ),
			$z_ok ? '' : __( 'Raise z-index to at least 2147483000 in Advanced settings.', 'travelay-cultural-welcome' ),
			admin_url( 'admin.php?page=tcw-settings' )
		);

		$debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;
		$checks[]      = self::check(
			'wp_debug_display',
			__( 'WP_DEBUG_DISPLAY', 'travelay-cultural-welcome' ),
			$debug_display ? 'warn' : 'pass',
			$debug_display
				? __( 'Debug output is shown on screen — can corrupt REST JSON responses.', 'travelay-cultural-welcome' )
				: __( 'Debug display is off (recommended for production).', 'travelay-cultural-welcome' ),
			$debug_display ? __( 'Set WP_DEBUG_DISPLAY to false in wp-config.php on production.', 'travelay-cultural-welcome' ) : ''
		);

		if ( TCW_Booking_Guard::amadex_is_active() ) {
			$checks[] = self::check(
				'amadex_detected',
				__( 'Amadex booking guard', 'travelay-cultural-welcome' ),
				'info',
				__( 'Amadex detected — welcomes are suppressed on booking/payment pages.', 'travelay-cultural-welcome' )
			);
		}

		return array(
			'id'     => 'configuration',
			'label'  => __( 'Configuration', 'travelay-cultural-welcome' ),
			'checks' => $checks,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function group_profiles() {
		$checks  = array();
		$stats   = self::profile_stats();
		$dash    = admin_url( 'admin.php?page=tcw-dashboard' );

		if ( 0 === $stats['total'] ) {
			$checks[] = self::check(
				'profiles_exist',
				__( 'Welcome profiles', 'travelay-cultural-welcome' ),
				'fail',
				__( 'No profiles found.', 'travelay-cultural-welcome' ),
				__( 'Run Sync All Pages or Sync Country Templates on the Profiles screen.', 'travelay-cultural-welcome' ),
				$dash
			);
		} else {
			$checks[] = self::check(
				'profiles_exist',
				__( 'Welcome profiles', 'travelay-cultural-welcome' ),
				'pass',
				sprintf(
					/* translators: %d: profile count */
					__( '%d profile(s) in the library.', 'travelay-cultural-welcome' ),
					$stats['total']
				)
			);
		}

		if ( $stats['total'] > 0 && 0 === $stats['ready'] ) {
			$checks[] = self::check(
				'profiles_ready',
				__( 'Live & enabled profiles', 'travelay-cultural-welcome' ),
				'fail',
				__( 'No profiles are both Live and Enabled — welcomes will not show.', 'travelay-cultural-welcome' ),
				__( 'Edit profiles: set Status → Live and enable each page you want.', 'travelay-cultural-welcome' ),
				$dash
			);
		} elseif ( $stats['ready'] > 0 ) {
			$checks[] = self::check(
				'profiles_ready',
				__( 'Live & enabled profiles', 'travelay-cultural-welcome' ),
				'pass',
				sprintf(
					/* translators: %d: ready profile count */
					__( '%d profile(s) ready to display on the frontend.', 'travelay-cultural-welcome' ),
					$stats['ready']
				)
			);
		}

		if ( $stats['reviewed'] > 0 ) {
			$checks[] = self::check(
				'profiles_reviewed',
				__( 'Reviewed / disabled profiles', 'travelay-cultural-welcome' ),
				'warn',
				sprintf(
					/* translators: %d: count */
					__( '%d profile(s) are Reviewed or disabled after sync.', 'travelay-cultural-welcome' ),
					$stats['reviewed']
				),
				__( 'Enable and set Live on the pages where you want welcomes.', 'travelay-cultural-welcome' ),
				$dash
			);
		}

		if ( $stats['broken_links'] > 0 ) {
			$checks[] = self::check(
				'broken_page_links',
				__( 'Broken page links', 'travelay-cultural-welcome' ),
				'warn',
				sprintf(
					/* translators: %d: count */
					__( '%d profile(s) link to deleted or missing posts.', 'travelay-cultural-welcome' ),
					$stats['broken_links']
				),
				__( 'Re-sync pages or update Linked Page ID on affected profiles.', 'travelay-cultural-welcome' ),
				$dash
			);
		}

		$published_pages = self::count_published( array( 'page' ) );
		if ( $published_pages > $stats['total'] + 5 && $stats['total'] > 0 ) {
			$checks[] = self::check(
				'content_gap',
				__( 'Pages without profiles', 'travelay-cultural-welcome' ),
				'warn',
				sprintf(
					/* translators: 1: page count, 2: profile count */
					__( 'Site has %1$d published pages but only %2$d profiles.', 'travelay-cultural-welcome' ),
					$published_pages,
					$stats['total']
				),
				__( 'Run Sync All Pages to import missing content.', 'travelay-cultural-welcome' ),
				$dash
			);
		} elseif ( $published_pages > 0 && $stats['total'] > 0 ) {
			$checks[] = self::check(
				'content_gap',
				__( 'Page coverage', 'travelay-cultural-welcome' ),
				'pass',
				sprintf(
					/* translators: 1: profiles, 2: pages */
					__( '%1$d profiles for %2$d published pages.', 'travelay-cultural-welcome' ),
					$stats['total'],
					$published_pages
				)
			);
		}

		return array(
			'id'     => 'profiles',
			'label'  => __( 'Profiles & Pages', 'travelay-cultural-welcome' ),
			'checks' => $checks,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function group_integrations() {
		$settings = TCW_Settings::get();
		$checks   = array();

		if ( ! empty( $settings['enable_voice_welcome'] ) ) {
			$key_ok = TCW_Google_API::is_configured();
			$checks[] = self::check(
				'google_api_key',
				__( 'Google API key (voice)', 'travelay-cultural-welcome' ),
				$key_ok ? 'pass' : 'fail',
				$key_ok
					? __( 'Google API key is configured.', 'travelay-cultural-welcome' )
					: __( 'Voice welcome is on but no API key is set.', 'travelay-cultural-welcome' ),
				__( 'Add key in Voice & Sound settings or TCW_GOOGLE_API_KEY in wp-config.php.', 'travelay-cultural-welcome' ),
				admin_url( 'admin.php?page=tcw-settings' )
			);

			if ( $key_ok ) {
				$meta     = TCW_Voice_Catalog::get_meta();
				$synced   = ! empty( $meta['synced_at'] );
				$checks[] = self::check(
					'voice_catalog',
					__( 'Voice catalog synced', 'travelay-cultural-welcome' ),
					$synced ? 'pass' : 'warn',
					$synced
						? sprintf(
							/* translators: %d: voice count */
							__( 'Catalog synced (%d voices).', 'travelay-cultural-welcome' ),
							(int) ( $meta['voice_count'] ?? 0 )
						)
						: __( 'Voice catalog has not been refreshed from Google.', 'travelay-cultural-welcome' ),
					$synced ? '' : __( 'Click Refresh Voice Catalog after saving your API key.', 'travelay-cultural-welcome' ),
					admin_url( 'admin.php?page=tcw-settings' )
				);
			}
		}

		if ( ! empty( $settings['enable_ip_welcome'] ) ) {
			$country_profiles = self::count_country_profiles();
			$checks[]         = self::check(
				'ip_welcome_profiles',
				__( 'IP welcome country profiles', 'travelay-cultural-welcome' ),
				$country_profiles > 0 ? 'pass' : 'warn',
				$country_profiles > 0
					? sprintf(
						/* translators: %d: count */
						__( '%d country profile(s) available for IP matching.', 'travelay-cultural-welcome' ),
						$country_profiles
					)
					: __( 'IP welcome is on but no country profiles with codes exist.', 'travelay-cultural-welcome' ),
				__( 'Sync Country Templates or add profiles with ISO country codes.', 'travelay-cultural-welcome' ),
				admin_url( 'admin.php?page=tcw-dashboard' )
			);
		}

		if ( ! empty( $settings['enable_rive'] ) ) {
			$riv_count = self::count_files_in( TCW_PLUGIN_DIR . 'assets/avatars/rive/', 'riv' );
			$checks[]  = self::check(
				'rive_assets',
				__( 'Rive avatar files', 'travelay-cultural-welcome' ),
				$riv_count > 0 ? 'pass' : 'warn',
				$riv_count > 0
					? sprintf(
						/* translators: %d: file count */
						__( '%d .riv file(s) found in assets/avatars/rive/.', 'travelay-cultural-welcome' ),
						$riv_count
					)
					: __( 'Rive is enabled but no .riv files are uploaded.', 'travelay-cultural-welcome' ),
				__( 'Upload {slug}.riv files matching profile slugs.', 'travelay-cultural-welcome' )
			);
		}

		if ( ! empty( $settings['enable_lottie'] ) ) {
			$lottie_count = self::count_files_in( TCW_PLUGIN_DIR . 'assets/avatars/lottie/', 'lottie' )
				+ self::count_files_in( TCW_PLUGIN_DIR . 'assets/avatars/lottie/', 'json' );
			$checks[]     = self::check(
				'lottie_assets',
				__( 'Lottie avatar files', 'travelay-cultural-welcome' ),
				$lottie_count > 0 ? 'pass' : 'info',
				$lottie_count > 0
					? sprintf(
						/* translators: %d: file count */
						__( '%d custom Lottie file(s) found (generated fallback used otherwise).', 'travelay-cultural-welcome' ),
						$lottie_count
					)
					: __( 'No custom Lottie files — plugin will use generated SVG/Lottie fallback.', 'travelay-cultural-welcome' )
			);
		}

		return array(
			'id'     => 'integrations',
			'label'  => __( 'Integrations', 'travelay-cultural-welcome' ),
			'checks' => $checks,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function group_technical() {
		$checks = array();

		$rest_ok = self::check_rest_api();
		$checks[] = self::check(
			'rest_api',
			__( 'WordPress REST API', 'travelay-cultural-welcome' ),
			$rest_ok['status'],
			$rest_ok['message'],
			$rest_ok['fix'] ?? '',
			$rest_ok['url'] ?? ''
		);

		$lottie_ok = self::check_lottie_rest();
		$checks[]  = self::check(
			'rest_lottie_clean',
			__( 'Lottie REST JSON', 'travelay-cultural-welcome' ),
			$lottie_ok['status'],
			$lottie_ok['message'],
			$lottie_ok['fix'] ?? ''
		);

		$frontend = self::check_frontend_assets();
		$checks[] = self::check(
			'frontend_assets',
			__( 'Frontend welcome assets', 'travelay-cultural-welcome' ),
			$frontend['status'],
			$frontend['message'],
			$frontend['fix'] ?? '',
			$frontend['url'] ?? ''
		);

		if ( self::consent_plugin_detected() ) {
			$checks[] = self::check(
				'consent_plugin',
				__( 'Cookie consent plugin', 'travelay-cultural-welcome' ),
				'info',
				__( 'A cookie/consent plugin was detected — first-visit timing may depend on consent banners.', 'travelay-cultural-welcome' ),
				__( 'Test welcome on a Live profile page in a private browser window.', 'travelay-cultural-welcome' )
			);
		}

		return array(
			'id'     => 'technical',
			'label'  => __( 'API & Frontend', 'travelay-cultural-welcome' ),
			'checks' => $checks,
		);
	}

	/**
	 * Profile counts for diagnostics.
	 *
	 * @return array<string, int>
	 */
	private static function profile_stats() {
		$posts = get_posts(
			array(
				'post_type'      => TCW_Profile::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$stats = array(
			'total'         => count( $posts ),
			'ready'         => 0,
			'reviewed'      => 0,
			'broken_links'  => 0,
		);

		foreach ( $posts as $post_id ) {
			$profile = TCW_Profile::format_post( get_post( $post_id ) );
			if ( ! empty( $profile['is_enabled'] ) && 'live' === $profile['status'] ) {
				++$stats['ready'];
			}
			if ( ! $profile['is_enabled'] || 'live' !== $profile['status'] ) {
				++$stats['reviewed'];
			}
			if ( $profile['page_id'] && ! get_post( $profile['page_id'] ) ) {
				++$stats['broken_links'];
			}
		}

		return $stats;
	}

	/**
	 * @param string[] $post_types Post types.
	 * @return int
	 */
	private static function count_published( $post_types ) {
		$query = new WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * @return int
	 */
	private static function count_country_profiles() {
		$query = new WP_Query(
			array(
				'post_type'      => TCW_Profile::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_tcw_country_code',
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

		return (int) $query->found_posts;
	}

	/**
	 * @param string $dir  Directory.
	 * @param string $ext  Extension without dot.
	 * @return int
	 */
	private static function count_files_in( $dir, $ext ) {
		if ( ! is_dir( $dir ) ) {
			return 0;
		}

		$files = glob( trailingslashit( $dir ) . '*.' . $ext );
		return is_array( $files ) ? count( $files ) : 0;
	}

	/**
	 * @return array<string, string>
	 */
	private static function check_rest_api() {
		$url      = rest_url( 'wp/v2/types' );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => 'fail',
				'message' => $response->get_error_message(),
				'fix'     => __( 'Ensure REST API is not blocked by security plugins or server rules.', 'travelay-cultural-welcome' ),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return array(
				'status'  => 'pass',
				'message' => __( 'REST API responds successfully.', 'travelay-cultural-welcome' ),
			);
		}

		return array(
			'status'  => 'fail',
			'message' => sprintf(
				/* translators: %d: HTTP status code */
				__( 'REST API returned HTTP %d.', 'travelay-cultural-welcome' ),
				$code
			),
			'fix'     => __( 'Check permalink settings and security plugins.', 'travelay-cultural-welcome' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private static function check_lottie_rest() {
		$slug = 'usa';
		$posts = get_posts(
			array(
				'post_type'      => TCW_Profile::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_tcw_location_slug',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		if ( ! empty( $posts ) ) {
			$profile = TCW_Profile::format_post( $posts[0] );
			if ( ! empty( $profile['location_slug'] ) ) {
				$slug = sanitize_title( $profile['location_slug'] );
				if ( false !== strpos( $slug, '/' ) ) {
					$parts = explode( '/', $slug );
					$slug  = end( $parts );
				}
			}
		}

		$url      = rest_url( 'tcw/v1/lottie/' . $slug );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 15,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => 'warn',
				'message' => $response->get_error_message(),
			);
		}

		$body = (string) wp_remote_retrieve_body( $response );
		$trim = ltrim( $body );

		if ( '' === $trim ) {
			return array(
				'status'  => 'warn',
				'message' => __( 'Lottie endpoint returned an empty response.', 'travelay-cultural-welcome' ),
			);
		}

		if ( '{' !== $trim[0] ) {
			return array(
				'status'  => 'fail',
				'message' => __( 'Lottie REST response contains HTML or noise before JSON — animations may fail.', 'travelay-cultural-welcome' ),
				'fix'     => __( 'Disable WP_DEBUG_DISPLAY and check plugins that inject output into REST responses.', 'travelay-cultural-welcome' ),
			);
		}

		$decoded = json_decode( $body, true );
		if ( ! is_array( $decoded ) ) {
			return array(
				'status'  => 'fail',
				'message' => __( 'Lottie REST response is not valid JSON.', 'travelay-cultural-welcome' ),
				'fix'     => __( 'Check for PHP notices corrupting API output.', 'travelay-cultural-welcome' ),
			);
		}

		return array(
			'status'  => 'pass',
			'message' => sprintf(
				/* translators: %s: slug tested */
				__( 'Lottie endpoint returns clean JSON (tested: %s).', 'travelay-cultural-welcome' ),
				$slug
			),
		);
	}

	/**
	 * Spot-check a Live profile page for welcome script markup.
	 *
	 * @return array<string, string>
	 */
	private static function check_frontend_assets() {
		$test_url = self::find_test_frontend_url();

		if ( ! $test_url ) {
			return array(
				'status'  => 'warn',
				'message' => __( 'No Live + Enabled profile with a viewable page to test.', 'travelay-cultural-welcome' ),
				'fix'     => __( 'Enable at least one profile and set Status → Live, then re-run.', 'travelay-cultural-welcome' ),
				'url'     => admin_url( 'admin.php?page=tcw-dashboard' ),
			);
		}

		$response = wp_remote_get(
			$test_url,
			array(
				'timeout'   => 20,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
				'user-agent' => 'TravelayCulturalWelcome/Compatibility',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => 'warn',
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Could not fetch test page: %s', 'travelay-cultural-welcome' ),
					$response->get_error_message()
				),
				'url'     => $test_url,
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return array(
				'status'  => 'warn',
				'message' => sprintf(
					/* translators: 1: HTTP code, 2: URL */
					__( 'Test page returned HTTP %1$d (%2$s).', 'travelay-cultural-welcome' ),
					$code,
					wp_parse_url( $test_url, PHP_URL_PATH ) ?: $test_url
				),
				'url'     => $test_url,
			);
		}

		$body = (string) wp_remote_retrieve_body( $response );
		$has_script = false !== strpos( $body, 'tcw-welcome.js' ) || false !== strpos( $body, 'tcw-welcome' );
		$has_root   = false !== strpos( $body, 'tcw-welcome-root' );

		if ( $has_script || $has_root ) {
			return array(
				'status'  => 'pass',
				'message' => sprintf(
					/* translators: %s: page path */
					__( 'Welcome assets detected on %s.', 'travelay-cultural-welcome' ),
					wp_parse_url( $test_url, PHP_URL_PATH ) ?: __( 'test page', 'travelay-cultural-welcome' )
				),
				'url'     => $test_url,
			);
		}

		return array(
			'status'  => 'warn',
			'message' => sprintf(
				/* translators: %s: URL path */
				__( 'Welcome scripts were not found on %s — cache, consent, or matcher may block output.', 'travelay-cultural-welcome' ),
				wp_parse_url( $test_url, PHP_URL_PATH ) ?: $test_url
			),
			'fix'     => __( 'Purge page cache, confirm profile is Live + Enabled, and test in a private window.', 'travelay-cultural-welcome' ),
			'url'     => $test_url,
		);
	}

	/**
	 * Find first Live+Enabled profile permalink for frontend test.
	 *
	 * @return string
	 */
	private static function find_test_frontend_url() {
		$query = new WP_Query(
			array(
				'post_type'      => TCW_Profile::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_tcw_is_enabled',
						'value' => '1',
					),
					array(
						'key'   => '_tcw_status',
						'value' => 'live',
					),
				),
			)
		);

		foreach ( $query->posts as $post ) {
			$profile = TCW_Profile::format_post( $post );
			if ( $profile['page_id'] ) {
				$permalink = get_permalink( $profile['page_id'] );
				if ( $permalink ) {
					return $permalink;
				}
			}
		}

		return '';
	}

	/**
	 * @return bool
	 */
	private static function consent_plugin_detected() {
		return defined( 'CMPLZ_VERSION' )
			|| class_exists( 'Cookie_Law_Info' )
			|| defined( 'CLI_VERSION' )
			|| function_exists( 'cookiebot' );
	}
}
