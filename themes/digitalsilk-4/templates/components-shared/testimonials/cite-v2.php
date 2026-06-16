<?php
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'name'                 => get_the_title(),
		'company_or_position'  => get_field( 'company_position', get_the_ID() ),
		'quote'                => get_field( 'quote', get_the_ID() ),
		'story'                => get_field( 'full_story', get_the_ID() ),
		'class'                => '',
		'quote_order'          => get_field( 'text_component_settings_quote_order' ) ?: 'default',
		'has_read_full_story'  => get_field( 'text_component_settings_has_read_full_story' ) ?: false,
		'has_avatar'           => get_field( 'media_component_settings_has_avatar' ) ?: false,
		'popup_id'             => '',
		'intro_title'          => get_field( 'testimonial_intro_title' ) ?: '',
		'cta_button'           => get_field( 'cta_button' ) ?? array(),
		'full_story_cta_label' => get_field( 'full_story_cta_label', get_the_ID() ) ?: __( 'View Full Testimonial', 'dstheme' ),
		'full_story_type'      => get_field( 'full_story_type', get_the_ID() ) ?: 'content',
		'full_story'           => get_field( 'full_story', get_the_ID() ),

	)
);

$args['cta_button']['title'] = $args['full_story_cta_label'];

$className = "order-{$args['quote_order']}";
if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}

?>
<blockquote class="c-quote <?php echo esc_attr( $className ); ?>">

	<?php if ( ! empty( $args['intro_title'] ) ) : ?>
		<div class="c-quote__title">
			<?php echo wp_kses_post( $args['intro_title'] ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $args['name'] ) || ! empty( $args['company_or_position'] ) ) : ?>
		<cite class="c-quote__content">
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
						'popup_content' => $args['full_story']['content'] ?? '',
						'popup_video'   => $args['full_story']['video'] ?? array(),
					)
				);
				?>

			<?php endif; ?>
		</cite>
	<?php endif; ?>
	<div class="c-quote__profile">
		<?php if ( $args['has_avatar'] ) : ?>
			<?php
			get_template_part(
				'templates/components/testimonials/photo',
				null,
				array(
					'class' => 'c-quote__photo',
				)
			);
			?>
		<?php endif; ?>

		<?php
		get_template_part(
			'templates/components/testimonials/author',
			null,
			array(
				'class'            => '',
				'name'             => $args['name'],
				'company_position' => $args['company_or_position'],
			)
		);
		?>
	</div>

</blockquote>
