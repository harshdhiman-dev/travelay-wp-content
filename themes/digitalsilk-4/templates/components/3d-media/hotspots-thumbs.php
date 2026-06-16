<?php
//phpcs:ignoreFile

/**
 * Build hotspots for spinner stage
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
        'spinner_settings' => get_field( 'module_image_spinner_settings' ),
        'spinner_hotspots' => get_field( 'module_image_spinner_hotspots' ) ?: [],
    )
);

if ( empty( $args['spinner_settings']['data_has-hotspots'] ) || empty( $args['spinner_hotspots'] ) ) {
    return;
}
?>
<div class="image-spinner__editor" style="">
    <?php foreach ( $args['spinner_hotspots'] as $hotspot ) : ?>
    <?php
        $frame_img_path = DS_ModuleImageSpinnerSettings::get_image_path_by_frame( $hotspot['hotspot_frame'], $args['spinner_settings'] );
    ?>
    <div class="image-spinner__editor-frame">
        <img class="hotspot-thumb" src="<?php echo $frame_img_path; ?>" alt="preview frame">
        <div class="hotspot hotspot-<?php echo $hotspot['hotspot_frame']; ?>" style="<?php echo "top:{$hotspot['hotspot_position']['top']}%; left: {$hotspot['hotspot_position']['left']}%"; ?>">
            <div class="hotspot__pin"></div>
        </div>
    </div>

    <?php endforeach; ?>
</div>
