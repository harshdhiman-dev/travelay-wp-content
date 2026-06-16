<?php
/**
 * Example CPT Create with Archive Filters:
 *
 * Needed Filters Are:
 *  -   ds_{$this->name}_archive_subheader_data - see @function archive_subheader_data()
 *  -   ds_{$this->name}_archive_filter_data - see @function archive_filter_data()
 *  -   ds_{$this->tax_name_1}_set_post_type - if $this->tax_name_1 is set as main taxonomy
 *
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_PostTypes_Example' ) ) {

	/**
	 * Class responsible for registering a CPT
	 */
	class DS_PostTypes_Example {

		/**
		 * Declare CPT name
		 *
		 * @var string
		 */
		private string $name = 'example';

		/**
		 * Declare CPT taxonomy name 1
		 *
		 * @var string
		 */
		private string $tax_name_1 = 'example_category';

		/**
		 * Declare CPT taxonomy name 1
		 *
		 * @var string
		 */
		private string $tax_name_2 = 'example_tag';

		/**
		 * Construct
		 */
		public function __construct() {

			/**
			 * If needed to be shown in some predefined Modules
			 */
			global $dsmp_settings;
			$dsmp_settings->set_global( 'post_types', $this->name );

			add_action( 'init', array( $this, 'register_post_types' ) );
			add_action( 'init', array( $this, 'register_taxonomies' ) );

			/**
			 * Archive Setup
			 */
			add_filter( "ds_{$this->name}_archive_subheader_data", array( $this, 'archive_subheader_data' ) );
			add_filter( "ds_{$this->name}_archive_filter_data", array( $this, 'archive_filter_data' ) );
			add_filter(
				"ds_{$this->tax_name_1}_set_post_type",
				function () {
					return $this->name;
				}
			);
			/**
			 * End Archive Setup
			 */
		}

		/**
		 * Add archive subheader data
		 *
		 * @param array $data contains data.
		 */
		public function archive_subheader_data( $data ) {
			return wp_parse_args(
				array(
					'background_image' => false,
					'pretitle'         => '',
					'title'            => __( 'Example CPT Title', 'dstheme' ),
					'subtitle'         => '',
				),
				$data
			);
		}

		/**
		 * Add archive subheader filter data
		 *
		 * @param array $data contains data.
		 */
		public function archive_filter_data( $data ) {
			return wp_parse_args(
				array(
					'main_taxonomy'             => $this->tax_name_1,
					'secondary_taxonomy'        => $this->tax_name_2, // leave empty if not needed.
					'secondary_taxonomy_title'  => __( 'Example Tags', 'dstheme' ), // leave empty if not needed.
					'enable_secondary_taxonomy' => true, // hide secondary filter on archive page if not needed.
				),
				$data
			);
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
							'name'                  => __( 'Examples', 'dstheme' ),
							'singular_name'         => _x( 'Example', 'Site post type singular name', 'dstheme' ),
							'add_new'               => __( 'Add Example', 'dstheme' ),
							'add_new_item'          => __( 'Add New Example', 'dstheme' ),
							'edit'                  => __( 'Edit', 'dstheme' ),
							'edit_item'             => __( 'Edit Example', 'dstheme' ),
							'new_item'              => __( 'New Example', 'dstheme' ),
							'view'                  => __( 'View Examples', 'dstheme' ),
							'view_item'             => __( 'View Example', 'dstheme' ),
							'search_items'          => __( 'Search Examples', 'dstheme' ),
							'not_found'             => __( 'No Examples found', 'dstheme' ),
							'not_found_in_trash'    => __( 'No Examples found in trash', 'dstheme' ),
							'parent'                => __( 'Parent Examples', 'dstheme' ),
							'menu_name'             => _x( 'Examples', 'Admin menu name', 'dstheme' ),
							'filter_items_list'     => __( 'Filter Examples', 'dstheme' ),
							'items_list_navigation' => __( 'Examples navigation', 'dstheme' ),
							'items_list'            => __( 'Examples list', 'dstheme' ),
						),
						'menu_position'       => 4,
						'menu_icon'           => 'dashicons-analytics',
						'public'              => true,
						'exclude_from_search' => false,
						'publicly_queryable'  => true,
						'show_in_rest'        => true,
						'has_archive'         => true,
						'supports'            => array( 'title', 'thumbnail', 'editor', 'excerpt' ),
						'rewrite'             => array(
							'slug'       => 'example',
							'with_front' => false,
						),
						'taxonomies'          => array( $this->tax_name_1, $this->tax_name_2 ),

					)
				);
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
				$this->tax_name_1,
				$this->name,
				array(
					'labels'             => array(
						'name'              => _x( 'Categories', 'taxonomy general name', 'dstheme' ),
						'singular_name'     => _x( 'Category', 'taxonomy singular name', 'dstheme' ),
						'search_items'      => __( 'Search Categories', 'dstheme' ),
						'all_items'         => __( 'All Categories', 'dstheme' ),
						'view_item'         => __( 'View Category', 'dstheme' ),
						'parent_item'       => __( 'Parent Category', 'dstheme' ),
						'parent_item_colon' => __( 'Parent Category:', 'dstheme' ),
						'edit_item'         => __( 'Edit Category', 'dstheme' ),
						'update_item'       => __( 'Update Category', 'dstheme' ),
						'add_new_item'      => __( 'Add New Category', 'dstheme' ),
						'new_item_name'     => __( 'New Category Name', 'dstheme' ),
						'not_found'         => __( 'No Categories Found', 'dstheme' ),
						'back_to_items'     => __( 'Back to Categories', 'dstheme' ),
						'menu_name'         => __( 'Category', 'dstheme' ),
					),
					'publicly_queryable' => true,
					'public'             => true,
					'hierarchical'       => true,
					'show_ui'            => true,
					'show_in_rest'       => true,
					'show_admin_column'  => true,
					'rewrite'            => array(
						'with_front' => false,
						'slug'       => 'example-category',
					),
				)
			);

			register_taxonomy(
				$this->tax_name_2,
				$this->name,
				array(
					'labels'             => array(
						'name'              => _x( 'Tag', 'taxonomy general name', 'dstheme' ),
						'singular_name'     => _x( 'Tag', 'taxonomy singular name', 'dstheme' ),
						'search_items'      => __( 'Search Tag', 'dstheme' ),
						'all_items'         => __( 'All Tag', 'dstheme' ),
						'view_item'         => __( 'View Tag', 'dstheme' ),
						'parent_item'       => __( 'Parent Tag', 'dstheme' ),
						'parent_item_colon' => __( 'Parent Tag:', 'dstheme' ),
						'edit_item'         => __( 'Edit Tag', 'dstheme' ),
						'update_item'       => __( 'Update Tag', 'dstheme' ),
						'add_new_item'      => __( 'Add New Tag', 'dstheme' ),
						'new_item_name'     => __( 'New Tag Name', 'dstheme' ),
						'not_found'         => __( 'No Tags Found', 'dstheme' ),
						'back_to_items'     => __( 'Back to Tag', 'dstheme' ),
						'menu_name'         => __( 'Tag', 'dstheme' ),
					),
					'publicly_queryable' => true,
					'public'             => false, // disable tags archive since we use it as filter.
					'hierarchical'       => false,
					'show_ui'            => true,
					'show_in_rest'       => true,
					'show_admin_column'  => true,
					'rewrite'            => array(
						'with_front' => false,
						'slug'       => 'example-tag',
					),
				)
			);
		}
	}

	// phpcs:ignore
	// new DS_PostTypes_Example();

}
