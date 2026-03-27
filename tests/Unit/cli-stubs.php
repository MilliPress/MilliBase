<?php
/**
 * WP-CLI stubs for CliController unit tests.
 *
 * Provides minimal WP_CLI class and utility function stubs that
 * capture calls for assertions rather than producing real output.
 */

namespace WP_CLI\Utils {
    if (! function_exists('WP_CLI\Utils\format_items')) {
        /** @param array<int, array<string, string>> $items */
        function format_items(string $format, array $items, array $fields): void
        {
            \WP_CLI::$calls['format_items'][] = [$format, $items, $fields];
        }
    }

    if (! function_exists('WP_CLI\Utils\get_flag_value')) {
        /**
         * @param array<string, mixed> $assoc_args
         * @param mixed                $default
         * @return mixed
         */
        function get_flag_value(array $assoc_args, string $flag, $default = null)
        {
            if (isset($assoc_args[$flag])) {
                return $assoc_args[$flag];
            }
            return $default;
        }
    }
}

namespace {
    if (! class_exists('WP_CLI')) {
        class WP_CLI
        {
            /** @var array<string, list<array<int, mixed>>> */
            public static array $calls = [];

            public static function reset(): void
            {
                self::$calls = [];
            }

            /** @param object|string $instance */
            public static function add_command(string $name, $instance): void
            {
                self::$calls['add_command'][] = [$name, $instance];
            }

            /**
             * @return never
             */
            public static function error(string $message): void
            {
                self::$calls['error'][] = [$message];
                throw new \RuntimeException("WP_CLI::error: $message");
            }

            public static function success(string $message): void
            {
                self::$calls['success'][] = [$message];
            }

            public static function warning(string $message): void
            {
                self::$calls['warning'][] = [$message];
            }

            /** @param array<string, string> $assoc_args */
            public static function confirm(string $message, array $assoc_args = []): void
            {
                self::$calls['confirm'][] = [$message];
            }

            public static function line(string $message = ''): void
            {
                self::$calls['line'][] = [$message];
            }

            /** @param mixed $value */
            public static function print_value($value, array $assoc_args = []): void
            {
                self::$calls['print_value'][] = [$value, $assoc_args];
            }
        }
    }

    if (! defined('HOUR_IN_SECONDS')) {
        define('HOUR_IN_SECONDS', 3600);
    }

    if (! function_exists('update_option')) {
        function update_option(string $key, $value, $autoload = null): bool
        {
            return true;
        }
    }

    if (! function_exists('delete_option')) {
        function delete_option(string $key): bool
        {
            return true;
        }
    }

    if (! function_exists('set_transient')) {
        function set_transient(string $key, $value, int $expiration = 0): bool
        {
            return true;
        }
    }

    if (! function_exists('delete_transient')) {
        function delete_transient(string $key): bool
        {
            return true;
        }
    }

    if (! function_exists('get_option')) {
        function get_option(string $key, $default = false)
        {
            return $default;
        }
    }

    if (! function_exists('get_transient')) {
        function get_transient(string $key)
        {
            return false;
        }
    }

    if (! function_exists('apply_filters')) {
        function apply_filters(string $hook, ...$args)
        {
            return $args[0];
        }
    }
}
