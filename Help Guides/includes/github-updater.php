<?php

if (!defined('ABSPATH')) {
    exit;
}

function hgw_register_github_updater(): void
{
    if (!is_admin()) {
        return;
    }
    add_filter('pre_set_site_transient_update_plugins', 'hgw_add_update_data');
    add_filter('site_transient_update_plugins', 'hgw_add_update_data');
    add_filter('plugins_api', 'hgw_plugin_details', 10, 3);
    add_filter('plugin_row_meta', 'hgw_plugin_row_meta', 10, 2);
    add_action('admin_init', 'hgw_handle_manual_update_check');
    add_action('admin_notices', 'hgw_update_check_notice');
    add_action('network_admin_notices', 'hgw_update_check_notice');
    add_action('upgrader_process_complete', 'hgw_clear_update_cache_after_upgrade', 10, 2);
}

function hgw_add_update_data($transient)
{
    if (!is_object($transient)) {
        $transient = new stdClass();
    }
    $transient->response = isset($transient->response) && is_array($transient->response) ? $transient->response : array();
    $transient->no_update = isset($transient->no_update) && is_array($transient->no_update) ? $transient->no_update : array();
    unset($transient->response[HGW_PLUGIN_BASENAME], $transient->no_update[HGW_PLUGIN_BASENAME]);

    $release = hgw_latest_github_release();
    if (!$release || !version_compare($release['version'], HGW_VERSION, '>')) {
        return $transient;
    }
    $transient->response[HGW_PLUGIN_BASENAME] = (object) array(
        'id'           => hgw_github_repo_url(),
        'slug'         => 'help-guides',
        'plugin'       => HGW_PLUGIN_BASENAME,
        'new_version'  => $release['version'],
        'url'          => $release['release_url'],
        'package'      => $release['package'],
        'requires'     => '6.0',
        'requires_php' => '8.1',
    );
    return $transient;
}

function hgw_plugin_details($result, string $action, object $args)
{
    if ('plugin_information' !== $action || 'help-guides' !== ($args->slug ?? '')) {
        return $result;
    }
    $release = hgw_latest_github_release();
    if (!$release) {
        return $result;
    }
    return (object) array(
        'name'          => 'Help Guides (Wiki-style)',
        'slug'          => 'help-guides',
        'version'       => $release['version'],
        'author'        => 'Techn',
        'homepage'      => hgw_github_repo_url(),
        'download_link' => $release['package'],
        'requires'      => '6.0',
        'requires_php'  => '8.1',
        'sections'      => array(
            'description' => __('Wiki-style WordPress admin guides with contextual and inline help.', 'help-guides'),
            'changelog'   => wp_kses_post($release['body']),
        ),
    );
}

function hgw_plugin_row_meta(array $links, string $file): array
{
    if (HGW_PLUGIN_BASENAME !== $file) {
        return $links;
    }
    $links[] = '<a href="' . esc_url(hgw_github_repo_url()) . '">' . esc_html__('GitHub', 'help-guides') . '</a>';
    if (current_user_can('update_plugins')) {
        $check_url = wp_nonce_url(add_query_arg('hgw_check_updates', '1', hgw_plugins_page_url()), 'hgw_check_updates');
        $links[] = '<a href="' . esc_url($check_url) . '">' . esc_html__('Check for updates', 'help-guides') . '</a>';
    }
    return $links;
}

function hgw_handle_manual_update_check(): void
{
    if (empty($_GET['hgw_check_updates'])) {
        return;
    }
    if (!current_user_can('update_plugins')) {
        wp_die(esc_html__('You do not have permission to check for plugin updates.', 'help-guides'));
    }
    check_admin_referer('hgw_check_updates');
    hgw_clear_github_update_cache();
    delete_site_transient('update_plugins');
    if (!function_exists('wp_update_plugins')) {
        require_once ABSPATH . 'wp-includes/update.php';
    }
    $_GET['force-check'] = '1';
    wp_update_plugins();
    $transient = get_site_transient('update_plugins');
    $transient = hgw_add_update_data(is_object($transient) ? $transient : new stdClass());
    set_site_transient('update_plugins', $transient);

    $result = isset($transient->response[HGW_PLUGIN_BASENAME]) ? 'available' : 'current';
    if (get_site_transient('hgw_github_release_error')) {
        $result = 'failed';
    }
    wp_safe_redirect(add_query_arg('hgw_update_check', $result, hgw_plugins_page_url()));
    exit;
}

