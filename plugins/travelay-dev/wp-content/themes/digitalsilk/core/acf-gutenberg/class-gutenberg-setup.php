<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Configure Gutenberg Theme functionality
 *
 * @package DST
 */

/**
 * Handles Gutenberg setup for the theme.
 */
class DS_GutenbergSetup {

	/**
	 * Constructor to set up Gutenberg related actions and filters.
	 */
	public function __construct() {
		// Disable gutenberg patterns.
		add_action( 'after_setup_theme', [ $this, 'remove_patterns' ], 99 );
		add_action( 'init', [ $this, 'remove_patterns_category' ], 99 );

		// Allows to use different modules for different templates.
		add_filter( 'allowed_block_types_all', [ $this, 'ds_allowed_block_types' ], 99, 2 );

		// Add support for patterns thumbnail.
		add_filter( 'register_wp_block_post_type_args', [ $this, 'allow_patterns_thumbnail' ] );

		// Enqueue block plugins.
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_plugins' ] );

		// Load sepparate block assets as they are needed, not all of the time.
		add_filter( 'should_load_separate_core_block_assets', '__return_true' );
	}

	/**
	 * Remove core block patterns.
	 *
	 * @return void
	 */
	public function remove_patterns() {
		remove_theme_support( 'core-block-patterns' );
	}

	/**
	 * Remove core block pattern categories.
	 *
	 * @return void
	 */
	public function remove_patterns_category() {
		unregister_block_pattern_category( 'buttons' );
		unregister_block_pattern_category( 'columns' );
		unregister_block_pattern_category( 'gallery' );
		unregister_block_pattern_category( 'header' );
		unregister_block_pattern_category( 'text' );
	}


	/**
	 * Allow patterns thumbnail in the block editor.
	 *
	 * @param array $args The arguments for the post type.
	 * @return array The modified arguments.
	 */
	public function allow_patterns_thumbnail( $args ) {
		if ( ! is_array( $args['supports'] ) ) {
			$args['supports'] = [];
		}
		$args['supports'][] = 'thumbnail';

		return $args;
	}

	/**
	 * Filter allowed block types based on the post type and template.
	 *
	 * @param array                   $allowed_block_types The allowed block types.
	 * @param WP_Block_Editor_Context $block_editor_context The block editor context.
	 *
	 * @return array The filtered allowed block types.
	 */
	public function ds_allowed_block_types( $allowed_block_types, $block_editor_context ) {
		$post = $block_editor_context->post;
		if ( empty( $post ) ) {
			return $allowed_block_types;
		}

		$allowed_blocks      = WP_Block_Type_Registry::get_instance()->get_all_registered();
		$allowed_blocks      = array_keys( $allowed_blocks );
		$allowed_blocks_list = [];

		if (
			( 'page' === $post->post_type && 'templates/template-simple-text.php' !== $post->page_template )
			|| in_array( $post->post_type, [ 'wp_block', 'module_styles', 'case_studies', 'megamenu' ], true )
		) {
			$allowed_prefixes = [
				'acf',
				'ds-blocks',
				'core/block',
				'flytravelay',
				'core/paragraph',
				'core/list',
				'core/heading',
				'core/quote',
				'core/table',
				'core/freeform',
				'core/html',
				'core/navigation',
				'woocommerce/checkout',
				'woocommerce/cart',
				'woocommerce/classic-shortcode',
				'gravityforms/form',
			];
			foreach ( $allowed_blocks as $value ) {
				if ( ds_strposa( $value, $allowed_prefixes ) === 0 ) {
					$allowed_blocks_list[] = $value;
				}
			}
		} else {
			foreach ( $allowed_blocks as $key => $value ) {
				if ( ds_strposa( $value, [ 'list', 'cta', 'ds-blocks' ] ) !== false ) {
					$allowed_blocks_list[] = $value;
				} elseif ( strpos( $value, 'acf' ) !== 0 ) {
					$allowed_blocks_list[] = $value;
				}
			}
		}

		return $allowed_blocks_list;
	}

	/**
	 * Enqueue block plugins.
	 */
	public function enqueue_block_plugins() {
		global $post;
		/**
		 * Add our high order components.
		 */
		// Gap Control High Order Component.
		$gaps_control_assets = get_modules_asset_info( 'hoc-components/gap-control' );
		if ( is_array( $gaps_control_assets ) && ! empty( $gaps_control_assets ) ) {
			wp_enqueue_script(
				'ds-gap-control',
				$gaps_control_assets['url'],
				$gaps_control_assets['dependencies'],
				$gaps_control_assets['version'],
				true
			);
		}
		// Container Control High Order Component.
		$container_control_assets = get_modules_asset_info( 'hoc-components/container-control' );
		if ( is_array( $container_control_assets ) && ! empty( $container_control_assets ) ) {
			wp_enqueue_script(
				'ds-container-control',
				$container_control_assets['url'],
				$container_control_assets['dependencies'],
				$container_control_assets['version'],
				true
			);
		}
		// Effects Control High Order Component.
		$effects_control_assets = get_modules_asset_info( 'hoc-components/effects-control' );
		if ( is_array( $effects_control_assets ) && ! empty( $effects_control_assets ) ) {
			wp_enqueue_script(
				'ds-effects-control',
				$effects_control_assets['url'],
				$effects_control_assets['dependencies'],
				$effects_control_assets['version'],
				true
			);
		}
		// Class List High Order Component.
		$class_list_control_assets = get_modules_asset_info( 'hoc-components/class-list-control' );
		if ( is_array( $class_list_control_assets ) && ! empty( $class_list_control_assets ) ) {
			wp_enqueue_script(
				'ds-class-list-control',
				$class_list_control_assets['url'],
				$class_list_control_assets['dependencies'],
				$class_list_control_assets['version'],
				true
			);
		}

		// Add some core blocks to the page post type as inner blocks.
		if ( 'page' === get_post_type() && 'templates/template-simple-text.php' !== $post->page_template ) {
			$block_inserter_assets = get_modules_asset_info( 'pages/block-inserter' );
			if ( is_array( $block_inserter_assets ) && ! empty( $block_inserter_assets ) ) {
				wp_enqueue_script(
					'block-inserter',
					$block_inserter_assets['url'],
					$block_inserter_assets['dependencies'],
					$block_inserter_assets['version'],
					true
				);
			}
		}
	}
}

new DS_GutenbergSetup();
