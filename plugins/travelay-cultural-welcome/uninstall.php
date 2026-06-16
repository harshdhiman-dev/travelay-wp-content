<?php
/**
 * Uninstall handler — runs only when plugin is deleted from WordPress.
 *
 * @package TravelayCulturalWelcome
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Keep welcome profiles and visitor-facing data unless explicitly removed.
// Only delete plugin settings and cached voice catalog.
delete_option( 'tcw_settings' );
delete_option( 'tcw_voice_catalog_meta' );
delete_transient( 'tcw_tts_voice_catalog' );
