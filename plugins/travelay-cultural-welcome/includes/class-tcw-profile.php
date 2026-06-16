<?php
/**
 * Location profile registry (country now, city later).
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Profile
 */
class TCW_Profile {

	public const POST_TYPE = 'tcw_profile';

	/**
	 * Register CPT.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Welcome Profiles', 'travelay-cultural-welcome' ),
					'singular_name' => __( 'Welcome Profile', 'travelay-cultural-welcome' ),
					'add_new_item'  => __( 'Add Welcome Profile', 'travelay-cultural-welcome' ),
					'edit_item'     => __( 'Edit Welcome Profile', 'travelay-cultural-welcome' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
			)
		);
	}

	/**
	 * Meta keys.
	 *
	 * @return string[]
	 */
	public static function meta_keys() {
		return array(
			'entity_type',
			'location_slug',
			'country_code',
			'display_name',
			'gesture',
			'welcome_message_en',
			'tone_override',
			'trigger_override',
			'status',
			'cultural_notes',
			'is_enabled',
			'parent_country_slug',
			'page_id',
			'voice_script',
			'voice_enabled',
			'voice_language',
			'voice_name',
			'voice_speaking_rate',
			'rive_state_machine',
			'rive_hover_input',
			'rive_entry_trigger',
			'rive_tap_triggers',
		);
	}

