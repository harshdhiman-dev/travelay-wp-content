<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */

// phpcs:ignoreFile

if ( (int) $moduleConfig->layout_settings_columns_ratio !== 0 ) {
	$moduleConfig->set_style( '--columns-ratio', "{$moduleConfig->layout_settings_columns_ratio}%" );
}

$args = array(
	'columns' => get_field( 'layout_settings_card_columns' ) ?: 3,
	'layout'  => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'gap'     => get_field( 'layout_settings_card_gap' ) ?: 0,
);

?>
<div class="m-block<?php echo esc_attr( $block['className'] ); ?> l-content-3" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-block__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="l-block <?php echo "l-block-{$args['layout']}"; ?> <?php echo "l-block--{$args['columns']}-col"; ?> <?php echo "l-block--{$args['gap']}-gap"; ?>">
			<?php if ( have_rows( 'cards_widget' ) ) : ?>

				<?php
				while ( have_rows( 'cards_widget' ) ) :
					the_row();
					?>

					<?php get_template_part( 'templates/components-shared/blocks/block-v1' ); ?>

				<?php endwhile; ?>

			<?php endif; ?>
		</div>
	</div>

	<?php get_template_part( 'templates/components/nav/scroll-down' ); ?>

</div>
