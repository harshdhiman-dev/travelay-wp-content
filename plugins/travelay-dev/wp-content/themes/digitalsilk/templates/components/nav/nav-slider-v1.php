<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'title' => '',
		'icon'  => array(),
		'class' => '',
	)
);

$className = '';
if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}
?>
<div class="slider-nav__item<?php echo esc_attr( $className ); ?>">
	<?php if ( ! empty( $args['icon']['ID'] ) ) : ?>
		<span class="slider-nav__icon">
			<?php echo ds_generate_image( $args['icon']['ID'], 'ds_small', 'slider-nav__src' ); ?>
		</span>
	<?php endif; ?>
	<?php if ( ! empty( $args['title'] ) ) : ?>
		<span class="slider-nav__label"><?php echo wp_kses_post( $args['title'] ); ?></span>
	<?php endif; ?>
</div>
