<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'name'    => get_the_title(),
		'role'    => get_field( 'position', get_the_ID() ),
		'bio'     => get_field( 'bio', get_the_ID() ),
		'image'   => get_the_post_thumbnail_url( 'full' ),
		'email'   => get_field( 'email', get_the_ID() ),
		'phone'   => get_field( 'phone', get_the_ID() ),
		'socials' => get_field( 'socials', get_the_ID() ),
	)
);
?>
<?php if ( ! empty( $args['name'] ) || ! empty( $args['role'] ) || ! empty( $args['image'] ) ) : ?>

    <div class="c-team__details container">

        <div class="c-team__details-inner">

	        <div class="c-team__details-info">

		        <div>

	                <div class="c-team__details-img">
						<?php
		                get_template_part(
		                    'templates/components-shared/team/team-card-v1',
		                    null,
		                    array(
								'show_bio'                 => false,
								'show_social_networks'     => false,
								'component_type'           => false,
								'component_gap_vertical'   => 0,
								'component_gap_horizontal' => 0,
								'has_background'           => false,
								'has_hover'                => false,
								'component_background'     => false,
								'horizontal_alignment'     => false,
								'vertical_alignment'       => false,
		                    )
						);
		                ?>

	                </div>

			        <div class="c-team__details-social">
				        <?php if ( ! empty( $args['socials'] ) ) : ?>
					        <?php get_template_part( 'templates/components/socials', null, array( 'socials' => $args['socials'] ) ); ?>
				        <?php endif; ?>

				        <?php if ( ! empty( $args['phone'] ) ) : ?>
					        <div class="c-team__details-phone">
						        <a href="tel:<?php echo $args['phone']; ?>"><?php echo $args['phone']; ?></a></div>
				        <?php endif; ?>

				        <?php if ( ! empty( $args['email'] ) ) : ?>
					        <div class="c-team__details-email">
						        <a href="mailto:<?php echo $args['email']; ?>"><?php echo $args['email']; ?></a></div>
				        <?php endif; ?>

			        </div>

		        </div>
	        </div>

            <div class="c-team__details-content">
				<?php if ( ! empty( $args['name'] ) ) : ?>
                    <h4 class="c-team__details-name"><?php echo $args['name']; ?></h4>
				<?php endif; ?>

				<?php if ( ! empty( $args['role'] ) ) : ?>
                    <div class="c-team__details-role"><?php echo $args['role']; ?></div>
				<?php endif; ?>

				<?php if ( ! empty( $args['bio'] ) ) : ?>
                    <div class="c-team__details-bio is-wysiwyg"><?php echo $args['bio']; ?></div>
				<?php endif; ?>
            </div>
        </div>
    </div>
<?php
endif;
