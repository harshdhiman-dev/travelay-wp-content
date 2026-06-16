<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

$sc_site_url  = get_option('vx_sc_site_url','');
$sc_json_key  = get_option('vx_sc_json_key','');
$is_configured = $sc_site_url && ($sc_json_key || get_option('vx_ga_json_key',''));

$data  = null;
$error = null;
if ($is_configured) {
    $data = vx_sc_fetch_data();
    if (is_wp_error($data)) { $error=$data->get_error_message(); $data=null; }
}

$totals = ['clicks'=>0,'impressions'=>0,'ctr'=>0,'position'=>0];
$prev_sc = ['clicks'=>0,'impressions'=>0,'ctr'=>0,'position'=>0];
$chart  = [];
if ($data) {
    $cnt_cur = $cnt_prev = 0;
    foreach ($data['summary']['rows']??[] as $row) {
        // SC API returns date as keys[0]; we split by comparing to date ranges
        // We pass two date ranges in vx_sc_fetch_data — rows are interleaved by date
        // Simplest: store all rows, then split by date comparison after
        $totals['clicks']      += (int)($row['clicks']??0);
        $totals['impressions'] += (int)($row['impressions']??0);
        $totals['ctr']         += (float)($row['ctr']??0);
        $totals['position']    += (float)($row['position']??0);
        $chart[] = ['date'=>$row['keys'][0]??'','clicks'=>(int)($row['clicks']??0)];
        $cnt_cur++;
    }
    // Previous period from separate key if available
    foreach ($data['summary_prev']['rows']??[] as $row) {
        $prev_sc['clicks']      += (int)($row['clicks']??0);
        $prev_sc['impressions'] += (int)($row['impressions']??0);
        $prev_sc['ctr']         += (float)($row['ctr']??0);
        $prev_sc['position']    += (float)($row['position']??0);
        $cnt_prev++;
    }
    if ($cnt_cur>0)  { $totals['ctr']=round($totals['ctr']/$cnt_cur*100,2); $totals['position']=round($totals['position']/$cnt_cur,1); }
    if ($cnt_prev>0) { $prev_sc['ctr']=round($prev_sc['ctr']/$cnt_prev*100,2); $prev_sc['position']=round($prev_sc['position']/$cnt_prev,1); }
}

if (!function_exists('vx_delta')) {
function vx_delta( $cur, $prev, $invert = false ) {
    if ( $prev <= 0 ) return [ '', '', 'neutral' ];
    $pct = round( ($cur - $prev) / $prev * 100, 1 );
    if ( abs($pct) < 1 ) return [ '', '~0%', 'neutral' ];
    $up    = $pct > 0;
    $good  = $invert ? !$up : $up;
    $arrow = $up ? '↑' : '↓';
    $sign  = $up ? '+' : '';
    $color = $good ? 'var(--green)' : 'var(--red)';
    $html  = "<span style='font-size:11px;font-weight:700;color:{$color};white-space:nowrap;'>{$arrow} {$sign}{$pct}% <span style='font-weight:500;opacity:.75;'>vs last month</span></span>";
    return [ $html, "{$sign}{$pct}%", $good ? 'good' : 'bad' ];
}
}

$d_clicks      = vx_delta( $totals['clicks'],      $prev_sc['clicks'] );
$d_impressions = vx_delta( $totals['impressions'], $prev_sc['impressions'] );
$d_ctr         = vx_delta( $totals['ctr'],         $prev_sc['ctr'] );
$d_position    = vx_delta( $totals['position'],    $prev_sc['position'], true ); // lower = better

$conn_class = $is_configured?($data?'vx-conn-yes':'vx-conn-err'):'vx-conn-no';
$conn_text  = $is_configured?($data?'✓ Connected':'⚠ Error'):'⚠ Not Connected';

// Doctor banner
$issues = [];
if ($totals['position']>20)         $issues[] = 'your pages are buried on page 2+';
if ($totals['ctr']<2 && $data)      $issues[] = 'very few people click your results';
if ($totals['impressions']<100 && $data) $issues[] = 'Google rarely shows your site';

if (empty($issues))         $dx_text="Google is finding and showing your site well. Push your best pages to position 1–3 for maximum traffic.";
elseif (count($issues)===1) $dx_text="Mostly healthy, but: ".implode('',$issues).". Scroll down to see which pages need work.";
else                        $dx_text="Google visibility needs work: ".implode(' and ',$issues).". Run a full SEO audit and fix technical issues first.";

