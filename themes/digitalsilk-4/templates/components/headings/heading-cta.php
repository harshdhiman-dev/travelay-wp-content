<?php
//phpcs:ignoreFile
/**
 * Heading component together with CTA repeater component
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'alignment_mobile' => get_field( 'title_styles_horizontal_alignment_mobile' ) ?: 'left',
		'alignment'        => get_field( 'title_styles_horizontal_alignment' ) ?: 'left',
		'layout'           => get_field( 'title_styles_layout' ) ?: 'v1',
	)
);
?>
<div class="l-heading <?php echo "text-{$args['alignment']}"; ?> <?php echo ( $args['alignment'] != $args['alignment_mobile'] ) ? "text-{$args['alignment_mobile']}-mobile" : ''; ?> <?php echo "l-heading-{$args['layout']}"; ?>">

	<?php get_template_part( 'templates/components/headings/heading' ); ?>

	<?php get_template_part( 'templates/components/cta-list', null, array( 'class' => '' ) ); ?>

</div>
