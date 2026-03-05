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
