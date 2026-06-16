<?php
/**
 * Google Analytics 4 Integration
 * Uses the GA4 Data API (analyticsdata.googleapis.com) with a Service Account.
 * No OAuth redirect needed — just a JSON key file pasted into settings.
 */
defined( 'ABSPATH' ) || exit;

// ── Save settings ─────────────────────────────────────────────
add_action( 'admin_post_vx_save_ga', 'vx_ga_save_settings' );
function vx_ga_save_settings() {
    check_admin_referer( 'vx_save_ga' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    update_option( 'vx_ga_property_id', sanitize_text_field( $_POST['vx_ga_property_id'] ?? '' ) );
    // JSON key — store raw, trim whitespace
    $json = trim( stripslashes( $_POST['vx_ga_json_key'] ?? '' ) );
    update_option( 'vx_ga_json_key', $json );

    // Clear cached data so it re-fetches
    delete_transient( 'vx_ga_data_7d' );
    delete_transient( 'vx_ga_data_30d' );

    wp_redirect( admin_url( 'admin.php?page=vixion-seo-analytics&saved=1' ) );
    exit;
}

// ── Core API caller ───────────────────────────────────────────
function vx_ga_get_access_token() {
    $cached = get_transient( 'vx_ga_access_token' );
    if ( $cached ) return $cached;

    $json_key = get_option( 'vx_ga_json_key', '' );
    if ( ! $json_key ) return new WP_Error( 'no_key', 'No service account JSON key configured.' );

    $sa = json_decode( $json_key, true );
    if ( ! $sa || ! isset( $sa['private_key'], $sa['client_email'] ) ) {
        return new WP_Error( 'invalid_json', 'Invalid service account JSON. Make sure you pasted the full file contents.' );
    }

    // Build JWT for Google OAuth
    $header  = base64_encode( json_encode( [ 'alg' => 'RS256', 'typ' => 'JWT' ] ) );
    $now     = time();
    $payload = base64_encode( json_encode( [
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ] ) );

    $unsigned = $header . '.' . $payload;
    $signature = '';
    $key_res   = openssl_pkey_get_private( $sa['private_key'] );
    if ( ! $key_res ) return new WP_Error( 'openssl', 'Could not parse private key from JSON. Make sure OpenSSL is enabled on your server.' );
    openssl_sign( $unsigned, $signature, $key_res, 'sha256WithRSAEncryption' );
    $jwt = $unsigned . '.' . base64_encode( $signature );

    $resp = wp_remote_post( 'https://oauth2.googleapis.com/token', [
        'body'    => [ 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt ],
        'timeout' => 15,
    ] );

    if ( is_wp_error( $resp ) ) return $resp;
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );
    if ( empty( $body['access_token'] ) ) {
        return new WP_Error( 'token_error', 'Could not get access token: ' . ( $body['error_description'] ?? $body['error'] ?? 'Unknown error' ) );
    }

    $token = $body['access_token'];
    set_transient( 'vx_ga_access_token', $token, 3500 ); // cache 58 min
    return $token;
}

