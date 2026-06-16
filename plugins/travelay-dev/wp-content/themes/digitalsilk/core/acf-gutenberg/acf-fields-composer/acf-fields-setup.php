<?php
// phpcs:ignoreFile

if ( class_exists( 'ACFComposer\ACFComposer' ) ) {
	/**
	 * Adds common used fields types
	 */
	include_once 'class-field.php';

	/**
	 * Adds common used fields/groups
	 */
	$files_to_include = glob( get_template_directory() . '/core/acf-gutenberg/acf-fields-composer/[fields-]*/*.php' );
	foreach ( $files_to_include as $filename ) {
		include_once $filename;
	}
}
