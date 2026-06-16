<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

// ── Save ────────────────────────────────────────────────────
$saved = false;
if ( isset($_POST['vx_save']) && check_admin_referer('vx_save_settings') ) {
    update_option('vx_setting_brand',   sanitize_text_field($_POST['vx_brand']    ?? ''));
    update_option('vx_setting_email',   sanitize_email($_POST['vx_email']         ?? ''));
    update_option('vx_setting_whatsapp',sanitize_text_field($_POST['vx_whatsapp'] ?? ''));
    $saved = true;
}

// ── System data ─────────────────────────────────────────────
$active_plugins = get_option('active_plugins', []);
$seo_plugins = [
    'wordpress-seo/wp-seo.php'                     => 'Yoast SEO',
    'seo-by-rank-math/rank-math.php'               => 'Rank Math',
    'all-in-one-seo-pack/all_in_one_seo_pack.php'  => 'All in One SEO',
    'wp-seopress/seopress.php'                     => 'SEOPress',
];
$active_seo = 'None detected';
foreach ($seo_plugins as $path => $name) {
    if (in_array($path, $active_plugins, true)) { $active_seo = $name; break; }
}
$cache_plugins = [
    'wp-rocket/wp-rocket.php'           => 'WP Rocket',
    'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
    'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
    'wp-super-cache/wp-cache.php'       => 'WP Super Cache',
];
$active_cache = 'None detected';
foreach ($cache_plugins as $path => $name) {
    if (in_array($path, $active_plugins, true)) { $active_cache = $name; break; }
}

// ── System status ────────────────────────────────────────────
$blog_public    = (bool) get_option('blog_public');
$permalink      = get_option('permalink_structure', '');
$issues         = [];
if ( !is_ssl() )              $issues[] = ['HTTPS not active', 'https://wordpress.org/support/article/administration-over-ssl/'];
if ( !$blog_public )          $issues[] = ['Search engines blocked', admin_url('options-reading.php')];
if ( $active_cache === 'None detected' ) $issues[] = ['No cache plugin detected', 'https://wordpress.org/plugins/litespeed-cache/'];
$system_status  = empty($issues) ? 'healthy' : (count($issues) >= 2 ? 'critical' : 'warning');
$status_labels  = ['healthy' => ['✅ System Healthy', 'var(--green)', 'var(--green-l)', 'var(--green-b)'],
                   'warning' => ['⚠ 1 Issue Detected', 'var(--amber)', 'var(--amber-l)', 'var(--amber-b)'],
                   'critical'=> ['❌ Issues Found', 'var(--red)', 'var(--red-l)', 'var(--red-b)']];
[$status_text, $s_col, $s_bg, $s_bdr] = $status_labels[$system_status];

// ── Plan data ────────────────────────────────────────────────
$history       = vx_db_get_history(50);
$audits_this_week = 0;
foreach ($history as $h) {
    if (strtotime($h->created_at) >= strtotime('-7 days')) $audits_this_week++;
}
$audit_limit   = 2;
$audits_left   = max(0, $audit_limit - $audits_this_week);
?>

