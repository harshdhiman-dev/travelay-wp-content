<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package DS_Theme
 */

get_header();

$_post_type = false;
if ( is_home() || is_category() ) {
	$_post_type = 'post';
} elseif ( ! is_post_type_archive() && get_query_var( 'taxonomy' ) !== '' ) {
	$_post_type = apply_filters( 'ds_' . get_query_var( 'taxonomy' ) . '_set_post_type', '' );
} elseif ( is_post_type_archive() ) {
	$_post_type = get_query_var( 'post_type' );
} elseif ( is_search() ) {
	$_post_type = 'any';
}

$pagination_type = get_field( 'pagination_type', 'options' );
$_per_page       = get_option( 'posts_per_page' );

$data_attrs = array(
	'data-per-page'       => $_per_page,
	'data-action'         => 'ds_archive_load_more',
	'data-post-type'      => $_post_type,
	'data-page-parameter' => true,
	'data-first-load'     => true,
	'data-is-archive'     => true, // TODO
);

$post_type_data = apply_filters(
	"ds_{$_post_type}_archive_filter_data",
	array(
		'post_type'                 => $_post_type, // don't change this.
		'is_ajax'                   => 'ajax',
		'filter_type'               => get_field( 'category_filter_type', 'options' ),
		'main_taxonomy'             => '', // example: category.
		'secondary_taxonomy'        => '', // example: post_tag.
		'secondary_taxonomy_title'  => '',
		'enable_secondary_taxonomy' => false,
	)
);

if ( ! empty( $post_type_data['main_taxonomy'] ) ) {
	$data_attrs['data-main-taxonomy'] = $post_type_data['main_taxonomy'];
}
if ( ! empty( $post_type_data['main_taxonomy'] ) ) {
	$data_attrs['data-secondary-taxonomy'] = $post_type_data['secondary_taxonomy'];
}
?>

<?php
$blog_subheader = get_field( 'blog_subheader', 'options' );
get_template_part(
	'templates/parts/subheader-archive',
	$_post_type,
	apply_filters(
		"ds_{$_post_type}_archive_subheader_data",
		array(
			'background_image' => array(),
			'pretitle'         => '',
			'title'            => '',
			'subtitle'         => '',
			'subheader_show'   => $blog_subheader ? $blog_subheader : 'hide',
		)
	)
);
?>

<div class="js-ajax-block <?php the_field( 'listing_style', 'options' ); ?> <?php the_field( 'filter_style', 'options' ); ?>" <?php echo build_attrs( $data_attrs ); //phpcs:ignore ?>>

	<?php get_template_part( 'templates/parts/filter-archive', $_post_type, $post_type_data ); ?>

	<div class="blog-filter__wrap container">

		<?php get_template_part( 'templates/parts/filter/sort-by-dropdown' ); ?>

		<?php get_template_part( 'templates/parts/filter/clear-button' ); ?>

	</div>

	<div class="blog-grid container" data-container="ajax-result" aria-live="polite">

	</div>

	<?php get_template_part( 'templates/parts/pagination-archive', ( ! empty( $pagination_type ) ) ? $pagination_type : 'standard' ); ?>

	<div class="blog-subscribe container">
		<?php block_template_part( 'subscribe' ); ?>
	</div>

</div>

<?php
get_footer();
