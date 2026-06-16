<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$socials = get_field( 'socials_group', 'options' );

$args = wp_parse_args(
	$args,
	array(
		'socials' => $socials['socials'] ?? false,
		'type'    => $socials['type'] ?? false,
		'style'   => $socials['style'] ?? false,
	)
);

if ( ! empty( $args['socials'] ) ) : ?>
    <ul class="social-list <?php echo '-' . $args['type']; ?> <?php echo '-' . $args['style']; ?>">
		<?php foreach ( $args['socials'] as $social ) : ?>
            <li class="social-list__item">
				<?php if ( ! empty( $social['link'] ) && ! empty( $social['social_network'] ) ) : ?>
                    <a class="social-list__link" href="<?php echo $social['link']; ?>" target="_blank" aria-label="Go to <?php echo $social['social_network']; ?>">
						<?php
						echo get_svg(
							array(
								'icon'  => 'social-' . $social['social_network'] . '',
								'class' => 'social-list__icon social-icon',
							)
						);
						?>
                    </a>
				<?php endif; ?>
            </li>
		<?php endforeach; ?>
    </ul>
<?php endif; ?>
