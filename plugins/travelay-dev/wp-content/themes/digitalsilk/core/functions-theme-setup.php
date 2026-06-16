<?php
/**
 * Sets up theme defaults and registers the various WordPress features that
 * custom supports.
 *
 * @uses    load_theme_textdomain() For translation/localization support.
 * @uses    add_editor_style() To add Visual Editor stylesheets.
 * @uses    add_theme_support() To add support for automatic feed links, post
 * formats, and post thumbnails.
 * @uses    register_nav_menu() To add support for a navigation menu.
 * @uses    set_post_thumbnail_size() To set a custom post thumbnail size.
 *
 * @since   custom 1.0
 * @package DS_Theme
 */

if ( ! function_exists( 'dst_setup_theme' ) ) {

	/**
	 * Setup theme
	 */
	function dst_setup_theme() {
		/*
		 * Makes custom available for translation.
		 *
		 * Translations can be added to the /languages/ directory.
		 * If you're building a theme based on custom, use a find and
		 * replace to change 'custom' to the name of your theme in all
		 * template files.
		 */
		load_theme_textdomain( 'dstheme', get_template_directory() . '/languages' );

		// Adds RSS feed links to <head> for posts and comments.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Switches default core markup for search form, comment form,
		 * and comments to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);

		/*
		 * Gutenberg full-width group support
		 */
		add_theme_support( 'align-wide' );

		/*
		 * This theme uses a custom image size for featured images, displayed on
		 * "standard" posts and pages.
		 */
		add_theme_support( 'post-thumbnails' );

		// phpcs:ignore
		// set_post_thumbnail_size(300, 400, true);

		add_image_size( 'ds_medium', 400, 300, false );
		add_image_size( 'ds_small', 200, 200, false );
	}

	add_action( 'after_setup_theme', 'dst_setup_theme' );
}


/**
 * Init Theme Widgets
 */
if ( ! function_exists( 'dst_register_widgets' ) ) {
	/**
	 * Init Theme Widgets
	 */
	function dst_register_widgets() {
		register_sidebar(
			array(
				'name'        => __( 'Widgets', 'dstheme' ),
				'id'          => 'widgets',
				'description' => 'Main Widget Area',
				'class'       => '',
			)
		);
	}

	// phpcs:ignore
	// add_action( 'widgets_init', 'dst_register_widgets' );
}


/**
 * Register Options page for ACF groups
 */
if ( ! function_exists( 'dst_register_option_page' ) ) {
	/**
	 * Register Options page for ACF groups
	 */
	function dst_register_option_page() {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return false;
		}

		$current_user = wp_get_current_user();

		if ( strpos( $current_user->user_email, '@digitalsilk.com' ) ) {
			$parent_features = acf_add_options_page(
				array(
					'page_title' => __( 'Theme Features', 'dstheme' ),
					'menu_title' => __( 'Theme Features', 'dstheme' ),
					'menu_slug'  => 'theme-features',
					'position'   => '1',
					'icon_url'   => 'dashicons-lightbulb',
					'redirect'   => false,
				)
			);

			acf_add_options_sub_page(
				array(
					'page_title'  => __( 'Allowed modules', 'dstheme' ),
					'menu_title'  => __( 'Allowed modules', 'dstheme' ),
					'menu_slug'   => 'allowed-modules',
					'parent_slug' => $parent_features['menu_slug'],
				)
			);
		}

		if ( strpos( $current_user->user_email, '@digitalsilk.com' ) ) {
			$parent_settings = acf_add_options_page(
				array(
					'page_title' => __( 'Theme Settings', 'dstheme' ),
					'menu_title' => __( 'Theme Settings', 'dstheme' ),
					'menu_slug'  => 'theme-settings',
					'position'   => '1',
					'icon_url'   => '',
					'redirect'   => false,
				)
			);

			acf_add_options_page(
				array(
					'page_title'  => __( 'Theme Fonts', 'dstheme' ),
					'menu_title'  => __( 'Fonts', 'dstheme' ),
					'menu_slug'   => 'theme-fonts',
					'parent_slug' => $parent_settings['menu_slug'],
				)
			);

			acf_add_options_page(
				array(
					'page_title'  => __( 'Theme Buttons', 'dstheme' ),
					'menu_title'  => __( 'Buttons', 'dstheme' ),
					'menu_slug'   => 'theme-buttons',
					'parent_slug' => $parent_settings['menu_slug'],
				)
			);

			acf_add_options_page(
				array(
					'page_title'  => __( 'Theme Forms', 'dstheme' ),
					'menu_title'  => __( 'Forms', 'dstheme' ),
					'menu_slug'   => 'theme-forms',
					'parent_slug' => $parent_settings['menu_slug'],
				)
			);
			acf_add_options_sub_page(
				array(
					'page_title'  => __( 'Theme General Content', 'dstheme' ),
					'menu_title'  => __( 'General Content', 'dstheme' ),
					'menu_slug'   => 'theme-general-content',
					'parent_slug' => $parent_settings['menu_slug'],
				)
			);
		}

		$parent_content = acf_add_options_page(
			array(
				'page_title' => __( 'Theme Content', 'dstheme' ),
				'menu_title' => __( 'Theme Content', 'dstheme' ),
				'menu_slug'  => 'theme-content',
				'position'   => '1',
				'icon_url'   => 'dashicons-welcome-widgets-menus',
			)
		);

		acf_add_options_sub_page(
			array(
				'menu_title'  => __( 'Theme Header', 'dstheme' ),
				'page_title'  => __( 'Theme Header', 'dstheme' ),
				'menu_slug'   => 'header-theme-content',
				'parent_slug' => $parent_content['menu_slug'],
			)
		);

		acf_add_options_sub_page(
			array(
				'menu_title'  => __( 'Theme Footer', 'dstheme' ),
				'page_title'  => __( 'Theme Footer', 'dstheme' ),
				'menu_slug'   => 'footer-theme-content',
				'parent_slug' => $parent_content['menu_slug'],
			)
		);

		acf_add_options_sub_page(
			array(
				'menu_title'  => __( 'Global content', 'dstheme' ),
				'page_title'  => __( 'Global content', 'dstheme' ),
				'menu_slug'   => 'global-theme-content',
				'parent_slug' => $parent_content['menu_slug'],
			)
		);

		acf_add_options_sub_page(
			array(
				'menu_title'  => __( 'Navigation widgets', 'dstheme' ),
				'page_title'  => __( 'Navigation widgets', 'dstheme' ),
				'menu_slug'   => 'navigation-theme-content',
				'parent_slug' => $parent_content['menu_slug'],
			)
		);

		if ( strpos( $current_user->user_email, '@digitalsilk.com' ) ) {
			acf_add_options_sub_page(
				array(
					'menu_title'  => __( 'Blog Settings', 'dstheme' ),
					'page_title'  => __( 'Blog Settings', 'dstheme' ),
					'menu_slug'   => 'blog-settings',
					'parent_slug' => 'edit.php',
				)
			);
		}
	}

	add_action( 'acf/init', 'dst_register_option_page' );
}


