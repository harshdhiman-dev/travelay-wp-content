<?php
/**
 * Render global side navigation if enabled
 */
// phpcs:ignoreFile
class DS_SideNavigation {

	public function __construct() {

		$post_id = get_queried_object_id();
		if ( class_exists( 'woocommerce' ) && is_shop() ) {
			$post_id = wc_get_page_id( 'shop' );
		}

		if ( empty( get_option( 'options_side_navigation_enable' ) ) || get_field( 'side_navigation_disable', $post_id ) ) {
			return;
		}

		$side_nav = get_field( 'side_navigation', 'option' );

		if ( empty( $side_nav['links'] ) ) {
			return;
		}

		acf_render_block(
			array(
				'id'   => uniqid( 'block_' ),
				'name' => 'acf/navigation',
				'data' => array(
					'anchor_navigation'                => $side_nav['links'],
					'component_settings_style'         => $side_nav['style'],
					'component_settings_orientation'   => $side_nav['orientation'],
					'component_settings_position'      => $side_nav['position'],
					'component_settings_add_icon'      => $side_nav['add_icon'],
					'component_settings_icon'          => $side_nav['icon'],
					'component_settings_icon_position' => $side_nav['icon_position'],
				),
			)
		);
	}
}

add_action(
	'ds_after_content',
	function () {
		new DS_SideNavigation();
	},
	9
);
