<?php
/**
 * Register a Custom Post Type
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_PostTypes_Events' ) ) {

	/**
	 * Class responsible for registering a CPT
	 */
	class DS_PostTypes_Events {

		/**
		 * Declare CPT name
		 *
		 * @var string
		 */
		private string $name = 'events';

		/**
		 * Construct
		 */
		public function __construct() {
			if ( ! theme_feature( 'events_feature' ) ) {
				return;
			}

			global $dsmp_settings;
			$dsmp_settings->set_global( 'post_types', $this->name );

			add_action( 'init', array( $this, 'register_post_types' ) );
			add_action( 'init', array( $this, 'register_taxonomies' ) );

			if ( class_exists( 'acf' ) && is_admin() ) {
				add_filter( "manage_{$this->name}_posts_columns", array( $this, 'event_admin_columns' ) );
				add_action(
					"manage_{$this->name}_posts_custom_column",
					array(
						$this,
						'event_admin_columns_data',
					),
					10,
					2
				);
			}
		}

		/**
		 * Register post type
		 */
		public function register_post_types() {
			if ( ! is_blog_installed() ) {
				return;
			}

			if ( ! post_type_exists( $this->name ) ) {
				register_post_type(
					$this->name,
					array(
						'labels'        => array(
							'name'                  => __( 'Events', 'dstheme' ),
							'singular_name'         => _x( 'Event', 'Site post type singular name', 'dstheme' ),
							'add_new'               => __( 'Add Event', 'dstheme' ),
							'add_new_item'          => __( 'Add New Event', 'dstheme' ),
							'edit'                  => __( 'Edit', 'dstheme' ),
							'edit_item'             => __( 'Edit Event', 'dstheme' ),
							'new_item'              => __( 'New Event', 'dstheme' ),
							'view'                  => __( 'View Events', 'dstheme' ),
							'view_item'             => __( 'View Events', 'dstheme' ),
							'search_items'          => __( 'Search Events', 'dstheme' ),
							'not_found'             => __( 'No Events found', 'dstheme' ),
							'not_found_in_trash'    => __( 'No Events found in trash', 'dstheme' ),
							'parent'                => __( 'Parent Events', 'dstheme' ),
							'menu_name'             => _x( 'Events', 'Admin menu name', 'dstheme' ),
							'filter_items_list'     => __( 'Filter Events', 'dstheme' ),
							'items_list_navigation' => __( 'Events navigation', 'dstheme' ),
							'items_list'            => __( 'Events list', 'dstheme' ),
						),
						'menu_position' => 4,
						'menu_icon'     => 'dashicons-calendar',
						'public'        => true,
						'show_in_rest'  => true,
						'supports'      => array( 'title', 'editor', 'thumbnail' ),
						'rewrite'       => array(
							'slug'       => 'events',
							'with_front' => false,
						),
					)
				);
			}
		}

		/**
		 * Add custom admin columns
		 *
		 * @param array $columns contains admin columns.
		 */
		public static function event_admin_columns( $columns ) {
			unset( $columns['author'] );
			unset( $columns['date'] );

			$columns['event_start_date'] = __( 'Event Date', 'dstheme' );
			$columns['author']           = __( 'Event Author', 'dstheme' );
			$columns['date']             = __( 'Publish Date', 'dstheme' );

			return $columns;
		}

		/**
		 * Populate custom column data
		 *
		 * @param string $column contains the column name.
		 * @param int    $post_id contains  post id.
		 */
		public static function event_admin_columns_data( $column, $post_id ) {
			switch ( $column ) {
				case 'event_start_date':
					$event_start_date = get_field( 'event_start_date', $post_id );
					if ( $event_start_date ) {
						echo '<strong>' . esc_html( $event_start_date ) . '</strong>';
					}
					break;
			}
		}


		/**
		 * Register custom taxonomy
		 */
		public function register_taxonomies() {
			if ( ! is_blog_installed() ) {
				return;
			}

			register_taxonomy(
				'events_category',
				array( $this->name ),
				array(
					'label'        => 'Categories',
					'hierarchical' => true,
				)
			);
		}
	}

	new DS_PostTypes_Events();
}
