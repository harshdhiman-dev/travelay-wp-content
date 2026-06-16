<?php get_template_part(
	'templates/components/cta-custom',
	null,
	array(
		'link'             => get_sub_field( 'link' ),
		'icon_position'    => get_sub_field( 'icon_position' ),
		'icon'             => get_sub_field( 'icon' ),
		'background_color' => get_sub_field( 'background_color' ),
		'text_color'       => get_sub_field( 'text_color' ),
	)
);
