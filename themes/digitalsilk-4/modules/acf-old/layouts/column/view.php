<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

?>
<div class="m-wrapper-column <?php echo esc_attr( $block['className'] ); ?>">
	<InnerBlocks/>
</div>
