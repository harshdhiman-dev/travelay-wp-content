<?php
/**
 * Contact Form Banner Template
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $module_config ->get_styles(), ->data_attributes, ->container, ->container_width.
 */

if ( 0 !== (int) $module_config->layout_settings_columns_ratio ) {
	$module_config->set_style( '--columns-ratio', "{$module_config->layout_settings_columns_ratio}%" );
}

$args = array(
	'columns_order'    => get_field( 'layout_settings_columns_order' ) ?: 'default',
	'vertical_columns' => get_field( 'layout_settings_vertical_columns' ) ?: false,
	'layout'           => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'content_type'     => get_field( 'content_type' ) ?: 'none',
	'add_phone'        => get_field( 'add_phone' ) ?: false,
	'add_address'      => get_field( 'add_address' ) ?: false,
);

$vertical_class = ! empty( $args['vertical_columns'] ) ? ' is-vertical' : '';
?>
<div
	class="m-form<?php echo esc_attr( $block['className'] ); ?>" <?php echo esc_attr( $module_config->data_attributes ); ?> <?php echo $module_config->get_styles(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-form__container <?php echo esc_attr( $module_config->container ); ?>"
		 style="<?php echo $module_config->container_width; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">

		<div
			class="l-form <?php echo 'l-form-' . esc_attr( $args['layout'] ); ?> <?php echo 'order-' . esc_attr( $args['columns_order'] ); ?><?php echo esc_attr( $vertical_class ); ?>">

			<div class="l-form__col l-form__content">

				<InnerBlocks className="l-form__content-inner"
							 allowedBlocks="<?php echo esc_attr( wp_json_encode( $block['attributes']['allowedBlocks'] ) ); ?>"
							 template="<?php echo esc_attr( wp_json_encode( $block['attributes']['templateArr'] ) ); ?>"
				/>

				<?php if ( 'info_box' === $args['content_type'] ) : ?>

					<?php get_template_part( 'templates/components/content/box-info' ); ?>

				<?php elseif ( 'image' === $args['content_type'] ) : ?>

					<?php get_template_part( 'templates/components/images/image-gallery-v1' ); ?>

				<?php elseif ( 'map_iframe' === $args['content_type'] ) : ?>

					<?php get_template_part( 'templates/components/maps/map-iframe' ); ?>

				<?php endif; ?>

			</div>

			<div class="l-form__col l-form__form">
				<?php get_template_part( 'templates/components/forms/form-v1' ); ?>
			</div>

		</div>

	</div>
</div>
