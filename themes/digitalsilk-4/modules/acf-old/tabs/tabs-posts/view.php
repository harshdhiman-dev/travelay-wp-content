<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args         = array(
	'post_categories' => get_field( 'post_categories' ),
	'columns'         => get_field( 'layout_settings_card_columns' ) ?: 3,
	'layout'          => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'nav_layout'      => get_field( 'layout_settings_nav_layout_type' ) ?: 'v1',
	'wrapper_layout'  => get_field( 'layout_settings_wrapper_layout_type' ) ?: 'v1',
	'gap'             => get_field( 'layout_settings_card_gap' ) ?: 0,
	'block_id'        => $block['id'],
	'container'       => $moduleConfig->container,
);
$tabsPrefixId = $args['block_id'];
$tabsItems    = array();

if ( ! empty( $args['post_categories'] ) ) {
	$queryArgs = array(
		'post_type'   => 'post',
		'post_status' => 'publish',
		'numberposts' => 3,
		'fields'      => 'ids',
	);

	$allPosts = get_posts( $queryArgs );
	if ( ! empty( $allPosts ) ) {
		$tabsItems[] = array(
			'tab_id' => $tabsPrefixId . '-all',
			'title'  => 'All',
			'items'  => $allPosts,
		);
	}

	foreach ( $args['post_categories'] as $cat ) {
		$queryArgs['category'] = $cat->term_id;
		$catPosts              = get_posts( $queryArgs );

		if ( empty( $catPosts ) ) {
			continue;
		}

		$tabsItems[] = array(
			'tab_id' => $tabsPrefixId . '-' . htmlspecialchars( $cat->slug ),
			'title'  => $cat->name,
			'items'  => $catPosts,
		);
	}
}
?>
<div class="m-tabs<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-tabs__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="m-tabs__inner">

			<?php if ( ! empty( $tabsItems ) ) : ?>
				<div class="l-tabs-wrapper <?php echo "l-tabs-wrapper-{$args['wrapper_layout']}"; ?> js-tabs-to-acc-wrapper">
					<div class="l-tbnav <?php echo "l-tbnav-{$args['nav_layout']}"; ?>" role="tablist">
						<?php
						foreach ( $tabsItems as $key => $item ) :
							$item['class'] = 0 === $key ? 'is-active' : '';
							?>
							<?php get_template_part( 'templates/components/tabs-nav/nav-v1', null, $item ); ?>
						<?php endforeach; ?>
					</div>

					<div class="l-tbpanel <?php echo "l-tbpanel-{$args['layout']}"; ?>">
						<?php foreach ( $tabsItems as $key => $tab ) : ?>
							<div class="l-tbpanel__item l-tabs__panel js-tabs-panel <?php echo 0 === $key ? 'is-active' : ''; ?>" aria-hidden="<?php echo 0 === $key ? 'false' : 'true'; ?>" aria-labelledby="data-tab-<?php echo $tab['tab_id']; ?>" id="data-tab-<?php echo $tab['tab_id']; ?>" role="tabpanel">
								<div class="l-tbpanel__label js-tabs-label"><?php echo $tab['title']; ?></div>
								<div class="l-posts l-rcbl js-ta-content <?php echo 0 === $key ? 'is-active' : ''; ?>" style="<?php echo "--l-block__col: {$args['columns']}"; ?>">
									<?php
									foreach ( $tab['items'] as $post_id ) :
										$img_id = get_post_thumbnail_id( $post_id );
										?>
										<div class="l-rcbl__col">
											<?php
											get_template_part(
												'templates/components-shared/posts/post-v1',
												null,
												array(
													'image' => array(
														'url' => wp_get_attachment_url( $img_id ),
														'alt' => trim( strip_tags( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) ),
														'title' => get_the_title( $img_id ),
													),
													'title' => get_the_title( $post_id ),
													'link' => get_permalink( $post_id ),
												)
											);
											?>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</div>
	</div>
</div>
