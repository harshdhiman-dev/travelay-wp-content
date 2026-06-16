<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

if ((int)$moduleConfig->layout_settings_columns_ratio !== 0) {
	$moduleConfig->set_style('--columns-ratio', "{$moduleConfig->layout_settings_columns_ratio}%");
}

$args = array(
	'columns_order' => get_field('layout_settings_columns_order') ?: 'default',
	'vertical_columns' => get_field('layout_settings_vertical_columns') ?: false,
	'layout' => get_field('layout_settings_layout_type') ?: 'v1',
	'content_type' => get_field('content_type') ?: 'none',
	'add_phone' => get_field('add_phone') ?: false,
	'add_address' => get_field('add_address') ?: false,
	'form_socials_title' => get_field('form_socials_title') ?: '',

);

$vertical_class = !empty($args['vertical_columns']) ? ' is-vertical' : '';
?>
<div
	class="m-form<?php echo esc_attr($block['className']); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part('templates/components/anchor', null, array('anchor_id' => $block['anchor'] ?? '')); ?>

	<?php get_template_part('templates/components/decorations/module-decorations'); ?>

	<div class="m-form__container <?php echo $moduleConfig->container; ?>"
		 style="<?php echo $moduleConfig->container_width; ?>">

		<div
			class="l-form <?php echo "l-form-{$args['layout']}"; ?> <?php echo "order-{$args['columns_order']}"; ?><?php echo $vertical_class; ?>">

			<div class="l-form__col l-form__content">
				<?php get_template_part('templates/components/headings/heading-contact'); ?>

				<?php get_template_part('templates/components/cta-list'); ?>

				<?php if ($args['content_type'] !== 'none') : ?>

					<?php if ($args['content_type'] === 'info_box') : ?>

						<?php get_template_part('templates/components/content/box-info'); ?>

					<?php elseif ($args['content_type'] === 'image') : ?>

						<?php get_template_part('templates/components/images/image-gallery-v1'); ?>

					<?php elseif ($args['content_type'] === 'map_iframe') : ?>

						<?php get_template_part('templates/components/maps/map-iframe'); ?>

					<?php endif; ?>

				<?php endif; ?>

				<?php get_template_part('templates/components/list/list-v2'); ?>

				<?php if (!empty($args['form_socials_title'])) : ?>
					<div class="socials-title"> <?php echo esc_html($args['form_socials_title']); ?></div>
				<?php endif ?>

				<?php get_template_part('templates/components/socials'); ?>

			</div>

			<div class="l-form__col l-form__form">
				<?php get_template_part('templates/components/forms/form-v1'); ?>
			</div>

		</div>


	</div>
</div>
