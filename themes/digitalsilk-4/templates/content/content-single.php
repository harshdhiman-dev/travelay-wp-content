<?php
/**
 * Template responsible for content single
 *
 * @package DS_Theme
 */

$args = wp_parse_args(
	$args,
	array(
		'taxonomy' => '',
	)
);

$single_post_header_style = get_field( 'single_post_header_style', 'options' );
$header_style             = $single_post_header_style ? $single_post_header_style : 'style-1';

$terms_list = '';
if ( taxonomy_exists( $args['taxonomy'] ) ) {
	$terms_list = get_the_term_list( get_the_ID(), $args['taxonomy'], '', '' );
}
?>

<?php get_template_part( 'templates/parts/breadcrumbs' ); ?>
<div class="container">
	<div
		class="content-single dark-mode__wrap <?php echo esc_attr( $header_style ); ?>">
		<?php if ( shortcode_exists( 'addtoany' ) ) : ?>
			<div class="addtoany_share_save_container addtoany_content">
				<?php echo do_shortcode( '[addtoany]' ); ?>
			</div>
		<?php endif; ?>
		<article id="post-<?php the_ID(); ?>" class="article">
			<?php get_template_part( 'templates/parts/single-post-header', $header_style, array( 'terms_list' => $terms_list ) ); ?>
			<div <?php post_class( 'is-wysiwyg js-has-toc' ); ?>>

				<?php the_content(); ?>
			</div>
			<div class="article-info">
				<?php if ( ! empty( $terms_list ) ) : ?>
					<div class="article-info__tags">
						<label><?php esc_html_e( 'Categories (tags):', 'dstheme' ); ?></label>
						<div
							class="article-info__tag"><?php echo $terms_list; // phpcs:ignore?></div>
					</div>
				<?php endif; ?>
			</div>
		</article>
	</div>
</div>
