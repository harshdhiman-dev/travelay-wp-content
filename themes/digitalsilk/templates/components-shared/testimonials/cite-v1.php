<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'name'                => get_the_title(),
		'company_or_position' => get_field( 'company_position', get_the_ID() ),
		'show_avatar'         => true,
		'class'               => '',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}
?>
<div class="c-quote__profile<?php echo esc_attr( $className ); ?>">
	<?php if ( $args['show_avatar'] ) : ?>
		<?php
		get_template_part(
			'templates/components/testimonials/photo',
			null,
			array(
				'class' => 'c-quote__photo',
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! empty( $args['name'] ) || ! empty( $args['company_or_position'] ) ) : ?>
		<div class="c-quote__author">
			<div class="c-quote__name">
				<?php echo wp_kses_post( $args['name'] ); ?>
			</div>
			<?php if ( ! empty( $args['company_or_position'] ) ) : ?>
				<span class="c-quote__company"><?php echo wp_kses_post( $args['company_or_position'] ); ?></span>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
