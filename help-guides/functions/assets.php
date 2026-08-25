<?php

if (!defined('ABSPATH')) {
    exit;
}

function thg_register_asset_hooks(): void
{
    add_action('admin_enqueue_scripts', 'thg_enqueue_admin_assets');
}

function thg_enqueue_admin_assets(): void
{
    wp_enqueue_style('thg-admin', THG_PLUGIN_URL . 'styles/help-guides.css', array('dashicons'), THG_VERSION);

    if (!current_user_can('edit_posts')) {
        return;
    }
    wp_enqueue_script('thg-admin', THG_PLUGIN_URL . 'scripts/help-guides.js', array(), THG_VERSION, true);
    wp_localize_script(
        'thg-admin',
        'THGAdmin',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('thg_inline_help'),
            'labels'  => array(
                'close'       => __('Close help', 'help-guides'),
                'help'        => __('Help', 'help-guides'),
                'loading'     => __('Loading…', 'help-guides'),
                'loadFailure' => __('Could not load help content.', 'help-guides'),
            ),
            'acfAvailable' => function_exists('get_field'),
        )
    );
}
