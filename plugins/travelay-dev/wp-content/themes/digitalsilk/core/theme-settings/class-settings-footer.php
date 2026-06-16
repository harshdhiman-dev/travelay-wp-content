<?php
/**
 * Fetch footer template and define necessary items based on chosen footer
 */
class DS_Footer extends DS_Settings {

    /**
     * Stores the menu definitions for the footer.
     *
     * @var array
     */
    private $menu;

    /**
     * Constructor method for initializing the footer menu setup.
     */
    public function __construct() {
        // If the footer is set successfully, register the menus.
        if ( $this->set_footer() ) {
            ds_register_menu( $this->menu );
        }
    }

    /**
     * Defines the footer menus.
     *
     * @return bool Always returns true.
     */
    private function set_footer(): bool {
        $this->menu[] = array(
            'slug' => 'footer-menu',
            'name' => 'Footer Menu',
        );
        $this->menu[] = array(
            'slug' => 'privacy-menu',
            'name' => 'Footer Menu 2',
        );
		$this->menu[] = array(
			'slug' => 'footer-menu-3',
			'name' => 'Footer Menu 3',
		);
		$this->menu[] = array(
			'slug' => 'footer-menu-4',
			'name' => 'Footer Burger Menu',
		);

        return true;
    }
}

add_action(
    'after_setup_theme',
    function () {
        // Initialize the DS_Footer class after the theme setup.
        new DS_Footer();
    }
);
