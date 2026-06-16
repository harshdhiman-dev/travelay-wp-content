<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
        'taxonomy'   => '',
		'terms'      => array(),
		'param_name' => 'tag',
	)
);

$className = '';
if ( ! empty( $args['class'] ) ) {
	$className .= "{$args['class']}";
}

if( empty($args['terms']) && ! empty($args['taxonomy']) ){
    $args['terms'] = get_terms( $args[ 'taxonomy' ], array( 'hide_empty' => true ) );
}
?>

<?php if ( ! empty( $args['terms'] ) ) : ?>
	<div class="<?php echo esc_attr( $className ); ?>" >
        <label for="<?php echo 'term-all'; ?>">
            <input type="radio"
                   name="<?php echo esc_attr( $args['param_name'] ); ?>"
                   id="<?php echo 'term-all'; ?>"
                   value="">
            <?php _e( 'All', 'dstheme' ); ?>
        </label>

		<?php foreach ( $args['terms'] as $term ) : ?>
            <label for="<?php echo 'term-' . $term->slug; ?>">
                <input type="radio"
                       name="<?php echo $args['param_name']; ?>"
                       id="<?php echo 'term-' . $term->slug; ?>"
                       value="<?php echo $term->slug; ?>"
                       <?php echo ( isset( $_GET[ $args['param_name'] ] ) && $_GET[ $args['param_name'] ] === $term->slug ) ? 'checked' : ''; ?>>
                <?php echo wp_kses_post( $term->name ); ?>
            </label>
		<?php endforeach ?>
	</div>
	<?php
endif;