<style>
.vx-settings-wrap{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start}
.vx-settings-main{display:flex;flex-direction:column;gap:16px}
.vx-settings-side{display:flex;flex-direction:column;gap:16px;position:sticky;top:32px}
/* Plan card */
.vx-plan-card{background:var(--ink);border-radius:var(--r-lg);padding:22px 24px;box-shadow:var(--sh-lg);position:relative;overflow:hidden}
.vx-plan-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 85% 20%,rgba(255,255,255,.06),transparent 55%);pointer-events:none}
.vx-plan-locked{display:flex;align-items:center;gap:9px;padding:9px 12px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:8px;font-size:12.5px;color:rgba(255,255,255,.38)}
/* Integrations */
.vx-integ-item{display:flex;align-items:center;gap:12px;padding:11px 14px;border-radius:var(--r);border:1px solid var(--border);background:var(--surface)}
.vx-integ-item.connected{border-color:var(--green-b);background:var(--green-l)}
.vx-integ-item.locked{opacity:.55;filter:grayscale(.4)}
/* Health grid */
.vx-health-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--border);border-radius:var(--r);overflow:hidden}
.vx-health-item{background:var(--surface);padding:14px 16px}
.vx-health-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);display:block;margin-bottom:5px}
.vx-health-val{font-size:13.5px;font-weight:700;display:block}
.vx-h-ok{color:var(--green)} .vx-h-warn{color:var(--red)} .vx-h-neutral{color:var(--ink)}
/* Danger zone */
.vx-danger-zone{border-color:var(--red-b)!important}
.vx-danger-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid var(--border)}
.vx-danger-row:last-child{border-bottom:none;padding-bottom:0}
/* About grid */
.vx-about-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border-radius:var(--r);overflow:hidden}
.vx-about-cell{background:var(--surface);padding:14px 16px}
.vx-about-cell span{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);display:block;margin-bottom:4px}
.vx-about-cell strong{font-size:13.5px;font-weight:700;color:var(--ink)}
/* Toggle row */
.vx-toggle-row{display:flex;align-items:center;justify-content:space-between;padding:13px 0;border-bottom:1px solid var(--border)}
.vx-toggle-row:last-child{border-bottom:none}
.vx-toggle-lock{display:inline-flex;align-items:center;gap:5px;font-size:10px;font-weight:700;background:var(--surface2);border:1px solid var(--border);border-radius:var(--r-pill);padding:3px 9px;color:var(--muted)}
@media(max-width:1100px){.vx-settings-wrap{grid-template-columns:1fr}.vx-settings-side{position:static}.vx-health-grid{grid-template-columns:repeat(2,1fr)}.vx-about-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<!-- PAGE HEADER -->
<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title">Settings</h1>
    <p class="vx-page-sub">Manage your Vixion Health settings and integrations</p>
  </div>
  <span style="display:inline-flex;align-items:center;gap:7px;background:<?php echo $s_bg; ?>;border:1.5px solid <?php echo $s_bdr; ?>;color:<?php echo $s_col; ?>;border-radius:var(--r-pill);padding:9px 18px;font-size:13px;font-weight:700;"><?php echo $status_text; ?></span>
</div>

<?php if ($saved): ?>
  <div class="vx-notice vx-notice-ok">✓ Settings saved successfully.</div>
<?php endif; ?>

<?php if (!empty($issues)): ?>
  <div style="display:flex;flex-direction:column;gap:8px;">
    <?php foreach($issues as [$issue_txt, $issue_url]): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;background:var(--amber-l);border:1.5px solid var(--amber-b);border-radius:var(--r);padding:12px 18px;">
        <div style="display:flex;align-items:center;gap:10px;">
          <span style="font-size:16px;">⚠</span>
          <span style="font-size:13px;font-weight:600;color:var(--ink2);"><?php echo esc_html($issue_txt); ?></span>
        </div>
        <a href="<?php echo esc_url($issue_url); ?>" target="_blank" style="font-size:12px;font-weight:700;color:var(--amber);white-space:nowrap;">Fix this →</a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="vx-settings-wrap">

  <!-- ── MAIN COLUMN ──────────────────────────────────── -->
  <div class="vx-settings-main">

    <form method="post" id="vx-settings-form">
      <?php wp_nonce_field('vx_save_settings'); ?>

      <!-- ① ACCOUNT & BRANDING -->
      <div class="vx-card">
        <h3 class="vx-card-title">Account & Branding</h3>
        <p style="margin:-8px 0 18px;font-size:13px;color:var(--muted);">Used in health reports and exported documents.</p>
        <div class="vx-form-2col">
          <div class="vx-fg">
            <label class="vx-lbl">Brand / Agency Name</label>
            <input type="text" name="vx_brand" class="vx-input" value="<?php echo esc_attr(get_option('vx_setting_brand', get_bloginfo('name'))); ?>" />
            <p class="vx-hint">Appears in report headers and exports.</p>
          </div>
          <div class="vx-fg">
            <label class="vx-lbl">Notification Email</label>
            <input type="email" name="vx_email" class="vx-input" value="<?php echo esc_attr(get_option('vx_setting_email', get_bloginfo('admin_email'))); ?>" />
            <p class="vx-hint">Where alerts and reports are sent.</p>
          </div>
        </div>
        <div class="vx-fg" style="max-width:340px;">
          <label class="vx-lbl">WhatsApp Number (with country code)</label>
          <input type="text" name="vx_whatsapp" class="vx-input" value="<?php echo esc_attr(get_option('vx_setting_whatsapp', '')); ?>" placeholder="+91 98765 43210" />
        </div>
        <!-- Logo upload — Pro teaser -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--surface2);border:1.5px dashed var(--border);border-radius:var(--r);margin-top:4px;">
          <div>
            <div style="font-size:13px;font-weight:700;color:var(--ink);margin-bottom:3px;">Brand Logo <span style="font-size:10px;font-weight:700;background:var(--amber-l);color:var(--amber);border:1px solid var(--amber-b);border-radius:var(--r-pill);padding:2px 8px;margin-left:6px;">Pro</span></div>
            <div style="font-size:12px;color:var(--muted);">Shown in PDF exports and client-facing reports.</div>
          </div>
