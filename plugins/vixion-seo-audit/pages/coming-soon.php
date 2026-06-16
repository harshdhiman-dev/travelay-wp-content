<?php
/**
 * Reusable coming-soon teaser page.
 * Caller sets $vx_cs array before requiring this file.
 */
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

$icon       = $vx_cs['icon']       ?? '🔒';
$title      = $vx_cs['title']      ?? 'Coming Soon';
$tagline    = $vx_cs['tagline']    ?? 'This feature is on the roadmap.';
$bullets    = $vx_cs['bullets']    ?? [];
$cta_label  = $vx_cs['cta_label']  ?? 'Notify Me When It Launches';
$notify_key = $vx_cs['notify_key'] ?? 'feature';
$eta        = $vx_cs['eta']        ?? '';

// Handle email save
$saved_msg = '';
if ( isset($_POST['vx_notify_email'], $_POST['vx_notify_nonce'])
     && wp_verify_nonce( $_POST['vx_notify_nonce'], 'vx_notify_' . $notify_key ) ) {
    $email = sanitize_email( $_POST['vx_notify_email'] );
    if ( is_email($email) ) {
        $list = get_option( 'vx_early_access_' . $notify_key, [] );
        if ( ! in_array( $email, $list ) ) {
            $list[] = $email;
            update_option( 'vx_early_access_' . $notify_key, $list );
        }
        $saved_msg = 'success';
    } else {
        $saved_msg = 'error';
    }
}

$user_email = wp_get_current_user()->user_email;
?>

