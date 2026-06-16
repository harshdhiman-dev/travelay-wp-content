<?php
/**
 * Extend post type post
 *
 * @package DS_Theme
 */

global $dsmp_settings;
$dsmp_settings->set_global( 'post_types', 'post' );

if ( ! function_exists( 'ds_filter_archive_posts' ) ) {
	/**
	 * Filter archive posts
	 *
	 * @param WP_Query $query contains wp_query.
	 */
	function ds_filter_archive_posts( $query ) {
		if ( is_admin() ) {
			return $query;
		}

		// phpcs:ignore
		if ( isset( $_GET['tag'] ) && ! empty( $_GET['tag'] ) && $query->is_main_query() && ( $query->is_home() || $query->is_category() ) ) {
			$tax_query   = array();
			$tax_query[] = array(
				'taxonomy' => 'post_tag',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $_GET['tag'] ), // phpcs:ignore
			);
			$query->set( 'tax_query', $tax_query );
		}

		return $query;
	}

	add_action( 'pre_get_posts', 'ds_filter_archive_posts' );
}

if ( ! function_exists( 'ds_blog_taxonomy_register_args' ) ) {
	/**
	 * Update category
	 *
	 * @param array  $args contains args.
	 * @param string $taxonomy contains taxonomy name.
	 */
	function ds_blog_taxonomy_register_args( $args, $taxonomy ) {

		if ( ! in_array( $taxonomy, array( 'category', 'post_tag' ) ) ) {
			return $args;
		}

		if ( 'category' === $taxonomy ) {
			$args['rewrite']['with_front'] = false;
		}

		if ( 'post_tag' === $taxonomy ) {
			$args['public'] = false;
		}

		return $args;
	}

	add_filter( 'register_taxonomy_args', 'ds_blog_taxonomy_register_args', 10, 2 );
}

if ( ! function_exists( 'ds_blog_archive_subheader_data' ) ) {

	/**
	 * Blog archive subheader data
	 *
	 * @param array $data contains data.
	 */
	function ds_blog_archive_subheader_data( $data ): array {
		$page_id = get_option( 'page_for_posts' );

		return wp_parse_args(
			array(
				'background_image' => get_field( 'image', $page_id ),
				'pretitle'         => get_field( 'pretitle', $page_id ),
				'title'            => get_field( 'title', $page_id ),
				'subtitle'         => get_field( 'subtitle', $page_id ),
				'subheader_show'   => get_field( 'blog_subheader', 'options' ) ?: 'hide',
			),
			$data
		);
	}

	add_filter( 'ds_post_archive_subheader_data', 'ds_blog_archive_subheader_data', 10, 1 );
}

if ( ! function_exists( 'ds_blog_archive_filter_data' ) ) {
	/**
	 * Blog archive filter data
	 *
	 * @param array $data contains data.
	 */
	function ds_blog_archive_filter_data( $data ): array {
		return wp_parse_args(
			array(
				'main_taxonomy'             => 'category',
				'secondary_taxonomy'        => 'post_tag',
				'secondary_taxonomy_title'  => get_field( 'tag_filter_title', 'options' ),
				'enable_secondary_taxonomy' => get_field( 'show_tag_filter', 'options' ),
			),
			$data
		);
	}

	add_filter( 'ds_post_archive_filter_data', 'ds_blog_archive_filter_data', 10, 1 );
}
