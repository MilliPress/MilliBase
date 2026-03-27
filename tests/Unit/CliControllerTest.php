<?php

require_once __DIR__ . '/cli-stubs.php';

use MilliBase\CliController;
use MilliBase\Settings;

// ─── Helpers ────────────────────────────────────────────────────────

function make_cli(array $config = [], ?Settings $settings = null): CliController
{
    $settings = $settings ?? new Settings([
        'slug'     => 'testcli',
        'defaults' => [
            'cache'   => ['enabled' => true, 'ttl' => 3600],
            'storage' => ['host' => 'localhost', 'port' => 6379],
        ],
    ]);

    return new CliController(
        array_merge(['slug' => 'testcli'], $config),
        $settings,
    );
}

beforeEach(function () {
    WP_CLI::reset();
});

// ─── register_hooks() ──────────────────────────────────────────────

it('registers under the config sub-namespace', function () {
    $cli = make_cli();
    $cli->register_hooks();

    expect(WP_CLI::$calls['add_command'])->toHaveCount(1);
    expect(WP_CLI::$calls['add_command'][0][0])->toBe('testcli config');
    expect(WP_CLI::$calls['add_command'][0][1])->toBeInstanceOf(CliController::class);
});

it('uses custom slug for command name', function () {
    $cli = make_cli(['slug' => 'millicache']);
    $cli->register_hooks();

    expect(WP_CLI::$calls['add_command'][0][0])->toBe('millicache config');
});

// ─── get: single dot-key ────────────────────────────────────────────

it('outputs a single scalar value as raw text', function () {
    $cli = make_cli();
    $cli->get(['cache.ttl'], []);

    expect(WP_CLI::$calls['line'][0][0])->toBe('3600');
});

it('outputs a boolean value as raw text', function () {
    $cli = make_cli();
    $cli->get(['cache.enabled'], []);

    expect(WP_CLI::$calls['line'][0][0])->toBe('true');
});

it('outputs a single value via print_value with --format=json', function () {
    $cli = make_cli();
    $cli->get(['cache.ttl'], ['format' => 'json']);

    expect(WP_CLI::$calls['print_value'][0][0])->toBe(3600);
});

// ─── get: module name ───────────────────────────────────────────────

it('outputs module settings as table when given a module name', function () {
    $cli = make_cli();
    $cli->get(['cache'], []);

    expect(WP_CLI::$calls)->toHaveKey('format_items');
    $rows = WP_CLI::$calls['format_items'][0][1];

    $keys = array_column($rows, 'key');
    expect($keys)->toContain('cache.enabled');
    expect($keys)->toContain('cache.ttl');
});

it('shows only key and value columns by default', function () {
    $cli = make_cli();
    $cli->get(['cache'], []);

    $columns = WP_CLI::$calls['format_items'][0][2];
    expect($columns)->toBe(['key', 'value']);
});

it('includes source column with --show-source', function () {
    $cli = make_cli();
    $cli->get(['cache'], ['show-source' => '']);

    $columns = WP_CLI::$calls['format_items'][0][2];
    expect($columns)->toBe(['key', 'value', 'source']);

    $rows = WP_CLI::$calls['format_items'][0][1];
    expect($rows[0])->toHaveKey('source');
});

it('outputs raw nested JSON for module with --format=json', function () {
    $cli = make_cli();
    $cli->get(['cache'], ['format' => 'json']);

    // Should output raw JSON via WP_CLI::line, not format_items.
    expect(WP_CLI::$calls)->toHaveKey('line');
    $json = json_decode(WP_CLI::$calls['line'][0][0], true);
    expect($json)->toHaveKey('cache');
    expect($json['cache'])->toHaveKeys(['enabled', 'ttl']);
});

// ─── get: all settings ──────────────────────────────────────────────

it('outputs all settings as table when no key given', function () {
    $cli = make_cli();
    $cli->get([], []);

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
    $cli = make_cli();
    $cli->get([], ['format' => 'json']);

    expect(WP_CLI::$calls)->toHaveKey('line');
    $json = json_decode(WP_CLI::$calls['line'][0][0], true);
    expect($json)->toHaveKey('cache');
    expect($json)->toHaveKey('storage');
});

it('shows source column for all settings with --show-source', function () {
    $cli = make_cli();
    $cli->get([], ['show-source' => '']);

    $columns = WP_CLI::$calls['format_items'][0][2];
    expect($columns)->toContain('source');

    $rows = WP_CLI::$calls['format_items'][0][1];
    $ttl_row = array_values(array_filter($rows, fn ($r) => $r['key'] === 'cache.ttl'))[0];
    expect($ttl_row['source'])->toBe('default');
});

// ─── get: error cases ───────────────────────────────────────────────

it('errors on non-existent key', function () {
    $cli = make_cli();

    expect(fn () => $cli->get(['nonexistent.key'], []))
        ->toThrow(RuntimeException::class, 'not found');
});

it('errors on non-existent module', function () {
    $cli = make_cli();

    expect(fn () => $cli->get(['nonexistent'], []))
        ->toThrow(RuntimeException::class, 'not found');
});

// ─── set ────────────────────────────────────────────────────────────