<style>
.vx-cs-hero{background:var(--ink);border-radius:var(--r-lg);padding:40px 44px;position:relative;overflow:hidden;box-shadow:var(--sh-lg);display:grid;grid-template-columns:1fr 320px;gap:40px;align-items:center}
.vx-cs-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 88% 50%,rgba(255,255,255,.05),transparent 55%);pointer-events:none}
.vx-cs-bullets{display:flex;flex-direction:column;gap:9px;margin-top:20px}
.vx-cs-bullet{display:flex;align-items:center;gap:10px;font-size:13.5px;color:rgba(255,255,255,.65);line-height:1.4}
.vx-cs-bullet-dot{width:18px;height:18px;background:rgba(255,255,255,.1);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;flex-shrink:0}
.vx-notify-form{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:var(--r-lg);padding:24px;position:relative}
.vx-cs-roadmap{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.vx-cs-step{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:16px 18px;position:relative}
.vx-cs-step.done{border-color:var(--green-b);background:var(--green-l)}
.vx-cs-step.now{border-color:var(--ink);border-width:2px}
.vx-unlock-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
.vx-unlock-item{display:flex;align-items:flex-start;gap:11px;padding:13px 15px;background:var(--surface2);border:1px solid var(--border);border-radius:var(--r)}
.vx-unlock-dot{width:22px;height:22px;background:var(--ink);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;margin-top:1px}
@media(max-width:900px){.vx-cs-hero{grid-template-columns:1fr}.vx-cs-roadmap{grid-template-columns:repeat(2,1fr)}.vx-unlock-grid{grid-template-columns:1fr}}
</style>

<!-- PAGE HEADER -->
<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title"><?php echo $icon; ?> <?php echo esc_html($title); ?></h1>
    <p class="vx-page-sub">Launching soon &nbsp;·&nbsp; <span style="color:var(--green);font-weight:600;">Early access available</span></p>
  </div>
  <span style="display:inline-flex;align-items:center;gap:7px;background:var(--amber-l);border:1.5px solid var(--amber-b);color:var(--amber);border-radius:var(--r-pill);padding:9px 18px;font-size:13px;font-weight:700;">
    🚀 Coming Soon<?php echo $eta ? ' · ' . esc_html($eta) : ''; ?>
  </span>
</div>

<!-- HERO: teaser + email capture -->
<div class="vx-cs-hero">

  <!-- Left: What's coming -->
  <div style="position:relative;">
    <div style="display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);border-radius:var(--r-pill);padding:5px 14px;font-size:11px;font-weight:700;color:rgba(255,255,255,.7);letter-spacing:.06em;margin-bottom:16px;">
      ⚡ NEXT FEATURE LAUNCHING
    </div>
    <h2 style="font-size:28px;font-weight:800;color:#fff;margin:0 0 10px;line-height:1.2;letter-spacing:-.4px;">
      <?php echo esc_html($tagline); ?>
    </h2>
    <?php if (!empty($bullets)) : ?>
    <div class="vx-cs-bullets">
      <?php foreach ($bullets as $b) : ?>
        <div class="vx-cs-bullet">
          <div class="vx-cs-bullet-dot">✓</div>
          <?php echo esc_html($b); ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right: Email signup -->
  <div class="vx-notify-form">
    <?php if ($saved_msg === 'success') : ?>
      <div style="text-align:center;padding:16px 0;">
        <div style="font-size:40px;margin-bottom:12px;">🎉</div>
        <h3 style="font-size:17px;font-weight:800;color:#fff;margin:0 0 8px;">You're on the list!</h3>
        <p style="font-size:13px;color:rgba(255,255,255,.45);margin:0;line-height:1.6;">
          We'll email you the moment <strong style="color:#fff;"><?php echo esc_html($title); ?></strong> launches.<br>Early users get it free.
        </p>
      </div>
    <?php else : ?>
      <h3 style="font-size:15px;font-weight:800;color:#fff;margin:0 0 5px;"><?php echo esc_html($cta_label); ?></h3>
      <p style="font-size:12.5px;color:rgba(255,255,255,.4);margin:0 0 18px;line-height:1.6;">Enter your email — we'll notify you the moment it's live. Early access is free.</p>
      <?php if ($saved_msg === 'error') : ?>
        <div style="background:var(--red-l);color:var(--red);border-radius:8px;padding:9px 13px;font-size:12.5px;font-weight:600;margin-bottom:12px;">Please enter a valid email address.</div>
      <?php endif; ?>
      <form method="POST">
        <?php wp_nonce_field('vx_notify_' . $notify_key, 'vx_notify_nonce'); ?>
        <input type="email" name="vx_notify_email"
          value="<?php echo esc_attr($user_email); ?>"
          placeholder="your@email.com"
          style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid rgba(255,255,255,.15);background:rgba(255,255,255,.08);color:#fff;font-size:14px;font-family:inherit;outline:none;box-sizing:border-box;margin-bottom:10px;"
          onfocus="this.style.borderColor='rgba(255,255,255,.5)'"
          onblur="this.style.borderColor='rgba(255,255,255,.15)'" />
        <button type="submit"
          style="width:100%;padding:13px;background:#fff;color:var(--ink);border:none;border-radius:var(--r-pill);font-size:14px;font-weight:800;cursor:pointer;font-family:inherit;letter-spacing:-.1px;transition:opacity .15s;"
          onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
          Notify Me When It Launches →
        </button>
      </form>
      <p style="font-size:11px;color:rgba(255,255,255,.22);margin:10px 0 0;text-align:center;">No spam. One email when it's ready.</p>
    <?php endif; ?>
  </div>

</div>

<!-- ROADMAP STRIP -->
<div class="vx-cs-roadmap">
  <?php
  $roadmap = [
    [ 'done', '✅', 'Health Checks',      'Live — scan your site now'             ],
    [ 'done', '✅', 'AI Action Plan',     'Live — prioritised fix list'           ],
    [ 'now',  '🔄', $title,              'Building now — join early access'      ],
    [ 'next', '🔒', 'Pro Plans',         'Unlock all advanced features'          ],
  ];
  foreach ($roadmap as [$state, $ico, $name, $desc]) :
    $cls = $state === 'done' ? 'done' : ($state === 'now' ? 'now' : '');
  ?>
    <div class="vx-cs-step <?php echo $cls; ?>">
      <?php if ($state === 'now') : ?>
        <div style="position:absolute;top:10px;right:10px;font-size:9px;font-weight:700;background:var(--ink);color:#fff;padding:2px 8px;border-radius:var(--r-pill);">NEXT</div>
      <?php endif; ?>
      <div style="font-size:20px;margin-bottom:7px;"><?php echo $ico; ?></div>
      <div style="font-size:13px;font-weight:700;color:var(--ink);margin-bottom:3px;"><?php echo esc_html($name); ?></div>
      <div style="font-size:11.5px;color:var(--muted);line-height:1.5;"><?php echo esc_html($desc); ?></div>
    </div>
  <?php endforeach; ?>
</div>

<!-- WHAT YOU UNLOCK -->
<?php if (!empty($bullets)) : ?>
<div class="vx-card">
  <h3 class="vx-card-title" style="margin-bottom:16px;">What <?php echo esc_html($title); ?> will do for you</h3>
  <div class="vx-unlock-grid">
    <?php foreach ($bullets as $b) : ?>
      <div class="vx-unlock-item">
        <div class="vx-unlock-dot">✓</div>
        <span style="font-size:13px;font-weight:600;color:var(--ink2);line-height:1.4;"><?php echo esc_html($b); ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/layout-footer.php'; ?>