function hgw_update_check_notice(): void
{
    $result = isset($_GET['hgw_update_check']) ? sanitize_key((string) wp_unslash($_GET['hgw_update_check'])) : '';
    $messages = array(
        'available' => array('success', __('A newer Help Guides (Wiki-style) release is available below.', 'help-guides')),
        'current'   => array('info', __('Help Guides (Wiki-style) is current.', 'help-guides')),
        'failed'    => array('error', __('Help Guides (Wiki-style) could not check for updates. Please try again later.', 'help-guides')),
    );
    if (isset($messages[$result])) {
        echo '<div class="notice notice-' . esc_attr($messages[$result][0]) . ' is-dismissible"><p>' . esc_html($messages[$result][1]) . '</p></div>';
    }
}

function hgw_latest_github_release(): ?array
{
    static $request_checked = false;
    static $request_release = null;

    if ($request_checked) {
        return $request_release;
    }

    $forced = hgw_is_forced_update_check();
    if ($forced) {
        hgw_clear_github_update_cache();
    }
    $cached = get_site_transient('hgw_github_latest_release');
    if (is_array($cached)) {
        $request_checked = true;
        $request_release = $cached;
        return $request_release;
    }
    if (!$forced && get_site_transient('hgw_github_lookup_backoff')) {
        $request_checked = true;
        return null;
    }

    $release = hgw_release_from_manifest();
    if (!is_wp_error($release)) {
        $request_checked = true;
        $request_release = hgw_cache_github_release($release);
        return $request_release;
    }

    $release = hgw_release_from_redirect();
    if (!is_wp_error($release)) {
        $request_checked = true;
        $request_release = hgw_cache_github_release($release);
        return $request_release;
    }

    $release = hgw_release_from_api();
    if (!is_wp_error($release)) {
        $request_checked = true;
        $request_release = hgw_cache_github_release($release);
        return $request_release;
    }

    hgw_store_github_lookup_failure($release);
    $request_checked = true;
    return null;
}

