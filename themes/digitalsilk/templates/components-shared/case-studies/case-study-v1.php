<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'pretitle'                  => get_field( 'pretitle', get_the_ID() ),
		'title'                     => get_the_title(),
		'subtitle'                  => get_field( 'subtitle', get_the_ID() ),
		'description'               => get_field( 'description', get_the_ID() ),
		'image_id'                  => get_post_thumbnail_id(),
		'permalink'                 => get_the_permalink(),
		'permalink_title'           => get_field( 'link_title' ) ?: 'Full Story',
		// Main Wrapper Settings.
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
		'horizontal_alignment'      => get_field( 'text_component_settings_horizontal_alignment' ),
		'vertical_alignment'        => get_field( 'text_component_settings_vertical_alignment' ),
	)
);

$classNameTextComponent = '';
if ( ! empty( $args['horizontal_alignment'] ) ) {
	$classNameTextComponent .= " text-{$args['horizontal_alignment']}";
}

if ( ! empty( $args['vertical_alignment'] ) ) {
	$classNameTextComponent .= " align-{$args['vertical_alignment']}";
}
$styles = "--c-block-gl: {$args['component_gap_left']}px;--c-block-gr: {$args['component_gap_right']}px;--c-block-gt: {$args['component_gap_top']}px;--c-block-gb: {$args['component_gap_bottom']}px;";
if ( intval( $args['columns_ratio'] ) !== 0 ) {
	$styles .= "--columns-ratio: {$args['columns_ratio']}%;";
}

if ( $args['bg_color'] ) {
	$styles .= "--c-txt-bg: {$args['bg_color']};";
}

$stylesTextComponent = "--space-left: {$args['text_component_gap_left']}px; --space-right: {$args['text_component_gap_right']}px; --space-top: {$args['text_component_gap_top']}px; --space-bottom: {$args['text_component_gap_bottom']}px;";
?>

<div class="c-case-study<?php echo esc_attr( $className ); ?>" style="<?php echo $styles; ?>">
    <div class="c-case-study__col c-case-study__text<?php echo esc_attr( $classNameTextComponent ); ?>" style="<?php echo esc_attr( $stylesTextComponent ); ?>">
        <div class="c-case-study__inner">
			<?php
            get_template_part(
                'templates/components/headings/heading',
                null,
                array(
					'pretitle'    => $args['pretitle'],
					'title'       => $args['title'],
					'subtitle'    => $args['subtitle'],
					'description' => $args['description'],
                )
			);
            ?>
			<?php
            get_template_part(
                'templates/components/cta-list',
                null,
                array(
					'buttons' => [
						[
							'link' => [
								'url'   => $args['permalink'],
								'title' => $args['permalink_title'],
							],
						],
					],
                )
			);
            ?>
        </div>
    </div>

    <div class="c-case-study__col c-case-study__media">
		<?php echo wp_get_attachment_image( $args['image_id'], 'full' ); ?>
    </div>
</div>
