<?php
/**
 * Help Guides Plugin
 * Admin Menu and Submenus Registration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', function() {
    // Top-level menu: Help Guides
    add_menu_page(
        'Wiki Pages',            // Page title
        'Wiki Pages',            // Menu title
        'edit_posts',             // Capability required
        'editor-wiki-browser',            // Menu slug
        '',                       // No callback here; top menu links to first submenu
        'dashicons-info',     // Icon
        2                      // Position in menu
    );

    // Submenu 1: Edit/Create — links to default CPT listing for 'wiki'
    add_submenu_page(
        'editor-wiki',             // Parent slug
        'Edit/Create',             // Page title
        'Edit/Create',             // Menu title
        'edit_posts',              // Capability
        'edit.php?post_type=wiki'  // Links to WP's default CPT listing screen
    );

    // Submenu 2: View Guides — your custom Wiki Browser page
    add_submenu_page(
        'editor-wiki',              // Parent slug
        'View Guides',              // Page title
        'View Guides',              // Menu title
        'edit_posts',               // Capability
        'editor-wiki-browser',      // Menu slug
        'render_editor_wiki_page'   // Callback function to render the custom page
    );
});
