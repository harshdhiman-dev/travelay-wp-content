    <?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'module_decorations' => get_field( 'decor_settings' ),
	),
);

if ( ! empty( $args['module_decorations'] ) && is_array( $args['module_decorations'] ) ) : ?>
	<?php foreach ( $args['module_decorations'] as $decor ) : ?>
		<?php
		// Prevent warnings in case decor is disabled for module but in DB there is still old data that is not saved.
		if ( empty( $decor['decor_type'] ) ) {
			continue;
		}
		$class = '';
		if ( ( $decor['decor_type'] === 'image' && ! empty( $decor['decor_image'] ) ) || ( $decor['decor_type'] === 'global image' && ! empty( $decor['decor_global_image']['url'] ) ) ) {
			$class .= ' has-img-decor';
		}
		if ( ! empty( $decor['decor_class'] ) ) {
			$class .= " {$decor['decor_class']}";
		}
		?>
		<?php if ( ! empty( $class ) || ! empty( $decor['decor_image'] ) ) : ?>
            <div class="c-decor<?php echo $class; ?> --d-<?php echo $decor['horizontal_pos']; ?> --d-<?php echo $decor['vertical_pos']; ?>">
				<?php if ( ( $decor['decor_type'] === 'image' && ! empty( $decor['decor_image'] ) ) || ( $decor['decor_type'] === 'global image' && ! empty( $decor['decor_global_image']['url'] ) ) ) : ?>
					<?php
					$image_id  = false;
					$image_url = false;
					if ( $decor['decor_type'] === 'image' ) {
						$image_id  = $decor['decor_image']['ID'];
						$image_url = $decor['decor_image']['url'];
					} else {
						$image_id  = $decor['decor_global_image']['ID'];
						$image_url = $decor['decor_global_image']['url'];
					}
					?>
                    <div class="c-decor__image">
						<?php echo ds_get_embedded_image( $image_id, $image_url, false, $decor['embed_image'] ?? false ); ?>
                    </div>
				<?php endif; ?>
            </div>
		<?php endif; ?>

	<?php endforeach; ?>
<?php
endif;
