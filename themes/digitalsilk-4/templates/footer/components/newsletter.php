<?php
// phpcs:ignoreFile

/**
 * @var array $args
 */

if ( empty( $args['newsletter']['form'] ) && empty( $args['newsletter']['iframe'] ) ) {
	return;
}

?>
<div class="c-newsletter">
	<div class="c-newsletter__container">
		<?php if ( ! empty( $args['newsletter']['form'] ) ) : ?>
			<div class="c-newsletter__form">
				<?php if ( ! empty( $args['newsletter']['title'] ) ) : ?>
					<div class="c-newsletter__title"><?php echo $args['newsletter']['title']; ?></div>
				<?php endif; ?>
				<?php if ( ! empty( $args['newsletter']['subtitle'] ) ) : ?>
					<div class="c-newsletter__subtitle"><?php echo wp_kses_post( $args['newsletter']['subtitle'] ); ?></div>
				<?php endif; ?>
				<?php echo do_shortcode( '[gravityform id="' . $args['newsletter']['form'] . '" title="false" ajax="true"]' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $args['newsletter']['iframe'] ) ) : ?>
			<?php echo $args['newsletter']['iframe']; ?>
		<?php endif; ?>
	</div>
</div>
