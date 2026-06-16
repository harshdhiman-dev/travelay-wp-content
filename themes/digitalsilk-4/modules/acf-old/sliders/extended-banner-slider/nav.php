<?php
// phpcs:ignoreFile

/**
 * @var array $args
 */
$args = wp_parse_args(
	$args,
	array(
		'list'   => get_field( 'content_slider' ) ?: array(),
		'layout' => get_field( 'slider_navigation_component_settings_nav_layout_type' ) ?: 'v1',

	)
);
?>
<?php if ( ! empty( $args['list'] ) ) : ?>

	<div class="slider-nav <?php echo "slider-nav-{$args['layout']}"; ?>">
		<?php foreach ( $args['list'] as $key => $item ) : ?>
			<?php
			$class = 'js-nav__item';

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
					'icon'      => false,
					'has_video' => false,
					'video'     => false,
					'class'     => $class,
				)
			);
			?>
		<?php endforeach; ?>
	</div>
<?php endif; ?>
