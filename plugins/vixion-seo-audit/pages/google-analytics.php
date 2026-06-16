<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

$property_id   = get_option( 'vx_ga_property_id', '' );
$json_key      = get_option( 'vx_ga_json_key', '' );
$is_configured = $property_id && $json_key;

$data  = null;
$error = null;
if ( $is_configured ) {
    $data = vx_ga_fetch_report( 30 );
    if ( is_wp_error( $data ) ) { $error = $data->get_error_message(); $data = null; }
}

$totals = [ 'sessions'=>0,'users'=>0,'new_users'=>0,'bounce_rate'=>0,'pageviews'=>0,'duration'=>0 ];
$prev   = [ 'sessions'=>0,'users'=>0,'new_users'=>0,'bounce_rate'=>0,'pageviews'=>0,'duration'=>0 ];
$chart_days = [];

if ( $data && isset( $data['main']['rows'] ) ) {
    $rows      = $data['main']['rows'];
    $cnt_cur   = 0;
    $cnt_prev  = 0;

    foreach ( $rows as $row ) {
        $v       = $row['metricValues'];
        // GA4 returns dateRange dimension when 2 ranges requested
        $range   = $row['dimensionValues'][1]['value'] ?? 'date_range_0';
        $is_prev = ($range === 'date_range_1');

        if ( $is_prev ) {
            $prev['sessions']    += (float)($v[0]['value']??0);
            $prev['users']       += (float)($v[1]['value']??0);
            $prev['new_users']   += (float)($v[2]['value']??0);
            $prev['bounce_rate'] += (float)($v[3]['value']??0);
            $prev['duration']    += (float)($v[4]['value']??0);
            $prev['pageviews']   += (float)($v[5]['value']??0);
            $cnt_prev++;
        } else {
            $totals['sessions']    += (float)($v[0]['value']??0);
            $totals['users']       += (float)($v[1]['value']??0);
            $totals['new_users']   += (float)($v[2]['value']??0);
            $totals['bounce_rate'] += (float)($v[3]['value']??0);
            $totals['duration']    += (float)($v[4]['value']??0);
            $totals['pageviews']   += (float)($v[5]['value']??0);
            $cnt_cur++;
            $chart_days[] = [ 'date'=>substr($row['dimensionValues'][0]['value']??'',4,4), 'sessions'=>(int)($v[0]['value']??0) ];
        }
    }

    if ($cnt_cur>0)  $totals['bounce_rate'] = round($totals['bounce_rate']/$cnt_cur*100,1);
    if ($cnt_prev>0) $prev['bounce_rate']   = round($prev['bounce_rate']/$cnt_prev*100,1);
    $totals['duration'] = $cnt_cur>0  ? round($totals['duration']/$cnt_cur)  : 0;
    $prev['duration']   = $cnt_prev>0 ? round($prev['duration']/$cnt_prev)   : 0;
}

$dur_min = floor($totals['duration']/60);
$dur_sec = $totals['duration']%60;

// Growth delta helper — returns [arrow_html, pct_string, direction]
// For bounce rate: lower is better (invert)
function vx_delta( $cur, $prev, $invert = false ) {
    if ( $prev <= 0 ) return [ '', '', 'neutral' ];
    $pct = round( ($cur - $prev) / $prev * 100, 1 );
    if ( abs($pct) < 1 ) return [ '', '~0%', 'neutral' ];
    $up      = $pct > 0;
    $good    = $invert ? !$up : $up;
    $arrow   = $up ? '↑' : '↓';
    $sign    = $up ? '+' : '';
    $color   = $good ? 'var(--green)' : 'var(--red)';
    $label   = $good ? 'Improved' : 'Declined';
    $html    = "<span style='font-size:11px;font-weight:700;color:{$color};white-space:nowrap;'>{$arrow} {$sign}{$pct}% <span style='font-weight:500;opacity:.75;'>vs last month</span></span>";
    return [ $html, "{$sign}{$pct}%", $good ? 'good' : 'bad' ];
}

$d_sessions  = vx_delta( $totals['sessions'],    $prev['sessions'] );
$d_users     = vx_delta( $totals['users'],        $prev['users'] );
$d_pageviews = vx_delta( $totals['pageviews'],    $prev['pageviews'] );
$d_bounce    = vx_delta( $totals['bounce_rate'],  $prev['bounce_rate'], true );  // lower = better
$d_duration  = vx_delta( $totals['duration'],     $prev['duration'] );
$d_new_users = vx_delta( $totals['new_users'],    $prev['new_users'] );

$conn_class = $is_configured ? ($data?'vx-conn-yes':'vx-conn-err') : 'vx-conn-no';
$conn_text  = $is_configured ? ($data?'✓ Connected':'⚠ Error') : '⚠ Not Connected';

// Organic % for banner
$organic_src = 0;
foreach ( ($data['sources']['rows']??[]) as $r ) {
    if ( strpos($r['dimensionValues'][0]['value']??'','Organic')!==false )
        $organic_src += (int)($r['metricValues'][0]['value']??0);
}
$organic_pct = $totals['sessions']>0 ? round($organic_src/$totals['sessions']*100) : 0;

// Doctor banner
$issues = [];
if ($totals['bounce_rate']>=60)      $issues[] = 'high bounce rate';
if ($totals['duration']<60)          $issues[] = 'very short visit time';
if ($totals['sessions']<100)         $issues[] = 'low overall traffic';
if ($organic_pct<30 && $data)        $issues[] = 'very little traffic from Google';

