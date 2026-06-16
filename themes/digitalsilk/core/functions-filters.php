<?php
/**
 * Theme Filter Functions
 * Customize standard WordPress functionality for core or custom project filters
 *
 *  Usage: add_filter( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 )
 * Read more in codex: https://developer.wordpress.org/reference/functions/add_filter/
 *
 * @package DS_Theme
 */

if ( ! function_exists( 'theme_mime_types' ) ) {
	/**
	 * Add mime types
	 *
	 * @param array $existing_mimes contains existing mime types.
	 *
	 * @return mixed
	 */
	function theme_mime_types( $existing_mimes ) {
		$existing_mimes['webp'] = 'video/webp';
		$existing_mimes['svg']  = 'image/svg+xml';
		$existing_mimes['svgz'] = 'image/svg+xml';

		return $existing_mimes;
	}

	add_filter( 'upload_mimes', 'theme_mime_types' );
}


if ( ! function_exists( 'theme_excerpt_more' ) ) {
	/**
	 * Remove [] from the_excerpt();
	 *
	 * @param string $more contains the more text.
	 *
	 * @return string
	 */
	function theme_excerpt_more( $more ) {
		return '...';
	}

	add_filter( 'excerpt_more', 'theme_excerpt_more' );
}


if ( ! function_exists( 'theme_body_class' ) ) {
	/**
	 * Add special classes to pages
	 *
	 * @param array $classes contains body classes.
	 *
	 * @return mixed
	 */
	function theme_body_class( $classes ) {
		if ( is_home() ) {
			$classes[] = 'home';
		}

		return $classes;
	}

	add_filter( 'body_class', 'theme_body_class' );
}


if ( ! function_exists( 'ds_blog_rename_labels' ) ) {
	/**
	 * Rename default post type to Blog Posts
	 *
	 * @param object $labels contains current labels.
	 *
	 * @hooked post_type_labels_post
	 * @return object $labels
	 */
	function ds_blog_rename_labels( $labels ) {
		$labels->menu_name      = 'Resources';
		$labels->all_items      = 'All Resources';
		$labels->name_admin_bar = 'Resources';

		return $labels;
	}

	add_filter( 'post_type_labels_post', 'ds_blog_rename_labels' );
}


if ( ! function_exists( 'ds_blog_new_title_text' ) ) {
	/**
	 * Rename default post type labels
	 *
	 * @param string $title contains current title.
	 * @param object $post contains current post.
	 *
	 * @hooked enter_title_here
	 * @return string $title
	 */
	function ds_blog_new_title_text( $title, $post ) {
		if ( 'post' === $post->post_type ) {
			$title = 'Add Headline/H1';
		}

		return $title;
	}

	add_filter( 'enter_title_here', 'ds_blog_new_title_text', 10, 2 );
}


if ( ! function_exists( 'custom_wp_link_query' ) ) {
	/**
	 * Transforms absolute link to relative.
	 *
	 * @param array $results contains results.
	 * @param mixed $query contains query.
	 *
	 * @return array
	 */
	function custom_wp_link_query( $results, $query ) {
		$results_filtered = $results;

		if ( $results && is_array( $results ) ) {
			$results_filtered = array();
			foreach ( $results as $result ) {
				if ( ! empty( $result['permalink'] ) ) {
					$result['permalink'] = wp_make_link_relative( $result['permalink'] );
				}
				$results_filtered[] = $result;
			}
		}

		return $results_filtered;
	}

	add_filter( 'wp_link_query', 'custom_wp_link_query', 10, 2 );
}

/**
 * Disable WP comments
 */
add_action(
	'acf/init',
	function () {
		if ( ! theme_feature( 'comments_feature' ) ) {
			add_action(
				'admin_init',
				function () {
					// Redirect any user trying to access comments page.
					global $pagenow;
					if ( 'edit-comments.php' === $pagenow ) {
						wp_redirect( admin_url() ); //phpcs:ignore
						exit;
					}

					remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );

					foreach ( get_post_types() as $post_type ) {
						if ( post_type_supports( $post_type, 'comments' ) ) {
							remove_post_type_support( $post_type, 'comments' );
							remove_post_type_support( $post_type, 'trackbacks' );
						}
					}
				}
			);

			add_action(
				'admin_menu',
				function () {
					remove_menu_page( 'edit-comments.php' );
				}
			);

			add_action(
				'init',
				function () {
					if ( is_admin_bar_showing() ) {
						remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
					}
				}
			);

			add_filter( 'comments_open', '__return_false', 20, 2 );
			add_filter( 'pings_open', '__return_false', 20, 2 );
			add_filter( 'comments_array', '__return_empty_array', 10, 2 );
		}
	}
);

