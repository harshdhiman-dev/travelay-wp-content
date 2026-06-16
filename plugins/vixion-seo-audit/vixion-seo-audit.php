<?php
/**
 * Plugin Name:     Vixion Health
 * Plugin URI:      https://vixion.in/vixion-seo-audit/
 * Description:     Accurate internal SEO audit for WordPress websites — with GA4, Search Console, Keyword Tracker, Competitor Analysis, AI Briefs & Weekly Reports.
 * Version:         4.0.0
 * Author:          Harsh – Vixion
 * Author URI:      https://vixion.in
 * License:         Proprietary
 * Requires at least: 5.8
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

define('VX_SEO_VERSION', '4.0.0');
define('VX_SEO_DIR',     plugin_dir_path(__FILE__));
define('VX_SEO_URL',     plugin_dir_url(__FILE__));
define('VX_SEO_BASENAME', plugin_basename(__FILE__));

// Core
require_once VX_SEO_DIR . 'includes/db.php';
require_once VX_SEO_DIR . 'includes/helpers.php';
require_once VX_SEO_DIR . 'includes/license.php';
require_once VX_SEO_DIR . 'includes/admin-menu.php';
require_once VX_SEO_DIR . 'includes/enqueue.php';
require_once VX_SEO_DIR . 'includes/ajax.php';

// Feature modules
require_once VX_SEO_DIR . 'includes/features/google-analytics.php';
require_once VX_SEO_DIR . 'includes/features/search-console.php';
require_once VX_SEO_DIR . 'includes/features/keyword-tracker.php';
require_once VX_SEO_DIR . 'includes/features/competitor-analysis.php';
require_once VX_SEO_DIR . 'includes/features/ai-briefs.php';
require_once VX_SEO_DIR . 'includes/features/weekly-reports.php';

register_activation_hook(__FILE__, function () {
    vx_seo_create_tables();
    update_option('vx_seo_version', VX_SEO_VERSION);
    update_option('vx_seo_installed', time());
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('vx_daily_rank_check');
    wp_clear_scheduled_hook('vx_weekly_report');
});

add_filter('plugin_action_links_' . VX_SEO_BASENAME, function ($links) {
    array_unshift($links, '<a href="' . admin_url('admin.php?page=vixion-seo-audit') . '"><strong>Open Tool</strong></a>');
    return $links;
});

add_action('admin_init', function () {
    if (get_option('vx_seo_version') !== VX_SEO_VERSION) {
        vx_seo_create_tables();
        update_option('vx_seo_version', VX_SEO_VERSION);
    }
});
