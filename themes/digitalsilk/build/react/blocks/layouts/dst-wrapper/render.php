<?php
/**
 * Wrapper markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$html_tag          = ( isset( $attributes['htmlTag'] ) && ! empty( $attributes['htmlTag'] ) ) ? $attributes['htmlTag'] : 'div';
$decorations       = ( isset( $attributes['decorations'] ) ) ? (array) $attributes['decorations'] : [];
$background_color  = ( isset( $attributes['backgroundColor'] ) ) ? $attributes['backgroundColor'] : '';
$background_image  = ( isset( $attributes['backgroundImage'] ) ) ? $attributes['backgroundImage'] : [];
$exsisting_styles  = ( isset( $attributes['style'] ) ) ? $attributes['style'] : '';
$wrapper_variant   = ( isset( $attributes['moduleVariant'] ) && ! empty( $attributes['moduleVariant'] ) ) ? ' ' . $attributes['moduleVariant'] : '';
$height_variant   = ( isset( $attributes['heightVariant'] ) && ! empty( $attributes['heightVariant'] ) ) ? ' ' . $attributes['heightVariant'] : '';

// Create an array of additional classes
$additional_classes = array(
    'dst-wrapper',
);

// Add conditional classes to the array
if ( $wrapper_variant ) {
    $additional_classes[] = trim( $wrapper_variant );
}
if ( $height_variant ) {
    $additional_classes[] = trim( $height_variant );
}

// Add custom classes from dsClassList to additional classes
if ( isset( $attributes['class'] ) && ! empty( $attributes['class'] ) ) {
    $custom_classes = explode( ' ', $attributes['class'] );
    foreach ( $custom_classes as $class ) {
        $additional_classes[] = sanitize_html_class( $class );
    }
}

$additional_attributes = array();

if ( ! empty( $background_color ) ) {
	$additional_attributes['style'] = 'background:' . esc_attr( $background_color ) . ';' . $exsisting_styles;
}

$extra_attributes = ds_theme_generate_extra_atts(
	array_merge( $attributes, $additional_attributes ),
	$block
);

// Ensure we have the additional classes in the extra attributes
if ( ! empty( $additional_classes ) ) {
    if ( isset( $extra_attributes['class'] ) ) {
        $existing_classes = explode( ' ', $extra_attributes['class'] );
        $merged_classes = array_merge( $existing_classes, $additional_classes );
        $extra_attributes['class'] = implode( ' ', array_unique( array_filter( $merged_classes ) ) );
    } else {
        $extra_attributes['class'] = implode( ' ', array_unique( array_filter( $additional_classes ) ) );
    }
}
?>

<<?php echo esc_attr( $html_tag ); ?>
	<?php ds_theme_generate_anchor( $attributes ); ?>
	<?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<?php ds_render_decorations( $decorations ); ?>
	<?php ds_render_background_media( $background_image ); ?>
	<div class="dst-wrapper__inner">
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
</<?php echo esc_attr( $html_tag ); ?>>
