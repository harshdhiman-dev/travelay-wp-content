<?php
/**
 * The template for displaying default template pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package DS_Theme
 */

get_header(
	null,
	array(
		'class_body' => '',
		'class_main' => '',
	)
);
?>

<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		get_template_part( 'templates/content/content', 'page' );
	endwhile;
endif;
?>

<?php
get_footer();
