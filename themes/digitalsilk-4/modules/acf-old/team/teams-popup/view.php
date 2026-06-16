<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile
$args = array(
	'teams_content'     => get_field( 'teams_content' ),
	'layout'            => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'columns'           => get_field( 'layout_settings_columns' ) ?: 3,
	'gap'               => get_field( 'layout_settings_teams_gap' ) ?: 0,
	'show_bio_popup'    => get_field( 'show_bio_popup' ) ?: false,
	'popup_trigger_type'=> get_field( 'popup_trigger_type' ) ?: 'button',
	'cta_button'        => get_field( 'cta_button' ) ?: array(),
	'block_id'          => $block['id'],
);
?>
<div class="m-teams<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-teams__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="m-teams__inner m-teams__inner--row">

			<div class="l-teams <?php echo "l-teams-{$args['layout']}"; ?>">
				<?php if ( ! empty( $args['teams_content'] ) ) : ?>
					<ul class="grid-team l-team__list"
						style="<?php echo "--grid-col: {$args['columns']}"; ?> ; <?php echo "--grid-col-gap: {$args['gap']}px"; ?>">
						<?php
						foreach ( $args['teams_content'] as $key => $post ) :
							setup_postdata( $post );
							?>
							<li class="gridder-list l-team__item">
								<?php 
								get_template_part( 
									'templates/components-shared/team/team-card-v1', 
									null, 
									array(
										'show_bio_popup' => $args['show_bio_popup'],
										'popup_trigger_type' => $args['popup_trigger_type'],
										'cta_button' => $args['cta_button'],
									) 
								); 
								?>
							</li>

							<?php
						endforeach;
						wp_reset_postdata();
						?>
					</ul>
				<?php endif; ?>
			</div>

		</div>
	</div>
</div>
