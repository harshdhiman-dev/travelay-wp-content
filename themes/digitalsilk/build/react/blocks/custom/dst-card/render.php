<?php
/**
 * Test Block markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );
?>

<div <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
</div>
