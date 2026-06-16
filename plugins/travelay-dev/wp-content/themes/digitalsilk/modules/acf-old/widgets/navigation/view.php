<?php
/**
 * Image Rotation Main Template.
 *
 * @var array $block The block settings. Most used ['className'],['id'],['anchor']
 * @var object $moduleConfig ->get_styles(), ->data_attributes, ->container, ->container_width.
 *
 * @package DS_Theme
 */

$anchor_navigation = get_field( 'anchor_navigation' );
$style             = get_field( 'component_settings_style' );
$nav_orientation   = get_field( 'component_settings_orientation' );
$position          = get_field( 'component_settings_position' );
$add_icon          = get_field( 'component_settings_add_icon' );
$icon              = get_field( 'component_settings_icon' );
$icon_position     = get_field( 'component_settings_icon_position' );

$args = array(
	'anchor_navigation' => $anchor_navigation ? $anchor_navigation : array(),
	'style'             => $style ? $style : 'v1',
	'nav_orientation'   => $nav_orientation ? $nav_orientation : 'vertical',
	'position'          => $position ? $position : 'left',
	'add_icon'          => $add_icon ? $add_icon : false,
	'icon'              => $icon ? $icon : '',
	'icon_position'     => $icon_position ? $icon_position : 'left',
)
?>

<?php if ( ! empty( $args['anchor_navigation'] ) ) : ?>
    <?php //phpcs:ignore ?>
	<div class="m-side-nav <?php echo "l-side-nav-{$args['style']}"; ?> <?php echo "-{$args['nav_orientation']}"; ?> <?php echo "-{$args['position']}"; ?>">

		<?php get_template_part( 'templates/components/anchor', null, array( 'anchor_id' => $block['anchor'] ?? '' ) ); ?>

		<?php foreach ( $args['anchor_navigation'] as $key => $item ) : ?>
			<?php
			get_template_part(
				'templates/components/nav/nav-anchor',
				null,
				array(
					'label'         => $item['label'],
					'link_type'     => $item['type'] ?? '',
					'anchor_target' => $item['anchor_available_blocks'] ?? '',
					'link'          => $item['link'],
					'has_link'      => true,
					'add_icon'      => $args['add_icon'],
					'icon'          => $args['icon'],
					'icon_position' => $args['icon_position'],
				)
			);
			?>
		<?php endforeach; ?>
	</div>
	<?php
endif;
