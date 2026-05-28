<?php

use MilliBase\AdminPage;
use MilliBase\Schema;

function make_admin_page(array $config = []): AdminPage
{
    $config = array_merge(['slug' => 'test', 'tabs' => []], $config);
    return new AdminPage($config, new Schema($config));
}

// ─── filter_update_footer (right slot) ──────────────────────────────

it('renders just "MilliBase {version}" on the right when no footer config is provided', function () {
    $rendered = make_admin_page()->filter_update_footer('Version 6.7.1');

    // Plaintext starts with "MilliBase " and discards WP's "Version 6.7.1".
    expect($rendered)->toStartWith('MilliBase ');
    expect($rendered)->not->toContain('Version 6.7.1');
});

it('prepends the consumer\'s `footer.right` to MilliBase\'s own version, separated by " · "', function () {
    $rendered = make_admin_page(['footer' => ['right' => 'MilliCache 2.0.1']])
        ->filter_update_footer('Version 6.7.1');

    expect($rendered)->toStartWith('MilliCache 2.0.1 · MilliBase ');
    expect($rendered)->not->toContain('Version 6.7.1');
});

it('ignores a non-string `footer.right` (defensive: malformed config falls back to MilliBase-only)', function () {
    $rendered = make_admin_page(['footer' => ['right' => ['unexpected', 'array']]])
        ->filter_update_footer('');

    expect($rendered)->toStartWith('MilliBase ');
});

it('treats an empty `footer.right` string the same as missing (no leading separator)', function () {
    $rendered = make_admin_page(['footer' => ['right' => '']])
        ->filter_update_footer('');

    // No "  · MilliBase …" — a present-but-empty value shouldn't dangle a separator.
    expect($rendered)->toStartWith('MilliBase ');
    expect($rendered)->not->toContain(' · MilliBase ');
});

// ─── filter_admin_footer_text (left slot) ───────────────────────────

it('passes the default left footer text through unchanged when `footer.left` is absent', function () {
    $rendered = make_admin_page()->filter_admin_footer_text('Thank you for creating with WordPress.');

    expect($rendered)->toBe('Thank you for creating with WordPress.');
});

it('replaces the left footer text with `footer.left` when provided', function () {
    $rendered = make_admin_page(['footer' => ['left' => 'Thanks for using MilliCache.']])
        ->filter_admin_footer_text('Thank you for creating with WordPress.');

    expect($rendered)->toBe('Thanks for using MilliCache.');
});

it('ignores a non-string `footer.left` (defensive: malformed config falls back to default)', function () {
    $rendered = make_admin_page(['footer' => ['left' => 42]])
        ->filter_admin_footer_text('Thank you for creating with WordPress.');

    expect($rendered)->toBe('Thank you for creating with WordPress.');
});
