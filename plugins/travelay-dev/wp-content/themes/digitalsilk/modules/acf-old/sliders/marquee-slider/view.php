<?php
// phpcs:ignoreFile
/**
 * CSS Slider Images Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */

$args          = array(
	'layout'       => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'is_greyscale' => get_field( 'component_settings_is_greyscale' ) ?: false,
	'is_vertical'  => get_field( 'component_settings_is_vertical' ) ?: false,
	'is_fit'       => get_field( 'component_settings_is_fit' ) ?: false,
	'is_absolute'  => get_field( 'component_settings_is_absolute' ) ?: false,
	'is_autoplay'  => get_field( 'module_slider_settings_data_autoplay' ) ?: false,
);
$autoplay_attr = '';
if ( ! empty( $args['is_autoplay'] ) ) {
	$autoplay_attr = ' data-viewport=true data-viewport-repeat=true';
}
$containerStyles = '';
if ( ! empty( $moduleSlider->get_setting( 'interval' ) ) ) {
	$containerStyles .= '--dst--marquee-interval: ' . esc_attr( $moduleSlider->get_setting( 'interval' ) ) . 's;';
}
if ( ! empty( $moduleSlider->get_setting( 'gap' ) ) ) {
	$containerStyles .= '--dst--marquee-gap: ' . esc_attr( $moduleSlider->get_setting( 'gap' ) ) . 'px;';
}
if ( ! empty( $moduleSlider->get_setting( 'max_height' ) ) ) {
	$containerStyles .= '--dst--marquee-max-height: ' . esc_attr( $moduleSlider->get_setting( 'max_height' ) ) . 'px;';
}
if ( ! empty( $moduleSlider->get_setting( 'max_height' ) ) ) {
	$containerStyles .= '--dst--marquee-max-width: ' . esc_attr( $moduleSlider->get_setting( 'max_width' ) ) . 'px;';
}
?>
<div
	class="m-marquee<?php echo esc_attr( $block['className'] ); ?> <?php echo esc_attr( $moduleConfig->container ); ?> <?php echo esc_attr( $moduleSlider->classNames ); ?>" <?php echo esc_attr( $moduleConfig->data_attributes ); ?> <?php echo $moduleConfig->get_styles(); ?>>
	<?php
	$className = '';
	if ( $args['is_fit'] ) {
		$className .= ' is-fit-content';
	}
	if ( $args['is_absolute'] ) {
		$className .= ' is-absolute';
	}
	if ( $args['is_vertical'] ) {
		$className .= ' is-vertical';
	}
	if ( $args['is_greyscale'] ) {
		$className .= ' is-greyscale';
	}
	?>
	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => esc_attr( $block['anchor'] ?? '' ) ) ); ?>
	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>
	<div class="m-marquee__container<?php echo esc_attr( $className ); ?>" <?php echo $moduleSlider->data_attributes; ?><?php echo $autoplay_attr; ?> style="<?php echo esc_attr( $containerStyles ); ?>">
		<div class="m-marquee__wrapper">
			<?php if ( have_rows( 'content_images' ) ) : ?>
				<?php
				while ( have_rows( 'content_images' ) ) :
					the_row();
					?>
					<?php $image = get_sub_field( 'image' ); ?>
					<div class="m-marquee__slide">
						<?php get_template_part( 'templates/components/pictures/picture', null, array( 'image' => $image ) ); ?>
					</div>
				<?php endwhile; ?>
			<?php endif; ?>
		</div>
		<div class="m-marquee__wrapper" aria-hidden="true">
			<?php if ( have_rows( 'content_images' ) ) : ?>
				<?php
				while ( have_rows( 'content_images' ) ) :
					the_row();
					?>
					<?php $image = get_sub_field( 'image' ); ?>
					<div class="m-marquee__slide">
						<?php get_template_part( 'templates/components/pictures/picture', null, array( 'image' => $image ) ); ?>
					</div>
				<?php endwhile; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
