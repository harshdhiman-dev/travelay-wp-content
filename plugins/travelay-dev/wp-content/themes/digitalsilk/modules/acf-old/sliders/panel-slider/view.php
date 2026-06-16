<?php
/**
 * Slider Panel Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */

// phpcs:ignoreFile

$args = array(
	'list'   => get_field( 'content_list' ),
	'layout' => get_field( 'layout_settings_layout_type' ) ?: 'v1',
);
?>
<div class="m-slider slider-panel <?php echo $moduleSlider->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

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

				<?php if ( ! empty( $args['list'] ) ) : ?>

					<?php foreach ( $args['list'] as $key => $item ) : ?>
						<div class="m-slider__slide swiper-slide">
							<div class="m-slide">
								<div class="l-dcbl <?php echo "l-dcbl-{$args['layout']}"; ?>">
									<?php get_template_part( 'templates/components-shared/blocks/block-v2', null, $item ); ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

				<?php endif; ?>

			</div>

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
						'arrow_type' => $moduleSlider->get_setting( 'arrow_type' ),
					)
				);
				?>
			<?php endif; ?>
		</div>

	</div>

</div>
