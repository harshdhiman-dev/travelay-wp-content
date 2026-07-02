<?php
/**
 * CTA Banner markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$heading          = $attributes['heading']         ?? '';
$heading_color    = $attributes['headingColor']    ?? '#ffffff';
$section_bg_color = $attributes['sectionBgColor']  ?? '#6b9c3f';
$title            = $attributes['title']           ?? '';
$title_color      = $attributes['titleColor']      ?? '#ffffff';
$subtitle         = $attributes['subtitle']        ?? '';
$subtitle_color   = $attributes['subtitleColor']   ?? '#ffffff';
$min_height       = $attributes['minHeight']       ?? '320px';
$content_width    = $attributes['contentWidth']    ?? '560px';

$background = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'image'          => [],
		'overlayColor'   => '#000000',
		'overlayOpacity' => 35,
	]
);

$button = wp_parse_args(
	$attributes['button'] ?? [],
	[
		'text'         => 'Call Now',
		'phone'        => '',
		'bgColor'      => '#1f7a4d',
		'textColor'    => '#ffffff',
		'borderRadius' => '40px',
	]
);

$bg_image_url    = $background['image']['url'] ?? '';
$bg_image_alt    = $background['image']['alt'] ?? '';
$overlay_color   = $background['overlayColor'] ?? '#000000';
$overlay_opacity = (float) ( $background['overlayOpacity'] ?? 35 ) / 100;

$anchor      = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$anchor_attr = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';

$unique_id = 'dst-cta-banner-' . substr( md5( $heading . $title ), 0, 8 );

$phone_digits = preg_replace( '/[^0-9+]/', '', $button['phone'] ?? '' );
?>

<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	id="<?php echo esc_attr( $unique_id ); ?>"
	class="c-cta-banner wp-block-ds-blocks-cta-banner"
	style="background-color:<?php echo esc_attr( $section_bg_color ); ?>;"
>
	<div class="c-cta-banner__inner">

		<?php if ( ! empty( $heading ) ) : ?>
			<h2 class="c-cta-banner__heading" style="color:<?php echo esc_attr( $heading_color ); ?>;">
				<?php echo wp_kses_post( $heading ); ?>
			</h2>
		<?php endif; ?>

		<div class="c-cta-banner__photo" style="min-height:<?php echo esc_attr( $min_height ); ?>;">

			<?php if ( ! empty( $bg_image_url ) ) : ?>
				<img
					class="c-cta-banner__bg"
					src="<?php echo esc_url( $bg_image_url ); ?>"
					alt="<?php echo esc_attr( $bg_image_alt ); ?>"
					loading="lazy"
				/>
			<?php endif; ?>

			<?php if ( $overlay_opacity > 0 ) : ?>
				<span
					class="c-cta-banner__overlay"
					aria-hidden="true"
					style="background-color:<?php echo esc_attr( $overlay_color ); ?>; opacity:<?php echo esc_attr( $overlay_opacity ); ?>;"
				></span>
			<?php endif; ?>

			<div class="c-cta-banner__content" style="max-width:<?php echo esc_attr( $content_width ); ?>;">

				<?php if ( ! empty( $title ) ) : ?>
					<h3 class="c-cta-banner__title" style="color:<?php echo esc_attr( $title_color ); ?>;">
						<?php echo wp_kses_post( $title ); ?>
					</h3>
				<?php endif; ?>

				<?php if ( ! empty( $subtitle ) ) : ?>
					<p class="c-cta-banner__subtitle" style="color:<?php echo esc_attr( $subtitle_color ); ?>;">
						<?php echo wp_kses_post( $subtitle ); ?>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $button['text'] ) ) : ?>
					<a
						href="tel:<?php echo esc_attr( $phone_digits ); ?>"
						class="c-cta-banner__cta"
						style="background-color:<?php echo esc_attr( $button['bgColor'] ); ?>; color:<?php echo esc_attr( $button['textColor'] ); ?>; border-radius:<?php echo esc_attr( $button['borderRadius'] ); ?>;"
					>
						<span class="c-cta-banner__cta-icon" aria-hidden="true">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.2 1.1L6.6 10.8z" fill="currentColor"/>
							</svg>
						</span>
						<?php echo esc_html( $button['text'] ); ?>
					</a>
				<?php endif; ?>

			</div>
		</div>
	</div>
</div>
