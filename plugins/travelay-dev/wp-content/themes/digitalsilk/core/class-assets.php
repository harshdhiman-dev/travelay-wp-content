<?php
/**
 * Enqueue scripts and styles for the front end.
 *
 * @since custom 1.2
 * @package DS_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'DS_ThemeAssets' ) ) {

	/**
	 * Main class to handle theme assets
	 */
	class DS_ThemeAssets {
		/**
		 * Construct
		 */
		public function __construct() {
			/**
			 * Enqueue theme assets.
			 */
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'theme_assets' ), 99 );

			/**
			 * Enqueue Admin panel additional styles and scripts.
			 */
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'theme_admin_assets' ) );
			add_action( 'enqueue_block_assets', array( __CLASS__, 'theme_admin_assets' ) );
			add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'theme_admin_editor_assets' ) );

			/**
			 * Emoticons will still work and emoji’s will still work in browsers which have built in support for them.
			 * This action simply removes the extra code bloat used to add support for emoji’s in older browsers.
			 */
			add_action( 'init', array( __CLASS__, 'theme_disable_emojis' ) );

			/**
			 * Disable standard plugin scripts.
			 * For example remove Contact Form 7 css and ajax functionality
			 */
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'theme_deregister_scripts' ), 99 );
			add_action( 'wp_print_styles', array( __CLASS__, 'theme_deregister_styles' ), 99 );

			/**
			 * Optimize JS and CSS files.
			 */
			add_filter( 'script_loader_tag', array( __CLASS__, 'defer_js_parser' ) );
			add_filter( 'style_loader_tag', array( __CLASS__, 'load_css_parser' ) );
		}

		/**
		 * Enqueue scripts and styles for the front end.
		 */
		public static function theme_assets() {
			// phpcs:ignore
			// wp_enqueue_script( 'loadCSS', get_template_directory_uri() . '/assets/vendors/cssrelpreload.js', false, null, false );
			wp_enqueue_script( 'jquery' );

			global $wp_query;
			$ds_vars = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'query'    => wp_json_encode( $wp_query->query_vars ),

			);

			if ( class_exists( 'woocommerce' ) ) {
				$ds_vars['isCart']          = is_cart();
				$ds_vars['isShop']          = false;
				$ds_vars['isSingleProduct'] = is_product();
				$ds_vars['isCheckout']      = is_checkout();
			}

			if ( get_option( 'options_enable_toc' ) && is_single() ) { // TODO include list of public post types
				DS_ViteAssets::enqueue_script( 'js/dst-blog-toc.js' );
				DS_ViteAssets::enqueue_style( 'sass/blog/widgets/dst-toc.scss' );
				$ds_vars['toc_title'] = wp_json_encode( get_option( 'options_toc_settings_toc_title' ) );
				$ds_vars['toc_tags']  = wp_json_encode( get_option( 'options_toc_settings_tags_to_include' ) );
			}

			wp_localize_script( 'jquery', 'ds', $ds_vars );

			// Add vite dev script for local development
			DS_ViteAssets::add_vite_dev_scripts();

			// Enqueue critical CSS
			DS_ViteAssets::enqueue_style( 'sass/dst-critical.scss' );

			// Enqueue main CSS
			DS_ViteAssets::enqueue_style( 'sass/dst-main.scss' );

			// Enqueue blog assets if necessary
			if ( is_home() || is_category() || is_single() || is_search() || is_post_type_archive( 'post' ) ) {
				DS_ViteAssets::enqueue_script( 'js/dst-blog.js' );
				DS_ViteAssets::enqueue_style( 'sass/dst-blog.scss' );
			}

			// Enqueue main JS
			DS_ViteAssets::enqueue_script( 'js/index.js' );

			// Enqueue vendor JS
			DS_ViteAssets::enqueue_script( 'js/dst-vendor.js' );

			wp_enqueue_script( 'lazyload-js', get_template_directory_uri() . '/assets/vendors/lazyload/lazyload.min.js', true, true, true );

			wp_enqueue_script( 'dimbox-js', get_template_directory_uri() . '/assets/vendors/dimbox/js/dimbox.min.js', true, true, true );
			wp_enqueue_style( 'dimbox-css', get_template_directory_uri() . '/assets/vendors/dimbox/css/dimbox.min.css', array(), '1.8' );
		}

		/**
		 * Load Admin Panel styles and js
		 */
		public static function theme_admin_assets() {

			// Bail early to not load items on FE due to block assets hook.
			if ( ! is_admin() ) {
				return;
			}

			$uploads_dir    = wp_get_upload_dir();
			$theme_css_path = $uploads_dir['basedir'] . '/dsmp-assets/theme.css';
			if ( file_exists( $theme_css_path ) ) {
				wp_enqueue_style( 'theme-root-css', ds_get_uploads_dir_baseurl() . '/dsmp-assets/theme.css', array(), filemtime( $theme_css_path ) );
			}

			DS_ViteAssets::enqueue_style( 'sass/dst-admin.scss' );

			wp_enqueue_script( 'admin-js', get_template_directory_uri() . '/admin/js/theme.js', array( 'jquery' ), filemtime( get_template_directory() . '/admin/js/theme.js' ), true );

			$isset_plugin_acf = class_exists( 'Acf' );
			wp_localize_script(
				'admin-js',
				'theme_js_params',
				array(
					'is_acf_exist'      => $isset_plugin_acf,
					'theme_path'        => get_stylesheet_directory_uri(),
					'theme_parent_path' => get_template_directory_uri(),
					'styleguide_colors' => ds_get_styleguide_colors(),
				)
			);

			if ( ! ds_is_super_admin() ) {
				wp_enqueue_style( 'super-admin-css', get_template_directory_uri() . '/admin/css/super-admin.css', array(), filemtime( get_template_directory() . '/admin/css/super-admin.css' ) );
				wp_enqueue_script( 'hide-super-admin-data', get_template_directory_uri() . '/admin/js/hide-super-admin-data.js', array(), filemtime( get_template_directory() . '/admin/js/hide-super-admin-data.js' ), true );
			}

			// Enqueue dimbox styles.
			wp_enqueue_style( 'dimbox-css', get_template_directory_uri() . '/assets/vendors/dimbox/css/dimbox.min.css', array(), '1.8' );
		}

		/**
		 * Add Admin Editor assets
		 */
		public static function theme_admin_editor_assets() {
			wp_enqueue_script( 'content-available-blocks-js', get_template_directory_uri() . '/admin/js/content-available-blocks.js', array(), filemtime( get_template_directory() . '/admin/js/content-available-blocks.js' ), true );
			wp_enqueue_script( 'ds-wrapper-range', get_template_directory_uri() . '/admin/js/wrapper-range.js', array(), filemtime( get_template_directory() . '/admin/js/wrapper-range.js' ), true );

			$current_screen = get_current_screen();
			if ( isset( $current_screen->post_type ) && 'page' === $current_screen->post_type && $current_screen->is_block_editor ) {
				wp_enqueue_script( 'admin-page-template-switcher-js', get_template_directory_uri() . '/admin/js/ds-page-template-switcher.js', array(), filemtime( get_template_directory() . '/admin/js/ds-page-template-switcher.js' ), true );
			}
		}

		/**
		 * Disable the emoji's
		 */
		public static function theme_disable_emojis() {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
		}

		/**
		 * Dequeue Ajax scripts for the front end.
		 */
		public static function theme_deregister_scripts() {
			// phpcs:ignore
			// wp_dequeue_script( 'contact-form-7' );

			// These scripts are currently being used only on single post types.
			if ( ! is_single() ) {
				wp_dequeue_script( 'addtoany-core' );
				wp_deregister_script( 'addtoany-core' );
				wp_dequeue_script( 'addtoany-jquery' );
				wp_deregister_script( 'addtoany-jquery' );
			}
		}

		/**
		 * Dequeue Styles for the front end.
		 */
		public static function theme_deregister_styles() {
			// phpcs:ignore
			// wp_dequeue_style( 'contact-form-7' );

			// These styles are currently being used only on single post types.
			if ( ! is_single() ) {
				wp_dequeue_style( 'addtoany' );
				wp_dequeue_style( 'ez-toc' );
			}

			if ( ! is_single() && ! is_page_template( 'templates/template-simple-text.php' ) ) {
				wp_dequeue_style( 'wp-block-library' );
			}
		}

		/**
		 * Convert CSS scripts for use with loadCSS library - https://github.com/filamentgroup/loadCSS
		 *
		 * @param string $tag asset tag.
		 *
		 * @return string $tag
		 */
		public static function load_css_parser( $tag ) {
			if ( is_admin() ) {
				return $tag;
			}

			// do not edit, if not a stylesheet.
			if ( false === strpos( $tag, "rel='stylesheet'" ) ) {
				return $tag;
			}
			// if tag includes any of these file names do not load via LoadCSS.
			$styles_to_exclude = array(
				DS_ViteAssets::get_entry_from_manifest( 'sass/dst-critical.scss' ),
				'critical.min.css',
				'critical.css',
				'dashicons.min.css',
				'buttons.min.css',
				'forms.min.css',
				'login.min.css',
				'tinvwl-webfont-font-css',
			);

			if ( class_exists( 'woocommerce' ) ) {
				$styles_to_exclude[] = DS_ViteAssets::get_entry_from_manifest( 'sass/woo/dst-wc-critical.scss' );
			}

			foreach ( $styles_to_exclude as $exclude_style ) {
				// phpcs:ignore
				if ( true == strpos( $tag, $exclude_style ) ) {
					return $tag;
				}
			}

			// Remove id attr from <link>.
			$tag = preg_replace( "/id='(.+?)'/", '', $tag );

			// Set a variable which will hold the default script for a '<noscript>' tag.
			$noscript = '<noscript>' . $tag . '</noscript>';
			// Change 'rel' value from 'stylesheet' to 'preload'.
			$tag = preg_replace( "/='stylesheet'/", '="preload"', $tag );
			// Add 'as' and 'onload' attributes.
			$tag = preg_replace( "/type='text\/css'/", 'as="style" onload="this.onload=null;this.rel=\'stylesheet\'"', $tag );
			// Remove media attribute.
			$tag = preg_replace( "/media='.*'/", '', $tag );

			return $tag . $noscript;
		}

		/**
		 * Defer parsing for js files - https://www.w3schools.com/tags/att_script_defer.asp
		 *
		 * @param string $url script url.
		 *
		 * @return string $url
		 */
		public static function defer_js_parser( $url ) {
			if ( is_user_logged_in() ) {
				return $url;
			}

			// do not edit if not a js files.
			if ( false === strpos( $url, '.js' ) ) {
				return $url;
			}

			if ( strpos( $url, 'jquery.js' ) ) {
				return $url;
			}

			if ( strpos( $url, 'wp-includes' ) ) {
				return $url;
			}

			if ( strpos( $url, 'woocommerce/assets/client/blocks' ) ) {
				return $url;
			}

			return str_replace( ' src', ' defer src', $url );
		}
	}

	new DS_ThemeAssets();
}

