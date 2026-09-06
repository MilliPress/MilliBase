<?php

use MilliBase\REST\Controller as RestController;
use MilliBase\Settings;

// ─── Stubs ──────────────────────────────────────────────────────────

if (! class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
    }
}

if (! class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public array $data;
        public int $status;

        public function __construct(array $data = [], int $status = 200)
        {
            $this->data   = $data;
            $this->status = $status;
        }

        public function get_data(): array
        {
            return $this->data;
        }

        public function get_status(): int
        {
            return $this->status;
        }
    }
}

if (! function_exists('apply_filters')) {
    function apply_filters(string $hook, ...$args)
    {
        return $args[0];
    }
}

if (! function_exists('register_rest_route')) {
    function register_rest_route(string $namespace, string $route, array $args = []): bool
    {
        $GLOBALS['__milli_test_rest_routes'][] = [
            'namespace' => $namespace,
            'route'     => $route,
            'args'      => $args,
        ];
        return true;
    }
}

if (! class_exists('WP_REST_Server')) {
    class WP_REST_Server
    {
        public const READABLE  = 'GET';
        public const CREATABLE = 'POST';
    }
}

if (! function_exists('get_transient')) {
    function get_transient(string $key)
    {
        return false;
    }
}

if (! function_exists('get_option')) {
    function get_option(string $key, $default = false)
    {
        return $default;
    }
}

// ─── Helpers ────────────────────────────────────────────────────────

function make_controller(array $config = [], ?Settings $settings = null): RestController
{
    $settings = $settings ?? new Settings([
        'slug' => 'test',
        'defaults' => [
            'cache'   => ['enabled' => true, 'ttl' => 3600],
            'storage' => ['host' => 'localhost'],
        ],
    ]);

    return new RestController(
        array_merge(['slug' => 'test'], $config),
        $settings,
    );
}

function call_get_status(RestController $controller): array
{
    $response = $controller->get_status(new WP_REST_Request());

    return $response->get_data();
}

// ─── get_status(): no status config ─────────────────────────────────

it('returns settings metadata when no status config is set', function () {
    $data = call_get_status(make_controller());

    expect($data)->toHaveKey('settings');
    expect($data['settings'])->toHaveKeys(['has_defaults', 'has_backup', 'constants']);
});

it('returns no extra keys when status is absent', function () {
    $data = call_get_status(make_controller());

    // Only the settings key should be present.
    expect(array_keys($data))->toBe(['settings']);
});

// ─── get_status(): status.data only ─────────────────────────────────

it('merges static status data', function () {
    $controller = make_controller([
        'status' => [
            'data' => ['version' => '1.0.0', 'feature' => true],
        ],
    ]);

    $data = call_get_status($controller);

    expect($data['version'])->toBe('1.0.0');
    expect($data['feature'])->toBeTrue();
    expect($data)->toHaveKey('settings');
});

// ─── get_status(): status.callback only ─────────────────────────────

it('merges callback output', function () {
    $controller = make_controller([
        'status' => [
            'callback' => function () {
                return ['healthy' => true, 'uptime' => 42];
            },
        ],
    ]);

    $data = call_get_status($controller);

    expect($data['healthy'])->toBeTrue();
    expect($data['uptime'])->toBe(42);
});

it('passes the request to the callback', function () {
    $received = null;

    $controller = make_controller([
        'status' => [
            'callback' => function ($request) use (&$received) {
                $received = $request;
                return [];
            },
        ],
    ]);

    call_get_status($controller);

    expect($received)->toBeInstanceOf(WP_REST_Request::class);
});

// ─── get_status(): data + callback ──────────────────────────────────

it('merges data and callback with callback winning on conflicts', function () {
    $controller = make_controller([
        'status' => [
            'data' => [
                'version' => '1.0.0',
                'healthy' => false,
            ],
            'callback' => function () {
                return ['healthy' => true, 'uptime' => 99];
            },
        ],
    ]);

    $data = call_get_status($controller);

    expect($data['version'])->toBe('1.0.0');  // from data
    expect($data['healthy'])->toBeTrue();      // callback overwrites
    expect($data['uptime'])->toBe(99);         // from callback
});

// ─── get_status(): constants are included ───────────────────────────

it('includes constant overrides in settings', function () {
    define('RCTEST_CACHE_TTL', 9999);

    $settings = new Settings([
        'slug'            => 'test',
        'constant_prefix' => 'RCTEST',
        'defaults'        => [
            'cache' => ['ttl' => 3600],
        ],
    ]);

    $controller = make_controller([], $settings);
    $data       = call_get_status($controller);

    expect($data['settings']['constants']['cache']['ttl'])->toBe(9999);
});

