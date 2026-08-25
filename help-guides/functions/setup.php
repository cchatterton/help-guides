<?php

if (!defined('ABSPATH')) {
    exit;
}

function thg_register_setup_hooks(): void
{
    add_action('init', 'thg_register_wiki_post_type');
    add_action('acf/include_fields', 'thg_register_acf_fields');
    add_filter('acf/load_value/name=screen_ids', 'thg_prefill_screen_ids', 10, 3);
    add_action('admin_notices', 'thg_acf_dependency_notice');
}

function thg_register_wiki_post_type(): void
{
    $labels = array(
        'name'               => __('Wiki Pages', 'help-guides'),
        'singular_name'      => __('Wiki Page', 'help-guides'),
        'menu_name'          => __('Wiki Pages', 'help-guides'),
        'name_admin_bar'     => __('Wiki Page', 'help-guides'),
        'add_new'            => __('Add New', 'help-guides'),
        'add_new_item'       => __('Add New Wiki Page', 'help-guides'),
        'new_item'           => __('New Wiki Page', 'help-guides'),
        'edit_item'          => __('Edit Wiki Page', 'help-guides'),
        'view_item'          => __('View Wiki Page', 'help-guides'),
        'all_items'          => __('All Wiki Pages', 'help-guides'),
        'search_items'       => __('Search Wiki Pages', 'help-guides'),
        'parent_item_colon'  => __('Parent Wiki Page:', 'help-guides'),
        'not_found'          => __('No wiki pages found.', 'help-guides'),
        'not_found_in_trash' => __('No wiki pages found in Trash.', 'help-guides'),
    );

    register_post_type(
        'wiki',
        array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => false,
            'hierarchical'    => true,
            'supports'        => array('title', 'editor', 'page-attributes', 'revisions'),
            'has_archive'     => false,
            'show_in_rest'    => true,
            'rewrite'         => false,
            'capability_type' => 'post',
            'map_meta_cap'    => true,
        )
    );
}

function thg_register_acf_fields(): void
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_675e69f52f970',
            'title'    => __('Related admin locations', 'help-guides'),
            'fields'   => array(
                array(
                    'key'          => 'field_675e69f57fe62',
                    'label'        => __('Screen IDs', 'help-guides'),
                    'name'         => 'screen_ids',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => __('Add Screen ID', 'help-guides'),
                    'wrapper'      => array('width' => '33'),
                    'sub_fields'   => array(
                        array(
                            'key'   => 'field_675e6a8957563',
                            'label' => __('Screen ID', 'help-guides'),
                            'name'  => 'screen_id',
                            'type'  => 'text',
                        ),
                    ),
                ),
                array(
                    'key'          => 'field_wiki_context_targets',
                    'label'        => __('Inline targets', 'help-guides'),
                    'name'         => 'context_targets',
                    'type'         => 'repeater',
                    'layout'       => 'table',
                    'button_label' => __('Add Target', 'help-guides'),
                    'wrapper'      => array('width' => '67'),
                    'sub_fields'   => array(
                        array(
                            'key'     => 'field_wiki_target_selectors',
                            'label'   => __('CSS selector', 'help-guides'),
                            'name'    => 'css_selectors',
                            'type'    => 'text',
                            'wrapper' => array('width' => '35'),
                        ),
                        array(
                            'key'           => 'field_wiki_target_selectors_count',
                            'label'         => __('Target position', 'help-guides'),
                            'name'          => 'css_position',
                            'type'          => 'number',
                            'default_value' => 1,
                            'min'           => 1,
                            'step'          => 1,
                            'wrapper'       => array('width' => '10'),
                        ),
                        array(
                            'key'          => 'field_wiki_target_url_rules',
                            'label'        => __('URL rules', 'help-guides'),
                            'name'         => 'url_rules',
                            'type'         => 'repeater',
                            'layout'       => 'table',
                            'button_label' => __('Add Rule', 'help-guides'),
                            'wrapper'      => array('width' => '55'),
                            'sub_fields'   => array(
                                array(
                                    'key'           => 'field_wiki_url_rule_mode',
                                    'label'         => __('Mode', 'help-guides'),
                                    'name'          => 'mode',
                                    'type'          => 'select',
                                    'choices'       => array(
                                        'include' => __('Include', 'help-guides'),
                                        'exclude' => __('Exclude', 'help-guides'),
                                    ),
                                    'default_value' => 'include',
                                    'ui'            => 1,
                                    'allow_null'    => 0,
                                    'wrapper'       => array('width' => '25'),
                                ),
                                array(
                                    'key'         => 'field_wiki_url_rule_pattern',
                                    'label'       => __('Pattern (regular expression)', 'help-guides'),
                                    'name'        => 'pattern',
                                    'type'        => 'text',
                                    'maxlength'   => 500,
                                    'wrapper'     => array('width' => '75'),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array('param' => 'post_type', 'operator' => '==', 'value' => 'wiki'),
                ),
            ),
            'position' => 'normal',
            'style'    => 'default',
            'active'   => true,
        )
    );
}

function thg_prefill_screen_ids($value, $post_id, array $field)
{
    unset($field);

    if (!empty($value) || !current_user_can('edit_posts') || !isset($_GET['pre_fill_screen_id'])) {
        return $value;
    }
    if ('new_post' !== $post_id && absint($post_id) > 0) {
        return $value;
    }

    $screen_id = sanitize_text_field(wp_unslash($_GET['pre_fill_screen_id']));
    if ('' === $screen_id || strlen($screen_id) > 200) {
        return $value;
    }

    return array(array('screen_id' => $screen_id));
}

function thg_acf_dependency_notice(): void
{
    if (!current_user_can('manage_options') || function_exists('get_field')) {
        return;
    }

    echo '<div class="notice notice-warning"><p>' . esc_html__('TN Help Guides requires Advanced Custom Fields Pro for screen mappings and inline targets. Existing wiki pages remain available, but contextual features are paused.', 'help-guides') . '</p></div>';
}
