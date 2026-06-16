<?php
/**
 * Template part for testimonial photo
 *
 * @var $args
 *
 * @package DS_Theme
 */

$args           = wp_parse_args(
	$args,
	array(
		'profile_picture_id' => get_post_thumbnail_id(),
		'is_rounded_image'   => get_field( 'media_component_settings_is_rounded' ) ?: false, //phpcs:ignore
		'title'              => get_the_title(),
		'is_slider_thumbs'   => false,
		'class'              => '',
	)
);
$css_class_name = '';
if ( ! empty( $args['class'] ) ) {
	$css_class_name .= " {$args['class']}";
}

if ( $args['is_rounded_image'] ) {
	$css_class_name .= ' is-img-rounded';
}

if ( $args['is_slider_thumbs'] ) {
	$css_class_name .= ' swiper-slide';
}
?>
<div class="c-quote__photo <?php echo esc_attr( $css_class_name ); ?>">
	<?php if ( ! empty( $args['profile_picture_id'] ) ) : ?>
		<?php echo wp_get_attachment_image( $args['profile_picture_id'], 'full' ); ?>
	<?php else : ?>
		<img class="c-media__src" loading="lazy" src="<?php echo ds_placeholder(); //phpcs:ignore?>" alt="<?php echo esc_attr( $args['title'] ); ?>" width="84" height="84">
	<?php endif; ?>
</div>

