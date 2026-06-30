<?php
/**
 * Season Timeline markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$heading = wp_parse_args(
	$attributes['heading'] ?? [],
	[
		'title'          => '',
		'showDecoration' => true,
	]
);

$seasons        = ( ! empty( $attributes['seasons'] ) && is_array( $attributes['seasons'] ) ) ? $attributes['seasons'] : [];
$decoration_url = $attributes['decorationUrl'] ?? '';

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

$inline_styles = [];
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
		$inline_styles[] = $css_prop . ': ' . esc_attr( $spacing[ $key ] );
	}
}

$anchor         = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$wrapper_style  = ! empty( $inline_styles ) ? ' style="' . implode( '; ', $inline_styles ) . '"' : '';
$anchor_attr    = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';
?>

<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="c-season-timeline wp-block-ds-blocks-season-timeline"<?php echo $wrapper_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php if ( ! empty( $decoration_url ) ) : ?>
		<img
			class="c-season-timeline__decoration"
			src="<?php echo esc_url( $decoration_url ); ?>"
			alt=""
			aria-hidden="true"
			loading="lazy"
		/>
	<?php endif; ?>

	<?php if ( ! empty( $heading['title'] ) ) : ?>
		<div class="c-season-timeline__heading">
			<?php if ( ! empty( $heading['showDecoration'] ) ) : ?>
				<span class="c-season-timeline__ornament" aria-hidden="true">
					<img src="https://www.flytravelay.com/wp-content/uploads/2026/06/Group-219.png" alt="" loading="lazy" />
				</span>
			<?php endif; ?>
			<h2 class="c-season-timeline__title"><?php echo wp_kses_post( $heading['title'] ); ?></h2>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $seasons ) ) : ?>
		<div class="c-season-timeline__track">
			<div class="c-season-timeline__line" aria-hidden="true"></div>

			<?php foreach ( $seasons as $index => $season ) : ?>
				<div class="c-season-timeline__season">
					<div class="c-season-timeline__season-top">
						<?php if ( ! empty( $season['name'] ) ) : ?>
							<strong class="c-season-timeline__season-name">
								<?php echo wp_kses_post( $season['name'] ); ?>
							</strong>
						<?php endif; ?>
						<?php if ( ! empty( $season['subtitle'] ) ) : ?>
							<span class="c-season-timeline__season-subtitle">
								<?php echo wp_kses_post( $season['subtitle'] ); ?>
							</span>
						<?php endif; ?>
					</div>

					<div class="c-season-timeline__dot" aria-hidden="true"></div>

					<?php if ( ! empty( $season['media']['url'] ) ) : ?>
						<div class="c-season-timeline__season-image">
							<img
								src="<?php echo esc_url( $season['media']['url'] ); ?>"
								alt="<?php echo esc_attr( $season['media']['alt'] ?? $season['name'] ?? '' ); ?>"
								loading="lazy"
							/>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
