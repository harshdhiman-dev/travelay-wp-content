<?php
/**
 * Register a Custom Post Type
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_PostTypes_Team' ) ) {
	/**
	 * Class responsible for registering a CPT
	 */
	class DS_PostTypes_Team {
		/**
		 * Declare CPT name
		 *
		 * @var string
		 */
		private string $name = 'team';

		/**
		 * Construct
		 */
		public function __construct() {
			if ( ! theme_feature( 'team_feature' ) ) {
				return;
			}

			add_action( 'init', array( $this, 'register_post_type' ) );
		}

		/**
		 * Register post type
		 */
		public function register_post_type() {
			if ( ! is_blog_installed() ) {
				return;
			}

			if ( ! post_type_exists( $this->name ) ) {
				register_post_type(
					$this->name,
					array(
						'labels'              => array(
							'name'                  => __( 'Team', 'dstheme' ),
							'singular_name'         => _x( 'Team', 'Site post type singular name', 'dstheme' ),
							'add_new'               => __( 'Add Team', 'dstheme' ),
							'add_new_item'          => __( 'Add New Team', 'dstheme' ),
							'edit'                  => __( 'Edit', 'dstheme' ),
							'edit_item'             => __( 'Edit Team', 'dstheme' ),
							'new_item'              => __( 'New Team', 'dstheme' ),
							'view'                  => __( 'View Team', 'dstheme' ),
							'view_item'             => __( 'View Team', 'dstheme' ),
							'search_items'          => __( 'Search Team', 'dstheme' ),
							'not_found'             => __( 'No Team found', 'dstheme' ),
							'not_found_in_trash'    => __( 'No Team found in trash', 'dstheme' ),
							'parent'                => __( 'Parent Team', 'dstheme' ),
							'menu_name'             => __( 'Team', 'dstheme' ),
							'filter_items_list'     => __( 'Filter Team', 'dstheme' ),
							'items_list_navigation' => __( 'Team navigation', 'dstheme' ),
							'items_list'            => __( 'Team list', 'dstheme' ),
						),
						'menu_position'       => 4,
						'menu_icon'           => 'dashicons-groups',
						'public'              => true,
						'exclude_from_search' => true,
						'publicly_queryable'  => false,
						'show_in_nav_menus'   => false,
						'show_in_rest'        => false,
						'has_archive'         => false,
						'supports'            => array( 'title', 'thumbnail' ),
					)
				);
			}
		}
	}

	new DS_PostTypes_Team();
}
