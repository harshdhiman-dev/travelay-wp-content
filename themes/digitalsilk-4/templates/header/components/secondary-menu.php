<?php if ( has_nav_menu( 'secondary-menu' ) ) : ?>
	<div class="site-header__widget">
		<div class="nav-secondary">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'secondary-menu',
					'container'      => 'ul',
					'menu_class'     => 'nav-secondary__links',
				)
			);
			?>
		</div>
	</div>
<?php endif; ?>
