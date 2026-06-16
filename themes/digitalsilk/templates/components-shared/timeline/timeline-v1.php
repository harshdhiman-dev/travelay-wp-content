<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'image'                => get_sub_field( 'image' ),
		'pretitle'             => get_sub_field( 'pretitle' ),
		'title'                => get_sub_field( 'title' ),
		'title_styles'         => get_field( 'component_title_styles' ) ?: array( 'tag' => 'h3' ),
		'description'          => get_sub_field( 'description' ),

		// Settings.
		'horizontal_alignment' => get_field( 'component_settings_horizontal_alignment' ) ?: 'center',
		'vertical_alignment'   => get_field( 'component_settings_vertical_alignment' ) ?: 'top',
		'component_background' => get_field( 'component_settings_component_background' ),
		'title_color'          => get_field( 'component_settings_title_color' ),
		'content_color'        => get_field( 'component_settings_content_color' ),

		'class'                => '',
	)
);

$className = '';
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}

if ( ! empty( $args['image'] ) ) {
	$className .= ' has-image';
}

if ( $args['title_color'] ) {
	$args['title_styles']['color'] = $args['title_color'];
}

if ( ! empty( $args['horizontal_alignment'] ) ) {
	$className .= " text-{$args['horizontal_alignment']}";
}

if ( ! empty( $args['vertical_alignment'] ) ) {
	$className .= " align-{$args['vertical_alignment']}";
}


?>

<div class="c-timeline <?php echo esc_attr( $className ); ?>">

	<?php if ( ! empty( $args['image'] ) && ! empty( $args['image']['url'] ) ) : ?>
		<div class="c-timeline__media">
			<?php get_template_part( 'templates/components/images/image-v1', null, array( 'image' => $args['image'] ) ); ?>
		</div>
	<?php endif; ?>

	<div class="c-timeline__body" <?php echo ( $args['content_color'] ) ? 'style=color:' . esc_attr( $args['content_color'] ) . ';' : ''; ?>>

		<?php if ( ! empty( $args['pretitle'] ) ) : ?>
			<div class="c-timeline__pretitle">
				<span><?php echo esc_html( $args['pretitle'] ); ?></span>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $args['title'] ) ) : ?>
			<div class="-h4">
				<?php echo wp_kses_post( acf_title( $args['title'], $args['title_styles'], 'c-timeline__title' ) ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $args['description'] ) ) : ?>
			<div class="c-timeline__description">
				<?php echo wp_kses_post( $args['description'] ); ?>
			</div>
		<?php endif; ?>

	</div>

</div>
