<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
	'has_image_description'    => get_field( 'has_image_description' ) ?: false,
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
	'gallery_component_type'   => get_field( 'component_settings_gallery_component_type' ) ?: 'v1',
	'columns_order'            => get_field( 'component_settings_columns_order' ) ?: 'default',
	'columns_ratio'            => get_field( 'component_settings_columns_ratio' ),
	'layout'                   => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'enable_gallery'           => get_field( 'accordion_gallery' ),
	'block_id'                 => $block['id'],
);

$component_data = '';
if ( $args['data_animation'] === 'js' ) {
	$component_data .= ' data-animation="js"';
}
if ( $args['data_gallery_animation'] === 'js' && $args['enable_gallery'] ) {
	$component_data .= ' data-gallery-animation="js"';
}
if ( $args['data_expanded'] === 'all' && ! $args['enable_gallery'] ) {
	$component_data .= ' data-expand="true"';
}
if ( $args['data_close'] && ! $args['enable_gallery'] ) {
	$component_data .= ' data-close="true"';
}
if ( ! $args['enable_gallery'] && $args['data_closed_at_start'] ) {
	$component_data .= 'data-start-closed="true"';
}

if ( $args['enable_gallery'] ) {
	$component_data .= ' data-gallery="true"';
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
if (intval( $args['columns_ratio'] ) !== 0 ) {
	$component_styles .= "--columns-ratio: {$args['columns_ratio']}%;";
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
		<div class="l-accordion <?php echo "l-accordion-{$args['layout']}"; ?> <?php echo "order-{$args['columns_order']}"; ?> js-acc-wrapper<?php echo $component_class; ?>" style="<?php echo $component_styles; ?>" <?php echo $component_data; ?>>

			<?php if ( have_rows( 'accordion_content' ) ) : ?>

				<div class="l-accordion__content">

					<?php get_template_part( 'templates/components/headings/heading' ); ?>

					<div class="c-accordion c-accordion-<?php echo $args['accordion_component_type']; ?>">
						<?php
						$counter = 0;
						while ( have_rows( 'accordion_content' ) ) :
							the_row();
							?>

							<?php
							get_template_part(
								'templates/components-shared/accordion/accordion',
								$args['accordion_component_type'],
								array(
									'class' => $counter === 0 && ! $args['data_closed_at_start'] ? 'is-active' : '',
								)
							);
							?>

							<?php
							++$counter;
endwhile;
						?>
					</div>

					<?php get_template_part( 'templates/components/cta-list' ); ?>

				</div>

				<div class="l-accordion__media">
					<?php if ( $args['enable_gallery'] ) : ?>
						<?php
						$counter = 0;
						while ( have_rows( 'accordion_content' ) ) :
							the_row();
							?>
							<div class="l-accordion__imgs c-accordion__media js-acc-media<?php echo $counter === 0 ? ' is-active' : ''; ?>" data-actab-id="<?php echo "{$args['block_id']}-$counter"; ?>">
								<?php
								$mixed_gallery = get_sub_field( 'mixed_gallery' );
								get_template_part(
									'templates/components-shared/media-mixed/mixed-gallery',
									$args['gallery_component_type'],
									array(
										'main_image'      => $mixed_gallery['main_image'] ?? false,
										'secondary_image' => $mixed_gallery['secondary_image'] ?? false,
										'main_content_type' => $mixed_gallery['main_content_type'] ?: 'image',
										'video_source'    => $mixed_gallery['main_video']['video_source'] ?? false,
										'video'           => $mixed_gallery['main_video']['video'] ?? false,
										'video_embed'     => $mixed_gallery['main_video']['video_embed'] ?? false,
										'poster_image'    => $mixed_gallery['main_video']['poster_image'] ?? false,
										'hide_controls'   => $mixed_gallery['main_video']['hide_controls'] ?? false,
										'autoplay'        => $mixed_gallery['main_video']['autoplay'] ?? false,
									)
								);
								?>

								<?php if ( get_sub_field( 'has_image_description' ) ) : ?>

									<?php get_template_part( 'templates/components/content/info-v1', null, array( 'description' => get_sub_field( 'image_description' ) ) ); ?>

								<?php endif; ?>

							</div>
							<?php
							++$counter;
endwhile;
						?>
					<?php else : ?>
						<div class="c-accordion__media">

							<?php get_template_part( 'templates/components-shared/media-mixed/mixed-gallery', $args['gallery_component_type'] ); ?>

							<?php if ( $args['has_image_description'] ) : ?>

								<?php get_template_part( 'templates/components/content/info-v1', null, array( 'description' => get_field( 'image_description' ) ) ); ?>

							<?php endif; ?>

						</div>
					<?php endif; ?>
				</div>

			<?php endif; ?>

		</div>
	</div>
</div>
