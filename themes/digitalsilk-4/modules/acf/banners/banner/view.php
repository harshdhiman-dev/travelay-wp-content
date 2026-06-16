<?php
/**
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var DS_ModuleDefaultSettings $module_config ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

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

if ( (int) $module_config->overlay_opacity !== 0 ) {
	$module_config->set_style( '--overlayOpacity', "{$module_config->overlay_opacity}%" );
	$additional_class .= ' has-overlay';
}

if ( ! empty( $args['overlay_opacity_color'] ) ) {
	$module_config->set_style( '--overlayOpacityColor', "{$module_config->overlay_opacity_color}" );
}

if ( (int) $module_config->layout_settings_columns_ratio !== 0 ) {
	$module_config->set_style( '--columns-ratio', "{$module_config->layout_settings_columns_ratio}%" );
}

if ( ! empty( $args['custom_height'] ) && is_array( $args['custom_height'] ) && $args['custom_height']['height'] ) {
	$module_config->set_style( '--bannerHeightDesktop', "{$args['custom_height']['height']}" );
}

if ( ! empty( $args['custom_height_mobile'] ) && is_array( $args['custom_height_mobile'] ) && $args['custom_height_mobile']['height'] ) {
	$module_config->set_style( '--bannerHeightMobile', "{$args['custom_height_mobile']['height']}" );
}

$columns_order_class = $module_config->layout_settings_columns_order !== false ? " order-{$module_config->layout_settings_columns_order}" : ' order-default';
$additional_class   .= $columns_order_class;

if ( $module_config->layout_settings_vertical_columns !== false ) {
	$additional_class .= ' is-vertical';
}

if ( $module_config->layout_settings_header_height !== false ) {
	$additional_class .= ' has-header-height';
}

$className = '';
$styles    = $module_config->get_styles();
if ( ! empty( $args['layout_gap_top'] ) ) {
	$className .= " {$args['layout_gap_top']}";

	if ( $args['layout_gap_top'] === 'space-top-custom' && ! empty( $args['layout_gap_top_custom'] ) ) {
		$styles .= "--padding-top: {$args['layout_gap_top_custom']};";
	}
}

if ( ! empty( $args['layout_gap_bottom'] ) ) {
	$className .= " {$args['layout_gap_bottom']}";

	if ( $args['layout_gap_bottom'] === 'space-bottom-custom' && ! empty( $args['layout_gap_bottom_custom'] ) ) {
		$styles .= "--padding-bottom: {$args['layout_gap_bottom_custom']};";
	}
}

$screen_height_class = is_array( $args['screen_height'] ) ? 'custom' : $args['screen_height'];

?>
<div class="m-banner m-banner--<?php echo $screen_height_class; ?><?php echo esc_attr( $block['className'] . $additional_class ); ?>" <?php echo $module_config->data_attributes; ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-banner__container <?php echo $module_config->container; ?>" <?php echo $module_config->get_styles(); ?>>

		<?php echo $module_config->backgroundMediaHTML; ?>

		<div class="m-banner__inner container <?php echo 'flex-' . $module_config->content_position; ?> <?php echo $module_config->content_style; ?>">

			<div class="l-banner <?php echo esc_attr( $className ); ?>">

				<?php get_template_part( 'templates/components-shared/blocks/block-banner-inner' ); ?>

			</div>

			<?php get_template_part( 'templates/components/nav/scroll-down' ); ?>
		</div>
	</div>

</div>
