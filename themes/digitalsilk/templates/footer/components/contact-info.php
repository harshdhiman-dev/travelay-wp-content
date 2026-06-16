<?php
// phpcs:ignoreFile
/**
 * @var array $args
 */

if ( ! isset( $args['contact_info'] ) ) {
	return;
}

?>

<div class="footer-block">
	<?php if ( ! empty( $args['contact_info']['title'] ) ) : ?>
        <div class="footer-title"><?php echo $args['contact_info']['title']; ?></div>
	<?php endif; ?>
    <ul class="contact-info">
		<?php if ( ! empty( $args['contact_info']['phone'] ) ) : ?>
            <li class="contact-info__item">
                <a class="contact-info__link" href="tel:<?php echo $args['contact_info']['phone']; ?>">
					<?php
					echo get_svg(
						array(
							'icon'  => 'phone',
							'class' => 'contact-info__icon',
						)
					);
					?>
					<?php echo $args['contact_info']['phone']; ?>
                </a>
            </li>
		<?php endif; ?>
		<?php if ( ! empty( $args['contact_info']['email'] ) ) : ?>
            <li class="contact-info__item">
                <a class="contact-info__link" href="mailto:<?php echo $args['contact_info']['email']; ?>">
					<?php

					echo get_svg(
						array(
							'icon'  => 'mail',
							'class' => 'contact-info__icon',
						)
					);
					?>
					<?php echo $args['contact_info']['email']; ?>
                </a>
            </li>
		<?php endif; ?>
		<?php if ( ! empty( $args['contact_info']['address_line'] ) ) : ?>
            <li class="contact-info__item">
				<?php

				echo get_svg(
					array(
						'icon'  => 'pin',
						'class' => 'contact-info__icon',
					)
				);
				?>
				<?php if ( ! empty( $args['contact_info']['address_line_link'] ) ) : ?>
                    <a class="contact-info__link" href="<?php echo $args['contact_info']['address_line_link']; ?>" target="_blank" rel="noopener"><?php echo $args['contact_info']['address_line']; ?></a>
				<?php else : ?>
					<?php echo $args['contact_info']['address_line']; ?>
				<?php endif; ?>
            </li>
		<?php endif; ?>
		<?php if ( ! empty( $args['contact_info']['working_hours'] ) ) : ?>
            <li class="contact-info__item">
				<?php

				echo get_svg(
					array(
						'icon'  => 'calendar',
						'class' => 'contact-info__icon',
					)
				);
				?>
				<?php echo esc_html( $args['contact_info']['working_hours'] ); ?>
            </li>
		<?php endif; ?>
    </ul>
</div>
