<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'filter_type'               => get_field( 'category_filter_type', 'options' ),
		'main_taxonomy'             => '', // example: category.
		'secondary_taxonomy'        => '', // example: post_tag.
		'secondary_taxonomy_title'  => '',
		'enable_secondary_taxonomy' => false,
	)
);


$main_tax_items = array();
if ( ! empty( $args['main_taxonomy'] ) ) {
	$main_tax_items = get_terms( array( 'taxonomy' => $args['main_taxonomy'] ) );
	$main_tax_items = is_wp_error( $main_tax_items ) ? array() : $main_tax_items; // if invalid tax name set empty array.
}

$secondary_tax_items = array();
if ( ! empty( $args['secondary_taxonomy'] ) ) {
	$secondary_tax_items = get_terms( array( 'taxonomy' => $args['secondary_taxonomy'] ) );
	$secondary_tax_items = is_wp_error( $secondary_tax_items ) ? array() : $secondary_tax_items; // if invalid tax name set empty array
}

$current_object = get_queried_object();

$main_tax_name        = 'category' === $args['main_taxonomy'] ? 'category_name' : $args['main_taxonomy'];
$secondary_tax_name   = 'post_tag' === $args['secondary_taxonomy'] ? 'tag' : $args['secondary_taxonomy'];
$is_main_tax_archive  = 'category' === $args['main_taxonomy'] ? is_category() : is_tax( $args['main_taxonomy'] );
$post_archive_link    = get_post_type_archive_link( $args['post_type'] );
$is_post_type_archive = is_post_type_archive( $args['post_type'] );
?>

<form class="ajax-filter-form" data-form="<?php echo $args['is_ajax']; ?>">
    <div class="blog-filter">
        <div class="blog-filter__inner container">

			<?php if ( ! empty( $main_tax_items ) ) : ?>

				<?php if ( 'selector' == $args['filter_type'] ) : ?>

					<?php
					get_template_part(
						'templates/parts/filter/taxonomy-dropdown',
						null,
						array(
							'terms'      => $main_tax_items,
							'taxonomy'   => $args['main_taxonomy'],
							'all_link'   => $post_archive_link,
							'attributes' => 'data-ajax="false" data-target="input" data-target-name="' . esc_attr( "target-$main_tax_name" ) . '"',
						)
					);
					?>

				<?php else : ?>

					<div class="flex-tabs__outer -desktop js-tabs-hide">
	                    <ul class="blog-filter__list -desktop flex-tabs -primary target-<?php echo esc_attr( $main_tax_name ); ?>" data-ajax-push-url="true" data-target-name="<?php echo esc_attr( "target-$main_tax_name" ); ?>">
							<?php if ( ! empty( $post_archive_link ) ) : ?>
	                            <li>
	                                <a href="<?php echo esc_url( $post_archive_link ); ?>" class="<?php echo ( is_post_type_archive( $args['post_type'] ) || is_home() ) ? 'active_term' : ''; ?>" data-term-slug=""><?php _e( 'All', 'dstheme' ); ?></a>
	                            </li>
							<?php endif; ?>

							<?php foreach ( $main_tax_items as $term ) : ?>
	                            <li>
	                                <a href="<?php echo esc_url( get_term_link( $term ) ); ?>" class="<?php echo ( isset( $current_object->term_id ) && $term->term_id == $current_object->term_id ) ? 'active_term' : ''; ?>" data-term-slug="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></a>
	                            </li>
							<?php endforeach ?>
	                    </ul>
					</div>

					<?php
					get_template_part(
						'templates/parts/filter/taxonomy-dropdown',
						null,
						array(
							'terms'      => $main_tax_items,
							'taxonomy'   => $args['main_taxonomy'],
							'all_link'   => $post_archive_link,
							'class'      => '-mobile ' . esc_attr( "target-$main_tax_name" ),
							'attributes' => 'data-ajax="false" data-target="ul" data-target-name="' . esc_attr( "target-$main_tax_name" ) . '"',
						)
					);
					?>

				<?php endif; ?>

			<?php endif; ?>

            <div class="blog-filter__search">
                <input class="blog-filter__search-field" type="text" name="s" value="<?php echo get_search_query(); ?>" placeholder="<?php echo esc_attr( __( 'Search...', 'dstheme' ) ); ?>">
                <button class="blog-filter__search-btn" type="submit" role="button" aria-label="<?php esc_attr_e( 'Submit search', 'dstheme' ); ?>" value="<?php esc_attr_e( 'Search', 'dstheme' ); ?>">
					<?php
					echo get_svg(
						array(
							'icon'  => 'search',
							'class' => 'site-search__icon',
						)
					);
					?>
                </button>
            </div>

			<?php if ( ! empty( $main_tax_name ) ) : ?>
                <input class="target-<?php echo esc_attr( $main_tax_name ); ?>" data-push-url="<?php echo esc_url( $is_post_type_archive ? $post_archive_link : get_permalink( $current_object ) ); ?>" type="hidden" name="<?php echo esc_attr( $main_tax_name ); ?>" value="<?php echo ! $is_post_type_archive ? esc_attr( $current_object->slug ) : ''; ?>">
			<?php endif; ?>
        </div>
    </div>

	<?php if ( $args['enable_secondary_taxonomy'] && ! empty( $secondary_tax_items ) ) : // tag filter ?>

        <div class="container blog-filter__dropdown">

			<?php if ( ! empty( $args['secondary_taxonomy_title'] ) ) : ?>
                <label for="<?php echo esc_attr( $secondary_tax_name ?? $args['secondary_taxonomy'] ); ?>"><?php echo esc_html( $args['secondary_taxonomy_title'] ); ?></label>
			<?php endif; ?>

			<?php
			get_template_part(
				'templates/parts/filter/taxonomy-dropdown',
				null,
				array(
					'terms'      => $secondary_tax_items,
					'taxonomy'   => $args['secondary_taxonomy'],
					'param_name' => $secondary_tax_name,
				)
			);
			?>

        </div>

	<?php endif; ?>

</form>
