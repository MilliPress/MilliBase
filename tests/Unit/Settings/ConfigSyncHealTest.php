<?php
/**
 * Config-file sync failure handling: a failed write sets a marker, the
 * marker disarms reconcile_overrides() (a stale file must not regress the
 * row), and the next reconcile heals the file from the row and clears it.
 */

use MilliBase\ConfigFile;
use MilliBase\Settings;

function csh_dir(): string {
    $dir = sys_get_temp_dir() . '/millibase-csh-' . uniqid();
    mkdir($dir, 0755, true);
    return $dir;
}

function csh_cleanup(string $dir): void {
    @chmod($dir, 0755);
    array_map('unlink', glob($dir . '/*') ?: []);
    if (is_dir($dir)) {
        rmdir($dir);
    }
}

/** Run a callback with fs warnings muted — the failure IS the fixture. */
function csh_quiet(callable $op): void {
    set_error_handler(static fn (): bool => true, E_WARNING);
    try {
        $op();
    } finally {
        restore_error_handler();
    }
}

it('marks the failure when the config file cannot be written, and clears it on success', function () {
    $_SERVER['HTTP_HOST'] = 'reconcile.test';
    $dir = csh_dir();

    $settings = new Settings([
        'slug' => 'csh1',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'config_file' => ['directory' => $dir],
    ]);

    chmod($dir, 0500);
    csh_quiet(fn () => $settings->set('cache.ttl', 60));

    expect($settings->config_sync_failed_at())->toBeInt();

    chmod($dir, 0755);
    $settings->set('cache.ttl', 90);

    expect($settings->config_sync_failed_at())->toBeNull();

    csh_cleanup($dir);
});

it('disarms reconcile while marked: a stale file cannot regress the row', function () {
    $_SERVER['HTTP_HOST'] = 'reconcile.test';
    $dir = csh_dir();

    // Stale file claims ttl 100; the row (written after the file went stale)
    // says 60 — the exact state a failed sync leaves behind.
    ( new ConfigFile($dir, 'reconcile_test', 'csh2') )->write(['cache' => ['ttl' => 100]]);
    $GLOBALS['__milli_test_options']['csh2']                    = ['cache' => ['ttl' => 60]];
    $GLOBALS['__milli_test_options']['csh2_config_sync_failed'] = time();

    chmod($dir, 0500); // Heal must fail too — the guard has to hold.

    $settings = new Settings([
        'slug' => 'csh2',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'config_file' => ['directory' => $dir],
    ]);

    csh_quiet(fn () => $settings->reconcile_overrides());

    expect($GLOBALS['__milli_test_options']['csh2']['cache']['ttl'])->toBe(60);
    expect($GLOBALS['__milli_test_actions_fired'] ?? [])->not->toContain('csh2_setting_changed/cache.ttl');
    expect($settings->config_sync_failed_at())->toBeInt();

    csh_cleanup($dir);
});

it('heals the file from the row on reconcile and clears the marker', function () {
    $_SERVER['HTTP_HOST'] = 'reconcile.test';
    $dir = csh_dir();

    ( new ConfigFile($dir, 'reconcile_test', 'csh3') )->write(['cache' => ['ttl' => 100]]);
    $GLOBALS['__milli_test_options']['csh3']                    = ['cache' => ['ttl' => 60]];
    $GLOBALS['__milli_test_options']['csh3_config_sync_failed'] = time();

    $settings = new Settings([
        'slug' => 'csh3',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'config_file' => ['directory' => $dir],
    ]);

    $settings->reconcile_overrides();

    expect($settings->config_sync_failed_at())->toBeNull();
    expect(( new ConfigFile($dir, 'reconcile_test', 'csh3') )->read())->toBe(['cache' => ['ttl' => 60]]);
    expect($GLOBALS['__milli_test_options']['csh3']['cache']['ttl'])->toBe(60);

    csh_cleanup($dir);
});

it('heals by writing the row as stored, not a decrypted copy', function () {
    // Keys for the decrypt path the post-heal resolve() hits; the garbage
    // cipher then throws SodiumException and stays encrypted, as in prod.
    if (! defined('AUTH_KEY')) {
        define('AUTH_KEY', 'test-auth-key');
    }
    if (! defined('SECURE_AUTH_KEY')) {
        define('SECURE_AUTH_KEY', 'test-secure-auth-key');
    }

    $_SERVER['HTTP_HOST'] = 'reconcile.test';
    $dir = csh_dir();

    ( new ConfigFile($dir, 'reconcile_test', 'csh4') )->write(['cache' => ['ttl' => 100]]);
    $GLOBALS['__milli_test_options']['csh4']                    = ['license' => ['enc_key' => 'ENC:cipher-at-rest']];
    $GLOBALS['__milli_test_options']['csh4_config_sync_failed'] = time();

    $settings = new Settings([
        'slug' => 'csh4',
        'defaults' => ['license' => ['enc_key' => '']],
        'encryption' => true,
        'config_file' => ['directory' => $dir],
    ]);

    $settings->reconcile_overrides();

    $paths = glob($dir . '/*');
    expect(file_get_contents($paths[0]))->toContain('ENC:cipher-at-rest');

    csh_cleanup($dir);
});

it('heals a failed delete by removing the surviving file', function () {
    $_SERVER['HTTP_HOST'] = 'reconcile.test';
    $dir = csh_dir();

    // Row already gone (reset), but the file survived the delete.
    ( new ConfigFile($dir, 'reconcile_test', 'csh5') )->write(['cache' => ['ttl' => 100]]);
    $GLOBALS['__milli_test_options']['csh5_config_sync_failed'] = time();

    $settings = new Settings([
        'slug' => 'csh5',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'config_file' => ['directory' => $dir],
    ]);

    $settings->reconcile_overrides();

    expect(glob($dir . '/*'))->toBe([]);
    expect($settings->config_sync_failed_at())->toBeNull();

    csh_cleanup($dir);
});

it('marks the failure when the option delete leaves the file behind', function () {
    $_SERVER['HTTP_HOST'] = 'reconcile.test';
    $dir = csh_dir();

    $settings = new Settings([
        'slug' => 'csh6',
        'defaults' => ['cache' => ['ttl' => 3600]],
        'config_file' => ['directory' => $dir],
    ]);

    $settings->set('cache.ttl', 60);
    expect($settings->config_sync_failed_at())->toBeNull();

    chmod($dir, 0500); // File can no longer be unlinked.
    csh_quiet(fn () => delete_option('csh6'));

    expect($settings->config_sync_failed_at())->toBeInt();

    csh_cleanup($dir);
});
