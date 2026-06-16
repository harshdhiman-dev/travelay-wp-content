<?php
/**
 * Search teamplate
 *
 * @package DS_Theme
 */

$search_type = get_sub_field( 'type' );
$search_type = ( ! empty( $search_type ) ) ? $search_type : get_field( 'search_type', 'options' );
?>

<div class="site-header__widget">
	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
        <div class="site-search woocommerce-search -<?php echo esc_attr( $search_type ); ?>">
	        <button role="button" class="site-search__toggle woocommerce-search__toggle" data-js="search-trigger">
		        <span aria-hidden="true" class="sr-only">Open Search</span>
		        <?php
		        //phpcs:ignore

		        echo get_svg(
			        array(
				        'icon'  => 'search',
				        'class' => 'site-search__icon',
			        )
		        );
		        ?>
	        </button>
	        <div class="search-overlay is-hidden" data-js="search-target">
                <a href="#" role="button" class="search-overlay__close" data-js="search-close">x</a>
				<?php echo get_product_search_form(); //phpcs:ignore ?>
            </div>
        </div>
	<?php else : ?>
        <div class="site-search -<?php echo esc_attr( $search_type ); ?>">
	        <button role="button" class="site-search__toggle" data-js="search-trigger" aria-label="Open Search">
		        <span aria-hidden="true" class="sr-only">Open Search</span>
		        <?php
		        //phpcs:ignore

		        echo get_svg(
			        array(
				        'icon'  => 'search',
				        'class' => 'site-search__icon',
			        )
		        );
		        ?>
	        </button>
            <div class="search-overlay is-hidden" data-js="search-target">
                <a href="#" role="button" class="search-overlay__close" data-js="search-close">x</a>
				<?php get_search_form(); ?>
            </div>
        </div>
	<?php endif; ?>
</div>