/**
 * Remove Autop for CF7
 */
add_filter( 'wpcf7_autop_or_not', '__return_false' );


if ( ! function_exists( 'ds_filter_wp_title' ) ) {
	/**
	 * Filter WP title to ensure no blank title is present
	 *
	 * @param string $title contains title.
	 * @param string $separator contains separator.
	 */
	function ds_filter_wp_title( $title, $separator ) {
		// Don't affect wp_title() calls in feeds.
		if ( is_feed() ) {
			return $title;
		}

		// The $paged global variable contains the page number of a listing of posts.
		// The $page global variable contains the page number of a single post that is paged.
		// We'll display whichever one applies, if we're not looking at the first page.
		global $paged, $page;

		if ( is_search() ) {
			// If we're a search, let's start over.
			$title = sprintf( __( 'Search results for %s', 'dstheme' ), '"' . get_search_query() . '"' ); //phpcs:ignore
			// Add a page number if we're on page 2 or more.
			if ( $paged >= 2 ) {
				$title .= " $separator " . sprintf( __( 'Page %s', 'dstheme' ), $paged ); //phpcs:ignore
			}
			// Add the site name to the end.
			$title .= " $separator " . get_bloginfo( 'name', 'display' );

			// We're done. Let's send the new title back to wp_title().
			return $title;
		}

		// Otherwise, let's start by adding the site name to the end.
		$title .= get_bloginfo( 'name', 'display' );

		// If we have a site description and we're on the home/front page, add the description.
		$site_description = get_bloginfo( 'description', 'display' );
		if ( $site_description && ( is_home() || is_front_page() ) ) {
			$title .= " $separator " . $site_description;
		}

		// Add a page number if necessary.
		if ( $paged >= 2 || $page >= 2 ) {
			$title .= " $separator " . sprintf( __( 'Page %s', 'dstheme' ), max( $paged, $page ) ); //phpcs:ignore
		}

		// Return the new title to wp_title().
		return $title;
	}

	add_filter( 'wp_title', 'ds_filter_wp_title', 10, 2 );
}


if ( ! function_exists( 'ds_mce_buttons_2' ) ) {
	/**
	 * Extend tinymce with additional options
	 *
	 * @param array $buttons contains mce buttons.
	 */
	function ds_mce_buttons_2( $buttons ) {

		array_unshift( $buttons, 'fontsizeselect', 'styleselect' );
		$buttons[] = 'superscript';
		$buttons[] = 'subscript';

		return $buttons;
	}

	add_filter( 'mce_buttons_2', 'ds_mce_buttons_2' );
}


if ( ! function_exists( 'ds_configure_tinymce' ) ) {

	/**
	 * Clear tags and attributes for copy-pasting from
	 * Word/Google doc to avoid unnecessary text formating
	 *
	 * @param array $init contains mce init.
	 *
	 * @return  array
	 */
	function ds_configure_tinymce( $init ) {
		$init['paste_preprocess'] = "function(plugin, args){
            // Strip all HTML tags except those we have whitelisted
            var whitelist = 'p,b,strong,i,em,h3,h4,h5,h6,ul,li,ol';
            var stripped = jQuery('<div>' + args.content + '</div>');
            var els = stripped.find('*').not(whitelist);
            for (var i = els.length - 1; i >= 0; i--) {
            var e = els[i];
            jQuery(e).replaceWith(e.innerHTML);
            }
            // Strip attributes
            stripped.find('*').removeAttr('id').removeAttr('class').removeAttr('style');
            // Return the clean HTML
            args.content = stripped.html();
            }";
		$init['fontsize_formats'] = 'inherit 10px 11px 12px 13px 14px 15px 16px 17px 18px 19px 20px 21px 22px 23px 24px 26px 28px 30px 32px 34px 36px 38px 40px 42px 44px 46px 48px 50px';

		// Styles for WYSIWYG editor in BE.
		$styles                = 'body.mce-content-body .read-more-wrapper {opacity: 0.5;} body.mce-content-body .read-more-toggle {display: none;} ';
		$init['content_style'] = isset( $init['content_style'] ) ? $init['content_style'] . $styles : $styles;

		return $init;
	}

	add_filter( 'tiny_mce_before_init', 'ds_configure_tinymce' );
}


