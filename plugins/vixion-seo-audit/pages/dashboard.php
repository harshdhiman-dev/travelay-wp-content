<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

// ── Audit history stats ──────────────────────────────────────
$stats   = vx_db_get_stats();
$total   = $stats['total'];
$avg     = $stats['avg'];
$last    = $stats['last'];
$site    = get_site_url();
$domain  = parse_url( $site, PHP_URL_HOST );
$history = vx_db_get_history( 20 );

// ── Quota & plan ─────────────────────────────────────────────
$audit_log   = get_option( 'vx_audit_log', [] );
$cooldown    = 7 * DAY_IN_SECONDS;
$now         = time();
$audit_log   = array_values( array_filter( $audit_log, fn( $ts ) => ( $now - $ts ) < $cooldown ) );
$used        = count( $audit_log );
$plan        = get_option( 'vx_plan', 'free' );
$plan_limits = [ 'free' => 2, 'gold' => 5, 'platinum' => 15 ];
$base_limit  = $plan_limits[ $plan ] ?? 2;
$bonus       = (int) get_option( 'vx_bonus_audits', 0 );
$limit       = $base_limit + $bonus;
$remaining   = max( 0, $limit - $used );
$plan_labels = [ 'free' => 'Free', 'gold' => '⭐ Gold', 'platinum' => '💎 Platinum' ];
$plan_label  = $plan_labels[ $plan ] ?? 'Free';

// ── Live site checks ─────────────────────────────────────────
$is_https     = str_starts_with( $site, 'https://' );
$blog_public  = (bool) get_option( 'blog_public' );
$wp_version   = get_bloginfo( 'version' );
$wp_ok        = version_compare( $wp_version, '6.0', '>=' );
$theme_name   = wp_get_theme()->get( 'Name' );
$plugin_count = count( get_option( 'active_plugins', [] ) );
$permalink_ok = ! empty( get_option( 'permalink_structure' ) );
global $wpdb;
$total_posts  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post'" );
$total_pages  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='page'" );
$total_images = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'" );
$draft_count  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='draft' AND post_type IN ('post','page')" );
$sitemap_ok   = false;
$sr = wp_remote_get( $site . '/sitemap.xml', [ 'timeout' => 4, 'sslverify' => false ] );
if ( ! is_wp_error( $sr ) && wp_remote_retrieve_response_code( $sr ) === 200 ) $sitemap_ok = true;

// ── Health score hero ─────────────────────────────────────────
// Use last audit score if available, otherwise derive from live checks
$hero_score   = null;
$hero_fail    = null;
$hero_warn    = null;
$hero_pass    = null;
$prev_score   = null;
$trend        = null; // 'up', 'down', 'same', null

if ( $last ) {
    $hero_score = (int) $last->score;
    $hero_fail  = (int) $last->fail;
    $hero_warn  = (int) $last->warn;
    $hero_pass  = (int) $last->pass;
    // find previous audit for trend
    if ( count( $history ) >= 2 ) {
        $prev_score = (int) $history[1]->score;
        $diff = $hero_score - $prev_score;
        $trend = $diff > 0 ? 'up' : ( $diff < 0 ? 'down' : 'same' );
    }
}

// Score ring colour
function vx_dash_ring_color( $score ) {
    if ( $score >= 75 ) return '#22c55e';
    if ( $score >= 50 ) return '#f59e0b';
    return '#ef4444';
}
function vx_dash_score_word( $score ) {
    if ( $score >= 75 ) return 'Healthy';
    if ( $score >= 50 ) return 'Needs Work';
    return 'Critical';
}
function vx_dash_score_emoji( $score ) {
    if ( $score >= 75 ) return '🟢';
    if ( $score >= 50 ) return '🟡';
    return '🔴';
}
?>

<!-- ══════════════════════════════════
     PAGE HEADER
