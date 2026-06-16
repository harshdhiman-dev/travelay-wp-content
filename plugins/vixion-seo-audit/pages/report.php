<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

$report = get_option( 'vx_last_report', null );

if ( ! $report ) :
?>
<div class="vx-page-header"><h1 class="vx-page-title">Last Report</h1></div>
<div class="vx-empty-page">
  <span style="font-size:56px">📋</span>
  <h2>No Report Yet</h2>
  <p>Run your first health check to see the detailed report.</p>
  <a href="<?php echo esc_url( admin_url( 'admin.php?page=vixion-seo-new-audit' ) ); ?>" class="vx-btn-primary">Run Health Check Now →</a>
</div>
<?php require __DIR__ . '/layout-footer.php'; return; endif;

$score   = $report['scoring']['score'];
$pass    = $report['scoring']['pass'];
$warn    = $report['scoring']['warn'];
$fail    = $report['scoring']['fail'];
$cls     = vx_score_class( $score );
$cats    = array_unique( array_column( $report['checks'], 'category' ) );
$site    = $report['site_url'] ?? get_site_url();
$domain  = parse_url( $site, PHP_URL_HOST );
$stats   = $report['stats'] ?? [];

// Estimated score improvement if critical issues fixed
$est_score = min( 100, $score + ( $fail * 3 ) + ( $warn * 1 ) );

// Human-readable label mappings for check names
function vx_human_label( $label ) {
    $map = [
        'Homepage Title Tag'          => 'Your homepage has no clear title for Google',
        'Homepage Meta Description'   => 'Your homepage is missing a description in search results',
        'Homepage H1 Tag'             => 'Your homepage is missing a main heading',
        'Posts Missing SEO Title'     => 'Some blog posts don\'t have proper titles',
        'Posts Missing Meta Description' => 'Some pages don\'t have descriptions',
        'Canonical Tag (Homepage)'    => 'Duplicate URL issue on your homepage',
        'Thin Content Posts'          => 'Some posts are too short to rank well',
        'Posts Without Category'      => 'Some blog posts have no category assigned',
        'Draft Posts'                 => 'You have unpublished draft posts sitting idle',
        'Images Missing Alt Text'     => 'Images are invisible to Google\'s search engine',
        'HTTPS / SSL'                 => 'Your site security (HTTPS)',
        'Search Engine Visibility'    => 'Google can find and index your site',
        'XML Sitemap'                 => 'Google\'s map to your site pages',
        'Robots.txt'                  => 'Your robots file may be blocking search engines',
        'Permalink Structure'         => 'Your page URL format',
        'WordPress Version'           => 'Your WordPress is up to date',
        'SEO Plugin'                  => 'An SEO plugin is installed',
        'Caching Plugin'              => 'A caching plugin to speed up your site',
        'Structured Data / Schema'    => 'Rich results markup for Google',
        'HTML Page Size'              => 'Your page is too heavy — slowing down load time',
        'Server Response Time (TTFB)' => 'Your server responds quickly to visitors',
        'Mobile Viewport Meta Tag'    => 'Your site works properly on mobile',
        'Open Graph Tags'             => 'How your site looks when shared on social media',
    ];
    return $map[ $label ] ?? $label;
}

// Impact + time estimates per check
function vx_impact_data( $label ) {
    $map = [
        'Homepage Title Tag'          => [ 'High',   '5 min'  ],
        'Homepage Meta Description'   => [ 'High',   '5 min'  ],
        'Homepage H1 Tag'             => [ 'High',   '2 min'  ],
        'Posts Missing SEO Title'     => [ 'High',   '15 min' ],
        'Posts Missing Meta Description' => [ 'Medium', '20 min' ],
        'Canonical Tag (Homepage)'    => [ 'High',   '10 min' ],
        'Thin Content Posts'          => [ 'Medium', '1–2 hrs'],
        'Posts Without Category'      => [ 'Low',    '5 min'  ],
        'Draft Posts'                 => [ 'Low',    '2 min'  ],
        'Images Missing Alt Text'     => [ 'High',   '20 min' ],
        'HTTPS / SSL'                 => [ 'High',   'Done'   ],
        'Search Engine Visibility'    => [ 'High',   'Done'   ],
        'XML Sitemap'                 => [ 'High',   'Auto'   ],
        'Robots.txt'                  => [ 'High',   '10 min' ],
        'Permalink Structure'         => [ 'Medium', '5 min'  ],
        'WordPress Version'           => [ 'Medium', '2 min'  ],
        'SEO Plugin'                  => [ 'High',   '5 min'  ],
        'Caching Plugin'              => [ 'Medium', '10 min' ],
        'Structured Data / Schema'    => [ 'Medium', '15 min' ],
        'HTML Page Size'              => [ 'Medium', '30 min' ],
        'Server Response Time (TTFB)' => [ 'High',   'Done'   ],
        'Mobile Viewport Meta Tag'    => [ 'High',   'Done'   ],
        'Open Graph Tags'             => [ 'Low',    '10 min' ],
    ];
    return $map[ $label ] ?? [ 'Medium', '10 min' ];
}
?>

