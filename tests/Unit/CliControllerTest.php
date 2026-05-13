<?php

require_once __DIR__ . '/cli-stubs.php';

use MilliBase\CLI;
use MilliBase\CLI\Backup;
use MilliBase\CLI\Command;
use MilliBase\CLI\Export;
use MilliBase\CLI\Get;
use MilliBase\CLI\Import;
use MilliBase\CLI\Reset;
use MilliBase\CLI\Restore;
use MilliBase\CLI\Set;
use MilliBase\Settings;
use MilliBase\Settings\Group;

function default_test_settings(): Settings
{
    return new Settings([
        'slug'     => 'testcli',
        'defaults' => [
            'cache'   => ['enabled' => true, 'ttl' => 3600],
            'storage' => ['host' => 'localhost', 'port' => 6379],
        ],
    ]);
}

function make_cli(array $config = [], ?Settings $settings = null): CLI
{
    return new CLI(
        array_merge(['slug' => 'testcli'], $config),
        new Group($settings ?? default_test_settings()),
    );
}

/**
 * @template T of Command
 * @param class-string<T> $class
 * @return T
 */
function make_command(string $class, array $config = [], ?Settings $settings = null): Command
{
    return new $class(
        array_merge(['slug' => 'testcli'], $config),
        new Group($settings ?? default_test_settings()),
    );
}

beforeEach(function () {
    WP_CLI::reset();
});

it('registers each subcommand under <slug> config', function () {
    make_cli()->register_hooks();

    $names = array_column(WP_CLI::$calls['add_command'], 0);
    expect($names)->toBe([
        'testcli config get',
        'testcli config set',
        'testcli config reset',
        'testcli config backup',
        'testcli config restore',
        'testcli config export',
        'testcli config import',
    ]);

    // Each registration's second arg is the per-command class instance.
    expect(WP_CLI::$calls['add_command'][0][1])->toBeInstanceOf(Get::class);
    expect(WP_CLI::$calls['add_command'][1][1])->toBeInstanceOf(Set::class);
});

it('uses custom slug for command name', function () {
    make_cli(['slug' => 'millicache'])->register_hooks();

    $names = array_column(WP_CLI::$calls['add_command'], 0);
    expect($names[0])->toBe('millicache config get');
});

// ─── get: single dot-key ────────────────────────────────────────────

it('outputs a single scalar value as raw text', function () {
    $get = make_command(Get::class);
    $get(['cache.ttl'], []);

    expect(WP_CLI::$calls['line'][0][0])->toBe('3600');
});

it('outputs a boolean value as raw text', function () {
    $get = make_command(Get::class);
    $get(['cache.enabled'], []);

    expect(WP_CLI::$calls['line'][0][0])->toBe('true');
});

it('outputs a single value via print_value with --format=json', function () {
    $get = make_command(Get::class);
    $get(['cache.ttl'], ['format' => 'json']);

    expect(WP_CLI::$calls['print_value'][0][0])->toBe(3600);
});

// ─── get: module name ───────────────────────────────────────────────

it('outputs module settings as table when given a module name', function () {
    $get = make_command(Get::class);
    $get(['cache'], []);

    expect(WP_CLI::$calls)->toHaveKey('format_items');
    $rows = WP_CLI::$calls['format_items'][0][1];

    $keys = array_column($rows, 'key');
    expect($keys)->toContain('cache.enabled');
    expect($keys)->toContain('cache.ttl');
});

it('shows only key and value columns by default', function () {
    $get = make_command(Get::class);
    $get(['cache'], []);

    $columns = WP_CLI::$calls['format_items'][0][2];
    expect($columns)->toBe(['key', 'value']);
});

it('includes source column with --show-source', function () {
    $get = make_command(Get::class);
    $get(['cache'], ['show-source' => '']);

    $columns = WP_CLI::$calls['format_items'][0][2];
    expect($columns)->toBe(['key', 'value', 'source']);

    $rows = WP_CLI::$calls['format_items'][0][1];
    expect($rows[0])->toHaveKey('source');
});

