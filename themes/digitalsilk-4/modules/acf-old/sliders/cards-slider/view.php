<?php
/**
 * Slider Cards Block Template.
 *
 * @var array $block The block settings and attributes.
 * @var DS_ModuleSlider $moduleSlider The slider module common settings and attributes.
 * @var DS_ModuleDefaultSettings $moduleConfig The module common styles, classes and attributes.
 */
// phpcs:ignoreFile

$args = array(
	'layout'                    => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'content_type'              => get_field( 'content_type' ),
	'query_type'                => get_field( 'post_type_data_query_type' ) ?: 'latest_posts',
	'post_type'                 => get_field( 'post_type_data_post_type' ),
	'filter_tabs'               => get_field( 'post_type_data_filter_tabs' ),
	'filter_categories'         => get_field( 'post_type_data_filter_categories' ),
	'selected_posts'            => get_field( 'post_type_data_select_posts' ) ?: [],
	'posts_per_page'            => get_field( 'post_type_data_posts_per_page' ) ?: 6,
	'post_type_button_text'     => get_field( 'post_type_data_button_text' ) ?: 'Read Full Article',
	'post_type_is_clickable'    => get_field( 'post_type_data_is_clickable' ) ?: false,
	'post_type_data_cta_button' => get_field( 'post_type_data_cta_button_styles' ) ?: [],
);

$componentSettings = new DS_ComponentSettings( $args );
$componentClass    = $componentSettings->class;
$componentStyles   = $componentSettings->styles;
?>
<div class="m-slider slider-cards <?php echo esc_attr( $block['className'] ); ?> <?php echo $moduleSlider->classNames; ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<div class="m-slider__outer <?php echo $moduleConfig->container; ?> ">

		<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

		<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

		<?php get_template_part( 'templates/components/headings/heading-cta' ); ?>

	    <div class="m-slider__inner">

			<?php get_template_part( 'templates/components/nav/slider-filter-tabs' ); ?>

	        <div class="m-slider__container swiper js-slider-simple <?php echo ( 'enable' == get_field( 'post_type_data_filter_tabs' ) ) ? 'slider-filter-tabs' : ''; ?>" <?php echo $moduleSlider->data_attributes; ?>>
	            <div class="m-slider__wrapper swiper-wrapper">
					<?php if ( $args['content_type'] == 'post_type' ) : ?>

						<?php
						$query_args = array(
							'post_type'      => $args['post_type'],
							'posts_per_page' => ! empty( $args['filter_tabs'] == 'enable' && $args['filter_categories'] ) ? count( $args['filter_categories'] ) * $args['posts_per_page'] : $args['posts_per_page'],
							'no_found_rows'  => true,
						);

						if ( $args['filter_tabs'] == 'enable' && ! empty( $args['filter_categories'] ) ) {
							$query_args['category__in'] = $args['filter_categories'];
						}

						if ( $args['query_type'] === 'select_posts' ) {
							global $dsmp_settings;
							$query_args['post_type']           = $dsmp_settings->post_types;
							$query_args['posts_per_page']      = count( $args['selected_posts'] );
							$query_args['post__in']            = count( $args['selected_posts'] ) > 0 ? $args['selected_posts'] : [ 0 ];
							$query_args['ignore_sticky_posts'] = true;
						}

						$query = new WP_Query( $query_args );
						?>
						<?php
	                    if ( $query->have_posts() ) :
							$badgeLabel = 1;
							?>
							<?php
	                        while ( $query->have_posts() ) :
								$query->the_post();
								$img_id = get_post_thumbnail_id( get_the_ID() );
								$link   = [
									'url'    => get_the_permalink(),
									'title'  => $args['post_type_button_text'],
									'target' => '',
								];

								$args['post_type_data_cta_button']['link'] = $link; // Add link inside array when cta button is shown because of structure of component for cta-list

								$data_attr = '';
								if ( $args['filter_tabs'] == 'enable' && ! empty( $args['filter_categories'] ) ) {
									$post_categories = wp_get_post_categories( get_the_ID(), array( 'fields' => 'all' ) );
									if ( ! empty( $post_categories ) ) {
										$data_attr_cats = '';
										foreach ( $post_categories as $cat ) {
											$data_attr_cats .= $cat->slug . ',';
										}
										$data_attr_cats = rtrim( $data_attr_cats, ',' );
										$data_attr      = 'data-categories="' . $data_attr_cats . '"';
									}
								}
								?>

	                            <div class="m-slider__slide swiper-slide" <?php echo $data_attr; ?>>
	                                <div class="m-slide">
	                                    <div class="l-rcbl__col l-posts <?php echo "l-rcbl-{$args['layout']}"; ?>">
											<?php
	                                        get_template_part(
	                                            'templates/components-shared/blocks/block-v1',
	                                            null,
	                                            array(
													'badge_label' => $badgeLabel,
													'image'    => [
														'ID'  => $img_id,
														'url' => ds_get_the_post_thumbnail_url(),
														'alt' => trim( strip_tags( get_post_meta( $img_id, '_wp_attachment_image_alt', true ) ) ),
														'title' => get_the_title( $img_id ),
													],
													'image_size' => 'ds_medium',
													'pretitle' => get_field( 'pretitle', get_the_ID() ),
													'title'    => get_the_title(),
													'subtitle' => get_field( 'subtitle', get_the_ID() ),
													'description' => get_field( 'description', get_the_ID() ) ?? ds_get_excerpt( '90' ),
													'cta_list' => $args['post_type_is_clickable'] ? false : [
														$args['post_type_data_cta_button'],
													],
													'is_clickable' => $args['post_type_is_clickable'],
													'component_link' => $args['post_type_is_clickable'] ? $link : false,
													'class'    => $componentClass,
													'styles'   => $componentStyles,
	                                            )
	                                        );
	                                        ?>
	                                    </div>
	                                </div>
	                            </div>
								<?php $badgeLabel ++; endwhile; ?>
						<?php
	                    endif;
						wp_reset_postdata();
	                    ?>

					<?php
	                elseif ( $args['content_type'] === 'static' && have_rows( 'cards_widget' ) ) :
						$badgeLabel = 1;
						?>

						<?php
	                    while ( have_rows( 'cards_widget' ) ) :
							the_row();
							?>
	                        <div class="m-slider__slide swiper-slide">
	                            <div class="m-slide l-rcbl__col l-posts">
									<?php
	                                get_template_part(
	                                    'templates/components-shared/blocks/block-v1',
	                                    null,
	                                    array(
											'badge_label' => $badgeLabel,
											'class'       => $componentClass,
											'styles'      => $componentStyles,
	                                    )
	                                );
	                                ?>
	                            </div>
	                        </div>
							<?php $badgeLabel ++; endwhile; ?>

					<?php endif; ?>

	            </div>
	        </div>

		    <div class="m-slider__controls">

			    <?php
			    if ( in_array(
				    $moduleSlider->get_setting( 'data_pagination' ),
				    array(
					    'default',
					    'progressbar',
					    'fraction',
					    'combo',
				    )
			    ) ) :
				    ?>
				    <div class="m-slider__pagination swiper-pagination"></div>
			    <?php endif; ?>

			    <?php if ( strpos( $moduleSlider->get_setting( 'data_navigation' ), 'arrows' ) !== false ) : ?>
				    <?php
				    get_template_part(
					    'templates/components/slider/arrows',
					    null,
					    array(
						    'arrow_type' => $moduleSlider->get_setting( 'arrow_type' ),
					    )
				    );
				    ?>
			    <?php endif; ?>

		    </div>

	    </div>

	</div>

</div>
