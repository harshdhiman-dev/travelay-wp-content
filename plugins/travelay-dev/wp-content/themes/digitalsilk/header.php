<?php
/**
 * The header template
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package DS_Theme
 *
 * CSS classes args.
 *
 * @var array $args
 */

$args = wp_parse_args(
    $args,
    array(
        'class_body' => '',
        'class_main' => '',
    )
);

global $dsmp_settings;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php wp_title('|', true, 'right'); ?></title>

    <link rel="profile" href="http://gmpg.org/xfn/11">
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <?php // phpcs:ignore ?>
    <link rel="stylesheet" href="<?php echo esc_url(ds_get_uploads_dir_baseurl()); ?>/dsmp-assets/theme.css" media="all" type="text/css">

    <?php wp_head(); ?>
</head>
<body <?php body_class($args['class_body']); ?>>

<?php do_action('wp_body_open'); ?>

<a class="skip-link screen-reader-text" href="#wp--skip-link--target">
    <?php esc_html_e('Skip to content', 'dstheme'); ?>
</a>

<?php
$global_background_image = get_field('global_background_image', 'options');
$show_background = is_archive()
    || is_home()
    || is_single()
    || is_page_template('templates/template-simple-text.php');

$wrapper_styles = '';
?>

<?php
if ($show_background && $global_background_image && ! empty($global_background_image['url'])) :
    $clean_url = str_replace('\\', '/', $global_background_image['url']);
    $wrapper_styles .= '--global-bg-url: url(' . esc_url($clean_url) . ')';
    $global_background_image__url = $clean_url;
endif;
?>

<div
    class="wrapper <?php echo $show_background && $global_background_image ? '-has-global-img' : ''; ?>"
    data-sticky="<?php echo esc_attr($dsmp_settings->header_sticky_type); ?>"
    style="<?php echo ! empty($wrapper_styles) ? esc_attr($wrapper_styles . ';') : ''; ?>"
>
<?php do_action('ds_wrapper_open'); ?>

<header class="site-header" role="banner">
    <?php get_template_part('templates/header/header'); ?>
    <?php echo do_shortcode('[amadex_regional_settings mode="modal"]'); ?>
</header>

<?php
do_action('ds_before_content', $args['class_main']);