<style>
.vx-impact-pill{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:var(--r-pill);border:1px solid transparent}
.vx-impact-high{background:var(--red-l);color:var(--red);border-color:var(--red-b)}
.vx-impact-medium{background:var(--amber-l);color:var(--amber);border-color:var(--amber-b)}
.vx-impact-low{background:var(--surface2);color:var(--muted);border-color:var(--border)}
.vx-time-pill{display:inline-flex;align-items:center;gap:4px;font-size:10.5px;font-weight:600;padding:3px 9px;border-radius:var(--r-pill);background:var(--blue-l);color:var(--blue);border:1px solid var(--blue-b)}
.vx-fix-preview{background:var(--green-l);border:1.5px solid var(--green-b);border-radius:var(--r-lg);padding:18px 24px;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
.vx-score-label-sub{font-size:12.5px;color:var(--ink3);margin-top:6px;line-height:1.5;text-align:center;max-width:130px}
</style>

<!-- PAGE HEADER -->
<div class="vx-page-header vx-report-head">
  <div>
    <h1 class="vx-page-title">Health Report</h1>
    <p class="vx-page-sub">
      <span class="dashicons dashicons-admin-site"></span>
      <strong><?php echo esc_html( $domain ); ?></strong>
      <span class="vx-muted"> · <?php echo esc_html( $report['timestamp'] ); ?></span>
    </p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <button onclick="window.print()" class="vx-btn-outline"><span class="dashicons dashicons-printer"></span> Print</button>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=vixion-seo-new-audit' ) ); ?>" class="vx-btn-primary">+ New Audit</a>
  </div>
</div>

<!-- SUMMARY ROW -->
<div class="vx-summary-row">

  <!-- Score ring -->
  <div class="vx-card vx-score-card">
    <div class="vx-score-ring vx-ring-<?php echo esc_attr( $cls ); ?>">
      <strong><?php echo esc_html( $score ); ?></strong>
      <span>/100</span>
    </div>
    <p class="vx-score-lbl vx-c-<?php echo esc_attr( $cls ); ?>"><?php echo esc_html( vx_score_label( $score ) ); ?></p>
    <p class="vx-score-sub">Website Health Score</p>
    <?php
    if ( $score >= 75 )      $score_msg = 'Your site is in good shape. Keep it up.';
    elseif ( $score >= 50 )  $score_msg = 'A few important issues are affecting your visibility.';
    else                     $score_msg = 'Your site needs urgent attention to rank well.';
    ?>
    <p class="vx-score-label-sub"><?php echo esc_html( $score_msg ); ?></p>
  </div>

  <!-- Issue breakdown -->
  <div class="vx-card vx-issues-card">
    <div class="vx-issues-top">
      <div class="vx-issue-col vx-issue-fail">
        <strong><?php echo esc_html( $fail ); ?></strong>
        <span>Critical</span>
        <small>Must fix now</small>
      </div>
      <div class="vx-issue-sep"></div>
      <div class="vx-issue-col vx-issue-warn">
        <strong><?php echo esc_html( $warn ); ?></strong>
        <span>Warnings</span>
        <small>Should improve</small>
      </div>
      <div class="vx-issue-sep"></div>
      <div class="vx-issue-col vx-issue-pass">
        <strong><?php echo esc_html( $pass ); ?></strong>
        <span>Passed</span>
        <small><?php echo $pass > 0 ? 'Good foundation ✓' : 'Keep working'; ?></small>
      </div>
    </div>
    <?php if ( $fail > 0 ) : ?>
    <div class="vx-issues-action">
      👉 Fix <?php echo esc_html( $fail ); ?> critical issue<?php echo $fail !== 1 ? 's' : ''; ?> first to improve your score quickly.
    </div>
    <?php endif; ?>
  </div>

  <!-- Site snapshot -->
  <div class="vx-card">
    <h3 class="vx-card-title">Site Snapshot</h3>
    <div class="vx-snaps">
      <?php
      $snaps = [
        'WordPress'    => $report['wp_version'] ?? get_bloginfo('version'),
        'Theme'        => $report['theme'] ?? wp_get_theme()->get('Name'),
        'SEO Plugin'   => $report['seo_plugin'] ?? 'None',
        'Cache Plugin' => $report['cache_plugin'] ?? 'None',
        'Total Posts'  => number_format( $stats['total_posts'] ?? 0 ),
        'Total Pages'  => number_format( $stats['total_pages'] ?? 0 ),
        'Images'       => number_format( $stats['total_images'] ?? 0 ),
        'TTFB'         => ( $stats['ttfb_ms'] ?? 0 ) . 'ms',
        'HTML Size'    => ( $stats['html_size_kb'] ?? 0 ) . ' KB',
        'Plugins'      => ( $stats['plugin_count'] ?? 0 ) . ' active',
      ];
      foreach ( $snaps as $k => $v ) : ?>
        <div class="vx-snap-row">
          <span><?php echo esc_html( $k ); ?></span>
          <strong><?php echo esc_html( $v ); ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- "IF YOU FIX THESE" IMPROVEMENT PREVIEW -->
<?php if ( $fail > 0 || $warn > 0 ) : ?>
<div class="vx-fix-preview">
  <span style="font-size:28px;flex-shrink:0;">📈</span>
  <div style="flex:1;">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--green);margin-bottom:5px;">Fix Potential</div>
    <p style="margin:0;font-size:14px;font-weight:700;color:var(--ink);line-height:1.4;">
      If you fix the <?php echo esc_html( $fail ); ?> critical issue<?php echo $fail!==1?'s':''; ?>, your score could reach
      <span style="color:var(--green);font-size:18px;"> <?php echo esc_html( $est_score ); ?>+</span>
    </p>
    <p style="margin:6px 0 0;font-size:13px;color:var(--ink3);">Better Google understanding · More pages indexed · Higher visibility in search results</p>
  </div>
  <a href="#vx-checks" class="vx-btn-primary" style="flex-shrink:0;">View Issues Below ↓</a>
