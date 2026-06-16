<?php
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/layout-header.php';

$user   = wp_get_current_user();
$uname  = $user->display_name;
$uemail = $user->user_email;

// Context-aware auto-fill: if referred from report page
$ref_topic = '';
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if ( strpos($ref, 'vixion-seo-report') !== false )         $ref_topic = 'Report results question';
elseif ( strpos($ref, 'vixion-seo-new-audit') !== false )  $ref_topic = 'Audit not running / error';
elseif ( strpos($ref, 'vixion-seo-subscription') !== false) $ref_topic = 'Subscription / billing';
?>

<style>
.vx-help-guide{display:flex;flex-direction:column;gap:8px}
.vx-help-row{display:flex;align-items:center;gap:14px;padding:12px 16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r);cursor:pointer;text-decoration:none;transition:border-color .15s,box-shadow .15s}
.vx-help-row:hover{border-color:var(--ink);box-shadow:var(--sh-sm)}
.vx-help-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.vx-help-icon-wa{background:#dcfce7;color:#16a34a}
.vx-help-icon-em{background:var(--surface2);color:var(--ink)}
.vx-help-icon-ca{background:var(--blue-l);color:var(--blue)}
.vx-help-body{flex:1}
.vx-help-body strong{font-size:13.5px;font-weight:700;color:var(--ink);display:block;line-height:1.2}
.vx-help-body span{font-size:12px;color:var(--muted)}
.vx-help-arrow{font-size:16px;color:var(--muted);flex-shrink:0}
.vx-help-tag{font-size:10px;font-weight:700;padding:3px 9px;border-radius:var(--r-pill);border:1px solid;flex-shrink:0}
.vx-tag-fast{background:#dcfce7;color:#16a34a;border-color:#86efac}
.vx-tag-24h{background:var(--surface2);color:var(--muted);border-color:var(--border)}
.vx-tag-free{background:var(--blue-l);color:var(--blue);border-color:var(--blue-b)}
/* Common fixes */
.vx-fix-item{display:flex;align-items:flex-start;gap:12px;padding:13px 16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r);transition:border-color .15s}
.vx-fix-item:hover{border-color:var(--ink)}
.vx-fix-num{width:24px;height:24px;border-radius:50%;background:var(--ink);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px}
.vx-fix-body strong{font-size:13px;font-weight:700;color:var(--ink);display:block;margin-bottom:3px}
.vx-fix-body p{font-size:12.5px;color:var(--ink3);margin:0;line-height:1.5}
</style>

<!-- PAGE HEADER -->
<div class="vx-page-header">
  <div>
    <h1 class="vx-page-title">We've Got You</h1>
    <p class="vx-page-sub">Real support from the Vixion team — we reply fast &nbsp;·&nbsp; <strong style="color:var(--green);">Avg response: 1–2 hours</strong></p>
  </div>
  <div style="display:flex;align-items:center;gap:16px;">
    <div style="font-size:12px;color:var(--muted);text-align:right;line-height:1.7;">
      <div>💬 <strong style="color:var(--ink);">100+</strong> sites helped</div>
      <div>⭐ Available 9am–7pm IST</div>
    </div>
  </div>
</div>

<!-- ① FASTEST WAY TO GET HELP guide -->
<div class="vx-card" style="padding:20px 24px;">
  <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);margin-bottom:14px;">What's the fastest way to get help?</div>
  <div class="vx-help-guide">

    <a href="https://wa.me/918264655849?text=Hi+Vixion%2C+I+need+help+with+the+SEO+Audit+plugin" target="_blank" class="vx-help-row">
      <div class="vx-help-icon vx-help-icon-wa">💬</div>
      <div class="vx-help-body">
        <strong>WhatsApp — for urgent issues</strong>
        <span>Stuck on something now? Message us directly. Typically reply within 1 hour.</span>
      </div>
      <span class="vx-help-tag vx-tag-fast">Fastest</span>
      <span class="vx-help-arrow">→</span>
    </a>

    <a href="mailto:hello@vixion.in?subject=Plugin Support" class="vx-help-row">
      <div class="vx-help-icon vx-help-icon-em">✉️</div>
      <div class="vx-help-body">
        <strong>Email — for detailed questions or report help</strong>
        <span>Send screenshots, explain your situation fully. We reply within 24 hours.</span>
      </div>
      <span class="vx-help-tag vx-tag-24h">24h reply</span>
      <span class="vx-help-arrow">→</span>
    </a>

    <a href="https://calendly.com/hello-visionx/30min" target="_blank" class="vx-help-row">
      <div class="vx-help-icon vx-help-icon-ca">📞</div>
      <div class="vx-help-body">
        <strong>Book a free walkthrough — if you want to understand your report</strong>
        <span>Need help understanding your results? We'll walk through your report with you live.</span>
      </div>
      <span class="vx-help-tag vx-tag-free">Free</span>
      <span class="vx-help-arrow">→</span>
    </a>

  </div>