it('sets a value and reports success', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['cache' => ['ttl' => 3600]],
    ]);
    $cli = make_cli([], $settings);
    $cli->set(['cache.ttl', '7200'], []);

    expect(WP_CLI::$calls['success'][0][0])->toContain('7200');
});

it('coerces boolean string values on set', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['cache' => ['enabled' => true]],
    ]);
    $cli = make_cli([], $settings);
    $cli->set(['cache.enabled', 'false'], []);

    expect(WP_CLI::$calls['success'][0][0])->toContain('false');
});

it('errors on invalid key format in set', function () {
    $cli = make_cli();

    expect(fn () => $cli->set(['nomodule', 'value'], []))
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
    $cli = make_cli([], $settings);

    expect(fn () => $cli->set(['cache.ttl', '5000'], []))
        ->toThrow(RuntimeException::class, 'constant');
});

it('masks encrypted field values in set output', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['storage' => ['enc_password' => '']],
    ]);
    $cli = make_cli([], $settings);
    $cli->set(['storage.enc_password', 'mysecret'], []);

    expect(WP_CLI::$calls['success'][0][0])->toContain('***');
    expect(WP_CLI::$calls['success'][0][0])->not->toContain('mysecret');
});

// ─── reset ──────────────────────────────────────────────────────────

it('resets settings and reports success', function () {
    $cli = make_cli();
    $cli->reset([], ['yes' => true]);

    expect(WP_CLI::$calls)->toHaveKey('confirm');
    expect(WP_CLI::$calls['success'][0][0])->toContain('all settings');
    expect(WP_CLI::$calls['success'][0][0])->toContain('backup');
});

it('resets a specific module', function () {
    $cli = make_cli();
    $cli->reset([], ['module' => 'cache', 'yes' => true]);

    expect(WP_CLI::$calls['confirm'][0][0])->toContain("module 'cache'");
    expect(WP_CLI::$calls['success'][0][0])->toContain("module 'cache'");
});

// ─── backup ─────────────────────────────────────────────────────────

it('creates a backup', function () {
    $cli = make_cli();
    $cli->backup([], []);

    expect(WP_CLI::$calls['success'][0][0])->toContain('Backup created');
    expect(WP_CLI::$calls['success'][0][0])->toContain('12 hours');
});

// ─── restore ────────────────────────────────────────────────────────

it('errors when no backup exists', function () {
    $cli = make_cli();

    expect(fn () => $cli->restore([], []))
        ->toThrow(RuntimeException::class, 'No backup');
});

// ─── export ─────────────────────────────────────────────────────────

it('exports settings as JSON to stdout', function () {
    $cli = make_cli();
    $cli->export([], []);

    expect(WP_CLI::$calls)->toHaveKey('line');
    $json = json_decode(WP_CLI::$calls['line'][0][0], true);
    expect($json)->toBeArray();
    expect($json)->toHaveKey('cache');
});

it('exports settings to a file with --file', function () {
    $tmpfile = tempnam(sys_get_temp_dir(), 'millibase_export_');

    $cli = make_cli();

    try {
        $cli->export([], ['file' => $tmpfile]);

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
    $cli = make_cli();

    expect(fn () => $cli->import([], []))
        ->toThrow(RuntimeException::class, 'Usage');
});

it('errors on non-existent import file', function () {
    $cli = make_cli();

    expect(fn () => $cli->import([], ['file' => '/nonexistent/file.json']))
        ->toThrow(RuntimeException::class, 'not found');
});

it('errors on invalid JSON', function () {
    $tmpfile = tempnam(sys_get_temp_dir(), 'millibase_test_');
    file_put_contents($tmpfile, 'not json');

    $cli = make_cli();

    try {
        expect(fn () => $cli->import([], ['file' => $tmpfile]))
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

    $cli = make_cli();

    try {
        $cli->import([], ['file' => $tmpfile]);

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

    $cli = make_cli();

    try {
        $cli->import([], ['file' => $tmpfile, 'merge' => false]);

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

    $cli = make_cli();

    try {
        expect(fn () => $cli->import([], ['file' => $tmpfile]))
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
    $cli = make_cli([], $settings);
    $cli->get([], []);

    $rows = WP_CLI::$calls['format_items'][0][1];
    $null_row = array_values(array_filter($rows, fn ($r) => $r['key'] === 'test.key'))[0];
    expect($null_row['value'])->toBe('null');
});

it('formats boolean values as "true"/"false" strings', function () {
    $cli = make_cli();
    $cli->get([], []);

    $rows = WP_CLI::$calls['format_items'][0][1];
    $bool_row = array_values(array_filter($rows, fn ($r) => $r['key'] === 'cache.enabled'))[0];
    expect($bool_row['value'])->toBe('true');
});

it('formats arrays as JSON strings', function () {
    $settings = new Settings([
        'slug'     => 'testcli',
        'defaults' => ['test' => ['list' => ['a', 'b']]],
    ]);
    $cli = make_cli([], $settings);
    $cli->get([], []);

    $rows = WP_CLI::$calls['format_items'][0][1];
    $arr_row = array_values(array_filter($rows, fn ($r) => $r['key'] === 'test.list'))[0];
    expect($arr_row['value'])->toBe('["a","b"]');
});