// ─── get_status(): callback exception ───────────────────────────────

it('returns error response when callback throws', function () {
    $controller = make_controller([
        'status' => [
            'callback' => function () {
                throw new \RuntimeException('Connection failed');
            },
        ],
    ]);

    $response = $controller->get_status(new WP_REST_Request());

    expect($response->get_status())->toBe(500);
    expect($response->get_data()['success'])->toBeFalse();
    expect($response->get_data()['message'])->toBe('Connection failed');
});

it('fires {slug}_rest_status_response with $is_network as the third argument', function () {
    $GLOBALS['__milli_test_filters'] = [];

    $captured = [];
    add_filter('test_rest_status_response', function ($status, $request, $is_network) use (&$captured) {
        $captured[] = [$status, $request, $is_network];
        return $status;
    });

    call_get_status(make_controller());
    call_get_status(make_controller(['network' => true]));

    expect($captured)->toHaveCount(2);
    expect($captured[0][2])->toBeFalse();
    expect($captured[1][2])->toBeTrue();
});

it('registers REST routes without a prefix in default (per-site) mode', function () {
    $GLOBALS['__milli_test_rest_routes'] = [];
    make_controller(['rest_namespace' => 'millicache/v1'])->register_routes();

    $paths = array_column($GLOBALS['__milli_test_rest_routes'], 'route');
    expect($paths)->toContain('/settings');
    expect($paths)->toContain('/settings/actions');
    expect($paths)->toContain('/status');
});

it('prefixes REST routes with /network when the Manager runs in network mode', function () {
    $GLOBALS['__milli_test_rest_routes'] = [];
    make_controller([
        'rest_namespace' => 'millicache/v1',
        'network'        => true,
    ])->register_routes();

    $paths = array_column($GLOBALS['__milli_test_rest_routes'], 'route');
    expect($paths)->toContain('/network/settings');
    expect($paths)->toContain('/network/settings/actions');
    expect($paths)->toContain('/network/status');

    // Same namespace is reused — only the route path differs from the site Manager.
    $namespaces = array_unique(array_column($GLOBALS['__milli_test_rest_routes'], 'namespace'));
    expect($namespaces)->toBe(['millicache/v1']);
});

it('applies the /network prefix to custom action routes too', function () {
    $GLOBALS['__milli_test_rest_routes'] = [];
    make_controller([
        'rest_namespace' => 'millicache/v1',
        'network'        => true,
        'actions'        => [
            [
                'name'     => 'purge',
                'endpoint' => '/purge',
                'callback' => static fn () => true,
            ],
        ],
    ])->register_routes();

    $paths = array_column($GLOBALS['__milli_test_rest_routes'], 'route');
    expect($paths)->toContain('/network/purge');
});

// ─── enc_ secret masking (security) ─────────────────────────────────

// Mirrors the private Settings::SECRET_MASK sentinel (20 bullets).
const SECRET_MASK = "••••••••••••••••••••";

function enc_settings(): Settings
{
    return new Settings([
        'slug'     => 'test',
        'defaults' => [
            'license' => ['enc_key' => ''],
            'cache'   => ['ttl' => 3600],
        ],
    ]);
}

function settings_request(array $body): WP_REST_Request
{
    return new class ($body) extends WP_REST_Request {
        public function get_json_params(): array
        {
            return $this->get_params();
        }
    };
}

it('masks a stored enc_ value on GET /settings and never leaks the plaintext', function () {
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'super-secret-license'],
    ];

    $data = make_controller([], enc_settings())->get_settings_value()->get_data();

    expect($data['license']['enc_key'])->toBe(SECRET_MASK);
    expect(json_encode($data))->not->toContain('super-secret-license');
});

it('returns an empty string for an unset enc_ value (configured vs. not)', function () {
    $data = make_controller([], enc_settings())->get_settings_value()->get_data();

    expect($data['license']['enc_key'])->toBe('');
});

it('keeps the stored secret when an unrelated setting is saved (mask round-trips)', function () {
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'stored-secret'],
        'cache'   => ['ttl' => 3600],
    ];

    $request = settings_request([
        'license' => ['enc_key' => SECRET_MASK],
        'cache'   => ['ttl' => 7200],
    ]);

    make_controller([], enc_settings())->save_settings_value($request);

    expect($GLOBALS['__milli_test_options']['test']['license']['enc_key'])->toBe('stored-secret');
    expect($GLOBALS['__milli_test_options']['test']['cache']['ttl'])->toBe(7200);
});