</div>

<!-- TICKET FORM + FAQ GRID -->
<div class="vx-support-grid">

  <!-- ② TICKET FORM with smart placeholder + context auto-fill -->
  <div class="vx-card">
    <h3 class="vx-card-title">Submit a Ticket</h3>
    <p class="vx-form-sub">We'll reply to your email · You'll receive a ticket ID</p>

    <div id="vx-ticket-ok"  class="vx-notice vx-notice-ok"  style="display:none;"></div>
    <div id="vx-ticket-err" class="vx-notice vx-notice-err" style="display:none;"></div>

    <div class="vx-form-2col">
      <div class="vx-fg">
        <label class="vx-lbl">Your Name</label>
        <input type="text" id="vx-sn" class="vx-input" value="<?php echo esc_attr($uname); ?>" />
      </div>
      <div class="vx-fg">
        <label class="vx-lbl">Email</label>
        <input type="email" id="vx-se" class="vx-input" value="<?php echo esc_attr($uemail); ?>" />
      </div>
    </div>

    <div class="vx-fg">
      <label class="vx-lbl">Topic</label>
      <select id="vx-ss" class="vx-input vx-select">
        <option value="">— What do you need help with? —</option>
        <option <?php selected($ref_topic, 'Audit not running / error'); ?>>Audit not running / error</option>
        <option <?php selected($ref_topic, 'Subscription / billing'); ?>>Subscription / billing</option>
        <option <?php selected($ref_topic, 'Report results question'); ?>>I don't understand my report results</option>
        <option>My score seems wrong</option>
        <option>Feature request</option>
        <option>Bug report</option>
        <option>Early access to Pro plan</option>
        <option>Other</option>
      </select>
      <?php if ($ref_topic) : ?>
        <div style="font-size:11.5px;color:var(--green);font-weight:600;margin-top:6px;">✓ Topic auto-filled based on the page you came from</div>
      <?php endif; ?>
    </div>

    <div class="vx-fg">
      <label class="vx-lbl">Message</label>
      <textarea id="vx-sm" class="vx-input vx-textarea" rows="5"
        placeholder="Tell us what's confusing or not working — screenshots and specific details help us reply faster."></textarea>
    </div>

    <button id="vx-submit-ticket" class="vx-btn-primary vx-btn-large" style="width:100%;">
      <span class="dashicons dashicons-email-alt"></span>
      <span id="vx-submit-text">Send Message</span>
    </button>
    <p style="margin:10px 0 0;text-align:center;font-size:12px;color:var(--muted);">We reply to every ticket — usually within 1–2 hours during business hours</p>
  </div>

  <!-- ③ FAQ with human questions + sticky position -->
  <div class="vx-faq">

    <div class="vx-card" style="padding:18px 20px;">
      <h3 class="vx-card-title" style="margin-bottom:14px;">Common Questions</h3>
      <?php foreach ( [
        [
          'Why does my health score say "Needs Work"?',
          'Usually it means a few important things are missing — like meta descriptions, image alt text, or a slow server. Go to your Health Report to see the exact issues with step-by-step fixes.'
        ],
        [
          'Can I scan competitor websites too?',
          'Yes! Go to "Your Site vs Competitors" in the menu, add competitor URLs, and click Run Comparison. We\'ll show you exactly where you\'re winning and where they have an edge.'
        ],
        [
          'What is server speed (TTFB) and why does it matter?',
          'TTFB = how fast your server starts responding to a visitor. Under 600ms is good. Slow TTFB hurts rankings. Fix it with a caching plugin like WP Rocket or better hosting.'
        ],
        [
          'When will the Pro plan launch?',
          'Soon! Message us on WhatsApp or use the form to join the early access list. All current features remain free during beta.'
        ],
        [
          'How do I fix "search engines blocked"?',
          'Go to WP Admin → Settings → Reading → uncheck "Discourage search engines from indexing this site" → Save Changes. This is critical — it can completely hide your site from Google.'
        ],
        [
          'Will my history survive plugin updates?',
          'Yes — your scan history is stored in a custom database table that persists across all updates. Your data is safe.'
        ],
      ] as $faq ) : ?>
        <div class="vx-faq-item">
          <div class="vx-faq-q">
            <?php echo esc_html($faq[0]); ?>
            <span class="dashicons dashicons-arrow-down-alt2 vx-faq-chevron"></span>
          </div>
          <div class="vx-faq-a"><?php echo esc_html($faq[1]); ?></div>
        </div>
      <?php endforeach; ?>
    </div>

  </div><!-- /right col -->

