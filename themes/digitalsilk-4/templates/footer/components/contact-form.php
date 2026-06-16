<?php
// phpcs:ignoreFile
/**
 * @var array $args
 */

if ( empty( $args['contact']['form'] ) && empty( $args['contact']['iframe'] ) ) {
	return;
}

?>

<div class="footer-block footer-form">
	<?php if ( ! empty( $args['contact']['title'] ) ) : ?>
		<div class="footer-title"><?php echo $args['contact']['title']; ?></div>
	<?php endif; ?>

	<?php if ( ! empty( $args['contact']['form'] ) ) : ?>
		<div class="c-contact">
			<?php echo do_shortcode( '[gravityform id="' . $args['contact']['form'] . '" title="false" ajax="true"]' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $args['contact']['iframe'] ) ) : ?>
		<?php echo $args['contact']['iframe']; ?>
	<?php endif; ?>
</div>
