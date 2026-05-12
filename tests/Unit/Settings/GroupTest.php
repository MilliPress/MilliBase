<?php

use MilliBase\Settings;
use MilliBase\Settings\Group;

function group_settings(string $slug, array $defaults): Settings
{
    return new Settings([
        'slug'     => $slug,
        'defaults' => $defaults,
    ]);
}

// ─── Routing helpers ────────────────────────────────────────────────

it('get(key) routes to the owning Settings', function () {
    $cache   = group_settings('cache', ['cache' => ['ttl' => 60]]);
    $storage = group_settings('storage', ['storage' => ['host' => 'localhost']]);

    $group = new Group($cache);
    $group->add($storage);

    expect($group->get('storage.host'))->toBe('localhost');
    expect($group->get('cache.ttl'))->toBe(60);
});

it('get() with no key merges trees from every Settings', function () {
    $cache   = group_settings('cache', ['cache' => ['ttl' => 60]]);
    $storage = group_settings('storage', ['storage' => ['host' => 'localhost']]);

    $group = new Group($cache);
    $group->add($storage);

    $merged = $group->get();
    expect($merged)->toHaveKey('cache');
    expect($merged)->toHaveKey('storage');
    expect($merged['cache']['ttl'])->toBe(60);
    expect($merged['storage']['host'])->toBe('localhost');
});

it('get(key) falls back to primary when no Settings owns the module', function () {
    $primary = group_settings('a', ['cache' => ['ttl' => 60]]);
    $group   = new Group($primary);

    expect($group->get('unknown.key'))->toBeNull();
});

it('set routes to the owning Settings and returns true', function () {
    $cache   = group_settings('cache-w', ['cache' => ['ttl' => 60]]);
    $storage = group_settings('storage-w', ['storage' => ['host' => 'localhost']]);

    $group = new Group($cache);
    $group->add($storage);

    expect($group->set('storage.host', '1.2.3.4'))->toBeTrue();
    expect($group->get('storage.host'))->toBe('1.2.3.4');
    expect($group->get('cache.ttl'))->toBe(60);
});

it('set returns false for an unknown module', function () {
    $group = new Group(group_settings('o', ['cache' => ['ttl' => 60]]));

    expect($group->set('unknown.key', 'x'))->toBeFalse();
});

// ─── reset routing ──────────────────────────────────────────────────

it('reset(null) resets every wrapped Settings', function () {
    $a = group_settings('reset-a', ['cache' => ['ttl' => 60]]);
    $b = group_settings('reset-b', ['storage' => ['host' => 'localhost']]);

    $a->set('cache.ttl', 9999);
    $b->set('storage.host', 'changed');

    $group = new Group($a);
    $group->add($b);
    expect($group->reset())->toBeTrue();

    expect($a->get('cache.ttl'))->toBe(60);
    expect($b->get('storage.host'))->toBe('localhost');
});

it('reset(module) routes to owning Settings only', function () {
    $a = group_settings('reset-c', ['cache' => ['ttl' => 60]]);
    $b = group_settings('reset-d', ['storage' => ['host' => 'localhost']]);

    $a->set('cache.ttl', 9999);
    $b->set('storage.host', 'changed');

    $group = new Group($a);
    $group->add($b);
    expect($group->reset('storage'))->toBeTrue();

    expect($a->get('cache.ttl'))->toBe(9999);   // untouched
    expect($b->get('storage.host'))->toBe('localhost');
});

it('reset(unknown_module) returns false', function () {
    $group = new Group(group_settings('o2', ['cache' => ['ttl' => 60]]));
    expect($group->reset('unknown'))->toBeFalse();
});

// ─── Defaults & sources ─────────────────────────────────────────────

it('get_default_settings() merges defaults from all wrapped Settings', function () {
    $a = group_settings('defs-a', ['cache' => ['ttl' => 60]]);
    $b = group_settings('defs-b', ['storage' => ['host' => 'localhost']]);

    $group = new Group($a);
    $group->add($b);
    $merged = $group->get_default_settings();

    expect($merged)->toHaveKey('cache');
    expect($merged)->toHaveKey('storage');
});

it('get_default_settings(module) routes to the owner', function () {
    $a = group_settings('defs-c', ['cache' => ['ttl' => 60]]);
    $b = group_settings('defs-d', ['storage' => ['host' => 'localhost']]);

    $group = new Group($a);
    $group->add($b);

    $cache_defaults = $group->get_default_settings('cache');
    expect($cache_defaults)->toHaveKey('cache');
    expect($cache_defaults)->not->toHaveKey('storage');
});

it('get_source routes to the owning Settings', function () {
    $a = group_settings('src-a', ['cache' => ['ttl' => 60]]);
    $b = group_settings('src-b', ['storage' => ['host' => 'localhost']]);

    $group = new Group($a);
    $group->add($b);

    expect($group->get_source('cache', 'ttl'))->toBe('default');
    expect($group->get_source('storage', 'host'))->toBe('default');
    expect($group->get_source('unknown', 'x'))->toBe('default');
});
