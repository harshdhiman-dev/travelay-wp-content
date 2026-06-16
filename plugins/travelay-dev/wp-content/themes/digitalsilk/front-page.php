<?php
/**
 * The template for displaying default template pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package DS_Theme
 */

get_header(); ?>

<?php get_template_part( 'templates/parts/subheader' ); ?>

<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		get_template_part( 'templates/content/content', 'page' );
	endwhile;
endif;
?>


<?php
// Travelay Sticky Call Banner - Mobile Only (Homepage)
if ( is_front_page() ) :
	?>
	<!-- Travelay Sticky Call Banner -->
	<div class="travelay-sticky-banner" id="travelayStickyBanner">
		<button class="travelay-sticky-close" aria-label="Close banner">&times;</button>
		
		<div class="travelay-sticky-avatar">
			<img src="https://www.flytravelay.com/wp-content/uploads/2025/08/Image.png" alt="Travelay Agent" />
			<span class="travelay-status-dot"></span>
		</div>
		
		<div class="travelay-sticky-content">
			<div class="travelay-sticky-title">Unlock Bigger Savings!</div>
			<div class="travelay-sticky-text">
				Use code <span class="travelay-sticky-code">CALL75</span> and save. Call us <strong>24/7</strong>.
			</div>
			<a href="tel:+1-888-823-4702" class="travelay-sticky-link">Tap to call</a>
		</div>
		
		<a href="tel:+1-888-823-4702" class="travelay-sticky-call-icon" aria-label="Call Travelay">☎</a>
	</div>
	<!-- /Travelay Sticky Call Banner -->
	<?php
endif;

get_footer();