function hgw_release_from_manifest(): array|WP_Error
{
    $response = wp_remote_get(
        'https://raw.githubusercontent.com/cchatterton/help-guides/main/update.json',
        array('timeout' => 10, 'headers' => array('User-Agent' => 'Help-Guides/' . HGW_VERSION))
    );
    if (is_wp_error($response)) {
        return $response;
    }
    if (200 !== (int) wp_remote_retrieve_response_code($response)) {
        return new WP_Error('manifest_http_error', 'Manifest lookup failed.');
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    $version = hgw_validate_release_version((string) ($data['version'] ?? ''));
    if (!$version) {
        return new WP_Error('manifest_invalid', 'Manifest data was invalid.');
    }
    return array(
        'version'     => $version,
        'body'        => sanitize_textarea_field((string) ($data['body'] ?? '')),
        'release_url' => hgw_github_repo_url() . '/releases/tag/v' . rawurlencode($version),
        'package'     => hgw_github_repo_url() . '/releases/download/v' . rawurlencode($version) . '/help-guides.zip',
    );
}

function hgw_release_from_redirect(): array|WP_Error
{
    $response = wp_remote_get(
        hgw_github_repo_url() . '/releases/latest',
        array('redirection' => 0, 'timeout' => 10, 'headers' => array('User-Agent' => 'Help-Guides/' . HGW_VERSION))
    );
    if (is_wp_error($response)) {
        return $response;
    }
    $location = (string) wp_remote_retrieve_header($response, 'location');
    if (!preg_match('#/releases/tag/(v?[0-9][0-9A-Za-z._+-]*)#', $location, $matches)) {
        return new WP_Error('redirect_invalid', 'Release redirect was invalid.');
    }
    $tag = sanitize_text_field(rawurldecode($matches[1]));
    $version = hgw_validate_release_version(ltrim($tag, 'vV'));
    if (!$version) {
        return new WP_Error('redirect_version_invalid', 'Release version was invalid.');
    }
    return array(
        'version'     => $version,
        'body'        => '',
        'release_url' => hgw_github_repo_url() . '/releases/tag/' . rawurlencode($tag),
        'package'     => hgw_github_repo_url() . '/releases/download/' . rawurlencode($tag) . '/help-guides.zip',
    );
}

function hgw_release_from_api(): array|WP_Error
{
    $response = wp_remote_get(
        'https://api.github.com/repos/cchatterton/help-guides/releases/latest',
        array(
            'timeout' => 10,
            'headers' => array('Accept' => 'application/vnd.github+json', 'User-Agent' => 'Help-Guides/' . HGW_VERSION),
        )
    );
    if (is_wp_error($response)) {
        return $response;
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    if (200 !== $code) {
        return new WP_Error(429 === $code ? 'rate_limited' : 'api_http_error', 'GitHub API lookup failed.', array('status' => $code));
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    $version = hgw_validate_release_version(ltrim((string) ($data['tag_name'] ?? ''), 'vV'));
    $package = '';
    foreach ((array) ($data['assets'] ?? array()) as $asset) {
        if ('help-guides.zip' === ($asset['name'] ?? '') && !empty($asset['browser_download_url'])) {
            $package = esc_url_raw((string) $asset['browser_download_url']);
            break;
        }
    }
    if (!$version || !$package) {
        return new WP_Error('api_invalid', 'GitHub API release data was invalid.');
    }
    return array(
        'version'     => $version,
        'body'        => (string) ($data['body'] ?? ''),
        'release_url' => esc_url_raw((string) ($data['html_url'] ?? hgw_github_repo_url())),
        'package'     => $package,
    );
}

function hgw_cache_github_release(array $release): array
{
    $ttl = version_compare($release['version'], HGW_VERSION, '>') ? 6 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
    set_site_transient('hgw_github_latest_release', $release, $ttl);
    delete_site_transient('hgw_github_release_error');
    delete_site_transient('hgw_github_lookup_backoff');
    return $release;
}

function hgw_store_github_lookup_failure(WP_Error $error): void
{
    set_site_transient(
        'hgw_github_release_error',
        array('type' => sanitize_key($error->get_error_code()), 'checked_at' => time()),
        10 * MINUTE_IN_SECONDS
    );
    set_site_transient('hgw_github_lookup_backoff', 1, 10 * MINUTE_IN_SECONDS);
    delete_site_transient('hgw_github_latest_release');
}

function hgw_validate_release_version(string $version): string
{
    return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][0-9A-Za-z.-]+)?$/', $version) ? $version : '';
}

function hgw_clear_update_cache_after_upgrade($upgrader, array $hook_extra): void
{
    unset($upgrader);
    if ('plugin' === ($hook_extra['type'] ?? '') && in_array(HGW_PLUGIN_BASENAME, (array) ($hook_extra['plugins'] ?? array()), true)) {
        hgw_clear_github_update_cache();
    }
}

function hgw_clear_github_update_cache(): void
{
    delete_site_transient('hgw_github_latest_release');
    delete_site_transient('hgw_github_release_error');
    delete_site_transient('hgw_github_lookup_backoff');
}

function hgw_is_forced_update_check(): bool
{
    if (!current_user_can('update_plugins')) {
        return false;
    }
    $action = isset($_REQUEST['action']) ? sanitize_key((string) wp_unslash($_REQUEST['action'])) : '';
    return isset($_REQUEST['force-check']) || isset($_GET['hgw_check_updates']) || in_array($action, array('update-selected', 'upgrade-plugin', 'do-plugin-upgrade'), true);
}

function hgw_github_repo_url(): string
{
    return 'https://github.com/cchatterton/help-guides';
}

function hgw_plugins_page_url(): string
{
    return is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php');
}

hgw_register_github_updater();
