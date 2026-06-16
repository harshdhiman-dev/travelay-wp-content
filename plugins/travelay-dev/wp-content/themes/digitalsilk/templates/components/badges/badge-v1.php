<?php
/**
 * @var array $args
 */

?>
<div class="c-badge">
	<?php if ( ! empty( $args['label'] ) ) : ?>
        <span class="c-badge__label"><?php echo sprintf( '%02d', $args['label'] ); //phpcs:ignore ?></span>
	<?php endif; ?>
</div>
