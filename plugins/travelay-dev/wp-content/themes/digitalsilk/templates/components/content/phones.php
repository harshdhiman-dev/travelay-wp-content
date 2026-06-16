<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */
$args      = wp_parse_args(
	$args,
	array(
		'phone'         => get_field( 'phones' ),
		'section_title' => get_field( 'phone_section_title' ),
		'class'         => '',
	)
);
$className = '';

if ( ! empty( $args['class'] ) ) {
	$className = " {$args['class']}";
}

if ( ! empty( $args['phone'] ) ) : ?>
    <div class="c-contacts__item -phone<?php echo esc_attr( $className ); ?>">
        <div class="c-contacts__icon">
			<?php
			echo get_svg(
				array(
					'icon'  => 'phone',
					'class' => 'c-phone__icon',
				)
			);
			?>
        </div>
        <div class="c-contacts__text">
			<?php if ( ! empty( $args['section_title'] ) ) : ?>
				<?php echo $args['section_title']; ?>
			<?php endif; ?>

			<?php foreach ( $args['phone'] as $phone ) : ?>
				<?php if ( ! empty( $phone['phone'] ) ) : ?>
                    <a href="tel:<?php echo esc_html( $phone['phone'] ); ?>"><?php echo esc_html( $phone['phone'] ); ?></a>
				<?php endif; ?>
			<?php endforeach; ?>
        </div>
    </div>
<?php
endif;
