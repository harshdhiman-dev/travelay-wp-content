<?php
/*
Plugin Name: adivaha&reg; Travel Plugin
Plugin URI: http://www.adivaha.com
Description: Supercharge your WordPress with adivaha® — free hotel and air content with seamless direct connection to GDS, OTA and 150+ top suppliers. WhatsApp Support : +91 7303443889. Availability: 11 AM - 5 PM (IST).
Author: adivaha&reg; - Travel Platform
Version: 3.1
Author URI: http://www.adivaha.com
*/

define('ADIVAHA__PLUGIN_DIR', plugin_dir_path(__FILE__));
$site_url = get_site_url();
define('ADIVAHA__PLUGIN_SITE_URL', $site_url); // mine
define('ADIVAHA__PLUGIN_URL', $site_url . "/wp-content/plugins/adiaha-hotel/"); // mine

global $current_user, $user_pid;
$plugin_version = "v2";

require(ABSPATH . WPINC . '/pluggable.php');

if (!function_exists('wp_get_current_user')) {
    echo 'Function not set';
    function wp_get_current_user()
    {
        global $current_user;
        get_currentuserinfo();
        return $current_user;
    }
}
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

if (!function_exists('get_pid')) {
    function get_pid()
    {
        global $user_pid;
        $get_user_pid = get_option('adh_pid', false);
        return $get_user_pid;
    }
}
$user_pid = get_pid($user_id);

// Add Toolbar Menus
if (!function_exists('adi_add_admin_bar_link_adi')) {
    function adi_add_admin_bar_link_adi()
    {
        global $wp_admin_bar;
        if (!is_super_admin() || !is_admin_bar_showing())
            return;
        $wp_admin_bar->add_menu(array(
            'id' => 'unq_adivaha',
            'title' => __('adivaha&reg; Plugin', 'adi_framework'),
            'href' => admin_url('admin.php?page=unq_adivaha'),
            'meta'   => array(
                'class'    => 'adi-item2',
            ),
        ));
    }
}
add_action('admin_bar_menu', 'adi_add_admin_bar_link_adi', 26);

add_action('admin_menu', 'adivaha_main_menu');
function adivaha_main_menu()
{
    ob_start();
    global $wpdb;
    global $files;
    global $Plugin_Path;
    add_menu_page("adivaha&reg; Plugin", "adivaha&reg; Plugin", "manage_options", "unq_adivaha", "adivaha_dashboard_output", ADIVAHA__PLUGIN_URL . "asset/images/icon.png");
    function adivaha_dashboard_output()
    {
        include(ADIVAHA__PLUGIN_DIR . 'apps/index.php');
    }
}

add_shortcode('adivaha_searchBox', 'searchBox');
add_shortcode('adivaha_searchResults', 'searchResults');
function adivaha_booking_engine()
{
    function searchBox()
    {
        ob_start();
        global $user_pid, $plugin_version;
		global $site_url;

        if (!isset($user_pid) || (isset($user_pid) && $user_pid == "")) {
            beforeConnect();
        } else {
           $URL = "https://www.abengines.com/online-booking/?pid=" . $user_pid."&ip=".$_SERVER["REMOTE_ADDR"];
            $URL = str_replace(" ", '%20', $URL);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $contents = curl_exec($ch);
			$contents = str_replace("https://mytourmyway.com/search-result.html", $site_url."/adivaha-search-results/",$contents);
            curl_close($ch);
           
			print_r($contents);
           // echo '<div id="adivaha-wrapper"><script charset="utf-8" type="text/javascript" src="//www.abengines.com/ui/' . $plugin_version . '/' . $user_pid . '/combo/"></script></div>';
        }

        return ob_get_clean();
    }

    function searchResults()
    {
        ob_start();
        global $user_pid, $plugin_version;
        $mid = $_REQUEST['mid'];
		$URL = "https://www.abengines.com/online-booking/search-results.html?pid=" . $user_pid . "&" . $_SERVER["QUERY_STRING"];
                $URL = str_replace(" ", '%20', $URL);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $URL);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $contents = curl_exec($ch);
                curl_close($ch);
                print_r($contents);
        //echo "<div id='adivaha-wrapper'><script charset='utf-8' type='text/javascript' src='https://www.abengines.com/ui/" . $plugin_version . "/" . $user_pid . "/" . $mid . "/mt/search-results/'></script></div>";
        return ob_get_clean();
    }
}

