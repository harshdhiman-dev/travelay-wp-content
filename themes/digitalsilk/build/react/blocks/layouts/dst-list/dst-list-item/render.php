<?php
/**
 * Block Name: c-list-item
 *
 * @package Digitalsilk
 */

$block_context = isset( $block->context ) ? (array) $block->context : [];
$show_subtitle = isset( $block_context['ds-blocks/showSubtitle'] ) ? (bool) $block_context['ds-blocks/showSubtitle'] : false;
$show_icons    = isset( $block_context['ds-blocks/showIcons'] ) ? (bool) $block_context['ds-blocks/showIcons'] : false;
$show_hero     = isset( $block_context['ds-blocks/showHeroText'] ) ? (bool) $block_context['ds-blocks/showHeroText'] : false;
$icon          = isset( $attributes['icon'] ) ? (string) $attributes['icon'] : '';
$icon_size     = isset( $attributes['iconSize'] ) ? (string) $attributes['iconSize'] : '100%';
$icon_display  = isset( $attributes['iconDisplay'] ) ? (string) $attributes['iconDisplay'] : 'inline';
$icon_inline   = 'inline' === $icon_display ? true : false;
$hero_text     = isset( $attributes['heroText'] ) ? (string) $attributes['heroText'] : '';
$list_title    = isset( $attributes['listTitle'] ) ? (string) $attributes['listTitle'] : '';
$list_subtitle = isset( $attributes['listSubTitle'] ) ? (string) $attributes['listSubTitle'] : '';

$attributes = array_merge(
	$attributes,
	[
		'class' => 'dst-list__item',
	]
);

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );

// If we are not showing hero text or icons
// check if we have list title or subtitle.
if ( ! $show_hero && ! $show_icons ) {
	if ( empty( $list_title ) && empty( $list_subtitle ) ) {
		return '';
	}
}

?>
<li <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $content ) : ?>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php else : ?>
		<?php if ( ! empty( $icon ) && $show_icons ) : ?>
			<div class="dst-list__media ds-list-item__icon"
				<?php if ( $icon_size ) : ?>
					style="--dst-list__icon-size: <?php echo esc_attr( $icon_size ); ?>;"
				<?php endif; ?>
			>
				<span class="dst-icon">
					<?php
					get_icon(
						[
							'icon'   => $icon,
							'size'   => $icon_size,
							'inline' => $icon_inline,
							'echo'   => true,
						]
					);
					?>
				</span>
			</div>
		<?php endif; ?>
		<?php if ( $show_hero && ! empty( $hero_text ) ) : ?>
			<div class="dst-list__hero">
				<?php echo wp_kses_post( $hero_text ); ?>
			</div>
		<?php endif; ?>
		<div class="dst-list__content">
			<?php if ( ! empty( $list_title ) ) : ?>
				<div class="dst-list__title">
					<?php echo wp_kses_post( $list_title ); ?>
				</div>
			<?php endif; ?>
			<?php if ( $show_subtitle && ! empty( $list_subtitle ) ) : ?>
				<div class="dst-list__description">
					<?php echo wp_kses_post( $list_subtitle ); ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</li>
