<?php
/**
 * Header Logo
 *
 * @package DS_Theme
 */

$logo = get_field( 'header_logo', 'options' ); ?>

<div class="site-header__widget">
	<?php if ( $logo ) : ?>
		<a href="<?php echo esc_url( home_url() ); ?>" class="site-header__logo" aria-label="<?php echo esc_attr( 'Logo linked to the home page of ' . get_bloginfo( 'name' ) ); ?>">
			<?php if ( ds_is_svg( $logo['url'] ) ) : ?>

				<?php echo ds_get_embedded_image( $logo['ID'], $logo['url'] ); //phpcs:ignore ?>

			<?php else : ?>

				<img width="200" height="50" src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"/>

			<?php endif; ?>
		</a>
	<?php endif; ?>
</div>
