<?php
/**
 * Scan site content and create welcome profiles linked to posts/pages.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TCW_Page_Sync
 */
class TCW_Page_Sync {

	public const BATCH_SIZE = 40;

	public const JOB_TRANSIENT = 'tcw_page_sync_job';

	/**
	 * Register AJAX handlers.
	 */
	public static function register() {
		add_action( 'wp_ajax_tcw_sync_start', array( __CLASS__, 'ajax_start' ) );
		add_action( 'wp_ajax_tcw_sync_batch', array( __CLASS__, 'ajax_batch' ) );
	}

	/**
	 * Sync scope labels for the admin dropdown.
	 *
	 * @return array<string, string>
	 */
	public static function scope_options() {
		return array(
			'pages'      => __( 'Pages only', 'travelay-cultural-welcome' ),
			'posts'      => __( 'Posts & custom post types', 'travelay-cultural-welcome' ),
			'everything' => __( 'Everything (pages, posts & all public types)', 'travelay-cultural-welcome' ),
		);
	}

	/**
	 * Resolve post types for a sync scope.
	 *
	 * @param string $scope Scope key.
	 * @return string[]
	 */
	public static function post_types_for_scope( $scope ) {
		$scope = sanitize_key( $scope );

		if ( 'pages' === $scope ) {
			return array( 'page' );
		}

		$public = get_post_types(
			array(
				'public' => true,
			),
			'names'
		);

		$skip = array(
			'attachment',
			TCW_Profile::POST_TYPE,
		);

		foreach ( $skip as $type ) {
			unset( $public[ $type ] );
		}

		if ( 'posts' === $scope ) {
			unset( $public['page'] );
			return array_values( $public );
		}

		return array_values( $public );
	}

	/**
	 * Count published posts for a scope (excluding filtered slugs).
	 *
	 * @param string $scope Scope key.
	 * @return int
	 */
	public static function count_syncable( $scope ) {
		$ids = self::collect_post_ids( $scope );
		return count( $ids );
	}

	/**
	 * Collect all post IDs for a scope.
	 *
	 * @param string $scope Scope key.
	 * @return int[]
	 */
	public static function collect_post_ids( $scope ) {
		$post_types = self::post_types_for_scope( $scope );
		if ( empty( $post_types ) ) {
			return array();
		}

		$ids    = array();
		$offset = 0;
		$chunk  = 200;

		do {
			$query = new WP_Query(
				array(
					'post_type'              => $post_types,
					'post_status'            => 'publish',
					'posts_per_page'         => $chunk,
					'offset'                 => $offset,
					'orderby'                => 'ID',
					'order'                  => 'ASC',
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $query->posts as $post_id ) {
				$post = get_post( $post_id );
				if ( $post && ! self::should_exclude_post( $post ) ) {
					$ids[] = (int) $post_id;
				}
			}

			$offset += $chunk;
		} while ( count( $query->posts ) === $chunk );

		return $ids;
	}

	/**
	 * Whether a post should be skipped during sync.
	 *
	 * @param WP_Post $post Post object.
	 * @return bool
	 */
	public static function should_exclude_post( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return true;
		}

		$exclude = TCW_Settings::sync_exclude_slugs();
		if ( in_array( $post->post_name, $exclude, true ) ) {
			return true;
		}

		if ( TCW_Booking_Guard::is_excluded_booking_post( $post ) ) {
			return true;
		}

		/**
		 * Filter whether a post is excluded from page sync.
		 *
		 * @param bool    $exclude Whether to exclude.
		 * @param WP_Post $post    Post object.
		 */
		return (bool) apply_filters( 'tcw_sync_exclude_post', false, $post );
	}

