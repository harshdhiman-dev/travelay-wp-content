<?php
/**
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var DS_ModuleDefaultSettings $module_config ->get_styles(), ->data_attributes, ->container, ->container_width.
 */

if ( 0 !== (int) $module_config->layout_settings_columns_ratio ) {
	$module_config->set_style( '--columns-ratio', "{$module_config->layout_settings_columns_ratio}%" );
}

if ( 0 !== (int) $module_config->layout_settings_columns_gap ) {
	$module_config->set_style( '--columns-gap', "{$module_config->layout_settings_columns_gap}%" );
}
?>
<div class="m-block<?php echo esc_attr( $block['className'] ); ?> m-dcbl"
    <?php echo $module_config->data_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php echo $module_config->get_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

    <div class="m-block__container <?php echo esc_attr( $module_config->container ); ?>" style="<?php echo esc_attr( $module_config->container_width ); ?>">

        <div class="l-dcbl">
			<?php
                get_template_part(
                    'templates/components-shared/blocks/block-v2.5',
                    null,
                    array(
						'allowed_blocks' => $block['attributes']['allowedBlocks'],
						'template_arr'   => $block['attributes']['templateArr'],
					)
                );
            ?>
        </div>

    </div>

	<?php get_template_part( 'templates/components/nav/scroll-down' ); ?>

</div>
