<?php
/**
 * Promo Banner markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$title            = $attributes['title']    ?? '';
$subtitle         = $attributes['subtitle'] ?? '';
$title_color      = $attributes['titleColor']      ?? '#1a5c2a';
$subtitle_color   = $attributes['subtitleColor']   ?? '#1a5c2a';
$title_font_size  = $attributes['titleFontSize']   ?? '';
$title_align      = $attributes['titleAlign']      ?? 'center';
$subtitle_font_size = $attributes['subtitleFontSize'] ?? '';
$subtitle_align   = $attributes['subtitleAlign']   ?? 'center';
$min_height       = $attributes['minHeight']       ?? '220px';
$content_width    = $attributes['contentWidth']    ?? '900px';
$subtitle_border  = $attributes['subtitleBorder']  ?? true;

$title_font = wp_parse_args(
	$attributes['titleFont'] ?? [],
	[ 'url' => '', 'family' => '' ]
);

$background = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'image'          => [],
		'bgColor'        => '#d4edda',
		'overlayColor'   => '#000000',
		'overlayOpacity' => 0,
	]
);

$button = wp_parse_args(
	$attributes['button'] ?? [],
	[
		'text'         => '',
		'url'          => '',
		'bgColor'      => '#1a5c2a',
		'textColor'    => '#ffffff',
		'borderRadius' => '40px',
		'target'       => '_self',
	]
);

$bg_image_url    = $background['image']['url'] ?? '';
$bg_image_alt    = $background['image']['alt'] ?? '';
$bg_color        = $background['bgColor'] ?? '#d4edda';
$overlay_color   = $background['overlayColor'] ?? '#000000';
$overlay_opacity = (float) ( $background['overlayOpacity'] ?? 0 ) / 100;

$font_family = $title_font['family'] ?? '';
$font_url    = $title_font['url']    ?? '';

$anchor      = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$anchor_attr = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';

$unique_id = 'dst-promo-' . substr( md5( $title . $font_url ), 0, 8 );

// Build title inline style
$title_style = 'color:' . esc_attr( $title_color ) . ';';
$title_style .= 'text-align:' . esc_attr( $title_align ) . ';';
if ( $title_font_size ) {
	$title_style .= 'font-size:' . esc_attr( $title_font_size ) . ';';
}
if ( $font_family ) {
	$title_style .= "font-family:'" . esc_attr( $font_family ) . "',serif;";
}

// Build subtitle inline style
$subtitle_style = 'color:' . esc_attr( $subtitle_color ) . ';';
$subtitle_style .= 'text-align:' . esc_attr( $subtitle_align ) . ';';
if ( $subtitle_font_size ) {
	$subtitle_style .= 'font-size:' . esc_attr( $subtitle_font_size ) . ';';
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bevan&display=swap" rel="stylesheet">

<?php if ( ! empty( $font_url ) && ! empty( $font_family ) ) : ?>
<style>
	@font-face {
		font-family: '<?php echo esc_attr( $font_family ); ?>';
		src: url('<?php echo esc_url( $font_url ); ?>') format('woff2'),
			url('<?php echo esc_url( $font_url ); ?>') format('woff'),
			url('<?php echo esc_url( $font_url ); ?>') format('truetype');
		font-weight: normal;
		font-style: normal;
		font-display: swap;
	}
</style>
<?php endif; ?>

<div class="c-promo-banner__outer">
<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	id="<?php echo esc_attr( $unique_id ); ?>"
	class="c-promo-banner wp-block-ds-blocks-promo-banner"
	style="min-height:<?php echo esc_attr( $min_height ); ?>; background-color:<?php echo esc_attr( $bg_color ); ?>;"
>
	<?php if ( ! empty( $bg_image_url ) ) : ?>
		<img
			class="c-promo-banner__bg"
			src="<?php echo esc_url( $bg_image_url ); ?>"
			alt="<?php echo esc_attr( $bg_image_alt ); ?>"
			aria-hidden="true"
			loading="lazy"
		/>
	<?php endif; ?>

	<?php if ( $overlay_opacity > 0 ) : ?>
		<span
			class="c-promo-banner__overlay"
			aria-hidden="true"
			style="background-color:<?php echo esc_attr( $overlay_color ); ?>; opacity:<?php echo esc_attr( $overlay_opacity ); ?>;"
		></span>
	<?php endif; ?>

	<div class="c-promo-banner__inner" style="max-width:<?php echo esc_attr( $content_width ); ?>;">
		<div class="c-promo-banner__content">

			<?php if ( ! empty( $title ) ) : ?>
				<h2 class="c-promo-banner__title" style="<?php echo esc_attr( $title_style ); ?>">
					<?php echo wp_kses_post( $title ); ?>
				</h2>
			<?php endif; ?>

			<?php if ( ! empty( $subtitle ) ) : ?>
				<p class="c-promo-banner__subtitle<?php echo $subtitle_border ? ' c-promo-banner__subtitle--border' : ''; ?>"
					style="<?php echo esc_attr( $subtitle_style ); ?>">
					<?php echo wp_kses_post( $subtitle ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $button['text'] ) && ! empty( $button['url'] ) ) : ?>
				<div class="c-promo-banner__cta-wrap">
					<a
						href="<?php echo esc_url( $button['url'] ); ?>"
						class="c-promo-banner__cta"
						target="<?php echo esc_attr( $button['target'] ?? '_self' ); ?>"
						rel="<?php echo ( ( $button['target'] ?? '' ) === '_blank' ) ? 'noopener noreferrer' : ''; ?>"
						style="background-color:<?php echo esc_attr( $button['bgColor'] ); ?>; color:<?php echo esc_attr( $button['textColor'] ); ?>; border-radius:<?php echo esc_attr( $button['borderRadius'] ); ?>;"
					>
						<?php echo esc_html( $button['text'] ); ?>
					</a>
				</div>
			<?php endif; ?>

		</div>
	</div>
</div>
</div>