function beforeConnect()
{
    echo "<div class='setupguide'><div class='setup-dialog'><div class='setup-content'><div class='setup-body'><p class='setup-bodyimg'><img src='https://www.adivaha.com/images/error.png'/></p><p class='setup-body1'>Config Error: Missing Partner ID (PID) and API Key</p><p class='setup-body2'>Please Configure your Partner ID and API Keys in</p><p class='setup-body3'>WP-Admin &#10132; adivaha &#10132; General Settings</p></div><div class='setup-footer'> <a class='setupbtn setupbtn-default' href='https://www.adivaha.com/documentations/price-comparison/setup-plugin.html' target='_blank'>Setup Documentation</a><a class='setupbtn setupbtn-defaults' href='https://youtu.be/eJEFy7yLd'g'  target='_blank'>Video Guide</a></div></div></div></div><style>.setupguide{left:0;right:0;width:100%;height:100%;margin:0 auto;font-family:sans-serif}.setup-dialog{display:flex;align-items:center;flex-direction:column;height:100%;width:100%;justify-content:center}.setup-content{padding:40px;    padding: 40px;
    background: #fff;
    box-shadow: 0px 1px 7px #6b6a6a2e;}.setup-bodyimg{text-align:center}.setup-body1{    font-size: 19px;
    color: #000;
    font-weight: 700;
    margin-top: 0;}.setup-body2,.setup-body3{font-size: 14px;
    color: #757272;
    font-weight: 500;
    text-align: center;}.setup-body2{margin-bottom:0}.setup-body3{    margin-top: 4px;
    color: #F44336;
    font-weight: 600;}.setup-footer{margin: 20px 0 0;
    float: left;
    width: 100%;
    display: flex;
    align-items: center;
    flex-direction: row;
    justify-content: center;}.setupbtn-default{    border: 2px solid #F44336;
    padding: 10px;
    color: #fff!important;
    font-size: 14px;
    border-radius: 3px;
    font-weight: 500;
    margin-right: 5px;
    cursor: pointer;
    text-decoration: none;
    width: 100%;
    float: left;
    text-align: center;
    background: #F44336;
    text-transform: uppercase}.setupbtn-defaults{ border: 2px solid #F44336;
    padding: 10px;
    color: #F44336!important;
    font-size: 14px;
    border-radius: 3px;
    font-weight: 500;
    margin-right: 5px;
    cursor: pointer;
    text-decoration: none;
    width: 100%;
    float: left;
    text-align: center;
    background: #fff;
    text-transform: uppercase;}</style>";
}


add_action('init', 'adivaha_booking_engine');
function adivha_pro_install_portal()
{
    init_db_myplugin();
}

// Initialize DB Tables
function init_db_myplugin()
{
    // WP Globals
    global $table_prefix, $wpdb;
    // Customer Table
    $customerTable = $table_prefix . 'custom_plugin';
    // Create Customer Table if not exist
    if ($wpdb->get_var("show tables like '$customerTable'") != $customerTable) {

        // Query - Create Table
        $sql = "CREATE TABLE `$customerTable` (";
        $sql .= " `id` int(11) NOT NULL auto_increment, ";
        $sql .= " `pid` varchar(25) NOT NULL, ";
        $sql .= " PRIMARY KEY (`id`) ";
        $sql .= ") ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;";

        // Include Upgrade Script
        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
        // Create Table
        dbDelta($sql);
    }
}

register_activation_hook(__FILE__, 'adivha_pro_install_portal');
function create_page($title_of_the_page, $content, $parent_id = NULL)
{
    global $site_url;

    $array_of_objects = get_posts([
        'title' => $title_of_the_page,
        'post_type' => 'any',
    ]);
    if (count($array_of_objects) == 0) {
        $post_data = array(
            'comment_status' => 'close',
            'ping_status'    => 'close',
            'post_title'    => ucwords($title_of_the_page),
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'      => 'page',
            'post_author'   => 1, // Author ID
            'post_name'      => strtolower(str_replace(' ', '-', trim($title_of_the_page))),
            'page_template' => $title_of_the_page
        );
        $page_id = wp_insert_post($post_data, false);
    }

    $array_of_objects = get_posts([
        'title' => $title_of_the_page,
        'post_type' => 'any',
    ]);

    $page_id = $array_of_objects[0];
    $page_url = $site_url . "/" . $array_of_objects[0]->post_name . "/";

    return $page_url;
}

/* function addSearchBox()
{
    echo do_shortcode('[adivaha_searchBox]');
    if ($user_pid != "") {
        add_action('wp_footer', 'addSearchBox');
    }
} */

if (is_admin()) {
    add_action('wp_ajax_deleteUser', 'deleteUser');
	add_action('wp_ajax_addUser', 'addUser');
    function deleteUser()
    {
        $user_pid = @$_REQUEST['pid'];
        $flag = 0;
        if ($user_pid != "") {
            $delete_one = delete_option('adh_pid');
            if ($delete_one) {
                $flag = 1;
            } else {
                $flag = 0;
            }
        }
        echo $flag;
        die;
    }

    add_action('wp_ajax_addUser', 'addUser');
    add_action('wp_ajax_nopriv_addUser', 'addUser');
    function addUser()
    {
        $user_pid = @$_REQUEST['pid'];
        $flag = 0;
        if ($user_pid != "") {
            $add_one = add_option('adh_pid', $user_pid, 'yes');
            create_page('adivaha Search Results', '[adivaha_searchResults]');
            if ($add_one) {
                $flag = 1;
            } else {
                $flag = 0;
            }
        }
        echo $flag;
        die;
    }
}
