<?php
//phpcs:ignoreFile
// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// check if class already exists
if ( ! class_exists( 'DS_acf_field_Demo_Form_Settings' ) ) :
	#[AllowDynamicProperties]
	class DS_acf_field_Demo_Form_Settings extends acf_field {

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

			$this->name = 'demo_form_settings';

			/*
			*  label (string) Multiple words, can include spaces, visible when selecting a field type
			*/

			$this->label = __( 'Demo Form Settings', 'dstheme' );

			/*
			*  category (string) basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
			*/

			$this->category = 'Demo';

			/*
			*  defaults (array) Array of default settings which are merged into the field object. These are used later in settings
			*/

			$this->defaults = array(
				'label_text'        => 'Label',
				'input_text'        => 'Input with Text',
				'input_placeholder' => 'Placeholder',
				'success_text'      => 'Thank you for your message. It has been sent.',
				'error_text'        => 'The field is required.',
				'desc_text'         => 'Please let us know what\'s on your mind. Have a question for us? Ask away.',

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

		function render_field_settings( $field ) {

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
					'label'        => __( 'Label Text', 'dstheme' ),
					'instructions' => __( 'Customise the label', 'dstheme' ),
					'type'         => 'text',
					'name'         => 'label_text',
                )
            );

			acf_render_field_setting(
                $field,
                array(
					'label'        => __( 'Input Text', 'dstheme' ),
					'instructions' => __( 'Customise the input text', 'dstheme' ),
					'type'         => 'text',
					'name'         => 'input_text',
                )
            );

			acf_render_field_setting(
                $field,
                array(
					'label'        => __( 'Input Placeholder', 'dstheme' ),
					'instructions' => __( 'Customise the input placeholder', 'dstheme' ),
					'type'         => 'text',
					'name'         => 'input_placeholder',
                )
            );

			acf_render_field_setting(
                $field,
                array(
					'label'        => __( 'Success Message', 'dstheme' ),
					'instructions' => __( 'Customise the success message', 'dstheme' ),
					'type'         => 'text',
					'name'         => 'success_text',
                )
            );

			acf_render_field_setting(
                $field,
                array(
					'label'        => __( 'Error Message', 'dstheme' ),
					'instructions' => __( 'Customise the error message', 'dstheme' ),
					'type'         => 'text',
					'name'         => 'error_text',
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

		function render_field( $field ) {
			?>
            <div class="m-form">
                <div class="c-form gform_wrapper gform-theme gravity-theme">
                    <div role="form" class="l-form__form">
                        <form><?php // wp removes first form ?></form>

	                    <div class="gform_validation_errors" id="gform_1_validation_container">
		                    <h2 class="gform_submission_error">
			                    <span class="gform-icon gform-icon--circle-error"></span> <?php echo esc_html( $field['error_text'] ); ?>
		                    </h2>
	                    </div>

	                    <div id="gform_confirmation_wrapper_1" class="gform_confirmation_wrapper gform_wrapper gform-theme gform-theme--foundation gform-theme--framework">
		                    <div class=" gform_confirmation_message"><?php echo esc_html( $field['success_text'] ); ?></div>
	                    </div>

                        <form class="gform_fields gform_validation_error">

	                        <div class="gfield ginput_complex ginput_container gform-grid-row">

	                            <div class="gform-grid-col gform-grid-col--size-auto gfield_error">
	                                <label class="gfield_label gform-field-label"><?php echo esc_html( $field['label_text'] ); ?></label>
		                            <div class="ginput_container ginput_container_text">
	                                    <input type="email" class="large" name="<?php echo esc_attr( $field['name'] ) . '_error_input'; ?>" placeholder="<?php echo esc_attr( $field['input_placeholder'] ); ?>" value=""/>
	                                </div>
		                            <div class="gfield_description validation_message gfield_validation_message"><?php echo esc_html( $field['error_text'] ); ?></div>
	                            </div>

	                            <div class="gform-grid-col gform-grid-col--size-auto">
	                                <label class="gfield_label gform-field-label"><?php echo esc_html( $field['label_text'] ); ?> <span class="gfield_required"><span class="gfield_required gfield_required_text">(Required)</span></span></label>
		                            <div class="ginput_container ginput_container_select">
			                            <select name="input_8" id="input_1_8" class="large gfield_select" aria-required="true" aria-invalid="false">
				                            <option value="" selected="selected" class="gf_placeholder">Select Country</option>
				                            <option value="Afghanistan">Afghanistan</option>
				                            <option value="Albania">Albania</option>
				                            <option value="Algeria">Algeria</option>
				                            <option value="American Samoa">American Samoa</option>
				                            <option value="Andorra">Andorra</option>
			                            </select>
		                            </div>
	                            </div>

	                        </div>

	                        <div class="gfield ginput_complex ginput_container gform-grid-row">

	                            <div class="gform-grid-col gform-grid-col--size-auto">
	                                <label class="gfield_label gform-field-label"><?php echo esc_html( $field['label_text'] ); ?></label>
		                            <div class="ginput_container ginput_container_text">
	                                    <input type="email" class="large" name="<?php echo esc_attr( $field['name'] ) . '_error_input'; ?>" placeholder="<?php echo esc_attr( $field['input_placeholder'] ); ?>" value=""/>
	                                </div>
		                            <div class="gfield_description validation_message gfield_validation_message"><?php echo esc_html( $field['error_text'] ); ?></div>
	                            </div>

	                            <div class="gform-grid-col gform-grid-col--size-auto">
	                                <label class="gfield_label gform-field-label"><?php echo esc_html( $field['label_text'] ); ?></label>
		                            <div class="ginput_container ginput_container_text"">
		                                <input type="text" class="large" name="<?php echo esc_attr( $field['name'] ) . '_success_input'; ?>" placeholder="<?php echo esc_attr( $field['input_placeholder'] ); ?>" value="<?php echo esc_attr( $field['input_text'] ); ?>"/>
		                            </div>
	                            </div>

	                        </div>

			                <div class="gfield gfield--type-textarea gfield--input-type-textarea gfield_contains_required gfield--has-description field_description_above field_validation_below">
				                <label class="gfield_label gform-field-label" for="input_1_3">Comments<span class="gfield_required"><span class="gfield_required gfield_required_asterisk">*</span></span></label>
				                <div class="gfield_description"><?php echo esc_html( $field['desc_text'] ); ?></div>
				                <div class="ginput_container ginput_container_textarea">
					                <textarea class="textarea medium" maxlength="600" aria-required="true" aria-invalid="true" rows="10" cols="50" spellcheck="false"></textarea>
				                </div>
			                </div>

			                <fieldset class="gfield gfield--type-radio gfield--type-choice gfield--input-type-radio field_description_above">
				                <label class="gfield_label gform-field-label">Radio Buttons</label>
				                <div class="ginput_container ginput_container_radio">
					                <div class="gfield_radio" id="input_1_5">
						                <div class="gchoice gchoice_1_5_0">
							                <input class="gfield-choice-input" name="input_5" type="radio" value="First Choice" id="choice_1_5_0" onchange="gformToggleRadioOther( this )">
							                <label for="choice_1_5_0" id="label_1_5_0" class="gform-field-label gform-field-label--type-inline">First Choice</label>
						                </div>
						                <div class="gchoice gchoice_1_5_1">
							                <input class="gfield-choice-input" name="input_5" type="radio" value="Second Choice" id="choice_1_5_1" onchange="gformToggleRadioOther( this )">
							                <label for="choice_1_5_1" id="label_1_5_1" class="gform-field-label gform-field-label--type-inline">Second Choice</label>
						                </div>
					                </div>
				                </div>
			                </fieldset>

			                <fieldset class="gfield gfield--type-checkbox gfield--type-choice gfield--input-type-checkbox field_validation_below">
				                <label class="gfield_label gform-field-label">Checkbox</label>
				                <div class="ginput_container ginput_container_checkbox">
					                <div class="gfield_checkbox " id="input_1_7">
						                <div class="gchoice gchoice_1_7_1">
							                <input class="gfield-choice-input" name="input_7.1" type="checkbox" value="First Choice" id="choice_1_7_1">
							                <label for="choice_1_7_1" id="label_1_7_1" class="gform-field-label gform-field-label--type-inline">First Choice</label>
						                </div>
					                </div>
				                </div>
			                </fieldset>

			                <div class="gform-footer gform_footer top_label">
				                <input type="submit" class="gform_button button">
			                </div>

                        </form>
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

		function input_admin_enqueue_scripts() {
			// register & include JS
			wp_register_script( 'ds-form-settings-js', get_template_directory_uri() . '/admin/js/demo-form-settings.js', array( 'acf-input' ), '1.0', true );
			wp_enqueue_script( 'ds-form-settings-js' );
		}
}


// initialize
	new DS_acf_field_Demo_Form_Settings();


// class_exists check
endif;
