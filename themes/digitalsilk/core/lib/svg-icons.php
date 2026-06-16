<?php
/**
 * Helper functions to handle svg icons
 *
 * @package DS_Theme
 */

/**
 * Add mime types
 *
 * @param array $mimes contains existing mime types.
 * @return mixed
 */
function svg_mime_types( $mimes ) {
	$mimes['svg'] = 'image/svg+xml';

	return $mimes;
}

add_filter( 'upload_mimes', 'svg_mime_types' );

/**
 * Fix SVG style
 */
function fix_svg() {
	echo '<style type="text/css">
          .attachment-266x266, .thumbnail img {
               width: 100% !important;
               height: auto !important;
          }
          </style>';
}

add_action( 'admin_head', 'fix_svg' );

/**
 * Add SVG definitions to the footer
 */
function include_svg_icons() {
	// Define SVG sprite file.
	$svg_icons = get_parent_theme_file_path( '/assets/_dist/assets/spritemap.svg.php' );
	// If it exists, include it.
	if ( file_exists( $svg_icons ) ) { ?>
		<div class="svg-sprite" style="position: absolute; width: 0; height: 0;">
			<?php get_template_part( '/assets/_dist/assets/spritemap.svg' ); ?>
		</div>
		<?php
	}

	if ( class_exists( 'woocommerce' ) ) {
		// Define SVG sprite file.
		$svg_icons_wc = get_parent_theme_file_path( '/assets/_dist/assets/spritemap-wc.svg.php' );
		// If it exists, include it.
		if ( file_exists( $svg_icons_wc ) ) {
			?>
				<div class="svg-sprite -wc" style="position: absolute; width: 0; height: 0;">
					<?php get_template_part( '/assets/_dist/assets/spritemap-wc.svg' ); ?>
				</div>
			<?php
		}
	}
}

add_action( 'wp_body_open', 'include_svg_icons', 9999 );
add_action( 'admin_head', 'include_svg_icons', 9999 );

/**
 * Return SVG markup.
 *
 * @param array $args {
 *     Parameters needed to display an SVG.
 *
 *     @type string $icon  Required SVG icon filename.
 *     @type string $title Optional SVG title.
 *     @type string $desc  Optional SVG description.
 *     @type bool   $echo  Optional. Whether to echo the SVG markup, or return it. Default is false.
 * }
 * @return string SVG markup.
 */
function get_svg( $args = array() ) {
	// Make sure $args are an array.
	if ( empty( $args ) ) {
		return __( 'Please define default parameters in the form of an array.', 'dstheme' );
	}

	// Define an icon.
	if ( false === array_key_exists( 'icon', $args ) ) {
		return __( 'Please define an SVG icon filename.', 'dstheme' );
	}

	// Parse args.
	$args = wp_parse_args(
		$args,
		[
			'icon'        => '',
			'title'       => '',
			'desc'        => '',
			'aria_hidden' => true, // Hide from screen readers.
			'fallback'    => false,
			'class'       => '',
			'echo'        => false,
			'size'        => 30,
		]
	);

	// Define icon.
	$icon = $args['icon'];

	// Set aria hidden.
	$aria_hidden = '';

	if ( true === $args['aria_hidden'] ) {
		$aria_hidden = ' aria-hidden="true"';
	}

	/**
	 * If icon is numeric, it means we are dealing with an image from a media library.
	 */
	if ( is_numeric( $icon ) ) {
		// Get the attachment object.
		$attachment = get_post( $icon );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			return __( 'Your current icon is deleted. Please change the icon.', 'dstheme' );
		}

		// Ensure it's an SVG file.
		if ( strpos( $attachment->post_mime_type, 'svg' ) !== false ) {
			$file_path   = get_attached_file( $icon );
			$svg_content = file_get_contents( $file_path ); // phpcs:ignore.

			if ( $svg_content ) {
				if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
					$processor = new WP_HTML_Tag_Processor( $svg_content );

					if ( $processor->next_tag( 'svg' ) ) {
						$processor->set_attribute( 'width', (string) $args['size'] );
						$processor->set_attribute( 'height', (string) $args['size'] );

						$svg_content = $processor->get_updated_html();
					}
				}
				if ( $args['echo'] ) {
					echo wp_kses( $svg_content, svg_kses_extended_ruleset() ); // phpcs:ignore
				}
				return $svg_content;
			}
		}

		$image = wp_get_attachment_image(
			$icon, // Attachment ID.
			[ $args['size'], $args['size'] ], // Size.
			true, // Treat it as an icon.
			[
				'class' => 'icon icon-' . $icon . ' -library ',
				'role'  => 'presentation',
			]
		);
		if ( $args['echo'] ) {
			echo $image; // phpcs:ignore
		}
		return $image;
	}

	// Set ARIA.
	$aria_labelledby = '';

	if ( $args['title'] && $args['desc'] ) {
		$aria_labelledby = ' aria-labelledby="title desc"';
	}

	// Begin SVG markup.
	$svg = '<svg width="' . esc_attr( $args['size'] ) . '" height="' . esc_attr( $args['size'] ) . '" class="icon icon-' . esc_attr( $icon ) . ' ' . esc_attr( $args['class'] ) . '" ' . $aria_hidden . $aria_labelledby . ' role="img">';

	// If there is a title, display it.
	if ( $args['title'] ) {
		$svg .= '<title>' . esc_html( $args['title'] ) . '</title>';
	}

	// If there is a description, display it.
	if ( $args['desc'] ) {
		$svg .= '<desc>' . esc_html( $args['desc'] ) . '</desc>';
	}

	$svg .= '<use xlink:href="#' . esc_html( $icon ) . '"></use>';

	// Add some markup to use as a fallback for browsers that do not support SVGs.
	if ( $args['fallback'] ) {
		$svg .= '<span class="svg-fallback icon-' . esc_attr( $icon ) . '"></span>';
	}

	$svg .= '</svg>';

	if ( $args['echo'] ) {
		echo wp_kses( $svg, svg_kses_extended_ruleset() );
	}
	return wp_kses( $svg, svg_kses_extended_ruleset() );
}

/**
 * Get the icon from an icon picker component.
 *
 * @param array $args {
 *     Parameters needed to display an SVG.
 * }.
 */
function get_icon( $args ) {

	// Default arguments.
	$defaults = [
		'icon'   => '',
		'size'   => 30,
		'echo'   => true,
		'inline' => true,
	];

	// Parse arguments.
	$args = wp_parse_args( $args, $defaults );

	// Bail early if no icon is provided.
	if ( empty( $args['icon'] ) ) {
		return '';
	}

	$icon          = $args['icon'];
	$is_uploaded   = is_numeric( $icon );
	$rendered_icon = '';

	if ( $is_uploaded && ! $args['inline'] ) {
		$rendered_icon = wp_get_attachment_image(
			$icon,
			'medium',
			true,
			[
				'class' => 'icon icon-' . esc_attr( $icon ) . ' -img-format',
			]
		);
		// Update width and height attributes for the image.
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new WP_HTML_Tag_Processor( $rendered_icon );
			if ( $processor->next_tag( 'img' ) ) {
				$processor->set_attribute( 'width', (string) $args['size'] );
				$processor->set_attribute( 'height', (string) $args['size'] );
				$rendered_icon = $processor->get_updated_html();
			}
		}
	} else {
		$rendered_icon = get_svg(
			[
				'icon' => $icon,
				'size' => $args['size'],
				'echo' => false,
			]
		);
	}

	if ( $args['echo'] ) {
		echo $rendered_icon; // phpcs:ignore
	}
	return $rendered_icon;
}
