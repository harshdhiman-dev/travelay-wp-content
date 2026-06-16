<?php
/**
 * Template part for testimonial autor
 *
 * @package DS_Theme
 */

$args           = wp_parse_args(
	$args,
	array(
		'name'             => get_the_title(),
		'company_position' => get_field( 'company_position', get_the_ID() ),
		'class'            => '',
	)
);
$css_class_name = '';
if ( ! empty( $args['class'] ) ) {
	$css_class_name .= " {$args['class']}";
}

?>
<?php if ( ! empty( $args['name'] ) || ! empty( $args['company_position'] ) ) : ?>
	<div class="c-quote__author<?php echo esc_attr( $css_class_name ); ?>">
		<div class="c-quote__name">
			<?php echo esc_html( $args['name'] ); ?>
		</div>

		<?php if ( ! empty( $args['company_position'] ) ) : ?>
			<span class="c-quote__company"><?php echo esc_html( $args['company_position'] ); ?></span>
		<?php endif; ?>
	</div>
	<?php
endif;
