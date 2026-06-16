<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'pagination_type'     => get_field( 'single_post_pagination_type', 'options' ),
		'related_posts_title' => get_field( 'single_post_related_posts_title', 'options' ),
		'taxonomy'            => '',
	)
);
if ( 'related_posts' == $args['pagination_type'] && taxonomy_exists( $args['taxonomy'] ) ) : ?>

	<?php
	global $post;
	$related_posts = get_posts(
		array(
			'post_type'    => $post->post_type,
			'tax_query'    => array(
				array(
					'taxonomy' => $args['taxonomy'],
					'field'    => 'term_id',
					'terms'    => wp_get_post_terms( $post->ID, $args['taxonomy'], array( 'fields' => 'ids' ) ),
				),
			),
			'numberposts'  => 3,
			'post__not_in' => array( $post->ID ),
		)
	);

	if ( $related_posts ) :
		?>
		<div class="m-posts gt gb">
			<div class="container">
				<?php if ( ! empty( $args['related_posts_title'] ) ) : ?>
					<div class="l-heading text-center">
						<div class="c-heading -h2">
							<h3 class="c-heading__title"><?php echo esc_html( $args['related_posts_title'] ); ?></h3>
						</div>
					</div>
				<?php endif; ?>
				<div class="l-rcbl l-posts">
					<?php
					//phpcs:ignore
					foreach ( $related_posts as $post ) :
						setup_postdata( $post );
						?>
						<div class="l-rcbl__col">
							<?php get_template_part( 'templates/content/content-archive' ); ?>
						</div>
					<?php
					endforeach;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

<?php elseif ( 'next_prev_links' == $args['pagination_type'] ) : ?>

	<div class="content-single__pagination container bottom">
		<?php
		$prev_post = get_adjacent_post( false, '', true, $args['taxonomy'], );
		if ( ! empty( $prev_post ) ) :
			?>
			<a href="<?php echo esc_url( get_permalink( $prev_post->ID ) ); ?>" class="c-btn -primary cta_1 -small has-icon icon-left prev">
				<span class="c-btn__txt">
					<?php esc_html_e( 'Previous', 'dstheme' ); ?>
				</span>
				<span class="c-btn__ico icon-reversed">
					<?php
					echo get_svg(
						array(
							'icon'  => 'lib-icon-arrow2',
							'class' => '',
						)
					);
					?>
				</span>
			</a>
		<?php endif; ?>

		<?php
		$next_post = get_adjacent_post( false, '', false, $args['taxonomy'] );
		if ( ! empty( $next_post ) ) :
			?>
			<a href="<?php echo esc_url( get_permalink( $next_post->ID ) ); ?>" class="c-btn -primary cta_1 -small -standard has-icon icon-right next">
				<span class="c-btn__txt">
					<?php esc_html_e( 'Next', 'dstheme' ); ?>
				</span>
				<span class="c-btn__ico">
					<?php
					echo get_svg(
						array(
							'icon'  => 'lib-icon-arrow2',
							'class' => '',
						)
					);
					?>
				</span>
			</a>
		<?php endif; ?>
	</div>

<?php endif; ?>