	/**
	 * Entity type stored on a synced profile.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public static function entity_type_for_post( $post ) {
		if ( 'page' === $post->post_type ) {
			return 'page';
		}
		if ( 'post' === $post->post_type ) {
			return 'post';
		}

		return sanitize_key( $post->post_type );
	}

	/**
	 * Location slug for a post (nested pages use parent/child path).
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public static function location_slug_for_post( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$slug = sanitize_title( $post->post_name );
		if ( 'page' !== $post->post_type || ! $post->post_parent ) {
			return $slug;
		}

		$ancestors = get_post_ancestors( $post );
		if ( empty( $ancestors ) ) {
			return $slug;
		}

		$parts = array();
		foreach ( array_reverse( $ancestors ) as $ancestor_id ) {
			$ancestor = get_post( $ancestor_id );
			if ( $ancestor ) {
				$parts[] = sanitize_title( $ancestor->post_name );
			}
		}
		$parts[] = $slug;

		return implode( '/', $parts );
	}

	/**
	 * Default welcome copy for a synced profile.
	 *
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public static function default_welcome_message( $post ) {
		$site = get_bloginfo( 'name' );
		$title = get_the_title( $post );

		return sprintf(
			/* translators: 1: site name, 2: page or post title */
			__( 'Welcome to %1$s — glad you are exploring %2$s.', 'travelay-cultural-welcome' ),
			$site,
			$title
		);
	}

	/**
	 * Rotate through friendly default gestures.
	 *
	 * @param int $index Post index.
	 * @return string
	 */
	public static function default_gesture( $index ) {
		$gestures = array( 'wave', 'open_welcome', 'nod' );
		return $gestures[ absint( $index ) % count( $gestures ) ];
	}

	/**
	 * Create or update a profile for one post.
	 *
	 * @param WP_Post $post  Post object.
	 * @param int     $index Batch index for gesture rotation.
	 * @return string created|updated|skipped
	 */
	public static function upsert_post_profile( $post, $index = 0 ) {
		if ( ! $post instanceof WP_Post || self::should_exclude_post( $post ) ) {
			return 'skipped';
		}

		$slug        = self::location_slug_for_post( $post );
		$entity_type = self::entity_type_for_post( $post );
		$title       = get_the_title( $post );
		$existing    = TCW_Profile::find_by_linked_post_id( $post->ID );

		if ( $existing ) {
			$post_id = (int) $existing['id'];
			wp_update_post(
				array(
					'ID'         => $post_id,
					'post_title' => $title,
				)
			);
			TCW_Profile::save_meta(
				$post_id,
				array(
					'display_name'  => $title,
					'location_slug' => $slug,
					'page_id'       => (int) $post->ID,
					'entity_type'   => $entity_type,
				)
			);
			return 'updated';
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => TCW_Profile::POST_TYPE,
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return 'skipped';
		}

		TCW_Profile::save_meta(
			$post_id,
			array(
				'entity_type'        => $entity_type,
				'location_slug'      => $slug,
				'country_code'       => '',
				'display_name'       => $title,
				'gesture'            => self::default_gesture( $index ),
				'welcome_message_en' => self::default_welcome_message( $post ),
				'tone_override'      => 'inherit',
				'trigger_override'   => 'inherit',
				'status'             => 'reviewed',
				'cultural_notes'     => '',
				'is_enabled'         => false,
				'parent_country_slug'=> '',
				'page_id'            => (int) $post->ID,
			)
		);

		return 'created';
	}

	/**
	 * AJAX: start sync job and return totals.
	 */
	public static function ajax_start() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'travelay-cultural-welcome' ) ), 403 );
		}

		check_ajax_referer( 'tcw_sync_pages', 'nonce' );

		$scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'pages';
		if ( ! array_key_exists( $scope, self::scope_options() ) ) {
			$scope = 'pages';
		}

		$ids = self::collect_post_ids( $scope );
		$job = array(
			'scope'   => $scope,
			'ids'     => $ids,
			'offset'  => 0,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
		);

		set_transient( self::JOB_TRANSIENT . '_' . get_current_user_id(), $job, HOUR_IN_SECONDS );

		wp_send_json_success(
			array(
				'total'   => count( $ids ),
				'scope'   => $scope,
				'message' => sprintf(
					/* translators: %d: number of posts */
					__( 'Found %d items to sync.', 'travelay-cultural-welcome' ),
					count( $ids )
				),
			)
		);
	}

	/**
	 * AJAX: process next batch.
	 */
	public static function ajax_batch() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'travelay-cultural-welcome' ) ), 403 );
		}

		check_ajax_referer( 'tcw_sync_pages', 'nonce' );

		$key = self::JOB_TRANSIENT . '_' . get_current_user_id();
		$job = get_transient( $key );
		if ( ! is_array( $job ) || empty( $job['ids'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Sync session expired. Please start again.', 'travelay-cultural-welcome' ) ) );
		}

		$batch_ids = array_slice( $job['ids'], (int) $job['offset'], self::BATCH_SIZE );
		foreach ( $batch_ids as $index => $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				++$job['skipped'];
				continue;
			}

			$result = self::upsert_post_profile( $post, (int) $job['offset'] + $index );
			if ( isset( $job[ $result ] ) ) {
				++$job[ $result ];
			}
		}

		$job['offset'] += count( $batch_ids );
		$total          = count( $job['ids'] );
		$done           = (int) $job['offset'] >= $total;

		if ( $done ) {
			delete_transient( $key );
		} else {
			set_transient( $key, $job, HOUR_IN_SECONDS );
		}

		wp_send_json_success(
			array(
				'done'    => $done,
				'total'   => $total,
				'offset'  => (int) $job['offset'],
				'created' => (int) $job['created'],
				'updated' => (int) $job['updated'],
				'skipped' => (int) $job['skipped'],
				'message' => $done
					? sprintf(
						/* translators: 1: created count, 2: updated count */
						__( 'Sync complete. %1$d created, %2$d updated.', 'travelay-cultural-welcome' ),
						(int) $job['created'],
						(int) $job['updated']
					)
					: sprintf(
						/* translators: 1: current offset, 2: total count */
						__( 'Syncing… %1$d / %2$d', 'travelay-cultural-welcome' ),
						min( (int) $job['offset'], $total ),
						$total
					),
			)
		);
	}
}
