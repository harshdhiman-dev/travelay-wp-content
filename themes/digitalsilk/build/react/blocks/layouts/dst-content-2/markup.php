<?php
/**
 * Content 2 markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$media                      = isset( $attributes['media'] ) ? (array) $attributes['media'] : [];
$columns_order              = isset( $attributes['columnsOrder'] ) ? (string) $attributes['columnsOrder'] : 'order-default';
$columns_order_mobile       = isset( $attributes['columnsOrderMobile'] ) ? (string) $attributes['columnsOrderMobile'] : '';
$columns_gap                = isset( $attributes['columnsGap'] ) ? (string) $attributes['columnsGap'] : '20px';
$content_rato               = isset( $attributes['contentRatio'] ) ? (int) $attributes['contentRatio'] : 50;
$is_vertical                = isset( $attributes['isVertical'] ) ? (bool) $attributes['isVertical'] : false;
$text_padding_left_desktop  = isset( $attributes['textPaddingLeftDesktop'] ) ? (string) $attributes['textPaddingLeftDesktop'] : '';
$text_padding_left_mobile   = isset( $attributes['textPaddingLeftMobile'] ) ? (string) $attributes['textPaddingLeftMobile'] : '';
$text_padding_right_desktop = isset( $attributes['textPaddingRightDesktop'] ) ? (string) $attributes['textPaddingRightDesktop'] : '';
$text_padding_right_mobile  = isset( $attributes['textPaddingRightMobile'] ) ? (string) $attributes['textPaddingRightMobile'] : '';
$text_y_align               = isset( $attributes['textYAlign'] ) ? (string) $attributes['textYAlign'] : 'align-center';
$media_x_align              = isset( $attributes['mediaXAlign'] ) ? (string) $attributes['mediaXAlign'] : 'media-to-center';
$media_y_align              = isset( $attributes['mediaYAlign'] ) ? (string) $attributes['mediaYAlign'] : 'media-justify-center';
$exsisting_classes          = isset( $attributes['class'] ) ? (string) $attributes['class'] : '';

// Generate block wrapper attributes without container classes
$block_wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'm-block m-dcbl ' . $exsisting_classes,
	]
);

// Define c-block classes and styles.
$c_block_classes = [ 'c-block', $columns_order ];
if ( ! empty( $columns_order_mobile ) ) {
	$c_block_classes[] = $columns_order_mobile;
}
if ( $is_vertical ) {
	$c_block_classes[] = 'is-vertical';
}
$c_block_styles = [];
if ( $content_rato ) {
	$c_block_styles[] = '--columns-ratio: ' . $content_rato . '%';
}
if ( $columns_gap ) {
	$c_block_styles[] = '--columns-gap: ' . $columns_gap;
}

// Define c-block__text classes and styles.
$c_block_text_classes = [ 'c-block__text', $text_y_align ];
$c_block_text_styles  = [];
if ( $text_padding_left_desktop ) {
	$c_block_text_styles[] = '--space-left: ' . $text_padding_left_desktop;
}
if ( $text_padding_left_mobile ) {
	$c_block_text_styles[] = '--space-left-m: ' . $text_padding_left_mobile;
}
if ( $text_padding_right_desktop ) {
	$c_block_text_styles[] = '--space-right: ' . $text_padding_right_desktop;
}
if ( $text_padding_right_mobile ) {
	$c_block_text_styles[] = '--space-right-m: ' . $text_padding_right_mobile;
}

// Define c-block__media classes.
$c_block_media_classes = [ 'c-block__media', $media_x_align, $media_y_align ];

// Generate container classes for inner container
$inner_container_attributes = ds_theme_generate_extra_atts(
	array_merge(
		$attributes,
		[
			'class' => '',
		]
	),
	$block
);
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo $block_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="m-block__container <?php echo esc_attr( $inner_container_attributes['class'] ?? '' ); ?>" <?php if ( isset( $inner_container_attributes['style'] ) ) : ?>style="<?php echo esc_attr( $inner_container_attributes['style'] ); ?>"<?php endif; ?>>
		<div class="l-dcbl">
			<div
				class="<?php echo esc_attr( implode( ' ', array_filter( $c_block_classes ) ) ); ?>"
				style="<?php echo esc_attr( implode( '; ', array_filter( $c_block_styles ) ) ); ?>"
			>
				<div
					class="<?php echo esc_attr( implode( ' ', array_filter( $c_block_text_classes ) ) ); ?>"
					style="<?php echo esc_attr( implode( '; ', array_filter( $c_block_text_styles ) ) ); ?>"
				>
					<div class="c-block__inner">
						<?php
						/*
						* $content outputs html from innerBlocks, it is already sanitized.
						*
						* Don't re-sanitize with `wp_kses_post` as it can break the core filters
						* No additional sanitization is required
						*/
						echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
				<?php if ( $media ) : ?>
					<div class="<?php echo esc_attr( implode( ' ', array_filter( $c_block_media_classes ) ) ); ?>">
						<?php get_template_part( 'templates/components-shared/media/dst', 'media', $media ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
