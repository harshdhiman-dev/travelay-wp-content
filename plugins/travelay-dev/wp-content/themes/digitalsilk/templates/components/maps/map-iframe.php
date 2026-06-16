<?php
// phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'map_iframe' => get_field( 'map_iframe' ) ?: '',
	)
);

if ( ! empty( $args['map_iframe'] ) ) : ?>
	<div class="c-map-iframe">
		<?php echo $args['map_iframe']; ?>
	</div>
<?php endif; ?>
