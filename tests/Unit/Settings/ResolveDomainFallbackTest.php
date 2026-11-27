<?php
/**
 * Config-file identifier without a request host: a plain PHP CLI process
 * (cron, scheduler, Artisan) has no $_SERVER['HTTP_HOST']. The WP_HOME
 * constant from wp-config.php names the install's own home and must take
 * over, while a present request host keeps precedence.
 */

use MilliBase\Settings;

if (! defined('WP_HOME')) {
    define('WP_HOME', 'https://www.fallback-home.test/');
}

function rdf_settings(string $slug, string $dir): Settings {
    return new Settings([
        'slug' => $slug,
        'defaults' => ['cache' => ['ttl' => 3600]],
        'config_file' => ['directory' => $dir],
    ]);
}

beforeEach(function () {
    $this->savedHost = $_SERVER['HTTP_HOST'] ?? null;
    $this->dir = sys_get_temp_dir() . '/millibase-rdf-' . uniqid();
    mkdir($this->dir, 0755, true);
});

afterEach(function () {
    if (null === $this->savedHost) {
        unset($_SERVER['HTTP_HOST']);
    } else {
        $_SERVER['HTTP_HOST'] = $this->savedHost;
    }
    $GLOBALS['__milli_test_is_multisite'] = false;

    // scandir() also lists the dot-named `.php` an empty identifier produces.
    foreach (array_diff(scandir($this->dir) ?: [], ['.', '..']) as $file) {
        unlink($this->dir . '/' . $file);
    }
    rmdir($this->dir);
});

it('names the config file after WP_HOME when no request host exists', function () {
    unset($_SERVER['HTTP_HOST']);

    rdf_settings('rdf1', $this->dir)->set('cache.ttl', 60);

    expect(file_exists($this->dir . '/www_fallback-home_test.php'))->toBeTrue();
    expect(file_exists($this->dir . '/.php'))->toBeFalse();
});

it('keeps the request host ahead of WP_HOME', function () {
    $_SERVER['HTTP_HOST'] = 'request-host.test';

    rdf_settings('rdf2', $this->dir)->set('cache.ttl', 60);

    expect(file_exists($this->dir . '/request-host_test.php'))->toBeTrue();
    expect(file_exists($this->dir . '/www_fallback-home_test.php'))->toBeFalse();
});

it('treats an empty request host like a missing one', function () {
    $_SERVER['HTTP_HOST'] = '';

    rdf_settings('rdf3', $this->dir)->set('cache.ttl', 60);

    expect(file_exists($this->dir . '/www_fallback-home_test.php'))->toBeTrue();
    expect(file_exists($this->dir . '/.php'))->toBeFalse();
});

it('ignores WP_HOME on multisite, where WordPress ignores it too', function () {
    unset($_SERVER['HTTP_HOST']);
    $GLOBALS['__milli_test_is_multisite'] = true;

    rdf_settings('rdf4', $this->dir)->set('cache.ttl', 60);

    expect(file_exists($this->dir . '/www_fallback-home_test.php'))->toBeFalse();
    expect(file_exists($this->dir . '/.php'))->toBeTrue();
});
