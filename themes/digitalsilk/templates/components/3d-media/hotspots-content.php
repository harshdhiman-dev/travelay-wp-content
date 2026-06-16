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
        'has_labels'       => get_field( 'hotspots_content_type_label' ) ?: false,
        'spinner_ctrls'    => get_field( 'module_image_spinner_controls' ),
    )
);

if ( empty( $args['spinner_settings']['data_has-hotspots'] ) || empty( $args['spinner_hotspots'] ) ) {
    return;
}

if ( empty( $args['has_labels'] ) && empty( $args['spinner_ctrls']['data_hotspots-nav'] ) ) {
    return;
}

$hs_index = 0;
?>
<div class="hotspots-content">
    <?php get_template_part( 'templates/components/3d-media/hotspots', 'nav' ); ?>

    <ul class="js-hotspots-list hotspots-content__list">

        <?php foreach ( $args['spinner_hotspots'] as $hotspot ) : ?>

        <?php if ( ! empty( $hotspot['hotspot_label'] ) ) : ?>
        <li class="js-hotspots-list-item hotspots-content__list-item hs-index-<?php echo $hs_index; ?> hs-frame-<?php echo $hotspot['hotspot_frame'] - 1; ?>" data-hs-index="<?php echo $hs_index; ?>" data-hs-frame="<?php echo $hotspot['hotspot_frame'] - 1; ?>">        
            <div class="hotspot_label"><?php echo $hotspot['hotspot_label']; ?></div>
        </li>
        <?php endif; ?>

        <?php $hs_index++; ?>

        <?php endforeach; ?>

    </ul>            
</div>
