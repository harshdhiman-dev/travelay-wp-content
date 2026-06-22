<?php
/**
 * Tips Strip markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$extra_attributes = ds_theme_generate_extra_atts( $attributes, $block );

$heading = wp_parse_args(
	$attributes['heading'] ?? [],
	[
		'title'    => '',
		'subtitle' => '',
	]
);

$background = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'image'          => [],
		'overlayColor'   => '#137c43',
		'overlayOpacity' => 65,
	]
);

$tips    = ( ! empty( $attributes['tips'] ) && is_array( $attributes['tips'] ) ) ? $attributes['tips'] : [];
$spacing = wp_parse_args(
	$attributes['spacing'] ?? [],
	[
		'paddingTop'    => '',
		'paddingRight'  => '',
		'paddingBottom' => '',
		'paddingLeft'   => '',
		'marginTop'     => '',
		'marginRight'   => '',
		'marginBottom'  => '',
		'marginLeft'    => '',
	]
);

$spacing_style = [];
foreach (
	[
		'paddingTop'    => 'padding-top',
		'paddingRight'  => 'padding-right',
		'paddingBottom' => 'padding-bottom',
		'paddingLeft'   => 'padding-left',
		'marginTop'     => 'margin-top',
		'marginRight'   => 'margin-right',
		'marginBottom'  => 'margin-bottom',
		'marginLeft'    => 'margin-left',
	] as $key => $css_prop
) {
	if ( ! empty( $spacing[ $key ] ) ) {
		$spacing_style[] = $css_prop . ': ' . esc_attr( $spacing[ $key ] );
	}
}

if ( ! empty( $spacing_style ) ) {
	$extra_attributes['style'] = ( isset( $extra_attributes['style'] ) ? $extra_attributes['style'] . '; ' : '' ) . implode( '; ', $spacing_style );
}

$bg_image_url    = $background['image']['url'] ?? '';
$bg_image_alt    = $background['image']['alt'] ?? '';
$overlay_color   = $background['overlayColor'] ?? '#137c43';
$overlay_opacity = (float) ( $background['overlayOpacity'] ?? 65 ) / 100;
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( ! empty( $bg_image_url ) ) : ?>
		<img class="c-tips-strip__bg" src="<?php echo esc_url( $bg_image_url ); ?>" alt="<?php echo esc_attr( $bg_image_alt ); ?>" aria-hidden="true" loading="lazy" />
	<?php endif; ?>

	<span
		class="c-tips-strip__overlay"
		aria-hidden="true"
		style="background-color: <?php echo esc_attr( $overlay_color ); ?>; opacity: <?php echo esc_attr( $overlay_opacity ); ?>;"
	></span>

	<div class="c-tips-strip__inner">
		<div class="c-tips-strip__content">
			<?php if ( ! empty( $heading['title'] ) ) : ?>
				<h2 class="c-tips-strip__title"><?php echo wp_kses_post( $heading['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $heading['subtitle'] ) ) : ?>
				<p class="c-tips-strip__subtitle"><?php echo wp_kses_post( $heading['subtitle'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $tips ) ) : ?>
			<div class="c-tips-strip__cards">
				<?php foreach ( $tips as $index => $tip ) : ?>
					<div class="c-tips-strip__card -card-<?php echo esc_attr( $index + 1 ); ?>">
						<?php if ( ! empty( $tip['icon']['url'] ) ) : ?>
							<img
								class="c-tips-strip__card-icon"
								src="<?php echo esc_url( $tip['icon']['url'] ); ?>"
								alt="<?php echo esc_attr( $tip['icon']['alt'] ?? '' ); ?>"
								loading="lazy"
							/>
						<?php endif; ?>

						<?php if ( ! empty( $tip['title'] ) ) : ?>
							<h3 class="c-tips-strip__card-title"><?php echo wp_kses_post( $tip['title'] ); ?></h3>
						<?php endif; ?>

						<?php if ( ! empty( $tip['description'] ) ) : ?>
							<p class="c-tips-strip__card-description"><?php echo wp_kses_post( $tip['description'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