if (empty($issues))         { $banner_text="Your site traffic looks healthy. Visitors are engaging well. Keep publishing quality content to grow."; }
elseif (count($issues)===1) { $banner_text="Mostly fine, but watch: ".implode('',$issues).". Scroll down for details."; }
else                        { $banner_text="A few things need attention: ".implode(' and ',$issues).". Normal for a growing site — fix technical SEO first, then focus on content."; }

$b_color = empty($issues)?'var(--green)':(count($issues)>=2?'var(--red)':'var(--amber)');
$b_bg    = empty($issues)?'var(--green-l)':(count($issues)>=2?'var(--red-l)':'var(--amber-l)');
$b_bd    = empty($issues)?'var(--green-b)':(count($issues)>=2?'var(--red-b)':'var(--amber-b)');
$b_icon  = empty($issues)?'🟢':(count($issues)>=2?'🔴':'🟡');

// 🎯 ONE priority focus — most impactful single action
$returning_pct = $totals['users']>0 ? round(($totals['users']-$totals['new_users'])/$totals['users']*100) : 0;
$ppv           = $totals['sessions']>0 ? round($totals['pageviews']/$totals['sessions'],1) : 0;

if ($totals['sessions'] < 100) {
    $focus_obs    = "You have very low traffic right now.";
    $focus_action = "Start publishing 2–3 blog posts per week targeting specific questions your customers ask.";
} elseif ($totals['bounce_rate'] >= 65) {
    $focus_obs    = "Your bounce rate is high — most visitors leave after seeing just one page.";
    $focus_action = "Add a clear next step (a related article, a CTA, or an offer) at the top of your highest-traffic pages.";
} elseif ($totals['duration'] < 60) {
    $focus_obs    = "Visitors are leaving in under a minute on average.";
    $focus_action = "Rewrite your most visited page's opening paragraph — make it immediately useful and specific.";
} elseif ($organic_pct < 25 && $totals['sessions'] >= 100) {
    $focus_obs    = "Only {$organic_pct}% of your traffic comes from Google Search.";
    $focus_action = "Run an SEO audit and fix your top 3 issues — this alone can double organic traffic in 60 days.";
} elseif ($returning_pct < 15 && $totals['sessions'] >= 100) {
    $focus_obs    = number_format(100-$returning_pct)."% of visitors never come back after their first visit.";
    $focus_action = "Add an email signup or push notification so first-time visitors have a reason to return.";
} elseif ($ppv < 1.5 && $totals['sessions'] >= 100) {
    $focus_obs    = "Most visitors only view one page per visit.";
    $focus_action = "Add 2–3 internal links near the top of your most popular pages pointing to related content.";
} else {
    $focus_obs    = "Your core traffic metrics are in good shape.";
    $focus_action = "Focus on growing traffic — publish one new SEO-optimised piece of content this week.";
}

$src_colors = ['Organic Search'=>'#1A6B3C','Direct'=>'#18181A','Referral'=>'#1A3A8B','Organic Social'=>'#6d28d9','Email'=>'#7A4A0A','Unassigned'=>'#A0A0A8'];
?>

<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title">Traffic Report</h1>
    <p class="vx-page-sub">How people find and use your site &mdash; last 30 days · GA4</p>
  </div>
  <div style="display:flex;gap:10px;align-items:center;">
    <?php if ($is_configured): ?>
      <button id="vx-refresh-ga" class="vx-btn-outline">↻ Refresh</button>
    <?php endif; ?>
    <span class="vx-conn-badge <?php echo $conn_class; ?>"><?php echo $conn_text; ?></span>
  </div>
</div>

<?php if (isset($_GET['saved'])) echo '<div class="vx-notice vx-notice-ok">✓ Settings saved.</div>'; ?>
<?php if ($error): ?>
<div class="vx-notice vx-notice-err">❌ <?php echo esc_html($error); ?> — Check your Property ID, JSON key, and service account Viewer access.</div>
<?php endif; ?>

<?php if ($data): ?>

<div style="background:<?php echo $b_bg; ?>;border:1.5px solid <?php echo $b_bd; ?>;border-radius:var(--r-lg);padding:18px 22px;display:flex;align-items:flex-start;gap:14px;">
  <span style="font-size:20px;flex-shrink:0;margin-top:2px;"><?php echo $b_icon; ?></span>
  <div>
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:<?php echo $b_color; ?>;margin-bottom:4px;">Doctor's Diagnosis</div>
    <p style="margin:0;font-size:13.5px;line-height:1.65;color:var(--ink2);"><?php echo esc_html($banner_text); ?></p>
  </div>
</div>

<!-- 🎯 THIS MONTH'S FOCUS -->
<div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 22px;display:flex;align-items:flex-start;gap:16px;box-shadow:var(--sh-sm);">
  <div style="width:36px;height:36px;background:#18181A;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:17px;margin-top:1px;">🎯</div>
  <div style="flex:1;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--muted);margin-bottom:6px;">This Month's Focus</div>
    <p style="margin:0 0 6px;font-size:13.5px;color:var(--ink3);line-height:1.5;"><?php echo esc_html($focus_obs); ?></p>
    <p style="margin:0;font-size:14px;font-weight:600;color:var(--ink);line-height:1.5;">→ <?php echo esc_html($focus_action); ?></p>
  </div>
</div>

