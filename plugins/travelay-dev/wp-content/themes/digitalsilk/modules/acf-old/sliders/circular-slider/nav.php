<?php
// phpcs:ignoreFile

/**
 * @var array $args
 */
$args = wp_parse_args(
	$args,
	array(
		'list'                          => get_field( 'content_slider' ) ?: array(),
		'navigation_inner_circle_image' => get_field( 'navigation_inner_circle_image' ),
		'arrange_items'                 => get_field( 'module_slider_nav_settings_data_circular-arrange' ) ?: false,
		'layout'                        => get_field( 'slider_navigation_component_settings_nav_layout_type' ) ?: 'v1',
		'is_slider_thumbs'              => false,
	)
);

$dataAttributes = '';
$activeKeyIndex = 0;
if ( ! empty( $args['list'] ) && $args['arrange_items'] === 'center' ) {
	$activeKeyIndex  = intval( ( ( count( $args['list'] ) - 1 ) / 2 ) );
	$dataAttributes .= ' data-initial-index="' . ( $activeKeyIndex ) . '"';
}

?>
<?php if ( ! empty( $args['list'] ) ) : ?>
	<?php if ( ! empty( $args['navigation_inner_circle_image'] ) ) : ?>
		<?php
		get_template_part(
			'templates/components/images/image-v1',
			null,
			array(
				'image' => $args['navigation_inner_circle_image'],
				'class' => 'c-slider-circle__image',
			)
		);
		?>
	<?php endif; ?>

	<div class="slider-nav <?php echo "slider-nav-{$args['layout']}"; ?>" <?php echo $dataAttributes; ?>>
		<?php foreach ( $args['list'] as $key => $item ) : ?>
			<?php
			$class = 'js-nav__item';
			if ( $args['is_slider_thumbs'] ) {
				$class .= ' swiper-slide';
			}

			if ( $key === $activeKeyIndex ) {
				$class .= ' is-active';
			}
			?>
			<?php
			get_template_part(
				'templates/components/nav/nav-slider-v1',
				null,
				array(
					'title' => $item['slider_navigation_text'] ?? '',
					'icon'  => $item['slider_navigation_icon'] ?? '',
					'class' => $class,
				)
			);
			?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
