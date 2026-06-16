<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
    'columns'        => get_field( 'layout_settings_card_columns' ) ?: 3,
    'layout'         => get_field( 'layout_settings_layout_type' ) ?: 'v1',
    'gap'            => get_field( 'layout_settings_card_gap' ) ?: 0,
    'gap_vertical'   => get_field( 'layout_settings_card_gap_vertical' ) ?: 0,
    'gap_horizontal' => get_field( 'layout_settings_card_gap_horizontal' ) ?: 0,
);
?>
<div class="m-counter<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

    <?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

    <?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

    <div class="m-counter__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
        <div class="l-rcbl l-counter" style="--l-block__col: <?php echo $args['columns']; ?>; --l-block__gap:<?php echo "{$args['gap']}px"; ?>;--l-block__padding-block: <?php echo $args['gap_vertical']; ?>px;--l-block__padding-inline: <?php echo $args['gap_horizontal']; ?>px;">

            <?php if ( have_rows( 'counter_widget' ) ) : ?>

                <?php
                while ( have_rows( 'counter_widget' ) ) :
the_row();
?>

                    <?php get_template_part( 'templates/components/counters/counter-v1' ); ?>

                <?php endwhile; ?>

            <?php endif; ?>

        </div>
    </div>
</div>
