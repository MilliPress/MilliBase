<?php

use MilliBase\Abilities\Controller as AbilitiesController;
use MilliBase\Settings;

function make_abilities_controller(array $config = [], ?Settings $settings = null): AbilitiesController
{
    $config = array_merge(['slug' => 'test'], $config);

    $settings = $settings ?? new Settings([
        'slug'     => is_string($config['slug']) && '' !== $config['slug'] ? $config['slug'] : 'test',
        'defaults' => [],
    ]);

    return new AbilitiesController($config, $settings);
}

function abilities_calls(string $fn): array
{
    return array_values(array_filter(
        $GLOBALS['millibase_abilities_calls'],
        fn ($call) => $call['fn'] === $fn,
    ));
}

function action_callbacks_for(object $instance, string $hook): array
{
    $methods = [];
    foreach ($GLOBALS['__milli_test_actions'][$hook] ?? [] as $by_priority) {
        foreach ($by_priority as $callback) {
            if (is_array($callback) && ($callback[0] ?? null) === $instance) {
                $methods[] = $callback[1];
            }
        }
    }
    return $methods;
}

function valid_ability(array $overrides = []): array
{
    return array_merge(
        [
            'id'          => 'foo',
            'label'       => 'Foo',
            'description' => 'Foo ability for tests.',
            'callback'    => fn () => true,
        ],
        $overrides,
    );
}


it('hooks register_category to wp_abilities_api_categories_init and register_abilities to wp_abilities_api_init', function () {
    $controller = make_abilities_controller();
    $controller->register_hooks();

    expect(action_callbacks_for($controller, 'wp_abilities_api_categories_init'))
        ->toContain('register_category');
    expect(action_callbacks_for($controller, 'wp_abilities_api_init'))
        ->toContain('register_abilities');
});


it('registers a category for the plugin slug with the menu_title as label', function () {
    make_abilities_controller(['menu_title' => 'My Plugin'])->register_category();

    $calls = abilities_calls('wp_register_ability_category');

    expect($calls)->toHaveCount(1);
    expect($calls[0]['slug'])->toBe('test');
    expect($calls[0]['args']['label'])->toBe('My Plugin');
});

it('falls back to the slug as the category label when no menu_title is set', function () {
    make_abilities_controller()->register_category();

    expect(abilities_calls('wp_register_ability_category')[0]['args']['label'])->toBe('test');
});

it('always passes a non-empty description to the category registration', function () {
    make_abilities_controller(['menu_title' => 'My Plugin'])->register_category();

    $description = abilities_calls('wp_register_ability_category')[0]['args']['description'];

    expect($description)->toBeString();
    expect($description)->not->toBe('');
    expect($description)->toContain('My Plugin');
});

it('honours the abilities_category override for label and description', function () {
    $config = [
        'menu_title'         => 'My Plugin',
        'abilities_category' => [
            'label'       => 'Custom Label',
            'description' => 'Custom description for AI agents.',
        ],
    ];

    make_abilities_controller($config)->register_category();
    $args = abilities_calls('wp_register_ability_category')[0]['args'];

    expect($args['label'])->toBe('Custom Label');
    expect($args['description'])->toBe('Custom description for AI agents.');
});

it('falls back per-field when only one of label/description is overridden', function () {
    $config = [
        'menu_title'         => 'My Plugin',
        'abilities_category' => ['description' => 'Only description overridden.'],
    ];

    make_abilities_controller($config)->register_category();
    $args = abilities_calls('wp_register_ability_category')[0]['args'];

    expect($args['label'])->toBe('My Plugin');
    expect($args['description'])->toBe('Only description overridden.');
});

it('skips category registration when the slug is empty', function () {
    make_abilities_controller(['slug' => ''])->register_category();

    expect(abilities_calls('wp_register_ability_category'))->toBe([]);
});

it('skips category registration when the slug fails the abilities-api regex', function () {
    foreach (['my_plugin', 'My-Plugin', 'plugin.foo', 'plugin/foo', '-leading', 'trailing-'] as $slug) {
        $GLOBALS['millibase_abilities_calls']      = [];
        $GLOBALS['millibase_abilities_categories'] = [];
        make_abilities_controller(['slug' => $slug])->register_category();
        expect(abilities_calls('wp_register_ability_category'))->toBe([], "expected no registration for slug '{$slug}'");
    }
});

it('skips category registration when the category is already registered', function () {
    $GLOBALS['millibase_abilities_categories']['test'] = true;

    make_abilities_controller(['menu_title' => 'Test'])->register_category();

    expect(abilities_calls('wp_register_ability_category'))->toBe([]);
});


