<?php
/**
 * reconcile_overrides(): constant/config-file overrides apply like writes —
 * the standard change events fire once per drift, the row becomes the
 * applied-state memory, and the config file is never rewritten by the sync.
 */

use MilliBase\ConfigFile;
use MilliBase\Settings;

/** Occurrences of a hook in the fired-actions recorder. */
function rc_fired( string $hook ): int {
    return count(
        array_filter(
            $GLOBALS['__milli_test_actions_fired'] ?? [],
            static fn (string $fired): bool => $fired === $hook
        )
    );
}

it('fires the changed event once and syncs the row when a constant differs', function () {
    if (! defined('RC1_CACHE_ENABLED')) {
        define('RC1_CACHE_ENABLED', true);
    }

    $settings = new Settings([
        'slug' => 'rc1',
        'defaults' => ['cache' => ['enabled' => false]],
        'constant_prefix' => 'rc1',
    ]);

    $captured = [];
    add_action('rc1_setting_changed/cache.enabled', function ($new, $old, $key) use (&$captured) {
        $captured[] = [$new, $old, $key];
    }, 10, 3);

    $settings->reconcile_overrides();

    expect($captured)->toBe([[true, false, 'cache.enabled']]);
    expect(rc_fired('rc1_setting_changed'))->toBe(1);
    expect($GLOBALS['__milli_test_options']['rc1']['cache']['enabled'])->toBeTrue();

    // Settled: a second pass fires nothing.
    $settings->reconcile_overrides();
    expect(rc_fired('rc1_setting_changed/cache.enabled'))->toBe(1);
});

it('fires the deactivation direction when a constant switches a stored-on value off', function () {
    if (! defined('RC2_CACHE_ENABLED')) {
        define('RC2_CACHE_ENABLED', false);
    }

    $GLOBALS['__milli_test_options']['rc2'] = ['cache' => ['enabled' => true]];

    $settings = new Settings([
        'slug' => 'rc2',
        'defaults' => ['cache' => ['enabled' => false]],
        'constant_prefix' => 'rc2',
    ]);

    $captured = [];
    add_action('rc2_setting_changed/cache.enabled', function ($new, $old) use (&$captured) {
        $captured[] = [$new, $old];
    }, 10, 2);

    $settings->reconcile_overrides();

    expect($captured)->toBe([[false, true]]);
    expect($GLOBALS['__milli_test_options']['rc2']['cache']['enabled'])->toBeFalse();
});

it('does nothing when the constant matches the stored row', function () {
    if (! defined('RC3_CACHE_ENABLED')) {
        define('RC3_CACHE_ENABLED', true);
    }

    $GLOBALS['__milli_test_options']['rc3'] = ['cache' => ['enabled' => true]];

    $settings = new Settings([
        'slug' => 'rc3',
        'defaults' => ['cache' => ['enabled' => false]],
        'constant_prefix' => 'rc3',
    ]);

    $settings->reconcile_overrides();

    expect(rc_fired('rc3_setting_changed/cache.enabled'))->toBe(0);
});

it('does not create an option row when nothing is overridden', function () {
    $settings = new Settings([
        'slug' => 'rc4',
        'defaults' => ['cache' => ['enabled' => false]],
        'constant_prefix' => 'rc4',
    ]);

    $settings->reconcile_overrides();

    expect(array_key_exists('rc4', $GLOBALS['__milli_test_options']))->toBeFalse();
    expect(rc_fired('rc4_setting_changed'))->toBe(0);
});

it('applies a config-file drift and leaves the file itself untouched', function () {
    $_SERVER['HTTP_HOST'] = 'reconcile.test';

    $dir = sys_get_temp_dir() . '/millibase-rc-' . uniqid();
    mkdir($dir, 0755, true);

    // A "git-deployed" file: ttl differs from the default, no row exists.
    $file = new ConfigFile($dir, 'reconcile_test', 'rc5');
    $file->write(['cache' => ['ttl' => 100]]);

    $paths  = glob($dir . '/*');
    $before = file_get_contents($paths[0]);
    $mtime  = filemtime($paths[0]);

    $settings = new Settings([
        'slug' => 'rc5',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'config_file' => ['directory' => $dir],
    ]);

    $captured = [];
    add_action('rc5_setting_changed/cache.ttl', function ($new, $old) use (&$captured) {
        $captured[] = [$new, $old];
    }, 10, 2);

    $settings->reconcile_overrides();

    expect($captured)->toBe([[100, 3600]]);
    expect($GLOBALS['__milli_test_options']['rc5']['cache']['ttl'])->toBe(100);
    expect(file_get_contents($paths[0]))->toBe($before);
    expect(filemtime($paths[0]))->toBe($mtime);

    array_map('unlink', glob($dir . '/*'));
    rmdir($dir);
});

it('syncs an enc_ key supplied via the unprefixed constant name', function () {
    if (! defined('RC6_LICENSE_KEY')) {
        define('RC6_LICENSE_KEY', 'CONST-SECRET');
    }

    $settings = new Settings([
        'slug' => 'rc6',
        'defaults' => ['license' => ['enc_key' => '']],
        'constant_prefix' => 'rc6',
    ]);

    $settings->reconcile_overrides();

    expect(rc_fired('rc6_setting_changed/license.enc_key'))->toBe(1);
    expect($GLOBALS['__milli_test_options']['rc6']['license']['enc_key'])->toBe('CONST-SECRET');
});

it('keeps has_default_settings() true when the row only mirrors constants', function () {
    if (! defined('RC7_CACHE_ENABLED')) {
        define('RC7_CACHE_ENABLED', true);
    }

    $settings = new Settings([
        'slug' => 'rc7',
        'defaults' => ['cache' => ['enabled' => false, 'ttl' => 3600]],
        'constant_prefix' => 'rc7',
    ]);

    $settings->reconcile_overrides();
    expect($settings->has_default_settings())->toBeTrue();

    // A genuine user value still reads as customized.
    $settings->set('cache.ttl', 60);
    expect($settings->has_default_settings())->toBeFalse();
});
