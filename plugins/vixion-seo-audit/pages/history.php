<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

$history = vx_db_get_history( 50 );
$domain  = parse_url( get_site_url(), PHP_URL_HOST );
$count   = count( $history );

// ── Progress analytics ────────────────────────────────────────
$latest       = $history[0]  ?? null;
$previous     = $history[1]  ?? null;
$oldest       = $history[ $count - 1 ] ?? null;
$score_latest = $latest   ? (int) $latest->score   : null;
$score_prev   = $previous ? (int) $previous->score : null;
$score_oldest = $oldest   ? (int) $oldest->score   : null;

// Trend vs previous audit
$delta_prev = ( $score_latest !== null && $score_prev !== null ) ? $score_latest - $score_prev : null;

// Overall trend vs first audit
$delta_all  = ( $score_latest !== null && $score_oldest !== null && $count > 1 )
              ? $score_latest - $score_oldest : null;

// Best score ever
$best_score = $count ? max( array_column( (array) $history, 'score' ) ) : null;

// Days since last audit
$days_since = null;
if ( $latest ) {
    $ts = strtotime( $latest->created_at );
    if ( $ts ) $days_since = (int) floor( ( time() - $ts ) / DAY_IN_SECONDS );
}

// Progress summary label
function vx_trend_label( $delta ) {
    if ( $delta === null )  return [ '→', 'var(--muted)',  'No data yet'    ];
    if ( $delta > 0 )       return [ '↑', 'var(--green)',  'Improving'      ];
    if ( $delta < 0 )       return [ '↓', 'var(--red)',    'Declining'      ];
    return                         [ '→', 'var(--muted)',  'Stable'         ];
}
[ $arrow, $t_color, $t_word ] = vx_trend_label( $delta_prev );

// Check if recent runs are identical (collapse hint)
$streak_same = 0;
if ( $count >= 2 ) {
    for ( $i = 0; $i < $count - 1; $i++ ) {
        if ( $history[$i]->score == $history[$i+1]->score &&
             $history[$i]->fail  == $history[$i+1]->fail  &&
             $history[$i]->warn  == $history[$i+1]->warn ) {
            $streak_same++;
        } else break;
    }
}
?>

<style>
/* Trend badges inline with score */
.vx-trend-up  {display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:700;color:var(--green);background:var(--green-l);border:1px solid var(--green-b);border-radius:var(--r-pill);padding:2px 8px}
.vx-trend-dn  {display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:700;color:var(--red);background:var(--red-l);border:1px solid var(--red-b);border-radius:var(--r-pill);padding:2px 8px}
.vx-trend-eq  {display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:600;color:var(--muted);background:var(--surface2);border:1px solid var(--border);border-radius:var(--r-pill);padding:2px 8px}
/* Compare modal */
.vx-compare-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;align-items:center;justify-content:center}
.vx-compare-modal.open{display:flex}
.vx-compare-box{background:var(--surface);border-radius:var(--r-lg);padding:28px 32px;max-width:560px;width:94%;box-shadow:var(--sh-lg);position:relative}
.vx-compare-close{position:absolute;top:14px;right:16px;font-size:22px;cursor:pointer;color:var(--muted);background:none;border:none;line-height:1}
.vx-compare-grid{display:grid;grid-template-columns:1fr auto 1fr;gap:12px;align-items:center;margin-top:18px}
.vx-compare-col{background:var(--surface2);border:1px solid var(--border);border-radius:var(--r);padding:16px}
.vx-compare-col h4{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin:0 0 12px}
.vx-compare-row{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px}
.vx-compare-row:last-child{border-bottom:none}
.vx-compare-arrow{font-size:22px;color:var(--muted);text-align:center}
.vx-diff-good{color:var(--green);font-weight:700}
.vx-diff-bad {color:var(--red);font-weight:700}
/* Collapse notice */
.vx-same-notice{background:var(--surface2);border:1px solid var(--border);border-radius:var(--r);padding:10px 16px;font-size:12.5px;color:var(--muted);font-style:italic;text-align:center}
/* Spark mini chart in summary */
.vx-spark{display:flex;align-items:flex-end;gap:2px;height:32px}
.vx-spark-bar{width:6px;border-radius:2px 2px 0 0;background:var(--ink);opacity:.18;min-height:2px}
.vx-spark-bar.latest{opacity:1}
</style>

