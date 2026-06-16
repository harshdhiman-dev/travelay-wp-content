<?php
/**
 * Slider Extended Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */
// phpcs:ignoreFile

$args = array(
	'list'                     => get_field( 'content_slider' ),
	'screen_height'            => get_field( 'layout_settings_screen_height' ) ?: 'small',
	'custom_height'            => get_field( 'layout_settings_custom_height' ),
	'layout'                   => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'layout_gap_top'           => get_field( 'layout_settings_top_layout_gap' ) ?: false,
	'layout_gap_top_custom'    => get_field( 'layout_settings_top_layout_custom_gap' ) ?: false,
	'layout_gap_bottom'        => get_field( 'layout_settings_bottom_layout_gap' ) ?: false,
	'layout_gap_bottom_custom' => get_field( 'layout_settings_bottom_layout_custom_gap' ) ?: false,
);
?>
<div class="m-slider slider-banner-tabs<?php echo esc_attr( $block['className'] ); ?> <?php echo $moduleConfig->container; ?> <?php echo $moduleSlider->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-slider__container swiper js-slider--with-nav js-slider-extended" <?php echo $moduleSlider->data_attributes; ?>>
		<div class="m-slider__wrapper swiper-wrapper">
			<?php
			$styles = '';
			if ( $args['screen_height'] == 'custom' && ! empty( $args['custom_height']['height'] ) ) {
				$styles .= "--bannerHeight: {$args['custom_height']['height']}{$args['custom_height']['unit']};";
			}

			$layoutClassName = '';
			$layoutStyles    = '';
			if ( ! empty( $args['layout_gap_top'] ) ) {
				$layoutClassName .= " {$args['layout_gap_top']}";

				if ( $args['layout_gap_top'] === 'space-top-custom' && ! empty( $args['layout_gap_top_custom'] ) ) {
					$layoutStyles .= "--padding-top: {$args['layout_gap_top_custom']};";
				}
			}

			if ( ! empty( $args['layout_gap_bottom'] ) ) {
				$layoutClassName .= " {$args['layout_gap_bottom']}";

				if ( $args['layout_gap_bottom'] === 'space-bottom-custom' && ! empty( $args['layout_gap_bottom_custom'] ) ) {
					$layoutStyles .= "--padding-bottom: {$args['layout_gap_bottom_custom']};";
				}
			}
			?>
			<?php if ( ! empty( $args['list'] ) ) : ?>
				<?php
				foreach ( $args['list'] as $key => $item ) :
					$item['loop_index_key'] = $key;
					$slide_styles = '';
					$slide_classes = '';
					if ( intval( $item['overlay_opacity'] ) !== 0 ) {
						$slide_classes .= ' has-overlay';
						$slide_styles  .= "--overlayOpacity: {$item['overlay_opacity']}%;";
					}

					?>
					<div class="m-slider__slide swiper-slide">

						<div class="m-banner m-banner--<?php echo $args['screen_height']; ?><?php echo $slide_classes; ?>" style="<?php echo $styles; ?><?php echo $slide_styles; ?>">

							<?php if ( $item['media_background_type'] === 'image' ) : ?>
								<?php
								get_template_part(
									'templates/components/pictures/picture-banner',
									null,
									array(
										'image'          => $item['media_image'],
										'mobile_image'   => $item['media_mobile_image'],
										'image_position' => $item['media_image_position'],
										'disable_lazy'   => $item['disable_lazy'] ?? false,
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
												'video'            => $item['media_video']['video'],
												'poster_image'     => $item['media_video']['poster_image'],
												'show_js_controls' => true,
												'hide_controls'    => $item['media_video']['hide_controls'] ?? false,
												'autoplay'         => $item['media_video']['autoplay'] ?? false,
												'disable_lazy'     => $item['disable_lazy'] ?? false,
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
										'iframe'       => $item['media_video']['video_embed'],
										'class'        => 'm-banner__media',
										'disable_lazy' => $item['disable_lazy'] ?? false,
									)
								);
								?>
							<?php endif; ?>

						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<div class="m-slider__content container">

			<div class="l-slider-content">
				<div class="l-slider-content__wrapper">
					<?php if ( ! empty( $args['list'] ) ) : ?>
						<?php foreach ( $args['list'] as $key => $item ) : ?>
							<?php
							$class = 'js-content-item swiper-slide';
							?>
							<?php
							get_template_part(
								'templates/components-shared/blocks/block-v3',
								null,
								array(
									'pretitle'    => $item['pretitle'],
									'title'       => $item['title'],
									'description' => $item['description'],
									'cta_list'    => $item['cta_list'],
									'class'       => $class,
								)
							);
							?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="l-slider-nav">

				<div class="l-slider-nav__wrap">

					<?php if ( ! empty( $moduleSlider->get_setting( 'data_autoplay' )['data_enabled'] ) ) : ?>
						<?php get_template_part( 'templates/components/progress/slider-progress' ); ?>
					<?php endif; ?>

					<?php get_template_part( 'modules/acf-old/sliders/extended-banner-slider/nav', null, array() ); ?>

				</div>

			</div>

		</div>

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
