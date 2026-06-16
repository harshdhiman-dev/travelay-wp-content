<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'component_type' => get_field( 'component_settings_type' ) ?: 'v1',
		'image'          => get_sub_field( 'image' ),
        'image_size'     => 'ds_medium',
		'title'          => get_sub_field( 'title' ),
		'show_post_date' => get_field( 'component_settings_show_date' ) ?: false,
		'date'           => '',
		'category'       => '',
		'link'           => '',
		'class'          => '',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}
?>

<div class="c-block<?php echo esc_attr( $className ); ?>">
	<a class="c-block__link-full" href="<?php echo $args['link']; ?>"></a>

	<?php if ( ! empty( $args['image'] ) && ! empty( $args['image']['url'] ) ) : ?>
        <div class="c-block__media">
			<?php
            get_template_part(
                'templates/components/images/image-v1',
                null,
                array(
					'image'      => $args['image'],
					'image_size' => $args['image_size'],
                )
            );
			?>
        </div>
	<?php endif; ?>

    <div class="c-block__body">
		<?php if ( ! empty( $args['category'] ) ) : ?>
            <div class="c-block__tags">
                <a class="c-block__tag" href="<?php echo esc_url( get_category_link( $args['category']->term_id ) ); ?>"><?php echo esc_html( $args['category']->name ); ?></a>
            </div>
		<?php endif; ?>
		<?php if ( $args['show_post_date'] && ! empty( $args['date'] ) ) : ?>
            <div class="c-block__date"><?php echo $args['date']; ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $args['title'] ) ) : ?>
            <h4 class="c-block__title">
                <a href="<?php echo $args['link']; ?>"><?php echo $args['title']; ?></a>
            </h4>
		<?php endif; ?>
    </div>
</div>
