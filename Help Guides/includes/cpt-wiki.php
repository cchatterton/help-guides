<?php
/**
 * Help Guides Plugin
 * Register Wiki Custom Post Type
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', function() {
    $labels = [
        'name'               => 'Wiki Pages',
        'singular_name'      => 'Wiki',
        'menu_name'          => 'Wikis',
        'name_admin_bar'     => 'Wiki',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Page',
        'new_item'           => 'New Wiki Page',
        'edit_item'          => 'Edit Wiki Page',
        'view_item'          => 'View Wiki Page',
        'all_items'          => 'All Wiki Pages',
        'search_items'       => 'Search Wiki',
        'parent_item_colon'  => 'Parent Wiki Page:',
        'not_found'          => 'No wiki Pages found.',
        'not_found_in_trash' => 'No wikis Pages found in Trash.',
    ];

    $args = [
        'labels'             => $labels,
        'public'             => false,               // Hidden from front end
        'show_ui'            => true,                // Show in admin
        'show_in_menu'       => 'editor-wiki',       // Place under custom menu
        'hierarchical'       => true,                 // Supports parent/child
        'supports'           => [ 'title', 'editor', 'page-attributes', 'revisions' ],
        'has_archive'        => false,
        'show_in_rest'       => true,                 // Gutenberg support
        'rewrite'            => false,                // No front-end URLs
        'menu_position'      => 20,
    ];

    register_post_type( 'wiki', $args );
});
