<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'button_styles' => get_field( 'cta_button_styles' ),
		'button'        => get_field( 'cta_button' ),
	)
);

if ( ! empty( $args['button'] ) && ! empty( $args['button']['title'] ) && ! empty( $args['button']['url'] ) ) :
	$link_size            = $args['button_styles']['size'] ?? '-normal';
	$link_style           = $args['button_styles']['style'] ?? '-primary';
	$icon_settings        = get_button_icon_settings( $args['button'] );
	$button_args['class'] = "c-btn {$link_size} {$link_style}";
	$button               = array(
		'url'    => $args['button']['url'],
		'title'  => $args['button']['title'],
		'target' => $args['button']['target'] ?? '',
	); ?>

	<?php echo acf_button( $button, $button_args, $icon_settings['icon'], $icon_settings['icon_args'], [] ); //phpcs:ignore?>

	<?php
endif;