it('clears the stored secret when the enc_ field is submitted empty', function () {
    // KeyField/PasswordField render the server's masked value as the input's
    // PLACEHOLDER, not its value — so an untouched field round-trips the mask
    // (preserved via bullet-detection) and the only way to produce an empty
    // submission is the explicit × clear button or the user actively deleting
    // a typed value. Both express intent to clear, so empty → clear.
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'stored-secret'],
    ];

    $request = settings_request(['license' => ['enc_key' => '']]);

    make_controller([], enc_settings())->save_settings_value($request);

    expect($GLOBALS['__milli_test_options']['test']['license']['enc_key'])->toBe('');
});

it('persists a genuinely new enc_ value typed by the admin', function () {
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'old-secret'],
    ];

    $request = settings_request(['license' => ['enc_key' => 'brand-new-key']]);

    make_controller([], enc_settings())->save_settings_value($request);

    expect($GLOBALS['__milli_test_options']['test']['license']['enc_key'])->toBe('brand-new-key');
});

it('never writes the mask sentinel as a value when nothing is stored', function () {
    $request = settings_request(['license' => ['enc_key' => SECRET_MASK]]);

    make_controller([], enc_settings())->save_settings_value($request);

    expect($GLOBALS['__milli_test_options']['test']['license']['enc_key'])->toBe('');
});

it('masks enc_ constant values on GET /status but keeps the key and non-secret values', function () {
    define('SECMASK_LICENSE_ENC_KEY', 'plaintext-license-from-constant');
    define('SECMASK_CACHE_TTL', 4242);

    $settings = new Settings([
        'slug'            => 'test',
        'constant_prefix' => 'SECMASK',
        'defaults'        => [
            'license' => ['enc_key' => ''],
            'cache'   => ['ttl' => 3600],
        ],
    ]);

    $data      = call_get_status(make_controller([], $settings));
    $constants = $data['settings']['constants'];

    // Secret constant is masked, plaintext never leaves the server …
    expect($constants['license']['enc_key'])->toBe(SECRET_MASK);
    expect(json_encode($constants))->not->toContain('plaintext-license-from-constant');
    // … but the key is still present so the client keeps the field disabled …
    expect($constants['license'])->toHaveKey('enc_key');
    // … and non-secret constant values pass through untouched.
    expect($constants['cache']['ttl'])->toBe(4242);
});

// ─── enc_ partial mask (type:key opt-in recognition) ────────────────

// A controller whose schema declares license.enc_key as a type:key field.
function key_controller(Settings $settings): RestController
{
    return make_controller([
        'tabs' => [[
            'name'     => 'settings',
            'sections' => [[
                'id'     => 'license',
                'fields' => [
                    ['key' => 'license.enc_key', 'type' => 'key'],
                ],
            ]],
        ]],
    ], $settings);
}

it('reveals the leading/trailing chars of a type:key enc_ value on GET /settings', function () {
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
    ];

    $data = key_controller(enc_settings())->get_settings_value()->get_data();

    // First 4 + bullets matching the real middle (18) + last 4. Plaintext never leaks.
    expect($data['license']['enc_key'])->toBe('ABCD' . str_repeat('•', 18) . 'WXYZ');
    expect(json_encode($data))->not->toContain('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
});

it('renders the partial mask at the input length (only mask:"full" hides length)', function () {
    // Default partial mask preserves the input's length so admins can compare
    // shapes. mask:'full' is the only mode that hides it — covered separately.
    $short = str_repeat('x', 14) . 'TAIL';   // 18 chars
    $long  = str_repeat('y', 60) . 'TAIL';   // 64 chars

    $GLOBALS['__milli_test_options']['test'] = ['license' => ['enc_key' => $short]];
    $a = key_controller(enc_settings())->get_settings_value()->get_data();

    $GLOBALS['__milli_test_options']['test'] = ['license' => ['enc_key' => $long]];
    $b = key_controller(enc_settings())->get_settings_value()->get_data();

    expect(mb_strlen($a['license']['enc_key']))->toBe(18);
    expect(mb_strlen($b['license']['enc_key']))->toBe(64);
});

it('falls back to the full mask for a type:key value too short to reveal safely', function () {
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'short'],
    ];

    $data = key_controller(enc_settings())->get_settings_value()->get_data();

    expect($data['license']['enc_key'])->toBe(SECRET_MASK);
});

it('keeps the stored secret when the partial mask round-trips on save', function () {
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
    ];

    // Client submits the exact value it received (the input-length partial mask).
    $request = settings_request([
        'license' => ['enc_key' => 'ABCD' . str_repeat('•', 18) . 'WXYZ'],
    ]);

    key_controller(enc_settings())->save_settings_value($request);

    expect($GLOBALS['__milli_test_options']['test']['license']['enc_key'])->toBe('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
});

