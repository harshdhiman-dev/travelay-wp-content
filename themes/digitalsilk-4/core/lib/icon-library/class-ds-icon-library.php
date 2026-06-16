<?php
/**
 * Class DS_Icon_Library
 *
 * Handles icon library functionality of our website.
 *
 * @package dstheme
 */

/**
 * Our icon library class.
 */
class DS_Icon_Library {

	/**
	 * Constructor to register the necessary actions.
	 */
	public function __construct() {
		// Register admin page.
		add_action( 'admin_menu', [ $this, 'register_icon_library_page' ] );
		// Register an attachment taxonomy.
		add_action( 'init', [ $this, 'register_attachment_taxonomy' ] );
		// Setup taxonomy terms.
		add_action( 'admin_init', [ $this, 'ensure_uncategorized_term_exists' ] );
		// Add admin scripts & styles.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		// Add admin inline scripts & styles.
		add_action( 'admin_enqueue_scripts', [ $this, 'add_inline_scripts' ] );
		// Assign all of the images from the button settings options page, to go into the icon library.
		add_action( 'add_attachment', [ $this, 'auto_assign_icon_type' ] );
	}

	/**
	 * Registers the "Icon Library" options page under "Media".
	 */
	public function register_icon_library_page() {
		add_media_page(
			__( 'Icon Library', 'dstheme' ),
			__( 'Icon Library', 'dstheme' ),
			'upload_files',
			'ds-icon-library',
			[ $this, 'icon_library_page_html' ]
		);
	}

	/**
	 * Callback function to display the content of the Icon Library page.
	 */
	public function icon_library_page_html() {
		// Minimal html markup, everything is in the react app.
		echo '<div class="wrap"><div id="ds-icon-library-app"></div></div>';
	}

	/**
	 * Register a custom taxonomy 'icon_type' for the 'attachment' post type.
	 *
	 * This taxonomy is used to categorize media attachments (icons).
	 * It is not publicly queryable, but it is shown in the REST API by default.
	 *
	 * @return void
	 */
	public function register_attachment_taxonomy() {
		// Define the labels for the taxonomy.
		$labels = [
			'name'          => _x( 'Icon Types', 'taxonomy general name', 'dstheme' ),
			'singular_name' => _x( 'Icon Type', 'taxonomy singular name', 'dstheme' ),
			'search_items'  => __( 'Search Icon Types', 'dstheme' ),
			'all_items'     => __( 'All Icon Types', 'dstheme' ),
			'edit_item'     => __( 'Edit Icon Type', 'dstheme' ),
			'update_item'   => __( 'Update Icon Type', 'dstheme' ),
			'add_new_item'  => __( 'Add New Icon Type', 'dstheme' ),
			'new_item_name' => __( 'New Icon Type Name', 'dstheme' ),
			'menu_name'     => __( 'Icon Type', 'dstheme' ),
		];

		// Register the taxonomy with the given labels and arguments.
		$args = [
			'labels'            => $labels,
			'public'            => true,
			'show_ui'           => false,
			'show_in_menu'      => false,
			'show_in_nav_menus' => false,
			'show_in_rest'      => true,
			'hierarchical'      => false,
			'rewrite'           => false,
		];

		// Register the 'icon_type' taxonomy for the 'attachment' post type.
		register_taxonomy( 'icon_type', 'attachment', $args );
	}

	/**
	 * Fetch all of the icons from our database.
	 * Used to display them right away in the gutenberg components.
	 */
	public static function get_all_icons() {
		// Set up the arguments for the query.
		$args = [
			'post_type'              => 'attachment',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'post_status'            => 'inherit',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'tax_query'              => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				[
					'taxonomy' => 'icon_type',
					'field'    => 'term_id',
					'operator' => 'EXISTS',
				],
			],
			'include_icon_type'      => true, // We include this custom query param so we can see our custom icons.
		];
		// Execute the query.
		$query = new WP_Query( $args );

		// Initialize the result array.
		$results = [];

