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

// ─── ['component' => '…'] form (React-portal hydration) ─────────────

it('renders a placeholder span when `footer.right` is `[component => name]`', function () {
    $rendered = make_admin_page(['footer' => ['right' => ['component' => 'MyFooterRight']]])
        ->filter_update_footer('Version 6.7.1');

    // Placeholder is hydrated by FooterRenderer at runtime; MilliBase
    // version is still appended so the framework version stays visible.
    expect($rendered)->toContain('<span class="millibase-footer-slot" data-component="MyFooterRight"></span>');
    expect($rendered)->toContain(' · MilliBase ');
});

it('renders a placeholder span when `footer.left` is `[component => name]`', function () {
    $rendered = make_admin_page(['footer' => ['left' => ['component' => 'MyFooterLeft']]])
        ->filter_admin_footer_text('Thank you for creating with WordPress.');

    expect($rendered)->toBe('<span class="millibase-footer-slot" data-component="MyFooterLeft"></span>');
});

it('escapes the component name to keep the data attribute safe against malicious config', function () {
    $rendered = make_admin_page([
        'footer' => ['right' => ['component' => 'X"><script>alert(1)</script>']],
    ])->filter_update_footer('');

    expect($rendered)->not->toContain('<script>');
    // esc_attr converts " into &quot;
    expect($rendered)->toContain('&quot;');
});

it('ignores a `[component => "" ]` shape (malformed) and falls back to MilliBase-only', function () {
    $rendered = make_admin_page(['footer' => ['right' => ['component' => '']]])
        ->filter_update_footer('');

    expect($rendered)->toStartWith('MilliBase ');
    expect($rendered)->not->toContain('millibase-footer-slot');
});

it('ignores a `[component => 42]` shape (non-string) and falls back', function () {
    $rendered = make_admin_page(['footer' => ['left' => ['component' => 42]]])
        ->filter_admin_footer_text('Thank you for creating with WordPress.');

    expect($rendered)->toBe('Thank you for creating with WordPress.');
});

// ─── wp_kses_post sanitization (string form) ────────────────────────

it('lets safe HTML (anchors, basic formatting) survive in `footer.right`', function () {
    $rendered = make_admin_page([
        'footer' => ['right' => '<a href="https://example.com">Docs</a> · <strong>Pro</strong>'],
    ])->filter_update_footer('');

    expect($rendered)->toContain('<a href="https://example.com">Docs</a>');
    expect($rendered)->toContain('<strong>Pro</strong>');
});

it('strips dangerous HTML (script/style) from string footer config', function () {
    $rendered = make_admin_page([
        'footer' => ['left' => '<script>alert(1)</script>Hello'],
    ])->filter_admin_footer_text('');

    expect($rendered)->not->toContain('<script>');
    expect($rendered)->toContain('Hello');
});
