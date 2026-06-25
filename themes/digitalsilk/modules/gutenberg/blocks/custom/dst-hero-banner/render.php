<?php
/**
 * Hero Banner markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$tag            = $attributes['tag'] ?? '';
$title          = $attributes['title'] ?? '';
$subtitle       = $attributes['subtitle'] ?? '';
$shortcode      = $attributes['shortcode'] ?? '[amadex_search_modern]';
$title_color    = $attributes['titleColor'] ?? '#ffffff';
$tag_color      = $attributes['tagColor'] ?? '#ffffff';
$subtitle_color = $attributes['subtitleColor'] ?? '#ffffff';
$min_height     = $attributes['minHeight'] ?? '520px';

$background      = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'image'          => [],
		'overlayColor'   => '#000000',
		'overlayOpacity' => 40,
	]
);

$title_font = wp_parse_args(
	$attributes['titleFont'] ?? [],
	[
		'url'    => '',
		'family' => 'Shivaraja',
	]
);

$bg_image_url    = $background['image']['url'] ?? '';
$bg_image_alt    = $background['image']['alt'] ?? '';
$overlay_color   = $background['overlayColor'] ?? '#000000';
$overlay_opacity = (float) ( $background['overlayOpacity'] ?? 40 ) / 100;

$anchor      = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$anchor_attr = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';

$font_family = $title_font['family'] ?? 'Shivaraja';
$font_url    = $title_font['url'] ?? '';

$unique_id = 'dst-hero-' . substr( md5( $title . $font_url ), 0, 8 );
?>

<?php if ( ! empty( $font_url ) ) : ?>
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

	#<?php echo esc_attr( $unique_id ); ?> .c-hero-banner__title {
		font-family: '<?php echo esc_attr( $font_family ); ?>', serif;
	}
</style>
<?php endif; ?>

<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	id="<?php echo esc_attr( $unique_id ); ?>"
	class="c-hero-banner wp-block-ds-blocks-hero-banner"
	style="min-height: <?php echo esc_attr( $min_height ); ?>;"
>

	<?php if ( ! empty( $bg_image_url ) ) : ?>
		<img
			class="c-hero-banner__bg"
			src="<?php echo esc_url( $bg_image_url ); ?>"
			alt="<?php echo esc_attr( $bg_image_alt ); ?>"
			aria-hidden="true"
			loading="eager"
		/>
	<?php endif; ?>

	<span
		class="c-hero-banner__overlay"
		aria-hidden="true"
		style="background-color: <?php echo esc_attr( $overlay_color ); ?>; opacity: <?php echo esc_attr( $overlay_opacity ); ?>;"
	></span>

	<div class="c-hero-banner__inner">
		<div class="c-hero-banner__content">
			<?php if ( ! empty( $tag ) ) : ?>
				<p class="c-hero-banner__tag" style="color: <?php echo esc_attr( $tag_color ); ?>;">
					<?php echo wp_kses_post( $tag ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $title ) ) : ?>
				<h1 class="c-hero-banner__title" style="color: <?php echo esc_attr( $title_color ); ?>;">
					<?php echo wp_kses_post( $title ); ?>
				</h1>
			<?php endif; ?>

			<?php if ( ! empty( $subtitle ) ) : ?>
				<p class="c-hero-banner__subtitle" style="color: <?php echo esc_attr( $subtitle_color ); ?>;">
					<?php echo wp_kses_post( $subtitle ); ?>
				</p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $shortcode ) ) : ?>
			<div class="c-hero-banner__search">
				<?php echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</div>
</div>
