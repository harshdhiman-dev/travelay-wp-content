<?php
/**
 * Create a video element.
 *
 * @package DS_Theme
 *
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'video'            => get_field( 'main_video_video' ) ? get_field( 'main_video_video' ) : [],
		'poster_image'     => get_field( 'main_video_poster_image' ) ? get_field( 'main_video_poster_image' ) : '',
		'class'            => '',
		'show_js_controls' => false,
		'hide_controls'    => get_field( 'main_video_hide_controls' ) ?: false,
		'autoplay'         => get_field( 'main_video_autoplay' ) ?: false,
		'disable_lazy'     => get_field( 'main_video_disable_lazy' ) ?: false,
	)
);

if ( is_admin() ) {
	$args['disable_lazy'] = true;
}

$class_names = ! empty( $args['class'] ) ? " {$args['class']}" : '';
if ( $args['show_js_controls'] && ! $args['hide_controls'] ) {
	$class_names .= ' js-video-init';
}

if ( ! $args['disable_lazy'] ) {
	$class_names .= ' lazy';
}

$data_attrs = '';
if ( ! empty( $args['poster_image']['url'] ) ) {
	$data_attrs .= ' ' . ( false === $args['disable_lazy'] ? ' data-' : '' ) . "poster={$args['poster_image']['url']}";
}

if ( ! $args['show_js_controls'] && ! $args['hide_controls'] ) {
	$data_attrs .= ' controls';
}

if ( $args['autoplay'] ) {
	$data_attrs .= ' autoplay loop';
}

?>
<?php if ( ! empty( $args['video'] ) ) : ?>

	<?php if ( $args['show_js_controls'] && ! $args['hide_controls'] ) : ?>
		<div class="c-video__wrap js-video-wrap<?php echo $args['autoplay'] ? ' is-video-playing' : ''; ?>">
	<?php endif; ?>

	<video
		muted
		playsinline
		disablePictureInPicture
		<?php echo esc_attr( $data_attrs ); ?>
		class="c-media__src<?php echo esc_attr( $class_names ); ?>"
	>
		<source <?php echo ( false === $args['disable_lazy'] ? 'data-' : '' ) . 'src="' . esc_url( $args['video']['url'] ) . '"'; ?>
			type="<?php echo esc_attr( $args['video']['mime_type'] ?? '' ); ?>">
	</video>

	<?php if ( $args['show_js_controls'] && ! $args['hide_controls'] ) : ?>
		<div class="c-video__controls">
			<button class="c-video__btn btn-play <?php echo $args['autoplay'] ? ' is-playing' : ''; ?>"
				title="Play/Pause"></button>
			<button class="c-video__btn btn-mute is-muted" title="Mute"></button>
		</div>
		</div>
	<?php endif; ?>

<?php
endif;
