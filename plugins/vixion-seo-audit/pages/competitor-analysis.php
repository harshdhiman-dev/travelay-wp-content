<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

$competitors = get_option( 'vx_competitors', [] );
$results     = get_option( 'vx_competitor_results', [] );
$last_run    = get_option( 'vx_competitor_last_run', '' );

$my_stats   = vx_db_get_stats();
$my_score   = $my_stats['last'] ? (int)$my_stats['last']->score : null;
$my_fail    = $my_stats['last'] ? (int)$my_stats['last']->fail  : 0;
$my_warn    = $my_stats['last'] ? (int)$my_stats['last']->warn  : 0;
$domain     = parse_url( get_site_url(), PHP_URL_HOST );

// Human-readable check labels (outcome > mechanism)
$check_labels = [
    'title_ok'       => 'Homepage title properly optimised',
    'desc_ok'        => 'Search result description written',
    'h1_ok'          => 'Main page heading present',
    'canonical'      => 'Duplicate page issue resolved',
    'og_tags'        => 'Social media sharing set up',
    'schema'         => 'Rich results markup added',
    'viewport'       => 'Works properly on mobile',
    'sitemap'        => 'Sitemap submitted to Google',
    'https'          => 'Site connection is secure',
    'fast'           => 'Server responds quickly',
    'good_html_size' => 'Page code is lean and fast',
    'good_content'   => 'Pages have enough content',
];

// Build valid results only
$valid_results = array_values( array_filter( $results, fn($r) => ! isset($r['error']) ) );
$comp_count    = count( $valid_results );

// Compute averages & strategic data
$avg_comp_score = $comp_count
    ? round( array_sum( array_column( $valid_results, 'score' ) ) / $comp_count )
    : null;

// Where you win / lose vs each competitor
$you_win = []; $you_lose = [];
if ( $my_score !== null && $comp_count ) {
    // Aggregate: my checks from last report
    $my_report = get_option( 'vx_last_report', null );
    $my_checks = [];
    if ( $my_report ) {
        foreach ( $my_report['checks'] as $chk ) {
            // Map label back to key
            foreach ( $check_labels as $k => $lbl ) {
                if ( $chk['label'] === $lbl || stripos( $lbl, substr($chk['label'],0,12) ) !== false ) {
                    $my_checks[$k] = ( $chk['status'] === 'pass' );
                }
            }
        }
    }

    foreach ( $check_labels as $key => $lbl ) {
        $i_pass  = $my_checks[$key] ?? false;
        // How many competitors pass this check
        $comp_pass_count = array_sum( array_map( fn($r) => (int)($r['checks'][$key] ?? false), $valid_results ) );
        $any_comp_pass   = $comp_pass_count > 0;
        $all_comp_fail   = $comp_pass_count === 0;

        if ( $i_pass && $all_comp_fail )        $you_win[]  = $lbl;
        if ( ! $i_pass && $any_comp_pass )       $you_lose[] = $lbl;
    }
}

// Opportunity line
$est_gain    = $my_fail * 3 + $my_warn;
$est_new_gap = $avg_comp_score !== null && $my_score !== null
    ? ( $my_score + $est_gain - $avg_comp_score ) : null;
?>

