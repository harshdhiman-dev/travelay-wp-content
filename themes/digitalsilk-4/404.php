<?php
/**
 * The template for displaying an 404 pagee
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package DS_Theme
 */

get_header(); ?>

<div class="page-content error404__inner">

	<?php block_template_part( '404' ); ?>

</div>

<?php
get_footer();
