<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'url'               => '',
		'poster_image'      => [],
		'poster_image_size' => 'medium_large',
		'class'             => '',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}
?>
<?php if ( ! empty( $args['url'] ) ) : ?>
	<div class="c-video-popup<?php echo esc_attr( $className ); ?>">
		<?php if ( ! empty( $args['poster_image']['ID'] ) ) : ?>

			<?php echo ds_generate_image( $args['poster_image']['ID'], $args['poster_image_size'], 'c-video-popup__poster' ); ?>

		<?php endif; ?>
		<?php
		get_template_part(
			'templates/components/videos/controls',
			null,
			array(
				'label' => '',
				'url'   => $args['url'],
			)
		);
		?>
	</div>
<?php
endif;
