<?php
/**
 * Explore Highlights markup
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

$features        = ( ! empty( $attributes['features'] ) && is_array( $attributes['features'] ) ) ? $attributes['features'] : [];
$images          = ( ! empty( $attributes['images'] ) && is_array( $attributes['images'] ) ) ? $attributes['images'] : [];
$show_decoration = ! isset( $attributes['showDecoration'] ) || ! empty( $attributes['showDecoration'] );

$background = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'type'            => 'none',
		'color'           => '#1f7a4d',
		'image'           => [],
		'imageMobile'     => [],
		'mediaFit'        => 'cover',
		'mediaPosition'   => 'center center',
		'disableLazyLoad' => false,
		'overlayOpacity'  => 0,
		'overlayColor'    => '#000000',
	]
);

$has_background_color = ( 'color' === $background['type'] && ! empty( $background['color'] ) );
$has_background_image  = ( 'image' === $background['type'] && ! empty( $background['image']['url'] ) );

$has_mobile_background = ( $has_background_image && ! empty( $background['imageMobile']['url'] ) );

if ( $has_background_color ) {
	$extra_attributes['style'] = ( isset( $extra_attributes['style'] ) ? $extra_attributes['style'] . '; ' : '' ) . 'background-color: ' . esc_attr( $background['color'] );
} elseif ( $has_background_image ) {
	$bg_style   = [];
	$bg_style[] = 'background-image: url(' . esc_url( $background['image']['url'] ) . ')';
	$bg_style[] = 'background-size: ' . ( 'contain' === $background['mediaFit'] ? 'contain' : 'cover' );
	$bg_style[] = 'background-position: ' . esc_attr( $background['mediaPosition'] );
	$bg_style[] = 'background-repeat: no-repeat';

	if ( $has_mobile_background ) {
		$bg_style[] = '--dst-bg-mobile: url(' . esc_url( $background['imageMobile']['url'] ) . ')';
	}

	$extra_attributes['style'] = ( isset( $extra_attributes['style'] ) ? $extra_attributes['style'] . '; ' : '' ) . implode( '; ', $bg_style );
}

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
if ( ! empty( $spacing['paddingTop'] ) ) {
	$spacing_style[] = 'padding-top: ' . esc_attr( $spacing['paddingTop'] );
}
if ( ! empty( $spacing['paddingRight'] ) ) {
	$spacing_style[] = 'padding-right: ' . esc_attr( $spacing['paddingRight'] );
}
if ( ! empty( $spacing['paddingBottom'] ) ) {
	$spacing_style[] = 'padding-bottom: ' . esc_attr( $spacing['paddingBottom'] );
}
if ( ! empty( $spacing['paddingLeft'] ) ) {
	$spacing_style[] = 'padding-left: ' . esc_attr( $spacing['paddingLeft'] );
}
if ( ! empty( $spacing['marginTop'] ) ) {
	$spacing_style[] = 'margin-top: ' . esc_attr( $spacing['marginTop'] );
}
if ( ! empty( $spacing['marginRight'] ) ) {
	$spacing_style[] = 'margin-right: ' . esc_attr( $spacing['marginRight'] );
}
if ( ! empty( $spacing['marginBottom'] ) ) {
	$spacing_style[] = 'margin-bottom: ' . esc_attr( $spacing['marginBottom'] );
}
if ( ! empty( $spacing['marginLeft'] ) ) {
	$spacing_style[] = 'margin-left: ' . esc_attr( $spacing['marginLeft'] );
}

if ( ! empty( $spacing_style ) ) {
	$extra_attributes['style'] = ( isset( $extra_attributes['style'] ) ? $extra_attributes['style'] . '; ' : '' ) . implode( '; ', $spacing_style );
}
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $has_background_image && (float) $background['overlayOpacity'] > 0 ) : ?>
		<span
			class="c-explore__bg-overlay"
			aria-hidden="true"
			style="background-color: <?php echo esc_attr( $background['overlayColor'] ); ?>; opacity: <?php echo esc_attr( (float) $background['overlayOpacity'] / 100 ); ?>;"
		></span>
	<?php endif; ?>
	
	<div class="c-explore__inner">
		<div class="c-explore__content">
			<?php if ( ! empty( $heading['title'] ) ) : ?>
				<h2 class="c-explore__title"><?php echo wp_kses_post( $heading['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $heading['subtitle'] ) ) : ?>
				<p class="c-explore__subtitle"><?php echo wp_kses_post( $heading['subtitle'] ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $features ) ) : ?>
				<div class="c-explore__card">
					<?php foreach ( $features as $feature ) : ?>
						<div class="c-explore__feature">
							<?php if ( ! empty( $feature['title'] ) ) : ?>
								<h3 class="c-explore__feature-title"><?php echo wp_kses_post( $feature['title'] ); ?></h3>
							<?php endif; ?>
							<?php if ( ! empty( $feature['description'] ) ) : ?>
								<p class="c-explore__feature-description"><?php echo wp_kses_post( $feature['description'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php
		$primary_image_url = $images[0]['media']['imagePrimary']['url'] ?? '';
		$primary_image_alt = $images[0]['media']['imagePrimary']['alt'] ?? '';
		$loading            = ! empty( $background['disableLazyLoad'] ) ? 'eager' : 'lazy';
		?>
		<?php if ( ! empty( $primary_image_url ) ) : ?>
			<div class="c-explore__media">
				<div class="c-explore__image -only">
					<img src="<?php echo esc_url( $primary_image_url ); ?>" alt="<?php echo esc_attr( $primary_image_alt ); ?>" loading="<?php echo esc_attr( $loading ); ?>" />
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