it('outputs raw nested JSON for module with --format=json', function () {
    $get = make_command(Get::class);
    $get(['cache'], ['format' => 'json']);

    expect(WP_CLI::$calls)->toHaveKey('line');
    $json = json_decode(WP_CLI::$calls['line'][0][0], true);
    expect($json)->toHaveKey('cache');
    expect($json['cache'])->toHaveKeys(['enabled', 'ttl']);
});

// ─── get: all settings ──────────────────────────────────────────────

it('outputs all settings as table when no key given', function () {
    $get = make_command(Get::class);
    $get([], []);

    expect(WP_CLI::$calls)->toHaveKey('format_items');
    [$format, $rows, $columns] = WP_CLI::$calls['format_items'][0];

    expect($format)->toBe('table');
    expect($columns)->toBe(['key', 'value']);
    expect(count($rows))->toBeGreaterThanOrEqual(4);

    foreach ($rows as $row) {
        expect($row['key'])->toContain('.');
    }
});

it('outputs raw nested JSON for all settings with --format=json', function () {
    $get = make_command(Get::class);
    $get([], ['format' => 'json']);

    expect(WP_CLI::$calls)->toHaveKey('line');
    $json = json_decode(WP_CLI::$calls['line'][0][0], true);
    expect($json)->toHaveKey('cache');
    expect($json)->toHaveKey('storage');
});

it('shows source column for all settings with --show-source', function () {
    $get = make_command(Get::class);
    $get([], ['show-source' => '']);

    $columns = WP_CLI::$calls['format_items'][0][2];
    expect($columns)->toContain('source');

    $rows = WP_CLI::$calls['format_items'][0][1];
    $ttl_row = array_values(array_filter($rows, fn ($r) => $r['key'] === 'cache.ttl'))[0];
    expect($ttl_row['source'])->toBe('default');
});

// ─── get: error cases ───────────────────────────────────────────────

it('errors on non-existent key', function () {
    $get = make_command(Get::class);

    expect(fn () => $get(['nonexistent.key'], []))
        ->toThrow(RuntimeException::class, 'not found');
});

it('errors on non-existent module', function () {
    $get = make_command(Get::class);

    expect(fn () => $get(['nonexistent'], []))
        ->toThrow(RuntimeException::class, 'not found');
});

// ─── set ────────────────────────────────────────────────────────────

it('sets a value and reports success', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['cache' => ['ttl' => 3600]],
    ]);
    $set = make_command(Set::class, [], $settings);
    $set(['cache.ttl', '7200'], []);

    expect(WP_CLI::$calls['success'][0][0])->toContain('7200');
});

it('coerces boolean string values on set', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['cache' => ['enabled' => true]],
    ]);
    $set = make_command(Set::class, [], $settings);
    $set(['cache.enabled', 'false'], []);

    expect(WP_CLI::$calls['success'][0][0])->toContain('false');
});

it('errors on invalid key format in set', function () {
    $set = make_command(Set::class);

    expect(fn () => $set(['nomodule', 'value'], []))
        ->toThrow(RuntimeException::class, 'dot notation');
});

it('errors when setting is overridden by constant', function () {
    if (! defined('CLITEST_CACHE_TTL')) {
        define('CLITEST_CACHE_TTL', 9999);
    }

    $settings = new Settings([
        'slug'            => 'testcli',
        'constant_prefix' => 'CLITEST',
        'defaults'        => ['cache' => ['ttl' => 3600]],
    ]);
    $set = make_command(Set::class, [], $settings);

    expect(fn () => $set(['cache.ttl', '5000'], []))
        ->toThrow(RuntimeException::class, 'constant');
});

it('masks encrypted field values in set output', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['storage' => ['enc_password' => '']],
    ]);
    $set = make_command(Set::class, [], $settings);
    $set(['storage.enc_password', 'mysecret'], []);

    expect(WP_CLI::$calls['success'][0][0])->toContain('***');
    expect(WP_CLI::$calls['success'][0][0])->not->toContain('mysecret');
});

// ─── reset ──────────────────────────────────────────────────────────

