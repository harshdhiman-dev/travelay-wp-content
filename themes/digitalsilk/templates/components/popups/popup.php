<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'content'  => '',
		'popup_id' => uniqid( 'p_' ),
		'class'    => '',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}
?>
<?php if ( ! empty( $args['content'] ) ) : ?>
	<div id="<?php echo esc_attr( $args['popup_id'] ); ?>" class="c-popup<?php echo esc_attr( $className ); ?>" style='display: none'>
		<?php echo wp_kses_post( $args['content'] ); ?>
	</div>
	<?php
endif;
