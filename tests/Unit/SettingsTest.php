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

it('normalizes hyphenated module keys to underscore constant names', function () {
    if (! defined('TEST7_OBJECT_CACHE_ACTIVE')) {
        define('TEST7_OBJECT_CACHE_ACTIVE', true);
    }

    $settings = new Settings([
        'slug' => 'test',
        'defaults' => ['object-cache' => ['active' => false]],
        'constant_prefix' => 'test7',
    ]);

    $result = $settings->get_settings_from_constants();

    expect($result['object-cache']['active'])->toBeTrue();
});

it('ignores the hyphenated constant spelling (underscore form only)', function () {
    if (! defined('TEST8_OBJECT-CACHE_ACTIVE')) {
        define('TEST8_OBJECT-CACHE_ACTIVE', true);
    }

    $settings = new Settings([
        'slug' => 'test',
        'defaults' => ['object-cache' => ['active' => false]],
        'constant_prefix' => 'test8',
    ]);

    expect($settings->get_settings_from_constants())->toBe([]);
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

// ─── get_raw() ──────────────────────────────────────────────────────

it('reads a DB key absent from defaults while get() returns the fallback', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    $GLOBALS['__milli_test_options']['test'] = ['license' => ['enc_key' => 'ABC-123']];

    expect($settings->get_raw('license.enc_key'))->toBe('ABC-123');
    expect($settings->get('license.enc_key', ''))->toBe('');
});

it('defers to get() for a standalone instance with no stored row', function () {
    $settings = Settings::standalone(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    expect($settings->get_raw('cache.ttl'))->toBe(3600);
});

it('returns a present non-string value as-is', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    $GLOBALS['__milli_test_options']['test'] = ['mod' => ['key' => ['a', 'b']]];

    expect($settings->get_raw('mod.key'))->toBe(['a', 'b']);
});

it('reads the network store via get_site_option', function () {
    $settings = new Settings(['slug' => 'test', 'network' => true, 'defaults' => ['cache' => ['ttl' => 3600]]]);

    $GLOBALS['__milli_test_site_options']['test'] = ['license' => ['enc_key' => 'NET-KEY']];
    $GLOBALS['__milli_test_options']['test']      = ['license' => ['enc_key' => 'SITE-KEY']];

    expect($settings->get_raw('license.enc_key'))->toBe('NET-KEY');
});

it('returns the fallback when the key resolves nowhere', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    expect($settings->get_raw('nope.missing', 'FB'))->toBe('FB');
});

it('resolves a constant-only key before its defaults are registered', function () {
    if (! defined('TEST9_LICENSE_KEY')) {
        define('TEST9_LICENSE_KEY', 'CONST-KEY');
    }

    // No defaults and no row — the pre-init state of a constant-only key.
    $settings = new Settings([
        'slug' => 'test',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'constant_prefix' => 'test9',
    ]);

    expect($settings->get_raw('license.enc_key', ''))->toBe('CONST-KEY');
    expect($settings->get('license.enc_key', ''))->toBe('');
});

it('lets a constant outrank the stored row in get_raw()', function () {
    if (! defined('TEST10_LICENSE_KEY')) {
        define('TEST10_LICENSE_KEY', 'CONST-WINS');
    }

    $settings = new Settings([
        'slug' => 'test',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'constant_prefix' => 'test10',
    ]);

    $GLOBALS['__milli_test_options']['test'] = ['license' => ['enc_key' => 'ROW-KEY']];

    expect($settings->get_raw('license.enc_key', ''))->toBe('CONST-WINS');
});

it('resolves a hyphenated module via get_raw() with the underscore constant', function () {
    if (! defined('TEST11_OBJECT_CACHE_ACTIVE')) {
        define('TEST11_OBJECT_CACHE_ACTIVE', true);
    }

    $settings = new Settings([
        'slug' => 'test',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'constant_prefix' => 'test11',
    ]);

    expect($settings->get_raw('object-cache.active', false))->toBeTrue();
});

// ─── reset() with preserved keys ────────────────────────────────────

