<?php
/**
 * Test bootstrap — stub WordPress functions so pure-logic tests
 * can run without a full WordPress installation.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Stub sanitize_text_field().
if (! function_exists('sanitize_text_field')) {
    function sanitize_text_field(string $str): string
    {
        return strip_tags($str);
    }
}

// Stub sanitize_hex_color().
if (! function_exists('sanitize_hex_color')) {
    function sanitize_hex_color(string $color): ?string
    {
        if (preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $color)) {
            return $color;
        }

        return null;
    }
}

// Stub sanitize_file_name().
if (! function_exists('sanitize_file_name')) {
    function sanitize_file_name(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename);
    }
}

// Stub ABSPATH constant.
if (! defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wp/');
}

// Stub FS_CHMOD_FILE constant.
if (! defined('FS_CHMOD_FILE')) {
    define('FS_CHMOD_FILE', 0644);
}

// Stub wp_mkdir_p().
if (! function_exists('wp_mkdir_p')) {
    function wp_mkdir_p(string $target): bool
    {
        if (is_dir($target)) {
            return true;
        }
        return mkdir($target, 0755, true);
    }
}

// Stub wp_delete_file().
if (! function_exists('wp_delete_file')) {
    function wp_delete_file(string $file): void
    {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

// Stub WP_Filesystem and $wp_filesystem global.
if (! function_exists('WP_Filesystem')) {
    // Minimal filesystem object that delegates to native PHP.
    $GLOBALS['wp_filesystem'] = new class {
        public function put_contents(string $file, string $contents, int $mode = 0644): bool
        {
            return file_put_contents($file, $contents) !== false;
        }
    };

    function WP_Filesystem(): bool
    {
        return true;
    }
}

// ─── REST / hook stubs for action tests ─────────────────────────────

if (! class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        private array $params;

        public function __construct(array $params = [])
        {
            $this->params = $params;
        }

        public function get_param(string $key)
        {
            return $this->params[$key] ?? null;
        }

        public function get_params(): array
        {
            return $this->params;
        }
    }
}

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        public string $code;
        public string $message;
        /** @var array<string, mixed> */
        public array $data;

        public function __construct(string $code = '', string $message = '', array $data = [])
        {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }

        public function get_error_data()
        {
            return $this->data;
        }
    }
}

if (! function_exists('add_filter')) {
    function add_filter(string $hook, callable $callback, int $priority = 10): bool
    {
        $GLOBALS['__milli_test_filters'][$hook][$priority][] = $callback;
        return true;
    }
}

if (! function_exists('add_action')) {
    function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        $GLOBALS['__milli_test_actions'][$hook][$priority][] = $callback;
        return true;
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, ...$args)
    {
        $value = $args[0];
        $bag   = $GLOBALS['__milli_test_filters'][$hook] ?? [];
        ksort($bag);
        foreach ($bag as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...array_slice($args, 1));
            }
        }
        return $value;
    }
}

if (! function_exists('do_action')) {
    function do_action(string $hook, ...$args): void
    {
        // Record every fired hook so tests can assert on (non-)dispatch, then
        // dispatch to registered callbacks the way apply_filters() already does.
        $GLOBALS['__milli_test_actions_fired'][] = $hook;

        $bag = $GLOBALS['__milli_test_actions'][$hook] ?? [];
        ksort($bag);
        foreach ($bag as $callbacks) {
            foreach ($callbacks as $callback) {
                $callback(...$args);
            }
        }
    }
}

