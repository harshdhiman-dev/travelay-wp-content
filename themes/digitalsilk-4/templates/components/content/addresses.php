<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'address'       => get_field( 'addresses' ),
		'section_title' => get_field( 'address_section_title' ),
		'class'         => '',
	)
);
$className = '';

if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}

if ( ! empty( $args['address'] ) ) : ?>
    <div class="c-contacts__item -addr<?php echo esc_attr( $className ); ?>">
        <div class="c-contacts__icon">
			<?php
			echo get_svg(
				array(
					'icon'  => 'pin',
					'class' => 'c-address__icon',
				)
			);
			?>
        </div>
        <div class="c-contacts__text">
			<?php if ( ! empty( $args['section_title'] ) ) : ?>
				<?php echo wp_kses_post( $args['section_title'] ); ?>
			<?php endif; ?>

			<?php foreach ( $args['address'] as $address ) : ?>
				<?php if ( ! empty( $address['link'] ) && ! empty( $address['address'] ) ) : ?>
                    <a href="<?php echo esc_url( $address['link'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $address['address'] ); ?></a>
				<?php elseif ( ! empty( $address['address'] ) ) : ?>
					<?php echo esc_html( $address['address'] ); ?>
				<?php endif; ?>
			<?php endforeach; ?>
        </div>
    </div>
<?php
endif;
