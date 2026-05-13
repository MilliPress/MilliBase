<?php

use MilliBase\Abilities\FrameworkAbilities;


function make_settings_fake(array $stubs = []): object
{
    return new class ($stubs) {
        public array $calls = [];
        /** @var array<string, mixed> */
        private array $stubs;

        public function __construct(array $stubs)
        {
            $this->stubs = array_merge([
                'has_backup'     => false,
                'reset'          => true,
                'restore_backup' => true,
                'export'         => ['cache' => ['ttl' => 3600]],
                'is_network'     => false,
            ], $stubs);
        }

        public function is_network(): bool
        {
            return (bool) $this->stubs['is_network'];
        }

        public function backup(?string $module = null): void
        {
            $this->calls[] = ['method' => 'backup', 'module' => $module];
        }

        public function reset(?string $module = null): bool
        {
            $this->calls[] = ['method' => 'reset', 'module' => $module];
            return (bool) $this->stubs['reset'];
        }

        public function has_backup(): bool
        {
            $this->calls[] = ['method' => 'has_backup'];
            return (bool) $this->stubs['has_backup'];
        }

        public function restore_backup(): bool
        {
            $this->calls[] = ['method' => 'restore_backup'];
            return (bool) $this->stubs['restore_backup'];
        }

        public function export(?string $module = null, bool $include_encrypted = false): array
        {
            $this->calls[] = [
                'method'            => 'export',
                'module'            => $module,
                'include_encrypted' => $include_encrypted,
            ];
            return (array) $this->stubs['export'];
        }
    };
}

function ability_by_id(array $abilities, string $id): array
{
    foreach ($abilities as $entry) {
        if (($entry['id'] ?? null) === $id) {
            return $entry;
        }
    }
    throw new RuntimeException("Ability {$id} not found");
}


it('returns four entries in the documented order', function () {
    $abilities = FrameworkAbilities::settings(make_settings_fake());

    expect($abilities)->toHaveCount(4);
    expect($abilities[0]['id'])->toBe('settings-export');
    expect($abilities[1]['id'])->toBe('settings-reset');
    expect($abilities[2]['id'])->toBe('settings-backup');
    expect($abilities[3]['id'])->toBe('settings-restore');
});

it('prefixes ids with network- and names the scope explicitly when network-scoped', function () {
    $abilities = FrameworkAbilities::settings(make_settings_fake(['is_network' => true]));

    expect($abilities[0]['id'])->toBe('network-settings-export');
    expect($abilities[1]['id'])->toBe('network-settings-reset');
    expect($abilities[2]['id'])->toBe('network-settings-backup');
    expect($abilities[3]['id'])->toBe('network-settings-restore');

    foreach ($abilities as $entry) {
        expect(strtolower($entry['label']))->toContain('network');
        expect(strtolower($entry['description']))->toContain('network');
    }
});

it('gives every entry a non-empty label and description', function () {
    foreach (FrameworkAbilities::settings(make_settings_fake()) as $entry) {
        expect($entry['label'])->toBeString()->not->toBe('');
        expect($entry['description'])->toBeString()->not->toBe('');
    }
});

it('builds export with the documented schema and readonly annotation', function () {
    $entry = ability_by_id(FrameworkAbilities::settings(make_settings_fake()), 'settings-export');

    expect($entry['input_schema']['type'])->toBe('object');
    expect($entry['input_schema']['properties'])->toHaveKeys(['module', 'include_encrypted']);
    expect($entry['output_schema']['type'])->toBe('object');
    expect($entry['output_schema']['additionalProperties']['type'])->toBe('object');
    expect($entry['meta']['annotations']['readonly'])->toBeTrue();
});

it('builds reset with the documented schema and destructive annotation', function () {
    $entry = ability_by_id(FrameworkAbilities::settings(make_settings_fake()), 'settings-reset');

    expect($entry['input_schema']['type'])->toBe('object');
    expect($entry['input_schema']['properties'])->toHaveKey('module');
    expect($entry['output_schema']['properties']['success']['type'])->toBe('boolean');
    expect($entry['output_schema']['required'])->toContain('success');
    expect($entry['meta']['annotations']['destructive'])->toBeTrue();
});

