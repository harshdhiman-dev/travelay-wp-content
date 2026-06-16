<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
	'list'                => get_field( 'content_tabs' ),
	'columns_order'       => get_field( 'layout_settings_columns_order' ) ?: 'default',
	'layout'              => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'nav_layout'          => get_field( 'layout_settings_nav_layout_type' ) ?: 'v1',
	'wrapper_layout'      => get_field( 'layout_settings_wrapper_layout_type' ) ?: 'v1',
	'transform_on_mobile' => get_field( 'tabs_component_settings_transform_on_mobile' ) ?: false,
	'scroll_to_view'      => get_field( 'tabs_component_settings_data_scroll_to_view' ) ?: false,
	'accordion_display'   => get_field( 'tabs_component_settings_data_display' ) ?: false,
	'block_id'            => $block['id'],
	'container'           => $moduleConfig->container,
);

$tabsPanelClassName = '';
if ( $args['container'] === 'container-fluid' ) {
	$tabsPanelClassName = ' l-tabs--fluid';
}

$tabsJsHandler  = 'js-tabs-wrapper';
$tabsAttributes = '';
if ( $args['transform_on_mobile'] === 'accordion' ) {
	$tabsJsHandler = 'js-tabs-to-acc-wrapper';
	if ( ! empty( $args['scroll_to_view'] ) && $args['scroll_to_view'] ) {
		$tabsAttributes .= 'data-scroll-to-view="true"';
	}
	if ( ! empty( $args['accordion_display'] ) && $args['accordion_display'] !== 'flex' ) {
		$tabsAttributes .= 'data-acc-display="' . $args['accordion_display'] . '"';
	}
} elseif ( $args['transform_on_mobile'] === 'dropdown' ) {
	$tabsJsHandler = 'js-tabsTabDrop-wrapper';
}

$navLayoutClassName = '';
if ( $args['nav_layout'] === 'vTimeline' ) {
	$timelineType       = get_field( 'layout_settings_timeline_type' ) ?: 'circles';
	$timelineWidth      = get_field( 'layout_settings_timeline_width' ) ?: 'full';
	$timelineCircleType = get_field( 'layout_settings_timeline_circle_type' ) ?: 'above';

	$navLayoutClassName .= " -{$timelineType} -{$timelineWidth} -{$timelineCircleType}";
}
?>
<div class="m-tabs<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-tabs__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="m-tabs__inner">

			<?php if ( ! empty( $args['list'] ) ) : ?>
				<div class="l-tabs-wrapper <?php echo "l-tabs-wrapper-{$args['wrapper_layout']}"; ?> <?php echo $tabsJsHandler; ?>"<?php echo $tabsAttributes; ?>>
					<div class="l-tbnav <?php echo "l-tbnav-{$args['nav_layout']}"; ?><?php echo esc_attr( $navLayoutClassName ); ?>" role="tablist">
						<?php
						$selectOptions = array();
						foreach ( $args['list'] as $key => $item ) :
							$tab_id = "{$args['block_id']}-{$key}";
							?>
							<?php
							get_template_part(
								'templates/components/tabs-nav/nav-v1',
								null,
								array(
									'class'  => ( $key === 0 ? 'is-active' : '' ),
									'icon'   => $item['icon'],
									'title'  => $item['title_nav'],
									'tab_id' => $tab_id,
								)
							);
							?>
							<?php
							$selectOptions[] = '<option value="' . $tab_id . '" ' . ( $key === 0 ? 'selected' : '' ) . '>' . ( ! empty( $item['title_nav'] ) ? $item['title_nav'] : __( "Option - {$key}", 'dstheme' ) ) . '</option>';
						endforeach;
						?>

						<?php if ( $args['transform_on_mobile'] === 'dropdown' ) : ?>
							<select class="js-tabs-dropdown">
								<?php echo implode( '', $selectOptions ); ?>
							</select>
						<?php endif; ?>
					</div>

					<div class="l-tbpanel <?php echo "l-tbpanel-{$args['layout']}"; ?><?php echo $tabsPanelClassName; ?> <?php echo "order-{$args['columns_order']}"; ?>">
						<?php
						foreach ( $args['list'] as $key => $item ) :
							$tab_id         = "{$args['block_id']}-{$key}";
							$item['class']  = $key === 0 ? 'is-active' : '';
							$item['class'] .= $args['transform_on_mobile'] === 'accordion' ? ' js-ta-content' : '';
							?>
							<div class="l-tbpanel__item js-tabs-panel l-dcbl l-dcbl-v1 <?php echo $key === 0 ? 'is-active' : ''; ?>"
								aria-hidden="<?php echo $key === 0 ? 'false' : 'true'; ?>" aria-labelledby="data-tab-<?php echo $tab_id; ?>"
								id="data-tab-<?php echo $tab_id; ?>" role="tabpanel">
								<?php if ( $args['transform_on_mobile'] === 'accordion' ) : ?>
									<div class="l-tbpanel__label js-tabs-label">
										<?php echo $item['title_nav']; ?>
										<span class="l-tbpanel__label-icon"></span>
									</div>
								<?php endif; ?>
								<?php get_template_part( 'templates/components-shared/blocks/block-v2', null, $item ); ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

		</div>
	</div>
</div>
