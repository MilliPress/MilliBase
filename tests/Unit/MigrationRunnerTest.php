<?php

use MilliBase\Migration\Runner;

beforeEach(function () {
    $GLOBALS['__milli_test_options']      = [];
    $GLOBALS['__milli_test_site_options'] = [];
    $GLOBALS['__milli_test_is_multisite'] = false;
});

/**
 * Build a stand-in "manager" object for callback identity checks.
 */
function migration_test_manager(): object
{
    return (object) ['marker' => 'test-manager-' . uniqid()];
}

// ─── 1. Empty migrations array ───────────────────────────────────────

it('does nothing when the migrations list is empty', function () {
    $runner = new Runner('test', [], migration_test_manager());

    $runner->run();

    expect($GLOBALS['__milli_test_options'])->toBe([]);
    expect($GLOBALS['__milli_test_site_options'])->toBe([]);
});

// ─── 2. scope: site migration runs once ──────────────────────────────

it('runs a site-scoped migration once and skips it on the next run', function () {
    $calls = 0;
    $migrations = [
        [
            'name'     => 'move_thing',
            'version'  => '1.0.0',
            'scope'    => 'site',
            'callback' => function ($manager) use (&$calls) {
                $calls++;
            },
        ],
    ];

    (new Runner('test', $migrations, migration_test_manager()))->run();

    expect($calls)->toBe(1);
    expect($GLOBALS['__milli_test_options']['test_migration_state'] ?? null)
        ->toBe(['move_thing@1.0.0' => 'completed']);
    expect($GLOBALS['__milli_test_site_options'])->toBe([]);

    (new Runner('test', $migrations, migration_test_manager()))->run();

    expect($calls)->toBe(1);
});

// ─── 3. scope: network on multisite ──────────────────────────────────

it('runs a network-scoped migration on multisite and records it in site_options', function () {
    $GLOBALS['__milli_test_is_multisite'] = true;

    $calls = 0;
    $migrations = [
        [
            'name'     => 'move_thing',
            'version'  => '1.0.0',
            'scope'    => 'network',
            'callback' => function ($manager) use (&$calls) {
                $calls++;
            },
        ],
    ];

    (new Runner('test', $migrations, migration_test_manager()))->run();

    expect($calls)->toBe(1);
    expect($GLOBALS['__milli_test_site_options']['test_migration_state'] ?? null)
        ->toBe(['move_thing@1.0.0' => 'completed']);
    expect($GLOBALS['__milli_test_options'])->toBe([]);

    (new Runner('test', $migrations, migration_test_manager()))->run();

    expect($calls)->toBe(1);
});

// ─── 4. scope: network on single-site ────────────────────────────────

it('silently skips network-scoped migrations on single-site and writes no state', function () {
    $GLOBALS['__milli_test_is_multisite'] = false;

    $calls = 0;
    $migrations = [
        [
            'name'     => 'move_thing',
            'version'  => '1.0.0',
            'scope'    => 'network',
            'callback' => function ($manager) use (&$calls) {
                $calls++;
            },
        ],
    ];

    (new Runner('test', $migrations, migration_test_manager()))->run();

    expect($calls)->toBe(0);
    expect($GLOBALS['__milli_test_options'])->toBe([]);
    expect($GLOBALS['__milli_test_site_options'])->toBe([]);
});

// ─── 5. Failing migration ────────────────────────────────────────────

it('records failure with message and timestamp, and does not retry', function () {
    $calls = 0;
    $migrations = [
        [
            'name'     => 'broken',
            'version'  => '1.0.0',
            'scope'    => 'site',
            'callback' => function ($manager) use (&$calls) {
                $calls++;
                throw new RuntimeException('intentional');
            },
        ],
    ];

    $before = time();
    (new Runner('test', $migrations, migration_test_manager()))->run();
    $after = time();

    $state = $GLOBALS['__milli_test_options']['test_migration_state'] ?? null;
    expect($state)->toBeArray()->toHaveKey('broken@1.0.0');

    $entry = $state['broken@1.0.0'];
    expect($entry[0])->toBe('failed');
    expect($entry[1])->toBe('intentional');
    expect($entry[2])->toBeGreaterThanOrEqual($before);
    expect($entry[2])->toBeLessThanOrEqual($after);

    (new Runner('test', $migrations, migration_test_manager()))->run();
    expect($calls)->toBe(1);
});

// ─── 6. Multiple migrations, mixed scopes, array order ───────────────

it('runs migrations in array order across mixed scopes', function () {
    $GLOBALS['__milli_test_is_multisite'] = true;

    $order = [];
    $migrations = [
        ['name' => 'A', 'version' => '1.0.0', 'scope' => 'site',    'callback' => function ($m) use (&$order) { $order[] = 'A'; }],
        ['name' => 'B', 'version' => '1.0.0', 'scope' => 'network', 'callback' => function ($m) use (&$order) { $order[] = 'B'; }],
        ['name' => 'C', 'version' => '1.0.0', 'scope' => 'site',    'callback' => function ($m) use (&$order) { $order[] = 'C'; }],
    ];

    (new Runner('test', $migrations, migration_test_manager()))->run();

    expect($order)->toBe(['A', 'B', 'C']);
    expect($GLOBALS['__milli_test_options']['test_migration_state'])
        ->toBe(['A@1.0.0' => 'completed', 'C@1.0.0' => 'completed']);
    expect($GLOBALS['__milli_test_site_options']['test_migration_state'])
        ->toBe(['B@1.0.0' => 'completed']);
});

// ─── 7. Version bump re-runs ─────────────────────────────────────────

it('treats a version bump as a new identity and re-runs the migration', function () {
    $calls = 0;
    $cb = function ($m) use (&$calls) { $calls++; };

    (new Runner('test', [
        ['name' => 'reshape', 'version' => '1.0.0', 'scope' => 'site', 'callback' => $cb],
    ], migration_test_manager()))->run();

    expect($calls)->toBe(1);

    (new Runner('test', [
        ['name' => 'reshape', 'version' => '1.1.0', 'scope' => 'site', 'callback' => $cb],
    ], migration_test_manager()))->run();

    expect($calls)->toBe(2);
    expect($GLOBALS['__milli_test_options']['test_migration_state'])
        ->toBe([
            'reshape@1.0.0' => 'completed',
            'reshape@1.1.0' => 'completed',
        ]);
});

// ─── 8. Callback receives the Manager ────────────────────────────────

it('passes the Manager instance to each callback', function () {
    $manager  = migration_test_manager();
    $received = null;

    (new Runner('test', [
        [
            'name'     => 'check',
            'version'  => '1.0.0',
            'scope'    => 'site',
            'callback' => function ($m) use (&$received) {
                $received = $m;
            },
        ],
    ], $manager))->run();

    expect($received)->toBe($manager);
});

// ─── Bonus: malformed entries are skipped without aborting the run ────

it('skips malformed migrations without aborting subsequent ones', function () {
    $calls = 0;
    $migrations = [
        ['scope' => 'site', 'callback' => function () {}],   // no name/version
        ['name' => 'good', 'version' => '1.0.0', 'scope' => 'site', 'callback' => function ($m) use (&$calls) { $calls++; }],
        ['name' => 'bad-scope', 'version' => '1.0.0', 'scope' => 'install', 'callback' => function () {}],
        ['name' => 'no-cb', 'version' => '1.0.0', 'scope' => 'site'],
    ];

    (new Runner('test', $migrations, migration_test_manager()))->run();

    expect($calls)->toBe(1);
    expect($GLOBALS['__milli_test_options']['test_migration_state'])
        ->toBe(['good@1.0.0' => 'completed']);
});
