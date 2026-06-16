<?php
/**
 * Register a Custom Post Type
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_PostTypes_MegaMenu' ) ) {

	/**
	 * Class responsible for registering a CPT
	 */
	class DS_PostTypes_MegaMenu {

		/**
		 * Declare CPT name
		 *
		 * @var string
		 */
		private string $name = 'megamenu';

		/**
		 * Construct
		 */
		public function __construct() {

			if ( ! class_exists( 'acf' ) ) {
				return;
			}

			add_action( 'init', array( $this, 'register_post_types' ) );
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
						'labels'              => array(
							'name'                  => __( 'DST Megamenus', 'dstheme' ),
							'singular_name'         => _x( 'DST Megamenu', 'Site post type singular name', 'dstheme' ),
							'add_new'               => __( 'Add Megamenu', 'dstheme' ),
							'add_new_item'          => __( 'Add New Megamenu', 'dstheme' ),
							'edit'                  => __( 'Edit', 'dstheme' ),
							'edit_item'             => __( 'Edit Megamenu', 'dstheme' ),
							'new_item'              => __( 'New Megamenu', 'dstheme' ),
							'view'                  => __( 'View Megamenu', 'dstheme' ),
							'view_item'             => __( 'View Megamenu', 'dstheme' ),
							'search_items'          => __( 'Search Megamenu', 'dstheme' ),
							'not_found'             => __( 'No Megamenu found', 'dstheme' ),
							'not_found_in_trash'    => __( 'No Megamenu found in trash', 'dstheme' ),
							'parent'                => __( 'Parent Megamenu', 'dstheme' ),
							'menu_name'             => _x( 'DST Megamenus', 'Admin menu name', 'dstheme' ),
							'filter_items_list'     => __( 'Filter Megamenus', 'dstheme' ),
							'items_list_navigation' => __( 'Megamenus navigation', 'dstheme' ),
							'items_list'            => __( 'Megamenus list', 'dstheme' ),
						),
						'show_in_menu'        => 'themes.php',
						'menu_icon'           => 'dashicons-art',
						'public'              => true,
						'exclude_from_search' => true,
						'publicly_queryable'  => false,
						'show_in_rest'        => true,
						'has_archive'         => false,
						'supports'            => array( 'title', 'editor' ),
					)
				);
			}
		}
	}

	new DS_PostTypes_MegaMenu();
}
