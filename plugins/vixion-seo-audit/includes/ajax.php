<?php
defined( 'ABSPATH' ) || exit;

// Run audit on current site
add_action( 'wp_ajax_vx_run_audit', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );

    // ── RATE LIMIT: plan-aware weekly limit ──────────────────
    $audit_log   = get_option( 'vx_audit_log', [] );
    $cooldown    = 7 * DAY_IN_SECONDS;
    $now         = time();
    $bonus       = (int) get_option( 'vx_bonus_audits', 0 );
    $plan        = get_option( 'vx_plan', 'free' );
    $plan_limits = [ 'free' => 2, 'gold' => 5, 'platinum' => 15 ];
    $base_limit  = $plan_limits[ $plan ] ?? 2;
    $limit       = $base_limit + $bonus;

    $audit_log = array_values( array_filter( $audit_log, fn( $ts ) => ( $now - $ts ) < $cooldown ) );

    if ( count( $audit_log ) >= $limit ) {
        $oldest    = min( $audit_log );
        $next_ts   = $oldest + $cooldown;
        $remaining = $next_ts - $now;
        $days      = floor( $remaining / DAY_IN_SECONDS );
        $hours     = floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
        $minutes   = floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );

        wp_send_json_error( [
            'code'           => 'rate_limited',
            'message'        => 'Weekly audit limit reached.',
            'used'           => count( $audit_log ),
            'limit'          => $limit,
            'plan'           => $plan,
            'next_audit_ts'  => $next_ts,
            'remaining_sec'  => $remaining,
            'remaining_text' => sprintf(
                '%d day%s, %d hour%s, %d minute%s',
                $days,    $days    !== 1 ? 's' : '',
                $hours,   $hours   !== 1 ? 's' : '',
                $minutes, $minutes !== 1 ? 's' : ''
            ),
        ] );
    }
    // ── END RATE LIMIT ───────────────────────────────────────

    // upgrade.php MUST be loaded for dbDelta to work in AJAX context
    if ( ! function_exists( 'dbDelta' ) ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    }
    vx_seo_create_tables();

    // Run the audit
    $report = vx_run_site_audit();

    if ( ! $report || ! isset( $report['scoring'] ) ) {
        wp_send_json_error( [
            'code'    => 'audit_failed',
            'message' => 'Audit did not return a valid report. Check your PHP error log.',
        ] );
    }

    // Save to database and verify it worked
    global $wpdb;
    $saved = vx_db_save_audit( $report );
    if ( ! $saved ) {
        wp_send_json_error( [
            'code'    => 'db_save_failed',
            'message' => 'Audit ran but failed to save to database. DB error: ' . $wpdb->last_error,
        ] );
    }

    update_option( 'vx_last_report', $report );

    // Log audit timestamp
    $audit_log[] = $now;
    update_option( 'vx_audit_log', $audit_log );

    // Consume bonus audit if used beyond base limit
    if ( count( $audit_log ) > $base_limit && $bonus > 0 ) {
        update_option( 'vx_bonus_audits', $bonus - 1 );
    }

    wp_send_json_success( $report );
} );

// Clear history
add_action( 'wp_ajax_vx_clear_history', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
    vx_db_clear_history();
    delete_option( 'vx_last_report' );
    delete_option( 'vx_audit_log' );
    wp_send_json_success();
} );

// Support ticket
add_action( 'wp_ajax_vx_submit_support', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );

    $name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
    $email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
    $subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
    $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

    if ( ! $name || ! $email || ! $subject || ! $message ) {
        wp_send_json_error( 'All fields are required.' );
    }

    $id = vx_db_save_support( compact( 'name', 'email', 'subject', 'message' ) );

    wp_mail( 'hello@vixion.in',
        '[Vixion Plugin Support] ' . $subject,
        "From: $name <$email>\n\n$message",
        [ 'Reply-To: ' . $name . ' <' . $email . '>' ]
    );

    wp_send_json_success( [ 'ticket_id' => $id ] );
} );