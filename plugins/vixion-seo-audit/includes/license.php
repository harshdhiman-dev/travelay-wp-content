<?php
/**
 * License verification for Vixion Health
 * Calls vixion.in every 12 hours to confirm the license is valid.
 * Domain binding is enforced server-side — key sharing is blocked.
 *
 * File: vixion-seo-audit/includes/license.php
 */
defined( 'ABSPATH' ) || exit;

define( 'VX_LICENSE_SERVER', 'https://vixion.in/wp-json/vxl/v1' );
define( 'VX_LICENSE_CACHE',  'vx_license_status' );
define( 'VX_LICENSE_BACKUP', 'vx_license_last_good' );
define( 'VX_LICENSE_TTL',    12 * HOUR_IN_SECONDS );

// ── Hook: verify on every admin load ─────────────────────────
add_action( 'admin_init', 'vx_maybe_verify_license' );
function vx_maybe_verify_license() {
    // Only run if transient is expired (i.e. not cached)
    if ( get_transient( VX_LICENSE_CACHE ) !== false ) return;
    vx_verify_license_now();
}

// ── Core verify function ──────────────────────────────────────
function vx_verify_license_now() {
    $key    = get_option( 'vx_license_key', '' );
    $domain = vx_get_domain();

    // No key entered → free plan, no API call needed
    if ( ! $key ) {
        $status = [ 'valid' => false, 'plan' => 'free', 'reason' => 'no_key' ];
        set_transient( VX_LICENSE_CACHE, $status, VX_LICENSE_TTL );
        vx_apply_license_status( $status );
        return $status;
    }

    // Call vixion.in verify endpoint
    $response = wp_remote_post( VX_LICENSE_SERVER . '/verify', [
        'body'    => [
            'license_key' => $key,
            'domain'      => $domain,
        ],
        'timeout' => 10,
        'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
    ] );

    // Server unreachable → grace period: use last known good status
    if ( is_wp_error( $response ) ) {
        $fallback = get_option( VX_LICENSE_BACKUP, [ 'valid' => false, 'plan' => 'free', 'reason' => 'server_unreachable' ] );
        // Cache for 2 hours only during grace (retry sooner)
        set_transient( VX_LICENSE_CACHE, $fallback, 2 * HOUR_IN_SECONDS );
        return $fallback;
    }

    $body   = json_decode( wp_remote_retrieve_body( $response ), true );
    $status = [
        'valid'      => (bool) ( $body['valid'] ?? false ),
        'plan'       => sanitize_key( $body['plan'] ?? 'free' ),
        'reason'     => sanitize_text_field( $body['reason'] ?? '' ),
        'expires_at' => sanitize_text_field( $body['expires_at'] ?? '' ),
        'checked_at' => time(),
    ];

    // Cache result for 12 hours
    set_transient( VX_LICENSE_CACHE, $status, VX_LICENSE_TTL );

    // Save as backup (used during server downtime grace period)
    if ( $status['valid'] ) {
        update_option( VX_LICENSE_BACKUP, $status );
    }

    vx_apply_license_status( $status );
    return $status;
}

// ── Apply verified plan to wp_options ────────────────────────
// This is the ONLY place vx_plan gets written — always from server
function vx_apply_license_status( $status ) {
    $plan = $status['valid'] ? ( $status['plan'] ?? 'free' ) : 'free';
    // Only valid plans accepted
    if ( ! in_array( $plan, [ 'free', 'gold', 'platinum' ], true ) ) {
        $plan = 'free';
    }
    update_option( 'vx_plan', $plan );
}

// ── Get current status (from cache or fresh) ─────────────────
function vx_get_license_status() {
    $cached = get_transient( VX_LICENSE_CACHE );
    if ( $cached !== false ) return $cached;
    return vx_verify_license_now();
}

