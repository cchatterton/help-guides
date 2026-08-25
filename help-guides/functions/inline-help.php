<?php

if (!defined('ABSPATH')) {
    exit;
}

function thg_register_inline_help_hooks(): void
{
    add_action('wp_ajax_thg_inline_targets', 'thg_ajax_inline_targets');
    add_action('wp_ajax_thg_inline_get_post', 'thg_ajax_inline_get_post');
}

function thg_ajax_inline_targets(): void
{
    check_ajax_referer('thg_inline_help', 'nonce');
    if (!current_user_can('edit_posts') || !function_exists('get_field')) {
        wp_send_json_error(array('message' => __('Inline help is unavailable.', 'help-guides')), 403);
    }

    $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
    if (!thg_is_local_admin_url($url)) {
        wp_send_json_error(array('message' => __('The admin URL was invalid.', 'help-guides')), 400);
    }

    $wiki_posts = get_posts(
        array(
            'post_type'              => 'wiki',
            'posts_per_page'         => -1,
            'post_status'            => 'publish',
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        )
    );
    $matches = array();

    foreach ($wiki_posts as $wiki_post) {
        $matched_targets = array();
        foreach ((array) get_field('context_targets', $wiki_post->ID) as $target) {
            $selector = isset($target['css_selectors']) ? sanitize_text_field((string) $target['css_selectors']) : '';
            $position = max(1, absint($target['css_position'] ?? 1));
            $rules = thg_normalise_url_rules((array) ($target['url_rules'] ?? array()));
            if ('' === $selector || !$rules || !thg_url_rules_match($url, $rules)) {
                continue;
            }
            $matched_targets[] = array('selector' => $selector, 'position' => $position);
        }

        if ($matched_targets) {
            $matches[] = array(
                'id'      => (int) $wiki_post->ID,
                'title'   => sanitize_text_field((string) $wiki_post->post_title),
                'targets' => $matched_targets,
            );
        }
    }

    wp_send_json_success(array('matches' => $matches));
}

function thg_ajax_inline_get_post(): void
{
    check_ajax_referer('thg_inline_help', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('You do not have permission to view help guides.', 'help-guides')), 403);
    }

    $post_id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    $wiki_post = $post_id ? get_post($post_id) : null;
    if (!$wiki_post || 'wiki' !== $wiki_post->post_type || 'publish' !== $wiki_post->post_status) {
        wp_send_json_error(array('message' => __('The help guide was not found.', 'help-guides')), 404);
    }

    wp_send_json_success(
        array(
            'id'    => (int) $wiki_post->ID,
            'title' => sanitize_text_field((string) $wiki_post->post_title),
            'html'  => wp_kses_post(apply_filters('the_content', $wiki_post->post_content)),
        )
    );
}

function thg_is_local_admin_url(string $url): bool
{
    $submitted_host = wp_parse_url($url, PHP_URL_HOST);
    $admin_host = wp_parse_url(admin_url(), PHP_URL_HOST);
    $submitted_path = (string) wp_parse_url($url, PHP_URL_PATH);
    $admin_path = rtrim((string) wp_parse_url(admin_url(), PHP_URL_PATH), '/');

    return $submitted_host && $admin_host && hash_equals(strtolower($admin_host), strtolower($submitted_host)) && str_starts_with($submitted_path, $admin_path . '/');
}

function thg_normalise_url_rules(array $rules): array
{
    $normalised = array();
    foreach ($rules as $rule) {
        $pattern = isset($rule['pattern']) ? trim((string) $rule['pattern']) : '';
        if ('' === $pattern || strlen($pattern) > 500) {
            continue;
        }
        $normalised[] = array(
            'mode'    => isset($rule['mode']) && 'exclude' === $rule['mode'] ? 'exclude' : 'include',
            'pattern' => $pattern,
        );
    }
    return $normalised;
}

function thg_url_rules_match(string $url, array $rules): bool
{
    $includes = array();
    $excludes = array();
    foreach ($rules as $rule) {
        if ('exclude' === $rule['mode']) {
            $excludes[] = $rule['pattern'];
        } else {
            $includes[] = $rule['pattern'];
        }
    }
    foreach ($excludes as $pattern) {
        if (thg_regex_matches($pattern, $url)) {
            return false;
        }
    }
    foreach ($includes as $pattern) {
        if (!thg_regex_matches($pattern, $url)) {
            return false;
        }
    }
    return true;
}

function thg_regex_matches(string $pattern, string $subject): bool
{
    if (strlen($pattern) > 500) {
        return false;
    }

    $result = @preg_match($pattern, $subject);
    if (false === $result) {
        $result = @preg_match('~' . str_replace('~', '\\~', $pattern) . '~u', $subject);
    }
    return 1 === $result;
}
