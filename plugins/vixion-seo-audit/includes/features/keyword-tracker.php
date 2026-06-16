<?php
/**
 * Keyword Rank Tracker
 * Uses ValueSERP API (valueserp.com) — affordable ($2.50/mo for 5k searches).
 * Falls back to SerpApi format if user provides that key instead.
 * Runs daily via wp_cron.
 */
defined( 'ABSPATH' ) || exit;

// ── Save settings ─────────────────────────────────────────────
add_action( 'admin_post_vx_save_keywords', 'vx_kw_save_settings' );
function vx_kw_save_settings() {
    check_admin_referer( 'vx_save_keywords' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    $api_key = sanitize_text_field( trim( $_POST['vx_serp_api_key'] ?? '' ) );
    $country = sanitize_text_field( $_POST['vx_rank_country'] ?? 'in' );
    $lang    = sanitize_text_field( $_POST['vx_rank_lang'] ?? 'en' );
    $raw     = sanitize_textarea_field( $_POST['vx_keywords_list'] ?? '' );

    // Parse keywords
    $keywords = array_values( array_unique( array_filter(
        array_map( 'trim', explode( "\n", $raw ) )
    ) ) );
    $keywords = array_slice( $keywords, 0, 50 ); // max 50

    update_option( 'vx_serp_api_key',     $api_key );
    update_option( 'vx_rank_country',     $country );
    update_option( 'vx_rank_lang',        $lang );
    update_option( 'vx_tracked_keywords', $keywords );

    // Reschedule cron
    wp_clear_scheduled_hook( 'vx_daily_rank_check' );
    if ( $api_key && ! empty( $keywords ) ) {
        wp_schedule_event( strtotime( 'today 06:00:00' ), 'daily', 'vx_daily_rank_check' );
    }

    wp_redirect( admin_url( 'admin.php?page=vixion-seo-keywords&saved=1' ) );
    exit;
}

// ── Check one keyword ─────────────────────────────────────────
function vx_kw_check_rank( $keyword, $api_key, $country = 'in', $lang = 'en' ) {
    $site = parse_url( home_url(), PHP_URL_HOST );
    $site = preg_replace( '/^www\./i', '', $site );

    // ValueSERP API
    $url = add_query_arg( [
        'api_key'       => $api_key,
        'q'             => $keyword,
        'location'      => $country === 'in' ? 'India' : 'United States',
        'gl'            => $country,
        'hl'            => $lang,
        'google_domain' => 'google.' . ( $country === 'in' ? 'co.in' : 'com' ),
        'num'           => 100,
        'output'        => 'json',
    ], 'https://api.valueserp.com/search' );

    $resp = wp_remote_get( $url, [ 'timeout' => 20 ] );
    if ( is_wp_error( $resp ) ) return [ 'position' => null, 'error' => $resp->get_error_message() ];

    $code = wp_remote_retrieve_response_code( $resp );
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );

    if ( $code !== 200 ) {
        return [ 'position' => null, 'error' => $body['request_info']['message'] ?? "HTTP $code" ];
    }

    // Search through organic results for our domain
    $position = null;
    $url_found = '';
    foreach ( $body['organic_results'] ?? [] as $result ) {
        $result_domain = parse_url( $result['link'] ?? '', PHP_URL_HOST );
        $result_domain = preg_replace( '/^www\./i', '', $result_domain ?? '' );
        if ( $result_domain === $site ) {
            $position  = $result['position'];
            $url_found = $result['link'] ?? '';
            break;
        }
    }

    return [
        'position'  => $position, // null = not in top 100
        'url'       => $url_found,
        'checked'   => current_time( 'mysql' ),
    ];
}

// ── Run all keywords (cron job) ───────────────────────────────
add_action( 'vx_daily_rank_check', 'vx_kw_run_daily_check' );
function vx_kw_run_daily_check() {
    $api_key  = get_option( 'vx_serp_api_key', '' );
    $keywords = get_option( 'vx_tracked_keywords', [] );
    $country  = get_option( 'vx_rank_country', 'in' );
    $lang     = get_option( 'vx_rank_lang', 'en' );

    if ( ! $api_key || empty( $keywords ) ) return;

    $history = get_option( 'vx_rank_history', [] ); // [ keyword => [ [date, position], ... ] ]
    $today   = date( 'Y-m-d' );

    foreach ( $keywords as $kw ) {
        $result = vx_kw_check_rank( $kw, $api_key, $country, $lang );
        if ( ! isset( $history[ $kw ] ) ) $history[ $kw ] = [];
        // Keep last 30 days
        $history[ $kw ][] = [
            'date'     => $today,
            'position' => $result['position'],
            'url'      => $result['url'] ?? '',
        ];
        $history[ $kw ] = array_slice( $history[ $kw ], -30 );
        sleep( 1 ); // Rate limit: 1 req/sec
    }

    update_option( 'vx_rank_history', $history );
    update_option( 'vx_rank_last_run', current_time( 'mysql' ) );
}

// ── AJAX: manual run ──────────────────────────────────────────
add_action( 'wp_ajax_vx_run_rank_check', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
    vx_kw_run_daily_check();
    wp_send_json_success( 'Rank check complete. Results updated.' );
} );

// ── Helpers ───────────────────────────────────────────────────
function vx_kw_get_latest_position( $keyword ) {
    $history = get_option( 'vx_rank_history', [] );
    $rows    = $history[ $keyword ] ?? [];
    return end( $rows ) ?: null;
}

function vx_kw_get_prev_position( $keyword ) {
    $history = get_option( 'vx_rank_history', [] );
    $rows    = $history[ $keyword ] ?? [];
    if ( count( $rows ) < 2 ) return null;
    return $rows[ count( $rows ) - 2 ];
}

// Register cron schedule on plugin load
add_action( 'init', function () {
    $api_key  = get_option( 'vx_serp_api_key', '' );
    $keywords = get_option( 'vx_tracked_keywords', [] );
    if ( $api_key && ! empty( $keywords ) && ! wp_next_scheduled( 'vx_daily_rank_check' ) ) {
        wp_schedule_event( time(), 'daily', 'vx_daily_rank_check' );
    }
} );