<!-- PAGE HEADER -->
<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title">Health History</h1>
    <p class="vx-page-sub">Track how your website health improves over time · <?php echo esc_html($count); ?> scan<?php echo $count!==1?'s':''; ?> recorded</p>
  </div>
  <?php if ( $history ) : ?>
  <div style="display:flex;gap:10px;align-items:center;">
    <?php if ( $count >= 2 ) : ?>
      <button id="vx-compare-btn" class="vx-btn-outline">⇄ Compare Last Two</button>
    <?php endif; ?>
    <button id="vx-clear" class="vx-btn-outline vx-btn-danger">
      <span class="dashicons dashicons-trash"></span> Clear All
    </button>
  </div>
  <?php endif; ?>
</div>

<?php if ( empty($history) ) : ?>
  <div class="vx-empty-page">
    <span style="font-size:56px">📈</span>
    <h2>No History Yet</h2>
    <p>Run your first health check to start tracking your progress over time.</p>
    <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-new-audit')); ?>" class="vx-btn-primary">Run First Health Check →</a>
  </div>
<?php else : ?>

<!-- ── RETENTION REMINDER ─────────────────────────────────── -->
<?php if ( $days_since !== null && $days_since >= 7 ) : ?>
<div style="display:flex;align-items:center;gap:12px;background:var(--amber-l);border:1.5px solid var(--amber-b);border-radius:var(--r);padding:13px 18px;">
  <span style="font-size:20px;flex-shrink:0;">⏰</span>
  <p style="margin:0;font-size:13px;color:var(--amber);font-weight:600;line-height:1.5;">
    <strong style="color:var(--ink2);">It's been <?php echo esc_html($days_since); ?> days since your last check.</strong>
    Run a new health scan to see if anything has changed.
  </p>
  <a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-new-audit')); ?>"
     class="vx-btn-primary" style="flex-shrink:0;white-space:nowrap;">Run New Scan →</a>
</div>
<?php endif; ?>

<!-- ── PROGRESS SUMMARY BOX ───────────────────────────────── -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">

  <!-- Current score -->
  <div class="vx-card" style="text-align:center;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:8px;">Current Score</div>
    <strong style="font-size:44px;font-weight:800;color:<?php echo vx_score_class($score_latest)==='good'?'var(--green)':(vx_score_class($score_latest)==='warn'?'var(--amber)':'var(--red)'); ?>;line-height:1;"><?php echo esc_html($score_latest); ?></strong>
    <div style="font-size:12px;color:var(--muted);margin-top:4px;">/ 100</div>
  </div>

  <!-- Trend -->
  <div class="vx-card" style="text-align:center;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:8px;">Trend</div>
    <strong style="font-size:44px;font-weight:800;color:<?php echo $t_color; ?>;line-height:1;"><?php echo $arrow; ?></strong>
    <div style="font-size:12px;font-weight:600;color:<?php echo $t_color; ?>;margin-top:4px;"><?php echo esc_html($t_word); ?>
      <?php if ( $delta_prev !== null && $delta_prev !== 0 ) echo '(' . ($delta_prev > 0 ? '+' : '') . esc_html($delta_prev) . ')'; ?>
    </div>
  </div>

  <!-- Overall change -->
  <div class="vx-card" style="text-align:center;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:8px;">Since First Scan</div>
    <?php if ( $delta_all !== null ) :
      $da_color = $delta_all > 0 ? 'var(--green)' : ($delta_all < 0 ? 'var(--red)' : 'var(--muted)');
    ?>
      <strong style="font-size:44px;font-weight:800;color:<?php echo $da_color; ?>;line-height:1;"><?php echo ($delta_all>0?'+':'').esc_html($delta_all); ?></strong>
      <div style="font-size:12px;color:var(--muted);margin-top:4px;"><?php echo $delta_all>0?'points gained':($delta_all<0?'points lost':'no change'); ?></div>
    <?php else : ?>
      <strong style="font-size:44px;font-weight:800;color:var(--muted);line-height:1;">—</strong>
      <div style="font-size:12px;color:var(--muted);margin-top:4px;">need 2+ scans</div>
    <?php endif; ?>
  </div>

  <!-- Best score + spark chart -->
  <div class="vx-card" style="text-align:center;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:8px;">Best Score</div>
    <strong style="font-size:44px;font-weight:800;color:var(--ink);line-height:1;"><?php echo esc_html($best_score); ?></strong>
    <div style="font-size:12px;color:var(--muted);margin-top:4px;">all time</div>
    <?php
    // Spark chart (last 8 scores)
    $spark_scores = array_reverse( array_slice( (array) $history, 0, 8 ) );
    $spark_max    = max( array_column( $spark_scores, 'score' ) ) ?: 1;
    ?>
    <div class="vx-spark" style="justify-content:center;margin-top:8px;">
      <?php foreach ( $spark_scores as $si => $sr ) :
        $h = max( 2, round( (int)$sr->score / $spark_max * 30 ) );
        $is_last = ( $si === count($spark_scores) - 1 );
      ?>
        <div class="vx-spark-bar <?php echo $is_last ? 'latest' : ''; ?>" style="height:<?php echo $h; ?>px;"></div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- ── COLLAPSE NOTICE if streak identical ─────────────────── -->
