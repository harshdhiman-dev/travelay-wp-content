<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'image'                    => get_sub_field( 'image' ),
		'description'              => get_sub_field( 'description' ),

		'component_gap_vertical'   => get_field( 'component_settings_inner_gap_vertical' ) ?: 0,
		'component_gap_horizontal' => get_field( 'component_settings_inner_gap_horizontal' ) ?: 0,

		'has_background'           => get_field( 'component_settings_has_background' ) ?: false,
		'component_background'     => get_field( 'component_settings_component_background' ),

		'has_hover'                => get_field( 'component_settings_has_hover' ) ?: false,

		'class'                    => '',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className .= $args['class'];
}

$componentStyles = "--c-block__padding-block:{$args['component_gap_vertical']}px;--c-block__padding-inline:{$args['component_gap_horizontal']}px;";

if ( $args['has_background'] ) {
	$className .= ' has-background';

	if ( $args['component_background'] ) {
		$componentStyles .= "--c-block__bg:{$args['component_background']};";
	}
}

if ( $args['has_hover'] ) {
	$className .= ' has-hover';
}

?>
<?php if ( ! empty( $args['image'] ) && is_array( $args['image'] ) ) : ?>
	<div class="c-gallery-card l-rcbl__col <?php echo esc_attr( $className ); ?>"
		 style="<?php echo esc_attr( $componentStyles ); ?>">
		<a href="<?php echo esc_url( $args['image']['url'] ); ?>"
		   title="<?php echo esc_attr( $args['description'] ); ?>"
		   class="c-popup-gallery" data-dimbox="dst-popup-gallery">
			<?php get_template_part( 'templates/components/images/image-v1', null, array( 'image' => $args['image'] ) ); ?>
		</a>
	</div>
<?php
endif;
