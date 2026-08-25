<?php
/**
 * Plugin Name: TN Help Guides
 * Description: Adds wiki-style admin guides, contextual help tabs, screen IDs, and inline help targets.
 * Version: 1.2.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/help-guides
 * Author: Techn
 * Author URI: https://techn.com.au
 * Text Domain: help-guides
 */

if (!defined('ABSPATH')) {
    exit;
}

define('THG_VERSION', '1.2.0');
define('THG_PLUGIN_FILE', __FILE__);
define('THG_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('THG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('THG_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once THG_PLUGIN_DIR . 'functions/setup.php';
require_once THG_PLUGIN_DIR . 'functions/admin.php';
require_once THG_PLUGIN_DIR . 'functions/contextual-help.php';
require_once THG_PLUGIN_DIR . 'functions/inline-help.php';
require_once THG_PLUGIN_DIR . 'functions/assets.php';
require_once THG_PLUGIN_DIR . 'functions/github-updater.php';

add_action('plugins_loaded', 'thg_load_plugin');

function thg_load_plugin(): void
{
    thg_register_setup_hooks();
    thg_register_admin_hooks();
    thg_register_contextual_help_hooks();
    thg_register_inline_help_hooks();
    thg_register_asset_hooks();
    thg_register_github_updater();
}
