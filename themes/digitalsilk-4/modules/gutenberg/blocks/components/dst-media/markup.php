<?php
/**
 * DST Media markup
 *
 * @package DST\Blocks\ds_theme
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );

if ( ! isset( $attributes['media'] ) || empty( $attributes['media'] ) ) {
	return;
}
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress handles escaping for block wrapper attributes ?>>
	<?php
		get_template_part( 'templates/components-shared/media/dst', 'media', $attributes['media'] );
	?>
</div>
