<?php
/**
 * The footer template
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 * @package DS_Theme
 */

global $dsmp_settings;
?>

<?php do_action( 'ds_after_content' ); ?>

<footer class="site-footer" role="contentinfo">

	<?php get_template_part( 'templates/footer/footer' ); ?>

</footer>

</div><!-- wrapper -->

<?php get_template_part( 'templates/global/support-form.php' ); ?>

<?php wp_footer(); ?>

</body>
</html>

