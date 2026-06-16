<?php
/**
 * Create a universal video element,
 * that has ether an external video and or local one.
 *
 * @package DS_Theme
 *
 * @var array $args
 */

/**
 * Default arguments for the video component.
 *
 * @var array $args {
 *     Array of arguments to configure the video component.
 *
 *     @type array  $video            Local video data, including 'url' and 'mime_type'.
 *     @type array  $poster_image     Poster image for the video, containing 'url' and other attributes.
 *     @type string $embed_html       Embed HTML for external video (e.g., YouTube, Vimeo).
 *     @type string $class            Additional CSS class for the video element.
 *     @type string $class_container  Default CSS class for the video container.
 *     @type bool   $show_js_controls Whether to show custom JavaScript-based video controls.
 *     @type bool   $hide_controls    Whether to hide default video controls.
 *     @type bool   $autoplay         Whether the video should autoplay.
 *     @type bool   $disable_lazy     Whether to disable lazy loading for the video.
 * }
 */
$args = wp_parse_args(
	$args,
	[
		'video'            => get_field( 'main_video_video' ) ?? [],
		'poster_image'     => get_field( 'main_video_poster_image' ) ?? '',
		'embed_html'       => '',
		'class'            => 'c-media__src', // Also allows an array of classes.
		'class_container'  => 'c-video__wrap', // Also allows an array of classes.
		'show_js_controls' => false,
		'hide_controls'    => get_field( 'main_video_hide_controls' ) ?? false,
		'autoplay'         => get_field( 'main_video_autoplay' ) ?? false,
		'disable_lazy'     => false,
	]
);

// Create video and wrapper class names.
$video_classnames     = is_array( $args['class'] ) ? $args['class'] : [ $args['class'] ];
$container_classnames = is_array( $args['class_container'] ) ? $args['class_container'] : [ $args['class_container'] ];
if ( $args['show_js_controls'] && ! $args['hide_controls'] ) {
	$video_classnames[]     = 'js-video-init';
	$container_classnames[] = 'js-video-wrap';
}
if ( ! $args['disable_lazy'] ) {
	$video_classnames[] = 'lazy';
}

// Create video data attributes.
$data_attrs = [];
if (
	isset( $args['poster_image']['url'] ) &&
	! empty( $args['poster_image']['url'] )
) {
	$data_attrs[] = ( false === $args['disable_lazy'] ? ' data-' : '' ) . "poster={$args['poster_image']['url']}";
}
if ( ! $args['show_js_controls'] && ! $args['hide_controls'] ) {
	$data_attrs[] = 'controls';
}
if ( $args['autoplay'] ) {
	$data_attrs[]           = 'autoplay';
	$data_attrs[]           = 'loop';
	$container_classnames[] = 'is-video-playing';
}

?>
<?php if ( ! empty( $args['video'] ) || ! empty( $args['embed_html'] ) ) : ?>

	<div class="<?php echo esc_attr( implode( ' ', $container_classnames ) ); ?>">

		<?php
		/**
		 * Show a local video file.
		 */
		if ( $args['video'] ) :
			?>
			<video
				muted
				playsinline
				disablePictureInPicture
				<?php echo implode( ' ', array_map( 'esc_attr', $data_attrs ) ); ?>
				class="<?php echo esc_attr( implode( ' ', $video_classnames ) ); ?>"
			>
				<source
					<?php echo ( false === $args['disable_lazy'] ? 'data-' : '' ) . 'src="' . esc_url( $args['video']['url'] ) . '"'; ?>
					<?php echo ! empty( $args['video']['mime_type'] ) ? ' type="' . esc_attr( $args['video']['mime_type'] ) . '"' : ''; ?>
				>
			</video>

			<?php if ( $args['show_js_controls'] && ! $args['hide_controls'] ) : ?>
				<div class="c-video__controls">
					<button class="c-video__btn btn-play <?php echo $args['autoplay'] ? ' is-playing' : ''; ?>" title="<?php esc_attr_e( 'Play/Pause', 'dstheme' ); ?>"></button>
					<button class="c-video__btn btn-mute is-muted" title="<?php esc_attr_e( 'Mute', 'dstheme' ); ?>"></button>
				</div>
			<?php endif; ?>

			<?php
			/**
			 * Show an external video.
			 */
			elseif ( $args['embed_html'] ) :
				$video_iframe = $args['embed_html'];

				// Check if lazy loading is enabled and ensure 'loading="lazy"' is added to the iframe.
				if ( ! $args['disable_lazy'] && false === strpos( $video_iframe, 'loading=' ) ) {
					// Add loading="lazy" to the iframe tag.
					$video_iframe = preg_replace( '/<iframe(.*?)>/', '<iframe loading="lazy"$1>', $video_iframe );
				}

				// Allowed HTML for the video iframe.
				$allowed_html = [
					'iframe' => [
						'src'             => true,
						'width'           => true,
						'height'          => true,
						'frameborder'     => true,
						'allow'           => true,
						'allowfullscreen' => true,
						'referrerpolicy'  => true,
						'sandbox'         => true,
						'loading'         => true,
						'title'           => true,
						'name'            => true,
						'id'              => true,
						'class'           => true,
						'style'           => true,
					],
				];
				echo wp_kses( $video_iframe, $allowed_html );
		endif;
			?>

	</div>

	<?php
endif;
