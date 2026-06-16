<?php
/**
 * @var array $args
 */

//phpcs:ignoreFile
$args = wp_parse_args(
	$args,
	array(
		'buttons' => get_field( 'cta_list' ),
	)
);
// check for 2nd lvl clone field
$args['buttons'] = isset( $args['buttons']['cta_list'] ) ? $args['buttons']['cta_list'] : $args['buttons'];

if ( ! empty( $args['buttons'] ) && is_array( $args['buttons'] ) ) : ?>
	<?php
    if ( ! empty( $args['class'] ) ) :
?>
<div class="c-btn-bar-container <?php echo $args['class']; ?>"><?php endif; ?>
    <div class="c-block__btn">
		<?php
        foreach ( $args['buttons'] as $link ) :
			$linkSize              = $link['size'] ?? '-normal';
			$linkStyle             = $link['style'] ?? '-primary';
			$icon_settings         = get_button_icon_settings( $link );
			$link['args']['class'] = "c-btn {$linkSize} {$linkStyle}";
			?>

			<?php echo acf_button( $link['link'], $link['args'], $icon_settings['icon'], $icon_settings['icon_args'], $link['link_popup'] ?? [] ); ?>

		<?php endforeach; ?>
    </div>
	<?php
    if ( ! empty( $args['class'] ) ) :
?>
</div><?php endif; ?>
<?php endif; ?>