<!-- 5-col stat tiles -->
<div class="vx-stats-row vx-stats-row-5">

  <div class="vx-stat-card">
    <span class="vx-stat-label">Total Visits</span>
    <span class="vx-stat-icon">👥</span>
    <strong class="vx-stat-num"><?php echo number_format($totals['sessions']); ?></strong>
    <?php if ($d_sessions[0]) echo '<div style="margin:2px 0 2px;">'.$d_sessions[0].'</div>'; ?>
    <span class="vx-stat-sub"><?php echo number_format($totals['users']); ?> unique people</span>
    <div class="vx-stat-dx <?php echo $totals['sessions']>=500?'good':($totals['sessions']>=100?'warn':'bad'); ?>">
      <?php echo $totals['sessions']>=500 ? 'Solid traffic — people are regularly finding your site.' : ($totals['sessions']>=100 ? 'Growing audience. Keep publishing consistently.' : 'Very low traffic — focus on getting found first.'); ?>
    </div>
  </div>

  <div class="vx-stat-card">
    <span class="vx-stat-label">New Visitors</span>
    <span class="vx-stat-icon">🔄</span>
    <?php $new_pct = $totals['users']>0?round($totals['new_users']/$totals['users']*100):0; ?>
    <strong class="vx-stat-num"><?php echo $new_pct; ?>%</strong>
    <?php if ($d_new_users[0]) echo '<div style="margin:2px 0 2px;">'.$d_new_users[0].'</div>'; ?>
    <span class="vx-stat-sub"><?php echo number_format($totals['new_users']); ?> first-time</span>
    <div class="vx-stat-dx">
      <?php $ret=$totals['users']-$totals['new_users']; echo $ret>10 ? number_format($ret).' returning visitors — these people liked your site enough to come back.' : 'Almost all visitors are new — add an email signup to build a loyal audience.'; ?>
    </div>
  </div>

  <div class="vx-stat-card">
    <span class="vx-stat-label">Pages per Visit</span>
    <span class="vx-stat-icon">📄</span>
    <?php $ppv=$totals['sessions']>0?round($totals['pageviews']/$totals['sessions'],1):0; ?>
    <strong class="vx-stat-num" style="<?php echo $ppv>=2?'color:var(--green)':''; ?>"><?php echo $ppv?:'—'; ?></strong>
    <?php if ($d_pageviews[0]) echo '<div style="margin:2px 0 2px;">'.$d_pageviews[0].'</div>'; ?>
    <span class="vx-stat-sub"><?php echo number_format($totals['pageviews']); ?> total pageviews</span>
    <div class="vx-stat-dx <?php echo $ppv>=2?'good':'warn'; ?>">
      <?php echo $ppv>=3 ? 'Visitors explore multiple pages — people are genuinely interested in your content.' : ($ppv>=1.5 ? 'Visitors view a couple of pages — internal links are working, keep adding more.' : 'Most visitors only see one page — add related content links to guide them forward.'); ?>
    </div>
  </div>

  <div class="vx-stat-card">
    <span class="vx-stat-label">Bounce Rate</span>
    <span class="vx-stat-icon">↩</span>
    <strong class="vx-stat-num" style="color:<?php echo $totals['bounce_rate']<50?'var(--green)':($totals['bounce_rate']<65?'var(--amber)':'var(--red)'); ?>"><?php echo $totals['bounce_rate']; ?>%</strong>
    <?php if ($d_bounce[0]) echo '<div style="margin:2px 0 2px;">'.$d_bounce[0].'</div>'; ?>
    <span class="vx-stat-sub">left without clicking further</span>
    <div class="vx-stat-dx <?php echo $totals['bounce_rate']<50?'good':($totals['bounce_rate']<65?'warn':'bad'); ?>">
      <?php echo $totals['bounce_rate']<50 ? 'Most visitors explore beyond the first page — your content is guiding them well.' : ($totals['bounce_rate']<65 ? 'About half leave after one page — a clearer call-to-action could keep more people reading.' : 'Most visitors leave after the first page — your landing page may not be guiding them forward.'); ?>
    </div>
  </div>

  <div class="vx-stat-card">
    <span class="vx-stat-label">Avg Visit Time</span>
    <span class="vx-stat-icon">⏱</span>
    <strong class="vx-stat-num" style="font-size:<?php echo $dur_min>=10?'28px':'34px'; ?>;color:<?php echo $totals['duration']>=120?'var(--green)':($totals['duration']>=60?'var(--amber)':'var(--red)'); ?>"><?php echo "{$dur_min}m {$dur_sec}s"; ?></strong>
    <?php if ($d_duration[0]) echo '<div style="margin:2px 0 2px;">'.$d_duration[0].'</div>'; ?>
    <span class="vx-stat-sub">average per visitor</span>
    <div class="vx-stat-dx <?php echo $totals['duration']>=180?'good':($totals['duration']>=60?'warn':'bad'); ?>">
      <?php echo $totals['duration']>=300 ? '5+ min average — visitors are deeply reading your content. Strong content quality.' : ($totals['duration']>=180 ? '3+ min average — people are genuinely reading, not just skimming.' : ($totals['duration']>=60 ? 'About a minute on average — decent, but richer content could keep them longer.' : 'Under a minute on average — visitors may not be finding what they came for.')); ?>
    </div>
  </div>

</div>

