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

if ( empty( $args['spinner_ctrls']['data_progress-fraction'] ) ) {
	return;
}
?>
<div class="image-spinner__fraction">
	<span class="image-spinner__fraction-current">1</span>
	/
	<span class="image-spinner__fraction-total"><?php echo absint( $args['spinner_settings']['data_count'] ); ?></span>
</div>