$d_color = empty($issues)?'var(--green)':(count($issues)>=2?'var(--red)':'var(--amber)');
$d_bg    = empty($issues)?'var(--green-l)':(count($issues)>=2?'var(--red-l)':'var(--amber-l)');
$d_bd    = empty($issues)?'var(--green-b)':(count($issues)>=2?'var(--red-b)':'var(--amber-b)');
$d_icon  = empty($issues)?'🟢':(count($issues)>=2?'🔴':'🟡');

// 🎯 ONE priority focus
if ($totals['impressions'] < 50) {
    $sc_focus_obs    = "Google is barely showing your site in search results.";
    $sc_focus_action = "Run a full SEO audit and make sure your key pages are indexed — check Google Search Console's Coverage report for errors.";
} elseif ($totals['position'] > 20) {
    $sc_focus_obs    = "Your pages rank on page 2 or deeper on average — almost no one sees them.";
    $sc_focus_action = "Pick your single most important page and rewrite it with a stronger title, clearer headings, and more specific content.";
} elseif ($totals['ctr'] < 2 && $totals['impressions'] >= 50) {
    $sc_focus_obs    = "Google shows your pages, but people rarely click — your titles aren't compelling enough.";
    $sc_focus_action = "Rewrite the page title and meta description of your top 3 ranking pages to be more specific and benefit-focused.";
} elseif ($totals['position'] > 10 && $totals['position'] <= 20) {
    $sc_focus_obs    = "You're ranking on page 2 for many queries — just outside where clicks happen.";
    $sc_focus_action = "Add more depth to your near-ranking pages: longer content, FAQs, and internal links from your stronger pages.";
} elseif ($totals['clicks'] < 20 && $totals['impressions'] >= 200) {
    $sc_focus_obs    = "You have good impressions but very few clicks — a gap between visibility and action.";
    $sc_focus_action = "Update meta descriptions on your top 5 ranking pages to include a clear reason to click.";
} else {
    $sc_focus_obs    = "Your Google visibility is performing well overall.";
    $sc_focus_action = "Keep publishing content — target long-tail keywords where you already rank in positions 4–10 to push them to the top 3.";
}
?>

<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title">Google Visibility</h1>
    <p class="vx-page-sub">How Google finds, ranks, and sends visitors to your site &mdash; last 28 days</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <?php if ($is_configured): ?>
      <button id="vx-refresh-sc" class="vx-btn-outline">↻ Refresh</button>
    <?php endif; ?>
    <span class="vx-conn-badge <?php echo $conn_class; ?>"><?php echo $conn_text; ?></span>
  </div>
</div>

<?php if (isset($_GET['saved'])) echo '<div class="vx-notice vx-notice-ok">✓ Settings saved.</div>'; ?>
<?php if ($error): ?>
<div class="vx-notice vx-notice-err">❌ <?php echo esc_html($error); ?> — Check: site verified in Search Console, service account added as Owner, Search Console API enabled.</div>
<?php endif; ?>

<?php if ($data): ?>

<div style="background:<?php echo $d_bg; ?>;border:1.5px solid <?php echo $d_bd; ?>;border-radius:var(--r-lg);padding:18px 22px;display:flex;align-items:flex-start;gap:14px;">
  <span style="font-size:20px;flex-shrink:0;margin-top:2px;"><?php echo $d_icon; ?></span>
  <div>
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:<?php echo $d_color; ?>;margin-bottom:4px;">Google Diagnosis</div>
    <p style="margin:0;font-size:13.5px;line-height:1.65;color:var(--ink2);"><?php echo esc_html($dx_text); ?></p>
  </div>
</div>

<!-- 🎯 THIS MONTH'S FOCUS -->
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 22px;display:flex;align-items:flex-start;gap:16px;box-shadow:var(--sh-sm);">
  <div style="width:36px;height:36px;background:#18181A;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:17px;margin-top:1px;">🎯</div>
  <div style="flex:1;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:6px;">This Month's Focus</div>
    <p style="margin:0 0 6px;font-size:13.5px;color:var(--ink3);line-height:1.5;"><?php echo esc_html($sc_focus_obs); ?></p>
    <p style="margin:0;font-size:14px;font-weight:600;color:var(--ink);line-height:1.5;">→ <?php echo esc_html($sc_focus_action); ?></p>
  </div>
</div>

