<?php
/**
 * DigitalSilk Tabs markup
 *
 * @package DS_Theme\Blocks\ds_theme
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

/**
 * Extract block class name.
 */
$block_class = ( isset( $block->parsed_block['blockName'] ) ) ? 'wp-block-' . sanitize_title( $block->parsed_block['blockName'] ) : '';

/**
 * Extract block attributes.
 */
$show_labels_title   = isset( $attributes['showLabelsTitle'] ) ? (bool) $attributes['showLabelsTitle'] : false;
$labels_title        = isset( $attributes['labelsTitle'] ) ? (string) $attributes['labelsTitle'] : '';
$tab_items           = isset( $attributes['tabItem'] ) ? (array) $attributes['tabItem'] : [];
$tab_arrows          = isset( $attributes['tabArrows'] ) ? (bool) $attributes['tabArrows'] : false;
$tab_arrows_icon     = isset( $attributes['tabArrowsIcon'] ) ? (string) $attributes['tabArrowsIcon'] : 'tabs-arrow';
$selected_index      = isset( $attributes['blockSelectedIndex'] ) ? (int) $attributes['blockSelectedIndex'] : 0;
$active_tab_selected = isset( $attributes['isActiveSelected'] ) ? $attributes['isActiveSelected'] : false;
$isNumberedLabels 	 = isset( $attributes['isNumberedLabels'] ) ? $attributes['isNumberedLabels'] : false;
$showDescription 	 = isset( $attributes['showDescription'] ) ? $attributes['showDescription'] : false;
$is_tab_accordion    = isset( $attributes['tabAccordion'] ) ? $attributes['tabAccordion'] : false;
$is_tab_dropdown     = isset( $attributes['tabDropdown'] ) ? $attributes['tabDropdown'] : false;
$tab_styles          = isset( $attributes['tabStyles'] ) ? $attributes['tabStyles'] : [];
$tabs_layout         = isset( $attributes['tabsStyle'] ) ? $attributes['tabsStyle'] : 'horizontal';
$anchor              = isset( $attributes['anchor'] ) ? $attributes['anchor'] : wp_unique_id( 'ds-tabs-' );
$additonal_classes   = [];

/**
 * Create additional classes.
 */
if ( $tabs_layout ) {
	$additonal_classes[] = "is-style-{$tabs_layout}";
}
if ( $is_tab_accordion ) {
	$additonal_classes[] = 'is-accordion-mobile';
}
if ( $is_tab_dropdown ) {
	$additonal_classes[] = 'is-dropdown-mobile';
}
if ( $tab_arrows ) {
	$additonal_classes[] = 'has-tab-arrows';
}

$module_variant = isset( $attributes['moduleVariant'] ) && $attributes['moduleVariant'] ? sanitize_html_class( $attributes['moduleVariant'] ) : '';
if ( $module_variant ) {
    $additonal_classes[] = $module_variant;
}

// Add custom classes from dsClassList to additional classes
if ( isset( $attributes['class'] ) && ! empty( $attributes['class'] ) ) {
    $custom_classes = explode( ' ', $attributes['class'] );
    foreach ( $custom_classes as $class ) {
        $additonal_classes[] = sanitize_html_class( $class );
    }
}

// Create extra attributes.
$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );

// Ensure we have the additional classes in the extra attributes
if ( ! empty( $additonal_classes ) ) {
    if ( isset( $extra_attributes['class'] ) ) {
        $existing_classes = explode( ' ', $extra_attributes['class'] );
        $merged_classes = array_merge( $existing_classes, $additonal_classes );
        $extra_attributes['class'] = implode( ' ', array_unique( array_filter( $merged_classes ) ) );
    } else {
        $extra_attributes['class'] = implode( ' ', array_unique( array_filter( $additonal_classes ) ) );
    }
}

/**
 * Our tabs selected index is increased by 1 in the edit.js.
 * Check what tab is selected by checking the index and active_tab_selected var.
 */
