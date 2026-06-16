<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
    'list'                     => get_field( 'content_tabs' ),
    'columns_order'            => get_field( 'layout_settings_columns_order' ) ?: 'default',
    'layout'                   => get_field( 'layout_settings_layout_type' ) ?: 'v1',
    'nav_layout'               => get_field( 'layout_settings_nav_layout_type' ) ?: 'v1',
    'wrapper_layout'           => get_field( 'layout_settings_wrapper_layout_type' ) ?: 'v1',
    'transform_on_mobile'      => get_field( 'tabs_component_settings_transform_on_mobile' ) ?: false,
    'orientation'              => get_field( 'tabs_component_settings_orientation' ) ?: 'horizontal',

    // accordion settings
    'data_animation'           => get_field( 'accordion_component_settings_data_animation' ) ?: 'css',
    'data_expanded'            => get_field( 'accordion_component_settings_data_expanded' ) ?: 'single',
    'data_close'               => get_field( 'accordion_component_settings_data_close' ) ?: false,
    'data_closed_at_start'     => get_field( 'accordion_component_settings_data_closed_at_start' ) ?: false,
    'scroll_to_view'           => get_field( 'accordion_component_settings_data_scroll_to_view' ) ?: false,
    'accordion_display'        => get_field( 'accordion_component_settings_data_display' ) ?: false,
    'component_gap_left'       => get_field( 'accordion_component_settings_inner_gap_left' ) ?: 0,
    'component_gap_right'      => get_field( 'accordion_component_settings_inner_gap_right' ) ?: 0,
    'component_gap_top'        => get_field( 'accordion_component_settings_inner_gap_top' ) ?: 0,
    'component_gap_bottom'     => get_field( 'accordion_component_settings_inner_gap_bottom' ) ?: 0,
    'has_border'               => get_field( 'accordion_component_settings_has_border' ) ?: false,
    'border_color'             => get_field( 'accordion_component_settings_border_color' ),
    'title_bg_color'           => get_field( 'accordion_component_settings_title_bg_color' ),
    'area_bg_color'            => get_field( 'accordion_component_settings_area_bg_color' ),
    'icon_styles'              => get_field( 'accordion_component_settings_icon_styles' ),
    'accordion_component_type' => 'v1',
    'block_id'                 => $block['id'],
    'container'                => $moduleConfig->container,
);

$tabsPanelClassName = '';
if ( $args['container'] === 'container-fluid' ) {
    $tabsPanelClassName = ' l-tabs--fluid';
}

$tabsJsHandler = 'js-tabs-wrapper';
$tabsAttributes = '';
if ( $args['transform_on_mobile'] === 'accordion' ) {
    $tabsJsHandler = 'js-tabs-to-acc-wrapper';
    if ( ! empty( $args['scroll_to_view'] ) && $args['scroll_to_view'] ) {
        $tabsAttributes .= 'data-scroll-to-view="true"';
    }
    if ( ! empty( $args['accordion_display'] ) ) {
        $tabsAttributes .= 'data-acc-display="' . $args['accordion_display'] . '"';
    }
} elseif ( $args['transform_on_mobile'] === 'dropdown' ) {
    $tabsJsHandler = 'js-tabsTabDrop-wrapper';
}

// Accordion Options Bellow
$accordionComponentData = '';
if ( $args['data_animation'] === 'js' ) {
    $accordionComponentData .= ' data-animation="js"';
}
if ( $args['data_expanded'] === 'all' ) {
    $accordionComponentData .= ' data-expand="true"';
}
if ( $args['data_close'] ) {
    $accordionComponentData .= ' data-close="true"';
}
if ( $args['data_closed_at_start'] ) {
    $accordionComponentData .= 'data-start-closed="true"';
}

$accordionComponentStyles = "--c-block-gl: {$args['component_gap_left']}px;--c-block-gr: {$args['component_gap_right']}px;--c-block-gt: {$args['component_gap_top']}px;--c-block-gb: {$args['component_gap_bottom']}px;";

