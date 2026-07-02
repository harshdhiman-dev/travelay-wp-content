<?php
/**
 * Icon Component markup
 *
 * @package DST\Blocks\ds_theme
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );
$icon             = isset( $attributes['icon'] ) ? (string) $attributes['icon'] : '';
$icon_size        = isset( $attributes['size'] ) ? (int) $attributes['size'] : 25;

if ( ! $icon ) {
	return;
}
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore ?>>
	<span class="dst-icon">
		<?php
		get_svg(
			[
				'icon' => $icon,
				'size' => $icon_size,
				'echo' => true,
			]
		);
		?>
	</span>
</div>
