<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'links' => get_field( 'links_list' ),
	)
);

$args['links'] = isset( $args['links']['links_list'] ) ? $args['links']['links_list'] : $args['links'];

if ( ! empty( $args['links'] ) ) : ?>
    <ul class="links-list">
		<?php foreach ( $args['links'] as $link ) : ?>
            <li class="links-list__item">
				<?php if ( ! empty( $link['title'] ) ) : ?>
                    <span><?php echo $link['title']; ?></span>
				<?php endif; ?>

				<?php if ( ! empty( $link['icon'] ) && is_array( $link['icon'] ) ) : ?>
					<?php echo ds_get_embedded_image( $link['icon']['ID'], $link['icon']['url'] ); ?>
				<?php endif; ?>

				<?php if ( ! empty( $link['caption'] ) ) : ?>
                    <span><?php echo $link['caption']; ?></span>
				<?php endif; ?>

				<?php if ( $link['main_content'] == 'text' ) : ?>
					<?php if ( ! empty( $link['text'] ) ) : ?>
						<?php echo $link['text']; ?>
					<?php endif; ?>
				<?php else : ?>
					<?php if ( ! empty( $link['link'] ) ) : ?>
						<?php echo acf_button( $link['link'], [ 'class' => 'links-list__btn ' . $link['icon_position'] ], false ); ?>
					<?php endif; ?>
				<?php endif; ?>

            </li>
		<?php endforeach; ?>
    </ul>
<?php endif; ?>
