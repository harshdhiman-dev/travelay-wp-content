<?php
//phpcs:ignoreFile

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// check if class already exists
if ( ! class_exists( 'DS_acf_field_Demo_Content_Settings' ) ) :
	#[AllowDynamicProperties]
	class DS_acf_field_Demo_Content_Settings extends acf_field {

		/*
		*  __construct
		*
		*  This function will setup the field type data
		*
		*  @type	function
		*  @date	5/03/2014
		*  @since	5.0.0
		*
		*  @param	n/a
		*  @return	n/a
		*/

		function __construct( $settings = [] ) {

			/*
			*  name (string) Single word, no spaces. Underscores allowed
			*/
			$this->name = 'demo_content_settings';

			/*
			*  label (string) Multiple words, can include spaces, visible when selecting a field type
			*/
			$this->label = __( 'Demo Content Settings', 'dstheme' );

			/*
			*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
			*/
			$this->category = 'Demo';

			$this->settings = $settings;

			// do not delete!
			parent::__construct();
		}

		/*
		*  render_field()
		*
		*  Create the HTML interface for your field
		*
		*  @param	$field (array) the $field being rendered
		*
		*  @type	action
		*  @since	3.6
		*  @date	23/01/13
		*
		*  @param	$field (array) the $field being edited
		*  @return	n/a
		*/
		function render_field( $field ) {
			?>
			<div class="c-wysiwyg-preview">
				<section>
					<div class="is-wysiwyg">
						<div class="-h2"><h2><?php _e( 'Heading 2', 'dstheme' ); ?></h2></div>
						<div class="-h3"><h3><?php _e( 'Heading 3', 'dstheme' ); ?></h3></div>
						<div class="-h4"><h4><?php _e( 'Heading 4', 'dstheme' ); ?></h4></div>
						<div class="-h5"><h5><?php _e( 'Heading 5', 'dstheme' ); ?></h5></div>
						<p class="large-text"><?php _e( '<strong>Larger</strong> paragraph text. This is a sample paragraph of text that will be displayed on the website. It is a placeholder and should be replaced with actual content.', 'dstheme' ); ?></p>
						<p class="small-text"><?php _e( '<strong>Smaller</strong> paragraph text. This is a sample paragraph of text that will be displayed on the website. It is a placeholder and should be replaced with actual content.', 'dstheme' ); ?></p>
						<p><?php _e( 'This is a sample paragraph of text that will be displayed on the website. <a href="#">This is a link</a>. It is a placeholder and should be replaced with actual content.', 'dstheme' ); ?></p>
						<p><?php _e( 'This is another sample paragraph of text that will be displayed on the website. It is also a placeholder and should be replaced with actual content.', 'dstheme' ); ?></p>
						<ul>
							<li><?php _e( 'Unordered List Item 1', 'dstheme' ); ?></li>
							<li><?php _e( 'Unordered List Item 2', 'dstheme' ); ?></li>
							<li><?php _e( '<a href="#">This is a link</a>', 'dstheme' ); ?></li>
						</ul>
						<ol>
							<li><?php _e( 'Ordered List Item 1', 'dstheme' ); ?></li>
							<li><?php _e( 'Ordered List Item 2', 'dstheme' ); ?></li>
							<li><?php _e( 'Ordered List Item 2', 'dstheme' ); ?></li>
						</ol>
						<table>
							<thead>
							<tr>
								<th><?php _e( 'Table Head 1', 'dstheme' ); ?></th>
								<th><?php _e( 'Table Head 2', 'dstheme' ); ?></th>
								<th><?php _e( 'Table Head 3', 'dstheme' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<tr>
								<td><?php _e( 'Table Cell 1', 'dstheme' ); ?></td>
								<td><?php _e( 'Table Cell 2', 'dstheme' ); ?></td>
								<td><?php _e( 'Table Cell 3', 'dstheme' ); ?></td>
							</tr>
							<tr>
								<td><?php _e( 'Table Cell 1', 'dstheme' ); ?></td>
								<td><?php _e( 'Table Cell 2', 'dstheme' ); ?></td>
								<td><?php _e( 'Table Cell 3', 'dstheme' ); ?></td>
							</tr>
							</tbody>
						</table>
						<blockquote class="wp-block-quote"><?php _e( 'This is a longer blockquote text that serves as a placeholder for actual content. It should be replaced with a meaningful quote or phrase.', 'dstheme' ); ?></blockquote>
						<p><?php _e( 'Last paragraph text goes here.', 'dstheme' ); ?></p>
					</div>
				</section>
				<section class="is-style-colors-inverted" style="background-color: #666">
					<div class="is-wysiwyg">
						<div class="-h2"><h2><?php _e( 'Heading 2', 'dstheme' ); ?></h2></div>
						<div class="-h3"><h3><?php _e( 'Heading 3', 'dstheme' ); ?></h3></div>
						<div class="-h4"><h4><?php _e( 'Heading 4', 'dstheme' ); ?></h4></div>
						<div class="-h5"><h5><?php _e( 'Heading 5', 'dstheme' ); ?></h5></div>
						<p class="large-text"><?php _e( '<strong>Larger</strong> paragraph text. This is a sample paragraph of text that will be displayed on the website. It is a placeholder and should be replaced with actual content.', 'dstheme' ); ?></p>
						<p class="small-text"><?php _e( '<strong>Smaller</strong> paragraph text. This is a sample paragraph of text that will be displayed on the website. It is a placeholder and should be replaced with actual content.', 'dstheme' ); ?></p>
						<p><?php _e( 'This is a sample paragraph of text that will be displayed on the website. <a href="#">This is a link</a>. It is a placeholder and should be replaced with actual content.', 'dstheme' ); ?></p>
						<p><?php _e( 'This is another sample paragraph of text that will be displayed on the website. It is also a placeholder and should be replaced with actual content.', 'dstheme' ); ?></p>
						<ul>
							<li><?php _e( 'Unordered List Item 1', 'dstheme' ); ?></li>
							<li><?php _e( 'Unordered List Item 2', 'dstheme' ); ?></li>
							<li><?php _e( '<a href="#">This is a link</a>', 'dstheme' ); ?></li>
						</ul>
						<ol>
							<li><?php _e( 'Ordered List Item 1', 'dstheme' ); ?></li>
							<li><?php _e( 'Ordered List Item 2', 'dstheme' ); ?></li>
							<li><?php _e( 'Ordered List Item 2', 'dstheme' ); ?></li>
						</ol>
						<table>
							<thead>
							<tr>
								<th><?php _e( 'Table Head 1', 'dstheme' ); ?></th>
								<th><?php _e( 'Table Head 2', 'dstheme' ); ?></th>
								<th><?php _e( 'Table Head 3', 'dstheme' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<tr>
								<td><?php _e( 'Table Cell 1', 'dstheme' ); ?></td>
								<td><?php _e( 'Table Cell 2', 'dstheme' ); ?></td>
								<td><?php _e( 'Table Cell 3', 'dstheme' ); ?></td>
							</tr>
							<tr>
								<td><?php _e( 'Table Cell 1', 'dstheme' ); ?></td>
								<td><?php _e( 'Table Cell 2', 'dstheme' ); ?></td>
								<td><?php _e( 'Table Cell 3', 'dstheme' ); ?></td>
							</tr>
							</tbody>
						</table>
						<blockquote class="wp-block-quote"><?php _e( 'This is a longer blockquote text that serves as a placeholder for actual content. It should be replaced with a meaningful quote or phrase.', 'dstheme' ); ?></blockquote>
						<p><?php _e( 'Last paragraph text goes here.', 'dstheme' ); ?></p>
					</div>
				</section>
			</div>
			<?php
		}

		/*
        *  input_admin_enqueue_scripts()
        *
        *  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
        *  Use this action to add CSS + JavaScript to assist your render_field() action.
        *
        *  @type	action (admin_enqueue_scripts)
        *  @since	3.6
        *  @date	23/01/13
        *
        *  @param	n/a
        *  @return	n/a
        */
		function input_admin_enqueue_scripts() {
			// register & include JS
			wp_register_script( 'ds-content-settings-js', get_template_directory_uri() . '/admin/js/demo-content-settings.js', array( 'acf-input' ), '1.0', true );
			wp_enqueue_script( 'ds-content-settings-js' );
		}
	}

// initialize
	new DS_acf_field_Demo_Content_Settings();

// class_exists check
endif;