</div>
<?php endif; ?>

<!-- ENCOURAGEMENT for passed checks -->
<?php if ( $pass > 0 ) : ?>
<div style="display:flex;align-items:center;gap:12px;background:var(--green-l);border:1px solid var(--green-b);border-radius:var(--r);padding:12px 18px;">
  <span style="font-size:20px;flex-shrink:0;">🌟</span>
  <p style="margin:0;font-size:13px;color:var(--green);font-weight:600;line-height:1.5;">
    <strong style="color:var(--ink2);"><?php echo esc_html($pass); ?> things are already working well</strong> — that's a solid foundation to build on. Focus on the issues below to push your score higher.
  </p>
</div>
<?php endif; ?>

<!-- AI ACTION PLAN -->
<?php if ( ! empty( $report['recs'] ) ) : ?>
<div class="vx-ai-card" id="vx-action-plan">
  <div class="vx-ai-head">
    <span class="vx-ai-badge">🧠 AI Action Plan</span>
    <p>Here's what's hurting <strong><?php echo esc_html( $domain ); ?></strong> and how to fix it — in order of impact</p>
  </div>
  <div class="vx-ai-list">
    <?php foreach ( $report['recs'] as $i => $rec ) :
      [ $impact, $time ] = vx_impact_data( $rec['label'] );
      $impact_cls = strtolower( $impact );
      // Human-first headline
      $human_label = vx_human_label( $rec['label'] );
      $is_human    = $human_label !== $rec['label'];
    ?>
      <div class="vx-ai-item vx-ai-<?php echo esc_attr( $rec['priority'] ); ?>">
        <div class="vx-ai-num"><?php echo str_pad( $i + 1, 2, '0', STR_PAD_LEFT ); ?></div>
        <div class="vx-ai-body">
          <div class="vx-ai-top" style="flex-wrap:wrap;gap:8px;margin-bottom:6px;">
            <!-- Human headline first -->
            <?php if ( $rec['priority'] === 'high' ) : ?>
              <strong style="color:#fff;">❌ <?php echo esc_html( $human_label ); ?></strong>
            <?php else : ?>
              <strong style="color:#fff;">⚠ <?php echo esc_html( $human_label ); ?></strong>
            <?php endif; ?>
            <!-- Technical name as subtext if different -->
            <?php if ( $is_human ) : ?>
              <span class="vx-ai-cat"><?php echo esc_html( $rec['label'] ); ?></span>
            <?php endif; ?>
            <!-- Impact + time pills -->
            <span class="vx-impact-pill vx-impact-<?php echo esc_attr($impact_cls); ?>">Impact: <?php echo esc_html($impact); ?></span>
            <span class="vx-time-pill">⏱ <?php echo esc_html($time); ?></span>
          </div>
          <p><?php echo esc_html( $rec['fix'] ); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- DETAILED CHECKS -->