<!-- Chart + Sources -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;">

  <div class="vx-card">
    <div class="vx-card-head">
      <h3 class="vx-card-title">Sessions over time</h3>
      <span style="font-size:12px;color:var(--muted);">Last 30 days</span>
    </div>
    <?php
    $max_s    = max(1, max(array_column($chart_days,'sessions')));
    $peak_idx = array_search($max_s, array_column($chart_days,'sessions'));
    ?>
    <div class="vx-chart-bars" style="height:100px;">
      <?php foreach ($chart_days as $i=>$day):
        $h = max(3, round($day['sessions']/$max_s*94));
        $op = ($i===$peak_idx)?'1':'.42';
      ?>
        <div class="vx-chart-bar" style="height:<?php echo $h; ?>px;background:var(--ink);opacity:<?php echo $op; ?>;"
             title="<?php echo esc_attr($day['date'].': '.$day['sessions'].' sessions'); ?>"></div>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px solid var(--border);">
      <span style="font-size:12px;color:var(--muted);">Total: <strong style="color:var(--ink);"><?php echo number_format($totals['sessions']); ?></strong></span>
      <span style="font-size:12px;color:var(--muted);">From Google: <strong style="color:var(--ink);"><?php echo $organic_pct; ?>%</strong></span>
    </div>
  </div>

  <div class="vx-card">
    <h3 class="vx-card-title">How People Found You</h3>
    <?php
    $sources = $data['sources']['rows']??[];
    $max_src = max(1,(int)($sources[0]['metricValues'][0]['value']??1));

    // Work out which source is #1 by sessions for smart suggestion
    $top_source_name = !empty($sources) ? ($sources[0]['dimensionValues'][0]['value']??'') : '';
    $top_source_cnt  = !empty($sources) ? (int)($sources[0]['metricValues'][0]['value']??0) : 0;
    $top_source_pct  = $totals['sessions']>0 ? round($top_source_cnt/$totals['sessions']*100) : 0;

    // Build suggestion based on source mix
    if ($top_source_name==='Direct' && $top_source_pct>=50) {
        $src_note_icon = '💪';
        $src_note_bg   = 'var(--green-l)'; $src_note_bd = 'var(--green-b)'; $src_note_c = 'var(--green)';
        $src_note      = "Direct is high ({$top_source_pct}%) — people already know your brand. Great sign. Now work on growing Google traffic so new people can find you too.";
    } elseif ($organic_pct >= 50) {
        $src_note_icon = '🌱';
        $src_note_bg   = 'var(--green-l)'; $src_note_bd = 'var(--green-b)'; $src_note_c = 'var(--green)';
        $src_note      = "Strong organic search ({$organic_pct}%) — Google is sending you most of your visitors. Keep publishing quality content to maintain this.";
    } elseif ($organic_pct < 20) {
        $src_note_icon = '📈';
        $src_note_bg   = 'var(--amber-l)'; $src_note_bd = 'var(--amber-b)'; $src_note_c = 'var(--amber)';
        $src_note      = "Only {$organic_pct}% from Google Search — most traffic isn't from SEO yet. Run a full audit and start targeting keywords your customers actually search for.";
    } elseif ($organic_pct < 35) {
        $src_note_icon = '💡';
        $src_note_bg   = 'var(--amber-l)'; $src_note_bd = 'var(--amber-b)'; $src_note_c = 'var(--amber)';
        $src_note      = "Organic is at {$organic_pct}% — decent but there's room to grow. Publishing 2 SEO-focused articles per month can steadily increase this.";
    } else {
        $src_note_icon = '✅';
        $src_note_bg   = 'var(--surface2)'; $src_note_bd = 'var(--border)'; $src_note_c = 'var(--ink3)';
        $src_note      = "Good mix of traffic sources. Don't rely too heavily on any single channel — keep diversifying.";
    }

    foreach (array_slice($sources,0,6) as $row):
      $name = $row['dimensionValues'][0]['value']??'Other';
      $cnt  = (int)($row['metricValues'][0]['value']??0);
      $pct  = round($cnt/$max_src*100);
      $col  = $src_colors[$name]??'#888';
    ?>
      <div class="vx-bar-row">
        <span class="vx-bar-label"><?php echo esc_html($name); ?></span>
        <div class="vx-bar-track"><div class="vx-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $col; ?>;"></div></div>
        <span class="vx-bar-val"><?php echo number_format($cnt); ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (empty($sources)) echo '<p style="color:var(--muted);font-size:13px;">No source data yet.</p>'; ?>

    <div style="margin-top:14px;padding:10px 13px;background:<?php echo $src_note_bg; ?>;border-radius:9px;border:1px solid <?php echo $src_note_bd; ?>;display:flex;align-items:flex-start;gap:9px;">
      <span style="font-size:14px;flex-shrink:0;margin-top:1px;"><?php echo $src_note_icon; ?></span>
      <p style="margin:0;font-size:12px;line-height:1.55;color:<?php echo $src_note_c; ?>;font-weight:600;"><?php echo esc_html($src_note); ?></p>
    </div>
  </div>

</div>

<!-- Top Pages -->
<?php
// Pre-compute quick wins from page data
$high_bounce_pages = [];
$top_page_path     = '';
$top_page_sessions = 0;
if (!empty($data['pages']['rows'])) {
    foreach ($data['pages']['rows'] as $pr) {
        $pbr   = round(($pr['metricValues'][1]['value']??0)*100,1);
        $ppath = $pr['dimensionValues'][1]['value']??'';
        $psess = (int)($pr['metricValues'][0]['value']??0);
        if ($pbr >= 70 && $ppath) $high_bounce_pages[] = ['path'=>$ppath,'br'=>$pbr,'sess'=>$psess];
        if ($psess > $top_page_sessions) { $top_page_sessions=$psess; $top_page_path=$ppath; }
    }
}
?>