<!-- 4-col stat tiles -->
<div class="vx-stats-row">

  <div class="vx-stat-card">
    <span class="vx-stat-label">Visitors from Google</span>
    <span class="vx-stat-icon">👆</span>
    <strong class="vx-stat-num" style="color:<?php echo $totals['clicks']>=100?'var(--green)':($totals['clicks']>=10?'var(--amber)':'var(--red)'); ?>"><?php echo number_format($totals['clicks']); ?></strong>
    <?php if ($d_clicks[0]) echo '<div style="margin:2px 0 2px;">'.$d_clicks[0].'</div>'; ?>
    <span class="vx-stat-sub">clicks from search</span>
    <div class="vx-stat-dx <?php echo $totals['clicks']>=100?'good':($totals['clicks']>=10?'warn':'bad'); ?>">
      <?php echo $totals['clicks']>=100 ? 'Google is regularly sending visitors to your site — your SEO is working.' : ($totals['clicks']>=10 ? 'A handful of people finding you via Google. Keep improving your content.' : 'Almost no Google traffic yet — your pages may not be ranking or indexed.'); ?>
    </div>
  </div>

  <div class="vx-stat-card">
    <span class="vx-stat-label">Times Shown in Google</span>
    <span class="vx-stat-icon">👁️</span>
    <strong class="vx-stat-num" style="color:<?php echo $totals['impressions']>=500?'var(--green)':($totals['impressions']>=50?'var(--amber)':'var(--red)'); ?>"><?php echo number_format($totals['impressions']); ?></strong>
    <?php if ($d_impressions[0]) echo '<div style="margin:2px 0 2px;">'.$d_impressions[0].'</div>'; ?>
    <span class="vx-stat-sub">appeared in search results</span>
    <div class="vx-stat-dx <?php echo $totals['impressions']>=500?'good':($totals['impressions']>=50?'warn':'bad'); ?>">
      <?php echo $totals['impressions']>=500 ? 'Google shows your pages frequently — strong visibility in search.' : ($totals['impressions']>=50 ? 'Google is showing your site occasionally — more content will increase this.' : 'Google rarely displays your site — check that your pages are properly indexed.'); ?>
    </div>
  </div>

  <div class="vx-stat-card">
    <span class="vx-stat-label">Click-Through Rate</span>
    <span class="vx-stat-icon">🎯</span>
    <strong class="vx-stat-num" style="color:<?php echo $totals['ctr']>=5?'var(--green)':($totals['ctr']>=2?'var(--amber)':'var(--red)'); ?>"><?php echo $totals['ctr']; ?>%</strong>
    <?php if ($d_ctr[0]) echo '<div style="margin:2px 0 2px;">'.$d_ctr[0].'</div>'; ?>
    <span class="vx-stat-sub">of searchers click your result</span>
    <div class="vx-stat-dx <?php echo $totals['ctr']>=5?'good':($totals['ctr']>=2?'warn':'bad'); ?>">
      <?php echo $totals['ctr']>=5 ? 'Strong click rate — your titles and descriptions make people want to visit.' : ($totals['ctr']>=2 ? 'Average click rate. Rewriting page titles to be more specific and benefit-focused could double this.' : 'Low click rate — when Google shows your page, people skip it. Rewrite your page titles.'); ?>
    </div>
  </div>

  <div class="vx-stat-card">
    <span class="vx-stat-label">Average Ranking</span>
    <span class="vx-stat-icon">📍</span>
    <strong class="vx-stat-num" style="color:<?php echo $totals['position']<=5?'var(--green)':($totals['position']<=15?'var(--amber)':'var(--red)'); ?>"><?php echo $totals['position']; ?></strong>
    <?php if ($d_position[0]) echo '<div style="margin:2px 0 2px;">'.$d_position[0].'</div>'; ?>
    <span class="vx-stat-sub">position in Google results</span>
    <div class="vx-stat-dx <?php echo $totals['position']<=5?'good':($totals['position']<=15?'warn':'bad'); ?>">
      <?php echo $totals['position']<=3 ? 'Top 3 — you appear before almost everyone else. Protect this position.' : ($totals['position']<=10 ? 'Page 1 — most searchers will see you. Improve titles to reach the top 3.' : ($totals['position']<=20 ? 'Page 2 — most people never scroll this far. Your content needs more depth.' : 'Page 3+ — practically invisible to searchers. Start with a full SEO audit.')); ?>
    </div>
  </div>

