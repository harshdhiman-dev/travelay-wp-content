<?php
/**
 * DST Media markup
 *
 * @package ds_theme
 *
 * @var array $args
 */

/**
 * Default arguments.
 */
$args = wp_parse_args(
	$args,
	[
		'primaryType'        => '',
		'videoExternal'      => [
			'url'  => '',
			'html' => '',
		],
		'imagePrimary'       => [
			'id'    => '',
			'url'   => '',
			'alt'   => '',
			'size'  => 'full',
			'sizes' => [
				'full'      => [],
				'large'     => [],
				'medium'    => [],
				'thumbnail' => [],
			],
		],
		'imagePrimaryMobile' => [
			'id'    => '',
			'url'   => '',
			'alt'   => '',
			'size'  => 'full',
			'sizes' => [
				'full'      => [],
				'large'     => [],
				'medium'    => [],
				'thumbnail' => [],
			],
		],
		'videoLocal'         => [
			'id'       => '',
			'url'      => '',
			'alt'      => '',
			'autoplay' => false,
			'controls' => false,
			'poster'   => [
				'id'  => '',
				'url' => '',
			],
		],
		'mediaDescription'   => [
			'show' => false,
			'text' => '',
		],
		'lazyLoad'           => false,
		'showImageSecondary' => false,
		'imageSecondary'     => [
			'id'    => '',
			'url'   => '',
			'alt'   => '',
			'size'  => 'full',
			'sizes' => [
				'full'      => [],
				'large'     => [],
				'medium'    => [],
				'thumbnail' => [],
			],
		],
		'videoPopup'         => false,
		'style'              => [
			'desktop' => [
				'mediaRatio' => '',
				'mediaFit'   => '',
				'focalPoint' => [
					'x' => 0.5,
					'y' => 0.5,
				],
			],
			'mobile'  => [
				'mediaRatio' => '',
				'mediaFit'   => '',
				'focalPoint' => [
					'x' => 0.5,
					'y' => 0.5,
				],
			],
		],
	]
);

// Extract global settings.
$primary_type   = (string) $args['primaryType'];
$lazy_load      = (bool) $args['lazyLoad'];
$show_secondary = (bool) $args['showImageSecondary'];

// Bail if there is no primary type.
if ( ! $primary_type ) {
	return;
}

// Create a container data attributes.
$container_data_attributes = [];

// Create a container class, based on the primary type.
$container_class = [ 'dst-media__primary' ];
if ( 'videoLocal' === $primary_type ) {
	$container_class[] = 'is-video is-local';
} elseif ( 'videoExternal' === $primary_type ) {
	$container_class[] = 'is-video is-external';
} elseif ( 'image' === $primary_type ) {
	$container_class[] = 'is-image';
}
// Add inline styles.
$container_class[] = Ds_Media_Helpers::get_styles_classes( $args );

// Check for video popup settings.
$video_popup   = ( isset( $args['videoPopup'] ) ) ? (bool) $args['videoPopup'] : false;
$container_tag = 'figure';
if (
	( 'videoExternal' === $primary_type || 'videoLocal' === $primary_type ) &&
	$video_popup
) {
	$container_tag             = 'a';
	$container_class[]         = ' is-popup';
	$container_data_attributes = [
		'data-dimbox'      => wp_unique_id( 'dst-popup-video-' ),
		'data-dimbox-type' => ( 'videoLocal' === $primary_type ) ? 'video' : 'iframe',
		'href'             => Ds_Media_Helpers::get_video_url( $args ),
	];
}

// Create media description.
$media_description = '';
if (
	isset( $args['mediaDescription']['show'] ) &&
	$args['mediaDescription']['show'] &&
	isset( $args['mediaDescription']['text'] ) &&
	! empty( $args['mediaDescription']['text'] )
) {
	$media_description_tag = ( $video_popup ) ? 'span' : 'figcaption';
	$media_description     = '<' . esc_attr( $media_description_tag ) . ' class="dst-media__caption">' . wpautop( wp_kses_post( $args['mediaDescription']['text'] ) ) . '</' . esc_attr( $media_description_tag ) . '>';
}

// Get inline styles.
$inline_styles = Ds_Media_Helpers::get_styles_inline( $args );

?>

<div class="dst-media">
	<<?php echo esc_html( $container_tag ); ?> 
		class="<?php echo esc_attr( implode( ' ', $container_class ) ); ?>"
		<?php echo $inline_styles ? 'style="' . esc_attr( $inline_styles ) . '"' : ''; ?>
		<?php
		if ( ! empty( $container_data_attributes ) ) {
			foreach ( $container_data_attributes as $key => $value ) {
				printf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
			}
		}
		?>
	>
	<?php
	/**
	 * Primary image.
	 */
	if (
		'image' === $primary_type &&
		isset( $args['imagePrimary']['id'] ) &&
		! empty( $args['imagePrimary']['id'] ) &&
		! empty( $args['imagePrimary']['sizes'] )
	) :
		get_template_part(
			'templates/components/pictures/picture',
			null,
			Ds_Media_Helpers::convert_image_data( $args )
		);
		echo $media_description; // phpcs:ignore
		/**
		 * Primary local video file.
		 */
		elseif (
			'videoLocal' === $primary_type &&
			(int) $args['videoLocal']['id']
		) :
			?>
			<div class="c-video">
				<?php
					get_template_part(
						'templates/components/videos/video',
						null,
						Ds_Media_Helpers::convert_local_video_data( $args )
					);
				?>
			</div>
			<?php echo $media_description; // phpcs:ignore ?>
			<?php
			/**
			 * Primary external video file.
			 */
			elseif (
				'videoExternal' === $primary_type &&
				$args['videoExternal']['html']
			) :
				?>
			<div class="c-video">
				<?php
					get_template_part(
						'templates/components/videos/video',
						null,
						Ds_Media_Helpers::convert_external_video_data( $args )
					);
				?>
			</div>
			<?php echo $media_description; // phpcs:ignore ?>
	<?php endif; ?>
	</<?php echo esc_html( $container_tag ); ?>>
	<?php if ( $show_secondary && $args['imageSecondary'] && isset( $args['imageSecondary']['id'] ) ) : ?>
		<figure class="dst-media__secondary">
			<?php echo ds_generate_image( $args['imageSecondary']['id'], $args['imageSecondary']['size'], 'dst-media__src', '', $args['lazyLoad'] ); // phpcs:ignore ?>
		</figure>
	<?php endif; ?>
</div>