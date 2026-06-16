<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

$site   = get_site_url();
$domain = parse_url( $site, PHP_URL_HOST );

$steps = [
    'db'      => 'Reading WordPress database',
    'posts'   => 'Analysing posts & pages',
    'content' => 'Checking content quality',
    'images'  => 'Scanning image library',
    'plugins' => 'Checking plugins & settings',
    'fetch'   => 'Fetching live homepage',
    'onpage'  => 'Checking on-page SEO',
    'speed'   => 'Measuring server speed',
    'tech'    => 'Technical SEO checks',
    'robots'  => 'Checking robots & sitemap',
    'schema'  => 'Detecting structured data',
    'ai'      => 'Generating action plan',
];

$audit_log   = get_option( 'vx_audit_log', [] );
$cooldown    = 7 * DAY_IN_SECONDS;
$now         = time();
$bonus       = (int) get_option( 'vx_bonus_audits', 0 );
$plan        = get_option( 'vx_plan', 'free' );
$plan_limits = [ 'free' => 2, 'gold' => 5, 'platinum' => 15 ];
$plan_icons  = [ 'free' => '🆓', 'gold' => '⭐', 'platinum' => '💎' ];
$base_limit  = $plan_limits[ $plan ] ?? 2;
$limit       = $base_limit + $bonus;
$audit_log   = array_values( array_filter( $audit_log, fn( $ts ) => ( $now - $ts ) < $cooldown ) );
$used        = count( $audit_log );
$locked      = $used >= $limit;

if ( $locked && ! empty( $audit_log ) ) {
    $oldest    = min( $audit_log );
    $next_ts   = $oldest + $cooldown;
    $remaining = $next_ts - $now;
    $days      = floor( $remaining / DAY_IN_SECONDS );
    $hours     = floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
    $minutes   = floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
    $next_time = date_i18n( 'D, d M Y \a\t g:i A', $next_ts );
    $last_time = date_i18n( 'D, d M Y \a\t g:i A', max( $audit_log ) );
}

$subscription_url = admin_url( 'admin.php?page=vixion-seo-subscription' );
?>

