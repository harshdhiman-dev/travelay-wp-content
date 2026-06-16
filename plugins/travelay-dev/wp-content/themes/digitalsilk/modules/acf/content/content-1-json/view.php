<?php
/**
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var DS_ModuleDefaultSettings $module_config ->get_styles(), ->data_attributes, ->container, ->container_width.
 */

if ( 0 !== (int) $module_config->layout_settings_columns_ratio ) {
	$module_config->set_style( '--columns-ratio', "{$module_config->layout_settings_columns_ratio}%" );
}

$args = array(
	'content_type'   => get_field( 'content_type' ),
	'post_type'      => get_field( 'post_type_data_post_type' ),
	'posts_per_page' => get_field( 'post_type_data_posts_per_page' ) ?: 3,
	'columns'        => get_field( 'layout_settings_card_columns' ) ?: 3,
	'gap_vertical'   => get_field( 'layout_settings_card_gap_vertical' ) ?: 20,
	'gap_horizontal' => get_field( 'layout_settings_card_gap_horizontal' ) ?: 20,
);

$class_obj        = new DS_ComponentSettings( $args );
$component_class  = $class_obj->class;
$component_styles = $class_obj->styles;
?>
<div
	class="m-block<?php echo esc_attr( $block['className'] ); ?> m-rcbl" <?php echo esc_attr( $module_config->data_attributes ); ?> <?php echo $module_config->get_styles(); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-block__container <?php echo esc_attr( $module_config->container ); ?>"
		 style="<?php echo $module_config->container_width; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">

		<InnerBlocks className="l-heading"
					 allowedBlocks="<?php echo esc_attr( wp_json_encode( $block['attributes']['allowedBlocks'] ) ); ?>"
					 template="<?php echo esc_attr( wp_json_encode( $block['attributes']['templateArr'] ) ); ?>"
		/>

		<div class="l-rcbl"
			 style="--l-block__col: <?php echo esc_attr( $args['columns'] ); ?>;--l-block__padding-block: <?php echo esc_attr( $args['gap_vertical'] ); ?>px;--l-block__padding-inline: <?php echo esc_attr( $args['gap_horizontal'] ); ?>px;">

			<?php if ( 'query' == $args['content_type'] || 'posts' == $args['content_type'] ) : ?>

				<?php
				$read_more_text = get_field( 'read_more' );

				$query_args = array(
					'post_type'      => $args['post_type'],
					'posts_per_page' => $args['posts_per_page'],
					'post_status'    => 'publish',
				);

				$selected_posts = get_field( 'posts' );
				if ( 'posts' == $args['content_type'] && ! empty( $selected_posts ) ) {
					$query_args['posts_per_page']      = count( $selected_posts );
					$query_args['post__in']            = $selected_posts;
					$query_args['ignore_sticky_posts'] = true;
				}

				$query = new WP_Query( $query_args );
				?>

				<?php if ( $query->have_posts() ) : ?>
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						?>

						<div class="l-rcbl__col">
							<?php
							$img_id = get_post_thumbnail_id( get_the_ID() );
							get_template_part(
								'templates/components-shared/blocks/block-v1',
								null,
								array(
									'image'          => array(
										'ID'    => $img_id,
										'url'   => ds_get_the_post_thumbnail_url(),
										'alt'   => trim( wp_strip_all_tags( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) ),
										'title' => get_the_title( $img_id ),
									),
									'image_size'     => 'ds_medium',
									'pretitle'       => get_field( 'pretitle', get_the_ID() ),
									'title'          => get_the_title(),
									'subtitle'       => get_field( 'subtitle', get_the_ID() ),
									// 'description' => get_field('description', get_the_ID()) ?? get_the_excerpt(),
									'cta_list'       => false,
									'is_clickable'   => true,
									'class'          => $component_class,
									'styles'         => $component_styles,
									'component_link' => array(
										'url'    => get_the_permalink(),
										'title'  => $read_more_text,
										'target' => '',
									),
								)
							);
							?>
						</div>

					<?php endwhile; ?>
				<?php
				endif;
				wp_reset_postdata();
				?>

			<?php
			elseif ( 'static' == $args['content_type'] && have_rows( 'cards_widget' ) ) :
				$badgeLabel = 1;
				?>

				<?php
				while ( have_rows( 'cards_widget' ) ) :
					the_row();
					?>
					<div class="l-rcbl__col">
						<?php
						get_template_part(
							'templates/components-shared/blocks/block-v1',
							null,
							array(
								'badge_label' => $badgeLabel,
								'class'       => $component_class,
								'styles'      => $component_styles,
							)
						);
						?>
					</div>
					<?php
					++ $badgeLabel;
				endwhile;
				?>

			<?php endif; ?>
		</div>

	</div>

	<?php get_template_part( 'templates/components/nav/scroll-down' ); ?>

</div>
