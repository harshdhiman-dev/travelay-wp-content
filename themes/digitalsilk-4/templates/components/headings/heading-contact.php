<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'pretitle'     => get_field( 'pretitle' ),
		'title'        => get_field( 'title' ),
		'title_styles' => get_field( 'title_styles' ),
		'subtitle'     => get_field( 'subtitle' ),
		'description'  => get_field( 'description' ),
	)
);

$title_tag_style = ! empty( $args['title_styles'] ) && ! empty( $args['title_styles']['tag_style'] ) ? $args['title_styles']['tag_style'] : 'h2';
if ( ! empty( $args['pretitle'] ) || ! empty( $args['title'] ) || ! empty( $args['subtitle'] ) || ! empty( $args['description'] ) ) : ?>
    <div class="l-form__text">
        <div class="c-heading <?php echo "-{$title_tag_style}"; ?> ">
			<?php if ( ! empty( $args['pretitle'] ) ) : ?>
                <div class="c-heading__pre"><?php echo $args['pretitle']; ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $args['title'] ) ) : ?>
				<?php echo acf_title( $args['title'], $args['title_styles'], 'c-heading__title' ); ?>
			<?php endif; ?>

			<?php if ( ! empty( $args['subtitle'] ) ) : ?>
                <div class="c-heading__sub"><?php echo $args['subtitle']; ?></div>
			<?php endif; ?>
        </div>

		<?php if ( ! empty( $args['description'] ) ) : ?>
            <div class="c-heading__description is-wysiwyg">
				<?php echo $args['description']; ?>
            </div>
		<?php endif; ?>

    </div>
<?php
endif;
