<?php

use MilliBase\Settings;

// ─── coerce_value() ─────────────────────────────────────────────────

it('coerces "true" to boolean true', function () {
    expect(Settings::coerce_value('true'))->toBeTrue();
    expect(Settings::coerce_value('TRUE'))->toBeTrue();
    expect(Settings::coerce_value('True'))->toBeTrue();
});

it('coerces "false" to boolean false', function () {
    expect(Settings::coerce_value('false'))->toBeFalse();
    expect(Settings::coerce_value('FALSE'))->toBeFalse();
});

it('coerces "null" to null', function () {
    expect(Settings::coerce_value('null'))->toBeNull();
    expect(Settings::coerce_value('NULL'))->toBeNull();
});

it('coerces integer strings', function () {
    expect(Settings::coerce_value('42'))->toBe(42);
    expect(Settings::coerce_value('0'))->toBe(0);
    expect(Settings::coerce_value('-7'))->toBe(-7);
});

it('coerces float strings', function () {
    expect(Settings::coerce_value('3.14'))->toBe(3.14);
    expect(Settings::coerce_value('0.0'))->toBe(0.0);
});

it('returns non-numeric strings as-is', function () {
    expect(Settings::coerce_value('hello'))->toBe('hello');
    expect(Settings::coerce_value(''))->toBe('');
});

// ─── Constructor ────────────────────────────────────────────────────

it('throws when slug is empty', function () {
    new Settings([]);
})->throws(\InvalidArgumentException::class);

it('derives option_name from slug', function () {
    $settings = new Settings(['slug' => 'test']);

    expect($settings->get_option_name())->toBe('test');
});

it('uses provided option_name', function () {
    $settings = new Settings(['slug' => 'test', 'option_name' => 'my_settings']);

    expect($settings->get_option_name())->toBe('my_settings');
});

// ─── get_default_settings() ─────────────────────────────────────────

it('returns all defaults when no module specified', function () {
    $defaults = [
        'cache' => ['enabled' => true, 'ttl' => 3600],
        'debug' => ['verbose' => false],
    ];

    $settings = new Settings(['slug' => 'test', 'defaults' => $defaults]);

    expect($settings->get_default_settings())->toBe(
        $defaults + ['host' => ['domain' => '']]
    );
});

it('returns defaults filtered by module', function () {
    $defaults = [
        'cache' => ['enabled' => true, 'ttl' => 3600],
        'debug' => ['verbose' => false],
    ];

    $settings = new Settings(['slug' => 'test', 'defaults' => $defaults]);

    expect($settings->get_default_settings('cache'))->toBe([
        'cache' => ['enabled' => true, 'ttl' => 3600],
    ]);
});

it('returns empty array for non-existent module', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['enabled' => true]]]);

    expect($settings->get_default_settings('nonexistent'))->toBe([]);
});

// ─── get_settings_from_constants() ──────────────────────────────────

it('returns empty when constant_prefix is empty', function () {
    $settings = new Settings([
        'slug' => 'test',
        'defaults' => ['cache' => ['enabled' => true]],
        'constant_prefix' => '',
    ]);

    expect($settings->get_settings_from_constants())->toBe([]);
});

it('reads defined constants with prefix', function () {
    // Define a test constant.
    if (! defined('TEST_CACHE_TTL')) {
        define('TEST_CACHE_TTL', 7200);
    }

    $settings = new Settings([
        'slug' => 'test',
        'defaults' => ['cache' => ['ttl' => 3600, 'enabled' => true]],
        'constant_prefix' => 'test',
    ]);

    $result = $settings->get_settings_from_constants();

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

    $settings = new Settings([
        'slug' => 'test',
        'defaults' => ['storage' => ['enc_host' => '']],
        'constant_prefix' => 'test2',
    ]);

    $result = $settings->get_settings_from_constants();

    expect($result['storage']['enc_host'])->toBe('s3.example.com');
});

it('filters constants by module', function () {
    if (! defined('TEST3_CACHE_TTL')) {
        define('TEST3_CACHE_TTL', 1000);
    }
    if (! defined('TEST3_DEBUG_VERBOSE')) {
        define('TEST3_DEBUG_VERBOSE', true);
    }

    $settings = new Settings([
        'slug' => 'test',
        'defaults' => [
            'cache' => ['ttl' => 3600],
            'debug' => ['verbose' => false],
        ],
        'constant_prefix' => 'test3',
    ]);

    $result = $settings->get_settings_from_constants('cache');

    expect($result)->toHaveKey('cache');
    expect($result)->not->toHaveKey('debug');
});

// ─── filter_settings_by_constants() ─────────────────────────────────

it('merges defaults and removes obsolete keys and modules', function () {
    $settings = new Settings([
        'slug' => 'test',
        'defaults' => [
            'cache' => ['enabled' => true, 'ttl' => 3600],
        ],
        'constant_prefix' => '',
    ]);

    $data = [
        'cache' => ['enabled' => false, 'obsolete_key' => 'remove me'],
        'obsolete_module' => ['key' => 'value'],
    ];

    $result = $settings->filter_settings_by_constants($data);

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
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['enabled' => true]]]);

    expect($settings->filter_settings_by_constants(false))->toBe([]);
});