function vx_ga_fetch_report( $days = 30 ) {
    $cache_key = 'vx_ga_data_' . $days . 'd';
    $cached    = get_transient( $cache_key );
    if ( $cached ) return $cached;

    $property_id = get_option( 'vx_ga_property_id', '' );
    if ( ! $property_id ) return new WP_Error( 'no_property', 'No GA4 Property ID configured.' );

    $token = vx_ga_get_access_token();
    if ( is_wp_error( $token ) ) return $token;

    $start      = date( 'Y-m-d', strtotime( "-{$days} days" ) );
    $end        = date( 'Y-m-d' );
    $prev_end   = date( 'Y-m-d', strtotime( "-{$days} days -1 day" ) );
    $prev_start = date( 'Y-m-d', strtotime( "-" . ($days*2) . " days -1 day" ) );

    $body = [
        'dateRanges' => [
            [ 'startDate' => $start,      'endDate' => $end ],
            [ 'startDate' => $prev_start, 'endDate' => $prev_end ],
        ],
        'metrics'    => [
            [ 'name' => 'sessions' ],
            [ 'name' => 'totalUsers' ],
            [ 'name' => 'newUsers' ],
            [ 'name' => 'bounceRate' ],
            [ 'name' => 'averageSessionDuration' ],
            [ 'name' => 'screenPageViews' ],
        ],
        'dimensions' => [ [ 'name' => 'date' ] ],
        'orderBys'   => [ [ 'dimension' => [ 'dimensionName' => 'date' ] ] ],
    ];

    $resp = wp_remote_post(
        "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport",
        [
            'body'    => wp_json_encode( $body ),
            'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
            'timeout' => 20,
        ]
    );

    if ( is_wp_error( $resp ) ) return $resp;

    $code = wp_remote_retrieve_response_code( $resp );
    $data = json_decode( wp_remote_retrieve_body( $resp ), true );

    if ( $code !== 200 ) {
        $msg = $data['error']['message'] ?? "API error (HTTP $code)";
        return new WP_Error( 'api_error', $msg );
    }

    // Also fetch top pages
    $pages_body = [
        'dateRanges' => [ [ 'startDate' => $start, 'endDate' => $end ] ],
        'metrics'    => [ [ 'name' => 'sessions' ], [ 'name' => 'bounceRate' ] ],
        'dimensions' => [ [ 'name' => 'pageTitle' ], [ 'name' => 'pagePath' ] ],
        'orderBys'   => [ [ 'metric' => [ 'metricName' => 'sessions' ], 'desc' => true ] ],
        'limit'      => 10,
    ];
    $pages_resp = wp_remote_post(
        "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport",
        [
            'body'    => wp_json_encode( $pages_body ),
            'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
            'timeout' => 15,
        ]
    );
    $pages_data = is_wp_error( $pages_resp ) ? null : json_decode( wp_remote_retrieve_body( $pages_resp ), true );

    // Traffic sources
    $source_body = [
        'dateRanges' => [ [ 'startDate' => $start, 'endDate' => $end ] ],
        'metrics'    => [ [ 'name' => 'sessions' ] ],
        'dimensions' => [ [ 'name' => 'sessionDefaultChannelGrouping' ] ],
        'orderBys'   => [ [ 'metric' => [ 'metricName' => 'sessions' ], 'desc' => true ] ],
        'limit'      => 8,
    ];
    $source_resp = wp_remote_post(
        "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport",
        [
            'body'    => wp_json_encode( $source_body ),
            'headers' => [ 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ],
            'timeout' => 15,
        ]
    );
    $source_data = is_wp_error( $source_resp ) ? null : json_decode( wp_remote_retrieve_body( $source_resp ), true );

    $result = [
        'main'    => $data,
        'pages'   => $pages_data,
        'sources' => $source_data,
        'fetched' => time(),
    ];

    set_transient( $cache_key, $result, 6 * HOUR_IN_SECONDS );
    return $result;
}

// ── AJAX: refresh data ────────────────────────────────────────
add_action( 'wp_ajax_vx_refresh_ga', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
    delete_transient( 'vx_ga_data_7d' );
    delete_transient( 'vx_ga_data_30d' );
    delete_transient( 'vx_ga_access_token' );
    $data = vx_ga_fetch_report( 30 );
    if ( is_wp_error( $data ) ) wp_send_json_error( $data->get_error_message() );
    wp_send_json_success( 'Data refreshed.' );
} );

// ── Helper: parse totals from GA4 report rows ─────────────────
function vx_ga_sum_metric( $report, $metric_index ) {
    $total = 0;
    foreach ( $report['rows'] ?? [] as $row ) {
        $total += (float) ( $row['metricValues'][ $metric_index ]['value'] ?? 0 );
    }
    return $total;
}
