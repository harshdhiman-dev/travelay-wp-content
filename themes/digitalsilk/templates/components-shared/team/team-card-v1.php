<?php
/**
 * @var array $args
 */

$args      = wp_parse_args(
	$args,
	array(
		'name'                     => get_the_title(),
		'role'                     => get_field( 'position', get_the_ID() ),
		'bio'                      => get_field( 'bio', get_the_ID() ),
		'image'                    => get_the_post_thumbnail( get_the_ID(), 'full' ),
		'socials'                  => get_field( 'socials', get_the_ID() ),
		'email'                    => get_field( 'email', get_the_ID() ),
		'phone'                    => get_field( 'phone', get_the_ID() ),
		'class'                    => '',
		// Settings.
		'component_type'           => get_field( 'component_settings_type' ) ?: 'v1',
		'component_gap_vertical'   => get_field( 'component_settings_inner_gap_vertical' ) ?: 0,
		'component_gap_horizontal' => get_field( 'component_settings_inner_gap_horizontal' ) ?: 0,
		'has_background'           => get_field( 'component_settings_has_background' ) ?: false,
		'has_hover'                => get_field( 'component_settings_has_hover' ) ?: false,
		'has_horizontal_align'     => false,
		'component_background'     => get_field( 'component_settings_component_background' ),
		// Text Component Settings.
		'show_role'                => get_field( 'text_component_settings_show_role' ) ?: false,
		'show_bio'                 => get_field( 'text_component_settings_show_bio' ) ?: false,
		'show_social_networks'     => get_field( 'text_component_settings_show_social_networks' ) ?: false,
		'show_bio_popup'           => get_field( 'show_bio_popup' ) ?: false,
		'popup_trigger_type'       => get_field( 'popup_trigger_type' ) ?: 'button',
		'cta_button'               => get_field( 'cta_button' ) ?? array(),
		'horizontal_alignment'     => get_field( 'text_component_settings_horizontal_alignment' ) ?: 'center',
		'has_vertical_align'       => false,
		'vertical_alignment'       => get_field( 'text_component_settings_vertical_alignment' ) ?: 'top',
		'title_styles'             => get_field( 'component_title_styles' ) ?: array( 'tag' => 'h4' ),
		'title_color'              => get_field( 'text_component_settings_title_color' ),
		'content_color'            => get_field( 'text_component_settings_content_color' ),
		'popup_id'                 => uniqid( 'p_' ),
	)
);
$className = ' c-block';
$styles    = "--c-block__padding-block:{$args['component_gap_vertical']}px;--c-block__padding-inline:{$args['component_gap_horizontal']}px;";

if ( ! empty( $args['class'] ) ) {
	$className .= " {$args['class']}";
}

if ( $args['has_background'] ) {
	$className .= ' has-background';
}

if ( $args['component_background'] ) {
	$styles .= "--c-block__bg:{$args['component_background']};";
}

if ( $args['title_color'] ) {
	$args['title_styles']['color'] = $args['title_color'];
}

if ( $args['has_hover'] ) {
	$className .= ' has-hover';
}

if ( ! empty( $args['horizontal_alignment'] ) ) {
	$className .= " text-{$args['horizontal_alignment']}";
}

if ( ! empty( $args['vertical_alignment'] ) ) {
	$className .= " align-{$args['vertical_alignment']}";
}

