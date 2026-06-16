<?php
/**
 * Block Name: c-list
 *
 * @package Digitalsilk
 */

$gap_between           = ( isset( $attributes['gapBetween'] ) ) ? (string) $attributes['gapBetween'] : '10px';
$gap_between_content   = ( isset( $attributes['gapBetweenContent'] ) ) ? (string) $attributes['gapBetweenContent'] : '10px';
$column_count          = ( isset( $attributes['colCount'] ) ) ? (int) $attributes['colCount'] : 1;
$alignment             = ( isset( $attributes['alignment'] ) ) ? (string) $attributes['alignment'] : 'row';
$icon_size             = ( isset( $attributes['iconsSize'] ) ) ? (string) $attributes['iconsSize'] : '';
$subtitle_size         = ( isset( $attributes['subtitleSize'] ) ) ? (string) $attributes['subtitleSize'] : '';
$hero_text_size        = ( isset( $attributes['heroTextSize'] ) ) ? (string) $attributes['heroTextSize'] : '';
$title_size            = ( isset( $attributes['titleSize'] ) ) ? (string) $attributes['titleSize'] : '';
$title_line_height     = ( isset( $attributes['titleLineHeight'] ) ) ? (string) $attributes['titleLineHeight'] : '';
$color_hero            = ( isset( $attributes['colorHero'] ) ) ? (string) $attributes['colorHero'] : '';
$color_subtitle        = ( isset( $attributes['colorSubtitle'] ) ) ? (string) $attributes['colorSubtitle'] : '';
$color_icon            = ( isset( $attributes['colorIcon'] ) ) ? (string) $attributes['colorIcon'] : '';
$color_title           = ( isset( $attributes['colorTitle'] ) ) ? (string) $attributes['colorTitle'] : '';
$title_transform       = ( isset( $attributes['titleTransform'] ) ) ? (string) $attributes['titleTransform'] : '';
$title_weight          = ( isset( $attributes['titleWeight'] ) ) ? (string) $attributes['titleWeight'] : '';
$item_padding          = ( isset( $attributes['itemPadding'] ) ) ? (array) $attributes['itemPadding'] : [];
$has_vertical_border   = ( isset( $attributes['hasVerticalBorder'] ) ) ? (bool) $attributes['hasVerticalBorder'] : false;
$has_horizontal_border = ( isset( $attributes['hasHorizontalBorder'] ) ) ? (bool) $attributes['hasHorizontalBorder'] : false;
$module_variant        = ( isset( $attributes['moduleVariant'] ) ) ? (string) $attributes['moduleVariant'] : '';
$icon_alignment        = ( isset( $attributes['iconAlignment'] ) ) ? (string) $attributes['iconAlignment'] : 'flex-start';
$styles_array          = [];


if ( $column_count ) {
	$styles_array[] = '--dst-list__col: ' . esc_attr( $column_count );
}
if ( $gap_between_content ) {
	$styles_array[] = '--dst-list__content-gap: ' . esc_attr( $gap_between_content );
}
if ( $gap_between ) {
	$styles_array[] = '--dst-list__gap: ' . esc_attr( $gap_between );
}
if ( $alignment ) {
	$styles_array[] = '--dst-list__direction: ' . esc_attr( $alignment );
}
if ( $icon_size ) {
	$styles_array[] = '--dst-list__media-size: ' . esc_attr( $icon_size );
}
if ( $icon_alignment ) {
	$styles_array[] = '--dst-list__media-align: ' . esc_attr( $icon_alignment );
}
if ( $subtitle_size ) {
	$styles_array[] = '--dst-list__subtitle-size: ' . esc_attr( $subtitle_size );
}
if ( $title_size ) {
	$styles_array[] = '--dst-list__title-size: ' . esc_attr( $title_size );
}
if ( $title_line_height ) {
	$styles_array[] = '--dst-list__title-lh: ' . esc_attr( $title_line_height );
}
if ( $hero_text_size ) {
	$styles_array[] = '--dst-list__hero-size: ' . esc_attr( $hero_text_size );
}
if ( $color_hero ) {
	$styles_array[] = '--dst-list__hero-color: ' . esc_attr( $color_hero );
}
if ( $color_subtitle ) {
	$styles_array[] = '--dst-list__subtitle-color: ' . esc_attr( $color_subtitle );
}
if ( $color_icon ) {
	$styles_array[] = '--dst-list__media-color: ' . esc_attr( $color_icon );
}
if ( $color_title ) {
	$styles_array[] = '--dst-list__title-color: ' . esc_attr( $color_title );
}
if ( $title_transform ) {
	$styles_array[] = '--dst-list__title-transform: ' . esc_attr( $title_transform );
}
if ( $title_weight ) {
	$styles_array[] = '--dst-list__title-weight: ' . esc_attr( $title_weight );
}
if ( $item_padding && is_array( $item_padding ) && ! empty( array_filter( $item_padding ) ) ) {
	$padding_top    = ( isset( $item_padding['top'] ) ) ? (string) $item_padding['top'] : '0rem';
	$padding_right  = ( isset( $item_padding['right'] ) ) ? (string) $item_padding['right'] : '0rem';
	$padding_bottom = ( isset( $item_padding['bottom'] ) ) ? (string) $item_padding['bottom'] : '0rem';
	$padding_left   = ( isset( $item_padding['left'] ) ) ? (string) $item_padding['left'] : '0rem';
	$padding_value  = $padding_top . ' ' . $padding_right . ' ' . $padding_bottom . ' ' . $padding_left;
	$styles_array[] = '--dst-list__item-padding: ' . trim( $padding_value );
}

// Create an array of additional classes
$additional_classes = array(
    'dst-list',
    'is-' . $alignment,
);

// Add conditional classes to the array
if ( $has_vertical_border ) {
    $additional_classes[] = 'has-vertical-border';
}
if ( $has_horizontal_border ) {
    $additional_classes[] = 'has-horizontal-border';
}
if ( $module_variant ) {
    $additional_classes[] = $module_variant;
}

// Add custom classes from dsClassList to additional classes
if ( isset( $attributes['class'] ) && ! empty( $attributes['class'] ) ) {
    $custom_classes = explode( ' ', $attributes['class'] );
    foreach ( $custom_classes as $class ) {
        $additional_classes[] = sanitize_html_class( $class );
    }
}

// Create style attribute from styles array
$style_attribute = implode( '; ', $styles_array );

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

// Add style attribute to extra attributes
if ( ! empty( $style_attribute ) ) {
    if ( isset( $extra_attributes['style'] ) ) {
        $extra_attributes['style'] = $extra_attributes['style'] . '; ' . $style_attribute;
    } else {
        $extra_attributes['style'] = $style_attribute;
    }
}

?>
<ul <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress handles escaping for block wrapper attributes ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</ul>