it('keeps enc_ fields fully masked when the schema does not declare them as type:key', function () {
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
    ];

    // No tabs/fields in config → empty mask map → full mask.
    $data = make_controller([], enc_settings())->get_settings_value()->get_data();

    expect($data['license']['enc_key'])->toBe(SECRET_MASK);
});

it('keeps a type:key field fully masked when its mask is set to "full"', function () {
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
    ];

    $controller = make_controller([
        'tabs' => [[
            'name'     => 'settings',
            'sections' => [[
                'id'     => 'license',
                'fields' => [
                    ['key' => 'license.enc_key', 'type' => 'key', 'mask' => 'full'],
                ],
            ]],
        ]],
    ], enc_settings());

    $data = $controller->get_settings_value()->get_data();

    expect($data['license']['enc_key'])->toBe(SECRET_MASK);
});

it('preserves non-alphanumeric separators in the masked middle when mask => "structured"', function () {
    // Structured mode mirrors the input's shape — dashes pass through,
    // alphanumerics become bullets. So "MILLI-AAAA-BBBB-CCCC-DDDD" reads
    // back as "MILL•-••••-••••-••••-DDDD" with the default first 4 / last 4.
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'MILLI-AAAA-BBBB-CCCC-DDDD'],
    ];

    $controller = make_controller([
        'tabs' => [[
            'name'     => 'settings',
            'sections' => [[
                'id'     => 'license',
                'fields' => [
                    ['key' => 'license.enc_key', 'type' => 'key', 'mask' => 'structured'],
                ],
            ]],
        ]],
    ], enc_settings());

    $data = $controller->get_settings_value()->get_data();

    expect($data['license']['enc_key'])->toBe('MILL•-••••-••••-••••-DDDD');
    expect(json_encode($data))->not->toContain('MILLI-AAAA-BBBB-CCCC-DDDD');
});

it('honors custom first/last alongside the structured flag', function () {
    // first=5, last=4, structured=true → "MILLI-••••-••••-••••-DDDD"
    // Lets users reveal a whole leading segment for keys with a known prefix.
    $GLOBALS['__milli_test_options']['test'] = [
        'license' => ['enc_key' => 'MILLI-AAAA-BBBB-CCCC-DDDD'],
    ];

    $controller = make_controller([
        'tabs' => [[
            'name'     => 'settings',
            'sections' => [[
                'id'     => 'license',
                'fields' => [
                    [
                        'key'  => 'license.enc_key',
                        'type' => 'key',
                        'mask' => ['first' => 5, 'last' => 4, 'structured' => true],
                    ],
                ],
            ]],
        ]],
    ], enc_settings());

    $data = $controller->get_settings_value()->get_data();

    expect($data['license']['enc_key'])->toBe('MILLI-••••-••••-••••-DDDD');
});

// ─── pattern validation on save ─────────────────────────────────────

function pattern_controller(): RestController
{
    $settings = new Settings([
        'slug'     => 'test',
        'defaults' => [
            'storage' => ['prefix' => 'mll'],
        ],
    ]);

    return make_controller([
        'tabs' => [[
            'name'     => 'settings',
            'sections' => [[
                'id'     => 'storage',
                'fields' => [
                    [
                        'key'     => 'storage.prefix',
                        'type'    => 'text',
                        'label'   => 'Key Prefix',
                        'default' => 'mll',
                        'pattern' => '^[A-Za-z0-9_-]{1,32}$',
                    ],
                ],
            ]],
        ]],
    ], $settings);
}

it('rejects a save whose value fails the field pattern and persists nothing', function () {
    $GLOBALS['__milli_test_options']['test'] = ['storage' => ['prefix' => 'mll']];

    $result = pattern_controller()->save_settings_value(settings_request(['storage' => ['prefix' => 'bad value']]));

    expect($result)->toBeInstanceOf(WP_Error::class);
    expect($result->get_error_code())->toBe('invalid_settings_value');
    expect($result->get_error_message())->toBe('Key Prefix contains characters that are not allowed.');
    expect($result->get_error_data())->toBe([
        'status' => 400,
        'errors' => ['storage.prefix' => 'Key Prefix contains characters that are not allowed.'],
    ]);
    expect($GLOBALS['__milli_test_options']['test']['storage']['prefix'])->toBe('mll');
});

it('persists a save whose value matches the field pattern', function () {
    $GLOBALS['__milli_test_options']['test'] = ['storage' => ['prefix' => 'mll']];

    $result = pattern_controller()->save_settings_value(settings_request(['storage' => ['prefix' => 'shop_a-1']]));

    expect($result)->toBeInstanceOf(WP_REST_Response::class);
    expect($GLOBALS['__milli_test_options']['test']['storage']['prefix'])->toBe('shop_a-1');
});
