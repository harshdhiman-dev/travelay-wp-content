<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'iframe'     => get_field( 'main_video_video_embed' ),
		'params'     => array(
			'controls' => 1,
		),
		'attributes' => 'frameborder="0"',
		'class'      => '',
	)
);

if ( empty( $args['iframe'] ) ) {
	return;
}
// Use preg_match to find iframe src.
preg_match( '/src="(.+?)"/', $args['iframe'], $matches );
if ( empty( $matches ) ) {
	return;
}
$src = $matches[1];

// Add extra parameters to src and replace HTML.
$new_src = add_query_arg( $args['params'], $src );
$iframe  = str_replace( $src, $new_src, $args['iframe'] );

// Add extra attributes to iframe HTML.
$iframe = str_replace( '></iframe>', ' ' . $args['attributes'] . '></iframe>', $iframe );
?>

<div class="c-media c-media__embed <?php echo esc_attr( $args['class'] ); ?>">
	<?php echo $iframe; //phpcs:ignore?>
</div>