if ( ! function_exists( 'ds_add_the_table_button' ) ) {
	/**
	 * Add TinyMCE table button
	 *
	 * @param array $buttons contains mce buttons.
	 *
	 * @return  array
	 */
	function ds_add_the_table_button( $buttons ) {
		array_push( $buttons, 'table' );

		return $buttons;
	}

	add_filter( 'mce_buttons', 'ds_add_the_table_button' );

	/**
	 * Add MCE table plugin
	 *
	 * @param array $plugins mce plugins.
	 */
	function ds_add_the_table_plugin( $plugins ) {
		$plugins['table'] = get_template_directory_uri() . '/admin/js/tinymce-table.min.js';

		return $plugins;
	}

	add_filter( 'mce_external_plugins', 'ds_add_the_table_plugin' );
}


if ( ! function_exists( 'ds_add_the_readmore_button' ) ) {
	/**
	 * Add TinyMCE custom readmore button
	 *
	 * @param array $buttons contains mce buttons.
	 *
	 * @return  array
	 */
	function ds_add_the_readmore_button( $buttons ) {
		array_push( $buttons, 'readmore_content' );

		return $buttons;
	}

	add_filter( 'mce_buttons', 'ds_add_the_readmore_button' );

	/**
	 * Add MCE readmore plugin
	 *
	 * @param array $plugins mce plugins.
	 */
	function ds_add_the_readmore_plugin( $plugins ) {
		$plugins['readmore_content'] = get_template_directory_uri() . '/admin/js/tinymce-read-more.js';

		return $plugins;
	}

	add_filter( 'mce_external_plugins', 'ds_add_the_readmore_plugin' );
}


if ( ! function_exists( 'ds_add_extra_body_classes' ) ) {

	/**
	 * Add classes <body> element
	 *
	 * @param array $classes body classes.
	 */
	function ds_add_extra_body_classes( $classes ) {
		$post_content = get_the_content();
		if ( is_page() && has_blocks( $post_content ) ) {
			$blocks      = parse_blocks( $post_content );
			$first_block = $blocks[0] ?? false;

			if ( ! empty( $first_block['innerBlocks'] ) ) {
				$first_block = isset( $first_block['innerBlocks'][0]['blockName'] ) ? acf_get_block_type( $first_block['innerBlocks'][0]['blockName'] ) : false;
			} else {
				$first_block = isset( $blocks[0]['blockName'] ) ? acf_get_block_type( $blocks[0]['blockName'] ) : false;
			}

			// phpcs:ignore
			if ( empty( $first_block ) || ( 'ds-banners' !== $first_block['category'] && ! in_array( 'banner', $first_block['keywords'] ) ) ) {
				$classes[] = 'no-banner';
			}
		}

		return $classes;
	}

	add_filter( 'body_class', 'ds_add_extra_body_classes' );
}


if ( ! function_exists( 'dst_fix_svg_metadata' ) ) {
	/**
	 * Fix WP and Woo throwing warnings due to svg missing width and height in regeneration of images in core.
	 *
	 * @param array $data contains data.
	 */
	function dst_fix_svg_metadata( $data ) {

		if ( ! isset( $data['width'] ) ) {
			$data['width'] = false;
		}

		if ( ! isset( $data['height'] ) ) {
			$data['height'] = false;
		}

		return $data;
	}

	add_filter( 'wp_get_attachment_metadata', 'dst_fix_svg_metadata' );
}


if ( ! function_exists( 'ds_search_archive_subheader_data' ) ) {

	/**
	 * Search page subheader
	 *
	 * @param array $data contains subheader data.
	 */
	function ds_search_archive_subheader_data( $data ): array {
		return wp_parse_args(
			array(
				'title'          => __( 'Search Results', 'dstheme' ),
				'search_form'    => get_search_form( array( 'echo' => false ) ),
				'subheader_show' => 'show',
			),
			$data
		);
	}

	add_filter( 'ds_any_archive_subheader_data', 'ds_search_archive_subheader_data', 10, 1 );
}


if ( ! function_exists( 'ds_disable_emojis_tinymce' ) ) {
	/**
	 * Filter function used to remove the tinymce emoji plugin.
	 *
	 * @param array $plugins contains mce plugins.
	 *
	 * @return array Difference betwen the two arrays
	 */
	function ds_disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, array( 'wpemoji' ) );
		} else {
			return array();
		}
	}

	add_filter( 'tiny_mce_plugins', 'ds_disable_emojis_tinymce' );
}


