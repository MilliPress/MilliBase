<?php

use MilliBase\Schema;

// ─── get_defaults() ─────────────────────────────────────────────────

it('extracts defaults from nested tab/section/field config', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'general',
                'title' => 'General',
                'sections' => [
                    [
                        'id' => 'cache',
                        'title' => 'Cache',
                        'fields' => [
                            ['key' => 'cache.enabled', 'type' => 'toggle', 'default' => true],
                            ['key' => 'cache.ttl', 'type' => 'number', 'default' => 3600],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'advanced',
                'title' => 'Advanced',
                'sections' => [
                    [
                        'id' => 'debug',
                        'title' => 'Debug',
                        'fields' => [
                            ['key' => 'debug.verbose', 'type' => 'toggle', 'default' => false],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect($schema->get_defaults())->toBe([
        'cache' => ['enabled' => true, 'ttl' => 3600],
        'debug' => ['verbose' => false],
    ]);
});

it('handles missing keys, non-string keys, and single-segment keys', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'tab',
                'title' => 'Tab',
                'sections' => [
                    [
                        'id' => 'sec',
                        'title' => 'Section',
                        'fields' => [
                            ['type' => 'text', 'default' => 'no-key'],        // missing key
                            ['key' => 123, 'type' => 'text', 'default' => 'x'], // non-string key
                            ['key' => 'single', 'type' => 'text', 'default' => 'y'], // single segment
                            ['key' => 'mod.field', 'type' => 'text'],           // no default
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect($schema->get_defaults())->toBe([
        'mod' => ['field' => null],
    ]);
});

it('caches defaults after first call', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'tab',
                'title' => 'Tab',
                'sections' => [
                    [
                        'id' => 'sec',
                        'title' => 'Section',
                        'fields' => [
                            ['key' => 'a.b', 'type' => 'text', 'default' => 'val'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $first = $schema->get_defaults();
    $second = $schema->get_defaults();

    expect($first)->toBe($second);
});

// ─── get_rest_schema() ──────────────────────────────────────────────

it('generates correct JSON schema types', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'tab',
                'title' => 'Tab',
                'sections' => [
                    [
                        'id' => 'sec',
                        'title' => 'Section',
                        'fields' => [
                            ['key' => 'mod.str', 'type' => 'text', 'default' => 'hello'],
                            ['key' => 'mod.bool', 'type' => 'toggle', 'default' => true],
                            ['key' => 'mod.int', 'type' => 'number', 'default' => 42],
                            ['key' => 'mod.float', 'type' => 'number', 'default' => 3.14],
                            ['key' => 'mod.arr', 'type' => 'token-list', 'default' => ['a', 'b']],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $rest = $schema->get_rest_schema();

    expect($rest['type'])->toBe('object');
    expect($rest['properties']['mod']['properties']['str']['type'])->toBe('string');
    expect($rest['properties']['mod']['properties']['bool']['type'])->toBe('boolean');
    expect($rest['properties']['mod']['properties']['int']['type'])->toBe('integer');
    expect($rest['properties']['mod']['properties']['float']['type'])->toBe('number');
    expect($rest['properties']['mod']['properties']['arr']['type'])->toBe('array');
});

it('accepts custom defaults for rest schema generation', function () {
    $schema = new Schema(['tabs' => []]);

    $rest = $schema->get_rest_schema([
        'custom' => ['flag' => true, 'name' => 'test'],
    ]);

    expect($rest['properties']['custom']['properties']['flag']['type'])->toBe('boolean');
    expect($rest['properties']['custom']['properties']['name']['type'])->toBe('string');
});

// ─── to_client_array() ──────────────────────────────────────────────

it('strips server-only properties and preserves safe keys', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'general',
                'title' => 'General',
                'sections' => [
                    [
                        'id' => 'sec',
                        'title' => 'Section',
                        'fields' => [
                            [
                                'key' => 'mod.field',
                                'type' => 'text',
                                'label' => 'My Field',
                                'default' => 'val',
                                'tooltip' => 'Help text',
                                'sanitize' => 'some_callback', // server-only — should be stripped
                                'validate' => 'another_callback', // server-only
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $client = $schema->to_client_array();
    $field = $client['tabs'][0]['sections'][0]['fields'][0];

    expect($field)->toHaveKeys(['key', 'type', 'label', 'default', 'tooltip']);
    expect($field)->not->toHaveKeys(['sanitize', 'validate']);
});

it('handles custom tab types, intro text, and icons', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'custom',
                'title' => 'Custom Tab',
                'type' => 'custom',
                'component' => 'MyComponent',
                'intro' => 'Welcome text',
                'sections' => [
                    [
                        'id' => 'sec',
                        'title' => 'Section',
                        'icon' => 'settings',
                        'intro' => 'Section intro',
                        'fields' => [],
                    ],
                ],
            ],
        ],
    ]);

    $client = $schema->to_client_array();
    $tab = $client['tabs'][0];

    expect($tab['type'])->toBe('custom');
    expect($tab['component'])->toBe('MyComponent');
    expect($tab['intro'])->toBe('Welcome text');
    expect($tab['sections'][0]['icon'])->toBe('settings');
    expect($tab['sections'][0]['intro'])->toBe('Section intro');
});

it('passes status config through to the client and defaults open to error', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'general',
                'title' => 'General',
                'sections' => [
                    [
                        'id' => 'connection',
                        'title' => 'Connection',
                        'status' => [
                            'key'       => 'storage.connected',
                            'ok'        => true,
                            'indicator' => true,
                            'badge'     => ['ok' => 'Connected', 'error' => 'Disconnected'],
                        ],
                        'fields' => [],
                    ],
                    [
                        'id' => 'advanced',
                        'title' => 'Advanced',
                        'status' => [
                            'key'       => 'storage.connected',
                            'ok'        => true,
                            'indicator' => false,
                        ],
                        'open' => 'ok',
                        'fields' => [],
                    ],
                    [
                        'id' => 'plain',
                        'title' => 'Plain Section',
                        'fields' => [],
                    ],
                ],
            ],
        ],
    ]);

    $client = $schema->to_client_array();
    $sections = $client['tabs'][0]['sections'];

    // Section with status and no explicit open defaults to 'error'.
    expect($sections[0]['status']['key'])->toBe('storage.connected');
    expect($sections[0]['status']['badge']['error'])->toBe('Disconnected');
    expect($sections[0]['open'])->toBe('error');

    // Section with status and explicit open preserves the value.
    expect($sections[1]['open'])->toBe('ok');
    expect($sections[1]['status']['indicator'])->toBeFalse();

    // Section without status defaults open to true.
    expect($sections[2])->not->toHaveKey('status');
    expect($sections[2]['open'])->toBeTrue();
});

it('skips fields without key or type', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'tab',
                'title' => 'Tab',
                'sections' => [
                    [
                        'id' => 'sec',
                        'title' => 'Section',
                        'fields' => [
                            ['key' => 'mod.a', 'type' => 'text', 'label' => 'Valid'],
                            ['key' => 'mod.b', 'label' => 'No type'],           // missing type
                            ['type' => 'text', 'label' => 'No key'],             // missing key
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $client = $schema->to_client_array();
    $fields = $client['tabs'][0]['sections'][0]['fields'];

    expect($fields)->toHaveCount(1);
    expect($fields[0]['key'])->toBe('mod.a');
});

// ─── get_all_fields() ───────────────────────────────────────────────

it('flattens fields from all tabs and sections', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'tab1',
                'title' => 'Tab 1',
                'sections' => [
                    [
                        'id' => 'sec1',
                        'title' => 'Section 1',
                        'fields' => [
                            ['key' => 'a.one', 'type' => 'text'],
                            ['key' => 'a.two', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'tab2',
                'title' => 'Tab 2',
                'sections' => [
                    [
                        'id' => 'sec2',
                        'title' => 'Section 2',
                        'fields' => [
                            ['key' => 'b.three', 'type' => 'toggle'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $fields = $schema->get_all_fields();

    expect($fields)->toHaveCount(3);
    expect(array_column($fields, 'key'))->toBe(['a.one', 'a.two', 'b.three']);
});

it('handles tabs without sections gracefully', function () {
    $schema = new Schema([
        'tabs' => [
            ['name' => 'empty', 'title' => 'Empty Tab'],
            [
                'name' => 'tab',
                'title' => 'Tab',
                'sections' => [
                    ['id' => 'no-fields', 'title' => 'No Fields'],
                ],
            ],
        ],
    ]);

    expect($schema->get_all_fields())->toBe([]);
});

// ─── Tab / section override (keyed-by-name) ─────────────────────────

it('overrides a tab when a later entry uses the same name', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'general',
                'title' => 'General',
                'sections' => [
                    [
                        'id' => 'cache',
                        'title' => 'Cache',
                        'fields' => [
                            ['key' => 'cache.ttl', 'type' => 'number', 'default' => 3600],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'general',
                'title' => 'General Pro',
                'sections' => [
                    [
                        'id' => 'cache',
                        'title' => 'Cache Pro',
                        'fields' => [
                            ['key' => 'cache.ttl', 'type' => 'number', 'default' => 7200],
                            ['key' => 'cache.strategy', 'type' => 'text', 'default' => 'lru'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $client = $schema->to_client_array();

    // Only one tab should remain.
    expect($client['tabs'])->toHaveCount(1);
    expect($client['tabs'][0]['title'])->toBe('General Pro');
    expect($client['tabs'][0]['sections'][0]['title'])->toBe('Cache Pro');

    // Defaults reflect the overridden tab.
    expect($schema->get_defaults())->toBe([
        'cache' => ['ttl' => 7200, 'strategy' => 'lru'],
    ]);
});

it('overrides a single section within a tab when ids match', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'general',
                'title' => 'General',
                'sections' => [
                    [
                        'id' => 'cache',
                        'title' => 'Cache',
                        'fields' => [
                            ['key' => 'cache.ttl', 'type' => 'number', 'default' => 3600],
                        ],
                    ],
                    [
                        'id' => 'cache',
                        'title' => 'Cache Replaced',
                        'fields' => [
                            ['key' => 'cache.ttl', 'type' => 'number', 'default' => 900],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $client = $schema->to_client_array();

    // Only one section remains — the last one wins.
    expect($client['tabs'][0]['sections'])->toHaveCount(1);
    expect($client['tabs'][0]['sections'][0]['title'])->toBe('Cache Replaced');
});

it('adds new sections to an existing tab without removing others', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'general',
                'title' => 'General',
                'sections' => [
                    [
                        'id' => 'cache',
                        'title' => 'Cache',
                        'fields' => [
                            ['key' => 'cache.ttl', 'type' => 'number', 'default' => 3600],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'general',
                'title' => 'General Pro',
                'sections' => [
                    [
                        'id' => 'preload',
                        'title' => 'Preload',
                        'fields' => [
                            ['key' => 'preload.enabled', 'type' => 'toggle', 'default' => false],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $client = $schema->to_client_array();

    // One tab, but both sections are present.
    expect($client['tabs'])->toHaveCount(1);
    expect($client['tabs'][0]['title'])->toBe('General Pro');
    expect($client['tabs'][0]['sections'])->toHaveCount(2);
    expect($client['tabs'][0]['sections'][0]['id'])->toBe('cache');
    expect($client['tabs'][0]['sections'][1]['id'])->toBe('preload');

    // Defaults include fields from both sections.
    expect($schema->get_defaults())->toBe([
        'cache'   => ['ttl' => 3600],
        'preload' => ['enabled' => false],
    ]);
});

it('replaces a tab entirely when replace flag is set', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'status',
                'title' => 'Status',
                'type' => 'custom',
                'component' => 'StatusTab',
                'sections' => [
                    [
                        'id' => 'overview',
                        'title' => 'Overview',
                        'fields' => [
                            ['key' => 'status.hits', 'type' => 'number', 'default' => 0],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'status',
                'title' => 'Status Pro',
                'type' => 'custom',
                'component' => 'ProStatusTab',
                'replace' => true,
                'sections' => [],
            ],
        ],
    ]);

    $client = $schema->to_client_array();

    expect($client['tabs'])->toHaveCount(1);
    expect($client['tabs'][0]['title'])->toBe('Status Pro');
    expect($client['tabs'][0]['component'])->toBe('ProStatusTab');
    expect($client['tabs'][0]['sections'])->toHaveCount(0);

    // The replace flag itself should not leak to the client.
    expect($client['tabs'][0])->not->toHaveKey('replace');
});

// ─── Active-toggle support ───────────────────────────────────────────

it('normalizes string active shorthand into array with default false', function () {
    $schema = new Schema(['tabs' => []]);

    expect($schema->normalize_active('cache.enabled'))->toBe([
        'key'     => 'cache.enabled',
        'default' => false,
    ]);
});

it('normalizes array active config with custom default', function () {
    $schema = new Schema(['tabs' => []]);

    expect($schema->normalize_active([
        'key'     => 'minify.enabled',
        'default' => true,
    ]))->toBe([
        'key'     => 'minify.enabled',
        'default' => true,
    ]);
});

it('returns null for invalid active values', function () {
    $schema = new Schema(['tabs' => []]);

    expect($schema->normalize_active(null))->toBeNull();
    expect($schema->normalize_active(''))->toBeNull();
    expect($schema->normalize_active(42))->toBeNull();
    expect($schema->normalize_active([]))->toBeNull();
    expect($schema->normalize_active(['default' => true]))->toBeNull(); // missing key
});

it('includes active-toggle defaults in get_defaults()', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'modules',
                'title' => 'Modules',
                'sections' => [
                    [
                        'id'     => 'cache',
                        'title'  => 'Page Cache',
                        'active' => 'cache.enabled',
                        'fields' => [
                            ['key' => 'cache.ttl', 'type' => 'number', 'default' => 3600],
                        ],
                    ],
                    [
                        'id'     => 'minify',
                        'title'  => 'Minification',
                        'active' => ['key' => 'minify.enabled', 'default' => true],
                        'fields' => [],
                    ],
                ],
            ],
        ],
    ]);

    $defaults = $schema->get_defaults();

    // cache.enabled gets default false (string shorthand).
    expect($defaults['cache']['enabled'])->toBeFalse();
    // cache.ttl still comes from the field.
    expect($defaults['cache']['ttl'])->toBe(3600);
    // minify.enabled gets default true (array form).
    expect($defaults['minify']['enabled'])->toBeTrue();
});

it('gives field defaults precedence over active-toggle defaults for the same key', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'tab',
                'title' => 'Tab',
                'sections' => [
                    [
                        'id'     => 'sec',
                        'title'  => 'Section',
                        // Active would set cache.enabled = false...
                        'active' => 'cache.enabled',
                        'fields' => [
                            // ...but the field default of true takes precedence.
                            ['key' => 'cache.enabled', 'type' => 'toggle', 'default' => true],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect($schema->get_defaults()['cache']['enabled'])->toBeTrue();
});

it('passes active config to client array', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'modules',
                'title' => 'Modules',
                'sections' => [
                    [
                        'id'     => 'cache',
                        'title'  => 'Page Cache',
                        'active' => 'cache.enabled',
                        'fields' => [],
                    ],
                    [
                        'id'     => 'debug',
                        'title'  => 'Debug',
                        'fields' => [],
                    ],
                ],
            ],
        ],
    ]);

    $client   = $schema->to_client_array();
    $sections = $client['tabs'][0]['sections'];

    // Section with active: normalized config present.
    expect($sections[0]['active'])->toBe([
        'key'     => 'cache.enabled',
        'default' => false,
    ]);

    // Section without active: no active key.
    expect($sections[1])->not->toHaveKey('active');
});

it('includes active-toggle booleans in REST schema automatically', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'tab',
                'title' => 'Tab',
                'sections' => [
                    [
                        'id'     => 'cdn',
                        'title'  => 'CDN',
                        'active' => ['key' => 'cdn.active', 'default' => false],
                        'fields' => [],
                    ],
                ],
            ],
        ],
    ]);

    $rest = $schema->get_rest_schema();

    // The active boolean flows through get_defaults() → get_rest_schema().
    expect($rest['properties']['cdn']['properties']['active']['type'])->toBe('boolean');
});

it('flattens all sections via get_all_sections()', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'tab1',
                'title' => 'Tab 1',
                'sections' => [
                    ['id' => 'a', 'title' => 'A', 'fields' => []],
                    ['id' => 'b', 'title' => 'B', 'fields' => []],
                ],
            ],
            [
                'name' => 'tab2',
                'title' => 'Tab 2',
                'sections' => [
                    ['id' => 'c', 'title' => 'C', 'fields' => []],
                ],
            ],
        ],
    ]);

    $sections = $schema->get_all_sections();

    expect($sections)->toHaveCount(3);
    expect(array_column($sections, 'id'))->toBe(['a', 'b', 'c']);
});

it('combines active and status config in the same section', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'general',
                'title' => 'General',
                'sections' => [
                    [
                        'id'     => 'redis',
                        'title'  => 'Redis',
                        'active' => 'redis.enabled',
                        'status' => [
                            'key'       => 'redis.connected',
                            'ok'        => true,
                            'indicator' => true,
                            'badge'     => ['ok' => 'Connected', 'error' => 'Disconnected'],
                        ],
                        'fields' => [
                            ['key' => 'redis.host', 'type' => 'text', 'default' => '127.0.0.1'],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $client  = $schema->to_client_array();
    $section = $client['tabs'][0]['sections'][0];

    // Both active and status are present.
    expect($section['active']['key'])->toBe('redis.enabled');
    expect($section['status']['badge']['ok'])->toBe('Connected');

    // Defaults include both field and active-toggle.
    $defaults = $schema->get_defaults();
    expect($defaults['redis'])->toBe([
        'host'    => '127.0.0.1',
        'enabled' => false,
    ]);
});

it('preserves order of distinct tabs and sections', function () {
    $schema = new Schema([
        'tabs' => [
            [
                'name' => 'general',
                'title' => 'General',
                'sections' => [
                    ['id' => 'a', 'title' => 'A', 'fields' => []],
                    ['id' => 'b', 'title' => 'B', 'fields' => []],
                ],
            ],
            [
                'name' => 'advanced',
                'title' => 'Advanced',
                'sections' => [
                    ['id' => 'c', 'title' => 'C', 'fields' => []],
                ],
            ],
        ],
    ]);

    $client = $schema->to_client_array();

    expect($client['tabs'])->toHaveCount(2);
    expect($client['tabs'][0]['name'])->toBe('general');
    expect($client['tabs'][1]['name'])->toBe('advanced');
    expect($client['tabs'][0]['sections'])->toHaveCount(2);
    expect($client['tabs'][0]['sections'][0]['id'])->toBe('a');
    expect($client['tabs'][0]['sections'][1]['id'])->toBe('b');
});
