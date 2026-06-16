<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'main_image'                        => get_field( 'main_image' ),
		'main_image_size'                   => 'large',
		'front_image'                       => get_field( 'front_image' ),
		'front_image_size'                  => 'medium_large',
		'media_ratio'                       => get_field( 'media_component_settings_media_ratio' ),
		'main_image_position'               => get_field( 'media_component_settings_main_image_position' ),
		'main_image_vertical_position'      => get_field( 'media_component_settings_main_image_vertical_position' ),
		'class'                             => '',
	)
);
$className = '';
$classNameInner = '';
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}

if ( ! empty( $args['main_image_position'] ) ) {
	$className .= " media-to-{$args['main_image_position']}";
}

if ( ! empty( $args['main_image_vertical_position'] ) ) {
	$className .= " media-justify-{$args['main_image_vertical_position']}";
}

if ( ! empty( $args['media_ratio'] ) ) {
	$className .= " r-{$args['media_ratio']}";
}
?>
<?php if ( ! empty( $args['main_image'] ) || ! empty( $args['front_image'] ) ) : ?>
    <div class="c-media<?php echo esc_attr( $className ); ?>">

		<?php if ( ! empty( $args['main_image']['ID'] ) ) : ?>
			<figure class="c-media__primary<?php echo esc_attr( $classNameInner ); ?>">
				<?php echo ds_generate_image( $args['main_image']['ID'], $args['main_image_size'], 'c-media__src' ); ?>
			</figure>
		<?php endif; ?>

		<?php if ( ! empty( $args['front_image']['ID'] ) ) : ?>
			<figure class="c-media__secondary">
				<?php echo ds_generate_image( $args['front_image']['ID'], $args['front_image_size'], 'c-media__src' ); ?>
			</figure>
		<?php endif; ?>

    </div>
<?php
endif;
