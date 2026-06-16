<?php
/**
 * Render method for button group block.
 *
 * @package dstheme
 */

$alignment       = ( isset( $attributes['alignment'] ) ) ? (string) $attributes['alignment'] : 'horizontal';
$justify_content = ( isset( $attributes['justifyContent'] ) ) ? (string) $attributes['justifyContent'] : 'flex-start';
$justify_content_mobile = ( isset( $attributes['justifyContentMobile'] ) ) ? (string) $attributes['justifyContentMobile'] : $justify_content;
$gap_between     = ( isset( $attributes['gapBetween'] ) ) ? (string) $attributes['gapBetween'] : 10;

// Add attributes to styles for the block.
if ( ! isset( $attributes['style'] ) ) {
	$attributes['style'] = '';
}
$attributes['style'] .= ' --gap: ' . $gap_between . 'px; --v-align: ' . $justify_content . '; --v-align-mobile: ' . $justify_content_mobile . ';';
// Add additional classes for the block.
if ( ! isset( $attributes['class'] ) ) {
	$attributes['class'] = '';
}
$attributes['class'] .= ' dst-button-group c-block__btn is-' . $alignment;

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );

?>
<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