it('deletes the option on full reset when no keys are preserved', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    $GLOBALS['__milli_test_options']['test'] = ['cache' => ['ttl' => 99]];

    $settings->reset();

    expect(array_key_exists('test', $GLOBALS['__milli_test_options']))->toBeFalse();
});

it('re-stores a preserved value as a minimal option, dropping everything else', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'defaults'       => ['license' => ['enc_key' => ''], 'cache' => ['ttl' => 3600]],
        'preserved_keys' => ['license.enc_key'],
    ]);

    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'LIVE-KEY'],
        'cache'   => ['ttl' => 99],
    ];

    $settings->reset();

    // Only the preserved key survives; the cache customization is gone.
    expect($GLOBALS['__milli_test_options']['test'])->toBe(['license' => ['enc_key' => 'LIVE-KEY']]);
});

it('skips a preserved value that is empty', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'defaults'       => ['license' => ['enc_key' => '']],
        'preserved_keys' => ['license.enc_key'],
    ]);

    $GLOBALS['__milli_test_options']['test'] = ['license' => ['enc_key' => '']];

    $settings->reset();

    // Nothing worth preserving → option stays deleted.
    expect(array_key_exists('test', $GLOBALS['__milli_test_options']))->toBeFalse();
});

it('skips a preserved value already equal to its default', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'defaults'       => ['retention' => ['days' => 30]],
        'preserved_keys' => ['retention.days'],
    ]);

    $GLOBALS['__milli_test_options']['test'] = ['retention' => ['days' => 30]];

    $settings->reset();

    expect(array_key_exists('test', $GLOBALS['__milli_test_options']))->toBeFalse();
});

it('preserves a non-default value for the same key', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'defaults'       => ['retention' => ['days' => 30]],
        'preserved_keys' => ['retention.days'],
    ]);

    $GLOBALS['__milli_test_options']['test'] = ['retention' => ['days' => 90]];

    $settings->reset();

    expect($GLOBALS['__milli_test_options']['test'])->toBe(['retention' => ['days' => 90]]);
});

it('re-stores a preserved value into the network store on a network reset', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'network'        => true,
        'defaults'       => ['license' => ['enc_key' => '']],
        'preserved_keys' => ['license.enc_key'],
    ]);

    $GLOBALS['__milli_test_site_options']['test'] = ['license' => ['enc_key' => 'NET-KEY']];

    $settings->reset();

    expect($GLOBALS['__milli_test_site_options']['test'])->toBe(['license' => ['enc_key' => 'NET-KEY']]);
});

it('leaves preserved keys untouched on a per-module reset', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'defaults'       => ['cache' => ['ttl' => 3600], 'license' => ['enc_key' => '']],
        'preserved_keys' => ['license.enc_key'],
    ]);

    $GLOBALS['__milli_test_options']['test'] = [
        'cache'   => ['ttl' => 99],
        'license' => ['enc_key' => 'LIVE-KEY'],
    ];

    $settings->reset('cache');

    // Module reset isolates the module; the option is not deleted and the
    // license is carried through by the ordinary module-reset path.
    expect($GLOBALS['__milli_test_options']['test']['cache'])->toBe(['ttl' => 3600]);
    expect($GLOBALS['__milli_test_options']['test']['license'])->toBe(['enc_key' => 'LIVE-KEY']);
});

// ─── has_default_settings() ignores preserved keys ──────────────────

it('reports defaults when only a preserved key differs from its default', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'defaults'       => ['license' => ['enc_key' => ''], 'cache' => ['ttl' => 3600]],
        'preserved_keys' => ['license.enc_key'],
    ]);

    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'LIVE-KEY'],
        'cache'   => ['ttl' => 3600],
    ];

    expect($settings->has_default_settings())->toBeTrue();
});

it('reports non-defaults when a non-preserved key differs', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'defaults'       => ['license' => ['enc_key' => ''], 'cache' => ['ttl' => 3600]],
        'preserved_keys' => ['license.enc_key'],
    ]);

    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'LIVE-KEY'],
        'cache'   => ['ttl' => 999],
    ];

    expect($settings->has_default_settings())->toBeFalse();
});

