<?php

if (!defined('ABSPATH')) {
    exit;
}

function thg_register_admin_hooks(): void
{
    add_action('admin_menu', 'thg_register_admin_menu');
    add_action('admin_notices', 'thg_show_screen_id');
}

function thg_register_admin_menu(): void
{
    add_menu_page(
        __('Help Guides', 'help-guides'),
        __('Help Guides', 'help-guides'),
        'edit_posts',
        'editor-wiki-browser',
        'thg_render_wiki_browser',
        'dashicons-info',
        2
    );

    add_submenu_page(
        'editor-wiki-browser',
        __('View Guides', 'help-guides'),
        __('View Guides', 'help-guides'),
        'edit_posts',
        'editor-wiki-browser',
        'thg_render_wiki_browser'
    );

    add_submenu_page(
        'editor-wiki-browser',
        __('Edit or create guides', 'help-guides'),
        __('Edit/Create', 'help-guides'),
        'edit_posts',
        'edit.php?post_type=wiki'
    );
}

function thg_render_wiki_browser(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('You do not have permission to view help guides.', 'help-guides'));
    }

    $selected_id = isset($_GET['wiki_id']) ? absint($_GET['wiki_id']) : 0;
    $wiki_posts = get_posts(
        array(
            'post_type'              => 'wiki',
            'posts_per_page'         => -1,
            'orderby'                => array('menu_order' => 'ASC', 'title' => 'ASC'),
            'post_status'            => 'publish',
            'no_found_rows'          => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        )
    );
    $posts_by_parent = array();
    foreach ($wiki_posts as $wiki_post) {
        $posts_by_parent[(int) $wiki_post->post_parent][] = $wiki_post;
    }

    $selected_post = $selected_id ? get_post($selected_id) : null;
    if (!$selected_post || 'wiki' !== $selected_post->post_type || 'publish' !== $selected_post->post_status) {
        $selected_post = null;
    }

    require THG_PLUGIN_DIR . 'templates/wiki-browser.php';
}

function thg_render_wiki_tree(array $posts_by_parent, int $parent_id, int $selected_id): string
{
    if (empty($posts_by_parent[$parent_id])) {
        return '';
    }

    $html = '<ul class="thg-tree">';
    foreach ($posts_by_parent[$parent_id] as $wiki_post) {
        $post_id = (int) $wiki_post->ID;
        $children = thg_render_wiki_tree($posts_by_parent, $post_id, $selected_id);
        $selected = $selected_id === $post_id;
        $html .= '<li>';
        if ('' !== $children) {
            $html .= '<button type="button" class="thg-tree-toggle" aria-expanded="true"><span class="screen-reader-text">' . esc_html__('Toggle child guides', 'help-guides') . '</span><span aria-hidden="true">−</span></button>';
        } else {
            $html .= '<span class="thg-tree-spacer" aria-hidden="true"></span>';
        }
        $html .= '<a ' . ($selected ? 'aria-current="page" class="is-selected" ' : '') . 'href="' . esc_url(add_query_arg('wiki_id', $post_id, admin_url('admin.php?page=editor-wiki-browser'))) . '">';
        $html .= esc_html(get_the_title($wiki_post));
        $html .= '</a>' . $children . '</li>';
    }
    return $html . '</ul>';
}

function thg_show_screen_id(): void
{
    if (!current_user_can('edit_posts')) {
        return;
    }
    $screen = get_current_screen();
    if (!$screen) {
        return;
    }
    echo '<p class="thg-screen-id"><span class="screen-reader-text">' . esc_html__('Current WordPress admin screen ID:', 'help-guides') . '</span>' . esc_html($screen->id) . '</p>';
}
