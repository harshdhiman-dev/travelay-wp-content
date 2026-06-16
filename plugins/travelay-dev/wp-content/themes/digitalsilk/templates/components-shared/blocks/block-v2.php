<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'class'                             => '',
		'main_icon'                         => get_field( 'main_icon' ),
		'pretitle'                          => get_field( 'pretitle' ),
		'title'                             => get_field( 'title' ),
		'subtitle'                          => get_field( 'subtitle' ),
		'description'                       => get_field( 'description' ),
		'list'                              => get_field( 'list' ),
		'cta_list'                          => get_field( 'cta_list' ),
		'mixed_gallery'                     => get_field( 'mixed_gallery' ) ?? [],
		'has_image_description'             => get_field( 'has_image_description' ) ?: false,
		'image_description'                 => get_field( 'image_description' ),
		// Main Wrapper Settings
		'columns_order'                     => get_field( 'component_settings_columns_order' ) ?: 'default',
		'is_vertical'                       => get_field( 'component_settings_is_vertical' ) ?: false,
		'columns_ratio'                     => get_field( 'component_settings_columns_ratio' ) ?: 50,
		'columns_gap'                       => get_field( 'component_settings_columns_gap' ) ?: 20,
		// Text Component Settings
		'text_component_gap_left'           => get_field( 'text_component_settings_gap_left_padding_desktop' ) ?: 0,
		'text_component_gap_left_mobile'    => get_field( 'text_component_settings_gap_left_padding_mobile' ) ?: 0,
		'text_component_gap_right'          => get_field( 'text_component_settings_gap_right_padding_desktop' ) ?: 0,
		'text_component_gap_right_mobile'   => get_field( 'text_component_settings_gap_right_padding_mobile' ) ?: 0,
		'text_component_gap_top'            => get_field( 'text_component_settings_gap_top_padding_desktop' ) ?: 0,
		'text_component_gap_top_mobile'     => get_field( 'text_component_settings_gap_top_padding_mobile' ) ?: 0,
		'text_component_gap_bottom'         => get_field( 'text_component_settings_gap_bottom_padding_desktop' ) ?: 0,
		'text_component_gap_bottom_mobile'  => get_field( 'text_component_settings_gap_bottom_padding_mobile' ) ?: 0,
		'vertical_alignment'                => get_field( 'text_component_settings_vertical_alignment' ),
		'alignment_mobile'                  => get_field( 'title_styles_horizontal_alignment_mobile' ) ?: 'left',
		'alignment'                         => get_field( 'title_styles_horizontal_alignment' ) ?: 'left',
		'layout'                            => get_field( 'title_styles_layout' ) ?: 'v1',
	)
);

$className = " order-{$args['columns_order']}";
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}

if ( $args['is_vertical'] ) {
	$className .= ' is-vertical';
}

$classNameTextComponent = '';
if ( ! empty( $args['alignment'] ) ) {
	$classNameTextComponent .= " text-{$args['alignment']}";
}

if ( ! empty( $args['alignment_mobile'] ) ) {
	$classNameTextComponent .= " text-{$args['alignment_mobile']}-mobile";
}

if ( ! empty( $args['layout'] ) ) {
	$classNameTextComponent .= " layout-{$args['layout']}";
}

if ( ! empty( $args['vertical_alignment'] ) ) {
	$classNameTextComponent .= " align-{$args['vertical_alignment']}";
}
$styles = "";
if ( intval( $args['columns_ratio'] ) !== 0 ) {
	$styles .= "--columns-ratio: {$args['columns_ratio']}%;";
}

if ( intval( $args['columns_gap'] ) !== 0 ) {
	$styles .= "--columns-gap: {$args['columns_gap']}px;";
}

$stylesTextComponent = "--space-left: {$args['text_component_gap_left']}%; --space-left-m: {$args['text_component_gap_left_mobile']}px; --space-right: {$args['text_component_gap_right']}%;--space-right-m: {$args['text_component_gap_right_mobile']}px; --space-top: {$args['text_component_gap_top']}%; --space-top-m: {$args['text_component_gap_top_mobile']}px; --space-bottom: {$args['text_component_gap_bottom']}%;--space-bottom-m: {$args['text_component_gap_bottom_mobile']}px;";
?>

<div class="c-block<?php echo esc_attr( $className ); ?>" style="<?php echo $styles; ?>">
	<div class="c-block__text<?php echo esc_attr( $classNameTextComponent ); ?>" style="<?php echo esc_attr( $stylesTextComponent ); ?>">
		<div class="c-block__inner">

			<?php if ( ! empty( $args['main_icon']['ID'] ) ) : ?>

				<?php echo ds_generate_image( $args['main_icon']['ID'], 'ds_small', 'c-block__icon' ); ?>

			<?php endif; ?>

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
				'templates/components/list/list-v1',
				null,
				array(
					'list' => $args['list'],
				)
			);
			?>
			<?php
			get_template_part(
				'templates/components/cta-list',
				null,
				array(
					'buttons' => $args['cta_list'],
				)
			);
			?>
		</div>
	</div>

	<div class="c-block__media">
		<?php
		get_template_part(
			'templates/components-shared/media-mixed/mixed-gallery-v1',
			null,
			array(
				'main_content_type'    => $args['mixed_gallery']['main_content_type'] ?? false,
				'main_image'           => $args['mixed_gallery']['main_image'] ?? false,
				'main_image_size'      => $args['mixed_gallery']['main_image_size'] ?? 'medium_large',
				'secondary_image'      => $args['mixed_gallery']['secondary_image'] ?? false,
				'secondary_image_size' => $args['mixed_gallery']['secondary_image_size'] ?? 'medium_large',
				'video_source'         => $args['mixed_gallery']['main_video']['video_source'] ?? false,
				'video'                => $args['mixed_gallery']['main_video']['video'] ?? false,
				'video_embed'          => $args['mixed_gallery']['main_video']['video_embed'] ?? false,
				'poster_image'         => $args['mixed_gallery']['main_video']['poster_image'] ?? false,
				'hide_controls'        => $args['mixed_gallery']['main_video']['hide_controls'] ?? false,
				'autoplay'             => $args['mixed_gallery']['main_video']['autoplay'] ?? false,
				'disable_lazy'         => $args['mixed_gallery']['disable_lazy'] ?? false,
			)
		);
		?>

		<?php if ( $args['has_image_description'] ) : ?>

			<?php get_template_part( 'templates/components/content/info-v1', null, array( 'description' => $args['image_description'] ) ); ?>

		<?php endif; ?>
	</div>
</div>
