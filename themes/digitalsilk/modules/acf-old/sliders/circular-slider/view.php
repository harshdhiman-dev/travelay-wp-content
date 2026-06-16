<?php
/**
 * Slider Circular Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */

// phpcs:ignoreFile


$args = array(
	'list'          => get_field( 'content_slider' ),
	'text_position' => get_field( 'text_position' ) ?: 'left',
	'layout'        => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'position'      => get_field( 'content_position' ),
	'content_style' => get_field( 'content_style' ),
	'screen_height' => get_field( 'layout_settings_screen_height' ) ?: 'small',
	'custom_height' => get_field( 'layout_settings_custom_height' ),
);

?>
<div class="m-slider  slider-circular<?php echo esc_attr( $block['className'] ); ?> <?php echo $moduleConfig->container; ?> <?php echo $moduleSlider->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-slider__container swiper js-slider--with-nav js-circular-adv" <?php echo $moduleSlider->data_attributes; ?>>
		<div class="m-slider__wrapper swiper-wrapper">
			<?php

			$styles = '';
			if ( $args['screen_height'] == 'custom' && ! empty( $args['custom_height']['height'] ) ) {
				$styles .= "--bannerHeight: {$args['custom_height']['height']}{$args['custom_height']['unit']};";
			}
			?>

			<?php if ( ! empty( $args['list'] ) ) : ?>
				<?php foreach ( $args['list'] as $item ) : ?>
					<div class="m-slider__slide swiper-slide">

						<div class="m-banner m-banner--<?php echo $args['screen_height']; ?>" style="<?php echo $styles; ?>">

							<?php if ( $item['media_background_type'] === 'image' ) : ?>
								<?php
								get_template_part(
									'templates/components/pictures/picture-banner',
									null,
									array(
										'image'          => $item['media_image'],
										'mobile_image'   => $item['media_mobile_image'],
										'image_position' => $item['media_image_position'],
									)
								);
								?>
							<?php elseif ( $item['media_background_type'] === 'video' ) : ?>

								<?php if ( $item['media_video']['video_source'] === 'internal' ) : ?>
									<div class="m-banner__media">
										<?php
										get_template_part(
											'templates/components/videos/video-box',
											null,
											array(
												'video'    => $item['media_video']['video'],
												'poster_image' => $item['media_video']['poster_image'],
												'show_js_controls' => true,
												'hide_controls' => $item['media_video']['hide_controls'] ?? false,
												'autoplay' => $item['media_video']['autoplay'] ?? false,
											)
										);
										?>
									</div>
								<?php endif; ?>
								<?php
								get_template_part(
									'templates/components/videos/video-embed',
									null,
									array(
										'iframe' => $item['media_video']['video_embed'],
										'class'  => 'm-banner__media',
									)
								);
								?>
							<?php endif; ?>

							<div class="m-banner__container container">

								<div class="m-banner__inner <?php echo 'flex-' . $args['position']; ?> <?php echo $args['content_style']; ?>">

									<div class="l-circular <?php echo "l-circular-{$args['layout']}"; ?> <?php echo "text-{$args['text_position']}"; ?>">
										<?php get_template_part( 'templates/components/headings/heading', null, $item ); ?>
										<?php get_template_part( 'templates/components/cta-list', null, array( 'buttons' => $item['cta_list'] ) ); ?>
									</div>

								</div>

							</div>

						</div>
					</div>

					<?php
				endforeach;
				wp_reset_postdata();
			endif;
			?>
		</div>

		<?php if ( $moduleSlider->get_setting( 'tabbed_navigation' ) || $moduleSlider->get_setting( 'data_thumbs' ) ) : ?>
			<div class="l-slider-nav container <?php echo $moduleSlider->navClassNames; ?>" <?php echo $moduleSlider->navDataAttributes; ?>>
				<?php
				get_template_part(
					'modules/acf-old/sliders/circular-slider/nav',
					null,
					array(
						'is_slider_thumbs' => $moduleSlider->get_setting( 'data_thumbs' ) === 'thumbs' || $moduleSlider->get_setting( 'data_thumbs' ) === true,
					)
				);
				?>
			</div>
		<?php endif; ?>
		<?php
		if ( in_array(
			$moduleSlider->get_setting( 'data_pagination' ),
			array(
				'default',
				'progressbar',
				'fraction',
				'combo',
			)
		) ) :
			?>
			<div class="m-slider__pagination swiper-pagination"></div>
		<?php endif; ?>
	</div>

	<?php if ( strpos( $moduleSlider->get_setting( 'data_navigation' ), 'arrows' ) !== false ) : ?>
		<?php
		get_template_part(
			'templates/components/slider/arrows',
			null,
			array(
				'class'      => '',
				'arrow_type' => $moduleSlider->get_setting( 'arrow_type' ),
			)
		);
		?>
	<?php endif; ?>


</div>
