<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */
$args      = wp_parse_args(
	$args,
	array(
		'class'         => '',
		'label'         => '',
		'has_link'      => false,
		'link_type'     => '',
		'add_icon'      => get_field( 'component_settings_add_icon' ) ?: false,
		'icon'          => get_field( 'component_settings_icon' ),
		'icon_position' => get_field( 'component_settings_icon_position' ) ?: 'left',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}

if ( $args['add_icon'] && ! empty( 'icon' ) ) {
	$className .= " icon-{$args['icon_position']}";
}
?>
<div class="c-anchor-nav__item<?php echo esc_attr( $className ); ?>">

	<?php if ( $args['has_link'] ) : ?>

	    <?php if ( $args['link_type'] === 'block' && ! empty( $args['anchor_target'] ) ) : ?>

            <a href="#<?php echo $args['anchor_target']; ?>" title="<?php echo $args['label']; ?>">

		<?php elseif ( ! empty( $args['link'] ) ) : ?>

            <a href="<?php echo esc_url( $args['link']['url'] ); ?>" title="<?php echo esc_attr( $args['link']['title'] ); ?>" <?php echo $args['link']['target'] ? 'target="' . esc_attr( $args['link']['target'] ) . '"' : ''; ?>>

        <?php endif; ?>

    <?php endif; ?>

    <?php if ( $args['add_icon'] && ! empty( $args['icon'] ) ) : ?>
		<?php echo ds_generate_image( $args['icon']['ID'], 'ds_small', 'c-anchor-nav__icon' ); ?>
    <?php endif; ?>


    <?php if ( ! empty( $args['label'] ) ) : ?>
        <span class="c-anchor-nav__label"><?php echo $args['label']; ?></span>
    <?php endif; ?>


    <?php if ( $args['has_link'] ) : // close above <a> tag if link exists. ?>
        <?php if ( ($args['link_type'] === 'block' && ! empty( $args['anchor_target'] )) || ! empty( $args['link'] ) ) : ?>
            </a>
        <?php endif; ?>
	<?php endif; ?>
</div>
