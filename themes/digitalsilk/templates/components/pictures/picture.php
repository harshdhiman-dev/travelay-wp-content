<?php
/**
 * Create a picture element with srcset and sizes attributes.
 *
 * @package DS_Theme
 *
 * @var array $args
 */

/**
 * Default arguments for rendering a <picture> element.
 *
 * @var array $args {
 *     Array of arguments for generating the <picture> element.
 *
 *     @type array  $image                      The primary image data array, typically from ACF. Defaults to `get_field( 'image' )`.
 *     @type array  $mobile_image               The mobile-specific image data array, typically from ACF. Defaults to `get_field( 'mobile_image' )`.
 *     @type string $class                      Additional CSS classes to add to the <picture> element.
 *     @type string $class_container            Default class for the <picture> container. Defaults to `"c-picture"`.
 *     @type string $class_image                Additional CSS classes for the final `<img>` tag inside the `<picture>` element.
 *     @type array  $media_srcset               Array defining the responsive image sizes, where keys are media query breakpoints
 *                                              and values are the corresponding WordPress image sizes. Defaults to:
 *                                              [
 *                                                  '1500px' => 'default',
 *                                                  '1024px' => '1536x1536',
 *                                                  '768px'  => 'large',
 *                                                  '300px'  => 'medium_large',
 *                                              ].
 *     @type string $mobile_image_fallback_size The fallback image size for the mobile image if `mobile_image` is not provided.
 *                                              Defaults to `"medium_large"`.
 *     @type string $image_size                 The image size to use for the `<img>` tag inside `<picture>`. Defaults to `"full"`.
 *     @type bool   $disable_lazy               Whether to disable lazy loading. Defaults to `false` (lazy loading enabled).
 * }
 */
$args = wp_parse_args(
	$args,
	[
		'image'                      => get_field( 'image' ),
		'mobile_image'               => get_field( 'mobile_image' ),
		'class'                      => '',
		'class_container'            => 'c-picture',
		'class_image'                => '',
		'media_srcset'               => [
			'1280px' => 'default',
			'768px'  => 'large',
			'300px'  => 'medium_large',
		],
		'mobile_image_fallback_size' => 'medium_large',
		'image_size'                 => 'full',
		'disable_lazy'               => false,
	]
);

// Set up the class names for the <picture> element.
$picture_classnames = [];
if ( $args['class_container'] ) {
	$picture_classnames[] = $args['class_container'];
}
if ( $args['class'] ) {
	$picture_classnames[] = $args['class'];
}
?>
<?php if ( ! empty( $args['image'] ) ) : ?>
	<picture
		<?php if ( $picture_classnames ) : ?>
			class="<?php echo esc_attr( implode( ' ', $picture_classnames ) ); ?>"
		<?php endif; ?>
	>
		<?php if ( ! empty( $args['media_srcset'] ) && is_array( $args['media_srcset'] ) ) : ?>

			<?php foreach ( $args['media_srcset'] as $media_width => $size ) : ?>

				<?php if ( 'default' === $size ) : ?>
					<source media="(min-width: <?php echo esc_attr( $media_width ); ?>)" srcset="<?php echo esc_url( $args['image']['sizes'][ $args['image_size'] ] ?? $args['image']['url'] ); ?>">
				<?php elseif ( isset( $args['image']['sizes'][ $size ] ) ) : ?>
					<source media="(min-width: <?php echo esc_attr( $media_width ); ?>)" srcset="<?php echo esc_url( $args['image']['sizes'][ $size ] ); ?>">
				<?php endif; ?>

			<?php endforeach; ?>

		<?php endif; ?>

		<?php if ( ! empty( $args['mobile_image'] ) ) : ?>
			<source media="(min-width: 300px)" srcset="<?php echo esc_url( $args['mobile_image']['sizes'][ $args['mobile_image_fallback_size'] ] ?? $args['mobile_image']['url'] ); ?>">
		<?php elseif ( isset( $args['image']['sizes'][ $args['mobile_image_fallback_size'] ] ) ) : ?>
			<source media="(min-width: 300px)" srcset="<?php echo esc_url( $args['image']['sizes'][ $args['mobile_image_fallback_size'] ] ); ?>">
		<?php endif; ?>

		<?php echo ds_generate_image( $args['image']['ID'], $args['image_size'], $args['class_image'], '', ! $args['disable_lazy'] ); // phpcs:ignore ?>
	</picture>
<?php endif; ?>
