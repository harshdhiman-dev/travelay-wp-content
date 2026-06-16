<?php
$styles = get_field( 'footer_content_styles', 'options' );

if ( empty( $styles['video_background'] ) && empty( $styles['background-image'] ) ) {
	return;
}

?>

<div class="l-footer__media <?php echo ( 'hide' === $styles['mobile_video'] ) ? 'hide-mobile-video' : ''; ?>">
	<?php
	if (
		( empty( $styles['video_background'] ) && ! empty( $styles['background-image'] ) )
		||
		( wp_is_mobile() && 'hide' === $styles['mobile_video'] && ! empty( $styles['background-image'] ) )
	) :
		?>

		<picture class="l-footer__picture">
			<source media="(min-width: 767px)" srcset="<?php echo esc_url( ds_generate_image_url( $styles['background-image']['ID'] ) ); ?>">

			<?php if ( ! empty( $styles['background-image-mobile']['ID'] ) ) : ?>
				<source media="(min-width: 300px)" srcset="<?php echo esc_url( ds_generate_image_url( $styles['background-image-mobile']['ID'], 'medium_large' ) ); ?>">
			<?php endif; ?>

			<?php echo ds_generate_image( $styles['background-image']['ID'] ); ?>
		</picture>

	<?php elseif ( ! empty( $styles['video_background'] ) ) : ?>
		<div class="l-footer__video">
			<?php
			get_template_part(
				'templates/components/videos/video-box',
				null,
				array(
					'video'         => $styles['video_background'],
					'poster_image'  => array( 'url' => $styles['background-image']['url'] ?? '' ),
					'hide_controls' => true,
					'autoplay'      => true,
				)
			);
			?>
		</div>
	<?php endif; ?>
</div>
