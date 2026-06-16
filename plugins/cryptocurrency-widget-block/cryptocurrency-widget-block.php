<?php
/**
 * Plugin Name:       Cryptocurrency Widget Block
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           1.1.1
 * Author:            sahniaman94
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Elementor requires at least: 3.22
 * Elementor tested up to: 3.28.4
 * Text Domain:       cryptocurrency-widget-block
 * Description:	Showcase cryptocurrency data using a variety of customizable widget blocks, each designed to present real-time information in an engaging and user-friendly format.
 */

/*

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 
define( 'ELEMENTOR_CCWFG_WIDGET', __FILE__ );
include_once  __DIR__ .'/includes/functions.php';



add_action( 'init', function() {  
	register_block_type_from_metadata( __DIR__ . '/build',array(
        'api_version'   => 3,       
    ) );
} );

// This goes in your main plugin file

register_activation_hook(__FILE__, function () {
    ccwfg_fetch_coin_data();
}
);

