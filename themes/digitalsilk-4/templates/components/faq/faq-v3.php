<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */
$args = wp_parse_args(
    $args,
    array(
        'component_type' => get_field( 'component_settings_type' ) ?: 'v1',
        'title'          => get_sub_field( 'title' ),
        'description'    => get_sub_field( 'description' ),
        'main_image'     => get_sub_field( 'main_image' ),
        'front_image'    => get_sub_field( 'front_image' ),
        'cta_list'       => get_sub_field( 'cta_list' ),
        'accordion_id'   => '',
        'class'          => '',
        'active'         => '',
    )
);
$className = '';
if ( ! empty( $args['class'] ) ) {
    $className = " {$args['class']}";
}

?>
    <div class="c-faq__item js-acc-item <?php echo $args['active']; ?>">
        <?php if ( ! empty( $args['title'] ) ) : ?>
            <h4 class="c-faq__title js-acc-button"><?php echo $args['title']; ?></h4>
        <?php endif; ?>

        <?php if ( ! empty( $args['description'] ) ) : ?>
            <div class="c-faq__content js-acc-content"><?php echo $args['description']; ?></div>
        <?php endif; ?>

    </div>