it('resets settings and reports success', function () {
    $reset = make_command(Reset::class);
    $reset([], ['yes' => true]);

    expect(WP_CLI::$calls)->toHaveKey('confirm');
    expect(WP_CLI::$calls['success'][0][0])->toContain('all settings');
    expect(WP_CLI::$calls['success'][0][0])->toContain('backup');
});

it('resets a specific module', function () {
    $reset = make_command(Reset::class);
    $reset([], ['module' => 'cache', 'yes' => true]);

    expect(WP_CLI::$calls['confirm'][0][0])->toContain("module 'cache'");
    expect(WP_CLI::$calls['success'][0][0])->toContain("module 'cache'");
});

// ─── backup ─────────────────────────────────────────────────────────

it('creates a backup', function () {
    $backup = make_command(Backup::class);
    $backup([], []);

    expect(WP_CLI::$calls['success'][0][0])->toContain('Backup created');
    expect(WP_CLI::$calls['success'][0][0])->toContain('3 days');
});

// ─── restore ────────────────────────────────────────────────────────

it('errors when no backup exists', function () {
    $restore = make_command(Restore::class);

    expect(fn () => $restore([], []))
        ->toThrow(RuntimeException::class, 'No backup');
});

// ─── export ─────────────────────────────────────────────────────────

it('exports settings as JSON to stdout', function () {
    $export = make_command(Export::class);
    $export([], []);

    expect(WP_CLI::$calls)->toHaveKey('line');
    $json = json_decode(WP_CLI::$calls['line'][0][0], true);
    expect($json)->toBeArray();
    expect($json)->toHaveKey('cache');
});

it('exports settings to a file with --file', function () {
    $tmpfile = tempnam(sys_get_temp_dir(), 'millibase_export_');

    $export = make_command(Export::class);

    try {
        $export([], ['file' => $tmpfile]);

        expect(WP_CLI::$calls['success'][0][0])->toContain($tmpfile);
        expect(file_exists($tmpfile))->toBeTrue();

        $data = json_decode((string) file_get_contents($tmpfile), true);
        expect($data)->toBeArray();
        expect($data)->toHaveKey('cache');
    } finally {
        if (file_exists($tmpfile)) {
            unlink($tmpfile);
        }
    }
});

// ─── import ─────────────────────────────────────────────────────────

it('errors when no --file is given', function () {
    $import = make_command(Import::class);

    expect(fn () => $import([], []))
        ->toThrow(RuntimeException::class, 'Usage');
});

it('errors on non-existent import file', function () {
    $import = make_command(Import::class);

    expect(fn () => $import([], ['file' => '/nonexistent/file.json']))
        ->toThrow(RuntimeException::class, 'not found');
});

it('errors on invalid JSON', function () {
    $tmpfile = tempnam(sys_get_temp_dir(), 'millibase_test_');
    file_put_contents($tmpfile, 'not json');

    $import = make_command(Import::class);

    try {
        expect(fn () => $import([], ['file' => $tmpfile]))
            ->toThrow(RuntimeException::class, 'Invalid JSON');
    } finally {
        unlink($tmpfile);
    }
});

it('imports valid JSON file with auto-backup', function () {
    $tmpfile = tempnam(sys_get_temp_dir(), 'millibase_test_');
    file_put_contents($tmpfile, json_encode([
        'cache' => ['ttl' => 9999, 'enabled' => false],
    ]));

    $import = make_command(Import::class);

    try {
        $import([], ['file' => $tmpfile]);

        expect(WP_CLI::$calls['success'][0][0])->toContain('1 module(s)');
        expect(WP_CLI::$calls['success'][0][0])->toContain('merged');
    } finally {
        unlink($tmpfile);
    }
});

it('asks for confirmation on non-merge import', function () {
    $tmpfile = tempnam(sys_get_temp_dir(), 'millibase_test_');
    file_put_contents($tmpfile, json_encode([
        'cache' => ['ttl' => 1234],
    ]));

    $import = make_command(Import::class);

    try {
        $import([], ['file' => $tmpfile, 'merge' => false]);

        expect(WP_CLI::$calls)->toHaveKey('confirm');
        expect(WP_CLI::$calls['confirm'][0][0])->toContain('replace');
    } finally {
        unlink($tmpfile);
    }
});

