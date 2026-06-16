<?php
/**
 * Activation hooks and optional Travelay country template seeding.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Seeder
 */
class TCW_Seeder {

	/**
	 * Plugin activation.
	 */
	public static function activate() {
		TCW_Profile::register();
		flush_rewrite_rules();

		if ( false === get_option( TCW_Settings::OPTION_KEY, false ) ) {
			TCW_Settings::update( TCW_Settings::defaults() );
		}
	}

	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Seed country profiles that map to existing pages.
	 */
	public static function seed_profiles() {
		$definitions = self::country_definitions();

		foreach ( $definitions as $definition ) {
			self::upsert_profile( $definition );
		}
	}

	/**
	 * Country definitions with culturally considered gestures.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function country_definitions() {
		return array(
			array(
				'location_slug'      => 'india',
				'country_code'       => 'IN',
				'display_name'       => 'India',
				'gesture'            => 'namaste',
				'welcome_message_en' => 'Namaste! Welcome to Travelay — we are honored to help you explore India.',
				'cultural_notes'     => 'Namaste with palms joined and a gentle bow. Avoid caricature or stereotyping.',
			),
			array(
				'location_slug'      => 'japan',
				'country_code'       => 'JP',
				'display_name'       => 'Japan',
				'gesture'            => 'bow',
				'welcome_message_en' => 'Welcome to Travelay — discover Japan with care and confidence.',
				'cultural_notes'     => 'Use a modest bow. Keep posture respectful and understated.',
			),
			array(
				'location_slug'      => 'italy',
				'country_code'       => 'IT',
				'display_name'       => 'Italy',
				'gesture'            => 'open_welcome',
				'welcome_message_en' => 'Welcome to Travelay — your Italian adventure begins here.',
				'cultural_notes'     => 'Open, warm hospitality gesture. Elegant rather than exaggerated.',
			),
			array(
				'location_slug'      => 'brazil',
				'country_code'       => 'BR',
				'display_name'       => 'Brazil',
				'gesture'            => 'wave',
				'welcome_message_en' => 'Welcome to Travelay — let us help you fly to Brazil with ease.',
				'cultural_notes'     => 'Warm, open wave. Inclusive and joyful without clichés.',
			),
			array(
				'location_slug'      => 'australia',
				'country_code'       => 'AU',
				'display_name'       => 'Australia',
				'gesture'            => 'wave',
				'welcome_message_en' => 'Welcome to Travelay — ready for your journey to Australia?',
				'cultural_notes'     => 'Friendly, relaxed wave. Approachable and grounded.',
			),
			array(
				'location_slug'      => 'saudi-arabia',
				'country_code'       => 'SA',
				'display_name'       => 'Saudi Arabia',
				'gesture'            => 'hand_heart',
				'welcome_message_en' => 'Welcome to Travelay — we are here for your journey to Saudi Arabia.',
				'cultural_notes'     => 'Hand over heart conveys sincerity. Use modest, respectful presentation.',
			),
			array(
				'location_slug'      => 'france',
				'country_code'       => 'FR',
				'display_name'       => 'France',
				'gesture'            => 'nod',
				'welcome_message_en' => 'Welcome to Travelay — explore France with clarity and style.',
				'cultural_notes'     => 'Subtle nod or refined open gesture. Elegant tone preferred.',
			),
			array(
				'location_slug'      => 'spain',
				'country_code'       => 'ES',
				'display_name'       => 'Spain',
				'gesture'            => 'open_welcome',
				'welcome_message_en' => 'Welcome to Travelay — your Spanish journey starts here.',
				'cultural_notes'     => 'Open, hospitable gesture. Warm but respectful.',
			),
			array(
				'location_slug'      => 'united-kingdom',
				'country_code'       => 'GB',
				'display_name'       => 'United Kingdom',
				'gesture'            => 'wave',
				'welcome_message_en' => 'Welcome to Travelay — trusted support for your UK travel plans.',
				'cultural_notes'     => 'Warm professional wave with elegant British hospitality. No caricature uniforms or flag costumes.',
			),
			array(
				'location_slug'      => 'mexico',
				'country_code'       => 'MX',
				'display_name'       => 'Mexico',
				'gesture'            => 'wave',
				'welcome_message_en' => 'Welcome to Travelay — let us help you reach Mexico with confidence.',
				'cultural_notes'     => 'Warm wave with open posture. Respectful and inviting.',
			),
			array(
				'location_slug'      => 'canada',
				'country_code'       => 'CA',
				'display_name'       => 'Canada',
				'gesture'            => 'wave',
				'welcome_message_en' => 'Welcome to Travelay — friendly support for your Canada travel plans.',
				'cultural_notes'     => 'Friendly wave. Calm, inclusive, and welcoming.',
			),
			array(
				'location_slug'      => 'usa',
				'country_code'       => 'US',
				'display_name'       => 'United States',
				'gesture'            => 'wave',
				'welcome_message_en' => 'Welcome to Travelay — real agents, real support for your US flights.',
				'cultural_notes'     => 'Open friendly wave. Professional and approachable.',
			),
			array(
				'location_slug'      => 'netherlands',
				'country_code'       => 'NL',
				'display_name'       => 'Netherlands',
				'gesture'            => 'nod',
				'welcome_message_en' => 'Welcome to Travelay — thoughtful support for your Netherlands travel.',
				'cultural_notes'     => 'Understated nod or small wave. Direct and respectful.',
			),
			array(
				'location_slug'      => 'greece',
				'country_code'       => 'GR',
				'display_name'       => 'Greece',
				'gesture'            => 'open_welcome',
				'welcome_message_en' => 'Welcome to Travelay — begin your Greek journey with us.',
				'cultural_notes'     => 'Open welcome gesture reflecting hospitality. Dignified presentation.',
			),
			array(
				'location_slug'      => 'russia',
				'country_code'       => 'RU',
				'display_name'       => 'Russia',
				'gesture'            => 'nod',
				'welcome_message_en' => 'Welcome to Travelay — dependable support for your Russia travel plans.',
				'cultural_notes'     => 'Reserved nod. Formal, respectful tone.',
			),
			array(
				'location_slug'      => 'switzerland',
				'country_code'       => 'CH',
				'display_name'       => 'Switzerland',
				'gesture'            => 'nod',
				'welcome_message_en' => 'Welcome to Travelay — precise support for your Switzerland travel.',
				'cultural_notes'     => 'Polite nod. Clean, refined visual style.',
			),
			array(
				'location_slug'      => 'all-country-test',
				'country_code'       => 'IN',
				'display_name'       => 'India',
				'gesture'            => 'namaste',
				'welcome_message_en' => 'Namaste! This is a Travelay cultural welcome preview.',
				'cultural_notes'     => 'Staging test profile for QA.',
				'status'             => 'live',
			),
		);
	}

	/**
	 * Create or update a profile.
	 *
	 * @param array<string, mixed> $definition Definition.
	 */
	private static function upsert_profile( $definition ) {
		$slug = sanitize_title( $definition['location_slug'] );

		$existing = get_posts(
			array(
				'post_type'      => TCW_Profile::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending' ),
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'   => '_tcw_location_slug',
						'value' => $slug,
					),
					array(
						'key'   => '_tcw_entity_type',
						'value' => 'country',
					),
				),
			)
		);

		$page = get_page_by_path( $slug, OBJECT, 'page' );
		$page_id = $page ? (int) $page->ID : 0;

		$status = isset( $definition['status'] ) ? $definition['status'] : ( $page_id ? 'live' : 'reviewed' );
		$enabled = isset( $definition['is_enabled'] ) ? (bool) $definition['is_enabled'] : (bool) $page_id || 'all-country-test' === $slug;

		$data = array(
			'entity_type'         => 'country',
			'location_slug'       => $slug,
			'country_code'        => $definition['country_code'],
			'display_name'        => $definition['display_name'],
			'gesture'             => $definition['gesture'],
			'welcome_message_en'  => $definition['welcome_message_en'],
			'tone_override'       => 'inherit',
			'trigger_override'    => 'inherit',
			'status'              => $status,
			'cultural_notes'      => $definition['cultural_notes'],
			'is_enabled'          => $enabled,
			'parent_country_slug' => '',
			'page_id'             => $page_id,
		);

		if ( ! empty( $existing ) ) {
			$post_id = (int) $existing[0]->ID;
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $definition['display_name'],
					'post_status'=> 'publish',
				)
			);
			TCW_Profile::save_meta( $post_id, $data );
			return;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => TCW_Profile::POST_TYPE,
				'post_title'  => $definition['display_name'],
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return;
		}

		TCW_Profile::save_meta( $post_id, $data );
	}
}
