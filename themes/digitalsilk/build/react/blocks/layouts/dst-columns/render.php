<?php
/**
 * DST Columns markup
 *
 * @package DS_Theme\Blocks\shared\ds-columns
 *
 * @var array $attributes Block attributes.
 * @var string $content Block content.
 * @var WP_Block $block Block instance.
 */

$gap                     = isset( $attributes['gap'] ) ? $attributes['gap'] : 0;
$count                   = isset( $attributes['count'] ) ? (int) $attributes['count'] : 0;
$vertical_align          = isset( $attributes['verticalAlign'] ) ? (string) $attributes['verticalAlign'] : '';
$reverse_mobile          = isset( $attributes['reverseMobile'] ) ? (bool) $attributes['reverseMobile'] : false;
$text_align              = isset( $attributes['textAlign'] ) ? (string) $attributes['textAlign'] : '';
$text_align_mobile       = isset( $attributes['textAlignMobile'] ) ? (string) $attributes['textAlignMobile'] : '';
$is_multirow             = isset( $attributes['isMultirow'] ) ? (bool) $attributes['isMultirow'] : false;
$desktop_columns_per_row = isset( $attributes['desktopColumnsPerRow'] ) && is_numeric( $attributes['desktopColumnsPerRow'] ) ? (int) $attributes['desktopColumnsPerRow'] : null;
$module_variant          = isset( $attributes['moduleVariant'] ) ? (string) $attributes['moduleVariant'] : '';
$background_color        = isset( $attributes['backgroundColor'] ) ? $attributes['backgroundColor'] : '';
$background_image        = isset( $attributes['backgroundImage'] ) ? $attributes['backgroundImage'] : [];
$decorations             = isset( $attributes['decorations'] ) ? $attributes['decorations'] : [];

// Create row classes.
$row_classes   = [ 'ds-row' ];
$row_classes[] = ( $vertical_align ) ? sprintf( 'items-%s', $vertical_align ) : '';
$row_classes[] = ( $reverse_mobile ) ? 'reverse-mobile' : '';
$row_classes[] = $text_align ? sprintf( 'text-%s', $text_align ) : '';
$row_classes[] = $text_align_mobile ? sprintf( 'text-%s-mobile', $text_align_mobile ) : '';

// Add multirow class if enabled.
if ( $is_multirow ) {
	$row_classes[] = 'ds-columns-is-multirow';
}

// Initialize style attribute.
$style = '';

// Add gaps to style.
if ( $gap ) {
	$style .= sprintf( '--ds-row-gap: %s; ', $gap );
}

// Add columns count to style.
if ( $count ) {
	$style .= sprintf( '--ds-columns-count: %s; ', $count );

	// For multirow layouts, use the desktopColumnsPerRow or fallback to count.
	$columns_per_row = $is_multirow && null !== $desktop_columns_per_row
		? $desktop_columns_per_row
		: $count;

	// Generate grid-template-columns.
	if ( $is_multirow ) {
		// For multirow, use variable columns to support custom widths.
		$grid_columns = [];

		// Always use var() syntax for all columns to ensure custom widths apply.
		for ( $i = 1; $i <= $columns_per_row; $i++ ) {
			$grid_columns[] = sprintf( 'var(--col%d_desktop-fr, 1fr)', $i );
		}
		$style .= sprintf( '--grid-template-columns: %s; ', implode( ' ', $grid_columns ) );
	} else {
		// For single row, always use the variable columns approach to ensure custom widths apply.
		$grid_columns = [];

		// Always use var() syntax for all columns to ensure custom widths apply.
		for ( $i = 1; $i <= $count; $i++ ) {
			$grid_columns[] = sprintf( 'var(--col%d_desktop-fr, 1fr)', $i );
		}
		$style .= sprintf( '--grid-template-columns: %s; ', implode( ' ', $grid_columns ) );
	}

	// Get inner blocks to extract column widths.
	$inner_blocks = $block->inner_blocks;
	if ( ! empty( $inner_blocks ) ) {
		foreach ( $inner_blocks as $index => $inner_block ) {
			if ( $index < $count && isset( $inner_block->attributes['columnSpan'] ) ) {
				$col_width = (float) $inner_block->attributes['columnSpan'];
				// Only add custom variables if the width is not the default (1).
				if ( abs( $col_width - 1 ) > 0.01 ) { // Use small epsilon for float comparison.
					$style .= sprintf( '--col%d_desktop-fr: %sfr; ', $index + 1, $col_width );
				}
			}
		}
	}
}

