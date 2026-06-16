<?php
// phpcs:ignoreFile

/**
 * @var array $args
 */
$args = wp_parse_args(
	$args,
	array(
		'list'             => get_field( 'content_slider' ) ?: array(),
		'layout'           => get_field( 'slider_navigation_component_settings_nav_layout_type' ) ?: 'v1',
		'gap'              => get_field( 'layout_settings_card_gap' ) ?: 0,
		'text_position'    => get_field( 'slider_navigation_component_settings_horizontal_alignment' ) ?: 'center',
		'has_video'        => get_field( 'has_video_in_navigation' ) ?: false,
		'is_slider_thumbs' => false,

	)
);
?>
<?php if ( ! empty( $args['list'] ) ) : ?>
	<div class="slider-nav <?php echo "slider-nav-{$args['layout']}"; ?> <?php echo "text-{$args['text_position']}"; ?>">
		<?php foreach ( $args['list'] as $key => $item ) : ?>
			<?php
			$class = 'js-nav__item';
			if ( $args['is_slider_thumbs'] ) {
				$class .= ' swiper-slide';
			}

			if ( $key === 0 ) {
				$class .= ' is-active';
			}
			?>
			<?php
			get_template_part(
				'templates/components-shared/nav/nav-banner',
				null,
				array(
					'counter'   => $key + 1,
					'label'     => $item['slider_navigation_text'],
					'icon'      => $item['slider_navigation_icon'],
					'has_video' => $args['has_video'],
					'video'     => $item['slider_navigation_video'],
					'class'     => $class,
				)
			);
			?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
