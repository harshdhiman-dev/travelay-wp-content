<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile
$args = array(
	'teams_content'    => get_field( 'teams_content' ),
	'layout'           => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'columns'          => get_field( 'layout_settings_columns' ) ?: 3,
	'gap'              => get_field( 'layout_settings_teams_gap' ) ?: 0,
	'gridder_settings' => get_field( 'gridder_settings' ),
	'block_id'         => $block['id'],
);
?>
<div class="m-teams<?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-teams__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">
		<div class="m-teams__inner m-teams__inner--row">

			<div class="l-team <?php echo "l-teams-{$args['layout']}"; ?>">
				<?php if ( ! empty( $args['teams_content'] ) ) : ?>
					<div class="grid-team l-team__gridder js-gridder js-teams-<?php echo "js-gridder-{$args['layout']}"; ?>"
						style="<?php echo "--grid-col: {$args['columns']}"; ?> ; <?php echo "--grid-col-gap: {$args['gap']}px"; ?>"
						data-gridder-columns="<?php echo $args['columns']; ?>"
						data-gridder-gap="<?php echo $args['gap']; ?>"
						data-gridder-scroll-offset="<?php echo $args['gridder_settings']['scroll_offset']; ?>"
						data-gridder-animation-effect="<?php echo $args['gridder_settings']['animation_effect']; ?>"
						data-gridder-animation-speed="<?php echo $args['gridder_settings']['animation_speed']; ?>">
						<div class="l-team__list gridder-list">
							<?php
							foreach ( $args['teams_content'] as $key => $post ) :
								setup_postdata( $post );
								?>
								<div class="l-team__item" data-target="<?php echo "{$args['block_id']}-$key"; ?>">
									<?php get_template_part( 'templates/components-shared/team/team-card-v1' ); ?>
								</div>
								<?php
							endforeach;
							wp_reset_postdata();
							?>
						</div>
					</div>

					<ul class="l-team__list-target">
						<?php
						foreach ( $args['teams_content'] as $key => $post ) :
							setup_postdata( $post );
							?>
							<li class="gridder-content l-team__content" id="<?php echo "{$args['block_id']}-$key"; ?>" style="display:none">
								<?php get_template_part( 'templates/components-shared/team/team-v1' ); ?>
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
