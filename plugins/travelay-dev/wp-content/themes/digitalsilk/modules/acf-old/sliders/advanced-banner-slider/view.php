<?php
/**
 * Slider Advanced Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */
// phpcs:ignoreFile

$args = array(
	'list'                     => get_field( 'content_slider' ),
	'position'                 => get_field( 'content_position' ),
	'content_style'            => get_field( 'content_style' ),
	'screen_height'            => get_field( 'layout_settings_screen_height' ) ?: 'small',
	'custom_height'            => get_field( 'layout_settings_custom_height' ),
	'custom_height_mobile'     => get_field( 'layout_settings_custom_height_mobile' ),
	'layout'                   => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'layout_gap_top'           => get_field( 'layout_settings_top_layout_gap' ) ?: false,
	'layout_gap_top_custom'    => get_field( 'layout_settings_top_layout_custom_gap' ) ?: false,
	'layout_gap_bottom'        => get_field( 'layout_settings_bottom_layout_gap' ) ?: false,
	'layout_gap_bottom_custom' => get_field( 'layout_settings_bottom_layout_custom_gap' ) ?: false,
	'overlay_opacity_color'    => get_field( 'overlay_opacity_color' ) ?: false,
);

$additional_class = '';
if ( $moduleConfig->layout_settings_header_height !== false ) {
	$additional_class .= ' has-header-height';
}

?>
<div class="m-slider slider-banner <?php echo esc_attr( $block['className'] . $additional_class ); ?> <?php echo $moduleConfig->container; ?> <?php echo $moduleSlider->classNames; ?> " <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<div class="m-slider__outer">

		<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

		<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

		<div class="m-slider__container swiper js-slider--with-nav js-slider-advanced" <?php echo $moduleSlider->data_attributes; ?>>
			<div class="m-slider__wrapper swiper-wrapper">

				<?php
				$styles = '';
				if ( $args['screen_height'] == 'custom' && ! empty( $args['custom_height']['height'] ) ) {
					$styles .= "--bannerHeightDesktop: {$args['custom_height']['height']};";
				}

				if ( ! empty( $args['custom_height_mobile'] ) && is_array( $args['custom_height_mobile'] ) && $args['custom_height_mobile']['height'] ) {
					$styles .= "--bannerHeightMobile: {$args['custom_height_mobile']['height']};";
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
						if ( ! empty( $item['overlay_opacity_color'] ) ) {
							$slide_styles .= "--overlayOpacityColor: {$item['overlay_opacity_color']};";
						}
						$columns_ratio = (int) $item['columns_ratio'] !== 0 ? $item['columns_ratio'] : 75;
						$slide_styles  .= "--columns-ratio: {$columns_ratio}%";

						$columns_order = ! empty( $item['columns_order'] ) ? $item['columns_order'] : 'default';
						$slide_classes .= " order-{$columns_order}";

						$slide_classes .= ! empty( $item['vertical_columns'] ) ? ' is-vertical' : '';
						?>
						<div class="m-slider__slide swiper-slide">

							<div class="m-banner m-banner--<?php echo $args['screen_height']; ?><?php echo $slide_classes; ?>" style="<?php echo $styles; ?><?php echo $slide_styles; ?>">

								<div class="m-banner__container">

									<?php if ( $item['media_background_type'] === 'image' ) : ?>
										<?php
										get_template_part(
											'templates/components/pictures/picture-banner',
											null,
											array(
												'image'          => $item['media_image'],
												'mobile_image'   => $item['media_mobile_image'],
												'image_position' => $item['media_image_position'],
												'image_fit'      => $item['media_image_fit'],
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

									<div class="m-banner__inner container <?php echo 'flex-' . $args['position']; ?> <?php echo $args['content_style']; ?>">
										<div class="l-banner <?php echo "l-banner-{$args['layout']}"; ?><?php echo $layoutClassName; ?>" style="<?php echo $layoutStyles; ?>">
											<?php get_template_part( 'templates/components-shared/blocks/block-banner-inner', null, $item ); ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

			</div>

			<?php if ( $moduleSlider->get_setting( 'tabbed_navigation' ) || $moduleSlider->get_setting( 'data_thumbs' ) && ! $moduleSlider->get_setting( 'data_vertical' ) ) : ?>
                <div class="l-slider-nav container" <?php if ( !empty($nav_styles)): ?>style="<?php echo $nav_styles ?>"<?php endif; ?>>
					<?php
					get_template_part(
						'modules/acf-old/sliders/advanced-banner-slider/nav',
						null,
						array(
							'is_slider_thumbs' => $moduleSlider->get_setting( 'data_thumbs' ) === 'thumbs' || $moduleSlider->get_setting( 'data_thumbs' ) === true,
						)
					);
					?>
                </div>
			<?php endif; ?>

		</div>

		<?php if ( $moduleSlider->get_setting( 'tabbed_navigation' ) || $moduleSlider->get_setting( 'data_thumbs' ) && $moduleSlider->get_setting( 'data_vertical' ) ) : ?>
			<div class="l-slider-nav container">
				<?php
				get_template_part(
					'modules/acf-old/sliders/advanced-banner-slider/nav',
					null,
					array(
						'is_slider_thumbs' => $moduleSlider->get_setting( 'data_thumbs' ) === 'thumbs' || $moduleSlider->get_setting( 'data_thumbs' ) === true,
					)
				);
				?>
			</div>
		<?php endif; ?>

		<div class="m-slider__controls">
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

	</div>

</div>
