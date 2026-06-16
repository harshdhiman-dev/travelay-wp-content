<?php
defined( 'ABSPATH' ) || exit;

/**
 * Run a full audit of the current WordPress site using real data
 */
function vx_run_site_audit() {

    $site_url   = get_site_url();
    $home_url   = home_url( '/' );
    $start_time = microtime( true );

    /* ── Fetch homepage HTML ─────────────────── */
    $response = wp_remote_get( $home_url, [
        'timeout'    => 30,
        'user-agent' => 'VixionSEOAudit/3.0 WordPress/' . get_bloginfo('version'),
        'sslverify'  => false,
    ] );

    $html        = '';
    $status_code = 0;
    $ttfb_ms     = round( ( microtime( true ) - $start_time ) * 1000 );

    if ( ! is_wp_error( $response ) ) {
        $html        = wp_remote_retrieve_body( $response );
        $status_code = (int) wp_remote_retrieve_response_code( $response );
    }

    /* ── WordPress DB data ───────────────────── */
    global $wpdb;

    // Published posts & pages
    $total_posts   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post'" );
    $total_pages   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='page'" );

    // Posts missing SEO title (checking postmeta for common SEO plugins)
    $posts_no_seo_title = (int) $wpdb->get_var( "
        SELECT COUNT(*) FROM {$wpdb->posts} p
        WHERE p.post_status='publish' AND p.post_type IN ('post','page')
        AND p.ID NOT IN (
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key IN ('_yoast_wpseo_title','rank_math_title','_seopress_titles_title','_aioseo_title')
            AND meta_value != ''
        )
    " );

    // Posts missing meta description
    $posts_no_meta_desc = (int) $wpdb->get_var( "
        SELECT COUNT(*) FROM {$wpdb->posts} p
        WHERE p.post_status='publish' AND p.post_type IN ('post','page')
        AND p.ID NOT IN (
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key IN ('_yoast_wpseo_metadesc','rank_math_description','_seopress_titles_desc','_aioseo_description')
            AND meta_value != ''
        )
    " );

    // Images without alt text (attachment posts)
    $images_no_alt = (int) $wpdb->get_var( "
        SELECT COUNT(*) FROM {$wpdb->posts} p
        WHERE p.post_type = 'attachment'
        AND p.post_mime_type LIKE 'image/%'
        AND p.post_status = 'inherit'
        AND p.ID NOT IN (
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_wp_attachment_image_alt'
            AND meta_value != ''
        )
    " );
    $total_images = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='attachment' AND post_mime_type LIKE 'image/%'" );

    // Orphan posts (no category)
    $posts_no_category = (int) $wpdb->get_var( "
        SELECT COUNT(*) FROM {$wpdb->posts} p
        WHERE p.post_type='post' AND p.post_status='publish'
        AND p.ID NOT IN (
            SELECT object_id FROM {$wpdb->term_relationships} tr
            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.taxonomy = 'category' AND tt.term_id != 1
        )
    " );

    // Drafts count
    $draft_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='draft' AND post_type IN ('post','page')" );

    // WordPress version
    $wp_version      = get_bloginfo( 'version' );
    $wp_version_ok   = version_compare( $wp_version, '6.0', '>=' );

    // Active plugins count
    $active_plugins  = get_option( 'active_plugins', [] );
    $plugin_count    = count( $active_plugins );

    // Theme
    $theme           = wp_get_theme();
    $theme_name      = $theme->get( 'Name' );
    $theme_version   = $theme->get( 'Version' );

    // Permalink structure
    $permalink       = get_option( 'permalink_structure', '' );
    $good_permalink  = ! empty( $permalink ) && $permalink !== '/?p=%postname%';

    // Site language
    $site_language   = get_bloginfo( 'language' );

    // Comments open on posts?
    $comments_open = get_option( 'default_comment_status' ) === 'open';

    // Parse homepage HTML for on-page data
    $title     = '';
    $desc      = '';
    $h1_count  = 0;
    $h2_count  = 0;
    $has_og    = false;
    $has_schema = false;
    $has_viewport = false;
    $has_canonical = false;
    $html_size_kb = 0;
    $word_count = 0;

    if ( $html ) {
        preg_match( '/<title[^>]*>(.*?)<\/title>/is', $html, $t );
        $title = isset( $t[1] ) ? trim( strip_tags( $t[1] ) ) : '';

        preg_match( '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)/i', $html, $d );
        preg_match( '/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description/i', $html, $d2 );
        $desc = isset( $d[1] ) ? trim( $d[1] ) : ( isset( $d2[1] ) ? trim( $d2[1] ) : '' );

        preg_match_all( '/<h1[^>]*>/i', $html, $h1m );
        preg_match_all( '/<h2[^>]*>/i', $html, $h2m );
        $h1_count = count( $h1m[0] );
        $h2_count = count( $h2m[0] );

        $has_og        = (bool) preg_match( '/property=["\']og:/i', $html );
        $has_schema    = (bool) preg_match( '/application\/ld\+json/i', $html );
        $has_viewport  = (bool) preg_match( '/name=["\']viewport["\']/i', $html );
        $has_canonical = (bool) preg_match( '/rel=["\']canonical["\']/i', $html );
        $html_size_kb  = round( strlen( $html ) / 1024, 1 );
        $word_count    = str_word_count( strip_tags( preg_replace( '/<(script|style)[^>]*>.*?<\/(script|style)>/is', '', $html ) ) );
    }

    // HTTPS
    $is_https = str_starts_with( $site_url, 'https://' );

    // Sitemap check
    $sitemap_resp = wp_remote_get( $site_url . '/sitemap.xml', [ 'timeout' => 6, 'sslverify' => false ] );
    $has_sitemap  = ! is_wp_error( $sitemap_resp ) && wp_remote_retrieve_response_code( $sitemap_resp ) === 200;

    // Robots.txt check
    $robots_resp = wp_remote_get( $site_url . '/robots.txt', [ 'timeout' => 6, 'sslverify' => false ] );
    $has_robots  = ! is_wp_error( $robots_resp ) && wp_remote_retrieve_response_code( $robots_resp ) === 200;
    $robots_body = ! is_wp_error( $robots_resp ) ? wp_remote_retrieve_body( $robots_resp ) : '';
    $robots_blocks_all = (bool) preg_match( '/Disallow:\s*\//m', $robots_body );

    // Active SEO plugin
    $seo_plugins = [
        'wordpress-seo/wp-seo.php'          => 'Yoast SEO',
        'seo-by-rank-math/rank-math.php'     => 'Rank Math',
        'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
        'wp-seopress/seopress.php'           => 'SEOPress',
    ];
    $active_seo = '';
    foreach ( $seo_plugins as $path => $name ) {
        if ( in_array( $path, $active_plugins, true ) ) { $active_seo = $name; break; }
    }

    // Caching plugin check
    $cache_plugins = [
        'wp-rocket/wp-rocket.php'       => 'WP Rocket',
        'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
        'litespeed-cache/litespeed-cache.php' => 'LiteSpeed Cache',
        'wp-super-cache/wp-cache.php'   => 'WP Super Cache',
        'autoptimize/autoptimize.php'   => 'Autoptimize',
        'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache',
    ];
    $active_cache = '';
    foreach ( $cache_plugins as $path => $name ) {
        if ( in_array( $path, $active_plugins, true ) ) { $active_cache = $name; break; }
    }

    // SSL / HTTPS force
    $force_ssl = (bool) get_option( 'force_ssl_admin' );

    // Reading settings
    $blog_public = (bool) get_option( 'blog_public' );

    // Recent posts – check for thin content
    $recent_posts = $wpdb->get_results( "
        SELECT ID, post_title, post_content, post_date
        FROM {$wpdb->posts}
        WHERE post_status='publish' AND post_type='post'
        ORDER BY post_date DESC LIMIT 10
    " );
    $thin_posts = 0;
    foreach ( $recent_posts as $p ) {
        if ( str_word_count( strip_tags( $p->post_content ) ) < 300 ) $thin_posts++;
    }

    /* ── Build checks ────────────────────────── */
    $checks = [];

    // ── ON-PAGE ───────────────────────────────
    $title_len = mb_strlen( $title );
    $checks[] = [
        'category' => 'On-Page',
        'label'    => 'Homepage Title Tag',
        'current'  => $title ? '"' . mb_strimwidth( $title, 0, 60, '…' ) . '" (' . $title_len . ' chars)' : 'No title tag found',
        'status'   => $title_len >= 50 && $title_len <= 60 ? 'pass' : ( $title_len === 0 ? 'fail' : 'warn' ),
        'issue'    => $title_len === 0 ? 'No title tag on homepage.' : ( $title_len < 50 ? 'Title too short (' . $title_len . ' chars). Google may rewrite it.' : ( $title_len > 60 ? 'Title too long (' . $title_len . ' chars). Gets truncated in SERPs.' : '' ) ),
        'fix'      => 'Go to your SEO plugin (Yoast / Rank Math) → Home page settings → Set a title of 50–60 characters including your primary keyword near the start.',
    ];

    $desc_len = mb_strlen( $desc );
    $checks[] = [
        'category' => 'On-Page',
        'label'    => 'Homepage Meta Description',
        'current'  => $desc ? '"' . mb_strimwidth( $desc, 0, 80, '…' ) . '" (' . $desc_len . ' chars)' : 'No meta description found',
        'status'   => $desc_len >= 120 && $desc_len <= 160 ? 'pass' : ( $desc_len === 0 ? 'fail' : 'warn' ),
        'issue'    => $desc_len === 0 ? 'No meta description on homepage — reduces CTR from search results.' : ( $desc_len < 120 ? 'Description too short (' . $desc_len . ' chars).' : ( $desc_len > 160 ? 'Description too long (' . $desc_len . ' chars) — may be cut off.' : '' ) ),
        'fix'      => 'SEO Plugin → Homepage → Write a 120–160 character description that clearly explains what the site offers and includes your main keyword.',
    ];

    $checks[] = [
        'category' => 'On-Page',
        'label'    => 'Homepage H1 Tag',
        'current'  => $h1_count . ' H1 tag' . ( $h1_count !== 1 ? 's' : '' ) . ' found on homepage',
        'status'   => $h1_count === 1 ? 'pass' : ( $h1_count === 0 ? 'fail' : 'warn' ),
        'issue'    => $h1_count === 0 ? 'No H1 heading on homepage — critical for SEO.' : ( $h1_count > 1 ? $h1_count . ' H1 tags found. Only one should exist per page.' : '' ),
        'fix'      => $h1_count === 0 ? 'Add exactly one H1 heading to your homepage via the page editor. It should contain your main keyword.' : 'Remove extra H1 tags — keep only one. Use H2/H3 for subheadings.',
    ];

    $checks[] = [
        'category' => 'On-Page',
        'label'    => 'Posts Missing SEO Title',
        'current'  => $posts_no_seo_title . ' of ' . ($total_posts + $total_pages) . ' posts/pages have no custom SEO title',
        'status'   => $posts_no_seo_title === 0 ? 'pass' : ( $posts_no_seo_title <= 5 ? 'warn' : 'fail' ),
        'issue'    => $posts_no_seo_title > 0 ? $posts_no_seo_title . ' posts/pages are using the default WordPress title with no custom SEO title set.' : '',
        'fix'      => 'Go to each post/page → SEO plugin sidebar → set a custom title of 50–60 characters for each. Prioritise high-traffic pages first.',
    ];

    $checks[] = [
        'category' => 'On-Page',
        'label'    => 'Posts Missing Meta Description',
        'current'  => $posts_no_meta_desc . ' of ' . ($total_posts + $total_pages) . ' posts/pages have no meta description',
        'status'   => $posts_no_meta_desc === 0 ? 'pass' : ( $posts_no_meta_desc <= 5 ? 'warn' : 'fail' ),
        'issue'    => $posts_no_meta_desc > 0 ? $posts_no_meta_desc . ' posts/pages have no meta description — Google will auto-generate from content, often poorly.' : '',
        'fix'      => 'Edit each post/page → SEO plugin → add a 120–160 char description with a clear value proposition and keyword.',
    ];

    $checks[] = [
        'category' => 'On-Page',
        'label'    => 'Canonical Tag (Homepage)',
        'current'  => $has_canonical ? 'Canonical tag present' : 'No canonical tag found',
        'status'   => $has_canonical ? 'pass' : 'warn',
        'issue'    => ! $has_canonical ? 'No canonical tag on homepage — risk of duplicate content if page is accessible via multiple URLs.' : '',
        'fix'      => 'Install Yoast SEO or Rank Math — they auto-add canonical tags. Or add <link rel="canonical" href="' . esc_url($home_url) . '"> to your theme header.',
    ];

    // ── CONTENT ───────────────────────────────
    $checks[] = [
        'category' => 'Content',
        'label'    => 'Thin Content Posts',
        'current'  => $thin_posts . ' of last 10 posts have under 300 words',
        'status'   => $thin_posts === 0 ? 'pass' : ( $thin_posts <= 2 ? 'warn' : 'fail' ),
        'issue'    => $thin_posts > 0 ? $thin_posts . ' recent posts have under 300 words — Google considers these thin and ranks them poorly.' : '',
        'fix'      => 'Expand short posts to at least 600 words. Add more detail, examples, FAQs, or data. Thin content is one of the top reasons for low rankings.',
    ];

    $checks[] = [
        'category' => 'Content',
        'label'    => 'Posts Without Category',
        'current'  => $posts_no_category . ' posts have no custom category assigned',
        'status'   => $posts_no_category === 0 ? 'pass' : 'warn',
        'issue'    => $posts_no_category > 0 ? $posts_no_category . ' posts are in "Uncategorised" — hurts topical authority and site structure.' : '',
        'fix'      => 'Go to Posts → edit each post → assign a relevant category. Create topic clusters (e.g. SEO, Marketing, Case Studies).',
    ];

    $checks[] = [
        'category' => 'Content',
        'label'    => 'Draft Posts',
        'current'  => $draft_count . ' draft posts/pages sitting unpublished',
        'status'   => $draft_count <= 5 ? 'pass' : 'warn',
        'issue'    => $draft_count > 10 ? 'You have ' . $draft_count . ' drafts — review and publish or delete old drafts.' : '',
        'fix'      => 'Review old drafts. Either finish and publish them or delete if no longer relevant. Lots of unpublished content can indicate a content planning problem.',
    ];

    // ── IMAGES ───────────────────────────────
    $checks[] = [
        'category' => 'Images',
        'label'    => 'Images Missing Alt Text',
        'current'  => $images_no_alt . ' of ' . $total_images . ' images have no alt text',
        'status'   => $images_no_alt === 0 ? 'pass' : ( $images_no_alt <= 10 ? 'warn' : 'fail' ),
        'issue'    => $images_no_alt > 0 ? $images_no_alt . ' images in your media library have no alt text — hurts image SEO and accessibility.' : '',
        'fix'      => 'Go to Media Library → click each image → add descriptive alt text. Use a plugin like "Auto Alt Text" for bulk updates. Describe the image content naturally, include keywords where relevant.',
    ];

    // ── TECHNICAL ─────────────────────────────
    $checks[] = [
        'category' => 'Technical',
        'label'    => 'HTTPS / SSL',
        'current'  => $is_https ? 'Site is running on HTTPS ✓' : 'Site is running on HTTP — NOT secure',
        'status'   => $is_https ? 'pass' : 'fail',
        'issue'    => ! $is_https ? 'Site not using HTTPS. This is a confirmed Google ranking factor and shows "Not Secure" to visitors.' : '',
        'fix'      => 'Contact your hosting provider to install a free SSL certificate (Let\'s Encrypt). Then update WordPress URLs in Settings → General to https://. Install "Really Simple SSL" plugin.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'Search Engine Visibility',
        'current'  => $blog_public ? 'Site is visible to search engines ✓' : '⚠ Site is set to BLOCK search engines',
        'status'   => $blog_public ? 'pass' : 'fail',
        'issue'    => ! $blog_public ? 'Settings → Reading → "Discourage search engines from indexing this site" is checked. Googlebot cannot crawl your site!' : '',
        'fix'      => 'Go to Settings → Reading → UNCHECK "Discourage search engines from indexing this site" → Save. This is critical.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'XML Sitemap',
        'current'  => $has_sitemap ? 'Sitemap found at ' . $site_url . '/sitemap.xml ✓' : 'No sitemap found at /sitemap.xml',
        'status'   => $has_sitemap ? 'pass' : 'fail',
        'issue'    => ! $has_sitemap ? 'No XML sitemap found. Google needs this to efficiently crawl and index all your pages.' : '',
        'fix'      => 'Install Yoast SEO or Rank Math — they auto-generate sitemaps. Then submit it to Google Search Console under Sitemaps.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'Robots.txt',
        'current'  => $has_robots ? ( $robots_blocks_all ? 'Robots.txt found but may be blocking all crawlers!' : 'Robots.txt found ✓' ) : 'No robots.txt found',
        'status'   => ! $has_robots ? 'warn' : ( $robots_blocks_all ? 'fail' : 'pass' ),
        'issue'    => ! $has_robots ? 'No robots.txt file.' : ( $robots_blocks_all ? 'Robots.txt has "Disallow: /" which may be blocking search crawlers!' : '' ),
        'fix'      => ! $has_robots ? 'WordPress generates robots.txt automatically. Make sure it\'s accessible at ' . $site_url . '/robots.txt' : 'Review your robots.txt and ensure you are not blocking important pages from being crawled.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'Permalink Structure',
        'current'  => $permalink ? '"' . $permalink . '"' : 'Default ugly permalinks (?p=123)',
        'status'   => $good_permalink ? 'pass' : 'fail',
        'issue'    => ! $good_permalink ? 'Using default /?p=123 URLs — not SEO friendly. Search engines prefer descriptive URLs.' : '',
        'fix'      => 'Go to Settings → Permalinks → Select "Post name" (/sample-post/) → Save. This is one of the most important basic SEO settings.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'WordPress Version',
        'current'  => 'WordPress ' . $wp_version . ( $wp_version_ok ? ' (up to date) ✓' : ' (outdated)' ),
        'status'   => $wp_version_ok ? 'pass' : 'warn',
        'issue'    => ! $wp_version_ok ? 'Running WordPress ' . $wp_version . '. Outdated versions have security vulnerabilities and missing performance improvements.' : '',
        'fix'      => 'Go to Dashboard → Updates → Update WordPress to the latest version. Always backup first.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'SEO Plugin',
        'current'  => $active_seo ? $active_seo . ' is active ✓' : 'No dedicated SEO plugin installed',
        'status'   => $active_seo ? 'pass' : 'fail',
        'issue'    => ! $active_seo ? 'No SEO plugin detected. You are missing critical SEO functionality — meta tags, sitemaps, schema, and more.' : '',
        'fix'      => 'Install Rank Math (recommended — free) or Yoast SEO from Plugins → Add New. Complete the setup wizard after installation.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'Caching Plugin',
        'current'  => $active_cache ? $active_cache . ' is active ✓' : 'No caching plugin detected',
        'status'   => $active_cache ? 'pass' : 'warn',
        'issue'    => ! $active_cache ? 'No caching plugin found. Without caching, every page loads fresh from the server — significantly slowing response times.' : '',
        'fix'      => 'Install WP Rocket (paid, best) or LiteSpeed Cache / WP Super Cache (free) from Plugins → Add New. Enable caching after installation.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'Structured Data / Schema',
        'current'  => $has_schema ? 'JSON-LD schema markup detected on homepage ✓' : 'No schema markup found on homepage',
        'status'   => $has_schema ? 'pass' : 'warn',
        'issue'    => ! $has_schema ? 'No structured data found. Schema markup helps Google show rich results (stars, FAQs, etc.) in search results.' : '',
        'fix'      => 'Enable schema in your SEO plugin. Rank Math does this automatically. Add Organization, WebSite, and BreadcrumbList schema at minimum.',
    ];

    $checks[] = [
        'category' => 'Technical',
        'label'    => 'HTML Page Size',
        'current'  => $html_size_kb . ' KB homepage HTML',
        'status'   => $html_size_kb <= 100 ? 'pass' : ( $html_size_kb <= 200 ? 'warn' : 'fail' ),
        'issue'    => $html_size_kb > 100 ? 'Homepage HTML is ' . $html_size_kb . ' KB — large HTML slows page parsing.' : '',
        'fix'      => 'Enable HTML minification (WP Rocket or Autoptimize). Remove unused plugins and custom code from header/footer. Aim for under 100KB HTML.',
    ];

    // ── SPEED ─────────────────────────────────
    $checks[] = [
        'category' => 'Speed',
        'label'    => 'Server Response Time (TTFB)',
        'current'  => $ttfb_ms . 'ms to first byte from homepage',
        'status'   => $ttfb_ms <= 600 ? 'pass' : ( $ttfb_ms <= 1200 ? 'warn' : 'fail' ),
        'issue'    => $ttfb_ms > 600 ? 'TTFB is ' . $ttfb_ms . 'ms — ' . ( $ttfb_ms > 1200 ? 'very slow.' : 'above the recommended 600ms.' ) . ' Slow TTFB directly hurts Core Web Vitals.' : '',
        'fix'      => 'Enable server-side caching (WP Rocket / LiteSpeed Cache). Upgrade to faster hosting (SSD + PHP 8.1+). Use a CDN like Cloudflare. Optimise your database.',
    ];

    // ── MOBILE ────────────────────────────────
    $checks[] = [
        'category' => 'Mobile',
        'label'    => 'Mobile Viewport Meta Tag',
        'current'  => $has_viewport ? 'Viewport meta tag present ✓' : 'No viewport meta tag on homepage',
        'status'   => $has_viewport ? 'pass' : 'fail',
        'issue'    => ! $has_viewport ? 'No viewport meta tag. Page will not render correctly on mobile devices.' : '',
        'fix'      => 'Add <meta name="viewport" content="width=device-width, initial-scale=1"> to your theme\'s header.php, or use a mobile-responsive theme.',
    ];

    // ── SOCIAL ────────────────────────────────
    $checks[] = [
        'category' => 'Social',
        'label'    => 'Open Graph Tags',
        'current'  => $has_og ? 'Open Graph (og:) meta tags detected ✓' : 'No Open Graph tags found',
        'status'   => $has_og ? 'pass' : 'warn',
        'issue'    => ! $has_og ? 'No OG tags. When pages are shared on Facebook/LinkedIn/WhatsApp, no thumbnail or description will show.' : '',
        'fix'      => 'Enable Open Graph in your SEO plugin (Yoast: Social → Facebook → Enable. Rank Math: Titles & Meta → Homepage → Social tab). Upload a 1200x630px social image.',
    ];

    // ── SCORE ─────────────────────────────────
    $pass = $warn = $fail = 0;
    foreach ( $checks as $c ) {
        if ( $c['status'] === 'pass' ) $pass++;
        elseif ( $c['status'] === 'warn' ) $warn++;
        else $fail++;
    }
    $total = count( $checks );
    $score = $total > 0 ? max( 0, min( 100, round( ( ( $pass + $warn * 0.5 ) / $total ) * 100 ) ) ) : 0;

    // ── RECOMMENDATIONS ───────────────────────
    $recs = [];
    foreach ( $checks as $c ) {
        if ( $c['status'] !== 'pass' ) {
            $recs[] = [
                'priority' => $c['status'] === 'fail' ? 'high' : 'medium',
                'label'    => $c['label'],
                'fix'      => $c['fix'],
                'category' => $c['category'],
            ];
        }
    }
    usort( $recs, fn($a,$b) => $a['priority'] === 'high' ? -1 : 1 );

    return [
        'site_url'   => $site_url,
        'timestamp'  => current_time( 'mysql' ),
        'wp_version' => $wp_version,
        'theme'      => $theme_name . ' v' . $theme_version,
        'seo_plugin' => $active_seo ?: 'None detected',
        'cache_plugin'=> $active_cache ?: 'None detected',
        'stats' => [
            'total_posts'  => $total_posts,
            'total_pages'  => $total_pages,
            'total_images' => $total_images,
            'plugin_count' => $plugin_count,
            'ttfb_ms'      => $ttfb_ms,
            'html_size_kb' => $html_size_kb,
            'word_count'   => $word_count,
        ],
        'checks'  => $checks,
        'scoring' => [ 'score' => $score, 'pass' => $pass, 'warn' => $warn, 'fail' => $fail ],
        'recs'    => array_slice( $recs, 0, 10 ),
    ];
}

function vx_score_class( $s ) { return $s >= 80 ? 'good' : ( $s >= 50 ? 'warn' : 'poor' ); }
function vx_score_label( $s ) { return $s >= 80 ? 'Good' : ( $s >= 50 ? 'Needs Work' : 'Poor' ); }
