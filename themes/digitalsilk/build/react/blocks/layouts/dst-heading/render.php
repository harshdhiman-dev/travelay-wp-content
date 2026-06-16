<?php
/**
 * Render method for c-heading block.
 *
 * @package dstheme
 */

// Add our classes to block attributes.
$align_desktop   = ( isset( $attributes['alignment'] ) ) ? (string) $attributes['alignment'] : 'left';
$align_mobile    = ( isset( $attributes['alignmentMobile'] ) ) ? (string) $attributes['alignmentMobile'] : 'left';
$heading_theme   = ( isset( $attributes['headingTheme'] ) && 'inverted' === $attributes['headingTheme'] ) ? ' is-style-colors-inverted' : '';
$heading_variant = ( isset( $attributes['moduleVariant'] ) && ! empty( $attributes['moduleVariant'] ) ) ? ' ' . $attributes['moduleVariant'] : '';
$disable_margins = ( isset( $attributes['disableModuleMargins'] ) && true === $attributes['disableModuleMargins'] ) ? ' no-inner-margin' : '';

// Create an array of additional classes
$additional_classes = array(
    "text-{$align_desktop}",
    "text-{$align_mobile}-mobile",
);

// Add conditional classes to the array
if ( $heading_theme ) {
    $additional_classes[] = trim( $heading_theme );
}
if ( $heading_variant ) {
    $additional_classes[] = trim( $heading_variant );
}
if ( $disable_margins ) {
    $additional_classes[] = trim( $disable_margins );
}

// Add custom classes from dsClassList to additional classes
if ( isset( $attributes['class'] ) && ! empty( $attributes['class'] ) ) {
    $custom_classes = explode( ' ', $attributes['class'] );
    foreach ( $custom_classes as $class ) {
        $additional_classes[] = sanitize_html_class( $class );
    }
}

// Generate extra attributes
$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );

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

// Because we are using inner blocks for the description, our description attribute is actually $content.
$show_description                   = ( isset( $attributes['showDescription'] ) ) ? (bool) $attributes['showDescription'] : false;
$attributes['description']          = ( $show_description ) ? $content : false;
$attributes['sanitize_description'] = ( $show_description && $content ) ? false : true;
?>
<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress handles escaping for block wrapper attributes ?>>
	<?php
		get_template_part( 'templates/components/headings/heading', '', $attributes );
	?>
</div>