if ( ! empty( $args['border_color'] ) ) {
    $accordionComponentStyles .= "--c-block-border-color:{$args['border_color']};";
}
if ( ! empty( $args['title_bg_color'] ) ) {
    $accordionComponentStyles .= "--c-block-title-bg-color:{$args['title_bg_color']};";
}
if ( ! empty( $args['area_bg_color'] ) ) {
    $accordionComponentStyles .= "--c-block-text-bg-color:{$args['area_bg_color']};";
}

$accordionComponentCLassNames = '';
if ( ! empty( $args['icon_styles'] ) ) {
    $accordionComponentCLassNames .= " {$args['icon_styles']}";
}
?>
<div class="m-tabs<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

    <?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

    <?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

    <div class="m-tabs__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
        <div class="m-tabs__inner">

            <?php if ( ! empty( $args['list'] ) ) : ?>
                <div class="l-tabs-wrapper <?php echo "l-tabs-wrapper-{$args['wrapper_layout']}"; ?> <?php echo "is-{$args['orientation']}"; ?> <?php echo $tabsJsHandler; ?> is-block" <?php echo $tabsAttributes; ?>>
                    <div class="l-tbnav <?php echo "l-tbnav-{$args['nav_layout']}"; ?>" role="tablist">
                        <?php
                        $selectOptions = [];
                        foreach ( $args['list'] as $key => $item ) :
$tab_id = "{$args['block_id']}-{$key}";
                        ?>
                            <?php
                            get_template_part(
                                'templates/components/tabs-nav/nav-v1',
                                null,
                                array(
									'class'  => ( 0 === $key ? 'is-active' : '' ),
									'icon'   => $item['icon'],
									'title'  => $item['title_nav'],
									'tab_id' => $tab_id,
                                )
                            );
                            ?>
                            <?php
                            $selectOptions[] = '<option value="' . $tab_id . '" ' . ( 0 === $key ? 'selected' : '' ) . '>' . ( ! empty( $item['title_nav'] ) ? $item['title_nav'] : __( "Option - {$key}", 'dstheme' ) ) . '</option>';
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
                            $tab_id = "{$args['block_id']}-{$key}";
                            $itemClass = $args['transform_on_mobile'] === 'accordion' ? ' js-ta-content' : '';
                            ?>
                            <div class="l-tbpanel__item js-tabs-panel<?php echo 0 === $key ? ' is-active' : ''; ?>" aria-hidden="<?php echo 0 === $key ? 'false' : 'true'; ?>" aria-labelledby="data-tab-<?php echo $tab_id; ?>" id="data-tab-<?php echo $tab_id; ?>" role="tabpanel">
                                <?php if ( $args['transform_on_mobile'] === 'accordion' ) : ?>
                                    <div class="l-tbpanel__label js-tabs-label">
                                        <?php echo $item['title_nav']; ?>
                                        <span class="l-tbpanel__label-icon"></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ( ! empty( $item['faq_list'] ) ) : ?>
                                    <div class="l-accordion l-accordion-v1<?php echo $itemClass; ?>">
                                        <div class="c-accordion c-accordion-<?php echo $args['accordion_component_type']; ?> js-acc-wrapper<?php echo $accordionComponentCLassNames; ?>" style="<?php echo $accordionComponentStyles; ?>" <?php echo $accordionComponentData; ?>>
                                            <?php
                                            $count = 0;
                                            foreach ( $item['faq_list'] as $i => $faq ) :
                                            ?>

                                                <?php
                                                get_template_part(
                                                    'templates/components-shared/accordion/accordion',
                                                    $args['accordion_component_type'],
                                                    array(
														'accordion_id' => "{$tab_id}-$i",
														'title'        => $faq->post_title,
														'description'  => apply_filters( 'the_content', $faq->post_content ),
														'class'        => ( $i === 0 && ! $args['data_closed_at_start'] ? ' is-active' : '' ),
                                                    )
                                                );
                                                ?>

                                                <?php
                                                $count ++;
                                            endforeach;
                                            ?>

                                            <?php if ( ! empty( $item['cta_list'] ) ) : ?>
                                                <?php
                                                get_template_part(
                                                    'templates/components/cta-list',
                                                    null,
                                                    array(
														'buttons' => $item['cta_list'],
                                                    )
                                                );
                                                ?>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
