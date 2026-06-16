<?php
/**
 * Register a Custom Post Type
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_PostTypes_Testimonials' ) ) {
	/**
	 * Class responsible for registering a CPT
	 */
	class DS_PostTypes_Testimonials {

		/**
		 * Declare CPT name
		 *
		 * @var string
		 */
		private string $name = 'testimonials';


		/**
		 * Construct
		 */
		public function __construct() {
			if ( ! theme_feature( 'testimonials_feature' ) ) {
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
							'name'                  => __( 'Testimonials', 'dstheme' ),
							'singular_name'         => _x( 'Testimonials', 'Site post type singular name', 'dstheme' ),
							'add_new'               => __( 'Add Testimonial', 'dstheme' ),
							'add_new_item'          => __( 'Add New Testimonial', 'dstheme' ),
							'edit'                  => __( 'Edit', 'dstheme' ),
							'edit_item'             => __( 'Edit Testimonial', 'dstheme' ),
							'new_item'              => __( 'New Testimonial', 'dstheme' ),
							'view'                  => __( 'View Testimonials', 'dstheme' ),
							'view_item'             => __( 'View Testimonials', 'dstheme' ),
							'search_items'          => __( 'Search Testimonials', 'dstheme' ),
							'not_found'             => __( 'No Testimonials found', 'dstheme' ),
							'not_found_in_trash'    => __( 'No Testimonials found in trash', 'dstheme' ),
							'parent'                => __( 'Parent Testimonial', 'dstheme' ),
							'menu_name'             => __( 'Testimonials', 'dstheme' ),
							'filter_items_list'     => __( 'Filter Testimonials', 'dstheme' ),
							'items_list_navigation' => __( 'Testimonials navigation', 'dstheme' ),
							'items_list'            => __( 'Testimonials list', 'dstheme' ),
							'featured_image'        => _x( 'Testimonial Avatar', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'dstheme' ),
							'set_featured_image'    => _x( 'Set avatar image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'dstheme' ),
							'remove_featured_image' => _x( 'Remove avatar image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'dstheme' ),
							'use_featured_image'    => _x( 'Use as avatar image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'dstheme' ),
						),
						'menu_position'       => 4,
						'menu_icon'           => 'dashicons-format-status',
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

	new DS_PostTypes_Testimonials();
}