</div>

<!-- Chart + Queries -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">

  <div class="vx-card">
    <div class="vx-card-head">
      <h3 class="vx-card-title">Clicks over time</h3>
      <span style="font-size:12px;color:var(--muted);">Last 28 days</span>
    </div>
    <?php
    $max_c    = max(1,max(array_column($chart,'clicks')));
    $peak_idx = array_search($max_c,array_column($chart,'clicks'));
    ?>
    <div class="vx-chart-bars" style="height:90px;">
      <?php foreach ($chart as $i=>$day):
        $h = max(3,round($day['clicks']/$max_c*84));
        $op = ($i===$peak_idx)?'1':'.44';
      ?>
        <div class="vx-chart-bar" style="height:<?php echo $h; ?>px;background:var(--green);opacity:<?php echo $op; ?>;"
             title="<?php echo esc_attr($day['date'].': '.$day['clicks'].' clicks'); ?>"></div>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
      <span style="font-size:12px;color:var(--muted);">Total clicks: <strong style="color:var(--ink);"><?php echo number_format($totals['clicks']); ?></strong></span>
      <span style="font-size:12px;color:var(--muted);">Impressions: <strong style="color:var(--ink);"><?php echo number_format($totals['impressions']); ?></strong></span>
    </div>
  </div>

  <div class="vx-card">
    <h3 class="vx-card-title">What people search to find you</h3>
    <?php foreach (array_slice($data['queries']['rows']??[],0,8) as $row):
      $pos_c = ($row['position']??99)<=5?'var(--green)':(($row['position']??99)<=10?'var(--amber)':'var(--muted)');
    ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border);gap:10px;">
        <span style="font-size:12.5px;font-weight:600;color:var(--ink2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo esc_html($row['keys'][0]??''); ?></span>
        <span style="font-size:11px;white-space:nowrap;flex-shrink:0;">
          <strong style="color:var(--ink)"><?php echo $row['clicks']??0; ?></strong>
          <span style="color:var(--muted);margin:0 4px;">·</span>
          <span style="color:<?php echo $pos_c; ?>;font-weight:700;">#<?php echo round($row['position']??0); ?></span>
        </span>
      </div>
    <?php endforeach; ?>
  </div>

</div>

<!-- Top pages in search -->
<?php if (!empty($data['pages']['rows'])): ?>
<div class="vx-card">
  <div class="vx-card-head">
    <h3 class="vx-card-title">Pages Google trusts most</h3>
    <span style="font-size:12px;color:var(--muted);">Last 28 days</span>
  </div>
  <div class="vx-table-wrap">
    <table class="vx-table">
      <thead><tr><th>#</th><th>Page</th><th style="text-align:right">Clicks</th><th style="text-align:right">Shown</th><th style="text-align:right">Click Rate</th><th style="text-align:right">Ranking</th><th>Diagnosis</th></tr></thead>
      <tbody>
      <?php $i=1; foreach ($data['pages']['rows'] as $row):
        $ctr  = round(($row['ctr']??0)*100,2);
        $pos  = round($row['position']??0,1);
        $path = esc_html(str_replace(home_url(),'',$row['keys'][0]??'')?:'/');
        $pc   = $pos<=5?'var(--green)':($pos<=10?'var(--amber)':'var(--red)');
        $cc   = $ctr>=5?'var(--green)':($ctr>=2?'var(--amber)':'var(--muted)');
        $dx   = $pos<=3?'Top 3 — keep it there':($pos<=10?'Page 1 — improve title to move up':'Buried — needs content work');
      ?>
        <tr>
          <td style="color:var(--muted);font-size:12px;font-weight:700;"><?php echo $i++; ?></td>
          <td><a href="<?php echo esc_url($row['keys'][0]??''); ?>" target="_blank" style="font-size:12.5px;color:var(--ink);font-weight:600;text-decoration:none;"><?php echo $path; ?></a></td>
          <td style="text-align:right;font-weight:700;"><?php echo $row['clicks']??0; ?></td>
          <td style="text-align:right;color:var(--muted);"><?php echo number_format($row['impressions']??0); ?></td>
          <td style="text-align:right;font-weight:700;color:<?php echo $cc; ?>"><?php echo $ctr; ?>%</td>
          <td style="text-align:right;font-weight:700;color:<?php echo $pc; ?>">#<?php echo $pos; ?></td>
          <td style="font-size:12px;color:var(--muted);font-style:italic;"><?php echo esc_html($dx); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php else:
