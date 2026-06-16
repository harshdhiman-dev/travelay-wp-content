<?php
/**
 * Shared admin hero header.
 *
 * @package TravelayCulturalWelcome
 *
 * @var string $tcw_hero_title
 * @var string $tcw_hero_tagline
 * @var string $tcw_hero_icon
 * @var array  $tcw_hero_chips
 * @var string $tcw_hero_actions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tcw_hero_icon    = $tcw_hero_icon ?? 'dashicons-welcome-view-site';
$tcw_hero_chips   = is_array( $tcw_hero_chips ?? null ) ? $tcw_hero_chips : array();
$tcw_hero_actions = $tcw_hero_actions ?? '';
?>
<header class="tcw-hero">
	<div class="tcw-hero__main">
		<div class="tcw-hero__icon" aria-hidden="true">
			<span class="dashicons <?php echo esc_attr( $tcw_hero_icon ); ?>"></span>
		</div>
		<div class="tcw-hero__text">
			<h1><?php echo esc_html( $tcw_hero_title ); ?></h1>
			<?php if ( ! empty( $tcw_hero_tagline ) ) : ?>
				<p class="tcw-hero__tagline"><?php echo esc_html( $tcw_hero_tagline ); ?></p>
			<?php endif; ?>
			<p class="tcw-hero__version"><?php echo esc_html( sprintf( __( 'Version %s', 'travelay-cultural-welcome' ), TCW_VERSION ) ); ?></p>
			<?php if ( $tcw_hero_actions ) : ?>
				<div class="tcw-hero__actions"><?php echo $tcw_hero_actions; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in caller ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( ! empty( $tcw_hero_chips ) ) : ?>
		<div class="tcw-hero__meta">
			<div class="tcw-stat-chips">
				<?php foreach ( $tcw_hero_chips as $chip ) : ?>
					<?php
					$chip_class = isset( $chip['class'] ) ? sanitize_html_class( $chip['class'] ) : '';
					$strong     = isset( $chip['strong'] ) ? (string) $chip['strong'] : '';
					$text       = isset( $chip['text'] ) ? (string) $chip['text'] : '';
					?>
					<span class="tcw-stat-chip <?php echo esc_attr( $chip_class ); ?>">
						<?php if ( '' !== $strong ) : ?>
							<strong><?php echo esc_html( $strong ); ?></strong>
						<?php endif; ?>
						<?php echo esc_html( $text ); ?>
					</span>
				<?php endforeach; ?>
			</div>
			<p class="tcw-hero__brand"><?php esc_html_e( 'Developed and Copyright Patent Travelay™', 'travelay-cultural-welcome' ); ?></p>
		</div>
	<?php else : ?>
		<div class="tcw-hero__meta">
			<p class="tcw-hero__brand"><?php esc_html_e( 'Developed and Copyright Patent Travelay™', 'travelay-cultural-welcome' ); ?></p>
		</div>
	<?php endif; ?>
</header>