<?php if (!empty($data['pages']['rows'])): ?>
<div class="vx-card">
  <div class="vx-card-head">
    <h3 class="vx-card-title">Top pages</h3>
    <span style="font-size:12px;color:var(--muted);">By sessions, last 30 days</span>
  </div>
  <div class="vx-table-wrap">
    <table class="vx-table">
      <thead><tr><th>#</th><th>Page</th><th>Path</th><th style="text-align:right">Sessions</th><th style="text-align:right">Bounce</th><th>Diagnosis</th></tr></thead>
      <tbody>
      <?php $i=1; foreach ($data['pages']['rows'] as $row):
        $br    = round(($row['metricValues'][1]['value']??0)*100,1);
        $sess  = (int)($row['metricValues'][0]['value']??0);
        $path  = $row['dimensionValues'][1]['value']??'';
        $is_problem = $br >= 70;
        $br_c  = $br<45?'var(--green)':($br<65?'var(--amber)':'var(--red)');
        if ($br >= 70)      $dx = '🔴 High exit — improve this page first';
        elseif ($br >= 65)  $dx = '🟡 Above average — add a next step';
        elseif ($br < 45)   $dx = '🟢 Visitors engage well';
        else                $dx = 'Average engagement';
      ?>
        <tr style="<?php echo $is_problem ? 'background:var(--red-l);' : ''; ?>">
          <td style="color:var(--muted);font-size:12px;font-weight:700;"><?php echo $i++; ?></td>
          <td>
            <span style="font-weight:600;font-size:13px;"><?php echo esc_html(mb_strimwidth($row['dimensionValues'][0]['value']??'',0,46,'…')); ?></span>
            <?php if ($is_problem): ?>
              <span style="display:inline-block;margin-left:7px;font-size:10px;font-weight:700;background:var(--red);color:#fff;padding:2px 7px;border-radius:4px;vertical-align:middle;letter-spacing:.04em;">NEEDS WORK</span>
            <?php endif; ?>
          </td>
          <td><code style="font-size:11px;background:<?php echo $is_problem?'rgba(139,26,26,.08)':'var(--surface2)'; ?>;padding:2px 7px;border-radius:5px;color:<?php echo $is_problem?'var(--red)':'var(--ink3)'; ?>;"><?php echo esc_html($path); ?></code></td>
          <td style="text-align:right;font-weight:700;"><?php echo number_format($sess); ?></td>
          <td style="text-align:right;font-weight:700;color:<?php echo $br_c; ?>"><?php echo $br; ?>%<?php if ($is_problem) echo ' ⚠'; ?></td>
          <td style="font-size:12px;color:<?php echo $is_problem?'var(--red)':'var(--muted)'; ?>;font-weight:<?php echo $is_problem?'600':'400'; ?>;"><?php echo esc_html($dx); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($high_bounce_pages)): ?>
  <div style="margin-top:14px;padding:11px 14px;background:var(--red-l);border:1px solid var(--red-b);border-radius:9px;display:flex;align-items:center;gap:10px;">
    <span style="font-size:16px;flex-shrink:0;">🔴</span>
    <p style="margin:0;font-size:12.5px;color:var(--red);font-weight:600;line-height:1.5;">
      <?php echo count($high_bounce_pages); ?> page<?php echo count($high_bounce_pages)>1?'s':''; ?> with very high exit rates detected.
      Start with <strong><?php echo esc_html($high_bounce_pages[0]['path']); ?></strong> (<?php echo $high_bounce_pages[0]['br']; ?>% bounce) — add a visible call-to-action or link to related content.
    </p>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
// ── Engagement Badges ─────────────────────────────────────────
$ppv_val = $totals['sessions']>0?round($totals['pageviews']/$totals['sessions'],1):0;

// Traffic Quality
if ($totals['sessions']>=500 && $organic_pct>=30)       { $tq='Strong';  $tq_c='var(--green)'; $tq_bg='var(--green-l)'; $tq_bd='var(--green-b)'; }
elseif ($totals['sessions']>=100)                        { $tq='Growing'; $tq_c='var(--amber)'; $tq_bg='var(--amber-l)'; $tq_bd='var(--amber-b)'; }
else                                                     { $tq='Early Stage'; $tq_c='var(--muted)'; $tq_bg='var(--surface2)'; $tq_bd='var(--border)'; }

// Engagement Level
if ($totals['bounce_rate']<50 && $totals['duration']>=120) { $el='Strong';  $el_c='var(--green)'; $el_bg='var(--green-l)'; $el_bd='var(--green-b)'; }
elseif ($totals['bounce_rate']<65 || $totals['duration']>=60){ $el='Moderate'; $el_c='var(--amber)'; $el_bg='var(--amber-l)'; $el_bd='var(--amber-b)'; }
else                                                     { $el='Needs Work'; $el_c='var(--red)'; $el_bg='var(--red-l)'; $el_bd='var(--red-b)'; }

// Growth Potential
$d_growth = $d_sessions[2]??'neutral';
if ($d_growth==='good' && $organic_pct>=30)              { $gp='High';   $gp_c='var(--green)'; $gp_bg='var(--green-l)'; $gp_bd='var(--green-b)'; }
elseif ($organic_pct<20 || $totals['sessions']<100)      { $gp='High';   $gp_c='var(--green)'; $gp_bg='var(--green-l)'; $gp_bd='var(--green-b)'; } // low = lots of room
elseif ($d_growth==='bad')                               { $gp='At Risk'; $gp_c='var(--red)'; $gp_bg='var(--red-l)'; $gp_bd='var(--red-b)'; }
else                                                     { $gp='Medium';  $gp_c='var(--amber)'; $gp_bg='var(--amber-l)'; $gp_bd='var(--amber-b)'; }
?>

