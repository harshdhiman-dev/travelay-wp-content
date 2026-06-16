<?php
/**
 * The template for displaying single posts
 *
 * Copy this template for a single custom post type and change 'taxonomy' => 'category' to 'taxonomy' => "example_taxonomy_name".
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

		get_template_part( 'templates/content/content', 'single', array( 'taxonomy' => 'category' ) );
	endwhile;

	get_template_part( 'templates/parts/pagination', 'single', array( 'taxonomy' => 'category' ) );
endif;
?>

<?php
get_footer();