// FOMO: days since plugin activated vs last connection attempt
$days_waiting = (int) floor( ( time() - (int) get_option('vx_installed_at', time()) ) / DAY_IN_SECONDS );
?>

<style>
/* Blurred preview layer */
.vx-locked-preview{position:relative;border-radius:var(--r-lg);overflow:hidden;user-select:none;pointer-events:none}
.vx-locked-preview::after{content:'';position:absolute;inset:0;backdrop-filter:blur(5px);-webkit-backdrop-filter:blur(5px);background:rgba(244,243,240,.72);z-index:2}
.vx-lock-overlay{position:absolute;inset:0;z-index:3;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px}
/* Insight example card */
.vx-insight-ex{background:var(--green-l);border:1.5px solid var(--green-b);border-radius:var(--r);padding:14px 18px}
/* Advanced setup collapsible */
.vx-adv-toggle{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--muted);background:none;border:none;padding:0;font-family:inherit}
.vx-adv-toggle:hover{color:var(--ink)}
.vx-adv-body{margin-top:16px}
.vx-adv-body.open{display:block}
</style>

<?php if ( $days_waiting >= 7 ) : ?>
<!-- FOMO alert -->
<div style="display:flex;align-items:center;gap:12px;background:var(--amber-l);border:1.5px solid var(--amber-b);border-radius:var(--r);padding:13px 18px;">
  <span style="font-size:20px;flex-shrink:0;">📉</span>
  <p style="margin:0;font-size:13px;font-weight:600;color:var(--amber);line-height:1.5;">
    <strong style="color:var(--ink2);">Your Google visibility data isn't connected yet.</strong>
    You may be missing easy ranking opportunities right now.
  </p>
</div>
<?php endif; ?>

<!-- HERO: unlock CTA -->
<div style="background:var(--ink);border-radius:var(--r-lg);padding:36px 40px;display:grid;grid-template-columns:1fr 360px;gap:36px;align-items:center;position:relative;overflow:hidden;box-shadow:var(--sh-lg);">
  <div style="position:absolute;inset:0;background:radial-gradient(circle at 85% 50%,rgba(255,255,255,.05),transparent 55%);pointer-events:none;"></div>
  <div style="position:relative;">
    <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);border-radius:var(--r-pill);padding:5px 14px;font-size:11px;font-weight:700;color:#fff;letter-spacing:.05em;margin-bottom:16px;">🔒 NOT CONNECTED</div>
    <h2 style="font-size:28px;font-weight:800;color:#fff;margin:0 0 12px;line-height:1.2;letter-spacing:-.4px;">Unlock insights Google already has about your site</h2>
    <p style="font-size:14px;color:rgba(255,255,255,.5);margin:0 0 22px;line-height:1.65;">Google tracks every time your site appears in search, every click, every ranking position. Connect once and see it all in plain English.</p>
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:24px;">
      <?php foreach([
        'Pages Google trusts most — and which to improve',
        'Keywords where you rank 4–10 (easy wins to push higher)',
        'Pages shown often but rarely clicked — title problems',
        'Your visibility trend over the last 28 days',
      ] as $b): ?>
        <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:rgba(255,255,255,.65);">
          <span style="width:18px;height:18px;background:rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0;color:#fff;">✓</span>
          <?php echo esc_html($b); ?>
        </div>
      <?php endforeach; ?>
    </div>
    <button onclick="document.getElementById('vx-connect-form').scrollIntoView({behavior:'smooth'})"
      style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:var(--ink);border:none;border-radius:var(--r-pill);padding:14px 28px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;">
      🔓 Connect Google Search Console
    </button>
    <p style="margin:10px 0 0;font-size:12px;color:rgba(255,255,255,.3);">Takes less than 2 minutes</p>
  </div>

  <!-- Insight example card -->
  <div style="position:relative;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.3);margin-bottom:12px;">Example insight after connecting</div>
    <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:var(--r-lg);padding:20px;display:flex;flex-direction:column;gap:14px;">
      <!-- Mock stat tiles -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <?php foreach([
          ['🖐', '128', 'Visitors from Google'],
          ['👁', '2,340', 'Times shown in search'],
          ['🎯', '5.4%', 'Click-through rate'],
          ['📍', '#8.3', 'Avg. ranking position'],
        ] as [$ic,$val,$lbl]): ?>
          <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:14px 14px 12px;">
            <div style="font-size:16px;margin-bottom:4px;"><?php echo $ic; ?></div>
            <div style="font-size:22px;font-weight:800;color:#fff;line-height:1;"><?php echo $val; ?></div>
            <div style="font-size:10.5px;color:rgba(255,255,255,.35);margin-top:3px;"><?php echo esc_html($lbl); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <!-- Mock opportunity line -->
      <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:13px 15px;border-left:3px solid #22c55e;">
        <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">Top opportunity</div>
        <div style="font-size:13px;font-weight:600;color:#fff;line-height:1.5;">You rank #6–9 for 5 keywords. Better titles could double your clicks.</div>
      </div>
      <div style="text-align:center;font-size:11.5px;color:rgba(255,255,255,.2);font-style:italic;">Your real data will appear here after connecting</div>
    </div>
  </div>