// ─── reset() suppresses phantom change hooks for the write-back ──────

it('does not fire setting-changed hooks for the preservation write-back', function () {
    $settings = new Settings([
        'slug'           => 'test',
        'defaults'       => ['license' => ['enc_key' => '']],
        'preserved_keys' => ['license.enc_key'],
    ]);

    $GLOBALS['__milli_test_options']['test'] = ['license' => ['enc_key' => 'LIVE-KEY']];

    $settings->reset();

    $changed = array_filter(
        $GLOBALS['__milli_test_actions_fired'],
        fn ($hook) => strpos($hook, 'test_setting_changed') === 0
    );

    expect($changed)->toBe([]);
});

it('fires setting-changed hooks on an ordinary option write (recorder sanity check)', function () {
    // Proves the suppression test above is not vacuous: the same hook path
    // fires normally when not suppressed.
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    $settings->on_add_option('test', ['cache' => ['ttl' => 5]]);

    expect($GLOBALS['__milli_test_actions_fired'])->toContain('test_setting_changed');
});

// ─── set() treats an unchanged value as success ───────────────────────

it('returns true when setting a key to its current stored value', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    $GLOBALS['__milli_test_options']['test'] = ['cache' => ['ttl' => 99]];

    expect($settings->set('cache.ttl', 99))->toBeTrue();
    expect($GLOBALS['__milli_test_options']['test']['cache']['ttl'])->toBe(99);
});

it('returns true and persists when setting a key to a new value', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    $GLOBALS['__milli_test_options']['test'] = ['cache' => ['ttl' => 99]];

    expect($settings->set('cache.ttl', 100))->toBeTrue();
    expect($GLOBALS['__milli_test_options']['test']['cache']['ttl'])->toBe(100);
});

it('returns false for a key without dot notation', function () {
    $settings = new Settings(['slug' => 'test', 'defaults' => ['cache' => ['ttl' => 3600]]]);

    expect($settings->set('cache', 99))->toBeFalse();
});

// ─── import() ───────────────────────────────────────────────────────

it('never lets a masked secret overwrite the stored one', function () {
    // A `mask` export reads enc_ fields back as bullets. Importing that
    // verbatim would destroy the real password with no way back.
    $settings = new Settings([
        'slug'     => 'test',
        'defaults' => ['storage' => ['enc_password' => '', 'host' => '']],
    ]);

    $GLOBALS['__milli_test_options']['test'] = [
        'storage' => ['enc_password' => 'ENC:real-secret', 'host' => 'old.example.com'],
    ];

    $settings->import([
        'storage' => [
            'enc_password' => str_repeat('•', 20),
            'host'         => 'new.example.com',
        ],
    ]);

    $stored = $GLOBALS['__milli_test_options']['test'];

    expect($stored['storage']['enc_password'])->toBe('ENC:real-secret');
    expect($stored['storage']['host'])->toBe('new.example.com');
});

it('backs up the current settings before importing over them', function () {
    $settings = new Settings([
        'slug'     => 'test',
        'defaults' => ['cache' => ['ttl' => 3600]],
    ]);

    $GLOBALS['__milli_test_options']['test'] = ['cache' => ['ttl' => 60]];

    $settings->import(['cache' => ['ttl' => 999]]);

    expect($GLOBALS['__milli_test_options']['test']['cache']['ttl'])->toBe(999);
    expect($GLOBALS['__milli_test_transients']['test_backup']['cache']['ttl'])->toBe(60);
});

it('does not back up when the payload holds no known module', function () {
    $settings = new Settings([
        'slug'     => 'test',
        'defaults' => ['cache' => ['ttl' => 3600]],
    ]);

    expect($settings->import(['nonsense' => ['a' => 1]]))->toBeFalse();
    expect($GLOBALS['__milli_test_transients']['test_backup'] ?? null)->toBeNull();
});
