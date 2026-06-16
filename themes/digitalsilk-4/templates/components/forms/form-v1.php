<?php
// phpcs:ignoreFile
/**
 * @var array $args
 */
$args = wp_parse_args(
	$args,
	array(
		'pretitle'               => get_field( 'form_pretitle' ),
		'title'                  => get_field( 'form_title' ),
		'subtitle'               => get_field( 'form_subtitle' ),
		'description'            => get_field( 'form_description' ),
		'form_shortcode'         => get_field( 'form_shortcode' ),
		'component_gap'          => get_field( 'component_settings_inner_gap' ),
		'component_input_margin' => get_field( 'component_settings_input_margin' ),
		'background_color'       => get_field( 'component_settings_bg_color' ),
		'border_radius'          => get_field( 'component_settings_border_radius' ),
		'title_styles'           => get_field( 'title_styles' ),
		'form_type'              => get_field( 'form_type' ) ?: 'shortcode',
		'form_embed'             => get_field( 'form_embed' ),
	)
);

$componentStyles  = ! empty( $args['component_gap'] ) ? "--c-block-gap:{$args['component_gap']}px;" : '';
$componentStyles .= ! empty( $args['component_input_margin'] ) ? "--c-block-input-margin:{$args['component_input_margin']}px;" : '';
$componentStyles .= ! empty( $args['background_color'] ) ? "--c-block__bg-color:{$args['background_color']};" : '';
$componentStyles .= ! empty( $args['border_radius'] ) ? "--c-block-border-radius:{$args['border_radius']}px;" : '';

$title_tag_style = ! empty( $args['title_styles'] ) && ! empty( $args['title_styles']['tag_style'] ) ? $args['title_styles']['tag_style'] : 'h2';

$form_styles = ! empty( $componentStyles ) ? ' style="' . $componentStyles . '"' : '';

if ( ! empty( $args['pretitle'] ) || ! empty( $args['title'] ) || ! empty( $args['subtitle'] ) ) : ?>
	<div class="c-heading <?php echo "-{$title_tag_style}"; ?> ">
		<?php if ( ! empty( $args['pretitle'] ) ) : ?>
			<div class="c-heading__pre"><?php echo $args['pretitle']; ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $args['title'] ) ) : ?>
			<div class="c-heading__title"><?php echo $args['title']; ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $args['subtitle'] ) ) : ?>
			<div class="c-heading__sub"><?php echo $args['subtitle']; ?></div>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if ( ! empty( $args['description'] ) ) : ?>
	<div class="c-form__description"><?php echo $args['description']; ?></div>
<?php endif; ?>

<?php if ( $args['form_type'] == 'shortcode' && ! empty( ( $args['form_shortcode'] ) ) ) : ?>
	<div class="c-form"<?php echo $form_styles; ?>>
		<div class="c-form__cf7">
			<?php echo do_shortcode( $args['form_shortcode'] ); ?>
		</div>
	</div>
<?php elseif ( $args['form_type'] == 'embed' && ! empty( ( $args['form_embed'] ) ) ) : ?>
	<div class="c-form"<?php echo $form_styles; ?>>
		<div class="c-form__embed">
			<?php echo $args['form_embed']; ?>
		</div>
	</div>
<?php endif; ?>