if (! defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (! function_exists('get_transient')) {
    function get_transient(string $key)
    {
        return $GLOBALS['__milli_test_transients'][$key] ?? false;
    }
}

if (! function_exists('set_transient')) {
    function set_transient(string $key, $value, int $expiration = 0): bool
    {
        $GLOBALS['__milli_test_transients'][$key] = $value;
        return true;
    }
}

if (! function_exists('delete_transient')) {
    function delete_transient(string $key): bool
    {
        unset($GLOBALS['__milli_test_transients'][$key]);
        return true;
    }
}

if (! function_exists('get_site_transient')) {
    function get_site_transient(string $key)
    {
        return $GLOBALS['__milli_test_site_transients'][$key] ?? false;
    }
}

if (! function_exists('set_site_transient')) {
    function set_site_transient(string $key, $value, int $expiration = 0): bool
    {
        $GLOBALS['__milli_test_site_transients'][$key] = $value;
        return true;
    }
}

if (! function_exists('delete_site_transient')) {
    function delete_site_transient(string $key): bool
    {
        unset($GLOBALS['__milli_test_site_transients'][$key]);
        return true;
    }
}

// Stateful option/site_option/transient stubs for tests that exercise
// storage round-trips (Migration runner, etc.). Backed by $GLOBALS so
// tests can reset between cases.
$GLOBALS['__milli_test_options']      = [];
$GLOBALS['__milli_test_site_options'] = [];
$GLOBALS['__milli_test_transients']   = [];
$GLOBALS['__milli_test_is_multisite'] = false;

if (! function_exists('get_option')) {
    function get_option(string $key, $default = false)
    {
        return $GLOBALS['__milli_test_options'][$key] ?? $default;
    }
}

if (! function_exists('update_option')) {
    function update_option(string $key, $value): bool
    {
        $existed = array_key_exists($key, $GLOBALS['__milli_test_options']);
        $old     = $GLOBALS['__milli_test_options'][$key] ?? false;

        // Mirror WordPress: an unchanged value short-circuits before any
        // hooks fire and reports false (no DB write happened).
        if ($existed && $old === $value) {
            return false;
        }

        $GLOBALS['__milli_test_options'][$key] = $value;

        // Fire the matching lifecycle action so registered Settings hooks run.
        if ($existed) {
            do_action("update_option_{$key}", $old, $value);
        } else {
            do_action("add_option_{$key}", $key, $value);
        }

        return true;
    }
}

if (! function_exists('delete_option')) {
    function delete_option(string $name): bool
    {
        unset($GLOBALS['__milli_test_options'][$name]);
        do_action('delete_option', $name);
        return true;
    }
}

if (! function_exists('get_site_option')) {
    function get_site_option(string $key, $default = false)
    {
        return $GLOBALS['__milli_test_site_options'][$key] ?? $default;
    }
}

if (! function_exists('update_site_option')) {
    function update_site_option(string $key, $value): bool
    {
        $existed = array_key_exists($key, $GLOBALS['__milli_test_site_options']);
        $old     = $GLOBALS['__milli_test_site_options'][$key] ?? false;

        // Mirror WordPress: an unchanged value short-circuits before any
        // hooks fire and reports false (no DB write happened).
        if ($existed && $old === $value) {
            return false;
        }

        $GLOBALS['__milli_test_site_options'][$key] = $value;

        if ($existed) {
            do_action("update_site_option_{$key}", $key, $value, $old);
        } else {
            do_action("add_site_option_{$key}", $key, $value);
        }

        return true;
    }
}

if (! function_exists('delete_site_option')) {
    function delete_site_option(string $name): bool
    {
        unset($GLOBALS['__milli_test_site_options'][$name]);
        do_action('delete_site_option', $name);
        return true;
    }
}

if (! function_exists('is_multisite')) {
    function is_multisite(): bool
    {
        return (bool) ($GLOBALS['__milli_test_is_multisite'] ?? false);
    }
}

if (! function_exists('_doing_it_wrong')) {
    function _doing_it_wrong(string $function, string $message, string $version): void
    {
        $GLOBALS['__milli_test_doing_it_wrong'][] = compact('function', 'message', 'version');
    }
}

if (! function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return $text;
    }
}

if (! function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        // Minimal stub: escape ", &, <, > the way WP's esc_attr does for tests.
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (! function_exists('wp_kses')) {
    function wp_kses(string $content, array $allowed_html): string
    {
        // Minimal stub: strip tags outside the allowlist. Attribute
        // filtering is not emulated — tests only verify disallowed tags
        // are removed and allowed ones survive.
        return strip_tags($content, array_keys($allowed_html));
    }
}

if (! function_exists('wp_kses_post')) {
    function wp_kses_post(string $html): string
    {
        // Minimal stub: strip <script>/<style> blocks (the dangerous shapes
        // we care about in tests). Real WP allows post-safe HTML; this
        // stub is just enough to verify the production code passes input
        // through a sanitizer and dangerous tags don't reach the output.
        $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html);
        return is_string($html) ? $html : '';
    }
}

if (! function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (! function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

if (! function_exists('rest_ensure_response')) {
    function rest_ensure_response($response): WP_REST_Response
    {
        if ($response instanceof WP_REST_Response) {
            return $response;
        }
        return new WP_REST_Response(is_array($response) ? $response : []);
    }
}

// Abilities-API stubs — global so every test that loads bootstrap sees them. Recorders reset per-test in tests/Pest.php.

if (! function_exists('wp_register_ability_category')) {
    function wp_register_ability_category(string $slug, array $args)
    {
        $GLOBALS['millibase_abilities_calls'][] = [
            'fn'   => 'wp_register_ability_category',
            'slug' => $slug,
            'args' => $args,
        ];
        $GLOBALS['millibase_abilities_categories'][$slug] = true;
        return null;
    }
}

if (! function_exists('wp_register_ability')) {
    function wp_register_ability(string $name, array $args)
    {
        $GLOBALS['millibase_abilities_calls'][] = [
            'fn'   => 'wp_register_ability',
            'name' => $name,
            'args' => $args,
        ];
        $GLOBALS['millibase_abilities_names'][$name] = true;
        return null;
    }
}

if (! function_exists('wp_has_ability_category')) {
    function wp_has_ability_category(string $slug): bool
    {
        return isset($GLOBALS['millibase_abilities_categories'][$slug]);
    }
}

if (! function_exists('wp_has_ability')) {
    function wp_has_ability(string $name): bool
    {
        return isset($GLOBALS['millibase_abilities_names'][$name]);
    }
}

if (! function_exists('current_user_can')) {
    function current_user_can(string $cap): bool
    {
        return $GLOBALS['millibase_abilities_can'][$cap] ?? false;
    }
}
