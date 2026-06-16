<?php
/**
 * Competitor Analysis
 * Runs lightweight HTTP checks on competitor URLs and compares to your last audit.
 * No external API needed — uses wp_remote_get just like the main audit.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_vx_save_competitors', 'vx_ca_save_settings' );
function vx_ca_save_settings() {
    check_admin_referer( 'vx_save_competitors' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    $raw   = sanitize_textarea_field( $_POST['vx_competitors_list'] ?? '' );
    $lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
    $urls  = [];
    foreach ( $lines as $line ) {
        $url = esc_url_raw( $line );
        if ( filter_var( $url, FILTER_VALIDATE_URL ) ) $urls[] = $url;
    }
    $urls = array_values( array_unique( array_slice( $urls, 0, 10 ) ) );
    update_option( 'vx_competitors', $urls );

    wp_redirect( admin_url( 'admin.php?page=vixion-seo-competitors&saved=1' ) );
    exit;
}

// ── Audit a single competitor URL ────────────────────────────
function vx_ca_audit_url( $url ) {
    $start = microtime( true );
    $resp  = wp_remote_get( $url, [
        'timeout'    => 20,
        'user-agent' => 'Mozilla/5.0 (compatible; VixionSEO/3.0)',
        'sslverify'  => false,
    ] );

    $ttfb = round( ( microtime( true ) - $start ) * 1000 );

    if ( is_wp_error( $resp ) ) {
        return [ 'url' => $url, 'error' => $resp->get_error_message() ];
    }

    $html        = wp_remote_retrieve_body( $resp );
    $status_code = (int) wp_remote_retrieve_response_code( $resp );

    // Parse everything the main audit checks
    preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $t );
    $title = isset( $t[1] ) ? trim( strip_tags( $t[1] ) ) : '';

    preg_match( '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)[\"\']?/i', $html, $d );
    if ( empty( $d[1] ) ) preg_match( '/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description/i', $html, $d );
    $desc = isset( $d[1] ) ? trim( $d[1] ) : '';

    preg_match_all( '/<h1[^>]*>/i', $html, $h1m );
    preg_match_all( '/<h2[^>]*>/i', $html, $h2m );

    $has_og        = (bool) preg_match( '/property=["\']og:/i', $html );
    $has_schema    = (bool) preg_match( '/application\/ld\+json/i', $html );
    $has_viewport  = (bool) preg_match( '/name=["\']viewport["\']/i', $html );
    $has_canonical = (bool) preg_match( '/rel=["\']canonical["\']/i', $html );
    $html_size_kb  = round( strlen( $html ) / 1024, 1 );
    $word_count    = str_word_count( strip_tags( preg_replace( '/<(script|style)[^>]*>.*?<\/(script|style)>/is', '', $html ) ) );

    $title_len = mb_strlen( $title );
    $desc_len  = mb_strlen( $desc );
    $h1_count  = count( $h1m[0] );

    // Sitemap check
    $parsed   = parse_url( $url );
    $base_url = ( $parsed['scheme'] ?? 'https' ) . '://' . ( $parsed['host'] ?? '' );
    $sitemap_resp = wp_remote_get( $base_url . '/sitemap.xml', [ 'timeout' => 5, 'sslverify' => false ] );
    $has_sitemap  = ! is_wp_error( $sitemap_resp ) && wp_remote_retrieve_response_code( $sitemap_resp ) === 200;

    // Calculate score same formula as main audit
    $checks = [
        'title_ok'      => $title_len >= 50 && $title_len <= 60,
        'desc_ok'       => $desc_len >= 120 && $desc_len <= 160,
        'h1_ok'         => $h1_count === 1,
        'canonical'     => $has_canonical,
        'og_tags'       => $has_og,
        'schema'        => $has_schema,
        'viewport'      => $has_viewport,
        'sitemap'       => $has_sitemap,
        'https'         => str_starts_with( $url, 'https://' ),
        'fast'          => $ttfb <= 600,
        'good_html_size'=> $html_size_kb <= 100,
        'good_content'  => $word_count >= 300,
    ];

    $pass  = count( array_filter( $checks ) );
    $total = count( $checks );
    $score = round( ( $pass / $total ) * 100 );

    return [
        'url'        => $url,
        'title'      => $title,
        'desc'       => $desc,
        'h1_count'   => $h1_count,
        'ttfb_ms'    => $ttfb,
        'html_size'  => $html_size_kb,
        'word_count' => $word_count,
        'checks'     => $checks,
        'score'      => $score,
        'pass'       => $pass,
        'total'      => $total,
        'fetched'    => current_time( 'mysql' ),
    ];
}

// ── AJAX: run comparison ──────────────────────────────────────
add_action( 'wp_ajax_vx_run_competitor_audit', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $competitors = get_option( 'vx_competitors', [] );
    if ( empty( $competitors ) ) wp_send_json_error( 'No competitors configured.' );

    $results = [];
    foreach ( $competitors as $url ) {
        $results[] = vx_ca_audit_url( $url );
    }

    // Cache results
    update_option( 'vx_competitor_results', $results );
    update_option( 'vx_competitor_last_run', current_time( 'mysql' ) );

    wp_send_json_success( $results );
} );
