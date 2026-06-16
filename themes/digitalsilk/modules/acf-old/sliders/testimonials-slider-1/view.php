<?php
/**
 * Slider Testimonials 1 Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */
// phpcs:ignoreFile

$args = array(
	'testimonials'         => get_field( 'testimonials' ),
	'layout'               => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'position'             => get_field( 'content_position' ) ?: 'left',
	'screen_height'        => get_field( 'layout_settings_screen_height' ) ?: 'small',
	'custom_height'        => get_field( 'layout_settings_custom_height' ),
	'text_position'        => get_field( 'text_position' ) ?: 'left',
	'popup_video'          => get_field( 'play_video_in_popup' ),
	'play_video_text'      => get_field( 'video_play_text' ) ?: 'Play Video',
	'component_background' => get_field( 'component_background' ),
	'data_vertical'        => get_field( 'data_vertical' ) ?: 'horizontal',
	'quote_image'          => get_field( 'quote_image' ) ?: 'media-show',
	'show_avatar'          => get_field( 'show_avatar' ),
	'intro_title'          => get_field( 'testimonial_intro_title' ) ?: '',
);
?>
<div
	class="m-slider slider-testimonials<?php echo esc_attr( $block['className'] ); ?>  <?php echo $moduleSlider->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<div class="m-slider__outer <?php echo $moduleConfig->container; ?>">

		<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

		<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

		<?php get_template_part( 'templates/components/nav/slider-filter-tabs' ); ?>

		<div class="m-slider__container swiper js-slider-simple
		<?php
		if ( get_field( 'post_type_data_filter_tabs' ) == 'enable' ) {
			echo 'slider-filter-tabs';
		}
		?>
		" <?php echo $moduleSlider->data_attributes; ?>>
			<div class="m-slider__wrapper swiper-wrapper">
				<?php
				$styles = '';
				if ( $args['screen_height'] == 'custom' && ! empty( $args['custom_height']['height'] ) ) {
					$styles .= "--moduleHeight: {$args['custom_height']['height']}{$args['custom_height']['unit']};";
				}
				?>

				<?php if ( ! empty( $args['testimonials'] ) ) : ?>
					<?php
					foreach ( $args['testimonials'] as $post ) :
						setup_postdata( $post );
						$testimonial_image = get_field( 'testimonial_main_image', get_the_ID() );
						$full_story_type   = get_field( 'full_story_type', get_the_ID() );
						$full_story        = get_field( 'full_story', get_the_ID() );
						?>
						<div class="m-slider__slide swiper-slide" style="<?php echo $styles; ?>">
							<div
								class="c-block l-testimonials <?php echo "-{$args['layout']}"; ?> direction-<?php echo "{$args['data_vertical']}"; ?> <?php echo "{$args['quote_image']}"; ?>"
								style="--c-block__bg: <?php echo "{$args['component_background']}"; ?>">
								<div class="c-block__media">

									<?php if ( $full_story_type === 'content' ) : ?>
										<?php get_template_part( 'templates/components/images/image-v1', null, array( 'image' => $testimonial_image ) ); ?>
									<?php elseif ( $full_story_type === 'video' && $args['popup_video'] ) : ?>

										<?php
										$video_url = '';
										if ( $full_story['video']['popup_video_type'] === 'file' ) {
											$video_url = $full_story['video']['popup_video_file']['url'] ?? '';
										} else {
											$iframe = wp_oembed_get( $full_story['video']['popup_video_url'] );
											// Use preg_match to find iframe src.
											preg_match( '/src="(.+?)"/', $iframe, $matches );

											$video_url = ! empty( $matches ) ? $matches[1] : $full_story['video']['popup_video_url'];
										}
										?>

										<?php get_template_part( 'templates/components/images/image-v1', null, array( 'image' => $testimonial_image ) ); ?>

										<?php
										get_template_part(
											'templates/components/videos/controls',
											null,
											array(
												'label' => $args['play_video_text'],
												'url'   => $video_url,
											)
										);
										?>

									<?php elseif ( is_array( $full_story ) ) : ?>

										<?php if ( $full_story['video']['popup_video_type'] === 'file' ) : ?>
											<?php
											get_template_part(
												'templates/components/videos/video-box',
												null,
												array(
													'video'            => $full_story['video']['popup_video_file'] ?? array(),
													'poster_image'     => $testimonial_image['url'] ?? '',
													'show_js_controls' => true,
												)
											);
											?>
										<?php else : ?>

											<?php
											get_template_part(
												'templates/components/videos/video-embed',
												null,
												array(
													'iframe' => $full_story['video']['popup_video_url'] ? wp_oembed_get( $full_story['video']['popup_video_url'] ) : '',
													'class'  => '',
												)
											);
											?>
										<?php endif; ?>

									<?php endif; ?>
								</div>
								<div
									class="c-block__text <?php echo "flex-{$args['position']}"; ?> <?php echo "text-{$args['text_position']}"; ?>">
									<?php get_template_part( 'templates/components-shared/testimonials/testimonial-v1', null, array( 'show_avatar' => $args['show_avatar'], 'intro_title' => $args['intro_title'] ) ); ?>
								</div>
							</div>
						</div>
					<?php
					endforeach;
					wp_reset_postdata();
				endif;
				?>
			</div>
		</div>

		<div class="m-slider__controls">
			<?php if ( strpos( $moduleSlider->get_setting( 'data_navigation' ), 'arrows' ) !== false ) : ?>
				<?php
				get_template_part(
					'templates/components/slider/arrows',
					null,
					array(
						'arrow_type' => $moduleSlider->get_setting( 'arrow_type' ),
					)
				);
				?>
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

	</div>

</div>
