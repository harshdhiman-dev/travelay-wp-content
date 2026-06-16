<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'popup_link' => '',
		'content'    => '',
		'class'      => '',
	)
);
$className = '';
if ( ! empty( $args['className'] ) ) {
	$className .= " {$args['class']}";
}
?>
<?php if ( ! empty( $args['popup_link'] ) ) : ?>
	<a href="<?php echo esc_attr( $args['popup_link'] ); ?>"
	   class="c-popup-link" data-dimbox="c-popup">
		<?php if ( ! empty( $args['content'] ) ) : ?>
			<?php echo wp_kses_post( $args['content'] ); ?>
		<?php endif; ?>
	</a>
<?php
endif;
