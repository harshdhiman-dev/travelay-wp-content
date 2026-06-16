<?php
defined( 'ABSPATH' ) || exit;

// Style coming-soon items: dimmed with a lock icon appended
add_action( 'admin_head', function () { ?>
<style>
#adminmenu .vx-cs-item > a::after{content:'🔒';font-size:9px;margin-left:6px;opacity:.5;vertical-align:middle}
#adminmenu .vx-cs-item > a{opacity:.7}
#adminmenu .vx-cs-item > a:hover{opacity:1}
</style>
<?php } );

// Inject JS to add .vx-cs-item class to coming-soon menu items
add_action( 'admin_footer', function () {
    $cs = json_encode(['vixion-seo-keywords','vixion-seo-ai-briefs','vixion-seo-reports','vixion-seo-subscription']);
    echo "<script>(function(){var s={$cs};s.forEach(function(slug){var el=document.querySelector('#adminmenu a[href*=\"page='+slug+'\"]');if(el&&el.parentElement)el.parentElement.classList.add('vx-cs-item');});})();</script>";
} );

add_action( 'admin_menu', function () {
    add_menu_page(
        'Vixion Health', 'Vixion Health', 'manage_options',
        'vixion-seo-audit',
        function () { require VX_SEO_DIR . 'pages/dashboard.php'; },
        'dashicons-chart-line', 30
    );

    $sub = [
        // ── Live ─────────────────────────────────────────────
        [ 'vixion-seo-new-audit',      'Run Health Check',   'new-audit.php'           ],
        [ 'vixion-seo-report',         'Latest Report',      'report.php'              ],
        [ 'vixion-seo-history',        'Progress',           'history.php'             ],
        [ 'vixion-seo-analytics',      'Traffic Health',     'google-analytics.php'    ],
        [ 'vixion-seo-search-console', 'Google Visibility',  'search-console.php'      ],
        [ 'vixion-seo-competitors',    'Competitors',        'competitor-analysis.php' ],

        // ── Coming Soon (elegant teasers, not hidden) ─────────
        [ 'vixion-seo-keywords',       'Keyword Tracking',   'keyword-tracker.php'     ],
        [ 'vixion-seo-ai-briefs',      'AI Optimisation',    'ai-briefs.php'           ],
        [ 'vixion-seo-reports',        'Advanced Reports',   'weekly-reports.php'      ],
        [ 'vixion-seo-subscription',   'Pro Plans',          'subscription.php'        ],

        // ── Utility ───────────────────────────────────────────
        [ 'vixion-seo-support',        'Support',            'support.php'             ],
        [ 'vixion-seo-settings',       'Settings',           'settings.php'            ],
    ];

    foreach ( $sub as $item ) {
        $file = $item[2];
        add_submenu_page(
            'vixion-seo-audit', $item[1], $item[1], 'manage_options', $item[0],
            function () use ( $file ) { require VX_SEO_DIR . 'pages/' . $file; }
        );
    }
} );
