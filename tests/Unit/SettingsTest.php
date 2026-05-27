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

    expect($settings->get_default_settings())->toBe($defaults);
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

it('passes false through unchanged to preserve "option does not exist" signal', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['enabled' => true]]]);

    // `update_network_option` relies on strict `=== false` to route to add_network_option;
    // collapsing false to [] would mis-route writes for non-existent network options.
    expect($settings->filter_settings_by_constants(false))->toBe(false);
});

it('returns empty array for non-array, non-false input', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['enabled' => true]]]);

    expect($settings->filter_settings_by_constants('garbage'))->toBe([]);
    expect($settings->filter_settings_by_constants(null))->toBe([]);
});

// ─── merge_defaults() ──────────────────────────────────────────────

it('merges additional defaults without overwriting existing keys', function () {
    $settings = new Settings([
        'slug'     => 'test',
        'defaults' => [
            'cache' => ['ttl' => 3600],
        ],
    ]);

    $settings->merge_defaults([
        'cache'  => ['ttl' => 9999, 'enabled' => false],
        'minify' => ['enabled' => true],
    ]);

    $defaults = $settings->get_default_settings();

    // Existing key preserved.
    expect($defaults['cache']['ttl'])->toBe(3600);
    // New key added to existing module.
    expect($defaults['cache']['enabled'])->toBeFalse();
    // New module added.
    expect($defaults['minify']['enabled'])->toBeTrue();
});

it('makes merged defaults visible to filter_settings_by_constants', function () {
    $settings = new Settings([
        'slug'     => 'test',
        'defaults' => [
            'cache' => ['ttl' => 3600],
        ],
    ]);

    // Before merge: minify module is "obsolete" and gets stripped.
    $before = $settings->filter_settings_by_constants([
        'cache'  => ['ttl' => 1800],
        'minify' => ['enabled' => true],
    ]);
    expect($before)->not->toHaveKey('minify');

    // Merge schema-derived defaults.
    $settings->merge_defaults([
        'minify' => ['enabled' => false],
    ]);

    // After merge: minify is recognised and preserved.
    $after = $settings->filter_settings_by_constants([
        'cache'  => ['ttl' => 1800],
        'minify' => ['enabled' => true],
    ]);
    expect($after)->toHaveKey('minify');
    expect($after['minify']['enabled'])->toBeTrue();
});

// ─── flatten_diff() ─────────────────────────────────────────────────

it('detects value changes', function () {
    $old = ['cache' => ['ttl' => 3600, 'enabled' => true]];
    $new = ['cache' => ['ttl' => 7200, 'enabled' => true]];

    $result = Settings::flatten_diff($old, $new);

    expect($result)->toBe([
        'cache.ttl' => ['old' => 3600, 'new' => 7200],
    ]);
});

it('detects additions with old as null', function () {
    $old = ['cache' => ['ttl' => 3600]];
    $new = ['cache' => ['ttl' => 3600, 'enabled' => true]];

    $result = Settings::flatten_diff($old, $new);

    expect($result)->toBe([
        'cache.enabled' => ['old' => null, 'new' => true],
    ]);
});

it('detects removals with new as null', function () {
    $old = ['cache' => ['ttl' => 3600, 'enabled' => true]];
    $new = ['cache' => ['ttl' => 3600]];

    $result = Settings::flatten_diff($old, $new);

    expect($result)->toBe([
        'cache.enabled' => ['old' => true, 'new' => null],
    ]);
});

it('recurses into nested arrays', function () {
    $old = ['warming' => ['schedule' => ['interval' => 60, 'enabled' => false]]];
    $new = ['warming' => ['schedule' => ['interval' => 60, 'enabled' => true]]];

    $result = Settings::flatten_diff($old, $new);

    expect($result)->toBe([
        'warming.schedule.enabled' => ['old' => false, 'new' => true],
    ]);
});

it('returns empty array when identical', function () {
    $data = ['cache' => ['ttl' => 3600, 'enabled' => true]];

    expect(Settings::flatten_diff($data, $data))->toBe([]);
});

it('handles mixed types: array to scalar', function () {
    $old = ['cache' => ['options' => ['a' => 1, 'b' => 2]]];
    $new = ['cache' => ['options' => 'none']];

    $result = Settings::flatten_diff($old, $new);

    expect($result)->toBe([
        'cache.options' => ['old' => ['a' => 1, 'b' => 2], 'new' => 'none'],
    ]);
});

it('handles mixed types: scalar to array', function () {
    $old = ['cache' => ['options' => 'none']];
    $new = ['cache' => ['options' => ['a' => 1]]];

    $result = Settings::flatten_diff($old, $new);

    expect($result)->toBe([
        'cache.options' => ['old' => 'none', 'new' => ['a' => 1]],
    ]);
});

