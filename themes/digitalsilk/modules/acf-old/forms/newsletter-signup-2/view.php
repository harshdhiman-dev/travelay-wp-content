<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
	'columns_order'    => get_field( 'layout_settings_columns_order' ) ?: 'default',
	'layout'           => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'vertical_columns' => get_field( 'layout_settings_vertical_columns' ) ?: false,
	'columns_ratio'    => get_field( 'layout_settings_columns_ratio' ),
);

$vertical_class = ! empty( $args['vertical_columns'] ) ? ' is-vertical' : '';

$styles        = '';
$columns_ratio = ! empty( $args['columns_ratio'] ) && ( (int) $args['columns_ratio'] !== 0 ) ? $args['columns_ratio'] : 40;
$styles       .= "--columns-ratio: {$columns_ratio}%";
?>
<div class="m-newsletter-signup<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-newsletter-signup__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="m-newsletter-signup__inner">

			<div class="l-newsletter-signup <?php echo "l-newsletter-signup-{$args['layout']}"; ?> <?php echo "order-{$args['columns_order']}"; ?><?php echo $vertical_class; ?>" style="<?php echo $styles; ?>">

				<div class="l-newsletter-signup__col l-newsletter-signup__col1">
					<?php get_template_part( 'templates/components/content/box-info' ); ?>
				</div>

				<div class="l-newsletter-signup__col l-newsletter-signup__col2">
					<?php get_template_part( 'templates/components/forms/form-v1' ); ?>
				</div>

			</div>

		</div>
	</div>
</div>