it('builds backup with the documented schema and idempotent annotation', function () {
    $entry = ability_by_id(FrameworkAbilities::settings(make_settings_fake()), 'settings-backup');

    expect($entry['input_schema']['properties'])->toHaveKey('module');
    expect($entry['output_schema']['properties']['success']['type'])->toBe('boolean');
    expect($entry['meta']['annotations']['idempotent'])->toBeTrue();
});

it('builds restore with an empty-object input_schema and a destructive annotation', function () {
    $entry = ability_by_id(FrameworkAbilities::settings(make_settings_fake()), 'settings-restore');

    expect($entry['input_schema']['type'])->toBe('object');
    expect($entry['input_schema']['additionalProperties'])->toBeFalse();
    expect($entry['output_schema']['properties']['success']['type'])->toBe('boolean');
    expect($entry['meta']['annotations']['destructive'])->toBeTrue();
});

it('omits show_in_rest by default — plugins opt in per-ability', function () {
    foreach (FrameworkAbilities::settings(make_settings_fake()) as $entry) {
        $meta = $entry['meta'] ?? [];
        expect($meta)->not->toHaveKey('show_in_rest');
    }
});

it('forwards module and include_encrypted from input to Settings::export', function () {
    $fake     = make_settings_fake(['export' => ['ok' => true]]);
    $callback = ability_by_id(FrameworkAbilities::settings($fake), 'settings-export')['callback'];

    $result = $callback(['module' => 'cache', 'include_encrypted' => true]);

    expect($fake->calls)->toBe([
        ['method' => 'export', 'module' => 'cache', 'include_encrypted' => true],
    ]);
    expect($result)->toBe(['ok' => true]);
});

it('coerces stringified booleans correctly for include_encrypted', function () {
    $fake     = make_settings_fake();
    $callback = ability_by_id(FrameworkAbilities::settings($fake), 'settings-export')['callback'];

    $callback(['include_encrypted' => 'false']);
    $callback(['include_encrypted' => 'true']);
    $callback(['include_encrypted' => '0']);
    $callback(['include_encrypted' => '1']);

    expect($fake->calls)->toBe([
        ['method' => 'export', 'module' => null, 'include_encrypted' => false],
        ['method' => 'export', 'module' => null, 'include_encrypted' => true],
        ['method' => 'export', 'module' => null, 'include_encrypted' => false],
        ['method' => 'export', 'module' => null, 'include_encrypted' => true],
    ]);
});

it('treats non-array input to export as no input', function () {
    $fake     = make_settings_fake();
    $callback = ability_by_id(FrameworkAbilities::settings($fake), 'settings-export')['callback'];

    $callback(null);
    $callback('not-an-array');

    expect($fake->calls)->toBe([
        ['method' => 'export', 'module' => null, 'include_encrypted' => false],
        ['method' => 'export', 'module' => null, 'include_encrypted' => false],
    ]);
});

it('resets the requested module and reports the reset result', function () {
    $fake     = make_settings_fake(['reset' => true]);
    $callback = ability_by_id(FrameworkAbilities::settings($fake), 'settings-reset')['callback'];

    $result = $callback(['module' => 'cache']);

    expect($fake->calls[0])->toBe(['method' => 'reset', 'module' => 'cache']);
    expect($result)->toBe(['success' => true]);
});

it('reports backup success based on has_backup after the call', function () {
    $fake     = make_settings_fake(['has_backup' => true]);
    $callback = ability_by_id(FrameworkAbilities::settings($fake), 'settings-backup')['callback'];

    $result = $callback(['module' => 'cache']);

    expect($fake->calls[0])->toBe(['method' => 'backup', 'module' => 'cache']);
    expect($fake->calls[1])->toBe(['method' => 'has_backup']);
    expect($result)->toBe(['success' => true]);
});

it('reports backup failure when has_backup returns false', function () {
    $fake     = make_settings_fake(['has_backup' => false]);
    $callback = ability_by_id(FrameworkAbilities::settings($fake), 'settings-backup')['callback'];

    expect($callback(null))->toBe(['success' => false]);
});

it('returns the restore_backup result wrapped as success', function () {
    $fake     = make_settings_fake(['restore_backup' => false]);
    $callback = ability_by_id(FrameworkAbilities::settings($fake), 'settings-restore')['callback'];

    $result = $callback();

    expect($fake->calls[0])->toBe(['method' => 'restore_backup']);
    expect($result)->toBe(['success' => false]);
});
