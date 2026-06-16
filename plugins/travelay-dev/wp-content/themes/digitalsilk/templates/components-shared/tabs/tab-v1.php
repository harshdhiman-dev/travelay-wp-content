<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'component_type' => get_field( 'component_settings_type' ) ?: 'v1',
		'title'          => get_sub_field( 'title' ),
		'description'    => get_sub_field( 'description' ),
		'main_image'     => get_sub_field( 'main_image' ),
		'front_image'    => get_sub_field( 'front_image' ),
		'cta_list'       => get_sub_field( 'cta_list' ),
		'tab_id'         => '',
		'class'          => '',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}
?>
<div class="c-tab c-tab-<?php echo esc_attr( $args['component_type'] ); ?><?php echo esc_attr( $className ); ?>">

	<?php if ( ! empty( $args['main_image'] ) || ! empty( $args['front_image'] ) ) : ?>
        <div class="c-tab__media">
			<?php
            get_template_part(
                'templates/components/images/image-gallery-v1',
                null,
                array(
					'main_image'  => $args['main_image'],
					'front_image' => $args['front_image'],
                )
			);
            ?>
        </div>
	<?php endif; ?>

    <div class="c-tab__content">
		<?php if ( ! empty( $args['title'] ) ) : ?>
            <h4 class="c-tab__title js-tab-item"><?php echo esc_html( $args['title'] ); ?></h4>
		<?php endif; ?>

		<?php if ( ! empty( $args['description'] ) ) : ?>
            <div class="c-tab__description"><?php echo wp_kses_post( $args['description'] ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $args['cta_list'] ) ) : ?>
			<?php
            get_template_part(
                'templates/components/cta-list',
                null,
                array(
					'buttons' => $args['cta_list'],
                )
			);
            ?>
		<?php endif; ?>
    </div>

</div>
