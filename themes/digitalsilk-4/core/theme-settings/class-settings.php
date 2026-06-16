<?php
/**
 * $dsmp_settings - global variable
 * contains theme settings such as header/footer templates, etc.
 */

global $dsmp_settings;

#[AllowDynamicProperties]
class DS_Settings {

    /**
     * Magic method to retrieve a property value.
     *
     * @param string $key The name of the property.
     * @return mixed The value of the property or an empty string if not set.
     */
	public function __get( $key ) {
		return empty( $this->{$key} ) ? '' : $this->{$key};
	}

    /**
     * Magic method to set a property value.
     *
     * @param string $key The name of the property.
     * @param mixed  $value The value to set the property to.
     * @return void
     */
	public function __set( $key, $value ) {
		$this->set_global( $key, $value );
	}

    /**
     * Retrieve a setting value from the database using the `get_option` function.
     *
     * @param string $get The name of the setting option.
     * @return mixed The value of the setting.
     */
	public function get_setting( $get ) {
		return get_option( "options_{$get}" );
	}

    /**
     * Retrieve an ACF setting value, especially for advanced fields like groups with subfields.
     *
     * @param string $get The name of the ACF field.
     * @return mixed The value of the ACF field.
     */
	public function get_setting_acf( $get ) {
		return get_field( $get, 'options' );
	}

    /**
     * Set a value globally by adding it to the global `$dsmp_settings` object.
     *
     * @param string $key The key under which the value will be stored.
     * @param mixed  $value The value to store.
     * @return bool Always returns true.
     */
	public function set_global( $key, $value ) {
		global $dsmp_settings;
		if ( isset( $dsmp_settings->{$key} ) ) {
			if ( ! is_array( $dsmp_settings->{$key} ) ) {
				$dsmp_settings->{$key} = array( $dsmp_settings->{$key} );
			}

            $dsmp_settings->{$key}[] = $value;
        } else {
            $dsmp_settings->test = $value; // Assign the value to a test property.
            $dsmp_settings->{$key} = $value;
        }

        return true;
    }
}

// Instantiate the global DS_Settings object.
$dsmp_settings = new DS_Settings();
