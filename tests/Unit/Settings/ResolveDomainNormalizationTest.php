<?php
/**
 * The config-file identifier must not depend on where the host came from:
 * a request host may carry a port, upper case or a trailing dot, and a
 * Unicode site address reaches the server as Punycode, while the admin
 * writes the file from home_url(). All of them have to name one file.
 *
 * WP_HOME is stubbed as https://www.Fallback-Home.test/ in tests/bootstrap.php,
 * with the admin's capitalisation on purpose.
 */

use MilliBase\Settings;

function rdn_settings(string $slug, string $dir): Settings {
    return new Settings([
        'slug' => $slug,
        'defaults' => ['cache' => ['ttl' => 3600]],
        'config_file' => ['directory' => $dir],
    ]);
}

function rdn_files(string $dir): array {
    return array_values(array_diff(scandir($dir) ?: [], ['.', '..']));
}

beforeEach(function () {
    $this->savedHost = $_SERVER['HTTP_HOST'] ?? null;
    $this->dir = sys_get_temp_dir() . '/millibase-rdn-' . uniqid();
    mkdir($this->dir, 0755, true);
});

afterEach(function () {
    if (null === $this->savedHost) {
        unset($_SERVER['HTTP_HOST']);
    } else {
        $_SERVER['HTTP_HOST'] = $this->savedHost;
    }
    foreach (rdn_files($this->dir) as $file) {
        unlink($this->dir . '/' . $file);
    }
    rmdir($this->dir);
});

it('drops the port, like home_url() parsing does', function () {
    $_SERVER['HTTP_HOST'] = 'localhost:8080';

    rdn_settings('rdn1', $this->dir)->set('cache.ttl', 60);

    expect(rdn_files($this->dir))->toBe(['localhost.php']);
});

it('lower-cases the host and drops a trailing dot', function () {
    $_SERVER['HTTP_HOST'] = 'Example.COM.';

    rdn_settings('rdn2', $this->dir)->set('cache.ttl', 60);

    expect(rdn_files($this->dir))->toBe(['example_com.php']);
});

it('strips the port from an IPv6 literal without touching the address', function () {
    $_SERVER['HTTP_HOST'] = '[::1]:8080';

    rdn_settings('rdn3', $this->dir)->set('cache.ttl', 60);

    expect(rdn_files($this->dir))->toBe(['___1_.php']);
});

it('names a Unicode host by its Punycode form, as browsers send it', function () {
    $_SERVER['HTTP_HOST'] = 'MÜNCHEN.de';

    rdn_settings('rdn4', $this->dir)->set('cache.ttl', 60);

    expect(rdn_files($this->dir))->toBe(['xn--mnchen-3ya_de.php']);
})->skip(! function_exists('idn_to_ascii'), 'intl extension not loaded');

it('encodes ß non-transitionally, as browsers do, on every ICU version', function () {
    $_SERVER['HTTP_HOST'] = 'faß.de';

    rdn_settings('rdn5', $this->dir)->set('cache.ttl', 60);

    expect(rdn_files($this->dir))->toBe(['xn--fa-hia_de.php']);
})->skip(! function_exists('idn_to_ascii'), 'intl extension not loaded');

it('normalizes the WP_HOME host too', function () {
    unset($_SERVER['HTTP_HOST']);

    rdn_settings('rdn6', $this->dir)->set('cache.ttl', 60);

    expect(rdn_files($this->dir))->toBe(['www_fallback-home_test.php']);
});
