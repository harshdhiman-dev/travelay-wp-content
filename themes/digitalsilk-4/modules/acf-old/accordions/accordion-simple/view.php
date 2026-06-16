<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
	'component_gap_left'       => get_field( 'accordion_component_settings_inner_gap_left' ) ?: 0,
	'component_gap_right'      => get_field( 'accordion_component_settings_inner_gap_right' ) ?: 0,
	'component_gap_top'        => get_field( 'accordion_component_settings_inner_gap_top' ) ?: 0,
	'component_gap_bottom'     => get_field( 'accordion_component_settings_inner_gap_bottom' ) ?: 0,
	'has_border'               => get_field( 'accordion_component_settings_has_border' ) ?: false,
	'border_color'             => get_field( 'accordion_component_settings_border_color' ),
	'title_text_color'         => get_field( 'accordion_component_settings_title_text_color' ),
	'title_bg_color'           => get_field( 'accordion_component_settings_title_bg_color' ),
	'area_text_color'          => get_field( 'accordion_component_settings_area_text_color' ),
	'area_bg_color'            => get_field( 'accordion_component_settings_area_bg_color' ),
	'icon_styles'              => get_field( 'accordion_component_settings_icon_styles' ),
	'layout'                   => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'accordion_component_type' => 'v1',
	'block_id'                 => $block['id'],
);

$component_data = 'data-close="true"';

$component_styles = '';
if ($args['component_gap_left'] != 0) {
	$component_styles .= "--c-block-gl: {$args['component_gap_left']}px;";
}
if ($args['component_gap_right'] != 0) {
	$component_styles .= "--c-block-gr: {$args['component_gap_right']}px;";
}
if ($args['component_gap_top'] != 0) {
	$component_styles .= "--c-block-gt: {$args['component_gap_top']}px;";
}
if ($args['component_gap_bottom'] != 0) {
	$component_styles .= "--c-block-gb: {$args['component_gap_bottom']}px;";
}
if ( ! empty( $args['border_color'] ) ) {
	$component_styles .= "--c-block-border-color:{$args['border_color']};";
}
if ( ! empty( $args['title_text_color'] ) ) {
	$component_styles .= "--c-block-title-color:{$args['title_text_color']};";
}
if ( ! empty( $args['title_bg_color'] ) ) {
	$component_styles .= "--c-block-title-bg-color:{$args['title_bg_color']};";
}
if ( ! empty( $args['area_text_color'] ) ) {
	$component_styles .= "--c-block-text-color:{$args['area_text_color']};";
}
if ( ! empty( $args['area_bg_color'] ) ) {
	$component_styles .= "--c-block-text-bg-color:{$args['area_bg_color']};";
}

$component_class = '';
if ( ! empty( $args['icon_styles'] ) ) {
	$component_class .= " {$args['icon_styles']}";
}
?>
<div class="m-accordion<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-accordion__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="l-accordion <?php echo "l-accordion-{$args['layout']}"; ?> js-a-light<?php echo $component_class; ?>" style="<?php echo $component_styles; ?>" <?php echo $component_data; ?> data-animation="css">

			<div class="c-accordion c-accordion-<?php echo $args['accordion_component_type']; ?>">
				<?php
				get_template_part(
					'templates/components-shared/accordion/accordion',
					$args['accordion_component_type'],
					array(
						'title'           => get_field( 'title' ),
						'description'     => get_field( 'description' ),
						'cta_list'        => get_field( 'cta_list' ),
						'light_accordion' => true,
					)
				);
				?>
			</div>

		</div>
	</div>
</div>
