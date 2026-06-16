<?php
/**
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var DS_ModuleDefaultSettings $module_config ->get_styles(), ->data_attributes, ->container, ->container_width.
 */

$args = array(
	'layout_gap_top'           => get_field( 'layout_settings_top_layout_gap' ) ?: false,
	'layout_gap_top_custom'    => get_field( 'layout_settings_top_layout_custom_gap' ) ?: false,
	'layout_gap_bottom'        => get_field( 'layout_settings_bottom_layout_gap' ) ?: false,
	'layout_gap_bottom_custom' => get_field( 'layout_settings_bottom_layout_custom_gap' ) ?: false,
	'screen_height'            => get_field( 'layout_settings_screen_height' ) ?: 'small',
	'custom_height'            => get_field( 'layout_settings_custom_height' ),
	'custom_height_mobile'     => get_field( 'layout_settings_custom_height_mobile' ),
	'overlay_opacity_color'    => get_field( 'overlay_opacity_color' ) ?: false,
);

$additional_class = '';

if ( 0 !== (int) $module_config->overlay_opacity ) {
	$module_config->set_style( '--overlayOpacity', "{$module_config->overlay_opacity}%" );
	$additional_class .= ' has-overlay';
}

if ( ! empty( $args['overlay_opacity_color'] ) ) {
	$module_config->set_style( '--overlayOpacityColor', "{$module_config->overlay_opacity_color}" );
}

if ( 0 !== (int) $module_config->layout_settings_columns_ratio ) {
	$module_config->set_style( '--columns-ratio', "{$module_config->layout_settings_columns_ratio}%" );
}

if ( ! empty( $args['custom_height'] ) && is_array( $args['custom_height'] ) && $args['custom_height']['height'] ) {
	$module_config->set_style( '--bannerHeightDesktop', "{$args['custom_height']['height']}" );
}

if ( ! empty( $args['custom_height_mobile'] ) && is_array( $args['custom_height_mobile'] ) && $args['custom_height_mobile']['height'] ) {
	$module_config->set_style( '--bannerHeightMobile', "{$args['custom_height_mobile']['height']}" );
}

$columns_order_class = false !== $module_config->layout_settings_columns_order ? " order-{$module_config->layout_settings_columns_order}" : ' order-default';
$additional_class    .= $columns_order_class;

if ( false !== $module_config->layout_settings_vertical_columns ) {
	$additional_class .= ' is-vertical';
}

if ( false !== $module_config->layout_settings_header_height ) {
	$additional_class .= ' has-header-height';
}

$className     = '';
$banner_styles = '';

// Store the original columns ratio variable
if ( isset( $module_config->layout_settings_columns_ratio ) ) {
	$banner_styles .= "--columns-ratio: {$module_config->layout_settings_columns_ratio}%;";
} else {
	$banner_styles .= '--columns-ratio: 100%;';
}

// Add custom padding top if needed
if ( ! empty( $args['layout_gap_top'] ) ) {
	$className .= " {$args['layout_gap_top']}";

	if ( 'space-top-custom' === $args['layout_gap_top'] && ! empty( $args['layout_gap_top_custom'] ) ) {
		$banner_styles .= " --padding-top: {$args['layout_gap_top_custom']};";
	}
}

// Add custom padding bottom if needed
if ( ! empty( $args['layout_gap_bottom'] ) ) {
	$className .= " {$args['layout_gap_bottom']}";

	if ( 'space-bottom-custom' === $args['layout_gap_bottom'] && ! empty( $args['layout_gap_bottom_custom'] ) ) {
		$banner_styles .= " --padding-bottom: {$args['layout_gap_bottom_custom']};";
	}
}

$screen_height_class = is_array( $args['screen_height'] ) ? 'custom' : $args['screen_height'];

?>

<div
	class="m-banner m-banner--<?php echo $screen_height_class; ?><?php echo esc_attr( $block['className'] . $additional_class ); ?>" <?php echo $module_config->data_attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div
		class="m-banner__container <?php echo esc_attr( $module_config->container ); ?>"
		<?php echo $module_config->get_styles(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

		<?php echo $module_config->backgroundMediaHTML; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div
			class="m-banner__inner container <?php echo 'flex-' . esc_attr( $module_config->content_position ); ?> <?php echo esc_attr( $module_config->content_style ); ?>">

			<div class="l-banner <?php echo esc_attr( $className ); ?>"
				 style="<?php echo esc_attr( $banner_styles ); ?>">

				<?php
				get_template_part(
					'templates/components-shared/blocks/block-banner-inner',
					'hybrid',
					array(
						'allowed_blocks' => $block['attributes']['allowedBlocks'],
						'template_arr'   => $block['attributes']['templateArr'],
					)
				);
				?>

			</div>

			<?php get_template_part( 'templates/components/nav/scroll-down' ); ?>
		</div>
	</div>
</div>
