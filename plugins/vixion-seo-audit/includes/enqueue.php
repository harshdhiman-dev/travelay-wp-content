<?php
defined( 'ABSPATH' ) || exit;

function vx_our_page_slugs() {
    return [
        'vixion-seo-audit','vixion-seo-new-audit','vixion-seo-report','vixion-seo-history',
        'vixion-seo-analytics','vixion-seo-search-console','vixion-seo-keywords',
        'vixion-seo-competitors','vixion-seo-ai-briefs','vixion-seo-reports',
        'vixion-seo-subscription','vixion-seo-support','vixion-seo-settings',
    ];
}

function vx_is_our_page() {
    if ( ! is_admin() ) return false;
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    return in_array($page, vx_our_page_slugs(), true);
}

add_action( 'admin_enqueue_scripts', function () {
    if ( ! vx_is_our_page() ) return;
    wp_enqueue_style( 'vixion-seo', VX_SEO_URL . 'assets/css/main.css', [], VX_SEO_VERSION );
    wp_enqueue_script( 'vixion-seo', VX_SEO_URL . 'assets/js/main.js', ['jquery'], VX_SEO_VERSION, true );
    wp_localize_script( 'vixion-seo', 'vxSeo', [
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('vx_nonce'),
        'adminUrl' => admin_url('admin.php'),
        'siteUrl'  => get_site_url(),
    ]);
});

add_action( 'admin_head', function () {
    if ( ! vx_is_our_page() ) return;
    echo '<style>#wpcontent{padding-left:0!important}#wpbody-content{padding-bottom:0!important}.notice,.update-nag,.updated,.is-dismissible{display:none!important}</style>';
});
