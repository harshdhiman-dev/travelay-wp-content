<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'testimonial_main_image'    => get_field( 'testimonial_main_image', get_the_ID() ),
		'has_side_image'            => get_field( 'media_component_settings_has_side_image' ),
		// Main Wrapper Settings.
		'class'                     => '',
		'columns_order'             => get_field( 'component_settings_columns_order' ) ?: 'default',
		'columns_ratio'             => get_field( 'component_settings_columns_ratio' ),
		'component_gap_left'        => get_field( 'component_settings_inner_gap_left' ) ?: 0,
		'component_gap_right'       => get_field( 'component_settings_inner_gap_right' ) ?: 0,
		'component_gap_top'         => get_field( 'component_settings_inner_gap_top' ) ?: 0,
		'component_gap_bottom'      => get_field( 'component_settings_inner_gap_bottom' ) ?: 0,
		'bg_color'                  => get_field( 'component_settings_bg_color' ),
		// Text Component Settings.
		'text_component_gap_left'   => get_field( 'text_component_settings_inner_gap_left' ) ?: 0,
		'text_component_gap_right'  => get_field( 'text_component_settings_inner_gap_right' ) ?: 0,
		'text_component_gap_top'    => get_field( 'text_component_settings_inner_gap_top' ) ?: 0,
		'text_component_gap_bottom' => get_field( 'text_component_settings_inner_gap_bottom' ) ?: 0,
		'has_read_full_story'       => get_field( 'text_component_settings_has_read_full_story' ) ?: false,
		'horizontal_alignment'      => get_field( 'text_component_settings_horizontal_alignment' ),
		'vertical_alignment'        => get_field( 'text_component_settings_vertical_alignment' ),
		'popup_id'                  => uniqid( 'p_' ),
		'full_story_type'           => get_field( 'full_story_type', get_the_ID() ) ?: 'text',
		'full_story'                => get_field( 'full_story', get_the_ID() ),
	)
);
$className = " order-{$args['columns_order']}";
if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}

if ( ! empty( $args['horizontal_alignment'] ) ) {
	$className .= " text-{$args['horizontal_alignment']}";
}

if ( ! empty( $args['vertical_alignment'] ) ) {
	$className .= " align-{$args['vertical_alignment']}";
}
$styles = "--c-block-gl: {$args['component_gap_left']}%;--c-block-gr: {$args['component_gap_right']}%;--c-block-gt: {$args['component_gap_top']}%;--c-block-gb: {$args['component_gap_bottom']}%;";
if ( intval( $args['columns_ratio'] ) !== 0 ) {
	$styles .= "--columns-ratio: {$args['columns_ratio']}%;";
}

if ( $args['bg_color'] ) {
	$styles .= "--c-block__bg: {$args['bg_color']};";
}
// Text Component Gaps.
$stylesText = "--space-left: {$args['text_component_gap_left']}%; --space-right: {$args['text_component_gap_right']}%; --space-top: {$args['text_component_gap_top']}%; --space-bottom: {$args['text_component_gap_bottom']}%;";
?>

<div class="c-block l-testimonials -v2 <?php echo esc_attr( $className ); ?>" style="
<?php
echo esc_html( $styles );
echo esc_html( $stylesText );
?>
		  ">

	<?php if ( $args['has_side_image'] && ! empty( $args['testimonial_main_image'] ) ) : ?>
		<div class="c-block__media">
			<?php get_template_part( 'templates/components/images/image-v1', null, array( 'image' => $args['testimonial_main_image'] ) ); ?>
		</div>
	<?php endif; ?>

	<div class="c-block__text">

		<?php
		get_template_part(
			'templates/components-shared/testimonials/cite-v2',
			null,
			array(
				'class'               => '-v2',
				'has_read_full_story' => $args['has_read_full_story'],
				'popup_id'            => $args['popup_id'],
				'full_story_type'     => $args['full_story_type'],
				'full_story'          => $args['full_story'],
			)
		);
		?>

	</div>

</div>