<span style="font-size:12px;color:var(--muted);">Coming soon</span>
        </div>
      </div>

      <!-- ② INTEGRATIONS -->
      <div class="vx-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
          <h3 class="vx-card-title" style="margin:0;">Integrations</h3>
          <span style="font-size:11px;font-weight:700;background:var(--amber-l);color:var(--amber);border:1px solid var(--amber-b);border-radius:var(--r-pill);padding:4px 11px;">Connect to unlock real data</span>
        </div>
        <p style="margin:0 0 18px;font-size:13px;color:var(--muted);">Connect your Google data to see real traffic, rankings, and visibility — all inside Vixion Health.</p>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <?php
          $ga_connected = get_option('vx_ga_property_id','') && get_option('vx_ga_json_key','');
          $sc_connected = get_option('vx_sc_site_url','')    && (get_option('vx_sc_json_key','') || get_option('vx_ga_json_key',''));
          $integrations = [
            ['📊','Google Analytics 4','Sessions, users, page views, traffic sources',  $ga_connected, admin_url('admin.php?page=vixion-seo-analytics')],
            ['🔍','Google Search Console','Impressions, clicks, keywords, ranking',      $sc_connected, admin_url('admin.php?page=vixion-seo-search-console')],
            ['📈','Trend Reports','Score improvements over time',                         false, admin_url('admin.php?page=vixion-seo-reports')],
            ['🎯','Keyword Gaps','Find what you rank for but don\'t target',             false, admin_url('admin.php?page=vixion-seo-keywords')],
          ];
          foreach ($integrations as [$ico,$name,$desc,$connected,$url]):
            $cls = $connected ? 'connected' : ($url ? '' : 'locked');
          ?>
            <a href="<?php echo esc_url($url); ?>" class="vx-integ-item <?php echo $cls; ?>" style="text-decoration:none;">
              <span style="font-size:22px;flex-shrink:0;"><?php echo $ico; ?></span>
              <div style="flex:1;">
                <div style="font-size:13.5px;font-weight:700;color:var(--ink);margin-bottom:2px;"><?php echo esc_html($name); ?></div>
                <div style="font-size:12px;color:var(--muted);"><?php echo esc_html($desc); ?></div>
              </div>
              <?php if ($connected): ?>
                <span style="font-size:11px;font-weight:700;color:var(--green);background:var(--green-l);border:1px solid var(--green-b);border-radius:var(--r-pill);padding:3px 9px;flex-shrink:0;">✓ Connected</span>
              <?php else: ?>
                <span style="font-size:11px;font-weight:700;color:var(--muted);border:1px solid var(--border);border-radius:var(--r-pill);padding:3px 9px;flex-shrink:0;">Connect →</span>
              <?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ③ AUTOMATION — locked Pro teaser -->
      <div class="vx-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
          <h3 class="vx-card-title" style="margin:0;">Automation</h3>
          <span style="font-size:11px;font-weight:700;background:var(--surface2);color:var(--muted);border:1px solid var(--border);border-radius:var(--r-pill);padding:4px 11px;">🔒 Pro Feature</span>
        </div>
        <?php foreach([
          ['Run weekly health check automatically',   'Scans your site every Monday and emails you the results.'],
          ['Alert when health score drops',           'Notifies you if your score drops more than 5 points.'],
          ['Monthly progress summary email',          'A plain-English summary of your improvement over the month.'],
        ] as [$label, $desc]): ?>
          <div class="vx-toggle-row">
            <div>
              <div style="font-size:13.5px;font-weight:600;color:var(--ink2);"><?php echo esc_html($label); ?></div>
              <div style="font-size:12px;color:var(--muted);margin-top:2px;"><?php echo esc_html($desc); ?></div>
            </div>
            <span class="vx-toggle-lock">🔒 Pro</span>
          </div>
        <?php endforeach; ?>
        <p style="margin:14px 0 0;font-size:12px;color:var(--muted);">These features are in development and will be available soon.</p>
      </div>

      <button type="submit" name="vx_save" class="vx-btn-primary vx-btn-large">Save Settings</button>
    </form>

    <!-- ④ SYSTEM HEALTH OVERVIEW -->
    <div class="vx-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 class="vx-card-title" style="margin:0;">System Health Overview</h3>
        <span style="font-size:12px;font-weight:700;background:<?php echo $s_bg; ?>;color:<?php echo $s_col; ?>;border:1px solid <?php echo $s_bdr; ?>;border-radius:var(--r-pill);padding:4px 11px;"><?php echo $status_text; ?></span>
      </div>
      <div class="vx-health-grid">
        <?php
        $health_items = [
          ['WordPress', get_bloginfo('version'),           version_compare(get_bloginfo('version'),'6.0','>=')],
          ['PHP',       PHP_VERSION,                       version_compare(PHP_VERSION,'8.0','>=')],
          ['SEO Plugin',$active_seo,                       $active_seo !== 'None detected'],
          ['Cache',     $active_cache,                     $active_cache !== 'None detected'],
          ['HTTPS',     is_ssl() ? 'Active' : 'Not Active',is_ssl()],
          ['Search',    $blog_public ? 'Visible' : '⚠ Blocked', $blog_public],
          ['Permalink', $permalink ?: '/?p=123 (default)', !empty($permalink)],
          ['Plugins',   count($active_plugins).' active',  count($active_plugins) < 30],
          ['Theme',     wp_get_theme()->get('Name'),        true],
          ['Language',  get_bloginfo('language'),           true],
          ['Admin Email',get_bloginfo('admin_email'),       true],
          ['Multisite', is_multisite() ? 'Yes' : 'No',     true],
        ];
        foreach ($health_items as [$lbl,$val,$ok]): ?>
          <div class="vx-health-item">
            <span class="vx-health-label"><?php echo esc_html($lbl); ?></span>
            <span class="vx-health-val <?php echo $ok ? 'vx-h-ok' : 'vx-h-warn'; ?>"><?php echo esc_html($val); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ⑤ DANGER ZONE -->
    <div class="vx-card" style="border:1.5px solid var(--red-b);">
      <h3 class="vx-card-title" style="color:var(--red);margin-bottom:4px;">⚠ Danger Zone</h3>
      <p style="margin:0 0 16px;font-size:13px;color:var(--muted);">These actions are permanent and cannot be undone.</p>
      <div class="vx-danger-row">
        <div>
          <strong style="font-size:13.5px;color:var(--ink2);">Clear All Audit History</strong>
          <p style="margin:3px 0 0;font-size:12px;color:var(--muted);">Deletes all scan records from the database.</p>
        </div>
        <button type="button" id="vx-danger-clear" class="vx-btn-outline vx-btn-danger" style="flex-shrink:0;">Clear History</button>
      </div>
      <div class="vx-danger-row">
        <div>
          <strong style="font-size:13.5px;color:var(--ink2);">Disconnect Google Accounts</strong>
          <p style="margin:3px 0 0;font-size:12px;color:var(--muted);">Removes saved GA4 and Search Console credentials.</p>
        </div>
        <button type="button" id="vx-danger-google" class="vx-btn-outline vx-btn-danger" style="flex-shrink:0;">Disconnect</button>
      </div>
      <div class="vx-danger-row">
        <div>
          <strong style="font-size:13.5px;color:var(--ink2);">Reset All Plugin Data</strong>
          <p style="margin:3px 0 0;font-size:12px;color:var(--muted);">Wipes all settings, history, and integrations. Fresh start.</p>
        </div>
        <button type="button" id="vx-danger-reset" class="vx-btn-outline vx-btn-danger" style="flex-shrink:0;">Reset Plugin</button>
      </div>
    </div>

    <!-- ⑥ ABOUT PLUGIN -->
    <div class="vx-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
        <h3 class="vx-card-title" style="margin:0;">About Vixion Health</h3>
        <a href="https://vixion.in/changelog" target="_blank" style="font-size:12.5px;font-weight:700;color:var(--muted);text-decoration:none;">📋 View Changelog →</a>
      </div>
      <div class="vx-about-grid">
        <?php foreach([
          ['Plugin',    'Vixion Health'],
          ['Version',   VX_SEO_VERSION],
          ['Author',    'Vixion'],
          ['Website',   'vixion.in'],
          ['Support',   'hello@vixion.in'],
          ['Installed', date_i18n('M j, Y', get_option('vx_seo_installed', time()))],
        ] as [$k,$v]): ?>
          <div class="vx-about-cell">
            <span><?php echo esc_html($k); ?></span>
            <?php if (in_array($k, ['Website','Support'])): ?>
              <strong><a href="<?php echo $k==='Support'?'mailto:':'https://'; ?><?php echo esc_attr($v); ?>" style="color:var(--ink);text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'"><?php echo esc_html($v); ?></a></strong>
            <?php else: ?>
              <strong><?php echo esc_html($v); ?></strong>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- /main col -->

  <!-- ── SIDEBAR COLUMN ────────────────────────────────── -->
  <div class="vx-settings-side">

    <!-- Plan & License card -->
    <div class="vx-plan-card">
      <div style="position:relative;">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.35);margin-bottom:8px;">Current Plan</div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
          <span style="font-size:26px;font-weight:800;color:#fff;">Free</span>
          <span style="font-size:11px;font-weight:700;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);border-radius:var(--r-pill);padding:4px 11px;color:rgba(255,255,255,.6);">Beta</span>
        </div>
        <!-- Audit usage bar -->
        <div style="margin-bottom:18px;">
          <div style="display:flex;justify-content:space-between;font-size:11px;color:rgba(255,255,255,.4);margin-bottom:6px;">
            <span>Scans this week</span>
            <span><?php echo $audits_this_week; ?> / <?php echo $audit_limit; ?></span>
          </div>
          <div style="background:rgba(255,255,255,.1);border-radius:4px;height:6px;overflow:hidden;">
            <div style="width:<?php echo min(100, round($audits_this_week/$audit_limit*100)); ?>%;background:#fff;height:6px;border-radius:4px;transition:width .4s;"></div>
          </div>
          <?php if ($audits_left <= 2): ?>
            <div style="font-size:11px;color:rgba(255,200,100,.8);margin-top:5px;font-weight:600;"><?php echo $audits_left; ?> scan<?php echo $audits_left!==1?'s':''; ?> left this week</div>
          <?php endif; ?>
        </div>
        <!-- Unlocked -->
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.3);margin-bottom:8px;">Included in Free</div>
        <div style="display:flex;flex-direction:column;gap:5px;margin-bottom:18px;">
          <?php foreach(['Health Checks','Competitor Analysis','Progress Tracking','Traffic Overview'] as $f): ?>
            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:rgba(255,255,255,.65);">
              <span style="color:#22c55e;font-size:13px;">✓</span> <?php echo esc_html($f); ?>
            </div>
          <?php endforeach; ?>
        </div>
        <!-- More coming -->
        <div style="margin-top:6px;padding:12px 14px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:8px;font-size:12px;color:rgba(255,255,255,.38);line-height:1.6;">
          🚀 More features launching soon — keyword tracking, AI insights, and advanced reports.
        </div>
      </div>
    </div>

    <!-- Quick links -->
    <div class="vx-card" style="padding:16px 18px;">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:12px;">Quick Links</div>
      <div style="display:flex;flex-direction:column;gap:2px;">
        <?php foreach([
          ['Run a new health check',  admin_url('admin.php?page=vixion-seo-new-audit')],
          ['View latest report',      admin_url('admin.php?page=vixion-seo-report')],
          ['Check your progress',     admin_url('admin.php?page=vixion-seo-history')],
          ['Contact support',         admin_url('admin.php?page=vixion-seo-support')],
        ] as [$lbl,$url]): ?>
          <a href="<?php echo esc_url($url); ?>" style="display:flex;align-items:center;justify-content:space-between;padding:9px 11px;border-radius:var(--r);font-size:13px;font-weight:600;color:var(--ink2);text-decoration:none;transition:background .12s;"
             onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background=''">
            <?php echo esc_html($lbl); ?> <span style="color:var(--muted);">→</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- /sidebar -->

</div><!-- /wrap -->

<script>
(function($){
  $('#vx-danger-clear').on('click',function(){
    if(!confirm('Delete all audit history? This cannot be undone.'))return;
    $.post(vxSeo.ajaxUrl,{action:'vx_clear_history',nonce:vxSeo.nonce})
      .done(function(){location.reload();});
  });
  $('#vx-danger-google').on('click',function(){
    if(!confirm('Remove saved Google credentials? You will need to reconnect GA4 and Search Console.'))return;
    $.post(vxSeo.ajaxUrl,{action:'vx_disconnect_google',nonce:vxSeo.nonce})
      .done(function(){location.reload();});
  });
  $('#vx-danger-reset').on('click',function(){
    if(!confirm('Reset ALL plugin data? This deletes all settings, history, and integrations. Cannot be undone.'))return;
    $.post(vxSeo.ajaxUrl,{action:'vx_reset_plugin',nonce:vxSeo.nonce})
      .done(function(){location.reload();});
  });
})(jQuery);
</script>

<?php require __DIR__ . '/layout-footer.php'; ?>
