<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'description' => get_field( 'description' ),
		'class'       => '',
	)
);
$className = '';

if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}
?>
<?php if ( ! empty( $args['description'] ) ) : ?>
	<div class="c-info<?php echo esc_attr( $className ); ?>">
		<div class="c-info__description"><?php echo wp_kses_post( $args['description'] ); ?></div>
	</div>
<?php endif; ?>
