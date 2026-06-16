<?php
/**
 * AI Content Briefs
 * Uses OpenAI GPT-4o API to generate content briefs from audit gap data.
 */
defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_vx_save_ai', 'vx_ai_save_settings' );
function vx_ai_save_settings() {
    check_admin_referer( 'vx_save_ai' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

    update_option( 'vx_openai_key', sanitize_text_field( trim( $_POST['vx_openai_key'] ?? '' ) ) );
    update_option( 'vx_ai_tone',    sanitize_text_field( $_POST['vx_ai_tone'] ?? 'professional' ) );
    update_option( 'vx_ai_lang',    sanitize_text_field( $_POST['vx_ai_lang'] ?? 'English' ) );

    wp_redirect( admin_url( 'admin.php?page=vixion-seo-ai-briefs&saved=1' ) );
    exit;
}

// ── Generate a brief from last audit report ───────────────────
function vx_ai_generate_brief( $target_keyword = '', $target_url = '' ) {
    $api_key = get_option( 'vx_openai_key', '' );
    if ( ! $api_key ) return new WP_Error( 'no_key', 'No OpenAI API key configured.' );

    $report = vx_db_get_last_report();
    $tone   = get_option( 'vx_ai_tone', 'professional' );
    $lang   = get_option( 'vx_ai_lang', 'English' );

    // Build context from audit gaps
    $gaps = [];
    if ( $report ) {
        foreach ( $report['checks'] ?? [] as $check ) {
            if ( $check['status'] !== 'pass' && ! empty( $check['issue'] ) ) {
                $gaps[] = '- ' . $check['label'] . ': ' . $check['issue'];
            }
        }
        $score    = $report['scoring']['score'] ?? 0;
        $site_url = $report['site_url'] ?? home_url();
    } else {
        $score    = 0;
        $site_url = home_url();
    }

    $gap_text    = $gaps ? implode( "\n", array_slice( $gaps, 0, 8 ) ) : 'No specific audit gaps available.';
    $kw_context  = $target_keyword ? "Target keyword: {$target_keyword}" : "Use the most relevant keyword for this site.";
    $url_context = $target_url ? "Page URL being optimised: {$target_url}" : "General site optimisation.";

    $system_prompt = "You are an expert SEO content strategist. Generate a detailed, actionable content brief. Write in {$lang}. Tone: {$tone}. Return ONLY valid JSON, no markdown.";

    $user_prompt = <<<PROMPT
Site: {$site_url}
Current SEO score: {$score}/100
{$kw_context}
{$url_context}

SEO audit gaps to address:
{$gap_text}

Generate a complete content brief as JSON with exactly these keys:
{
  "title": "Recommended page title (50-60 chars)",
  "meta_description": "Meta description (120-160 chars)",
  "target_keyword": "Primary keyword",
  "secondary_keywords": ["kw1", "kw2", "kw3"],
  "word_count_target": 1500,
  "content_type": "Blog post / Landing page / etc",
  "h1": "H1 heading",
  "outline": [
    {"tag": "H2", "heading": "...", "notes": "What to cover in this section"},
    {"tag": "H2", "heading": "...", "notes": "..."},
    {"tag": "H2", "heading": "...", "notes": "..."},
    {"tag": "H3", "heading": "...", "notes": "..."}
  ],
  "cta": "Call to action text",
  "seo_tips": ["Specific tip 1", "Specific tip 2", "Specific tip 3"],
  "audit_fixes": ["Fix 1 from the gaps above", "Fix 2"]
}
PROMPT;

    $resp = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
        'body'    => wp_json_encode( [
            'model'       => 'gpt-4o',
            'messages'    => [
                [ 'role' => 'system', 'content' => $system_prompt ],
                [ 'role' => 'user',   'content' => $user_prompt ],
            ],
            'max_tokens'  => 1500,
            'temperature' => 0.7,
            'response_format' => [ 'type' => 'json_object' ],
        ] ),
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'timeout' => 40,
    ] );

    if ( is_wp_error( $resp ) ) return $resp;

    $code = wp_remote_retrieve_response_code( $resp );
    $body = json_decode( wp_remote_retrieve_body( $resp ), true );

    if ( $code !== 200 ) {
        return new WP_Error( 'openai_error', $body['error']['message'] ?? "OpenAI API error (HTTP $code)" );
    }

    $content = $body['choices'][0]['message']['content'] ?? '';
    $brief   = json_decode( $content, true );

    if ( ! $brief ) {
        return new WP_Error( 'parse_error', 'Could not parse response from OpenAI. Try again.' );
    }

    // Save brief with timestamp
    $briefs   = get_option( 'vx_content_briefs', [] );
    $briefs[] = array_merge( $brief, [
        'generated_at' => current_time( 'mysql' ),
        'keyword'      => $target_keyword,
        'url'          => $target_url,
    ] );
    $briefs = array_slice( $briefs, -10 ); // Keep last 10
    update_option( 'vx_content_briefs', $briefs );

    return $brief;
}

// ── AJAX: generate brief ──────────────────────────────────────
add_action( 'wp_ajax_vx_generate_brief', function () {
    check_ajax_referer( 'vx_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $keyword = sanitize_text_field( $_POST['keyword'] ?? '' );
    $url     = esc_url_raw( $_POST['url'] ?? '' );

    $result = vx_ai_generate_brief( $keyword, $url );

    if ( is_wp_error( $result ) ) wp_send_json_error( $result->get_error_message() );
    wp_send_json_success( $result );
} );
