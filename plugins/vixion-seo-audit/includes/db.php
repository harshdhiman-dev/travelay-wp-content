<?php
defined( 'ABSPATH' ) || exit;

// ── Table creation ────────────────────────────────────────────
function vx_seo_create_tables() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    if ( ! function_exists( 'dbDelta' ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vx_audits (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        site_url    VARCHAR(500) NOT NULL,
        score       TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
        pass        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        warn        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        fail        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        report_json LONGTEXT NOT NULL,
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset;" );

    dbDelta( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vx_support (
        id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        name        VARCHAR(100) NOT NULL,
        email       VARCHAR(200) NOT NULL,
        subject     VARCHAR(200) NOT NULL,
        message     TEXT NOT NULL,
        status      VARCHAR(20) NOT NULL DEFAULT 'open',
        created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset;" );
}

// ── Safety net: check table exists before every DB operation ──
// Uses a static flag so the SHOW TABLES query only runs once per request.
function vx_ensure_tables() {
    static $checked = false;
    if ( $checked ) return;
    $checked = true;
    global $wpdb;
    $table = $wpdb->prefix . 'vx_audits';
    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
        vx_seo_create_tables();
    }
}

// ── Data functions ────────────────────────────────────────────
function vx_db_save_audit( $report ) {
    global $wpdb;
    vx_ensure_tables();
    $result = $wpdb->insert( $wpdb->prefix . 'vx_audits', [
        'user_id'     => get_current_user_id(),
        'site_url'    => get_site_url(),
        'score'       => $report['scoring']['score'],
        'pass'        => $report['scoring']['pass'],
        'warn'        => $report['scoring']['warn'],
        'fail'        => $report['scoring']['fail'],
        'report_json' => wp_json_encode( $report ),
        'created_at'  => current_time( 'mysql' ),
    ] );
    return $result ? $wpdb->insert_id : 0;
}

function vx_db_get_history( $limit = 30 ) {
    global $wpdb;
    vx_ensure_tables();
    return $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vx_audits ORDER BY created_at DESC LIMIT %d", $limit
    ) );
}

function vx_db_get_last_report() {
    global $wpdb;
    vx_ensure_tables();
    $row = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}vx_audits ORDER BY created_at DESC LIMIT 1" );
    if ( ! $row ) return null;
    return json_decode( $row->report_json, true );
}

function vx_db_clear_history() {
    global $wpdb;
    $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}vx_audits" );
}

function vx_db_save_support( $data ) {
    global $wpdb;
    $wpdb->insert( $wpdb->prefix . 'vx_support', [
        'user_id'    => get_current_user_id(),
        'name'       => sanitize_text_field( $data['name'] ),
        'email'      => sanitize_email( $data['email'] ),
        'subject'    => sanitize_text_field( $data['subject'] ),
        'message'    => sanitize_textarea_field( $data['message'] ),
        'status'     => 'open',
        'created_at' => current_time( 'mysql' ),
    ] );
    return $wpdb->insert_id;
}

function vx_db_get_stats() {
    global $wpdb;
    vx_ensure_tables();
    $table = $wpdb->prefix . 'vx_audits';
    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table" );
    $avg   = $total ? (int) $wpdb->get_var( "SELECT ROUND(AVG(score)) FROM $table" ) : null;
    $last  = $wpdb->get_row( "SELECT * FROM $table ORDER BY created_at DESC LIMIT 1" );
    return compact( 'total', 'avg', 'last' );
}