<?php if ( $streak_same >= 3 ) : ?>
<div class="vx-same-notice">
  📊 Your last <?php echo esc_html( $streak_same + 1 ); ?> scans show no change — score is steady at <?php echo esc_html($score_latest); ?>. Fix critical issues to see your score move.
</div>
<?php endif; ?>

<!-- ── HISTORY TABLE ─────────────────────────────────────── -->
<div class="vx-card" style="padding:0;overflow:hidden;">
  <table class="vx-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Site</th>
        <th>Score</th>
        <th>Change</th>
        <th>Critical</th>
        <th>Warnings</th>
        <th>Passed</th>
        <th>Date</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ( $history as $i => $row ) :
        $cls       = vx_score_class( $row->score );
        $next_row  = $history[ $i + 1 ] ?? null; // older audit
        $delta     = $next_row ? ( (int)$row->score - (int)$next_row->score ) : null;
        $fail_prev = $next_row ? (int)$next_row->fail : null;
        $warn_prev = $next_row ? (int)$next_row->warn : null;

        if ( $delta === null ) {
            $trend_badge = '<span class="vx-trend-eq">First scan</span>';
        } elseif ( $delta > 0 ) {
            $trend_badge = '<span class="vx-trend-up">↑ +' . $delta . '</span>';
        } elseif ( $delta < 0 ) {
            $trend_badge = '<span class="vx-trend-dn">↓ ' . $delta . '</span>';
        } else {
            $trend_badge = '<span class="vx-trend-eq">→ No change</span>';
        }

        // Critical issues change note
        $issue_note = '';
        if ( $fail_prev !== null && (int)$row->fail < $fail_prev ) {
            $issue_note = '<div style="font-size:11px;color:var(--green);font-weight:600;margin-top:4px;">✓ Critical reduced ' . $fail_prev . '→' . $row->fail . '</div>';
        } elseif ( $fail_prev !== null && (int)$row->fail > $fail_prev ) {
            $issue_note = '<div style="font-size:11px;color:var(--red);font-weight:600;margin-top:4px;">↑ Critical grew ' . $fail_prev . '→' . $row->fail . '</div>';
        }
      ?>
        <tr data-score="<?php echo esc_attr($row->score); ?>">
          <td class="vx-muted" style="font-size:12px;"><?php echo esc_html( $count - $i ); ?></td>
          <td><strong style="font-size:13px;"><?php echo esc_html( $domain ); ?></strong></td>
          <td>
            <span class="vx-score-pill vx-score-bg-<?php echo esc_attr($cls); ?>"><?php echo esc_html($row->score); ?></span>
          </td>
          <td><?php echo $trend_badge; ?><?php echo $issue_note; ?></td>
          <td><strong class="vx-c-poor"><?php echo esc_html($row->fail); ?></strong></td>
          <td><strong class="vx-c-warn"><?php echo esc_html($row->warn); ?></strong></td>
          <td><strong class="vx-c-good"><?php echo esc_html($row->pass); ?></strong></td>
          <td class="vx-muted" style="font-size:12px;white-space:nowrap;"><?php echo esc_html($row->created_at); ?></td>
          <td><a href="<?php echo esc_url(admin_url('admin.php?page=vixion-seo-report')); ?>" class="vx-btn-sm">View →</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ── COMPARE MODAL ─────────────────────────────────────── -->
