<?php

if (!defined('ABSPATH')) {
    exit;
}

function thg_register_contextual_help_hooks(): void
{
    add_action('current_screen', 'thg_add_contextual_help_tab');
}

function thg_add_contextual_help_tab(WP_Screen $screen): void
{
    if (!current_user_can('edit_posts') || !function_exists('get_field')) {
        return;
    }

    $wiki_posts = get_posts(
        array(
            'post_type'              => 'wiki',
            'posts_per_page'         => -1,
            'post_status'            => 'publish',
            'orderby'                => 'title',
            'order'                  => 'ASC',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        )
    );
    $matches = array();
    foreach ($wiki_posts as $wiki_post) {
        foreach ((array) get_field('screen_ids', $wiki_post->ID) as $row) {
            if (isset($row['screen_id']) && hash_equals((string) $row['screen_id'], (string) $screen->id)) {
                $matches[] = $wiki_post;
                break;
            }
        }
    }
    if (!$matches) {
        return;
    }

    $content = '<p>' . esc_html__('Help guides related to this screen:', 'help-guides') . '</p><ul class="thg-help-links">';
    foreach ($matches as $wiki_post) {
        $url = add_query_arg('wiki_id', (int) $wiki_post->ID, admin_url('admin.php?page=editor-wiki-browser'));
        $content .= '<li><a href="' . esc_url($url) . '">' . esc_html(get_the_title($wiki_post)) . '</a></li>';
    }
    $content .= '</ul>';

    $create_url = add_query_arg(
        array('post_type' => 'wiki', 'pre_fill_screen_id' => $screen->id),
        admin_url('post-new.php')
    );
    $content .= '<p><a href="' . esc_url($create_url) . '" class="button button-primary">' . esc_html__('Create Help Guide', 'help-guides') . '</a></p>';

    $screen->add_help_tab(
        array(
            'id'      => 'thg_wiki_help',
            'title'   => __('Wiki Pages', 'help-guides'),
            'content' => $content,
        )
    );
}