it('rejects import with no valid modules', function () {
    $tmpfile = tempnam(sys_get_temp_dir(), 'millibase_test_');
    file_put_contents($tmpfile, json_encode([
        'unknown_module' => ['key' => 'value'],
    ]));

    $import = make_command(Import::class);

    try {
        expect(fn () => $import([], ['file' => $tmpfile]))
            ->toThrow(RuntimeException::class, 'No valid modules');
    } finally {
        unlink($tmpfile);
    }
});

// ─── stringify (tested indirectly via get output) ───────────────────

it('formats null values as "null" string', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['test' => ['key' => null]],
    ]);
    $get = make_command(Get::class, [], $settings);
    $get([], []);

    $rows = WP_CLI::$calls['format_items'][0][1];
    $null_row = array_values(array_filter($rows, fn ($r) => $r['key'] === 'test.key'))[0];
    expect($null_row['value'])->toBe('null');
});

it('formats boolean values as "true"/"false" strings', function () {
    $get = make_command(Get::class);
    $get([], []);

    $rows = WP_CLI::$calls['format_items'][0][1];
    $bool_row = array_values(array_filter($rows, fn ($r) => $r['key'] === 'cache.enabled'))[0];
    expect($bool_row['value'])->toBe('true');
});

it('formats arrays as JSON strings', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['test' => ['list' => ['a', 'b']]],
    ]);
    $get = make_command(Get::class, [], $settings);
    $get([], []);

    $rows = WP_CLI::$calls['format_items'][0][1];
    $arr_row = array_values(array_filter($rows, fn ($r) => $r['key'] === 'test.list'))[0];
    expect($arr_row['value'])->toBe('["a","b"]');
});

// ─── --network flag ─────────────────────────────────────────────────

it('routes to the network Settings when --network is passed', function () {
    $site    = new Settings([
        'slug'     => 'testcli',
        'network'  => false,
        'defaults' => ['cache' => ['ttl' => 3600]],
    ]);
    $network = new Settings([
        'slug'     => 'testcli',
        'network'  => true,
        'option_name' => 'testcli_network',
        'defaults' => ['cache' => ['ttl' => 9999]],
    ]);
    $group = new Group($site);
    $group->add($network);

    $get = new Get(['slug' => 'testcli'], $group);
    $get(['cache.ttl'], ['network' => true]);

    expect(WP_CLI::$calls['line'][0][0])->toBe('9999');
});

it('routes to the per-site Settings when --network is omitted', function () {
    $site    = new Settings([
        'slug'     => 'testcli',
        'network'  => false,
        'defaults' => ['cache' => ['ttl' => 3600]],
    ]);
    $network = new Settings([
        'slug'     => 'testcli',
        'network'  => true,
        'option_name' => 'testcli_network',
        'defaults' => ['cache' => ['ttl' => 9999]],
    ]);
    $group = new Group($site);
    $group->add($network);

    $get = new Get(['slug' => 'testcli'], $group);
    $get(['cache.ttl'], []);

    expect(WP_CLI::$calls['line'][0][0])->toBe('3600');
});

it('errors when --network is passed but no network Settings is registered', function () {
    $site  = new Settings(['slug' => 'testcli', 'network' => false, 'defaults' => []]);
    $group = new Group($site);

    $get = new Get(['slug' => 'testcli'], $group);
    expect(fn () => $get([], ['network' => true]))
        ->toThrow(RuntimeException::class, 'No network Settings');
});

it('falls back to network Settings when only network is registered and no flag is passed', function () {
    $network = new Settings([
        'slug'     => 'testcli',
        'network'  => true,
        'defaults' => ['cache' => ['ttl' => 60]],
    ]);
    $group = new Group($network);

    $get = new Get(['slug' => 'testcli'], $group);
    $get(['cache.ttl'], []);

    expect(WP_CLI::$calls['line'][0][0])->toBe('60');
});
