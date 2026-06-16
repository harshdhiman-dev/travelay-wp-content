<?php
/**
 * BE part for Posts load more functionality
 *
 * @package DS_Theme
 */
// phpcs:ignoreFile
if ( ! function_exists( 'ds_archive_load_more' ) ) {

	function ds_archive_load_more() {
		$args['post_status'] = 'publish';

		$is_browser_button_used  = isset( $_POST['browser_button_used'] ) && $_POST['browser_button_used'] === 'true';
		$_POST['query']['paged'] = intval( $_POST['query']['paged'] ) ?: 1;

		foreach ( $_POST['query'] as $key => $value ) {
			if ( ! empty( $value ) && in_array( $key, array( 'post_type', 'posts_per_page', 'paged', 'orderby' ) ) ) {
				if ($key == 'orderby') {
					$orderdy_data = explode('_', $value); //$value can have view "title_DESC"
					$args['orderby'] = $orderdy_data[0]; // 1st param after explode "title"
					$args['order'] = $orderdy_data[1]; // 2nd param after explode "DESC"
				} else {
					$args[$key] = $value;
				}
			}
		}

		if ( ! empty( $_POST['form'] ) ) {
			parse_str( $_POST['form'], $form );

			foreach ( $form as $key => $value ) {
				if ( ! empty( $value ) && in_array( $key, array( 's', 'category_name', 'tag' ) ) ) {
					$args[ $key ] = $value;
				}
			}
		}

		if ( $is_browser_button_used && 'ajax' == $_POST['pagination'] ) {
			$args['posts_per_page'] = $args['posts_per_page'] * $args['paged'];
			// Reset here to 1 since we are querying multiple pages at once with above line.
			$args['paged'] = 1;
		}

		$wp_query = new WP_Query( $args );

		$display_term = false;
		if ( isset( $args['category_name'] ) ) {
			$display_term = get_term_by( 'slug', $args['category_name'], 'category' );

		} elseif ( isset( $_POST['main_taxonomy'] ) && isset( $args[ $_POST['main_taxonomy'] ] ) ) {
			$display_term = get_term_by( 'slug', $args[ $_POST['main_taxonomy'] ], $_POST['main_taxonomy'] );
		}

		ob_start();
		if ( $wp_query->have_posts() ) :
			while ( $wp_query->have_posts() ) :
				$wp_query->the_post();
				get_template_part(
					'templates/content/content',
					'archive',
					array(
						'taxonomy'        => $_POST['main_taxonomy'] ?? '',
						'display_term_id' => $display_term ? $display_term->term_id : false,
					)
				);
			endwhile;
			wp_reset_postdata();
		else :
			get_template_part( 'templates/content/content', 'none' );
		endif;
		$posts = ob_get_clean();

		ob_start();
		ds_pagination_ajax_links( $wp_query );
		$pagination = ob_get_clean();

		ob_start();
		get_template_part( 'templates/parts/filter/items-count', null, array( 'query' => $wp_query ) );
		$counter = ob_get_clean();

		$return = array(
			'max_pages'              => $wp_query->max_num_pages === 0 ? 1 : $wp_query->max_num_pages,
			'page'                   => $args['paged'],
			'posts'                  => $posts,
			'query_posts'            => count( $wp_query->posts ),
			'is_browser_button_used' => $is_browser_button_used,
			'fragments'   => array(
				'div.pagination-container'    => $pagination,
				'div.posts-counter-container' => $counter,
			),
		);

		wp_send_json( $return );
		die;
	}

	add_action( 'wp_ajax_ds_archive_load_more', 'ds_archive_load_more' );
	add_action( 'wp_ajax_nopriv_ds_archive_load_more', 'ds_archive_load_more' );
}