	/**
	 * Get profile by location slug and entity type.
	 *
	 * @param string $slug        Page slug.
	 * @param string $entity_type Entity type.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_slug( $slug, $entity_type = 'country' ) {
		$slug        = self::sanitize_location_slug( $slug );
		$entity_type = sanitize_key( $entity_type );

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_tcw_location_slug',
						'value' => $slug,
					),
					array(
						'key'   => '_tcw_entity_type',
						'value' => $entity_type,
					),
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

		if ( empty( $query->posts ) ) {
			return null;
		}

		return self::format_post( $query->posts[0] );
	}

	/**
	 * Get live country profile by ISO country code (e.g. US, GB).
	 *
	 * @param string $country_code Two-letter country code.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_country_code( $country_code ) {
		$country_code = strtoupper( sanitize_text_field( $country_code ) );
		if ( 2 !== strlen( $country_code ) ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_tcw_country_code',
						'value' => $country_code,
					),
					array(
						'key'   => '_tcw_entity_type',
						'value' => 'country',
					),
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

		if ( empty( $query->posts ) ) {
			return null;
		}

		return self::format_post( $query->posts[0] );
	}

	/**
	 * Find a profile linked to a post ID (any status / enabled state).
	 *
	 * @param int $post_id Linked WordPress post ID.
	 * @return array<string, mixed>|null
	 */
	public static function find_by_linked_post_id( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => '_tcw_page_id',
						'value' => (string) $post_id,
					),
				),
			)
		);

		if ( empty( $query->posts ) ) {
			return null;
		}

		return self::format_post( $query->posts[0] );
	}

	/**
	 * Get profile by linked page ID.
	 *
	 * @param int $page_id Page ID.
	 * @return array<string, mixed>|null
	 */
	public static function get_by_page_id( $page_id ) {
		$page_id = absint( $page_id );
		if ( ! $page_id ) {
			return null;
		}

		$query = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => '_tcw_page_id',
						'value' => (string) $page_id,
					),
				),
			)
		);

		if ( empty( $query->posts ) ) {
			return null;
		}

		$profile = self::format_post( $query->posts[0] );
		if ( empty( $profile['is_enabled'] ) || 'live' !== $profile['status'] ) {
			return null;
		}

		return $profile;
	}

	/**
	 * Format WP_Post to array.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string, mixed>
	 */
	public static function format_post( $post ) {
		$slug = get_post_meta( $post->ID, '_tcw_location_slug', true );

		return array(
			'id'                  => $post->ID,
			'title'               => get_the_title( $post ),
			'entity_type'         => get_post_meta( $post->ID, '_tcw_entity_type', true ) ?: 'country',
			'location_slug'       => $slug,
			'country_code'        => strtoupper( (string) get_post_meta( $post->ID, '_tcw_country_code', true ) ),
			'display_name'        => (string) get_post_meta( $post->ID, '_tcw_display_name', true ),
			'gesture'             => TCW_Gestures::sanitize( (string) get_post_meta( $post->ID, '_tcw_gesture', true ) ),
			'welcome_message_en'  => (string) get_post_meta( $post->ID, '_tcw_welcome_message_en', true ),
			'tone_override'       => (string) get_post_meta( $post->ID, '_tcw_tone_override', true ) ?: 'inherit',
			'trigger_override'    => (string) get_post_meta( $post->ID, '_tcw_trigger_override', true ) ?: 'inherit',
			'status'              => (string) get_post_meta( $post->ID, '_tcw_status', true ) ?: 'draft',
			'cultural_notes'      => (string) get_post_meta( $post->ID, '_tcw_cultural_notes', true ),
			'is_enabled'          => '1' === get_post_meta( $post->ID, '_tcw_is_enabled', true ),
			'parent_country_slug' => (string) get_post_meta( $post->ID, '_tcw_parent_country_slug', true ),
			'page_id'             => absint( get_post_meta( $post->ID, '_tcw_page_id', true ) ),
			'voice_script'        => (string) get_post_meta( $post->ID, '_tcw_voice_script', true ),
			'voice_enabled'       => '0' !== get_post_meta( $post->ID, '_tcw_voice_enabled', true ),
			'voice_language'      => (string) get_post_meta( $post->ID, '_tcw_voice_language', true ),
			'voice_name'          => (string) get_post_meta( $post->ID, '_tcw_voice_name', true ),
			'voice_speaking_rate' => (float) get_post_meta( $post->ID, '_tcw_voice_speaking_rate', true ),
			'rive_state_machine'  => (string) get_post_meta( $post->ID, TCW_Rive::META_STATE_MACHINE, true ),
			'rive_hover_input'    => (string) get_post_meta( $post->ID, TCW_Rive::META_HOVER_INPUT, true ),
			'rive_entry_trigger'  => (string) get_post_meta( $post->ID, TCW_Rive::META_ENTRY_TRIGGER, true ),
			'rive_tap_triggers'   => TCW_Rive::parse_tap_triggers( get_post_meta( $post->ID, TCW_Rive::META_TAP_TRIGGERS, true ) ),
			'palette'             => TCW_Gestures::palette_for_slug( $slug ),
		);
	}

	/**
	 * Save profile meta.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $data    Data.
	 */
	public static function save_meta( $post_id, $data ) {
		$map = array(
			'entity_type'         => '_tcw_entity_type',
			'location_slug'       => '_tcw_location_slug',
			'country_code'        => '_tcw_country_code',
			'display_name'        => '_tcw_display_name',
			'gesture'             => '_tcw_gesture',
			'welcome_message_en'  => '_tcw_welcome_message_en',
			'tone_override'       => '_tcw_tone_override',
			'trigger_override'    => '_tcw_trigger_override',
			'status'              => '_tcw_status',
			'cultural_notes'      => '_tcw_cultural_notes',
			'is_enabled'          => '_tcw_is_enabled',
			'parent_country_slug' => '_tcw_parent_country_slug',
			'page_id'             => '_tcw_page_id',
			'voice_script'        => '_tcw_voice_script',
			'voice_enabled'       => '_tcw_voice_enabled',
			'voice_language'      => '_tcw_voice_language',
			'voice_name'          => '_tcw_voice_name',
			'voice_speaking_rate' => '_tcw_voice_speaking_rate',
		);

		foreach ( $map as $key => $meta_key ) {
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			$value = $data[ $key ];

			switch ( $key ) {
				case 'location_slug':
					$value = self::sanitize_location_slug( $value );
					break;
				case 'parent_country_slug':
					$value = sanitize_title( $value );
					break;
				case 'country_code':
					$value = strtoupper( sanitize_text_field( $value ) );
					break;
				case 'display_name':
				case 'welcome_message_en':
					$value = sanitize_text_field( $value );
					break;
				case 'cultural_notes':
					$value = sanitize_textarea_field( $value );
					break;
				case 'gesture':
					$value = TCW_Gestures::sanitize( $value );
					break;
				case 'entity_type':
					$value = self::sanitize_entity_type( $value );
					break;
				case 'tone_override':
					$value = in_array( $value, array( 'inherit', 'elegant', 'playful' ), true ) ? $value : 'inherit';
					break;
				case 'trigger_override':
					$value = in_array( $value, array( 'inherit', 'auto', 'manual', 'both' ), true ) ? $value : 'inherit';
					break;
				case 'status':
					$value = in_array( $value, array( 'draft', 'reviewed', 'live' ), true ) ? $value : 'draft';
					break;
				case 'is_enabled':
					$value = $value ? '1' : '0';
					break;
				case 'page_id':
					$value = absint( $value );
					break;
				case 'voice_script':
					$value = sanitize_textarea_field( $value );
					break;
				case 'voice_enabled':
					$value = $value ? '1' : '0';
					break;
				case 'voice_language':
				case 'voice_name':
					$value = sanitize_text_field( $value );
					break;
				case 'voice_speaking_rate':
					$value = max( 0, (float) $value );
					break;
				default:
					$value = sanitize_text_field( $value );
			}

			update_post_meta( $post_id, $meta_key, $value );
		}
	}

	/**
	 * Sanitize a location slug, preserving nested page paths (about/team).
	 *
	 * @param string $value Raw slug.
	 * @return string
	 */
	public static function sanitize_location_slug( $value ) {
		$value = (string) $value;
		if ( false === strpos( $value, '/' ) ) {
			return sanitize_title( $value );
		}

		$parts = array_map( 'sanitize_title', array_filter( explode( '/', $value ) ) );
		return implode( '/', $parts );
	}

	/**
	 * Sanitize entity type for profiles.
	 *
	 * @param string $value Raw entity type.
	 * @return string
	 */
	public static function sanitize_entity_type( $value ) {
		$value = sanitize_key( $value );
		$built_in = array( 'country', 'city', 'page', 'post' );

		if ( in_array( $value, $built_in, true ) ) {
			return $value;
		}

		if ( post_type_exists( $value ) ) {
			return $value;
		}

		return 'country';
	}

	/**
	 * Human-readable entity type label.
	 *
	 * @param string $entity_type Entity type key.
	 * @return string
	 */
	public static function entity_type_label( $entity_type ) {
		$entity_type = sanitize_key( $entity_type );
		$labels      = array(
			'country' => __( 'Country', 'travelay-cultural-welcome' ),
			'city'    => __( 'City', 'travelay-cultural-welcome' ),
			'page'    => __( 'Page', 'travelay-cultural-welcome' ),
			'post'    => __( 'Post', 'travelay-cultural-welcome' ),
		);

		if ( isset( $labels[ $entity_type ] ) ) {
			return $labels[ $entity_type ];
		}

		$object = get_post_type_object( $entity_type );
		if ( $object && ! empty( $object->labels->singular_name ) ) {
			return (string) $object->labels->singular_name;
		}

		return ucfirst( $entity_type );
	}
}
