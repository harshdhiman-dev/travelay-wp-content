<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'terms_list' => '',
		'image_size' => 'full',
	)
);

$_pid                   = get_the_ID();
$post_thumbnail_data    = wp_get_attachment_image_src( get_post_thumbnail_id(), $args['image_size'] );
$post_thumbnail_caption = get_the_post_thumbnail_caption( $_pid );
?>
<div class="article-heading -v1">
    <div class="article-heading__txt">
		<?php the_title( '<h1 class="article-heading__title">', '</h1>' ); ?>
    </div>
    <figure class="article-heading__img">
        <img src="<?php echo esc_url( ds_get_the_post_thumbnail_url( $_pid, $args['image_size'] ) ); ?>" alt="<?php echo esc_attr( get_the_title( $_pid ) ); ?>" width="<?php echo esc_attr( $post_thumbnail_data[1] ?? 410 ); ?>" height="<?php echo esc_attr( $post_thumbnail_data[2] ?? 315 ); ?>">
		<?php if ( ! empty( $post_thumbnail_caption ) ) : ?>
            <figcaption><?php echo esc_html( $post_thumbnail_caption ); ?></figcaption>
		<?php endif; ?>
    </figure>
</div>