<!-- Site Health Badges -->
<div class="vx-card">
  <div class="vx-card-head" style="margin-bottom:16px;">
    <h3 class="vx-card-title">Site Health Summary</h3>
  </div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
    <div style="background:<?php echo $tq_bg; ?>;border:1px solid <?php echo $tq_bd; ?>;border-radius:var(--r);padding:16px 18px;">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:<?php echo $tq_c; ?>;margin-bottom:6px;">Traffic Quality</div>
      <div style="font-size:22px;font-weight:800;color:<?php echo $tq_c; ?>;letter-spacing:-.5px;"><?php echo $tq; ?></div>
      <p style="margin:6px 0 0;font-size:11.5px;color:var(--ink3);line-height:1.5;"><?php echo $tq==='Strong'?'Good volume with healthy organic share.':($tq==='Growing'?'Traffic is building — keep publishing.':'Focus on getting found before optimising.'); ?></p>
    </div>
    <div style="background:<?php echo $el_bg; ?>;border:1px solid <?php echo $el_bd; ?>;border-radius:var(--r);padding:16px 18px;">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:<?php echo $el_c; ?>;margin-bottom:6px;">Engagement Level</div>
      <div style="font-size:22px;font-weight:800;color:<?php echo $el_c; ?>;letter-spacing:-.5px;"><?php echo $el; ?></div>
      <p style="margin:6px 0 0;font-size:11.5px;color:var(--ink3);line-height:1.5;"><?php echo $el==='Strong'?'Visitors are reading and exploring.':($el==='Moderate'?'Some engagement — room to improve.':'Most visitors leave quickly.'); ?></p>
    </div>
    <div style="background:<?php echo $gp_bg; ?>;border:1px solid <?php echo $gp_bd; ?>;border-radius:var(--r);padding:16px 18px;">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:<?php echo $gp_c; ?>;margin-bottom:6px;">Growth Potential</div>
      <div style="font-size:22px;font-weight:800;color:<?php echo $gp_c; ?>;letter-spacing:-.5px;"><?php echo $gp; ?></div>
      <p style="margin:6px 0 0;font-size:11.5px;color:var(--ink3);line-height:1.5;"><?php echo $gp==='High'?'Lots of room to grow with the right moves.':($gp==='Medium'?'Steady — consistent effort will compound.':'Traffic dipping — investigate the cause.'); ?></p>
    </div>
  </div>
</div>

<?php
// ── Quick Wins ────────────────────────────────────────────────
$wins = [];

// Win from high-bounce pages
if (!empty($high_bounce_pages)) {
    $p = $high_bounce_pages[0];
    $wins[] = [ 'icon'=>'🔴', 'urgency'=>'high', 'text'=>"Add a call-to-action or related content link on <strong>".esc_html($p['path'])."</strong> — it has a {$p['br']}% exit rate." ];
}

// Win from low organic
if ($organic_pct < 25) {
    $wins[] = [ 'icon'=>'📈', 'urgency'=>'high', 'text'=>"Only {$organic_pct}% of visits come from Google — run a full SEO audit this week to find and fix your biggest ranking blockers." ];
} elseif ($organic_pct < 40) {
    $wins[] = [ 'icon'=>'📈', 'urgency'=>'medium', 'text'=>"Boost organic traffic by optimising the meta title and description of your top 3 pages for a more compelling click-through." ];
}

// Win from low returning visitors
$ret_pct = $totals['users']>0?round(($totals['users']-$totals['new_users'])/$totals['users']*100):0;
if ($ret_pct < 15) {
    $wins[] = [ 'icon'=>'📧', 'urgency'=>'medium', 'text'=>"Only {$ret_pct}% of visitors return — add an email signup offer on your most visited page to build a loyal repeat audience." ];
}

// Win from top page — double down
if ($top_page_path && $top_page_path!=='/') {
    $wins[] = [ 'icon'=>'⭐', 'urgency'=>'low', 'text'=>"Your top page is <strong>".esc_html($top_page_path)."</strong> ({$top_page_sessions} sessions) — add 3 internal links from it to push visitors deeper into your site." ];
}

// Win from low engagement
if ($totals['duration'] < 90) {
    $wins[] = [ 'icon'=>'✍️', 'urgency'=>'medium', 'text'=>"Average visit time is under 90 seconds — rewrite your homepage's opening paragraph to immediately answer what visitors came for." ];
}

// Win from sessions growth
if ($totals['sessions'] < 200) {
    $wins[] = [ 'icon'=>'📝', 'urgency'=>'low', 'text'=>"Publish one 800-word blog post answering a specific question your customers search for — this alone can bring consistent new traffic within 60 days." ];
}

// Always add a content win as the last item if list is short
if (count($wins) < 3) {
    $wins[] = [ 'icon'=>'🔗', 'urgency'=>'low', 'text'=>"Go through your last 5 blog posts and add 2 internal links in each pointing to related content — this improves SEO and pages-per-visit." ];
}

// Cap at 4 wins max
$wins = array_slice($wins, 0, 4);
$urgency_colors = [ 'high'=>['var(--red-l)','var(--red-b)','var(--red)'], 'medium'=>['var(--amber-l)','var(--amber-b)','var(--amber)'], 'low'=>['var(--surface2)','var(--border)','var(--ink3)'] ];
?>

