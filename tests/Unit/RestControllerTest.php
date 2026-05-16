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
