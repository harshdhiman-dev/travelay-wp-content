<?php
//phpcs:ignoreFile

/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'filter_tabs'        => get_field( 'post_type_data_filter_tabs' ),
		'filter_type'        => get_field( 'post_type_data_filter_type' ),
		'filter_categories'  => get_field( 'post_type_data_filter_categories' ),
		'filter_layout_type' => get_field( 'layout_settings_filter_layout_type' ) ?: 'v1',
	),
);
$hasDropdown = '';

if ( $args['filter_type'] == 'dropdown' ) {
	$hasDropdown .= ' has-dropdown';
}

if ( $args['filter_tabs'] == 'enable' && ! empty( $args['filter_categories'] ) ) : ?>
	<div class="l-slider-fnav <?php echo "l-slider-fnav-{$args['filter_layout_type']}"; ?><?php echo $hasDropdown; ?> js-slider-fnav">
		<?php if ( $args['filter_type'] == 'dropdown' ) : ?>
			<button class="c-slider-fnav__dropdown js-slider-fnav-dropdown">
				<?php _e( 'Dropdown', 'dstheme' ); ?>
			</button>
		<?php endif; ?>

		<ul class="c-fnav js-filter-fnav-list">
			<li class="c-fnav__item js-filter-fnav-item is-active"><a data-slider-filter="all" href="#filter_all" ><?php _e( 'All', 'dstheme' ); ?></a></li>

			<?php
			foreach ( $args['filter_categories'] as $tab ) :
				$tax = get_term( $tab, 'category' );
				?>
				<li class="c-fnav__item js-filter-fnav-item"><a data-slider-filter="<?php echo $tax->slug; ?>" href="#filter_<?php echo $tax->slug; ?>"><?php echo $tax->name; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
