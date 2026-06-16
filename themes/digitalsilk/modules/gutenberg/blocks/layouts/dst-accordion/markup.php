<?php
/**
 * DST Accordion markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$exsisting_classes = isset( $attributes['class'] ) ? $attributes['class'] : '';

$extra_attributes = ds_theme_generate_extra_atts(
	array_merge(
		$attributes,
		array(
			'class' => 'dst-accordion ' . $exsisting_classes,
		)
	),
	$block
);
?>

<div <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore ?>>
	<div class="dst-accordion__inner">
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
