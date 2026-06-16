<?php
/**
 * Slider Double Cards Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */
// phpcs:ignoreFile

$args = array(
	'layout' => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'gap'    => get_field( 'layout_settings_card_gap' ) ?: 0,
);
?>
<div class="m-slider slider-dsbls <?php echo esc_attr( $block['className'] ); ?> <?php echo $moduleConfig->container; ?> <?php echo $moduleSlider->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-slider__container swiper js-slider-dsbls" <?php echo $moduleSlider->data_attributes; ?>>
		<div class="m-slider__wrapper swiper-wrapper">
			<?php if ( have_rows( 'cards_widget' ) ) : ?>
				<?php
				while ( have_rows( 'cards_widget' ) ) :
					the_row();
					?>
					<div class="m-slider__slide swiper-slide">
						<figure class="m-slide">
							<?php get_template_part( 'templates/components/pictures/picture-slider' ); ?>
						</figure>
					</div>
				<?php endwhile; ?>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $moduleSlider->get_setting( 'tabbed_navigation' ) ) : ?>
		<div class="l-slider-nav l-slider-nav__container">
			<div class="l-slider-nav__inner swiper js-slider-dsbls-m" <?php echo $moduleSlider->data_attributes; ?>>

				<?php get_template_part( 'modules/acf-old/sliders/double-cards/nav', null, array( 'columns' => $moduleSlider->get_setting( 'data_columns' ) ) ); ?>

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
					<div class="l-slider-nav__pagination swiper-pagination"></div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

</div>
