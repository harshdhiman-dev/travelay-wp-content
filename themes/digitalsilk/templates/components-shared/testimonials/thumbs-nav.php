<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'name'               => get_the_title(),
		'company_position'   => get_field( 'company_position', get_the_ID() ),
		'show_avatar'        => get_field( 'tabbed_navigation_component_settings_show_avatar' ) ?: false,
		'show_name_position' => get_field( 'tabbed_navigation_component_settings_show_name_position' ) ?: false,
		'is_rounded_image'   => get_field( 'tabbed_navigation_component_settings_is_image_rounded' ) ?: false,
		'is_slider_thumbs'   => false,
		'class'              => '',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}

if ( $args['is_slider_thumbs'] ) {
	$className .= ' swiper-slide';
}
?>
<?php if ( $args['show_avatar'] || $args['show_name_position'] ) : ?>
    <div class="l-testimonials__thumb <?php echo esc_attr( $className ); ?>">
		<?php if ( $args['show_avatar'] ) : ?>
			<?php
            get_template_part(
                'templates/components/testimonials/photo',
                null,
                array(
					'title'            => $args['name'],
					'is_rounded_image' => $args['is_rounded_image'],
                )
            );
            ?>
		<?php endif; ?>
		<?php if ( $args['show_name_position'] ) : ?>
			<?php
            get_template_part(
                'templates/components/testimonials/author',
                null,
                array(
					'class'            => '',
					'name'             => $args['name'],
					'company_position' => $args['company_position'],
                )
			);
            ?>
		<?php endif; ?>
    </div>
<?php endif; ?>