// ── Activate license (bind domain to key) ────────────────────
function vx_activate_license( $key ) {
    $domain   = vx_get_domain();
    $response = wp_remote_post( VX_LICENSE_SERVER . '/activate', [
        'body'    => [
            'license_key' => sanitize_text_field( $key ),
            'domain'      => $domain,
        ],
        'timeout' => 12,
        'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
    ] );

    if ( is_wp_error( $response ) ) {
        return [ 'success' => false, 'message' => 'Could not reach license server. Check your internet connection.' ];
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( ! empty( $body['success'] ) ) {
        // Save key and immediately re-verify
        update_option( 'vx_license_key', sanitize_text_field( $key ) );
        delete_transient( VX_LICENSE_CACHE );
        $status = vx_verify_license_now();
        return [
            'success' => true,
            'plan'    => $status['plan'] ?? 'free',
            'message' => 'License activated! Plan upgraded to ' . ucfirst( $status['plan'] ?? 'free' ) . '.',
        ];
    }

    // Activation failed
    $reason  = $body['reason'] ?? '';
    $message = match ( $reason ) {
        'domain_mismatch' => 'This license is already activated on another domain. Contact hello@vixion.in to transfer it.',
        'invalid_key'     => 'Invalid license key. Double-check and try again.',
        'inactive'        => 'This license has been revoked. Contact hello@vixion.in.',
        default           => $body['message'] ?? 'Activation failed. Contact hello@vixion.in.',
    };

    return [ 'success' => false, 'message' => $message ];
}

// ── Deactivate (clear key locally) ───────────────────────────
function vx_deactivate_license() {
    delete_option( 'vx_license_key' );
    delete_option( 'vx_plan' );
    delete_option( VX_LICENSE_BACKUP );
    delete_transient( VX_LICENSE_CACHE );
    update_option( 'vx_plan', 'free' );
}

// ── Admin notice when license invalid/expired ─────────────────
add_action( 'admin_notices', 'vx_license_admin_notice' );
function vx_license_admin_notice() {
    // Only on our plugin pages
    $screen = get_current_screen();
    if ( ! $screen ) return;
    $our_pages = [
        'toplevel_page_vixion-seo-audit',
        'seo-audit_page_vixion-seo-new-audit',
        'seo-audit_page_vixion-seo-report',
        'seo-audit_page_vixion-seo-history',
        'seo-audit_page_vixion-seo-subscription',
        'seo-audit_page_vixion-seo-support',
        'seo-audit_page_vixion-seo-settings',
    ];
    if ( ! in_array( $screen->id, $our_pages, true ) ) return;

    // Suppress default WP notices on our pages (enqueue.php already does this via CSS,
    // but we still want OUR notice to show)
    $key    = get_option( 'vx_license_key', '' );
    $status = vx_get_license_status();

    // No key → show upgrade prompt (soft, not alarming)
    if ( ! $key ) {
        // Suppress on subscription page to avoid duplication
        if ( str_contains( $screen->id, 'subscription' ) ) return;
        ?>
        <div class="notice notice-info" style="border-left-color:#d97706;display:flex;align-items:center;gap:12px;padding:12px 16px;">
            <span style="font-size:18px;">⭐</span>
            <div style="flex:1;">
                <strong style="font-size:13px;">Running on Free Plan — 2 audits/week.</strong>
                <span style="font-size:13px;color:#555;margin-left:6px;">Upgrade to Gold (5/wk) or Platinum (15/wk).</span>
            </div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vixion-seo-subscription' ) ); ?>"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#d97706;color:#fff;border-radius:50px;font-size:13px;font-weight:700;text-decoration:none;">
                Upgrade →
            </a>
        </div>
        <?php
        return;
    }

    // Key present but invalid
    if ( ! $status['valid'] ) {
        $reason_map = [
            'expired'         => 'Your Vixion Health license has <strong>expired</strong>.',
            'domain_mismatch' => 'License key is registered to a <strong>different domain</strong>.',
            'inactive'        => 'Your license has been <strong>revoked</strong>.',
            'invalid_key'     => 'License key is <strong>invalid</strong>.',
        ];
        $msg = $reason_map[ $status['reason'] ?? '' ] ?? 'License verification failed.';
        ?>
        <div class="notice notice-error" style="display:flex;align-items:center;gap:12px;padding:12px 16px;">
            <span style="font-size:18px;">🔑</span>
            <div style="flex:1;">
                <strong>Vixion Health:</strong> <?php echo $msg; ?>
                Running as <strong>Free plan</strong> until resolved.
            </div>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=vixion-seo-settings' ) ); ?>"
               style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#dc2626;color:#fff;border-radius:50px;font-size:13px;font-weight:700;text-decoration:none;">
                Fix License →
            </a>
        </div>
        <?php
    }
}

// ── Helper: clean domain ──────────────────────────────────────
function vx_get_domain() {
    $url = home_url();
    $host = parse_url( $url, PHP_URL_HOST );
    // Strip www. prefix so www.site.com and site.com match
    return preg_replace( '/^www\./i', '', $host ?? '' );
}

// ── AJAX: activate license from settings page ─────────────────
add_action( 'wp_ajax_vx_activate_license', 'vx_ajax_activate_license' );
function vx_ajax_activate_license() {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $key = sanitize_text_field( $_POST['license_key'] ?? '' );
    if ( ! $key ) wp_send_json_error( 'License key cannot be empty.' );

    $result = vx_activate_license( $key );

    if ( $result['success'] ) {
        wp_send_json_success( $result );
    } else {
        wp_send_json_error( $result['message'] );
    }
}

// ── AJAX: deactivate license ──────────────────────────────────
add_action( 'wp_ajax_vx_deactivate_license', 'vx_ajax_deactivate_license' );
function vx_ajax_deactivate_license() {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
    vx_deactivate_license();
    wp_send_json_success( [ 'message' => 'License deactivated. Running as Free plan.' ] );
}
