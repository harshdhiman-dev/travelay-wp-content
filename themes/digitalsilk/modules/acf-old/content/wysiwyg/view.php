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

$content = get_field( 'editor' ) ?: '<p>Sample Editor Content</p>';
?>
<div class="m-block<?php echo esc_attr( $block['className'] ); ?> l-wysiwyg" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-block__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">

		<?php if ( ! empty( $content ) ) : ?>
			<div class="content-single">
				<?php echo $content; ?>
			</div>
		<?php endif; ?>

	</div>

	<?php get_template_part( 'templates/components/nav/scroll-down' ); ?>

</div>
