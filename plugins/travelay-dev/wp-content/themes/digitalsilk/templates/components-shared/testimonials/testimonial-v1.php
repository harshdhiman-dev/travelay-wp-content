<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'quote'                => get_field( 'quote', get_the_ID() ),
		'cta_button'           => get_field( 'cta_button' ) ?? array(),
		'popup_id'             => uniqid( 'p_' ),
		'has_read_full_story'  => get_field( 'text_component_settings_has_read_full_story' ) ?: false,
		'full_story_type'      => get_field( 'full_story_type', get_the_ID() ) ?: 'text',
		'full_story'           => get_field( 'full_story', get_the_ID() ),
		'full_story_cta_label' => get_field( 'full_story_cta_label', get_the_ID() ) ?: __( 'View Full Testimonial', 'dstheme' ),
		'name'                 => get_the_title(),
		'company_or_position'  => get_field( 'company_position', get_the_ID() ),
		'show_avatar'          => true,
		'intro_title'          => '',
	)
);

$args['cta_button']['title'] = $args['full_story_cta_label'];
$popup_content               = '';

if ( ! empty( $args['full_story']['content'] ) ) {
	$popup_author_name     = '';
	$popup_author_position = '';
	if ( ! empty( $args['name'] ) ) {
		$popup_author_name = '<div class="c-quote__name">' .
							 wp_kses_post( $args['name'] ) .
							 '</div>';
	}
	if ( ! empty( $args['company_or_position'] ) ) {
		$popup_author_position = '<span class="c-quote__company">' .
								 wp_kses_post( $args['company_or_position'] ) .
								 '</span>';
	}

	$popup_content = '<div class="quote-popup">
   <div class="quote-popup__text is-wysiwyg">' . $args['full_story']['content'] . '</div>
   <div class="quote-popup__author">' . $popup_author_name . $popup_author_position . '</div>
	</div>';
}
?>
<blockquote class="c-quote">

	<?php if ( ! empty( $args['intro_title'] ) ) : ?>
		<div class="c-quote__title">
			<?php echo wp_kses_post( $args['intro_title'] ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $args['quote'] ) ) : ?>
		<p class="c-quote__text"><?php echo wp_kses_post( $args['quote'] ); ?></p>
	<?php endif; ?>

	<?php if ( $args['has_read_full_story'] ) : ?>
		<?php
		get_template_part(
			'templates/components/cta-popup',
			null,
			array(
				'button'        => $args['cta_button'],
				'popup_type'    => $args['full_story_type'] ?? '',
				'popup_content' => $popup_content ?? '',
				'popup_video'   => $args['full_story']['video'] ?? array(),
			)
		);
		?>
	<?php endif; ?>

	<?php
	get_template_part(
		'templates/components-shared/testimonials/cite-v1',
		null,
		array(
			'class'       => '-v1',
			'show_avatar' => $args['show_avatar'],
		)
	);
	?>

</blockquote>