		// Loop through the found post IDs.
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				// Get the terms of the 'icon_type' taxonomy for this attachment.
				$terms = wp_get_post_terms( $post_id, 'icon_type' );
				// Get the thumbnail URL of the attachment.
				$thumbnail_url = wp_get_attachment_image_url( $post_id, 'thumbnail' );
				// Loop through each term and add the attachment info to the corresponding term's group.
				if ( $terms && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$term_slug = $term->slug;
						// Initialize the array for this term if it doesn't exist.
						if ( ! isset( $results[ $term_slug ] ) ) {
							$results[ $term_slug ] = [];
						}
						// Add the attachment information to the term's group.
						$results[ $term_slug ][] = [
							'id'    => $post_id,
							'title' => get_the_title( $post_id ),
							'url'   => $thumbnail_url,
						];
					}
				}
			}
		}
		return $results;
	}

	/**
	 * Ensures the "uncategorized" term exists for the 'icon_type' taxonomy and stores the term ID in the option.
	 *
	 * This method will only run once, as it updates the 'icon_library_setup' option with the term ID after creating the term.
	 *
	 * @return void
	 */
	public function ensure_uncategorized_term_exists() {
		// Check if the setup has already been done by retrieving the term ID from the option.
		$existing_term_id = get_option( 'icon_library_setup' );
		if ( $existing_term_id && is_array( $existing_term_id ) ) {
			return;
		}

		// Define what taxonomy to use.
		$taxonomy = 'icon_type';

		// Define what terms to create.
		$terms_to_create = [
			[
				'slug' => 'general',
				'name' => __( 'General', 'dstheme' ),
			],
			[
				'slug' => 'social',
				'name' => __( 'Social', 'dstheme' ),
			],
			[
				'slug' => 'buttons',
				'name' => __( 'Buttons', 'dstheme' ),
			],
		];

		// Define terms that will later be saved.
		$terms_array = [];

		// Loop trough our terms list and create them.
		foreach ( $terms_to_create as $term_item ) {
			$term_name = $term_item['name'];
			$term_slug = $term_item['slug'];

			// Check if the term already exists.
			$term = get_term_by( 'slug', $term_slug, $taxonomy );

			if ( ! $term ) {
				// If the term doesn't exist, create it.
				$new_term = wp_insert_term(
					$term_name,
					$taxonomy,
					[
						'slug' => $term_slug,
					]
				);

				// Handle WP_Error.
				if ( is_wp_error( $new_term ) ) {
					continue;
				}

				// Set the newly created term ID.
				$term_id = $new_term['term_id'];
				// Add to terms array.
				$terms_array[ $term_slug ] = $term_id;
			} else {
				// If the term exists, get its ID.
				$term_id = $term->term_id;
				// Add to terms array.
				$terms_array[ $term_slug ] = $term_id;
			}
		}

		// Store the term array in the 'icon_library_setup' option to indicate setup is complete.
		update_option( 'icon_library_setup', $terms_array );
	}

	/**
	 * Add admin scripts & styles.
	 *
	 * @param string $hook Hook suffix for the current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		// Enqueue only on media library page.
		if ( 'media_page_ds-icon-library' !== $hook ) {
			return;
		}

		// Check for assets.
		$icon_library_assets = get_modules_asset_info( 'pages/icon-library' );

		if ( ! is_array( $icon_library_assets ) || empty( $icon_library_assets ) ) {
			return;
		}

		// Enqueue icon library scripts.
		wp_enqueue_script(
			'icon-library',
			$icon_library_assets['url'],
			$icon_library_assets['dependencies'],
			$icon_library_assets['version'],
			true
		);
		// Enqueue icon library styles.
		wp_enqueue_style(
			'icon-library',
			$icon_library_assets['style'],
			[ 'wp-edit-blocks' ],
			$icon_library_assets['version']
		);
	}

	/**
	 * Add admin inline scripts & styles.
	 *
	 * @param string $hook Hook suffix for the current admin page.
	 */
	public function add_inline_scripts( $hook ) {
		/**
		 * Include inline scripts on all post types
		 * and our custom pages.
		 */
		$allowed_pages = [
			'media_page_ds-icon-library',
			'toplevel_page_theme-settings',
			'toplevel_page_header-theme-content',
			'post.php',
			'post-new.php',
			'site-editor.php',
		];
		if ( ! in_array( $hook, $allowed_pages, true ) ) {
			return;
		}
		// Register and enqueue dummy scripts.
		wp_register_script( 'icon-library-persistent', false ); // phpcs:ignore
		wp_enqueue_script( 'icon-library-persistent' );

		// Get the required term data.
		$icon_type_terms = get_option( 'icon_library_setup' );
		$icon_list       = $this->get_all_icons();

		// Add inline script.
		wp_add_inline_script(
			'icon-library-persistent',
			'window.iconLibraryDataStore = ' . wp_json_encode(
				[
					'taxonomyTerms' => $icon_type_terms,
					'iconsList'     => $icon_list,
				]
			)
		);
	}

	/**
	 * Exclude media library attachments that have any 'icon_type' taxonomy terms.
	 * Currently disabled. Can be hooked into 'pre_get_posts'.
	 *
	 * @param WP_Query $query The current query object.
	 */
	public function exclude_icon_type_media_library( $query ) {
		// Check if we are in the admin, and we're querying attachments.
		if ( is_admin() && 'attachment' === $query->get( 'post_type' ) ) {
			// Check if the custom key is set in the query, indicating we should skip this exclusion.
			if ( $query->get( 'include_icon_type' ) ) {
				return;
			}
			// Define a taxonomy query to exclude attachments with 'icon_type' taxonomy terms.
			$tax_query = [
				[
					'taxonomy' => 'icon_type',
					'field'    => 'term_id',
					'operator' => 'NOT EXISTS',
				],
			];
			// Set the taxonomy query to exclude some attachments.
			$query->set( 'tax_query', $tax_query );
		}
	}

	/**
	 * Automatically assign the 'buttons' term to the 'icon_type' taxonomy for uploaded images.
	 *
	 * @param int $attachment_id The attachment ID.
	 */
	public function auto_assign_icon_type( $attachment_id ) {
		// Ensure user is logged in.
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Ensure user has permission to upload files.
		if ( ! current_user_can( 'upload_files' ) ) {
			return;
		}

		// Check if this is an ACF AJAX upload from the correct options page.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_SERVER['HTTP_REFERER'] ) ) {

			// Sanitize the referer URL.
			$referer = esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) );

			// Verify that the request originated from the "theme-buttons" options page.
			if ( strpos( $referer, 'admin.php?page=theme-buttons' ) !== false ) {

				// Assign "buttons" term to the attachment in "icon_type" taxonomy.
				wp_set_object_terms( $attachment_id, 'buttons', 'icon_type', false );
			}
		}
	}
}

// Initialize the class.
new DS_Icon_Library();
