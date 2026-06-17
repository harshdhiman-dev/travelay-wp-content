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
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

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
					?>
					<?php if ( ! empty( $image_url ) ) : ?>
						<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" />
					<?php endif; ?>

					<?php if ( ! empty( $item['label'] ) ) : ?>
						<span class="c-destination-grid__label"><?php echo wp_kses_post( $item['label'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
