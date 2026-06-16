<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'class'       => '',
		'counter'     => false,
		'has_counter' => get_field( 'slider_navigation_component_settings_has_counter' ) ?: false,
		'label'       => '',
		'icon'        => false,
		'is_rounded'  => get_field( 'slider_navigation_component_settings_is_icon_rounded' ) ?: false,
		'has_video'   => false,
		'video'       => array(),
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}

if ( ! empty( $args['icon'] ) && $args['is_rounded'] ) {
	$className .= ' is-icon-rounded';
}
?>
<button class="slider-nav__item<?php echo esc_attr( $className ); ?>">

	<?php if ( $args['has_counter'] && ! empty( $args['counter'] ) ) : ?>
		<span class="slider-nav__counter"><?php printf( '%02d', $args['counter'] ); //phpcs:ignore?></span>
	<?php endif; ?>

	<?php if ( ! empty( $args['icon']['ID'] ) ) : ?>
		<span class="slider-nav__icon">
			<?php echo ds_generate_image( $args['icon']['ID'], 'ds_small', 'slider-nav__src' ); ?> <!-- TODO: ds_small doesn't work, BE to check -->
		</span>
	<?php endif; ?>

	<?php if ( ! empty( $args['label'] ) ) : ?>
		<span class="slider-nav__label"><?php echo wp_kses_post( $args['label'] ); ?></span>
	<?php endif; ?>

	<?php if ( $args['has_video'] && ! empty( $args['video'] ) ) : ?>
		<div class="slider-nav__bottom">
			<?php
			get_template_part(
				'templates/components-shared/videos/video-popup',
				null,
				array(
					'url'          => 'internal' === $args['video']['video_source'] ? ( $args['video']['video']['url'] ?? '' ) : ( ds_get_src_from_iframe( $args['video']['video_embed'] ?? '' ) ),
					'poster_image' => $args['video']['poster_image'] ?? array(),
				)
			);
			?>
		</div>
	<?php endif; ?>
</button>
