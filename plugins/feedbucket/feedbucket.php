<?php
/**
 * Plugin Name: Feedbucket
 * Plugin URI: https://feedbucket.app
 * Description: Enabling your clients and team members to submit feedback with screenshots and video recording directly on your WordPress site.
 * Version: 1.0.8
 * Author: Feedbucket
 * Author URI: https://feedbucket.app
 */

register_activation_hook(__FILE__, 'feedbucket_activation');
function feedbucket_activation() {
    // Default values
    $options = [
        'key' => '',
        'enable' => true,
        'enableAdmin' => false,
        'visibility' => 'all',
        'roles' => [],
        'setReporter' => true
    ];
    add_option('feedbucket_options', $options);
}

register_uninstall_hook(__FILE__, 'feedbucket_uninstall');
function feedbucket_uninstall() {
    delete_option('feedbucket_options');
}

function feedbucket_enqueue_admin_scripts($hook) {
    if ($hook !== 'settings_page_feedbucket-plugin') {
        return;
    }

    wp_enqueue_style('feedbucket_admin_styles', plugin_dir_url(__FILE__) . 'dist/styles.css', [], '1.0.0');
    wp_enqueue_script('feedbucket_vue', plugin_dir_url(__FILE__) . 'dist/vue.js');    
}
add_action('admin_enqueue_scripts', 'feedbucket_enqueue_admin_scripts');

function feedbucket_add_menu_item() {
    add_options_page(
        'Feedbucket settings',
        'Feedbucket',
        'manage_options',
        'feedbucket-plugin',
        'feedbucket_settings_page',
    );
}
add_action('admin_menu', 'feedbucket_add_menu_item');

function feedbucket_settings_link($links) {
    $url = esc_url(add_query_arg(
        'page',
        'feedbucket-plugin',
        get_admin_url() . 'options-general.php?page=feedbucket-plugin'
    ));

    $settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';

	array_push(
		$links,
		$settings_link
	);

	return $links;
}
add_filter('plugin_action_links_feedbucket/feedbucket.php', 'feedbucket_settings_link');

function feedbucket_settings_page() {
    if (! current_user_can('manage_options')) {
        return;
    }

    $data = get_option('feedbucket_options', array());
    $wp_roles = get_editable_roles();
    $roles = [];
    foreach ($wp_roles as $slug => $role) {
        $roles[] = [
            'slug' => $slug,
            'name' => $role['name']
        ];
    }
    $logo = plugin_dir_url(__FILE__).'dist/feedbucket_logo.png';

    require_once 'templates/feedbucket-plugin-admin.php';
}

function feedbucket_save_options() {
    check_ajax_referer('feedbucket_save_settings');
    
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Forbidden', 403);
    }

    $roles = [];
    $wp_roles = array_keys(get_editable_roles());
    if (sanitize_text_field($_POST['visibility']) === 'role') {
        foreach($_POST['roles'] as $role) {
            $role = strtolower(sanitize_text_field($role));
            if (in_array($role, $wp_roles)) {
                $roles[] = $role;
            }
        }
    }

    $newOptions = [
        'key' => sanitize_text_field($_POST['key']),
        'enable' => sanitize_text_field($_POST['enable']) === 'true',
        'enableAdmin' => sanitize_text_field($_POST['enableAdmin']) === 'true',
        'visibility' => sanitize_text_field($_POST['visibility']),
        'roles' => $roles,
        'setReporter' => sanitize_text_field($_POST['setReporter']) === 'true'
    ];

    update_option('feedbucket_options', $newOptions);

    return wp_json_encode($newOptions);
    wp_die();
}
add_action('wp_ajax_feedbucket_save_options', 'feedbucket_save_options');

function feedbucket_script() {
    $options = get_option('feedbucket_options', array());

    $scriptHtml = '';
    if (array_key_exists('setReporter', $options) && $options['setReporter'] && is_user_logged_in()) {
        $user = wp_get_current_user();
        $scriptHtml .= '<script>
        window.feedbucketConfig = {
            reporter: {
                name: "'.esc_js($user->display_name).'",
                email: "'.esc_js($user->user_email).'",
            }
        }
        </script>';
    }

    if (array_key_exists('key', $options)) {
        $scriptHtml .= '
        <script type="text/javascript">
            (function(k) {
                s=document.createElement("script");s.module=true;s.defer=true;
                s.src="'.esc_url("https://cdn.feedbucket.app/assets/feedbucket.js").'";
                s.dataset.feedbucket=k;document.head.appendChild(s);
            })("'.esc_js($options['key']).'")
        </script>';
    }

    return $scriptHtml;
}

function feedbucket_add_admin_script() {
    $options = get_option('feedbucket_options', array());
    
    if (
        !array_key_exists('enableAdmin', $options) || 
        !array_key_exists('visibility', $options) ||
        !array_key_exists('roles', $options) ||
        !$options['enableAdmin']
    ) {
        return;
    }

    if ($options['visibility'] === 'role' && !array_intersect(wp_get_current_user()->roles, $options['roles'])) {
        return;
    }

    echo feedbucket_script();
}
add_action('admin_head', 'feedbucket_add_admin_script');

function feedbucket_add_script() {
    $options = get_option('feedbucket_options', array());

    if (
        !array_key_exists('enable', $options) ||
        !array_key_exists('visibility', $options) ||
        !array_key_exists('roles', $options) ||
        !is_array($options['roles']) ||
        !$options['enable'] ||
        in_bricks_builder() ||
        in_elementor_preview()
    ) {
        return;
    }

    if ($options['visibility'] === 'all') {
        echo feedbucket_script();
        return;
    }

    if ($options['visibility'] === 'auth' && is_user_logged_in()) {
        echo feedbucket_script();
        return;
    }

    if ($options['visibility'] === 'role' && array_intersect(wp_get_current_user()->roles, $options['roles'])) {
        echo feedbucket_script();
        return;
    }
}
add_action('wp_head', 'feedbucket_add_script');

/**
 * Bricks is a page builder that acts as the frontend even though its
 * backend and tries to insert the script code. This function is to 
 * determine if we are in the page builder or not.
 */
function in_bricks_builder() {
    if (!function_exists( 'bricks_is_builder' )) {
        return false;
    } else {
        return (bool) bricks_is_builder();
    }
}

/**
 * Similar to Bricks we do not want to show Feedbucket in Elementor preview.
 */
function in_elementor_preview() {
    return isset($_GET['elementor-preview']) || 
        isset($_GET['elementor_library']) || 
        (isset($_GET['action']) && $_GET['action'] === 'elementor');
}