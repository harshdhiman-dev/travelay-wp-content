<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args          = array(
	'tabs'                => get_field( 'tabs' ),
	'columns'             => get_field( 'layout_settings_card_columns' ) ?: 4,
	'layout'              => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'nav_layout'          => get_field( 'layout_settings_nav_layout_type' ) ?: 'v1',
	'wrapper_layout'      => get_field( 'layout_settings_wrapper_layout_type' ) ?: 'v1',
	'transform_on_mobile' => get_field( 'tabs_component_settings_transform_on_mobile' ) ?: false,
	'gap_vertical'        => get_field( 'layout_settings_card_gap_vertical' ) ?: 0,
	'gap_horizontal'      => get_field( 'layout_settings_card_gap_horizontal' ) ?: 0,
	'button'              => get_field( 'cta_button' ),
	'button_styles'       => get_field( 'cta_button_styles' ),
	'block_id'            => $block['id'],
	'container'           => $moduleConfig->container,
);
$tabsJsHandler = 'js-tabs-wrapper';
if ( $args['transform_on_mobile'] === 'dropdown' ) {
	$tabsJsHandler = 'js-tabsTabDrop-wrapper';
}

if ( ! empty( $args['tabs'] ) ) :
	?>
<div class="m-tabs<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-tabs__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="m-tabs__inner">

			<div class="l-tabs-wrapper m-tbcards <?php echo $tabsJsHandler; ?>" <?php echo ( $args['use_anchors'] ) ? 'data-use-anchors="true"' : ''; ?>>
				<div class="m-tbcards__heading">
					<?php if ( count( $args['tabs'] ) > 1 ) : ?>
						<div class="l-tbnav" role="tablist">
							<?php
							$selectOptions = array();
							foreach ( $args['tabs'] as $key => $item ) :
								$item['class']  = $key === 0 ? 'is-active' : '';
								$item['title']  = $item['title_nav'];
								$item['tab_id'] = 'tabbed-cards-' . $key;
								?>
								<?php get_template_part( 'templates/components/tabs-nav/nav-v1', null, $item ); ?>
								<?php
								$selectOptions[] = '<option value="' . $item['tab_id'] . '" ' . ( $key === 0 ? 'selected' : '' ) . '>' . ( ! empty( $item['title_nav'] ) ? $item['title_nav'] : __( "Option - {$key}", 'dstheme' ) ) . '</option>';
							endforeach;
							?>

							<?php if ( $args['transform_on_mobile'] === 'dropdown' ) : ?>
								<select class="js-tabs-dropdown">
									<?php echo implode( '', $selectOptions ); ?>
								</select>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="l-tbpanel rcbl-v1">
					<?php
					foreach ( $args['tabs'] as $key => $tab ) :
						$tab['tab_id'] = 'tabbed-cards-' . $key;
						?>
						<div class="l-tbpanel__item l-tabs__panel js-tabs-panel <?php echo $key === 0 ? 'is-active' : ''; ?>"
							aria-hidden="<?php echo $key === 0 ? 'false' : 'true'; ?>" aria-labelledby="data-tab-<?php echo $tab['tab_id']; ?>"
							id="data-tab-<?php echo $tab['tab_id']; ?>" role="tabpanel">
							<div class="js-ta-content <?php echo $key === 0 ? 'is-active' : ''; ?>" style="--l-block__col: <?php echo $args['columns']; ?>;--l-block__padding-block: <?php echo $args['gap_vertical']; ?>px;--l-block__padding-inline: <?php echo $args['gap_horizontal']; ?>px">

								<?php
								get_template_part(
									'templates/components/headings/heading',
									null,
									array(
										'title'    => $tab['title'],
										'subtitle' => $tab['subtitle'],
									)
								);
								?>

								<?php
								get_template_part(
									'templates/components/cta-list',
									null,
									array(
										'buttons' => $tab['cta_list'],
									)
								);
								?>

								<div class="l-cards_grid l-rcbl l-rcbl-v1 <?php echo "l-cards_grid-{$args['layout']}"; ?>">
									<?php
									while ( have_rows( 'cards_widget' ) ) :
										the_row();
										?>
										<div class="l-rcbl__col">
											<?php
											get_template_part(
												'templates/components-shared/blocks/block-v1',
												null,
												array(
													'class' => '',
												)
											);
											?>
										</div>
									<?php endwhile; ?>

									<?php foreach ( $tab['cards_widget'] as $card ) : ?>
										<div class="l-rcbl__col">
											<?php
											get_template_part(
												'templates/components-shared/blocks/block-v1',
												null,
												array(
													'image'    => $card['image'],
													'pretitle' => $card['pretitle'],
													'title'    => $card['title'],
													'description' => $card['description'],
													'cta_list' => $card['cta_list'],
													'is_clickable' => $card['is_clickable'],
													'component_link' => $card['component_link'],
												)
											);
											?>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

		</div>
	</div>
</div>
	<?php
endif;