<!-- ⚡ Quick Wins -->
<div class="vx-card">
  <div class="vx-card-head" style="margin-bottom:16px;">
    <div>
      <h3 class="vx-card-title" style="margin-bottom:4px;">⚡ Quick Wins to Grow Traffic</h3>
      <p style="margin:0;font-size:12px;color:var(--muted);">Based on your actual data — pick one and do it today.</p>
    </div>
  </div>
  <div style="display:flex;flex-direction:column;gap:10px;">
    <?php foreach ($wins as $idx => $win):
      $uc = $urgency_colors[$win['urgency']];
    ?>
    <div style="display:flex;align-items:flex-start;gap:14px;padding:14px 16px;background:<?php echo $uc[0]; ?>;border:1px solid <?php echo $uc[1]; ?>;border-radius:var(--r);">
      <div style="width:28px;height:28px;background:var(--surface);border:1px solid <?php echo $uc[1]; ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;margin-top:1px;"><?php echo $win['icon']; ?></div>
      <div style="flex:1;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;">
          <span style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:<?php echo $uc[2]; ?>;background:var(--surface);border:1px solid <?php echo $uc[1]; ?>;padding:2px 8px;border-radius:20px;"><?php echo strtoupper($win['urgency']); ?> PRIORITY</span>
        </div>
        <p style="margin:0;font-size:13.5px;color:var(--ink2);line-height:1.6;"><?php echo $win['text']; ?></p>
      </div>
      <div style="width:22px;height:22px;border:1.5px solid <?php echo $uc[1]; ?>;border-radius:50%;flex-shrink:0;margin-top:3px;cursor:pointer;" onclick="this.style.background='<?php echo $uc[2]; ?>';this.innerHTML='<span style=\'color:#fff;font-size:11px;font-weight:700;\'>✓</span>';" title="Mark done"></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- 💼 Conversion Reminder -->
<div class="vx-card" style="background:var(--ink);border-color:transparent;">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:20px;flex-wrap:wrap;">
    <div style="flex:1;min-width:240px;">
      <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.4);margin-bottom:8px;">Business Reality Check</div>
      <h3 style="font-family:'Inter',sans-serif;font-size:20px;font-weight:700;color:#fff;margin:0 0 6px;letter-spacing:-.3px;">Are Visitors Taking Action?</h3>
      <p style="font-size:13px;color:rgba(255,255,255,.52);line-height:1.7;margin:0;">Traffic without action is just numbers. Make sure visitors are actually doing something valuable when they land on your site.</p>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;min-width:300px;">
      <?php
      $conv_items = [
        ['📞','Contact form submissions','Are people reaching out?'],
        ['📥','PDF or resource downloads','Are they grabbing your content?'],
        ['✉️','Email signup clicks','Are they joining your list?'],
        ['🛒','Purchase or booking clicks','Are they buying or booking?'],
      ];
      foreach ($conv_items as $ci):
      ?>
      <div style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:12px 14px;">
        <div style="font-size:18px;margin-bottom:6px;"><?php echo $ci[0]; ?></div>
        <div style="font-size:12px;font-weight:600;color:#fff;line-height:1.3;margin-bottom:3px;"><?php echo $ci[1]; ?></div>
        <div style="font-size:11px;color:rgba(255,255,255,.38);"><?php echo $ci[2]; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,.1);display:flex;align-items:center;gap:10px;">
    <span style="font-size:14px;">💡</span>
    <p style="margin:0;font-size:12.5px;color:rgba(255,255,255,.5);line-height:1.6;">To track these, set up <strong style="color:rgba(255,255,255,.75);">GA4 Events</strong> for button clicks and form submissions, or ask your developer to add event tracking. Even tracking one action is better than none.</p>
  </div>
</div>

<?php else:
$days_waiting = (int) floor( ( time() - (int) get_option('vx_installed_at', time()) ) / DAY_IN_SECONDS );
?>

<style>
.vx-adv-toggle{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;color:var(--muted);background:none;border:none;padding:0;font-family:inherit}
.vx-adv-toggle:hover{color:var(--ink)}
.vx-adv-body{margin-top:16px}
.vx-adv-body.open{display:block}
</style>

<?php if ( $days_waiting >= 7 ) : ?>
<div style="display:flex;align-items:center;gap:12px;background:var(--amber-l);border:1.5px solid var(--amber-b);border-radius:var(--r);padding:13px 18px;">
  <span style="font-size:20px;flex-shrink:0;">📉</span>
  <p style="margin:0;font-size:13px;font-weight:600;color:var(--amber);line-height:1.5;">
    <strong style="color:var(--ink2);">Your traffic data isn't connected yet.</strong>
    You may be missing important visitor trends right now.
  </p>
</div>
<?php endif; ?>

<!-- HERO: unlock CTA -->
<div style="background:var(--ink);border-radius:var(--r-lg);padding:36px 40px;display:grid;grid-template-columns:1fr 360px;gap:36px;align-items:center;position:relative;overflow:hidden;box-shadow:var(--sh-lg);">
  <div style="position:absolute;inset:0;background:radial-gradient(circle at 85% 50%,rgba(255,255,255,.05),transparent 55%);pointer-events:none;"></div>
  <div style="position:relative;">
    <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);border-radius:var(--r-pill);padding:5px 14px;font-size:11px;font-weight:700;color:#fff;letter-spacing:.05em;margin-bottom:16px;">🔒 NOT CONNECTED</div>
    <h2 style="font-size:28px;font-weight:800;color:#fff;margin:0 0 12px;line-height:1.2;letter-spacing:-.4px;">See exactly who visits your site — and where they come from</h2>
    <p style="font-size:14px;color:rgba(255,255,255,.5);margin:0 0 22px;line-height:1.65;">Google Analytics tracks every visitor, every page, every traffic source. Connect once and see it all in plain English — no dashboards to navigate.</p>
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:24px;">
      <?php foreach([
        'How many real visitors your site gets each week',
        'Which pages attract the most traffic',
        'Where your visitors come from — Google, social, direct',
        'Which traffic sources are growing or shrinking',
      ] as $b): ?>
        <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:rgba(255,255,255,.65);">
          <span style="width:18px;height:18px;background:rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0;color:#fff;">✓</span>
          <?php echo esc_html($b); ?>
        </div>
      <?php endforeach; ?>
    </div>
    <button onclick="document.getElementById('vx-ga-connect-form').scrollIntoView({behavior:'smooth'})"
      style="display:inline-flex;align-items:center;gap:8px;background:#fff;color:var(--ink);border:none;border-radius:var(--r-pill);padding:14px 28px;font-size:15px;font-weight:800;cursor:pointer;font-family:inherit;">
      🔓 Connect Google Analytics
    </button>
    <p style="margin:10px 0 0;font-size:12px;color:rgba(255,255,255,.3);">Takes less than 2 minutes</p>
  </div>

  <!-- Mock preview -->
  <div style="position:relative;">
    <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.3);margin-bottom:12px;">Example — what you'll see after connecting</div>
    <div style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:var(--r-lg);padding:20px;display:flex;flex-direction:column;gap:14px;">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <?php foreach([
          ['👥', '1,284', 'Monthly visitors'],
          ['📄', '3,940', 'Page views'],
          ['🔍', '42%', 'From Google'],
          ['⏱', '2m 14s', 'Avg. time on site'],
        ] as [$ic,$val,$lbl]): ?>
          <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:14px 14px 12px;">
            <div style="font-size:16px;margin-bottom:4px;"><?php echo $ic; ?></div>
            <div style="font-size:22px;font-weight:800;color:#fff;line-height:1;"><?php echo $val; ?></div>
            <div style="font-size:10.5px;color:rgba(255,255,255,.35);margin-top:3px;"><?php echo esc_html($lbl); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:13px 15px;border-left:3px solid #f59e0b;">
        <div style="font-size:10px;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;">Top opportunity</div>
        <div style="font-size:13px;font-weight:600;color:#fff;line-height:1.5;">Your homepage gets 68% of traffic. 4 other pages have high potential but almost no visitors.</div>
      </div>
      <div style="text-align:center;font-size:11.5px;color:rgba(255,255,255,.2);font-style:italic;">Your real data will appear here after connecting</div>
    </div>
  </div>
