<?php
/**
 * @var array $args
 */
// phpcs:ignoreFile
?>
<div class="blog-filter__sort blog-filter__dropdown">
	<label for="orderby"><?php esc_html_e( 'Sort By', 'dstheme' ); ?></label>

	<select class="ajax-sort-by blog-filter__sorter" name="orderby" aria-label="<?php esc_attr_e( 'Sort by', 'dstheme' ); ?>">
		<option value="" <?php if ( isset( $_GET['orderby'] ) ) selected( $_GET['orderby'], '', true ); ?>><?php _e( 'New to old', 'dstheme' ); ?></option>
		<option value="date_ASC" <?php if ( isset( $_GET['orderby'] ) ) selected( $_GET['orderby'], 'date_ASC', true ); ?>><?php _e( 'Old to new', 'dstheme' ); ?></option>
		<option value="title_ASC" <?php if ( isset( $_GET['orderby'] ) ) selected( $_GET['orderby'], 'title_ASC', true ); ?>><?php _e( 'Title A-Z', 'dstheme' ); ?></option>
		<option value="title_DESC" <?php if ( isset( $_GET['orderby'] ) ) selected( $_GET['orderby'], 'title_DESC', true ); ?>><?php _e( 'Title Z-A', 'dstheme' ); ?></option>
	</select>
</div>
