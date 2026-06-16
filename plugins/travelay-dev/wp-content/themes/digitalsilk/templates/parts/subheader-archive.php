<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'background_image' => get_field( 'image', get_option( 'page_for_posts' ) ),
		'pretitle'         => get_field( 'pretitle', get_option( 'page_for_posts' ) ),
		'title'            => get_field( 'title', get_option( 'page_for_posts' ) ),
		'subtitle'         => get_field( 'subtitle', get_option( 'page_for_posts' ) ),
		'search_form'      => '',
		'subheader_show'   => false,
	)
);
?>

<?php if ( 'show' === $args['subheader_show'] ) : ?>
	<div class="m-banner m-banner--custom order-default m-banner__blog has-overlay">
		<div class="m-banner__container container-fluid">
			<?php if ( ! empty( $args['background_image']['ID'] ) ) : ?>
				<div class="m-banner__media">
					<?php echo ds_generate_image( $args['background_image']['ID'], 'full', 'c-media__src m-banner__picture' ); ?>
				</div>
			<?php endif; ?>
			<div class="m-banner__inner container">
				<div class="l-banner space-top-custom space-bottom-custom">
					<div class="l-banner__text">
						<div class="l-banner__inner">
							<?php if ( ! empty( $args['pretitle'] ) ) : ?>
								<div class="c-heading__pre"><?php echo wp_kses_post( $args['pretitle'] ); ?></div>
							<?php endif; ?>
							<?php if ( ! empty( $args['title'] ) ) : ?>
								<div class="c-heading -h1">
									<h1 class="c-heading__title"><?php echo wp_kses_post( $args['title'] ); ?></h1>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $args['subtitle'] ) ) : ?>
								<div class="c-banner__description">
									<p><?php echo wp_kses_post( $args['subtitle'] ); ?></p>
								</div>
							<?php endif; ?>
							<?php if ( ! empty( $args['search_form'] ) ) : ?>
								<div class="c-banner__search">
									<?php echo $args['search_form']; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
endif;
