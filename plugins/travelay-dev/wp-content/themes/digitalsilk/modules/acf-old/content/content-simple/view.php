<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */

?>
<div
	class="m-block<?php echo esc_attr( $block['className'] ); ?> simple-content"
	<?php echo $moduleConfig->data_attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo $moduleConfig->get_styles(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<div class="m-block__container <?php echo esc_attr( $moduleConfig->container ); ?>"
		 style="<?php echo esc_attr( $moduleConfig->container_width ); ?>">

		<?php get_template_part( 'templates/components/headings/heading-cta' ); ?>

	</div>
</div>
