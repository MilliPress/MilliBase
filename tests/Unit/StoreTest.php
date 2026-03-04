<?php

use MilliBase\Store;

// ─── coerce_value() ─────────────────────────────────────────────────

it('coerces "true" to boolean true', function () {
    expect(Store::coerce_value('true'))->toBeTrue();
    expect(Store::coerce_value('TRUE'))->toBeTrue();
    expect(Store::coerce_value('True'))->toBeTrue();
});

it('coerces "false" to boolean false', function () {
    expect(Store::coerce_value('false'))->toBeFalse();
    expect(Store::coerce_value('FALSE'))->toBeFalse();
});

it('coerces "null" to null', function () {
    expect(Store::coerce_value('null'))->toBeNull();
    expect(Store::coerce_value('NULL'))->toBeNull();
});

it('coerces integer strings', function () {
    expect(Store::coerce_value('42'))->toBe(42);
    expect(Store::coerce_value('0'))->toBe(0);
    expect(Store::coerce_value('-7'))->toBe(-7);
});

it('coerces float strings', function () {
    expect(Store::coerce_value('3.14'))->toBe(3.14);
    expect(Store::coerce_value('0.0'))->toBe(0.0);
});

it('returns non-numeric strings as-is', function () {
    expect(Store::coerce_value('hello'))->toBe('hello');
    expect(Store::coerce_value(''))->toBe('');
});

// ─── Constructor ────────────────────────────────────────────────────

it('defaults option_name to "millibase"', function () {
    $store = new Store([]);

    expect($store->get_option_name())->toBe('millibase');
});

it('uses provided option_name', function () {
    $store = new Store(['option_name' => 'my_settings']);

    expect($store->get_option_name())->toBe('my_settings');
});

// ─── get_default_settings() ─────────────────────────────────────────

it('returns all defaults when no module specified', function () {
    $defaults = [
        'cache' => ['enabled' => true, 'ttl' => 3600],
        'debug' => ['verbose' => false],
    ];

    $store = new Store(['defaults' => $defaults]);

    expect($store->get_default_settings())->toBe($defaults);
});

it('returns defaults filtered by module', function () {
    $defaults = [
        'cache' => ['enabled' => true, 'ttl' => 3600],
        'debug' => ['verbose' => false],
    ];

    $store = new Store(['defaults' => $defaults]);

    expect($store->get_default_settings('cache'))->toBe([
        'cache' => ['enabled' => true, 'ttl' => 3600],
    ]);
});

it('returns empty array for non-existent module', function () {
    $store = new Store(['defaults' => ['cache' => ['enabled' => true]]]);

    expect($store->get_default_settings('nonexistent'))->toBe([]);
});

// ─── get_settings_from_constants() ──────────────────────────────────

it('returns empty when constant_prefix is empty', function () {
    $store = new Store([
        'defaults' => ['cache' => ['enabled' => true]],
        'constant_prefix' => '',
    ]);

    expect($store->get_settings_from_constants())->toBe([]);
});

it('reads defined constants with prefix', function () {
    // Define a test constant.
    if (! defined('TEST_CACHE_TTL')) {
        define('TEST_CACHE_TTL', 7200);
    }

    $store = new Store([
        'defaults' => ['cache' => ['ttl' => 3600, 'enabled' => true]],
        'constant_prefix' => 'test',
    ]);

    $result = $store->get_settings_from_constants();

    expect($result)->toHaveKey('cache');
    expect($result['cache']['ttl'])->toBe(7200);
    // 'enabled' should not be present — no constant defined for it.
    expect($result['cache'])->not->toHaveKey('enabled');
});

it('resolves enc_ prefix stripping for encrypted fields', function () {
    // Define constant without enc_ prefix.
    if (! defined('TEST2_STORAGE_HOST')) {
        define('TEST2_STORAGE_HOST', 's3.example.com');
    }

    $store = new Store([
        'defaults' => ['storage' => ['enc_host' => '']],
        'constant_prefix' => 'test2',
    ]);

    $result = $store->get_settings_from_constants();

    expect($result['storage']['enc_host'])->toBe('s3.example.com');
});

it('filters constants by module', function () {
    if (! defined('TEST3_CACHE_TTL')) {
        define('TEST3_CACHE_TTL', 1000);
    }
    if (! defined('TEST3_DEBUG_VERBOSE')) {
        define('TEST3_DEBUG_VERBOSE', true);
    }

    $store = new Store([
        'defaults' => [
            'cache' => ['ttl' => 3600],
            'debug' => ['verbose' => false],
        ],
        'constant_prefix' => 'test3',
    ]);

    $result = $store->get_settings_from_constants('cache');

    expect($result)->toHaveKey('cache');
    expect($result)->not->toHaveKey('debug');
});

// ─── filter_settings_by_constants() ─────────────────────────────────

it('merges defaults and removes obsolete keys and modules', function () {
    $store = new Store([
        'defaults' => [
            'cache' => ['enabled' => true, 'ttl' => 3600],
        ],
        'constant_prefix' => '',
    ]);

    $settings = [
        'cache' => ['enabled' => false, 'obsolete_key' => 'remove me'],
        'obsolete_module' => ['key' => 'value'],
    ];

    $result = $store->filter_settings_by_constants($settings);

    // Missing default key 'ttl' should be added.
    expect($result['cache']['ttl'])->toBe(3600);
    // Existing key should be preserved.
    expect($result['cache']['enabled'])->toBeFalse();
    // Obsolete key should be removed.
    expect($result['cache'])->not->toHaveKey('obsolete_key');
    // Obsolete module should be removed.
    expect($result)->not->toHaveKey('obsolete_module');
});

it('returns empty array for non-array input', function () {
    $store = new Store(['defaults' => ['cache' => ['enabled' => true]]]);

    expect($store->filter_settings_by_constants(false))->toBe([]);
});
