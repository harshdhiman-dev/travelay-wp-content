<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'main_image'                   => get_field( 'mixed_gallery_main_image' ),
		'main_image_size'              => get_field( 'mixed_gallery_main_image_size' ),
		'secondary_image'              => get_field( 'mixed_gallery_secondary_image' ),
		'secondary_image_size'         => get_field( 'mixed_gallery_secondary_image_size' ),
		'main_content_type'            => get_field( 'mixed_gallery_main_content_type' ) ?: 'image',
		'video_source'                 => get_field( 'mixed_gallery_main_video_video_source' ),
		'video'                        => get_field( 'mixed_gallery_main_video_video' ),
		'video_embed'                  => get_field( 'mixed_gallery_main_video_video_embed' ),
		'poster_image'                 => get_field( 'mixed_gallery_main_video_poster_image' ),
		'hide_controls'                => get_field( 'mixed_gallery_main_video_hide_controls' ) ?: false,
		'autoplay'                     => get_field( 'mixed_gallery_main_video_autoplay' ) ?: false,
		'disable_lazy'                 => get_field( 'mixed_gallery_disable_lazy' ) ?: false,
		'media_ratio'                  => get_field( 'media_component_settings_media_ratio' ) ?: '16x9',
		'media_ratio_mobile'           => get_field( 'media_component_settings_mobile_media_ratio_mobile' ) ?: '16x9',
		'main_media_position'          => get_field( 'media_component_settings_main_media_position' ),
		'main_media_vertical_position' => get_field( 'media_component_settings_main_media_vertical_position' ),
		'media_fit'                    => get_field( 'media_component_settings_media_fit' ) ?: 'cover',
		'media_fit_mobile'             => get_field( 'media_component_settings_mobile_media_fit_mobile' ) ?: 'cover',
		'focal_point'                  => get_field( 'media_component_settings_focal_point' ),
		'class'                        => '',
	)
);
$className = '';
$classNameInner = '';
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}

if ( ! empty( $args['main_media_position'] ) ) {
	$className .= " media-to-{$args['main_media_position']}";
}

if ( ! empty( $args['main_media_vertical_position'] ) ) {
	$className .= " media-justify-{$args['main_media_vertical_position']}";
}

if ( ! empty( $args['media_ratio'] ) ) {
	$classNameInner .= " r-{$args['media_ratio']}";
}

if ( ! empty( $args['media_ratio_mobile'] ) ) {
	$classNameInner .= " r-{$args['media_ratio_mobile']}-mobile";
}

if ( ! empty( $args['main_content_type'] ) ) {
	$classNameInner .= " is-{$args['main_content_type']}";
}

if ( ! empty( $args['media_fit'] ) ) {
	$classNameInner .= " media-{$args['media_fit']}";
}

if ( ! empty( $args['media_fit_mobile'] ) ) {
	$classNameInner .= " media-{$args['media_fit_mobile']}-mobile";
}

if ( ! empty( $args['focal_point'] ) ) {
	$classNameInner .= " focal-{$args['focal_point']}";
}
?>

<?php if ( ! empty( $args['main_image'] ) || ! empty( $args['secondary_image'] ) || ! empty( $args['video'] ) || ! empty( $args['video_embed'] ) ) : ?>
	<div class="c-media<?php echo esc_attr( $className ); ?>">

		<?php if ( 'image' === $args['main_content_type'] && ! empty( $args['main_image']['ID'] ) ) : ?>
			<figure class="c-media__primary<?php echo esc_attr( $classNameInner ); ?>">
				<?php echo ds_generate_image( $args['main_image']['ID'], $args['main_image_size'], 'c-media__src', '', ! $args['disable_lazy'] ); ?>
			</figure>
		<?php elseif ( 'video' === $args['main_content_type'] ) : ?>
			<figure class="c-media__primary<?php echo esc_attr( $classNameInner ); ?>">
				<?php if ( 'internal' === $args['video_source'] ) : ?>
					<?php
					get_template_part(
						'templates/components/videos/video-box',
						null,
						array(
							'video'            => $args['video'],
							'poster_image'     => $args['poster_image'],
							'show_js_controls' => true,
							'hide_controls'    => $args['hide_controls'],
							'autoplay'         => $args['autoplay'],
							'disable_lazy'     => $args['disable_lazy'],
						)
					);
					?>
				<?php else : ?>
					<?php
					get_template_part(
						'templates/components/videos/video-embed',
						null,
						array(
							'iframe'       => $args['video_embed'],
							'disable_lazy' => $args['disable_lazy'],
						)
					);
					?>
				<?php endif; ?>
			</figure>
		<?php endif; ?>

		<?php if ( ! empty( $args['secondary_image']['ID'] ) ) : ?>
			<figure class="c-media__secondary">
				<?php echo ds_generate_image( $args['secondary_image']['ID'], $args['secondary_image_size'], 'c-media__src', '', ! $args['disable_lazy'] ); ?>
			</figure>
		<?php endif; ?>

	</div>
<?php
endif;
