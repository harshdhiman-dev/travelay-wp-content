<?php
//phpcs:ignoreFile
Class DSMP_ExportSettingsPage {

    public string $page_slug = 'dsmp-export';

    private string $export_file;

    /**
     * Autoload method
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', array( &$this, 'register_sub_menu' ), 99 );

        $this->export_file = wp_get_upload_dir()['basedir'] . '/dsmp-assets/theme.css';

        // add_action( 'admin_enqueue_scripts', array(&$this, 'register_sub_assets') );
    }

    /**
     * Register submenu
     * @return void
     */
    public function register_sub_menu() {
        add_submenu_page(
            'theme-settings',
            __( 'Export Settings', 'dstheme-admin' ),
            __( 'Export Settings', 'dstheme-admin' ),
            'manage_options',
            $this->page_slug,
            array( &$this, 'submenu_page_callback' )
        );
    }

    /**
     * Register submenu assets
     * @return void
     */
// public function register_sub_assets() {
// $screen = get_current_screen();
//
// if( $screen->base === $this->page_slug ){
// wp_enqueue_script( 'dsmp-import-js', get_template_directory_uri() . '/admin/js/dsmp-import.js', array() , 1.0, true );
// wp_localize_script( 'dsmp-import-js', 'ajax',
// array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
// }
// }

    /**
     * Render submenu
     * @return void
     */
    public function submenu_page_callback() {
        ?>
        <div class="wrap" style="max-width: 850px;">
            <style>

            </style>

            <?php if ( ! empty( $this->export_file ) ) : ?>

                <h1><?php _e( 'DSMP Export Settings', 'dstheme-admin' ); ?></h1>

                <h2><?php _e( 'Download export file', 'dstheme-admin' ); ?></h2>
                <a class="button" href="<?php echo $this->export_file; ?>" download><?php _e( 'Download export file', 'dstheme-admin' ); ?></a>

                <h2><?php _e( 'Copy export', 'dstheme-admin' ); ?></h2>
                <textarea name="import_json" style="width: 100%;height:200px;resize: none;">
                    <?php echo file_get_contents( $this->export_file ); ?>
                </textarea>

            <?php else : ?>
                <p><?php _e( 'Export not available!', 'dstheme-admin' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}

new DSMP_ExportSettingsPage();