</div>

<!-- CONNECT FORM -->
<div class="vx-card" id="vx-connect-form">
  <h3 class="vx-card-title">Connect Google Search Console</h3>
  <p style="margin:-8px 0 20px;font-size:13px;color:var(--muted);">Enter your details below — takes less than 2 minutes.</p>
  <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="max-width:560px;">
    <?php wp_nonce_field('vx_save_sc'); ?>
    <input type="hidden" name="action" value="vx_save_sc" />
    <div class="vx-fg">
      <label class="vx-lbl">Site URL in Search Console</label>
      <input type="url" name="vx_sc_site_url" class="vx-input" value="<?php echo esc_attr($sc_site_url?:home_url()); ?>" placeholder="https://yoursite.com" />
      <p class="vx-hint">Must match exactly — including https:// and with/without www.</p>
    </div>
    <div class="vx-fg">
      <label class="vx-lbl">Service Account JSON Key</label>
      <textarea name="vx_sc_json_key" class="vx-input vx-textarea" rows="6" style="font-family:monospace;font-size:12px;" placeholder='{"type": "service_account", ...}'><?php echo esc_textarea($sc_json_key); ?></textarea>
    </div>
    <button type="submit" class="vx-btn-primary">Save & Connect →</button>
    <p style="margin:10px 0 0;font-size:12px;color:var(--muted);">⏱ Takes less than 2 minutes · You can reuse the same JSON key as GA4</p>
  </form>

  <!-- Advanced setup — collapsed by default -->
  <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);">
    <button class="vx-adv-toggle" onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('.vx-adv-arrow').textContent=this.nextElementSibling.classList.contains('open')?'▲':'▼'">
      <span>🔧 Manual Setup Instructions (Advanced)</span>
      <span class="vx-adv-arrow">▲</span>
    </button>
    <div class="vx-adv-body open">
      <div class="vx-setup-steps" style="margin-top:0;">
        <div class="vx-setup-step"><div class="vx-step-num">1</div><div class="vx-step-body"><strong>Verify your site</strong><p>Go to <a href="https://search.google.com/search-console" target="_blank">search.google.com/search-console</a> → Add Property → verify via HTML tag or DNS.</p></div></div>
        <div class="vx-setup-step"><div class="vx-step-num">2</div><div class="vx-step-body"><strong>Enable Search Console API</strong><p>In <a href="https://console.cloud.google.com" target="_blank">Google Cloud Console</a> → APIs & Services → Library → enable <strong>"Google Search Console API"</strong>.</p></div></div>
        <div class="vx-setup-step"><div class="vx-step-num">3</div><div class="vx-step-body"><strong>Grant access</strong><p>In Search Console → Settings → Users and permissions → Add user → service account email → <strong>Full</strong>.</p></div></div>
        <div class="vx-setup-step"><div class="vx-step-num">4</div><div class="vx-step-body"><strong>Enter your site URL and JSON key above</strong><p>You can reuse the same JSON key as GA4 if the service account also has Search Console access.</p></div></div>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<script>
document.getElementById('vx-refresh-sc')?.addEventListener('click',function(){
  this.textContent='Refreshing…';this.disabled=true;
  const fd=new FormData();fd.append('action','vx_refresh_sc');fd.append('nonce',vxSeo.nonce);
  fetch(vxSeo.ajaxUrl,{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if(res.success)location.reload();else{alert('Error: '+res.data);this.textContent='↻ Refresh';this.disabled=false;}
  });
});
</script>

<?php require __DIR__ . '/layout-footer.php'; ?>
