<?php
// phpcs:ignoreFile
defined( 'ABSPATH' ) || exit;

if ( is_single() ) {
	return;
}

$enable_need_help = get_field( 'enable_need_help', 'options' );

if ( ! empty( $enable_need_help ) ) :
	$need_help_content = get_field( 'need_help_content', 'options' );
	?>
    <div class="w-support__widget">
        <a class="w-support__toggle js-w-support-open" href="#" id="w-support-toggle">
            <div class="w-support__icons">
				<?php

				echo get_svg(
					array(
						'icon'  => 'wc-support-icon',
						'class' => 'icon-support',
					)
				);
				?>
            </div>
			<?php if ( ! empty( $need_help_content['button_title'] ) ) : ?>
                <span><?php echo esc_html( $need_help_content['button_title'] ); ?></span>
			<?php endif; ?>
        </a>
        <div class="w-support__popup" id="need_help">
            <div class="w-support__form">
                <div class="w-support__form-close js-w-support-close">
					<?php

					echo get_svg(
						array(
							'icon'  => 'wc-close',
							'class' => 'wc-icon-close',
						)
					);
					?>
                </div>
                <div class="w-support__form-overflow">
                    <div class="w-support__form-header">
						<?php if ( ! empty( $need_help_content['title'] ) ) : ?>
                            <div class="w-support__form-title">
								<?php echo esc_html( $need_help_content['title'] ); ?>
                            </div>
						<?php endif ?>
                        <div class="w-support__form-subtitle">
							<?php if ( ! empty( $need_help_content['subtitle'] ) ) : ?>
                                <div class="w-support__form-text">
									<?php echo esc_html( $need_help_content['subtitle'] ); ?>
                                </div>
							<?php endif ?>
							<?php if ( ! empty( $need_help_content['phone'] ) ) : ?>
                                <div class="w-support__form-phone">
                                    <a href="tel:<?php echo preg_replace( '/[^\p{L}\p{N}\s]/u', '', $need_help_content['phone'] ); ?>"><?php echo esc_html( $need_help_content['phone'] ); ?></a>
                                </div>
							<?php endif ?>
                        </div>
                    </div>
                    <div class="w-support__form-row js-w-support-success-hide">
						<?php if ( ! empty( $need_help_content['text'] ) ) : ?>
                            <p class="w-support__form-desc">
								<?php echo $need_help_content['text']; ?>
                            </p>
						<?php endif ?>
                    </div>
                    <div class="w-support__form-fields">
						<?php if ( ! empty( $need_help_content['gravity_form'] ) ) : ?>
							<?php echo do_shortcode( "[gravityform id='{$need_help_content['gravity_form']}' title='false' description='false' ajax='true']" ); ?>
						<?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="w-support__bg js-w-support-close"></div>
        </div>
    </div>
<?php
endif;
