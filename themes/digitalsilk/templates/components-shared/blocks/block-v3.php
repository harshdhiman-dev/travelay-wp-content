<?php
/**
 * @var array $args
 */

$args            = wp_parse_args(
	$args,
	array(
		'image'        => get_sub_field( 'image' ),
		'pretitle'     => get_sub_field( 'pretitle' ),
		'title'        => get_sub_field( 'title' ),
		'title_styles' => get_field( 'title_styles' ) ?: array( 'tag' => 'h3' ),
		'description'  => get_sub_field( 'description' ),
		'cta_list'     => get_sub_field( 'cta_list' ),
		'class'        => '',
		'styles'       => '',
	)
);
$componentClass  = $args['class'];
$componentStyles = $args['styles'];
?>

<div class="c-block <?php echo esc_attr( $componentClass ); ?>" style="<?php echo esc_attr( $componentStyles ); ?>">

	<div class="c-block__body">

		<?php if ( ! empty( $args['pretitle'] ) ) : ?>
			<div class="c-block__pretitle">
				<span><?php echo esc_html( $args['pretitle'] ); ?></span>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $args['title'] ) ) : ?>
			<div class="<?php echo '-' . ( esc_html( $args['title_styles']['tag'] ) ?? 'h4' ); ?>">
				<?php echo acf_title( $args['title'], $args['title_styles'], 'c-block__title' ); //phpcs:ignore?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $args['description'] ) ) : ?>
			<div class="c-block__description">
				<?php echo wp_kses_post( $args['description'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $args['cta_list'] ) ) : ?>
			<?php
			get_template_part(
				'templates/components/cta-list',
				null,
				array(
					'buttons' => $args['cta_list'],
				)
			);
			?>
		<?php endif; ?>

	</div>

</div>
