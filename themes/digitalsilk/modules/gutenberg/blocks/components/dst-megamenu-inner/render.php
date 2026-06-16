<?php
/**
 * Megamenu Wrapper markup
 *
 * @package DST\Blocks\ds_theme
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$block_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'megamenu__inner-wrapper',
	)
);
?>

<div <?php echo $block_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress handles escaping for block wrapper attributes ?>>
	<?php
	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
</div>
