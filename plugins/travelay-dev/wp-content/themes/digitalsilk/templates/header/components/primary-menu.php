<?php if ( has_nav_menu( 'primary-menu' ) ) :
	$desktop_burger_menu = get_sub_field( 'desktop_burger_menu' );
	$menu_class          = ( ! empty( $desktop_burger_menu ) && 'enable' === $desktop_burger_menu[0] ) ? 'nav-main__links desktop-burger' : 'nav-main__links'; ?>
	<?php echo $desktop_burger_menu ? '<div class="site-header__burger">' : ''; ?>
	<?php if ( $desktop_burger_menu ) : ?>
		<button class="nav-main__desktop-btn js-d-burger-toggle" aria-label="Menu" aria-expanded="false" role="button">
			<span class="d-burger-icon">
				<span class="d-burger-line d-burger-line-1"></span>
				<span class="d-burger-line d-burger-line-2"></span>
				<span class="d-burger-line d-burger-line-3"></span>
			</span>
		</button>
	<?php endif; ?>
	<div class="nav-main__wrap <?php echo $desktop_burger_menu ? 'nav-main__wrap-burger js-d-burger-wrap' : ''; ?>">
		<nav class="nav-main" aria-label="Main Menu">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary-menu',
					'container'      => 'ul',
					'menu_class'     => $menu_class,
				)
			);
			?>
		</nav>
	</div>
	<?php echo $desktop_burger_menu ? '</div>' : ''; ?>
<?php endif; ?>