/*
* You can add standart plugin from core
*
* example:
*
*
* Adds Masonry to handle vertical alignment of footer widgets.
* wp_enqueue_script( 'jquery-masonry' );
*
*
*
* Adds JavaScript to pages with the comment form to support
* sites with threaded comments (when in use).
*
* if (is_singular() && comments_open() && get_option('thread_comments')) {
*      wp_enqueue_script('comment-reply');
* }
*
*
*
* Full scripts list(scripts inclide in wp-includes/script-loader.php file):
*
utils                     /wp-admin/js/utils.js
common                    /wp-admin/js/common.js
sack                      /wp-includes/js/tw-sack.js
quicktags                 /wp-includes/js/quicktags.js
colorpicker               /wp-includes/js/colorpicker.js
editor                    /wp-admin/js/editor.js
wp-fullscreen             /wp-admin/js/wp-fullscreen.js
prototype                 /wp-includes/js/prototype.js
wp-ajax-response          /wp-includes/js/wp-ajax-response.js
wp-pointer                /wp-includes/js/wp-pointer.js
autosave                  /wp-includes/js/autosave.js
wp-lists                  /wp-includes/js/wp-lists.js
scriptaculous-root        /wp-includes/js/scriptaculous/wp-scriptaculous.js
scriptaculous-builder     /wp-includes/js/scriptaculous/builder.js
scriptaculous-dragdrop    /wp-includes/js/scriptaculous/dragdrop.js
scriptaculous-effects   /wp-includes/js/scriptaculous/effects.js
scriptaculous-slider    /wp-includes/js/scriptaculous/slider.js
scriptaculous-sound /wp-includes/js/scriptaculous/sound.js
scriptaculous-controls  /wp-includes/js/scriptaculous/controls.js
scriptaculous   scriptaculous-dragdrop, scriptaculous-slider, scriptaculous-controls, scriptaculous-root
cropper                   /wp-includes/js/crop/cropper.js
jquery                    /wp-includes/js/jquery/jquery.js
jquery-ui-core            /wp-includes/js/jquery/ui/jquery.ui.core.min.js
jquery-effects-core /wp-includes/js/jquery/ui/jquery.effects.core.min.js
jquery-effects-blind    /wp-includes/js/jquery/ui/jquery.effects.blind.min.js
jquery-effects-bounce   /wp-includes/js/jquery/ui/jquery.effects.bounce.min.js
jquery-effects-clip /wp-includes/js/jquery/ui/jquery.effects.clip.min.js
jquery-effects-drop /wp-includes/js/jquery/ui/jquery.effects.drop.min.js
jquery-effects-explode  /wp-includes/js/jquery/ui/jquery.effects.explode.min.js
jquery-effects-fade /wp-includes/js/jquery/ui/jquery.effects.fade.min.js
jquery-effects-fold /wp-includes/js/jquery/ui/jquery.effects.fold.min.js
jquery-effects-highlight    /wp-includes/js/jquery/ui/jquery.effects.highlight.min.js
jquery-effects-pulsate  /wp-includes/js/jquery/ui/jquery.effects.pulsate.min.js
jquery-effects-scale    /wp-includes/js/jquery/ui/jquery.effects.scale.min.js
jquery-effects-shake    /wp-includes/js/jquery/ui/jquery.effects.shake.min.js
jquery-effects-slide    /wp-includes/js/jquery/ui/jquery.effects.slide.min.js
jquery-effects-transfer /wp-includes/js/jquery/ui/jquery.effects.transfer.min.js
jquery-ui-accordion /wp-includes/js/jquery/ui/jquery.ui.accordion.min.js
jquery-ui-autocomplete  /wp-includes/js/jquery/ui/jquery.ui.autocomplete.min.js
jquery-ui-button          /wp-includes/js/jquery/ui/jquery.ui.button.min.js
jquery-ui-datepicker    /wp-includes/js/jquery/ui/jquery.ui.datepicker.min.js
jquery-ui-dialog          /wp-includes/js/jquery/ui/jquery.ui.dialog.min.js
jquery-ui-draggable /wp-includes/js/jquery/ui/jquery.ui.draggable.min.js
jquery-ui-droppable /wp-includes/js/jquery/ui/jquery.ui.droppable.min.js
jquery-ui-mouse           /wp-includes/js/jquery/ui/jquery.ui.mouse.min.js
jquery-ui-position  /wp-includes/js/jquery/ui/jquery.ui.position.min.js
jquery-ui-progressbar   /wp-includes/js/jquery/ui/jquery.ui.progressbar.min.js
jquery-ui-resizable /wp-includes/js/jquery/ui/jquery.ui.resizable.min.js
jquery-ui-selectable    /wp-includes/js/jquery/ui/jquery.ui.selectable.min.js
jquery-ui-slider          /wp-includes/js/jquery/ui/jquery.ui.slider.min.js
jquery-ui-sortable  /wp-includes/js/jquery/ui/jquery.ui.sortable.min.js
jquery-ui-tabs            /wp-includes/js/jquery/ui/jquery.ui.tabs.min.js
jquery-ui-widget        /wp-includes/js/jquery/ui/jquery.ui.widget.min.js
jquery-form               /wp-includes/js/jquery/jquery.form.js
jquery-color              /wp-includes/js/jquery/jquery.color.js
jquery-query              /wp-includes/js/jquery/jquery.query.js
jquery-serialize-object /wp-includes/js/jquery/jquery.serialize-object.js
jquery-hotkeys            /wp-includes/js/jquery/jquery.hotkeys.js
jquery-table-hotkeys    /wp-includes/js/jquery/jquery.table-hotkeys.js
jquery-masonry            /wp-includes/js/jquery/jquery.masonry.min.js
suggest                   /wp-includes/js/jquery/suggest.js
schedule                  /wp-includes/js/jquery/jquery.schedule.js
thickbox                  /wp-includes/js/thickbox/thickbox.js
jcrop                     /wp-includes/js/jcrop/jquery.Jcrop.js
swfobject                 /wp-includes/js/swfobject.js
plupload                  /wp-includes/js/plupload/plupload.js
plupload-html5            /wp-includes/js/plupload/plupload.html5.js
plupload-flash            /wp-includes/js/plupload/plupload.flash.js"
plupload-silverlight    /wp-includes/js/plupload/plupload.silverlight.js
plupload-html4            /wp-includes/js/plupload/plupload.html4.js
plupload-full   plupload, plupload-html5, plupload-flash, plupload-silverlight, plupload-html4
plupload-handlers         /wp-includes/js/plupload/handlers.js
swfupload                 /wp-includes/js/swfupload/swfupload.js
swfupload-swfobject /wp-includes/js/swfupload/plugins/swfupload.swfobject.js
swfupload-queue           /wp-includes/js/swfupload/plugins/swfupload.queue.js
swfupload-speed           /wp-includes/js/swfupload/plugins/swfupload.speed.js
swfupload-all             /wp-includes/js/swfupload/swfupload-all.js
swfupload-handlers  /wp-includes/js/swfupload/handlers.js
comment-reply             /wp-includes/js/comment-reply.js
json2                     /wp-includes/js/json2.js
imgareaselect             /wp-includes/js/imgareaselect/jquery.imgareaselect.js
password-strength-meter /wp-admin/js/password-strength-meter.js
user-profile              /wp-admin/js/user-profile.js
admin-bar                 /wp-includes/js/admin-bar.js
wplink                    /wp-includes/js/wplink.js
wpdialogs                 /wp-includes/js/tinymce/plugins/wpdialogs/js/wpdialog.js
wpdialogs-popup           /wp-includes/js/tinymce/plugins/wpdialogs/js/popup.js
word-count                /wp-admin/js/word-count.js
media-upload              /wp-admin/js/media-upload.js
*
*/
