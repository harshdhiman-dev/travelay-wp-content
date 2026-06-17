<?php
/**
 * Destination Grid markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );

$heading = wp_parse_args(
	$attributes['heading'] ?? [],
	[
		'title'          => '',
		'showDecoration' => true,
	]
);

$items = ( ! empty( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : [];

$background = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'type'            => 'none',
		'color'           => '#1f7a4d',
		'image'           => [],
		'imageMobile'     => [],
		'mediaFit'        => 'cover',
		'mediaPosition'   => 'center center',
		'disableLazyLoad' => false,
		'overlayOpacity'  => 0,
		'overlayColor'    => '#000000',
	]
);

$has_background_color = ( 'color' === $background['type'] && ! empty( $background['color'] ) );
$has_background_image  = ( 'image' === $background['type'] && ! empty( $background['image']['url'] ) );

$has_mobile_background = ( $has_background_image && ! empty( $background['imageMobile']['url'] ) );

if ( $has_background_color ) {
	$extra_attributes['style'] = ( isset( $extra_attributes['style'] ) ? $extra_attributes['style'] . '; ' : '' ) . 'background-color: ' . esc_attr( $background['color'] );
} elseif ( $has_background_image ) {
	$bg_style   = [];
	$bg_style[] = 'background-image: url(' . esc_url( $background['image']['url'] ) . ')';
	$bg_style[] = 'background-size: ' . ( 'contain' === $background['mediaFit'] ? 'contain' : 'cover' );
	$bg_style[] = 'background-position: ' . esc_attr( $background['mediaPosition'] );
	$bg_style[] = 'background-repeat: no-repeat';

	if ( $has_mobile_background ) {
		$bg_style[] = '--dst-bg-mobile: url(' . esc_url( $background['imageMobile']['url'] ) . ')';
	}

	$extra_attributes['style'] = ( isset( $extra_attributes['style'] ) ? $extra_attributes['style'] . '; ' : '' ) . implode( '; ', $bg_style );
}
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $has_background_image && (float) $background['overlayOpacity'] > 0 ) : ?>
		<span
			class="c-destination-grid__bg-overlay"
			aria-hidden="true"
			style="background-color: <?php echo esc_attr( $background['overlayColor'] ); ?>; opacity: <?php echo esc_attr( (float) $background['overlayOpacity'] / 100 ); ?>;"
		></span>
	<?php endif; ?>

	<?php if ( ! empty( $heading['title'] ) ) : ?>
		<div class="c-destination-grid__heading">
			<?php if ( ! empty( $heading['showDecoration'] ) ) : ?>
				<span class="c-destination-grid__decoration" aria-hidden="true">
					<img src="https://www.flytravelay.com/wp-content/uploads/2026/06/Group-219.png" alt="" loading="lazy" />
				</span>
			<?php endif; ?>

			<h2 class="c-destination-grid__title"><?php echo wp_kses_post( $heading['title'] ); ?></h2>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $items ) ) : ?>
		<div class="c-destination-grid__items">
			<?php foreach ( $items as $index => $item ) : ?>
				<div class="c-destination-grid__item -item-<?php echo esc_attr( $index + 1 ); ?>">
					<?php
					$image_url = $item['media']['imagePrimary']['url'] ?? '';
					$image_alt = $item['media']['imagePrimary']['alt'] ?? '';
					$loading   = ! empty( $background['disableLazyLoad'] ) ? 'eager' : 'lazy';
					?>
					<?php if ( ! empty( $image_url ) ) : ?>
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="<?php echo esc_attr( $loading ); ?>" />
					<?php endif; ?>

					<?php if ( ! empty( $item['label'] ) ) : ?>
						<span class="c-destination-grid__label"><?php echo wp_kses_post( $item['label'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>