<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'url'      => '',
		'label'    => 'Play Video',
		'popup_id' => uniqid( 'p_' ),
	)
);
?>
<?php if ( ! empty( $args['url'] ) ) : ?>
	<div class="c-controls c-controls-v1">
		<div class="c-controls__option">
			<a href="<?php echo esc_url( $args['url'] ); ?>"
			   class="c-controls__link c-popup-video"
			   data-dimbox="dst-popup-video-<?php echo esc_attr( $args['popup_id'] ); ?>">
				<span class="c-controls__play" title="Play/Pause"></span>
				<?php if ( ! empty( $args['label'] ) ) : ?>
					<span class="c-controls__label"><?php echo esc_html( $args['label'] ); ?></span>
				<?php endif; ?>
			</a>
		</div>
	</div>
<?php
endif;
