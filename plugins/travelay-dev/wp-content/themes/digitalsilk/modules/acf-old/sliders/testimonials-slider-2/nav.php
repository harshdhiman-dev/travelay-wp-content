<?php
// phpcs:ignoreFile

/**
 * @var array $args
 */
$args = wp_parse_args(
	$args,
	array(
		'testimonials'     => get_field( 'testimonials' ),
		'is_rounded_image' => get_field( 'tabbed_navigation_component_settings_is_image_rounded' ),
		'layout'           => get_field( 'layout_settings_nav_layout_type' ) ?: 'v1',
		'is_slider_thumbs' => false,
	)
);
?>

<?php if ( ! empty( $args['testimonials'] ) ) : ?>
	<div class="slider-nav <?php echo "slider-nav-{$args['layout']}"; ?>">
		<?php
		foreach ( $args['testimonials'] as $key => $post ) :
			setup_postdata( $post );
			?>
			<?php
			get_template_part(
				'templates/components-shared/testimonials/thumbs-nav',
				null,
				array(
					'class'            => 'js-nav__item',
					'is_rounded_image' => $args['is_rounded_image'],
					'is_slider_thumbs' => $args['is_slider_thumbs'],
				)
			);
			?>
			<?php
		endforeach;
		wp_reset_postdata();
		?>
	</div>
	<?php
endif;