<style>
.vx-audit-quota{display:flex;align-items:center;gap:10px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--r-pill);padding:10px 18px;flex-shrink:0}
.vx-audit-quota.locked{background:var(--red-l);border-color:var(--red-b)}
.vx-quota-plan{font-size:13px;font-weight:700;color:var(--ink)}
.vx-quota-count{font-size:12px;color:var(--muted)}
.vx-quota-dots{display:flex;gap:5px}
.vx-quota-dot{width:11px;height:11px;border-radius:50%}
.vx-quota-dot.used{background:#ef4444}
.vx-quota-dot.free{background:#dcfce7;border:1.5px solid #86efac}
.vx-ratelimit-banner{display:flex;align-items:center;gap:20px;background:var(--red-l);border:1.5px solid var(--red-b);border-radius:var(--r-lg);padding:20px 24px;flex-wrap:wrap}
.vx-ratelimit-icon{font-size:28px;flex-shrink:0}
.vx-ratelimit-body{flex:1;display:flex;flex-direction:column;gap:3px;min-width:200px}
.vx-ratelimit-body strong{font-size:15px;color:var(--red);font-weight:700}
.vx-ratelimit-body span{font-size:13px;color:var(--ink3)}
.vx-ratelimit-body em{font-style:normal;color:var(--ink2);font-weight:600}
.vx-ratelimit-countdown{display:flex;align-items:center;gap:6px;background:var(--ink);border-radius:10px;padding:12px 20px}
.vx-cd-block{display:flex;flex-direction:column;align-items:center;min-width:38px}
.vx-cd-num{font-size:26px;font-weight:800;color:#fff;line-height:1;font-family:monospace}
.vx-cd-lbl{font-size:10px;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.08em;margin-top:2px}
.vx-cd-sep{font-size:22px;font-weight:700;color:var(--red);margin-bottom:14px}
.vx-outcome-item{display:flex;align-items:flex-start;gap:12px;padding:11px 14px;background:var(--green-l);border:1px solid var(--green-b);border-radius:10px}
.vx-outcome-check{font-size:15px;font-weight:700;color:var(--green);flex-shrink:0;margin-top:1px}
.vx-outcome-text{font-size:13.5px;font-weight:600;color:var(--ink2);line-height:1.4}
.vx-preview-card{background:var(--surface2);border:1.5px dashed var(--border2);border-radius:var(--r-lg);padding:20px 22px}
.vx-preview-ring{width:54px;height:54px;border-radius:50%;border:4px solid #f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.vx-preview-ring strong{font-size:18px;font-weight:800;color:var(--ink);line-height:1}
.vx-preview-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.vx-newaudit-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start}
@media(max-width:900px){.vx-newaudit-grid{grid-template-columns:1fr}}
</style>

<!-- PAGE HEADER -->
<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title">Website Health Check</h1>
    <p class="vx-page-sub">Let's see if <strong><?php echo esc_html( $domain ); ?></strong> is healthy — or quietly losing traffic</p>
  </div>
  <div class="vx-audit-quota <?php echo $locked ? 'locked' : ''; ?>">
    <span class="vx-quota-plan"><?php echo $plan_icons[$plan] ?? ''; ?> <?php echo esc_html( ucfirst( $plan ) ); ?></span>
    <span class="vx-quota-count"><?php echo esc_html( $used ); ?>/<?php echo esc_html( $limit ); ?> used</span>
    <div class="vx-quota-dots">
      <?php for ( $i = 0; $i < $limit; $i++ ) : ?>
        <span class="vx-quota-dot <?php echo $i < $used ? 'used' : 'free'; ?>"></span>
      <?php endfor; ?>
    </div>
  </div>
</div>

<?php if ( ! $locked && $used === $limit - 1 ) : ?>
<div style="background:var(--amber-l);border:1px solid var(--amber-b);border-radius:var(--r);padding:11px 16px;font-size:13px;color:var(--amber);font-weight:600;">
  ⚠ This is your last health check this week on the <?php echo esc_html( ucfirst( $plan ) ); ?> plan.

</div>
<?php endif; ?>

<div class="vx-newaudit-grid">

  <!-- LEFT COLUMN -->
  <div style="display:flex;flex-direction:column;gap:16px;">

    <!-- Site card — domain only, no WP version/plugin count -->
    <div class="vx-card" style="display:flex;align-items:center;gap:18px;padding:20px 24px;">
      <div style="width:48px;height:48px;background:var(--bg);border:1px solid var(--border);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;">🌐</div>
      <div style="flex:1;min-width:0;">
        <strong style="font-size:16px;font-weight:800;color:var(--ink);display:block;line-height:1.2;"><?php echo esc_html( $domain ); ?></strong>
        <span style="font-size:13px;color:var(--muted);"><?php echo esc_html( $site ); ?></span>
      </div>
      <?php if ( $locked ) : ?>
        <span class="vx-badge vx-badge-red">🔒 Limit Reached</span>
      <?php else : ?>
        <span class="vx-badge vx-badge-green">✓ Ready to scan</span>
      <?php endif; ?>
    </div>

    <!-- Outcome list: what they GET, not how it works -->
    <div class="vx-card">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:14px;">What you'll find out</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ( [
          "What's hurting your traffic right now",
          'Which pages Google struggles to understand',
          'Whether your server speed is costing you rankings',
          'Images and headings invisible to search engines',
          'A clear, step-by-step fix plan — no guesswork',
          'Your health score so you can track improvement',
        ] as $outcome ) : ?>
          <div class="vx-outcome-item">
            <span class="vx-outcome-check">✔</span>
            <span class="vx-outcome-text"><?php echo esc_html( $outcome ); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ( ! $locked ) : ?>

    <!-- Risk reminder — emotional trigger above button -->
    <div style="display:flex;align-items:flex-start;gap:12px;background:var(--amber-l);border:1.5px solid var(--amber-b);border-radius:var(--r);padding:14px 18px;">
      <span style="font-size:20px;flex-shrink:0;margin-top:1px;">⚠️</span>
      <p style="margin:0;font-size:13.5px;color:var(--amber);font-weight:600;line-height:1.55;"><strong style="color:var(--ink2);">Most websites have 3–7 hidden issues</strong> quietly affecting their Google ranking. Most owners never know until traffic drops.</p>
    </div>

    <!-- Big CTA button + time expectation -->
    <div style="display:flex;flex-direction:column;gap:8px;">
      <button id="vx-run-btn" class="vx-btn-primary"
        style="font-size:16px;padding:16px 32px;border-radius:var(--r-pill);width:100%;justify-content:center;font-weight:800;letter-spacing:-.2px;">
        <span class="dashicons dashicons-search" style="font-size:18px;width:18px;height:18px;margin-top:1px;"></span>
        <span id="vx-btn-text">Find My Issues</span>
      </button>
      <p style="margin:0;text-align:center;font-size:12px;color:var(--muted);">
        ⏱ Takes 30–45 seconds &nbsp;·&nbsp; <?php echo esc_html( ucfirst( $plan ) ); ?> plan &nbsp;·&nbsp; No setup needed
      </p>
    </div>

    <?php else : ?>

    <!-- Locked state -->
    <div class="vx-ratelimit-banner" id="vx-ratelimit-banner">
      <div class="vx-ratelimit-icon">🔒</div>
      <div class="vx-ratelimit-body">
        <strong>All <?php echo esc_html( $limit ); ?> weekly check-ups used</strong>
        <span>Last scan: <em><?php echo esc_html( $last_time ); ?></em></span>
        <span>Next slot opens: <em><?php echo esc_html( $next_time ); ?></em></span>
        <span style="margin-top:6px;">
          <span style="font-size:13px;color:var(--ink3);">Your limit resets 7 days after your first scan this week.</span>
        </span>
      </div>
      <div class="vx-ratelimit-countdown" id="vx-countdown" data-remaining="<?php echo esc_attr( $remaining ); ?>">
        <div class="vx-cd-block"><span class="vx-cd-num" id="vx-cd-days"><?php echo $days; ?></span><span class="vx-cd-lbl">days</span></div>
        <div class="vx-cd-sep">:</div>
        <div class="vx-cd-block"><span class="vx-cd-num" id="vx-cd-hours"><?php echo $hours; ?></span><span class="vx-cd-lbl">hrs</span></div>
        <div class="vx-cd-sep">:</div>
        <div class="vx-cd-block"><span class="vx-cd-num" id="vx-cd-mins"><?php echo $minutes; ?></span><span class="vx-cd-lbl">min</span></div>
        <div class="vx-cd-sep">:</div>
        <div class="vx-cd-block"><span class="vx-cd-num" id="vx-cd-secs">00</span><span class="vx-cd-lbl">sec</span></div>
      </div>
    </div>
    <script>
    (function() {
      var el = document.getElementById('vx-countdown');
      if ( ! el ) return;
      var remaining = parseInt( el.dataset.remaining, 10 );
      function pad(n) { return String(n).padStart(2, '0'); }
      function tick() {
        if ( remaining <= 0 ) { location.reload(); return; }
        remaining--;
        document.getElementById('vx-cd-days').textContent  = pad( Math.floor( remaining / 86400 ) );
        document.getElementById('vx-cd-hours').textContent = pad( Math.floor( ( remaining % 86400 ) / 3600 ) );
        document.getElementById('vx-cd-mins').textContent  = pad( Math.floor( ( remaining % 3600 ) / 60 ) );
        document.getElementById('vx-cd-secs').textContent  = pad( remaining % 60 );
      }
      setInterval( tick, 1000 );
      tick();
    })();
    </script>

    <?php endif; ?>

    <p id="vx-error-msg" style="display:none;color:var(--red);font-weight:600;font-size:13px;margin:0;"></p>

    <!-- Progress card hidden until scan starts -->
    <div class="vx-card" id="vx-progress-wrap" style="display:none;">
      <div class="vx-progress-head">
        <span id="vx-progress-label">Starting check-up…</span>
        <span class="vx-progress-pct" id="vx-pct">0%</span>
      </div>
      <div class="vx-progress-track"><div class="vx-progress-fill" id="vx-bar"></div></div>
      <div class="vx-steps-grid">
        <?php foreach ( $steps as $id => $label ) : ?>
          <div class="vx-step" id="vx-step-<?php echo esc_attr( $id ); ?>">
            <span class="vx-step-status-icon"></span>
            <span class="vx-step-lbl"><?php echo esc_html( $label ); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- /left col -->

  <!-- RIGHT COLUMN -->
  <div style="display:flex;flex-direction:column;gap:16px;">

    <!-- Mock result preview — creates curiosity -->
    <div class="vx-preview-card">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:16px;">Example — what your report looks like</div>

      <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid var(--border);">
        <div class="vx-preview-ring"><strong>68</strong></div>
        <div>
          <div style="font-size:15px;font-weight:800;color:var(--ink);line-height:1.2;">Needs Work</div>
          <div style="font-size:12px;color:var(--muted);margin-top:2px;">Website Health Score</div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:9px;">
        <div style="display:flex;align-items:center;gap:9px;font-size:13px;">
          <div class="vx-preview-dot" style="background:var(--red);"></div>
          <span style="color:var(--red);font-weight:700;">3 urgent issues detected</span>
        </div>
        <div style="display:flex;align-items:center;gap:9px;font-size:12.5px;">
          <div class="vx-preview-dot" style="background:var(--amber);"></div>
          <span style="color:var(--ink2);">Homepage bounce rate too high</span>
        </div>
        <div style="display:flex;align-items:center;gap:9px;font-size:12.5px;">
          <div class="vx-preview-dot" style="background:var(--amber);"></div>
          <span style="color:var(--ink2);">12 images missing alt text</span>
        </div>
        <div style="display:flex;align-items:center;gap:9px;font-size:12.5px;">
          <div class="vx-preview-dot" style="background:var(--amber);"></div>
          <span style="color:var(--ink2);">Meta description too short</span>
        </div>
        <div style="display:flex;align-items:center;gap:9px;font-size:12.5px;">
          <div class="vx-preview-dot" style="background:var(--green);"></div>
          <span style="color:var(--green);">HTTPS secure ✓</span>
        </div>
        <div style="display:flex;align-items:center;gap:9px;font-size:12.5px;">
          <div class="vx-preview-dot" style="background:var(--green);"></div>
          <span style="color:var(--green);">Sitemap found ✓</span>
        </div>
      </div>

      <div style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border);font-size:11.5px;color:var(--muted);font-style:italic;line-height:1.5;">
        Your report will show real data from <strong style="color:var(--ink2);font-style:normal;"><?php echo esc_html( $domain ); ?></strong>
      </div>
    </div>

    <!-- 18 checks compact trust list -->
    <div class="vx-card" style="padding:18px 20px;">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:12px;">18 areas we examine</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ( [
          [ '🔒', 'Security & HTTPS'    ],
          [ '⚡', 'Server speed (TTFB)' ],
          [ '📄', 'Title & meta tags'   ],
          [ '🔤', 'Heading structure'   ],
          [ '🖼️', 'Image alt text'     ],
          [ '🗺️', 'Sitemap & robots'   ],
          [ '📱', 'Mobile readiness'    ],
          [ '🧩', 'Schema markup'       ],
          [ '🔗', 'Internal links'      ],
        ] as [ $ic, $lb ] ) : ?>
          <div style="display:flex;align-items:center;gap:9px;font-size:12.5px;color:var(--ink2);">
            <span style="font-size:14px;flex-shrink:0;"><?php echo $ic; ?></span>
            <?php echo esc_html( $lb ); ?>
          </div>
        <?php endforeach; ?>
        <div style="font-size:11.5px;color:var(--muted);padding-top:4px;font-style:italic;">+ 9 more checks</div>
      </div>
    </div>

  </div><!-- /right col -->

