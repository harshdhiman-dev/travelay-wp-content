<?php
/**
 * Add additional data to menu items
 * $caption
 * $image aka icon
 * $hash to the url
 *
 * @package DS_Theme
 */

if ( ! function_exists( 'ds_add_extra_items_to_menu' ) ) {
	/**
	 * Add extra menu items
	 *
	 * @param array $items contains menu items.
	 * @param array $args contains menu args.
	 */
	function ds_add_extra_items_to_menu( $items, $args ) {
		foreach ( $items as &$item ) {
			$caption = get_field( 'caption', $item );
			if ( $caption ) {
				$item->title = '<span class="menu-item-title">' . $item->title . '</span><span class="menu-item-caption">' . $caption . '</span>';
			}

			$image = get_field( 'icon', $item );
			if ( $image ) {
				$item->title = '<div class="menu-item-img-wrapper">' . ds_generate_image( $image['ID'], 'ds_small', 'menu-item-image' ) . '</div><div class="menu-item-content">' . $item->title . '</div>';
			}
		}

		return $items;
	}

	add_filter( 'wp_nav_menu_objects', 'ds_add_extra_items_to_menu', 10, 2 );
}

if ( ! function_exists( 'ds_change_a_tag_to_span' ) ) {
	/**
	 * Adjust the <a> tag to be span in case the link is set as #
	 *
	 * @param html   $item_output contains item output.
	 * @param object $item menu item.
	 * @param int    $depth menu depth.
	 * @param array  $args menu args.
	 */
	function ds_change_a_tag_to_span( $item_output, $item, $depth, $args ) {
		if ( '#' === $item->url ) {
			// Add toggle for menu item wish has children (mobile).
			// phpcs:ignore
			if ( in_array( 'menu-item-has-children', $item->classes ) || in_array( 'page_item_has_children', $item->classes ) ) {
				return "<div class='plain-menu-item'>{$item->post_title} </div><button class='nav-icon js-sub-menu-toggle'>" . get_svg( array( 'icon' => 'arrow-navbar' ) ) . '</button>';
			} else {
				return "<div class='plain-menu-item'>{$item->post_title}</div>";
			}
		}

		return $item_output;
	}

	add_filter( 'walker_nav_menu_start_el', 'ds_change_a_tag_to_span', 10, 4 );
}

if ( ! function_exists( 'ds_megamenu' ) ) {
	/**
	 * Processes and outputs a megamenu item if custom post-type of added menu is megamenu.
	 *
	 * @param string $item_output The default output for the menu item.
	 * @param object $item The menu item object.
	 * @param int    $depth The depth of the menu item.
	 * @param array  $args Arguments for the menu.
	 *
	 * @return string The modified menu item output, including megamenu content if applicable.
	 */
	function ds_megamenu( $item_output, $item, $depth, $args ) {
		if ( 'megamenu' === $item->object ) {
			if ( $depth > 0 ) {
				$megamenu_id = $item->object_id;
				if ( ! empty( $megamenu_id ) ) {
					ob_start(); ?>
					<div class="megamenu">
						<?php echo apply_filters( 'the_content', get_the_content( null, false, $megamenu_id ) ); //phpcs:ignore ?>
					</div>
					<?php
					return ob_get_clean();
				}
			} else {
				return "<div class='plain-menu-item'>{$item->post_title}</div>";
			}
		}

		return $item_output;
	}

	add_filter( 'walker_nav_menu_start_el', 'ds_megamenu', 20, 4 );
}

if ( ! function_exists( 'ds_toggle_btn_menu_item_has_children' ) ) {
	/**
	 * Add toggle button for menu item wish has children
	 *
	 * @param html   $item_output contains item output.
	 * @param object $item menu item.
	 * @param int    $depth menu depth.
	 * @param array  $args menu args.
	 */
	function ds_toggle_btn_menu_item_has_children( $item_output, $item, $depth, $args ) {
		// phpcs:ignore
		if ( in_array( $args->theme_location, array( 'primary-menu', 'secondary-menu' ) ) ) {
			if ( in_array( 'menu-item-has-children', $item->classes ) || in_array( 'page_item_has_children', $item->classes ) ) {
				$item_output = str_replace( $args->link_after . '</a>', $args->link_after . '</a><button class="nav-icon js-sub-menu-toggle">' . get_svg( array( 'icon' => 'arrow-navbar' ) ) . '</button>', $item_output );
			}
		}

		return $item_output;
	}

	add_filter( 'walker_nav_menu_start_el', 'ds_toggle_btn_menu_item_has_children', 11, 4 );
}
