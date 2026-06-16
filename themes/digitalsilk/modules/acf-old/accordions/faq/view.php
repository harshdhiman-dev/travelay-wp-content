<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
	'layout'                   => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'faq_list'                 => get_field( 'faq_list' ),
	'data_animation'           => get_field( 'accordion_component_settings_data_animation' ) ?: 'css',
	'data_expanded'            => get_field( 'accordion_component_settings_data_expanded' ) ?: 'single',
	'data_close'               => get_field( 'accordion_component_settings_data_close' ) ?: false,
	'data_closed_at_start'     => get_field( 'accordion_component_settings_data_closed_at_start' ) ?: false,
	'scroll_to_view'           => get_field( 'accordion_component_settings_data_scroll_to_view' ) ?: false,
	'accordion_display'        => get_field( 'accordion_component_settings_data_display' ) ?: false,
	'component_gap_left'       => get_field( 'accordion_component_settings_inner_gap_left' ) ?: 0,
	'component_gap_right'      => get_field( 'accordion_component_settings_inner_gap_right' ) ?: 0,
	'component_gap_top'        => get_field( 'accordion_component_settings_inner_gap_top' ) ?: 0,
	'component_gap_bottom'     => get_field( 'accordion_component_settings_inner_gap_bottom' ) ?: 0,
	'has_border'               => get_field( 'accordion_component_settings_has_border' ) ?: false,
	'border_color'             => get_field( 'accordion_component_settings_border_color' ),
	'title_bg_color'           => get_field( 'accordion_component_settings_title_bg_color' ),
	'area_bg_color'            => get_field( 'accordion_component_settings_area_bg_color' ),
	'icon_styles'              => get_field( 'accordion_component_settings_icon_styles' ),
	'accordion_component_type' => 'v1',
	'block_id'                 => $block['id'],
);

$component_data = '';
if ( $args['data_animation'] === 'js' ) {
	$component_data .= ' data-animation="js"';
}
if ( $args['data_expanded'] === 'all' ) {
	$component_data .= ' data-expand="true"';
}
if ( $args['data_close'] ) {
	$component_data .= ' data-close="true"';
}
if ( $args['data_closed_at_start'] ) {
	$component_data .= 'data-start-closed="true"';
}

if ( $args['scroll_to_view'] ) {
	$component_data .= 'data-scroll-to-view="true"';
}
if ( ! empty( $args['accordion_display'] ) && $args['accordion_display'] !== 'block' ) {
	$component_data .= 'data-acc-display="flex"';
}

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
if ( ! empty( $args['title_bg_color'] ) ) {
	$component_styles .= "--c-block-title-bg-color:{$args['title_bg_color']};";
}
if ( ! empty( $args['area_bg_color'] ) ) {
	$component_styles .= "--c-block-text-bg-color:{$args['area_bg_color']};";
}

$component_class = '';
if ( ! empty( $args['icon_styles'] ) ) {
	$component_class .= " {$args['icon_styles']}";
}
?>
<div class="m-accordion faq <?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-accordion__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="l-accordion <?php echo "l-accordion-{$args['layout']}"; ?> js-acc-wrapper<?php echo $component_class; ?>" style="<?php echo $component_styles; ?>" <?php echo $component_data; ?>>

			<?php if ( ! empty( $args['faq_list'] ) ) : ?>

				<div class="c-accordion c-accordion-<?php echo $args['accordion_component_type']; ?>">

					<?php
					$count = 0;
					foreach ( $args['faq_list'] as $i => $item ) :
						?>

						<?php
						get_template_part(
							'templates/components-shared/accordion/accordion',
							$args['accordion_component_type'],
							array(
								'accordion_id' => "{$args['block_id']}-$i",
								'title'        => $item->post_title,
								'description'  => apply_filters( 'the_content', $item->post_content ),
								'class'        => $count === 0 && ! $args['data_closed_at_start'] ? 'is-active' : '',
							)
						);
						?>

						<?php
						++$count;
					endforeach;
					?>

				</div>

			<?php endif; ?>

		</div>
	</div>
</div>
