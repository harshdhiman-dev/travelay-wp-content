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
	'layout' => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'items'  => get_field( 'timeline_listing' ),
);

$styles = '';

if ( ! empty( $args['items'] ) ) {
	$styles .= '--timeline-items-number:' . count( $args['items'] ) . ';';
}
?>
<div class="m-block<?php echo esc_attr( $block['className'] ); ?> l-timeline-1" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-block__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">

		<?php if ( ! empty( $args['items'] ) ) : ?>
			<div class="l-timeline <?php echo "l-timeline-{$args['layout']}"; ?>" style="<?php echo $styles; ?>">
				<div class="l-timeline__box">
					<?php foreach ( $args['items'] as $item ) : ?>
						<div class="l-timeline__col">
							<?php get_template_part( 'templates/components-shared/timeline/timeline-v1', null, $item ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

	</div>

	<?php get_template_part( 'templates/components/nav/scroll-down' ); ?>

</div>