if ( ! function_exists( 'ds_disable_emojis_remove_dns_prefetch' ) ) {
	/**
	 * Remove emoji CDN hostname from DNS prefetching hints.
	 *
	 * @param array  $urls URLs to print for resource hints.
	 * @param string $relation_type The relation type the URLs are printed for.
	 *
	 * @return array Difference betwen the two arrays.
	 */
	function ds_disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' === $relation_type ) {
			/** This filter is documented in wp-includes/formatting.php */
			$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );
			$urls          = array_diff( $urls, array( $emoji_svg_url ) );
		}

		return $urls;
	}

	add_filter( 'wp_resource_hints', 'ds_disable_emojis_remove_dns_prefetch', 10, 2 );
}


if ( ! function_exists( 'ds_acf_format_span_and_break' ) ) {
	/**
	 * Format all acf text fields to replace:
	 * - |Example| to <span>Example</span>
	 * - \ to <br/>
	 *
	 * @param string $value The value.
	 * @param int    $post_id The post_id.
	 * @param string $field The field.
	 */
	function ds_acf_format_span_and_break( $value, $post_id, $field ) {
		if ( function_exists( 'ds_line_to_span_and_break' ) ) {
			return ds_line_to_span_and_break( $value );
		}

		return $value;
	}

	add_filter( 'acf/format_value/type=text', 'ds_acf_format_span_and_break', 10, 3 );
}


if ( ! function_exists( 'ds_remove_post_type_from_wp_link_query_args' ) ) {
	/**
	 * Remove specific post types from the search query in the link builder popup.
	 *
	 * @param array $query wp_query args.
	 */
	function ds_remove_post_type_from_wp_link_query_args( $query ) {
		$cpts_to_remove = array(
			'module_styles',
			'team',
			'testimonials',
			'faq',
		);

		if ( ! empty( $cpts_to_remove ) ) {
			foreach ( $cpts_to_remove as $cpt ) {
				// phpcs:ignore
				$key = array_search( $cpt, $query['post_type'] );

				// remove the array item.
				if ( $key ) {
					unset( $query['post_type'][ $key ] );
				}
			}
		}

		return $query;
	}

	add_filter( 'wp_link_query_args', 'ds_remove_post_type_from_wp_link_query_args' );
}


if ( ! function_exists( 'ds_adjust_archive_posts_per_page' ) ) {
	/**
	 * Adjust archive load more initial query with amount of posts to show based on GET param provided from JS logic.
	 *
	 * @param object $query wp_query.
	 */
	function ds_adjust_archive_posts_per_page( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return $query;
		}

		// phpcs:ignore
		if ( isset( $_GET['orderby'] ) ) {
			// phpcs:ignore
			$orderby_data = explode( '_', $_GET['orderby'] );
			if ( isset( $orderby_data[0] ) ) {
				$query->set( 'orderby', $orderby_data[0] );
			}
			if ( isset( $orderby_data[1] ) ) {
				$query->set( 'order', $orderby_data[1] );
			}
		}

		$pagination_type = get_field( 'pagination_type', 'options' );
		// phpcs:ignore
		if ( isset( $_GET['page_num'] ) && 'ajax' == $pagination_type ) {
			$posts_per_page = $query->query_vars['posts_per_page'] ?? intval( get_option( 'posts_per_page' ) );
			$page_num       = intval( $_GET['page_num'] ); // phpcs:ignore

			if ( 0 === $posts_per_page || 0 === $page_num ) {
				return $query;
			}

			$query->set( 'posts_per_page', $page_num * $posts_per_page );
		}

		return $query;
	}

	add_action( 'pre_get_posts', 'ds_adjust_archive_posts_per_page' );
}


if ( ! function_exists( 'ds_enable_honeypot_on_form_creation' ) ) {
	/**
	 * Enable honeypot automatically when new form created
	 *
	 * @param array   $form form data.
	 * @param boolean $is_new mark form saved is new or edited.
	 */
	function ds_enable_honeypot_on_form_creation( $form, $is_new ) {
		if ( $is_new ) {
			$form['enableHoneypot'] = true;

			\GFAPI::update_form( $form );
		}
	}

	add_action( 'gform_after_save_form', 'ds_enable_honeypot_on_form_creation', 10, 2 );
}

/**
 * Remove default styles for GravutyForms
 */
add_filter( 'gform_disable_css', '__return_true' );


/**
 * Convert absolute URLs to relative URLs for images in post content
 */
function dst_convert_urls_to_relative( $content ) {
	$site_url   = get_site_url();
	$parsed_url = wp_parse_url( $site_url );
	$base_url   = $parsed_url['scheme'] . '://' . $parsed_url['host'];

	return str_replace( $base_url, '', $content );
}

add_filter( 'content_save_pre', 'dst_convert_urls_to_relative' );
