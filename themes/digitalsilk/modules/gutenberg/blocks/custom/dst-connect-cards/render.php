<?php
/**
 * Connect Cards markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$heading        = $attributes['heading']       ?? '';
$heading_color  = $attributes['headingColor']  ?? '#ffffff';
$heading_align  = $attributes['headingAlign']  ?? 'left';
$columns        = (int) ( $attributes['columns'] ?? 4 );
$cta_icon       = $attributes['ctaIcon']       ?? 'email';
$cta_bg_color   = $attributes['ctaBgColor']    ?? '#ffffff';
$cta_text_color = $attributes['ctaTextColor']  ?? '#1a1a1a';
$cta_border_color = $attributes['ctaBorderColor'] ?? '#dddddd';
$cards          = ( ! empty( $attributes['cards'] ) && is_array( $attributes['cards'] ) ) ? $attributes['cards'] : [];

$background = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'image'          => [],
		'bgColor'        => '#1f7a4d',
		'overlayColor'   => '#000000',
		'overlayOpacity' => 0,
	]
);

$bg_image_url    = $background['image']['url'] ?? '';
$bg_image_alt    = $background['image']['alt'] ?? '';
$bg_color        = $background['bgColor']        ?? '#1f7a4d';
$overlay_color   = $background['overlayColor']   ?? '#000000';
$overlay_opacity = (float) ( $background['overlayOpacity'] ?? 0 ) / 100;

$anchor      = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$anchor_attr = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';

// Icon SVGs
$icons = [
	'email' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M2 7l10 7 10-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
	'phone' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.2 1.1L6.6 10.8z" fill="currentColor"/></svg>',
	'link'  => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
];
?>

<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	class="c-connect-cards wp-block-ds-blocks-connect-cards"
	style="background-color:<?php echo esc_attr( $bg_color ); ?>;"
>
	<?php if ( ! empty( $bg_image_url ) ) : ?>
		<img class="c-connect-cards__bg" src="<?php echo esc_url( $bg_image_url ); ?>" alt="<?php echo esc_attr( $bg_image_alt ); ?>" aria-hidden="true" loading="lazy" />
	<?php endif; ?>

	<?php if ( $overlay_opacity > 0 ) : ?>
		<span class="c-connect-cards__overlay" aria-hidden="true" style="background-color:<?php echo esc_attr( $overlay_color ); ?>; opacity:<?php echo esc_attr( $overlay_opacity ); ?>;"></span>
	<?php endif; ?>

	<div class="c-connect-cards__inner">

		<?php if ( ! empty( $heading ) ) : ?>
			<h2 class="c-connect-cards__heading" style="color:<?php echo esc_attr( $heading_color ); ?>; text-align:<?php echo esc_attr( $heading_align ); ?>;">
				<?php echo wp_kses_post( $heading ); ?>
			</h2>
		<?php endif; ?>

		<div class="c-connect-cards__grid" style="grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);">
			<?php foreach ( $cards as $card ) :
				$image       = $card['image']       ?? [];
				$image_url   = $image['url']         ?? '';
				$image_alt   = $image['alt']         ?? '';
				$title       = $card['title']        ?? '';
				$description = $card['description']  ?? '';
				$cta_type    = $card['ctaType']      ?? 'email';
				$cta_label   = $card['ctaLabel']     ?? '';
				$cta_value   = $card['ctaValue']     ?? '';

				$href = '#';
				if ( $cta_type === 'email' && $cta_value ) {
					$href = 'mailto:' . antispambot( $cta_value );
				} elseif ( $cta_type === 'phone' && $cta_value ) {
					$href = 'tel:' . preg_replace( '/[^0-9+]/', '', $cta_value );
				} elseif ( $cta_type === 'link' && $cta_value ) {
					$href = esc_url( $cta_value );
				}

				$icon_svg = $icons[ $cta_type ] ?? $icons['email'];
			?>
				<div class="c-connect-cards__card">
					<?php if ( ! empty( $image_url ) ) : ?>
						<div class="c-connect-cards__image-wrap">
							<img class="c-connect-cards__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" />
						</div>
					<?php else : ?>
						<div class="c-connect-cards__image-placeholder"></div>
					<?php endif; ?>

					<?php if ( ! empty( $title ) ) : ?>
						<h3 class="c-connect-cards__card-title"><?php echo wp_kses_post( $title ); ?></h3>
					<?php endif; ?>

					<?php if ( ! empty( $description ) ) : ?>
						<p class="c-connect-cards__card-desc"><?php echo wp_kses_post( $description ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $cta_label ) ) : ?>
						<a
							href="<?php echo $href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
							class="c-connect-cards__cta"
							style="background-color:<?php echo esc_attr( $cta_bg_color ); ?>; color:<?php echo esc_attr( $cta_text_color ); ?>; border-color:<?php echo esc_attr( $cta_border_color ); ?>;"
						>
							<span class="c-connect-cards__cta-icon"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<?php echo esc_html( $cta_label ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

	</div>
</div>
