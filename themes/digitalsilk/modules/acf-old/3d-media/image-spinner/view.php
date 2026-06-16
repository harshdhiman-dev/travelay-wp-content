<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 * @var object $moduleImageSpinner image spinner specific settings
 */
// phpcs:ignoreFile

$args = array(
	'spinner_settings' => get_field( 'module_image_spinner_settings' ),
	'spinner_layout'   => get_field( 'module_image_spinner_layout' ),
	'spinner_ctrls'    => get_field( 'module_image_spinner_controls' ),
	'spinner_hotspots' => get_field( 'module_image_spinner_hotspots' ) ?: array(),
	'has_labels'       => get_field( 'hotspots_content_type_label' ) ?: false,
	'alignment'        => get_field( 'component_settings_horizontal_alignment' ) ?: 'center',
);

if ( empty( $args['spinner_settings'] ) ) {
	return;
}

$image_max_width = '100%';

if ( ! empty( $args['spinner_layout']['image_max_width'] ) ) {
	$image_max_width = $args['spinner_layout']['image_max_width'];
}

$image_aspect_ratio = '1.5';

if ( ! empty( $args['spinner_layout']['image_aspect_ratio'] ) ) {
	$image_aspect_ratio = $args['spinner_layout']['image_aspect_ratio'];
}

$first_image_path = DS_ModuleImageSpinnerSettings::get_image_path_by_frame( 1, $args['spinner_settings'] );

$has_toc = ! empty( $args['has_labels'] );

$spinner_styles = "max-width:{$image_max_width}; aspect-ratio:{$image_aspect_ratio};";
?>
<div class="m-image-spinner<?php echo esc_attr( $block['className'] ); ?> <?php echo $moduleImageSpinner->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?> <?php echo $moduleImageSpinner->data_attributes; ?> <?php echo $moduleImageSpinner->controlsdata_attributes; ?> <?php echo $moduleImageSpinner->hotspotsdata_attributes; ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-image-spinner__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="m-image-spinner__inner">

			<div class="l-image-spinner <?php echo ( $has_toc ) ? ' has-toc' : ''; ?>">

				<div class="image-spinner__content <?php echo " text-{$args['alignment']}"; ?>">
					<?php get_template_part( 'templates/components/headings/heading' ); ?>
					<?php get_template_part( 'templates/components/cta-list', null, array( 'class' => '' ) ); ?>
				</div>

				<?php if ( is_admin() ) : ?>
					<?php get_template_part( 'templates/components/3d-media/hotspots', 'thumbs' ); ?>
				<?php endif; ?>

				<div class="image-spinner__wrap">
					<div class="image-spinner" style="<?php echo $spinner_styles; ?>">

						<div class="js-image-spinner"
							data-first-frame="<?php echo esc_attr( $first_image_path ); ?>"></div>
						<?php if ( is_admin() ) : ?>
							<img src="<?php echo esc_url( esc_attr( $first_image_path ) ); ?>"/>
						<?php endif; ?>

						<?php get_template_part( 'templates/components/3d-media/controls', 'simple' ); ?>

						<?php get_template_part( 'templates/components/3d-media/fraction' ); ?>

						<?php get_template_part( 'templates/components/3d-media/hotspots', 'stage' ); ?>
					</div>

					<?php get_template_part( 'templates/components/3d-media/hotspots', 'content' ); ?>
				</div>
			</div>

		</div>
	</div>
</div>
