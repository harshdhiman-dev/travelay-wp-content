<?php
/**
 * Template part for testimonial popup
 *
 * @package DS_Theme
 */

$args = wp_parse_args(
	$args,
	array(
		'story'     => get_field( 'full_story', get_the_ID() ),
		'popup_id'  => uniqid( 'p_' ),
		'show_link' => true,
	)
);
?>
<?php if ( ! empty( $args['story'] ) ) : ?>
	<?php if ( $args['show_link'] ) : ?>
		<a href="#<?php echo esc_attr( $args['popup_id'] ); ?>"
		   class="c-popup-testimonial"
		   data-dimbox="dst-popup-testimonial-<?php echo esc_attr( $args['popup_id'] ); ?>">
			<?php esc_attr_e( 'Read Their Story', 'dstheme' ); ?>
		</a>
	<?php endif; ?>
	<div id="<?php echo esc_attr( $args['popup_id'] ); ?>" class="c-popup-testimonial-content"
		 style='display: none'>
		<?php echo wp_kses_post( $args['story'] ); ?>
	</div>
<?php
endif;