════════════════════════════════════ -->
<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title">Dashboard</h1>
    <p class="vx-page-sub">
      Live SEO overview for <strong><?php echo esc_html( $domain ); ?></strong>
      &nbsp;·&nbsp; <?php echo esc_html( $plan_label ); ?> plan
    </p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <span class="vx-quota-pill <?php echo $remaining === 0 ? 'locked' : ''; ?>">
      <span class="vx-quota-dot <?php echo $remaining > 0 ? 'open' : ''; ?>"></span>
      <?php echo esc_html( $remaining ); ?> audit<?php echo $remaining !== 1 ? 's' : ''; ?> left this week
    </span>
    <?php if ( $remaining > 0 ) : ?>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=vixion-seo-new-audit' ) ); ?>" class="vx-btn-primary">
        <span class="dashicons dashicons-search"></span> Run New Audit
      </a>
    <?php else : ?>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=vixion-seo-subscription' ) ); ?>" class="vx-btn-primary">🔒 Get More Audits</a>
    <?php endif; ?>
  </div>
</div>

<!-- ══════════════════════════════════
     HERO: BIG HEALTH SCORE
════════════════════════════════════ -->
<?php if ( $hero_score !== null ) :
  $ring_color  = vx_dash_ring_color( $hero_score );
  $score_word  = vx_dash_score_word( $hero_score );
  $score_emoji = vx_dash_score_emoji( $hero_score );
  $circumference = 2 * M_PI * 54; // r=54
  $dash_offset   = $circumference * ( 1 - $hero_score / 100 );
