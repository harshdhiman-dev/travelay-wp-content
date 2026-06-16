<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
	'left_accordion_title'     => get_field( 'left_accordion_title' ) ?: '',
	'right_accordion_title'    => get_field( 'right_accordion_title' ) ?: '',
	'main_image'               => get_field( 'main_image' ) ?: array(),
	'block_id'                 => $block['id'],
	// Settings
	'data_animation'           => get_field( 'accordion_component_settings_data_animation' ) ?: 'js',
	'data_gallery_animation'   => get_field( 'accordion_component_settings_data_gallery_animation' ) ?: 'js',
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
	'title_text_color'         => get_field( 'accordion_component_settings_title_text_color' ),
	'title_bg_color'           => get_field( 'accordion_component_settings_title_bg_color' ),
	'area_text_color'          => get_field( 'accordion_component_settings_area_text_color' ),
	'area_bg_color'            => get_field( 'accordion_component_settings_area_bg_color' ),
	'icon_styles'              => get_field( 'accordion_component_settings_icon_styles' ),
	'accordion_component_type' => get_field( 'component_settings_type' ) ?: 'v1',
	'layout'                   => get_field( 'layout_settings_layout_type' ) ?: 'v1',

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
		<div class="l-accordion-double <?php echo "l-accordion-double-{$args['layout']}"; ?> <?php echo $component_class; ?>" style="<?php echo $component_styles; ?>">

			<div class="l-accordion__media c-block__media">
				<?php get_template_part( 'templates/components/images/image-v1', null, array( 'image' => $args['main_image'] ) ); ?>
			</div>

			<?php if ( have_rows( 'left_accordion_content' ) ) : ?>

				<div class="l-accordion__col l-accordion__content js-acc-wrapper" <?php echo $component_data; ?>>

					<?php if ( ! empty( $args['left_accordion_title'] ) ) : ?>
						<div class="c-heading -h4">
							<h4><?php echo $args['left_accordion_title']; ?></h4>
						</div>
					<?php endif; ?>

					<div class="c-accordion c-accordion-<?php echo $args['accordion_component_type']; ?>">
						<?php
						$counter = 0;
						while ( have_rows( 'left_accordion_content' ) ) :
							the_row();
							?>

							<?php
							get_template_part(
								'templates/components-shared/accordion/accordion',
								$args['accordion_component_type'],
								array(
									'class' => '-right' . ( $counter === 0 && ! $args['data_closed_at_start'] ? ' is-active' : '' ),
									'image' => get_sub_field( 'image' ),
								)
							);
							?>

							<?php
							++$counter;
endwhile;
						?>
					</div>

				</div>

			<?php endif; ?>

			<?php if ( have_rows( 'right_accordion_content' ) ) : ?>

				<div class="l-accordion__col l-accordion__content js-acc-wrapper" <?php echo $component_data; ?>>

					<?php if ( ! empty( $args['right_accordion_title'] ) ) : ?>
						<div class="c-heading -h4">
							<h4><?php echo $args['right_accordion_title']; ?></h4>
						</div>
					<?php endif; ?>

					<div class="c-accordion c-accordion-<?php echo $args['accordion_component_type']; ?>">
						<?php
						$counter = 0;
						while ( have_rows( 'right_accordion_content' ) ) :
							the_row();
							?>

							<?php
							get_template_part(
								'templates/components-shared/accordion/accordion',
								$args['accordion_component_type'],
								array(
									'class' => '-left' . ( $counter === 0 && ! $args['data_closed_at_start'] ? ' is-active' : '' ),
									'image' => get_sub_field( 'image' ),
								)
							);
							?>

							<?php
							++$counter;
endwhile;
						?>
					</div>

				</div>

			<?php endif; ?>

		</div>

	</div>
</div>
