<?php

define('ABSPATH', __DIR__ . '/');
define('HGW_VERSION', '1.2.1');
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);

final class WP_Error
{
    public function __construct(private string $code = '', private string $message = '', private mixed $data = null)
    {
    }

    public function get_error_code(): string
    {
        return $this->code;
    }

    public function get_error_message(): string
    {
        return $this->message;
    }

    public function get_error_data(): mixed
    {
        return $this->data;
    }
}

$hgw_test_transients = array();
$hgw_test_requests = array();
$hgw_test_scenario = getenv('HGW_TEST_SCENARIO') ?: 'manifest';

function is_admin(): bool
{
    return true;
}

function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
{
    unset($hook, $callback, $priority, $accepted_args);
    return true;
}

function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
{
    unset($hook, $callback, $priority, $accepted_args);
    return true;
}

function current_user_can(string $capability): bool
{
    unset($capability);
    return false;
}

function get_site_transient(string $key)
{
    global $hgw_test_transients;
    return $hgw_test_transients[$key] ?? false;
}

function set_site_transient(string $key, mixed $value, int $expiration): bool
{
    global $hgw_test_transients;
    unset($expiration);
    $hgw_test_transients[$key] = $value;
    return true;
}

function delete_site_transient(string $key): bool
{
    global $hgw_test_transients;
    unset($hgw_test_transients[$key]);
    return true;
}

function wp_remote_get(string $url, array $arguments): array
{
    global $hgw_test_requests, $hgw_test_scenario;
    unset($arguments);
    $hgw_test_requests[] = $url;

    if (in_array($hgw_test_scenario, array('redirect', 'failure'), true) && str_contains($url, 'raw.githubusercontent.com')) {
        return array('response' => array('code' => 404, 'message' => 'Not Found'), 'body' => '', 'headers' => array());
    }
    if ('redirect' === $hgw_test_scenario && str_ends_with($url, '/releases/latest')) {
        return array(
            'response' => array('code' => 302, 'message' => 'Found'),
            'body'     => '',
            'headers'  => array('location' => 'https://github.com/cchatterton/help-guides/releases/tag/v1.2.1'),
        );
    }
    if ('failure' === $hgw_test_scenario && str_contains($url, 'api.github.com')) {
        return array('response' => array('code' => 429, 'message' => 'Too Many Requests'), 'body' => '', 'headers' => array());
    }
    if ('failure' === $hgw_test_scenario && str_ends_with($url, '/releases/latest')) {
        return array('response' => array('code' => 503, 'message' => 'Unavailable'), 'body' => '', 'headers' => array());
    }
    return array(
        'response' => array('code' => 200, 'message' => 'OK'),
        'body'     => '{"version":"1.2.1","body":"Release summary."}',
        'headers'  => array(),
    );
}

function is_wp_error(mixed $value): bool
{
    return $value instanceof WP_Error;
}

function wp_remote_retrieve_response_code(array $response): int
{
    return (int) $response['response']['code'];
}

function wp_remote_retrieve_body(array $response): string
{
    return (string) $response['body'];
}

function wp_remote_retrieve_header(array $response, string $header): string
{
    return (string) ($response['headers'][$header] ?? '');
}

function sanitize_textarea_field(string $value): string
{
    return $value;
}

function sanitize_text_field(string $value): string
{
    return $value;
}

function sanitize_key(string $value): string
{
    return strtolower(preg_replace('/[^a-z0-9_\-]/', '', $value));
}

require dirname(__DIR__) . '/Help Guides/includes/github-updater.php';

$release = hgw_latest_github_release();
$manifest_url = 'https://raw.githubusercontent.com/cchatterton/help-guides/main/update.json';
$expected_requests = 'redirect' === $hgw_test_scenario
    ? array($manifest_url, 'https://github.com/cchatterton/help-guides/releases/latest')
    : array($manifest_url);

if ('failure' === $hgw_test_scenario) {
    $expected_requests = array(
        $manifest_url,
        'https://github.com/cchatterton/help-guides/releases/latest',
        'https://api.github.com/repos/cchatterton/help-guides/releases/latest',
    );
}

if ('failure' !== $hgw_test_scenario && (!is_array($release) || '1.2.1' !== $release['version'])) {
    fwrite(STDERR, "Manifest release was not parsed.\n");
    exit(1);
}

if ('failure' === $hgw_test_scenario && (null !== $release || empty($hgw_test_transients['hgw_github_lookup_backoff']) || 'rate_limited' !== ($hgw_test_transients['hgw_github_release_error']['type'] ?? ''))) {
    fwrite(STDERR, var_export($hgw_test_transients, true) . "\n");
    fwrite(STDERR, "A rate-limit failure did not create separate backoff and diagnostic state.\n");
    exit(1);
}

if ($expected_requests !== $hgw_test_requests) {
    fwrite(STDERR, "The updater did not follow the expected rate-limit-safe lookup order.\n");
    exit(1);
}

echo ucfirst($hgw_test_scenario) . " updater-order test passed.\n";
