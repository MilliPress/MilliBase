<?php

use MilliBase\Manager;
use MilliBase\Settings;
use MilliBase\Settings\Group;

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

it('fires {slug}_settings_schema with $is_network as the second argument', function () {
    $GLOBALS['__milli_test_filters']  = [];
    $GLOBALS['__milli_test_actions']  = [];

    $captured = [];
    add_filter('test-slug_settings_schema', function ($config, $is_network) use (&$captured) {
        $captured[] = [$config, $is_network];
        return $config;
    }, 10);

    new Manager('test-slug', static fn () => [
        'tabs'    => [],
        'network' => true,
    ]);

    // Drive the queued `init` callback (Manager hooks itself on init priority 0).
    foreach ($GLOBALS['__milli_test_actions']['init'] ?? [] as $by_priority) {
        foreach ($by_priority as $cb) {
            $cb();
        }
    }

    // At late firing (in resolve_schema), $is_network reflects the resolved config.
    expect($captured)->not->toBe([]);
    $last = end($captured);
    expect($last[1])->toBeTrue();
});

it('reports abilities-API availability via abilities_active()', function () {
    $manager = new Manager('test-slug', static fn () => ['tabs' => []]);

    // The bootstrap stubs in tests/bootstrap.php define wp_register_ability,
    // so the helper should report the API as available in the test env.
    expect($manager->abilities_active())->toBeTrue();
});

it('merges two Managers sharing a slug into one abilities Settings\\Group', function () {
    $reflection = new ReflectionClass(Manager::class);
    $registry   = $reflection->getProperty('abilities_groups');
    $registry->setAccessible(true);
    $registry->setValue(null, []);
    $fingerprints = $reflection->getProperty('registered_fingerprints');
    $fingerprints->setAccessible(true);
    $fingerprints->setValue(null, []);

    // Realistic shared-slug pattern: one per-site + one network Manager.
    // Two per-site Managers sharing a slug would trigger the collision guard.
    new Manager('shared-slug', static fn () => ['tabs' => []]);
    new Manager('shared-slug', static fn () => ['tabs' => [], 'network' => true]);

    foreach ($GLOBALS['__milli_test_actions']['init'] ?? [] as $by_priority) {
        foreach ($by_priority as $cb) {
            $cb();
        }
    }

    $groups = $registry->getValue();
    expect($groups)->toHaveKey('shared-slug');
    expect($groups['shared-slug'])->toBeInstanceOf(Group::class);

    // Both Settings landed in the same Group — the second Manager appended
    // rather than replacing or being silently dropped.
    $listProp = (new ReflectionClass(Group::class))->getProperty('list');
    $listProp->setAccessible(true);
    expect($listProp->getValue($groups['shared-slug']))->toHaveCount(2);
});

it('warns via _doing_it_wrong when two Managers share slug and network mode', function () {
    $reflection   = new ReflectionClass(Manager::class);
    $fingerprints = $reflection->getProperty('registered_fingerprints');
    $fingerprints->setAccessible(true);
    $fingerprints->setValue(null, []);

    new Manager('collide', static fn () => ['tabs' => []]);
    new Manager('collide', static fn () => ['tabs' => []]);

    foreach ($GLOBALS['__milli_test_actions']['init'] ?? [] as $by_priority) {
        foreach ($by_priority as $cb) {
            $cb();
        }
    }

    expect($GLOBALS['__milli_test_doing_it_wrong'])->not->toBe([]);
    $last = end($GLOBALS['__milli_test_doing_it_wrong']);
    expect($last['message'])->toContain('"collide"');
    expect($last['message'])->toContain('network=false');
});

it('does not warn when two Managers share slug but differ in network mode', function () {
    $reflection   = new ReflectionClass(Manager::class);
    $fingerprints = $reflection->getProperty('registered_fingerprints');
    $fingerprints->setAccessible(true);
    $fingerprints->setValue(null, []);

    new Manager('split', static fn () => ['tabs' => []]);
    new Manager('split', static fn () => ['tabs' => [], 'network' => true]);

    foreach ($GLOBALS['__milli_test_actions']['init'] ?? [] as $by_priority) {
        foreach ($by_priority as $cb) {
            $cb();
        }
    }

    expect($GLOBALS['__milli_test_doing_it_wrong'])->toBe([]);
});
