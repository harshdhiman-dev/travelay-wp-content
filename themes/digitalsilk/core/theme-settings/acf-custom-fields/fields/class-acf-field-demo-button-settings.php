<?php
//phpcs:ignoreFile
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


// check if class already exists
if ( ! class_exists( 'DS_acf_field_Demo_Button_Settings' ) ) :
	#[AllowDynamicProperties]
    class DS_acf_field_Demo_Button_Settings extends acf_field
    {

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

        function __construct( $settings = [] )
        {

            /*
            *  name (string) Single word, no spaces. Underscores allowed
            */

            $this->name = 'demo_button_settings';

            /*
            *  label (string) Multiple words, can include spaces, visible when selecting a field type
            */

            $this->label = __( 'Demo Button Settings', 'dstheme' );

            /*
            *  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
            */

            $this->category = 'Demo';

            /*
            *  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
            */

            $this->defaults = array(
                'link'         => [
                    'url'   => home_url( '/' ),
                    'title' => 'Button',
                ],
                'button_class' => '',
                'styles'       => '',
            );

            $this->settings = $settings;

            // do not delete!
            parent::__construct();
        }


        /*
        *  render_field_settings()
        *
        *  Create extra settings for your field. These are visible when editing a field
        *
        *  @type	action
        *  @since	3.6
        *  @date	23/01/13
        *
        *  @param	$field (array) the $field being edited
        *  @return	n/a
        */

        function render_field_settings( $field )
        {

            /*
            *  acf_render_field_setting
            *
            *  This function will create a setting for your field. Simply pass the $field parameter and an array of field settings.
            *  The array of settings does not require a `value` or `prefix`; These settings are found from the $field array.
            *
            *  More than one setting can be added by copy/paste the above code.
            *  Please note that you must also have a matching $defaults value for the field name (font_size)
            */
            acf_render_field_setting(
                $field,
                array(
					'label'        => __( 'Demo Link', 'dstheme' ),
					'instructions' => __( 'Customise the link', 'dstheme' ),
					'type'         => 'link',
					'name'         => 'link',
                )
            );

            acf_render_field_setting(
                $field,
                array(
					'label'        => __( 'Button class', 'dstheme' ),
					'instructions' => __( 'Customise the button class', 'dstheme' ),
					'type'         => 'text',
					'name'         => 'button_class',
                )
            );

            acf_render_field_setting(
                $field,
                array(
					'label'        => __( 'Wrapper style attribute', 'dstheme' ),
					'instructions' => __( 'Customise the style attribute', 'dstheme' ),
					'type'         => 'text',
					'name'         => 'styles',
                )
            );
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

        function render_field( $field )
        {
            $btn_class = ! empty( $field['button_class'] ) ? " {$field['button_class']}" : '';
            $styles_attr = ! empty( $field['styles'] ) ? ' style="' . $field['styles'] . '"' : '';
            ?>
            <div class="c-btn-bar c-btn-bar__preview"<?php echo $styles_attr; ?>>
                <div class="c-btn-bar__preview__item">
                    <label>CTA Primary</label>
                    <div>
	                    <?php echo acf_button( $field['link'], [ 'class' => "c-btn -primary -small {$btn_class}" ] ); ?>
                        <?php echo acf_button( $field['link'], [ 'class' => "c-btn -primary {$btn_class}" ] ); ?>
                        <?php echo acf_button( $field['link'], [ 'class' => "c-btn -primary -large {$btn_class}" ] ); ?>
                    </div>
                </div>
                <div class="c-btn-bar__preview__item">
                    <label>CTA Secondary</label>
                    <div>
                        <?php echo acf_button( $field['link'], [ 'class' => "c-btn -secondary -small {$btn_class}" ] ); ?>
                        <?php echo acf_button( $field['link'], [ 'class' => "c-btn -secondary {$btn_class}" ] ); ?>
                        <?php echo acf_button( $field['link'], [ 'class' => "c-btn -secondary -large {$btn_class}" ] ); ?>
                    </div>
                </div>
                <div class="c-btn-bar__preview__item">
                    <label>CTA Link Style</label>
                    <div>
                        <?php echo acf_button( $field['link'], [ 'class' => "c-btn -link {$btn_class}" ] ); ?>
                    </div>
                </div>
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

        function input_admin_enqueue_scripts()
        {
            // register & include JS
            wp_register_script( 'ds-button-settings-js', get_template_directory_uri() . '/admin/js/demo-button-settings.js', array( 'acf-input' ), '1.0', true );
            wp_enqueue_script( 'ds-button-settings-js' );
        }
}


// initialize
    new DS_acf_field_Demo_Button_Settings();


// class_exists check
endif;
