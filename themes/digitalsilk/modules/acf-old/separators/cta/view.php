<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

$args = array(
	'title'           => get_field( 'title' ) ?: 'CTA title',
	'cta_link'        => get_field( 'cta_link' ) ?: array(),
	'link_rel'        => ! empty( get_field( 'link_rel' ) ) ? 'rel="' . get_field( 'link_rel' ) . '"' : '',
	'title_color'     => get_field( 'title_color' ),
	'cta_text_color'  => get_field( 'cta_text_color' ),
	'cta_bg_settings' => array(
		'bg_color_type'      => get_field( 'cta_type' ) ?: 'color',
		'gradient_color_1'   => get_field( 'cta_gradient_color_1' ) ?: '',
		'gradient_color_2'   => get_field( 'cta_gradient_color_2' ) ?: '',
		'gradient_direction' => get_field( 'cta_gradient_direction' ) ?: '',
		'color'              => get_field( 'cta_color' ) ?: '',
	),
);

$cta_background = '';

if ( $args['cta_bg_settings']['bg_color_type'] == 'color' ) {
	$cta_background .= "background-color:{$args['cta_bg_settings']['color']};";
} elseif ( $args['cta_bg_settings']['bg_color_type'] == 'gradient' ) {
	$g_c_1           = ( empty( $args['cta_bg_settings']['gradient_color_1'] ) ) ? 'transparent' : $args['cta_bg_settings']['gradient_color_1'];
	$g_c_2           = ( empty( $args['cta_bg_settings']['gradient_color_2'] ) ) ? 'transparent' : $args['cta_bg_settings']['gradient_color_2'];
	$cta_background .= "background-image: linear-gradient(to {$args['cta_bg_settings']['gradient_direction']}, {$g_c_1}, {$g_c_2});";
}
?>

<div class="separators-custom-box <?php echo esc_attr( $block['className'] ); ?>" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php if ( ! empty( $args['cta_link']['url'] ) ) : ?>

		<?php if ( ! empty( $args['title'] ) ) : ?>
			<div class="cta_title" 
			<?php
			if ( ! empty( $args['title_color'] ) ) {
				echo "style='color:{$args['title_color']};'";
			}
			?>
			><?php echo $args['title']; ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $args['cta_link'] ) ) : ?>
			<a href="<?php echo $args['cta_link']['url']; ?>"
				<?php echo ! empty( $args['link_rel'] ) ? $args['link_rel'] : ''; ?> <?php echo ! empty( $args['cta_link']['target'] ) ? 'target="_blank"' : ''; ?>
				style="<?php echo $cta_background; ?>"
				class=""
			>
			<span class="" 
			<?php
			if ( ! empty( $args['cta_text_color'] ) ) {
				echo "style='color:{$args['cta_text_color']};'";
			}
			?>
			>
				<?php echo $args['cta_link']['title']; ?>
			</span>
			</a>
		<?php endif; ?>

	<?php endif; ?>
</div>
