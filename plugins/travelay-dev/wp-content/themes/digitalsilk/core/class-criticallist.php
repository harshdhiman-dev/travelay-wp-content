<?php
/**
 * Generate JSON file with list of all pages
 * to create critical css files with deploying
 *
 * @package DS_Theme
 */
// phpcs:ignoreFile
if ( ! class_exists( 'DS_CriticalList' ) ) {

	class DS_CriticalList {

		public $site_url;

		public function __construct() {
			$this->site_url = site_url();

			/**
			 * Actions to generate critical list
			 */
			add_action( 'after_switch_theme', array( $this, 'save_pages_list' ) );
			add_action( 'save_post_page', array( $this, 'save_pages_list' ) );

			/**
			 * Get critical css file for page
			 */
			add_action( 'wp_head', array( $this, 'get_page_critical' ), 0 );
		}

		/**
		 * Writes slugs into file
		 */
		public function save_pages_list() {
			$assets_dir = wp_get_upload_dir()['basedir'] . '/dsmp-assets/';

			if ( ! is_dir( $assets_dir ) ) {
				mkdir( $assets_dir, 0755 );
			}

			file_put_contents( "{$assets_dir}/criticallist.json", $this->get_pages_list() );
		}

		/**
		 * Get all pages slugs
		 *
		 * @param $post
		 *
		 * @return string
		 */
		public function get_pages_list() {
			$pages_list        = get_pages();
			$pages['site_url'] = $this->site_url;

			foreach ( $pages_list as $page ) {
				$pages['urls'][] = $this->get_slug( $page->ID );
			}

			return json_encode( $pages );
		}

		/**
		 * display critical css file
		 *
		 * @param $post
		 *
		 * @return string
		 */
		public function get_page_critical() {
			$queried_object = get_queried_object();
			if ( isset( $queried_object->post_type ) && $queried_object->post_type == 'page' ) {
				$slug = $this->get_slug( $queried_object->ID )['name'];
				$file = "/assets/_dist/css/critical/{$slug}.css";

				$critical_file_url  = get_template_directory_uri() . $file;
				$critical_file_path = get_theme_file_path() . $file;

				if ( file_exists( $critical_file_path ) ) {
					echo '<style title="CriticalCSS">' . file_get_contents( $critical_file_url ) . '</style>';
				}
			}
		}

		/**
		 * Get page slug
		 *
		 * @param $id
		 *
		 * @return array
		 */
		public function get_slug( $id ) {
			$frontpage_id   = get_option( 'page_on_front' );
			$page_permalink = get_permalink( $id );
			$permalink      = trim( str_replace( $this->site_url, '', $page_permalink ), '/' );

			return array(
				'link' => $page_permalink,
				'name' => $frontpage_id == $id ? 'home' : $permalink,
			);
		}
	}

	new DS_CriticalList();
}
