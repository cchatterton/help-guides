<?php
/**
 * Plugin Name: Help Guides (Wiki-style)
 * Description: Adds Wiki CPT, Help Guides admin menus, screen ID display, and help tabs.
 * Version: 1.2.1
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/help-guides
 * Author: TECHN
 * Author URI: https://techn.com.au/
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

define( 'HGW_VERSION', '1.2.1' );
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
    'includes/github-updater.php',
];

foreach ( $includes as $file ) {
    require_once $dir . $file;
}
