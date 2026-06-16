<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'pretitle'      => get_field( 'pretitle' ),
		'title'         => get_field( 'title' ),
		'title_styles'  => get_field( 'title_styles' ),
		'subtitle'      => get_field( 'subtitle' ),
		'description'   => get_field( 'description' ),
		'text_position' => get_field( 'text_position' ),
		'buttons'       => get_field( 'cta_list' ),
		'is_slider'     => false,
	)
);

if ( $args['is_slider'] ) {
	$args['title_styles']['tag'] = 'h1';
}

$className = '';
if ( ! empty( $args['text_position'] ) ) {
	$className .= " text-{$args['text_position']}";
}

?>
<div class="<?php echo esc_attr( $className ); ?>">
	<div class="c-banner__content c-banner__col">
		<?php if ( ! empty( $args['pretitle'] ) ) : ?>
			<h6 class="c-banner__pre"><?php echo esc_html( $args['pretitle'] ); ?></h6>
		<?php endif; ?>

		<?php if ( ! empty( $args['title'] ) ) : ?>
			<?php echo wp_kses_post( acf_title( $args['title'], $args['title_styles'], 'c-banner__title' ) ); ?>
		<?php endif; ?>

		<?php if ( ! empty( $args['subtitle'] ) ) : ?>
			<h2 class="c-banner__sub"><?php echo esc_html( $args['subtitle'] ); ?></h2>
		<?php endif; ?>
	</div>

	<div class="c-banner__content-right c-banner__col">
		<?php if ( ! empty( $args['description'] ) ) : ?>
			<div class="c-banner__description">
				<?php echo apply_filters( 'acf_the_content', $args['description'] ); //phpcs:ignore ?>
			</div>
		<?php endif; ?>

		<?php
		get_template_part(
			'templates/components/cta-list',
			null,
			array(
				'buttons' => $args['buttons'],
			),
		);
		?>
	</div>
</div>
