<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 */
// phpcs:ignoreFile

if ( (int) $moduleConfig->layout_settings_columns_ratio !== 0 ) {
	$moduleConfig->set_style( '--columns-ratio', "{$moduleConfig->layout_settings_columns_ratio}%" );
}

$args = array(
	'cards_widget' => get_field( 'cards_widget' ),
	'layout'       => get_field( 'layout_settings_layout_type' ) ?: 'v1',
	'gap'          => get_field( 'layout_settings_card_gap' ) ?: 0,
);

$cardsListOne = array();
$cardsListTwo = array();

if ( ! empty( $args['cards_widget'] ) ) {
	$cardsList = array_chunk( $args['cards_widget'], ceil( count( $args['cards_widget'] ) / 2 ) ); // Split for two columns

	$cardsListOne = $cardsList[0] ?? array();
	$cardsListTwo = $cardsList[1] ?? array();
}
?>
<div class="m-block<?php echo esc_attr( $block['className'] ); ?> l-content-4" <?php echo $moduleConfig->data_attributes; ?> <?php echo $moduleConfig->get_styles(); ?>>

	<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

	<?php get_template_part( 'templates/components/decorations/module-decorations' ); ?>

	<div class="m-block__container <?php echo $moduleConfig->container; ?>" style="<?php echo $moduleConfig->container_width; ?>">

		<div class="l-ccbl <?php echo "l-ccbl-{$args['layout']}"; ?>" style="--l-block__gap: <?php echo "{$args['gap']}px"; ?>">


			<div class="l-ccbl__img">
				<?php get_template_part( 'templates/components/images/image-v1', null, [ 'image_size' => 'medium_large' ] ); ?>
			</div>

			<?php if ( ! empty( $cardsListOne ) ) : ?>

				<?php foreach ( $cardsListOne as $item ) : ?>
					<div class="l-ccbl__item">
						<?php get_template_part( 'templates/components-shared/blocks/block-v1', null, $item ); ?>
					</div>
				<?php endforeach; ?>

			<?php endif; ?>

			<?php if ( ! empty( $cardsListTwo ) ) : ?>

				<?php foreach ( $cardsListTwo as $item ) : ?>
					<div class="l-ccbl__item">
						<?php get_template_part( 'templates/components-shared/blocks/block-v1', null, $item ); ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

		</div>

	</div>

	<?php get_template_part( 'templates/components/nav/scroll-down' ); ?>

</div>
