<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'spinner_settings' => get_field( 'module_image_spinner_settings' ),
		'spinner_ctrls'    => get_field( 'module_image_spinner_controls' ),
	)
);

/**
 * Due to an issue with hotspots with zoom/fullscr,
 * these controls are disabled when hotspots are in use.
 */
?>
<div class="image-spinner__controls">
	<?php if ( ! empty( $args['spinner_ctrls']['data_playback'] ) ) : ?>
        <button class="js-image-spinner-play image-spinner__bttn" title="Toggle Animation">
	        Play  <!-- images/modules/3s-spinner/ -->

			Pause  <!-- images/modules/3s-spinner/ -->
        </button>
	<?php endif; ?>

	<?php if ( ! empty( $args['spinner_ctrls']['data_frames-nav'] ) ) : ?>
        <button class="js-image-spinner-prev image-spinner__bttn" title="Previous Frame">
	        Prev  <!-- images/modules/3s-spinner/ -->
        </button>

        <button class="js-image-spinner-next image-spinner__bttn" title="Next Frame">
	        Next  <!-- images/modules/3s-spinner/ -->
        </button>
	<?php endif; ?>

	<?php if ( empty( $args['spinner_settings']['data_has-hotspots'] ) && ! empty( $args['spinner_ctrls']['data_zoom'] ) ) : ?>
        <button class="js-image-spinner-zoom image-spinner__bttn" title="Toggle Zoom">
	        Zoom In  <!-- images/modules/3s-spinner/ -->
	        Zoom Out <!-- images/modules/3s-spinner/ -->
        </button>
	<?php endif; ?>

	<?php if ( empty( $args['spinner_settings']['data_has-hotspots'] ) && ! empty( $args['spinner_ctrls']['data_fullscr'] ) ) : ?>
        <button class="js-image-spinner-fullscr image-spinner__bttn" title="Full Screen">
	        Fullscreen  <!-- images/modules/3s-spinner/ -->
        </button>
	<?php endif; ?>
</div>
