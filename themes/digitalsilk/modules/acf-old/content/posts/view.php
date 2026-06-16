<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
	'columns'    => get_field( 'layout_settings_card_columns' ) ?: 4,
	'layout'     => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'gap'        => get_field( 'layout_settings_card_gap' ) ?: 0,
	'query_type' => get_field( 'query_type' ),
	'posts_list' => get_field( 'posts_list' ) ?: array(),
);
?>
<div class="m-block m-posts<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-posts__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="m-posts__inner">

			<div class="l-rcbl l-posts" style="--l-block__col: <?php echo $args['columns']; ?>; --l-block__gap: <?php echo $args['gap']; ?>px">

				<?php if ( $args['query_type'] == 'recent' ) : ?>

					<?php
					$query = new WP_Query(
						array(
							'post_type'      => 'post',
							'posts_per_page' => $args['columns'],
						)
					);
					?>

					<?php if ( $query->have_posts() ) : ?>
						<?php
						while ( $query->have_posts() ) :
							$query->the_post();
							$post_categories = get_the_category( get_the_ID() );
							$img_id          = get_post_thumbnail_id( get_the_ID() )
							?>
							<div class="l-rcbl__col">
								<?php
								get_template_part(
									'templates/components-shared/posts/post-v1',
									null,
									array(
										'image'      => array(
											'ID'    => $img_id,
											'url'   => ds_get_the_post_thumbnail_url(),
											'alt'   => trim( strip_tags( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) ),
											'title' => $img_id ? get_the_title( $img_id ) : get_the_title(),
										),
										'image_size' => 'medium_large',
										'title'      => get_the_title( get_the_ID() ),
										'link'       => get_permalink( get_the_ID() ),
										'date'       => get_the_date( 'F d, Y', get_the_ID() ),
										'category'   => ! empty( $post_categories ) ? $post_categories[0] : array(),
									)
								);
								?>
							</div>
						<?php endwhile; ?>
						<?php
					endif;
					wp_reset_postdata();
					?>

				<?php elseif ( ! empty( $args['posts_list'] ) ) : ?>

					<?php
					foreach ( $args['posts_list'] as $post_id ) :
						$post_categories = get_the_category( $post_id );
						$img_id          = get_post_thumbnail_id( $post_id )
						?>
						<div class="l-rcbl__col">
							<?php
							get_template_part(
								'templates/components-shared/posts/post-v1',
								null,
								array(
									'image'      => array(
										'ID'    => $img_id,
										'url'   => ds_get_the_post_thumbnail_url( $post_id ),
										'alt'   => trim( strip_tags( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) ),
										'title' => $img_id ? get_the_title( $img_id ) : get_the_title( $post_id ),
									),
									'image_size' => 'medium_large',
									'title'      => get_the_title( $post_id ),
									'link'       => get_permalink( $post_id ),
									'date'       => get_the_date( 'F d, Y', $post_id ),
									'category'   => ! empty( $post_categories ) ? $post_categories[0] : array(),
								)
							);
							?>
						</div>
					<?php endforeach; ?>

				<?php endif; ?>

			</div>

		</div>
	</div>
</div>