<style>
.vx-verdict-card{display:grid;grid-template-columns:auto 1fr auto;gap:24px;align-items:center;background:var(--ink);border-radius:var(--r-lg);padding:24px 28px;box-shadow:var(--sh-lg);position:relative;overflow:hidden}
.vx-verdict-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 85% 50%,rgba(255,255,255,.05),transparent 55%);pointer-events:none}
.vx-score-compare-block{display:flex;flex-direction:column;align-items:center;gap:4px}
.vx-score-big{font-size:52px;font-weight:800;line-height:1}
.vx-score-compare-lbl{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;opacity:.5;color:#fff}
.vx-gap-badge{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--r-pill);font-size:14px;font-weight:800;border:2px solid}
.vx-gap-ahead{background:var(--green-l);color:var(--green);border-color:var(--green-b)}
.vx-gap-behind{background:var(--red-l);color:var(--red);border-color:var(--red-b)}
.vx-gap-even{background:var(--surface2);color:var(--muted);border-color:var(--border)}
.vx-win-lose-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.vx-win-card{background:var(--green-l);border:1.5px solid var(--green-b);border-radius:var(--r-lg);padding:20px 22px}
.vx-lose-card{background:var(--red-l);border:1.5px solid var(--red-b);border-radius:var(--r-lg);padding:20px 22px}
.vx-wl-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px}
.vx-wl-title.win{color:var(--green)} .vx-wl-title.lose{color:var(--red)}
.vx-wl-item{display:flex;align-items:center;gap:9px;font-size:13px;font-weight:600;padding:6px 0;border-bottom:1px solid rgba(0,0,0,.06)}
.vx-wl-item:last-child{border-bottom:none}
.vx-adv-card{background:var(--ink);border-radius:var(--r-lg);padding:22px 26px;box-shadow:var(--sh-lg);position:relative;overflow:hidden}
.vx-adv-card::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 90% 15%,rgba(255,255,255,.04),transparent 55%);pointer-events:none}
.vx-adv-step{display:flex;align-items:flex-start;gap:14px;padding:12px 14px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);border-radius:10px}
.vx-adv-num{font-size:22px;font-weight:800;color:rgba(255,255,255,.15);line-height:1;flex-shrink:0;width:26px}
.vx-adv-body strong{font-size:13.5px;color:#fff;font-weight:700;display:block;margin-bottom:3px}
.vx-adv-body p{font-size:12.5px;color:rgba(255,255,255,.48);margin:0;line-height:1.5}
.vx-score-row{display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid var(--border)}
.vx-score-row:last-child{border-bottom:none}
.vx-score-row-label{font-size:13.5px;font-weight:700;width:200px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.vx-score-track{flex:1;background:var(--bg);border-radius:4px;height:10px;overflow:hidden}
.vx-score-fill{height:100%;border-radius:4px;transition:width .6s ease}
.vx-score-val{font-size:20px;font-weight:800;width:44px;text-align:right;flex-shrink:0}
.vx-gap-num{font-size:13px;font-weight:700;width:64px;text-align:right;flex-shrink:0}
@media(max-width:900px){.vx-win-lose-grid{grid-template-columns:1fr}.vx-verdict-card{grid-template-columns:1fr}}
</style>

<!-- PAGE HEADER -->
<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title">Your Site vs Competitors</h1>
    <p class="vx-page-sub">
      <?php echo esc_html(count($competitors)); ?> competitor<?php echo count($competitors)!==1?'s':''; ?> tracked
      <?php if ($last_run) echo ' · Last analysed: ' . esc_html(date('M j, g:ia', strtotime($last_run))); ?>
    </p>
  </div>
  <?php if (!empty($competitors)): ?>
  <?php endif; ?>
</div>

<?php if (isset($_GET['saved'])) echo '<div class="vx-notice vx-notice-ok">✓ Competitors saved.</div>'; ?>
<div id="vx-comp-msg" style="display:none;margin-bottom:4px;"></div>

<!-- COMPETITORS INPUT — top of page -->
<div class="vx-card">
  <h3 class="vx-card-title">Competitors <span style="font-size:13px;color:var(--muted);font-weight:600;"><?php echo count($competitors); ?>/10</span></h3>
  <p style="margin:-8px 0 14px;font-size:13px;color:var(--muted);">Add the URLs of websites ranking above you in Google search.</p>
  <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php wp_nonce_field('vx_save_competitors'); ?>
    <input type="hidden" name="action" value="vx_save_competitors" />
    <div class="vx-fg">
      <label class="vx-lbl">Competitor URLs — one per line</label>
      <textarea name="vx_competitors_list" class="vx-input" rows="6" style="resize:vertical;font-size:13px;" placeholder="https://competitor1.com&#10;https://competitor2.com"><?php echo esc_textarea(implode("\n",$competitors)); ?></textarea>
    </div>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <button type="submit" class="vx-btn-primary">Save Competitors →</button>
      <?php if (!empty($competitors)): ?>
        <button type="button" id="vx-run-competitor" class="vx-btn-outline">▶ Run Comparison</button>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if ( ! empty($valid_results) && $my_score !== null ) :

  // Verdict
  $gap = $my_score - $avg_comp_score;
  if ( $gap > 5 )       { $v_icon='🟢'; $v_word='You\'re Ahead'; $v_color='var(--green)'; $gap_cls='vx-gap-ahead'; }
  elseif ( $gap < -5 )  { $v_icon='🔴'; $v_word='You\'re Behind'; $v_color='var(--red)';   $gap_cls='vx-gap-behind'; }
  else                  { $v_icon='🟡'; $v_word='Neck and Neck'; $v_color='var(--amber)'; $gap_cls='vx-gap-even'; }

  $v_detail = '';
  if ( $gap > 5 )  $v_detail = 'You have a stronger technical foundation. Fix your remaining issues to make this lead permanent.';
  elseif ($gap < -5) $v_detail = 'Your competitor has an edge. Focus on the areas below where they beat you — close the gap fast.';
  else              $v_detail = 'Very close. Small improvements on the issues below could tip the balance in your favour.';
?>

<!-- ① STRATEGIC VERDICT -->
<div class="vx-verdict-card">
  <div style="position:relative;text-align:center;">
    <span style="font-size:22px;"><?php echo $v_icon; ?></span>
    <div style="font-size:15px;font-weight:800;color:#fff;margin-top:4px;white-space:nowrap;"><?php echo esc_html($v_word); ?></div>
  </div>
  <div style="position:relative;">
    <p style="margin:0 0 10px;font-size:14px;color:rgba(255,255,255,.55);line-height:1.6;">
      <strong style="color:#fff;">Your score: <?php echo $my_score; ?></strong> &nbsp;·&nbsp;
      Competitor average: <?php echo $avg_comp_score; ?>
    </p>
    <p style="margin:0;font-size:13px;color:rgba(255,255,255,.45);line-height:1.65;"><?php echo esc_html($v_detail); ?></p>
  </div>
  <div style="position:relative;flex-shrink:0;">
    <span class="vx-gap-badge <?php echo $gap_cls; ?>">
      <?php echo ($gap>=0?'+':'').esc_html($gap); ?> pts gap
    </span>
  </div>
</div>

<!-- ② WHERE YOU WIN / LOSE -->
<?php if ( ! empty($you_win) || ! empty($you_lose) ) : ?>
<div class="vx-win-lose-grid">

  <div class="vx-win-card">
    <div class="vx-wl-title win">✅ You're stronger here</div>
    <?php if ( empty($you_win) ) : ?>
      <p style="font-size:13px;color:var(--ink3);font-style:italic;">No exclusive wins yet — keep improving.</p>
    <?php else : foreach ( $you_win as $w ) : ?>
      <div class="vx-wl-item">
        <span style="color:var(--green);font-size:14px;">✓</span>
        <span style="color:var(--ink2);"><?php echo esc_html($w); ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="vx-lose-card">
    <div class="vx-wl-title lose">⚠ Competitor does this better</div>
    <?php if ( empty($you_lose) ) : ?>
      <p style="font-size:13px;color:var(--ink3);font-style:italic;">You're not losing any checks — great!</p>
    <?php else : foreach ( array_slice($you_lose,0,6) as $l ) : ?>
      <div class="vx-wl-item">
        <span style="color:var(--red);font-size:14px;">✕</span>
        <span style="color:var(--ink2);"><?php echo esc_html($l); ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

</div>
<?php endif; ?>

<!-- ③ OPPORTUNITY INDICATOR -->
<?php if ( $my_fail > 0 && $est_new_gap !== null ) : ?>
<div style="display:flex;align-items:center;gap:14px;background:var(--blue-l);border:1.5px solid var(--blue-b);border-radius:var(--r);padding:14px 20px;flex-wrap:wrap;">
  <span style="font-size:20px;flex-shrink:0;">⚡</span>
  <p style="margin:0;font-size:13.5px;font-weight:600;color:var(--blue);line-height:1.55;flex:1;">
    If you fix your <?php echo esc_html($my_fail); ?> critical issue<?php echo $my_fail!==1?'s':''; ?>, your score could increase by
    <strong style="color:var(--ink2);">~<?php echo esc_html($est_gain); ?> points</strong>
    <?php if ($est_new_gap > 0) echo '— widening your lead to <strong style="color:var(--ink2);">+'.esc_html($est_new_gap).'</strong> points.'; elseif($est_new_gap <= 0) echo '— enough to close this gap completely.'; ?>
  </p>
</div>
<?php endif; ?>

<!-- ④ QUICK ADVANTAGE PLAN -->
<?php if ( ! empty($you_lose) ) : ?>
<div class="vx-adv-card">
  <div style="position:relative;margin-bottom:16px;">
    <span style="display:inline-block;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);border-radius:var(--r-pill);padding:4px 13px;font-size:11px;font-weight:700;color:#fff;letter-spacing:.04em;">🎯 Quick Advantage Plan</span>
    <p style="margin:8px 0 0;font-size:13px;color:rgba(255,255,255,.45);position:relative;">Do these to widen the gap over your competitor</p>
  </div>
  <div style="position:relative;display:flex;flex-direction:column;gap:8px;">
    <?php foreach ( array_slice($you_lose,0,4) as $idx => $l ) : ?>
    <div class="vx-adv-step">
      <div class="vx-adv-num"><?php echo str_pad($idx+1,2,'0',STR_PAD_LEFT); ?></div>
      <div class="vx-adv-body">
        <strong><?php echo esc_html($l); ?></strong>
        <p>Your competitor passes this check. Fixing it will close the gap and may lift your score.</p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ⑤ SCORE COMPARISON — bold numbers, gap column, no "higher = better SEO" label -->
<div class="vx-card">
  <h3 class="vx-card-title" style="margin-bottom:16px;">Score Comparison</h3>

  <!-- Your site -->
  <div class="vx-score-row">
    <div class="vx-score-row-label" style="color:var(--ink);">✦ Your Website</div>
    <div class="vx-score-track">
      <div class="vx-score-fill" style="width:<?php echo $my_score; ?>%;background:var(--ink);"></div>
    </div>
    <div class="vx-score-val" style="color:var(--ink);"><?php echo $my_score; ?></div>
    <div class="vx-gap-num" style="color:var(--muted);">—</div>
  </div>

  <?php foreach ( $valid_results as $r ) :
    $host  = parse_url($r['url'], PHP_URL_HOST);
    $sc    = (int)$r['score'];
    $col   = $sc >= 75 ? 'var(--green)' : ( $sc >= 50 ? 'var(--amber)' : 'var(--red)' );
    $rdiff = $my_score - $sc;
    $diff_cls = $rdiff > 0 ? 'color:var(--green)' : ($rdiff < 0 ? 'color:var(--red)' : 'color:var(--muted)');
  ?>
  <div class="vx-score-row">
    <div class="vx-score-row-label"><?php echo esc_html($host); ?></div>
    <div class="vx-score-track">
      <div class="vx-score-fill" style="width:<?php echo $sc; ?>%;background:<?php echo $col; ?>;"></div>
    </div>
    <div class="vx-score-val" style="color:<?php echo $col; ?>"><?php echo $sc; ?></div>
    <div class="vx-gap-num" style="font-size:12px;<?php echo $diff_cls; ?>">
      <?php echo ($rdiff>=0?'+':'').esc_html($rdiff); ?> pts
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ⑥ CHECK TABLE — human labels, no technical rules in headings -->
<div class="vx-card">
  <h3 class="vx-card-title" style="margin-bottom:2px;">Check-by-Check Breakdown</h3>
  <p style="margin:0 0 16px;font-size:12.5px;color:var(--muted);">✓ = passing &nbsp; ✕ = failing &nbsp; Gaps show where to focus.</p>
  <div class="vx-table-wrap">
    <table class="vx-table">
      <thead>
        <tr>
          <th>What We Check</th>
          <?php foreach ($valid_results as $r) : ?>
            <th style="text-align:center;"><?php echo esc_html(parse_url($r['url'],PHP_URL_HOST)); ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($check_labels as $key => $label) : ?>
        <tr>
          <td><strong style="font-size:13px;"><?php echo esc_html($label); ?></strong></td>
          <?php foreach ($valid_results as $r) : ?>
            <td style="text-align:center;">
              <?php echo ($r['checks'][$key]??false)
                ? '<span style="color:var(--green);font-size:16px;font-weight:700;">✓</span>'
                : '<span style="color:var(--red);font-size:14px;font-weight:700;">✕</span>'; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>

      <!-- Technical details section -->
      <tr><td colspan="<?php echo 1 + count($valid_results); ?>" style="background:var(--surface2);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);padding:10px 16px;">Technical Details</td></tr>
      <?php foreach ([
        ['Server Speed (TTFB)', 'ttfb_ms',    'ms'],
        ['Page Code Size',      'html_size',   'KB'],
        ['Word Count',          'word_count',  ' words'],
      ] as [$lbl,$key,$unit]) : ?>
        <tr>
          <td style="color:var(--ink3);font-weight:600;"><?php echo esc_html($lbl); ?></td>
          <?php foreach ($valid_results as $r) : ?>
            <td style="text-align:center;font-size:13px;font-weight:700;"><?php echo number_format($r[$key]??0).$unit; ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif ( empty($competitors) ) : ?>

<!-- EMPTY STATE: setup instructions -->
<div class="vx-setup-card">
  <h3>How to beat your competitors</h3>
  <div class="vx-setup-steps">
    <div class="vx-setup-step"><div class="vx-step-num">1</div><div class="vx-step-body"><strong>Find who you're competing with</strong><p>Search your main keywords on Google. The sites that consistently rank above you are your competitors. Copy their URLs.</p></div></div>
    <div class="vx-setup-step"><div class="vx-step-num">2</div><div class="vx-step-body"><strong>Add their URLs below</strong><p>One URL per line, up to 10. Use the full URL including https://</p></div></div>
    <div class="vx-setup-step"><div class="vx-step-num">3</div><div class="vx-step-body"><strong>Run the comparison</strong><p>We run the same 12 health checks on their site and yours — showing exactly where they beat you.</p></div></div>
    <div class="vx-setup-step"><div class="vx-step-num">4</div><div class="vx-step-body"><strong>Get your advantage plan</strong><p>We tell you exactly what to fix to close the gap and get ahead.</p></div></div>
  </div>
</div>

<?php else : ?>

<div class="vx-card" style="text-align:center;padding:40px;">
  <span style="font-size:36px;display:block;margin-bottom:12px;">⚙️</span>
  <p style="font-size:14px;color:var(--muted);">Competitors saved. Click <strong>Run Comparison</strong> to see where you stand.</p>
</div>

<?php endif; ?>



<script>
document.getElementById('vx-run-competitor')?.addEventListener('click', function(){
  var btn = this;
  var msg = document.getElementById('vx-comp-msg');
  btn.textContent = '⏳ Analysing…';
  btn.disabled = true;
  msg.style.cssText = 'display:block;padding:12px 16px;background:var(--green-l);border:1px solid var(--green-b);border-radius:10px;font-size:13px;color:var(--green);font-weight:700;';
  msg.textContent = 'Fetching and comparing <?php echo count($competitors); ?> competitor site<?php echo count($competitors)!==1?"s":""; ?>…';

  var fd = new FormData();
  fd.append('action','vx_run_competitor_audit');
  fd.append('nonce', vxSeo.nonce);
  fetch(vxSeo.ajaxUrl, {method:'POST',body:fd})
    .then(r=>r.json())
    .then(res=>{
      if(res.success){ msg.textContent='✓ Comparison complete!'; setTimeout(()=>location.reload(),800); }
      else { msg.style.background='var(--red-l)'; msg.style.color='var(--red)'; msg.textContent='❌ '+res.data; btn.textContent='▶ Run Comparison'; btn.disabled=false; }
    });
});
</script>

<?php require __DIR__ . '/layout-footer.php'; ?>
