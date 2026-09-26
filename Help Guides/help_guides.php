<?php
/**
 * Plugin Name: Help Guides (Wiki-style)
 * Description: Adds Wiki CPT, Help Guides admin menus, screen ID display, and help tabs.
 * Version: 1.2.2
 * Requires at least: 7.0
 * Requires PHP: 8.5
 * Update URI: https://github.com/cchatterton/help-guides
 * Author: Techn
 * Author URI: https://techn.com.au
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Techn Controller API: 1
 * Text Domain: help-guides
 * 
 * Release Notes:
 * ==============
 * 1.2.1 20260825 CC: GitHub release support
 * 1.1 20260102 CC: Inline Guides
 * 1.0 20250401 CC: New Plugn
 * 
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HGW_VERSION', '1.2.2' );
define( 'HGW_PLUGIN_FILE', __FILE__ );
define( 'HGW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

$dir = plugin_dir_path( __FILE__ );

$includes = [
    'includes/cpt-wiki.php',
    'includes/admin-menu.php',
    'includes/meta-boxes.php',
    'includes/screen-id.php',
    'includes/help-tabs.php',
    'includes/view-guides.php',
    'includes/inline-wiki.php',
];

foreach ( $includes as $file ) {
    require_once $dir . $file;
}

require_once __DIR__ . '/includes/controller-client.php';
tnuc_client_register(__FILE__, 'help-guides');
