<?php
/**
 * Register a Custom Post Type
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_PostTypes_CaseStudies' ) ) {

	/**
	 * Class responsible for registering a CPT
	 */
	class DS_PostTypes_CaseStudies {

		/**
		 * Declare CPT name
		 *
		 * @var string
		 */
		private string $name = 'case_studies';

		/**
		 * Construct
		 */
		public function __construct() {
			if ( ! theme_feature( 'case_studies_feature' ) ) {
				return;
			}

			global $dsmp_settings;
			$dsmp_settings->set_global( 'post_types', $this->name );

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
							'name'                  => __( 'Case Studies', 'dstheme' ),
							'singular_name'         => _x( 'Case Study', 'Site post type singular name', 'dstheme' ),
							'add_new'               => __( 'Add Case Study', 'dstheme' ),
							'add_new_item'          => __( 'Add New Case Study', 'dstheme' ),
							'edit'                  => __( 'Edit', 'dstheme' ),
							'edit_item'             => __( 'Edit Case Study', 'dstheme' ),
							'new_item'              => __( 'New Case Study', 'dstheme' ),
							'view'                  => __( 'View Case Studies', 'dstheme' ),
							'view_item'             => __( 'View Case Studies', 'dstheme' ),
							'search_items'          => __( 'Search Case Studies', 'dstheme' ),
							'not_found'             => __( 'No Case Studies found', 'dstheme' ),
							'not_found_in_trash'    => __( 'No Case Studies found in trash', 'dstheme' ),
							'parent'                => __( 'Parent Case Studies', 'dstheme' ),
							'menu_name'             => _x( 'Case Studies', 'Admin menu name', 'dstheme' ),
							'filter_items_list'     => __( 'Filter Case Studies', 'dstheme' ),
							'items_list_navigation' => __( 'Case Studies navigation', 'dstheme' ),
							'items_list'            => __( 'Case Studies list', 'dstheme' ),
						),
						'menu_position'       => 4,
						'menu_icon'           => 'dashicons-analytics',
						'public'              => true,
						'exclude_from_search' => false,
						'publicly_queryable'  => true,
						'show_in_rest'        => true,
						'has_archive'         => false,
						'supports'            => array( 'title', 'thumbnail', 'editor' ),
						'rewrite'             => array(
							'slug'       => 'case-studies',
							'with_front' => false,
						),
					)
				);
			}
		}
	}

	new DS_PostTypes_CaseStudies();
}
