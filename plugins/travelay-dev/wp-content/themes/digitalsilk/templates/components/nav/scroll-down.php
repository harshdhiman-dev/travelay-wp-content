<?php
/**
 * @var array $args
 */

if ( ! get_field( 'scroll_down_enable', 'option' ) ) {
	return;
}

$scroll_down          = get_field( 'scroll_down' );
$module_args          = [];
$scroll_down_position = get_field( 'scroll_down_position' );

if ( 'global' === $scroll_down ) {
	$module_args = array(
		'scroll_down_position' => 'default' === $scroll_down_position ? get_field( 'scroll_down_position', 'option' ) : $scroll_down_position,
		'scroll_down_icon'     => get_field( 'scroll_down_icon', 'option' ),
		'scroll_down_text'     => get_field( 'scroll_down_text', 'option' ),
	);
}


$args = wp_parse_args( $args, $module_args );

if ( $scroll_down ) : ?>

	<div class="scroll-down <?php echo esc_attr( $args['scroll_down_position'] ); ?>">
        <span class="scroll-down__txt">
            <?php echo wp_kses_post( $args['scroll_down_text'] ); ?>
        </span>
		<?php if ( $args['scroll_down_icon']['ID'] ) : ?>
			<?php echo ds_generate_image( $args['scroll_down_icon']['ID'], 'ds_small', 'scroll-down__ico' ); ?>
		<?php else : ?>
			<span>&#8659;</span>
		<?php endif; ?>
	</div>

<?php
endif;
