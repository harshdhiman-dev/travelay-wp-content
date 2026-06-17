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
					<svg viewBox="0 0 200 40" xmlns="http://www.w3.org/2000/svg" focusable="false">
						<path d="M2 20 H80" stroke="currentColor" stroke-width="1" fill="none" />
						<path d="M120 20 H198" stroke="currentColor" stroke-width="1" fill="none" />
						<g fill="currentColor">
							<path d="M100 6c-6 6-16 8-22 8 8 4 16 4 22 0 6 4 14 4 22 0-6 0-16-2-22-8z" />
							<circle cx="100" cy="22" r="2.5" />
							<path d="M100 24v8M94 30l6-4 6 4" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />
						</g>
					</svg>
				</span>
			<?php endif; ?>

			<h2 class="c-destination-grid__title"><?php echo wp_kses_post( $heading['title'] ); ?></h2>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $items ) ) : ?>
		<div class="c-destination-grid__items">
			<?php foreach ( $items as $index => $item ) : ?>
				<div class="c-destination-grid__item -item-<?php echo esc_attr( $index + 1 ); ?>">
					<?php if ( ! empty( $item['media'] ) ) : ?>
						<?php get_template_part( 'templates/components-shared/media/dst', 'media', $item['media'] ); ?>
					<?php endif; ?>

					<?php if ( ! empty( $item['label'] ) ) : ?>
						<span class="c-destination-grid__label"><?php echo wp_kses_post( $item['label'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
