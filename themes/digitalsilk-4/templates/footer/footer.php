<?php
// phpcs:ignoreFile
$footer = get_field('footer', 'options');
$copyright = get_field('copyright', 'options');
$design_by = get_field('design_by', 'options');
?>

<?php get_template_part('templates/footer/components/background'); ?>

<?php if (!empty($footer)): ?>
	<div class="l-footer">

		<div class="footer-top">
			<div class="footer-top__inner container">

				<div class="footer-top__col -newsletter">
					<?php if (!empty($footer['footer_logo']['ID'])) : ?>
						<div class="footer-block">
							<?php echo ds_generate_image($footer['footer_logo']['ID'], 'ds_medium', 'footer-logo'); ?>
						</div>
					<?php endif; ?>

					<?php get_template_part('templates/footer/components/newsletter', null, array('newsletter' => $footer['newsletter'] ?? [])); ?>
				</div>

				<?php if (has_nav_menu('footer-menu')) : ?>
					<div class="footer-top__col">
						<div class="footer-block">
							<div class="footer-title"><?php echo wp_get_nav_menu_name('footer-menu'); ?></div>

							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'footer-menu',
									'container' => 'ul',
									'menu_class' => 'footer-nav v-direction',
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>

				<?php if (has_nav_menu('privacy-menu')) : ?>
					<div class="footer-top__col">
						<div class="footer-block">
							<div class="footer-title"><?php echo wp_get_nav_menu_name('privacy-menu'); ?></div>

							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'privacy-menu',
									'container' => 'ul',
									'menu_class' => 'footer-nav v-direction',
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>

				<?php if (has_nav_menu('footer-menu-3')) : ?>
					<div class="footer-top__col">
						<div class="footer-block">
							<div class="footer-title"><?php echo wp_get_nav_menu_name('footer-menu-3'); ?></div>

							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'footer-menu-3',
									'container' => 'ul',
									'menu_class' => 'footer-nav v-direction',
								)
							);
							?>
						</div>
					</div>
				<?php endif; ?>

				<?php if (!empty($footer['additional_content']['title'])) : ?>
					<div class="footer-back-title"><?php echo $footer['additional_content']['title']; ?></div>
				<?php endif; ?>

				<?php if (!empty($footer['additional_content']['back_title_image'])) : ?>
					<div class="footer-back-title-image">
						<?php get_template_part('templates/components/images/image-v1', null, array('image' => $footer['additional_content']['back_title_image'], 'full', 'back-title', true)); ?>
					</div>
				<?php endif; ?>

				<?php get_template_part('templates/components/socials'); ?>
			</div>
		</div>

		<div class="footer-bottom">
			<div class="footer-bottom__inner container">
				<?php if (has_nav_menu('footer-menu-4')) : ?>
					<div class="footer-nav js-menu" data-action-trigger="hover" data-init-active-index="0">
						<div class="footer-nav__btn js-menu-switcher" role="button" aria-expanded="false">
							<span class="burger-icon"><span><?php echo get_svg( array( 'icon' => 'burger-icon' ) ); ?></span></span>
						</div>
						<div class="footer-nav__links-wrapper">
							<?php wp_nav_menu(array(
								'theme_location' => 'footer-menu-4',
								'container' => 'ul',
								'menu_class' => 'footer-nav__links js-menu-links'
							)); ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if (!empty($copyright)) : ?>
					<div class="copyright"><?php echo do_shortcode($copyright); ?></div>
				<?php endif; ?>

				<?php if (!empty($design_by)) : ?>
					<div class="design-by"><?php echo $design_by; ?></div>
				<?php endif; ?>

				<?php get_template_part('templates/components/socials'); ?>

			</div>
		</div>

	</div>
<?php endif;
