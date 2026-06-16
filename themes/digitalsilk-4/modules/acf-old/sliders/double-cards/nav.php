<?php
// phpcs:ignoreFile

/**
 * @var array $args
 */
$args = wp_parse_args(
	$args,
	array(
		'list'    => get_field( 'cards_widget' ) ?: array(),
		'layout'  => get_field( 'layout_settings_layout_type' ) ?: 'v1',
		'gap'     => get_field( 'layout_settings_card_gap' ) ?: 0,
		'columns' => 1,
	)
);
?>
<?php if ( ! empty( $args['list'] ) ) : ?>
	<div class="swiper-wrapper <?php echo "l-slider-nav--{$args['columns']}-col"; ?> <?php echo "l-slider-nav-{$args['layout']}"; ?> <?php echo $args['gap'] > 0 ? $args['gap'] . '-gap' : ''; ?>">
		<?php
		foreach ( $args['list'] as $key => $item ) :
			$item['class'] = 'slider-nav__item js-dsbls-nav-item' . ( $key === 0 ? ' is-active' : '' );
			?>
			<?php get_template_part( 'templates/components-shared/blocks/block-dsbls', null, $item ); ?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
