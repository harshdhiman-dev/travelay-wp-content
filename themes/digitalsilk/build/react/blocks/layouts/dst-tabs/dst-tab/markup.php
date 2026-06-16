<?php
/**
 * DigitalSilk Tab markup
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
$block_class         = ( isset( $block->parsed_block['blockName'] ) ) ? 'wp-block-' . sanitize_title( $block->parsed_block['blockName'] ) : '';
$parent_block_class = 'wp-block-ds-blocks-ds-tabs';

// Retrieve the current block index from the block instance.
$current_block_index = isset( $block->context['blockEditor']['blockIndex'] ) ? $block->context['blockEditor']['blockIndex'] : null;

/**
 * Extract block attributes.
 */
$block_index        = isset( $attributes['currentBlockIndex'] ) ? (int) $attributes['currentBlockIndex'] : 0;
$block_context      = isset( $block->context ) ? (array) $block->context : [];
$selected_index     = isset( $block_context['ds-blocks/selectedIndex'] ) ? (int) $block_context['ds-blocks/selectedIndex'] : 0;
$is_accordion       = isset( $block_context['ds-blocks/tabAccordion'] ) ? (bool) $block_context['ds-blocks/tabAccordion'] : false;
$is_active_selected = isset( $block_context['ds-blocks/isActiveSelected'] ) ? (bool) $block_context['ds-blocks/isActiveSelected'] : false;
$showDescription 	= isset( $block_context['ds-blocks/showDescription'] ) ? (bool) $block_context['ds-blocks/showDescription'] : false;
$isNumberedLabels	= isset( $block_context['ds-blocks/isNumberedLabels'] ) ? (bool) $block_context['ds-blocks/isNumberedLabels'] : false;
$current_index      = ( $is_active_selected ) ? ( $selected_index ) : 0;
$tab_items          = ( isset( $block_context['ds-blocks/tabItem'] ) ) ? (array) $block_context['ds-blocks/tabItem'] : [];
$tab_item_array     = ( isset( $tab_items[ $block_index + 1 ] ) ) ? (array) $tab_items[ $block_index + 1 ] : [];
$client_id          = ( isset( $tab_item_array['id'] ) ) ? $tab_item_array['id'] : '';
$additonal_classes  = [];

// Check what tab is active, and if the current one is active.
$active_tab            = ( $is_active_selected ) ? $selected_index : 0;
$is_current_tab_active = $block_index === $active_tab;

// Add tab context.
$tab_context = wp_json_encode(
	[
		'currentTabIndex' => $block_index + 1,
	]
);
// Add class to attributes.
$attributes = wp_parse_args(
	$attributes,
	[
		'class'           => implode( ' ', $additonal_classes ),
		'data-wp-context' => wp_json_encode( [ 'currentTabIndex' => $block_index + 1 ] ),
	]
);
// Create extra attributes.
$extra_attributes = wp_parse_args(
	ds_theme_generate_extra_atts( $attributes, $block ),
	$attributes
);

// If anchor is set, it takes precedence over the block ID.
if ( isset( $attributes['anchor'] ) && ! empty( $attributes['anchor'] ) ) {
	$client_id = $attributes['anchor'];
}

?>
<?php
if ( $is_accordion && $tab_item_array ) :
	$icon_layout = isset( $tab_item_array['iconLayout'] ) ? $tab_item_array['iconLayout'] : 'none';
	$icon        = ( 'none' !== $icon_layout && isset( $tab_item_array['icon'] ) ) ? $tab_item_array['icon'] : false;
	$icon_size   = ( $icon && isset( $tab_item_array['iconSize'] ) ) ? $tab_item_array['iconSize'] : '1em';
	$icon_color  = ( $icon && isset( $tab_item_array['iconColor'] ) ) ? $tab_item_array['iconColor'] : false;
	$label_text  = isset( $tab_item_array['content'] ) ? $tab_item_array['content'] : false;
	$label_description   = isset( $tab_item_array['description'] ) ? $tab_item_array['description'] : false;
	// Create label classes.
	$labels_classes = [
		"{$parent_block_class}__label {$block_class}__label",
		"--icon-{$icon_layout}",
	];
	?>
	<span
		class="<?php echo esc_attr( implode( ' ', $labels_classes ) ); ?>"
		role="tab"
		data-wp-context="<?php echo esc_attr( $tab_context ); ?>"
		data-wp-on--click="actions.toggleAccordion"
		data-wp-class----active="callbacks.isPanelActive"
		data-wp-bind--aria-selected="callbacks.isPanelActive"
		data-wp-bind--tabindex="callbacks.getPanelTabIndex"
	>
		<?php if ( $isNumberedLabels ) : ?>
			<span class="<?php echo esc_attr( "{$block_class}__index {$parent_block_class}__index " ); ?>">
							<?php echo esc_html( $block_index + 1 ); ?>
						</span>
		<?php endif; ?>
		<?php
		if ( $icon && in_array( strtolower( $icon_layout ), [ 'left', 'top' ], true ) ) :
			?>
			<span class="<?php echo esc_attr( "{$parent_block_class}__icon {$block_class}__icon" ); ?>" <?php echo ( $icon_color ) ? 'style="color:' . esc_attr( $icon_color ) . '"' : ''; ?> >
			<?php
				get_svg(
					[
						'icon'   => $icon,
						'width'  => ( $icon_size ) ? $icon_size : '1em',
						'height' => ( $icon_size ) ? $icon_size : '1em',
						'echo'   => true,
					]
				);
			?>
			</span>
		<?php endif; ?>
		<?php if ( $label_text && $label_description && $showDescription ) : ?>
			<div class="<?php echo esc_attr( "{$block_class}__text-wrapper {$parent_block_class}__text-wrapper " ); ?>">
		<?php endif; ?>
		<?php if ( $label_text ) : ?>
			<span class="<?php echo esc_attr( "{$block_class}__text {$parent_block_class}__text" ); ?>">
				<?php echo esc_html( $label_text ); ?>
			</span>
		<?php endif; ?>
		<?php
		if ( $label_description && $showDescription ) :
        ?>
			<span class="<?php echo esc_attr( "{$block_class}__description {$parent_block_class}__description" ); ?>">
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
				get_svg(
					[
						'icon'   => $icon,
						'width'  => ( $icon_size ) ? $icon_size : '1em',
						'height' => ( $icon_size ) ? $icon_size : '1em',
						'echo'   => true,
					]
				);
			?>
			</span>
		<?php endif; ?>
	</span>
<?php endif; ?>
<div
	<?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	id="<?php echo esc_attr( $client_id ); ?>"
	role="tabpanel"
	data-wp-bind--tabindex="callbacks.getPanelTabIndex"
	aria-labelledby="<?php echo esc_attr( "label_{$client_id}" ); ?>"
	data-wp-class----active="callbacks.isPanelActive"
	data-wp-bind--hidden="callbacks.isPanelHidden"
>
	<div class="<?php echo esc_attr( "{$block_class}__content" ); ?>">
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
