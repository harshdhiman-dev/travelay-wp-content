<?php
/**
 * Register a Custom Post Type
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_PostTypes_FAQ' ) ) {

	/**
	 * Class responsible for registering a CPT
	 */
	class DS_PostTypes_FAQ {

		/**
		 * Declare CPT name
		 *
		 * @var string
		 */
		private string $name = 'faq';

		/**
		 * Construct
		 */
		public function __construct() {
			if ( ! theme_feature( 'faq_feature' ) ) {
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
							'name'                  => __( 'FAQ', 'dstheme' ),
							'singular_name'         => _x( 'FAQ', 'Site post type singular name', 'dstheme' ),
							'add_new'               => __( 'Add FAQ', 'dstheme' ),
							'add_new_item'          => __( 'Add New FAQ', 'dstheme' ),
							'edit'                  => __( 'Edit', 'dstheme' ),
							'edit_item'             => __( 'Edit FAQ', 'dstheme' ),
							'new_item'              => __( 'New FAQ', 'dstheme' ),
							'view'                  => __( 'View FAQ', 'dstheme' ),
							'view_item'             => __( 'View FAQ', 'dstheme' ),
							'search_items'          => __( 'Search FAQ', 'dstheme' ),
							'not_found'             => __( 'No FAQ found', 'dstheme' ),
							'not_found_in_trash'    => __( 'No FAQ found in trash', 'dstheme' ),
							'parent'                => __( 'Parent FAQ', 'dstheme' ),
							'menu_name'             => __( 'FAQ', 'dstheme' ),
							'filter_items_list'     => __( 'Filter FAQ', 'dstheme' ),
							'items_list_navigation' => __( 'FAQ navigation', 'dstheme' ),
							'items_list'            => __( 'FAQ list', 'dstheme' ),
						),
						'menu_position'       => 4,
						'menu_icon'           => 'dashicons-editor-help',
						'public'              => true,
						'exclude_from_search' => true,
						'publicly_queryable'  => false,
						'show_in_nav_menus'   => false,
						'show_in_rest'        => false,
						'has_archive'         => false,
						'supports'            => array( 'title', 'editor' ),
					)
				);
			}
		}
	}

	new DS_PostTypes_FAQ();
}
