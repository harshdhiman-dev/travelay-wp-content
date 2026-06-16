<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'content_type'   => get_field( 'content_type' ),
		'content_image'  => get_field( 'content_image' ),
        'image_size'     => get_field( 'image_size' ),
		'main_video'     => get_field( 'main_video' ),

		'cta_list'       => get_field( 'cta_list' ),
		'pretitle'       => get_field( 'pretitle' ),
		'title'          => get_field( 'title' ),
		'title_styles'   => get_field( 'title_styles' ),
		'subtitle'       => get_field( 'subtitle' ),
		'description'    => get_field( 'description' ),
		// 'text_position'  => get_field( 'text_position' ),
		'loop_index_key' => 0,
		'content_text'   => get_field( 'content_text' ),
	)
);

?>

<div class="l-banner__text">
    <?php get_template_part( 'templates/components/headings/heading-banner-v1', null, $args ); ?>
    <?php get_template_part( 'templates/components/cta-list', null, array( 'buttons' => $args['cta_list'] ) ); ?>
</div>

<?php if ( ! empty( $args['content_type'] ) && 'none' !== $args['content_type'] ) : ?>
    <div class="l-banner__media">
		<?php if ( 'image' === $args['content_type'] ) : ?>
			<?php if ( ! empty( $args['content_image'] ) ) : ?>

				<?php
                get_template_part(
                    'templates/components/images/image-v1',
                    null,
                    array(
						'image'      => $args['content_image'],
						'image_size' => $args['image_size'],
                    )
                );
                ?>

			<?php endif; ?>

		<?php elseif ( 'video' === $args['content_type'] ) : ?>

            <div class="l-banner__video_box">
				<?php if ( 'internal' === $args['main_video']['video_source'] ) : ?>
					<?php
                    get_template_part(
                        'templates/components/videos/video-box',
                        null,
                        array(
							'video'         => $args['main_video']['video'],
							'poster_image'  => $args['main_video']['poster_image'],
							'hide_controls' => $args['main_video']['hide_controls'],
							'autoplay'      => $args['main_video']['autoplay'],
                        )
                    );
                    ?>
				<?php else : ?>
					<?php
                    get_template_part(
                        'templates/components/videos/video-embed',
                        null,
                        array(
							'iframe' => $args['main_video']['video_embed'],
                        )
                    );
                    ?>
				<?php endif; ?>
            </div>

		<?php elseif ( 'text' === $args['content_type'] ) : ?>

			<?php get_template_part( 'templates/components/headings/heading', null, $args['content_text'] ); ?>

			<?php get_template_part( 'templates/components/cta-list', null, array( 'buttons' => $args['content_text']['cta_list'] ?? [] ) ); ?>

		<?php endif; ?>
    </div>
<?php endif; ?>