</div>

<!-- CONNECT FORM -->
<div class="vx-card" id="vx-ga-connect-form">
  <h3 class="vx-card-title">Connect Google Analytics 4</h3>
  <p style="margin:-8px 0 20px;font-size:13px;color:var(--muted);">Enter your credentials below — takes less than 2 minutes.</p>
  <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" style="max-width:560px;">
    <?php wp_nonce_field('vx_save_ga'); ?>
    <input type="hidden" name="action" value="vx_save_ga" />
    <div class="vx-fg">
      <label class="vx-lbl">GA4 Property ID <span style="font-weight:400;color:var(--muted)">(numeric, not the G- ID)</span></label>
      <input type="text" name="vx_ga_property_id" class="vx-input" value="<?php echo esc_attr($property_id); ?>" placeholder="123456789" />
    </div>
    <div class="vx-fg">
      <label class="vx-lbl">Service Account JSON Key</label>
      <textarea name="vx_ga_json_key" class="vx-input vx-textarea" rows="6" style="font-family:monospace;font-size:12px;" placeholder='Paste the full contents of your .json key file here...'><?php echo esc_textarea($json_key); ?></textarea>
      <p class="vx-hint">Open the downloaded .json file in any text editor, select all, copy, paste here.</p>
    </div>
    <button type="submit" class="vx-btn-primary">Save & Connect →</button>
    <p style="margin:10px 0 0;font-size:12px;color:var(--muted);">⏱ Takes less than 2 minutes</p>
  </form>

  <!-- Advanced setup collapsed -->
  <div style="margin-top:28px;padding-top:20px;border-top:1px solid var(--border);">
    <button class="vx-adv-toggle" onclick="this.nextElementSibling.classList.toggle('open');this.querySelector('.vx-adv-arrow').textContent=this.nextElementSibling.classList.contains('open')?'▲':'▼'">
      <span>🔧 Manual Setup Instructions (Advanced)</span>
      <span class="vx-adv-arrow">▲</span>
    </button>
    <div class="vx-adv-body open">
      <div class="vx-setup-steps" style="margin-top:0;">
        <div class="vx-setup-step"><div class="vx-step-num">1</div><div class="vx-step-body"><strong>Create a GA4 Property</strong><p>Go to <a href="https://analytics.google.com" target="_blank">analytics.google.com</a> → Admin → Create Property → Web. Copy the <strong>numeric Property ID</strong> (like <code>123456789</code>).</p></div></div>
        <div class="vx-setup-step"><div class="vx-step-num">2</div><div class="vx-step-body"><strong>Create a Service Account</strong><p>Go to <a href="https://console.cloud.google.com" target="_blank">console.cloud.google.com</a> → APIs & Services → Enable <strong>"Google Analytics Data API"</strong> → Credentials → Create Service Account → Download the JSON key.</p></div></div>
        <div class="vx-setup-step"><div class="vx-step-num">3</div><div class="vx-step-body"><strong>Grant access in GA4</strong><p>In GA4: Admin → Property Access Management → Add User → paste service account email → Role: <strong>Viewer</strong>.</p></div></div>
        <div class="vx-setup-step"><div class="vx-step-num">4</div><div class="vx-step-body"><strong>Paste credentials above and save</strong></div></div>
      </div>
    </div>
  </div>
</div>

<?php endif; ?>

<script>
document.getElementById('vx-refresh-ga')?.addEventListener('click',function(){
  this.textContent='Refreshing…';this.disabled=true;
  const fd=new FormData();fd.append('action','vx_refresh_ga');fd.append('nonce',vxSeo.nonce);
  fetch(vxSeo.ajaxUrl,{method:'POST',body:fd}).then(r=>r.json()).then(res=>{
    if(res.success)location.reload();else{alert('Error: '+res.data);this.textContent='↻ Refresh';this.disabled=false;}
  });
});
</script>

<?php require __DIR__ . '/layout-footer.php'; ?>
