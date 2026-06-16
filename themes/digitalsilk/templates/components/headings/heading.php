<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'pretitle'                       => get_field( 'pretitle' ),
		'backtitle'                      => get_field( 'backtitle' ),
		'title'                          => get_field( 'title' ),
		'title_styles'                   => get_field( 'title_styles' ) ?: [ 'tag' => 'h4' ],
		'subtitle'                       => get_field( 'subtitle' ),
		'description'                    => get_field( 'description' ),
		'sanitize_description'           => true,
		'pretitle_color'                 => get_field( 'content_styles_pretitle_color' ),
		'subtitle_color'                 => get_field( 'content_styles_subtitle_color' ),
		'showReadMore'                   => false,
		'description_hidden'             => '',
		'description_hidden_button'      => '',
		'description_hidden_button_less' => __( 'Show Less', 'dstheme' ),
	)
);

$title_tag = ! empty( $args['title_styles'] ) && ! empty( $args['title_styles']['tag_style'] ) ? $args['title_styles']['tag_style'] : 'h2';

if ( ! empty( $args['pretitle'] ) || ! empty( $args['backtitle'] ) || ! empty( $args['title'] ) || ! empty( $args['subtitle'] ) ) : ?>
	<div class="c-heading <?php echo '-' . esc_attr( $title_tag ); ?> ">
		<?php if ( ! empty( $args['backtitle'] ) ) : ?>
			<div class="c-heading__preamble">
				<span><?php echo wp_kses_post( $args['backtitle'] ); ?></span>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $args['pretitle'] ) ) : ?>
			<div
				class="c-heading__pre" <?php echo ( $args['pretitle_color'] ) ? "style='color:{$args['pretitle_color']};'" : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo wp_kses_post( $args['pretitle'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $args['title'] ) ) : ?>
			<?php echo acf_title( $args['title'], $args['title_styles'], 'c-heading__title' ); ?>
		<?php endif; ?>

		<?php if ( ! empty( $args['subtitle'] ) ) : ?>
			<div
				class="c-heading__sub" <?php echo ( $args['subtitle_color'] ) ? "style='color:{$args['subtitle_color']};'" : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php echo wp_kses_post( $args['subtitle'] ); ?>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $args['description'] ) ) : ?>
	<div class="c-heading__description is-wysiwyg">
		<?php if ( $args['sanitize_description'] ) : ?>
			<?php echo wp_kses_post( $args['description'] ); ?>
		<?php else : ?>
			<?php /* The $args['description'] can echo inner blocks, so it can't be escaped */ ?>
			<?php echo $args['description']; //phpcs:ignore ?>
		<?php endif; ?>

		<?php if ( ! empty( $args['showReadMore'] ) ) : ?>
			<div id=<?php echo esc_attr( uniqid( 'rm-' ) ); ?> class="read-more-wrapper">
				<div class="read-more-text">
					<?php echo wp_kses_post( $args['description_hidden'] ); ?>
				</div>
				<button class="c-btn -normal -link cta_1 read-more-toggle js-read-more-toggle"
						data-show-less-text="<?php echo esc_attr( $args['description_hidden_button_less'] ); ?>">
					<span class="c-btn__txt">
						<?php echo wp_kses_post( $args['description_hidden_button'] ); ?>
					</span>
				</button>
			</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
