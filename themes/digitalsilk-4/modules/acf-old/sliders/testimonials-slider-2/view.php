<?php
/**
 * Slider Testimonials 2 Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */
// phpcs:ignoreFile

$args = array(
	'testimonials' => get_field( 'testimonials' ),
	'layout'       => get_field( 'layout_settings_layout_type' ) ?: 'v1',
);

?>
<div class="m-slider slider-testimonials <?php echo esc_attr( $block['className'] ); ?> <?php echo $moduleSlider->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?>>

	<div class="m-slider__outer <?php echo $moduleConfig->container; ?>">

		<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

		<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

		<div class="m-slider__container swiper js-slider--with-nav js-slider-advanced" <?php echo $moduleSlider->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>
			<div class="m-slider__wrapper swiper-wrapper">
				<?php if ( ! empty( $args['testimonials'] ) ) : ?>
					<?php
					foreach ( $args['testimonials'] as $post ) :
						setup_postdata( $post );
						?>
						<div class="m-slider__slide swiper-slide <?php echo "-{$args['layout']}"; ?>">
							<?php get_template_part( 'templates/components-shared/testimonials/testimonial-v2' ); ?>
						</div>
						<?php
					endforeach;
					wp_reset_postdata();
				endif;
				?>
			</div>

			<?php if ( $moduleSlider->get_setting( 'tabbed_navigation' ) || $moduleSlider->get_setting( 'data_thumbs' ) && ! $moduleSlider->get_setting( 'data_vertical' ) ) : ?>
				<div class="l-slider-nav container">
					<?php
					get_template_part(
						'modules/acf-old/sliders/testimonials-slider-2/nav',
						null,
						array(
							'is_slider_thumbs' => $moduleSlider->get_setting( 'data_thumbs' ) === 'thumbs' || $moduleSlider->get_setting( 'data_thumbs' ) === true,
						)
					);
					?>
				</div>
			<?php endif; ?>

		</div>

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

		<?php if ( $moduleSlider->get_setting( 'tabbed_navigation' ) || $moduleSlider->get_setting( 'data_thumbs' ) && $moduleSlider->get_setting( 'data_vertical' ) ) : ?>
			<div class="l-slider-nav container">
				<?php
				get_template_part(
					'modules/acf-old/sliders/testimonials-slider-2/nav',
					null,
					array(
						'is_slider_thumbs' => $moduleSlider->get_setting( 'data_thumbs' ) === 'thumbs' || $moduleSlider->get_setting( 'data_thumbs' ) === true,
					)
				);
				?>
			</div>
		<?php endif; ?>

	</div>

</div>
