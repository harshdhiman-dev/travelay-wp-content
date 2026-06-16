<?php
/**
 * Slider Images Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */
// phpcs:ignoreFile

$args = array(
	'layout'       => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'is_greyscale' => get_field( 'component_settings_has_greyscale' ) ?: false,
);
?>
<div class="m-slider slider-logos <?php echo esc_attr( $block['className'] ); ?> <?php echo $moduleSlider->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

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
				$className = '';

				if ( $args['is_greyscale'] ) {
					$className .= ' is-greyscale';
				}
				?>
				<?php if ( have_rows( 'content_images' ) ) : ?>
					<?php
					while ( have_rows( 'content_images' ) ) :
						the_row();
						$image = get_sub_field( 'image' );
						?>
						<div class="m-slider__slide swiper-slide<?php echo esc_attr( $className ); ?>">
							<div class="m-slide">

								<?php
								if ( get_sub_field( 'has_link_or_popup' ) ) :
									$type = get_sub_field( 'type' );
									$link = get_sub_field( 'link' );
									?>

									<?php if ( $type === 'link' && ! empty( $link ) ) : ?>

										<a href="<?php echo esc_url( $link['url'] ); ?>" class="m-slide__link" title="<?php echo esc_attr( $link['title'] ); ?>" <?php echo $link['target'] ? 'target="' . esc_attr( $link['target'] ) . '"' : ''; ?>></a>

									<?php elseif ( $type === 'popup' && ! empty( $image ) ) : ?>

										<?php
										get_template_part(
											'templates/components/popups/popup-link',
											null,
											array(
												'popup_link' => $image['url'],
												'popup_type' => 'modern',
												'class' => 'm-slide__link',
											)
										);
										?>

									<?php endif; ?>

								<?php endif; ?>

								<?php get_template_part( 'templates/components/pictures/picture-slider', null, array( 'image' => $image ) ); ?>

							</div>
						</div>
					<?php endwhile; ?>
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
