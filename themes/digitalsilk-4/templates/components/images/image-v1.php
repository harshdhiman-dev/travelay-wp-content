<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'image'        => get_field( 'image' ),
		'image_size'   => get_field( 'image_size' ) ?: get_sub_field( 'image_size' ),
		'class'        => '',
		'disable_lazy' => false,
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}
?>
<?php if ( ! empty( $args['image']['ID'] ) ) : ?>
	<figure class="c-media<?php echo esc_attr( $className ); ?> c-media__primary">
		<?php echo ds_generate_image( $args['image']['ID'], $args['image_size'], 'c-media__src', '', ! $args['disable_lazy'] ); ?>
	</figure>
<?php
endif;
