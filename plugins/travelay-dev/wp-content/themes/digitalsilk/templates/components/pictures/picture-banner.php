<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'image'                      => get_field( 'image' ),
		'mobile_image'               => get_field( 'mobile_image' ),
		'class'                      => '',
		'media_srcset'               => [
			'1280px' => 'default',
			'768px'  => 'large',
		],
		'mobile_image_fallback_size' => 'medium_large',
		'image_fit'                  => '',
		'image_position'             => '',
		'image_size'                 => 'full',
		'disable_lazy'               => false,
	)
);

$className = '';
if ( ! empty( $args['image_position'] ) ) {
	$className .= " {$args['image_position']}";
}

if ( ! empty( $args['image_fit'] ) ) {
	$className .= " {$args['image_fit']}";
}

$pictureClassName = '';
if ( ! empty( $args['class'] ) ) {
	$pictureClassName .= " {$args['class']}";
}
?>
<?php if ( ! empty( $args['image'] ) ) : ?>
	<div class="m-banner__media<?php echo esc_attr( $className ); ?>">
		<picture class="m-banner__picture<?php echo esc_attr( $pictureClassName ); ?>">
			<?php if ( ! empty( $args['media_srcset'] ) && is_array( $args['media_srcset'] ) ) : ?>

				<?php foreach ( $args['media_srcset'] as $media_width => $size ) : ?>

					<?php if ( 'default' === $size ) : ?>
						<source media="(min-width: <?php echo esc_attr( $media_width ); ?>)" srcset="<?php echo esc_url( $args['image']['sizes'][ $args['image_size'] ] ?? $args['image']['url'] ); ?>">
					<?php elseif ( isset( $args['image']['sizes'][ $size ] ) ) : ?>
						<source media="(min-width: <?php echo esc_attr( $media_width ); ?>)" srcset="<?php echo esc_url( $args['image']['sizes'][ $size ] ); ?>">
					<?php endif; ?>

				<?php endforeach; ?>

			<?php endif; ?>

			<?php if ( ! empty( $args['mobile_image'] ) ) : ?>
				<source media="(min-width: 300px)" srcset="<?php echo esc_url( $args['mobile_image']['sizes'][ $args['mobile_image_fallback_size'] ] ?? $args['mobile_image']['url'] ); ?>">
			<?php elseif ( isset( $args['image']['sizes'][ $args['mobile_image_fallback_size'] ] ) ) : ?>
				<source media="(min-width: 300px)" srcset="<?php echo esc_url( $args['image']['sizes'][ $args['mobile_image_fallback_size'] ] ); ?>">
			<?php endif; ?>

			<?php echo ds_generate_image( $args['image']['ID'], $args['image_size'], 'c-media__src', '', ! $args['disable_lazy'] ); ?>

		</picture>
	</div>
<?php endif; ?>
