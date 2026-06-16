<?php
//phpcs:ignoreFile
/**
 * @var array $args
 */

$args = wp_parse_args(
	$args,
	array(
		'button'        => get_field( 'cta_button' ),
		'popup_type'    => 'content',
		'popup_content' => '',
		'popup_video'   => [
			'popup_video_type' => '',
			'popup_video_url'  => '',
			'popup_video_file' => '',
		],
		'popup_id' => uniqid('p_'),
	)
);
?>

<?php
if ( ! empty( $args['button'] ) && ! empty( $args['button']['title'] ) ) :
	$link_size            = $args['button']['size'] ?? '-normal';
	$link_style           = $args['button']['style'] ?? '-primary';
	$icon_settings        = get_button_icon_settings( $args['button'] );
	$button_args['class'] = "c-btn {$link_size} {$link_style}";
	$button               = [
		'url'    => '#',
		'title'  => $args['button']['title'],
		'target' => '',
	];

	$popup = [
		'open_popup' => $args['popup_type'],
		'popup_id' => $args['popup_id'],
	];
	if ( $args['popup_type'] === 'content' ) {
		$popup['popup_content'] = $args['popup_content'];
	} elseif ( $args['popup_type'] === 'video' ) {
		$popup['popup_video_type'] = $args['popup_video']['popup_video_type'] ?? '';
		$popup['popup_video_url']  = $args['popup_video']['popup_video_url'] ?? '';
		$popup['popup_video_file'] = $args['popup_video']['popup_video_file'] ?? [];
	}
	?>

	<?php
	if (
		( $args['popup_type'] === 'content' && ! empty( $popup['popup_content'] ) )
		|| (
			$args['popup_type'] === 'video'
			&& (
				$popup['popup_video_type'] === 'url' && ! empty( $popup['popup_video_url'] )
				|| $popup['popup_video_type'] === 'file' && ! empty( $popup['popup_video_file'] )
			)
		)
	) {
		echo acf_button( $button, $button_args, $icon_settings['icon'], $icon_settings['icon_args'], $popup ?? [] );
	}
	?>
<?php
endif;
