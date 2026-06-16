<?php
/**
 * @var array $args
 */

global $wp_query;

if ( empty( $args['query'] ) ) {
    $args['query'] = $wp_query;
}

$current_page   = $args['query']->query_vars['paged']; // current page.
$posts_per_page = $args['query']->query_vars['posts_per_page']; // posts per page.
$posts_total    = $args['query']->found_posts; // total in query.
$post_count     = $args['query']->post_count; // on current page.

$first = '';
$last  = '';
$found = '';

if ( 1 < $post_count ) {
    if ( 1 >= $current_page ) {
        $first = 1;
        $last = ( $post_count < $posts_total ) ? $post_count : $posts_total;
    } else {
        $first = 1 + $posts_per_page * ($current_page - 1);
        $last = ( $posts_per_page * $current_page < $posts_total ) ? $posts_per_page * $current_page : $posts_total;;
    }

    $found = $first . '-' . $last;
} else { // if only one post on page, may be last page.
    $found = $posts_total;
}

?>
<div class="posts-counter-container">
	<div class="posts-counter text-center">
		<?php echo sprintf( 'Showing %1$s of %2$s item(s)', $found, $posts_total ); // phpcs:ignore ?>
	</div>
</div>
