<?php
/**
 * Weekly Reports
 * Sends an HTML email digest every Monday (or chosen day) via wp_mail.
 * Reads from vx_audits table — no external service needed.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_vx_save_reports', 'vx_wr_save_settings' );
function vx_wr_save_settings() {
    check_admin_referer( 'vx_save_reports' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    $email   = sanitize_email( $_POST['vx_report_email'] ?? get_bloginfo('admin_email') );
    $enabled = isset( $_POST['vx_report_enabled'] ) ? '1' : '0';
    $day     = sanitize_text_field( $_POST['vx_report_day'] ?? 'monday' );
    $cc_raw  = sanitize_textarea_field( $_POST['vx_report_cc'] ?? '' );
    $cc      = array_filter( array_map( 'sanitize_email', explode( "\n", $cc_raw ) ) );

    update_option( 'vx_report_email',   $email );
    update_option( 'vx_report_enabled', $enabled );
    update_option( 'vx_report_day',     $day );
    update_option( 'vx_report_cc',      implode( "\n", $cc ) );

    // Reschedule cron
    wp_clear_scheduled_hook( 'vx_weekly_report' );
    if ( $enabled === '1' ) {
        $next = strtotime( 'next ' . $day . ' 08:00:00' );
        wp_schedule_event( $next, 'weekly', 'vx_weekly_report' );
    }

    // If test button clicked
    if ( isset( $_POST['vx_send_test'] ) ) {
        $sent = vx_wr_send_report( true );
        $param = $sent ? 'test_sent=1' : 'test_failed=1';
        wp_redirect( admin_url( "admin.php?page=vixion-seo-reports&{$param}" ) );
        exit;
    }

    wp_redirect( admin_url( 'admin.php?page=vixion-seo-reports&saved=1' ) );
    exit;
}

// ── Build HTML email ──────────────────────────────────────────
function vx_wr_build_email() {
    global $wpdb;
    $table   = $wpdb->prefix . 'vx_audits';
    $history = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC LIMIT 5" );

    if ( empty( $history ) ) return null;

    $latest  = $history[0];
    $prev    = $history[1] ?? null;
    $score   = (int) $latest->score;
    $change  = $prev ? ( $score - (int) $prev->score ) : 0;
    $report  = json_decode( $latest->report_json, true );

    $color   = $score >= 80 ? '#16a34a' : ( $score >= 50 ? '#d97706' : '#dc2626' );
    $change_txt = $change > 0 ? "↑ +{$change}" : ( $change < 0 ? "↓ {$change}" : "→ no change" );
    $change_col = $change > 0 ? '#16a34a' : ( $change < 0 ? '#dc2626' : '#888' );

    // Top 3 fixes from failing checks
    $top_fixes = [];
    foreach ( $report['checks'] ?? [] as $check ) {
        if ( $check['status'] === 'fail' ) {
            $top_fixes[] = $check;
            if ( count( $top_fixes ) >= 3 ) break;
        }
    }

    // Score history rows
    $score_rows = '';
    foreach ( array_slice( $history, 0, 5 ) as $row ) {
        $s   = (int) $row->score;
        $c   = $s >= 80 ? '#16a34a' : ( $s >= 50 ? '#d97706' : '#dc2626' );
        $d   = date( 'M j', strtotime( $row->created_at ) );
        $bar = str_repeat( '█', (int)($s/10) ) . str_repeat( '░', 10 - (int)($s/10) );
        $score_rows .= "<tr>
            <td style='padding:6px 12px;font-size:13px;color:#555;'>$d</td>
            <td style='padding:6px 12px;font-family:monospace;font-size:12px;color:#999;'>$bar</td>
            <td style='padding:6px 12px;font-size:14px;font-weight:800;color:{$c};'>{$s}</td>
        </tr>";
    }

    // Top fixes HTML
    $fixes_html = '';
    foreach ( $top_fixes as $fix ) {
        $label = htmlspecialchars( $fix['label'] );
        $issue = htmlspecialchars( $fix['issue'] ?? '' );
        $fixes_html .= "<div style='border-left:3px solid #dc2626;padding:10px 16px;background:#fef2f2;border-radius:0 8px 8px 0;margin-bottom:8px;'>
            <strong style='font-size:13px;color:#0d0d0d;'>{$label}</strong>
            <p style='font-size:12px;color:#555;margin:4px 0 0;'>{$issue}</p>
        </div>";
    }
    if ( ! $fixes_html ) $fixes_html = '<p style="color:#16a34a;font-weight:700;">✓ No critical issues found!</p>';

    $site_name = get_bloginfo( 'name' );
    $site_url  = home_url();
    $dash_url  = admin_url( 'admin.php?page=vixion-seo-audit' );
    $date      = date( 'F j, Y' );

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<div style="max-width:600px;margin:32px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 20px rgba(0,0,0,.08);">

  <!-- Header -->
  <div style="background:#0d0d0d;padding:28px 32px;background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);background-size:24px 24px;">
    <p style="color:rgba(255,255,255,.4);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:2px;margin:0 0 8px;">Weekly SEO Digest</p>
    <h1 style="color:#fff;font-size:26px;font-weight:900;margin:0 0 4px;">{$site_name}</h1>
    <p style="color:rgba(255,255,255,.4);font-size:13px;margin:0;">{$date}</p>
  </div>

  <!-- Score -->
  <div style="padding:28px 32px;border-bottom:1px solid #f0f0f0;">
    <div style="display:flex;align-items:center;gap:20px;">
      <div style="text-align:center;">
        <div style="font-size:56px;font-weight:900;color:{$color};line-height:1;">{$score}</div>
        <div style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#aaa;">SEO Score</div>
      </div>
      <div>
        <div style="font-size:22px;font-weight:800;color:{$change_col};">{$change_txt} vs last week</div>
        <div style="font-size:13px;color:#888;margin-top:4px;">{$latest->pass} passing · {$latest->warn} warnings · {$latest->fail} failures</div>
        <a href="{$dash_url}" style="display:inline-block;margin-top:12px;padding:8px 18px;background:#0d0d0d;color:#fff;border-radius:50px;font-size:13px;font-weight:700;text-decoration:none;">View Full Report →</a>
      </div>
    </div>
  </div>

  <!-- Score history -->
  <div style="padding:24px 32px;border-bottom:1px solid #f0f0f0;">
    <h3 style="font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#aaa;margin:0 0 12px;">Score History</h3>
    <table style="width:100%;border-collapse:collapse;">{$score_rows}</table>
  </div>

  <!-- Top fixes -->
  <div style="padding:24px 32px;border-bottom:1px solid #f0f0f0;">
    <h3 style="font-size:14px;font-weight:800;text-transform:uppercase;letter-spacing:1px;color:#aaa;margin:0 0 12px;">Top Issues to Fix This Week</h3>
    {$fixes_html}
  </div>

  <!-- Footer -->
  <div style="padding:20px 32px;background:#fafafa;">
    <p style="font-size:12px;color:#bbb;margin:0;">
      Sent by <a href="https://vixion.in" style="color:#888;text-decoration:none;font-weight:700;">Vixion Health</a> ·
      <a href="{$dash_url}" style="color:#888;text-decoration:none;">Open Dashboard</a>
    </p>
  </div>

</div>
</body>
</html>
HTML;
}

// ── Send the report ───────────────────────────────────────────
function vx_wr_send_report( $is_test = false ) {
    $to      = get_option( 'vx_report_email', get_bloginfo( 'admin_email' ) );
    $cc_raw  = get_option( 'vx_report_cc', '' );
    $cc      = array_filter( array_map( 'trim', explode( "\n", $cc_raw ) ) );

    $html = vx_wr_build_email();
    if ( ! $html ) return false;

    $subject = ( $is_test ? '[Test] ' : '' ) . 'Weekly SEO Report — ' . get_bloginfo( 'name' );

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: Vixion Health <' . get_bloginfo( 'admin_email' ) . '>',
    ];
    foreach ( $cc as $cc_email ) {
        $headers[] = 'Cc: ' . $cc_email;
    }

    $sent = wp_mail( $to, $subject, $html, $headers );
    if ( $sent && ! $is_test ) {
        update_option( 'vx_report_last_sent', current_time( 'mysql' ) );
    }
    return $sent;
}

// ── Cron hook ─────────────────────────────────────────────────
add_action( 'vx_weekly_report', 'vx_wr_send_report' );

// ── Register cron on plugin load ─────────────────────────────
add_action( 'init', function () {
    if ( get_option( 'vx_report_enabled', '0' ) === '1' && ! wp_next_scheduled( 'vx_weekly_report' ) ) {
        $day  = get_option( 'vx_report_day', 'monday' );
        $next = strtotime( 'next ' . $day . ' 08:00:00' );
        wp_schedule_event( $next, 'weekly', 'vx_weekly_report' );
    }
} );

// ── AJAX: send test from page ─────────────────────────────────
add_action( 'wp_ajax_vx_send_test_report', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
    $sent = vx_wr_send_report( true );
    if ( $sent ) wp_send_json_success( 'Test report sent to ' . get_option( 'vx_report_email' ) );
    else wp_send_json_error( 'Email failed. Check your WordPress SMTP settings.' );
} );