<div class="vx-checks-wrap" id="vx-checks">
  <div class="vx-checks-top">
    <div>
      <h2 class="vx-section-title">What We Found</h2>
      <p style="margin:4px 0 0;font-size:13px;color:var(--muted);">Here's what's hurting your site — and what's already working.</p>
    </div>
    <div class="vx-cat-tabs">
      <button class="vx-tab active" data-cat="All">All <span class="vx-tab-count"><?php echo count($report['checks']); ?></span></button>
      <?php
      $cat_counts = array_count_values( array_column( $report['checks'], 'category' ) );
      foreach ( $cats as $cat ) : ?>
        <button class="vx-tab" data-cat="<?php echo esc_attr( $cat ); ?>">
          <?php echo esc_html( $cat ); ?>
          <span class="vx-tab-count"><?php echo esc_html( $cat_counts[ $cat ] ?? 0 ); ?></span>
        </button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="vx-checks-list" id="vx-checks-list">
    <?php foreach ( $report['checks'] as $chk ) :
      $s          = $chk['status'];
      $ico        = $s === 'pass' ? '✓' : ( $s === 'warn' ? '⚠' : '✕' );
      $lbl        = $s === 'pass' ? 'Passed' : ( $s === 'warn' ? 'Warning' : 'Critical' );
      $human_lbl  = vx_human_label( $chk['label'] );
      $is_human   = $human_lbl !== $chk['label'];
      [ $impact, $time ] = vx_impact_data( $chk['label'] );
      $impact_cls = strtolower($impact);
    ?>
      <div class="vx-check-card vx-chk-<?php echo esc_attr($s); ?>" data-cat="<?php echo esc_attr($chk['category']); ?>">

        <!-- Header row -->
        <div class="vx-check-header" onclick="this.parentElement.classList.toggle('open')">
          <span class="vx-chk-icon vx-chk-icon-<?php echo esc_attr($s); ?>"><?php echo $ico; ?></span>
          <div class="vx-chk-meta">
            <!-- Human label first -->
            <span class="vx-chk-label"><?php echo esc_html( $human_lbl ); ?></span>
            <!-- Technical name as subtext -->
            <?php if ( $is_human ) : ?>
              <span class="vx-chk-cat"><?php echo esc_html( $chk['label'] ); ?> · <?php echo esc_html( $chk['category'] ); ?></span>
            <?php else : ?>
              <span class="vx-chk-cat"><?php echo esc_html( $chk['category'] ); ?></span>
            <?php endif; ?>
          </div>
          <div class="vx-chk-current">
            <span class="vx-chk-current-val"><?php echo esc_html( $chk['current'] ); ?></span>
          </div>
          <!-- Impact pill only for non-pass -->
          <?php if ( $s !== 'pass' ) : ?>
            <span class="vx-impact-pill vx-impact-<?php echo esc_attr($impact_cls); ?>" style="margin-right:4px;">Impact: <?php echo esc_html($impact); ?></span>
          <?php endif; ?>
          <span class="vx-status-pill vx-status-<?php echo esc_attr($s); ?>"><?php echo esc_html($lbl); ?></span>
          <span class="vx-chevron dashicons dashicons-arrow-down-alt2"></span>
        </div>

        <!-- Expandable detail -->
        <div class="vx-check-body">
          <div class="vx-check-body-inner">

            <div class="vx-detail-block vx-detail-current">
              <div class="vx-detail-tag">📍 Current State</div>
              <p><?php echo esc_html( $chk['current'] ); ?></p>
            </div>

            <?php if ( $s !== 'pass' && ! empty( $chk['issue'] ) ) : ?>
            <div class="vx-detail-block vx-detail-issue">
              <div class="vx-detail-tag">⚠ Why This Matters</div>
              <p><?php echo esc_html( $chk['issue'] ); ?></p>
            </div>
            <?php endif; ?>

            <?php if ( $s !== 'pass' && ! empty( $chk['fix'] ) ) : ?>
            <div class="vx-detail-block vx-detail-fix">
              <div class="vx-detail-tag">🔧 How to Fix It <span style="float:right;font-weight:600;">⏱ <?php echo esc_html($time); ?></span></div>
              <p><?php echo esc_html( $chk['fix'] ); ?></p>
            </div>
            <?php endif; ?>

            <?php if ( $s === 'pass' ) : ?>
            <div class="vx-detail-block vx-detail-ok">
              <div class="vx-detail-tag">✓ All Good</div>
              <p>This is working correctly. No action needed — keep it this way.</p>
            </div>
            <?php endif; ?>

          </div>
        </div>

      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
(function($){
  $('.vx-tab').on('click', function(){
    const cat = $(this).data('cat');
    $('.vx-tab').removeClass('active');
    $(this).addClass('active');
    if ( cat === 'All' ) {
      $('.vx-check-card').show();
    } else {
      $('.vx-check-card').hide().filter('[data-cat="' + cat + '"]').show();
    }
  });

  // Auto-open critical checks on page load
  $('.vx-chk-fail').first().addClass('open');
})(jQuery);
</script>

<?php require __DIR__ . '/layout-footer.php'; ?>
