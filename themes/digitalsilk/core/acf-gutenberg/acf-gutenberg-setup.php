<?php
// phpcs:ignoreFile
if ( function_exists( 'acf_register_block' ) ) {
	/**
	 * Adds ACFCompose fields' classes
	 */
	require_once 'acf-fields-composer/acf-fields-setup.php';

	/**
	 * Setup Gutenberg in theme
	 * Filter standard Gutenberg blocks;
	 */
	require_once 'class-gutenberg-setup.php';

	/**
	 * Register ACF block_categories for Gutenberg editor
	 */
	require_once 'class-gutenberg-groups.php';

	/**
	 * Build image spinner module data-attributes
	 */
	require_once 'class-module-settings-image-spinner.php';

	/**
	 * Build slider module data-attributes
	 */
	require_once 'class-module-settings-slider.php';

	/**
	 * Load all default settings for module
	 */
	require_once 'class-module-settings-default.php';

	/**
	 * Load default fields values
	 */
	require_once 'class-default-field-values.php';

	require_once 'class-side-navigation.php';

	/**
	 * TODO temporary Block-v1 component settings. ajax testing
	 */
	require_once 'class-component-settings.php';

	/**
	 * Register ACF groups as Gutenberg blocks
	 */
	require_once 'abstract-acf-module.php';

	/**
	 * Adds option page with list of all custom ACF modules
	 */
	require_once 'class-allow-modules.php';

	/**
	 * Register ACF blocks.
	 */
	require_once 'acf-block-manager.php';

	/**
	 * Register rest api endpoint for the client lock.
	 */
	require_once 'class-clients-lock.php';
}