it('diffs against empty array for first-time creation', function () {
    $new = ['cache' => ['ttl' => 3600], 'debug' => ['verbose' => false]];

    $result = Settings::flatten_diff([], $new);

    expect($result)->toBe([
        'cache.ttl'     => ['old' => null, 'new' => 3600],
        'debug.verbose' => ['old' => null, 'new' => false],
    ]);
});

it('uses strict comparison for type differences', function () {
    $old = ['cache' => ['ttl' => 0]];
    $new = ['cache' => ['ttl' => false]];

    $result = Settings::flatten_diff($old, $new);

    expect($result)->toBe([
        'cache.ttl' => ['old' => 0, 'new' => false],
    ]);
});

// ─── merge_defaults() ──────────────────────────────────────────────

it('invalidates resolved cache after merge_defaults', function () {
    $settings = new Settings([
        'slug'     => 'test',
        'defaults' => [
            'cache' => ['ttl' => 3600],
        ],
    ]);

    // Prime the cache.
    $before = $settings->get_default_settings();
    expect($before)->not->toHaveKey('minify');

    // Merge and verify cache was invalidated.
    $settings->merge_defaults(['minify' => ['enabled' => true]]);
    $after = $settings->get_default_settings();
    expect($after['minify']['enabled'])->toBeTrue();
});

it('is_network() returns false by default', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => []]);
    expect($settings->is_network())->toBeFalse();
});

it('is_network() returns true when constructed with network => true', function () {
    $settings = new Settings(['slug' => 'test', 'network' => true, 'defaults' => []]);
    expect($settings->is_network())->toBeTrue();
});

it('fires {slug}_settings_defaults with $is_network as the second argument', function () {
    $GLOBALS['__milli_test_filters'] = [];

    $captured = [];
    add_filter('test_settings_defaults', function ($defaults, $is_network) use (&$captured) {
        $captured[] = [$defaults, $is_network];
        return $defaults;
    });

    (new Settings(['slug' => 'test', 'defaults' => []]))->get_default_settings();
    (new Settings(['slug' => 'test', 'network' => true, 'defaults' => []]))->get_default_settings();

    expect($captured)->toHaveCount(2);
    expect($captured[0][1])->toBeFalse();
    expect($captured[1][1])->toBeTrue();
});

// ─── mask_for_field() / normalize_mask_config() ─────────────────────

// Public helpers consumers reach for when they need to surface a secret's
// partial mask outside the REST GET path (e.g. MilliPro showing the network
// key's mask inside a subsite admin UI).

it('mask_for_field returns the full SECRET_MASK when mask is "full"', function () {
    expect(Settings::mask_for_field('ABCDEFGHIJKLMNOPQRSTUVWXYZ', ['mask' => 'full']))
        ->toBe(str_repeat('•', 20));
});

it('mask_for_field defaults to first 4 / last 4, all-bullets middle, at input length', function () {
    expect(Settings::mask_for_field('ABCDEFGHIJKLMNOPQRSTUVWXYZ', []))
        ->toBe('ABCD' . str_repeat('•', 18) . 'WXYZ');
});

it('mask_for_field with mask "structured" preserves non-alphanumeric separators', function () {
    expect(Settings::mask_for_field('MILLI-AAAA-BBBB-CCCC-DDDD', ['mask' => 'structured']))
        ->toBe('MILL•-••••-••••-••••-DDDD');
});

it('mask_for_field honors per-field first/last + structured in array form', function () {
    expect(Settings::mask_for_field(
        'NETW-AAAA-BBBB-CCCC-DDDD',
        ['mask' => ['first' => 3, 'last' => 3, 'structured' => true]]
    ))->toBe('NET•-••••-••••-••••-•DDD');
});

it('mask_for_field returns empty string for empty input (caller chooses fallback)', function () {
    expect(Settings::mask_for_field('', ['mask' => 'structured']))->toBe('');
});

it('mask_for_field falls back to SECRET_MASK for values too short to safely reveal', function () {
    // first=4 + last=4 + minimum 4 hidden = 12-char minimum; "SHORT" is 5.
    expect(Settings::mask_for_field('SHORT', []))->toBe(str_repeat('•', 20));
});

it('normalize_mask_config returns null for "full" (signal: skip partial, use SECRET_MASK)', function () {
    expect(Settings::normalize_mask_config('full'))->toBeNull();
});

it('normalize_mask_config returns defaults for null / unknown input', function () {
    expect(Settings::normalize_mask_config(null))->toBe([
        'first'      => 4,
        'last'       => 4,
        'structured' => false,
    ]);
});

it('normalize_mask_config interprets "structured" as defaults + structured flag', function () {
    expect(Settings::normalize_mask_config('structured'))->toBe([
        'first'      => 4,
        'last'       => 4,
        'structured' => true,
    ]);
});

it('normalize_mask_config honors any subset of first/last/structured in array form', function () {
    expect(Settings::normalize_mask_config(['first' => 8]))->toBe([
        'first'      => 8,
        'last'       => 4,
        'structured' => false,
    ]);
});
