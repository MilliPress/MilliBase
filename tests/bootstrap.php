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
