<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'link'             => get_field( 'link' ),
		'icon_position'    => get_field( 'icon_position' ),
		'icon'             => get_field( 'icon' ),
		'background_color' => get_field( 'background_color' ),
		'text_color'       => get_field( 'text_color' ),
	)
);

if ( ! empty( $args['link'] ) ) : ?>

	<?php if ( ! empty( $args['link'] ) ) : ?>
		<a href="<?php echo $args['link']['url']; ?>" class="<?php echo $args['icon_position']; ?>" style="<?php echo "background-color:{$args['background_color']};color:{$args['text_color']};"; ?>">
			<?php echo $args['link']['title']; ?>

			<?php if ( ! empty( $args['icon']['ID'] ) ) : ?>

				<?php echo ds_generate_image( $args['icon']['ID'], 'ds_small' ); ?>

			<?php endif; ?>
		</a>
	<?php endif; ?>

<?php endif; ?>