// Add tablet columns CSS variables if tabletCount is set.
$tablet_count = isset( $attributes['tabletCount'] ) && is_numeric( $attributes['tabletCount'] ) ? (int) $attributes['tabletCount'] : null;
if ( $tablet_count && $tablet_count > 0 ) {
	// Always use variable columns for tablet to ensure custom widths apply.
	$tablet_grid_columns = array();
	for ( $i = 0; $i < $tablet_count; $i++ ) {
		$tablet_grid_columns[] = sprintf( 'var(--col%d_tablet-fr, 1fr)', $i + 1 );
	}
	$style .= sprintf( '--grid-template-columns_tablet: %s; ', implode( ' ', $tablet_grid_columns ) );

	// Set --colN_tablet-fr for each column - ONLY for non-default values.
	if ( ! empty( $inner_blocks ) ) {
		foreach ( $inner_blocks as $index => $inner_block ) {
			if ( $index < $tablet_count && isset( $inner_block->attributes['columnSpanTablet'] ) ) {
				$col_tablet = (float) $inner_block->attributes['columnSpanTablet'];
				// Only add custom variables if the width is not the default (1).
				if ( abs( $col_tablet - 1 ) > 0.01 ) { // Use small epsilon for float comparison.
					$style .= sprintf( '--col%d_tablet-fr: %sfr; ', $index + 1, $col_tablet );
				}
			}
		}
	}
}

// Mobile columns CSS variables if mobileCount is set.
$mobile_count = isset( $attributes['mobileCount'] ) && is_numeric( $attributes['mobileCount'] ) ? (int) $attributes['mobileCount'] : null;
if ( $mobile_count && $mobile_count > 0 ) {
	// Always use variable columns for mobile to ensure custom widths apply.
	$mobile_grid_columns = array();
	for ( $i = 0; $i < $mobile_count; $i++ ) {
		$mobile_grid_columns[] = sprintf( 'var(--col%d_mobile-fr, 1fr)', $i + 1 );
	}
	$style .= sprintf( '--grid-template-columns_mobile: %s; ', implode( ' ', $mobile_grid_columns ) );

	$style .= sprintf( '--ds-columns-count_mobile: %d; ', $mobile_count );

	// Set --colN_mobile-fr for each column - ONLY for non-default values.
	if ( ! empty( $inner_blocks ) ) {
		foreach ( $inner_blocks as $index => $inner_block ) {
			if ( $index < $mobile_count && isset( $inner_block->attributes['columnSpanMobile'] ) ) {
				$col_mobile = (float) $inner_block->attributes['columnSpanMobile'];
				// Only add custom variables if the width is not the default (1).
				if ( abs( $col_mobile - 1 ) > 0.01 ) { // Use small epsilon for float comparison.
					$style .= sprintf( '--col%d_mobile-fr: %sfr; ', $index + 1, $col_mobile );
				}
			}
		}
	}
}

// Add background color to style if set.
if ( ! empty( $background_color ) ) {
    $style .= 'background:' . esc_attr( $background_color ) . ';';
}

// Set the style attribute if we have styles.
if ( $style ) {
	$attributes['style'] = $style;
}

// Add class to attributes.
$exsisting_classes   = isset( $attributes['class'] ) ? (string) $attributes['class'] : '';
$attributes['class'] = trim( $exsisting_classes . ' ds-columns' . ( $module_variant ? ' ' . $module_variant : '' ) );

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php ds_render_decorations( $decorations ); ?>
	<?php ds_render_background_media( $background_image ); ?>
	<div class="<?php echo esc_attr( implode( ' ', array_filter( $row_classes ) ) ); ?>">
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
