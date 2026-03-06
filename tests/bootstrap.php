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