$tab_selected_index = $active_tab_selected ? $selected_index + 1 : 1;

$extra_attributes = wp_parse_args(
	$extra_attributes,
	[
		'id'                  => $anchor,
		'style'               => ds_generate_tab_styles( $tab_styles ),
		'data-wp-interactive' => 'ds-tabs',
		'data-wp-context'     => wp_json_encode(
			[
				'activeTab' => $tab_selected_index,
				'tabCount'  => count( $tab_items ),
			]
		),
	]
);

/**
 * Our tab items will always have an id.
 * If they only have an id, and nothing else, then bail early.
 */
if ( ! $tab_items || empty( array_filter( $tab_items ) ) ) {
	return;
}
$tab_list_items_modified = array_map(
	function ( $item ) {
		unset( $item['id'] );
		return $item;
	},
	$tab_items
);
if ( empty( array_filter( $tab_list_items_modified ) ) ) {
	return;
}

?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="<?php echo esc_attr( "{$block_class}__inner" ); ?>">
		<div class="<?php echo esc_attr( "{$block_class}__labels-wrapper" ); ?>">
			<?php if ( $show_labels_title && $labels_title ) : ?>
				<h2 class="<?php echo esc_attr( "{$block_class}__labels-title -h2" ); ?>">
					<?php echo esc_html( $labels_title ); ?>
				</h2>
			<?php endif; ?>
			<div class="<?php echo esc_attr( "{$block_class}__labels" ); ?>" role="tablist">
				<?php if ( $tab_arrows && $tab_arrows_icon ) : ?>
				<div class="<?php echo esc_attr( "{$block_class}__arrows" ); ?>">
					<span
							role="button"
							tabindex=0
							class="<?php echo esc_attr( "{$block_class}__arrow --previous" ); ?>"
							data-wp-on--click="actions.previousTab"
					>
						<?php
						get_svg(
                            [
								'icon' => $tab_arrows_icon,
								'echo' => true,
							]
						);
						?>
					</span>
				</div>
				<div class="<?php echo esc_attr( "{$block_class}__scroller" ); ?>">
					<div class="<?php echo esc_attr( "{$block_class}__track" ); ?>">
						<?php endif; ?>
						<?php
						foreach ( $tab_items as $tab_item_index => $tab_item_array ) :
							$icon_layout  = isset( $tab_item_array['iconLayout'] ) ? $tab_item_array['iconLayout'] : 'none';
							$icon         = ( 'none' !== $icon_layout && isset( $tab_item_array['icon'] ) ) ? $tab_item_array['icon'] : false;
							$icon_size    = ( $icon && isset( $tab_item_array['iconSize'] ) ) ? $tab_item_array['iconSize'] : '1em';
							$icon_color   = ( $icon && isset( $tab_item_array['iconColor'] ) ) ? $tab_item_array['iconColor'] : false;
							$label_text   = isset( $tab_item_array['content'] ) ? $tab_item_array['content'] : false;
							$label_description   = isset( $tab_item_array['description'] ) ? $tab_item_array['description'] : false;
							$label_target = isset( $tab_item_array['id'] ) ? $tab_item_array['id'] : false;
							$button_id    = "label_{$label_target}";
							// Create label classes.
							$labels_classes = [
								"{$block_class}__label",
								"--icon-{$icon_layout}",
							];
							// Check if tab is active or not.
							if ( intval( $tab_item_index ) === intval( $tab_selected_index ) ) {
								$labels_classes[] = '--active';
							}
							// Create tax conext.
							$tab_context = wp_json_encode(
                                [
									'currentTab' => $tab_item_index,
								]
							);
							?>
							<div
									id="<?php echo esc_attr( $button_id ); ?>"
									class="<?php echo esc_attr( implode( ' ', $labels_classes ) ); ?>"
									role="tab"
									aria-controls="<?php echo esc_attr( $label_target ); ?>"
									data-wp-context="<?php echo esc_attr( $tab_context ); ?>"
									data-wp-on--click="actions.setSelectedTab"
									data-wp-class----active="callbacks.isTabSelected"
									data-wp-bind--aria-selected="callbacks.isTabSelected"
									data-wp-bind--tabindex="callbacks.getTabIndex"
									data-wp-watch="callbacks.scrollTabLabelIntoView"
							>
								<?php if ( $isNumberedLabels ) : ?>
									<span class="<?php echo esc_attr( "{$block_class}__index" ); ?>">
							<?php echo esc_html( $tab_item_index ); ?>
						</span>
								<?php endif; ?>
								<?php
								if ( $icon && in_array( strtolower( $icon_layout ), [ 'left', 'top' ], true ) ) :
									?>
									<span class="<?php echo esc_attr( "{$block_class}__icon" ); ?>" <?php echo ( $icon_color ) ? 'style="color:' . esc_attr( $icon_color ) . '"' : ''; ?> >
						<?php
						get_icon(
                            [
								'icon'   => $icon,
								'size'   => ( $icon_size ) ? $icon_size : '1em',
								'inline' => false,
								'echo'   => true,
							]
						);
						?>
						</span>
								<?php endif; ?>
								<?php if ( $label_text && $label_description && $showDescription ) : ?>
								<div class="<?php echo esc_attr( "{$block_class}__text-wrapper" ); ?>">
									<?php endif; ?>
									<?php if ( $label_text ) : ?>
										<span class="<?php echo esc_attr( "{$block_class}__text" ); ?>">
							<?php echo esc_html( $label_text ); ?>
						</span>
									<?php endif; ?>
									<?php
									if ( $label_description && $showDescription ) :
										?>
										<span class="<?php echo esc_attr( "{$block_class}__description" ); ?>">
							<?php echo esc_html( $label_description ); ?>
						</span>
									<?php endif; ?>
									<?php if ( $label_text && $label_description && $showDescription ) : ?>
								</div>
							<?php endif; ?>
								<?php
								if ( $icon && in_array( strtolower( $icon_layout ), [ 'right', 'bottom' ], true ) ) :
									?>
									<span class="<?php echo esc_attr( "{$block_class}__icon" ); ?>" <?php echo ( $icon_color ) ? 'style="color:' . esc_attr( $icon_color ) . '"' : ''; ?> >
						<?php
						get_icon(
                            [
								'icon'   => $icon,
								'size'   => ( $icon_size ) ? $icon_size : '1em',
								'inline' => false,
								'echo'   => true,
							]
						);
						?>
						</span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
						<?php if ( $tab_arrows && $tab_arrows_icon ) : ?>
					</div>
				</div>
				<div class="<?php echo esc_attr( "{$block_class}__arrows" ); ?>">
					<span
							role="button"
							tabindex=0
							class="<?php echo esc_attr( "{$block_class}__arrow --next" ); ?>"
							data-wp-on--click="actions.nextTab"
					>
						<?php
						get_svg(
                            [
								'icon' => $tab_arrows_icon,
								'echo' => true,
							]
						);
						?>
					</span>
				</div>
			<?php endif; ?>
			</div>
		</div>
		<?php if ( $is_tab_dropdown ) : ?>
			<select
					class="<?php echo esc_attr( "{$block_class}__dropdown" ); ?>"
					data-wp-bind--value="context.activeTab"
					data-wp-on--change="actions.setTabFromDropdown"
			>
				<?php
				foreach ( $tab_items as $tab_item_index => $tab_item_array ) :
					$label_text = isset( $tab_item_array['content'] ) ? $tab_item_array['content'] : false;
					?>
					<option value="<?php echo esc_attr( $tab_item_index ); ?>">
						<?php echo esc_html( $label_text ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
		<div class="<?php echo esc_attr( "{$block_class}__panels" ); ?>">
			<?php
			/*
			* $content outputs html from innerBlocks and it is aleady sanitized.
			*
			* Don't re-sanitize with `wp_kses_post` as it can break the core filters
			* No additional sanitization is required
			*/
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</div>
