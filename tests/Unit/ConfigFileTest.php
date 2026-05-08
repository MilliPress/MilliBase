<?php

use MilliBase\ConfigFile;

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir() . '/millibase-test-' . uniqid();
    mkdir($this->tmpDir, 0755, true);
    $this->domain = 'example_com';
    $this->configFile = new ConfigFile(
        $this->tmpDir,
        fn (): string => $this->domain,
        'millibase'
    );
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

// ─── Dynamic domain resolution (multisite / switch_to_blog) ─────────

it('resolves the domain on every operation', function () {
    // Write under the construction-time domain.
    $this->configFile->write(['cache' => ['ttl' => 1]]);

    // Simulate switch_to_blog by mutating the resolver's source.
    $this->domain = 'sub_example_com';
    $this->configFile->write(['cache' => ['ttl' => 2]]);

    expect(file_exists($this->tmpDir . '/example_com.php'))->toBeTrue();
    expect(file_exists($this->tmpDir . '/sub_example_com.php'))->toBeTrue();

    // Read currently resolves to the second domain.
    expect($this->configFile->read('cache'))->toBe(['cache' => ['ttl' => 2]]);

    // Restore — read tracks back to the first.
    $this->domain = 'example_com';
    expect($this->configFile->read('cache'))->toBe(['cache' => ['ttl' => 1]]);
});

it('deletes the file at the currently-resolved domain', function () {
    $this->configFile->write(['cache' => ['ttl' => 1]]);
    $this->domain = 'sub_example_com';
    $this->configFile->write(['cache' => ['ttl' => 2]]);

    // Delete should target the *current* domain only.
    expect($this->configFile->delete())->toBeTrue();
    expect(file_exists($this->tmpDir . '/sub_example_com.php'))->toBeFalse();
    expect(file_exists($this->tmpDir . '/example_com.php'))->toBeTrue();
});

// ─── Back-compat: string domain (pre-2.4.3 signature) ───────────────

it('accepts a string domain for backward compatibility', function () {
    $configFile = new ConfigFile($this->tmpDir, 'legacy_example_com', 'millibase');
    $configFile->write(['cache' => ['ttl' => 42]]);

    expect(file_exists($this->tmpDir . '/legacy_example_com.php'))->toBeTrue();
    expect($configFile->read('cache'))->toBe(['cache' => ['ttl' => 42]]);
});

it('throws when the domain argument is neither string nor Closure', function () {
    new ConfigFile($this->tmpDir, 123, 'millibase');
})->throws(\InvalidArgumentException::class);
