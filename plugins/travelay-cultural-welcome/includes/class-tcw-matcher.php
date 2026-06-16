<?php
/**
 * Match the current page to a welcome profile.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Matcher
 */
class TCW_Matcher {

	/**
	 * Resolve profile for the current request.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function resolve_current() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return null;
		}

		if ( TCW_Booking_Guard::is_booking_flow_request() ) {
			return null;
		}

		$settings = TCW_Settings::get();
		if ( empty( $settings['enabled'] ) ) {
			return null;
		}

		$page_profile = self::resolve_page_profile();
		if ( $page_profile ) {
			$page_profile['match_source'] = 'page';
			return self::finalize_profile( $page_profile );
		}

		if ( ! empty( $settings['enable_ip_welcome'] ) && self::is_ip_welcome_context() ) {
			$ip_profile = self::resolve_ip_profile();
			if ( $ip_profile ) {
				$ip_profile['match_source'] = 'ip';
				return self::finalize_profile( $ip_profile );
			}
		}

		return null;
	}

	/**
	 * Match profile from the current WordPress page slug.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function resolve_page_profile() {
		if ( ! is_singular() ) {
			return null;
		}

		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return null;
		}

		$profile = TCW_Profile::get_by_page_id( $post_id );
		if ( $profile ) {
			return $profile;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return null;
		}

		$entity_type = TCW_Page_Sync::entity_type_for_post( $post );
		$slug        = TCW_Page_Sync::location_slug_for_post( $post );

		$page_profile = TCW_Profile::get_by_slug( $slug, $entity_type );
		if ( $page_profile ) {
			return $page_profile;
		}

		/**
		 * City profile matching is wired for phase 2 and disabled by default.
		 *
		 * @param bool $enabled Whether city slug matching is enabled.
		 */
		if ( apply_filters( 'tcw_enable_city_matching', false ) ) {
			$city_profile = TCW_Profile::get_by_slug( $post->post_name, 'city' );
			if ( $city_profile ) {
				return $city_profile;
			}
		}

		if ( is_singular( 'page' ) ) {
			return TCW_Profile::get_by_slug( $post->post_name, 'country' );
		}

		return null;
	}

	/**
	 * Whether the current request can show an IP-based welcome.
	 *
	 * @return bool
	 */
	private static function is_ip_welcome_context() {
		/**
		 * Filter IP welcome eligibility for the current request.
		 *
		 * @param bool $eligible Default eligibility.
		 */
		$eligible = is_front_page() || is_home() || is_singular( 'page' );

		return (bool) apply_filters( 'tcw_ip_welcome_eligible', $eligible );
	}

	/**
	 * Resolve profile from visitor IP geolocation.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function resolve_ip_profile() {
		/**
		 * Filter whether IP-based welcome is allowed for this request.
		 *
		 * @param bool $enabled Whether IP welcome is enabled.
		 */
		if ( ! apply_filters( 'tcw_enable_ip_welcome', true ) ) {
			return null;
		}

		$country_code = TCW_Geo::get_visitor_country_code();
		if ( ! $country_code ) {
			return null;
		}

		return TCW_Profile::get_by_country_code( $country_code );
	}

	/**
	 * Apply settings and filters to profile payload.
	 *
	 * @param array<string, mixed> $profile Profile.
	 * @return array<string, mixed>|null
	 */
	private static function finalize_profile( $profile ) {
		$settings = TCW_Settings::get();

		$profile['tone']    = TCW_Settings::resolve_tone( $profile );
		$profile['trigger'] = TCW_Settings::resolve_trigger( $profile );

		$message  = trim( (string) $profile['welcome_message_en'] );
		$site     = get_bloginfo( 'name' );
		$entity   = (string) ( $profile['entity_type'] ?? 'country' );
		$is_page  = in_array( $entity, array( 'page', 'post' ), true ) || post_type_exists( $entity );

		if ( '' === $message ) {
			if ( 'ip' === ( $profile['match_source'] ?? '' ) ) {
				$profile['welcome_message_en'] = sprintf(
					/* translators: 1: site name, 2: visitor home country display name */
					__( 'Welcome to %1$s — we are delighted to greet you from %2$s.', 'travelay-cultural-welcome' ),
					$site,
					$profile['display_name']
				);
			} elseif ( $is_page ) {
				$profile['welcome_message_en'] = sprintf(
					/* translators: 1: site name, 2: page or post title */
					__( 'Welcome to %1$s — glad you are exploring %2$s.', 'travelay-cultural-welcome' ),
					$site,
					$profile['display_name']
				);
			} else {
				$profile['welcome_message_en'] = sprintf(
					/* translators: 1: site name, 2: country or city display name */
					__( 'Welcome to %1$s — your journey to %2$s starts here.', 'travelay-cultural-welcome' ),
					$site,
					$profile['display_name']
				);
			}
		}

		$profile['auto_delay_ms']    = (int) $settings['auto_delay_ms'];
		$profile['auto_duration_ms'] = (int) $settings['auto_duration_ms'];
		$profile['frequency']        = $settings['frequency'];
		$profile['show_replay_button'] = (bool) $settings['show_replay_button'];
		$profile['respect_reduced_motion'] = (bool) $settings['respect_reduced_motion'];
		$profile['z_index']          = (int) $settings['z_index'];

		/**
		 * Filter whether the welcome should display for this profile.
		 *
		 * @param bool  $should_display Whether to display.
		 * @param array $profile        Profile data.
		 */
		$should_display = apply_filters( 'tcw_should_display', true, $profile );
		if ( ! $should_display ) {
			return null;
		}

		/**
		 * Filter the resolved profile payload passed to the frontend.
		 *
		 * @param array $profile Profile data.
		 */
		return apply_filters( 'tcw_profile_payload', $profile );
	}
}
