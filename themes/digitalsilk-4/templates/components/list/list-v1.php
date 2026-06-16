<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */
$args      = wp_parse_args(
	$args,
	array(
		'list'  => get_field( 'list' ),
		'class' => '',
	)
);
$className = '';
if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}
?>
<?php if ( ! empty( $args['list'] ) && is_array( $args['list'] ) ) : ?>
	<ul class="c-list__items<?php echo esc_attr( $className ); ?>">
		<?php foreach ( $args['list'] as $list ) : ?>
			<li class="c-list__item">


				<?php if ( ! empty( $list['icon']['ID'] ) ) : ?>

					<?php echo ds_generate_image( $list['icon']['ID'], 'ds_small' ); ?>

				<?php endif; ?>


				<?php if ( $list['type'] === 'phone' && ! empty( $list['phone'] ) ) : ?>

					<?php
					echo get_svg(
						array(
							'icon'  => 'phone',
							'class' => 'c-list__icon',
						)
					);
					?>

					<?php if ( ! empty( $list['label'] ) ) : ?>

						<span class="c-list__label"><?php echo esc_html( $list['label'] ); ?></span>

					<?php endif; ?>

					<a href="tel:<?php echo esc_html( $list['phone'] ); ?>"><?php echo esc_html( $list['phone'] ); ?></a>

				<?php elseif ( $list['type'] === 'address' && ! empty( $list['address'] ) ) : ?>

					<?php
					echo get_svg(
						array(
							'icon'  => 'pin',
							'class' => 'c-list__icon',
						)
					);
					?>

					<?php if ( ! empty( $list['label'] ) ) : ?>

						<span class="c-list__label"><?php echo esc_html( $list['label'] ); ?></span>

					<?php endif; ?>

					<?php if ( ! empty( $list['address_link'] ) ) : ?>
						<a href="<?php echo esc_url( $list['address_link'] ); ?>" target="_blank" rel="noreferrer"><?php echo esc_html( $list['address'] ); ?></a>
					<?php else : ?>
						<?php echo $list['address']; ?>
					<?php endif; ?>

				<?php elseif ( $list['type'] === 'working_hours' && ! empty( $list['working_hours'] ) ) : ?>

					<?php if ( ! empty( $list['label'] ) ) : ?>
						<span class="c-list__label"><?php echo esc_html( $list['label'] ); ?></span>
					<?php endif; ?>

					<div class="c-list_working-hours">
						<?php foreach ( $list['working_hours'] as $working_hours ) : ?>
							<?php if ( ! empty( $working_hours['day'] ) && ! empty( $working_hours['status'] ) ) : ?>
								<span class="c-list__day"><?php echo esc_html( $working_hours['day'] ); ?></span>

								<span class="c-list__status"><?php echo esc_html( $working_hours['status'] ); ?></span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>

				<?php elseif ( $list['type'] === 'link' && ! empty( $list['link'] ) ) : ?>

					<?php if ( ! empty( $list['label'] ) ) : ?>

						<span class="c-list__label"><?php echo $list['label']; ?></span>

					<?php endif; ?>
					<a href="<?php echo esc_url( $list['link']['url'] ); ?>" <?php echo $list['link']['target'] ? 'target="' . esc_attr( $list['link']['target'] ) . '"' : ''; ?>><?php echo $list['link']['title']; ?></a>

				<?php else : ?>

					<?php if ( ! empty( $list['label'] ) ) : ?>

						<span class="c-list__label"><?php echo esc_html( $list['label'] ); ?></span>

					<?php endif; ?>

				<?php endif; ?>

			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