</div><!-- /support grid -->

<!-- QUICK FIX GUIDES — full width below -->
<div class="vx-card" style="padding:20px 24px;">
  <h3 class="vx-card-title" style="margin-bottom:16px;">Quick Fix Guides</h3>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
    <?php foreach ( [
      [
        'How to write a good meta description',
        'Go to your SEO plugin → edit each page → add a 120–160 character description that explains what the page is about and why someone should click.',
      ],
      [
        'How to fix your homepage title',
        'Go to your SEO plugin → Homepage settings → write a title 50–60 characters long with your main keyword near the start.',
      ],
      [
        'How to add alt text to images',
        'Go to Media Library → click each image → fill in the Alt Text field. Describe the image naturally and include keywords where relevant.',
      ],
      [
        'How to unblock search engines',
        'WP Admin → Settings → Reading → uncheck "Discourage search engines" → Save. Then re-run your health check.',
      ],
      [
        'How to improve your health score fast',
        'Fix the Critical issues first — they carry the most weight. Each critical fix can add 3–5 points to your score.',
      ],
      [
        'How to speed up your server (TTFB)',
        'Install a caching plugin like LiteSpeed Cache or WP Rocket. A fast server response (under 600ms) improves both rankings and user experience.',
      ],
    ] as $idx => $fix ) : ?>
      <div class="vx-fix-item" style="align-items:flex-start;">
        <div class="vx-fix-num" style="flex-shrink:0;margin-top:2px;"><?php echo $idx + 1; ?></div>
        <div class="vx-fix-body">
          <strong><?php echo esc_html($fix[0]); ?></strong>
          <p><?php echo esc_html($fix[1]); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
(function($){
  $('#vx-submit-ticket').on('click', function(){
    const name    = $('#vx-sn').val().trim();
    const email   = $('#vx-se').val().trim();
    const subject = $('#vx-ss').val().trim();
    const message = $('#vx-sm').val().trim();
    $('#vx-ticket-ok,#vx-ticket-err').hide();

    if ( ! name || ! email || ! subject || ! message ) {
      $('#vx-ticket-err').text('Please fill in all fields — the more detail you give, the faster we can help.').show();
      return;
    }

    $(this).prop('disabled', true);
    $('#vx-submit-text').text('Sending…');

    $.post(vxSeo.ajaxUrl, {action:'vx_submit_support', nonce:vxSeo.nonce, name, email, subject, message})
    .done(function(r){
      if (r.success) {
        $('#vx-ticket-ok').html('✓ Message received! Ticket #' + r.data.ticket_id + ' — we\'ll reply to <strong>' + email + '</strong> within a few hours.').show();
        $('#vx-sm').val('');
        $('#vx-ss').val('');
        $('html,body').animate({scrollTop: $('#vx-ticket-ok').offset().top - 80}, 300);
      } else {
        $('#vx-ticket-err').text('Something went wrong: ' + (r.data || 'Please try again.')).show();
      }
    })
    .fail(function(){
      $('#vx-ticket-err').text('Server error — please try WhatsApp or email directly.').show();
    })
    .always(function(){
      $('#vx-submit-ticket').prop('disabled', false);
      $('#vx-submit-text').text('Send Message');
    });
  });
})(jQuery);
</script>

<script>
// FAQ accordion — jQuery fallback for WP admin environments
jQuery(function($){
  $('.vx-faq-q').on('click', function(){
    $(this).closest('.vx-faq-item').toggleClass('open');
  });
});

<?php require __DIR__ . '/layout-footer.php'; ?>