?>
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:28px 32px;box-shadow:var(--sh);display:grid;grid-template-columns:auto 1fr auto;gap:32px;align-items:center;">

  <!-- Ring -->
  <div style="position:relative;width:130px;height:130px;flex-shrink:0;">
    <svg width="130" height="130" style="transform:rotate(-90deg);">
      <circle cx="65" cy="65" r="54" fill="none" stroke="var(--border)" stroke-width="10"/>
      <circle cx="65" cy="65" r="54" fill="none"
              stroke="<?php echo $ring_color; ?>" stroke-width="10"
              stroke-dasharray="<?php echo $circumference; ?>"
              stroke-dashoffset="<?php echo $dash_offset; ?>"
              stroke-linecap="round"
              style="transition:stroke-dashoffset 1s ease;"/>
    </svg>
    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0;">
      <strong style="font-family:'DM Sans',sans-serif;font-size:38px;line-height:1;color:var(--ink);"><?php echo $hero_score; ?></strong>
      <span style="font-size:11px;color:var(--muted);font-weight:600;">/&nbsp;100</span>
    </div>
  </div>

  <!-- Headline + Priority breakdown -->
  <div style="display:flex;flex-direction:column;gap:14px;">
    <div>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:5px;">
        <span style="font-size:16px;"><?php echo $score_emoji; ?></span>
        <h2 style="font-family:'DM Sans',sans-serif;font-size:26px;font-weight:800;font-style:normal;margin:0;line-height:1;color:var(--ink);">Website Health: <?php echo $score_word; ?></h2>
        <?php if ( $trend ) :
          $t_color = $trend==='up'?'var(--green)':($trend==='down'?'var(--red)':'var(--muted)');
          $t_arrow = $trend==='up'?'↑':($trend==='down'?'↓':'→');
          $t_label = $trend==='up'?'Improving':($trend==='down'?'Declining':'Stable');
        ?>
          <span style="display:inline-flex;align-items:center;gap:4px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--r-pill);padding:4px 12px;font-size:12px;font-weight:700;color:<?php echo $t_color; ?>;">
            <?php echo $t_arrow; ?> <?php echo $t_label; ?>
            <?php if ($prev_score): ?>
              <span style="color:var(--muted);font-weight:500;">(was&nbsp;<?php echo $prev_score; ?>)</span>
            <?php endif; ?>
          </span>
        <?php endif; ?>
      </div>
      <?php if ( $hero_fail > 0 ) : ?>
        <p style="margin:0;font-size:13.5px;color:var(--ink3);line-height:1.5;">
          <?php echo $hero_fail; ?> critical issue<?php echo $hero_fail!==1?'s':''; ?> found that need immediate attention.
          <?php if ($hero_warn>0) echo 'Another '.esc_html($hero_warn).' item'.($hero_warn!==1?'s':'').' need improvement.'; ?>
        </p>
      <?php else : ?>
        <p style="margin:0;font-size:13.5px;color:var(--green);font-weight:600;">No critical issues found — your site is in good shape.</p>
      <?php endif; ?>
    </div>

    <!-- Priority pills -->
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
      <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-report&filter=fail')); ?>"
         style="display:inline-flex;align-items:center;gap:6px;background:var(--red-l);border:1.5px solid var(--red-b);color:var(--red);border-radius:var(--r-pill);padding:7px 14px;font-size:13px;font-weight:700;text-decoration:none;transition:opacity .15s;"
         onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
        <span style="font-size:16px;">🔴</span> <?php echo $hero_fail; ?> Urgent
      </a>
      <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-report&filter=warn')); ?>"
         style="display:inline-flex;align-items:center;gap:6px;background:var(--amber-l);border:1.5px solid var(--amber-b);color:var(--amber);border-radius:var(--r-pill);padding:7px 14px;font-size:13px;font-weight:700;text-decoration:none;transition:opacity .15s;"
         onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
        <span style="font-size:16px;">🟡</span> <?php echo $hero_warn; ?> Needs Improvement
      </a>
      <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-report&filter=pass')); ?>"
         style="display:inline-flex;align-items:center;gap:6px;background:var(--green-l);border:1.5px solid var(--green-b);color:var(--green);border-radius:var(--r-pill);padding:7px 14px;font-size:13px;font-weight:700;text-decoration:none;transition:opacity .15s;"
         onmouseover="this.style.opacity='.75'" onmouseout="this.style.opacity='1'">
        <span style="font-size:16px;">🟢</span> <?php echo $hero_pass; ?> Good
      </a>
      <span style="color:var(--border2);font-size:18px;">|</span>
      <span style="font-size:12px;color:var(--muted);">Last audit: <?php echo esc_html($last->created_at); ?></span>
    </div>
  </div>

  <!-- CTA buttons -->
  <div style="display:flex;flex-direction:column;gap:10px;flex-shrink:0;">
    <?php if ($hero_fail > 0): ?>
      <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-report')); ?>" class="vx-btn-primary vx-btn-large">
        Fix <?php echo $hero_fail; ?> Issue<?php echo $hero_fail!==1?'s':''; ?> →
      </a>
    <?php endif; ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-report')); ?>" class="vx-btn-outline">
      View Full Report
    </a>
    <?php if ($remaining > 0): ?>
    <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-new-audit')); ?>" class="vx-btn-outline" style="font-size:12px;">
      ↻ Re-run Audit
    </a>
    <?php endif; ?>
  </div>

</div>

<?php else : // No audit yet — invite to run first one ?>

<div style="background:var(--ink);border-radius:var(--r-lg);padding:36px 40px;box-shadow:var(--sh-lg);position:relative;overflow:hidden;display:flex;align-items:center;gap:32px;">
  <div style="position:absolute;inset:0;background:radial-gradient(circle at 85% 50%,rgba(255,255,255,.05),transparent 55%);pointer-events:none;"></div>
  <!-- Empty ring -->
  <div style="position:relative;width:120px;height:120px;flex-shrink:0;">
    <svg width="120" height="120" style="transform:rotate(-90deg);">
      <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="9" stroke-dasharray="5 4"/>
    </svg>
    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
      <span style="font-size:28px;">?</span>
      <span style="font-size:10px;color:rgba(255,255,255,.38);font-weight:700;letter-spacing:.05em;">SCORE</span>
    </div>
  </div>
  <div style="flex:1;position:relative;">
    <h2 style="font-family:'DM Sans',sans-serif;font-size:28px;font-weight:800;font-style:normal;color:#fff;margin:0 0 8px;line-height:1.1;">Your health score is waiting.</h2>
    <p style="font-size:14px;color:rgba(255,255,255,.5);margin:0 0 20px;line-height:1.6;">Run your first audit to get a score out of 100, see exactly what's hurting your SEO, and get a step-by-step fix list.</p>
    <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-new-audit')); ?>" class="vx-btn-primary vx-btn-large">Run Free Audit Now →</a>
  </div>
