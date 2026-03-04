<?php

use MilliBase\ConfigFile;

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir() . '/millibase-test-' . uniqid();
    mkdir($this->tmpDir, 0755, true);
    $this->configFile = new ConfigFile($this->tmpDir, 'example_com', 'millibase');
});

afterEach(function () {
    // Clean up all files in the temp directory.
    $files = glob($this->tmpDir . '/*');
    if ($files) {
        array_map('unlink', $files);
    }
    if (is_dir($this->tmpDir)) {
        rmdir($this->tmpDir);
    }
});

// ─── write() + read() ───────────────────────────────────────────────

it('roundtrips settings through write and read', function () {
    $settings = [
        'cache' => ['enabled' => true, 'ttl' => 3600],
        'debug' => ['verbose' => false],
    ];

    $this->configFile->write($settings);
    $result = $this->configFile->read();

    expect($result)->toBe($settings);
});

// ─── read() with nonexistent file ───────────────────────────────────

it('returns empty array when file does not exist', function () {
    expect($this->configFile->read())->toBe([]);
});

// ─── read() with module filter ──────────────────────────────────────

it('filters by module on read', function () {
    $settings = [
        'cache' => ['enabled' => true],
        'debug' => ['verbose' => false],
    ];

    $this->configFile->write($settings);

    expect($this->configFile->read('cache'))->toBe([
        'cache' => ['enabled' => true],
    ]);

    expect($this->configFile->read('nonexistent'))->toBe([]);
});

// ─── delete() ───────────────────────────────────────────────────────

it('deletes the config file', function () {
    $this->configFile->write(['cache' => ['enabled' => true]]);

    expect($this->configFile->delete())->toBeTrue();
    expect($this->configFile->read())->toBe([]);
});

it('returns false when deleting nonexistent file', function () {
    expect($this->configFile->delete())->toBeFalse();
});

// ─── File content ───────────────────────────────────────────────────

it('includes ABSPATH guard in written file', function () {
    $this->configFile->write(['cache' => ['enabled' => true]]);

    $files = glob($this->tmpDir . '/*.php');
    $content = file_get_contents($files[0]);

    expect($content)->toContain("defined( 'ABSPATH' ) || exit;");
});