/**
 * Register site containers for header.php and footer.php
 */
if ( ! function_exists( 'dst_before_content' ) && ! function_exists( 'dst_after_content' ) ) {
	/**
	 * Output main before content
	 *
	 * @param string $css_class contains css class.
	 */
	function dst_before_content( $css_class = '' ) {
		?>
		<?php //phpcs:ignore ?>
		<main class="site-content<?php echo ! empty( $class ) ? " {$css_class}" : ''; ?>" role="main" id="wp--skip-link--target">
		<?php
	}

	add_action( 'ds_before_content', 'dst_before_content', 10, 1 );

	/**
	 * Output main closing after content
	 */
	function dst_after_content() {
		?>
		</main><!--main-->
		<?php
	}

	add_action( 'ds_after_content', 'dst_after_content', 10 );
}


if ( ! function_exists( 'dst_wdc_comments' ) ) {
	/**
	 * Template for comments and pingbacks.
	 *
	 * To override this walker in a child theme without modifying the comments template
	 * simply create your own wdc_comments(), and that function will be used instead.
	 *
	 * Used as a callback by wp_list_comments() for displaying the comments.
	 *
	 * @param mixed $comment holds the comment.
	 * @param array $args    arguments.
	 * @param int   $depth   current depth.
	 */
	function dst_wdc_comments( $comment, $args, $depth ) {
		// phpcs:ignore
		$GLOBALS['comment'] = $comment; ?>
		<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
			<div class="comment-wrap">
				<div class="comment-img">
					<?php
					echo get_avatar(
						$comment,
						$args['avatar_size'],
						null,
						null,
						array(
							'class' => array(
								'img-responsive',
								'img-circle',
							),
						)
					);
					?>
				</div>
				<div class="comment-body">
					<h4 class="comment-author"><?php echo get_comment_author_link(); ?></h4>
					<span class="comment-date">
						<?php
						// phpcs:ignore
						printf( esc_html__( '%1$s at %2$s', 'dstheme' ), get_comment_date(), get_comment_time() ); ?>
					</span>
					<?php
					// phpcs:ignore
					if ( '0' == $comment->comment_approved ) { ?>
						<em><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> <?php esc_html_e( 'Comment awaiting approval', 'dstheme' ); ?>
						</em><br/>
					<?php } ?>
					<?php comment_text(); ?>
					<span class="comment-reply">
					<?php
					comment_reply_link(
						array_merge(
							$args,
							array(
								'reply_text' => __( 'Reply', 'dstheme' ),
								'depth'      => $depth,
								'max_depth'  => $args['max_depth'],
							)
						),
						$comment->comment_ID
					);
					?>
					</span>
				</div>
			</div>
		</li>
		<?php
	}
}


if ( ! function_exists( 'dst_remove_theme_json_fonts' ) ) {
	/**
	 * Remove all the fonts loaded from theme.json.
	 */
	function dst_remove_theme_json_fonts( $theme_json ) {
		$theme_data = $theme_json->get_data();

		if ( isset( $theme_data['settings']['typography']['fontFamilies']['theme'] ) ) {
			unset( $theme_data['settings']['typography']['fontFamilies']['theme'] );
		}

		return $theme_json->update_with( $theme_data );
	}

	add_filter( 'wp_theme_json_data_theme', 'dst_remove_theme_json_fonts', 999 );
}

/**
 * Resolve issue with conflict between regular slugs and attachment slugs.
 */
add_filter( 'wp_unique_post_slug_is_bad_attachment_slug', '__return_true' );
add_filter(
	'wp_unique_post_slug',
	function ( $slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug ) {
		if ( 'attachment' === $post_type ) {
			$slug = $original_slug . uniqid( '-' );
		}

		return $slug;
	},
	10,
	6
);