// Prepare popup content if bio popup is enabled
$popupContent = '';
if ( $args['show_bio_popup'] && ! empty( $args['bio'] ) ) {
	// Create the popup content directly, avoiding recursive call to team-v1.php
	ob_start();
	?>
	<div class="c-team__details container">
		<div class="c-team__details-inner">
			<div class="c-team__details-info">
				<div>
					<div class="c-team__details-img">
						<div class="c-team c-team__preview">
							<?php if ( ! empty( $args['image'] ) ) : ?>
								<div class="c-block__media">
									<div class="c-media">
										<?php echo wp_kses_post( $args['image'] ); ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<div class="c-team__details-title">
						<?php if ( ! empty( $args['name'] ) ) : ?>
							<h4 class="c-team__details-name"><?php echo esc_html( $args['name'] ); ?></h4>
						<?php endif; ?>

						<?php if ( ! empty( $args['role'] ) ) : ?>
							<div class="c-team__details-role"><?php echo esc_html( $args['role'] ); ?></div>
						<?php endif; ?>
					</div>
					<div class="c-team__details-social">
						<?php if ( ! empty( $args['socials'] ) ) : ?>
							<?php get_template_part( 'templates/components/socials', null, array( 'socials' => $args['socials'] ) ); ?>
						<?php endif; ?>

						<?php if ( ! empty( $args['phone'] ) ) : ?>
							<div class="c-team__details-phone">
								<a href="tel:<?php echo esc_attr( $args['phone'] ); ?>"><?php echo esc_html( $args['phone'] ); ?></a>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $args['email'] ) ) : ?>
							<div class="c-team__details-email">
								<a href="mailto:<?php echo esc_attr( $args['email'] ); ?>"><?php echo esc_html( $args['email'] ); ?></a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="c-team__details-content">
				<?php if ( ! empty( $args['bio'] ) ) : ?>
					<div class="c-team__details-bio is-wysiwyg"><?php echo wp_kses_post( $args['bio'] ); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	$popupContent = ob_get_clean();
}

// Generate a unique element ID for the popup content
$popupContentId = $args['popup_id'];
?>
<?php if ( ! empty( $args['name'] ) || ! empty( $args['role'] ) || ! empty( $args['image'] ) ) : ?>
	<div class="c-team c-team__preview <?php echo esc_attr( $className ); ?>"
		 style="<?php echo esc_attr( $styles ); ?>">
		<?php if ( $args['show_bio_popup'] && 'full_item' === $args['popup_trigger_type'] && ! empty( $popupContent ) ) : ?>
			<a class="c-block__link-full" href="#<?php echo esc_attr( $args['popup_id'] ); ?>"
			   title="<?php echo esc_attr( $args['name'] ); ?>"
			   data-dimbox="<?php echo esc_attr( $popupContentId ); ?>"></a>
		<?php endif; ?>
		<?php if ( ! empty( $args['image'] ) ) : ?>
			<div class="c-block__media">
				<div class="c-media">
					<?php echo wp_kses_post( $args['image'] ); ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="c-block__body">
			<?php if ( $args['show_social_networks'] && ! empty( $args['socials'] ) ) : ?>
				<?php get_template_part( 'templates/components/socials', null, array( 'socials' => $args['socials'] ) ); ?>
			<?php endif; ?>

			<?php if ( ! empty( $args['name'] ) ) : ?>
				<?php echo acf_title( $args['name'], $args['title_styles'], 'c-team__preview-name' ); //phpcs:ignore?>
			<?php endif; ?>

			<?php if ( $args['show_role'] && ! empty( $args['role'] ) ) : ?>
				<div class="c-team__preview-role"><?php echo esc_html( $args['role'] ); ?></div>
			<?php endif; ?>

			<?php
			// Only show bio in grid if popup is NOT enabled
			if ( $args['show_bio'] && ! empty( $args['bio'] ) && ! $args['show_bio_popup'] ) :
				?>
				<div class="c-team__preview-bio">
					<?php echo wp_kses_post( $args['bio'] ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $args['show_bio_popup'] && 'button' === $args['popup_trigger_type'] && ! empty( $popupContent ) ) : ?>
				<div class="c-team__preview-bio-button">
					<?php
					get_template_part(
						'templates/components/cta-popup',
						null,
						array(
							'button'        => $args['cta_button'],
							'popup_content' => $popupContent,
							'popup_id'      => $args['popup_id'],
						)
					);
					?>
				</div>
			<?php endif; ?>

		</div>
	</div>

	<?php if ( $args['show_bio_popup'] && ! empty( $popupContent ) ) : ?>
		<div id="<?php echo esc_attr( $popupContentId ); ?>" class="dimbox-hide">
			<?php echo wp_kses_post( $popupContent ); ?>
		</div>
	<?php endif; ?>
<?php
endif;