</div><!-- /grid -->

<?php if ( ! $locked ) : ?>
<script>
(function($){
  const steps = <?php echo wp_json_encode( array_keys( $steps ) ); ?>;

  $('#vx-run-btn').on('click', function(){
    $('#vx-error-msg').hide();
    $('#vx-progress-wrap').slideDown(200);
    $(this).prop('disabled', true);
    $('#vx-btn-text').text('Scanning…');

    let si = 0;
    const interval = setInterval(function(){
      if ( si > 0 ) {
        const prev = $('#vx-step-' + steps[si-1]);
        prev.removeClass('active').addClass('done');
        prev.find('.vx-step-status-icon').html('<span class="vx-tick">✓</span>');
      }
      if ( si < steps.length ) {
        const cur = $('#vx-step-' + steps[si]);
        cur.addClass('active');
        cur.find('.vx-step-status-icon').html('<span class="vx-spin">◌</span>');
        $('#vx-progress-label').text(cur.find('.vx-step-lbl').text() + '…');
        si++;
        const pct = Math.round((si / steps.length) * 80);
        $('#vx-bar').css('width', pct + '%');
        $('#vx-pct').text(pct + '%');
      } else {
        clearInterval(interval);
      }
    }, 600);

    $.post(vxSeo.ajaxUrl, { action: 'vx_run_audit', nonce: vxSeo.nonce })
    .done(function(r){
      clearInterval(interval);
      if ( r.success ) {
        $('#vx-bar').css('width','100%');
        $('#vx-pct').text('100%');
        $('#vx-progress-label').text('Check-up complete! Loading your results…');
        steps.forEach(s => {
          const el = $('#vx-step-' + s);
          el.removeClass('active').addClass('done');
          el.find('.vx-step-status-icon').html('<span class="vx-tick">✓</span>');
        });
        setTimeout(function(){ window.location.href = vxSeo.adminUrl + '?page=vixion-seo-report'; }, 800);
      } else {
        $('#vx-progress-wrap').hide();
        if ( r.data && r.data.code === 'rate_limited' ) {
          $('#vx-error-msg').html(
            '⛔ Limit reached (' + r.data.used + '/' + r.data.limit + ' this week). Next slot: <strong>' +
            r.data.remaining_text + '</strong>.'
          ).show();
          $('#vx-run-btn').prop('disabled', true).css({ opacity:'0.45', cursor:'not-allowed' });
          $('#vx-btn-text').text('🔒 Limit Reached');
        } else {
          var errMsg  = (r.data && r.data.message) ? r.data.message : (r.data || 'Scan failed. Please try again.');
          var errCode = (r.data && r.data.code) ? ' [' + r.data.code + ']' : '';
          $('#vx-error-msg').html('⚠ ' + errMsg + errCode).show();
          $('#vx-run-btn').prop('disabled', false);
          $('#vx-btn-text').text('Find My Issues');
        }
      }
    })
    .fail(function(){
      clearInterval(interval);
      $('#vx-error-msg').text('Server error. Please try again.').show();
      $('#vx-progress-wrap').hide();
      $('#vx-run-btn').prop('disabled', false);
      $('#vx-btn-text').text('Find My Issues');
    });
  });
})(jQuery);
</script>
<?php endif; ?>

<?php require __DIR__ . '/layout-footer.php'; ?>
