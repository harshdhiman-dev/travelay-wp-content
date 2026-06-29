<?php
/**
 * City Directory markup
 *
 * @package DST\Blocks\ds_blocks
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

$heading           = $attributes['heading'] ?? 'List of All Cities';
$cities            = ( ! empty( $attributes['cities'] ) && is_array( $attributes['cities'] ) ) ? $attributes['cities'] : [];
$items_per_page    = (int) ( $attributes['itemsPerPage'] ?? 9 );
$discover_more_text = $attributes['discoverMoreText'] ?? 'Discover More';
$view_more_text     = $attributes['viewMoreText'] ?? 'View More';
$card_layout        = in_array( $attributes['cardLayout'] ?? 'horizontal', [ 'horizontal', 'vertical' ], true )
	? $attributes['cardLayout']
	: 'horizontal';

$anchor      = ! empty( $attributes['anchor'] ) ? $attributes['anchor'] : '';
$anchor_attr = ! empty( $anchor ) ? ' id="' . esc_attr( $anchor ) . '"' : '';

$alphabet = array_merge( [ 'All' ], range( 'A', 'Z' ) );

// JSON-encode cities for the frontend JS.
$cities_json = wp_json_encode(
	array_map(
		function ( $city ) {
			return [
				'name'        => $city['name'] ?? '',
				'description' => $city['description'] ?? '',
				'link'        => $city['link'] ?? '#',
				'imageUrl'    => $city['media']['url'] ?? '',
				'imageAlt'    => $city['media']['alt'] ?? '',
			];
		},
		$cities
	)
);
?>

<div<?php echo $anchor_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="c-city-directory wp-block-ds-blocks-city-directory"
	data-cities="<?php echo esc_attr( $cities_json ); ?>"
	data-items-per-page="<?php echo esc_attr( $items_per_page ); ?>"
	data-discover-text="<?php echo esc_attr( $discover_more_text ); ?>"
	data-card-layout="<?php echo esc_attr( $card_layout ); ?>"
>

	<?php if ( ! empty( $heading ) ) : ?>
		<h2 class="c-city-directory__heading"><?php echo wp_kses_post( $heading ); ?></h2>
	<?php endif; ?>

	<div class="c-city-directory__wrapper">
		<div class="c-city-directory__toolbar">
			<div class="c-city-directory__search-wrap">
				<input
					type="text"
					class="c-city-directory__search"
					placeholder="<?php esc_attr_e( 'Search by City, State or Country', 'dstheme' ); ?>"
					aria-label="<?php esc_attr_e( 'Search cities', 'dstheme' ); ?>"
				/>
				<span class="c-city-directory__search-icon" aria-hidden="true">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
						<path d="M16.5 16.5L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
					</svg>
				</span>
			</div>
			<span class="c-city-directory__az-label"><?php esc_html_e( 'A-Z Cities', 'dstheme' ); ?></span>
		</div>

		<div class="c-city-directory__alphabet" role="group" aria-label="<?php esc_attr_e( 'Filter by letter', 'dstheme' ); ?>">
			<?php foreach ( $alphabet as $letter ) : ?>
				<button
					class="c-city-directory__letter<?php echo ( 'All' === $letter ) ? ' -active' : ''; ?>"
					data-letter="<?php echo esc_attr( $letter ); ?>"
					aria-pressed="<?php echo ( 'All' === $letter ) ? 'true' : 'false'; ?>"
				>
					<?php echo esc_html( $letter ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="c-city-directory__grid c-city-directory__grid--<?php echo esc_attr( $card_layout ); ?>" role="list">
			<?php foreach ( $cities as $index => $city ) : ?>
				<?php
				$city_name  = $city['name'] ?? '';
				$city_desc  = $city['description'] ?? '';
				$city_link  = $city['link'] ?? '#';
				$image_url  = $city['media']['url'] ?? '';
				$image_alt  = $city['media']['alt'] ?? $city_name;
				$first_char = strtoupper( mb_substr( $city_name, 0, 1 ) );
				$hidden     = ( $index >= $items_per_page ) ? ' style="display:none"' : '';
				?>
				<div
					class="c-city-directory__card c-city-directory__card--<?php echo esc_attr( $card_layout ); ?>"
					data-city-name="<?php echo esc_attr( $city_name ); ?>"
					data-letter="<?php echo esc_attr( $first_char ); ?>"
					role="listitem"
					<?php echo $hidden; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
					<?php if ( 'vertical' === $card_layout ) : ?>
						<div class="c-city-directory__card-image c-city-directory__card-image--vertical">
							<?php if ( ! empty( $image_url ) ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" />
							<?php else : ?>
								<div class="c-city-directory__image-placeholder"></div>
							<?php endif; ?>
						</div>
						<div class="c-city-directory__card-footer">
							<?php if ( ! empty( $city_link ) && '#' !== $city_link ) : ?>
								<a href="<?php echo esc_url( $city_link ); ?>" class="c-city-directory__card-name c-city-directory__card-name--vertical">
									<?php echo wp_kses_post( $city_name ); ?>
								</a>
							<?php else : ?>
								<span class="c-city-directory__card-name c-city-directory__card-name--vertical">
									<?php echo wp_kses_post( $city_name ); ?>
								</span>
							<?php endif; ?>
							<span class="c-city-directory__card-arrow">›</span>
						</div>
					<?php else : ?>
						<div class="c-city-directory__card-image">
							<?php if ( ! empty( $image_url ) ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" />
							<?php endif; ?>
						</div>
						<div class="c-city-directory__card-body">
							<h3 class="c-city-directory__card-name"><?php echo wp_kses_post( $city_name ); ?></h3>
							<p class="c-city-directory__card-description"><?php echo wp_kses_post( $city_desc ); ?></p>
							<?php if ( ! empty( $city_link ) && '#' !== $city_link ) : ?>
								<a href="<?php echo esc_url( $city_link ); ?>" class="c-city-directory__card-link">
									<?php echo esc_html( $discover_more_text ); ?> &rsaquo;
								</a>
							<?php else : ?>
								<span class="c-city-directory__card-link">
									<?php echo esc_html( $discover_more_text ); ?> &rsaquo;
								</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( count( $cities ) > $items_per_page ) : ?>
			<div class="c-city-directory__view-more-wrap">
				<button class="c-city-directory__view-more" data-shown="<?php echo esc_attr( $items_per_page ); ?>">
					<?php echo esc_html( $view_more_text ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
</div>
