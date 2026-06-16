<?php
/**
 * Google Search Console Integration
 * Uses Google Search Console API v3 with a Service Account JSON key.
 * Same service account + same JSON key as GA4 (if granted access to both).
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_vx_save_sc', 'vx_sc_save_settings' );
function vx_sc_save_settings() {
    check_admin_referer( 'vx_save_sc' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    update_option( 'vx_sc_site_url', esc_url_raw( trim( $_POST['vx_sc_site_url'] ?? '' ) ) );
    $json = trim( stripslashes( $_POST['vx_sc_json_key'] ?? '' ) );
    update_option( 'vx_sc_json_key', $json );
    delete_transient( 'vx_sc_data' );
    delete_transient( 'vx_sc_keywords' );

    wp_redirect( admin_url( 'admin.php?page=vixion-seo-search-console&saved=1' ) );
    exit;
}

function vx_sc_get_access_token() {
    $cached = get_transient( 'vx_sc_access_token' );
    if ( $cached ) return $cached;

    $json_key = get_option( 'vx_sc_json_key', '' );
    // Fall back to GA key if same service account
    if ( ! $json_key ) $json_key = get_option( 'vx_ga_json_key', '' );
    if ( ! $json_key ) return new WP_Error( 'no_key', 'No service account JSON key configured.' );

    $sa = json_decode( $json_key, true );
    if ( ! $sa || ! isset( $sa['private_key'], $sa['client_email'] ) ) {
        return new WP_Error( 'invalid_json', 'Invalid JSON key.' );
    }

    $header  = base64_encode( json_encode( [ 'alg' => 'RS256', 'typ' => 'JWT' ] ) );
    $now     = time();
    $payload = base64_encode( json_encode( [
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ] ) );

    $unsigned = $header . '.' . $payload;
    $signature = '';
    $key_res   = openssl_pkey_get_private( $sa['private_key'] );
    if ( ! $key_res ) return new WP_Error( 'openssl', 'Could not parse private key.' );
    openssl_sign( $unsigned, $signature, $key_res, 'sha256WithRSAEncryption' );
    $jwt = $unsigned . '.' . base64_encode( $signature );

    $resp = wp_remote_post( 'https://oauth2.googleapis.com/token', [
        'body'    => [ 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt ],
        'timeout' => 15,
    ] );

    if ( is_wp_error( $resp ) ) return $resp;
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( empty( $body['access_token'] ) ) {
        return new WP_Error( 'token_error', 'Token error: ' . ( $body['error_description'] ?? 'Unknown' ) );
    }

    $token = $body['access_token'];
    set_transient( 'vx_sc_access_token', $token, 3500 );
    return $token;
}

function vx_sc_fetch_data() {
    $cached = get_transient( 'vx_sc_data' );
    if ( $cached ) return $cached;

    $site_url = get_option( 'vx_sc_site_url', home_url() );
    if ( ! $site_url ) return new WP_Error( 'no_url', 'No site URL configured.' );

    $token = vx_sc_get_access_token();
    if ( is_wp_error( $token ) ) return $token;

    $end   = date( 'Y-m-d', strtotime( '-3 days' ) ); // GSC has ~3 day delay
    $start = date( 'Y-m-d', strtotime( '-31 days' ) );

    $encoded_url = rawurlencode( $site_url );

    // Summary (clicks, impressions, ctr, position)
    $summary_resp = wp_remote_post(
        "https://www.googleapis.com/webmasters/v3/sites/{$encoded_url}/searchAnalytics/query",
        [
            'body'    => wp_json_encode( [
                'startDate'  => $start,
                'endDate'    => $end,
                'dimensions' => [ 'date' ],
                'rowLimit'   => 31,
            ] ),
            'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
            'timeout' => 20,
        ]
    );

    if ( is_wp_error( $summary_resp ) ) return $summary_resp;
    $code = wp_remote_retrieve_response_code( $summary_resp );
    $summary = json_decode( wp_remote_retrieve_body( $summary_resp ), true );
    if ( $code !== 200 ) {
        return new WP_Error( 'api_error', $summary['error']['message'] ?? "API error HTTP $code" );
    }

    // Top queries
    $queries_resp = wp_remote_post(
        "https://www.googleapis.com/webmasters/v3/sites/{$encoded_url}/searchAnalytics/query",
        [
            'body'    => wp_json_encode( [
                'startDate'  => $start,
                'endDate'    => $end,
                'dimensions' => [ 'query' ],
                'rowLimit'   => 20,
            ] ),
            'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
            'timeout' => 15,
        ]
    );
    $queries = is_wp_error( $queries_resp ) ? null : json_decode( wp_remote_retrieve_body( $queries_resp ), true );

    // Top pages
    $pages_resp = wp_remote_post(
        "https://www.googleapis.com/webmasters/v3/sites/{$encoded_url}/searchAnalytics/query",
        [
            'body'    => wp_json_encode( [
                'startDate'  => $start,
                'endDate'    => $end,
                'dimensions' => [ 'page' ],
                'rowLimit'   => 10,
            ] ),
            'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
            'timeout' => 15,
        ]
    );
    $pages = is_wp_error( $pages_resp ) ? null : json_decode( wp_remote_retrieve_body( $pages_resp ), true );

    $result = [
        'summary' => $summary,
        'queries' => $queries,
        'pages'   => $pages,
        'period'  => [ 'start' => $start, 'end' => $end ],
        'fetched' => time(),
    ];

    set_transient( 'vx_sc_data', $result, 6 * HOUR_IN_SECONDS );
    return $result;
}

add_action( 'wp_ajax_vx_refresh_sc', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
    delete_transient( 'vx_sc_data' );
    delete_transient( 'vx_sc_access_token' );
    $data = vx_sc_fetch_data();
    if ( is_wp_error( $data ) ) wp_send_json_error( $data->get_error_message() );
    wp_send_json_success( 'Search Console data refreshed.' );
} );
