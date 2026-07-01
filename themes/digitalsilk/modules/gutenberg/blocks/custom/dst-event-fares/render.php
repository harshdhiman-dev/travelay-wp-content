<?php
/**
 * Event Fares markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$heading        = $attributes['heading']      ?? '';
$heading_color  = $attributes['headingColor'] ?? '#ffffff';
$columns        = (int) ( $attributes['columns']      ?? 3 );
$items_per_page = (int) ( $attributes['itemsPerPage'] ?? 6 );
$view_more_text = $attributes['viewMoreText'] ?? 'View More';
$cities         = ( ! empty( $attributes['cities'] ) && is_array( $attributes['cities'] ) ) ? $attributes['cities'] : [];

$background = wp_parse_args(
	$attributes['background'] ?? [],
	[
		'image'          => [],
		'bgColor'        => '#4a7c2f',
		'overlayColor'   => '#000000',
		'overlayOpacity' => 0,
	]
);

$bg_image_url    = $background['image']['url'] ?? '';
$bg_image_alt    = $background['image']['alt'] ?? '';
$bg_color        = $background['bgColor'] ?? '#4a7c2f';
$overlay_color   = $background['overlayColor'] ?? '#000000';
$overlay_opacity = (float) ( $background['overlayOpacity'] ?? 0 ) / 100;

$anchor      = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$anchor_attr = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';

$unique_id = 'dst-event-fares-' . substr( md5( $heading . wp_json_encode( $cities ) ), 0, 8 );
?>

<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	id="<?php echo esc_attr( $unique_id ); ?>"
	class="c-event-fares wp-block-ds-blocks-event-fares"
	style="background-color:<?php echo esc_attr( $bg_color ); ?>;"
	data-items-per-page="<?php echo esc_attr( $items_per_page ); ?>"
>
	<?php if ( ! empty( $bg_image_url ) ) : ?>
		<img
			class="c-event-fares__bg"
			src="<?php echo esc_url( $bg_image_url ); ?>"
			alt="<?php echo esc_attr( $bg_image_alt ); ?>"
			aria-hidden="true"
			loading="lazy"
		/>
	<?php endif; ?>

	<?php if ( $overlay_opacity > 0 ) : ?>
		<span
			class="c-event-fares__overlay"
			aria-hidden="true"
			style="background-color:<?php echo esc_attr( $overlay_color ); ?>; opacity:<?php echo esc_attr( $overlay_opacity ); ?>;"
		></span>
	<?php endif; ?>

	<div class="c-event-fares__inner">

		<?php if ( ! empty( $heading ) ) : ?>
			<h2 class="c-event-fares__heading" style="color:<?php echo esc_attr( $heading_color ); ?>;">
				<?php echo wp_kses_post( $heading ); ?>
			</h2>
		<?php endif; ?>

		<div class="c-event-fares__grid" style="grid-template-columns: repeat(<?php echo esc_attr( $columns ); ?>, 1fr);">
			<?php foreach ( $cities as $index => $city ) :
				$city_name    = $city['cityName']    ?? '';
				$stadium      = $city['stadium']      ?? '';
				$matches_text = $city['matchesText']  ?? '';
				$badge_text   = $city['badgeText']    ?? '';
				$badge_color  = $city['badgeColor']   ?? '#0E7D3F';
				$flights      = ( ! empty( $city['flights'] ) && is_array( $city['flights'] ) ) ? $city['flights'] : [];
				$hidden       = ( $index >= $items_per_page ) ? ' style="display:none"' : '';
			?>
				<div class="c-event-fares__card" data-card-index="<?php echo esc_attr( $index ); ?>"<?php echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

					<div class="c-event-fares__card-top">
						<div class="c-event-fares__card-titlewrap">
							<h3 class="c-event-fares__city-name"><?php echo esc_html( $city_name ); ?></h3>
							<?php if ( ! empty( $badge_text ) ) : ?>
								<span class="c-event-fares__badge" style="background-color:<?php echo esc_attr( $badge_color ); ?>;">
									<?php echo esc_html( $badge_text ); ?>
								</span>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $stadium ) ) : ?>
							<div class="c-event-fares__stadium">
								<span class="c-event-fares__flight-line" aria-hidden="true">
										<img src="https://www.flytravelay.com/wp-content/uploads/2026/07/Stadium.svg" alt="" width="25" height="14" loading="lazy" />
									</span>
								<?php echo esc_html( $stadium ); ?>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $matches_text ) ) : ?>
							<p class="c-event-fares__matches"><?php echo esc_html( $matches_text ); ?></p>
						<?php endif; ?>
					</div>

					<div class="c-event-fares__flights">
						<?php foreach ( $flights as $flight ) :
							$from_code = $flight['fromCode'] ?? '';
							$from_city = $flight['fromCity'] ?? '';
							$to_code   = $flight['toCode']   ?? '';
							$to_city   = $flight['toCity']   ?? '';
							$date      = $flight['date']     ?? '';
							$price     = $flight['price']    ?? '';
							$link      = $flight['link']     ?? '#';
						?>
							<a class="c-event-fares__flight" href="<?php echo esc_url( $link ); ?>">
								<div class="c-event-fares__flight-route">
									<div class="c-event-fares__flight-point">
										<span class="c-event-fares__flight-code"><?php echo esc_html( $from_code ); ?></span>
										<span class="c-event-fares__flight-city"><?php echo esc_html( $from_city ); ?></span>
									</div>
									<span class="c-event-fares__flight-line" aria-hidden="true">
										<img src="https://travelay.dsstaging1.com/wp-content/uploads/2026/07/Group-242.svg" alt="" width="100" height="14" loading="lazy" />
									</span>
									<div class="c-event-fares__flight-point c-event-fares__flight-point--end">
										<span class="c-event-fares__flight-code"><?php echo esc_html( $to_code ); ?></span>
										<span class="c-event-fares__flight-city"><?php echo esc_html( $to_city ); ?></span>
									</div>
									<span class="c-event-fares__flight-arrow" aria-hidden="true">
										<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
											<circle cx="12" cy="12" r="11" fill="#1f7a4d"/>
											<path d="M10 8l4 4-4 4" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</span>
								</div>
								<div class="c-event-fares__flight-bottom">
									<span class="c-event-fares__flight-date"><?php echo esc_html( $date ); ?></span>
									<span class="c-event-fares__flight-price">
										$<?php echo esc_html( $price ); ?><span class="c-event-fares__flight-per">/per person</span>
									</span>
								</div>
							</a>
						<?php endforeach; ?>
					</div>

				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( count( $cities ) > $items_per_page ) : ?>
			<div class="c-event-fares__view-more-wrap">
				<button class="c-event-fares__view-more" data-shown="<?php echo esc_attr( $items_per_page ); ?>">
					<?php echo esc_html( $view_more_text ); ?>
				</button>
			</div>
		<?php endif; ?>

	</div>
</div>

<script>
(function() {
	var root = document.getElementById('<?php echo esc_js( $unique_id ); ?>');
	if (!root) return;
	var btn = root.querySelector('.c-event-fares__view-more');
	if (!btn) return;
	var perPage = parseInt(root.getAttribute('data-items-per-page'), 10) || 6;
	btn.addEventListener('click', function() {
		var shown = parseInt(btn.getAttribute('data-shown'), 10) || perPage;
		var cards = root.querySelectorAll('.c-event-fares__card');
		var newShown = shown + perPage;
		cards.forEach(function(card, i) {
			if (i < newShown) card.style.display = '';
		});
		btn.setAttribute('data-shown', newShown);
		if (newShown >= cards.length) btn.style.display = 'none';
	});
})();
</script>
