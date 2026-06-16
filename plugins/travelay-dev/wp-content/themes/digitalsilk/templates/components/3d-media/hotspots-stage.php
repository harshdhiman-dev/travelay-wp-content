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
        'has_tooltips'     => get_field( 'hotspots_content_type_tooltip' ) ?: false,
    )
);

if ( empty( $args['spinner_settings']['data_has-hotspots'] ) || empty( $args['spinner_hotspots'] ) ) {
    return;
}

$hs_index = 0;
?>
<div class="hotspots" style="display:none;">
    <?php foreach ( $args['spinner_hotspots'] as $hotspot ) : ?>
    <div class="hotspot hotspot-frame-<?php echo $hotspot['hotspot_frame'] - 1; ?> hotspot-index-<?php echo $hs_index; ?>"
    data-hotspot-index="<?php echo $hs_index; ?>"
    data-hotspot-frame="<?php echo $hotspot['hotspot_frame'] - 1; ?>"
    style="<?php echo "top:{$hotspot['hotspot_position']['top']}%; left: {$hotspot['hotspot_position']['left']}%"; ?>">
        <div class="hotspot__pin js-hotspot-pin"></div>

        <?php if ( ! empty( $args['has_tooltips'] ) ) : ?>
            <div class="hotspot__tooltip">
                <?php if ( ! empty( $hotspot['hotspot_tooltip']['title'] ) ) : ?>
                <div class="hotspot__tooltip-title is-style-colors-inverted -h4"><?php echo $hotspot['hotspot_tooltip']['title']; ?></div>
                <?php endif; ?>

                <?php if ( ! empty( $hotspot['hotspot_tooltip']['description'] ) ) : ?>
                <div class="hotspot__tooltip-description is-wysiwyg"><?php echo $hotspot['hotspot_tooltip']['description']; ?></div>
                <?php endif; ?>

                <button class="hotspot__tooltip-close"></button>
            </div>
        <?php endif; ?>

        <?php $hs_index++; ?>
    </div>
    <?php endforeach; ?>
</div>
