<?php
/**
 * DS Column markup
 *
 * @package DS_Theme\Blocks\ds_theme
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$col_span_desktop = isset( $attributes['columnSpan'] ) ? (int) $attributes['columnSpan'] : 6;
$col_span_tablet  = isset( $attributes['columnSpanTablet'] ) ? (int) $attributes['columnSpanTablet'] : 6;
$col_min_height 	  = isset( $attributes['columnMinHeight'] ) ? (int) $attributes['columnMinHeight'] : 0;
$col_min_height_mobile 	  = isset( $attributes['columnMinHeightMobile'] ) ? (int) $attributes['columnMinHeightMobile'] : 0;
$align_vertical   = isset( $attributes['alignVertical'] ) ? (string) $attributes['alignVertical'] : '';
$align_horizontal = isset( $attributes['alignHorizontal'] ) ? (string) $attributes['alignHorizontal'] : '';
$css_class        = isset( $attributes['class'] ) ? (string) $attributes['class'] : '';

// Create wrapper css classes.
$css_classes = [];
if ( $css_class ) {
	$css_classes[] = $css_class;
}
if ( $align_horizontal ) {
	if ( 'left' === $align_horizontal ) {
		$css_classes[] = 'items-start';
	} elseif ( 'center' === $align_horizontal ) {
		$css_classes[] = 'items-center';
	} elseif ( 'right' === $align_horizontal ) {
		$css_classes[] = 'items-end';
	}
}
if ( $align_vertical ) {
	if ( 'top' === $align_vertical ) {
		$css_classes[] = 'flex-left';
	} elseif ( 'center' === $align_vertical ) {
		$css_classes[] = 'flex-center';
	} elseif ( 'bottom' === $align_vertical ) {
		$css_classes[] = 'flex-right';
	}
}

// Add classes to attributes.
$attributes['class'] = implode( ' ', $css_classes );

// Add tablet column width as a style variable if set and not default.
$style = '';
if ( $col_span_tablet > 0 && 6 !== $col_span_tablet ) {
	// Try to get column index from block context if available.
	$col_index = isset( $block->context['ds-columns/columnIndex'] ) ? (int) $block->context['ds-columns/columnIndex'] : null;
	if ( $col_index ) {
		$style .= sprintf( '--col%d_tablet-fr: %dfr; ', $col_index, $col_span_tablet );
	}
}
if ( $col_min_height > 0 ) {

	$style .= sprintf( '--column-min-height: %dpx;', $col_min_height );

}
if ( $col_min_height_mobile > 0 ) {

	$style .= sprintf( '--column-min-height-mobile: %dpx;', $col_min_height_mobile );

}
if ( $style ) {
	$attributes['style'] = isset( $attributes['style'] ) ? $attributes['style'] . $style : $style;
}

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php
	/*
	* $content outputs html from innerBlocks and it is aleady sanitized.
	*
	* Don't re-sanitize with `wp_kses_post` as it can break the core filters
	* No additional sanitization is required
	*/
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
