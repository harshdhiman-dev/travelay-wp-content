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

$has_background_color  = ( 'color' === $background['type'] && ! empty( $background['color'] ) );
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

$loading = ! empty( $background['disableLazyLoad'] ) ? 'eager' : 'lazy';
?>

<div <?php ds_theme_generate_anchor( $attributes ); ?> <?php echo get_block_wrapper_attributes( $extra_attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( $has_background_image && (float) $background['overlayOpacity'] > 0 ) : ?>
		<span
			class="c-explore__bg-overlay"
			aria-hidden="true"
			style="background-color: <?php echo esc_attr( $background['overlayColor'] ); ?>; opacity: <?php echo esc_attr( (float) $background['overlayOpacity'] / 100 ); ?>;"
		></span>
	<?php endif; ?>

	<?php if ( $show_decoration ) : ?>
		<svg class="c-explore__decoration" preserveAspectRatio="none" viewBox="0 0 1400 90" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<defs>
				<pattern id="dst-explore-skyline" width="140" height="90" patternUnits="userSpaceOnUse">
					<rect x="6" y="40" width="10" height="50" fill="#ffffff" />
					<rect x="22" y="20" width="8" height="70" fill="#ffffff" />
					<rect x="36" y="50" width="14" height="40" fill="#ffffff" />
					<rect x="56" y="14" width="8" height="76" fill="#ffffff" />
					<circle cx="60" cy="14" r="6" fill="#ffffff" />
					<rect x="74" y="35" width="10" height="55" fill="#ffffff" />
					<rect x="92" y="55" width="18" height="35" fill="#ffffff" />
					<rect x="116" y="25" width="8" height="65" fill="#ffffff" />
					<rect x="130" y="45" width="10" height="45" fill="#ffffff" />
				</pattern>
			</defs>
			<rect width="1400" height="90" fill="url(#dst-explore-skyline)" opacity="0.08" />
		</svg>
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

		<?php if ( ! empty( $images ) ) : ?>
			<div class="c-explore__gallery">
				<?php
				$gallery_columns = [
					[ 0, 2 ],
					[ 1, 3 ],
				];
				?>
				<?php foreach ( $gallery_columns as $col_index => $img_indexes ) : ?>
					<div class="c-explore__gallery-col<?php echo ( 1 === $col_index ) ? ' -offset' : ''; ?>">
						<?php foreach ( $img_indexes as $img_index ) : ?>
							<?php
							$item  = $images[ $img_index ] ?? [];
							$url   = $item['media']['imagePrimary']['url'] ?? '';
							$alt   = $item['media']['imagePrimary']['alt'] ?? '';
							$label = $item['label'] ?? ( 'Image ' . ( $img_index + 1 ) );
							?>
							<div class="c-explore__gallery-img">
								<?php if ( ! empty( $url ) ) : ?>
									<img src="<?php echo esc_url( $url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="<?php echo esc_attr( $loading ); ?>" />
								<?php else : ?>
									<span><?php echo esc_html( $label ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>