</div>

<?php endif; ?>


<!-- ══════════════════════════════════
     LIVE SITE INFO
════════════════════════════════════ -->
<p class="vx-live-label">Live site info — pulled from your WordPress database right now</p>
<div class="vx-live-strip">

  <div class="vx-lc">
    <span class="vx-lc-icon">🔒</span>
    <span class="vx-lc-val"><?php echo $is_https ? 'HTTPS' : 'HTTP'; ?></span>
    <span class="vx-lc-label">Security</span>
    <span class="vx-lc-sub <?php echo $is_https ? 'vx-lc-ok' : 'vx-lc-bad'; ?>"><?php echo $is_https ? '✓ Secure' : '✕ Not Secure'; ?></span>
  </div>

  <div class="vx-lc">
    <span class="vx-lc-icon">🌐</span>
    <span class="vx-lc-val"><?php echo $blog_public ? 'Public' : 'Hidden'; ?></span>
    <span class="vx-lc-label">Visibility</span>
    <span class="vx-lc-sub <?php echo $blog_public ? 'vx-lc-ok' : 'vx-lc-bad'; ?>"><?php echo $blog_public ? '✓ Indexed' : '✕ Blocked'; ?></span>
  </div>

  <div class="vx-lc">
    <span class="vx-lc-icon">📄</span>
    <span class="vx-lc-val"><?php echo esc_html($total_posts); ?></span>
    <span class="vx-lc-label">Posts</span>
    <span class="vx-lc-sub vx-lc-muted"><?php echo esc_html($total_pages); ?> pages</span>
  </div>

  <div class="vx-lc">
    <span class="vx-lc-icon">🖼️</span>
    <span class="vx-lc-val"><?php echo esc_html($total_images); ?></span>
    <span class="vx-lc-label">Images</span>
    <span class="vx-lc-sub <?php echo $draft_count>0?'vx-lc-warn':'vx-lc-muted'; ?>"><?php echo esc_html($draft_count); ?> draft<?php echo $draft_count!==1?'s':''; ?></span>
  </div>

  <div class="vx-lc">
    <span class="vx-lc-icon">🧩</span>
    <span class="vx-lc-val"><?php echo esc_html($plugin_count); ?></span>
    <span class="vx-lc-label">Plugins Active</span>
    <span class="vx-lc-sub vx-lc-muted"><?php echo esc_html($theme_name); ?></span>
  </div>

  <div class="vx-lc">
    <span class="vx-lc-icon">⚙️</span>
    <span class="vx-lc-val">WP <?php echo esc_html($wp_version); ?></span>
    <span class="vx-lc-label">WordPress</span>
    <span class="vx-lc-sub <?php echo $wp_ok?'vx-lc-ok':'vx-lc-warn'; ?>"><?php echo $wp_ok?'✓ Up to date':'⚠ Update available'; ?></span>
  </div>

  <div class="vx-lc">
    <span class="vx-lc-icon">🗺️</span>
    <span class="vx-lc-val"><?php echo $sitemap_ok?'Found':'Missing'; ?></span>
    <span class="vx-lc-label">XML Sitemap</span>
    <span class="vx-lc-sub <?php echo $sitemap_ok?'vx-lc-ok':'vx-lc-bad'; ?>"><?php echo $sitemap_ok?'✓ /sitemap.xml':'✕ Not found'; ?></span>
  </div>

  <div class="vx-lc">
    <span class="vx-lc-icon">🔗</span>
    <span class="vx-lc-val"><?php echo $permalink_ok?'Clean':'Ugly'; ?></span>
    <span class="vx-lc-label">Permalinks</span>
    <span class="vx-lc-sub <?php echo $permalink_ok?'vx-lc-ok':'vx-lc-bad'; ?>"><?php echo $permalink_ok?'✓ SEO friendly':'✕ Change needed'; ?></span>
  </div>

