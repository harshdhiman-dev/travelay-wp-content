<?php
/**
 * Fetch header template and define necessary items based on chosen header
 */
class DS_Header extends DS_Settings {

    /**
     * Stores menus for the header
     *
     * @var array
     */
    private $menu;

    /**
     * Sticky type for the header
     *
     * @var string
     */
    private $sticky_type;

    /**
     * DS_Header constructor.
     * Initializes the header settings and registers the menus.
     */
    public function __construct() {
        if ( $this->set_header() ) {
            ds_register_menu( $this->menu );
        }
    }

    /**
     * Sets up the header properties, including menus and sticky type.
     *
     * @return bool
     */
    private function set_header(): bool {
        // Define primary and secondary header menus.
        $this->menu[] = array(
            'slug' => 'primary-menu',
            'name' => 'Header Menu',
        );
        $this->menu[] = array(
            'slug' => 'secondary-menu',
            'name' => 'Secondary Header Menu',
        );

        // Retrieve sticky type setting and store it globally.
        $this->sticky_type = $this->get_setting( 'header_sticky_type' );
        $this->set_global( 'header_sticky_type', $this->sticky_type );

        // Add sticky type as a body class.
        add_filter(
            'body_class',
            function ( $classes ) {
                return array_merge( $classes, array( $this->sticky_type . '-header' ) );
            }
        );

        return true;
    }
}

add_action(
    'after_setup_theme',
    function () {
        new DS_Header();
    }
);
