<?php
/**
 * DST Button markup
 *
 * @package DST\Blocks\ds_theme
 *
 * @var array    $attributes         Block attributes.
 * @var string   $content            Block content.
 * @var WP_Block $block              Block instance.
 */

$extra_attributes = ds_theme_generate_extra_atts( $attributes );

// Button attributes.
$btn_text = ( isset( $attributes['text'] ) ) ? (string) $attributes['text'] : '';
$btn_link = ( isset( $attributes['link'] ) ) ? (array) $attributes['link'] : [];
$btn_type = ( isset( $attributes['btnType'] ) ) ? (string) $attributes['btnType'] : 'primary';
$btn_size = ( isset( $attributes['btnSize'] ) ) ? (string) $attributes['btnSize'] : 'default';
// Icon attributes.
$icon_type     = ( isset( $attributes['iconType'] ) ) ? (string) $attributes['iconType'] : 'default';
$has_icon      = ( isset( $attributes['hasIcon'] ) ) ? (bool) $attributes['hasIcon'] : false;
$icon_value    = ( isset( $attributes['iconValue'] ) ) ? (string) $attributes['iconValue'] : '';
$icon_reversed = ( isset( $attributes['iconRevesed'] ) ) ? (bool) $attributes['iconRevesed'] : false;
$icon_postion  = ( isset( $attributes['iconPosition'] ) ) ? (string) $attributes['iconPosition'] : 'row-reverse';
// Popup attributes.
$has_popup = ( isset( $attributes['hasPopup'] ) ) ? (bool) $attributes['hasPopup'] : false;

// Create button classes.
$button_classes = [
	'c-btn',
	"-{$btn_size}",
	"-{$btn_type}",
];
if ( isset( $attributes['className'] ) ) {
	$button_classes = array_merge( $button_classes, explode( ' ', $attributes['className'] ) );
}

/**
 * Create all of the variables acf button expects.
 */
$btn_args  = [
	'class' => implode( ' ', array_unique( $button_classes ) ),
];
$icon      = false;
$icon_args = [
	'is_custom_icon' => false,
];

// Check if we need to add a nofollow attribute, extracted from the link.
if ( isset( $btn_link['nofollow'] ) && $btn_link['nofollow'] ) {
	$btn_args['rel'] = 'nofollow';
}

/**
 * Create a button url in the format ACF does.
 */
$btn_link['title'] = $btn_text;
if ( isset( $btn_link['opensInNewTab'] ) && $btn_link['opensInNewTab'] ) {
	$btn_link['target'] = '_blank';
}

/**
 * Create the icon attributes.
 */
switch ( $icon_type ) {
	case 'default':
		$icon      = '';
		$icon_args = [];
		break;
	case 'custom':
		if ( $has_icon && $icon_value ) {
			$icon      = get_svg( [ 'icon' => $icon_value ] );
			$icon_args = [
				'reverse'        => $icon_reversed,
				'is_custom_icon' => true,
			];
			if ( 'row-reverse' === $icon_postion ) {
				$icon_args['position'] = 'left';
			} else {
				$icon_args['position'] = 'right';
			}
		}
		break;
	case 'none':
		$icon      = false;
		$icon_args = [
			'is_custom_icon' => false,
		];
		break;
}

// Check if we need to add a popup.
$popup = '';
if ( $has_popup && $content ) {
	$popup_id                = wp_unique_id( 'ds_btn_popup_' );
	$popup                   = "<div style='display:none'><div id='{$popup_id}'>{$content}</div></div>";
	$btn_args['data-dimbox'] = $popup_id;
	$btn_link['url']         = '#' . $popup_id;
}

echo acf_button( $btn_link, $btn_args, $icon, $icon_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $popup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