</div>


<!-- ══════════════════════════════════
     MAIN GRID
════════════════════════════════════ -->
<div class="vx-dash-grid">

  <!-- Run audit CTA / locked state -->
  <?php if ( $remaining > 0 ) : ?>
  <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-new-audit')); ?>" class="vx-hero-card">
    <div class="vx-hero-icon"><span class="dashicons dashicons-search"></span></div>
    <h2>Run a New<?php echo $hero_score?' Full':' First'; ?> Audit</h2>
    <p>Scan <strong><?php echo esc_html($domain); ?></strong> across 18 real checks — technical SEO, content quality, speed, security, and search visibility.</p>
    <span class="vx-hero-cta">Start Audit → (<?php echo esc_html($remaining); ?> left this week)</span>
  </a>
  <?php else : ?>
  <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-subscription')); ?>" class="vx-hero-card">
    <div class="vx-hero-icon" style="font-size:22px;">🔒</div>
    <h2>Weekly Limit Reached</h2>
    <p>You've used all <strong><?php echo esc_html($limit); ?></strong> audits this week on the <strong><?php echo esc_html($plan_label); ?></strong> plan.</p>
    <span class="vx-hero-cta" style="color:rgba(255,255,255,.7);">Upgrade Plan →</span>
  </a>
  <?php endif; ?>

  <!-- Health Progress (merged audit stats + recent history) -->
  <div class="vx-card">
    <div class="vx-card-head">
      <h3 class="vx-card-title">Health Progress</h3>
      <?php if ($total) : ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-history')); ?>" class="vx-link">Full history →</a>
      <?php endif; ?>
    </div>

    <?php if ($hero_score !== null) : ?>

    <!-- Score trend summary -->
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0;border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:16px;">
      <div style="padding:14px 16px;border-right:1px solid var(--border);display:flex;flex-direction:column;gap:3px;">
        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Current Score</span>
        <strong style="font-family:'DM Sans',sans-serif;font-size:34px;color:<?php echo vx_dash_ring_color($hero_score); ?>;line-height:1;"><?php echo $hero_score; ?></strong>
        <span style="font-size:11px;color:var(--muted);"><?php echo esc_html(vx_dash_score_word($hero_score)); ?></span>
      </div>
      <div style="padding:14px 16px;border-right:1px solid var(--border);display:flex;flex-direction:column;gap:3px;">
        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Previous Score</span>
        <?php if ($prev_score): ?>
          <strong style="font-family:'DM Sans',sans-serif;font-size:34px;color:var(--ink3);line-height:1;"><?php echo $prev_score; ?></strong>
          <span style="font-size:11px;color:var(--muted);"><?php echo $total; ?> total audits</span>
        <?php else: ?>
          <strong style="font-family:'DM Sans',sans-serif;font-size:34px;color:var(--muted);line-height:1;">—</strong>
          <span style="font-size:11px;color:var(--muted);">first audit</span>
        <?php endif; ?>
      </div>
      <div style="padding:14px 16px;display:flex;flex-direction:column;gap:3px;">
        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);">Trend</span>
        <?php if ($trend): $t_c=$trend==='up'?'var(--green)':($trend==='down'?'var(--red)':'var(--muted)'); $t_a=$trend==='up'?'↑ ':($trend==='down'?'↓ ':'→ '); $t_w=$trend==='up'?'Improving':($trend==='down'?'Declining':'Stable'); ?>
          <strong style="font-family:'DM Sans',sans-serif;font-size:34px;color:<?php echo $t_c; ?>;line-height:1;"><?php echo $t_a.abs($hero_score-$prev_score); ?></strong>
          <span style="font-size:11px;color:<?php echo $t_c; ?>;font-weight:600;"><?php echo $t_w; ?></span>
        <?php else: ?>
          <strong style="font-family:'DM Sans',sans-serif;font-size:34px;color:var(--muted);line-height:1;">—</strong>
          <span style="font-size:11px;color:var(--muted);">run more audits</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Recent audit rows -->
    <?php foreach ( array_slice($history,0,4) as $row ) :
      $cls = vx_score_class($row->score); ?>
      <div class="vx-recent-row">
        <div class="vx-recent-score vx-score-bg-<?php echo esc_attr($cls); ?>"><?php echo esc_html($row->score); ?></div>
        <div class="vx-recent-info">
          <span class="vx-recent-domain"><?php echo esc_html($domain); ?></span>
          <span class="vx-recent-date"><?php echo esc_html($row->created_at); ?></span>
        </div>
        <span class="vx-recent-issues">
          <span class="vx-c-poor">✕ <?php echo esc_html($row->fail); ?></span>
          <span class="vx-c-warn">⚠ <?php echo esc_html($row->warn); ?></span>
          <span class="vx-c-good">✓ <?php echo esc_html($row->pass); ?></span>
        </span>
        <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-report')); ?>" class="vx-recent-arrow">→</a>
      </div>
    <?php endforeach; ?>

    <?php else : ?>
      <div class="vx-empty-state">
        <span class="dashicons dashicons-chart-line" style="font-size:36px;width:36px;height:36px;color:#ddd;margin-bottom:10px;"></span>
        <p>No audits yet. Run your first audit to start tracking health over time.</p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-new-audit')); ?>" class="vx-btn-sm">Run first audit →</a>
      </div>
    <?php endif; ?>
  </div>

  <!-- Trust Builder: 18 Real-Time Health Checks -->
  <div class="vx-card">
    <div style="margin-bottom:14px;">
      <h3 class="vx-card-title" style="margin-bottom:4px;">18 Real-Time Health Checks</h3>
      <p style="margin:0;font-size:12.5px;color:var(--ink3);line-height:1.55;">We examine <strong style="color:var(--ink);">security, speed, content clarity & search visibility</strong> — every time you run an audit, all 18 checks update instantly from your live site.</p>
    </div>
    <div class="vx-checklist">
      <?php foreach ( [
        [ '🔒', 'HTTPS & Security',              'Protects visitors and boosts rankings' ],
        [ '🤖', 'Robots.txt & Sitemap',           'Ensures Google can find your pages'    ],
        [ '📄', 'Title & Meta Description',       'First thing searchers read in Google'  ],
        [ '🔤', 'Heading Structure (H1–H4)',       'Helps Google understand your content'  ],
        [ '📝', 'Content Depth & Readability',    'Longer, clearer content ranks higher'  ],
        [ '🖼️', 'Image Alt Text',                 'Accessibility + image search rankings' ],
        [ '🔗', 'Internal & External Links',      'Signals authority and structure'       ],
        [ '⚡', 'Server Response Time (TTFB)',     'Slow servers lose Google favour'       ],
        [ '📐', 'HTML Page Size',                  'Bloated pages load slower'             ],
        [ '🧩', 'Schema / Structured Data',       'Enables rich results in Google'        ],
        [ '📱', 'Mobile Viewport',                 'Google indexes mobile-first'           ],
        [ '💬', 'Open Graph & Social Tags',       'Controls how links look when shared'   ],
      ] as [ $icon, $label, $why ] ) : ?>
        <div class="vx-checklist-item" style="gap:10px;align-items:flex-start;padding:9px 0;">
          <span style="flex-shrink:0;font-size:15px;margin-top:1px;"><?php echo $icon; ?></span>
          <div style="min-width:0;">
            <div style="font-size:12.5px;font-weight:600;color:var(--ink);line-height:1.3;"><?php echo esc_html($label); ?></div>
            <div style="font-size:11px;color:var(--muted);line-height:1.4;"><?php echo esc_html($why); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<?php require __DIR__ . '/layout-footer.php'; ?>
