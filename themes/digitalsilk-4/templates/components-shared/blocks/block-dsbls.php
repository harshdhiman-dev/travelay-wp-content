<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'component_type'  => get_field( 'component_type' ) ?: 'v1',
		'image'           => get_sub_field( 'image' ),
		'pretitle'        => get_sub_field( 'pretitle' ),
		'title'           => get_sub_field( 'title' ),
		'title_styles'    => get_field( 'component_title_styles' ) ?: [ 'tag' => 'h3' ],
		'description'     => get_sub_field( 'description' ),
		'cta_list'        => get_sub_field( 'cta_list' ),
		'component_badge' => get_field( 'component_badge' ),
		'class'           => '',
	)
);
?>

<div class="c-block c-block-dsbls <?php echo $args['class']; ?>">

	<?php if ( ! empty( $args['image'] ) && ! empty( $args['image']['url'] ) ) : ?>
		<?php if ( ! empty( $args['component_badge'] ) && ! empty( $args['badge_label'] ) ) : ?>
			<?php get_template_part( 'templates/components/content/badge-1', null, array( 'label' => $args['badge_label'] ) ); ?>
		<?php endif; ?>

		<?php get_template_part( 'templates/components/pictures/picture', null, array( 'image' => $args['image'] ) ); ?>

	<?php endif; ?>

	<?php if ( ! empty( $args['pretitle'] ) || ! empty( $args['title'] ) || ! empty( $args['description'] ) || ! empty( $args['cta_list'] ) ) : ?>
        <div class="c-block__body">

			<?php if ( ! empty( $args['pretitle'] ) ) : ?>
                <div class="c-block__pretitle">
                    <span><?php echo esc_html( $args['pretitle'] ); ?></span>
                </div>
			<?php endif; ?>

			<?php if ( ! empty( $args['title'] ) ) : ?>
				<?php echo acf_title( $args['title'], $args['title_styles'], 'c-block__title' ); ?>
			<?php endif; ?>

			<?php if ( ! empty( $args['description'] ) ) : ?>
                <div class="c-block__description">
					<?php echo apply_filters( 'acf_the_content', $args['description'] ); ?>
                </div>
			<?php endif; ?>

			<?php if ( ! empty( $args['cta_list'] ) ) : ?>
				<?php
                get_template_part(
                    'templates/components/cta-list',
                    null,
                    array(
						'buttons' => $args['cta_list'],
                    )
				);
                ?>
			<?php endif; ?>

        </div>
	<?php endif; ?>
</div>