<?php if ( $count >= 2 ) :
  $a = $history[0]; // latest
  $b = $history[1]; // previous
  $fields = [
    'Score'    => [ $a->score, $b->score, 'higher' ],
    'Critical' => [ $a->fail,  $b->fail,  'lower'  ],
    'Warnings' => [ $a->warn,  $b->warn,  'lower'  ],
    'Passed'   => [ $a->pass,  $b->pass,  'higher' ],
  ];
?>
<div class="vx-compare-modal" id="vx-compare-modal">
  <div class="vx-compare-box">
    <button class="vx-compare-close" id="vx-compare-close">✕</button>
    <h3 style="font-size:18px;font-weight:800;color:var(--ink);margin:0 0 4px;">Last Two Scans Compared</h3>
    <p style="font-size:13px;color:var(--muted);margin:0;">See exactly what changed between your last two health checks.</p>
    <div class="vx-compare-grid">

      <!-- Latest -->
      <div class="vx-compare-col">
        <h4>Latest Scan</h4>
        <div style="font-size:11px;color:var(--muted);margin-bottom:10px;"><?php echo esc_html($a->created_at); ?></div>
        <?php foreach ( $fields as $lbl => [ $va, $vb, $better ] ) : ?>
          <div class="vx-compare-row">
            <span style="color:var(--muted);"><?php echo esc_html($lbl); ?></span>
            <?php
            $diff = (int)$va - (int)$vb;
            $is_better = ( $better === 'higher' && $diff > 0 ) || ( $better === 'lower' && $diff < 0 );
            $is_worse  = ( $better === 'higher' && $diff < 0 ) || ( $better === 'lower' && $diff > 0 );
            $cls_val   = $is_better ? 'vx-diff-good' : ( $is_worse ? 'vx-diff-bad' : '' );
            ?>
            <strong class="<?php echo $cls_val; ?>"><?php echo esc_html($va); ?></strong>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Arrow -->
      <div class="vx-compare-arrow">vs</div>

      <!-- Previous -->
      <div class="vx-compare-col">
        <h4>Previous Scan</h4>
        <div style="font-size:11px;color:var(--muted);margin-bottom:10px;"><?php echo esc_html($b->created_at); ?></div>
        <?php foreach ( $fields as $lbl => [ $va, $vb, $better ] ) : ?>
          <div class="vx-compare-row">
            <span style="color:var(--muted);"><?php echo esc_html($lbl); ?></span>
            <strong><?php echo esc_html($vb); ?></strong>
          </div>
        <?php endforeach; ?>
      </div>

    </div>

    <!-- Summary sentence -->
    <?php
    $score_diff = (int)$a->score - (int)$b->score;
    $fail_diff  = (int)$a->fail  - (int)$b->fail;
    if ( $score_diff > 0 )       $summary = '✅ Your score improved by ' . $score_diff . ' point' . ($score_diff!==1?'s':'') . ' since the last scan.';
    elseif ( $score_diff < 0 )   $summary = '⚠ Your score dropped by ' . abs($score_diff) . ' point' . (abs($score_diff)!==1?'s':'') . '. Check what changed.';
    else                         $summary = '→ No score change. Fix critical issues to start improving.';
    if ( $fail_diff < 0 )        $summary .= ' Critical issues reduced from ' . $b->fail . ' → ' . $a->fail . '.';
    ?>
    <p style="margin:18px 0 0;font-size:13.5px;font-weight:600;color:var(--ink2);background:var(--surface2);border-radius:var(--r);padding:12px 16px;">
      <?php echo esc_html($summary); ?>
    </p>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
(function($){
  // Clear history
  $('#vx-clear').on('click', function(){
    if(!confirm('Delete all health history? This cannot be undone.')) return;
    $.post(vxSeo.ajaxUrl, {action:'vx_clear_history', nonce:vxSeo.nonce})
      .done(() => location.reload());
  });

  // Compare modal
  $('#vx-compare-btn').on('click', function(){
    $('#vx-compare-modal').addClass('open');
  });
  $('#vx-compare-close, #vx-compare-modal').on('click', function(e){
    if(e.target === this) $('#vx-compare-modal').removeClass('open');
  });
})(jQuery);
</script>

<?php require __DIR__ . '/layout-footer.php'; ?>
