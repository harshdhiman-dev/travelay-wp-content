<?php
/**
 * Wrapper Block Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile
if ( $moduleConfig->layout_settings_module_columns ) {
	$moduleConfig->set_style( '--wrap-col-1', "{$moduleConfig->layout_settings_columns_ratio}%" );
	$moduleConfig->set_style( '--wrap-col-2', ( 100 - $moduleConfig->layout_settings_columns_ratio ) . '%' );
	$moduleConfig->set_style( '--wrap-col-gap', ( $moduleConfig->layout_settings_columns_gap ) . 'px' );
	$block['className'] .= ' has-columns align-' . ( $moduleConfig->layout_settings_vertical_alignment ?: 'top' );
}
?>
<div class="m-wrapper<?php echo esc_attr( $block['className'] ); ?> <?php echo $moduleConfig->container; ?>" <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-wrapper__inner">
		<InnerBlocks/>
	</div>

	<div class="m-wrapper__bg"><?php echo $moduleConfig->backgroundMediaHTML; ?></div>
</div>
