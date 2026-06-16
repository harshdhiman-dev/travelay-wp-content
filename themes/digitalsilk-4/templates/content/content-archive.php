<?php
/**
 * Template for content archive
 *
 * @var array $args
 * @package DS_Theme
 */

$args = wp_parse_args(
	$args,
	array(
		'taxonomy'        => '',
		'display_term_id' => get_queried_object_id(),
		'image_size'      => 'medium_large',
	)
);

$post_thumbnail_data = wp_get_attachment_image_src( get_post_thumbnail_id(), $args['image_size'] );
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'blog-grid__item' ); ?>>
	<div class="c-block">
		<a href="<?php the_permalink(); ?>" class="c-block__link-full"></a>
		<div class="c-block__media">
			<figure class="c-media c-media__primary">
				<img class="c-media__src"
					 src="<?php echo esc_url( ds_get_the_post_thumbnail_url( get_the_ID(), $args['image_size'] ) ); ?>"
					 alt="<?php echo esc_attr( get_the_title() ); ?>"
					 width="<?php echo esc_attr( $post_thumbnail_data[1] ?? 410 ); ?>"
					 height="<?php echo esc_attr( $post_thumbnail_data[2] ?? 315 ); ?>">
			</figure>
		</div>

		<?php if ( taxonomy_exists( $args['taxonomy'] ) ) : ?>
			<?php
			$_term = ds_get_primary_taxonomy_term( $args['taxonomy'], get_the_ID(), $args['display_term_id'] );
			if ( ! empty( $_term ) ) :
				?>
				<div class="c-block__tags">
					<a class="c-block__tag"
					   href="<?php echo esc_url( get_term_link( $_term->term_id, $args['taxonomy'] ) ); ?>">
						<?php echo esc_html( $_term->name ); ?>
					</a>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="c-block__body">

			<?php if ( get_the_title() ) : ?>
				<h2 class="c-block__title">
					<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				</h2>
			<?php endif; ?>


			<div class="c-block__content">
				<?php if ( get_the_excerpt() ) : ?>
					<div class="c-block__description">
						<?php echo esc_html( ds_get_excerpt() ); ?>
					</div>
				<?php endif; ?>

				<a href="<?php the_permalink(); ?>" class="c-block__link"><?php esc_html_e( 'Read More', 'dstheme' ); ?><?php echo get_svg( array( 'icon' => 'lib-icon-arrow4' ) ); ?></a>
			</div>

		</div>
	</div>
</article>
