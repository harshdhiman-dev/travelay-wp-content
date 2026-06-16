<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
        'taxonomy'       => '',
        'terms'          => array(),
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
        <?php foreach ( $args['terms'] as $term ) : ?>

            <?php $checked = '';
            if( isset( $_GET ) ){
                foreach ( $_GET as $key => $value ) {

                    if( $args[ 'param_name' ] == $key ){
                        if( is_array($value) ){
                            foreach ( $value as $val ){
                                if( $val == $term->slug ){
                                    $checked = 'checked';
                                }
                            }
                        }
                        elseif( $value == $term->slug ){
                            $checked = 'checked';
                        }

                    }
                }
            }
            ?>
            <div class="blog-filter__item">
                <label for="<?php echo 'term-' . $term->slug; ?>">
                    <input type="checkbox"
                           name="<?php echo esc_attr( $args['param_name'] ); ?>[]"
                           id="<?php echo 'term-' . $term->slug; ?>"
                           value="<?php echo esc_attr( $term->slug ); ?>"
                           <?php echo esc_attr( $checked ); ?>>

                    <?php echo wp_kses_post( $term->name ); ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif;
