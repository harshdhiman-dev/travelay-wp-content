<?php // phpcs:disable WordPress.Files.FileName.InvalidClassFileName
/**
 * Blocks Loader class file.
 *
 * This file is for custom theme blocks and block related actions and filters.
 *
 * @package DST
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Handles loading of Gutenberg blocks from a specified directory.
 */
class DS_Blocks_Loader {

	/**
	 * Set up blocks
	 *
	 * @return void
	 */
	public static function setup(): void {
		// Register all blocks from the gutenberg/blocks directory.
		add_action( 'init', array( __CLASS__, 'register_theme_blocks' ) );
	}

	/**
	 * Register all blocks from the gutenberg/blocks directory, recursively.
	 *
	 * @return void
	 */
	public static function register_theme_blocks(): void {

		// Bail early if block dist directory doesn't exist.
		if ( ! file_exists( DS_THEME_BLOCK_DIST_DIR ) ) {
			return;
		}

		$block_files = [];

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( DS_THEME_BLOCK_DIST_DIR, RecursiveDirectoryIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ( $iterator as $file ) {
			if ( 'block.json' === $file->getFilename() ) {
				$block_files[] = $file->getPath();
			}
		}

		// Bail if we don't have custom blocks.
		if ( empty( $block_files ) ) {
			return;
		}

		// Register all found blocks.
		foreach ( $block_files as $block_folder ) {
			register_block_type( $block_folder );
		}
	}
}

// Initialize.
DS_Blocks_Loader::setup();