it('registers nothing when the abilities config key is missing', function () {
    make_abilities_controller()->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('registers nothing when the abilities array is empty', function () {
    make_abilities_controller(['abilities' => []])->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('treats a non-array abilities config as empty', function () {
    make_abilities_controller(['abilities' => 'not-array'])->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('skips non-array entries inside the abilities list', function () {
    $config = [
        'abilities' => [
            'not-an-array',
            valid_ability(),
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toHaveCount(1);
});

it('skips entries without id', function () {
    $config = [
        'abilities' => [
            valid_ability(['id' => null]),
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('skips entries with an empty label', function () {
    $config = [
        'abilities' => [
            valid_ability(['label' => '']),
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('skips entries with an empty description', function () {
    $config = [
        'abilities' => [
            valid_ability(['description' => '']),
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('skips entries without a callable callback', function () {
    $config = [
        'abilities' => [
            valid_ability(['callback' => 'not_a_function']),
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('skips ability entries whose resolved name fails the abilities-api regex', function () {
    $config = [
        'abilities' => [
            valid_ability(['id' => 'Cache_Purge']),               // uppercase + underscore
            valid_ability(['id' => 'cache.stats']),               // dot
            valid_ability(['id' => '-leading']),                  // leading dash
            valid_ability(['id' => 'foo/bar/baz']),               // multi-slash (more than two segments)
            valid_ability(['id' => 'other-plugin/Bad_Id']),       // explicit namespace, invalid second segment
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('skips ability registration entirely when the slug fails the abilities-api regex', function () {
    $config = [
        'slug'      => 'my_plugin',
        'abilities' => [valid_ability()],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('skips abilities that are already registered', function () {
    $GLOBALS['millibase_abilities_names']['test/foo'] = true;

    $config = [
        'abilities' => [valid_ability()],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toBe([]);
});

it('accepts non-closure callables (e.g. global function names, method arrays)', function () {
    if (! function_exists('millibase_test_ability_callback')) {
        function millibase_test_ability_callback(): string
        {
            return 'global-fn-was-called';
        }
    }

    $config = [
        'abilities' => [
            valid_ability(['callback' => 'millibase_test_ability_callback']),
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability'))->toHaveCount(1);
    $wrapped = abilities_calls('wp_register_ability')[0]['args']['execute_callback'];
    expect($wrapped)->toBeCallable();
    expect($wrapped(null))->toBe('global-fn-was-called');
});


it('does not prepend framework settings abilities by default', function () {
    $config = [
        'abilities' => [valid_ability()],
    ];

    make_abilities_controller($config)->register_abilities();
    $names = array_column(abilities_calls('wp_register_ability'), 'name');

    expect($names)->toBe(['test/foo']);
});

it('appends framework settings abilities when expose_settings_abilities is true', function () {
    $config = [
        'expose_settings_abilities' => true,
        'abilities' => [valid_ability()],
    ];

    make_abilities_controller($config)->register_abilities();
    $names = array_column(abilities_calls('wp_register_ability'), 'name');

    expect($names)->toBe([
        'test/foo',
        'test/settings-export',
        'test/settings-reset',
        'test/settings-backup',
        'test/settings-restore',
    ]);
});

it('lets a host-plugin ability override a framework ability with the same id', function () {
    $custom = static function () {
        return ['custom' => true];
    };
    $config = [
        'expose_settings_abilities' => true,
        'abilities' => [
            valid_ability([
                'id'          => 'settings-export',
                'label'       => 'Custom export',
                'description' => 'Plugin-specific export.',
                'callback'    => $custom,
            ]),
        ],
    ];

    make_abilities_controller($config)->register_abilities();
    $registered = abilities_calls('wp_register_ability');
    $by_name    = array_column($registered, null, 'name');

    // Controller wraps the user callback for exception isolation; verify
    // the wrapper delegates to the host plugin's callback.
    $wrapped = $by_name['test/settings-export']['args']['execute_callback'];
    expect($wrapped(null))->toBe(['custom' => true]);
    expect($by_name['test/settings-export']['args']['label'])->toBe('Custom export');
});

it('does not prepend framework settings abilities for non-strict-true values', function () {
    foreach ([1, '1', 'true', 'yes', [], false] as $non_strict_true) {
        $GLOBALS['millibase_abilities_calls'] = [];
        $GLOBALS['millibase_abilities_names'] = [];

        $config = [
            'expose_settings_abilities' => $non_strict_true,
            'abilities' => [valid_ability()],
        ];

        make_abilities_controller($config)->register_abilities();
        $names = array_column(abilities_calls('wp_register_ability'), 'name');

        expect($names)->toBe(['test/foo'], 'expected no framework prepend for ' . var_export($non_strict_true, true));
    }
});

it('does not append framework settings abilities when expose_settings_abilities is explicitly false', function () {
    $config = [
        'expose_settings_abilities' => false,
        'abilities' => [valid_ability()],
    ];

    make_abilities_controller($config)->register_abilities();
    $names = array_column(abilities_calls('wp_register_ability'), 'name');

    expect($names)->toBe(['test/foo']);
});


it('prefixes a bare id with the plugin slug', function () {
    $config = [
        'abilities' => [
            valid_ability(['id' => 'cache-purge']),
        ],
    ];

    make_abilities_controller($config)->register_abilities();
    $calls = abilities_calls('wp_register_ability');

    expect($calls)->toHaveCount(1);
    expect($calls[0]['name'])->toBe('test/cache-purge');
});

it('keeps an explicit namespace verbatim when the id contains a forward slash', function () {
    $config = [
        'abilities' => [
            valid_ability(['id' => 'other-plugin/something']),
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability')[0]['name'])->toBe('other-plugin/something');
});

it('uses the plugin slug as the category for each ability', function () {
    $config = [
        'abilities' => [valid_ability()],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability')[0]['args']['category'])->toBe('test');
});

it('passes label and description through verbatim', function () {
    $config = [
        'abilities' => [
            valid_ability([
                'label'       => 'Purge Cache',
                'description' => 'Clears the cache for one or more targets.',
            ]),
        ],
    ];

    make_abilities_controller($config)->register_abilities();
    $args = abilities_calls('wp_register_ability')[0]['args'];

    expect($args['label'])->toBe('Purge Cache');
    expect($args['description'])->toBe('Clears the cache for one or more targets.');
});


it('inherits the plugin-default capability when the ability has none', function () {
    $config = [
        'capability' => 'manage_options',
        'abilities'  => [valid_ability()],
    ];

    make_abilities_controller($config)->register_abilities();
    $callback = abilities_calls('wp_register_ability')[0]['args']['permission_callback'];

    $GLOBALS['millibase_abilities_can']['manage_options'] = true;
    expect($callback())->toBeTrue();

    $GLOBALS['millibase_abilities_can']['manage_options'] = false;
    expect($callback())->toBeFalse();
});

it('uses a per-ability capability string when provided', function () {
    $config = [
        'capability' => 'manage_options',
        'abilities'  => [
            valid_ability(['capability' => 'edit_posts']),
        ],
    ];

    make_abilities_controller($config)->register_abilities();
    $callback = abilities_calls('wp_register_ability')[0]['args']['permission_callback'];

    $GLOBALS['millibase_abilities_can']['edit_posts']     = true;
    $GLOBALS['millibase_abilities_can']['manage_options'] = false;

    expect($callback())->toBeTrue();
});

it('wraps host-plugin callbacks so a thrown exception becomes a WP_Error instead of bubbling out', function () {
    $config = [
        'abilities' => [
            valid_ability([
                'callback' => static function () {
                    throw new \RuntimeException('sensitive: db_password=hunter2');
                },
            ]),
        ],
    ];

    make_abilities_controller($config)->register_abilities();
    $wrapped = abilities_calls('wp_register_ability')[0]['args']['execute_callback'];

    $result = $wrapped(null);

    expect($result)->toBeInstanceOf(\WP_Error::class);
    expect($result->get_error_code())->toBe('ability_callback_exception');
    expect($result->get_error_message())->not->toContain('hunter2');
});

it('uses an explicit permission_callback when one is supplied', function () {
    $custom = fn (): bool => true;
    $config = [
        'abilities' => [
            valid_ability(['permission_callback' => $custom]),
        ],
    ];

    make_abilities_controller($config)->register_abilities();

    expect(abilities_calls('wp_register_ability')[0]['args']['permission_callback'])->toBe($custom);
});


it('passes input_schema, output_schema, and meta through unchanged', function () {
    $meta   = ['show_in_rest' => true, 'annotations' => ['readonly' => true]];
    $config = [
        'abilities' => [
            valid_ability([
                'input_schema'  => ['type' => 'string'],
                'output_schema' => ['type' => 'object'],
                'meta'          => $meta,
            ]),
        ],
    ];

    make_abilities_controller($config)->register_abilities();
    $args = abilities_calls('wp_register_ability')[0]['args'];

    expect($args['input_schema'])->toBe(['type' => 'string']);
    expect($args['output_schema'])->toBe(['type' => 'object']);
    expect($args['meta'])->toBe($meta);
});

it('omits input_schema, output_schema, and meta when not configured', function () {
    $config = [
        'abilities' => [valid_ability()],
    ];

    make_abilities_controller($config)->register_abilities();
    $args = abilities_calls('wp_register_ability')[0]['args'];

    expect($args)->not->toHaveKey('input_schema');
    expect($args)->not->toHaveKey('output_schema');
    expect($args)->not->toHaveKey('meta');
});
