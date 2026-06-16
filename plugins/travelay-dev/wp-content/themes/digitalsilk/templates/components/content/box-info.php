<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'title' => get_field( 'info_box_title' ),
	)
);
?>
<?php if ( ! empty( $args['title'] ) ) : ?>
	<div class="c-info-box">
		<h3 class="c-info-box__title"><?php echo esc_html( $args['title'] ); ?></h3>
	</div>
<?php endif; ?>
