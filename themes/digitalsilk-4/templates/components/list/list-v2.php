<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'list' => get_field('contact_info'),
		'class' => '',
	)
);
$className = '';
if (!empty($args['class'])) {
	$className = " {$args['class']}";
}
?>
<?php if (!empty($args['list']) && is_array($args['list'])) : ?>
	<ul class="c-list__items<?php echo esc_attr($className); ?>">
		<?php foreach ($args['list'] as $list) : ?>
			<li class="c-list__item">
				<?php if (!empty($list['section_title']) && $list['type'] !== 'contact_info') : ?>
					<div class="c-list__item-main">
						<?php

						echo get_svg(
							array(
								'icon' => $list['type'],
								'class' => 'c-list__icon',
							)
						);
						?>
						<span class="c-list__title"><?php echo esc_html($list['section_title']); ?></span>
					</div>
				<?php endif; ?>
				<?php if ($list['type'] === 'contact_info' && !empty($list['info'])) : ?>
					<?php
					$i = 0;
					foreach ($list['info'] as $info) :?>
						<?php if (!empty($info['value'])) : ?>
							<?php if ($i <= 0) : ?>
								<div class="c-list__item-main">
									<?php echo get_svg(array('icon' => $info['type'], 'class' => 'c-list__icon',)); ?>
									<span class="c-list__title"><?php echo esc_html($list['section_title']); ?></span>
								</div>
							<?php endif; ?>

							<div class="c-list__col">
								<?php if (!empty($info['label'])) : ?>
									<span class="c-list__label"><?php echo $info['label']; ?></span>
								<?php endif; ?>
								<a href="<?php echo esc_attr($info['type']); ?>:<?php echo esc_attr($info['value']); ?>"><?php echo esc_html($info['value']); ?></a>
							</div>
						<?php endif; ?>
					<?php $i++;
					endforeach; ?>

				<?php elseif ($list['type'] === 'address' && !empty($list['address']['location'])) : ?>

					<?php if (!empty($list['address']['location_link'])) : ?>
						<a href="<?php echo esc_url($list['address']['location_link']); ?>" target="_blank"
						   rel="noreferrer"><?php echo esc_html($list['address']['location']); ?></a>
					<?php else : ?>
						<?php echo esc_html($list['address']['location']); ?>
					<?php endif; ?>

				<?php elseif ($list['type'] === 'working_hours' && !empty($list['working_hours'])) : ?>
					<div class="c-list_working-hours">
						<?php foreach ($list['working_hours'] as $working_hours) : ?>
							<div class="c-list__col">
								<?php if (!empty($working_hours['day']) && !empty($working_hours['status'])) : ?>
									<span class="c-list__day"><?php echo esc_html($working_hours['day']); ?></span>

									<span
										class="c-list__status"><?php echo esc_html($working_hours['status']); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
