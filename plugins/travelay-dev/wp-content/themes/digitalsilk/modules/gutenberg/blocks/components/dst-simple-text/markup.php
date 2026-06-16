<?php
/**
 * Simple Text Component markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

// Add class to attributes.
$attributes = wp_parse_args(
	$attributes,
	[
		'class' => 'dst-simple-text',
	]
);

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore ?>>
	<div class="dst-simple-text__inner is-wysiwyg">
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
