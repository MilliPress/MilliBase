<?php

use MilliBase\Manager;
use MilliBase\Settings;

// ─── empty-slug bail-out (CR-1) ─────────────────────────────────────

it('does not throw when constructed with an empty slug', function () {
    $config = static fn () => [
        'tabs' => [],
    ];

    // Settings::__construct throws on empty slug; the Manager must
    // short-circuit before getting that far so the host site does not
    // 500 just because a plugin's slug filter returned ''.
    $manager = new Manager('', $config);

    expect($manager)->toBeInstanceOf(Manager::class);
});

it('does not register settings, controllers, or hooks for an empty slug', function () {
    $config = static fn () => [
        'tabs' => [],
    ];

    $GLOBALS['__milli_test_actions'] = [];
    $manager = new Manager('', $config);

    // No init-hook attached — empty-slug constructor returned before
    // either the immediate boot() path or the deferred add_action.
    expect($GLOBALS['__milli_test_actions']['init'] ?? [])->toBe([]);

    // settings() / schema() raise LogicException because the underlying
    // instances were never initialised.
    expect(fn () => $manager->settings())->toThrow(LogicException::class);
});

it('does register an init hook when constructed with a non-empty slug before init', function () {
    $config = static fn () => [
        'tabs' => [],
    ];

    $GLOBALS['__milli_test_actions'] = [];
    new Manager('test-slug', $config);

    expect($GLOBALS['__milli_test_actions'])->toHaveKey('init');
});

// ─── abilities_active() helper ──────────────────────────────────────

it('reports abilities-API availability via abilities_active()', function () {
    $manager = new Manager('test-slug', static fn () => ['tabs' => []]);

    // The bootstrap stubs in tests/bootstrap.php define wp_register_ability,
    // so the helper should report the API as available in the test env.
    expect($manager->abilities_active())->toBeTrue();
});
