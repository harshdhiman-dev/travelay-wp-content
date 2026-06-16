<?php
/**
 * Template Name: Simple Text Page
 *
 * @package Digitalsilk
 */

get_header(
	null,
	[
		'class_body' => '',
		'class_main' => '',
	]
);
?>

	<div class="container simple-page">

		<div class="content-single is-wysiwyg">

			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();

					get_template_part( 'templates/content/content', 'page' );
				endwhile;
			endif;
			?>

		</div>


	</div>

<?php
get_footer();
