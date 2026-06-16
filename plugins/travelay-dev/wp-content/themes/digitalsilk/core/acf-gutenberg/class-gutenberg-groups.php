<?php
/**
 * Register ACF block_categories for Gutenberg editor
 *
 * Category slug will be used as:
 * 1) variable value to register module $category
 * 2) separator between module controllers and templates
 * /core/gutenberg/modules/%category%/
 * /templates/gutenberg/%category%/
 */
// phpcs:ignoreFile
class DS_ACFGutenbergBlocks {

	public function __construct() {
		add_filter( 'block_categories_all', array( $this, 'acf_categories_init' ), 10, 2 );
	}

	public function acf_categories_init( $categories, $post ) {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'ds-hybrid',
					'title' => __( 'New DST Modules', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-components',
					'title' => __( 'DST Gutenberg Components', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-layouts',
					'title' => __( 'DST Layouts', 'dstheme-admin' ),
				),

				array(
					'slug'  => 'ds-content',
					'title' => __( 'DS Content Modules (Classic)', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-counters',
					'title' => __( 'DS Counters', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-sliders',
					'title' => __( 'DS Sliders', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-accordions',
					'title' => __( 'DS Accordions', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-tabs',
					'title' => __( 'DS Tabs', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-banners',
					'title' => __( 'DS Banners', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-forms',
					'title' => __( 'DS Forms', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-functional',
					'title' => __( 'DS Functional', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-3d-media',
					'title' => __( 'DS 3D Media', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-gallery',
					'title' => __( 'DS Galleries', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-team',
					'title' => __( 'DS Team', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-newsletter-signups',
					'title' => __( 'DS Newsletter Signup', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-posts',
					'title' => __( 'DST Blog Posts', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-separators',
					'title' => __( 'DS Separators', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-test',
					'title' => __( 'DS Test', 'dstheme-admin' ),
				),
				array(
					'slug'  => 'ds-widgets',
					'title' => __( 'DS Widgets', 'dstheme-admin' ),
				),
			)
		);
	}
}

new DS_ACFGutenbergBlocks();
