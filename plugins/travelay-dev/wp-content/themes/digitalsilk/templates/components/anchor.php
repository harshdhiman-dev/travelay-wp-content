<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'anchor_id' => '',
	)
);

if ( ! empty( $args['anchor_id'] ) ) : ?>
	<a id="<?php echo esc_attr( $args['anchor_id'] ); ?>"></a>
	<?php
endif;
