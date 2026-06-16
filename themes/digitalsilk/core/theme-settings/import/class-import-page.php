<?php
//phpcs:ignoreFile
Class DSMP_ImportSettingsPage {

    public string $page_slug = 'dsmp-import';

    /**
     * Autoload method
     * @return void
     */
    public function __construct() {
        add_action( 'admin_menu', array( &$this, 'register_sub_menu' ), 99 );

        // add_action( 'admin_enqueue_scripts', array(&$this, 'register_sub_assets') );
    }

    /**
     * Register submenu
     * @return void
     */
    public function register_sub_menu() {
        add_submenu_page(
            'theme-settings',
            __( 'Import Settings', 'dstheme-admin' ),
            __( 'Import Settings', 'dstheme-admin' ),
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

            <h1><?php _e( 'DSMP Import Settings', 'dstheme-admin' ); ?></h1>

            <?php if ( is_multisite() ) : ?>
            <form action="/" style="margin: 20px 0;">
                <h2><?php _e( 'Use muiltisite:', 'dstheme-admin' ); ?></h2>

                <select>
                    <option><?php _e( 'Select site', 'dstheme-admin' ); ?></option>
                    <?php
                    $blogs = get_sites();
                    foreach ( $blogs as $blog ) {
                        $subsite_id = get_object_vars( $blog )['blog_id'];
                        $subsite_name = get_blog_details( $subsite_id )->blogname;
                        echo "<option value='{$subsite_id}'>{$subsite_name}</option>";
                    }
                    ?>
                </select>
                <input type="submit" class="button" id="sync_recipes" value="<?php _e( 'Use' ); ?>">
            </form>
            <?php endif; ?>

            <form action="/" style="margin: 20px 0;">
                <h2><?php _e( 'Import file', 'dstheme-admin' ); ?></h2>

                <input type="file" name="dsmp_import_file">
                <input type="submit" class="button" id="sync_recipes" value="<?php _e( 'Import' ); ?>">
            </form>

            <form action="/" style="margin: 20px 0;">
                <h2><?php _e( 'Import setting', 'dstheme-admin' ); ?></h2>

                <textarea name="dsmp_import_json" style="width: 100%;height:200px;resize: none;"></textarea>
                <input type="submit" class="button" id="sync_recipes" value="<?php _e( 'Import' ); ?>">
            </form>

        </div>
        <?php
    }
}

new DSMP_ImportSettingsPage();
